<?php
/**
 * class-n2-chonbo.php
 *
 * @package neoneng
 */

if ( class_exists( 'N2_Chonbo' ) ) {
	new N2_Chonbo();
	return;
}

/**
 * チョンボ発見用ページ
 */
class N2_Chonbo {

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		// add_action( 'admin_menu', array( $this, 'add_menu' ) );
	}

	/**
	 * N2 SYNC　メニューの追加
	 */
	public function add_menu() {
		add_menu_page( 'N2 Chonbo', 'N2 Chonbo', 'ss_crew', 'N2_Chonbo', array( $this, 'sync_ui' ), 'dashicons-saved' );
	}

	/**
	 * 同期の為のUI
	 */
	public function sync_ui() {
		$template = isset( $_GET['tab'] ) ? "sync_ui_{$_GET['tab']}" : 'update_price_chonbo';
		?>
		<div class="wrap">
			<h1>N2 Chonbo</h1>
			<?php echo $this->$template(); ?>
		</div>
		<?php
	}

	/**
	 * 価格チョンボのDBアップデート
	 */
	public function update_price_chonbo() {
		$items_apis = array(
			'n2_rakuten_items_api'         => '楽天',
			'n2_furusato_choice_items_api' => 'ふるさとチョイス',
		);
		$items_apis = apply_filters( 'n2_update_price_chonbo', $items_apis );
		// 検証用配列生成
		$arr = array();
		foreach ( $items_apis as $name => $label ) {
			$api = get_option( $name )['data'] ?: false;
			if ( $api ) {
				foreach ( $api as $v ) {
					$arr[ $v['goods_g_num'] ][ $label ] = $v['goods_price'];
				}
			}
		}
		// 任意チョンボデータを追加
		$arr['LEH026']['unko'] = 10000000000;
		// チョンボ保存用配列
		$chonbo = array();
		foreach ( $arr as $key => $value ) {
			if ( 1 < count( array_unique( array_values( $value ) ) ) ) {
				$chonbo[ $key ] = $value;
			}
		}
		if ( ! empty( $chonbo ) ) {
			$chonbo_db = get_option( 'n2_price_chonbo', array() );
			// 日時をキーにする
			$chonbo_db[ wp_date( 'Y-m-d H:i:s' ) ] = $chonbo;
			// update_option( 'n2_price_chonbo', $chonbo_db );
		}
		$price_chonbo = get_option( 'n2_price_chonbo' );
		?>
		<h2>最新のチョンボ</h2>
		<table class="widefat striped fixed">
			<thead>
				<tr>
					<th>返礼品コード</th>
					<?php foreach ( end( end( $price_chonbo ) ) as $name => $price ) : ?>
					<th><?php echo $name; ?></th>
					<?php endforeach; ?>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( end( $price_chonbo ) as $code => $prices ) : ?>
				<tr>
					<td><?php echo $code; ?></td>
					<?php foreach ( $prices as $price ) : ?>
					<td><?php echo $price; ?></td>
					<?php endforeach; ?>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}
}
