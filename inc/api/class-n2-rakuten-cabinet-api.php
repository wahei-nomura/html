<?php
/**
 * RMS CABINET API
 *
 * @package neoneng
 */

if ( class_exists( 'N2_Rakuten_Cabinet_API' ) ) {
	new N2_Rakuten_Cabinet_API();
	return;
}

/**
 * N2からCABINETへ送信したりするAPI
 */
class N2_Rakuten_Cabinet_API extends N2_Rakuten_RMS_Base_API {
	/**
	 * option_name
	 *
	 * @var string
	 */
	protected $option_name = 'n2_rakuten_cabinet_api';
	/**
	 * connect
	 *
	 * @var bool
	 */
	protected $is_connect;
	/**
	 * フォルダ一覧
	 *
	 * @var array
	 */
	protected $folders = array();

	/**
	 * コンストラクター
	 */
	public function __construct() {
		parent::__construct();
		add_action( 'wp_ajax_n2_rakuten_cabinet_files', array( $this, 'files_get' ) );
		add_action( 'wp_ajax_n2_rakuten_cabinet_files_search', array( $this, 'files_search' ) );
		add_action( 'wp_ajax_n2_rakuten_cabinet_folders', array( $this, 'folders_get' ) );
	}

	/**
	 * フォルダ一覧取得
	 *
	 * @return array フォルダ一覧
	 */
	public function folders_get() {
		if ( ! $this->is_connect ) {
			return false;
		}
		$header  = parent::set_api_keys();
		$params  = array(
			'limit' => 100,
		);
		$url     = self::ENDPOINT . 'cabinet/folders/get?' . http_build_query( $params );
		$data    = wp_remote_get( $url, array( 'headers' => $header ) );
		$folders = simplexml_load_string( $data['body'] )->cabinetFoldersGetResult->folders;
		$folders = json_decode( wp_json_encode( $folders ), true )['folder'];

		return $folders;
	}
	/**
	 * ファイル一覧取得
	 *
	 * @param string $args パラメータ
	 * @return array ファイル一覧
	 */
	public function files_get( $args ) {
		$sku              = $args ?: $_GET['sku'];
		$action           = $_GET['action'] ?? false;
		$this->is_connect = $this->connect();
		$this->folders    = $this->folders_get();
		if ( ! $this->folders ) {
			return false;
		}
		$folder_id = $this->folders[ array_search( $sku, array_column( $this->folders, 'FolderName' ), true ) ]['FolderId'];

		$header     = parent::set_api_keys();
		$limit      = 100;
		$params     = array(
			'folderId' => $folder_id,
			'limit'    => $limit,
			'offset'   => 1,
		);
		$file_count = $limit;
		$files      = array();
		do {
			$url       = self::ENDPOINT . 'cabinet/folder/files/get?' . http_build_query( $params );
			$data      = wp_remote_get( $url, array( 'headers' => $header ) );
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

		if ( $action ) {
			header( 'Content-Type: application/json; charset=utf-8' );
			$json = wp_json_encode( $files, JSON_UNESCAPED_UNICODE );
			echo $json;
			exit;
		}
		return files;
	}
	/**
	 * ファイル一覧(検索)
	 *
	 * @param array $args パラメータ
	 */
	public static function files_search( $args ) {
		$files = array();
		$sku   = $args['sku'] ?? $_GET['sku'] ?? null;
		if ( null === $sku ) {
			return $files;
		}

		$action     = false;
		$header     = $args['header'] ?? parent::set_api_keys();
		$params     = array(
			'fileName' => $sku,
			'limit'    => 100,
			'offset'   => 1,
		);
		$limit      = 100;
		$file_count = 100;

		do {
			$url        = self::ENDPOINT . 'cabinet/files/search?' . http_build_query( $params );
			$data       = wp_remote_get( $url, array( 'headers' => $header ) );
			$file_count = (int) simplexml_load_string( $data['body'] )->cabinetFilesSearchResult->fileAllCount->__toString() - 1;
			$req_files  = simplexml_load_string( $data['body'] )->cabinetFilesSearchResult->files;
			$req_files  = json_decode( wp_json_encode( $req_files ), true )['file'] ?? array();
			$files      = array(
				...$files,
				...$req_files,
			);
			++$params['offset'];
		} while ( ( $params['offset'] - 1 ) * $limit < $file_count );

		if ( $action ) {
			header( 'Content-Type: application/json; charset=utf-8' );
			$json = wp_json_encode( $files, JSON_UNESCAPED_UNICODE );
			echo $json;
			exit;
		}
		return $files;
	}
}
