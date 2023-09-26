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
		$auth = $n2->settings['ふるさとチョイス']['tsv_header'];
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
		global $n2;
		$choice_settings = $n2->settings['ふるさとチョイス'];

		// 注意書き
		{
			$warning = array();
			// やきものの対応機器
			if ( in_array( 'やきもの', $n2values['商品タイプ'], true ) ) {
				$warning['やきもの対応機器'] = array( '電子レンジ対応', 'オーブン対応', '食洗機対応' );
				foreach ( $warning['やきもの対応機器'] as $key => $value ) {
					$warning['やきもの対応機器'][ $key ] = $value . $n2values[ $value ];
				}
				$warning['やきもの対応機器']  = implode( ' / ', $warning['やきもの対応機器'] );
				$warning['やきもの対応機器'] .= "\n{$n2values['対応機器備考']}";
			}
			// 商品タイプごとの注意書きを追加
			foreach ( array_filter( $n2values['商品タイプ'] ) as $type ) {
				$warning[ $type ] = wp_strip_all_tags( $n2->settings['注意書き'][ $type ] ?? '' );
			}
			$warning['共通'] = wp_strip_all_tags( $n2->settings['注意書き']['共通'] );
			// 浄化
			$warning = array_filter( array_values( $warning ) );
		}
		// 説明文
		{
			$n2values['説明文'] = array(
				$n2values['説明文'],
				$n2values['検索キーワード'] ?? '',
			);
			// 空要素削除して連結
			$n2values['説明文'] = implode( "\n\n", array_filter( $n2values['説明文'] ) );
		}

		// 内容量・規格等
		{
			$n2values['内容量・規格等'] = array(
				$n2values['内容量・規格等'],
				$n2values['原料原産地'] ? "【原料原産地】\n{$n2values['原料原産地']}" : '',
				$n2values['加工地'] ? "【加工地】\n{$n2values['加工地']}" : '',
				...$warning,
			);
			// 空要素削除して連結
			$n2values['内容量・規格等'] = implode( "\n\n", array_filter( $n2values['内容量・規格等'] ) );
		}

		// アレルゲン
		{
			$n2values['アレルゲン'] = (array) $n2values['アレルゲン'];
			$n2values['アレルゲン'] = preg_replace( '/（.*?）/', '', $n2values['アレルゲン'] );// 不純物（カッコの部分）を削除
		}

		// 賞味期限・消費期限
		{
			$n2values['消費期限'] = array(
				$n2values['賞味期限'] ? "【賞味期限】\n{$n2values['賞味期限']}" : '',
				$n2values['消費期限'],
			);
			// 空要素削除して連結
			$n2values['消費期限'] = implode( "\n\n【消費期限】\n", array_filter( $n2values['消費期限'] ) );
		};

		// preg_matchで判定
		$data = match ( 1 ) {
			preg_match( '/^管理コード$/', $val )  => $n2values['返礼品コード'],
			preg_match( '/^（必須）お礼の品名$/', $val ) => "{$n2values['タイトル']} [{$n2values['返礼品コード']}]",// * 200文字以内
			preg_match( '/^サイト表示事業者名$/', $val )  => $this->get_author_name( $n2values ),// 64文字以内
			preg_match( '/必要寄付金額$/', $val )  => $n2values['寄附金額'],// * 半角数字
			preg_match( '/（条件付き必須）ポイント$/', $val ) => match ( $choice_settings['ポイント導入'] ?? false ) {
				'導入する' => $n2values['価格'] * $n2values['定期便'],// 必要ポイントを半角数字で入力してください。
				default => '',
			},
			preg_match( '/^説明$/', $val )  => $n2values['説明文'],// 1,000文字以内
			preg_match( '/^キャッチコピー$/', $val )  => $n2values['キャッチコピー'],// 40文字以内
			preg_match( '/^容量$/', $val )  => $n2values['内容量・規格等'],// お礼の品の容量情報を1,000文字以内で入力
			preg_match( '/(お礼の品|スライド)画像[1]*$/', $val ) => mb_strtolower( $n2values['返礼品コード'] ) . '.jpg',// お礼の品画像のファイル名
			preg_match( '/スライド画像([2-8]{1})$/u', $val, $m ) => mb_strtolower( $n2values['返礼品コード'] ) . '-' . ( $m[1] - 1 ) . '.jpg',// スライド画像のファイル名を指定
			preg_match( '/品梱包画像$/u', $val ) => $n2->town . '-注意書き.jpg',// 〇〇(市|町|県)-注意書き.jpg
			preg_match( '/^申込期日$/', $val ) => $n2values['申込期間'],// お礼の品の申込期日情報を1,000文字以内で入力　〇〇(市|町|県)-注意書き.jpg
			preg_match( '/^発送期日$/', $val ) => $n2values['配送期間'],// 発送期日種別が任意入力の場合はお礼の品の発送期日情報を1,000文字以内で入力
			preg_match( '/アレルギー：([^（]*)/u', $val, $m ) => in_array( $m[1], $n2values['アレルゲン'], true ) ? 1 : 2,// アレルギー品目がありの場合は半角数字の1、なしの場合は半角数字の2、未確認の場合は半角数字の3
			preg_match( '/アレルギー特記事項/', $val ) => $n2values['アレルゲン注釈'],// アレルギーに関する注意情報を1,000文字以内で入力
			preg_match( '/地場産品類型番号/', $val )  => $n2values['地場産品類型'] ? "{$n2values['地場産品類型']}|{$n2values['類型該当理由']}" : '',// 設定したい地場産品類型番号と、地場産品に該当する理由（100文字以内）を入力してください。地場産品類型番号と該当する理由のテキストを『 | 』で区切ってください。
			preg_match( '/消費期限/', $val ) => $n2values['消費期限'],// 食品系の場合はなるべく消費期限を1,000文字以内で入力
			preg_match( '/^（必須）.+?配送$/', $val ) => false !== strpos( $val, $n2values['発送方法'] ) ? 1 : 0,// * 対応する場合は半角数字の1、対応しない場合は半角数字の0
			preg_match( '/^（必須）((包装|のし)対応)$/', $val, $m ) => false !== strpos( '有り', $n2values[ $m[1] ] ) ? 1 : 0,// * 対応する場合は半角数字の1、対応しない場合は半角数字の0
			preg_match( '/^（必須）定期配送対応$/', $val ) => $n2values['定期便'] > 1 ? 1 : 0,// * 対応する場合は半角数字の1、対応しない場合は半角数字の0
			preg_match( '/^（必須）(会員|チョイス)限定$/', $val ) => 0,// * 対応する場合は半角数字の1、対応しない場合は半角数字の0
			preg_match( '/^（必須）オンライン決済限定$/', $val ) => (int) in_array( '限定', (array) $n2values['オンライン決済限定'] ?? array(), true ),// * 対応する場合は半角数字の1、対応しない場合は半角数字の0
			preg_match( '/^（必須）(発送|配達|配送).+$/', $val ) => 0,// * 対応する場合は半角数字の1、対応しない場合は半角数字の0
			preg_match( '/^（必須）容量単位$/', $val ) => 0,// * グラムは半角数字の0、キログラムは半角数字の1、ミリリットルは半角数字の2、リットルは半角数字の3
			preg_match( '/^（必須）地域の生産者応援の品/', $val ) => 0,// * 適用する場合は半角数字の1、適用しない場合は半角数字の0
			preg_match( '/^（必須）.*(有無|可否)$/', $val ) => 1,// * 対応する場合は半角数字の1、対応しない場合は半角数字の0
			preg_match( '/受付開始日時/', $val ) => gmdate( 'Y/m/d', strtotime( '+1 year' ) ) . ' 00:00',// 受付開始日時を設定することが出来ます。指定した場合、この日時以降でないと申込みできない
			preg_match( '/還元率（\%）$/', $val ) => 30,// 対応する場合は半角数字の1、対応しない場合は半角数字の0
			preg_match( '/^（必須）別送対応$/', $val ) => 1,// * 対応する場合は半角数字の1、対応しない場合は半角数字の0
			default => '',
		};
		/**
		 * [hook] n2_item_export_furusato_choice_walk_values
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
		// 必須漏れエラー
		if ( preg_match( '/（必須）|必要寄付金額/', $name ) && '' === $value ) {
			$this->add_error( $n2values['id'], "「{$name}」がありません。" );
		}
		// 文字数制限エラー
		$maxlength = array(
			40   => 'キャッチコピー',
			64   => 'サイト表示事業者名',
			100  => '地場産品類型番号',
			200  => '（必須）お礼の品名',
			1000 => '^説明$|^容量$|^申込期日$|^発送期日$|アレルギー特記事項|消費期限',
		);
		foreach ( $maxlength as $max => $pattern ) {
			$len = match ( $pattern ) {
				'地場産品類型番号' => mb_strlen( preg_replace( '/.*?\|/u', '', $value ) ),// パイプ前を削除
				default => mb_strlen( $value ),
			};
			if ( preg_match( "/{$pattern}/", $name ) && $len > $max ) {
				$over = $len - $max;
				$this->add_error( $n2values['id'], "<div title='{$value}'>「{$name}」の文字数が{$over}文字多いです。</div>" );
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
		$str = preg_replace( '/\r\n?|\n/', "\r\n", $str );
		/**
		 * [hook] n2_item_export_furusato_choice_special_str_convert
		 */
		$str = apply_filters( mb_strtolower( get_class( $this ) ) . '_special_str_convert', $str );
		return $str;
	}
}
