<?php
/**
 * class-n2-change-sku-firstaid.php
 * N2移行完了までの一時しのぎ版
 *
 * @package neoneng
 */

if ( class_exists( 'N2_Change_Sku_Firstaid' ) ) {
	new N2_Change_Sku_Firstaid();
	return;
}

/**
 * 楽天FTPページ
 */
class N2_Change_Sku_Firstaid {

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		// ログインしててもしてなくても同じ動作させる
		add_action( 'wp_ajax_n2_change_sku_firstaid', array( $this, 'change_sku_firstaid' ) );
		add_action( 'wp_ajax_nopriv_n2_change_sku_firstaid', array( $this, 'change_sku_firstaid' ) );
	}

	/**
	 * 楽天への転送機能（超突貫）
	 *
	 * @return void
	 */
	public function change_sku_firstaid() {
		header( 'Content-Type: application/json; charset=utf-8' );
		$files                          = $_FILES['item_files'];
		$new_item_column                = '商品管理番号（商品URL）,商品番号,商品名,倉庫指定,ジャンルID,非製品属性タグID,キャッチコピー,PC用商品説明文,スマートフォン用商品説明文,PC用販売説明文,商品画像タイプ1,商品画像パス1,商品画像名（ALT）1,商品画像タイプ2,商品画像パス2,商品画像名（ALT）2,商品画像タイプ3,商品画像パス3,商品画像名（ALT）3,商品画像タイプ4,商品画像パス4,商品画像名（ALT）4,商品画像タイプ5,商品画像パス5,商品画像名（ALT）5,商品画像タイプ6,商品画像パス6,商品画像名（ALT）6,商品画像タイプ7,商品画像パス7,商品画像名（ALT）7,商品画像タイプ8,商品画像パス8,商品画像名（ALT）8,商品画像タイプ9,商品画像パス9,商品画像名（ALT）9,商品画像タイプ10,商品画像パス10,商品画像名（ALT）10,商品画像タイプ11,商品画像パス11,商品画像名（ALT）11,商品画像タイプ12,商品画像パス12,商品画像名（ALT）12,商品画像タイプ13,商品画像パス13,商品画像名（ALT）13,商品画像タイプ14,商品画像パス14,商品画像名（ALT）14,商品画像タイプ15,商品画像パス15,商品画像名（ALT）15,商品画像タイプ16,商品画像パス16,商品画像名（ALT）16,商品画像タイプ17,商品画像パス17,商品画像名（ALT）17,商品画像タイプ18,商品画像パス18,商品画像名（ALT）18,商品画像タイプ19,商品画像パス19,商品画像名（ALT）19,商品画像タイプ20,商品画像パス20,商品画像名（ALT）20,バリエーション項目キー定義,バリエーション項目名定義,バリエーション1選択肢定義,バリエーション2選択肢定義,バリエーション3選択肢定義,選択肢タイプ,商品オプション項目名,商品オプション選択肢1,商品オプション選択肢2,商品オプション選択肢3,商品オプション選択必須,SKU管理番号,販売価格,再入荷お知らせボタン,のし対応,在庫数,在庫あり時納期管理番号,送料,カタログIDなしの理由';
		$new_item_column_array          = explode( ',', $new_item_column );
		$output_column[ $column_count ] = $new_item_column;
		$column_count++;

		for ( $i = 0; $i < count( $files['name'] ); $i++ ) {
			print_r($i);
			$filename      = $files['name'][ $i ];
			$filetmp       = $files['tmp_name'][ $i ];
			$filesize      = $files['size'][ $i ];
			$fileerror     = $files['error'][ $i ];
			$result_item   = preg_match( '/.*item.*/', $filename );
			$result_select = preg_match( '/.*select.*/', $filename );
			$fn            = fopen( $filetmp, 'r' );
			$countloop     = 0;
			$csv_header_array = [];
			while ( ( $arr = fgetcsv( $fn ) ) != false ) {
				${'csv_array_' . $countloop} = [];
				foreach ( $arr as $key => $val ) {
					if ( 0 === $countloop ) {
						$csv_header_array[] = mb_convert_encoding( $val, 'UTF-8', 'SJIS' );
					} else {
						${'csv_array_' . $countloop}[] = str_replace( '"', '""', mb_convert_encoding( $val, 'UTF-8', 'SJIS' ) ?? '' );
					}
				}
				$countloop++;
			}
			if ( $result_item ) {
				// item.csvの要素を配列($csv_item_array)に格納
				$csv_item_array = array();
				for ( $j = 0; $j < $countloop; ++$j ) {
					if ( 0 == $j ) {
						$csv_item_array[] = $csv_header_array;
					} else {
						$csv_item_array[] = ${'csv_array_' . $j};
					}
				}
			}
			if ( $result_select ) {
				// select.csvの要素を配列($csv_select_array)に格納
				$csv_select_array = array();
				for ( $j = 0; $j < $countloop; ++$j ) {
					if ( 0 == $j ) {
						$csv_select_array[] = $csv_header_array;
					} else {
						$csv_select_array[] = ${'csv_array_' . $j};
					}
				}
			}
		}
		// 各種設定読み込み
		global $n2;
		$column_count = 0;
		$rakuten      = $n2->portal_setting['楽天'];
		// setlocale(LC_ALL, 'ja_JP.UTF-8');
		$error_options = array();

		if ( ! isset( $rakuten['select'] ) || ! $rakuten['select'] ) {
			$error_options = array( ...$error_options, '楽天セットアップ > 項目選択肢' );
		}
		if ( $error_options ) {
			// エラー出力して終了
			echo '項目選択肢の入力が完了していません。';
			exit();
			die();
		}
		$select_items = $rakuten['項目選択肢'];

		setlocale( LC_ALL, 'ja_JP.UTF-8' );
		extract( $_FILES['item_files'] );
		if ( ! empty( $tmp_name[0] ) ) {
			$new_item_column                = '商品管理番号（商品URL）,商品番号,商品名,倉庫指定,ジャンルID,非製品属性タグID,キャッチコピー,PC用商品説明文,スマートフォン用商品説明文,PC用販売説明文,商品画像タイプ1,商品画像パス1,商品画像名（ALT）1,商品画像タイプ2,商品画像パス2,商品画像名（ALT）2,商品画像タイプ3,商品画像パス3,商品画像名（ALT）3,商品画像タイプ4,商品画像パス4,商品画像名（ALT）4,商品画像タイプ5,商品画像パス5,商品画像名（ALT）5,商品画像タイプ6,商品画像パス6,商品画像名（ALT）6,商品画像タイプ7,商品画像パス7,商品画像名（ALT）7,商品画像タイプ8,商品画像パス8,商品画像名（ALT）8,商品画像タイプ9,商品画像パス9,商品画像名（ALT）9,商品画像タイプ10,商品画像パス10,商品画像名（ALT）10,商品画像タイプ11,商品画像パス11,商品画像名（ALT）11,商品画像タイプ12,商品画像パス12,商品画像名（ALT）12,商品画像タイプ13,商品画像パス13,商品画像名（ALT）13,商品画像タイプ14,商品画像パス14,商品画像名（ALT）14,商品画像タイプ15,商品画像パス15,商品画像名（ALT）15,商品画像タイプ16,商品画像パス16,商品画像名（ALT）16,商品画像タイプ17,商品画像パス17,商品画像名（ALT）17,商品画像タイプ18,商品画像パス18,商品画像名（ALT）18,商品画像タイプ19,商品画像パス19,商品画像名（ALT）19,商品画像タイプ20,商品画像パス20,商品画像名（ALT）20,バリエーション項目キー定義,バリエーション項目名定義,バリエーション1選択肢定義,バリエーション2選択肢定義,バリエーション3選択肢定義,選択肢タイプ,商品オプション項目名,商品オプション選択肢1,商品オプション選択肢2,商品オプション選択肢3,商品オプション選択必須,SKU管理番号,販売価格,再入荷お知らせボタン,のし対応,在庫数,在庫あり時納期管理番号,送料,カタログIDなしの理由';
			$output_column[ $column_count ] = $new_item_column;
			$column_count++;
			$new_item_column_array = explode( ',', $new_item_column );
			// 旧項目：コントロールカラム,商品管理番号（商品URL）,商品番号,全商品ディレクトリID,タグID,PC用キャッチコピー,モバイル用キャッチコピー,商品名,販売価格,送料,のし対応,PC用商品説明文,スマートフォン用商品説明文,PC用販売説明文,商品画像URL,在庫タイプ,在庫数,カタログID,カタログIDなしの理由
			$fn        = fopen( $tmp_name[0], 'r' );
			$countloop = 0;
			while ( ( $arr = fgetcsv( $fn ) ) != false ) {
				foreach ( $arr as $key => $val ) {
					if ( 0 === $countloop ) {
						$arr0[] = mb_convert_encoding( $val, 'UTF-8', 'SJIS' );
					}
					if ( 1 === $countloop ) {
						$arr1[] = str_replace( '"', '""', mb_convert_encoding( $val, 'UTF-8', 'SJIS' ) ?? '' );
					}
				}
				$countloop++;
			}
			$item_array = array_combine( $arr0, $arr1 );
			// 商品画像URLを分割
			if ( isset( $item_array['商品画像URL'] ) && '' !== $item_array['商品画像URL'] ) {
				$picture_array = explode( ' ', $item_array['商品画像URL'] );
				foreach ( $picture_array as $picture_key => $picture_val ) {
					$picture_name         = explode( '/', $picture_val );
					$picture_last_names[] = $picture_name[ array_key_last( $picture_name ) - 2 ] . '/' . $picture_name[ array_key_last( $picture_name ) - 1 ] . '/' . $picture_name[ array_key_last( $picture_name ) ];
				}
				$picture_count = count( $picture_last_names );
			}
			foreach ( $new_item_column_array as $item_key => $item_val ) {
				if ( isset( $item_array[ $item_val ] ) ) {
					$new_item_array[ $item_key ] = $item_array[ $item_val ];
				} elseif ( $item_val === '非製品属性タグID' ) { // タグIDは/(スラッシュ)から|(パイプ)に変換
					$new_tag_id_array            = explode( '/', $item_array['タグID'] );
					$new_tag_id                  = implode( '|', $new_tag_id_array );
					$new_item_array[ $item_key ] = $new_tag_id;
				} elseif ( $item_val === 'カタログIDなしの理由' ) { // 5で固定
					$new_item_array[ $item_key ] = '5';
				} elseif ( $item_val === 'キャッチコピー' ) { // モバイル用を入れる
					$new_item_array[ $item_key ] = $item_array['モバイル用キャッチコピー'];
				} elseif ( $item_val === 'SKU管理番号' ) {
					$sku_key                     = $item_key; // SKUスタートの番号を取っておく
					$new_item_array[ $item_key ] = $item_array['商品管理番号（商品URL）'];
				} else {
					$new_item_array[ $item_key ] = '';
				}
			}
			for ( $i = 0; $i < $picture_count; $i++ ) {
				$picture_type_no                   = '商品画像タイプ' . $i + 1;
				$picture_path_no                   = '商品画像パス' . $i + 1;
				$picture_no                        = array_search( $picture_type_no, $new_item_column_array );
				$new_item_array[ $picture_no ]     = 'CABINET';
				$new_item_array[ $picture_no + 1 ] = $picture_last_names[ $i ];
			}
			foreach ( $new_item_array as $new_item_key => $new_item ) {
				if ( $new_item_key !== 0 ) {
					$new_item_value_column .= ',';
				}
				if ( '' !== $new_item ) {
					$new_item_value_column .= '"' . $new_item . '"';
				} else {
					$new_item_value_column .= $new_item;
				}
			}
			$output_column[ $column_count ] = $new_item_value_column;
			$column_count++;
			// 項目選択肢
			$selects      = $n2->portal_setting['楽天']['select'];
			$selects      = str_replace( array( "\r\n", "\r" ), "\n", $selects );// 改行コード統一
			$selects      = preg_split( '/\n{2,}/', $selects );// 連続改行で分ける
			$select_count = count( $selects );
			if ( $select_count > 0 ) {
				$select_no = array_search( '選択肢タイプ', $new_item_column_array );
				foreach ( $selects as $select ) {
					$select_array = array();
					for ( $i = 0; $i < $select_no; $i++ ) {
						$select_array[ $i ] = '';
					}
					$select                     = explode( "\n", $select );
					$select_array[0]            = $item_array['商品管理番号（商品URL）'];
					$select_array[ $select_no ] = 's';
					$new_select_value_column    = '';
					foreach ( $select as $select_key => $value ) {
						$select_array[ $select_no + $select_key + 1 ] = $value;
					}
					foreach ( $select_array as $select_value_key => $select_value ) {
						if ( $select_value_key !== 0 ) {
							$new_select_value_column .= ',';
						}
						if ( '' !== $select_value ) {
							$new_select_value_column .= '"' . $select_value . '"';
						} else {
							$new_select_value_column .= $select_value;
						}
					}
					$output_column[ $column_count ] = $new_select_value_column;
					$column_count++;
				}
			}
			foreach ( $output_column as $output ) {
				echo $output . "\n";
			}
			exit();
			die();
		}
	}
}
