<?php
/**
 * class-n2-copypost.php
 *
 * @package neoneng
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'N2_Copypost' ) ) {
	new N2_Copypost();
	return;
}

/**
 * Setpost
 */
class N2_Copypost {
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
		$this->cls = get_class( $this );
		add_action( "wp_ajax_{$this->cls}", array( $this, 'copy_create_post' ) );
	}

	/**
	 * 投稿複製
	 *
	 * @return void
	 */
	public function copy_create_post() {
		$post = array(
			'post_title'  => '複製テスト',
			'post_status' => 'draft',
			'post_author' => 'admin',
		);

		$newpost_id = wp_insert_post( $post );
		echo $newpost_id;
		die();
	}
}
