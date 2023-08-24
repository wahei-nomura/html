<?php
/**
 * class-n2-furunavi-items-api.php
 *
 * @package neoneng
 */

if ( class_exists( 'N2_Furunavi_Items_API' ) ) {
	new N2_Furunavi_Items_API();
	return;
}

/**
 * 寄附金額の計算のためのAPI
 */
class N2_Furunavi_Items_API {

	/**
	 * option_name
	 *
	 * @var string
	 */
	private $option_name = 'n2_furunavi_items_api';

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		add_action( 'wp_ajax_n2_furunavi_items_api_update', array( $this, 'update' ) );
		add_action( 'wp_ajax_n2_furunavi_items_api', array( $this, 'get' ) );
		add_action( 'wp_ajax_nopriv_n2_furunavi_items_api', array( $this, 'get' ) );
		add_filter( 'n2_update_price_chonbo', array( $this, 'add_price_chonbo' ) );
		if ( ! wp_next_scheduled( 'wp_ajax_n2_furunavi_items_api_update' ) ) {
			wp_schedule_event( time() + 200, 'hourly', 'wp_ajax_n2_furunavi_items_api_update' );
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
		$url    = 'https://furunavi.jp/get_product_list.ashx?';
		$params = array(
			'keyword'  => $n2->town,
			'pagesize' => 100,
			'pageno'   => 1,
		);
		// データ取得を試みる
		$data = wp_remote_get( $url . http_build_query( $params ) );
		if ( 200 !== $data['response']['code'] ) {
			echo 'ふるなびの返礼品データ取得失敗';
			exit;
		}
		$data = $data['body'];
		$data = json_decode( $data, true );

		// ページ数算出
		$num_result    = $data['ProductCount'];
		$max_num_pages = ceil( $num_result / 100 );
		// 配列に突っ込む
		$data = $this->array_format( $data['ProductData'] );

		// マルチcURL
		$mh       = curl_multi_init();
		$ch_array = array();
		$params['pageno']++;
		while ( $max_num_pages >= $params['pageno'] ) {
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
			$params['pageno']++;
		}
		do {
			curl_multi_exec( $mh, $running );
			curl_multi_select( $mh );
		} while ( $running > 0 );

		foreach ( $ch_array as $ch ) {
			$res  = curl_multi_getcontent( $ch );
			$res  = json_decode( $res, true );
			$res  = $this->array_format( $res['ProductData'] );
			$data = array(
				...$data,
				...$res,
			);
			curl_multi_remove_handle( $mh, $ch );
			curl_close( $ch );
		}
		curl_multi_close( $mh );
		$data = array(
			'update' => date_i18n( 'Y-m-d H:i:s' ),
			'data'   => array_unique( $data, SORT_REGULAR ),
		);
		update_option( $this->option_name, $data );
		echo 'n2_furunavi_items_api「' . get_bloginfo( 'name' ) . 'のふるなび出品中」の返礼品データを保存しました（' . number_format( microtime( true ) - $before, 2 ) . ' 秒）';
		exit;
	}

	/**
	 * データの浄化＆配列の型の変換
	 *
	 * @param array $v 配列
	 */
	private function array_format( $v ) {
		global $n2;
		// 自治体のものだけにデータの浄化
		$v = array_values( array_filter( $v, fn( $d ) => $d['MunicipalName'] === $n2->town && 0 !== $d['Stock'] ) );
		$v = array_map(
			function( $d ) {
				preg_match( '/\[([A-Z]{2,3}[0-9]{2,3})\]/', $d['ProductName'], $m );
				return array(
					'goods_name'  => $d['ProductName'],
					'goods_g_num' => $m[1],
					'goods_price' => preg_replace( '/[^0-9]/', '', $d['LowerAmount'] ),
					'url'         => "https://furunavi.jp/product_detail.aspx?pid={$d['ProductID']}",
					'image'       => "https://cf.furunavi.jp/product_images/800/{$d['ProductID']}/1.jpg",
				);
			},
			$v
		);
		return $v;
	}

	/**
	 * 価格チョンボのDBに追加
	 *
	 * @param array $items_apis 対象のポータルAPI
	 */
	public function add_price_chonbo( $items_apis ) {
		$items_apis[ $this->option_name ] = 'ふるなび';
		return $items_apis;
	}
}
