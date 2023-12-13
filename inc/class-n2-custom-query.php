<?php
/**
 * class-n2-custom-query.php
 * WP_Queryのカスタム
 *
 * @package neoneng
 */

if ( class_exists( 'N2_Custom_Query' ) ) {
	new N2_Custom_Query();
	return;
}

/**
 * 管理画面の投稿一覧
 */
class N2_Custom_Query {
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
		add_filter( 'posts_join', array( $this, 'posts_join' ), 10, 2 );
		add_filter( 'posts_search', array( $this, 'posts_search' ), 10, 2 );
	}

	/**
	 * JOIN句のカスタム
	 * Filters the JOIN clause of the query.
	 *
	 * @param string   $join  The JOIN clause of the query.
	 * @param WP_Query $query The WP_Query instance (passed by reference).
	 */
	public function posts_join( $join, $query ) {
		global $wpdb;
		if ( $query->is_search() ) {
			// json化したpost_contentのダブルコーテーション外したテーブルを結合（検索に利用するため）
			$join .= "INNER JOIN ( SELECT ID, REPLACE( post_content, '\"', '' ) as post_content FROM {$wpdb->posts} ) as {$wpdb->posts}_json ON ( {$wpdb->posts}.ID = {$wpdb->posts}_json.ID )";
		}
		return $join;
	}
	/**
	 * searchのカスタム
	 * Filters the search SQL that is used in the WHERE clause of WP_Query.
	 *
	 * @param string   $search Search SQL for WHERE clause.
	 * @param WP_Query $query  The current WP_Query object.
	 */
	public function posts_search( $search, $query ) {
		global $wpdb;
		if ( $query->is_search() ) {
			// json化したpost_contentのダブルコーテーション外したテーブルを検索
			$search = str_replace( "{$wpdb->posts}.post_content", "{$wpdb->posts}_json.post_content", $search );
			// OR検索
			if ( preg_match( '/ OR /', $query->query['s'] ) ) {
				$search = str_replace( ')) AND ((', ')) OR ((', $search );
			}
		}
		return $search;
	}
}
