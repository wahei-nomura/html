<?php
/**
 * class-n2-rakuten-csv.php
 *
 * @package neoneng
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'N2_Rakuten_CSV' ) ) {
	new N2_Rakuten_CSV();
	return;
}

/**
 * Item_Export
 */
class N2_Rakuten_CSV {

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		add_action( 'wp_ajax_ledghome', array( $this, 'ledghome' ) );
		add_action( 'wp_ajax_item_csv', array( $this, 'item_csv' ) );
		add_action( 'wp_ajax_select_csv', array( $this, 'select_csv' ) );
		add_action( 'wp_ajax_error_log', array( $this, 'output_error_log' ) );
		add_action( 'wp_ajax_rakuten_pc_item_description', array( $this, 'pc_item_description' ) );
	}

	/**
	 * iniファイルから取得したい場合はここに追加する
	 *
	 * @param String $ajax_str csvの種類(iniファイルのセクション名)
	 * @return Array $arr 処理に必要なiniの情報を格納した配列
	 */
	private function get_yml( $ajax_str ) {
		// 初期化
		$arr = array();
		// ========ini一覧========
		$n2_fields      = yaml_parse_file( get_theme_file_path( 'config/n2-fields.yml' ) );
		$n2_towncodes   = yaml_parse_file( get_theme_file_path( 'config/n2-towncode.yml' ) );
		$n2_file_header = yaml_parse_file( get_theme_file_path( 'config/n2-file-header.yml' ) );

		// ========自治体コード=========
		$option_home = explode( '/', get_option( 'home' ) );
		$townname    = end( $option_home );
		$n2_towncode = $n2_towncodes[ $townname ];
		// ========楽天商品画像のパス=======
		$rakuten_img_dir = $n2_towncode['楽天']
		? str_replace( 'n2-towncode', $n2_towncode['楽天'], $n2_file_header['rakuten']['img_dir'] )
		: $n2_file_header['rakuten']['img_dir'];
		// ========header========
		if ( 'item_csv' === $ajax_str || 'select_csv' === $ajax_str ) {// 楽天の場合
			$header_str = $n2_file_header['rakuten'][ $ajax_str ];
		} else {
			$header_str = $n2_file_header[ $ajax_str ]['csv_header'];
		}
		// ========アレルゲン========
		$allergens_list = $n2_fields['アレルゲン']['option'];
		// ========クルーセットアップでの設定項目========
		$rakuten_select_option = get_option( 'N2_setupmenu' )['rakuten']['select'] ?? '';

		$arr = array(
			// あとでヘッダの上の連結するのに必要
			'csv_title'             => $header_str['title'],
			// プラグイン側でヘッダーを編集
			'header'                => apply_filters( 'n2_item_export_' . $ajax_str . '_header', $header_str['values'] ),
			// ajaxで渡ってきたpostidの配列
			'ids'                   => explode( '%2C', filter_input( INPUT_POST, $ajax_str, FILTER_SANITIZE_ENCODED ) ),
			'rakuten_img_dir'       => $rakuten_img_dir,
			'アレルゲン'                 => $allergens_list,
			'rakuten_select_option' => $rakuten_select_option,
		);
		// 内容を追加、または上書きするためのフック
		return apply_filters( 'n2_item_export_get_yml', $arr );
	}

	/**
	 * 楽天のエクスポート用CSV生成(item_csv)
	 *
	 * @return void
	 */
	public function item_csv() {
		// iniから情報を取得
		$yml_arr = $this->get_yml( __FUNCTION__ );

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

		foreach ( $yml_arr['ids'] as $post_id ) {
			// headerの項目を取得
			$items_arr[ $post_id ] = N2_Functions::get_post_meta_multiple( $post_id, $yml_arr['header'] );
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
			var_dump( get_post_meta($post_id) );
			$item_num       = trim( strtoupper( $post_meta_list['返礼品コード'] ) );
			$item_num_low   = trim( mb_strtolower( $post_meta_list['返礼品コード'] ) );
			$img_dir        = $yml_arr['rakuten_img_dir'];

			// GOLD（ne.jp）とキャビネット（co.jp）を判定してキャビネットは事業者コードディレクトリを追加
			preg_match( '/^[a-z]{2,3}/', $item_num_low, $m );// 事業者コード
			if ( ! preg_match( '/ne\.jp/', $img_dir ) && array_key_exists( 0, $m ) ) {
				$img_dir .= "/{$m[0]}";// キャビネットの場合事業者コード追加
			}

			// 存在する画像urlだけを文字連結
			$check_img_urls = function () use ( $item_num_low, $img_dir ) {
				$result = array();
				for ( $i = 0; $i < 15; ++$i ) {
					$img_url = "{$img_dir}/{$item_num_low}";
					if ( 0 === $i ) {
						$img_url .= '.jpg';
					} else {
						$img_url .= "-{$i}.jpg";
					}
					$response = wp_remote_get( $img_url );
					if ( ! is_wp_error( $response ) && 200 === $response['response']['code'] ) {
						$result[ $i ] = $img_url;
					}
				}
				return implode( ' ', $result );
			};
			// 画像URL一覧(文字列)
			$img_urls_str = $check_img_urls();

			// [html]画像URL一覧
			$img_urls_html = function() use ( $img_urls_str ) {
				$img_urls_arr = explode( ' ', $img_urls_str );
				?>
				<?php foreach ( $img_urls_arr as $index => $img_url ) : ?>
					<?php if ( array_key_last( $img_urls_arr ) === $index ) : ?>
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
				$option        = get_option( 'N2_setupmenu' );
				$add_text_name = $option['add_text'][ get_bloginfo( 'name' ) ] ?? '';
				$rakuten_html  = $option['rakuten']['html'] ?? '';
				?>
				<?php $img_urls_html(); ?>
				<?php $itemtable_html(); ?><br><br>
				<?php
				echo $add_text_name
					. apply_filters( 'n2_item_export_rakuten_porcelain_text', '', $post_id, 'PC用販売説明文' )
					. str_replace( '\"', '""', $rakuten_html );
				?>
				<?php
			};

			// [html]スマートフォン用商品説明文
			$sp_item_description_html = function () use ( $itemtable_html, $post_meta_list, $img_urls_html ) {
				$formatter       = fn( $post_key ) => str_replace( '＜br /＞', '<br />', N2_Functions::special_str_convert( $post_meta_list[ $post_key ] ) );
				$formatter_nl2br = fn( $post_key ) => nl2br( $formatter( $post_key ) );
				$option          = get_option( 'N2_setupmenu' );
				$add_text_name   = $option['add_text'][ get_bloginfo( 'name' ) ] ?? '';
				$rakuten_html    = $option['rakuten']['html'] ?? '';
				?>
				<?php $img_urls_html(); ?>
				<?php echo $formatter( '説明文' ); ?><br><br>
				<?php $itemtable_html(); ?>
				<?php if ( $post_meta_list['検索キーワード'] ) : ?>
					<br><br><?php echo $formatter_nl2br( '検索キーワード' ); ?>
				<?php endif; ?>
				<?php if ( $post_meta_list['楽天カテゴリー'] ) : ?>
					<br><br><?php echo $formatter_nl2br( '楽天カテゴリー' ); ?>
				<?php endif ?>
				<?php
				echo $add_text_name
					. str_replace( '\"', '""', $rakuten_html );
				?>
				<?php
			};
			$item_arr = array(
				'コントロールカラム'     => 'n',
				'商品管理番号（商品URL）' => trim( mb_strtolower( $post_meta_list['返礼品コード'] ) ),
				'商品番号'          => $item_num,
				'全商品ディレクトリID'   => $post_meta_list['全商品ディレクトリID'],
				'タグID'          => $post_meta_list['タグID'],
				'商品名'           => '【ふるさと納税】' . N2_Functions::special_str_convert( get_the_title( $post_id ) ) . " [{$item_num}]",
				'販売価格'          => $post_meta_list['寄附金額'],
				'のし対応'          => ( '有り' === $post_meta_list['のし対応'] ) ? 1 : '',
				'PC用キャッチコピー'    => N2_Functions::special_str_convert( $post_meta_list['キャッチコピー'] ),
				'モバイル用キャッチコピー'  => N2_Functions::special_str_convert( $post_meta_list['キャッチコピー'] ),
				'商品画像URL'       => $img_urls_str,
				'PC用商品説明文'      => PHP_EOL . $this->pc_item_description( $post_id ),
				'PC用販売説明文'      => PHP_EOL . N2_Functions::html2str( $pc_sales_description_html ),
				'スマートフォン用商品説明文' => PHP_EOL . N2_Functions::html2str( $sp_item_description_html ),
			);

			// 内容を追加、または上書きするためのフック
			$items_arr[ $post_id ] = array_merge(
				$items_arr[ $post_id ],
				apply_filters( 'n2_item_export_item_csv_items', $item_arr, $post_id ),
			);

			// ================ エラー関連　================

			// ymlファイルに自治体名が設定されていない場合
			if ( strpos( $img_dir, 'n2-towncode' ) ) {
				?>
				<style>
					.towncode-error{
						text-align : center;
						position : fixed;
						top : 50%;
						width : 100%;
						font-size : 40px;
						margin-top : -200px;
					}
				</style>
				<div class="towncode-error">
					<h1>ERROR</h1>
					<p>自治体コードが設定されていません！エンジニアにご連絡ください。</p>
				</div>
				<?php
				die();
			}

			// エラー時は$check_arrに詰め込む
			$check_error = function( $item_num, &$check_arr ) use ( $item_arr, $post_meta_list ) {
				// エラー項目
				$error_list = array(
					array(
						'condition' => ! $item_arr['商品画像URL'],
						'message'   => '商品画像を先にアップロードしてください！',
					),
					array(
						'condition' => ! $post_meta_list['寄附金額'],
						'message'   => '寄附金額を設定してください！',
					),

				);
				// ========エラー項目追加用hook========
				$error_list = apply_filters( 'n2_item_export_item_csv_add_error_item', $error_list );

				foreach ( $error_list as $error ) {
					if ( $error['condition'] ) {
						// 初期化
						if ( ! isset( $check_arr[ $item_num ] ) ) {
							$check_arr[ $item_num ] = array();
						}
						array_push( $check_arr[ $item_num ], $error['message'] );
					}
				}
			};
			$check_error( $item_num, $check_arr );
			// 商品画像が無い場合
		}
		// エラー項目を出力してCSVファイルを作らない
		if ( $check_arr ) {
			?>
			<div class='n2-errors'>
			<h1>ERROR</h1>
			<table id="error-table" >
				<thead>
					<tr>
						<th>商品コード</th>
						<th>エラー内容</th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $check_arr as $error_item => $errors ) : ?>
					<?php foreach ( $errors as $index => $error_message ) : ?>
					<tr>
					<?php if ( 0 === $index ) : ?>
						<th rowspan="<?php echo count( $errors ); ?>"><?php echo $error_item; ?></th>
					<?php endif; ?>
						<td>
							<label><input type="checkbox"><?php echo $error_message; ?></label>			
						</td>
					</tr>
					<?php endforeach; ?>
				<?php endforeach; ?>
				</tbody>
			</table>
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
					transform:translateY(-50%);
					display: flex;
					flex-direction: column;
					gap:10px 5px;
					
					align-items:center;
				}
				#error-table{
					border: 3px solid #000;
					border-collapse: collapse;
				}
				#error-table *{
					padding: 5px;
				}
				#error-table label{
					display: flex;
					column-gap: 5px;
				}
				#error-table th,td{
					border: 1px solid #000;
				}
			</style>
			<?php
		} else {
			// csv出力
			N2_Functions::download_csv( 'rakuten_item', $yml_arr['header'], $items_arr );
		}
		die();
	}

	/**
	 * 楽天のPC用商品説明文
	 *
	 * @param int  $post_id id
	 * @param bool $return_string 戻り値判定用(基本は文字列|HTML)
	 * @return string|void 楽天のPC用商品説明文を(文字列|HTML出力)する
	 */
	public function pc_item_description( $post_id = 55, $return_string = true ) {

		// get_post_metaのkey
		$post_keys = array(
			'説明文',
			'内容量・規格等',
			'賞味期限',
			'消費期限',
			'検索キーワード',
			'楽天カテゴリー',
			'原料原産地',
			'加工地',
		);
		// post_meta格納用
		$post_meta_list = N2_Functions::get_post_meta_multiple( $post_id, $post_keys );

		// ========[html]PC用商品説明文========
		$pc_description_html = function() use ( $post_meta_list, $post_id ) {
			$formatter       = fn( $post_key ) => str_replace( '＜br /＞', '<br />', N2_Functions::special_str_convert( $post_meta_list[ $post_key ] ) );
			$formatter_nl2br = fn( $post_key ) => nl2br( $formatter( $post_key ) );
			?>
			<?php echo $formatter( '説明文' ); ?><br><br>
			<?php echo $formatter( '内容量・規格等' ); ?><br>
			<?php if ( $post_meta_list['賞味期限'] ) : ?>
				<br>【賞味期限】<br><?php echo $formatter_nl2br( '賞味期限' ); ?><br>
			<?php endif; ?>
			<?php if ( $post_meta_list['消費期限'] ) : ?>
				<br>【消費期限】<br><?php echo $formatter_nl2br( '消費期限' ); ?><br>
			<?php endif; ?>
			<?php echo apply_filters( 'n2_item_export_rakuten_porcelain_text', '', $post_id, '対応機器' ); ?>
			<?php if ( $post_meta_list['原料原産地'] ) : ?>
				<br><br>【原料原産地】<br>
				<?php echo $formatter( '原料原産地' ); ?><br>
			<?php endif; ?>
			<?php if ( $post_meta_list['加工地'] ) : ?>
				<br><br>【加工地】<br>
				<?php echo $formatter_nl2br( '加工地' ); ?><br>
			<?php endif; ?>
			<?php if ( $post_meta_list['検索キーワード'] ) : ?>
				<br><br><?php echo $formatter_nl2br( '検索キーワード' ); ?>
			<?php endif; ?>
			<?php if ( $post_meta_list['楽天カテゴリー'] ) : ?>
				<br><br><?php echo $formatter_nl2br( '楽天カテゴリー' ); ?>
			<?php endif; ?>
			<?php
		};

		// ========戻り値判定========
		// 文字列を返却
		if ( $return_string ) {
			return N2_Functions::html2str( $pc_description_html );
		}
		// html出力
		$pc_description_html();
	}

	/**
	 * 商品説明テーブル
	 *
	 * @param int  $post_id post_id
	 * @param bool $return_string 戻り値を文字列で返す
	 *
	 * @return string 商品説明テーブル
	 */
	public function make_itemtable( $post_id, $return_string = true ) {
		$yml_arr        = $this->get_yml( 'item_csv' );
		$post_keys      = array(
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
			'アレルギー品目あり',
			'原料原産地',
			'加工地',
		);
		$post_meta_list = N2_Functions::get_post_meta_multiple( $post_id, $post_keys );

		// アレルギー表示
		$allergy_display     = function() use ( $post_meta_list, $yml_arr ) {
			$result      = '';
			$has_allergy = $post_meta_list['アレルギー品目あり'] ?: count( (array) $post_meta_list['アレルゲン'] );
			if ( $post_meta_list['アレルゲン'] || $post_meta_list['アレルゲン注釈'] ) {
				$result    = '含んでいる品目：';
				$allergens = array();
				foreach ( $post_meta_list['アレルゲン']  as $v ) {
					$allergens = array( ...$allergens, $v['label'] );
				}
				$result .= implode( '・', $allergens ) ?: 'なし';
				if ( $post_meta_list['アレルゲン注釈'] ) {
					$result .= '<br>※' . $post_meta_list['アレルゲン注釈'];
				}
			} elseif ( $has_allergy ) {
				$result = 'アレルギー品目なし';
			}
			return $result;
		};
		$allergy_display_str = $allergy_display();

		$formatter       = fn( $post_key ) => str_replace( '＜br /＞', '<br />', N2_Functions::special_str_convert( $post_meta_list[ $post_key ] ) );
		$formatter_nl2br = fn( $post_key ) => nl2br( $formatter( $post_key ) );
		$trs             = array(
			'名称'      => array(
				'td' => ( $formatter( '表示名称' ) ?: $formatter( '略称' ) ?: N2_Functions::special_str_convert( get_the_title( $post_id ) ) ),
			),
			'内容量'     => array(
				'td' => $formatter( '内容量・規格等' ),
			),
			'賞味期限'    => array(
				'td'        => $formatter_nl2br( '賞味期限' ),
				'condition' => $post_meta_list['賞味期限'],
			),
			'消費期限'    => array(
				'td'        => $formatter_nl2br( '消費期限' ),
				'condition' => $post_meta_list['消費期限'],
			),
			'原料原産地' => array(
				'td'        => $formatter( '原料原産地' ),
				'condition' => $post_meta_list['原料原産地'],
			),
			'加工地' => array(
				'td'        => $formatter_nl2br( '加工地' ),
				'condition' => $post_meta_list['加工地'],
			),
			'アレルギー表示' => array(
				'td'        => $allergy_display_str,
				'condition' => $allergy_display_str,
			),
			'配送方法'    => array(
				'td' => $formatter_nl2br( '発送方法' ),
			),
			'配送期日'    => array(
				'td' => $formatter_nl2br( '配送期間' ),
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

		// 内容を追加、または上書きするためのフック
		$trs = apply_filters( 'n2_item_export_make_itemtable', $trs, $post_id );

		// ========[html]商品説明テーブル========
		$itemtable_html = function() use ( $trs ) {
			?>
			<!-- 商品説明テーブル -->
			<p><b><font size=""5"">商品説明</font></b></p><hr noshade color=""black""><br>
			<table border=""1"" width=""100%"" cellspacing=""0"" cellpadding=""10"" bordercolor=""black"">
			<?php foreach ( $trs as $th => $td_params ) : ?>
				<?php if ( ! isset( $td_params['condition'] ) || $td_params['condition'] ) : ?>
				<tr><th><?php echo $th; ?></th><td><?php echo $td_params['td']; ?></td></tr>
				<?php endif; ?>
			<?php endforeach; ?>
			</table>
			<!-- /商品説明テーブル -->
			<?php
		};
		// ========戻り値判定========
		// 文字列を返却
		if ( $return_string ) {
			return N2_Functions::html2str( $itemtable_html );
		}
		// htmlで出力
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
		$yml_arr   = $this->get_yml( __FUNCTION__ );

		// select項目名 => array(選択肢)の形式に変換
		$select = array();

		foreach ( $yml_arr['rakuten_select_option'] as $v ) {
			if ( $v ) {
				$arr                      = explode( "\n", $v );
				$select_header            = trim( array_shift( $arr ) );
				$select[ $select_header ] = $arr;
			}
		}
		// 初期化
		$i = 0;
		foreach ( $yml_arr['ids'] as $post_id ) {
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
					$items_arr[ $i ] = N2_Functions::get_post_meta_multiple( $post_id, $yml_arr['header'] );
					$item_arr        = array(
						'項目選択肢用コントロールカラム' => 'n',
						'商品管理番号（商品URL）'   => $item_num,
						'選択肢タイプ'          => 's',
						'項目選択肢項目名'        => $key,
						'項目選択肢'           => trim( $v ),
						'項目選択肢選択必須'       => '1',
					);
					$items_arr[ $i ] = array( ...$items_arr[ $i ], ...$item_arr );
					++$i;
				}
			}
			// 内容を追加、または上書きするためのフック
			$items_arr[ $post_id ] = array(
				...$items_arr[ $post_id ],
				...apply_filters( 'n2_item_export_select_csv_items', $item_arr, $post_id ),
			);
		}
		N2_Functions::download_csv( 'rakuten_select', $yml_arr['header'], $items_arr );
	}

	/**
	 * 楽天のエクスポート用(error_log)
	 *
	 * @return void
	 */
	public function output_error_log() {
		// setlocale(LC_ALL, 'ja_JP.UTF-8');
		$n2_file_header = yaml_parse_file( get_theme_file_path( 'config/n2-file-header.yml' ) );
		$upload_server  = $n2_file_header['rakuten']['upload_server'];
		$conn_id        = ftp_connect( $upload_server['domain'], $upload_server['port'] );
		$option         = get_option( 'N2_Setupmenu' );
		$ftp_user       = $option['rakuten']['ftp_user'] ?? 'f423831-ojika';
		$ftp_pass       = $option['rakuten']['ftp_pass'] ?? 'Ojika002';
		$login          = ftp_login( $conn_id, $ftp_user, $ftp_pass );
		if ( ! $login ) {
			$login = ftp_login( $conn_id, $ftp_user, substr( $ftp_pass, 0, 7 ) . '2' ); // ログインできない場合は末尾を２に変更
		}
		if ( $login ) {
			ftp_pasv( $conn_id, true );
			$errors   = ftp_nlist( $conn_id, 'ritem/logs/' );
			$contents = '';
			arsort( $errors );
			foreach ( (array) $errors as $error ) {
				ob_start();
				if ( ftp_get( $conn_id, 'php://output', $error, FTP_BINARY ) ) {
					$data      = str_replace( ',', ' ------> ', mb_convert_encoding( ob_get_contents(), 'utf-8', 'sjis' ) );
					$contents .= <<<EOD
					<h1 style="background:#000;color:#fff;padding: 10px;margin: 10px 0 0;">{$error}</h1>
					<pre style="font-size: 16px;line-height: 2;padding: 10px;border: 1px solid #000;margin-top: 0;overflow: scroll;">{$data}</pre>
EOD;
				}
				ob_end_clean();
			}
			$contents = ( empty( $contents ) ) ? '<h1>エラーログはありません。</h1>' : $contents;
			echo <<<EOD
			<!DOCTYPE html>
			<html lang="ja">
			<head>
				<meta charset="UTF-8">
				<title>楽天エラーログ</title>
				<link rel="stylesheet" href="{$print_css}">
			</head>
			<body style="padding: 10px;">{$contents}</body>
			</html>
EOD;
			ftp_close( $conn_id );
		} else {
			echo 'パスワードが違います';
		}
		die();
	}
}
