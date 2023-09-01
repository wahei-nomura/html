<?php
/**
 * 楽天の商品エクスポート専用
 * 楽天CSVの仕様：https://steamship.docbase.io/posts/2774108
 * class-n2-item-export-rakuten-item.php
 * デバッグモード：admin-ajax.php?action=n2_item_export_rakuten&mode=debug
 *
 * @package neoneng
 */

if ( class_exists( 'N2_Item_Export_Rakuten_Cat' ) ) {
	new N2_Item_Export_Rakuten_Cat();
	return;
}

/**
 * N2_Item_Export_Base
 */
class N2_Item_Export_Rakuten_Cat extends N2_Item_Export_Base {

	/**
	 * 設定
	 *
	 * @var array
	 */
	public $settings = array(
		'filename'      => 'item-cat.csv',
		'delimiter'     => ',',
		'charset'       => 'sjis',
		'header_string' => '', // 基本は自動設定、falseでヘッダー文字列無し
	);

	/**
	 * RMS
	 *
	 * @var array
	 */
	private $rms = array(
		'header'       => null,
		'cabinet'      => array(),
		'use_api'      => null,
		'ignore_error' => false,
		'image_error'  => false,
	);

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * 楽天CSVヘッダーを取得
	 */
	protected function set_header() {
		global $n2;
		// CSVヘッダー
		$this->data['header'] = $n2->settings['楽天']['csv_header']['item-cat'];
		/**
		 * [hook] n2_item_export_rakuten_set_header
		 */
		$this->data['header'] = apply_filters( mb_strtolower( get_class( $this ) ) . '_set_header', $this->data['header'] );
	}

	/**
	 * 楽天用の内容を配列で作成
	 */
	protected function set_data() {
		global $n2;
		$data = array();

		$item_code_list = array_map(
			function( $item ) {
				return mb_strtolower( $item['返礼品コード'] );
			},
			$this->data['n2data'],
		);
		// 事業者コード一覧
		$item_code_list = array_unique( $item_code_list );

		// $this->check_fatal_error( $this->data['header'], 'ヘッダーが正しくセットされていません' );
		foreach ( $this->data['n2data'] as $key => $values ) {
			$id = $values['id'];
			$this->can_use_api();
			$categories       = $this->get_category_info( $values['返礼品コード'] );
			$categories_count = count( $categories );
			for ( $i = 0; $i < $categories_count; $i++ ) { // カテゴリの個数分まわす
				// $values['category_title'] = $categories[ $i ]['title'];
				$values_category_hierarchy = $this->make_category_hierarchy( $categories[ $i ] );
				$values['category_title']  = $values_category_hierarchy . $categories[ $i ]['title'];
				// ヘッダーをセット
				$data[ $id + $i ] = $this->data['header'];
				array_walk( $data[ $id + $i ], array( $this, 'walk_values' ), $values );
				$data[ $id + $i ] = array_combine( $this->data['header'], $data[ $id + $i ] );
			}
		}
		/**
		 * [hook] n2_item_export_base_set_data
		 */
		$data = apply_filters( mb_strtolower( get_class( $this ) ) . '_set_data', $data );
		// エラーは排除
		$data = array_diff_key( $data, $this->data['error'] );
		$data = array_values( $data );
		// dataをセット
		$this->data['data'] = $data;
	}

	/**
	 * データのマッピング（正しい値かどうかここでチェックする）
	 * 楽天CSVの仕様：https://steamship.docbase.io/posts/2774108
	 *
	 * @param string $val 項目名
	 * @param string $index インデックス
	 * @param array  $n2values n2dataのループ中の値
	 */
	protected function walk_values( &$val, $index, $n2values ) {
		global $n2;
		// preg_matchで判定
		$data = match ( 1 ) {
			preg_match( '/^コントロールカラム$/', $val )  => 'n',
			preg_match( '/^商品管理番号（商品URL）$/', $val )  => mb_strtolower( $n2values['返礼品コード'] ),
			preg_match( '/^表示先カテゴリ$/', $val )  => $n2values['category_title'],
				default => '',
		};
		/**
		 * [hook] n2_item_export_rakuten_walk_values
		 */
		$val = apply_filters( mb_strtolower( get_class( $this ) ) . '_walk_values', $data, $val, $n2values );
	}


	/**
	 * RMS APIが使えるか判定
	 */
	protected function can_use_api() {
		if ( null === $this->rms['use_api'] ) {
			$this->rms['use_api'] = N2_RMS_Category_API::ajax(
				array(
					'request' => 'connect',
					'mode'    => 'func',
				),
			);
		}
		return $this->rms['use_api'];
	}
	/**
	 * カテゴリ情報取得
	 *
	 * @param string $control_number 商品管理番号
	 *
	 * @return string
	 */
	public static function get_category_info( $control_number ) {
		$category_api   = new N2_RMS_Category_API();
		$category_all   = $category_api->categories_get( $control_number );
		$category_array = $category_all['categories'];
		// print_r($category_array);
		return $category_array;
	}
	/**
	 * カテゴリの階層生成
	 *
	 * @param string $category_info カテゴリ情報
	 *
	 * @return string
	 */
	protected function make_category_hierarchy( $category_info ) {
		$category_hierarchy   = '';
		$category_breadcrumbs = $category_info['breadcrumbs']['breadcrumbList'];
		if ( empty( $category_breadcrumbs ) ) {
			$category_hierarchy = 'nodata';
			return $category_hierarchy;
		}
		foreach ( $category_breadcrumbs as $key => $cat_bc ) {
			$category_hierarchy .= $cat_bc['title'] . '\\';
		}
		return $category_hierarchy;
	}
}
