<?php
/**
 * class-n2-setusers.php
 *
 * @package neoneng
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'N2_Sync' ) ) {
	new N2_Sync();
	return;
}

/**
 * Setusers
 */
class N2_Sync {
	/**
	 * コンストラクタ
	 */
	public function __construct() {
		add_action( 'wp_ajax_n2sync', array( $this, 'sync' ) );
	}

	/**
	 * NNS
	 *
	 * @return void
	 */
	public function sync() {
		$town = 'kawatana';
		$url  = "https://steamship.co.jp/{$town}/wp-json/wp/v2/posts";
		// params
		$params = array(
			'per_page' => 100,
			'page'     => 1,
		);
		// ページ
		$pages = 1;
		while ( $params['page'] <= $pages ) {
			$data    = file_get_contents( "{$url}?" . http_build_query( $params ) );
			$headers = iconv_mime_decode_headers( implode( "\n", $http_response_header ) );
			// 合計情報
			$total = $headers['X-WP-Total'];
			$pages = $headers['X-WP-TotalPages'];
			$params['page']++;
			$arr = json_decode( $data, true );
			// exit;
			foreach ( $arr as $v ) {
				// 返礼品情報を生成
				$postarr = array(
					'status'      => $v['status'],
					'type'        => $v['type'],
					'post_title'  => $v['title']['rendered'],
					'post_author' => $v['author'],
					'meta_input'  => $v['acf'],
				);
				// 「返礼品コード」が既に登録済みか調査
				$args = array(
					'post_type'   => 'post',
					'meta_key'    => '返礼品コード',
					'meta_value'  => $v['acf']['返礼品コード'],
					'post_status' => 'any',
				);
				// 返礼品の投稿IDを取得
				$id = get_posts( $args )[0]->ID;
				// 登録済みの場合
				if ( $id ) {
					$postarr['ID'] = $id;
				}
				wp_insert_post( $postarr );
			}
		}
		exit;
	}
}
