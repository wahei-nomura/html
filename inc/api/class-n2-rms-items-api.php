<?php
/**
 * class-n2-rms-category-api.php
 * RMS CATEGORY API
 *
 * @package neoneng
 */

if ( class_exists( 'N2_RMS_Items_API' ) ) {
	new N2_RMS_Items_API();
	return;
}

/**
 * RMS商品API
 */
class N2_RMS_Items_API extends N2_RMS_Base_API {

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		parent::__construct();
		add_action( 'wp_ajax_' . mb_strtolower( get_class( $this ) ) . '_ajax', array( $this, 'ajax' ) );
		add_action( 'wp_ajax_nopriv_' . mb_strtolower( get_class( $this ) ) . '_ajax', array( $this, 'ajax' ) );
	}

	/**
	 * RMS WEB SERVICE : items.search
	 * この機能を利用すると、指定した条件から通常商品・予約商品・定期購入商品の商品情報を検索することができます。
	 * 商品を登録・削除してから本機能の検索情報に反映されるまで、最大24時間かかります。
	 * 削除済みの商品が検索結果に含まれる場合、「manageNumber」のみが返却されます。
	 *
	 * @param string $offset ジャンルID
	 * @param string $hits 商品属性ID
	 * @param string $is_item_stockout 商品属性ID
	 * @return array
	 */
	public static function search( $offset = 0, $hits = 100, $is_item_stockout = 'false' ) {
		$params = array(
			'offset'         => $offset, // 0～10000
			'hits'           => $hits, // 1〜100
			'isItemStockout' => $is_item_stockout,
			'sortKey'        => 'manageNumber',
		);
		$url    = static::$settings['endpoint'] . '/2.0/items/search?' . http_build_query( $params );
		$data   = wp_remote_get( $url, array( 'headers' => static::$data['header'] ) );

		if ( is_wp_error( $data ) ) {
			return array();
		}

		return json_decode( $data['body'], true );
	}

	/**
	 * RMS WEB SERVICE : items.patch
	 * この機能を利用すると、商品管理番号を指定し、商品情報の部分更新をすることができます。
	 * リクエストに含まれない項目は更新対象にならないため、指定した項目のみ更新されます。
	 * https://webservice.rms.rakuten.co.jp/merchant-portal/view/ja/common/1-1_service_index/itemapi2/partiallyupdateitem/
	 *
	 * @param string $manageNumber manageNumber
	 * @param string $body         body
	 */
	public static function items_patch( $manageNumber, $body ) {
		static::check_fatal_error(
			preg_match( '/^[a-zA-Z0-9\-\_]*$/', $manageNumber ),
			'商品管理番号は英数字と「-」「_」のみ使用可能です'
		);
		$url = static::$settings['endpoint'] . '/2.0/items/manage-numbers/' . $manageNumber;
		return static::request(
			$url,
			array(
				'method'  => 'PATCH',
				'body'    => wp_unslash( $body ),
				'headers' => array(
					'Content-Type' => 'application/json',
				),
			),
		);
	}

	/**
	 * RMS WEB SERVICE : items.get
	 * この機能を利用すると、商品管理番号を指定し、商品情報を取得することができます。
	 * https://webservice.rms.rakuten.co.jp/merchant-portal/view/ja/common/1-1_service_index/itemapi2/getitem/
	 *
	 * @param string $manageNumber manageNumber
	 */
	public static function items_get( $manageNumber ) {
		static::check_fatal_error(
			preg_match( '/^[a-zA-Z0-9\-\_]*$/', $manageNumber ),
			'商品管理番号は英数字と「-」「_」のみ使用可能です'
		);
		$url = static::$settings['endpoint'] . '/2.0/items/manage-numbers/' . $manageNumber;
		return json_decode( static::request( $url )['body'], true );
	}
}
