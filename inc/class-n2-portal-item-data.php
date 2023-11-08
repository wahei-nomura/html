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
		add_action( 'init', array( $this, 'enable_portal_items_api' ) );

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

	/**
	 * API読込
	 */
	public function enable_portal_items_api() {
		global $n2;
		$translates = array_flip( N2_Settings::$settings );
		foreach ( array_filter( $n2->settings['N2']['出品ポータル'] ?? array() ) as $name ) {
			require_once get_theme_file_path( "/inc/api/class-n2-items-{$translates[ $name ]}-api.php" );
		}
	}
}
