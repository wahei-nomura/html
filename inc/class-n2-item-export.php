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
		$header_str = parse_ini_file( get_template_directory() . '/config/n2-file-header.ini', true )['ledghome']['csv_header'];
		$header     = explode( ',', $header_str );
		$csv        = $header_str . PHP_EOL;

		$ids = explode( ',', filter_input( INPUT_POST, 'ledghome' ) );

		foreach ( $ids as $id ) {
			foreach ( $header as $head ) {
				$csv .= ! empty( get_post_meta( $id, $head, true ) ) ? '"' . get_post_meta( $id, $head, true ) . '"' : '';
				$csv .= ',';
			}
			$csv  = substr( $csv, 0, -1 );
			$csv .= PHP_EOL;
		}

		$this->download_data( $csv );
	}
}
