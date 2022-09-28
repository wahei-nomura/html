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
		$data = file_get_contents( 'https://steamship.co.jp/shiroishi/wp-json/wp/v2/posts?sku=test002' );
		$arr  = json_decode( $data, true );
		foreach ( $arr as $v ) {

			$postarr = array(
				'status'      => $v['publish'],
				'type'        => $v['type'],
				'post_title'  => $v['title']['rendered'],
				'post_author' => $v['author'],
				'meta_input'  => $v['acf'],
			);
			wp_insert_post( $postarr );
		}
		exit;
	}
}
