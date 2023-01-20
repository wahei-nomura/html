<?php
/**
 * form rakuten-spa-category
 * 楽天SPAカテゴリー
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
<textarea <?php echo $attr; ?> @click="get_spa_category"></textarea>
<div v-if="楽天SPAカテゴリー.list.length">
	<span
		role="button"
		v-for="v in 楽天SPAカテゴリー.list"
		:class="`btn btn-sm me-1 mb-1 py-0 ${楽天SPAカテゴリー.text ? ( 楽天SPAカテゴリー.text.split('\n').includes(v) ? 'btn-danger': 'btn-dark' ): 'btn-dark'}`"
		@click="update_textarea(v, '楽天SPAカテゴリー', '\n')"
		v-text="v.replace('#/', '').split('/').filter(v=>v).join(' > ')"
	></span>
</div>
