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
	if ( isset( $post_data['返礼品コード'] ) ) {
		$product_code = mb_strtolower( $post_data['返礼品コード'] );
		preg_match(
			'/^[a-z]{2,3}/',
			$product_code,
			$m
		);// 事業者コード
		if ( ! preg_match( '/ne\.jp/', $img_dir ) && isset( $m[0] ) ) {
			$img_dir .= "/{$m[0]}";// キャビネットの場合事業者コード追加
		}
		$scraping_meta_key = 'スクレイピング';
		
		// 画像の存在判定 CSV出力機能でも使用するので統一したい
		$check_img_urls = function () use ( $product_code, $img_dir ) {
			// 初期化
			$arr = array();
			for ( $i = 0;$i < 15; $i++ ) {
				if ( 0 === $i ) {
					$img_url = "{$img_dir}/{$product_code
					}.jpg";
				} else {
					$img_url = "{$img_dir}/{$product_code
					}-{$i}.jpg";
				}
				$response = wp_remote_get( $img_url );
				if ( ! is_wp_error( $response ) && 200 === $response['response']['code'] ) {
					array_push( $arr, $img_url );
				} else {
					break;
				}
			}
			return $arr;
		};
		// 田代する　------------------------------------------------------------------------
		if ( ! isset( $post_data[$scraping_meta_key] ) ){
			// ポータル田代
			$post_data[$scraping_meta_key] = apply_filters( 'wp_ajax_n2_tashiro\N2_Portal_Scraper', array(), $town_name, $post_data['返礼品コード'] );
			// 画像リンク田代
			$post_data[$scraping_meta_key]['商品画像'] = $check_img_urls() 
				?: (isset($post_data['商品画像']) 
					? $post_data['商品画像']
					: array() );
			// 田代の結果をDBへ保存
			update_post_meta(get_the_ID(),$scraping_meta_key,$post_data[$scraping_meta_key] );
		}
		// 田代済みの画像へ変更
		$post_data['商品画像'] = $post_data[$scraping_meta_key]['商品画像'];
		unset($post_data[$scraping_meta_key]['商品画像']);
	}

	// 一覧へ戻る用 ------------------------------------------------------------------------------
	$return_url = explode( '?', "https://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}" )[0];
	$get_param  = $_GET;
	unset( $get_param['p'] );
	$return_url .= '?' . http_build_query( $get_param );
	// 事業者確認フラグ用　------------------------------------------------------------------------
	$check_param = get_post_meta( get_the_ID(), '事業者確認', true );
	$is_confirmed   = '' === $check_param || '確認未' === $check_param[0] ? false : true;
	$confirmed_text = array( '確認未','確認済み' );
	$confirmed_class = array( '', ' confirmed' );
	// ------------------------------------------------------------------------------------------
	?>
	<main class="wrapper">
		<a class="return-link" href="<?php echo $return_url; ?>">
			<div class="return-btn">
				一覧へ戻る
			</div>
		</a>
		<!-- 商品画像 -->
		<?php get_template_part( 'template/product-page/product-imgs', '', $post_data['商品画像'] ); ?>
		<h1 class='title'><?php the_title(); ?>【<?php echo $post_data['返礼品コード']; ?>】</h1>
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
		<section class='donation-amount'>
			<h2>寄附金額</h2>
			<div class="price"><?php echo number_format( $post_data['寄附金額'] ); ?></div>
		</section>
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
		<?php N2_Functions::get_template_part_with_args( 'template/product-page/product-scraping', '', $post_data[$scraping_meta_key] ); ?>

		<!-- ポータルサイト一覧 -->
		<?php N2_Functions::get_template_part_with_args( 'template/product-page/portal-links', '', $post_data[$scraping_meta_key] ); ?>
		
		<!-- 関連リンク -->
		<!-- 未実装 -->
		<?php $related_links = array(); ?>
		<?php N2_Functions::get_template_part_with_args( 'template/product-page/related-links', '', $related_links ); ?>
		
		<aside class="sub">
			<div class="sticky">
			<?php if ( ! empty( $_GET['look'] ) ) : ?>
				<button
					type='button'
					class='ok-btn btn-outline-info btn <?php echo $confirmed_class[ $is_confirmed ]; ?>'
					value='<?php the_ID(); ?>'
				><?php echo $confirmed_text[ $is_confirmed ]; ?></button>
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