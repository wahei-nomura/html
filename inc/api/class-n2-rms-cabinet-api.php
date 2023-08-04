<?php
/**
 * RMS CABINET API
 * /wp-admin/admin-ajax.php?action=n2_rms_cabinet_api_ajax&mode=debug&request=
 *
 * @package neoneng
 */

if ( class_exists( 'N2_RMS_Cabinet_API' ) ) {
	new N2_RMS_Cabinet_API();
	return;
}

/**
 * N2からCABINETへ送信したりするAPI
 */
class N2_RMS_Cabinet_API extends N2_RMS_Base_API {
	/**
	 * フォルダ一覧
	 *
	 * @var array
	 */
	protected $folders = array();

	/**
	 * フォルダ一覧取得
	 *
	 * @return array フォルダ一覧
	 */
	public static function folders_get() {
		$params  = array(
			'limit' => 100,
		);
		$url     = static::$settings['endpoint'] . '/1.0/cabinet/folders/get?' . http_build_query( $params );
		$data    = wp_remote_get( $url, array( 'headers' => static::$data['header'] ) );
		$folders = simplexml_load_string( $data['body'] )->cabinetFoldersGetResult->folders;
		$folders = json_decode( wp_json_encode( $folders ), true )['folder'];

		return $folders;
	}
	/**
	 * ファイル一覧取得
	 *
	 * @return array ファイル一覧
	 */
	public static function files_get() {

		static::check_fatal_error( isset( static::$data['params']['folderId'] ), 'フォルダーIDの取得に失敗しました。' );

		$files_get_params = array(
			'folderId' => static::$data['params']['folderId'],
			'limit'    => 100,
			'offset'   => 1,
		);

		$url = static::$settings['endpoint'] . '/1.0/cabinet/folder/files/get?' . http_build_query( $files_get_params );

		$files    = array();
		$requests = array();

		$response = wp_remote_get( $url, array( 'headers' => static::$data['header'] ) );

		$result     = simplexml_load_string( $response['body'] )->cabinetFolderFilesGetResult;
		$file_count = $result->fileAllCount;
		$res_files  = $result->files;
		$res_files  = json_decode( wp_json_encode( $res_files ), true )['file'] ?? array();
		$files      = match ( $file_count > 1 ) {
			true => $res_files,
			default => array( $res_files ),
		};
		// 開発途中、１ページ目だけ返す
		return $files;

		for ( $i = 1; $i < ( $file_count % $files_get_params['limit'] ); $i++ ) {
			$params['offset'] += 1;
			$requests[]        = array(
				'url' => static::$settings['endpoint'] . '/1.0/cabinet/folder/files/get?' . http_build_query( $params ),
			);
		}
		if ( empty( $requests ) ) {
			return $files;
		}

		$response = N2_Multi_URL_Request_API::ajax(
			array(
				'requests' => $requests,
				'call'     => 'request_multiple',
				'mode'     => 'func',
				'headers'  => static::$data['header'],
			),
		);

		foreach ( $response as $res ) {
			$result    = simplexml_load_string( $res->body )->cabinetFolderFilesGetResult;
			$res_files = $result->files;
			$res_files = json_decode( wp_json_encode( $res_files ), true )['file'] ?? array();
			$files     = array( ...$files, ...$res_files );
		}
		return $files;
	}
	/**
	 * ファイル一覧(検索)
	 *
	 * @return array 検索結果
	 */
	public static function files_search() {
		$files = array();

		static::check_fatal_error( static::$data['params']['keywords'] ?? false, '検索ワードが設定されていません。' );

		$keywords = static::$data['params']['keywords'];

		$requests = array_map(
			function ( $keyword ) {
				$params = array(
					'fileName' => $keyword,
					'limit'    => 100,
				);
				return array(
					'url' => static::$settings['endpoint'] . '/1.0/cabinet/files/search?' . http_build_query( $params ),
				);
			},
			$keywords,
		);

		$response = N2_Multi_URL_Request_API::ajax(
			array(
				'requests' => $requests,
				'call'     => 'request_multiple',
				'mode'     => 'func',
				'headers'  => static::$data['header'],
			),
		);

		foreach ( $response as $res ) {
			$keyword       = $res->headers->getValues( 'filename' )[0];
			$search_result = simplexml_load_string( $res->body )->cabinetFilesSearchResult;
			$res_files     = $search_result->files;
			$file_count    = (int) $search_result->fileCount->__toString();
			$res_files     = json_decode( wp_json_encode( $res_files ), true )['file'] ?? array();

			$files[ $keyword ] = match ( $file_count > 1 ) {
				true => $res_files,
				default => array( $res_files ),
			};
		}
		return $files;
	}

