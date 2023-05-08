<?php
/**
 * ふるさとチョイスの商品エクスポート専用
 * ふるさとチョイスTSVの仕様：https://steamship.docbase.io/posts/2917248
 * class-n2-item-export-furusato-choice.php
 * デバッグモード：admin-ajax.php?action=n2_item_export_furusato_choice&mode=debug
 *
 * @package neoneng
 */

if ( class_exists( 'N2_Item_Export_Furusato_Choice' ) ) {
	new N2_Item_Export_Furusato_Choice();
	return;
}

/**
 * N2_Item_Export_Base
 */
class N2_Item_Export_Furusato_Choice extends N2_Item_Export_Base {

	/**
	 * 設定
	 *
	 * @var array
	 */
	public $settings = array(
		'filename'      => 'n2_export_furusato_choice.tsv',
		'delimiter'     => "\t",
		'charset'       => 'utf-8',
		'header_string' => false,
	);



	/**
	 * ふるさとチョイスTSVヘッダーを取得
	 */
	protected function set_header() {
		global $n2;
		$auth = $n2->choice['auth'];
		$args = array(
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( "{$auth['user']}:{$auth['pass']}" ),
			),
		);
		// 取得
		$res = wp_remote_get( $auth['url'], $args );
		// TSVヘッダー本体
		$tsv_header = trim( $res['body'] );
		// TSVヘッダー配列化
		$this->data['header'] = explode( "\t", $tsv_header );
		/**
		 * [hook] n2_item_export_furusato_choice_set_header
		 */
		$this->data['header'] = apply_filters( mb_strtolower( get_class( $this ) ) . '_set_header', $this->data['header'] );
	}

	/**
	 * データのマッピング（正しい値かどうかここでチェックする）
	 * ふるさとチョイスTSVの仕様：https://steamship.docbase.io/posts/2917248
	 *
	 * @param string $val 項目名
	 * @param string $index インデックス
	 * @param array  $n2values n2dataのループ中の値
	 */
	protected function walk_values( &$val, $index, $n2values ) {
		// 最終的に入る項目の値（文字列）
		$data = '';
		// 必須などの部分を削除
		$val = preg_replace( '/^（.*?）/u', '', $val );
		switch ( $val ) {
			case 'お礼の品名':// 36文字以内(半角は0.5文字換算)
				$data = $n2values['タイトル'] ?? '';
				break;
			case 'サイト表示事業者名':// 64文字以内
				$data = $n2values['事業者名'] ?? '';
				break;
			case '必要寄付金額':// 半角数字
				$data = $n2values['寄附金額'] ?? 99999999;
				break;
			case '管理コード':
				$data = $n2values['返礼品コード'] ?? '';
				break;
			case 'キャッチコピー':// 40文字以内
				$data = $n2values['キャッチコピー'] ?? '';
				break;
			case '説明':// 1,000文字以内
				$data = $n2values['説明文'] ?? '';
				break;

			// 発送方法
			case '常温配送':// 常温配送に対応する場合は半角数字の1、対応しない場合は半角数字の0
			case '冷蔵配送':// 冷蔵配送に対応する場合は半角数字の1、対応しない場合は半角数字の0
			case '冷凍配送':// 冷凍配送に対応する場合は半角数字の1、対応しない場合は半角数字の0
				$data = false !== strpos( $val, $n2values['発送方法'] ) ? 1 : 0;
				break;

			// 包装・のし対応
			case '包装対応':// 包装に対応する場合は半角数字の1、対応しない場合は半角数字の0
			case 'のし対応':// のしに対応する場合は半角数字の1、対応しない場合は半角数字の0
				$data = false !== strpos( '有り', $n2values[ $val ] ) ? 1 : 0;
				break;

			// 0を設定
			case '容量単位':// グラムは半角数字の0、キログラムは半角数字の1、ミリリットルは半角数字の2、リットルは半角数字の3
			case '発送期日種別':// 決済から7日前後で発送の場合は1、決済から14日前後で発送の場合は2、決済から30日前後で発送の場合は3、任意入力の場合は0
			case '会員限定':// 会員限定に対応する場合は半角数字の1、対応しない場合は半角数字の0
			case 'チョイス限定':// チョイス限定のお礼の品の場合は半角数字の1、チョイス限定でない場合は半角数字の0
			case 'オンライン決済限定':// オンライン決済限定に対応する場合は半角数字の1、対応しない場合は半角数字の0
			case '配送業者':// 0:指定無し、1:ヤマト運輸、2:佐川急便、3:日本郵便、4:西濃運輸、5:福山通運、6:日本通運、7:佐川急便（6時間帯）、8:佐川急便（5時間帯）
			case '配達日種別':// 指定不可:0、月:1、旬:2、日:3
			case '配達日種別必須フラグ':// 配達時間指定を必ず寄付者に設定してほしい場合は1を、それ以外は0
			case '配達時間指定':// 指定できる:1、指定できない:0
			case '配達時間指定必須フラグ':// 配達時間指定を必ず寄付者に設定してほしい場合は1を、それ以外は0
				$data = 0;
				break;

			// 1を設定
			case '別送対応':// 別送に対応する場合は半角数字の1、対応しない場合は半角数字の0
			case 'ポイント情報表示有無':// ポイント情報を表示する場合は半角数字の1、ポイント情報を表示しない場合は半角数字の0
			case '即時交換可否':// 後日削除予定、半角数字の1
			case '表示有無':// 表示させる場合は半角数字の1、非表示にする場合は半角数字の0
			case '還元率入力有無':// 還元率を指定する場合は半角数字の1、還元率がわからないなど指定しない場合は半角数字の0
				$data = 1;
				break;
			// default:
			// 	$data = $val;
		}
		/**
		 * [hook] n2_item_export_base_walk_values
		 */
		$val = apply_filters( mb_strtolower( get_class( $this ) ) . '_walk_values', $data, $index );
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
		$str = preg_replace( '/\"{3,}/', '""', $str );
		/**
		 * [hook] n2_item_export_furusato_choice_special_str_convert
		 */
		$str = apply_filters( mb_strtolower( get_class( $this ) ) . '_special_str_convert', $str );
		return $str;
	}
}
