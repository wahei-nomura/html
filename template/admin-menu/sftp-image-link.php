<?php
/**
 * sftp-image-link
 *
 * @package neoneng
 */

if ( empty( $args['post_id'] ) ) {
	echo 'アップロードのログはありません。';
	return;
}

$post      = get_post( $args['post_id'] );
$revisions = wp_get_post_revisions( $args['post_id'] );

echo '<pre>';
var_dump( $post );
echo '</pre><br>';

echo '<pre>';
var_dump( $revisions );
echo '</pre><br>';
