<?php
/**
 * class-n2-item-export.php
 *
 * @package neoneng
 */

if ( class_exists( 'N2_Item_Export' ) ) {
	new N2_Item_Export();
	return;
}

/**
 * Foodparam
 */
class N2_Item_Export {
	/**
	 * コンストラクタ
	 */
	public function __construct() {
		// エクスポート関連全読込
		$path = get_theme_file_path( '/inc/item-export/*.php' );
		foreach ( glob( $path ) as $filename ) {
			require_once $filename;
		}
	}
}
