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

		foreach ( $response as $index => $res ) {
			$file = current(
				array_filter(
					$params['url'],
					fn ( $f ) => $res->url === $f['url']
				)
			);
			$zip->addFile( $tmp_files[ $index ], "{$zip_name}/{$file['folderName']}/{$file['filePath']}" );
		}
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

		// DLモード
		$dl_types = $params['type'] ?? array( 'フルサイズ' );

		foreach ( $ids as $id ) {
			$img_file_name = get_post_meta( $id, '返礼品コード', true );
			$dirname       = $img_file_name ?: get_the_title( $id );
			$filename      = mb_strtolower( $img_file_name ) ?: get_the_title( $id );
			foreach ( get_post_meta( $id, '商品画像', true ) as $i => $img ) {
				// フルサイズ確認用
				$set_fullsize = false;
				$index        = $i + 1;
				$extension    = pathinfo( $img['url'] )['extension'];
				foreach ( $dl_types as $type ) {
					$img_url   = "{$img['url']}?id={$id}";
					$type_info = array(
						'dirname'     => "{$dirname}_{$type}",
						'filename'    => "{$filename}-{$index}.{$extension}",
						'description' => $img['description'] ?? '',
						'tmp_uri'     => $info['フルサイズ'][ $img_url ]['tmp_uri'] ?? '',
					);

					// カスタムサイズの情報を取得
					$image_attributes = wp_get_attachment_image_src( $img['id'], $type );

					$type_info['tmp_uri'] = match ( ! $image_attributes ) {
						true => $type_info['tmp_uri'] ?: stream_get_meta_data( tmpfile() )['uri'],
						default => stream_get_meta_data( tmpfile() )['uri'],
					};
					if ( $image_attributes && ! $set_fullsize ) {
						$type_info['dirname']      = "{$dirname}_フルサイズ";
						$info['フルサイズ'][ $img_url ] = $type_info;
					}
					$img_url = match ( ! $image_attributes ) {
						true => $img_url,
						default => "{$image_attributes[0]}?id={$id}",
					};
					$info[ $type ][ $img_url ] = $type_info;

					if ( ! $image_attributes && $set_fullsize ) {
						continue;
					}
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
						'url'     => $img_url,
						'options' => array(
							'hooks'    => $hooks,
							'filename' => $type_info['tmp_uri'],
						),
					);
					if ( ! $image_attributes ) {
						$set_fullsize = true;
					}
				}
			}
		}
		$description = array();
		Requests::request_multiple( $requests, array( 'timeout' => 100 ) );
		foreach ( $info as $type => $type_info ) {
			foreach ( $type_info as $i ) {
				// DLした画像を必要ならリサイズ&トリミングする
				$tmp_uri = match ( $type ) {
					'楽天' => call_user_func_array(
						array(
							$this,
							'resize_and_crop_image_by_imagick',
						),
						array(
							'file_path' => $i['tmp_uri'],
						),
					),
					'チョイス' => call_user_func_array(
						array(
							$this,
							'resize_and_crop_image_by_imagick',
						),
						array(
							'file_path' => $i['tmp_uri'],
							'height'    => 435,
						),
					),
					default => $i['tmp_uri'],
				};
				if ( ! empty( $i['description'] ) ) {
					$description[ $i['dirname'] ][] = array(
						'dirname'     => $i['dirname'],
						'filename'    => $i['filename'],
						'description' => $i['description'],
					);
				}
				$zip->addFile( $tmp_uri, "{$i['dirname']}/{$i['filename']}" );
			}
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
		$name = match ( true ) {
			count( $ids ) === 1 => $dirname . '.zip',
			default    => 'NEONENG元画像.' . wp_date( 'Y-m-d-H-i' ) . '.zip',
		};
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

	/**
	 * リサイズ&トリミング
	 *
	 * @param string $file_path file_path
	 * @param int    $width width
	 * @param int    $height file_pathdddd
	 * @param int    $dpi file_path
	 *
	 * @return string new_file_path
	 */
	public function resize_and_crop_image_by_imagick( $file_path, $width = 700, $height = 700, $dpi = 72 ) {
		$imagick         = new \Imagick( $file_path );
		$original_width  = $imagick->getImageWidth();
		$original_height = $imagick->getImageHeight();

		// リサイズと中央をトリミング
		if ( $original_width !== $width && $original_height !== $height ) {
			$original_ratio = $original_width / $original_height;
			$resize_width   = (int) ( max( $width, $height ) * ( $original_ratio > 1 ? $original_ratio : 1 ) );
			$resize_height  = (int) ( max( $width, $height ) / ( $original_ratio < 1 ? $original_ratio : 1 ) );
			$imagick->resizeImage( $resize_width, $resize_height, \Imagick::FILTER_LANCZOS, 1 );
			// 中央を基準にトリミング
			$crop_x = (int) ( ( $resize_width - $width ) / 2 );
			$crop_y = (int) ( ( $resize_height - $height ) / 2 );
			$imagick->cropImage( $width, $height, $crop_x, $crop_y );
		}

		// 解像度を72に設定
		$imagick->setImageResolution( $dpi, $dpi );

		$new_file_path = stream_get_meta_data( tmpfile() )['uri'];

		// 画像を一時ファイルとして保存
		$imagick->writeImage( $new_file_path );

		// メモリのクリーンアップ
		$imagick->clear();
		$imagick->destroy();

		return $new_file_path;
	}
}
