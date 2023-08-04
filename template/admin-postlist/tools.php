<?php
/**
 * 投稿ツール
 *
 * @package neoneng
 */

global $n2;
?>
<span class="dashicons dashicons-admin-tools" onclick="document.getElementById(`n2-view-post-<?php echo esc_attr( $args['id'] ); ?>`).showPopover()"></span>
<div popover id="n2-view-post-<?php echo esc_attr( $args['id'] ); ?>" style="overflow-y: scroll;height: 80vh;width: 80vw;text-align: left;">
<div>
	<ul>
		<li>事業者変更</li>
		<li>ゴミ箱</li>
		<li>複製</li>
		<li>復元</li>
	</ul>
</div>
<table class="widefat striped">
	<?php foreach ( $args as $name => $value ) : ?>
	<tr>
		<th style="text-align: left;"><?php echo $name; ?></th>
		<td style="text-align: left;"><?php echo $value; ?></td>
	</tr>
	<?php endforeach; ?>
</table>
</div>
