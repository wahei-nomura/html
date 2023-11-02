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
	 * 設定項目
	 *
	 * @var array
	 */
	public $settings = array(
		'upload'    => 'アップロード',
		'error-log' => 'エラーログ',
		'client'    => 'CABINET',
	);

	/**
	 * データ
	 *
	 * @var array
	 */
	protected $data = array(
		'connect'          => null,
		'params'           => array(),
		'files'            => null,
		'n2data'           => array(),
		'error'            => array(),
		'log'              => array(),
		'rakuten_csv_name' => array( 'normal-item', 'item-cat' ),
		'extensions'       => '.csv',
		'insert_post'      => null,
		'post_type'        => 'n2_sftp',
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
		add_action( 'wp_ajax_n2_rakuten_sftp_upload_to_rakuten', array( $this, 'upload_to_rakuten' ) );
		add_action( 'wp_ajax_n2_rakuten_sftp_update_post', array( $this, 'update_post' ) );
		add_action( 'init', array( $this, 'register_post_type' ) );
	}
	public function __destruct() {
	}

	/**
	 * N2 SFTP　メニューの追加
	 */
	public function add_menu() {
		global $n2, $wp_filesystem;
		if ( isset( $n2->settings['楽天'] ) ) {
			add_menu_page( '楽天SFTP', '楽天SFTP', 'ss_crew', 'n2_rakuten_sftp', array( $this, 'display_ui' ), 'dashicons-admin-site-alt3' );

			foreach ( $this->settings as $page => $name ) {
				// 設定テンプレートの存在を確認して、ない場合は破棄してスキップする
				if ( ! $wp_filesystem->exists( get_theme_file_path( "template/admin-menu/sftp-{$page}.php" ) ) ) {
					unset( $this->settings[ $page ] );
					continue;
				}
				$menu_slug = $this->create_menu_slug( $page );
				add_submenu_page( 'n2_rakuten_sftp', $name, $name, 'ss_crew', $menu_slug, array( $this, 'display_ui' ) );
				register_setting( $menu_slug, $menu_slug );
			}
		}
	}

	/**
	 * メニュースラッグの作成
	 * ?page=n2_settings_xxxx
	 *
	 * @param string $page ページ
	 */
	private function create_menu_slug( $page ) {
		return 'n2_rakuten_sftp' . ( 'upload' === $page ? '' : "_{$page}" );
	}

	/**
	 * SFTP UI
	 */
	public function display_ui() {
		$template = $_GET['page'];
		$html     = array(
			'nav'      => '',
			'contents' => '',
			'args'     => null,
		);
		foreach ( $this->settings as $page => $name ) {
			$menu_slug    = $this->create_menu_slug( $page );
			$html['nav'] .= sprintf( '<a href="?page=%s" class="nav-tab%s">%s</a>', $menu_slug, $menu_slug === $template ? ' nav-tab-active' : '', $name );
			if ( $menu_slug === $template ) {
				$args = match ( $page ) {
					'error-log' => $this->error_log_args(),
					'upload'    => $this->upload_args(),
					default     => null,
				};
				ob_start();
				get_template_part( 'template/admin-menu/sftp', $page, $args );
				$html['contents'] = ob_get_clean();
			}
		}
		?>
		<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
		<div class="wrap">
			<h1>楽天SFTP</h1>
			<div id="crontrol-header">
				<nav class="nav-tab-wrapper"><?php echo $html['nav']; ?></nav>
			</div>
			<?php echo $html['contents']; ?>
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

		$user = $args['user'] ?? $n2->settings['楽天']['FTP']['user'] ?? '';
		$pass = $args['pass'] ?? $n2->settings['楽天']['FTP']['pass'] ?? '';
		$this->check_fatal_error( $user && $pass, '楽天セットアップが完了していません。' );

		WP_Filesystem();
		require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-ssh2.php';

		$opt        = array(
			'hostname' => $args['hostname'] ?? $n2->settings['楽天']['FTP']['upload_server'],
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

	/**
	 * エラーログテンプレート用の変数
	 */
	public function error_log_args() {
		$args = array();
		$this->connect();
		$args['connect'] = $this->data['connect'];

		if ( ! $args['connect'] ) {
			return $args;
		}
		$args['dir']  = 'ritem/logs';
		$args['logs'] = $this->sftp->dirlist( $args['dir'] );
		$args['logs'] = array_reverse( $args['logs'] );
		$args['logs'] = array_map(
			function ( $log ) use ( $args ) {
				$contents = $this->sftp->get_contents( "{$args['dir']}/{$log['name']}" );
				$contents = htmlspecialchars( mb_convert_encoding( $contents, 'utf-8', 'sjis' ) );
				return array(
					'name'     => $log['name'],
					'time'     => date( 'Y M d', $log['lastmodunix'] ),
					'contents' => $contents,
				);
			},
			$args['logs']
		);
		return $args;
	}

	/**
	 * アップロードテンプレート用の変数
	 */
	public function upload_args() {
		return array(
			'action'    => 'n2_rakuten_sftp_upload_to_rakuten',
			'radio'     => array(
				'img_upload' => '商品画像',
				'csv_upload' => '商品CSV',
			),
			'file'      => 'sftp_file[]',
			'post_type' => $this->data['post_type'],
		);
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
		$this->insert_post();
		$this->log_output();
	}

	public function img_upload() {
		global $n2;
		$name     = $this->data['files']['name'];
		$type     = $this->data['files']['type'];
		$tmp_name = $this->data['files']['tmp_name'];

		$img_dir = rtrim( $n2->settings['楽天']['商品画像ディレクトリ'], '/' ) . '/';

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
			if ( strpos( $name[ $k ], '.jpg' ) === false ) {
				$this->data['log'][] = 'ファイル形式(jpeg)が違います :' . $name[ $k ];
				continue;
			}
			// $img_dir からキャビネットのディレクトリ構造を作成
			$remote_dir = preg_replace( '/^.*cabinet/', 'cabinet/images', $img_dir );
			preg_match( '/^([0-9]{0,2}[a-z]{2,4})([0-9]{2,3})[-]*[0-9]*\.jpg/', $name[ $k ], $m );
			if ( ! ( $m[1] ) ) {
				$this->data['log'][] = 'ファイル名が違います :' . $name[ $k ];
				continue;
			}

			// 商品画像の場合
			if ( $this->sftp->mkdir( $remote_dir ) ) {
				$this->data['log'][] = "{$remote_dir}を作成";
			}
			$remote_dir .= $m[1];
			if ( $this->sftp->mkdir( $remote_dir ) ) {
				$this->data['log'][] = "{$remote_dir}を作成";
			}
			$remote_file         = "{$remote_dir}/{$name[$k]}";
			$image_data          = file_get_contents( "{$tmp}/{$name[$k]}" );
			$uploaded            = $this->sftp->put_contents( $remote_file, $image_data );
			$this->data['log'][] = match ( $uploaded ) {
				true => "転送成功 $name[$k]",
				default => "転送失敗 $name[$k]",
			};
			if ( $uploaded ) {
				$this->n2data[ $m[1] . $m[2] ][] = str_replace( 'cabinet/images', '', $remote_file );
			}
		}
		exec( "rm -Rf {$tmp}" );
	}

	public function csv_upload() {
		$name     = $this->data['files']['name'];
		$type     = $this->data['files']['type'];
		$tmp_name = $this->data['files']['tmp_name'];
		$name     = array_map(
			function ( $n ) {
				foreach ( $this->data['rakuten_csv_name'] as $file_name ) {
					// リネーム処理
					if ( str_contains( $n, $file_name ) ) {
						$n = $file_name . $this->data['extensions'];
						break;
					}
				}
				return $n;
			},
			$name,
		);

		foreach ( $tmp_name as $k => $file ) {
			if ( ! str_contains( $name[ $k ], $this->data['extensions'] ) ) {
				$this->data['log'][] = 'ファイル形式(csv)が違います :' . $name[ $k ];
				continue;
			}
			if ( ! in_array( preg_replace( "/\\{$this->data['extensions']}/", '', $name[ $k ] ), $this->data['rakuten_csv_name'], true ) ) {
				$this->data['log'][] = 'ファイル名に指定のワード(' . implode( ',', $this->data['rakuten_csv_name'] ) . ')が含まれていません :' . $name[ $k ];
				continue;
			}
			$remote_file         = "ritem/batch/{$name[ $k ]}";
			$file_data           = file_get_contents( $file );
			$this->data['log'][] = match ( $this->sftp->put_contents( $remote_file, $file_data ) ) {
				true => "転送成功 {$this->data['files']['name'][$k]}",
				default => "転送失敗 {$this->data['files']['name'][$k]}",
			};
		}
	}

	/**
	 * log output
	 */
	public function log_output() {
		$edit_link = get_edit_post_link( $this->data['insert_post'] );
		$data      = array(
			'url' => $edit_link,
			'log' => $this->data['log'],
		);
		header( 'Content-Type: application/json; charset=utf-8' );
		echo wp_json_encode( $data, JSON_UNESCAPED_UNICODE );
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
	 */
	private function set_files() {
		setlocale( LC_ALL, 'ja_JP.UTF-8' );
		$this->check_fatal_error( $this->data['params']['judge'], '転送モードが設定されていません' );
		$this->data['files'] = $_FILES['sftp_file'];
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
			http_response_code( 400 );
			echo wp_json_encode( array( 'message' => $message ), JSON_UNESCAPED_UNICODE );
			die;
		}
	}

	/**
	 * カスタム投稿を追加
	 */
	public function register_post_type() {
		$args = array(
			'label'    => 'sftp_log',
			'public'   => true,
			'supports' => array(
				'title',
				'editor',
				'revisions',
			),
		);
		register_post_type( $this->data['post_type'], $args );
	}

	/**
	 * insert
	 */
	public function insert_post() {
		global $n2;
		$now          = date( 'Y M d h:i:s A' );
		$judge        = $this->data['params']['judge'];
		$post_content = array(
			'upload_data'     => $this->n2data,
			'upload_type'     => $judge,
			'upload_log'      => $this->data['log'],
			'upload_date'     => $now,
			'image_revisions' => array(
				'before' => array(),
				'after'  => array(),
			),
		);
		$default      = array(
			'ID'           => 0,
			'post_author'  => $n2->current_user->ID,
			'post_status'  => 'pending',
			'post_type'    => $this->data['post_type'],
			'post_title'   => "[$now] $judge",
			'post_content' => wp_json_encode( $post_content, JSON_UNESCAPED_UNICODE ),
		);
		// $defaultを$argsで上書き
		$postarr                   = wp_parse_args( $args, $default );
		$this->data['insert_post'] = wp_insert_post( $postarr );
	}

	/**
	 * update_post
	 */
	public function update_post() {
		$params = $_GET;
		// $_POSTを$paramsで上書き
		if ( wp_verify_nonce( $_POST['n2nonce'] ?? '', 'n2nonce' ) ) {
			$params = wp_parse_args( $params, $_POST );
		}
		$this->check_fatal_error( $params['post_id'] ?? '', 'IDが未設定です' );
		$this->check_fatal_error( $params['post_content'] ?? '', 'contentが未設定です' );
		$result = wp_update_post(
			array(
				'ID'           => $params['post_id'],
				'post_content' => $params['post_content'],
			)
		);
		header( 'Content-Type: application/json; charset=utf-8' );
		echo wp_json_encode(
			array(
				'id'      => $result,
				'message' => 'updated',
			),
			JSON_UNESCAPED_UNICODE
		);
		exit;
	}
}
