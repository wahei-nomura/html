<?php
/**
 * class-n2-rakuten-sftp.php
 * SFTP対応版
 *
 * @package neoneng
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'N2_Rakuten_SFTP' ) ) {
	new N2_Rakuten_SFTP();
	return;
}


/**
 * 楽天SFTP
 */
class N2_Rakuten_SFTP {

	/**
	 * データ
	 *
	 * @var array
	 */
	protected $data = array(
		'connect' => null,
		'params'  => array(),
		'files'   => null,
		'data'    => array(),
		'error'   => array(),
		'log'     => array(),
	);

	/**
	 * SFTP
	 *
	 * @var WP_Filesystem_SSH2|null
	 */
	public $sftp = null;

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'wp_ajax_n2_upload_to_rakuten_sftp', array( $this, 'upload_to_rakuten' ) );
	}
	public function __destruct() {

	}

	/**
	 * N2 SFTP　メニューの追加
	 */
	public function add_menu() {
		global $n2;
		if ( isset( $n2->portal_setting['楽天'] ) ) {
			add_menu_page( '楽天SFTP', 'n2_rakuten_sftp', 'ss_crew', 'n2_rakuten_sftp_upload', array( $this, 'display_ui' ), 'dashicons-admin-site-alt3' );
			add_submenu_page( 'n2_rakuten_sftp_upload', '楽天エラーログ', '楽天エラーログ', 'ss_crew', 'n2_rakuten_sftp_error_log', array( $this, 'display_ui' ) );
		}
	}

	/**
	 * SFTP UI
	 */
	public function display_ui() {
		$template = str_replace( array('n2_rakuten_sftp_','_'),	array('','-'), $_GET['page'] );
		$args = match ( $template ) {
			'error-log' => $this->rakuten_error_log_args(),
			default     => null,
		}
		?>
		<div class="wrap">
			<h1>楽天SFTP</h1>
			<?php get_template_part( 'template/admin-menu/sftp', $template, $args ); ?>
		</div>
		<?php
	}

	/**
	 * SFTP CONNECT
	 */
	public function connect( $args = null ) {
		// 初回時のみ接続確認
		if ( null !== $this->data['connect'] ) {
			return $this->data['connect'];
		}
		global $n2;

		$user = $args['user'] ?? $n2->portal_setting['楽天']['ftp_user'] ?? '';
		$pass = $args['pass'] ?? $n2->portal_setting['楽天']['ftp_pass'] ?? '';
		$this->check_fatal_error( $user && $pass, '楽天セットアップが完了していません。' );

		WP_Filesystem();
		require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-ssh2.php';

		$opt        = array(
			'hostname' => $args['hostname'] ?? $n2->portal_setting['楽天']['upload_server'],
			'username' => $user,
			'password' => $pass,
		);
		$this->sftp = new WP_Filesystem_SSH2( $opt );

		if ( ! $this->sftp->connect() ) {
			$opt['password'] = rtrim( $opt['password'], 1 ) . '2';
			$this->sftp      = new WP_Filesystem_SSH2( $opt );
		}

		$this->data['connect'] = $this->sftp->connect();

		return $this->data['connect'];
	}

	public function rakuten_error_log_args() {
		$args = array();
		$this->connect();
		$args['connect'] = $this->data['connect'];

		if ( ! $args['connect'] ) {
			return $args;
		}
		$dir = 
		$args['dir'] = 'ritem/logs';
		$args['logs'] = $this->sftp->dirlist( $args['dir'] );
		$args['logs'] = array_reverse( $args['logs'] );
		$args['logs'] = array_map(function( $log ) use( $args ) {
			$contents = $this->sftp->get_contents( "{$args['dir']}/{$log['name']}" );
			$contents = htmlspecialchars( mb_convert_encoding( $contents, 'utf-8', 'sjis' ) );
			return array(
				'name' => $log['name'],
				'time' => date('Y M d', $log['lastmodunix'] ),
				'contents' => $contents,
			);
		},$args['logs']);
		return $args;
	}

	/**
	 * 楽天への転送機能（超突貫）
	 *
	 * @return void
	 */
	public function upload_to_rakuten() {
		$this->check_fatal_error( $this->connect(), 'パスワードが違います' );
		$this->set_params();
		$this->set_files();
		$this->{$this->data['params']['judge']}();
		$this->log_output();
	}
	
	public function img_upload () {
		global $n2;
		$name = $this->data['files']['name'];
		$type = $this->data['files']['type'];
		$tmp_name = $this->data['files']['tmp_name'];
	
		$img_dir = rtrim( $n2->portal_setting['楽天']['img_dir'], '/' ) . '/';

		// テンポラリディレクトリ作成
		$tmp = wp_tempnam( __CLASS__, dirname( __DIR__ ) . '/' );
		unlink( $tmp );
		mkdir( $tmp );

		foreach ( $tmp_name as $k => $file ) {
			// 画像圧縮処理
			$quality = isset( $quality ) ? $quality : 50;
			move_uploaded_file( $file, "{$tmp}/{$name[$k]}" );
			exec( "mogrify -quality {$quality} {$tmp}/{$name[$k]}" );

			// jpg以外はエラー
			if ( strpos( $name[$k], '.jpg' ) === false ) {
				$this->data['log'][] = 'ファイル形式(jpeg)が違います :' . $name[ $k ];
				continue;
			}
			// $img_dir からキャビネットのディレクトリ構造を作成
			$remote_dir = preg_replace( '/^.*cabinet/', 'cabinet/images', $img_dir );
			preg_match( '/^([a-z0-9]{0,2})([a-z]{2})[0-9]{2,3}[-]*[0-9]*\.jpg/', $name[$k], $m );
			if ( ! ( $m[1] || $m[2] ) ) {
				$this->data['log'][] = 'ファイル名が違います :' . $name[ $k ];
				continue;
			}
			
			// 商品画像の場合
			if ( $this->sftp->mkdir( $remote_dir ) ) {
				$this->data['log'][] = "{$remote_dir}を作成\n";
			};
			$remote_dir .= $m[1] . $m[2];
			if ( $this->sftp->mkdir( $remote_dir ) ) {
				$this->data['log'][] = "{$remote_dir}を作成\n";
			};
			$remote_file = "{$remote_dir}/{$name[$k]}";
			$image_data  = file_get_contents("{$tmp}/{$name[$k]}");
			$this->data['log'][] = match ( $this->sftp->put_contents( $remote_file, $image_data ) ) {
				true => "転送成功 $name[$k]\n",
				default => "転送失敗 $name[$k]\n",
			};
		}
		exec( "rm -Rf {$tmp}" );
	}

	public function csv_upload () {
		$name = $this->data['files']['name'];
		$type = $this->data['files']['type'];
		$tmp_name = $this->data['files']['tmp_name'];

		foreach ( $tmp_name as $k => $file ) {
			if ( strpos( $name[$k], '.csv' ) === false ) {
				$this->data['log'][] = 'ファイル形式(csv)が違います :' . $name[ $k ];
				continue;
			}
			$remote_file = 'ritem/batch/' . $name[$k];
			$file_data   = file_get_contents( $file );
			$this->data['log'][] = match ( $this->sftp->put_contents( $remote_file, $file_data ) ) {
				true => "転送成功 $name[$k]\n",
				default => "転送失敗 $name[$k]\n",
			};
		}
	}

	public function log_output() {
		header( 'Content-Type: application/json; charset=utf-8' );
		foreach ( $this->data['log'] as $log ) {
			echo $log;
		}
		exit;
	}


	/**
	 * 各パラメータ配列の作成
	 *
	 * $args > $_GET > $_POST > $default
	 *
	 * @param array $args args
	 */
	private function set_params( $args = array() ) {
		// $_GETを引数で上書き
		$params = wp_parse_args( $args, $_GET );
		// $_POSTを$paramsで上書き
		if ( wp_verify_nonce( $_POST['n2nonce'] ?? '', 'n2nonce' ) ) {
			$params = wp_parse_args( $params, $_POST );
		}

		/**
		 * [hook] n2_rakuten_sftp_set_params
		 */
		$this->data['params'] = apply_filters( mb_strtolower( get_called_class() ) . '_set_params', $params );
	}
	/**
	 * ファイル配列の作成
	 *
	 */
	private function set_files() {
		setlocale( LC_ALL, 'ja_JP.UTF-8' );
		$this->check_fatal_error( $this->data['params']['judge'], '転送モードが設定されていません' );
		$this->data['files'] = $_FILES[ 'sftp_file' ];
		$this->check_fatal_error( $this->data['files']['tmp_name'][0], 'ファイルをセットしてください。' );
	}

	/**
	 * 致命的なエラーのチェック
	 *
	 * @param array  $data チェックするデータ
	 * @param string $message メッセージ
	 */
	protected function check_fatal_error( $data, $message ) {
		if ( ! $data ) {
			header( 'Content-Type: application/json; charset=utf-8' );
			echo $message;
			exit;
		}
	}

}
