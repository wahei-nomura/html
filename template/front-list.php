<?php
/**
 * template/front-list.php
 *
 * @package neoneng
 */

?>
<section class="product-list-wrap">
	<?php
	the_posts_pagination(
		array(
			// 'mid_size'      => 2, // 現在ページの左右に表示するページ番号の数
			'prev_next' => true, // 「前へ」「次へ」のリンクを表示する場合はtrue
			'prev_text' => __( '前へ' ), // 「前へ」リンクのテキスト
			'next_text' => __( '次へ' ), // 「次へ」リンクのテキスト
			'type'      => 'list', // 戻り値の指定 (plain/list)
		)
	);
	?>
	<ul class="product-list">
		<?php
		if ( have_posts() ) {
			$url_parse      = explode( '/', get_option( 'home' ) ); // items_detailsより拝借(rakutenに画像がある場合はそちらを使用)
			$town_code      = end( $url_parse );
			$n2_fields      = yaml_parse_file( get_theme_file_path() . '/config/n2-fields.yml' );
			$n2_file_header = yaml_parse_file( get_theme_file_path() . '/config/n2-file-header.yml' );
			$n2_towncode    = yaml_parse_file( get_theme_file_path() . '/config/n2-towncode.yml' );
			$img_dir        = str_replace( 'n2-towncode', $n2_towncode[ $town_code ]['楽天'], $n2_file_header['rakuten']['img_dir'] );
			$portals        = array( '楽天', 'チョイス' );
			while ( have_posts() ) {
				the_post();
				$new_rakuten_pic = '';
				$new_meta_pic = '';

				$item_num_low = mb_strtolower( get_post_meta( get_the_ID(), '返礼品コード', true ) );
				preg_match( '/...(?=[0-9])/', $item_num_low, $item_code );
				if ( $item_num_low != '' && ! empty( $item_code ) ) {
					$new_rakuten_pic = $img_dir . '/' . $item_code[0] . '/' . $item_num_low . '.jpg';
				}
				if( ! empty( $meta_pic_arr ) ){
					$new_meta_pic = $meta_pic_arr[0];
				}
				$post_status = get_post_status();
				// var_dump(get_post_meta(get_the_ID(), '出品禁止ポータル', true));
				$meta_portals        = get_post_meta( get_the_ID(), '出品禁止ポータル', true );
				$meta_portal_section = '';
				if ( ! empty( $meta_portals ) ) {
					foreach ( $meta_portals as $key => $meta_portal ) {
						$meta_portal_section .= $meta_portal;
					}
				}
				?>
		<li class="<?php echo $post_status; ?>">
		<a href="<?php the_permalink(); ?>">
			<div class="product-img-wrap">
				<div class="product-img-box" style="background-image:url( <?php echo $new_rakuten_pic; ?>  ), url(<?php echo $new_meta_pic; ?>); background-size:cover;"><?php print $new_meta_pic == '' && $new_rakuten_pic == '' ? '<span class="product-img-section">No Image</span>' : ''; ?></div>
				<span class="product-img-section">No Image</span>
			</div>
			<span class="product-list-item">
			<span class="product-list-title"><?php echo '【' . get_post_meta( get_the_ID(), '返礼品コード', true ) . '】'; ?><?php the_title(); ?></span>
				<?php
				$meta_price = get_post_meta( get_the_ID(), '寄附金額', true );
				?>
			<span class="product-list-price"><?php print is_numeric( $meta_price ) ? number_format( $meta_price ) . ' 円' : ''; ?></span>
			<span class="product-list-auther"><?php echo get_the_author_meta( 'display_name', get_post_field( 'post_author', get_the_ID() ) ); ?></span>
				<?php
				/*
				if("" !== $meta_portal_section): ?>
				<span class="product-list-code"><?php echo $meta_portal_section; ?></span>
				<?php endif; */
				?>
			</span><!--product-list-item-->
		</a>
				<?php if ( ! empty( $_GET['look'] ) && ! empty( $_GET['author'] ) ) : ?>
			<button
				type='button'
				class='ok-btn'
				value='<?php the_ID(); ?>'
					<?php echo '' !== get_post_meta( get_the_ID(), '事業者確認', true ) ? 'disabled' : ''; ?>
			>
			確認OK
			</button>
		<?php endif; ?>
		</li>
				<?php
			}
		}
		wp_reset_postdata();
		?>

	</ul>
	<?php
	the_posts_pagination(
		array(
			// 'mid_size'      => 2, // 現在ページの左右に表示するページ番号の数
			'prev_next' => true, // 「前へ」「次へ」のリンクを表示する場合はtrue
			'prev_text' => __( '前へ' ), // 「前へ」リンクのテキスト
			'next_text' => __( '次へ' ), // 「次へ」リンクのテキスト
			'type'      => 'list', // 戻り値の指定 (plain/list)
		)
	);
	?>

</section>
