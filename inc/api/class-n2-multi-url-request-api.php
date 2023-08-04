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
	 * データ
	 *
	 * @var array
	 */
	protected static $data = array(
		'params'   => array(),
		'headers'   => array(),
		'response' => array(),
		'error'    => array(),
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
	 * リクエスト毎へ共通ヘッダーを付与
	 *
	 * @param array|void $arg_header header
	 */
	private static function set_headers( $arg_header ) {
		if ( empty( self::$data['headers'] ) ) {
			$headers = array(
				'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:75.0) Gecko/20100101 Firefox/75.0',
				...$arg_header,
			);
			/**
			 * [hook] n2_multi_url_request_api_set_header
			 */
			static::$data['headers'] = apply_filters( mb_strtolower( get_called_class() ) . '_set_headers', $headers );

			foreach( static::$data['params']['requests'] ?? array() as $index => $request ) {
				static::$data['params']['requests'][ $index ]['headers'] = array( ...static::$data['headers'], ...$request['headers'] ?? array() );
			}
		}
	}

	/**
	 * 各パラメータ配列の作成
	 * $args > $_GET > $_POST > $default
	 * 
	 * @param array|void $args args
	 */
	private static function set_params( $args ) {
		$params = $args;
		// headerは除外
		unset( $params['headers'] );
		// $_GETを引数で上書き
		$params = wp_parse_args( $params, $_GET );
		// $_POSTを$paramsで上書き
		if ( wp_verify_nonce( $_POST['n2nonce'] ?? '', 'n2nonce' ) ) {
			$params = wp_parse_args( $params, $_POST );
		}
		$default = array(
			'mode'    => 'func',
			'action'  => false,
			'call' => 'request_multiple',
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
	private static function call() {
		static::check_fatal_error(
			in_array(
				static::$data['params']['call'],
				static::$white_list,
				true,
			),
			'未定義です',
		);
		return static::{ static::$data['params']['call'] }();
	}

	/**
	 * 出力用
	 */
	private static function export() {
		switch ( static::$data['params']['mode'] ) {
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
	 *
	 * @param array|void $args args
	 * @return array|void
	 */
	public static function ajax( $args ) {

		static::set_params( $args );
		static::set_headers( $args['headers'] ?? array() );

		static::$data['response'] = static::call();

		// 出力時はここで終了
		static::export();

		// エクスポートしない場合
		return static::$data['response'];
	}

	/**
	 * URLを並列化でリクエストするAPI
	 *
	 * @return array|void
	 */
	public static function request_multiple() {
		static::check_fatal_error( static::$data['params']['requests'] ?? array(), 'リクエストが未設定です' );
		return Requests::request_multiple( static::$data['params']['requests'], array( 'timeout' => 60 ) );
	}

	/**
	 * 画像が存在するかチェックするAPI
	 *
	 * @return array
	 */
	public static function verify_images() {
		$response = self::request_multiple();
		$result   = array();

		foreach ( $response as $res ) {
			$result[ $res->url ] = 200 === $res->status_code;
		}
		return $result;
	}
}
