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
<h3 style="margin-top: 5em;">GS ユーザーの同期</h3>
<p>Googleスプレットシートからユーザーデータを同期します。</p>
