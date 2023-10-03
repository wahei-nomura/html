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
<div v-if="楽天カテゴリー.list.length" style="width:100%">
	<input
		type="text"
		list="n2-rakuten-category"
		style="width:100%;"
		placeholder="ここをクリックまたは入力すると現在登録されているカテゴリーが選択出来ます。"
		@change="update_textarea_by_selected_option($event, '楽天カテゴリー', '\n')"
	>
	<datalist id="n2-rakuten-category">
		<option
			v-for="v in 楽天カテゴリー.list"
			v-text="v"
		></option>
	</datalist>
</div>
