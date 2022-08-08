<?php
/**
 * front-page.php
 *
 * @package neoneng
 */

?>
<?php get_header(); ?>
	<article class="product-list-wrap">
		<ul class="product-list">
			<li class="product-list-header">
				<span class="product-list-header-item">登録日</span>
				<span class="product-list-header-item long">タイトル</span>
				<span class="product-list-header-item">返礼品コード</span>
				<span class="product-list-header-item">事業者名</span>
				<span class="product-list-header-item">寄附金額</span>
				<span class="product-list-header-item">画像</span>
			</li>
			<?php
			$args = array(
				'post_status' => 'any',
			);
			$wp_query = new WP_Query( $args );
			if ( $wp_query->have_posts() ) {
				while ( $wp_query->have_posts() ) {
					$wp_query->the_post();
					$meta_pic_arr = get_post_meta( get_the_ID(), '画像', true );
					$post_status = get_post_status();
			?>
			<li class="<?php echo $post_status; ?>">
			<a href="<?php the_permalink(); ?>">
				<span class="product-list-date"><?php the_date( 'y/m/d' ); ?></span>
				<span class="product-list-title"><?php the_title(); ?></span>
				<span class="product-list-code"><?php echo get_post_meta( get_the_ID(), '返礼品コード', true ); ?></span>
				<span class="product-list-auther"><?php echo get_the_author_meta( 'display_name', get_post_field( 'post_author', get_the_ID() ) ); ?></span>
				<span class="product-list-price"><?php echo get_post_meta( get_the_ID(), '寄附金額', true ); ?></span>
				<figure><img src="<?php echo $meta_pic_arr[0]; ?>" alt=""></figure>
			</a>
			</li>
			<?php
				}
			}
			?>
			<?php wp_reset_postdata(); ?>

		</ul>
	</article>
<?php get_footer(); ?>