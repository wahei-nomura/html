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
		<div v-for="(attr, index) in 商品画像" class="d-inline-block m-1">
			<img :src="attr.sizes.thumbnail.url || attr.sizes.thumbnail">
			<div>
				<span class="badge rounded-pill bg-dark">{{index+1}}</span>
				<span role="button" @click="商品画像.splice(index, 1)" class="badge rounded-pill bg-dark">削除</span>
				<span role="button" @click="商品画像.splice(index, 1)" class="badge rounded-pill bg-dark">拡大</span>
			</div>
		</div>
	</draggable>
	<div class="button" @click="add_media">画像を追加</div>
</div>
<!-- /.button -->
<!-- <div class="%1$s-image-block">
	<input type="hidden" class="%1$s-image-input" name="%2$s[]" value="%3$s">
	<span class="%1$s-image-delete dashicons dashicons-no-alt"></span>
	<span class="%1$s-image-big dashicons dashicons-editor-expand"></span>
	<span class="%1$s-image-num"></span>
	<img class="%1$s-image-url" src="%4$s" alt="" width="100%%" height="100%%" />
</div> -->
