<?php
/**
 * LedgHOMEの商品エクスポート専用
 * LedgHOMECSVの仕様：https://steamship.docbase.io/posts/2917248
 * class-n2-item-export-ledghome.php
 * デバッグモード：admin-ajax.php?action=n2_item_export_ledghome&mode=debug
 *
 * @package neoneng
 */

if ( class_exists( 'N2_Item_Export_Ledghome' ) ) {
	new N2_Item_Export_Ledghome();
	return;
}

/**
 * N2_Item_Export_Ledghome
 */
class N2_Item_Export_Ledghome extends N2_Item_Export_Base {

	/**
	 * 設定
	 *
	 * @var array
	 */
	public $settings = array(
		'filename'      => 'n2_export_ledghome.csv',
		'delimiter'     => ',',
		'charset'       => 'sjis',
		'header_string' => '"LedgHOMEクラウド謝礼品リスト"' . PHP_EOL,
	);

	/**
	 * LedgHOMECSVヘッダーを取得
	 */
	protected function set_header() {
		global $n2;
		// CSVヘッダー配列化
		$this->data['header'] = $n2->portal_setting['LedgHOME']['csv_header'];
		/**
		 * [hook] n2_item_export_ledghome_set_header
		 */
		$this->data['header'] = apply_filters( mb_strtolower( get_class( $this ) ) . '_set_header', $this->data['header'] );
	}

	/**
	 * データのマッピング（正しい値かどうかここでチェックする）
	 * LedgHOMECSVの仕様：https://steamship.docbase.io/posts/2917248
	 *
	 * @param string $val 項目名
	 * @param string $index インデックス
	 * @param array  $n2values n2dataのループ中の値
	 */
	protected function walk_values( &$val, $index, $n2values ) {
		global $n2;
		$data = array();
		for ( $i = 1; $i <= $n2values['定期便']; $i++ ) {
			// 返礼品コード
			$item_code = $n2values['返礼品コード'] . ( $n2values['定期便'] > 1 ? "_{$i}/{$n2values['定期便']}" : '' );
			// データ配列
			$data[ $i ] = match ( $val ) {
				'謝礼品番号' => $item_code,// 定期便の場合は「_1/12」をつける
				'謝礼品名' => "{$item_code} " . ( $n2values['LH表示名'] ?: $n2values['タイトル'] ),// LH表示名 > タイトル
				'配送名称' => "{$item_code} " . ( $n2values['配送伝票表示名'] ?: $n2values['タイトル'] ),// 配送伝票表示名 > タイトル
				'ふるさとチョイス名称' => "{$n2values['タイトル']} [{$n2values['返礼品コード']}]",// タイトル [返礼品コード]
				'楽天名称' => "【ふるさと納税】{$n2values['タイトル']} [{$n2values['返礼品コード']}]",// 【ふるさと納税】タイトル [返礼品コード]
				'事業者' => $n2values['事業者名'],
				'謝礼品カテゴリー' => $n2values['LHカテゴリー'],
				'セット内容' => $n2values['内容量・規格等'],
				'謝礼品紹介文' => $n2values['説明文'],
				'ステータス' => '受付中',
				'状態' => '表示',
				'寄附設定金額' => $i > 1 ? 0 : $n2values['寄附金額'],// 定期便の場合は１回目のみ
				'価格（税込み）' => $n2values['価格'],// 1回目に全部含めるかどうかの設定値を使う（まだ設定できるとこが無い）
				'送料' => $n2values['送料'],
				'送料反映' => match ( $n2values['発送サイズ'] ) {
					'その他' => '反映する',
					'レターパックプラス', 'レターパックライト' => $n2->portal_setting['LedgHOME']['レターパック送料反映'],// option設定できるようにする
					default => '反映しない',
				},
				'発送方法' => $n2values['発送方法'],
				'取り扱い方法' => implode( ',', (array) $n2values['取り扱い方法'] ),
				'申込可能期間' => '通年',
				'自由入力欄1' => wp_date( 'Y/m/d' ) . "：{$n2->current_user->data->display_name}",
				'自由入力欄2' => $n2values['送料'],
				'配送サイズコード' => is_numeric( $n2values['発送サイズ'] ) ? $n2values['発送サイズ'] : '',
				'地場産品類型' => implode( 'ー', mb_str_split( mb_convert_kana( $n2values['地場産品類型'], 'KA' ) ) ),// 全角（「８ーイ」形式）
				'類型該当理由' => $n2values['類型該当理由'],
				default => '',
			};
		}
		/**
		 * [hook] n2_item_export_ledghome_walk_values
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
		foreach ( (array) $value as $num => $val ) {
			// 定期便の一回目以降はこれ以下の処理はしない
			if ( $num > 1 ) {
				continue;
			}
			// SS的必須漏れエラー
			if ( preg_match( '/謝礼品番号|事業者|価格（税込み）|寄附設定金額/', $name ) && empty( $val ) ) {
				$this->add_error( $n2values['id'], "「{$name}」がありません。" );
			}
		}
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
		 * [hook] n2_item_export_ledghome_special_str_convert
		 */
		$str = apply_filters( mb_strtolower( get_class( $this ) ) . '_special_str_convert', $str );
		return $str;
	}
}
