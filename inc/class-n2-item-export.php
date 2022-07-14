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
		$arr = array();
		// ini一覧
		$n2_fields      = parse_ini_file( get_template_directory() . '/config/n2-fields.ini', true );
		$n2_towncodes   = parse_ini_file( get_template_directory() . '/config/n2-towncode.ini', true );
		$n2_file_header = parse_ini_file( get_template_directory() . '/config/n2-file-header.ini', true );

		// urlから自治体を取得
		$n2_towncode = $n2_towncodes[ end( explode( '/', get_option( 'home' ) ) ) ];

		// twoncodeを配列として取得
		if ( 'item_csv' === $ajax_str || 'select_csv' === $ajax_str ) { // 楽天の場合
			$header_str = $n2_file_header['rakuten'][ $ajax_str ];
		} else {
			$header_str = $n2_file_header[ $ajax_str ]['csv_header'];
		}

		// アレルゲンをiniファイルから取得して配列にする
		$allergens      = explode( ',', $n2_fields['アレルゲン']['option'] );
		$allergens_list = array();
		foreach ( $allergens as $allergen ) {
			$allergen                       = explode( '\\', $allergen );
			$allergens_list[ $allergen[0] ] = $allergen[1];
		}

		// クルーセットアップで設定した項目を取得
		$rakuten_select_option = get_option( 'N2_setupmenu' )['rakuten']['select'] ?? '';

		$arr = array(
			// あとでヘッダの上の連結するのに必要
			'csv_title'             => explode( "\n", $header_str )[0],
			// プラグイン側でヘッダーを編集
			'header'                => apply_filters( 'n2_item_export_' . $ajax_str . '_header', explode( ',', explode( "\n", $header_str )[1] ) ),
			// ajaxで渡ってきたpostidの配列
			'ids'                   => explode( ',', filter_input( INPUT_POST, $ajax_str, FILTER_SANITIZE_ENCODED ) ),
			'rakuten_img_dir'       => str_replace( 'n2-towncode', $n2_towncode['rakuten'], $n2_file_header['rakuten']['img_dir'] ),
			'allergens'             => $allergens_list,
			'rakuten_select_option' => $rakuten_select_option,
		);
		return $arr;
	}

	/**
	 * ledghomeのエクスポート用CSV生成
	 *
	 * @return void
	 */
	public function ledghome() {
		$ini_arr = $this->get_ini( __FUNCTION__ );

		// itemの情報を配列か
		$items_arr = array();
		// get_post_metaのkey
		$post_keys = array(
			'定期便',
			'返礼品コード',
			'略称',
			'配送伝票表示名',
			'カテゴリー',
			'内容量・規格等',
			'説明文',
			'寄附金額',
			'送料',
			'発送方法',
			'発送サイズ',
		);

		foreach ( $ini_arr['ids'] as $id ) {
			// get_post_metaを一括取得
			$post_meta_list = N2_Functions::get_post_meta_multiple( $id, $post_keys );
			$teiki          = $post_meta_list['定期便'];

			for ( $i = 1; $i <= $teiki; ++$i ) {
				$key_id   = 1 < $teiki ? "{$id}_{$i}" : $id;
				$teikinum = 1 < $teiki ? "_{$i}/{$teiki}" : '';
				// headerの項目を取得する
				$items_arr[ $key_id ] = N2_Functions::get_post_meta_multiple( $id, $ini_arr['header'] );

				$item_num = trim( strtoupper( $post_meta_list['返礼品コード'] ) ) . $teikinum;

				$item_arr = array(
					'謝礼品番号'     => $item_num,
					'謝礼品名'      => $item_num . ' ' . ( $post_meta_list['略称'] ? $post_meta_list['略称'] : N2_Functions::_s( get_the_title( $id ) ) ),
					'事業者'       => get_the_author_meta( 'display_name', get_post_field( 'post_author', $id ) ),
					'配送名称'      => ( $post_meta_list['配送伝票表示名'] ) ? ( $item_num . ' ' . $post_meta_list['配送伝票表示名'] ) : $item_num,
					'ふるさとチョイス名' => N2_Functions::_s( get_the_title( $id ) ) . " [{$item_num}]",
					'楽天名称'      => '【ふるさと納税】' . N2_Functions::_s( get_the_title( $id ) ) . " [{$item_num}]",
					'謝礼品カテゴリー'  => $post_meta_list['カテゴリー'],
					'セット内容'     => N2_Functions::_s( $post_meta_list['内容量・規格等'] ),
					'謝礼品紹介文'    => N2_Functions::_s( $post_meta_list['説明文'] ),
					'ステータ'      => '受付中',
					'状態'        => '表示',
					'寄附設定金額'    => $i < 2 ? $post_meta_list['寄附金額'] : 0,
					'送料'        => $post_meta_list['送料'],
					'発送方法'      => $post_meta_list['発送方法'],
					'申込可能期間'    => '通年',
					'自由入力欄1'    => date( 'Y/m/d' ) . '：' . wp_get_current_user()->display_name,
					'自由入力欄2'    => $post_meta_list['送料'],
					'配送サイズコード'  => ( is_numeric( $post_meta_list['発送サイズ'] ) ) ? $post_meta_list['発送サイズ'] : '',
				);

				// 内容を追加、または上書きするためのフック
				$items_arr[ $key_id ] = apply_filters( 'n2_item_export_ledghome_items', $item_arr, $id );
			}
		}

		$this->download_csv( 'ledghome', $ini_arr['header'], $items_arr, $ini_arr['csv_title'] );
	}

	/**
	 * 楽天のエクスポート用CSV生成(item_csv)
	 *
	 * @return void
	 */
	public function item_csv() {
		// iniから情報を取得
		$ini_arr = $this->get_ini( __FUNCTION__ );

		// 初期化
		$items_arr = array();
		$check_arr = array();
		// get_post_metaのkey一覧
		$post_keys = array(
			'返礼品コード',
			'全商品ディレクトリID',
			'タグID',
			'寄附金額',
			'のし対応',
			'キャッチコピー',
			'説明文',
			'賞味期限',
			'消費期限',
			'検索キーワード',
			'楽天カテゴリー',
		);

		foreach ( $ini_arr['ids'] as $post_id ) {
			// headerの項目を取得
			$items_arr[ $post_id ] = N2_Functions::get_post_meta_multiple( $post_id, $ini_arr['header'] );

			// 初期化
			foreach ( $items_arr[ $post_id ] as $k => $v ) {
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

			// get_post_meta格納用
			$post_meta_list = N2_Functions::get_post_meta_multiple( $post_id, $post_keys );
			$item_num       = trim( strtoupper( $post_meta_list['返礼品コード'] ) );
			$item_num_low   = trim( mb_strtolower( $post_meta_list['返礼品コード'] ) );
			$img_dir        = $ini_arr['rakuten_img_dir'];
			// GOLD（ne.jp）とキャビネット（co.jp）を判定してキャビネットは事業者コードディレクトリを追加
			preg_match( '/^[a-z]{2,3}/', $item_num_low, $m );// 事業者コード
			if ( ! preg_match( '/ne\.jp/', $img_dir ) ) {
				$img_dir .= "/{$m[0]}";// キャビネットの場合事業者コード追加
			}

			// サーバーにある画像urlを文字連結
			$check_img_urls = function () use ( $item_num_low, $img_dir ) {
				$arr      = '';
				$img_url  = "{$img_dir}/{$item_num_low}.jpg";
				$response = wp_remote_get( $img_url );
				if ( ! is_wp_error( $response ) && 200 === $response['response']['code'] ) {
					$arr = $img_url;
				}
				for ( $i = 1;$i < 15; $i++ ) {
					$img_url  = "{$img_dir}/{$item_num_low}-{$i}.jpg";
					$response = wp_remote_get( $img_url );
					if ( ! is_wp_error( $response ) && 200 === $response['response']['code'] ) {
						$arr .= ' ' . $img_url;
					}
				}
				return $arr;
			};
			// 画像URL一覧(文字列)
			$img_urls_str = $check_img_urls();
			// [html]画像URL一覧
			$img_urls_html = function() use ($img_urls_str) {
				$img_urls_arr = explode(' ', $img_urls_str);
				?>
				<?php foreach ($img_urls_arr as $index => $img_url): ?>
				<?php if($index === array_key_last($img_urls_arr)) : ?>
					<img src=""<?php echo $img_url; ?>"" width=""100%""><br><br>
				<?php else : ?>
					<img src=""<?php echo $img_url; ?>"" width=""100%"">
				<?php endif; ?>
				<?php endforeach; ?>
				<?php
			};
			// [html]商品説明テーブル
			$itemtable_html = fn() => $this->make_itemtable( $post_id, false );
			
			// [html]PC用販売説明文
			$pc_sales_description_html = function() use ( $itemtable_html, $post_id, $img_urls_html ) {	
				?>
				<?php $img_urls_html(); ?>
				<?php $itemtable_html(); ?><br><br>
				<?php echo get_option( 'N2_setupmenu' )['add_text'][ get_bloginfo( 'name' ) ] . apply_filters( 'n2_item_export_rakuten_porcelain_text', '', $post_id, 'PC用販売説明文' ) . str_replace( '\"', '""', get_option( 'N2_setupmenu' )['rakuten']['html'] );
			};

			// [html]スマートフォン用商品説明文
			$sp_item_description_html = function () use ( $itemtable_html, $post_meta_list, $img_urls_html ) {
				$sp_formatter = fn( $key ) => nl2br( N2_Functions::_s( $post_meta_list[ $key ] ) );
				?>
				<?php $img_urls_html(); ?>
				<?php echo $sp_formatter( '説明文' ) ?><br><br>
				<?php $itemtable_html() ?>
				<?php if ( $post_meta_list['検索キーワード'] ) : ?>
					<br><br><?php echo $sp_formatter( '検索キーワード' ) ?>
				<?php endif; ?>
				<?php if ( $post_meta_list['楽天カテゴリー'] ) : ?>
					<br><br><?php echo $sp_formatter( '楽天カテゴリー' ) ?>
				<?php endif ?>
				<?php echo get_option( 'N2_setupmenu' )['add_text'][ get_bloginfo( 'name' ) ] . str_replace( '\"', '""', get_option( 'N2_setupmenu' )['rakuten']['html'] );
			};


			$item_arr = array(
				'コントロールカラム'     => 'n',
				'商品管理番号（商品URL）' => trim( mb_strtolower( $post_meta_list['返礼品コード'] ) ),
				'商品番号'          => $item_num,
				'全商品ディレクトリID'   => $post_meta_list['全商品ディレクトリID'],
				'タグID'          => $post_meta_list['タグID'],
				// '商品名' => "【ふるさと納税】 ".$items_arr[$post_id]['商品番号']." ".N2_Functions::_s(get_the_title($post_id)),
				// '商品名' => "【ふるさと納税】".N2_Functions::_s(get_the_title($post_id))." ".$items_arr[$post_id]['商品番号'],
				'商品名'           => '【ふるさと納税】' . N2_Functions::_s( get_the_title( $post_id ) ) . " [{$item_num}]",
				'販売価格'          => $post_meta_list['寄附金額'],
				'のし対応'          => ( '有り' === $post_meta_list['のし対応'] ) ? 1 : '',
				'PC用キャッチコピー'    => N2_Functions::_s( $post_meta_list['キャッチコピー'] ),
				'モバイル用キャッチコピー'  => N2_Functions::_s( $post_meta_list['キャッチコピー'] ),
				'商品画像URL'       => $img_urls_str,
				'PC用商品説明文'      => $this->pc_item_description( $post_id, $post_meta_list ),
				'PC用販売説明文'      => PHP_EOL . N2_Functions::html2str($pc_sales_description_html),
				'スマートフォン用商品説明文' => PHP_EOL . N2_Functions::html2str($sp_item_description_html),
			);

			// 内容を追加、または上書きするためのフック
			$items_arr[ $post_id ] = array_merge(
				$items_arr[ $post_id ],
				apply_filters( 'n2_item_export_rakuten_item_csv', $item_arr, $post_id )
			);

			if ( strpos( $img_dir, 'n2-towncode' ) ) {
				echo '<div style="text-align:center;position:fixed;top:50%;width:100%;font-size:40px;margin-top:-200px;"><h1>ERROR</h1><p>自治体コードが設定されていません！エンジニアにご連絡ください。</p></div>';
				die();
			}

			$check_error = function() use($item_arr,$post_meta_list,&$check_arr,$item_num){
				$arr = array(
					array(
						'condition' => ! $item_arr['商品画像URL'],
						'message' => '商品画像を先にアップロードしてください！',
						'header' => '商品コード',
					),
					array(
						'condition' => ! $post_meta_list["寄附金額"],
						'message' => '寄附金額を設定してください！',
						'header' => '商品コード',
					),
					
				);
				foreach( $arr as $err ) {
					if ( $err['condition'] ) {
						// 初期化
						if ( ! isset( $check_arr[$err['message']][$err['header']] ) ) {
							$check_arr[$err['message']][$err['header'] ] = array();
						}
						array_push( $check_arr[$err['message']][$err['header']], $item_num );
					}
				}
			};
			$check_error();
			// 商品画像が無い場合
		}
		// 商品画像が無い場合には処理を止める
		if ( $check_arr ) {
			?>
			<div class='n2-errors'>
			<h1>ERROR</h1>
			<?php foreach ( $check_arr as $error_message => $errors ) : ?>
				<?php foreach ( $errors as $error_header => $error_contents ) : ?>
				<h2><?php echo $error_message; ?></h2>
				<div>
					<h3><?php echo $error_header; ?></h3>
					<ul>
					<?php foreach ( $error_contents as $content ) : ?>
						<label><li><input type="checkbox"><?php echo $content; ?></li></label>
					<?php endforeach; ?>
					</ul>
				</div>
				<?php endforeach ?>
			<?php endforeach ?>
			</div>
			<style>
				* {
					margin: 0;
					padding: 0;
					list-style: none;
				}
				.n2-errors{
					text-align:center;
					position:fixed;
					top:50%;
					width:100%;
					font-size:40px;
					transform:translateY(calc(-50% + 200px));
					margin-top:-200px;
				}
				.n2-errors div,.n2-errors ul,.n2-errors li{
					display:flex;
					gap:10px 5px;
					
					align-items:center;
				}
				.n2-errors div{
					margin-left: 20px;
					flex-direction: column;
				}
				.n2-errors ul{
					flex-wrap:wrap;
					flex-direction: row;
				}
			</style>
			<?php
		} else {
			$this->download_csv( 'rakuten_' . __FUNCTION__, $ini_arr['header'], $items_arr, $ini_arr['csv_title'] );
		}
		die();
	}

	/**
	 * 楽天のPC用商品説明文
	 *
	 * @param int $post_id id
	 * @return string $str 楽天のPC用商品説明文
	 */
	public function pc_item_description( $post_id ) {
		// get_post_metaのkey
		$post_keys = array(
			'説明文',
			'内容量・規格等',
			'賞味期限',
			'消費期限',
			'検索キーワード',
			'楽天カテゴリー',
		);
		// get_post_meta_multipleの格納用
		$post_meta_list = N2_Functions::get_post_meta_multiple( $post_id, $post_keys );
		$formatter      = fn( $post_key ) => nl2br( N2_Functions::_s( $post_meta_list[ $post_key ] ) );
		$pc_description = PHP_EOL . $formatter( '説明文' ) . '<br><br>' . PHP_EOL . $formatter( '内容量・規格等' ) . '<br>' . PHP_EOL;
		// 賞味期限
		if ( $post_meta_list['賞味期限'] ) {
			$pc_description .= '<br>【賞味期限】<br>' . $formatter( '賞味期限' ) . '<br><br>' . PHP_EOL;
		}

		// 消費期限
		if ( $post_meta_list['消費期限'] ) {
			$pc_description .= '<br>【消費期限】<br>' . $formatter( '消費期限' ) . '<br><br>' . PHP_EOL;
		}
		// やき物関連
		$pc_description .= apply_filters( 'n2_item_export_rakuten_porcelain_text', '', $post_id, '対応機器' ) . '<br>';
		// 検索キーワード
		if ( $post_meta_list['検索キーワード'] ) {
			$pc_description .= '<br><br>' . $formatter( '検索キーワード' );
		}
		$pc_description .= PHP_EOL;
		// 楽天カテゴリー
		if ( $post_meta_list['楽天カテゴリー'] ) {
			$pc_description .= '<br><br>' . $formatter( '楽天カテゴリー' );
		}
		// 追加説明文
		$pc_description .= get_option( 'N2_setupmenu' )['add_text'][ get_bloginfo( 'name' ) ];
		return $pc_description;
	}

	/**
	 * 商品説明テーブル
	 *
	 * @param int $post_id post_id
	 * @param bool $return_string 戻り値を文字列で返す
	 *
	 * @return string 商品説明テーブル
	 */
	public function make_itemtable( $post_id ,$return_string = true ) {
		$ini_arr                = $this->get_ini( 'item_csv' );
		$post_keys              = array(
			'表示名称',
			'略称',
			'内容量・規格等',
			'賞味期限',
			'消費期限',
			'発送方法',
			'配送期間',
			'提供事業者名',
			'アレルゲン',
			'アレルゲン注釈',
		);
		$post_meta_list = N2_Functions::get_post_meta_multiple( $post_id, $post_keys );

		// アレルギー表示
		$allergy_display     = function() use ( $post_meta_list, $ini_arr ) {
			$result = '';
			if ( $post_meta_list['アレルゲン'] || $post_meta_list['アレルゲン注釈'] ) {
				$allergens = '';
				foreach ( $post_meta_list['アレルゲン']  as $v ) {
					$allergens .= $ini_arr['allergens'][ $v ] . '・';
				}
				$result .= rtrim( $allergens, '・' );
				if ( $result && $post_meta_list['アレルゲン注釈'] ) {
					$result .= '<br>※';
				}
				$result .= $post_meta_list['アレルゲン注釈'];
			}
			return $result;
		};
		$allergy_display_str = $allergy_display();

		$formatter = fn( $post_key ) => N2_Functions::_s( $post_meta_list[ $post_key ] );
		$arr       = array(
			'名称'      => array(
				'td' => ( $formatter( '表示名称' ) ?: $formatter( '略称' ) ?: N2_Functions::_s( get_the_title( $post_id ) ) ),
			),
			'内容量'     => array(
				'td' => nl2br( $formatter( '内容量・規格等' ) ),
			),
			'賞味期限'    => array(
				'td'        => nl2br( $formatter( '賞味期限' ) ),
				'condition' => $post_meta_list['賞味期限'],
			),
			'消費期限'    => array(
				'td'        => nl2br( $formatter( '消費期限' ) ),
				'condition' => $post_meta_list['消費期限'],
			),
			'アレルギー表示' => array(
				'td'        => $allergy_display_str,
				'condition' => $allergy_display_str,
			),
			'配送方法'    => array(
				'td' => nl2br( $formatter( '発送方法' ) ),
			),
			'配送期日'    => array(
				'td' => nl2br( $formatter( '配送期間' ) ),
			),
			'提供事業者'   => array(
				'td'        => $post_meta_list['提供事業者名']
					?: preg_replace(
						'/\（.+?\）/',
						'',
						(
						get_the_author_meta( 'portal', get_post_field( 'post_author', $post_id ) )
						?: get_the_author_meta( 'first_name', get_post_field( 'post_author', $post_id ) )
						)
					),
				'condition' => '記載しない' !== get_the_author_meta( 'portal', get_post_field( 'post_author', $post_id ) ),
			),
		);

		// [html]商品説明テーブル
		$itemtable_html = function() use ( $arr ){
			?>
			<!-- 商品説明テーブル -->
			<p><b><font size=""5"">商品説明</font></b></p><hr noshade color=""black""><br>
			<table border=""1"" width=""100%"" cellspacing=""0"" cellpadding=""10"" bordercolor=""black"">
			<?php foreach ($arr as $th => $val) : ?>
				<?php if (! isset($val['condition']) || $val['condition']) : ?>
				<tr><th><?php echo $th; ?></th><td><?php echo $val['td']; ?></td></tr>
			<?php endif; ?>
			<?php endforeach; ?>
			</table>
			<!-- /商品説明テーブル -->
			<?php
		};

		if( $return_string ) return N2_Functions::html2str($itemtable_html);
		$itemtable_html();
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

		foreach ( $ini_arr['rakuten_select_option'] as $v ) {
			if ( $v ) {
				$arr                      = explode( "\n", $v );
				$select_header            = trim( array_shift( $arr ) );
				$select[ $select_header ] = $arr;
			}
		}
		// 初期化
		$i = 0;
		foreach ( $ini_arr['ids'] as $post_id ) {

			// get_post_metaのkey一覧
			$post_keys = array(
				'返礼品コード',
			);
			// get_post_meta格納用
			$post_meta_list = N2_Functions::get_post_meta_multiple( $post_id, $post_keys );
			$item_num       = trim( mb_strtolower( $post_meta_list['返礼品コード'] ) );

			// 連想配列作成
			foreach ( $select as $key => $value ) {
				foreach ( $value as $v ) {
					// headerの項目を取得
					$items_arr[ $i ] = N2_Functions::get_post_meta_multiple( $post_id, $ini_arr['header'] );

					$item_arr        = array(
						'項目選択肢用コントロールカラム' => 'n',
						'商品管理番号（商品URL）'   => $item_num,
						'選択肢タイプ'          => 's',
						'項目選択肢項目名'        => $key,
						'項目選択肢'           => trim( $v ),
						'項目選択肢選択必須'       => '1',
					);
					$items_arr[ $i ] = array_merge( $items_arr[ $i ], $item_arr );
					++$i;
				}
			}
			// 内容を追加、または上書きするためのフック
			$items_arr[ $post_id ] = array_merge(
				$items_arr[ $post_id ],
				apply_filters( 'n2_item_export_' . __FUNCTION__ . '_items', $item_arr, $post_id ),
			);
		}
		$this->download_csv( 'rakuten_' . __FUNCTION__, $ini_arr['header'], $items_arr, $ini_arr['csv_title'] );
		die();
	}
}
