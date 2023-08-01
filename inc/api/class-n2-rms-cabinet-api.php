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


		static::check_fatal_error( static::$data['params']['folderId'] !== null, 'フォルダーIDの取得に失敗しました。' );

		$params = array(
			'folderId' => static::$data['params']['folderId'],
			'limit'    => 100,
			'offset'   => 1,
		);

		$url      = static::$settings['endpoint'] . '/1.0/cabinet/folder/files/get?' . http_build_query( $params );
		
		$files = array();
		$urls  = array();

		$response = wp_remote_get( $url, array('headers'=> static::$data['header']) );

		$result     = simplexml_load_string( $response['body'] )->cabinetFolderFilesGetResult;
		$file_count = $result->fileAllCount;   
		$res_files  = $result->files;
		$res_files  = json_decode( wp_json_encode( $res_files ), true )['file'] ?? array();
		$files      = match( $file_count > 1 ) {
			true => $res_files,
			default => array( $res_files ),
		};

		return $files;

		for ($i=1; $i < $file_count % $params['limit']; $i++) { 
			$params['offset'] += 1;
			$urls[] = static::$settings['endpoint'] . '/1.0/cabinet/folder/files/get?' . http_build_query( $params );
		}
		if ( empty( $urls ) ) {
			return $files;
		}
		
		$response = N2_Multi_URL_Request_API::ajax(
			array(
				'urls'    => $urls,
				'header'  => static::$data['header'],
				'request' => 'request_multiple',
				'mode'    => 'func',
			),
		);

		foreach ( $response as $res ) {
			$result     = simplexml_load_string( $res->body )->cabinetFolderFilesGetResult;
			$res_files  = $result->files;
			$res_files  = json_decode( wp_json_encode( $res_files ), true )['file'] ?? array();
			$files      = array( ...$files, ...$res_files );
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

		$urls = array_map(
			function ( $keyword ) {
				$params = array(
					'fileName' => $keyword,
					'limit'    => 100,
				);
				return static::$settings['endpoint'] . '/1.0/cabinet/files/search?' . http_build_query( $params );
			},
			$keywords,
		);

		$response = N2_Multi_URL_Request_API::ajax(
			array(
				'urls'    => $urls,
				'header'  => static::$data['header'],
				'request' => 'request_multiple',
				'mode'    => 'func',
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
	public static function folder_insert () {
		static::check_fatal_error( static::$data['params']['folderName'] ?? false, 'フォルダ名が設定されていません。' );
		static::check_fatal_error( static::$data['params']['directoryName'] ?? false, 'directory名が設定されていません。' );
		static::check_fatal_error( static::$data['params']['upperFolderId'] ?? false, '上位階層フォルダIDが設定されていません。' );

		$url = static::$settings['endpoint'] . '/1.0/cabinet/folder/insert';
		$xml_request_body = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><request></request>');

		$request = array(
			'folderInsertRequest' => array(
				'folder' => array(
					'folderName' => static::$data['params']['folderName'],
					'directoryName' => static::$data['params']['directoryName'],
					'upperFolderId' => static::$data['params']['upperFolderId'],
				),
			),
		);
		static::array_to_xml( $request, $xml_request_body );
		// SimpleXMLElementオブジェクトを文字列に変換
		$xml_data = $xml_request_body->asXML();

		$request_args = array(
			'method'      => 'POST',
			'headers'     => array(
				...static::$data['header'],
			),
			'body'        => $xml_data, // XMLデータをリクエストボディに設定
		);
		$response = wp_remote_request( $url, $request_args );

		$response_body = wp_remote_retrieve_body($response);

		return $response_body;
	}
	/**
	 * ファイル追加
	 */
	public static function file_insert () {
		// static::check_fatal_error( static::$data['params']['fileName'] ?? false, 'フォルダ名が設定されていません。' );
		// static::check_fatal_error( static::$data['params']['folderId'] ?? false, 'directory名が設定されていません。' );

		$url = static::$settings['endpoint'] . '/1.0/cabinet/file/insert';
		$xml_request_body = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><request></request>');

		$request = array(
			'fileInsertRequest' => array(
				'file' => array(
					'fileName' => static::$data['params']['folderName'],
					'filePath' => static::$data['params']['filePath'],
					'folderId' => static::$data['params']['folderId'],
					'overWrite' => static::$data['params']['overWrite'] ?? true,
				),
			),
		);
		static::array_to_xml( $request, $xml_request_body );
		// SimpleXMLElementオブジェクトを文字列に変換
		$xml_data = $xml_request_body->asXML();

		$request_args = array(
			'method'      => 'POST',
			'headers'     => array(
				...static::$data['header'],
			),
			'body'        => array(
				'xml' => $xml_data, // XMLデータをリクエストボディに設定
				'file' => static::$data['params']['files'],
			),
		);
		// $response = wp_remote_request( $url, $request_args );

		// $response_body = wp_remote_retrieve_body($response);

		return $_FILES;
		// return $request_args;
	}

}
