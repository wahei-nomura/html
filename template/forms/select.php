<?php
/**
 * form select
 *
 * @package neoneng
 */

$defaults     = array();
$args         = wp_parse_args( $args, $defaults );
$option       = $args['option'];
$value        = $args['value'];
$option_equal = $args['option-equal'] ?? false;// optionのvalueと表示名が一緒かどうか判定
unset( $args['option'], $args['value'], $args['option-equal'] );
$attr = '';
foreach ( $args as $k => $v ) {
	$v     = esc_attr( $v );// エスケープしないとバグる
	$attr .= " {$k}=\"{$v}\"";
}
?>
<select <?php echo $attr; ?>>
	<?php
	foreach ( $option as $val => $data ) :
		// 属性の追加
		$text = $data;
		$attr = '';
		if ( is_array( $data ) ) {
			foreach ( $data as $k => $v ) {
				switch ( $k ) {
					case 'label':
						$text = $v;
						break;
					default:
						$attr .= " {$k}=\"{$v}\"";
				}
			}
		}
		// 値とラベルが同一の場合
		$val = $option_equal ? $text : $val;
	?>
	<option
		<?php echo $attr; ?>
		value="<?php echo $val; ?>"
		<?php selected( $value, $val ); ?>
	>
		<?php echo $text; ?>
	</option>
	<?php endforeach; ?>
</select>
