<?php
/**
 * N2全自治体データAPI
 * get_postsの引数で取得可能
 *
 * @package neoneng
 */

if ( class_exists( 'N2_Blogs_API' ) ) {
	new N2_Blogs_API();
	return;
}

/**
 * 寄附金額の計算のためのAPI
 */
class N2_Blogs_API {

	/**
	 * データ
	 *
	 * @var array
	 */
	public static $data = array(
		'params' => array(), // $_GET $_POST パラメータ
		'blogs'  => array(), // N2データ
	);

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		add_action( 'wp_ajax_n2_blogs_api', array( $this, 'api' ) );
		add_action( 'wp_ajax_nopriv_n2_blogs_api', array( $this, 'api' ) );
	}

	/**
	 * API
	 */
	public function api() {
		self::$data['blogs'] = self::get_blogs();
		// モード判定
		$mode = self::$data['params']['mode'];
		$mode = method_exists( 'N2_Blogs_API', $mode ) ? $mode : 'json';
		header( 'Content-Type: application/json; charset=utf-8' );
		$this->{$mode}();
		exit;
	}

	/**
	 * パラメータのセット
	 *
	 * @param array $params パラメータ
	 */
	public static function set_params( $params ) {
		global $n2;
		$params = $params ?: $_GET;
		// $_POSTを$paramsで上書き
		if ( wp_verify_nonce( $_POST['n2nonce'] ?? '', 'n2nonce' ) ) {
			$params = wp_parse_args( $params, $_POST );
		}
		// デフォルト値
		$defaults = array(
			'mode' => 'json',
		);
		// デフォルト値を$paramsで上書き
		self::$data['params'] = wp_parse_args( $params, $defaults );
	}

	/**
	 * データ取得
	 *
	 * @param array $params パラメータ
	 */
	public static function get_blogs( $params = array() ) {
		$params = wp_parse_args( $params );
		self::set_params( $params );
		// 自治体コードURLサイトに絞る
		$sites = array_filter(
			get_sites(),
			fn( $v ) => preg_match( '/f[0-9]{6}-[a-z].*?/', $v->path )
		);
		$blogs = array();
		// 自治体名・自治体コード・出品ポータル・LH
		foreach ( $sites as $site ) {
			switch_to_blog( $site->blog_id );
			global $n2;
			$blogs[ $site->blog_id ] = array(
				'name'       => $n2->town,
				'url'        => str_replace( 'wp-admin/admin-ajax.php', '', $n2->ajaxurl ),
				'ajaxurl'    => $n2->ajaxurl,
				'code'       => trim( $site->path, '/' ),
				'n2settings' => $n2->settings['N2'],
			);
			restore_current_blog();
		}
		/**
		 * [hook] n2_blogs_api_get_blogs
		 */
		return apply_filters( 'n2_blogs_api_get_blogs', $blogs );
	}

	/**
	 * json
	 */
	public function json() {
		echo wp_json_encode( self::$data, JSON_UNESCAPED_UNICODE );
	}

	/**
	 * デバッグ用
	 */
	public function debug() {
		print_r( self::$data );
	}

}
