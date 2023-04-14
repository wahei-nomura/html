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
		add_action( 'wp_ajax_n2_priv_output_gift_api', array( $this, 'get_by_priv_user' ) );
		add_action( 'wp_ajax_n2_nopriv_output_gift_api', array( $this, 'get_by_nopriv_user' ) );
	}

	/**
	 * 外部用API
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
			postmeta.寄附金額,
			postmeta.返礼品コード,
			postmeta.消費期限,
			postmeta.賞味期限,
			postmeta.説明文,
			postmeta.電子レンジ対応,
			postmeta.オーブン対応,
			postmeta.食洗機対応,
			postmeta.内容量・規格等,
			postmeta.発送方法,
			postmeta.定期便,
			postmeta.包装対応,
			postmeta.のし対応,
			postmeta.配送期間
		FROM
			wp_{$site_id}_posts as posts
		INNER JOIN (
			SELECT 
				post_id,
				max(CASE WHEN meta_key = '寄附金額' THEN meta_value END) AS 寄附金額,
				max(CASE WHEN meta_key = '返礼品コード' THEN meta_value END) AS 返礼品コード,
				max(CASE WHEN meta_key = '消費期限' THEN meta_value END) AS 消費期限,
				max(CASE WHEN meta_key = '賞味期限' THEN meta_value END) AS 賞味期限,
				max(CASE WHEN meta_key = '説明文' THEN meta_value END) AS 説明文,
				max(CASE WHEN meta_key = '電子レンジ対応' THEN meta_value END) AS 電子レンジ対応,
				max(CASE WHEN meta_key = 'オーブン対応' THEN meta_value END) AS オーブン対応,
				max(CASE WHEN meta_key = '食洗機対応' THEN meta_value END) AS 食洗機対応,
				max(CASE WHEN meta_key = '内容量・規格等' THEN meta_value END) AS 内容量・規格等,
				max(CASE WHEN meta_key = '発送方法' THEN meta_value END) AS 発送方法,
				max(CASE WHEN meta_key = '定期便' THEN meta_value END) AS 定期便,
				max(CASE WHEN meta_key = '包装対応' THEN meta_value END) AS 包装対応,
				max(CASE WHEN meta_key = 'のし対応' THEN meta_value END) AS のし対応,
				max(CASE WHEN meta_key = '配送期間' THEN meta_value END) AS 配送期間
			FROM
				wp_{$site_id}_postmeta
			GROUP BY
				post_id ) as postmeta
		ON
			postmeta.post_id = posts.id
		WHERE
		    postmeta.返礼品コード = %s;
		SELECT_SQL;

		// サニタイズした返礼品コードをクエリの「%s」の部分に入れてクエリを完成させる
		$query = $wpdb->prepare( $query, $sku );

		// SQLクエリを実行し、結果を連想配列で取得
		$results = $wpdb->get_results( $query, ARRAY_A );

		// 結果をJSON形式に変換して出力
		header( 'Content-Type: application/json' );
		echo wp_json_encode( $results );
		exit;
	}
	/**
	 * 外部用API
	 */
	public function get_by_nopriv_user() {
		echo 'これはno_priv用のやつ';
	}
}
