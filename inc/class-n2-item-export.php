<?php
/**
 * class-n2-item-export.php
 *
 * @package neoneng
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'N2_Item_Export' ) ) {
	new N2_Item_Export();
	return;
}

/**
 * Item_Export
 */
class N2_Item_Export {

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		add_action( 'wp_ajax_ledghome', array( $this, 'ledghome' ) );
		add_action( 'wp_ajax_rakuten', array( $this, 'rakuten' ) );
		add_action( 'wp_ajax_item_csv', array( $this, 'item_csv' ) );
		add_action( 'wp_ajax_select_csv', array( $this, 'select_csv' ) );
	}

	/**
	 * download_csv
	 *
	 * @param string $name データ名
	 * @param Array  $header header
	 * @param Array  $items_arr 商品情報配列
	 * @param string $csv_title あれば連結する
	 * @return void
	 */
	private function download_csv( $name, $header, $items_arr, $csv_title = '' ) {
		$csv  = $csv_title . PHP_EOL;
		$csv .= implode( ',', $header ) . PHP_EOL;

		// CSV文字列生成
		foreach ( $items_arr as $item ) {
			foreach ( $header as $head ) {
				$csv .= '"' . $item[ $head ] . '",';
			}
			$csv  = rtrim( $csv, ',' );
			$csv .= PHP_EOL;
		}

		// sjisに変換
		$csv = mb_convert_encoding( $csv, 'SJIS-win', 'utf-8' );

		header( 'Content-Type: application/octet-stream' );
		header( "Content-Disposition: attachment; filename={$name}.csv" );
		echo htmlspecialchars_decode( $csv );

		die();
	}

	/**
	 * iniファイルから取得する際はここに追加する
	 *
	 * @param String $ajax_str csvの種類(iniファイルのセクション名)
	 * @return Array $arr 処理に必要なiniの情報を格納した配列
	 */
	private function get_ini( $ajax_str ) {
		$arr            = array();
		$n2_file_header = parse_ini_file( get_template_directory() . '/config/n2-file-header.ini', true );
		$town           = end( explode( '/', get_option( 'home' ) ) ); // urlから自治体を取得
		// twoncodeを配列として取得
		$n2_towncode = parse_ini_file( get_template_directory() . '/config/n2-towncode.ini', true )[ $town ];
		if ( 'item_csv' === $ajax_str || 'select_csv' === $ajax_str ) { // 楽天の場合
			$header_str = $n2_file_header['rakuten'][ $ajax_str ];
		} else {
			$header_str = $n2_file_header[ $ajax_str ]['csv_header'];
		}
		// アレルゲンをiniファイルから取得して配列にする
		$allergens      = parse_ini_file( get_template_directory() . '/config/n2-fields.ini', true )['アレルゲン']['option'];
		$allergens      = explode( ',', $allergens );
		$allergens_list = array();
		foreach ( $allergens as $allergen ) {
			$allergen                       = explode( '\\', $allergen );
			$allergens_list[ $allergen[0] ] = $allergen[1];
		}

		$arr = array(
			// あとでヘッダの上の連結するのに必要
			'csv_title'       => explode( "\n", $header_str )[0],

			'header'          => explode( ',', explode( "\n", $header_str )[1] ),

			// ajaxで渡ってきたpostidの配列
			'ids'             => explode( ',', filter_input( INPUT_POST, $ajax_str ) ),
			'rakuten_img_dir' => str_replace( 'n2-towncode', $n2_towncode['rakuten'], $n2_file_header['rakuten']['img_dir'] ),
			'allergens'       => $allergens_list,
		);

		return $arr;
	}

	/**
	 * ledghomeのエクスポート用CSV生成
	 *
	 * @return void
	 */
	public function ledghome() {
		// itemの情報を配列か
		$items_arr  = array();
		$header_str = parse_ini_file( get_template_directory() . '/config/n2-file-header.ini', true )['ledghome']['csv_header'];

		// あとでヘッダの上の連結するのに必要
		$csv_title = explode( "\n", $header_str )[0];

		$header = explode( ',', explode( "\n", $header_str )[1] );

		// ajaxで渡ってきたpostidの配列
		$ids = explode( ',', filter_input( INPUT_POST, 'ledghome' ) );

		// プラグイン側でヘッダーを編集
		$header = apply_filters( 'n2_item_export_ledghome_header', $header );

		foreach ( $ids as $id ) {
			$teiki = get_post_meta( $id, '定期便', true );

			for ( $i = 1;$i <= $teiki;$i++ ) {
				$key_id   = 1 < $teiki ? "{$id}_{$i}" : $id;
				$teikinum = 1 < $teiki ? "_{$i}/{$teiki}" : '';
				foreach ( $header as $head ) {
					$items_arr[ $key_id ][ $head ] = ! empty( get_post_meta( $id, $head, true ) ) ? get_post_meta( $id, $head, true ) : '';
				}

				$item_num = trim( strtoupper( get_post_meta( $id, '返礼品コード', true ) ) ) . $teikinum;

				$item_arr = array(
					'謝礼品番号'     => $item_num,
					'謝礼品名'      => $item_num . ' ' . ( get_post_meta( $id, '略称', true ) ? get_post_meta( $id, '略称', true ) : N2_Functions::_s( get_the_title( $id ) ) ),
					'事業者'       => get_the_author_meta( 'display_name', get_post_field( 'post_author', $id ) ),
					'配送名称'      => ( get_post_meta( $id, '配送伝票表示名', true ) ) ? ( $item_num . ' ' . get_post_meta( $id, '配送伝票表示名', true ) ) : $item_num,
					'ふるさとチョイス名' => N2_Functions::_s( get_the_title( $id ) ) . " [{$item_num}]",
					'楽天名称'      => '【ふるさと納税】' . N2_Functions::_s( get_the_title( $id ) ) . " [{$item_num}]",
					'謝礼品カテゴリー'  => get_post_meta( $id, 'カテゴリー', true ),
					'セット内容'     => N2_Functions::_s( get_post_meta( $id, '内容量・規格等', true ) ),
					'謝礼品紹介文'    => N2_Functions::_s( get_post_meta( $id, '説明文', true ) ),
					'ステータ'      => '受付中',
					'状態'        => '表示',
					'寄附設定金額'    => $i < 2 ? get_post_meta( $id, '寄附金額', true ) : 0,
					'送料'        => get_post_meta( $id, '送料', true ),
					'発送方法'      => get_post_meta( $id, '発送方法', true ),
					'申込可能期間'    => '通年',
					'自由入力欄1'    => date( 'Y/m/d' ) . '：' . wp_get_current_user()->display_name,
					'自由入力欄2'    => get_post_meta( $id, '送料', true ),
					'配送サイズコード'  => ( is_numeric( get_post_meta( $id, '発送サイズ', true ) ) ) ? get_post_meta( $id, '発送サイズ', true ) : '',
				);

				// 内容を追加、または上書きするためのフック
				$items_arr[ $key_id ] = apply_filters( 'n2_item_export_ledghome_items', $item_arr, $id );
			}
		}

		$this->download_csv( 'ledghome', $header, $items_arr, $csv_title );
	}

	/**
	 * 楽天のエクスポート用CSV生成(item_csv)
	 *
	 * @return void
	 */
	public function item_csv() {
		$ini_arr       = $this->get_ini( __FUNCTION__ );
		$img_dir       = $ini_arr['rakuten_img_dir'];
		$allergen_list = $ini_arr['allergens'];
		// itemの情報を配列化
		$items_arr = array();

		foreach ( $ini_arr['ids'] as $post_id ) {
			foreach ( $ini_arr['header'] as $head ) {
				$items_arr[ $post_id ][ $head ] = get_post_meta( $post_id, $head, true ) ? : '';
			}
			foreach ( $items_arr[ $post_id ] as $k => $v ) {
				// 初期化
				$c0 = array( '在庫数' );
				$c1 = array( '送料', '代引料', '在庫タイプ', 'カタログIDなしの理由' );
				if ( in_array( $k, $c0, true ) ) {
					$items_arr[ $post_id ][ $k ] = 0;
				} elseif ( in_array( $k, $c1, true ) ) {
					$items_arr[ $post_id ][ $k ] = 1;
				} else {
					$items_arr[ $post_id ][ $k ] = '';
				}
			}
			$item_num  = trim( strtoupper( get_post_meta( $post_id, '返礼品コード', true ) ) );
			$item_arr  = array(
				// 寄附金額が０になってないかチェック
				// '寄附金額エラー' => get_post_meta($post_id, "寄附金額", true ) == 0 ? get_post_meta($post_id, "返礼品コード", true ):'',
				'コントロールカラム'     => 'n',
				'商品管理番号（商品URL）' => trim( mb_strtolower( get_post_meta( $post_id, '返礼品コード', true ) ) ),
				'商品番号'          => $item_num,
				'全商品ディレクトリID'   => get_post_meta( $post_id, '全商品ディレクトリID', true ),
				// 'タグID'          => get_option('N2_setupmenu')['rakuten']['tag_id'],
				// '商品名' => "【ふるさと納税】 ".$items_arr[$post_id]['商品番号']." ".N2_Functions::_s(get_the_title($post_id)),
				// '商品名' => "【ふるさと納税】".N2_Functions::_s(get_the_title($post_id))." ".$items_arr[$post_id]['商品番号'],
				'商品名'           => '【ふるさと納税】' . N2_Functions::_s( get_the_title( $post_id ) ) . " [{$item_num}]",
				'販売価格'          => get_post_meta( $post_id, '寄附金額', true ),
				'のし対応'          => ( '有り' === get_post_meta( $post_id, 'のし対応', true ) ) ? 1 : '',
				'PC用キャッチコピー'    => N2_Functions::_s( get_post_meta( $post_id, 'キャッチコピー１', true ) ),
				'モバイル用キャッチコピー'  => N2_Functions::_s( get_post_meta( $post_id, 'キャッチコピー１', true ) ),
				'PC用商品説明文'      => N2_Functions::nl2br_get_post_meta( $post_id, '説明文' ) . '<br><br>' . N2_Functions::nl2br_get_post_meta( $post_id, '内容量・規格等' ) . '<br>' . PHP_EOL,
				'商品画像URL'       => '',
			);
			$deadlines = array( '賞味期限', '消費期限' );
			foreach ( $deadlines as $deadline ) {
				$item_arr['PC用商品説明文'] .= ( get_post_meta( $post_id, $deadline, true ) )
				? '<br>【' . $deadline . '】<br>' . PHP_EOL . N2_Functions::nl2br_get_post_meta( $post_id, $deadline ) . '<br><br>' . PHP_EOL
				: '';
			}

			// やき物関連
			$item_arr['PC用商品説明文'] .= apply_filters( 'n2_item_export_rakuten_porcelain_text', '', $post_id, '対応機器' ) . '<br>';
			$item_arr['PC用商品説明文'] .= get_post_meta( $post_id, '検索キーワード', true )
			? PHP_EOL . '<br><br>' . ( N2_Functions::_s( get_post_meta( $post_id, '検索キーワード', true ) ) )
			: '';
			$item_arr['PC用商品説明文'] .= PHP_EOL;
			$item_arr['PC用商品説明文'] .= get_post_meta( $post_id, '楽天カテゴリー', true )
			? '<br><br>' . N2_Functions::nl2br_get_post_meta( $post_id, '楽天カテゴリー' )
			: '';
			$item_arr['PC用商品説明文'] .= get_option( 'N2_setupmenu' )['add_text'][ get_bloginfo( 'name' ) ];

			// GOLD（ne.jp）とキャビネット（co.jp）を判定してキャビネットは事業者コードディレクトリを追加
			if ( '' === $img_dir ) {
				echo '<div style="text-align:center;position:fixed;top:50%;width:100%;font-size:40px;margin-top:-200px;"><h1>ERROR</h1><p>自治体コードが設定されていません！エンジニアにご連絡ください。</p></div>';
				die();
			}
			preg_match( '/^[a-z]{2,3}/', $item_arr['商品管理番号（商品URL）'], $m );// 事業者コード
			if ( ! preg_match( '/ne\.jp/', $img_dir ) ) {
				$img_dir .= "/{$m[0]}";// キャビネットの場合事業者コード追加
			}
			for ( $i = 0;$i < 15;$i++ ) {
				$img_url = "{$img_dir}/{$item_arr['商品管理番号（商品URL）']}";
				if ( 0 !== $i ) {
					$img_url .= "-{$i}";
				}
				$img_url .= '.jpg';
				$response = wp_remote_get( $img_url );
				if ( ! is_wp_error( $response ) && 200 === $response['response']['code'] ) {
					if ( 0 !== $i ) {
						$item_arr['商品画像URL'] .= ' ';
					}
					$item_arr['商品画像URL'] .= $img_url;
				}
			}
			if ( '' === $item_arr['商品画像URL'] ) {// 商品画像が無い場合には処理を止める
				echo '<div style="text-align:center;position:fixed;top:50%;width:100%;font-size:40px;margin-top:-200px;"><h1>ERROR</h1><p>商品画像を先にアップロードしてください！</p></div>';
				die();
			}

			$allergy_display = '';
			if ( get_post_meta( $post_id, 'アレルゲン', true ) || get_post_meta( $post_id, 'アレルゲン注釈', true ) ) {
				$allergens = '';
				foreach ( get_post_meta( $post_id, 'アレルゲン', true )  as $v ) {
					$allergens .= $allergen_list[ $v ] . '・';
				}
				$allergens          = rtrim( $allergens, '・' );
				$allergy_annotation = get_post_meta( $post_id, 'アレルゲン注釈', true ) ?: '';
				if ( '' !== $allergens ) {
					$allergy_display = $allergens . ( $allergy_annotation ? '<br>※' . $allergy_annotation : '' );
				} else {
					$allergy_display = $allergy_annotation ?: '';
				}
			}

			$itemtable = '<!-- 商品説明テーブル --><p><b><font size=""5"">商品説明</font></b></p><hr noshade color=""black""><br>' . PHP_EOL . '<table border=""1"" width=""100%"" cellspacing=""0"" cellpadding=""10"" bordercolor=""black"">' . PHP_EOL . '<tr><th>名称</th><td>'
			. ( N2_Functions::_s( get_post_meta( $post_id, '表示名称', true ) )
				?: N2_Functions::_s( get_post_meta( $post_id, '略称', true ) )
				?: N2_Functions::_s( get_the_title( $post_id ) )
			) . '</td></tr>' . PHP_EOL . '<tr><th>内容量</th><td>' . N2_Functions::nl2br_get_post_meta( $post_id, '内容量・規格等' )
			. ( apply_filters( 'n2_item_export_rakuten_porcelain_text', '', $post_id, '対応機器' ) ) . '</td></tr>'
			. (
				( '' !== get_post_meta( $post_id, '賞味期限', true ) )
					? PHP_EOL . '<tr><th>賞味期限</th><td>' . PHP_EOL . N2_Functions::nl2br_get_post_meta( $post_id, '賞味期限' ) . PHP_EOL . '</td></tr>'
					: ''
			) . (
				( '' !== get_post_meta( $post_id, '消費期限', true ) )
					? PHP_EOL . '<tr><th>消費期限</th><td>' . PHP_EOL . N2_Functions::nl2br_get_post_meta( $post_id, '消費期限' ) . '</td></tr>'
					: ''
			) . (
				( '' !== $allergy_display )
					? PHP_EOL . '<tr><th>アレルギー表示</th><td>' . $allergy_display . '</td></tr>'
					: ''
			) . PHP_EOL . '<tr><th>配送方法</th><td>' . N2_Functions::_s( get_post_meta( $post_id, '発送方法', true ) ) . '</td></tr>' . PHP_EOL . '<tr><th>配送期日</th><td>' . N2_Functions::nl2br_get_post_meta( $post_id, '配送期間' ) . '</td></tr>' . PHP_EOL
			. (
				'記載しない' === get_the_author_meta( 'portal', get_post_field( 'post_author', $post_id ) )
					? ''
					: '<tr><th>提供事業者</th><td>'
				. ( get_post_meta( $post_id, '提供事業者名', true )
					?: ( preg_replace(
						'/\（.+?\）/',
						'',
						(
							get_the_author_meta( 'portal', get_post_field( 'post_author', $post_id ) )
								?: get_the_author_meta( 'first_name', get_post_field( 'post_author', $post_id ) )
						)
					) )
				) . '</td></tr>'
			) . '</table><!-- /商品説明テーブル -->';

			$item_arr['PC用販売説明文']  = '<img src=""' . str_replace( ' ', '"" width=""100%"">' . PHP_EOL . '<img src=""', $item_arr['商品画像URL'] ) . '"" width=""100%""><br><br>' . PHP_EOL . $itemtable . PHP_EOL . '<br><br>' . PHP_EOL . get_option( 'N2_setupmenu' )['add_text'][ get_bloginfo( 'name' ) ];
			$item_arr['PC用販売説明文'] .= apply_filters( 'n2_item_export_rakuten_porcelain_text', '', $post_id, 'PC用販売説明文' ) . str_replace( '\"', '""', get_option( 'N2_setupmenu' )['rakuten']['html'] );

			$item_arr['スマートフォン用商品説明文'] = PHP_EOL . '<img src=""' . str_replace( ' ', '"" width=""100%"">' . PHP_EOL . '<img src=""', $item_arr['商品画像URL'] ) . '"" width=""100%""><br><br>' . PHP_EOL . N2_Functions::nl2br_get_post_meta( $post_id, '説明文' ) . '<br><br>' . PHP_EOL . $itemtable
			. (
				get_post_meta( $post_id, '検索キーワード', true )
					? PHP_EOL . '<br><br>' . ( N2_Functions::_s( get_post_meta( $post_id, '検索キーワード', true ) ) )
					: ''
			) . PHP_EOL
			. (
				get_post_meta( $post_id, '楽天カテゴリー', true )
					? '<br><br>' . N2_Functions::nl2br_get_post_meta( $post_id, '楽天カテゴリー' )
					: ''
			) . PHP_EOL . get_option( 'N2_setupmenu' )['add_text'][ get_bloginfo( 'name' ) ] . str_replace( '\"', '""', get_option( 'N2_setupmenu' )['rakuten']['html'] );
			// 内容を追加、または上書きするためのフック
			$items_arr[ $post_id ] = array_merge(
				$items_arr[ $post_id ],
				apply_filters( 'n2_item_export_rakuten_item_csv', $item_arr, $post_id )
			);
		}
		$this->download_csv( 'rakuten_' . __FUNCTION__, $ini_arr['header'], $items_arr, $ini_arr['csv_title'] );
		die();
	}

	/**
	 * 楽天のエクスポート用CSV生成(select_csv)
	 *
	 * @return void
	 */
	public function select_csv() {
		// itemの情報を配列化
		$items_arr = array();
		$ini_arr   = $this->get_ini( __FUNCTION__ );

		// select項目名 => array(選択肢)の形式に変換
		$select = array();

		// 自治体ごとに項目内容が違う可能性あり？
		$opt = parse_ini_file( get_template_directory() . '/config/n2-file-header.ini', true )['rakuten']['select'];
		foreach ( $opt as $v ) {
			if ( '' !== $v ) {
				$arr                       = explode( "\n", $v );
				$select[ trim( $arr[0] ) ] = explode( ',', $arr[1] );
			}
		}
		// 初期化
		$i = 0;
		foreach ( $ini_arr['ids'] as $post_id ) {
			// 連想配列作成
			foreach ( $select as $key => $value ) {
				foreach ( $value as $v ) {

					foreach ( $ini_arr['header'] as $head ) {
						$items_arr[ $i ][ $head ] = get_post_meta( $post_id, $head, true ) ? : '';
					}
					$item_arr        = array(
						'項目選択肢用コントロールカラム' => 'n',
						'商品管理番号（商品URL）'   => trim( mb_strtolower( get_post_meta( $post_id, '返礼品コード', true ) ) ),
						'選択肢タイプ'          => 's',
						'項目選択肢項目名'        => $key,
						'項目選択肢'           => trim( $v ),
						'項目選択肢選択必須'       => '1',
					);
					$items_arr[ $i ] = array_merge( $items_arr[ $i ], $item_arr );
					++$i;
				}
			}
		}
		$this->download_csv( 'rakuten_' . __FUNCTION__, $ini_arr['header'], $items_arr, $ini_arr['csv_title'] );
		die();
	}
}
