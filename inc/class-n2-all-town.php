<?php
/**
 * class-n2-all-town.php
 *
 * @package neoneng
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'N2_All_Town' ) ) {
	new N2_All_Town();
	return;
}

/**
 * AllTown
 */
class N2_All_Town {
	/**
	 * コンストラクタ
	 */
	public function __construct() {
		$this->cls = get_class( $this );
		add_action( "wp_ajax_{$this->cls}_getdata", array( $this, 'get_check_data' ) );
		add_action( "wp_ajax_nopriv_{$this->cls}_getdata", array( $this, 'get_check_data' ) );
	}

	/**
	 * サイトネットワークトップに表示するデータを返す
	 */
	public function get_check_data(){
		$town_name = filter_input( INPUT_GET, 'townName' );
		$site_url  = filter_input( INPUT_GET, 'siteUrl' );

		$ids = get_posts(
			array(
				'post_type'     => 'post',
				'posts_per_page' => -1,
				'fields'        => 'ids',
				'meta_key'      => '事業者確認',
				'meta_value'    => '確認済',
				'meta_compare'  => 'LIKE',
			)
		);

		$posts = get_posts(
			array(
				'post_type'      => 'post',
				'posts_per_page' => -1,
				'exclude'        => implode( ',', $ids ),
				'fields'         => 'ids',
			)
		);

		$result = array(
			'townName' => $town_name,
			'count'    => count( $posts ),
			'townUrl'  => $site_url,
		);

		echo wp_json_encode( $result );
		exit;
	}
}
