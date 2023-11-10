<?php
/**
 * RMS WEB SERVICE : items
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
			'hits'           => $hits >= 1 ? $hits : 100, // 1〜100（N2では0以下で全件取得）
			'isItemStockout' => $is_item_stockout,
		);
		$url    = static::$settings['endpoint'] . '/2.0/items/search?';
		$data   = wp_remote_get( $url . http_build_query( $params ), array( 'headers' => static::$data['header'] ) );

		if ( is_wp_error( $data ) ) {
			return array();
		}
		$data = json_decode( $data['body'], true );
		// $hitsが1以上の場合通常
		if ( $hits >= 1 ) {
			return $data;
		}
		// $hitsが1以下の場合は全件取得
		$pages            = ceil( $data['numFound'] / $params['hits'] );// ページ数を取得
		$params['offset'] = $params['offset'] + $params['hits'];// 最初のページは要らない
		while ( $pages > ceil( $params['offset'] / $params['hits'] ) ) {
			$res = wp_remote_get( $url . http_build_query( $params ), array( 'headers' => static::$data['header'] ) );
			// $data['results']に追加
			$data['results'] = array(
				...$data['results'],
				...json_decode( $res['body'], true )['results'],
			);
			$params['offset'] = $params['offset'] + $params['hits'];
		}
		return $data;
	}
}
