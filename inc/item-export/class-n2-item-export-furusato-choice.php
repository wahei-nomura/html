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
	 * データのマッピング（基本的に拡張で上書きする）
	 * ふるさとチョイスTSVの仕様：https://steamship.docbase.io/posts/2917248
	 *
	 * @param string $val 項目名
	 * @param string $index インデックス
	 * @param string $values n2dataのループ中の値
	 */
	protected function walk_values( &$val, $index, $values ) {
		$data = '';
		// 必須などの部分を削除
		$val = preg_replace( '/^（.*?）/u', '', $val );
		switch ( $val ) {
			case 'お礼の品名':
				$data = $values['タイトル'] ?? '';
				break;
			case 'サイト表示事業者名':
				$data = $values['事業者名'] ?? '';
				break;
			case '必要寄付金額':
				$data = $values['寄附金額'] ?? 99999999;
				break;
			case '管理コード':
				$data = $values['返礼品コード'] ?? '';
				break;
			case 'キャッチコピー':
				$data = $values['キャッチコピー'] ?? '';
				break;
			case '説明':
				$data = $values['説明文'] ?? '';
				break;
			case '容量単位':
			case '発送期日種別':
			case '会員限定':
			case 'チョイス限定':
			case 'オンライン決済限定':
			case '配送業者':
			case '配達日種別':
			case '配達日種別必須フラグ':
			case '配達時間指定':
			case '配達時間指定必須フラグ':
				$data = 0;
				break;
			case '別送対応':
			case 'ポイント情報表示有無':
			case '即時交換可否':
			case '表示有無':
			case '還元率入力有無':
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
