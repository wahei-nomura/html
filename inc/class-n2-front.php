<?php
/**
 * class-n2-front.php
 *
 * @package neoneng
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
		add_action( 'posts_request', array( $this, 'front_request' ) );
		add_action( "wp_ajax_nopriv_{$this->cls}_item_confirm", array( $this, 'update_item_confirm' ) );
		add_action( 'pre_get_posts', array( $this, 'change_posts_per_page' ) );
	}


	/**
	 * 一定条件下でSQLを全書き換え
	 *
	 * @param string $query sql
	 * @return string $query sql
	 */
	public function front_request( $query ) {
		if ( ! is_search() ) {
			return $query;
		}
		global $wpdb;
		// 最終的に$query内に代入するWHERE句
		$where = "
		AND (
			(
				{$wpdb->posts}.post_type = 'post'
				AND (
					{$wpdb->posts}.post_status = 'publish'
					)
		";

		// $wpdbのprepareでプレイスフォルダーに代入するための配列
		$args = array();
		// キーワード検索 ----------------------------------------
		if ( ! empty( $_GET['s'] ) && '' !== $_GET['s'] ) {
			// 全角空白は半角空白へ変換し、複数キーワードを配列に
			$s_arr = explode( ' ', mb_convert_kana( $_GET['s'], 's' ) );
			// キーワード前後の空白
			$s_arr = array_filter( $s_arr );
			// OR検索対応
			$sql_pattern = ! empty( $_GET['or'] ) && '1' === $_GET['or'] ? 'OR' : 'AND';

			// WHERE句連結
			$where .= 'AND(';
			foreach ( $s_arr as $key => $s ) {
				if ( 0 !== $key ) {
					$where .= $sql_pattern;
				}

				$where .= "
						(
							{$wpdb->postmeta}.meta_value LIKE '%%%s%%'
							OR {$wpdb->posts}.post_title LIKE '%%%s%%'
						)
					";
				array_push( $args, $s ); // カスタムフィールド
				array_push( $args, $s ); // タイトル
			}
			$where .= ')';
		}
		// ここまでキーワード ------------------------------------
		// 出品禁止ポータル絞り込み ---------------------------------
		// if ( empty( $_GET['portal_rakuten'] ) ) { // 楽天除外
		// $where .= 'AND (';
		// $where .= "
		// {$wpdb->postmeta}.meta_key = '出品禁止ポータル'
		// AND {$wpdb->postmeta}.meta_value NOT LIKE '%%%s%%'
		// ";
		// array_push( $args, '楽天' );
		// $where .= ')';
		// }

		// if ( empty( $_GET['portal_choice'] ) ) { // チョイス除外
		// $where .= 'AND (';
		// $where .= "
		// {$wpdb->postmeta}.meta_key = '出品禁止ポータル'
		// AND {$wpdb->postmeta}.meta_value NOT LIKE '%%%s%%'
		// ";
		// array_push( $args, 'チョイス' );
		// $where .= ')';
		// }

		// if ( empty( $_GET['portal_furunavi'] ) ) { // チョイス除外
		// $where .= 'AND (';
		// $where .= "
		// {$wpdb->postmeta}.meta_key = '出品禁止ポータル'
		// AND {$wpdb->postmeta}.meta_value NOT LIKE '%%%s%%'
		// ";
		// array_push( $args, 'ふるなび' );
		// $where .= ')';
		// }
		// ここまで出品禁止ポータル ------------------------------------
		// 価格絞り込み ---------------------------------
		if ( ! empty( $_GET['min-price'] ) && '' !== $_GET['min-price'] ) { // 最低額
			$min_price = $_GET['min-price'];
			$where    .= 'AND (';
			$where    .= "
			{$wpdb->postmeta}.meta_key = '寄附金額'
			AND {$wpdb->postmeta}.meta_value >= '%s'
			";
			array_push( $args, $min_price );
			$where .= ')';
		}
		if ( ! empty( $_GET['max-price'] ) && '' !== $_GET['max-price'] ) { // 最高額
			$max_price = $_GET['max-price'];
			$where    .= 'AND (';
			$where    .= "
			{$wpdb->postmeta}.meta_key = '寄附金額'
			AND {$wpdb->postmeta}.meta_value <= '%s'
			";
			array_push( $args, $max_price );
			$where .= ')';
		}

		// 事業者絞り込み ----------------------------------------
		if ( ! empty( $_GET['author'] ) ) {
			$where .= "AND {$wpdb->posts}.post_author = '%s'";
			array_push( $args, filter_input( INPUT_GET, 'author', FILTER_VALIDATE_INT ) );
		}
		// ここまで事業者 ----------------------------------------

		// 返礼品コード絞り込み ----------------------------------------
		if ( ! empty( $_GET['code'] ) && '' !== $_GET['code'] ) {
			$code   = $_GET['code'];
			$where .= 'AND (';
			$where .= "
			{$wpdb->postmeta}.meta_key = '返礼品コード'
			AND {$wpdb->postmeta}.meta_value LIKE '%%%s%%'
			";
			array_push( $args, $code );
			$where .= ')';
		}
		// ここまで返礼品コード ----------------------------------------

		// 事業者確認未 -------------------------------------------
		if ( ! empty( $_GET['look'] ) && 'true' === $_GET['look'] ) {
			$where .= "AND(
				(
				{$wpdb->postmeta}.meta_key = '%s'
				AND {$wpdb->postmeta}.meta_value = ''
				) 
				OR {$wpdb->postmeta}.post_id NOT IN (SELECT {$wpdb->postmeta}.post_id FROM {$wpdb->postmeta} WHERE {$wpdb->postmeta}.meta_key = '事業者確認') 
			)";
			array_push( $args, '事業者確認' );
		}
		// ここまで事業者確認 ------------------------------------

		// ここまで価格 ------------------------------------
		// WHER句末尾連結
		$where .= '))';

		// SQL（postsとpostmetaテーブルを結合）
		$sql = "
		SELECT SQL_CALC_FOUND_ROWS *
		FROM {$wpdb->posts}
		INNER JOIN {$wpdb->postmeta} ON {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id
		WHERE 1 = 1 {$where}
		GROUP BY {$wpdb->posts}.ID
		ORDER BY {$wpdb->posts}.post_date DESC
		";

		// 検索用GETパラメータがある場合のみ$queryを上書き
		$query = count( $args ) > 0 ? $wpdb->prepare( $sql, ...$args ) : $query;
		return $query;
	}

	/**
	 * update_item_confirm
	 * ajaxで事業者確認パラメーターを更新
	 */
	public function update_item_confirm() {
		$post_id = filter_input( INPUT_POST, 'post_id', FILTER_SANITIZE_NUMBER_INT );
		update_post_meta( $post_id, '事業者確認', array( '確認済み' ) );
	}

	/**
	 * change_posts_per_page
	 * ページネーションの件数設定
	 */
	function change_posts_per_page( $query ) {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}
		if ( $query->is_main_query() || $query->is_search() ) { // メインページおよび検索結果で適用
			$query->set( 'posts_per_page', 20 );
		}
	}

}
