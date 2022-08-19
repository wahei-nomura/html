<?php
/**
 * front-page.php
 *
 * @package neoneng
 */

?>
<?php get_header(); ?>
	<article class="product-wrap">
		<section class="product-sidebar">
			<p>【探す】</p>
			<input type="text" placeholder="キーワードを入力">
			<p>金額</p>
			<select name="price" id="">
				<option value="1">0〜5,000</option>
				<option value="1">5,001〜10,000</option>
				<option value="1">10,001〜50,000</option>
				<option value="1">50,001〜100,000</option>
				<option value="1">100,001〜</option>
			</select>
			<p>登録日</p>
			<a href="">新しい順</a> <a href="">古い順</a>
			<p>事業者HP</p>
			<a href="">あり</a> <a href="">なし</a>
			<p>ポータルサイト</p>
			<label for="rakuten"><input type="checkbox" name="portalsite[]" id="rakuten" class="portalsite" checked="checked">楽天</label>
			<label for="choice"><input type="checkbox" name="portalsite[]" id="choice" class="portalsite" checked="checked">チョイス</label>
			<label for="furunavi"><input type="checkbox" name="portalsite[]" id="furunavi" class="portalsite" checked="checked">ふるなび</label>
		</section>
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
				?>
				<li class="<?php echo $post_status; ?>">
				<a href="<?php the_permalink(); ?>">
					<figure><img src="<?php echo $meta_pic_arr[0]; ?>" alt=""></figure>
					<?php /*<span class="product-list-date"><?php the_date( 'y/m/d' ); ?></span>*/ ?>
					<span class="product-list-title"><?php the_title(); ?></span>
					<span class="product-list-price"><?php echo get_post_meta( get_the_ID(), '寄附金額', true ); ?></span>
					<span class="product-list-auther"><?php echo get_the_author_meta( 'display_name', get_post_field( 'post_author', get_the_ID() ) ); ?></span>
					<span class="product-list-code"><?php echo get_post_meta( get_the_ID(), '返礼品コード', true ); ?></span>
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

	</article>
<?php get_footer(); ?>