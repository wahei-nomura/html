<?php
global $post;
$post_data      = N2_Functions::get_all_meta( $post );
$url_parse      = explode( '/', get_option( 'home' ) );
$town_code      = end( $url_parse );
$n2_fields      = yaml_parse_file( get_theme_file_path() . '/config/n2-fields.yml' );
$n2_file_header = yaml_parse_file( get_theme_file_path() . '/config/n2-file-header.yml' );
$n2_towncode    = yaml_parse_file( get_theme_file_path() . '/config/n2-towncode.yml' );
$img_dir        = str_replace( 'n2-towncode', $n2_towncode[ $town_code ]['楽天'], $n2_file_header['rakuten']['img_dir'] );

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

<style>
	*{
		list-style: none;

	}

	table{
		/* display: block; */
		table-layout: fixed;
		/* width: calc(100% + 60px); */
		width: 100%;
		border-collapse: collapse;
		border-spacing: 0;
		/* transform: translateX(-30px); */
		height: fit-content;
		scroll-snap-type: x mandatory;
		background-color: #fff;
	}
	th{
		background-color: gray;
	}
	table th,table td{
		padding: 5px 10px;
		/* text-align: center; */
		font-size: 0.8rem;
		scroll-snap-align: start;
		scroll-margin-left: 34px;
		border: 1px solid #fff;
	}
	table td:nth-child(odd){
		text-align: center;
	}
	thead th{
		background-color: #444;
		color:#fff;
		font-size: 0.9rem;
	}
	tbody tr:nth-child(odd){
		background-color: #eee;
	}
	tbody tr th:first-of-type {
		position: sticky;
		top: 0;
		left: 0;
		background-color: gray;
		color:#fff;
	}
	

	.wrapper *{
		margin: 0;
		padding: 0;
		line-height: 1.4;
		font-size: 1rem;
	}
	.wrapper{
		margin: 20px;
		display: grid;
		grid-template:
			"img_m  img_m  img_m"  auto
			"img_s  img_s  img_s"  auto
			"title  title  title"  auto
			"amount amount amount" auto 
			"link   link   link"   auto
			"tab_h  tab_h  tab_h"  auto
			"tab_i  tab_i  tab_i"  auto;
		grid-template-columns: repeat(3,1fr);
		grid-row-gap: 10px;
	}
	.wrapper .title{
		grid-area: title;
		font-size: 2rem;
	}
	.wrapper .worker{
		grid-area: worker;
		text-indent: 1rem;
		font-weight: bold;
	}
	.wrapper .worker .material-symbols-outlined{
		font-size: 1rem;
	}
	.wrapper-img-list{
		grid-area: img_s;
		width: 100%;
		overflow: hidden;
		
	}
	.wrapper .main-img{
		grid-area: img_m;
	}
	.wrapper .sub-imgs{
		display: flex;
		flex-direction: row;
		column-gap: 10px;
		justify-content: space-between;
		scroll-snap-type: x proximity;
		scroll-behavior: smooth;
		transform: translate(0,0);
		padding: 0;
		margin: 0;
	}
	.sub-img{
		justify-self: center;
		width: calc(100% / 3 - 20px);
		scroll-snap-align: start;
	}
	.wrapper .donation-amount{
		grid-area: amount;
	}
	.wrapper .price{
		text-indent: 3rem;
		font-size: 2rem;
	}
	.wrapper > .tabList{
		grid-area: tab_i;
	}
	.wrapper h2 {
		font-size: 1.3rem;
	}
	.wrapper > h2 + * {
		display: none;
		overflow: scroll;
	}
	.wrapper > h2 + .active {
		grid-column: 1/-1;
		display: block;
	}
	.price::before{
		content: '\0a5';
	}
	tr :not(th:first-of-type) {
		min-width: 100px;
		white-space: break-spaces;
	}
	.portal-scraper tbody tr:nth-child(2) td {
		vertical-align: top;
		text-align: left;
	}
	.portal-links{
		grid-area: 'link';
		grid-column: 1/-1;
		display: flex;
		grid-column-gap: 10px;
	}
	.portal-link{
		width:33%;
		border: 1px solid #000;
		border-radius: 10px;
		text-align: center;
		padding: 10px 0;
	}
	.portal-link a{
		text-decoration: none;
	}
	.portal-scraper{
		display: block;
	}
	.portal-scraper{
		text-align: center;
	}
	.mordal {
		position: fixed;
		top: 0;
		left: 0;
		width: 100%;
		height: 100%;
		background: #0008;
		display: grid;
		align-items: center;
	}
	.mordal > * {
		position: absolute;
		overflow-x: scroll;
		max-height: 70%;
		background-color: #fff;
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
			"mr1 img_m    img_m    img_m    mr2 title       title       title       mr3" minmax(6rem,auto)
			"mr1 img_m    img_m    img_m    mr2 worker      worker      worker      mr3" minmax(4rem,auto)
			"mr1 img_m    img_m    img_m    mr2 amount      amount      amount      mr3" minmax(6.5rem,auto)
			"mr1 img_m    img_m    img_m    mr2 description description description mr3" auto
			"mr1 img_s    img_s    img_s    mr2 description description description mr3" auto
			"mr1 detail_h detail_h detail_h mr2 application application application mr3" minmax(1rem,auto)
			"mr1 detail_t detail_t detail_t mr2 tabi        tabi        tabi        mr3" minmax(1rem,auto);
			grid-template-columns: 10px repeat(3,1fr) 0px repeat(3,1fr) 10px;
			grid-column-gap: 20px;
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
		.wrapper > div.description{
			grid-area:description;
			margin-top: 30px;
			line-height: 2;
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
			<div class="wrapper-img-list">
				<ul class='sub-imgs'>
					<?php for ( $i = 0; $i < 2; $i++ ) : ?>
					<?php foreach ( $post_data['商品画像'] as $img_url ) : ?>
						<img class='sub-img' src="<?php echo $img_url; ?>" width='100%' height="100%">
					<?php endforeach; ?>
					<?php endfor; ?>
				</ul>
			</div>
			<h1 class='title'><?php the_title(); ?></h1>
			<div class="worker">
				提供事業者：(株)優良企業
				<a href="#">公式ページ<span class="material-symbols-outlined">
open_in_new
</span></a>
			</div>
			<div class='donation-amount'>
				<h2>寄附金額</h2>
				<div class="price"><?php echo number_format( $post_data['寄附金額'] ); ?></div>
			</div>
			<?php $portals = array( '楽天', 'チョイス' ); ?>
			<ul class="portal-links">
			<?php foreach ( $portals as $portal ) : ?>
				<?php if ( isset( $tashiro[ $portal ] ) ) : ?>
					<li class="portal-link">
						<a href="<?php echo $tashiro[ $portal ]['item_url']; ?>"><?php echo $portal; ?></a>
					</li>
				<?php endif; ?>
			<?php endforeach; ?>
			</ul>

			<h2 class='item-detail'>商品詳細</h2>
			<table class="item-detail tabList">
				<thead>
					<tr><th width="35%">項目</th><th width="65%">内容</th></tr>
				</thead>
				<tbody>
					<?php $item_detail = array( '価格', '内容量・規格等', 'アレルゲン', 'アレルゲン注釈', '賞味期限', '消費期限', '限定数量' ); ?>
					<?php foreach ( $item_detail as $key ) : ?>
					<?php if ( 'checkbox' === $fields[ $key ]['type'] || 'select' === $fields[ $key ]['type'] ) :
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
			<h2 class="description">説明文</h2>
			<div class="description tabList">
				<?php echo str_replace( array("\r\n","\r"), '<br>', $post_data['説明文'] ); ?>
			</div>
			<h2 class="application">申込詳細</h2>
			<table class="application tabList">
				<thead>
					<tr><th width="35%">項目</th><th width="65%">内容</th></tr>
				</thead>
				<tbody>
					<?php $application = array( '申込期間', '配送期間', 'のし対応', '取り扱い方法1', '取り扱い方法2', '発送方法', '発送サイズ' ); ?>
					<?php foreach ( $application as $key ) : ?>
					<?php if ( 'checkbox' === $fields[ $key ]['type'] || 'select' === $fields[ $key ]['type'] ) :
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
			

			<h2>tashiro</h2>
			<table border="1" class='portal-scraper' style="display:none;">
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
		</div>
		<div class="mordal" style="display: none;">
		</div>


	<?php if ( 'publish' === get_post_status() ) : ?>
	<?php /* echo is_user_logged_in() ? get_template_part( 'template/progress' ) : '';*/ ?>
	<p>公開中の商品は違う感じの表示にする。</p>
	<script>
		// transformの各パラメータ
		const transform = () => {
			let matrix = {};
			if('none' !== jQuery('.sub-imgs').css('transform')){
				const transform = jQuery('.sub-imgs').css('transform').split('(')[1].split(')')[0].split(', ')
				if( 6 === transform.length ) {
					matrix = {
						'scale-x':transform[0],
						'rotate-p':transform[1],
						'rotate-m':transform[2],
						'scale-y':transform[3],
						'translate-x':transform[4],
						'translate-y':transform[5]
					};
				} else if ( 16 === transform.length ) {
					matrix = {
						'scale-x':transform[0],
						'rotate-z-p':transform[1],
						'rotate-y-p':transform[2],
						'perspective1':transform[3],
						'rotate-z-m':transform[4],
						'scale-y':transform[5],
						'rotate-x-p':transform[6],
						'perspective2':transform[7],
						'rotate-y-m':transform[8],
						'rotate-x-m':transform[9],
						'scale-z':transform[10],
						'perspective3':transform[11],
						'translate-x':transform[12],
						'translate-y':transform[13],
						'translate-z':transform[14],
						'perspective4':transform[15]
					};
				}
			}
			return matrix;
		}
		// transform用のアニメーション
		jQuery.fn.animate2 = function (properties, duration, ease) {
			ease = ease || 'ease';
			var $this = this;
			var cssOrig = { transition: $this.css('transition') };
			return $this.queue(next => {
				properties['transition'] = 'all ' + duration + 'ms ' + ease;
				$this.css(properties);
				setTimeout(function () {
					$this.css(cssOrig);
					next();
				}, duration);
			});
		};
		// 画像サイズの小数点以下を切り捨て
		jQuery.fn.imgResize = function(){
			jQuery(this).each(function(){
				jQuery(this).css({
					width:'',
					height:''
				}).css({
					width:Math.floor(jQuery(this).width()),
					height:Math.floor(jQuery(this).height())
				})
			})
			jQuery('.sub-imgs').css({
				
				transform: function(){
					return `translate(${ -jQuery('.sub-imgs').data('count') * (10 + jQuery('.sub-img').width())}px,0)`
				}()
			})
		}

		jQuery('.sub-img').on('click',function(){
			jQuery('.main-img').attr('src',jQuery(this).attr('src'))
		})
		jQuery('.wrapper > h2').on('click',function(){
			jQuery('.is-mordal').removeClass('is-mordal')
			console.log(jQuery(this).next());
			
			let html = jQuery(this).next().clone(false).css('display','');
			let class_name = jQuery(this).attr('class');
			console.log(class_name);
			jQuery(this).addClass('is-mordal')
			jQuery('.mordal').show().html(html)
			jQuery('body').css('overflow-y', 'hidden')
		})
		jQuery('.mordal').on('click',function(){
			jQuery(this).hide()
			jQuery('body').css('overflow-y','')
		})
		jQuery('.mordal').on('click','*', function(e){
			e.stopPropagation();
		})
		// 画像が選択状態か判断
		let mousedown_flg = false;
		jQuery('.sub-imgs').on('mousedown',function(){
			mousedown_flg = true;
		})
		jQuery(window).on('mouseup dragend',function(){
			mousedown_flg = false;
		});
		jQuery(window).resize(function(){
			jQuery('.sub-img').imgResize();
		})
		window.setInterval(
			function(e){
				if ( ! mousedown_flg ) {
					jQuery('.sub-imgs').css(function(){
						if (jQuery('.sub-img').length / 2 === Math.floor(- Number(transform()['translate-x'])/ (jQuery('.sub-img').width() + 10))) {
							return {
								'transform': 'translate(0,0)',
								'transition':  ''
							};
						}else{
							return {'color':''};
						}
					}()).animate2({
						transform: `translate(${ Number(transform()['translate-x']) - jQuery('.sub-img').width() - 10 }px,0)`
					},500)
					jQuery('.sub-imgs')
					.data('count',Math.floor(- Number(transform()['translate-x'])/ (jQuery('.sub-img').width() + 10))+1)
					.data('mx',jQuery('.sub-img').width()+10)
					console.log(jQuery('.sub-imgs').data());
				}
		},2000)
		
		jQuery(window).on('load',function(){
			jQuery('.sub-img').imgResize();
		})
	</script>
</body>
		<?php
	endif;
endwhile;
endif;


get_footer();
