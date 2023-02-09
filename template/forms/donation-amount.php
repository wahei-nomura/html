<?php
/**
 * 寄附金額
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

<!-- 寄附金額ロック -->
<!-- 寄附金額再計算 -->
<div class="input-group">
	<label class="input-group-text">
		<input class="form-check-input mt-0" type="checkbox" name="n2field[寄附金額固定][]" value="true" v-model="寄附金額固定">
		<input type="hidden" name="n2field[寄附金額固定][]">
	</label>
	<input type="text"<?php echo $attr; ?>>
	<div class="btn btn-dark" @click="update_donation()" v-if="!寄附金額固定.includes('true')">再計算</div>
</div>