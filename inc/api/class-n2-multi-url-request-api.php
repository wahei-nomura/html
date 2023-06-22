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
	}

	/**
	 * URLを並列化でリクエストするサムシングAPI
	 *
	 * @param array|string $args パラメータ
	 * @return array|void
	 */
	public static function requests( $args ) {
		$urls = $args ?? $_GET['urls'];
		$args = $args ? wp_parse_args( $args ) : $_GET;
		if ( wp_verify_nonce( $_POST['n2nonce'] ?? '', 'n2nonce' ) ) {
			$urls = wp_parse_args( $urls, $_POST['urls'] );
		}
		$action   = $args['action'] ?? false;
		$requests = array();

		foreach ( $urls as $url ) {
			$requests[] = array(
				'url'     => $url,
				'headers' => array(
					'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:75.0) Gecko/20100101 Firefox/75.0',
				),
			);
		}
		$result = Requests::request_multiple( $requests );

		if ( $action ) {
			header( 'Content-Type: application/json; charset=utf-8' );
			echo wp_json_encode( $result );
			exit;
		}
		return $result;
	}
}
