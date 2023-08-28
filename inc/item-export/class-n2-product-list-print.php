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
		$confirm_table_th_list = apply_filters( 'n2_product_list_print_add_confirm_table_th_list', $confirm_table_th_list );
		$product_table_tr_list = apply_filters( 'n2_product_list_print_add_product_table_tr_list', $product_table_tr_list );
		?>

		<!-- 補助コメントは後で整理するべし -->
		<!DOCTYPE html>
		<html lang="ja">
			<head>
				<meta charset="UTF-8">
				<title>返礼品シート印刷</title>
				<link rel="stylesheet" href="<?php echo $print_css; ?>">
				<link href="//cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
				<!-- Vue読み込み -->
				<script src="https://cdn.jsdelivr.net/npm/vue@2.x"></script>
			</head>
			<body>
				<!-- VueApp作成 -->
				<div id="app">
					<div class="form-check form-switch">
						<input class="form-check-input" type="checkbox" id="switch_1" name="switch_1" v-model="bool">
						<label class="form-check-label" for="switch_1">つけたり消したり</label>
					</div>
					<!-- itemsを一個一個回す -->
					<div v-for="p in items" :key="p['返礼品コード']">
						<h1 v-if="!p['寄附金額']">{{ p['返礼品コード'] }}：寄附金額が入力されていません。</h1>
						<!-- col -->
						<div v-else>
							<table v-if="bool">
								<tr v-for="(value, key) in filteredData(p)">
									<th>{{ key }}</th>
									<td colspan="2">{{ value }}</td>
								</tr>
							</table>
						</div>
					</div>
				</div>
				<script>
					// App
					new Vue({
						el: '#app',
						data: {
							items: <?php echo json_encode( N2_Items_API::get_items() ); ?>,
							bool: true,
						},
						methods: {
							filteredData: function(p) {
								const keys_to_extract = [ '返礼品コード', 'タイトル' ];
								let data = {};

								keys_to_extract.forEach(key => {
									if (p[key]) data[key] = p[key];
								});

								return data;
							},
							toggle: function(prop) {
								this[prop] = !this[prop];
							}
						}
					});
				</script>
			</body>
		</html>
	<?php
	die();
	}
}
