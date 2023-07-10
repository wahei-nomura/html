<?php
/**
 * class-n2-change-sku-firstaid.php
 * N2移行完了までの一時しのぎ版
 *
 * @package neoneng
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'wp_ajax_n2_change_to_sku', array( $this, 'change_to_sku' ) );
	}

	/**
	 * N2 SYNC　メニューの追加
	 */
	public function add_menu() {
		global $n2;
		if ( isset( $n2->portal_setting['楽天'] ) ) {
			add_menu_page( 'SKU変換', 'SKU変換', 'ss_crew', 'n2_rakuten_change_sku_firstaid_upload', array( $this, 'ftp_ui' ), 'dashicons-admin-site-alt3' );
			add_submenu_page( 'n2_rakuten_change_sku_firstaid_upload', '楽天エラーログ', '楽天エラーログ', 'ss_crew', 'n2_rakuten_error_log', array( $this, 'ftp_ui' ) );
		}
	}

	/**
	 * FTP UI
	 */
	public function ftp_ui() {
		$template = str_replace( 'n2_rakuten_', '', $_GET['page'] );
		?>
		<div class="wrap">
			<h1>SKU変換</h1>
		<?php echo $this->$template(); ?>
		</div>
		<?php
	}

	/**
	 * アップロード（超突貫）
	 */
	public function change_sku_firstaid_upload() {
		?>
		<div style="clear:both;padding:10px 0;margin-top:100px;border:1px solid #aaa;">
			<form action="admin-ajax.php" target="_blank" method="post" enctype="multipart/form-data">
				<input type="hidden" name="action" value="n2_change_to_sku">
				<input name="item_file[]" type="file" multiple="multiple">
				<input type="submit" class="button" value="item.csvをnormal-item.csv(SKU対応版)に変換">
			</form>
		</div>
		<?php
	}
	/**
	 * 楽天への転送機能（超突貫）
	 *
	 * @return void
	 */
	public function change_to_sku() {
		header( 'Content-Type: application/json; charset=utf-8' );
		// 各種設定読み込み
		global $n2;
		$column_count = 0;
		$rakuten      = $n2->settings['楽天'];
		// setlocale(LC_ALL, 'ja_JP.UTF-8');
		$error_options = array();

		if ( ! isset( $rakuten['項目選択肢'] ) || ! $rakuten['項目選択肢'] ) {
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
		extract( $_FILES['item_file'] );
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
				} else if($item_val === '非製品属性タグID'){
					print_r('たぐあいでぃー：'.$item_array[ 'タグID' ]);
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
			foreach( $new_item_array as $new_item_key => $new_item){
				if($new_item_key !== 0){
					$new_item_value_column .= ',';
				}
				if('' !== $new_item){
					$new_item_value_column .= '"' . $new_item . '"';
				}else{
					$new_item_value_column .= $new_item;
				}
			}
			$output_column[$column_count] = $new_item_value_column;
			$column_count++;
			// 項目選択肢
			$selects = $n2->settings['楽天']['項目選択肢'];
			$selects = str_replace( array( "\r\n", "\r" ), "\n", $selects );// 改行コード統一
			$selects = preg_split( '/\n{2,}/', $selects );// 連続改行で分ける
			$select_count = count( $selects );
			if ( $select_count > 0 ) {
				$select_no = array_search( '選択肢タイプ', $new_item_column_array );
				for($i = 0; $i < $select_no; $i++){
					$select_array[$i] = '';
				}
				foreach ( $selects as $select ) {
					$select = explode( "\n", $select );
					$select_array[0] = $item_array['商品管理番号（商品URL）'];
					$select_array[$select_no] = "s";
					foreach($select as $select_key => $value){
						$select_array[$select_no + $select_key +1] = $value;
					}
					foreach($select_array as $select_value_key => $select_value){
						if($select_value_key !== 0){
							$new_select_value_column .= ',';
						}
						if('' !== $select_value){
							$new_select_value_column .= '"' . $select_value . '"';
						}else{
							$new_select_value_column .= $select_value;
						}
					}
					$output_column[$column_count] = $new_select_value_column;
					$column_count++;
				}
			}
			foreach($output_column as $output){
				echo $output;
			}
			exit();
			die();
		}
	}
	/**
	 * エラーログ（超突貫）
	 */
	public function error_log() {
		global $n2;
		WP_Filesystem();
		require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-ftpext.php';
		$opt = array(
			'hostname' => $n2->settings['楽天']['FTP']['upload_server'],
			'username' => $n2->settings['楽天']['FTP']['ftp_user'],
			'password' => $n2->settings['楽天']['FTP']['ftp_pass'],
		);
		$ftp = new WP_Filesystem_FTPext( $opt );
		if ( ! $ftp->connect() ) {
			$opt['password'] = rtrim( $opt['password'], 1 ) . '2';
			$ftp             = new WP_Filesystem_FTPext( $opt );
			if ( ! $ftp->connect() ) {
				echo '接続エラー';
				exit;
			}
		}
		$logs = $ftp->dirlist( 'ritem/logs' );
		$logs = array_reverse( $logs );
		if ( empty( $logs ) ) {
			echo 'エラーログはありません。';
			exit;
		}
		?>
		<h3>エラーログ</h3>
		<table class="widefat striped" style="margin: 2em 0;">
		<?php
		foreach ( $logs as $log ) :
			$contents = $ftp->get_contents( "ritem/logs/{$log['name']}" );
			$contents = htmlspecialchars( mb_convert_encoding( $contents, 'utf-8', 'sjis' ) );
			?>
			<tr>
				<td><?php echo "{$log['year']} {$log['month']} {$log['day']}"; ?></td>
				<td>
					<button type="button" popovertarget="<?php echo $log['name']; ?>" class="button button-primary">エラー内容を見る</button>
					<div popover="auto" id="<?php echo $log['name']; ?>" style="width: 80%; max-height: 80%; overflow-y: scroll;"><pre><?php echo $contents; ?></pre></div>
				</td>
				<td><?php echo "ritem/logs/{$log['name']}"; ?></td>
			</tr>
		<?php endforeach; ?>
		</table>
		<?php
	}
}
