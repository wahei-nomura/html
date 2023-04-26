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
		$post          = get_post( filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT ) );
		$new_status    = filter_input( INPUT_POST, 'status', FILTER_SANITIZE_SPECIAL_CHARS );
		$post_all_meta = N2_Functions::get_all_meta( $post );
		$author_id     = $post->post_author;

		// POST受信を配列か
		$set_data = array(
			'複写後商品名' => filter_input( INPUT_POST, '複写後商品名', FILTER_SANITIZE_FULL_SPECIAL_CHARS ),
			'定期'     => filter_input( INPUT_POST, '定期', FILTER_VALIDATE_INT ),
		);

		// metaを上書き
		$post_all_meta['定期便']      = $set_data['定期'];
		$post_all_meta['寄附金額固定'] = array( '固定する' ); // 固定をデフォルトに

		// 新しい返礼品情報設定
		$new_post = array(
			'post_title'  => $set_data['定期'] > 1 ? "【全{$set_data['定期']}回定期便】{$set_data['複写後商品名']}" : $set_data['複写後商品名'],
			'post_status' => $new_status,
			'post_author' => $author_id,
			'meta_input'  => $post_all_meta,
		);

		$new_id = wp_insert_post( $new_post );

		wp_safe_redirect( admin_url( "post.php?post={$new_id}&action=edit" ) );
		exit;
	}
}
