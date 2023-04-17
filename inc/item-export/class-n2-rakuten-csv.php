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
	private function get_config( $ajax_str ) {
		global $n2;
		// 初期化
		$arr = array();
		// ========アレルゲン=======
		$allergens_list = $n2->custom_field['事業者用']['アレルゲン']['option'];
		// ========クルーセットアップでの設定項目========
		$n2_setupmenu = get_option( 'N2_setupmenu' ) ?? '';

		$arr = array(
			// ajaxで渡ってきたpostidの配列
			'ids'      => explode( '%2C', filter_input( INPUT_POST, $ajax_str, FILTER_SANITIZE_ENCODED ) ),
			'アレルゲン'    => $allergens_list,
			'各種セットアップ' => $n2_setupmenu,
		);
		// 内容を追加、または上書きするためのフック
		return apply_filters( 'n2_item_export_get_yml', $arr );
	}

	/**
	 * 各種セットアップの未入力エラー出力
	 *
	 * @param array $errors csvの種類(iniファイルのセクション名)
	 * @return void
	 */
	private function rakuetn_setup_error_output( $errors ) {
		$home = get_option( 'home' );
		?>
		<h2><a href="<?php echo "{$home}/wp-admin/admin.php?page=n2_crew_setup_menu"; ?>" target="_blank">各種セットアップ</a>の下記項目が未設定です<h2>
			<ul>
			<?php foreach ( $errors as $error ) : ?>
				<li><?php echo $error; ?></li>
			<?php endforeach; ?>
			</ul>
		<?php
		wp_die();
	}

	/**
	 * 楽天のエクスポート用CSV生成(item_csv)
	 *
	 * @return void
	 */
	public function item_csv() {
		$config        = $this->get_config( __FUNCTION__ );
		$option        = $config['各種セットアップ'];
		$error_options = array();
		if ( ! isset( $option['rakuten'][ __FUNCTION__ ] ) || ! $option['rakuten'][ __FUNCTION__ ] ) {
			$error_options = array( ...$error_options, '楽天セットアップ > item.csvのheader' );
		}
		if ( ! isset( $option['rakuten']['img_dir'] ) || ! $option['rakuten']['img_dir'] ) {
			$error_options = array( ...$error_options, '楽天セットアップ > 商品画像ディレクトリ' );
		}
		if ( ! isset( $option['rakuten']['tag_id'] ) || ! $option['rakuten']['tag_id'] ) {
			$error_options = array( ...$error_options, '楽天セットアップ > 楽天タグID' );
		}
		if ( ! isset( $option['rakuten']['html'] ) ) {
			$error_options = array( ...$error_options, '楽天セットアップ > 説明文追加html' );
		}
		if ( ! isset( $option['add_text'][ get_bloginfo( 'name' ) ] ) ) {
			$option['add_text'][ get_bloginfo( 'name' ) ] = '';
		}
		if ( $error_options ) {
			// エラー出力して終了
			$this->rakuetn_setup_error_output( $error_options );
		}

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
			'キャッチコピー１',
			'説明文',
			'賞味期限',
			'消費期限',
			'検索キーワード',
			'楽天SPAカテゴリー',
		);
		$header    = explode( '	', $option['rakuten'][ __FUNCTION__ ] );

		foreach ( $config['ids'] as $post_id ) {
			// headerの項目を取得
			$items_arr[ $post_id ] = N2_Functions::get_post_meta_multiple( $post_id, $header );
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

			$item_arr = array(
				'コントロールカラム'     => 'n',
				'商品管理番号（商品URL）' => trim( mb_strtolower( $post_meta_list['返礼品コード'] ) ),
				'商品番号'          => $item_num,
				'全商品ディレクトリID'   => $post_meta_list['全商品ディレクトリID'],
				'タグID'          => rtrim( $option['rakuten']['tag_id'], '/' ) . '/' . ltrim( $post_meta_list['タグID'], '/' ),
				'商品名'           => '【ふるさと納税】' . N2_Functions::special_str_convert( get_the_title( $post_id ) ) . " [{$item_num}]",
				'販売価格'          => $post_meta_list['寄附金額'],
				'のし対応'          => ( '有り' === $post_meta_list['のし対応'] ) ? 1 : '',
				'PC用キャッチコピー'    => N2_Functions::special_str_convert( $post_meta_list['キャッチコピー'] ?: $post_meta_list['キャッチコピー１'] ),
				'モバイル用キャッチコピー'  => N2_Functions::special_str_convert( $post_meta_list['キャッチコピー'] ?: $post_meta_list['キャッチコピー１'] ),
				'商品画像URL'       => $this->get_img_urls( $post_id ),
				'PC用商品説明文'      => PHP_EOL . $this->pc_item_description( $post_id ),
				'PC用販売説明文'      => $this->pc_sales_description( $post_id ),
				'スマートフォン用商品説明文' => PHP_EOL . $this->sp_item_description( $post_id ),
			);

			// 内容を追加、または上書きするためのフック
			$items_arr[ $post_id ] = array(
				...$items_arr[ $post_id ],
				...apply_filters( 'n2_item_export_item_csv_items', $item_arr, $post_id ),
			);
			// ================ エラー関連　================

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
							<?php echo $error_message; ?>
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
			// 商品番号（返礼品コード）で昇順ソート
			uasort(
				$items_arr,
				function ( $a, $b ) {
					return strnatcmp( $a['商品番号'], $b['商品番号'] );
				}
			);
			// csv出力
			N2_Functions::download_csv(
				array(
					'file_name' => 'item',
					'header'    => $header,
					'items_arr' => $items_arr,
				)
			);
		}
		die();
	}

	/**
	 * 楽天の画像URLを取得
	 *
	 * @param int    $post_id id
	 * @param string $return_type 戻り値判定用(string|html|array)
	 * @return string|array 楽天の画像URLを(文字列|配列)で取得する
	 */
	public function get_img_urls( $post_id, $return_type = 'string' ) {
		global $n2;
		// get_post_metaのkey
		$post_keys = array(
			'返礼品コード',
		);
		// post_meta格納用
		$post_meta_list = N2_Functions::get_post_meta_multiple( $post_id, $post_keys );
		$item_num_low   = trim( mb_strtolower( $post_meta_list['返礼品コード'] ) );
		$img_dir        = rtrim( $n2->rakuten['img_dir'], '/' );

		// GOLD（ne.jp）とキャビネット（co.jp）を判定してキャビネットは事業者コードディレクトリを追加
		preg_match( '/^[a-z]{2,3}/', $item_num_low, $m );// 事業者コード
		if ( ! preg_match( '/ne\.jp/', $img_dir ) && array_key_exists( 0, $m ) ) {
			$img_dir .= "/{$m[0]}";// キャビネットの場合事業者コード追加
		}

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
		// ========戻り値判定========
		switch ( $return_type ) {
			// 文字列を返却
			case 'string':
				return implode( ' ', $result );
			case 'html':
				$html = function() use ( $result ) {
					?>
					<?php foreach ( $result  as $index => $img_url ) : ?>
						<?php if ( array_key_last( $result ) === $index ) : ?>
							<img src=""<?php echo $img_url; ?>"" width=""100%""><br><br>
						<?php else : ?>
							<img src=""<?php echo $img_url; ?>"" width=""100%"">
						<?php endif; ?>
					<?php endforeach; ?>
					<?php
				};
				$html();
				break;
			// 配列出力
			default:
				return $result;
		}
	}
	/**
	 * 楽天のPC用商品説明文
	 *
	 * @param int  $post_id id
	 * @param bool $return_string 戻り値判定用(基本は文字列|HTML)
	 * @return string|void 楽天のPC用商品説明文を(文字列|HTML出力)する
	 */
	public function pc_sales_description( $post_id, $return_string = true ) {

		// get_post_metaのkey
		$post_keys = array(
			'説明文',
		);
		// post_meta格納用
		$post_meta_list = N2_Functions::get_post_meta_multiple( $post_id, $post_keys );
		// ========[html]PC用販売説明文========
		$html = function() use ( $post_meta_list, $post_id ) {
			global $n2;
			$formatter = fn( $post_key ) => nl2br( N2_Functions::special_str_convert( $post_meta_list[ $post_key ] ) );
			?>
			<?php $this->get_img_urls( $post_id, 'html' ); ?>
			<?php echo $formatter( '説明文' ); ?><br><br>
			<?php $this->make_itemtable( $post_id, false ); ?><br><br>
			<?php
				echo $n2->portal_common_discription
					. apply_filters( 'n2_item_export_rakuten_porcelain_text', '', $post_id, 'PC用販売説明文' )
					. str_replace( '\"', '""', $n2->rakuten['html'] ?? '' );
				?>
			<?php
		};
		// ========戻り値判定========
		// 文字列を返却
		if ( $return_string ) {
			return N2_Functions::html2str( $html );
		}
		// html出力
		$html();
	}
	/**
	 * 楽天のPC用商品説明文
	 *
	 * @param int  $post_id id
	 * @param bool $return_string 戻り値判定用(基本は文字列|HTML)
	 * @return string|void 楽天のPC用商品説明文を(文字列|HTML出力)する
	 */
	public function pc_item_description( $post_id, $return_string = true ) {
		// get_post_metaのkey
		$post_keys = array(
			'説明文',
			'内容量・規格等',
			'賞味期限',
			'消費期限',
			'検索キーワード',
			'楽天SPAカテゴリー',
			'原料原産地',
			'加工地',
		);
		// post_meta格納用
		$post_meta_list = N2_Functions::get_post_meta_multiple( $post_id, $post_keys );

		// ========[html]PC用商品説明文========
		$html = function() use ( $post_meta_list, $post_id ) {
			$formatter = fn( $post_key ) => nl2br( N2_Functions::special_str_convert( $post_meta_list[ $post_key ] ) );
			?>
			<?php echo $formatter( '説明文' ); ?><br><br>
			<?php echo $formatter( '内容量・規格等' ); ?><br>
			<?php if ( $post_meta_list['賞味期限'] ) : ?>
				<br>【賞味期限】<br><?php echo $formatter( '賞味期限' ); ?><br>
			<?php endif; ?>
			<?php if ( $post_meta_list['消費期限'] ) : ?>
				<br>【消費期限】<br><?php echo $formatter( '消費期限' ); ?><br>
			<?php endif; ?>
			<?php echo apply_filters( 'n2_item_export_rakuten_porcelain_text', '', $post_id, '対応機器' ); ?>
			<?php if ( $post_meta_list['原料原産地'] ) : ?>
				<br><br>【原料原産地】<br>
				<?php echo $formatter( '原料原産地' ); ?>
			<?php endif; ?>
			<?php if ( $post_meta_list['加工地'] ) : ?>
				<br><br>【加工地】<br>
				<?php echo $formatter( '加工地' ); ?><br>
			<?php endif; ?>
			<?php if ( $post_meta_list['検索キーワード'] ) : ?>
				<br><br><?php echo $formatter( '検索キーワード' ); ?>
			<?php endif; ?>
			<?php if ( $post_meta_list['楽天SPAカテゴリー'] ) : ?>
				<br><br><?php echo $formatter( '楽天SPAカテゴリー' ); ?>
			<?php endif; ?>
			<?php
		};

		// ========戻り値判定========
		// 文字列を返却
		if ( $return_string ) {
			return N2_Functions::html2str( $html );
		}
		// html出力
		$html();
	}
	/**
	 * 楽天のSP用商品説明文
	 *
	 * @param int  $post_id id
	 * @param bool $return_string 戻り値判定用(基本は文字列|HTML)
	 * @return string|void 楽天のSP用商品説明文を(文字列|HTML出力)する
	 */
	public function sp_item_description( $post_id, $return_string = true ) {
		// get_post_metaのkey
		$post_keys = array(
			'説明文',
			'内容量・規格等',
			'賞味期限',
			'消費期限',
			'検索キーワード',
			'楽天SPAカテゴリー',
			'原料原産地',
			'加工地',
		);
		// post_meta格納用
		$post_meta_list = N2_Functions::get_post_meta_multiple( $post_id, $post_keys );

		// ========[html]SP用商品説明文========
		$html = function() use ( $post_id, $post_meta_list, ) {
			global $n2;
			$formatter = fn( $post_key ) => nl2br( N2_Functions::special_str_convert( $post_meta_list[ $post_key ] ) );
			?>
			<?php $this->get_img_urls( $post_id, 'html' ); ?>
			<?php echo $formatter( '説明文' ); ?><br><br>
			<?php $this->make_itemtable( $post_id, false ); ?>
			<?php if ( $post_meta_list['検索キーワード'] ) : ?>
				<br><br><?php echo $formatter( '検索キーワード' ); ?>
			<?php endif; ?>
			<?php if ( $post_meta_list['楽天SPAカテゴリー'] ) : ?>
				<br><br><?php echo $formatter( '楽天SPAカテゴリー' ); ?>
			<?php endif ?>
			<?php
				echo $n2->portal_common_discription
					. str_replace( '\"', '""', $n2->rakuten['html'] ?? '' );
			?>
			<?php
		};
		// ========戻り値判定========
		// 文字列を返却
		if ( $return_string ) {
			return N2_Functions::html2str( $html );
		}
		// html出力
		$html();
	}
	/**
	 * アレルギ表示
	 *
	 * @param string $post_id id
	 * @param string $type type
	 *
	 * @return string
	 */
	public static function allergy_display( $post_id, $type = '' ) {
		$post_meta_list            = get_post_meta( $post_id, '', true );
		$post_meta_list['アレルゲン']   = unserialize( $post_meta_list['アレルゲン'][0] );
		$post_meta_list['アレルゲン注釈'] = $post_meta_list['アレルゲン注釈'][0];
		$allergens                 = array();
		$is_food                   = in_array( '食品', get_post_meta( $post_id, '商品タイプ', true ), true );
		$has_allergy               = in_array( 'アレルギー品目あり', get_post_meta( $post_id, 'アレルギー有無確認', true ) ?: array(), true );
		foreach ( $post_meta_list['アレルゲン'] as $v ) {
			if ( is_numeric( $v['value'] ) ) {
				$allergens = array( ...$allergens, $v['label'] );
			}
		}
		$allergens                 = implode( '・', $allergens );
		$post_meta_list['アレルゲン注釈'] = $post_meta_list['アレルゲン注釈'] ? '<br>※' . $post_meta_list['アレルゲン注釈'] : '';
		$result                    = '';
		switch ( true ) {
			case ! $is_food && 'print' === $type:
				$result = 'アレルギー表示しない';
				break;
			case ! $is_food:
				break;
			case ! $has_allergy:
				$result = 'アレルギーなし食品';
				break;
			case '' !== $allergens || '' !== $post_meta_list['アレルゲン注釈']:
				$allergens = $allergens ?: 'なし';
				$result    = "含んでいる品目：{$allergens}{$post_meta_list['アレルゲン注釈']}";
				break;
		}
		return $result;
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
		$config         = $this->get_config( 'item_csv' );
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
		$allergy_display_str = $this->allergy_display( $post_id );

		$formatter = fn( $post_key ) => nl2br( N2_Functions::special_str_convert( $post_meta_list[ $post_key ] ) );
		$trs       = array(
			'名称'      => array(
				'td' => ( $formatter( '表示名称' ) ?: $formatter( '略称' ) ?: N2_Functions::special_str_convert( get_the_title( $post_id ) ) ),
			),
			'内容量'     => array(
				'td' => $formatter( '内容量・規格等' ),
			),
			'原料原産地'   => array(
				'td'        => $formatter( '原料原産地' ),
				'condition' => $post_meta_list['原料原産地'],
			),
			'加工地'     => array(
				'td'        => $formatter( '加工地' ),
				'condition' => $post_meta_list['加工地'],
			),
			'賞味期限'    => array(
				'td'        => $formatter( '賞味期限' ),
				'condition' => $post_meta_list['賞味期限'],
			),
			'消費期限'    => array(
				'td'        => $formatter( '消費期限' ),
				'condition' => $post_meta_list['消費期限'],
			),
			'アレルギー表示' => array(
				'td'        => $allergy_display_str,
				'condition' => $allergy_display_str,
			),
			'配送方法'    => array(
				'td' => $formatter( '発送方法' ),
			),
			'配送期日'    => array(
				'td' => $formatter( '配送期間' ),
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

		$config        = $this->get_config( __FUNCTION__ );
		$option        = $config['各種セットアップ'];
		$error_options = array();
		if ( ! isset( $option['rakuten'][ __FUNCTION__ ] ) || ! $option['rakuten'][ __FUNCTION__ ] ) {
			$error_options = array( ...$error_options, '楽天セットアップ > select.csvのheader' );
		}
		if ( ! isset( $option['rakuten']['select'] ) ) {
			$error_options = array( ...$error_options, '楽天セットアップ > 項目選択肢' );
		}
		if ( $error_options ) {
			// エラー出力して終了
			$this->rakuetn_setup_error_output( $error_options );
		}

		// itemの情報を配列化
		$items_arr = array();
		// select項目名 => array(選択肢)の形式に変換
		$select = array();
		$header = explode( '	', $option['rakuten'][ __FUNCTION__ ] );

		foreach ( $option['rakuten']['select'] as $v ) {
			if ( $v ) {
				$arr                      = explode( "\n", $v );
				$select_header            = trim( array_shift( $arr ) );
				$select[ $select_header ] = $arr;
			}
		}
		foreach ( $config['ids'] as $post_id ) {
			// 初期化
			$i        = 0;
			$item_arr = array();
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
					$item_arr[ $i ] = N2_Functions::get_post_meta_multiple( $post_id, $header );
					$item_select    = array(
						'項目選択肢用コントロールカラム' => 'n',
						'商品管理番号（商品URL）'   => $item_num,
						'選択肢タイプ'          => 's',
						'項目選択肢項目名'        => $key,
						'項目選択肢'           => trim( $v ),
						'項目選択肢選択必須'       => '1',
					);
					$item_arr[ $i ] = array( ...$item_arr[ $i ], ...$item_select );
					++$i;
				}
			}
			// 返礼品ごとに内容を追加、または上書きするためのフック
			$items_arr = array(
				...$items_arr,
				...apply_filters( 'n2_item_export_select_csv_items', $item_arr, $post_id ),
			);
		}
		// 商品番号（返礼品コード）で昇順ソート
		uasort(
			$items_arr,
			function ( $a, $b ) {
				return strnatcmp( $a['商品管理番号（商品URL）'], $b['商品管理番号（商品URL）'] );
			}
		);

		N2_Functions::download_csv(
			array(
				'file_name' => 'select',
				'header'    => $header,
				'items_arr' => $items_arr,
			)
		);
	}

	/**
	 * 楽天のエクスポート用(error_log)
	 *
	 * @return void
	 */
	public function output_error_log() {
		// 各種設定読み込み
		global $n2;
		$rakuten = $n2->rakuten;
		// setlocale(LC_ALL, 'ja_JP.UTF-8');
		$error_options = array();

		if ( ! isset( $rakuten['ftp_user'] ) || ! $rakuten['ftp_user'] ) {
			$error_options = array( ...$error_options, '楽天セットアップ > FTPユーザー' );
		}
		if ( ! isset( $rakuten['ftp_pass'] ) || ! $rakuten['ftp_pass'] ) {
			$error_options = array( ...$error_options, '楽天セットアップ > FTPパスワード' );
		}
		if ( $error_options ) {
			// エラー出力して終了
			$this->rakuetn_setup_error_output( $error_options );
		}
		$conn_id = ftp_connect( $rakuten['upload_server'], $rakuten['upload_server_port'] );
		$login   = ftp_login( $conn_id, $rakuten['ftp_user'], $rakuten['ftp_pass'] );
		if ( ! $login ) {
			$login = ftp_login( $conn_id, $rakuten['ftp_user'], substr( $rakuten['ftp_pass'], 0, 7 ) . '2' ); // ログインできない場合は末尾を２に変更
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
