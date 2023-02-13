<?php
/**
 * form item-image
 *
 * @package neoneng
 */

$default = array();
$args    = wp_parse_args( $args, $default );
$name    = $args['name'];
?>
<div class="wrap col-12" id="item-image">
	<input type="hidden" name="<?php echo $name; ?>" :value="JSON.stringify(商品画像)">
	<draggable v-model="商品画像" class="">
		<div v-for="(attr, index) in 商品画像" class="d-inline-block position-relative details attachment" style="width: auto;max-width: 25%;">
			<!-- リンク -->
			<a :href="attr.url" class="d-block" target="_blank">
				<img :src="attr.sizes.thumbnail.url || attr.sizes.thumbnail" style="cursor: move;">
			</a>
			<!-- 削除 -->
			<div role="button" @click="商品画像.splice(index, 1)" class="check rounded-circle d-flex justify-content-center align-items-center">
				<span class="dashicons dashicons-no-alt text-white"></span>
			</div>
			<!-- Download -->
			<a :href="`${ajaxurl}?action=download_by_url&url=${attr.url}&name=${返礼品コード}-${index}`" class="position-absolute bottom-0 end-0 badge rounded-pill bg-dark text-white text-decoration-none">▼ Download</a>
		</div>
	</draggable>
	<div @click="add_media" class="d-inline-block position-relative selected attachment position-relative" style="width:auto;max-width: 25%;">
		<div style="width: 150px;max-width: 100%;padding-top: 100%;background: #c3c4c7;"></div>
		<div class="position-absolute top-0 start-0 end-0 bottom-0 d-flex justify-content-center align-items-center">
			<span class="dashicons dashicons-plus-alt2 text-white"></span>
		</div>
	</div>
</div>
