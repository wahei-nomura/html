<?php
/**
 * class-n2-img-download.php
 *
 * @package neoneng
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
		add_action( 'wp_ajax_download_by_url', array( $this, 'download_by_url' ) );
	}

	/**
	 * URLからダウンロード
	 */
	public function download_by_url() {
		// ajaxで渡ってきたpostidの配列
		$ids = explode( ',', $_POST['id'] );
		$url = $_GET['url'] ?: $_POST['url'];
		add_filter( 'https_ssl_verify', '__return_false' );
		// シングルダウンロード(zipとの判断基準は$_POST['id']を持ってるかどうか)
		if ( ! $_POST['id'] ) {
			$pathinfo = pathinfo( $url );
			$name     = ! preg_match( '/^-/', $_GET['name'] ) ? mb_strtolower( $_GET['name'] ) : $pathinfo['filename'];
			$name    .= ".{$pathinfo['extension']}";
			$content  = wp_remote_get( $url );
			$headers  = $content['headers']->getAll();
			header( "Content-Type: {$headers['content-type']}" );
			header( "Content-Disposition:attachment; filename = {$name}" );
			header( "Content-Length: {$headers['content-length']}" );
			echo $content['body'];
			exit;
		} else {
			// まとめてZIPダウンロード
			// 画像が１枚もない商品コードを格納する配列
			$undifind_images = array();
			// tmpディレクトリ作成
			$tmp_file      = tmpfile(); // temp file in memory
			$zip_file_dir  = stream_get_meta_data( $tmp_file )['uri'];
			$zip_file_name = date( 'YmdHi' ) . 'NEONENG元画像.zip';
			$zip           = new ZipArchive();
			$result        = $zip->open( $zip_file_dir, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE );
			if ( true !== $result ) { // エラー処理
			echo 'error!code:' . $result;
			exit();
			}
			foreach ( $ids as  $idkey => $id ) {
				$item_code = get_post_meta( $id, '返礼品コード', true ) ? get_post_meta( $id, '返礼品コード', true ) : 'id_' . $id;
				// ファイルナンバー保持のため
				// $fnum = 1;
				// echo  $fnum;
				// 画像数保持
				$file_length  = 0;
				$fname        = mb_strtolower( $item_code );
				$meta_pic_arr = get_post_meta( $id, '商品画像', true );
				foreach ( $meta_pic_arr as $pickey => $meta_pic ) {
					$fname = mb_strtolower( $item_code );
					// $pic_id    = attachment_url_to_postid( $meta_pic );
					// $fpath     = get_attached_file( $pic_id, true );
					$furl      = $meta_pic['url'];
					$extension = pathinfo( $furl );
					// 画像がアップロードされている場合のみ処理
					if ( $furl ) {
						$fname .= '-' . ( $pickey + 1 ) . '.' . $extension['extension'];
						if ( WP_Filesystem() ) {
							global $wp_filesystem;
							$zip->addFromString( $item_code . '/' . $fname, $wp_filesystem->get_contents( $furl ) );

						}
						$fnum ++;
						$file_length ++;
					};
				}
				$zip_meta_path = get_post_meta( $id, 'zip', true );
				$zip_id        = attachment_url_to_postid( $zip_meta_path );
				$zip_path      = get_attached_file( $zip_id );
				if ( $zip_path ) {
					// 返礼品コード名でzip
					$zip->addFile( $zip_path, $item_code . '/' . mb_strtolower( get_post_meta( $id, '返礼品コード', true ) ) . '内のzipファイル.zip' );
					$file_length ++;
				}

				// 画像ファイルが１つもない場合商品コードを記録
				if ( 0 === $file_length ) {
					array_push( $undifind_images, $item_code );
				}
			}
			// もし選択した返礼品全てに画像がなければ
			if ( ! $zip->numFiles ) {
				$redirect_url = admin_url( 'edit.php' );
				$zip->close();
				exit;
			}

			// 画像がなかった商品をログファイルとしてダウンロードに含める
			if ( count( $undifind_images ) ) {
				$nopicture_txt = '以下の商品の画像取得に失敗しました：';
				foreach ( $undifind_images as $key => $code ) {
					if ( 0 !== $key ) {
						$nopicture_txt .= ',';
					}
					$nopicture_txt .= $code;
				}

				$zip->addFromString( 'error.txt', $nopicture_txt );
			}

			// ZIP出力
			$zip->close();
			// ダウンロード
			header( 'Content-Type: application/zip' );
			header( 'Content-Transfer-Encoding: Binary' );
			header( 'Download-Name: ' . rawurlencode( $zip_file_name ) );// Javascriptに渡すために勝手に追加
			header( 'Content-Length: ' . filesize( $zip_file_dir ) );
			// ファイルを出力する前に、バッファの内容をクリア（ファイルの破損防止）
			while ( ob_get_level() ) {
				ob_end_clean();
			}
			readfile( $zip_file_dir );
			exit;
		}
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
		if ( ! isset( $_GET['id'] ) ) {
			echo 'idがセットされていません';
			exit;
		}
		WP_Filesystem();
		global $wp_filesystem;
		// localhostでのwp_remote_getに必須
		add_filter( 'https_ssl_verify', '__return_false' );
		$ids     = explode( ',', $_GET['id'] );
		$tmp_uri = stream_get_meta_data( tmpfile() )['uri'];
		$zip     = new ZipArchive();
		$zip->open( $tmp_uri, ZipArchive::CREATE );
		foreach ( $ids as $id ) {
			$item_code = get_post_meta( $id, '返礼品コード', true );
			$dirname   = $item_code ?: get_the_title( $id );
			$filename  = mb_strtolower( $item_code ) ?: get_the_title( $id );
			foreach ( get_post_meta( $id, '商品画像', true ) as $i => $img ) {
				$i         = $i > 0 ? "-{$i}" : '';
				$extension = pathinfo( $img['url'] )['extension'];
				$zip->addFromString( "{$dirname}/{$filename}{$i}.download.{$extension}", wp_remote_get( $img['url'] )['body'] );
			}
		}
		// 単品か複数かで名前と構造を変更
		$name = count( $ids ) > 1 ? 'NEONENG元画像.' . wp_date( 'Y-m-d-H-i' ) . '.zip' : "{$dirname}.zip";
		$zip->close();
		if ( ! file_exists( $tmp_uri ) ) {
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
