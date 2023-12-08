<?php
/**
 * functions.php
 *
 * @package neoneng
 */

/**
 * グローバル変数 $n2 生成
 */
require_once get_theme_file_path( 'inc/class-n2.php' );
add_action(
	'after_setup_theme',
	function () {
		$GLOBALS['n2'] = new N2();
	}
);
/**
 * Require inc
 */
$incs = array(
	'class-n2-auto-redirect',
	'class-n2-loginlimit',
	'class-n2-functions',
	'class-n2-settings',
	'class-n2-rakuten-sftp',
	'class-n2-setmenu',
	'class-n2-custom-query',
	'class-n2-admin-post-editor',
	'class-n2-admin-post-list',
	'class-n2-setusers',
	'class-n2-dashboard',
	'class-n2-enqueuescript',
	'class-n2-sync',
	'class-n2-front',
	'class-n2-img-download',
	'class-n2-all-town',
	'class-n2-item-export',
	'class-n2-user-profile',
	'class-n2-semi-auto-data-importer',
	'class-n2-chonbo',
	'class-n2-rakuten-ftp',
	'api/class-n2-donation-amount-api',
	'api/class-n2-rms-base-api',
	'api/class-n2-rms-cabinet-api',
	'api/class-n2-rms-category-api',
	'api/class-n2-rms-navigation-api',
	'api/class-n2-rms-shop-api',
	'api/class-n2-rms-items-api',
	'class-n2-chonbo',
	'api/class-n2-output-gift-api',
	'api/class-n2-post-history-api',
	'api/class-n2-users-api',
	'class-n2-chrome-checker',
	'api/class-n2-items-api',
	'api/class-n2-blogs-api',
	'api/class-n2-multi-url-request-api',
	'class-n2-change-allergen',
	'class-n2-change-sku-firstaid',
	'class-n2-portal-item-data',
);

foreach ( $incs as $name ) {
	require_once get_theme_file_path( "/inc/{$name}.php" );
}
