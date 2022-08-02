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
			foreach( get_posts() as $post ) :setup_postdata( $post );
			?>
			<li>
			<a href="<?php the_permalink(); ?>">
				<span class="product-list-date"><?php the_date( 'y/m/d' ); ?></span>
				<span class="product-list-title"><?php the_title(); ?></span>
				<span class="product-list-code"><?php echo get_post_meta( get_the_ID(), '返礼品コード', true ) ?></span>
				<span class="product-list-auther"><?php echo get_the_author_meta( 'display_name', get_post_field( 'post_author', get_the_ID() ) ); ?></span>
				<span class="product-list-price"><?php echo get_post_meta( get_the_ID(), '寄附金額', true ); ?></span>
				<figure><img src="<?php echo get_post_meta( get_the_ID(), '画像1', true ); ?>" alt=""></figure>
			</a>
			</li>
			<?php endforeach; wp_reset_postdata();?>
		</ul>
	</article>
<?php get_footer(); ?>