<?php
/**
 * class-n2-ledghome.php
 *
 * @package neoneng
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'N2_Ledghome' ) ) {
	new N2_Ledghome();
	return;
}

/**
 * クラウド版Legehome
 */
class N2_Ledghome {
	/**
	 * コンストラクタ
	 */
	public function __construct() {
		add_action( 'wp_ajax_ledghome', array( $this, 'create_csv' ) );
	}

	/**
	 * ledghomeのエクスポート用CSV生成
	 *
	 * @return void
	 */
	public function create_csv() {
		global $n2;

		// itemの情報を配列化
		$items_arr   = array();
		$error_items = '';
		// あとでヘッダの上の連結するのに必要

		$ledghome_csv_contents = $n2->ledghome_csv_contents;
		$csv_title             = $ledghome_csv_contents['ledghome']['csv_header']['title'];
		$header                = $ledghome_csv_contents['ledghome']['csv_header']['values'];
		$setting               = $ledghome_csv_contents['ledghome']['setting'];

		// ajaxで渡ってきたpostidの配列
		$ids = explode( ',', filter_input( INPUT_POST, 'ledghome' ) );
		foreach ( $ids as $id ) {
			$teiki = get_post_meta( $id, '定期便', true );
			$price = ( get_post_meta( $id, '定期便価格', true ) && ( $teiki > 1 ) ) ? get_post_meta( $id, '定期便価格', true ) : get_post_meta( $id, '価格', true );

			for ( $i = 1; $i <= $teiki; $i++ ) {
				$key_id   = 1 < $teiki ? "{$id}_{$i}" : $id;
				$teikinum = 1 < $teiki ? "_{$i}/{$teiki}" : '';
				foreach ( $header as $head ) {
					$items_arr[ $key_id ][ $head ] = ! empty( get_post_meta( $id, $head, true ) ) ? get_post_meta( $id, $head, true ) : '';
				}
				$item_num        = trim( strtoupper( get_post_meta( $id, '返礼品コード', true ) ) ) . $teikinum;
				$item_name       = $item_num . ' ' . (
														get_post_meta( $id, '略称', true )
															? get_post_meta( $id, '略称', true )
															: N2_Functions::special_str_convert( get_the_title( $id ) )
													 ) . apply_filters( 'append_text_item_name', '' ); // 謝礼品名に追加するフック
				$deliva_price    = get_post_meta( $id, '送料', true );
				$deliva_size     = get_post_meta( $id, '発送サイズ', true );
				$jibasanpin_type = implode( 'ー', mb_str_split( mb_convert_kana( get_post_meta( $id, '地場産品類型', true ), 'KA' ), 1 ) );

				$error_items .= get_post_meta( $id, '寄附金額', true ) === 0 || get_post_meta( $id, '寄附金額', true ) === '' ? "【{$item_code}】" . '<br>' : '';
				$arr          = array(
					'謝礼品番号'      => $item_num,
					'謝礼品名'       => ( get_post_meta( $id, 'LH表示名', true ) ) ? ( $item_num . ' ' . get_post_meta( $id, 'LH表示名', true ) ) : $item_name,
					'事業者'        => get_the_author_meta( 'first_name', get_post_field( 'post_author', $id ) ),
					'配送名称'       => ( get_post_meta( $id, '配送伝票表示名', true ) ) ? ( $item_num . ' ' . get_post_meta( $id, '配送伝票表示名', true ) ) : $item_name,
					'ふるさとチョイス名称' => N2_Functions::special_str_convert( get_the_title( $id ) ) . " [{$item_num}]",
					'楽天名称'       => '【ふるさと納税】' . N2_Functions::special_str_convert( get_the_title( $id ) ) . " [{$item_num}]",
					'謝礼品カテゴリー'   => get_post_meta( $id, 'LHカテゴリー', true ),
					'セット内容'      => N2_Functions::special_str_convert( get_post_meta( $id, '内容量・規格等', true ) ),
					'謝礼品紹介文'     => N2_Functions::special_str_convert( get_post_meta( $id, '説明文', true ) ),
					'ステータス'      => '受付中',
					'状態'         => '表示',
					'寄附設定金額'     => $i < 2 ? get_post_meta( $id, '寄附金額', true ) : 0,
					'価格（税込み）'    => ( $setting['teiki_price'] === true ) ? ( ( $i < 2 ) ? $price * $teiki : 0 ) : $price,
					'送料'         => $deliva_price,
					// 特定自治体の処理をフックで行う。
					'送料反映'       => ( ( ( ( apply_filters( 'deliva_price_no_reflect', '' )
											? '反映しない'
											: ( ! is_numeric( get_post_meta( $id, '発送サイズ', true ) ) ) )
												? '反映する'
												: ( get_post_meta( $id, 'web出荷・レターパック利用', true ) === '利用しない' ) )
													? '反映する'
													: ( $setting['deliva_price_reflect'] ) ) )
														? '反映する'
														: '反映しない',

					'発送方法'       => get_post_meta( $id, '発送方法', true ),
					'取り扱い方法'     => get_post_meta( $id, '取り扱い方法', true ) ? rtrim( implode( ',', get_post_meta( $id, '取り扱い方法', true ) ), ',' ) : '',
					'申込可能期間'     => '通年',
					'自由入力欄1'     => date( 'Y/m/d' ) . '：' . wp_get_current_user()->display_name,
					'自由入力欄2'     => get_post_meta( $id, '送料', true ),
					'配送サイズコード'   => ( is_numeric( get_post_meta( $id, '発送サイズ', true ) ) ) ? get_post_meta( $id, '発送サイズ', true ) : '',
					'地場産品類型'     => apply_filters( 'ledghome_jibasanpin_type', $jibasanpin_type ),
					'類型該当理由'     => apply_filters( 'ledghome_jibasanpin_type_reason', get_post_meta( $id, '類型該当理由', true ) ),
				);
				// 内容を追加、または上書きするためのフック
				$items_arr[ $key_id ] = apply_filters( 'n2_item_export_ledghome_items', $arr, $id );
			}
		}

		// 謝礼品番号（返礼品コード）で昇順ソート
		uasort(
			$items_arr,
			function ( $a, $b ) {
				return strnatcmp( $a['謝礼品番号'], $b['謝礼品番号'] );
			}
		);

		// 寄附金額アラート
		$kifukin_alert_str = '【以下の返礼品が寄附金額が０になっていたため、ダウンロードを中止しました】<br>';
		$kifukin_check_str = isset( $error_items ) ? $error_items : '';
		if ( $kifukin_check_str ) { // 寄付金額エラーで出力中断
			exit( $kifukin_alert_str . $kifukin_check_str );
		}

		N2_Functions::download_csv(
			array(
				'file_name' => 'ledghome',
				'header'    => $header,
				'items_arr' => $items_arr,
				'csv_title' => $csv_title,
			)
		);
	}
}
