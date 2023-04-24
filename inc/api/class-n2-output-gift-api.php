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
		$n2_active_flag = $n2->n2_active_flag;

		// N2が稼働していない or そもそも稼働状態が登録されていなかったらJSONでfalseを返す
		if  ( 'false' === $n2_active_flag ) {
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
		$gift_query = $wpdb->prepare( $gift_query, $sku );

		// SQLクエリを実行し、結果を連想配列で取得
		$results = $wpdb->get_results( $gift_query, ARRAY_A );

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

		// N2稼働フラグを追加する階層を作るために$giftをarrayに格納
		$gift_array = array( $gift );

		// N2稼働フラグを追加
		$result_array = array_merge( $gift_array, array( array( 'N2' => 'true' ) ) );
		
		// インデックス番号を取って必要なデータを取り出す & 順番を入れ替える
		$results = $result_array[1];
		$results['data'] = $result_array[0];

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
		$n2_active_flag = $n2->n2_active_flag;

		// N2が稼働していない or そもそも稼働状態が登録されていなかったらJSONでfalseを返す
		if  ( 'false' === $n2_active_flag ) {
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
		$gift_query = $wpdb->prepare( $gift_query, $sku );

		// SQLクエリを実行し、結果を連想配列で取得
		$results = $wpdb->get_results( $gift_query, ARRAY_A );

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

		// N2稼働フラグを追加する階層を作るために$giftをarrayに格納
		$gift_array = array( $gift );

		// N2稼働フラグを追加
		$result_array = array_merge( $gift_array, array( array( 'N2' => 'true' ) ) );
		
		// インデックス番号を取って必要なデータを取り出す & 順番を入れ替える
		$results = $result_array[1];
		$results['data'] = $result_array[0];

		// 結果をJSON形式に変換して出力
		header( 'Content-Type: application/json' );
		echo wp_json_encode( $results );
		exit;
	}
}
