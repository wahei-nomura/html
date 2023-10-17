<?php
/**
 * class-n2-chonbo.php
 *
 * @package neoneng
 */

 if ( class_exists( 'N2_Portal_Item_Data' ) ) {
	new N2_Portal_Item_Data();
	return;
}

/**
 * ポータルスクレイピング
 */
class N2_Portal_Item_Data {

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'create_post_type' ) );
	}

	/**
	 * 投稿タイプ追加
	 */
	public function create_post_type() {
		register_post_type(
			'portal_item_data',
			array(
				'supports'      => array(
					'revisions',
				),
			)
		);
	}
}
