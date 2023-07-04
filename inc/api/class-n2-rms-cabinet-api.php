<?php
/**
 * RMS CABINET API
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

		$sku                               = static::$data['params']['sku'];
		static::$data['params']['cabinet'] = self::folders_get();

		static::check_fatal_error( static::$data['params']['cabinet'], 'フォルダーIDの取得に失敗しました。' );

		$forder_name = array_search( $sku, array_column( static::$data['params']['cabinet'], 'FolderName' ), true );
		$folder_id   = static::$data['params']['cabinet'][ $forder_name ]['FolderId'];

		$limit      = 100;
		$params     = array(
			'folderId' => $folder_id,
			'limit'    => $limit,
			'offset'   => 1,
		);
		$file_count = $limit;
		$files      = array();
		do {
			$url       = self::$settings['endpoint'] . '/1.0/cabinet/folder/files/get?' . http_build_query( $params );
			$data      = wp_remote_get( $url, array( 'headers' => static::$data['header'] ) );
			$res_files = simplexml_load_string( $data['body'] )->cabinetFolderFilesGetResult->files;
			$res_files = json_decode( wp_json_encode( $res_files ), true )['file'];

			$files      = array(
				...$files,
				...$res_files,
			);
			$file_count = (int) simplexml_load_string( $data['body'] )->cabinetFolderFilesGetResult->fileAllCount->__toString() - 1;
			++$params['offset'];
		} while ( ( $params['offset'] - 1 ) * $limit < $file_count );
		$files = array_map(
			function( $file ) {
				return $file['FileUrl'];
			},
			$files,
		);

		return $files;
	}
	/**
	 * ファイル一覧(検索)
	 * 
	 * @return array 検索結果
	 */
	public static function files_search() {
		$files = array();

		static::check_fatal_error( static::$data['params']['sku'] ?? false, '検索ワードが設定されていません。' );

		$params     = array(
			'fileName' => static::$data['params']['sku'],
			'limit'    => 100,
			'offset'   => 1,
		);
		$limit      = 100;
		$file_count = 100;

		do {
			$url        = static::$settings['endpoint'] . '/1.0/cabinet/files/search?' . http_build_query( $params );
			$data       = wp_remote_get( $url, array( 'headers' => static::$data['header'] ) );
			$file_count = (int) simplexml_load_string( $data['body'] )->cabinetFilesSearchResult->fileAllCount->__toString() - 1;
			$req_files  = simplexml_load_string( $data['body'] )->cabinetFilesSearchResult->files;
			$req_files  = json_decode( wp_json_encode( $req_files ), true )['file'] ?? array();
			$files      = array(
				...$files,
				...$req_files,
			);
			++$params['offset'];
		} while ( ( $params['offset'] - 1 ) * $limit < $file_count );

		return $files;
	}
}
