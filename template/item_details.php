<?php
global $post;
$post_data      = N2_Functions::get_all_meta( $post );
$url_parse      = explode( '/', get_option( 'home' ) );
$town_code      = end( $url_parse );
$n2_fields      = yaml_parse_file( get_theme_file_path() . '/config/n2-fields.yml' );
$n2_file_header = yaml_parse_file( get_theme_file_path() . '/config/n2-file-header.yml' );
$n2_towncode    = yaml_parse_file( get_theme_file_path() . '/config/n2-towncode.yml' );
$img_dir        = str_replace( 'n2-towncode', $n2_towncode[ $town_code ]['楽天'], $n2_file_header['rakuten']['img_dir'] );
$portals        = array( '楽天', 'チョイス' );

// プラグインn2-developのn2_setpost_show_customfields呼び出し
$fields       = apply_filters( 'n2_setpost_show_customfields', $n2_fields, 'default' );
$item_num_low = mb_strtolower( $post_data['返礼品コード'] );
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
$post_data['商品画像'] = $check_img_urls() ?: $post_data['商品画像'];
// var_dump($post_data);
$tashiro = apply_filters( 'wp_ajax_SS_Portal_Scraper', $town_code, $post_data['返礼品コード'] );

// var_dump( $tashiro);
?>

<?php get_header(); ?>
<body <?php body_class(); ?>>


<?php
if ( have_posts() ) :
	while ( have_posts() ) :
		the_post();
