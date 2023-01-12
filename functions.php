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
 * Require config.php
 */
require_once get_theme_file_path() . '/config/config.php';

/**
 * Require inc
 */
$incs = array(
	'class-n2-loginlimit',
	'class-n2-functions',
	'class-n2-jigyousyaparam',
	'class-n2-setupmenu',
	'class-n2-setmenu',
	'class-n2-setpost',
	'class-n2-postlist',
	'class-n2-setusers',
	'class-n2-dashboard',
	'class-n2-enqueuescript',
	'class-n2-sync',
	'class-n2-item-export',
	'class-n2-copypost',
	'class-n2-front',
	'class-n2-img-download',
	'class-n2-all-town',
	// 'class-n2-front-comment', // 2022-11-29 コメントアウト taiki
);
foreach ( $incs as $name ) {
	require_once get_theme_file_path() . "/inc/{$name}.php";
}
