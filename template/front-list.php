<?php
/**
 * template/front-list.php
 *
 * @package neoneng
 */

?>
<section class="product-list-wrap">

<?php
			$user_lists = get_users( 'role=jigyousya' );
			// var_dump($user_lists);
			$search_params = $_GET;
			$search_result = '';
foreach ( $search_params as $key => $sch_prm ) {
	if ( '' !== $sch_prm && 'paged' !== $key ) {
		if ( '' !== $search_result ) {
			$search_result .= ', ';
		}
		if ( 'jigyousya' === $key ) {
			$key_no          = array_search( $sch_prm, array_column( $user_lists, 'ID' ) );
			$search_result .= $user_lists[ $key_no ]->display_name;
		} elseif ( '返礼品コード' === $key ) {
			foreach ( $sch_prm as $code_key => $code_prm ) {
				$code_meta_data = get_post_meta( $code_prm );
				$search_result .= get_post_meta( $code_prm, '返礼品コード', true );
				if ( array_key_last( $sch_prm ) !== $code_key ) {
					$search_result .= '/';
				}
			}
		} elseif ( 'sortcode' === $key ) {
			if ( 'sortbycode' === $sch_prm ) {
				$search_result .= 'コード順に表示';
			} else {
				$search_result .= '登録順に表示';
			}
		} elseif ( 's' === $key ) {
			$search_result .= $sch_prm;
		}
	}
}
if ( '' !== $search_result ) {
	echo '<h2 class="search-result-header text-primary">絞り込み：' . $search_result . '</h2>';
}
?>

	<?php
	$item_amount = 0; // 表示されている返礼品数用
	// 2022-11-29 コメントアウト taiki
	// if ( ! empty( $_GET['look'] ) && ! empty( $_GET['jigyousya'] ) ) :
	?>
		<!-- <h2 class="display-12 p-2 border-bottom border-success border-3"><span class="text-success"><?php echo get_userdata( filter_input( INPUT_GET, 'jigyousya', FILTER_VALIDATE_INT ) )->display_name; ?></span> 様 専用確認ページ</h2>
		<p>お手数ですが、各商品をご確認されましたら<span class="text-danger">「確認OK」</span>ボタンを押してください。（ご不明点はスチームシップまでお問い合わせください。）</p>
		<div class="btn-group" id="n2-status-toggle-btns" role="group" aria-label="Basic checkbox toggle button group">
			<input type="checkbox" class="btn-check" id="no-check" autocomplete="off" checked>
			<label class="btn btn-outline-danger" for="no-check">確認未</label>

			<input type="checkbox" class="btn-check" id="want-fix" autocomplete="off" checked>
			<label class="btn btn-outline-warning" for="want-fix">修正してほしい</label>

			<input type="checkbox" class="btn-check" id="no-fix" autocomplete="off" checked>
			<label class="btn btn-outline-success" for="no-fix">修正しなくていい</label>
		</div> -->
	<?php // endif; ?>

	<?php get_template_part( 'template/pagination' ); ?>
	<ul class="product-list">
		<?php
		if ( have_posts() ) {
			$url_parse      = explode( '/', get_option( 'home' ) ); // items_detailsより拝借(rakutenに画像がある場合はそちらを使用)
			$town_code      = end( $url_parse );
			$n2_fields      = yaml_parse_file( get_theme_file_path() . '/config/n2-fields.yml' );
			$n2_file_header = yaml_parse_file( get_theme_file_path() . '/config/n2-file-header.yml' );
			$n2_towncode    = yaml_parse_file( get_theme_file_path() . '/config/n2-towncode.yml' );
			$img_dir        = str_replace( 'n2-towncode', $n2_towncode[ $town_code ]['楽天'], $n2_file_header['rakuten']['img_dir'] );
			$img_dir_ex     = str_replace( 'n2-towncode', $n2_towncode[ $town_code ]['楽天'], 'https://www.rakuten.ne.jp/gold/n2-towncode/img/item' );
			$portals        = array( '楽天', 'チョイス' );
			while ( have_posts() ) {
				the_post();
				$new_rakuten_pic    = '';
				$new_rakuten_pic_ex = ''; // 波佐見など一部特殊な画像urlがある時用
				$new_meta_pic       = '';

				$item_num_low = mb_strtolower( get_post_meta( get_the_ID(), '返礼品コード', true ) );
				preg_match( '/[a-z]+/', $item_num_low, $item_code );
				if ( '' !== $item_num_low && ! empty( $item_code ) !== $item_num_low ) {
					$new_rakuten_pic    = $img_dir . '/' . $item_code[0] . '/' . $item_num_low . '.jpg';
					$new_rakuten_pic_ex = $img_dir_ex . '/' . $item_num_low . '.jpg';
				}
				if ( ! empty( $meta_pic_arr ) ) {
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

				// 詳細ページで保存された楽天画像
				$get_post_meta        = get_post_meta( get_the_ID(), 'スクレイピング', true );
				$meta_imgs = ! empty( $get_post_meta ) ? array_column( $get_post_meta, 'imgs' ) : '';
				$get_meta_rakuten_pic = ! empty( $meta_imgs[0] ) ? $meta_imgs[0][0] : '';
				// 2022-11-29 コメントアウト taiki
				// 事業者確認フラグ用　------------------------------------------------------------------------
				// $check_param   = get_post_meta( get_the_ID(), '事業者確認', true );
				// $checked_value = empty( $check_param ) ? '確認未' : $check_param[0];
				// -----------------------------------------------------------------------------------------

				// $item_link = ! empty( $_GET['look'] ) && ! empty( $_GET['jigyousya'] ) ? get_the_permalink() . '&look=' . $_GET['look'] : get_the_permalink();
				$item_link = get_the_permalink();
				?>
		<li class="<?php echo $post_status; ?>">
		<a href="<?php echo $item_link; ?>">
			<div class="product-img-wrap">
				<div class="product-img-box" style="background-image:url( <?php echo $new_rakuten_pic_ex; ?>  ),url( <?php echo $new_rakuten_pic; ?>  ), url(<?php echo $new_meta_pic; ?>), url(<?php echo $get_meta_rakuten_pic; ?>); background-size:cover;"></div>
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
				<?php endif; 
				*/
				?>
			</span><!--product-list-item-->
		</a>

		<!-- 2022-11-29 コメントアウト taiki -->
				<?php // if ( ! empty( $_GET['look'] ) && ! empty( $_GET['jigyousya'] ) ) : ?>
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

				<?php // endif; ?>
		</li>
				<?php
				$item_amount++; // 表示されている返礼品数をカウント
			}
		}
		wp_reset_postdata();
		?>

	</ul>
	<?php get_template_part( 'template/pagination' ); ?>

	<?php
	if ( 0 === $item_amount ) { // 返礼品が一つもない

		$e_state = '事業者の返礼品が存在しない';
		include get_theme_file_path() . '/404.php';
		echo "<script>console.log('検索エラー');</script>";

	} // elseif ( ! isset( $item_amount ) ) { // イレギュラー

		// 何もしない

	// }
	?>

</section>

<link href="https://cdn.jsdelivr.net/npm/bootstrap5-toggle@4.3.4/css/bootstrap5-toggle.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap5-toggle@4.3.4/js/bootstrap5-toggle.min.js"></script>
