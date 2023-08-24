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
			<span v-if="attr.description.length" :title="attr.description" class="position-absolute bottom-0 start-0 badge rounded-pill bg-danger text-white text-decoration-none">i</span>
			
			<!-- ポップオーバー -->
			<img :src="attr.sizes.thumbnail.url || attr.sizes.thumbnail" style="cursor: move;" :title="attr.description || ''" @click="document.getElementById(`image-popover-${index}`).showPopover()">
			<div popover :id="`image-popover-${index}`" style="max-width: 80%; max-height: 90%; border: 0; box-shadow: 0 0 0 100vw rgba(0,0,0,.5); padding: 0;">
				<div v-if="attr.description.length" v-text="attr.description" class="p-2 bg-dark text-white position-sticky top-0" @click="navigator.clipboard.writeText(attr.description).then(()=>{alert(`テキスト（${attr.description}）をコピーしました`);document.getElementById(`image-popover-${index}`).hidePopover();});"></div>
				<img :src="attr.url">
			</div>
			<!-- 削除 -->
			<div role="button" @click="商品画像.splice(index, 1)" class="check rounded-circle d-flex justify-content-center align-items-center">
				<span class="dashicons dashicons-no-alt text-white"></span>
			</div>
			<!-- Download -->
			<a :href="`${ajaxurl}?action=n2_download_image_by_url&url=${attr.url}&name=${返礼品コード}${index > 0 ? '-'+index : ''}`" class="position-absolute bottom-0 end-0 badge rounded-pill bg-dark text-white text-decoration-none" target="_blank">▼ Download</a>
		</div>
	</draggable>
	<div @click="add_media" class="d-inline-block position-relative selected attachment position-relative" style="width:auto;max-width: 25%;">
		<div style="width: 150px;max-width: 100%;padding-top: 100%;background: #c3c4c7;"></div>
		<div class="position-absolute top-0 start-0 end-0 bottom-0 d-flex justify-content-center align-items-center">
			<span class="dashicons dashicons-plus-alt2 text-white"></span>
		</div>
	</div>
</div>
