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
		$files = array(
			'class-n2-item-export-base',
			'class-n2-item-export-furusato-choice',
			'class-n2-item-export-ledghome',
			'class-n2-item-export-lhcloud',
			'class-n2-item-export-rakuten',
			'class-n2-item-export-rakuten-select',
			'class-n2-item-export-rakuten-sku',
			'class-n2-item-export-rakuten-cat',
			'class-n2-product-list-print',
			'class-n2-user-export-base',
		);
		// フルパス生成
		$files = array_map( fn( $v ) => get_theme_file_path( "/inc/item-export/{$v}.php" ), $files );
		/**
		 * [hook] n2_item_export_files
		 */
		$files = apply_filters( 'n2_item_export_files', $files );

		// $filesを全部読み込み
		foreach ( $files as $file ) {
			require_once $file;
		}
	}
}
