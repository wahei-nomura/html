<?php
/**
 * class-n2-ajax.php
 *
 * @package neoneng
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'N2_Ajax' ) ) {
	new N2_Ajax();
	return;
}

/**
 * ajax
 */
class N2_Ajax {
	/**
	 * コンストラクタ
	 */
	public function __construct() {
		add_action( 'wp_ajax_ledghome', array( $this, 'ledghome' ) );
		add_action( 'wp_ajax_item_csv', array( $this, 'item_csv' ) );
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
				$csv .= ! empty( get_post_meta( $id, $head, true ) ) ? get_post_meta( $id, $head, true ) : "";
				$csv .= ',';
				// $item_data[ $id ][ $head ] = ! empty( get_post_meta( $id, $head, true ) ) ? get_post_meta( $id, $head, true ) : '';
			}
			$csv .= "\n";
		}
		// $csv = 'ヘッダー1,ヘッダー2,ヘッダー3
		// 値1,値2,値3
		// 値4,値5,値6';

		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename=ledghome.csv' );
		echo htmlspecialchars_decode( $csv );

		// echo $ids;
		echo( $csv );

		die();
	}

	/**
	 * item_csv
	 *
	 * @return void
	 */
	public function item_csv() {
		$csv = '楽天1,楽天2,楽天3
値1,値2,値3
値4,値5,値6';

		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename=item.csv' );
		echo htmlspecialchars_decode( $csv );

		die();
	}
}
