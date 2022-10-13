<?php
/**
 * template/front-list.php
 *
 * @package neoneng
 */

?>
<section class="product-list-wrap">
	<?php 
		$args = array(
			'paged' => $paged,
			'posts_per_page' => 20,
			'post_status' => 'any',

		);
		$wp_query = new WP_Query( $args );
	?>
	<?php the_posts_pagination(
		array(
			// 'mid_size'      => 2, // 現在ページの左右に表示するページ番号の数
			'prev_next'     => true, // 「前へ」「次へ」のリンクを表示する場合はtrue
			'prev_text'     => __( '前へ'), // 「前へ」リンクのテキスト
			'next_text'     => __( '次へ'), // 「次へ」リンクのテキスト
			'type'          => 'list', // 戻り値の指定 (plain/list)
		)
	); ?>
	<ul class="product-list">
		<?php
		if ( $wp_query->have_posts() ) {
			while ( $wp_query->have_posts() ) {
				$wp_query->the_post();
				$meta_pic_arr = get_post_meta( get_the_ID(), '商品画像', true );
				$post_status = get_post_status();
				// var_dump(get_post_meta(get_the_ID(), '出品禁止ポータル', true));
				$meta_portals = get_post_meta( get_the_ID(), '出品禁止ポータル', true );
				$meta_portal_section = '';
				if(!empty($meta_portals)){
					foreach($meta_portals as $key => $meta_portal){
						$meta_portal_section .= $meta_portal;
					}	
				}
		?>
		<li class="<?php echo $post_status; ?>">
		<a href="<?php the_permalink(); ?>">
			<figure><img src="<?php echo $meta_pic_arr[0]; ?>" alt=""></figure>
			<?php /*<span class="product-list-date"><?php the_date( 'y/m/d' ); ?></span>*/ ?>
			<span class="product-list-item">
			<span class="product-list-title"><?php the_title(); ?></span>
			<span class="product-list-price"><?php echo number_format(get_post_meta( get_the_ID(), '寄附金額', true )) . ' 円'; ?></span>
			<span class="product-list-auther"><?php echo get_the_author_meta( 'display_name', get_post_field( 'post_author', get_the_ID() ) ); ?></span>
			<span class="product-list-code"><?php echo get_post_meta( get_the_ID(), '返礼品コード', true ); ?></span>
			<?php if("" !== $meta_portal_section): ?>
			<span class="product-list-code"><?php echo $meta_portal_section; ?></span>
			<?php endif; ?>
			</span><!--product-list-item-->
		</a>
		</li>
		<?php
			}
		}
		?>
		<?php wp_reset_postdata(); ?>

	</ul>
	<?php the_posts_pagination(
		array(
			// 'mid_size'      => 2, // 現在ページの左右に表示するページ番号の数
			'prev_next'     => true, // 「前へ」「次へ」のリンクを表示する場合はtrue
			'prev_text'     => __( '前へ'), // 「前へ」リンクのテキスト
			'next_text'     => __( '次へ'), // 「次へ」リンクのテキスト
			'type'          => 'list', // 戻り値の指定 (plain/list)
		)
	); ?>

</section>