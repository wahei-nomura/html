<?php
/**
 * RMS BASE API
 *
 * @package neoneng
 */

/**
 * RMS BASE API
 */
abstract class N2_RMS_Base_API {

	/**
	 * 設定（基本的に拡張で上書きする）
	 *
	 * @var class
	 */
	protected static $settings = array(
		'sheetId'  => '1FrFJ7zog1WUCsiREFOQ2pGAdhDYveDgBmGdaITrWeCo', // RMSのキー取得の為のスプレットシートID
		'range'    => 'RMS_API', // RMSのキー取得の為のスプレットシート範囲
		'endpoint' => 'https://api.rms.rakuten.co.jp/es/',
	);

	/**
	 * データ
	 *
	 * @var array
	 */
	protected static $data = array(
		'connect'  => null,
		'params'   => array(),
		'header'   => array(),
		'response' => array(),
		'error'    => array(),
		'string'   => '',
	);
	/**
	 * option_name
	 *
	 * @var string
	 */
	protected $option_name;

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		$this->option_name = mb_strtolower( get_class( $this ) );
		add_action( 'wp_ajax_' . $this->option_name . '_ajax', array( $this, 'ajax' ) );
		add_filter( $this->option_name . '_request', array( $this, 'check_error' ) );
	}

	/**
	 * 接続確認用API
	 */
	protected static function connect() {
		$path                  = '/1.0/shop/shopMaster';
		$data                  = wp_remote_get( static::$settings['endpoint'] . $path, array( 'headers' => static::$data['header'] ) );
		$code                  = $data['response']['code'];
		self::$data['connect'] = 200 === $code;
		// 利用可能か確認
		static::check_fatal_error( self::$data['connect'], 'RMS APIにアクセスできません' );
		return self::$data['connect'];
	}

	/**
	 * RMSのAPIキーをスプシから取得してセット
	 */
	private static function set_api_keys() {
		global $n2, $n2_sync;
		$keys = $n2_sync->get_spreadsheet_data( static::$settings['sheetId'], static::$settings['range'] );
		$keys = array_filter( $keys, fn( $v ) => $v['town'] === $n2->town );
		$keys = call_user_func_array( 'array_merge', $keys );
		// base64_encode
		$authkey = base64_encode( "{$keys['serviceSecret']}:{$keys['licenseKey']}" );
		return array(
			'Authorization' => "ESA {$authkey}",
		);
	}

	/**
	 * ヘッダー配列の作成
	 */
	private static function set_header() {
		if ( empty( static::$data['header'] ) ) {
			/**
			 * [hook] n2_rms_base_api_set_header
			 */
			static::$data['header'] = apply_filters( mb_strtolower( get_called_class() ) . '_set_header', self::set_api_keys() );;
		}
	}

	/**
	 * 各パラメータ配列の作成
	 *
	 * @param array $args args
	 */
	private static function set_params( $args ) {
		// $_GETを引数で上書き
		$params = wp_parse_args( $args, $_GET );
		// $_POSTを$paramsで上書き
		if ( wp_verify_nonce( $_POST['n2nonce'] ?? '', 'n2nonce' ) ) {
			$params = wp_parse_args( $params, $_POST );
		}
		$default = array(
			'mode'    => 'func',
			'request' => 'anonymous',
			'action'  => false,
		);
		// デフォルト値を$paramsで上書き
		$params = wp_parse_args( $params, $default );

		/**
		 * [hook] n2_rms_base_api_set_params
		 */
		static::$data['params'] = apply_filters( mb_strtolower( get_called_class() ) . '_set_params', $params );
	}

	/**
	 * APIを実行するサムシング
	 */
	private static function request() {
		return static::{ static::$data['params']['request'] }();
	}

	/**
	 * 出力用
	 */
	private static function export() {
		switch ( static::$data['params']['mode'] ) {
			case 'debug': // デバッグモード
				header( 'Content-Type: application/json; charset=utf-8' );
				print_r( static::$data['params'] );
				print_r( static::$data['response'] );
				exit;
			case 'json': // json出力
				header( 'Content-Type: application/json; charset=utf-8' );
				$json = wp_json_encode( static::$data['response'], JSON_UNESCAPED_UNICODE );
				echo $json;
				exit;
		}
	}

	/**
	 * 実行
	 *
	 * @param array|void $args args
	 * @return array|void
	 */
	public static function ajax( $args ) {

		static::set_header();
		static::set_params( $args );

		static::$data['response'] = static::request();

		// 出力時はここで終了
		static::export();

		// エクスポートしない場合
		return static::$data['response'];
	}

	/**
	 * ajaxデフォルト関数
	 */
	private static function anonymous() {
		print 'anonymous';
		exit;
	}

	/**
	 * 致命的なエラーのチェック
	 *
	 * @param array  $data チェックするデータ
	 * @param string $message メッセージ
	 */
	protected static function check_fatal_error( $data, $message ) {
		if ( ! $data ) {
			header( 'Content-Type: application/json; charset=utf-8' );
			echo $message;
			exit;
		}
	}
}
