<?php
/**
 * class-n2-rakuten-ftp.php
 *
 * @package neoneng
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'N2_Rakuten_FTP' ) ) {
	new N2_Rakuten_FTP();
	return;
}

/**
 * 楽天FTPページ
 */
class N2_Rakuten_FTP {

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
	}

	/**
	 * N2 SYNC　メニューの追加
	 */
	public function add_menu() {
		add_menu_page( '楽天FTP', '楽天FTP', 'ss_crew', 'n2-rakuten-ftp', array( $this, 'ftp_ui' ), 'dashicons-admin-site-alt3' );
	}

	/**
	 * FTP UI
	 */
	public function ftp_ui() {
		$template = isset( $_GET['tab'] ) ? "ftp_ui_{$_GET['tab']}" : 'update_server';
		?>
		<div class="wrap">
			<h1>商品CSV・画像UP用サーバー</h1>
			<?php echo $this->$template(); ?>
		</div>
		<?php
	}

	/**
	 * アップデートサーバーFTP
	 */
	public function update_server() {
		global $n2;
		WP_Filesystem();
		require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-ftpsockets.php';
		$opt = array(
			'hostname' => $n2->rakuten['upload_server'],
			'username' => $n2->rakuten['ftp_user'],
			'password' => $n2->rakuten['ftp_pass'],
		);
		$ftp = new WP_Filesystem_ftpsockets( $opt );
		$ftp->connect();

		echo '<pre>';print_r($ftp->dirlist('ritem/logs'));echo '</pre>';
		$contents = $ftp->get_contents('ritem/logs/item-cat.csv');
		$contents = mb_convert_encoding( $contents, 'utf-8', 'sjis' );
		echo '<pre>';print_r($contents);echo '</pre>';
	}
}
