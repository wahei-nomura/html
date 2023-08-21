<?php
/**
 * class-n2-ana-items-api.php
 *
 * @package neoneng
 */

if ( class_exists( 'N2_ANA_Items_API' ) ) {
	new N2_ANA_Items_API();
	return;
}

/**
 * 寄附金額の計算のためのAPI
 */
class N2_ANA_Items_API {

	/**
	 * option_name
	 *
	 * @var string
	 */
	private $option_name = 'n2_ana_items_api';

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		add_action( 'wp_ajax_n2_ana_items_api_update', array( $this, 'update' ) );
		add_action( 'wp_ajax_n2_ana_items_api', array( $this, 'get' ) );
		add_action( 'wp_ajax_nopriv_n2_ana_items_api', array( $this, 'get' ) );
		if ( ! wp_next_scheduled( 'wp_ajax_n2_ana_items_api_update' ) ) {
			wp_schedule_event( time() + 200, 'hourly', 'wp_ajax_n2_ana_items_api_update' );
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
		// すでに本日分がアップデートされていたら中止
		$data = get_option( $this->option_name );
		if ( $data['update'] > wp_date( 'Y-m-d' ) ) {
			echo '既に本日分をアップデート済のため処理を中止します。';
			exit;
		}
		$before = microtime( true );
		global $n2;
		// 自治体コードを自動取得
		$url    = 'https://furusato.ana.co.jp/products/list.php?';
		$params = array(
			'q'     => $n2->town,
			'limit' => 90,
			'o'     => 0,
		);
		// データ取得を試みる
		$data = wp_remote_get( $url . http_build_query( $params ) );
		if ( 200 !== $data['response']['code'] ) {
			echo 'ANAのふるさと納税の返礼品データ取得失敗';
			exit;
		}
		$data = $data['body'];

		// DOMDocument
		$dom_document = new \DOMDocument();
		@$dom_document->loadHTML( mb_convert_encoding( $data, 'HTML-ENTITIES', 'UTF-8' ) );
		$xml_object = simplexml_import_dom( $dom_document );
		// ページ数算出
		$num_result = $xml_object->xpath( '//span[@class="total_quantity"]' )[0]->__toString();
		$num_result = trim( str_replace( ',', '', $num_result ) );

		// 配列に突っ込む
		$data = $this->array_format( $xml_object );
		// マルチcURL
		$mh       = curl_multi_init();
		$ch_array = array();
		while ( $num_result >= $params['o'] ) {
			$ch         = curl_init();
			$ch_array[] = $ch;
			$options = array(
				CURLOPT_URL            => $url . http_build_query( $params ),
				CURLOPT_HEADER         => false,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_TIMEOUT        => 30,
			);
			curl_setopt_array( $ch, $options );
			curl_multi_add_handle( $mh, $ch );
			$params['o'] = $params['o'] + 90;
		}
		do {
			curl_multi_exec( $mh, $running );
			curl_multi_select( $mh );
		} while ( $running > 0 );

		foreach ( $ch_array as $ch ) {
			@$dom_document->loadHTML( mb_convert_encoding( curl_multi_getcontent( $ch ), 'HTML-ENTITIES', 'UTF-8' ) );
			$xml_object = simplexml_import_dom( $dom_document );
			$data       = array( ...$data, ...$this->array_format( $xml_object ) );
			curl_multi_remove_handle( $mh, $ch );
			curl_close( $ch );
		}
		curl_multi_close( $mh );
		$data = array(
			'update' => date_i18n( 'Y-m-d H:i:s' ),
			'data'   => array_unique( $data, SORT_REGULAR ),
		);
		update_option( $this->option_name, $data );
		echo 'n2_furusato_choice_items_api「' . get_bloginfo( 'name' ) . 'のANAのふるさと納税出品中」の返礼品データを保存しました（' . number_format( microtime( true ) - $before, 2 ) . ' 秒）';
		exit;
	}

	/**
	 * データ配列の型の変換
	 *
	 * @param array $xml_object xmlオブジェクト
	 */
	private function array_format( $xml_object ) {
		global $n2;
		$arr = array();
		// 返礼品情報を抜く
		foreach ( $xml_object->xpath( '//div[@class="as-product_detail_link"]' ) as $k => $v ) {
			if ( ! preg_match( '{' . $n2->town . '}', $v['data-product-pref'] ) || ! empty( $v->section->div->div ) ) {
				continue;
			}
			$arr[ $k ] = array(
				'goods_name'  => $v['data-product-name']->__toString(),
				'goods_g_num' => preg_replace( '/.*?\?product_code\=/', '', $v['data-product-url'] ),
				'goods_price' => $v['data-product-price']->__toString(),
				'url'         => "https://furusato.ana.co.jp{$v['data-product-url']}",
				'image'       => "https://furusato.ana.co.jp{$v->section->div->img['src']}",
			);
		}
		return $arr;
	}

}
