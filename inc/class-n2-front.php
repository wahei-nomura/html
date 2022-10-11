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
					OR {$wpdb->posts}.post_status = 'future'
					OR {$wpdb->posts}.post_status = 'draft'
					OR {$wpdb->posts}.post_status = 'pending'
					OR {$wpdb->posts}.post_status = 'private'
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
		if ( empty( $_GET['portal_rakuten'] ) && '' !== $_GET['portal_rakuten'] ) { // 楽天除外
			$portal_rakuten = $_GET['portal_rakuten'];
			$where         .= 'AND (';
			$where         .= "
			{$wpdb->postmeta}.meta_key = '出品禁止ポータル'
			AND {$wpdb->postmeta}.meta_value NOT LIKE '%%%s%%'
			";
			array_push( $args, '楽天' );
			$where .= ')';
		}

		if ( empty( $_GET['portal_choice'] ) && '' !== $_GET['portal_choice'] ) { // チョイス除外
			$portal_choice = $_GET['portal_choice'];
			$where        .= 'AND (';
			$where        .= "
			{$wpdb->postmeta}.meta_key = '出品禁止ポータル'
			AND {$wpdb->postmeta}.meta_value NOT LIKE '%%%s%%'
			";
			array_push( $args, 'チョイス' );
			$where .= ')';
		}

		if ( empty( $_GET['portal_furunavi'] ) && '' !== $_GET['portal_furunavi'] ) { // チョイス除外
			$portal_furunavi = $_GET['portal_furunavi'];
			$where          .= 'AND (';
			$where          .= "
			{$wpdb->postmeta}.meta_key = '出品禁止ポータル'
			AND {$wpdb->postmeta}.meta_value NOT LIKE '%%%s%%'
			";
			array_push( $args, 'ふるなび' );
			$where .= ')';
		}
		// ここまで出品禁止ポータル ------------------------------------
		// 価格絞り込み ---------------------------------
		if ( empty( $_GET['min-price'] ) && '' !== $_GET['min-price'] ) { // 楽天除外
			$min_price = $_GET['min-price'];
			var_dump($min_price);
			$where    .= 'AND (';
			$where    .= "
			{$wpdb->postmeta}.meta_key = '寄附金額'
			AND {$wpdb->postmeta}.meta_value >= '%s'
			";
			array_push( $args, $min_price );
			$where .= ')';
		}


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

}
