<?php
/**
 * N2返礼品データAPI
 * get_postsの引数で取得可能
 *
 * @package neoneng
 */

if ( class_exists( 'N2_Items_API' ) ) {
	new N2_Items_API();
	return;
}

/**
 * 寄附金額の計算のためのAPI
 */
class N2_Items_API {

	/**
	 * データ
	 *
	 * @var array
	 */
	public static $data = array(
		'params' => array(), // $_GET $_POST パラメータ
		'items'  => array(), // N2データ
	);

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		// post_contentに必要なデータを全部ぶっこむ
		add_action( 'wp_insert_post_data', array( $this, 'insert_post_data' ), 20, 4 );
		add_action( 'wp_ajax_n2_items_api', array( $this, 'api' ) );
		add_action( 'wp_ajax_nopriv_n2_items_api', array( $this, 'api' ) );
	}

	/**
	 * API
	 */
	public function api() {
		self::$data['items'] = self::get_items();
		// モード判定
		$mode = self::$data['params']['mode'];
		$mode = method_exists( 'N2_Items_API', $mode ) ? $mode : 'json';
		header( 'Content-Type: application/json; charset=utf-8' );
		$this->{$mode}();
		exit;
	}

	/**
	 * パラメータのセット
	 */
	public static function set_params() {
		global $n2;
		$params = $_GET;
		// $_POSTを$paramsで上書き
		if ( wp_verify_nonce( $_POST['n2nonce'] ?? '', 'n2nonce' ) ) {
			$params = wp_parse_args( $params, $_POST );
		}
		// デフォルト値
		$defaults = array(
			'post_status'    => 'any',
			'numberposts'    => -1,
			'mode'           => 'json',
			'n2_active_flag' => $n2->n2_active_flag,
		);
		// デフォルト値を$paramsで上書き
		self::$data['params'] = wp_parse_args( $params, $defaults );
	}

	/**
	 * N2データのみ取得
	 */
	public static function get_items() {
		self::set_params();
		$posts = get_posts( self::$data['params'] );
		// post_contentのみにする
		$posts = array_map(
			function( $v ) {
				$post_content = json_decode( $v->post_content, true );
				// idを混ぜ込む
				$post_content['id'] = $v->ID;
				return $post_content;
			},
			$posts
		);
		$posts = array_filter( $posts );
		/**
		 * [hook] n2_items_api_get_items
		 */
		return apply_filters( 'n2_items_api_set_items', $posts );
	}

	/**
	 * json
	 */
	private function json() {
		echo wp_json_encode( self::$data, JSON_UNESCAPED_UNICODE );
	}

	/**
	 * デバッグ用
	 */
	private function debug() {
		print_r( self::$data );
	}

	/**
	 * 全件post_contentをアップデート
	 */
	public static function update() {
		set_time_limit( 0 );
		foreach ( get_posts( self::$data['params'] ) as $post ) {
			wp_insert_post( $post );
		}
	}
	/**
	 * 投稿保存時にpost_contentにAPIデータを全部ぶち込む
	 *
	 * @param array $data                An array of slashed, sanitized, and processed post data.
	 * @param array $postarr             An array of sanitized (and slashed) but otherwise unmodified post data.
	 * @param array $unsanitized_postarr An array of slashed yet *unsanitized* and unprocessed post data as
	 *                                   originally passed to wp_insert_post().
	 * @param bool  $update              Whether this is an existing post being updated.
	 */
	public function insert_post_data( $data, $postarr, $unsanitized_postarr, $update ) {
		if ( 'post' !== $postarr['post_type'] || ( ! isset( $postarr['meta_input'] ) && ! $update ) ) {
			return $data;
		}
		$post_content = array();
		// タイトル追加
		$post_content['タイトル'] = $data['post_title'];
		// 特定のカスタムフィールド値のみ更新することがあるので既存の値とマージしないといけない
		$meta_input = array();
		foreach ( array_keys( (array) get_post_meta( $postarr['ID'] ) ) as $key ) {
			$meta_input[ $key ] = get_post_meta( $postarr['ID'], $key, true );
		}
		$meta_input = wp_parse_args( $postarr['meta_input'] ?? array(), $meta_input );

		// 事業者コード追加
		$post_content['事業者コード'] = get_user_meta( $data['post_author'], 'last_name', true );
		// 提供事業者名・ポータル表示名があれば取得
		$portal_site_display_name = $meta_input['提供事業者名'] ?? get_user_meta( $data['post_author'], 'portal_site_display_name', true );
		// 事業者名
		$post_content['事業者名'] = match ( $portal_site_display_name ) {
			'記載しない' => '',
			'' => get_user_meta( $data['post_author'], 'first_name', true ),
			default => $portal_site_display_name
		};
		// 投稿ステータス追加
		$post_content['ステータス'] = $data['post_status'];
		// n2fieldのカスタムフィールド全取得
		foreach ( $meta_input as $key => $meta ) {
			// 値が配列の場合、空は削除
			if ( is_array( $meta ) ) {
				$meta = array_filter( $meta, fn( $v ) => $v );
			}
			$post_content[ $key ] = $meta;
		}
		$data['post_content'] = addslashes( wp_json_encode( $post_content, JSON_UNESCAPED_UNICODE ) );
		return $data;
	}
}
