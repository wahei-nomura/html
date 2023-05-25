<?php
/**
 * class-n2-front.php
 *
 * @package neoneng
 */

if ( class_exists( 'N2_Front' ) ) {
	new N2_Front();
	return;
}

/**
 * Front
 */
class N2_Front {
	/**
	 * クラス名
	 *
	 * @var string
	 */
	private $cls;
	/**
	 * コンストラクタ
	 */
	public function __construct() {
		$this->cls = get_class( $this );
		add_action( "wp_ajax_nopriv_{$this->cls}_item_confirm", array( $this, 'update_item_confirm' ) );
		add_action( "wp_ajax_{$this->cls}_item_confirm", array( $this, 'update_item_confirm' ) );
		add_action( "wp_ajax_nopriv_{$this->cls}_search_code", array( $this, 'search_code' ) );
		add_action( "wp_ajax_{$this->cls}_search_code", array( $this, 'search_code' ) );
		add_action( 'pre_get_posts', array( $this, 'change_posts_per_page' ) );
		// add_filter( 'comments_open', array( $this, 'commets_open' ), 10, 2 ); // 2022-11-29 コメントアウト taiki
		// add_filter( 'comment_post_redirect', array( $this, 'comment_post_redirect' ) ); // 2022-11-29 コメントアウト taiki
	}

	/**
	 * update_item_confirm
	 * ajaxで事業者確認パラメーターを更新
	 */
	public function update_item_confirm() {
		global $n2;
		date_default_timezone_set( 'Asia/Tokyo' );
		$post_id       = filter_input( INPUT_POST, 'post_id', FILTER_SANITIZE_NUMBER_INT );
		$confirm_value = filter_input( INPUT_POST, 'confirm_value' );
		$is_ssoffice   = in_array( $_SERVER['REMOTE_ADDR'], $n2->ss_ip_address ) ? 'ssofice' : 'no-ssofice';
		update_post_meta( $post_id, '事業者確認', array( $confirm_value, date( 'Y-m-d G:i:s' ), $is_ssoffice ) );
	}

	/**
	 * change_posts_per_page
	 * ページネーションの件数設定
	 *
	 * @param string $query sql
	 */
	public function change_posts_per_page( $query ) {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}
		if ( $query->is_front_page() || $query->is_search() ) { // メインページおよび検索結果で適用
			$query->set( 'posts_per_page', '100' );
			return;
		}
	}

	// 2022-11-29 コメントアウト taiki
	/**
	 * 事業者確認のコメント機能open
	 *
	 * @param bool $open Whether look param is exists
	 * @return bool $open
	 */
	// public function commets_open( $open ) {
	// if ( ! empty( $_GET['look'] ) ) {
	// $open = true;
	// }
	// return $open;
	// }

	// 2022-11-29 コメントアウト taiki
	/**
	 * コメント送信時のリダイレクトURLにlookパラメータ付与
	 *
	 * @param string $location デフォルトURL
	 * @return string $location 変更後URL
	 */
	// public function comment_post_redirect( $location ) {
	// return preg_replace( '/\/#/', '&look=true#', $location );
	// }

	/**
	 * ajaxで事業者idを受け取って返礼品コード一覧を返す
	 */
	public function search_code() {
		$author_id = filter_input( INPUT_GET, 'author_id', FILTER_VALIDATE_INT );
		$ids       = get_posts(
			array(
				'fields'         => 'ids',
				'posts_per_page' => -1,
				'author'         => $author_id,
			)
		);
		$codes     = array();
		foreach ( $ids as $id ) {
			if ( '' !== get_post_meta( $id, '返礼品コード', true ) ) {
				$codes[ get_post_meta( $id, '返礼品コード', true ) ] = $id;
			}
		};

		ksort( $codes );

		echo wp_json_encode( $codes );

		exit;
	}
}