?>
				<?php if ( 'publish' !== get_post_status() ) : ?>
		<!-- プログレストラッカー -->
		<?php get_template_part( 'template/progress' ); ?>
					<?php endif; ?>
		<main class="wrapper">
			<section class="item-img">
				<div class="img-box">
					<img class='main-img' src="<?php echo $post_data['商品画像'][0]; ?>" width='100%'>
				</div>
				<div class="wrapper-img-list">
					<ul class='sub-imgs'>
						<?php for ( $i = 0; $i < 2; $i++ ) : ?>
						<?php foreach ( $post_data['商品画像'] as $img_url ) : ?>
							<img class='sub-img' src="<?php echo $img_url; ?>" width='100%' height="100%">
						<?php endforeach; ?>
						<?php endfor; ?>
					</ul>
				</div>
			</section>
			<h1 class='title'><?php the_title(); ?></h1>
			<section class="worker">
				<h2>提供事業者</h2>
				<a href="#">(株)優良企業<span class="material-symbols-outlined">open_in_new</span></a>
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
			<section class="item-detail">
				<h2 class="mordal-btn">商品詳細ページ</h2>
				<div>
					<h3 class='detail'>商品詳細</h3>
					<table class="detail tabList">
						<thead>
							<tr><th width="35%">項目</th><th width="65%">内容</th></tr>
						</thead>
						<tbody>
							<?php $item_detail = array( '価格', '内容量・規格等', 'アレルゲン', 'アレルゲン注釈', '賞味期限', '消費期限', '限定数量' ); ?>
							<?php foreach ( $item_detail as $key ) : ?>
							<?php
							if ( 'checkbox' === $fields[ $key ]['type'] || 'select' === $fields[ $key ]['type'] ) :
								$options = $fields[ $key ]['option'];
								// var_dump($options);
								// foreach ( $options as $option ) {
								// }
								$checked = '';
								if ( 'checkbox' === $fields[ $key ]['type'] ) {
									if ( ! empty( $post_data[ $key ] ) ) {
										$checked_arr = array_filter(
											$fields[ $key ]['option'],
											fn( $value) => in_array( array_search( $value, $fields[ $key ]['option'] ), $post_data[ $key ] )
										);
										$checked    .= implode( ',', $checked_arr );
									}
								} else {
									$checked = 'なし';
								};
								?>
							<tr>
								<th><?php echo $key; ?></th>
								<td><?php echo 'select' === $fields[ $key ]['type'] ? $options[ $fields [ $key ] ] : $checked; ?></td>
							</tr>
							<?php else : ?>
								<tr>
								<th><?php echo $key; ?></th>
								<td><?php echo ! empty( $post_data[ $key ] ) ? nl2br( $post_data[ $key ] ) : '入力無し'; ?></td>
							</tr>
						<?php endif; ?>
						<?PHP endforeach; ?>
						</tbody>
					</table>
					<h3 class="application">申込詳細</h3>
					<table class="application tabList">
						<thead>
							<tr><th width="35%">項目</th><th width="65%">内容</th></tr>
						</thead>
						<tbody>
							<?php $application = array( '申込期間', '配送期間', 'のし対応', '取り扱い方法1', '取り扱い方法2', '発送方法', '発送サイズ' ); ?>
							<?php foreach ( $application as $key ) : ?>
							<?php
							if ( 'checkbox' === $fields[ $key ]['type'] || 'select' === $fields[ $key ]['type'] ) :
								$new_options = $fields[ $key ]['option'];
								$cheked      = '';
								if ( 'checkbox' === $fields[ $key ]['type'] ) {
									if ( ! empty( $fields[ $key ] ) ) {
										foreach ( $fields[ $key ] as $chekedkey ) {
											$cheked .= implode( ',', $new_options[ $chekedkey ] );
										}
									}
								} else {
									$cheked = 'なし';
								};
								?>
							<tr>
								<th><?php echo $key; ?></th>
								<td><?php echo 'select' === $fields[ $key ]['type'] ? $new_options[ $post_data[ $key ] ] : $cheked; ?></td>
							</tr>
							<?php else : ?>
							<tr>
								<th><?php echo $key; ?></th>
								<td><?php echo ! empty( $post_data[ $key ] ) ? nl2br( $post_data[ $key ] ) : '入力無し'; ?></td>
							</tr>
							<?php endif; ?>
							<?PHP endforeach; ?>
						</tbody>
					</table>
				</div>
			</section>
			

			<section class='portal-scraper'>
				<h2 class="mordal-btn">ポータル比較</h2>
				<table border="1" style="display:none;" class="is-block">
					<thead>
						<tr>
							<th>-</th>
							<?php for ( $i = 0; $i < 3; ++$i ) : ?>
							<?php foreach ( $tashiro as $portal => $params ) : ?>
								<th><?php echo isset( $portal ) ? $portal : 'unknown'; ?></th>
							<?php endforeach; ?>
							<?php endfor; ?>
						</tr>
					</thead>
					<tbody>
						<?php $tashiro_th = array( '寄付額', '納期', '在庫' ); ?>
						<?php foreach ( $tashiro_th as $th ) : ?>
						<tr>
							<th><?php echo $th; ?></th>
							<?php for ( $i = 0; $i < 3; $i++ ) : ?>
							<?php foreach ( $tashiro as $portal => $params ) : ?>
								<?php if ( '寄付額' === $th ) : ?>
									<td class="price"><?php echo number_format( $params[ $th ] ); ?></td>
								<?php else : ?>
									<td><?php echo $params[ $th ]; ?></td>
								<?php endif; ?>
							<?php endforeach; ?>
							<?php endfor; ?>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</section>
			<section class="portal-links">
				<h2>各ポータルサイト</h2>
				<ul class="portal-link-list">
				<?php foreach ( $portals as $portal ) : ?>
					<?php if ( isset( $tashiro[ $portal ] ) ) : ?>
						<li class="portal-link">
							<a href="<?php echo $tashiro[ $portal ]['item_url']; ?>"><?php echo $portal; ?></a>
						</li>
					<?php endif; ?>
				<?php endforeach; ?>
				</ul>
			</section>
			<section class="related-links">
				<h2>関連リンク</h2>
			</section>
		</main>
		<aside class="sub">
			
		</aside>
		<div class="mordal" style="display: none;">
			<div class="mordal-box">
				<div class="close-btn"></div>
				<div class="mordal-wrapper"></div>
			</div>
		</div>


	<?php if ( 'publish' === get_post_status() ) : ?>
	<?php /* echo is_user_logged_in() ? get_template_part( 'template/progress' ) : '';*/ ?>
	<p>公開中の商品は違う感じの表示にする。</p>
</body>
		<?php
	endif;
endwhile;
endif;


get_footer();
