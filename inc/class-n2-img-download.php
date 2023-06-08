<?php
/**
 * class-n2-img-download.php
 *
 * @package neoneng
 */

if ( class_exists( 'N2_Img_Download' ) ) {
	new N2_Img_Download();
	return;
}
require_once ABSPATH . '/wp-admin/includes/file.php';
/**
 * Img_Download
 */
class N2_Img_Download {
	/**
	 * コンストラクタ
	 */
	public function __construct() {
		add_action( 'wp_ajax_n2_download_images_by_id', array( $this, 'download_images_by_id' ) );
		add_action( 'wp_ajax_n2_download_image_by_url', array( $this, 'download_image_by_url' ) );
	}

	/**
	 * URLからダウンロード
	 */
	public function download_image_by_url() {
		$url = $_GET['url'];
		add_filter( 'https_ssl_verify', '__return_false' );
		$pathinfo = pathinfo( $url );
		$name     = ! preg_match( '/^-/', $_GET['name'] ) ? mb_strtolower( $_GET['name'] ) : $pathinfo['filename'];
		$name    .= ".download.{$pathinfo['extension']}";
		$content  = wp_remote_get( $url );
		$headers  = $content['headers']->getAll();
		header( "Content-Type: {$headers['content-type']}" );
		header( "Content-Disposition:attachment; filename = {$name}" );
		header( "Content-Length: {$headers['content-length']}" );
		echo $content['body'];
		exit;
	}

	/**
	 * 投稿IDからダウンロード
	 */
	public function download_images_by_id() {
		$params = $_GET;
		// $_POSTを$paramsで上書き
		if ( wp_verify_nonce( $_POST['n2nonce'] ?? '', 'n2nonce' ) ) {
			$params = wp_parse_args( $params, $_POST );
		}
		if ( ! isset( $params['ids'] ) ) {
			echo 'idがセットされていません';
			exit;
		}
		WP_Filesystem();
		global $wp_filesystem;
		// localhostでのwp_remote_getに必須
		add_filter( 'https_ssl_verify', '__return_false' );
		$ids     = explode( ',', $params['ids'] );
		$tmp_uri = stream_get_meta_data( tmpfile() )['uri'];
		$zip     = new ZipArchive();
		$zip->open( $tmp_uri, ZipArchive::CREATE );
		foreach ( $ids as $id ) {
			$img_file_name = get_post_meta( $id, '返礼品コード', true );
			$dirname       = $img_file_name ?: get_the_title( $id );
			$filename      = mb_strtolower( $img_file_name ) ?: get_the_title( $id );
			foreach ( get_post_meta( $id, '商品画像', true ) as $i => $img ) {
				$index     = $i + 1;
				$extension = pathinfo( $img['url'] )['extension'];
				$zip->addFromString( "{$dirname}/{$filename}-{$index}.{$extension}", wp_remote_get( $img['url'] )['body'] );
			}
		}
		// 単品か複数かで名前と構造を変更
		$name = count( $ids ) > 1 ? 'NEONENG元画像.' . wp_date( 'Y-m-d-H-i' ) . '.zip' : "{$dirname}.zip";
		$zip->close();
		if ( ! file_exists( $tmp_uri ) ) {
			wp_safe_redirect( $_SERVER['HTTP_REFERER'] );
			exit;
		}
		// ダウンロード
		header( 'Content-Type: application/zip' );
		header( 'Content-Transfer-Encoding: Binary' );
		header( 'Content-Length: ' . filesize( $tmp_uri ) );
		header( "Content-Disposition:attachment; filename = {$name}" );
		echo $wp_filesystem->get_contents( $tmp_uri );
		exit;
	}
}
