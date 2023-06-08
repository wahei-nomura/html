<?php
/**
 * class-n2-product-list-print.php
 *
 * @package neoneng
 */

if ( class_exists( 'N2_Product_List_Print' ) ) {
	new N2_Product_List_Print();
	return;
}

/**
 * N2_Product_List_Print
 */
class N2_Product_List_Print {

	/**
	 * constructor
	 */
	public function __construct() {
		add_action( 'wp_ajax_n2_print_out', array( $this, 'print_out' ) );
	}
	/**
	 * 連想配列をhtmlのattrとして追加する
	 *
	 * @param array  $arr 連想配列
	 * @param string $delimiter デリミタ
	 *
	 * @return string
	 */
	public function attr_array2str( $arr, $delimiter = ' ' ) {
		$attr = array();
		foreach ( $arr as $key => $val ) {
			$attr = array( ...$attr, "{$key}=\"{$val}\"" );
		}
		if ( $attr ) {
			return $delimiter . implode( $delimiter, $attr );
		} else {
			return '';
		}
	}
	/**
	 * print out product list
	 *
	 * @return void
	 */
	public function print_out() {
		global $n2;
		$ids                   = filter_input( INPUT_POST, 'ids' );
		$print_css             = get_theme_file_uri( 'dist/css/admin-print.css' );
		$confirm_table_th_list = $n2->product_list_print['確認用テーブル']['th'];
		$product_table_tr_list = $n2->product_list_print['返礼品テーブル']['tr'];
		// プラグイン側で追加
		$confirm_table_th_list = apply_filters( 'n2_product_list_print_add_confirm_table_th_list', $confirm_table_th_list );
		$product_table_tr_list = apply_filters( 'n2_product_list_print_add_product_table_tr_list', $product_table_tr_list );
		?>
		<!DOCTYPE html>
		<html lang="ja">
			<head>
				<meta charset="UTF-8">
				<title>返礼品シート印刷</title>
				<link rel="stylesheet" href="<?php echo $print_css; ?>">
			</head>
			<body>
				<?php foreach ( get_posts( "include={$ids}&meta_key=返礼品コード&orderby=meta_value&order=ASC&post_status=any" ) as $p ) : ?>
					<?php $confirm_table_th_list['コード'] = get_post_meta( $p->ID, '返礼品コード', true ) . '&nbsp;'; ?>
					<div class="page-break">
						<table>
							<tbody>
								<tr>
									<th class="none" colspan="2" rowspan="2">
										<h1><?php echo $p->post_title; ?></h1>
									</th>
									<?php foreach ( $confirm_table_th_list as $th => $_ ) : ?>
										<th class="bg"><?php echo $th; ?></th>
									<?php endforeach; ?>
								</tr>
								<tr>
									<?php foreach ( $confirm_table_th_list as $_ => $td ) : ?>
										<td><?php echo $td; ?></td>
									<?php endforeach; ?>
								</tr>
							</tbody>
						</table>
						<table>
							<tbody>
								<tr>
									<td class="none" colspan="3">&nbsp;</td>
								</tr>
								<tr>
									<th class="bg">項目</th>
									<th class="bg" colspan="2">内容</th>
								</tr>
								<?php foreach ( $product_table_tr_list as $th => $val ) : ?>
									<?php
										$td = isset( $val['meta_key'] )
											? get_post_meta( $p->ID, $val['meta_key'], true )
											: get_post_meta( $p->ID, $th, true );
										$td = nl2br( $td );
										$td = preg_replace( '@\t|\r|\n|@', '', $td );
										$td = preg_replace( '@(<br />)+@', '<br />', $td );
										// thで分岐
										switch ( $th ) {
											case '事業者名':
												$td = get_the_author_meta( $val['meta_key'], $p->post_author );
												break;
											case '価格':
												$td = ( get_post_meta( $p->ID, '定期便価格', true ) && ( get_post_meta( $p->ID, '定期便', true ) > 1 ) )
													? get_post_meta( $p->ID, '定期便価格', true )
													: $td;
												$td = number_format( $td );
												break;
											case '送料':
												if ( ! $td || is_numeric( get_post_meta( $p->ID, '発送サイズ', true ) ) ) {
													continue 2;
												}
												$td = number_format( $td );
												break;
											case '定期便回数':
												$td  = $td > 1
													? $td . '回定期便'
													: '定期便ではない';
												$td .= '&nbsp';
												break;
											case 'キャッチコピー':
												$td .= '&nbsp';
												break;
											case '地場産品類型':
												if ( $td ) {
													$td .= '<br>';
												}
												$td .= '類型該当理由：' . get_post_meta( $p->ID, '類型該当理由', true );
												break;
											case 'アレルギー':
												// $td = N2_Rakuten_CSV::allergy_display( $p->ID, 'print' );
												break;
											case '発送サイズ':
												$td = ( is_numeric( $td ) )
													? ( ( mb_substr( $td, -1 ) * 20 ) + 40 ) . 'サイズ'
													: $td;
										}
										$th_attr = isset( $val['attr']['th'] )
											? $this->attr_array2str( $val['attr']['th'] )
											: '';
										$td_attr = isset( $val['attr']['td'] )
											? $this->attr_array2str( $val['attr']['td'] )
											: '';
									?>

								<tr>
									<th<?php echo $th_attr; ?>><?php echo $th; ?></th>
									<td colspan="2"<?php echo $td_attr; ?>><?php echo $td; ?></td>
								</tr>
								<?php endforeach; ?>
								<tr style="border: 3px solid #000;">
									<th class="bg">寄附金額</th>
									<td colspan="2" style="font-size: 18px;font-weight: bold;"><?php echo number_format( get_post_meta( $p->ID, '寄附金額', true ) ); ?></td>
								</tr>
							</tbody>
						</table>
					</div>
				<?php endforeach; ?>
			</body>
		</html>
	<?php
	die();
	}
}
