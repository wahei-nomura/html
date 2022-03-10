<?php
/**
 * index.php
 *
 * @package neoneng
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// ローカル開発用にログインリンク
if ( 'localhost' === $_SERVER['HTTP_HOST'] ) {
	echo '<a href="' . admin_url() . '">ログイン</a>';
}
