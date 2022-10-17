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

				$url_parse      = explode( '/', get_option( 'home' ) ); // items_detailsより拝借(rakutenに画像がある場合はそちらを使用)
				$town_code      = end( $url_parse );
				$n2_fields      = yaml_parse_file( get_theme_file_path() . '/config/n2-fields.yml' );
				$n2_file_header = yaml_parse_file( get_theme_file_path() . '/config/n2-file-header.yml' );
				$n2_towncode    = yaml_parse_file( get_theme_file_path() . '/config/n2-towncode.yml' );
				$img_dir        = str_replace( 'n2-towncode', $n2_towncode[ $town_code ]['楽天'], $n2_file_header['rakuten']['img_dir'] );
				$portals        = array( '楽天', 'チョイス' );
				
				$item_num_low = mb_strtolower( get_post_meta( get_the_ID(), '返礼品コード', true ) );
				preg_match( '/^[a-z]{2,3}/', $item_num_low, $m );// 事業者コード
							if ( ! preg_match( '/ne\.jp/', $img_dir ) ) {
								$img_dir .= "/{$m[0]}";// キャビネットの場合事業者コード追加
							}
				$check_img_urls    = function () use ( $item_num_low, $img_dir ) {
					$arr = array();
					for ( $i = 0;$i < 15; $i++ ) {
						if ( 0 === $i ) {
							$img_url = "{$img_dir}/{$item_num_low}.jpg";
						} else {
							$img_url = "{$img_dir}/{$item_num_low}-{$i}.jpg";
						}
						$response = wp_remote_get( $img_url );
						if ( ! is_wp_error( $response ) && 200 === $response['response']['code'] ) {
							array_push( $arr, $img_url );
						}
					}
					return $arr;
				};
				$meta_pic_arr = $check_img_urls() ?: get_post_meta( get_the_ID(), '商品画像', true );

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
			<span class="product-list-title"><?php echo '【' . get_post_meta( get_the_ID(), '返礼品コード', true ) . '】'; ?><?php the_title(); ?></span>
			<span class="product-list-price"><?php echo number_format(get_post_meta( get_the_ID(), '寄附金額', true )) . ' 円'; ?></span>
			<span class="product-list-auther"><?php echo get_the_author_meta( 'display_name', get_post_field( 'post_author', get_the_ID() ) ); ?></span>
			<?php /* if("" !== $meta_portal_section): ?>
			<span class="product-list-code"><?php echo $meta_portal_section; ?></span>
			<?php endif; */ ?>
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