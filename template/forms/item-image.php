<?php
/**
 * form item-image
 *
 * @package neoneng
 */

$default = array();
$args    = wp_parse_args( $args, $default );
$name    = $args['name'];
$value   = (array) $args['value'];
$style   = $args['style'];
?>
<div class="wrap">
	<input type="hidden" name="<?php echo $name; ?>" :value="JSON.stringify(商品画像)">
	<draggable v-model="商品画像" class="">
		<div v-for="(attr, index) in 商品画像" class="d-inline-block position-relative details attachment">
			<a :href="attr.url" class="d-block" target="_blank">
				<img :src="attr.sizes.thumbnail.url || attr.sizes.thumbnail" style="cursor: move;">
			</a>
			<!-- 削除 -->
			<div role="button" @click="商品画像.splice(index, 1)" class="check rounded-circle d-flex justify-content-center align-items-center">
				<span class="dashicons dashicons-no-alt text-white"></span>
			</div>
			<a :href="attr.url" target="_blank"　download class="position-absolute bottom-0 end-0 badge rounded-pill bg-dark text-white">
				▼ Download
			</a>
		</div>
	</draggable>
	<div @click="add_media" class="d-inline-block position-relative selected attachment position-relative">
		<div style="padding-top: 100%;background: #c3c4c7;"></div>
		<div class="position-absolute top-0 start-0 end-0 bottom-0 d-flex justify-content-center align-items-center">
			<span class="dashicons dashicons-plus-alt2 text-white"></span>
		</div>
		<!-- /.position-absolute -->
	</div>
</div>
<!-- /.button -->
<!-- <div class="%1$s-image-block">
	<input type="hidden" class="%1$s-image-input" name="%2$s[]" value="%3$s">
	<span class="%1$s-image-delete dashicons dashicons-no-alt"></span>
	<span class="%1$s-image-big dashicons dashicons-editor-expand"></span>
	<span class="%1$s-image-num"></span>
	<img class="%1$s-image-url" src="%4$s" alt="" width="100%%" height="100%%" />
</div> -->
