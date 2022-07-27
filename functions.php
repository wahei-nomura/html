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
require_once get_template_directory() . '/config/config.php';

/**
 * Require inc
 */
$incs = array(
	'class-n2-loginlimit',
	'class-n2-functions',
	'class-n2-setupmenu',
	'class-n2-setmenu',
	'class-n2-setpost',
	'class-n2-postlist',
	'class-n2-setusers',
	'class-n2-dashboard',
	'class-n2-enqueuescript',
	'class-n2-sync',
	'class-n2-item-export',
	'class-n2-img-download',
);
foreach ( $incs as $name ) {
	require_once get_template_directory() . "/inc/{$name}.php";
}
