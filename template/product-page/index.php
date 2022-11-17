<?php
if ( have_posts() ) :
	the_post();
	global $post;
	$post_data      = N2_Functions::get_all_meta( $post );
	
	$url_parse      = explode( '/', get_option( 'home' ) );
	$town_name      = end( $url_parse ); //urlから自治体を取得
	$n2_file_header = yaml_parse_file( get_theme_file_path() . '/config/n2-file-header.yml' );
	$n2_towncode    = yaml_parse_file( get_theme_file_path() . '/config/n2-towncode.yml' );
	$img_dir        = str_replace( 'n2-towncode', $n2_towncode[ $town_name ]['楽天'], $n2_file_header['rakuten']['img_dir'] );
	// DB登録用のキー
	$scraping_meta_key = 'スクレイピング';
	$imgs_meta_key = '商品詳細画像';
	$product_imgs = $post_data[$imgs_meta_key] ?? array();

	// 寄付金額の表示を楽天に変えておく
	$product_amount = $post_data[$scraping_meta_key]['楽天']['寄付額'] ?? $post_data['寄附金額'];

	if ( isset( $post_data['返礼品コード'] ) ) {
		// 田代する　------------------------------------------------------------------------
		if ( ! isset( $post_data[$scraping_meta_key] ) ){
			// ポータル田代
			$post_data[$scraping_meta_key] = apply_filters( 'wp_ajax_n2_tashiro\N2_Portal_Scraper', array(), $town_name, $post_data['返礼品コード'] );
			// 画像リンク田代
			$product_imgs = apply_filters( 'wp_ajax_n2_tashiro\N2_Portal_Scraper_imgs', array(), $town_name, $post_data['返礼品コード'] )
				?: $post_data['商品画像'] ?? array();
			// 田代の結果をDBへ保存
			update_post_meta(get_the_ID(),$scraping_meta_key,$post_data[$scraping_meta_key] );
			update_post_meta(get_the_ID(),$imgs_meta_key,$product_imgs );
		}
	}

	// 事業者確認フラグ用　------------------------------------------------------------------------
	$check_param = get_post_meta( get_the_ID(), '事業者確認', true );
	$is_confirmed   = '' === $check_param || '確認未' === $check_param[0] ? false : true;
	$confirmed_text = array( '確認未','確認済み' );
	$confirmed_class = array( '', ' confirmed' );
	// ------------------------------------------------------------------------------------------

	$host       = $_SERVER['HTTP_HOST'];
	$return_url = ! empty( $_SERVER['HTTP_REFERER'] ) && ( strpos( $_SERVER['HTTP_REFERER'], $host ) !== false ) ? $_SERVER['HTTP_REFERER'] : home_url();
	?>
	<main class="wrapper">
		<input type="hidden" id="product_id" value="<?php echo $post_data['返礼品コード']; ?>">
		<input type="hidden" id="scraping_key" value="<?php echo $scraping_meta_key ?>">
		<input type="hidden" id="imgs_key" value="<?php echo $imgs_meta_key ?>">
		<a class="return-link" href="<?php echo $return_url; ?>">
			<div class="return-btn">
				戻る
			</div>
		</a>
		<!-- 商品画像 -->
		<?php get_template_part( 'template/product-page/product-imgs', '', $product_imgs ); ?>
		<h1 class='title'><?php the_title(); ?>【<?php echo $post_data['返礼品コード']; ?>】</h1>
		<!-- 事業者 -->
		<section class="worker">
			<h2>提供事業者</h2>
			<?php if ( get_the_author_meta( 'user_url' ) ) : ?>
				<a href="<?php the_author_meta( 'user_url' ); ?>" target="_blank">
					<?php the_author(); ?>
					<span class="material-symbols-outlined">open_in_new</span>
				</a>
			<?php else : ?>
				<?php the_author(); ?>
			<?php endif; ?>
		</section>
		<!-- 寄付額 -->
		<section class='donation-amount'>
			<h2>寄附金額</h2>
			<div class="price"><?php echo number_format( $post_data['寄附金額'] ); ?></div>
		</section>
		<!-- 商品説明文 -->
		<section class="description">
			<h2>説明文</h3>
			<div>
				<div>
					<?php echo str_replace( array( "\r\n", "\r" ), '<br>', $post_data['説明文'] ); ?>
				</div>
			</div>
		</section>
		
		
		<!-- 商品詳細 -->
		<?php # N2_Functions::get_template_part_with_args( 'template/product-page/product-info', '', $post_data ); ?>
		<!-- ポータル比較 -->
		<?php N2_Functions::get_template_part_with_args( 'template/product-page/product-comparison', '', $post_data[$scraping_meta_key] ); ?>

		<!-- ポータルサイト一覧 -->
		<?php N2_Functions::get_template_part_with_args( 'template/product-page/portal-links', '', $post_data[$scraping_meta_key] ); ?>
		
		<!-- 関連リンク -->
		<!-- 未実装 -->
		<?php $related_links = array(); ?>
		<?php N2_Functions::get_template_part_with_args( 'template/product-page/related-links', '', $related_links ); ?>
		
		<aside class="sub">
			<div class="sticky">
			<?php if ( ! empty( $_GET['look'] ) ) : ?>
				<input class="check-toggle" <?php echo checked( $is_confirmed, true ); ?> type="checkbox" data-toggle="toggle" data-on="確認済み" data-off="未確認" data-onstyle="success" data-offstyle="danger" value="<?php the_ID(); ?>">
			<?php endif; ?>
			</div>
		</aside>
	</main>
	<div class="mordal" style="display: none;">
		<div class="mordal-box">
			<div class="close-btn"></div>
			<div class="mordal-wrapper"></div>
		</div>
	</div>
	<?php
else :
	echo '投稿ページが見つかりませんでした。';

endif;
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap5-toggle@4.3.4/css/bootstrap5-toggle.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap5-toggle@4.3.4/js/bootstrap5-toggle.min.js"></script>
