<?php
/**
 * error_log
 *
 * @package neo-neng
 */

global $n2;

$rakuten_sftp = new N2_Rakuten_SFTP();
if ( ! $rakuten_sftp->sftp_connect() ) {
	echo '接続エラー';
	die;
}
$sftp = $rakuten_sftp->sftp;

$logs = $sftp->dirlist( 'ritem/logs' );
$logs = array_reverse( $logs );
 if ( empty( $logs ) ) {
	 echo 'エラーログはありません。';
	 exit;
 }
 ?>
 <h3>エラーログ</h3>
 <table class="widefat striped" style="margin: 2em 0;">
 <?php
 foreach ( $logs as $log ) :
	 $contents = $sftp->get_contents( "ritem/logs/{$log['name']}" );
	 $contents = htmlspecialchars( mb_convert_encoding( $contents, 'utf-8', 'sjis' ) );
	 ?>
	 <tr>
		 <td><?php echo "{$log['year']} {$log['month']} {$log['day']}"; ?></td>
		 <td>
			 <button type="button" popovertarget="<?php echo $log['name']; ?>" class="button button-primary">エラー内容を見る</button>
			 <div popover="auto" id="<?php echo $log['name']; ?>" style="width: 80%; max-height: 80%; overflow-y: scroll;"><pre><?php echo $contents; ?></pre></div>
		 </td>
		 <td><?php echo "ritem/logs/{$log['name']}"; ?></td>
	 </tr>
 <?php endforeach; ?>
 </table>
 