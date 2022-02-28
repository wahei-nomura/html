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
);
foreach ( $incs as $name ) {
	require_once get_template_directory() . "/inc/{$name}.php";
}
