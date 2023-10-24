<?php
/**
 * sftp-image-link
 *
 * @package neoneng
 */

$query = new WP_Query(
	array(
		'post_type'      => $args,
		'posts_per_page' => -1,
	)
);

if ( ! $query->have_posts() ) {
	echo 'アップロードのログはありません。';
	wp_reset_postdata();
	return;
}

while ( $query->have_posts() ) {
	$query->the_post();

	// 投稿IDを取得します
	$post_id = get_the_ID();
	echo '<pre>';
	var_dump( $post_id );
	echo '</pre><br>';


	// postmetaデータを取得します
	$meta_data = get_post( $post_id );
	echo '<pre>';
	var_dump( $meta_data );
	echo '</pre><br>';

	// 必要に応じてpostmetaデータを処理します
	// ...
}
wp_reset_postdata(); ?>

<div id="ss-sftp"></div>