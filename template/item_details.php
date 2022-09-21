<?php
global $post;
$post_data = N2_Functions::get_all_meta( $post );
// var_dump($post_data);
// echo esc_html(get_post_type_object(get_post_type())->name);
$yml = yaml_parse_file( get_theme_file_path() . '/config/n2-fields.yml');

// プラグインn2-developのn2_setpost_show_customfields呼び出し
$fields = apply_filters( 'n2_setpost_show_customfields', $yml, 'default' );
var_dump($post_data['商品画像']);
// var_dump($fields);
$url_parse = explode( '/', get_option( 'home' ) );
$town_code =  end( $url_parse );
$tashiro = apply_filters('wp_ajax_SS_Portal_Scraper', $town_code, $post_data['返礼品コード'] );

var_dump( $tashiro);
?>

<style>
	*{
		list-style: none;
	}

	table{
		table-layout: fixed;
		width: 100%;
		border-collapse: collapse;
		border-spacing: 0;
	}
	table th,table td{
		padding: 5px 10px;
		text-align: left;
	}
	table td:nth-child(odd){
		text-align: center;
	}
	table tr:nth-child(odd){
		background-color: #eee;
	}
	table tr:first-child th {
		position: sticky;
		top: 0;
		z-index: 1;
		background-color: gray;
		color:#fff;
	}

	.wrapper > *{
		margin: 0;
		padding: 0;
		line-height: 1rem;
		font-size: 1rem;
	}
	.wrapper{
		margin: 20px;
		display: grid;
		grid-template:
			"img_m img_m img_m" auto
			"img_s img_s img_s" auto
			"title title title" auto
			"price price price" auto 
			"tab_h tab_h tab_h" auto
			"tab_i tab_i tab_i" 400px;
		grid-template-columns: repeat(3,1fr);
		grid-row-gap: 10px;
	}
	.wrapper .title{
		grid-area: title;
	}
	.wrapper .main-img{
		grid-area: img_m;
	}
	.wrapper .sub-imgs{
		grid-area: img_s;
		display: flex;
		flex-direction: row;
		column-gap: 10px;
		width: 100%;
		justify-content: space-between;
	}
	.sub-img{
		justify-self: center;
		max-width: calc(100% / 3 - 20px);
	}
	.wrapper .price{
		grid-area: price;
	}
	.wrapper > .tabList{
		grid-area: tab_i;
	}
	.wrapper > h2 {
		justify-self: center;
		align-self: center;
		grid-area: tab_h 1fr;
	}
	.wrapper > h2 + * {
		display: none;
		overflow: scroll;
	}
	.wrapper > h2 + .active {
		display: table;
	}
	.price::before{
		content: '\0a5';
	}
	@media screen and (max-width:766px) {
		.wrapper > h2{
			background-color: #888;
			width: 100%;
			height: 100%;
			text-align: center;
			line-height: 2rem;
		}
		.wrapper > h2.active{
			background-color: #eee;
		}
	}
	@media screen and (min-width:767px) {
		.wrapper{
			grid-template:
				"mr1 title title title title title title title mr3" auto
				"mr1 img_m img_m img_m mr2 price price price mr3" auto
				"mr1 img_m img_m img_m mr2 description description description mr3" auto
				"mr1 img_s img_s img_s mr2 description description description mr3" auto
				"mr1 detail_h detail_h detail_h mr2 application application application mr3" auto
				"mr1 detail_t detail_t detail_t mr2 tabi tabi tabi mr3" auto;
				/* "mr1 tabi tabi tabi mr2 tabi tabi tabi mr3" 400px; */
			grid-template-columns: 10px repeat(3,1fr) 0px repeat(3,1fr) 10px;
			grid-column-gap: 20px;
		}
		.wrapper .sub-imgs{
			grid-area: img_s;
		}
		.wrapper > .price{
			/* grid-row: 1/2;
			grid-column: 2/-1; */
		}
		.wrapper > h2:nth-of-type(1){
			grid-area:detail_h;
		}
		.wrapper > .item-detail{
			grid-area:detail_t;
		}
		.wrapper > h2:nth-of-type(2){
			grid-area:description;
			align-self: flex-start;
			justify-self: left;
		}
		.wrapper > .description{
			grid-area:description;
			margin-top: 30px;
		}
		.wrapper > h2:nth-of-type(3){
			grid-area:application;
		}
		.wrapper > .application{
			grid-area:tabi;
		}
		.wrapper h2::before{
			content:'【'
		}
		.wrapper h2::after{
			content:'】'
		}
		.wrapper > h2 + * {
		display: table;
		overflow: auto;
	}
	}
