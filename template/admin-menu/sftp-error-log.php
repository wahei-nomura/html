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
<h3>エラーログ</h3>
<table class="widefat striped" style="margin: 2em 0;">
<?php
foreach ( $args['logs'] as $log ) :
 ?>
 <tr>
	 <td><?php echo $log['time']; ?></td>
	 <td>
		 <button type="button" popovertarget="<?php echo $log['name']; ?>" class="button button-primary">エラー内容を見る</button>
		 <div popover="auto" id="<?php echo $log['name']; ?>" style="width: 80%; max-height: 80%; overflow-y: scroll;"><pre><?php echo $log['contents']; ?></pre></div>
	 </td>
	 <td><?php echo "{$args['dir']}/{$log['name']}"; ?></td>
 </tr>
<?php endforeach; ?>
</table>
