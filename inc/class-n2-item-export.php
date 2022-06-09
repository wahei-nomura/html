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
 * ajax
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
	 * ledghome
	 *
	 * @return void
	 */
	public function ledghome() {
		$item_data = array();
		$header    = explode( '	', parse_ini_file( get_template_directory() . '/config/n2-setting.ini', true )['ledghome']['csv_header'] );
		$csv       = implode( ',', $header ) . "\n";

		$ids = explode( ',', filter_input( INPUT_POST, 'ledghome' ) );

		foreach ( $ids as $id ) {
			foreach ( $header as $head ) {
				$csv .= ! empty( get_post_meta( $id, $head, true ) ) ? get_post_meta( $id, $head, true ) : '';
				$csv .= ',';
			}
			$csv .= "\n";
		}

		$this->download_data( $csv );
	}
}
