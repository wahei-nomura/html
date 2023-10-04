<?php
/**
 * GS ユーザー同期
 *
 * @package neoneng
 */

$default  = array(
	'id'    => '',
	'range' => '',
);
$settings = get_option( 'n2_sync_settings_spreadsheet', $default );
?>
<h2 style="margin-top: 1em; font-size: 2em;">GS ユーザーの同期</h2>
<p>Googleスプレットシートからユーザーデータを同期します。</p>
