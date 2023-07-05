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
		add_action( 'wp_ajax_n2_multi_url_request_api', array( $this, 'requests' ) );
		add_action( 'wp_ajax_nopriv_n2_multi_url_request_api', array( $this, 'requests' ) );
		add_action( 'wp_ajax_n2_multi_veryfy_images_api', array( $this, 'veryfy_images' ) );
		add_action( 'wp_ajax_nopriv_n2_multi_veryfy_images_api', array( $this, 'veryfy_images' ) );
	}

	/**
	 * データ
	 *
	 * @var array
	 */
	protected static $data = array(
		'params'   => array(),
		'header'   => array(),
		'response' => array(),
		'error'    => array(),
	);
	/**
	 * ヘッダー配列の作成
	 */
	private static function set_header( $args = array() ) {
		if ( empty( self::$data['header'] ) ) {

			$header = array(
				'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:75.0) Gecko/20100101 Firefox/75.0',
				...$args,
			);

			/**
			 * [hook] n2_multi_url_request_api_set_header
			 */
			static::$data['header'] = apply_filters( mb_strtolower( get_called_class() ) . '_set_header', $header );
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
			'action'  => false,
			'request' => 'multiple_request',
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

		static::set_header( $args['header'] ?? array() );
		static::set_params( $args );

		static::$data['response'] = static::request();

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
		$requests = array();

		$urls = static::$data['params']['urls'] ?? array();
		static::check_fatal_error( $urls, 'urlsが設定されていません' );

		foreach ( $urls as $url ) {
			$requests[] = array(
				'url'     => $url,
				'headers' => static::$data['header'],
			);
		}
		return Requests::request_multiple( $requests, array( 'timeout' => 60 ) );
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
