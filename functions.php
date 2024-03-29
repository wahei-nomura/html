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
	'class-n2-img-download',
	'class-n2-item-export',
	'class-n2-user-profile',
	'api/class-n2-donation-amount-api',
	'api/class-n2-rms-base-api',
	'api/class-n2-rms-cabinet-api',
	'api/class-n2-rms-category-api',
	'api/class-n2-rms-navigation-api',
	'api/class-n2-rms-shop-api',
	'api/class-n2-rms-items-api',
	'api/class-n2-output-gift-api', // おーとめ用
	'api/class-n2-post-history-api',
	'api/class-n2-users-api',
	'api/class-n2-items-api',
	'api/class-n2-blogs-api',
	'api/class-n2-multi-url-request-api',
	'class-n2-portal-item-data',
	'api/class-n2-openai-base-api',
	'api/class-n2-openai-chat-api',
	'api/class-n2-openai-assistants-api',
	'class-n2-notification', // お知らせ(管理用)
	'class-n2-notification-read', // お知らせ(閲覧用)
);

foreach ( $incs as $name ) {
	require_once get_theme_file_path( "/inc/{$name}.php" );
}
