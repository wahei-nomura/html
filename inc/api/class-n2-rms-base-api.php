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

		return self::$data['connect'];
	}

	/**
	 * RMSのAPIキーをスプシから取得してセット
	 */
	private static function set_api_keys() {
		$transient = 'rms_api_auth_key';
		$authkey   = static::get_decrypted_data_from_transient( $transient );
		if ( ! $authkey ) {
			global $n2, $n2_sync;
			$keys           = $n2_sync->get_spreadsheet_data( static::$settings['sheetId'], static::$settings['range'] );
			$keys           = array_filter( $keys, fn( $v ) => $v['town'] === $n2->town );
			$keys           = call_user_func_array( 'array_merge', $keys );
			$service_secret = $keys['serviceSecret'] ?? '';
			$license_key    = $keys['licenseKey'] ?? '';
			if ( ! ( $service_secret && $license_key ) ) {
				return array();
			}
			$authkey = "{$service_secret}:{$license_key}";
			$save    = static::save_encrypted_data_to_transient( $transient, $authkey );
		}
		// base64_encode
		$authkey = base64_encode( $authkey );
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
		$is_callable = is_callable( array( 'static', static::$data['params']['request'] ?? '' ) );
		static::check_fatal_error( $is_callable, '未定義のmethodです' );
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

	/**
	 * 秘密鍵を暗号化してtransientに保存する関数
	 *
	 * @param  string $key transient key
	 * @param  string $val transient value
	 * @param  array  $opt option
	 *
	 * @return bool 成功した場合はtrue、失敗した場合はfalse
	 */
	private static function save_encrypted_data_to_transient( $key, $val, $opt = array() ) {
		static::check_fatal_error( $key, '保存するkeyが設定されていません' );
		static::check_fatal_error( $val, '保存するvalueが設定されていません' );
		$default = array(
			'salt'       => SECURE_AUTH_SALT,
			'expiration' => 10 * MINUTE_IN_SECONDS,
		);
		// デフォルト値を$optで上書き
		$opt = wp_parse_args( $opt, $default );
		// ソルトと秘密鍵を結合して暗号化
		$data_to_encrypt = $opt['salt'] . $val;
		// 16バイトのIVを生成
		$iv             = openssl_random_pseudo_bytes( 16 );
		$encrypted_data = openssl_encrypt( $data_to_encrypt, 'AES-256-CBC', $opt['salt'], 0, $iv );
		// transientに暗号化したデータとIVを保存
		$data_to_save = wp_json_encode(
			array(
				'iv'             => base64_encode( $iv ),
				'encrypted_data' => $encrypted_data,
			)
		);

		// transientに暗号化したデータを保存
		return set_transient( $key, $data_to_save, $opt['expiration'] );
	}

	/**
	 * transientから暗号化された値を取得し、復号化する関数
	 *
	 * @param string $key  transient key
	 * @param string $salt salt
	 *
	 * @return string|bool 復号化した秘密鍵。失敗した場合はfalse
	 */
	private static function get_decrypted_data_from_transient( $key, $salt = null ) {
		$salt ??= SECURE_AUTH_SALT;
		// transientから暗号化されたデータを取得
		$encrypted_data = get_transient( $key );
		if ( $encrypted_data ) {
			$data           = json_decode( $encrypted_data, true );
			$iv             = base64_decode( $data['iv'] );
			$encrypted_data = $data['encrypted_data'];
			// 暗号化されたデータを復号化
			$decrypted_data = openssl_decrypt( $encrypted_data, 'AES-256-CBC', $salt, 0, $iv );

			// 復号化したデータからソルトを削除して秘密鍵を取得
			$key = str_replace( $salt, '', $decrypted_data );

			return $key;
		}

		return false;
	}
}
