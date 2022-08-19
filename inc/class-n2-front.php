<?php
/**
 * class-n2-front.php
 *
 * @package neoneng
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'N2_Front' ) ) {
	new N2_Front();
	return;
}

/**
 * Front
 */
class N2_Front {
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
		$this->cls  = get_class( $this );
		$this->page = 'edit.php';
		add_action( "wp_ajax_{$this->cls}", array( $this, 'ajax' ) );
	}


	/**
	 * JSに返礼品コード一覧を渡す
	 *
	 * @return void
	 */
	public function ajax() {
		global $wpdb;
		$jigyousya = filter_input( INPUT_GET, '事業者', FILTER_VALIDATE_INT );

		if ( ! empty( $jigyousya ) && '' !== $jigyousya ) {
			$sql = "SELECT * FROM $wpdb->posts WHERE $wpdb->posts.post_author = $jigyousya ;";
		} else {
			$sql = "SELECT * FROM $wpdb->posts ;";
		}
		$result = $wpdb->get_results( $sql );
		$arr    = array();
		foreach ( $result as $row ) {
			if (
				! empty( get_post_meta( $row->ID, '返礼品コード', 'true' ) ) &&
				'' !== get_post_meta( $row->ID, '返礼品コード', 'true' )
				) {
				$arr[ $row->ID ] = get_post_meta( $row->ID, '返礼品コード', 'true' ) . 'test';
			}
		}

		// echo json_encode( $arr );

		$newarr = array();
		$args = array(
			'paged' => $paged,
			'posts_per_page' => 20,
			'post_status' => 'any',

		);
		$wp_query = new WP_Query( $args );
		if ( $wp_query->have_posts() ) {
			while ( $wp_query->have_posts() ) {
				$wp_query->the_post();
				array_push($newarr, get_the_title());
			}
		}
		wp_reset_postdata();

		echo json_encode( $newarr );
		die();
	}

}
