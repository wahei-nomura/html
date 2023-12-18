<?php
/**
 * RMS商品API
 *
 * @package neoneng
 */

if ( class_exists( 'N2_Items_API_Rakuten' ) ) {
	new N2_Items_API_Rakuten();
	return;
}

/**
 * 寄附金額の計算のためのAPI
 */
class N2_Items_API_Rakuten extends N2_Portal_Item_Data {

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
			$this->exit( '[RMS Shop API] url取得失敗' );
		}
		$this->shop_code = $shop['url'];
		// 返礼品データ取得
		$items = N2_RMS_Items_API::search( 0, -1 );
		if ( ! isset( $items['results'] ) ) {
			$this->exit( '[RMS Items API] 返礼品データ取得失敗' );
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
		$item = $v['item'];
		$ids  = array( $item['itemNumber'], $item['manageNumber'] );
		return array(
			'goods_g_num' => $item['itemNumber'],
			'goods_name'  => $item['title'],
			'goods_price' => array_values(
				// $item['variants']のキーとmerchantDefinedSkuIdで絞り込み
				array_filter(
					$item['variants'],
					fn( $d, $k ) => in_array( $k, $ids, true ) || in_array( $d['merchantDefinedSkuId'], $ids, true ),
					ARRAY_FILTER_USE_BOTH
				)
			)[0]['standardPrice'],
			'insert_date' => $item['created'],
			'updated'     => $item['updated'],
			'url'         => "https://item.rakuten.co.jp/{$this->shop_code}/{$item['manageNumber']}",
			'image'       => 'CABINET' === $item['images'][0]['type']
				? "https://image.rakuten.co.jp/{$this->shop_code}/cabinet{$item['images'][0]['location']}"
				: "https://www.rakuten.ne.jp/gold/{$this->shop_code}{$item['images'][0]['location']}",
		);
	}
}
