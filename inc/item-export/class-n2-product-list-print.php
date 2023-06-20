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
				<?php
					foreach ( N2_Items_API::get_items() as $p ) :
				?>
					<?php $confirm_table_th_list['コード'] = $p['返礼品コード'] . '&nbsp;'; ?>
					<div class="page-break">
						<table>
							<tbody>
								<tr>
									<th class="none" colspan="2" rowspan="2">
										<h1><?php echo $p['タイトル']; ?></h1>
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
										$td = $p[ $val['meta_key'] ] ?? $p[ $th ];
										$td = nl2br( $td );
										$td = preg_replace( '@\t|\r|\n|@', '', $td );
										$td = preg_replace( '@(<br />)+@', '<br />', $td );
										// thで分岐
										switch ( $th ) {
											case '事業者名':
												$td = $p['事業者名'];
												break;
											case '価格':
												$td = $p['価格'];
												$td = number_format( $td );
												break;
											case '送料':
												if ( ! $td || is_numeric( $p['発送サイズ'] ) ) {
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
												$td .= '類型該当理由：' . $p['類型該当理由'];
												break;
											case 'アレルギー':
												$td  = empty( $meta['アレルゲン'] ) ? '' : '含んでいる品目：' . implode( ',', $meta['アレルゲン'] );
												$td .= $meta['アレルゲン注釈'] ? "<br>※ {$meta['アレルゲン注釈']}" : '';
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
								<?php if ( ! empty( $td ) ) : ?>
								<tr>
									<th<?php echo $th_attr; ?>><?php echo $th; ?></th>
									<td colspan="2"<?php echo $td_attr; ?>><?php echo $td; ?></td>
								</tr>
								<?php endif; ?>
								<?php endforeach; ?>
								<tr style="border: 3px solid #000;">
									<th class="bg">寄附金額</th>
									<td colspan="2" style="font-size: 18px;font-weight: bold;"><?php echo number_format( $p['寄附金額'] ); ?></td>
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
