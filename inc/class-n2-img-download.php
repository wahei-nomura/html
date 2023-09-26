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
		$tmp_files = array();
		$requests  = array_map(
			function ( $file ) use ( &$tmp_files ) {
				$tmp_uri     = stream_get_meta_data( tmpfile() )['uri'];
				$tmp_files[] = $tmp_uri;
				return array(
					'url'     => $file['url'],
					'options' => array(
						'filename' => $tmp_uri,
					),
				);
			},
			$params['url']
		);
		$response  = N2_Multi_URL_Request_API::request_multiple( $requests );

		$tmp_zip_uri = stream_get_meta_data( tmpfile() )['uri'];
		$zip         = new ZipArchive();
		$zip->open( $tmp_zip_uri, ZipArchive::CREATE );
		$zip_name = $params['zipName'];

		array_map(
			function ( $res, $index ) use ( $params, &$zip, $zip_name, $tmp_files ) {
				$file = array_filter(
					$params['url'],
					function ( $file ) use ( $res ) {
						return $res->url === $file['url'];
					},
				);
				// indexを振り直す
				$file = current( $file );
				$zip->addFile( $tmp_files[ $index ], "{$zip_name}/{$file['folderName']}/{$file['filePath']}" );
			},
			$response,
			array_keys( $response ),
		);
		$zip->close();

		header( 'Content-Type: application/zip' );
		header( 'Content-Transfer-Encoding: Binary' );
		header( 'Content-Length: ' . filesize( $tmp_zip_uri ) );
		header( "Content-Disposition:attachment; filename = {$zip_name}" );
		while ( ob_get_level() ) {
			ob_end_clean(); // 出力バッファの無効化
		}

		// 出力処理
		readfile( $tmp_zip_uri );
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
		$ids         = explode( ',', $params['ids'] );
		$tmp_zip_uri = stream_get_meta_data( tmpfile() )['uri'];
		$zip         = new ZipArchive();
		$requests    = array();// urlリスト
		$info        = array();// 画像情報
		$zip->open( $tmp_zip_uri, ZipArchive::CREATE );

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
				$tmp_uri = stream_get_meta_data( tmpfile() )['uri'];
				// 配列生成
				$requests[]                       = array(
					'url'     => "{$img['url']}?id={$id}",
					'options' => array(
						'hooks'    => $hooks,
						'filename' => $tmp_uri,
					),
				);
				$info[ "{$img['url']}?id={$id}" ] = array(
					'dirname'     => $dirname,
					'filename'    => "{$filename}-{$index}.{$extension}",
					'description' => $img['description'] ?? '',
					'tmp_url'     => $tmp_uri,
				);
			}
		}
		$description = array();
		foreach ( Requests::request_multiple( $requests, array( 'timeout' => 100 ) ) as $v ) {
			$v->info = $info[ $v->url ];
			$tmp_uri = $v->info['tmp_url'];
			unset( $v->info['tmp_url'] );
			// 説明を追加
			if ( ! empty( $info[ $v->url ]['description'] ) ) {
				$description[ $v->info['dirname'] ][] = array(
					'dirname'     => $v->info['dirname'],
					'filename'    => $v->info['filename'],
					'description' => $v->info['description'],
				);
			}
			$zip->addFile( $tmp_uri, "{$v->info['dirname']}/{$v->info['filename']}" );
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
		if ( ! file_exists( $tmp_zip_uri ) ) {
			wp_safe_redirect( $_SERVER['HTTP_REFERER'] );
			exit;
		}
		// ダウンロード
		header( 'Content-Type: application/zip' );
		header( 'Content-Transfer-Encoding: Binary' );
		header( 'Content-Length: ' . filesize( $tmp_zip_uri ) );
		header( "Content-Disposition:attachment; filename = {$name}" );

		while ( ob_get_level() ) {
			ob_end_clean(); // 出力バッファの無効化
		}

		// 出力処理
		readfile( $tmp_zip_uri );
		exit;
	}
}
