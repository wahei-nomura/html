<?php
/**
 * functions.php
 *
 * @package neoneng
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
 * Require config.php
 */
require_once get_theme_file_path( '/config/config.php' );

/**
 * Require inc
 */
$incs = array(
	'class-n2-auto-redirect',
	'class-n2-loginlimit',
	'class-n2-functions',
	'class-n2-jigyousyaparam',
	'class-n2-setupmenu',
	'class-n2-setmenu',
	'class-n2-admin-post-editor',
	'class-n2-postlist',
	'class-n2-setusers',
	'class-n2-dashboard',
	'class-n2-enqueuescript',
	'class-n2-sync',
	'class-n2-copypost',
	'class-n2-front',
	'class-n2-img-download',
	'class-n2-all-town',
	'class-n2-item-export',
	'class-n2-user-profile',
	'class-n2-semi-auto-data-importer',
	// 'class-n2-front-comment', // 2022-11-29 コメントアウト taiki
	'class-n2-rakuten-transfer',
	'api/class-n2-donation-amount-api',
	'api/class-n2-output-gift-api',
	'api/class-n2-post-history-api',
	'api/class-n2-users-api',
);
foreach ( $incs as $name ) {
	require_once get_theme_file_path( "/inc/{$name}.php" );
}
