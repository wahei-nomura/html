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
	 *
	 * N2設定にmunicipalidがあればそれを利用、なければ取得しN2設定にセットする
	 * municipalidが取れたら、１回目にページ数を取得するためシングルアクセス
	 * ページ数確定後、N2のマルチリクエストAPIで並列で取得
	 * 取得した配列を整形し、wp_insert_postでDB保存
	 */
	public function update() {
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
		$data = $data['body'];
		$data = json_decode( $data, true );
		// ページ数算出
		$num_result    = $data['ProductCount'];
		$max_num_pages = ceil( $num_result / 100 );
		// 配列に突っ込む
		$data = $this->array_format( $data['ProductData'] );

		// マルチcURL
		$ch_array = array();
		$requests = array();
		$params['pageno']++;
		while ( $max_num_pages >= $params['pageno'] ) {
			$requests[] = array(
				'url' => $url . http_build_query( $params ),
			);
			$params['pageno']++;
		}
		$response = N2_Multi_URL_Request_API::request_multiple( $requests );
		$response = array_map( fn( $v ) => json_decode( $v->body, true ), $response );
		foreach ( $response as $res ) {
			if ( empty( $res ) ) {
				continue;
			}
			$res  = $this->array_format( $res['ProductData'] );
			$data = array(
				...$data,
				...$res,
			);
		}
		$data = array_unique( $data, SORT_REGULAR );
		// goods_g_numでソートする
		array_multisort( array_column( $data, 'goods_g_num' ), SORT_ASC, $data );
		$data = array_values( $data );
		$args = array(
			'post_type'    => 'portal_item_data',
			'post_title'   => 'furunavi',
			'post_content' => wp_json_encode( $data, JSON_UNESCAPED_UNICODE ),
		);
		$ids  = get_posts( 'title=furunavi&fields=ids&post_type=portal_item_data&post_status=any' );
		if ( empty( $ids ) ) {
			$id = wp_insert_post( $args );
			// ログ追加
			if ( ! is_wp_error( $id ) ) {
				wp_save_post_revision( $id );// 初回リビジョンの登録
			}
		} else {
			$args['ID'] = $ids[0];
			$id         = wp_update_post( $args );
		}
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
