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
		'data'    => array(),
		'error'   => array(),
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
	}

	/**
	 * N2 SFTP　メニューの追加
	 */
	public function add_menu() {
		global $n2;
		if ( isset( $n2->portal_setting['楽天'] ) ) {
			add_menu_page( '楽天SFTP', 'n2_rakuten_sftp', 'ss_crew', 'n2_rakuten_sftp_upload', array( $this, 'sftp_ui' ), 'dashicons-admin-site-alt3' );
			add_submenu_page( 'n2_rakuten_sftp_upload', '楽天エラーログ', '楽天エラーログ', 'ss_crew', 'n2_rakuten_sftp_error_log', array( $this, 'sftp_ui' ) );
		}
	}

	/**
	 * SFTP UI
	 */
	public function sftp_ui() {
		$template = str_replace(
			array(
				'n2_rakuten_sftp_',
				'_'
			),
			array(
				'',
				'-'
			),
			$_GET['page']
		);
		?>
		<div class="wrap">
			<h1>楽天SFTP</h1>
			<?php get_template_part( 'template/admin-menu/sftp', $template, array( 'sftp' => $this->sftp ) ); ?>
		</div>
		<?php
	}

	/**
	 * SFTP CONNECT
	 */
	public function sftp_connect() {
		// 初回時のみ接続確認
		if ( null !== $this->data['connect'] ) {
			return $this->data['connect'];
		}
		global $n2;

		if ( ! isset( $n2->portal_setting['楽天']['ftp_user'] ) || ! $n2->portal_setting['楽天']['ftp_user'] ) {
			$this->data['error'][] = '楽天セットアップ > FTPユーザー';
		}
		if ( ! isset( $n2->portal_setting['楽天']['ftp_pass'] ) || ! $n2->portal_setting['楽天']['ftp_pass'] ) {
			$this->data['error'][] = '楽天セットアップ > FTPパスワード';
		}
		if ( $this->data['error'] ) {
			// エラー出力して終了
			echo '楽天セットアップが完了していません。';
			exit();
		}
		WP_Filesystem();
		require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-ssh2.php';

		$opt        = array(
			'hostname' => $n2->portal_setting['楽天']['upload_server'],
			'username' => $n2->portal_setting['楽天']['ftp_user'],
			'password' => $n2->portal_setting['楽天']['ftp_pass'],
		);
		$this->sftp = new WP_Filesystem_SSH2( $opt );

		if ( ! $this->sftp->connect() ) {
			$opt['password'] = rtrim( $opt['password'], 1 ) . '2';
			$this->sftp      = new WP_Filesystem_SSH2( $opt );
		}

		$this->data['connect'] = $this->sftp->connect();

		return $this->data['connect'];
	}

	public function upload_to_rakuten() {

	}
}