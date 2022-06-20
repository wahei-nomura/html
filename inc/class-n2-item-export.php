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

		// プラグイン側でヘッダーを編集
		$header = apply_filters( 'n2_item_export_ledghome_header', $header );

		// ajaxで渡ってきたpostidの配列
		$ids = explode( ',', filter_input( INPUT_POST, 'ledghome' ) );

		foreach ( $ids as $id ) {
			$teiki = get_post_meta( $id, '定期便', true );

			for ( $i = 1;$i <= $teiki;$i++ ) {
				$key_id   = 1 < $teiki ? "{$id}_{$i}" : $id;
				$teikinum = 1 < $teiki ? "_{$i}/{$teiki}" : '';
				foreach ( $header as $head ) {
					$items_arr[ $key_id ][ $head ] = ! empty( get_post_meta( $id, $head, true ) ) ? get_post_meta( $id, $head, true ) : '';
				}

				$item_num = trim( strtoupper( get_post_meta( $id, '返礼品コード', true ) ) ) . $teikinum;

				$arr = array(
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
				list( $id, $items_arr[ $key_id ] ) = apply_filters( 'n2_item_export_ledghome_items', array( $id, $arr ) );
			}
		}

		$this->download_csv( 'ledghome', $header, $items_arr, $csv_title );
	}
}
