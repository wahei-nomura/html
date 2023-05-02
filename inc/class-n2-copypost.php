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
	 * 返礼品複製
	 *
	 * @return void
	 */
	public function copy_create_post() {
		// 複製元の返礼品情報取得
		$post_id       = filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT );
		$post          = get_post( $post_id );
		$post_all_meta = N2_Functions::get_all_meta( $post );
		$author_id     = $post->post_author;
		$title         = get_the_title( $post_id );

		// metaを上書き
		$post_all_meta['寄附金額固定'] = array( '' ); // 非固定をデフォルトに

		// 新しい返礼品情報設定
		$new_post = array(
			'post_title'  => $title,
			'post_status' => 'draft',
			'post_author' => $author_id,
			'meta_input'  => $post_all_meta,
		);

		$new_id = wp_insert_post( $new_post );

		wp_safe_redirect( admin_url( "post.php?post={$new_id}&action=edit" ) );
		exit;
	}
}
