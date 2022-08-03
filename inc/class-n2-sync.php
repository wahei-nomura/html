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
		$params  = http_build_query(
			array(
				'per_page' => 100,
				'page'     => 5,
			)
		);
		$data    = file_get_contents( "{$url}?{$params}" );
		$headers = iconv_mime_decode_headers( implode( "\n", $http_response_header ) );
		// 合計情報
		$total = $headers['X-WP-Total'];
		$pages = $headers['X-WP-TotalPages'];
		echo '<pre>';
		echo "合計：{$total}\n";
		echo "ページ数：{$pages}\n";
		$arr = json_decode( $data, true );
		// exit;
		foreach ( $arr as $v ) {

			$postarr = array(
				'status'      => $v['publish'],
				'type'        => $v['type'],
				'post_title'  => $v['title']['rendered'],
				'post_author' => $v['author'],
				'meta_input'  => $v['acf'],
			);
			print_r($postarr);
			// wp_insert_post( $postarr );
		}
		exit;
	}
}
