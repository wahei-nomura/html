<?php
/**
 * class-n2-multi-url-request-api.php
 * URLを並列化でリクエストするサムシングAPI（jsでもPHPでも）
 *
 * @package neoneng
 */

if ( class_exists( 'N2_Multi_URL_Request_API' ) ) {
	new N2_Multi_URL_Request_API();
	return;
}

/**
 * URLを並列化でリクエストするサムシングAPI
 */
class N2_Multi_URL_Request_API {

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		add_action( 'wp_ajax_' . mb_strtolower( get_class( $this ) ) . '_ajax', array( $this, 'ajax' ) );
	}

	/**
	 * params
	 *
	 * @var array
	 */
	protected static $params = array(
		'requests'   => array(),
		'options'  => array(),
	);
	/**
	 * データ
	 *
	 * @var array
	 */
	protected static $data = array(
		'response' => array(),
	);

	/**
	 * APIで実行可能な関数リスト
	 *
	 * @var array
	 */
	protected static $white_list = array(
		'request_multiple',
		'verify_images',
	);

	/**
	 * options配列の作成
	 *
	 * @param array|void $arg_options options
	 */
	private static function set_options() {
		$default = array(
			'timeout' => 60,
		);
		// defaultをstatic::$params['options']で上書き
		$options = wp_parse_args( static::$params['options'], $default );
		/**
		 * [hook] n2_multi_url_request_api_set_options
		 */
		static::$params['options'] = apply_filters( mb_strtolower( get_called_class() ) . '_set_options', $options );
	}

	/**
	 * 各パラメータ配列の作成
	 * $args > $_GET > $_POST > $default
	 *
	 * @param array|void $args args
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
		static::check_fatal_error(
			in_array(
				static::$data['params']['call'],
				static::$white_list,
				true,
			),
			'未定義です',
		);
		$arguments = static::$params;
		// 不要な項目は削除
		unshift(
			$arguments['call'],
			$arguments['n2nonce'],
			$arguments['action'],
			$arguments['mode'],
		);
		return call_user_func_array( array( 'static', 'call' ), $arguments );
	}

	/**
	 * 出力用
	 */
	private static function export() {
		switch ( static::$params['mode'] ) {
			case 'debug': // デバッグモード
				header( 'Content-Type: application/json; charset=utf-8' );
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
	 * 実行
	 */
	public static function ajax() {
		static::set_params();
		static::set_options();
		static::$data['response'] = static::call();
		// 出力時はここで終了
		static::export();
	}

	/**
	 * URLを並列化でリクエストするAPI
	 *
	 * @var    array $requests requests
	 * @var    array $options  options 
	 * @return array|void
	 */
	public static function request_multiple( $requests, $options = array() ) {
		return Requests::request_multiple( $requests, $options );
	}

	/**
	 * 画像が存在するかチェックするAPI
	 *
	 * @var    array $requests requests
	 * @var    array $options  options 
	 * @return array
	 */
	public static function verify_images( $requests, $options = array() ) {
		$response = self::request_multiple( $requests, $options );
		$result   = array();
		foreach ( $response as $res ) {
			$result[ $res->url ] = 200 === $res->status_code;
		}
		return $result;
	}
}
