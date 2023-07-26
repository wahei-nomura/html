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

		$keywords                          = static::$data['params']['keywords'];
		static::$data['params']['cabinet'] = self::folders_get();

		static::check_fatal_error( static::$data['params']['cabinet'], 'フォルダーIDの取得に失敗しました。' );

		$folder_name_list = array_column( static::$data['params']['cabinet'], 'FolderName' );

		foreach ( $keywords as $keyword ) {
			$forder_name = array_search( $keyword, $folder_name_list, true );

			if ( false !== $forder_name ) {
				$exist_forder[] = $forder_name;
			}
		}
		$folder_ids = array_map(
			function ( $folder ) {
				$dict[ $folder ] = static::$data['params']['cabinet'][ $folder ]['FolderId'];
				return static::$data['params']['cabinet'][ $folder ]['FolderId'];
			},
			$exist_forder
		);

		$urls  = array_map(
			function ( $id ) {
				$params = array(
					'folderId' => $id,
					'limit'    => 100,
				);
				return self::$settings['endpoint'] . '/1.0/cabinet/folder/files/get?' . http_build_query( $params );
			},
			$folder_ids,
		);
		$files = array();

		$response = N2_Multi_URL_Request_API::ajax(
			array(
				'urls'    => $urls,
				'header'  => static::$data['header'],
				'request' => 'request_multiple',
				'mode'    => 'func',
			),
		);
		foreach ( $response as $res ) {
			$keyword           = $res->headers->getValues( 'folderid' )[0];
			$keyword           = array_search( $keyword, $dict, true );
			$res_files         = simplexml_load_string( $res->body )->cabinetFolderFilesGetResult->files;
			$res_files         = json_decode( wp_json_encode( $res_files ), true )['file'] ?? array();
			$files[ $keyword ] = $res_files;
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

}
