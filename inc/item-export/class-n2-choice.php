<?php
/**
 * class-n2-choice.php
 *
 * @package neoneng
 */

if ( class_exists( 'N2_Choice' ) ) {
	new N2_Choice();
	return;
}

/**
 * N2_Choice
 */
class N2_Choice {

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		add_action( 'wp_ajax_choice', array( $this, 'create_tsv' ) );
	}

	/**
	 * チョイスのエクスポート用TSV生成
	 *
	 * @return void
	 */
	public function create_tsv() {
		global $n2;

		// チョイスのサンプルヘッダー取得
		$choice_user   = $n2->choice['auth']['user'];
		$choice_pass   = $n2->choice['auth']['pass'];
		$choice_url    = $n2->choice['auth']['url'];
		// カスタムフィールドのアレルゲン全選択肢
		$all_allergen  = $n2->custom_field['事業者用']['アレルゲン']['option'];
		$sample_header = wp_remote_get( str_replace( '//', "//{$choice_user}:{$choice_pass}@", $choice_url ), array() )['body'];
		$sample_header = trim( $sample_header );
		$sample_header = array_flip( explode( "\t", $sample_header ) );

		$header0                   = $n2->choice['tsv_header']['value0']; // 初期値として0をセットするヘッダーグループ
		$header1                   = $n2->choice['tsv_header']['value1']; // 初期値として1をセットするヘッダーグループ
		$portal_common_discription = $n2->portal_common_discription; // 説明文へ追加するポータル共通説明文

		$items_arr   = array();
		$error_items = '';

		// ajaxで渡ってきたpostidの配列
		$ids = explode( ',', filter_input( INPUT_POST, 'choice' ) );
		foreach ( $ids as $id ) {
			$items_arr[ $id ] = array( ...$sample_header, ...get_post_meta( $id, '', false ) );
			$item_code        = strtoupper( get_post_meta( $id, '返礼品コード', true ) );
			// 初期化処理
			foreach ( $items_arr[ $id ] as $k => $v ) {
				if ( in_array( $k, $header0, true ) ) {
					$items_arr[ $id ][ $k ] = 0;
				} elseif ( in_array( $k, $header1, true ) ) {
					$items_arr[ $id ][ $k ] = 1;
				} else {
					$items_arr[ $id ][ $k ] = '';
				}
			}
			$product_type     = get_post_meta( $id, '商品タイプ' )[0][0];


			// 寄附金額チェック
			$error_items .= get_post_meta( $id, '寄附金額', true ) === 0 || get_post_meta( $id, '寄附金額', true ) === '' ? "【{$item_code}】" . '<br>' : '';

			$arr = array(
				'管理コード'          => $item_code,
				'（必須）お礼の品名'      => N2_Functions::special_str_convert( get_the_title( $id ) ) . " [{$item_code}]",
				// ポータル表示用名称が登録されてたら優先。
				'サイト表示事業者名'      => get_post_meta( $id, '提供事業者名', true )
												?:
													(
														get_the_author_meta( 'portal', get_post_field( 'post_author', $id ) )
														? (
															get_the_author_meta( 'portal', get_post_field( 'post_author', $id ) ) === '記載しない'
																? ''
																: get_the_author_meta( 'portal', get_post_field( 'post_author', $id ) )
															)
														: get_the_author_meta( 'first_name', get_post_field( 'post_author', $id ) )
													),
				'（条件付き必須）必要寄付金額' => get_post_meta( $id, '寄附金額', true ),
				// NENGから同期したデータは「キャッチコピー1」になってるので修正が終わるまで回避処理を入れる
				'キャッチコピー'        => N2_Functions::special_str_convert( get_post_meta( $id, 'キャッチコピー', true ) ?: get_post_meta( $id, 'キャッチコピー1', true ) ),
				'説明'             => N2_Functions::special_str_convert( get_post_meta( $id, '説明文', true ) ) .
								(
									get_post_meta( $id, '検索キーワード', true )
										? "\n\n" . ( N2_Functions::special_str_convert( get_post_meta( $id, '検索キーワード', true ) ) )
										: ''
								) .
								(
									// 楽天カテゴリーを追記するフック
									// 説明文を追記するフック
									apply_filters( 'add_discription_text', '' )

								) . "\n\n" . $portal_common_discription, // 説明文の末尾に、設定されている場合はポータル共通説明文が入る。その後の記述は禁止。

				'容量'             => N2_Functions::special_str_convert( get_post_meta( $id, '内容量・規格等', true ) ) .
								(
									(
										// 電子レンジ等の対応機器表示フック
										apply_filters( 'append_enabled_devices_text', '' )
									) .
									(
										// 個体差がある旨等の注意表示フック
										apply_filters( 'append_quality_inconsistent_message', '' )
									)
								) .
								(
									get_post_meta( $id, '原料原産地', true )
										? "\n\n【原料原産地】\n" . N2_Functions::special_str_convert( get_post_meta( $id, '原料原産地', true ) )
										: ''
								) .
								(
									get_post_meta( $id, '加工地', true )
										? "\n\n【加工地】\n" . N2_Functions::special_str_convert( get_post_meta( $id, '加工地', true ) )
										: ''
								),
				'申込期日'           => get_post_meta( $id, '申込期間', true ),
				'発送期日'           => get_post_meta( $id, '配送期間', true ),
				'アレルギー特記事項'   => get_post_meta( $id, 'アレルゲン注釈', true ) ?: '',
				'地場産品類型番号'       => get_post_meta( $id, '地場産品類型', true )
												? get_post_meta( $id, '地場産品類型', true ) . '|' . get_post_meta( $id, '類型該当理由', true )
												: '',
				'消費期限'           => ( get_post_meta( $id, '賞味期限', true ) !== '' )
										? "【賞味期限】\n" . N2_Functions::special_str_convert( get_post_meta( $id, '賞味期限', true ) ) .
											(
												( get_post_meta( $id, '消費期限', true ) !== '' )
													? "\n\n【消費期限】\n" . N2_Functions::special_str_convert( get_post_meta( $id, '消費期限', true ) )
													: ''
											)
										: N2_Functions::special_str_convert( get_post_meta( $id, '消費期限', true ) ),
				'（必須）常温配送'       => ( get_post_meta( $id, '発送方法', true ) === '常温' ) ? 1 : 0,
				'（必須）冷蔵配送'       => ( get_post_meta( $id, '発送方法', true ) === '冷蔵' ) ? 1 : 0,
				'（必須）冷凍配送'       => ( get_post_meta( $id, '発送方法', true ) === '冷凍' ) ? 1 : 0,
				'（必須）包装対応'       => ( get_post_meta( $id, '包装対応', true ) === '有り' ) ? 1 : 0,
				'（必須）のし対応'       => ( get_post_meta( $id, 'のし対応', true ) === '有り' ) ? 1 : 0,
				'（必須）定期配送対応'     => ( get_post_meta( $id, '定期便', true ) === '1' || get_post_meta( $id, '定期便', true ) === '' ) ? 0 : 1,
				'（必須）オンライン決済限定'  => ( get_post_meta( $id, 'オンライン決済限定', true ) === 'オンライン決済限定' ) ? 1 : 0,
				'カテゴリー'          => '',
				'お礼の品画像'         => mb_strtolower( $item_code ) . '.jpg',
				'受付開始日時'         => '2025/04/01 00:00',
				'（条件付き必須）還元率（%）' => 30,
			);

			// デフォルトで全てのアレルギーに2をセット
			foreach ( $all_allergen as $allergen_name ) {
				if ( '落花生' === $allergen_name ) {
					$arr['アレルギー：落花生（ピーナッツ）'] = 2;
					continue;
				}
				$arr[ "アレルギー：{$allergen_name}" ] = 2;
			}
			// アレルゲンの値がある項目のindexで全アレルギー項目から名前を取得し1をセット
			foreach ( get_post_meta( $id, 'アレルゲン' )[0] as $selected ) {
				if ( '落花生' === $all_allergen[ $selected['value'] ] ) {
					$arr['アレルギー：落花生（ピーナッツ）'] = 1;
					continue;
				}
				$arr[ "アレルギー：{$all_allergen[ $selected['value'] ]}" ] = 1;
			}

			// 「スライド画像」カラムに値を入れる処理
			for ( $i = 1; $i < 9; $i++ ) {
				$ii  = ( ( $i - 1 ) === 0 ) ? '' : '-' . ( $i - 1 );
				$arr = $arr + array( "スライド画像{$i}" => mb_strtolower( $item_code ) . "{$ii}.jpg" );
			}

			// 内容を追加、または上書きするためのフック
			$items_arr[ $id ] = array( ...$items_arr[ $id ], ...apply_filters( 'n2_item_export_choice_items', $arr, $id ) );

		}

		// 管理コード（返礼品コード）で昇順ソート
		uasort(
			$items_arr,
			function ( $a, $b ) {
				return strnatcmp( $a['管理コード'], $b['管理コード'] );
			}
		);

		// 寄附金額0アラート
		$kifukin_alert_str = '【以下の返礼品が寄附金額が０になっていたため、ダウンロードを中止しました】<br>';
		$kifukin_check_str = isset( $error_items ) ? $error_items : '';
		if ( $kifukin_check_str ) { // 寄付金額エラーで出力中断
			exit( $kifukin_alert_str . $kifukin_check_str );
		}

		// tsv出力
		N2_Functions::download_csv(
			array(
				'file_name'      => 'choice',
				'header'         => array_keys( $sample_header ),
				'items_arr'      => $items_arr,
				'type'           => 'tsv',
				'character_code' => 'utf-8',
			)
		);

	}
}

