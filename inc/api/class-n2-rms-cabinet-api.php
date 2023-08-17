<?php
/**
 * RMS CABINET API
 * /wp-admin/admin-ajax.php?action=n2_rms_cabinet_api_ajax&mode=debug&call=
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
		if ( $file_count <= $files_get_params['limit'] ) {
			return $files;
		}
		// 開発途中
		$requests = array_map(
			function( $offset ) use ( $files_get_params ) {
				$files_get_params['offset'] = $offset;
				return array(
					'url' => static::$settings['endpoint'] . '/1.0/cabinet/folder/files/get?' . http_build_query( $files_get_params ),
				);
			},
			range( 2, floor( $file_count / $files_get_params['limit'] ) + 1 )
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
		$limit    = 100;

		$requests = array_map(
			function ( $keyword ) use ( $limit )  {
				$params = array(
					'fileName' => $keyword,
					'limit'    => $limit,
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
			$keyword       = urldecode($res->headers->getValues( 'filename' )[0]);
			$search_result = simplexml_load_string( $res->body )->cabinetFilesSearchResult;
			$res_files     = $search_result->files;
			$file_all_count = $search_result->fileAllCount;
			$file_count    = (int) $search_result->fileCount->__toString();
			$res_files     = json_decode( wp_json_encode( $res_files ), true )['file'] ?? array();

			$files[ $keyword ] = match ( $file_count > 1 ) {
				true => $res_files,
				default => array( $res_files ),
			};

			if ( $file_all_count < $limit ) {
				continue;
			}
			// limit以上なら追加でAPIを叩く
			$additional_requests = array_map(
				function ( $offset ) use ( $keyword, $limit ) {
					$params = array(
						'fileName' => $keyword,
						'limit'    => $limit,
						'offset'   => $offset,
					);
					return array(
						'url' => static::$settings['endpoint'] . '/1.0/cabinet/files/search?' . http_build_query( $params ),
					);
				},
				range( 2, floor( $file_all_count / $limit ) + 1 ),
			);

			$additional_response = N2_Multi_URL_Request_API::ajax(
				array(
					'requests' => $additional_requests,
					'call'     => 'request_multiple',
					'mode'     => 'func',
					'headers'  => static::$data['header'],
				),
			);
			
			foreach ($additional_response as $additional_res ) {
				$search_result = simplexml_load_string( $additional_res->body )->cabinetFilesSearchResult;
				$res_files     = $search_result->files;
				$file_count    = (int) $search_result->fileCount->__toString();
				$res_files     = json_decode( wp_json_encode( $res_files ), true )['file'] ?? array();
				$files[ $keyword ] = match ( $file_count > 1 ) {
					true => array( ...$files[ $keyword ], ...$res_files),
					default => array( ...$files[ $keyword ], array( $res_files ) ),
				};
			}
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
		$response_body = simplexml_load_string( $response_body );

		return $response_body;
	}
	/**
	 * ファイル追加
	 */
	public static function file_insert() {
		static::check_fatal_error( static::$data['params']['folderId'] ?? false, 'folderIdが設定されていません。' );

		$url = static::$settings['endpoint'] . '/1.0/cabinet/file/insert';
		static::set_files();

		$requests = array();

		foreach ( static::$data['files']['tmp_name'] as $index => $tmp_name ) {
			$file_content_type = mime_content_type($tmp_name);
			$file_name         = static::$data['files']['name'][ $index ];
			$request           = array(
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
						'fileName'  => preg_replace('/\.[^.]+$/', '', $file_name ),
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
			$request['data'] .= "Content-Type: {$file_content_type}\r\n";
			$request['data'] .= "\r\n" . file_get_contents( $tmp_name ) . "\r\n";
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

	/**
	 * ファイル削除
	 */
	public static function file_delete() {
		static::check_fatal_error( ! empty( static::$data['params']['fileId'] ?? array() ), 'フォルダ名が設定されていません。' );

		$url              = static::$settings['endpoint'] . '/1.0/cabinet/file/delete';
		$xml_request_body = new SimpleXMLElement( '<?xml version="1.0" encoding="UTF-8"?><request></request>' );

		$request = array(
			'fileDeleteRequest' => array(
				'file' => array(
					'fileId' => static::$data['params']['fileId'],
				),
			),
		);
		static::array_to_xml( $request, $xml_request_body );
		// SimpleXMLElementオブジェクトを文字列に変換
		$xml_data = $xml_request_body->asXML();
		$requests = array_map(
			function ( $file_id ) use ( $url ) {
				$xml_request_body = new SimpleXMLElement( '<?xml version="1.0" encoding="UTF-8"?><request></request>' );
				$request          = array(
					'fileDeleteRequest' => array(
						'file' => array(
							'fileId' => $file_id,
						),
					),
				);
				static::array_to_xml( $request, $xml_request_body );
				$xml_data = $xml_request_body->asXML();
				return array(
					'url'  => $url,
					'type' => Requests::POST,
					'data' => $xml_data,
				);
			},
			static::$data['params']['fileId'],
		);

		$response = N2_Multi_URL_Request_API::ajax(
			array(
				'requests' => $requests,
				'call'     => 'request_multiple',
				'mode'     => 'func',
				'headers'  => static::$data['header'],
			),
		);

		return $response;
	}

	/**
	 * 削除したファイル一覧
	 */
	public static function trashbox_files_get() {
		$files_get_params = array(
			'limit'  => 100,
			'offset' => 1,
		);
		$url              = static::$settings['endpoint'] . '/1.0/cabinet/trashbox/files/get?';

		$files    = array();
		$requests = array();

		$response = wp_remote_get( $url . http_build_query( $files_get_params ), array( 'headers' => static::$data['header'] ) );

		$result     = simplexml_load_string( $response['body'] )->cabinetTrashboxFilesGetResult;
		$file_count = $result->fileAllCount;
		$res_files  = $result->files;
		$res_files  = json_decode( wp_json_encode( $res_files ), true )['file'] ?? array();
		$files      = match ( $file_count > 1 ) {
			true => $res_files,
			default => array( $res_files ),
		};
		if ( $file_count <= $files_get_params['limit'] ) {
			return $files;
		}
		// 開発途中
		$requests = array_map(
			function( $offset ) use ( $files_get_params ) {
				$files_get_params['offset'] = $offset;
				return array(
					'url' => $url . http_build_query( $files_get_params ),
				);
			},
			range( 2, floor( $file_count / $files_get_params['limit'] ) + 1 )
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
			$result    = simplexml_load_string( $res->body )->cabinetTrashboxFilesGetResult;
			$res_files = $result->files;
			$res_files = json_decode( wp_json_encode( $res_files ), true )['file'] ?? array();
			$files     = array( ...$files, ...$res_files );
		}
		return $files;
	}

	/**
	 * 削除したファイルを元に戻す
	 */
	public static function trashbox_files_revert() {
		static::check_fatal_error( ! empty( static::$data['params']['fileId'] ?? array() ), 'フォルダ名が設定されていません。' );
		$url            = static::$settings['endpoint'] . '/1.0/cabinet/trashbox/file/revert';
		$file_ids       = static::$data['params']['fileId'];
		$trashbox_files = static::trashbox_files_get();
		$target_files   = array_filter(
			$trashbox_files,
			function ( $file ) use ( $file_ids ) {
				return in_array( $file['FileId'], static::$data['params']['fileId'], true );
			},
		);
		$folders        = static::folders_get();
		$requests       = array_map(
			function ( $file ) use ( $folders, $url ) {
				$xml_request_body = new SimpleXMLElement( '<?xml version="1.0" encoding="UTF-8"?><request></request>' );
				$foleder_index    = array_search(
					$file['FolderPath'],
					array_column( $folders, 'FolderPath' ),
					true,
				);
				$xml_array        = array(
					'fileRevertRequest' => array(
						'file' => array(
							'fileId'   => $file['FileId'],
							'folderId' => $folders[ $foleder_index ]['FolderId'],
						),
					),
				);
				// SimpleXMLElementオブジェクトを文字列に変換
				static::array_to_xml( $xml_array, $xml_request_body );
				$xml_data = $xml_request_body->asXML();

				return array(
					'url'  => $url,
					'type' => Requests::POST,
					'data' => $xml_data,
				);

			},
			$target_files
		);

		$response = N2_Multi_URL_Request_API::ajax(
			array(
				'requests' => $requests,
				'call'     => 'request_multiple',
				'mode'     => 'func',
				'headers'  => static::$data['header'],
			),
		);
		return $response;
	}

	/**
	 * Cabinetの利用状況
	 */
	public static function usage_get() {
		$url = static::$settings['endpoint'] . '/1.0/cabinet/usage/get';

		$data = wp_remote_get( $url, array( 'headers' => static::$data['header'] ) );
		$body = wp_remote_retrieve_body( $data );
		$body = simplexml_load_string( $body );

		return $body;
	}


}
