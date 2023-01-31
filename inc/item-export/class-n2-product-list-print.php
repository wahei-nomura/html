<?php
/**
 * class-n2-product-list-print.php
 *
 * @package neoneng
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
		add_action( 'wp_ajax_print', array( $this, 'print_out' ) );
	}
	/**
	 * array to string
	 */
	public function attr_array2str( $arr, $join_str = ' ' ) {
		$attr = array();
		foreach ( $arr as $key => $val ) {
			$attr = array( ...$attr, "{$key}=\"{$val}\"" );
		}
		if ( $attr ) {
			return $join_str . implode( $join_str, $attr );
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
		$ids                          = $_POST['print'];
		$print_css                    = get_theme_file_uri( 'dist/print.css' );
		$print_yaml                   = yaml_parse_file( get_theme_file_path( 'config/n2-product-list-print.yml' ) );
		$confirm_table_th_list        = $print_yaml['確認用テーブル']['th'];
		$confirm_table_th_list        = apply_filters( 'n2_product_list_print_th_list', $confirm_table_th_list );
		$product_table_tr_list        = $print_yaml['返礼品テーブル']['tr'];
		?>
		<!DOCTYPE html>
		<html lang="ja">
			<head>
				<meta charset="UTF-8">
				<title>返礼品シート印刷</title>
				<link rel="stylesheet" href="<?php echo $print_css; ?>">
			</head>
			<body>
				<?php foreach (get_posts("include={$ids}&meta_key=返礼品コード&orderby=meta_value&order=ASC&post_status=any") as $p) : ?>
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
												if ( ! $td ) {
													return;
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
												$td = N2_Rakuten_CSV::allergy_display( $p->ID );
												if( ! $td ) {
													$td = 'アレルギー表示しない';
												} elseif ( 'アレルギー品目なし' === $td ) {
													$td = 'アレルギー品目なし食品';
												}
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
									<th<?php echo $th_attr; ?>><?php echo $th ?></th>
									<td colspan="2"<?php echo $td_attr; ?>><?php echo $td ?></td>
								</tr>
								<?php endforeach; ?>
								<tr style="border: 3px solid #000;">
									<th class="bg">寄附金額</th>
									<td colspan="2" style="font-size: 18px;font-weight: bold;"><?php echo number_format(get_post_meta($p->ID, "寄附金額", true)); ?></td>
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