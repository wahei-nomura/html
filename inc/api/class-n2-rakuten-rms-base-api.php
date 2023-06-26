<?php
/**
 * RMS API
 *
 * @package neoneng
 */

if ( class_exists( 'N2_Rakuten_RMS_Base_API' ) ) {
	new N2_Rakuten_RMS_Base_API();
	return;
}
/**
 * 寄附金額の計算のためのAPI
 */
class N2_Rakuten_RMS_Base_API {

	/**
	 * option_name
	 *
	 * @var string
	 */
	protected $option_name = 'n2_rakuten_rms_base_api';

	/**
	 * RMSのキー取得の為のスプレットシートID
	 *
	 * @var string
	 */
	public static $sheetid = '1FrFJ7zog1WUCsiREFOQ2pGAdhDYveDgBmGdaITrWeCo';

	/**
	 * RMSのキー取得の為のスプレットシート範囲
	 *
	 * @var string
	 */
	private static $range = 'RMS_API';

	/**
	 * 楽天ショップコード
	 *
	 * @var string
	 */
	protected $shop_code = '';

	/**
	 * Cabinet フォルダ一覧
	 *
	 * @var string
	 */
	protected $cabinet_folders = array();

	/**
	 *  エンドポイント
	 */
	public const ENDPOINT = 'https://api.rms.rakuten.co.jp/es/1.0/';

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		add_action( 'n2_rakuten_rms_api_connect', array( $this, 'connect' ) );
		add_action( 'wp_ajax_n2_rakuten_rms_api_connect', array( $this, 'connect' ) );
	}


	/**
	 * 外部用API
	 */
	public function get() {
		$data = get_option( $this->option_name );
		$data = wp_json_encode( $data, JSON_UNESCAPED_UNICODE );
		header( 'Content-Type: application/json; charset=utf-8' );
		echo $data;
		exit;
	}


	/**
	 * RMSがアクセス可能か判定用
	 *
	 * @return array
	 */
	public static function connect() {
		$path     = 'shop/shopMaster';
		$header   = self::set_api_keys();
		$data     = wp_remote_get( self::ENDPOINT . $path, array( 'headers' => $header ) );
		$code     = $data['response']['code'];
		$response = array(
			'code' => $code,
		);
		return $response;
	}

	/**
	 * RMSのAPIキーをスプシから取得してセット
	 */
	public static function set_api_keys() {
		global $n2, $n2_sync;
		$keys = $n2_sync->get_spreadsheet_data( self::$sheetid, self::$range );
		$keys = array_filter( $keys, fn( $v ) => $v['town'] === $n2->town );
		$keys = call_user_func_array( 'array_merge', $keys );
		// base64_encode
		$authkey = base64_encode( "{$keys['serviceSecret']}:{$keys['licenseKey']}" );
		return array(
			'Authorization' => "ESA {$authkey}",
		);
	}
}
