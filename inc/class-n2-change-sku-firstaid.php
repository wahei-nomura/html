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
		$defaults = array(
			'mode' => 'ui',
		);
		$params   = wp_parse_args( $_GET, $defaults );
		switch ( $params['mode'] ) {
			case 'ui':
				get_template_part( 'template/change-sku' );
				exit;
			default:
				$files          = $_FILES['item_files'];
				$csv_item_flg   = 0;
				$csv_select_flg = 0;
				// csvの読み込み&配列格納
				for ( $i = 0; $i < count( $files['name'] ); $i++ ) {
					$filename         = $files['name'][ $i ];
					$filetmp          = $files['tmp_name'][ $i ];
					$filesize         = $files['size'][ $i ];
					$fileerror        = $files['error'][ $i ];
					$result_item      = preg_match( '/.*item.*/', $filename );
					$result_select    = preg_match( '/.*select.*/', $filename );
					$fn               = fopen( $filetmp, 'r' );
					$countloop        = 0;
					$csv_header_array = array();
					while ( ( $arr = fgetcsv( $fn ) ) != false ) {
						${'csv_array_' . $countloop} = array();
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
						$csv_item_count = 0;
						for ( $j = 0; $j < $countloop; ++$j ) {
							if ( 0 == $j ) {
								$csv_item_array[] = $csv_header_array;
							} else {
								$csv_item_array[] = ${'csv_array_' . $j};
							}
						}
						$csv_item_count = $countloop;
						$csv_item_flg   = 1;
					}
					if ( $result_select ) {
						// select.csvの要素を配列($csv_select_array)に格納
						$csv_select_array = array();
						$csv_select_count = 0;
						for ( $j = 1; $j < $countloop; ++$j ) {
							if ( ${'csv_array_' . $j}[3] !== $csv_select_array[ $csv_select_count ][2] ) {
								$csv_select_count++;
								$csv_select_array[ $csv_select_count ][0] = ${'csv_array_' . $j}[1];
								$csv_select_array[ $csv_select_count ][1] = 's';
								$csv_select_array[ $csv_select_count ][2] = ${'csv_array_' . $j}[3];
							}
							$csv_select_array[ $csv_select_count ][] = ${'csv_array_' . $j}[4];
						}
						$csv_select_flg = 1;
					}
				}
				if ( ! $csv_item_flg || ! $csv_select_flg ) {
					if ( ! $csv_item_flg ) {
						print_r( 'item.csvがアップロードされていません。(ファイル名に「item」が入っていない場合もこのエラーが出ます)' );
					}
					if ( ! $csv_select_flg ) {
						print_r( 'select.csvがアップロードされていません。(ファイル名に「select」が入っていない場合もこのエラーが出ます)' );
					}
					exit();
					die();
				} else {
					$this->output_csv( $csv_item_array, $csv_select_array );
				}
		}
	}
	public function output_csv( $csv_item_array, $csv_select_array ) {
		header( 'Content-Type: application/json; charset=utf-8' );
		setlocale( LC_ALL, 'ja_JP.UTF-8' );
		$new_item_column = '商品管理番号（商品URL）,商品番号,商品名,倉庫指定,ジャンルID,非製品属性タグID,キャッチコピー,PC用商品説明文,スマートフォン用商品説明文,PC用販売説明文,商品画像タイプ1,商品画像パス1,商品画像名（ALT）1,商品画像タイプ2,商品画像パス2,商品画像名（ALT）2,商品画像タイプ3,商品画像パス3,商品画像名（ALT）3,商品画像タイプ4,商品画像パス4,商品画像名（ALT）4,商品画像タイプ5,商品画像パス5,商品画像名（ALT）5,商品画像タイプ6,商品画像パス6,商品画像名（ALT）6,商品画像タイプ7,商品画像パス7,商品画像名（ALT）7,商品画像タイプ8,商品画像パス8,商品画像名（ALT）8,商品画像タイプ9,商品画像パス9,商品画像名（ALT）9,商品画像タイプ10,商品画像パス10,商品画像名（ALT）10,商品画像タイプ11,商品画像パス11,商品画像名（ALT）11,商品画像タイプ12,商品画像パス12,商品画像名（ALT）12,商品画像タイプ13,商品画像パス13,商品画像名（ALT）13,商品画像タイプ14,商品画像パス14,商品画像名（ALT）14,商品画像タイプ15,商品画像パス15,商品画像名（ALT）15,商品画像タイプ16,商品画像パス16,商品画像名（ALT）16,商品画像タイプ17,商品画像パス17,商品画像名（ALT）17,商品画像タイプ18,商品画像パス18,商品画像名（ALT）18,商品画像タイプ19,商品画像パス19,商品画像名（ALT）19,商品画像タイプ20,商品画像パス20,商品画像名（ALT）20,バリエーション項目キー定義,バリエーション項目名定義,バリエーション1選択肢定義,バリエーション2選択肢定義,バリエーション3選択肢定義,バリエーション4選択肢定義,バリエーション5選択肢定義,選択肢タイプ,商品オプション項目名,商品オプション選択肢1,商品オプション選択肢2,商品オプション選択肢3,商品オプション選択肢4,商品オプション選択肢5,商品オプション選択肢6,商品オプション選択肢7,商品オプション選択肢8,商品オプション選択肢9,商品オプション選択必須,SKU管理番号,システム連携用SKU番号,販売価格,再入荷お知らせボタン,のし対応,在庫数,在庫あり時納期管理番号,送料,カタログIDなしの理由';
		$output_data     = $new_item_column . "\n";
		$output_array[]  = $new_item_column;
		// １行目：新ヘッダー
		$new_item_column_array = explode( ',', $new_item_column );
		$new_item_count        = count( $new_item_column_array );
		// selectの出力データを作る(商品管理番号はまだ入れない)
		$output_select    = '';
		$select_no        = array_search( '選択肢タイプ', $new_item_column_array );
		$select_option_no = array_search( '商品オプション選択必須', $new_item_column_array );
		foreach ( $csv_select_array as $csv_select ) {
			$select_array            = array();
			$new_select_value_column = '';
			for ( $i = 0; $i < $new_item_count; $i++ ) {
				if ( $i === $select_option_no ) {
					$select_array[ $i ] = '1';
				} else {
					$select_array[ $i ] = '';
				}
			}
			$select_array[0] = $csv_select[0]; // 一番最初に返礼品コードセット
			foreach ( $csv_select as $select_key => $value ) {
				if ( 0 !== $select_key ) { // 返礼品コードは1列目にセットしたのでそれ以外を入れていく
					$select_array[ $select_no + $select_key - 1 ] = $value;
				}
			}
			foreach ( $select_array as $select_value_key => $select_value ) {
				if ( $select_value_key !== 0 ) {
					$new_select_value_column .= ',';
				}
				$new_select_value_column .= $select_value;
			}
			$output_select_array[ $csv_select[0] ][] = $new_select_value_column;
			$output_select                          .= $new_select_value_column . "\n";
		}
		// itemの出力データを作る
		$picture_no = array_search( '商品画像URL', $csv_item_array[0] );
		foreach ( $csv_item_array as $item_key => $csv_item ) {
			$picture_last_names = array();
			if ( $item_key !== 0 ) { // headerは除外
				// 商品画像URLを分割
				if ( isset( $csv_item[ $picture_no ] ) && '' !== $csv_item[ $picture_no ] ) {
					$picture_array = explode( ' ', $csv_item[ $picture_no ] );
					foreach ( $picture_array as $picture_key => $picture_val ) {
						$picture_name         = explode( '/', $picture_val );
						$picture_last_names[] = '/' . $picture_name[ array_key_last( $picture_name ) - 2 ] . '/' . $picture_name[ array_key_last( $picture_name ) - 1 ] . '/' . $picture_name[ array_key_last( $picture_name ) ];
					}
					$picture_count = count( $picture_last_names );
				}
				foreach ( $new_item_column_array as $new_item_key => $item_val ) {
					if ( array_search( $item_val, $csv_item_array[0] ) ) {
						if ( $item_val === '販売価格' ) {
							$price_no                        = array_search( $item_val, $csv_item_array[0] );
							$new_price_no                    = $new_item_key;
							$price_value                     = $csv_item[ $price_no ];
							$new_item_array[ $new_item_key ] = '';
						} elseif ( $item_val === 'のし対応' ) {
							$noshi_no                        = array_search( $item_val, $csv_item_array[0] );
							$new_noshi_no                    = $new_item_key;
							$noshi_value                     = $csv_item[ $noshi_no ];
							$new_item_array[ $new_item_key ] = '';
						} elseif ( $item_val === '在庫数' ) {
							$zaiko_no                        = array_search( $item_val, $csv_item_array[0] );
							$new_zaiko_no                    = $new_item_key;
							$zaiko_value                     = $csv_item[ $zaiko_no ];
							$new_item_array[ $new_item_key ] = '';
						} elseif ( $item_val === 'PC用商品説明文' ) {
							$pc_item_no                      = array_search( $item_val, $csv_item_array[0] );
							$new_pc_item_no                  = $new_item_key;
							$new_item_array[ $new_item_key ] = $csv_item[ $pc_item_no ];
						} elseif ( $item_val === 'スマートフォン用商品説明文' ) {
							$sp_item_no                      = array_search( $item_val, $csv_item_array[0] );
							$new_sp_item_no                  = $new_item_key;
							$new_item_array[ $new_item_key ] = $csv_item[ $sp_item_no ];
						} elseif ( $item_val === 'PC用販売説明文' ) {
							$pc_sale_no                      = array_search( $item_val, $csv_item_array[0] );
							$new_pc_sale_no                  = $new_item_key;
							$new_item_array[ $new_item_key ] = $csv_item[ $pc_sale_no ];
						} elseif ( $item_val === '送料' ) {
							$postage_no                      = array_search( $item_val, $csv_item_array[0] );
							$new_postage_no                  = $new_item_key;
							$postage_value                   = $csv_item[ $postage_no ];
							$new_item_array[ $new_item_key ] = '';
						} elseif ( $item_val === 'カタログIDなしの理由' ) {
							$catalog_no                      = array_search( $item_val, $csv_item_array[0] );
							$new_catalog_no                  = $new_item_key;
							$catalog_value                   = '5'; // 5で固定
							$new_item_array[ $new_item_key ] = '';
						} else {
							$item_val_no = array_search( $item_val, $csv_item_array[0] );
							if ( $item_val === '商品管理番号（商品URL）' ) {
								$code_value = $csv_item[ $item_val_no ];
							} elseif ( $item_val === '商品番号' ) {
								$large_code_value = $csv_item[ $item_val_no ];
							}
							$new_item_array[ $new_item_key ] = $csv_item[ $item_val_no ];

						}
					} elseif ( $item_val === '非製品属性タグID' ) { // タグIDは/(スラッシュ)から|(パイプ)に変換
						$tag_id_no                       = array_search( 'タグID', $csv_item_array[0] );
						$new_tag_id_array                = explode( '/', $csv_item[ $tag_id_no ] );
						$new_tag_id                      = implode( '|', $new_tag_id_array );
						$new_item_array[ $new_item_key ] = $new_tag_id;
					} elseif ( $item_val === 'キャッチコピー' ) { // モバイル用を入れる
						$catchcopy_no                    = array_search( 'モバイル用キャッチコピー', $csv_item_array[0] );
						$new_item_array[ $new_item_key ] = $csv_item[ $catchcopy_no ];
					} elseif ( $item_val === 'SKU管理番号' ) {
						$sku_key                         = $new_item_key; // SKUスタートの番号を取っておく
						$new_item_array[ $new_item_key ] = '';
					} elseif ( $item_val === 'ジャンルID' ) {
						$catchcopy_no                    = array_search( '全商品ディレクトリID', $csv_item_array[0] );
						$new_item_array[ $new_item_key ] = $csv_item[ $catchcopy_no ];
					} elseif ( $item_val === '倉庫指定' ) {
						$new_item_array[ $new_item_key ] = 0;
					} else {
						$new_item_array[ $new_item_key ] = '';
					}
				}
				for ( $i = 0; $i < $picture_count; $i++ ) {
					$picture_type_no                       = '商品画像タイプ' . $i + 1;
					$picture_path_no                       = '商品画像パス' . $i + 1;
					$new_picture_no                        = array_search( $picture_type_no, $new_item_column_array );
					$new_item_array[ $new_picture_no ]     = 'CABINET';
					$new_item_array[ $new_picture_no + 1 ] = $picture_last_names[ $i ];
				}
				$output_new_item_data = '';
				foreach ( $new_item_array as $new_item_key => $new_item ) {
					if ( $new_item_key !== 0 ) {
						$output_data          .= ',';
						$output_new_item_data .= ',';
					}
					if ( '' !== $new_item && ( $new_pc_sale_no === $new_item_key || $new_pc_item_no === $new_item_key || $new_sp_item_no === $new_item_key ) ) {
						$output_data          .= '"' . $new_item . '"';
						$output_new_item_data .= '"' . $new_item . '"';
					} else {
						$output_data          .= $new_item;
						$output_new_item_data .= $new_item;
					}
				}
				$output_array[] = $output_new_item_data;
				$output_data   .= "\n";
				// select出力
				$new_output_select = $output_select;
				$output_data      .= $new_output_select;
				foreach ( $output_select_array[ $code_value ] as $output_select_val ) {
					$output_array[] = $output_select_val;
				}
				// SKU出力
				$sku_data = '';
				for ( $k = 0; $k < $new_item_count;$k++ ) {
					if ( $k === 0 ) {
						$sku_data .= $code_value;
					} elseif ( $k === $sku_key ) {
						$sku_data .= $code_value;
					} elseif ( $k === $new_price_no ) {
						$sku_data .= $price_value;
					} elseif ( $k === $new_noshi_no ) {
						$sku_data .= $noshi_value;
					} elseif ( $k === $new_zaiko_no ) {
						$sku_data .= $zaiko_value;
					} elseif ( $k === $new_postage_no ) {
						$sku_data .= $postage_value;
					} elseif ( $k === $new_system_sku_no ) {
						$sku_data .= $large_code_value;
					} elseif ( $k === $new_catalog_no ) {
						$sku_data .= $catalog_value;
					}
					if ( $k < $new_item_count - 1 ) {
						$sku_data .= ',';
					}
				}
				$output_array[] = $sku_data;
				$sku_data      .= "\n";
				$output_data   .= $sku_data;
			}
		}
		// CSVファイルでダウンロード
		$csvfilename    = 'normal-item.csv';
		$csvfilepathdir = sys_get_temp_dir();
		// CSVファイルを作成する一時ファイルのパス
		$csvfilepath = $csvfilepathdir . '/normal-item.csv';
		// 一時的なCSVファイルを作成
		$csvfile = fopen( $csvfilepath, 'w' );
		// テキストファイルの各行をCSV形式に変換して書き込む
		foreach ( $output_array as $line ) {
			$csvline = mb_convert_encoding( $line, 'SJIS-win', 'UTF-8' ); // エンコーディングをShift-JISに変換
			fputcsv( $csvfile, str_getcsv( $csvline, ',', '"', '\\' ) ); // エスケープ処理を追加
		}
		fclose( $csvfile );
		// ダウンロードヘッダを設定
		header( 'Content-Type: application/csv' );
		header( 'Content-Disposition: attachment; filename=' . $csvfilename );
		header( 'Content-Length: ' . filesize( $csvfilepath ) );
		// 一時ファイルを出力し、ダウンロードさせる
		readfile( $csvfilepath );
		// 一時ファイルを削除
		unlink( $csvfilepath );
		exit();
		die();
	}
}
