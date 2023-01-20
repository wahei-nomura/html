<?php
/**
 * form rakuten-genreid
 * 楽天 タグID
 *
 * @package neoneng
 */

$defaults = array();
$args     = wp_parse_args( $args, $defaults );
$attr     = '';
foreach ( $args as $k => $v ) {
	$attr .= " {$k}=\"{$v}\"";
}
?>
<small>{{タグID.text.length}} / 256 Byte</samll>
<textarea <?php echo $attr; ?> @click="get_genreid();"><?php echo $value; ?></textarea>
<div class="d-flex" v-if="全商品ディレクトリID.list.length != 0">
	<div class="col-5 gap-1 p-1 me-2" style="max-height: 200px; overflow-y: scroll;">
		<p
			role="button"
			v-for="v in 全商品ディレクトリID.list.tagGroups"
			:class="v.tagGroup.tagGroupName == タグID.group ? 'bg-white m-0 p-1': 'm-0 p-1'"
			@click="タグID.list = v.tagGroup.tags;タグID.group = v.tagGroup.tagGroupName"
			v-text="v.tagGroup.tagGroupName"
		></p>
	</div>
	<div class="col-7" style="max-height: 200px; overflow-y: scroll;">
		<span
			role="button"
			v-for="v in タグID.list"
			:class="`btn btn-sm me-1 mb-1 py-0 ${タグID.text.split('/').includes(v.tag.tagId.toString()) ? 'btn-danger': 'btn-dark'}`"
			@click="update_tagid(v.tag.tagId)"
			v-text="v.tag.tagName"
		></span>
	</div>
</div>
