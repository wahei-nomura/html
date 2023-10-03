<?php
/**
 * 楽天カテゴリーのエクスポート
 * class-n2-item-export-rakuten-cat.php
 * デバッグモード：admin-ajax.php?action=n2_item_export_rakuten_cat&mode=debug
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
	 * 楽天CSVヘッダーを取得
	 */
	protected function set_header() {
		global $n2;
		// CSVヘッダー
		$this->data['header'] = $n2->settings['楽天']['csv_header']['item-cat'];
		/**
		 * [hook] n2_item_export_rakuten_cat_set_header
		 */
		$this->data['header'] = apply_filters( mb_strtolower( get_class( $this ) ) . '_set_header', $this->data['header'] );
	}

	/**
	 * データのマッピング（正しい値かどうかここでチェックする）
	 *
	 * @param string $val 項目名
	 * @param string $index インデックス
	 * @param array  $n2values n2dataのループ中の値
	 */
	protected function walk_values( &$val, $index, $n2values ) {
		global $n2;
		foreach ( explode( PHP_EOL, $n2values['楽天カテゴリー'] ) as $category ) {
			// preg_matchで判定
			$data[] = match ( $val ) {
				'コントロールカラム'      => 'n',
				'商品管理番号（商品URL）'  => mb_strtolower( $n2values['返礼品コード'] ),
				'表示先カテゴリ'          => $category,
				default => '',
			};
		}
		/**
		 * [hook] n2_item_export_rakuten_cat_walk_values
		 */
		$val = apply_filters( mb_strtolower( get_class( $this ) ) . '_walk_values', $data, $val, $n2values );
	}
}
