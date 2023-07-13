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
		// $path = get_theme_file_path( '/inc/item-export/*.php' );
		// foreach ( glob( $path ) as $filename ) {
		// 	require_once $filename;
		// }
		$path    = '/inc/item-export/';
		$exports = array(
			'class-n2-item-export-base.php',
			'class-n2-item-export-furusato-choice.php',
			'class-n2-item-export-ledghome.php',
			'class-n2-item-export-rakuten.php',
			'class-n2-item-export-rakuten-select.php',
			'class-n2-item-export-rakuten-sku.php',
			'class-n2-product-list-print.php',
		);
		foreach ( $exports as $export ) {
			require_once get_theme_file_path( $path . $export );
		}
	}
}
