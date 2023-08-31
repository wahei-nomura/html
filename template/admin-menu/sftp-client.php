<?php
/**
 * SFTP CLIENT VIEW
 *
 * @package neo-neng
 */

$connect = N2_RMS_Cabinet_API::ajax(
	array(
		'call' => 'connect',
		'mode' => 'func',
	),
);

if ( ! $connect ) {
	echo 'RMS CABINETに接続できませんでした。';
	die();
}

?>
<div id="ss-cabinet" class="container-fluid">
	<input type="hidden" name="n2nonce" value="<?php echo esc_attr( wp_create_nonce( 'n2nonce' ) ); ?>">
</div>
