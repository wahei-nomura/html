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
$value    = $args['value']['text'] ?? '';
unset( $args['value'] );
foreach ( $args as $k => $v ) {
	$v     = esc_attr( $v );// エスケープしないとバグる
	$attr .= " {$k}=\"{$v}\"";
}
?>
<textarea <?php echo $attr; ?>><?php echo $value; ?></textarea>
<div v-if="tmp.楽天SPAカテゴリー">
	<span
		role="button"
		v-for="v in tmp.楽天SPAカテゴリー"
		:class="`btn btn-sm me-1 mb-1 py-0 ${楽天SPAカテゴリー ? ( 楽天SPAカテゴリー.split('\n').includes(v) ? 'btn-danger': 'btn-dark' ): 'btn-dark'}`"
		@click="update_textarea(v)"
		v-text="v.replace('#/', '').split('/').filter(v=>v).join(' > ')"
	></span>
</div>
