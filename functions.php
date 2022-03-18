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
	'class-n2-hogehoge',
	'class-n2-setmenu',
	'class-n2-setpost',
	'class-n2-engineersetup',
);
foreach ( $incs as $name ) {
	require_once get_template_directory() . "/inc/{$name}.php";
}
