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
 * Require inc
 */
$incs = array(
	'class-n2-hogehoge',
	'class-n2-setmenu',
	'class-n2-setpost',
);
foreach ( $incs as $name ) {
	require_once get_template_directory() . "/inc/{$name}.php";
}
