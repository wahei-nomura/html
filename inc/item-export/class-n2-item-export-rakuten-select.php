<?php
/**
 * 楽天selectの商品エクスポート専用
 * 楽天selectCSVの仕様：https://steamship.docbase.io/posts/2917248
 * class-n2-item-export-rakuten-select.php
 * デバッグモード：admin-ajax.php?action=n2_item_export_rakuten_select&mode=debug
 *
 * @package neoneng
 */

if ( class_exists( 'N2_Item_Export_Rakuten_Select' ) ) {
	new N2_Item_Export_Rakuten_Select();
	return;
}

/**
 * N2_Item_Export_Rakuten_Select
 */
class N2_Item_Export_Rakuten_Select extends N2_Item_Export_Base {

	/**
	 * 設定
	 *
	 * @var array
	 */
	public $settings = array(
		'filename'      => 'select.csv',
		'delimiter'     => ',',
		'charset'       => 'sjis',
		'header_string' => '',
	);

	/**
	 * 楽天selectCSVヘッダーを取得
	 */
	protected function set_header() {
		global $n2;
		$this->data['header'] = $n2->portal_setting['楽天']['csv_header']['select'];
		/**
		 * [hook] n2_item_export_rakuten_select_set_header
		 */
		$this->data['header'] = apply_filters( mb_strtolower( get_class( $this ) ) . '_set_header', $this->data['header'] );
	}

	/**
	 * データのマッピング（正しい値かどうかここでチェックする）
	 * 楽天selectCSVの仕様：https://steamship.docbase.io/posts/2917248
	 *
	 * @param string $val 項目名
	 * @param string $index インデックス
	 * @param array  $n2values n2dataのループ中の値
	 */
	protected function walk_values( &$val, $index, $n2values ) {
		global $n2;
		$selects = $n2->portal_setting['楽天']['select'];
		$selects = str_replace( array( "\r\n", "\r" ), "\n", $selects );// 改行コード統一
		$selects = preg_split( '/\n{2,}/', $selects );// 連続改行で分ける
		foreach ( $selects as $select ) {
			$select = explode( "\n", $select );
			$name   = array_shift( $select );
			foreach ( $select as $value ) {
				$data[] = match ( $val ) {
					'項目選択肢用コントロールカラム' => 'n',// n：新規登録 u：更新(変更） d：削除 全角・大文字を半角に自動的に変換。 ([3]がs/c/fの場合、更新（u）は利用できません。該当選択肢を削除（d）をしてから新規登録（n）してください。)
					'商品管理番号（商品URL）' => mb_strtolower( trim( $n2values['返礼品コード'] ) ),// 商品登録用CSVファイルで登録されたものが存在していない場合はエラーです。先に商品登録してください。32byteまで。
					'選択肢タイプ' => 's',// s：セレクトボックス　c：チェックボックス　f：フリーテキスト　i：項目選択肢別在庫　全角・大文字を半角に自動的に変換。
					'項目選択肢項目名' => $name,// 255byteまで。
					'項目選択肢' => $value,// 32byteまで。
					'項目選択肢選択必須' => 1,// 空欄可。0：選択必須としない 1：選択必須とする
					default => '',
				};
			}
		}
		/**
		 * [hook] n2_item_export_rakuten_select_walk_values
		 */
		$val = apply_filters( mb_strtolower( get_class( $this ) ) . '_walk_values', $data, $val, $n2values );
	}

	/**
	 * エラーチェック
	 *
	 * @param string $value 項目値
	 * @param string $name 項目名
	 * @param array  $n2values n2dataのループ中の値
	 *
	 * @return $value
	 */
	public function check_error( $value, $name, $n2values ) {
		return $value;
	}
	/**
	 * 文字列の置換
	 *
	 * @param string $str 文字列
	 * @return string $str 置換後の文字列
	 */
	protected function special_str_convert( $str ) {
		global $n2;
		$str = str_replace( array_keys( $n2->special_str_convert ), array_values( $n2->special_str_convert ), $str );
		/**
		 * [hook] n2_item_export_rakuten_select_special_str_convert
		 */
		$str = apply_filters( mb_strtolower( get_class( $this ) ) . '_special_str_convert', $str );
		return $str;
	}
}