</style>

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
		<div class="wrapper">
			<img class='main-img' src="<?php echo $post_data['商品画像'][0]; ?>" width='100%'>
			<ul class='sub-imgs'>
				<img class='sub-img' src="<?php echo $post_data['商品画像'][1]; ?>" width='100%'>
				<img class='sub-img' src="<?php echo $post_data['商品画像'][2]; ?>" width='100%'>
				<img class='sub-img' src="<?php echo $post_data['商品画像'][3]; ?>" width='100%'>
			</ul>
			<h1 class='title'><?php the_title(); ?></h1>
			<div class='price'><?php echo number_format( $post_data['価格'] ); ?></div>
			<h2 class='active'>商品詳細</h2>
			<table class="item-detail tabList active">
				<tr><th width="35%">項目</th><th width="65%">内容</th></tr>
				<?php $item_detail = array( '価格', '内容量・規格等', 'アレルゲン', 'アレルゲン注釈', '賞味期限', '消費期限', '限定数量' ); ?>
				<?php foreach ( $item_detail as $key ) : ?>
					<?php
						if ( 'checkbox' === $fields[$key]['type'] || 'select' === $fields[$key]['type'] ) :
							$options = $fields[$key]['option'];
							// var_dump($options);
							// foreach ( $options as $option ) {
							// }
							$checked = '';
							if ( 'checkbox' === $fields[$key]['type'] ) {
								if ( ! empty( $post_data[ $key ] ) ) {
									$checked_arr =array_filter(
										$fields[$key]['option'],
										fn($value) => in_array( array_search( $value, $fields[ $key ]['option'] ), $post_data[ $key ] )
									);
									$checked .= implode( ',', $checked_arr );
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
			</table>
			<h2>説明文</h2>
			<div class="description tabList">
				<?php echo $post_data['説明文']; ?>
			</div>
			<h2>申込詳細</h2>
			<table class="application tabList">
				<tr><th width="35%">項目</th><th width="65%">内容</th></tr>
				<?php $application = array( '申込期間', '配送期間', 'のし対応', '取り扱い方法1', '取り扱い方法2', '発送方法', '発送サイズ'); ?>
				<?php foreach ( $application as $key ) : ?>
						<?php
						if ('checkbox' === $fields[$key]['type'] || 'select' === $fields[$key]['type'] ) :
							$new_options = $fields[$key]['option'];
							$cheked = '';
							if ( 'checkbox' === $fields[$key]['type'] ) {
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
							<td><?php echo 'select' === $fields[$key]['type'] ? $new_options[ $post_data[ $key ] ] : $cheked; ?></td>
						</tr>
						<?php else : ?>
						<tr>
							<th><?php echo $key; ?></th>
							<td><?php echo ! empty( $post_data[ $key ] ) ? nl2br( $post_data[ $key ] ) : '入力無し'; ?></td>
						</tr>
						<?php endif; ?>
				<?PHP endforeach; ?>
			</table>

			<h2>tashiro</h2>
			<table style="grid-column:2/-2;">
				<?php foreach ( $tashiro as $portal => $params ) : ?>
				<tr>
					<th><?php echo isset( $portal ) ?$portal :'unknown'; ?></th>
					<?php foreach ( $params as $param ) : ?>
					<td><?php echo $param; ?></td>
					<?php endforeach; ?>
				</tr>
				<?php endforeach; ?>
			</table>
		</div>


	<?php if ( 'publish' === get_post_status() ) : ?>
	<?php /* echo is_user_logged_in() ? get_template_part( 'template/progress' ) : '';*/ ?>
	<p>公開中の商品は違う感じの表示にする。</p>
	<script>
		jQuery('.wrapper > h2').on('click',function(){
			jQuery(this).siblings().removeClass('active')
			jQuery(this).addClass('active')
			jQuery(this).next().addClass('active')
		})
	</script>
</body>
		<?php
	endif;
endwhile;
endif;


get_footer();