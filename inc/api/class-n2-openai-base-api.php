<?php
/**
 * OpenAI Base API
 *
 * @package neoneng
 */

/**
 * OpenAI Base API
 */
abstract class N2_OpenAI_Base_API {

	/**
	 * 設定（基本的に拡張で上書きする）
	 *
	 * @var class
	 */
	protected static $settings = array(
		'sheetId'   => '1FrFJ7zog1WUCsiREFOQ2pGAdhDYveDgBmGdaITrWeCo',
		'range'     => 'OpenAI_API',
		'endpoint'  => 'https://api.openai.com/v1',
		'transient' => array(
			'key'  => 'openai_api_key',
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
		add_action( 'wp_ajax_' . mb_strtolower( get_class( $this ) ), array( $this, 'ajax' ) );
		// 初期化しとかないと継承先クラスのメソッドが使えない
		$this->set_header();
	}

	/**
	 * APIキーをスプシから取得してセット
	 */
	private static function set_api_key() {
		// キャッシュ確認
		$key = static::get_decrypted_data_from_site_transient( static::$settings['transient']['key'] );
		if ( ! $key ) {
			global $n2_sync;
			$key = $n2_sync->get_spreadsheet_data( static::$settings['sheetId'], static::$settings['range'] );
			if ( ! empty( $key ) ) {
				$save = static::set_encrypted_data_to_site_transient( static::$settings['transient']['key'], $key );
			}
			// 取得や保存できなければ空を返す
			if ( ! $save ) {
				return array();
			}
		}
		if ( ! is_array( $key ) ) {
			return array();
		}
		return array(
			'Authorization' => 'Bearer ' . reset( $key )['api_key'],
		);
	}

	/**
	 * ヘッダー配列の作成
	 */
	protected static function set_header() {
		static::$data['header'] = array_merge(
			array( 'Content-Type' => 'application/json' ),
			static::set_api_key(),
		);
	}

	/**
	 * 各パラメータ配列の作成
	 */
	private static function set_params() {
		static::$data['params'] = $_GET;
	}

	/**
	 * テンプレート取得する関数
	 */
	protected static function get_template() {
		global $n2;
		return $n2->openai_template;
	}

	/**
	 * APIを実行するサムシング
	 */
	private static function call() {
		$method      = static::$data['params']['call'] ?? '';
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
	 * 出力用
	 */
	private static function export() {
		switch ( static::$data['params']['mode'] ) {
			case 'debug': // デバッグモード
				header( 'Content-Type: application/json; charset=utf-8' );
				print_r( static::$data['params'] );
				print_r( static::get_template() );
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
	 * 秘密鍵を暗号化してsite_transientに保存する関数
	 *
	 * @param  string $key transient key
	 * @param  string $val transient value
	 * @param  array  $opt option
	 *
	 * @return bool 成功した場合はtrue、失敗した場合はfalse
	 */
	private static function set_encrypted_data_to_site_transient( $key, $val, $opt = array() ) {
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
		return set_site_transient( $key, $data_to_save, $opt['expiration'] );
	}

	/**
	 * site_transientから暗号化された値を取得し、復号化する関数
	 *
	 * @param string $key  transient key
	 * @param string $salt salt
	 *
	 * @return string|bool 復号化した秘密鍵。失敗した場合はfalse
	 */
	private static function get_decrypted_data_from_site_transient( $key, $salt = null ) {
		$salt ??= SECURE_AUTH_SALT;
		// transientから暗号化されたデータを取得
		$encrypted_data = get_site_transient( $key );
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
	 * 認証情報のキャッシュをリセット
	 */
	public static function reset_openai_transient() {
		delete_site_transient( static::$settings['transient']['key'] );
		return array(
			'reset' => (bool) static::set_api_key(),
		);
	}
}
