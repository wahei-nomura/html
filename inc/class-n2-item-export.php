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
	 * download_data
	 *
	 * @param string $data 出力したいデータ
	 * @return void
	 */
	private function download_data( $data ) {
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename=ledghome.csv' );
		echo htmlspecialchars_decode( $data );

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
		$header     = explode( ',', explode( "\n", $header_str )[1] );
		$csv        = $header_str . PHP_EOL;

		$ids = explode( ',', filter_input( INPUT_POST, 'ledghome' ) );

		foreach ( $ids as $id ) {
			foreach ( $header as $head ) {
				$items_arr[ $id ][ $head ] = ! empty( get_post_meta( $id, $head, true ) ) ? get_post_meta( $id, $head, true ) : '';
			}

			$items_arr[ $id ]['謝礼品番号']      = trim( strtoupper( get_post_meta( $id, '返礼品コード', true ) ) );
			$items_arr[ $id ]['謝礼品名']       = $items_arr[ $id ]['謝礼品番号'] . ' ';
			$items_arr[ $id ]['謝礼品名']      .= ( get_post_meta( $id, '略称', true ) ) ? get_post_meta( $id, '略称', true ) : N2_Functions::_s( get_the_title( $id ) );
			$items_arr[ $id ]['配送名称']       = ( get_post_meta( $id, '配送伝票表示名', true ) ) ? ( $items_arr[ $id ]['謝礼品番号'] . ' ' . get_post_meta( $id, '配送伝票表示名', true ) ) : $items_arr[ $id ]['謝礼品名'];
			$items_arr[ $id ]['ふるさとチョイス名称'] = N2_Functions::_s( get_the_title( $id ) ) . " [{$items_arr[$id]['謝礼品番号']}]";
			$items_arr[ $id ]['楽天名称']       = '【ふるさと納税】' . N2_Functions::_s( get_the_title( $id ) ) . " [{$items_arr[$id]['謝礼品番号']}]";
			$items_arr[ $id ]['謝礼品カテゴリー']   = get_post_meta( $id, 'カテゴリー', true );
			$items_arr[ $id ]['セット内容']      = N2_Functions::_s( get_post_meta( $id, '内容量・規格等', true ) );
			$items_arr[ $id ]['謝礼品紹介文']     = N2_Functions::_s( get_post_meta( $id, '説明文', true ) );
			$items_arr[ $id ]['ステータス']      = '受付中';
			$items_arr[ $id ]['状態']         = '表示';
			// $items_arr[ $id ]['寄附設定金額']  = ( $i < 2 ) ? get_post_meta( $id, '寄附金額', true ) : 0;
			// $arr[ $kid ]['価格（税込み）'] = ( $opt['ledghome']['teikiprice'] == true ) ? ( ( $i < 2 ) ? $price * $teiki : 0 ) : $price;
			$items_arr[ $id ]['送料'] = get_post_meta( $id, '送料', true );
			// $items_arr[ $id ]['送料反映'] = ( empty( $opt['ledghome']['souryouhanei'] ) ) ? '反映しない' : '反映する';
			$items_arr[ $id ]['発送方法'] = get_post_meta( $id, '発送方法', true );
			// 取り扱い方法保留

			$items_arr[ $id ]['申込可能期間']   = '通年';
			$items_arr[ $id ]['自由入力欄1']   = date( 'Y/m/d' ) . '：' . wp_get_current_user()->display_name;
			$items_arr[ $id ]['自由入力欄2']   = $items_arr[ $id ]['送料'];
			$items_arr[ $id ]['配送サイズコード'] = ( is_numeric( get_post_meta( $id, '発送サイズ', true ) ) ) ? get_post_meta( $id, '発送サイズ', true ) : '';

		}

		foreach ( $items_arr as $item ) {
				$csv .= '"' . implode( '","', $item ) . '"' . PHP_EOL;
		}
		$this->download_data( $csv );
	}
}
