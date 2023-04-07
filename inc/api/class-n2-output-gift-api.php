<?php

/**
 * N2返礼品出力API
 *
 * @package neoneng
 */

if (class_exists('N2_Output_Gift_API')) {
	new N2_Output_Gift_API();
	return;
}

class N2_Output_Gift_API
{

	public function __construct()
	{
		add_action('wp_ajax_n2_output_gift_api', array($this, 'get'));
	}

	/**
	 * 外部用API
	 */
	public function get()
	{
		global $wpdb;

		// URLの末尾からcodeを取得、サニタイズ
		$code = isset($_GET['code']) ? sanitize_text_field($_GET['code']) : '';

		// 自治体コードを取得
		global $n2;
		$id = ($n2->site_id);

		// wp_postsテーブルから情報を取得するSQLクエリを準備
		$query = <<<SELECT_SQL
		SELECT
			a.post_title as title,
			b.寄附金額,
			b.消費期限,
			b.賞味期限,
			b.説明文,
			b.電子レンジ対応,
			b.オーブン対応,
			b.食洗機対応,
			b.内容量・規格等,
			b.発送方法,
			b.定期便,
			b.包装対応,
			b.のし対応,
			b.配送期間
		FROM
			wp_{$id}_posts as a
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
				wp_{$id}_postmeta
			GROUP BY
				post_id ) as b
		ON
			b.post_id = a.id
		WHERE
		    b.返礼品コード = %s;
		SELECT_SQL;

		// サニタイズした返礼品コードをクエリの「%s」の部分に入れてクエリを完成させる
		$query = $wpdb->prepare($query, $code);

		// SQLクエリを実行し、結果を連想配列で取得
		$results = $wpdb->get_results($query, ARRAY_A);

		// 結果をJSON形式に変換して出力
		header('Content-Type: application/json');
		echo json_encode($results);
		exit;
	}
}
