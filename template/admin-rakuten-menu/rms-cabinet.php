<?php
/**
 * SFTP CLIENT VIEW
 *
 * @package neo-neng
 */
?>
<div id="ss-cabinet" class="container-fluid">
	<input id="n2nonce" type="hidden" name="n2nonce" value="<?php echo esc_attr( wp_create_nonce( 'n2nonce' ) ); ?>">
	<div class="cabinet-modal position-fixed top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center">
		<span class="spinner-grow text-primary" role="status"></span>
	</div>
</div>
