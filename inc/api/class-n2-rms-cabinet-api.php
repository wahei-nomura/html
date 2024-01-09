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
	 * レスポンスからファイルをサルベージ
	 *
	 * @var int          $file_all_count file_all_count
	 * @var string       $result_key     result_key
	 * @var array|object $response       response
	 * @return array     files
	 */
	private static function response_files( &$file_all_count ) {
		return function( $result_key ) use ( &$file_all_count ) {
			return function ( $response ) use ( $result_key, &$file_all_count ) {
				$response = (array) $response; // WpOrg\Requests\Response Objectを変換
				$result = simplexml_load_string( $response['body'] )->{$result_key};
				$files = (array) $result->files;
				$files = $files['file'] ?? array();
				$file_all_count = (int) $result->fileAllCount;
				return match ( (int) $result->fileCount > 1 ) {
					true => $files,
					default => array( $files),
				};
			};
		};
	}

	/**
	 * フォルダ一覧取得
	 *
	 * @return array フォルダ一覧
	 */
	public static function folders_get() {
		$limit            = 100;
		$url              = fn ( $offset = 1 ) => static::$settings['endpoint'] . '/1.0/cabinet/folders/get?' . http_build_query(
			array(
				'limit'  => $limit,
				'offset' => $offset,
			)
		);
		$response         = static::request( $url() );
		$response_folders = function ( $res ) use ( &$folder_all_count ) {
			$res              = (array) $res;
			$result           = simplexml_load_string( $res['body'] )->cabinetFoldersGetResult;
			$folder_all_count = (int) $result->folderAllCount;
			$result           = (array) $result->folders;
			return $result['folder'];
		};
		$folders          = $response_folders( $response );
		if ( $folder_all_count <= $limit ) {
			return $folders;
		}
		$requests = array_map(
			fn ( $offset ) => array(
				'url' => $url( $offset ),
			),
			range( 2, ceil( $folder_all_count / $limit ) )
		);
		foreach ( static::request_multiple( $requests ) as $res ) {
			$folders = array( ...$folders, ...$response_folders( $res ) );
		}
		return $folders;
	}
	/**
	 * ファイル一覧取得
	 *
	 * @var    string $folderId forlder id
	 * @return array ファイル一覧
	 */
	public static function files_get( $folderId ) {
		$limit    = 100;
		$url      = fn( $offset = 1 ) => static::$settings['endpoint'] . '/1.0/cabinet/folder/files/get?' . http_build_query(
			array(
				'folderId' => $folderId,
				'limit'    => $limit,
				'offset'   => $offset,
			)
		);
		$response_files = static::response_files( $file_all_count )('cabinetFolderFilesGetResult');
		$response = static::request( $url() );
		$files = $response_files( $response );

		if ( $file_all_count <= $limit ) {
			return $files;
		}
		$requests = array_map(
			fn ( $offset ) => array(
				'url' => $url( $offset ),
			),
			range( 2, ceil( $file_all_count / $limit ) )
		);

		foreach ( static::request_multiple( $requests ) as $res ) {
			$files = array( ...$files, ...$response_files( $res ) );
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
		// 重複削除
		$keywords = array_unique( $keywords );
		$files    = array();
		$limit    = 100;
		$url      = fn ( $keyword ) => fn ( $offset = 1 ) => static::$settings['endpoint'] . '/1.0/cabinet/files/search?' . http_build_query(
			array(
				'fileName' => $keyword,
				'limit'    => $limit,
				'offset'   => $offset,
			)
		);
		$requests = array_map(
			fn ( $keyword ) => array(
				'url' => $url( $keyword )(),
			),
			$keywords,
		);
		$response_files = static::response_files( $file_all_count )('cabinetFilesSearchResult');
		foreach ( static::request_multiple( $requests ) as $res ) {
			$keyword        = urldecode( $res->headers->getValues( 'filename' )[0] );
			$files[ $keyword ] = $response_files( $res );
			if ( $file_all_count < $limit ) {
				continue;
			}

			// limit以上なら追加でAPIを叩く
			$additional_requests = array_map(
				fn ( $offset ) => array(
					'url' => $url( $keyword )( $offset ),
				),
				range( 2, ceil( $file_all_count / $limit ) ),
			);

			foreach ( static::request_multiple( $additional_requests ) as $res ) {
				$files[ $keyword ] = array( ...$files[ $keyword ], ...$response_files( $res ) );
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
		$request          = array(
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

		$request_args  = array(
			'body'    => $xml_data, // XMLデータをリクエストボディに設定
			'method'  => 'POST',
		);
		$response      = static::request( $url, $request_args );
		$response_body = wp_remote_retrieve_body( $response );
		return simplexml_load_string( $response_body );
	}
	/**
	 * ファイル追加
	 *
	 * @var array  $files    files
	 * @var string $folderId folderId
	 */
	public static function file_insert( $files, $folderId, $tmp_path = null ) {
		static::check_fatal_error( ! empty( $files['tmp_name'] ), 'ファイルをセットしてください。' );

		$requests = array();
		foreach ( $files['tmp_name'] as $index => $tmp_name ) {
			$file_content_type = mime_content_type( $tmp_name );
			$file_name         = $files['name'][ $index ];
			$request           = array(
				'url'     => static::$settings['endpoint'] . '/1.0/cabinet/file/insert',
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
		$response = static::request_multiple( $requests );
		if ( isset( $tmp_path ) ) {
			exec( "rm -Rf {$tmp_path}" );
		}
		return $response;
	}

	/**
	 * ファイル移動
	 * 公式には存在しないのでfile_insertとfile_deleteを組み合わせる
	 *
	 * @var array  $fileIds          fileIds
	 * @var string $currentFolderId currentFolderId
	 * @var string $targetFolderId  targetFolderId
	 */
	public static function files_move( $fileIds, $currentFolderId, $targetFolderId) {

		// 　必須項目を確認
		static::check_fatal_error( ! empty( $fileIds ), 'ファイルIdが設定されていません。' );

		// 必要なfileのみに絞る
		$files = array_filter(
			static::files_get( $currentFolderId ),
			fn ( $file ) => in_array( (string) $file->FileId, $fileIds ),
		);
		// indexを振り直す
		$files = array_values( $files );

		// 一時ディレクトリ作成
		$tmp = wp_tempnam( __CLASS__, get_theme_file_path() . '/' );
		unlink( $tmp );
		mkdir( $tmp );

		$requests  = array_map(
			fn( $file ) => array(
				'url'     => (string) $file->FileUrl,
				'options' => array(
					'filename' => $tmp . '/' . basename( (string) $file->FileUrl ),
				),
			),
			$files,
		);

		// 初期化
		$files = array();

		// insertするfileをstatic::$data['file']に設定する
		foreach ( N2_Multi_URL_Request_API::request_multiple( $requests ) as $index => $response ) {
			if ( ! is_a( $response, 'WpOrg\Requests\Response' ) || $response->status_code !== 200 ) {
				continue;
			}
			$filename                    = $requests[ $index ]['options']['filename'];
			$files['name'][ $index ]     = basename( $filename );
			$files['type'][ $index ]     = mime_content_type( $filename );
			$files['tmp_name'][ $index ] = $filename;
			$files['error'][ $index ]    = 0;
			$files['size'][ $index ]     = filesize( $filename );
		}

		$insert_error = array_filter(
			static::file_insert( $files, $targetFolderId, $tmp ),
			fn ( $res ) => $res->status_code !== 200,
		);
		// 失敗した場合は移動前のファイルを残す
		$fileIds = array_filter(
			$fileIds,
			fn ( $key ) => ! in_array( $key, array_keys( $insert_error ) ),
			ARRAY_FILTER_USE_KEY,
		);
		return static::file_delete( $fileIds );
	}

	/**
	 * ファイル削除
	 *
	 * @var array $fileIds fileIds
	 */
	public static function file_delete( $fileIds ) {
		static::check_fatal_error( ! empty( $fileIds ), 'ファイルIdが設定されていません。' );
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
					'url'  => static::$settings['endpoint'] . '/1.0/cabinet/file/delete',
					'type' => Requests::POST,
					'data' => $xml_data,
				);
			},
			$fileIds,
		);
		return static::request_multiple( $requests );
	}

	/**
	 * 削除したファイル一覧
	 */
	public static function trashbox_files_get() {
		$limit = 100;
		$url   = fn( $offset = 1 ) => static::$settings['endpoint'] . '/1.0/cabinet/trashbox/files/get?' . http_build_query(
			array(
				'limit'  => $limit,
				'offset' => $offset,
			)
		);

		$response_files = static::response_files( $file_all_count )('cabinetTrashboxFilesGetResult');

		$response   = static::request( $url() );
		$files      = $response_files( $response );
		if ( $file_all_count <= $limit ) {
			return $files;
		}
		$requests = array_map(
			fn ( $offset ) => array(
				'url' => $url( $offset ),
			),
			range( 2, ceil( $file_all_count / $limit ) )
		);

		foreach ( static::request_multiple( $requests ) as $res ) {
			$files = array( ...$files, ...$response_files( $res ) );
		}
		return $files;
	}

	/**
	 * 削除したファイルを元に戻す
	 *
	 * @var array $fileIds fileIds
	 */
	public static function trashbox_files_revert( $fileIds ) {
		static::check_fatal_error( ! empty( $fileIds ), 'ファイルIdが設定されていません。' );

		$target_files = array_filter(
			static::trashbox_files_get(),
			fn ( $file ) => in_array( (string) $file->FileId, $fileIds, true ),
		);
		$folders      = static::folders_get();
		$requests     = array_map(
			function ( $file ) use ( $folders ) {
				$xml_request_body = new SimpleXMLElement( '<?xml version="1.0" encoding="UTF-8"?><request></request>' );
				$foleder_index    = array_search(
					$file->FolderPath,
					array_column( $folders, 'FolderPath' ),
					true,
				);
				$xml_array        = array(
					'fileRevertRequest' => array(
						'file' => array(
							'fileId'   => $file->FileId,
							'folderId' => $folders[ $foleder_index ]->FolderId,
						),
					),
				);
				// SimpleXMLElementオブジェクトを文字列に変換
				static::array_to_xml( $xml_array, $xml_request_body );
				$xml_data = $xml_request_body->asXML();

				return array(
					'url'  => static::$settings['endpoint'] . '/1.0/cabinet/trashbox/file/revert',
					'type' => Requests::POST,
					'data' => $xml_data,
				);
			},
			$target_files
		);
		return static::request_multiple( $requests );
	}

	/**
	 * Cabinetの利用状況
	 */
	public static function usage_get() {
		$url = static::$settings['endpoint'] . '/1.0/cabinet/usage/get';
		$data = static::request( $url );
		$body = wp_remote_retrieve_body( $data );
		return simplexml_load_string( $body );
	}
}
