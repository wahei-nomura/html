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
	 * @var    string $folderId forlder id
	 * @return array ファイル一覧
	 */
	public static function files_get( $folderId ) {
		$files_get_params = array(
			'folderId' => $folderId,
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
		$requests = array_map(
			function( $offset ) use ( $files_get_params ) {
				$files_get_params['offset'] = $offset;
				return array(
					'url' => static::$settings['endpoint'] . '/1.0/cabinet/folder/files/get?' . http_build_query( $files_get_params ),
					'headers'  => static::$data['header'],
				);
			},
			range( 2, floor( $file_count / $files_get_params['limit'] ) + 1 )
		);

		$response = N2_Multi_URL_Request_API::request_multiple( $requests );

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
	 * @var    string $keywords keywords
	 * @return array 検索結果
	 */
	public static function files_search( $keywords ) {
		$files = array();
		$limit    = 100;

		$requests = array_map(
			function ( $keyword ) use ( $limit ) {
				$params = array(
					'fileName' => $keyword,
					'limit'    => $limit,
				);
				return array(
					'url' => static::$settings['endpoint'] . '/1.0/cabinet/files/search?' . http_build_query( $params ),
					'headers'  => static::$data['header'],
				);
			},
			$keywords,
		);

		$response = N2_Multi_URL_Request_API::request_multiple( $requests );

		foreach ( $response as $res ) {
			$keyword        = urldecode( $res->headers->getValues( 'filename' )[0] );
			$search_result  = simplexml_load_string( $res->body )->cabinetFilesSearchResult;
			$res_files      = $search_result->files;
			$file_all_count = $search_result->fileAllCount;
			$file_count     = (int) $search_result->fileCount->__toString();
			$res_files      = json_decode( wp_json_encode( $res_files ), true )['file'] ?? array();

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
						'headers'  => static::$data['header'],
					);
				},
				range( 2, floor( $file_all_count / $limit ) + 1 ),
			);

			$additional_response = N2_Multi_URL_Request_API::request_multiple($additional_requests );

			foreach ( $additional_response as $additional_res ) {
				$search_result     = simplexml_load_string( $additional_res->body )->cabinetFilesSearchResult;
				$res_files         = $search_result->files;
				$file_count        = (int) $search_result->fileCount->__toString();
				$res_files         = json_decode( wp_json_encode( $res_files ), true )['file'] ?? array();
				$files[ $keyword ] = match ( $file_count > 1 ) {
					true => array( ...$files[ $keyword ], ...$res_files ),
					default => array( ...$files[ $keyword ], array( $res_files ) ),
				};
			}
		}
		return $files;
	}

	/**
	 * フォルダ追加
	 *
	 * @var string $folderName folderName
	 * @var string $directoryName directoryName
	 * @var string $upperFolderId upperFolderId
	 * @return array
	 */
	public static function folder_insert( $folderName, $directoryName = '', $upperFolderId = '', ) {
		$url              = static::$settings['endpoint'] . '/1.0/cabinet/folder/insert';
		$xml_request_body = new SimpleXMLElement( '<?xml version="1.0" encoding="UTF-8"?><request></request>' );
		$request = array(
			'folderInsertRequest' => array(
				'folder' => array(
					'folderName'    => $folderName,
					'directoryName' => $directoryName,
					'upperFolderId' => $upperFolderId,
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
	 *
	 * @var array  $files    files
	 * @var string $folderId folderId
	 */
	public static function file_insert( $files, $folderId, $tmp_path = null ) {
		static::check_fatal_error( $files['tmp_name'][0], 'ファイルをセットしてください。' );
		$url = static::$settings['endpoint'] . '/1.0/cabinet/file/insert';

		$requests = array();
		foreach ( $files['tmp_name'] as $index => $tmp_name ) {
			$file_content_type = mime_content_type( $tmp_name );
			$file_name         = $files['name'][ $index ];
			$request           = array(
				'url'     => $url,
				'type'    => Requests::POST,
				'headers' => array(
					'Content-Type' => 'multipart/form-data;',
					...static::$data['header'],
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
						'filePath'  => preg_replace( '/\.[^.]+$/', '.jpg', $file_name ),
						'fileName'  => preg_replace( '/\.[^.]+$/', '', $file_name ),
						'folderId'  => $folderId,
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
		$response = N2_Multi_URL_Request_API::request_multiple( $requests );
		if ( isset( $tmp_path ) ) {
			exec( "rm -Rf {$tmp_path}" );
		}
		return $response;
	}

	/**
	 * ファイル移動
	 * 公式には存在しないのでfile_insertとfile_deleteを組み合わせる
	 *
	 * @var array  $fileId          fileId
	 * @var string $currentFolderId currentFolderId
	 * @var string $targetFolderId  targetFolderId
	 */
	public static function files_move( $fileId, $currentFolderId, $targetFolderId) {

		// 　必須項目を確認
		static::check_fatal_error( ! empty( $fileId ?? array() ), 'ファイルIdが設定されていません。' );

		$files = static::files_get( $currentFolderId );
		// 必要なfileのみに絞る
		$files = array_filter(
			$files,
			function( $file ) use ($fileId ) {
				return in_array( $file['FileId'], $fileId );
			},
		);
		// indexを振り直す
		$files = array_values( $files );

		// 一時ディレクトリ作成
		$tmp = wp_tempnam( __CLASS__, get_theme_file_path() . '/' );
		unlink( $tmp );
		mkdir( $tmp );

		$requests  = array_map(
			function( $file ) use ( $tmp ) {
				return array(
					'url'     => $file['FileUrl'],
					'options' => array(
						'filename' => $tmp . '/' . basename( $file['FileUrl'] ),
					),
				);
			},
			$files,
		);
		$responses = N2_Multi_URL_Request_API::request_multiple( $requests );

		// 初期化
		$files = array();

		// insertするfileをstatic::$data['file']に設定する
		foreach ( $responses as $index => $response ) {
			if ( ! is_a( $response, 'WpOrg\Requests\Response' ) || $response->status_code !== 200 ) {
				continue;
			}
			$filename                                    = $requests[ $index ]['options']['filename'];
			$files['name'][ $index ]     = basename( $filename );
			$files['type'][ $index ]     = mime_content_type( $filename );
			$files['tmp_name'][ $index ] = $filename;
			$files['error'][ $index ]    = 0;
			$files['size'][ $index ]     = filesize( $filename );
		}

		$insert_response       = static::file_insert( $files, $targetFolderId, $tmp );
		$insert_error_response = array_filter(
			$insert_response,
			function( $res ) {
				return $res->status_code !== 200;
			},
		);
		// 失敗した場合は移動前のファイルを残す
		$fileId = array_filter(
			$fileId,
			function ( $key ) use ( $insert_error_response ) {
				return ! in_array( $key, array_keys( $insert_error_response ) );
			},
			ARRAY_FILTER_USE_KEY
		);
		return static::file_delete( $fileId );
	}

	/**
	 * ファイル削除
	 *
	 * @var array $fileId fileId
	 */
	public static function file_delete( $fileId ) {
		static::check_fatal_error( ! empty( $fileId ?? array() ), 'ファイルIdが設定されていません。' );

		$url      = static::$settings['endpoint'] . '/1.0/cabinet/file/delete';
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
				// SimpleXMLElementオブジェクトを文字列に変換
				static::array_to_xml( $request, $xml_request_body );
				$xml_data = $xml_request_body->asXML();
				return array(
					'url'  => $url,
					'type' => Requests::POST,
					'data' => $xml_data,
					'headers'  => static::$data['header'],
				);
			},
			$fileId,
		);

		$response = N2_Multi_URL_Request_API::request_multiple( $requests );

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
		$requests = array_map(
			function( $offset ) use ( $files_get_params ) {
				$files_get_params['offset'] = $offset;
				return array(
					'url' => $url . http_build_query( $files_get_params ),
					'headers'  => static::$data['header'],
				);
			},
			range( 2, floor( $file_count / $files_get_params['limit'] ) + 1 )
		);

		$response = N2_Multi_URL_Request_API::request_multiple( $requests );

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
	 *
	 * @var array $fileId fileId
	 */
	public static function trashbox_files_revert( $fileId ) {
		static::check_fatal_error( ! empty( $fileId ?? array() ), 'フォルダ名が設定されていません。' );
		$url            = static::$settings['endpoint'] . '/1.0/cabinet/trashbox/file/revert';
		$trashbox_files = static::trashbox_files_get();
		$target_files   = array_filter(
			$trashbox_files,
			function ( $file ) use ( $fileId ) {
				return in_array( $file['FileId'], $fileId, true );
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
					'headers'  => static::$data['header'],
				);

			},
			$target_files
		);

		$response = N2_Multi_URL_Request_API::request_multiple( $requests );
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
