<?php
if ( have_posts() ) :
	the_post();
	global $post;
	$post_data = N2_Functions::get_all_meta( $post );

	$url_parse      = explode( '/', get_option( 'home' ) );
	$town_name      = end( $url_parse ); // urlから自治体を取得
	$n2_file_header = yaml_parse_file( get_theme_file_path() . '/config/n2-file-header.yml' );
	$n2_towncode    = yaml_parse_file( get_theme_file_path() . '/config/n2-towncode.yml' );
	$img_dir        = str_replace( 'n2-towncode', $n2_towncode[ $town_name ]['楽天'], $n2_file_header['rakuten']['img_dir'] );
	// DB登録用のキー
	$scraping_meta_key = 'スクレイピング';
	if ( ! isset( $post_data[ $scraping_meta_key ]['date'] )  ) {
		$scraping_params = array(
			'product_id'    => $post_data['返礼品コード'],
			'town_name'     => $town_name,
			'post_id'       => get_the_ID(),
			'post_meta_key' => $scraping_meta_key,
			// 'tashiro' => true,
		);
		$post_data[ $scraping_meta_key ] = apply_filters(
			'n2_portal_scraper', // ポータル田代
			$post_data[ $scraping_meta_key ] ?? array(),
			$scraping_params,
		);
	}

	// 寄付金額の表示を楽天に変えておく
	$product_amount = $post_data[ $scraping_meta_key ]['params']['楽天']['寄付額'] ?? $post_data['寄附金額'];
	// 商品画像
	$product_imgs = $post_data[ $scraping_meta_key ]['params']['楽天']['imgs']
		?? $post_data[ $scraping_meta_key ]['params']['チョイス']['imgs']
		?? $post_data['商品画像']
		?? array();
	// 事業者確認フラグ用　------------------------------------------------------------------------
	$check_param   = get_post_meta( get_the_ID(), '事業者確認', true );
	$checked_value = empty( $check_param ) ? '確認未' : $check_param[0];
	// ---------

	// 2022-11-29 コメントアウト taiki
	// $host       = $_SERVER['HTTP_HOST'];
	// $return_url = ! empty( $_SERVER['HTTP_REFERER'] ) && ( strpos( $_SERVER['HTTP_REFERER'], $host ) !== false ) ? $_SERVER['HTTP_REFERER'] : home_url();
	?>
	<main class="wrapper">
		<input type="hidden" id="product_id" value="<?php echo $post_data['返礼品コード']; ?>">

		<input type="hidden" id="scraping_key" value="<?php echo $scraping_meta_key ?>">
		<a class="return-link" href="<?php echo home_url(); ?>">
			<div class="return-btn">
				トップへ戻る
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
		<?php // N2_Functions::get_template_part_with_args( 'template/product-page/product-info', '', $post_data ); ?>
		<?php // var_dump( $post_data[ $scraping_meta_key ]['params'][0]['params'] ); ?>
		<!-- ポータル比較 -->
		<?php N2_Functions::get_template_part_with_args( 'template/product-page/product-comparison', '', $post_data[ $scraping_meta_key ]['params'] ?? '' ); ?>

		<!-- ポータルサイト一覧 -->
		<?php N2_Functions::get_template_part_with_args( 'template/product-page/portal-links', '', $post_data[ $scraping_meta_key ]['params'] ?? '' ); ?>
		<!-- 関連リンク -->
		<!-- 未実装 -->
		<?php $related_links = array(); ?>
		<?php N2_Functions::get_template_part_with_args( 'template/product-page/related-links', '', $related_links ); ?>
		
		<aside class="sub">
			<div class="sticky">
			<?php # if ( ! empty( $_GET['look'] ) ) : ?>
				<!-- <div class='n2-jigyousya-radiobox card p-2 bg-light'>
					<div class="form-check text-danger text-center">
						<input type="radio" class="form-check-input no-check" name="jigyousya-check-<?php echo the_ID(); ?>" id="no-check-<?php echo the_ID(); ?>" value='確認未' <?php echo checked( $checked_value, '確認未', false ); ?>>
						<label for="no-check-<?php echo the_ID(); ?>" class="form-check-label">
						確認未
					</label>
					</div>
					<div class="form-check text-warning text-center">
						<input type="radio" class="form-check-input want-fix" name="jigyousya-check-<?php echo the_ID(); ?>" id="want-fix-<?php echo the_ID(); ?>" value='修正希望' <?php echo checked( $checked_value, '修正希望', false ); ?>>
						<label for="want-fix-<?php echo the_ID(); ?>" class="form-check-label">
							修正してほしい
						</label>
					</div>
					<div class="form-check text-success text-center">
						<input type="radio" class="form-check-input no-fix" name="jigyousya-check-<?php echo the_ID(); ?>" id="no-fix-<?php echo the_ID(); ?>" value='確認済'<?php echo checked( $checked_value, '確認済', false ); ?>>
						<label for="no-fix-<?php echo the_ID(); ?>" class="form-check-label">
							修正しなくていい
						</label>
					</div>
				</div> -->
			<?php # endif; ?>
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
