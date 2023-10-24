<?php
/**
 * error_log
 *
 * @package neo-neng
 */

global $n2;

if ( ! $args['connect'] ) {
	echo '接続エラー';
	die;
}

if ( empty( $args['logs'] ) ) {
	echo 'エラーログはありません。';
	exit;
}
?>
<table class="widefat striped" style="margin: 2em 0;">
<?php
foreach ( $args['logs'] as $log ) :
 ?>
 <tr>
	 <td><?php echo esc_html( $log['time'] ); ?></td>
	 <td>
		 <button type="button" popovertarget="<?php echo esc_attr( $log['name'] ); ?>" class="button button-primary">エラー内容を見る</button>
		 <div popover="auto" id="<?php echo esc_attr( $log['name'] ); ?>" style="width: 80%; max-height: 80%; overflow-y: scroll;"><pre><?php echo esc_html( $log['contents'] ); ?></pre></div>
	 </td>
	 <td><?php echo esc_html( "{$args['dir']}/{$log['name']}" ); ?></td>
 </tr>
<?php endforeach; ?>
</table>
