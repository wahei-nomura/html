<?php
/**
 * RMS商品API
 *
 * @package neoneng
 */

if ( class_exists( 'N2_Items_Rakuten_API' ) ) {
	new N2_Items_Rakuten_API();
	return;
}

/**
 * 寄附金額の計算のためのAPI
 */
class N2_Items_Rakuten_API extends N2_Portal_Item_Data {

	/**
	 * 楽天ショップコード
	 *
	 * @var string
	 */
	private $shop_code = '';

	/**
	 * 保存時のタイトル
	 *
	 * @var array
	 */
	public $post_title = '楽天';

	/**
	 * APIデータのアップデート
	 */
	public function update() {
		// ショップコード取得
		$shop = N2_RMS_Shop_API::shop_master_get();
		if ( ! isset( $shop['url'] ) ) {
			echo 'ERROR！RMS ShopAPIでurl取得失敗。処理を中止します。';
			exit;
		}
		$this->shop_code = $shop['url'];
		// 返礼品データ取得
		$items = N2_RMS_Items_API::search( 0, -1 );
		if ( ! isset( $items['results'] ) ) {
			echo 'ERROR！RMS ShopAPIで返礼品データ取得失敗。処理を中止します。';
			exit;
		}
		$this->data = array_map( array( $this, 'array_format' ), $items['results'] );
		$this->data = array_unique( $this->data, SORT_REGULAR );
		$this->insert_portal_data();
		exit;
	}

	/**
	 * データ配列の型の変換
	 *
	 * @param array $v RMSAPIから取得した生配列
	 */
	public function array_format( $v ) {
		return array(
			'goods_g_num' => $v['item']['itemNumber'],
			'goods_name'  => $v['item']['title'],
			'goods_price' => array_values( $v['item']['variants'] )[0]['standardPrice'],
			'insert_date' => $v['item']['created'],
			'updated'     => $v['item']['updated'],
			'url'         => "https://item.rakuten.co.jp/{$this->shop_code}/{$v['item']['manageNumber']}",
			'image'       => 'CABINET' === $v['item']['images'][0]['type']
				? "https://image.rakuten.co.jp/{$this->shop_code}/cabinet{$v['item']['images'][0]['location']}"
				: "https://www.rakuten.ne.jp/gold/{$this->shop_code}{$v['item']['images'][0]['location']}",
		);
	}
}
