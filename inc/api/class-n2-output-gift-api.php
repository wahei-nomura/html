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
		global $n2;
		// 自治体コードを取得
		$site_id = $n2->site_id;
		// N2稼働中か判定するフラグを取得
		$n2_active_flag = $n2->settings['N2']['稼働中'];

		// N2が稼働していない or そもそも稼働状態が登録されていなかったらJSONでfalseを返す
		if ( ! $n2_active_flag ) {
			header( 'Content-Type: application/json' );
			echo '{"N2": "false"}';
			exit;
		}

		// URLの末尾からskuを取得、サニタイズ
		$sku = isset( $_GET['sku'] ) ? sanitize_text_field( $_GET['sku'] ) : '';

		// N2稼働中でskuが空の場合、N2稼働中であることだけ知らせて処理を終了する
		if ( '' === $sku ) {
			header( 'Content-Type: application/json' );
			echo '{"N2": "true"}';
			exit;
		}

		// 返礼品情報を取得するSQLクエリを準備
		$gift_query = <<<SELECT_SQL
		SELECT
			posts.post_title as title,
			posts.post_type,
			posts.post_status,
			postmeta.meta_key,
			postmeta.meta_value
		FROM
			wp_{$site_id}_posts as posts
		INNER JOIN
			wp_{$site_id}_postmeta as postmeta
		ON
			posts.id = postmeta.post_id
		WHERE
			posts.post_type = 'post' AND
			posts.post_status != 'trash' AND
			posts.id in (
				SELECT
					post_id
				FROM
					wp_{$site_id}_postmeta
				WHERE
					meta_key = '返礼品コード'
				AND
				    meta_value = %s
			);
		SELECT_SQL;

		// サニタイズした返礼品コードをクエリの「%s」の部分に入れてクエリを完成させる
		$gift_query = $wpdb->prepare( $gift_query, $sku );

		// SQLクエリを実行し、結果を連想配列で取得
		$data = $wpdb->get_results( $gift_query, ARRAY_A );

		// titleがnullの場合、N2稼働状況だけ返す（skuが入力されているが無効の場合の処理）
		if ( null === $data[0]['title'] ) {
			header( 'Content-Type: application/json' );
			echo '{"N2": "true"}';
			exit;
		}

		// 結果の連想配列からキーを取り出す
		$existing_keys = array();
		foreach ( $data as $datum ) {
			$existing_keys[] = $datum['meta_key'];
		}

		// 最終的に出力が期待されるキーのリストを作成
		$expected_keys = array(
			'送料',
			'寄附金額',
			'全商品ディレクトリID',
			'地場産品類型',
			'類型該当理由',
			'価格',
			'説明文',
			'内容量・規格等',
			'原料原産地',
			'加工地',
			'アレルゲン',
			'アレルゲン注釈',
			'申込期間',
			'配送期間',
			'賞味期限',
			'消費期限',
			'限定数量',
			'発送方法',
			'包装対応',
			'のし対応',
			'発送サイズ',
			'定期便',
			'LHカテゴリー',
			'検索キーワード',
			'略称',
			'表示名称',
			'返礼品コード',
			'社内共有事項',
			'配送伝票表示名',
			'_neng_id',
			'寄附金額固定',
			'取り扱い方法',
			'商品画像',
			'商品タイプ',
			'キャッチコピー',
			'楽天SPAカテゴリー',
			'事業者確認',
			'電子レンジ対応',
			'オーブン対応',
			'食洗機対応',
			'やきもの',
		);

		// 実際に存在するキーと出力が期待されるキーとを比較し、不足しているキーがあれば、そのキーとその値（空文字）をdataに追加
		$missing_keys = array_diff( $expected_keys, $existing_keys );
		foreach ( $missing_keys as $missing_key ) {
			$data[] = array(
				'title'      => $data[0]['title'],
				'meta_key'   => $missing_key,
				'meta_value' => '',
			);
		}

		// 空の配列を作って取得したデータを整える
		$results = array(
			'N2'   => true,
			'data' => array(),
		);
		foreach ( $data as $datum ) {
			$title      = $datum['title'];
			$meta_key   = $datum['meta_key'];
			$meta_value = $datum['meta_value'];

			if ( ! isset( $results['data']['title'] ) ) {
				$results['data']['title'] = $datum['title'];
			}
			$results['data'][ $meta_key ] = $meta_value;
		}

		// 結果をJSON形式に変換して出力
		header( 'Content-Type: application/json' );
		echo wp_json_encode( $results );
		exit;
	}
	/**
	 * 非プライベートユーザー用API
	 */
	public function get_by_nopriv_user() {
		global $wpdb;
		global $n2;
		// 自治体コードを取得
		$site_id = $n2->site_id;
		// N2稼働中か判定するフラグを取得
		$n2_active_flag = $n2->settings['N2']['稼働中'];

		// N2が稼働していない or そもそも稼働状態が登録されていなかったらJSONでfalseを返す
		if ( ! $n2_active_flag ) {
			header( 'Content-Type: application/json' );
			echo '{"N2": "false"}';
			exit;
		}

		// URLの末尾からskuを取得、サニタイズ
		$sku = isset( $_GET['sku'] ) ? sanitize_text_field( $_GET['sku'] ) : '';

		// skuが空の場合、N2稼働中であることだけ知らせて処理を終了する
		if ( '' === $sku ) {
			header( 'Content-Type: application/json' );
			echo '{"N2": "true"}';
			exit;
		}

		// テーブルから情報を取得するSQLクエリを準備
		$gift_query = <<<SELECT_SQL
		SELECT
			posts.post_title as title,
			posts.post_type,
			posts.post_status,
			postmeta.meta_key,
			postmeta.meta_value
		FROM
			wp_{$site_id}_posts as posts
		INNER JOIN
			wp_{$site_id}_postmeta as postmeta
		ON
			posts.id = postmeta.post_id
		WHERE
			post.post_type = 'post' AND
			posts.post_status != 'trash' AND
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
				'配送期間',
				'やきもの'
				)
		AND
			posts.id in (
				SELECT
					post_id
				FROM
					wp_{$site_id}_postmeta
				WHERE
					meta_key = '返礼品コード'
				AND
				    meta_value = %s
			);
		SELECT_SQL;

		// サニタイズした返礼品コードをクエリの「%s」の部分に入れてクエリを完成させる
		$gift_query = $wpdb->prepare( $gift_query, $sku );

		// SQLクエリを実行し、結果を連想配列で取得
		$data = $wpdb->get_results( $gift_query, ARRAY_A );

		// titleがnullの場合、N2稼働状況だけ返す（skuが無効の場合の処理）
		if ( null === $data[0]['title'] ) {
			header( 'Content-Type: application/json' );
			echo '{"N2": "true"}';
			exit;
		}

		// 結果の連想配列からキーを取り出す
		$existing_keys = array();
		foreach ( $data as $datum ) {
			$existing_keys[] = $datum['meta_key'];
		}

		// 最終的に出力が期待されるキーのリストを作成
		$expected_keys = array(
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
			'配送期間',
			'やきもの',
		);

		// 実際に存在するキーと出力が期待されるキーとを比較し、不足しているキーがあれば、そのキーとその値（空文字）をdataに追加
		$missing_keys = array_diff( $expected_keys, $existing_keys );
		foreach ( $missing_keys as $missing_key ) {
			$data[] = array(
				'title'      => $data[0]['title'],
				'meta_key'   => $missing_key,
				'meta_value' => '',
			);
		}

		// 空の配列を作って取得したデータを整える
		$results = array(
			'N2'   => true,
			'data' => array(),
		);
		foreach ( $data as $datum ) {
			$title      = $datum['title'];
			$meta_key   = $datum['meta_key'];
			$meta_value = $datum['meta_value'];

			if ( ! isset( $results['data']['title'] ) ) {
				$results['data']['title'] = $datum['title'];
			}
			$results['data'][ $meta_key ] = $meta_value;
		}

		// 結果をJSON形式に変換して出力
		header( 'Content-Type: application/json' );
		echo wp_json_encode( $results );
		exit;
	}
}
