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
use WpOrg\Requests\HookManager;
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
		add_action( 'wp_ajax_n2_download_multiple_image_by_url', array( $this, 'download_multiple_image_by_url' ) );
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
	 * URLからダウンロード(複数)
	 */
	public function download_multiple_image_by_url() {
		$params = $_GET;
		// $_POSTを$paramsで上書き
		if ( wp_verify_nonce( $_POST['n2nonce'] ?? '', 'n2nonce' ) ) {
			$params = wp_parse_args( $params, $_POST );
		}
		if ( ! isset( $params['url'] ) || empty( $params['url'] ) ) {
			echo 'urlがセットされていません';
			exit;
		}

		WP_Filesystem();
		global $wp_filesystem;

		$requests = array_map(
			function( $file ) {
				return array(
					'url' => $file['url'],
				);
			},
			$params['url']
		);
		$response = N2_Multi_URL_Request_API::request_multiple( $requests );

		$tmp_uri = stream_get_meta_data( tmpfile() )['uri'];
		$zip     = new ZipArchive();
		$zip->open( $tmp_uri, ZipArchive::CREATE );
		$zip_name = $params['zipName'];

		array_map(
			function( $res ) use ( $params, $zip, $zip_name ) {
				global $n2;
				$file = array_filter(
					$params['url'],
					function( $file ) use ( $res ) {
						return $res->url === $file['url'];
					},
				);
				// indexを振り直す
				$file = array_values( $file )[0];
				$zip->addFromString( "{$zip_name}/{$file['folderName']}/{$file['filePath']}", $res->body );
			},
			$response,
		);
		$zip->close();

		header( 'Content-Type: application/zip' );
		header( 'Content-Transfer-Encoding: Binary' );
		header( 'Content-Length: ' . filesize( $tmp_uri ) );
		header( "Content-Disposition:attachment; filename = {$zip_name}" );
		echo $wp_filesystem->get_contents( $tmp_uri );
		exit;
	}

	/**
	 * 投稿IDからダウンロード
	 */
	public function download_images_by_id() {
		// タイムアウト制限を設定
		set_time_limit( 120 );
		global $n2;
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
		$ids      = explode( ',', $params['ids'] );
		$tmp_uri  = stream_get_meta_data( tmpfile() )['uri'];
		$zip      = new ZipArchive();
		$requests = array();// urlリスト
		$info     = array();// 画像情報
		$zip->open( $tmp_uri, ZipArchive::CREATE );

		// 一時フォルダに画像を格納する
		$tmp = wp_tempnam( __CLASS__, get_theme_file_path() . '/' );
		unlink( $tmp );
		mkdir( $tmp );

		foreach ( $ids as $id ) {
			$img_file_name = get_post_meta( $id, '返礼品コード', true );
			$dirname       = $img_file_name ?: get_the_title( $id );
			$filename      = mb_strtolower( $img_file_name ) ?: get_the_title( $id );
			foreach ( get_post_meta( $id, '商品画像', true ) as $i => $img ) {
				$index     = $i + 1;
				$extension = pathinfo( $img['url'] )['extension'];
				// オレオレ証明書も許可する
				$hooks = new Requests_Hooks();
				$hooks->register(
					'curl.before_multi_add',
					function ( $ch ) {
						curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
					}
				);
				// 配列生成
				$requests[] = array(
					'url'     => "{$img['url']}?id={$id}",
					'options' => array(
						'hooks'    => $hooks,
						'filename' => "{$tmp}/{$filename}-{$index}.{$extension}",
					),
				);
				$info[ "{$img['url']}?id={$id}" ] = array(
					'dirname'     => $dirname,
					'filename'    => "{$filename}-{$index}.{$extension}",
					'description' => $img['description'] ?? '',
				);
			}
		}
		$description = array();
		foreach ( Requests::request_multiple( $requests, array( 'timeout' => 100 ) ) as $v ) {
			$v->info = $info[ $v->url ];
			// 説明を追加
			if ( ! empty( $info[ $v->url ]['description'] ) ) {
				$description[ $v->info['dirname'] ][] = array(
					'dirname'     => $v->info['dirname'],
					'filename'    => $v->info['filename'],
					'description' => $v->info['description'],
				);
			}
			$zip->addFile( "{$tmp}/{$v->info['filename']}", "{$v->info['dirname']}/{$v->info['filename']}" );
		}
		// 説明.txt生成
		if ( ! empty( $description ) ) {
			foreach ( $description as $dirname => $desc ) {
				array_multisort( array_column( $desc, 'filename' ), SORT_ASC, $desc );
				$desc = implode( PHP_EOL, array_map( fn( $v ) => implode( "\t", $v ), $desc ) );
				$zip->addFromString( "{$dirname}/説明.txt", $desc );
			}
		}
		// 単品か複数かで名前と構造を変更
		$name = count( $ids ) > 1 ? 'NEONENG元画像.' . wp_date( 'Y-m-d-H-i' ) . '.zip' : "{$dirname}.zip";
		$zip->close();
		// 一時フォルダを削除
		exec( "rm -Rf {$tmp}" );
		if ( ! file_exists( $tmp_uri ) ) {
			wp_safe_redirect( $_SERVER['HTTP_REFERER'] );
			exit;
		}
		// ダウンロード
		header( 'Content-Type: application/zip' );
		header( 'Content-Transfer-Encoding: Binary' );
		header( 'Content-Length: ' . filesize( $tmp_uri ) );
		header( "Content-Disposition:attachment; filename = {$name}" );

		while ( ob_get_level() ) {
			ob_end_clean(); // 出力バッファの無効化
		}

		// 出力処理
		readfile( $tmp_uri );
		exit;
	}
}
