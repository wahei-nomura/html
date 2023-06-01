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
		add_action( 'wp_after_insert_post', array( $this, 'after_insert_post' ), 10, 3 );
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
		$posts = array_map( fn( $v ) => json_decode( $v->post_content, true ), $posts );
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
	 * 投稿保存時にpost_contentにAPIデータを全部ぶち込む
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @param bool    $update  Whether this is an existing post being updated.
	 */
	public function after_insert_post( $post_id, $post, $update ) {
		if ( 'post' !== $post->post_type ) {
			return;
		}
		$items = array();
		// ID追加
		$items['id'] = $post_id;
		// タイトル追加
		$items['タイトル'] = $post->post_title;
		// 事業者コード追加
		$items['事業者コード'] = get_user_meta( $post->post_author, 'last_name', true );
		// 提供事業者名・ポータル表示名があれば取得
		$portal_site_display_name = get_post_meta( $post_id, '提供事業者名', true ) ?: get_user_meta( $post->post_author, 'portal_site_display_name', true );
		// 事業者名
		$items['事業者名'] = match ( $portal_site_display_name ) {
			'記載しない' => '',
			'' => get_user_meta( $post->post_author, 'first_name', true ),
			default => $portal_site_display_name
		};
		// 投稿ステータス追加
		$items['ステータス'] = get_post_status( $post_id );
		// n2fieldのカスタムフィールド全取得
		foreach ( array_keys( get_post_meta( $post_id ) ) as $key ) {
			$meta = get_post_meta( $post_id, $key, true );
			// 値が配列の場合、空は削除
			if ( is_array( $meta ) ) {
				$meta = array_filter( $meta, fn( $v ) => $v );
			}
			$items[ $key ] = $meta;
		}
		$post->post_content = addslashes( wp_json_encode( $items, JSON_UNESCAPED_UNICODE ) );
		wp_insert_post( $post, false, false );
	}
}