	/**
	 * フォルダ追加
	 */
	public static function folder_insert() {
		static::check_fatal_error( static::$data['params']['folderName'] ?? false, 'フォルダ名が設定されていません。' );

		$url              = static::$settings['endpoint'] . '/1.0/cabinet/folder/insert';
		$xml_request_body = new SimpleXMLElement( '<?xml version="1.0" encoding="UTF-8"?><request></request>' );

		$request = array(
			'folderInsertRequest' => array(
				'folder' => array(
					'folderName'    => static::$data['params']['folderName'],
					'directoryName' => static::$data['params']['directoryName'] ?? '',
					'upperFolderId' => static::$data['params']['upperFolderId'] ?? '',
				),
			),
		);
		static::array_to_xml( $request, $xml_request_body );
		// SimpleXMLElementオブジェクトを文字列に変換
		$xml_data = $xml_request_body->asXML();

		$request_args = array(
			'method'  => 'POST',
			'headers' => array(
				...static::$data['header'],
			),
			'body'    => $xml_data, // XMLデータをリクエストボディに設定
		);
		$response     = wp_remote_request( $url, $request_args );

		$response_body = wp_remote_retrieve_body( $response );

		return $response_body;
	}
	/**
	 * ファイル追加
	 */
	public static function file_insert() {
		// static::check_fatal_error( static::$data['params']['fileName'] ?? false, 'フォルダ名が設定されていません。' );
		// static::check_fatal_error( static::$data['params']['folderId'] ?? false, 'directory名が設定されていません。' );

		$url = static::$settings['endpoint'] . '/1.0/cabinet/file/insert';
		static::set_files();

		$requests = array();

		foreach ( static::$data['files']['tmp_name'] as $index => $tmp_name ) {
			$file_name = static::$data['files']['name'][ $index ];
			$request   = array(
				'url'     => $url,
				'type'    => Requests::POST,
				'headers' => array(
					'Content-Type' => 'multipart/form-data;',
				),
				'data'    => null,
			);

			/**
			 * XML
			 */
			$xml_request_body = new SimpleXMLElement( '<?xml version="1.0" encoding="UTF-8"?><request></request>' );
			$xml_array        = array(
				'fileInsertRequest' => array(
					'file' => array(
						'filePath'  => $file_name,
						'fileName'  => str_replace( '.jpg', '', $file_name ),
						'folderId'  => static::$data['params']['folderId'],
						'overWrite' => 'true',
					),
				),
			);
			static::array_to_xml( $xml_array, $xml_request_body );
			// SimpleXMLElementオブジェクトを文字列に変換
			$xml_payload = $xml_request_body->asXML();

			/**
			 * request body
			 */
			// Define boundary
			$boundary         = wp_generate_uuid4();
			$request['data']  = "--{$boundary}\r\n";
			$request['data'] .= "Content-Disposition: form-data; name=\"xml\"\r\n";
			$request['data'] .= "\r\n{$xml_payload}\r\n";
			$request['data'] .= "--{$boundary}\r\n";
			$request['data'] .= "Content-Disposition: form-data; name=\"file\"; filename=\"{$file_name}\"\r\n";
			$request['data'] .= "Content-Type: image/jpg\r\n";
			$request['data'] .= "\r\n" . wp_remote_get( $tmp_name ) . "\r\n";
			$request['data'] .= "--{$boundary}--";

			/**
			 * add header
			 */
			$request['headers']['Content-Type']  .= 'boundary=' . $boundary;
			$request['headers']['Content-Length'] = strlen( $request['data'] );

			$requests[] = $request;
		}

		return N2_Multi_URL_Request_API::ajax(
			array(
				'requests' => $requests,
				'call'     => 'request_multiple',
				'mode'     => 'func',
				'headers'  => static::$data['header'],
			),
		);
	}

}
