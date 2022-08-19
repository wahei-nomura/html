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
		// 複製元の返礼品情報取得
		$post     = get_post( filter_input( INPUT_POST, 'original_id', FILTER_VALIDATE_INT ) );
		$set_data = filter_input( INPUT_POST, 'set_data' );

		// $post_all_meta = N2_Functions::get_all_meta( $post );

		// // 新しい返礼品情報設定
		// $new_post = array(
		// 'post_title'  => get_the_title( $post ),
		// 'post_status' => 'draft',
		// 'post_author' => get_userdata( $post->post_author )->ID,
		// );

		// // 作成
		// $newpost_id = wp_insert_post( $new_post );

		// // metaを上書き
		// foreach ( $post_all_meta as $key => $value ) {
		// update_post_meta( $newpost_id, $key, $value );
		// }

		// if ( '' === $set_data['teiki'] ) {
		// update_post_meta( $newpost_id, '定期便', $set_data['teiki'] );
		// }

		// echo get_the_title( get_post( $newpost_id ) );
		echo json_encode( $set_data );
		die();
	}
}
