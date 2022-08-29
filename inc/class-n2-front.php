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
		$this->cls  = get_class( $this );
		add_action( 'posts_request', array( $this, 'front_request' ) );
	}


	/**
	 * 一定条件下でSQLを全書き換え
	 *
	 * @param string $query sql
	 * @return string $query sql
	 */
	public function front_request( $query ) {
		if(!is_front_page()){
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

		// 事業者絞り込み ----------------------------------------
		if ( ! empty( $_GET['事業者'] ) && '' !== $_GET['事業者'] ) {
			$where .= "AND {$wpdb->posts}.post_author = '%s'";
			array_push( $args, filter_input( INPUT_GET, '事業者', FILTER_VALIDATE_INT ) );
		}

		// 返礼品コード絞り込み------------------------------------
		if ( ! empty( $_GET['返礼品コード'] ) && '' !== $_GET['返礼品コード'] ) {
			$code_arr = $_GET['返礼品コード'];
			$where   .= 'AND (';
			foreach ( $code_arr as $key => $code ) {
				if ( 0 !== $key ) {
					$where .= ' OR '; // 複数返礼品コードをOR検索(前後の空白必須)
				}
				$where .= "{$wpdb->posts}.ID = '%s'";
				array_push( $args, $code );
			}
			$where .= ')';
		}

		// ステータス絞り込み ------------------------------------
		if ( ! empty( $_GET['ステータス'] ) && '' !== $_GET['ステータス'] ) {
			$where .= "AND {$wpdb->posts}.post_status = '%s'";
			array_push( $args, filter_input( INPUT_GET, 'ステータス' ) );
		}

		// 定期便絞り込み ---------------------------------------
		if ( ! empty( $_GET['定期便'] ) && '' !== $_GET['定期便'] ) {
			$where .= "
					AND {$wpdb->postmeta}.meta_key = '定期便'
					AND {$wpdb->postmeta}.meta_value = '%s'
				";
			array_push( $args, filter_input( INPUT_GET, '定期便', FILTER_VALIDATE_INT ) );
		}

		// WHER句末尾連結
		$where .= '))';
		// var_dump($where);
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
