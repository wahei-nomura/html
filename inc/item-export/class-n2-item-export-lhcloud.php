<?php
/**
 * LedgHOMEの商品エクスポート専用
 * LedgHOMECSVの仕様：https://steamship.docbase.io/posts/2917248
 * class-n2-item-export-lhcloud.php
 *
 * @package neoneng
 */

if ( class_exists( 'N2_Item_Export_LHcloud' ) ) {
	new N2_Item_Export_LHcloud();
	return;
}

/**
 * N2_Item_Export_LHcloud
 */
class N2_Item_Export_LHcloud extends N2_Item_Export_Base {

	/**
	 * 設定
	 *
	 * @var array
	 */
	public $settings = array(
		'filename'      => 'n2_export_lhcloud.csv',
		'delimiter'     => ',',
		'charset'       => 'sjis',
		'header_string' => '"LedgHOMEクラウド謝礼品リスト"' . PHP_EOL,
	);

	/**
	 * LedgHOMECSVヘッダーを取得
	 */
	protected function set_header() {
		global $n2;
		$lh_setting = $n2->settings['LedgHOME'];
		$params     = $this->data['params'];
		$type       = $params['type'] ?? '謝礼品リスト';
		// CSVヘッダー配列化
		$this->data['header'] = $lh_setting['csv_header'][ $type ];

		// filename変更
		$this->settings['filename'] = "{$type}.csv";

		switch ( $type ) {
			case '謝礼品リスト':
				// その他経費を利用しない場合はヘッダーから抹消
				if ( '利用しない' === $lh_setting['その他経費'] ) {
					$this->data['header'] = array_filter( $this->data['header'], fn( $v ) => 'その他経費' !== $v );
				}
				// eチケットを対応しない場合はヘッダーから抹消
				if ( ! in_array( 'eチケット', $n2->custom_field['事業者用']['商品タイプ']['option'], true ) ) {
					$this->data['header'] = array_filter( $this->data['header'], fn( $v ) => '特設サイト名称' !== $v );
				}
				break;
			case '定期便（基本情報）リスト':
				$this->settings['header_string'] = 'LedgHOMEクラウド 定期便（基本情報）リスト' . PHP_EOL;
				break;
			case '定期便（子謝礼品）リスト':
				// ヘッダー文字列変更
				add_filter( 'n2_item_export_lhcloud_set_header_string', fn() => $lh_setting['csv_header_string']['定期便（子謝礼品）リスト'] );
				break;
		}

		/**
		 * [hook] n2_item_export_lhcloud_set_header
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
		$params = $this->data['params'];
		$data   = array();
		// LH設定
		$lh_setting = $n2->settings['LedgHOME'];
		// 定期便の初期化
		$n2values['定期便'] = $n2values['定期便'] ?: 1;
		// eチケット判定
		$is_e_ticket = in_array( 'eチケット', (array) $n2values['商品タイプ'], true );
		// 発送サイズがヤマトか判定
		$is_yamato = is_numeric( $n2values['発送サイズ'] );
		// ループ回数
		$loop = match ( $params['type'] ) {
			'謝礼品リスト' => $n2values['定期便'],
			default => $n2values['定期便'] > 1 ? 1 : 0,
		};
		for ( $i = 1; $i <= $loop; $i++ ) {
			// 返礼品コード
			$item_code = $n2values['返礼品コード'] . ( $loop > 1 ? "_{$i}/{$n2values['定期便']}" : '' );
			// データ配列
			$data[ $i ] = match ( $val ) {
				'謝礼品番号', '定期便番号' => $item_code,
				'取扱年度' => $is_e_ticket ? '2023' : '',// 謎の2023
				'謝礼品名', '定期便名' => "{$item_code} " . ( $n2values['LH表示名'] ?: $n2values['タイトル'] ),// LH表示名 > タイトル
				'定期便種別' => '回数',
				'配送名称' => "{$item_code} " . ( $n2values['配送伝票表示名'] ?: $n2values['タイトル'] ),// 配送伝票表示名 > タイトル
				'特設サイト名称' => $is_e_ticket ? "{$item_code} " . ( $n2values['配送伝票表示名'] ?: $n2values['タイトル'] ) : '',// eチケット
				'事業者' => $n2values['事業者名'],
				'謝礼品カテゴリー' => $n2values['LHカテゴリー'],
				'セット内容' => $n2values['内容量・規格等'],
				'謝礼品紹介文' => $n2values['説明文'],
				'ステータス' => '受付中',
				'状態' => '表示',
				'寄附設定金額' => $i > 1 ? 0 : $n2values['寄附金額'],// 定期便の場合は１回目のみ
				'価格（税込み）' => match ( $lh_setting['価格'] ) {
					'定期便初回に全額をまとめて登録' => $i > 1 ? '' : (int) $n2values['価格'] * (int) $n2values['定期便'],
					default => $n2values['価格'] ?: 0,
				},
				'その他経費' => match ( $lh_setting['その他経費'] ) {
					'ヤマト以外の送料を登録' => $is_yamato ? '' : $n2values['送料'],
					'ヤマト以外の送料を登録（定期便の場合は1回目に総額）' => $is_yamato || $i > 1 ? '' : (int) $n2values['送料'] * (int) $n2values['定期便'],
					default => '',
				},
				'送料' => match ( $lh_setting['送料'] ) {
					'ヤマト以外は送料を空欄で登録' => $is_yamato ? $n2values['送料'] : 0,// 土岐カオスなのでやっつけたい By わかちゃん
					'送料は空欄で登録' => '',
					default => $n2values['送料'],
				},
				'送料反映' => match ( $n2values['発送サイズ'] ) {
					'その他' => in_array( 'その他', $lh_setting['送料反映'], true ) ? '反映する' : '反映しない',
					'レターパックプラス', 'レターパックライト' => in_array( 'レターパック', $lh_setting['送料反映'], true ) ? '反映する' : '反映しない',
					default => '反映しない',
				},
				'発送方法' => $is_e_ticket ? '常温' : $n2values['発送方法'],
				'取り扱い方法' => implode( ',', (array) $n2values['取り扱い方法'] ),
				'申込可能期間' => '通年',
				'自由入力欄1' => $is_e_ticket ? '' : wp_date( 'Y/m/d' ) . "：{$n2->current_user->data->display_name}",
				'自由入力欄2' => $is_e_ticket ? wp_date( 'Y/m/d' ) . "：{$n2->current_user->data->display_name}" : '',
				'配送サイズコード' => $is_yamato ? $n2values['発送サイズ'] : '',
				'地場産品類型' => str_replace( 'イ', '', $n2values['地場産品類型'] ),
				'類型該当理由' => $n2values['類型該当理由'],
				'自動出荷依頼予約（種別）' => $lh_setting['自動出荷依頼予約日'] ? 'する（月指定）' : 'しない',
				'自動出荷依頼予約（値）' => $lh_setting['自動出荷依頼予約日'] ?: '',
				'1月1' => 1 <= $n2values['定期便'] ? "{$item_code}_1/{$n2values['定期便']}" : '',
				'2月1' => 2 <= $n2values['定期便'] ? "{$item_code}_2/{$n2values['定期便']}" : '',
				'3月1' => 3 <= $n2values['定期便'] ? "{$item_code}_3/{$n2values['定期便']}" : '',
				'4月1' => 4 <= $n2values['定期便'] ? "{$item_code}_4/{$n2values['定期便']}" : '',
				'5月1' => 5 <= $n2values['定期便'] ? "{$item_code}_5/{$n2values['定期便']}" : '',
				'6月1' => 6 <= $n2values['定期便'] ? "{$item_code}_6/{$n2values['定期便']}" : '',
				'7月1' => 7 <= $n2values['定期便'] ? "{$item_code}_7/{$n2values['定期便']}" : '',
				'8月1' => 8 <= $n2values['定期便'] ? "{$item_code}_8/{$n2values['定期便']}" : '',
				'9月1' => 9 <= $n2values['定期便'] ? "{$item_code}_9/{$n2values['定期便']}" : '',
				'10月1' => 10 <= $n2values['定期便'] ? "{$item_code}_10/{$n2values['定期便']}" : '',
				'11月1' => 11 <= $n2values['定期便'] ? "{$item_code}_11/{$n2values['定期便']}" : '',
				'12月1' => 12 <= $n2values['定期便'] ? "{$item_code}_12/{$n2values['定期便']}" : '',
				'1月日' => 1 <= $n2values['定期便'] ? $lh_setting['自動出荷依頼予約日'] : '',
				'2月日' => 2 <= $n2values['定期便'] ? $lh_setting['自動出荷依頼予約日'] : '',
				'3月日' => 3 <= $n2values['定期便'] ? $lh_setting['自動出荷依頼予約日'] : '',
				'4月日' => 4 <= $n2values['定期便'] ? $lh_setting['自動出荷依頼予約日'] : '',
				'5月日' => 5 <= $n2values['定期便'] ? $lh_setting['自動出荷依頼予約日'] : '',
				'6月日' => 6 <= $n2values['定期便'] ? $lh_setting['自動出荷依頼予約日'] : '',
				'7月日' => 7 <= $n2values['定期便'] ? $lh_setting['自動出荷依頼予約日'] : '',
				'8月日' => 8 <= $n2values['定期便'] ? $lh_setting['自動出荷依頼予約日'] : '',
				'9月日' => 9 <= $n2values['定期便'] ? $lh_setting['自動出荷依頼予約日'] : '',
				'10月日' => 10 <= $n2values['定期便'] ? $lh_setting['自動出荷依頼予約日'] : '',
				'11月日' => 11 <= $n2values['定期便'] ? $lh_setting['自動出荷依頼予約日'] : '',
				'12月日' => 12 <= $n2values['定期便'] ? $lh_setting['自動出荷依頼予約日'] : '',
				default => '',
			};
		}
		/**
		 * [hook] n2_item_export_lhcloud_walk_values
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
			if ( preg_match( '/謝礼品番号|事業者|価格（税込み）|寄附設定金額/', $name ) && '' === trim( $val ) ) {
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
		 * [hook] n2_item_export_lhcloud_special_str_convert
		 */
		$str = apply_filters( mb_strtolower( get_class( $this ) ) . '_special_str_convert', $str );
		return $str;
	}
}
