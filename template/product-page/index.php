<?php
global $post;
$post_data      = N2_Functions::get_all_meta( $post );
$url_parse      = explode( '/', get_option( 'home' ) );
$town_name      = end( $url_parse );
$n2_file_header = yaml_parse_file( get_theme_file_path() . '/config/n2-file-header.yml' );
$n2_towncode    = yaml_parse_file( get_theme_file_path() . '/config/n2-towncode.yml' );
$img_dir        = str_replace( 'n2-towncode', $n2_towncode[ $town_name ]['楽天'], $n2_file_header['rakuten']['img_dir'] );
$portals        = array( '楽天', 'チョイス' );


$product_code_l
 = mb_strtolower( $post_data['返礼品コード'] );
if ($product_code_l
){
	preg_match( '/^[a-z]{2,3}/', $product_code_l
	, $m );// 事業者コード
	if ( ! preg_match( '/ne\.jp/', $img_dir ) ) {
		$img_dir .= "/{$m[0]}";// キャビネットの場合事業者コード追加
	}

}
// 画像の存在判定
$check_img_urls    = function () use ( $product_code_l
, $img_dir ) {
	// 初期化
	$arr = array();
	for ( $i = 0;$i < 15; $i++ ) {
		if ( 0 === $i ) {
			$img_url = "{$img_dir}/{$product_code_l
			}.jpg";
		} else {
			$img_url = "{$img_dir}/{$product_code_l
			}-{$i}.jpg";
		}
		$response = wp_remote_get( $img_url );
		if ( ! is_wp_error( $response ) && 200 === $response['response']['code'] ) {
			array_push( $arr, $img_url );
		}
	}
	return $arr;
};
$post_data['商品画像'] = $check_img_urls() ?: $post_data['商品画像'];
// var_dump( $post_data );
$tashiro = apply_filters( 'wp_ajax_SS_Portal_Scraper', array(), $town_name, $post_data['返礼品コード'] );
if ( have_posts() ) :
	while ( have_posts() ) :
		the_post();
?>
<main class="wrapper">
	<!-- 商品画像 -->
	<?php N2_Functions::get_template_part_with_args( 'template/product-page/product-imgs', '', $post_data['商品画像'] ) ?>
	<h1 class='title'><?php the_title(); ?></h1>
	<section class="worker">
		<h2>提供事業者</h2>
		<?php if ( get_the_author_meta( 'user_url' ) ) : ?>
			<a href="<?php the_author_meta( 'user_url' ); ?>">
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
	<?php N2_Functions::get_template_part_with_args( 'template/product-page/product-info', '', $post_data ) ?>
	<!-- ポータル比較 -->
	<?php N2_Functions::get_template_part_with_args( 'template/product-page/product-scraping', '', $tashiro ) ?>

	<!-- ポータルサイト一覧 -->
	<?php N2_Functions::get_template_part_with_args( 'template/product-page/portal-links', '', $tashiro ) ?>
	
	<!-- 関連リンク -->
	<?php $related_links = array() ?>
	<?php N2_Functions::get_template_part_with_args( 'template/product-page/related-links', '', $related_links ) ?>
	
	<aside class="sub">
		<div class="sticky">
		<?php if ( ! empty( $_GET['look'] ) ) : ?>
			<button
				type='button'
				class='ok-btn'
				value='<?php the_ID(); ?>'
				<?php echo '' !== get_post_meta( get_the_ID(), '事業者確認', true ) ? 'disabled' : ''; ?>
			>
			確認OK
			</button>
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
endwhile;
endif;
?>