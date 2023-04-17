<?php
/**
 * N2返礼品出力API
 *
 * @package neoneng
 */

if ( class_exists( 'N2_Output_Gift_API' ) ) {
	new N2_Output_Gift_API();
	return;
}

/**
 * API出力に関する処理をまとめたクラス
 */
class N2_Output_Gift_API {

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		add_action( 'wp_ajax_n2_output_gift_api', array( $this, 'get_by_priv_user' ) );
		add_action( 'wp_ajax_nopriv_n2_output_gift_api', array( $this, 'get_by_nopriv_user' ) );
	}

	/**
	 * プライベートユーザー用API
	 */
	public function get_by_priv_user() {
		global $wpdb;

		// URLの末尾からskuを取得、サニタイズ
		$sku = isset( $_GET['sku'] ) ? sanitize_text_field( $_GET['sku'] ) : '';

		// skuが空の場合、処理を終了する
		if ( '' === $sku ) :
			exit;
		endif;

		// 自治体コードを取得
		global $n2;
		$site_id = $n2->site_id;

		// テーブルから情報を取得するSQLクエリを準備
		$query = <<<SELECT_SQL
		SELECT
			posts.post_title as title,
			postmeta.meta_key,
			postmeta.meta_value
		FROM
			wp_{$site_id}_posts as posts
		INNER JOIN
			wp_{$site_id}_postmeta as postmeta
		ON
			posts.id = postmeta.post_id
		WHERE
			posts.id in (
				SELECT
					post_id
				FROM
					wp_{$site_id}_postmeta
				WHERE
					meta_value = %s
			);
		SELECT_SQL;

		// サニタイズした返礼品コードをクエリの「%s」の部分に入れてクエリを完成させる
		$query = $wpdb->prepare( $query, $sku );

		// SQLクエリを実行し、結果を連想配列で取得
		$results = $wpdb->get_results( $query, ARRAY_A );

		// 空の配列を作って取得したデータを整える
		$gift = array();
		foreach ( $results as $result ) {
			$title      = $result['title'];
			$meta_key   = $result['meta_key'];
			$meta_value = $result['meta_value'];

			if ( ! isset( $gift[ $title ] ) ) {
				$gift[ $title ] = array( 'title' => $title );
			}
			$gift[ $title ][ $meta_key ] = $meta_value;
		}

		// 最終的な出力
		$output = array_values( $gift );

		// 結果をJSON形式に変換して出力
		header( 'Content-Type: application/json' );
		echo wp_json_encode( $output );
		exit;
	}
	/**
	 * 非プライベートユーザー用API
	 */
	public function get_by_nopriv_user() {
		global $wpdb;

		// URLの末尾からskuを取得、サニタイズ
		$sku = isset( $_GET['sku'] ) ? sanitize_text_field( $_GET['sku'] ) : '';

		// skuが空の場合、処理を終了する
		if ( '' === $sku ) :
			exit;
		endif;

		// 自治体コードを取得
		global $n2;
		$site_id = $n2->site_id;

		// テーブルから情報を取得するSQLクエリを準備
		$query = <<<SELECT_SQL
		SELECT
			posts.post_title as title,
			postmeta.meta_key,
			postmeta.meta_value
		FROM
			wp_{$site_id}_posts as posts
		INNER JOIN
			wp_{$site_id}_postmeta as postmeta
		ON
			posts.id = postmeta.post_id
		WHERE
			postmeta.meta_key in (
				'寄附金額',
				'返礼品コード',
				'消費期限',
				'賞味期限',
				'説明文',
				'電子レンジ対応',
				'オーブン対応',
				'食洗機対応',
				'内容量・規格等',
				'発送方法',
				'定期便',
				'包装対応',
				'のし対応',
				'配送期間'
				)
		AND
			posts.id in (
				SELECT
					post_id
				FROM
					wp_{$site_id}_postmeta
				WHERE
					meta_value = %s
			);
		SELECT_SQL;

		// サニタイズした返礼品コードをクエリの「%s」の部分に入れてクエリを完成させる
		$query = $wpdb->prepare( $query, $sku );

		// SQLクエリを実行し、結果を連想配列で取得
		$results = $wpdb->get_results( $query, ARRAY_A );

		// 空の配列を作って取得したデータを整える
		$gift = array();
		foreach ( $results as $result ) {
			$title      = $result['title'];
			$meta_key   = $result['meta_key'];
			$meta_value = $result['meta_value'];

			if ( ! isset( $gift[ $title ] ) ) {
				$gift[ $title ] = array( 'title' => $title );
			}
			$gift[ $title ][ $meta_key ] = $meta_value;
		}

		// 最終的な出力
		$output = array_values( $gift );

		// 結果をJSON形式に変換して出力
		header( 'Content-Type: application/json' );
		echo wp_json_encode( $output );
		exit;
	}
}
