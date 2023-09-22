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
		'sheetId'   => '1FrFJ7zog1WUCsiREFOQ2pGAdhDYveDgBmGdaITrWeCo', // RMSのキー取得の為のスプレットシートID
		'range'     => 'RMS_API', // RMSのキー取得の為のスプレットシート範囲
		'endpoint'  => 'https://api.rms.rakuten.co.jp/es',
		'transient' => array(
			'key'  => 'rms_api_auth_key',
			'salt' => SECURE_AUTH_SALT,
		),
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
	 * コンストラクタ
	 */
	public function __construct() {
		static::set_header();
		add_action( 'wp_ajax_' . mb_strtolower( get_class( $this ) ) . '_ajax', array( $this, 'ajax' ) );
	}

	/**
	 * 接続確認用API
	 */
	public static function connect() {
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
		$authkey   = static::get_decrypted_data_from_transient( static::$settings['transient']['key'] );
		if ( ! $authkey || ( static::$data['params']['apiUpdate'] ?? false ) ) {
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
			$save    = static::save_encrypted_data_to_transient( static::$settings['transient']['key'], $authkey );
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
			static::$data['header'] = apply_filters( mb_strtolower( get_called_class() ) . '_set_header', self::set_api_keys() );
		}
	}

	/**
	 * 各パラメータ配列の作成
	 */
	private static function set_params() {
		$params = $_GET;
		// $_POSTを$paramsで上書き
		if ( wp_verify_nonce( $_POST['n2nonce'] ?? '', 'n2nonce' ) ) {
			$params = wp_parse_args( $params, $_POST );
		}
		$default = array();
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
	private static function call() {
		$method = static::$data['params']['call'] ?? '';
		$is_callable = is_callable( array( 'static', $method ) );
		static::check_fatal_error( $is_callable, "未定義のmethodです: {$method}" );
		$arguments = static::$data['params'];
		// 不要なプロパティを削除
		unset(
			$arguments['call'],
			$arguments['n2nonce'],
			$arguments['action'],
			$arguments['mode'],
		);
		return call_user_func_array( array( 'static', $method ), $arguments );
	}

	/**
	 * wp_remote_requestのラッパー
	 *
	 * @param string $url  url
	 * @param array  $args args
	 */
	public static function request( $url, $args = array() ) {
		$args['headers'] = array(
			...$args['headers'] ?? array(),
			...static::$data['header'],
		);
		return wp_remote_request( $url, $args );
	}

	/**
	 * 認証を付与したrequest_multiple
	 *
	 * @param array $requests requests
	 * @param array $options  options
	 */
	public static function request_multiple( $requests, $options = array() ) {
		$requests = array_map(
			function( $request ) {
				$request['headers'] = array(
					...$request['headers'] ?? array(),
					...static::$data['header'],
				);
				return $request;
			},
			$requests,
		);
		return N2_Multi_URL_Request_API::request_multiple( $requests, $options );
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
	 * @return array|void
	 */
	public static function ajax() {

		static::set_params();
		static::set_header();
		static::set_files();
		static::$data['response'] = static::call();
		static::export();
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
			'expiration' => 24 * HOUR_IN_SECONDS,
		);
		// デフォルト値を$optで上書き
		$opt = wp_parse_args( $opt, $default );
		// serialize
		$val = maybe_serialize( $val );
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
			$data = str_replace( $salt, '', $decrypted_data );
			return maybe_unserialize( $data );
		}
		return false;
	}

	/**
	 * 連想配列をXMLに変換する
	 *
	 * @param array  $arr array
	 * @param object $xml SimpleXMLElement
	 */
	protected static function array_to_xml( $arr, &$xml ) {
		foreach ( $arr as $key => $value ) {
			if ( is_array( $value ) ) {
				if ( ! is_numeric( $key ) ) {
					$subnode = $xml->addChild( "$key" );
					self::array_to_xml( $value, $subnode );
				} else {
					self::array_to_xml( $value, $xml );
				}
			} else {
				$xml->addChild( "$key", "$value" );
			}
		}
	}

	/**
	 * ファイル配列の作成
	 */
	protected static function set_files() {
		if ( isset( $_FILES['cabinet_file'] ) ) {
			setlocale( LC_ALL, 'ja_JP.UTF-8' );
			static::$data['params'] = array(
				...static::$data['params'],
				...static::image_compressor( $_FILES['cabinet_file'] ),
			);
		}
	}

	/**
	 * 画像圧縮
	 *
	 * @param array $files files
	 */
	protected static function image_compressor( $files ) {
		// ファイルがなければ何もしない
		if ( empty( $files['tmp_name'] ) ) {
			return $files;
		}
		$name     = $files['name'];
		$type     = $files['type'];
		$tmp_name = $files['tmp_name'];

		// 一時ディレクトリ作成
		$tmp  = wp_tempnam( __CLASS__, get_theme_file_path() . '/' );
		unlink( $tmp );
		mkdir( $tmp );
		foreach ( $tmp_name as $k => $file ) {
			// 画像圧縮処理
			$quality = isset( $quality ) ? $quality : 50;
			move_uploaded_file( $file, "{$tmp}/{$name[$k]}" );
			$local_file = "{$tmp}/{$name[$k]}";
			exec( "mogrify -quality {$quality} {$local_file}" );
			// pathを修正
			$files['tmp_name'][ $k ] = $local_file;
		}
		return array(
			'files' => $files,
			'tmp_path' => $tmp,
		);
	}
}
