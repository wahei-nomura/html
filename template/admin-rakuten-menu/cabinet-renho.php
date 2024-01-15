<?php
/**
 * キャビ蓮舫
 *
 * @package neo-neng
 */

global $n2;
$img_dir = rtrim( $n2->settings['楽天']['商品画像ディレクトリ'], '/' );
?>
<div id="ss-rakuten-auto-update">
	<input id="n2nonce" type="hidden" name="n2nonce" value="<?php echo esc_attr( wp_create_nonce( 'n2nonce' ) ); ?>">
	<input id="n2nonce" type="hidden" name="imgDir" value="<?php echo esc_attr( $img_dir ); ?>">
	Loading...
</div>
