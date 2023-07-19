<?php
/**
 * RMS商品API
 *
 * @package neoneng
 */

if ( class_exists( 'N2_Rakuten_Items_API' ) ) {
	new N2_Rakuten_Items_API();
	return;
}

/**
 * 寄附金額の計算のためのAPI
 */
class N2_Rakuten_Items_API {

	/**
	 * option_name
	 *
	 * @var string
	 */
	private $option_name = 'n2_rakuten_items_api';

	/**
	 * RMSのキー取得の為のスプレットシートID
	 *
	 * @var string
	 */
	private $sheetid = '1FrFJ7zog1WUCsiREFOQ2pGAdhDYveDgBmGdaITrWeCo';

	/**
	 * RMSのキー取得の為のスプレットシート範囲
	 *
	 * @var string
	 */
	private $range = 'RMS_API';

	/**
	 * 楽天ショップコード
	 *
	 * @var string
	 */
	private $shop_code = '';

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		add_action( 'wp_ajax_n2_rakuten_items_api_update', array( $this, 'update' ) );
		add_action( 'wp_ajax_n2_rakuten_items_api', array( $this, 'get' ) );
		add_action( 'wp_ajax_nopriv_n2_rakuten_items_api', array( $this, 'get' ) );
		if ( ! wp_next_scheduled( 'wp_ajax_n2_rakuten_items_api_update' ) ) {
			wp_schedule_event( time() + 200, 'hourly', 'wp_ajax_n2_rakuten_items_api_update' );
		}
	}

	/**
	 * 外部用API
	 */
	public function get() {
		$data = get_option( $this->option_name );
		$data = wp_json_encode( $data, JSON_UNESCAPED_UNICODE );
		header( 'Content-Type: application/json; charset=utf-8' );
		echo $data;
		exit;
	}

	/**
	 * APIデータのアップデート
	 */
	public function update() {
		$before = microtime( true );
		$header = $this->set_api_keys();
		// 店舗URL取得
		$data                  = wp_remote_get( 'https://api.rms.rakuten.co.jp/es/1.0/shop/shopMaster', array( 'headers' => $header ) );
		$this->shop_code = simplexml_load_string( $data['body'] )->result->shopMaster->url->__toString();
		// 商品検索API
		$url    = 'https://api.rms.rakuten.co.jp/es/2.0/items/search?';
		$params = array(
			'isItemStockout' => 'false',
			'hits'           => 100,
			'offset'         => 0,
		);
		// データ取得を試みる
		$data = wp_remote_get( $url . http_build_query( $params ), array( 'headers' => $header ) );
		$data = json_decode( $data['body'], true );
		if ( ! $data['numFound'] ) {
			echo '認証エラー';
			exit;
		}
		// hitsに応じてページ数を取得
		$pages = ceil( $data['numFound'] / $params['hits'] );
		$data  = array( ...$data['results'] );
		$data  = array_map( array( $this, 'array_format' ), $data );
		// 最初のページは要らない
		$params['offset'] = $params['offset'] + $params['hits'];
		while ( $pages > ceil( $params['offset'] / $params['hits'] ) ) {
			$res  = wp_remote_get( $url . http_build_query( $params ), array( 'headers' => $header ) );
			$res  = json_decode( $res['body'], true );
			$res  = array_map( array( $this, 'array_format' ), $res['results'] );
			$data = array( ...$data, ...$res );
			// オフセット更新
			$params['offset'] = $params['offset'] + $params['hits'];
		}
		$data = array(
			'update' => date_i18n( 'Y-m-d H:i:s' ),
			'data'   => array_unique( $data, SORT_REGULAR ),
		);
		update_option( $this->option_name, $data );
		echo 'N2_Rakuten_Items_API「' . get_bloginfo( 'name' ) . 'の楽天出品中」の返礼品データを保存しました（' . number_format( microtime( true ) - $before, 2 ) . ' 秒）';
		exit;
	}

	/**
	 * RMSのAPIキーをスプシから取得してセット
	 */
	private function set_api_keys() {
		global $n2, $n2_sync;
		$keys = $n2_sync->get_spreadsheet_data( $this->sheetid, $this->range );
		$keys = array_filter( $keys, fn( $v ) => $v['town'] === $n2->town );
		$keys = call_user_func_array( 'array_merge', $keys );
		// base64_encode
		$authkey = base64_encode( "{$keys['serviceSecret']}:{$keys['licenseKey']}" );
		return array(
			'Authorization' => "ESA {$authkey}",
		);
	}

	/**
	 * データ配列の型の変換
	 *
	 * @param array $v RMSAPIから取得した生配列
	 */
	private function array_format( $v ) {
		return array(
			'goods_name'  => $v['item']['title'],
			'goods_g_num' => $v['item']['itemNumber'],
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
