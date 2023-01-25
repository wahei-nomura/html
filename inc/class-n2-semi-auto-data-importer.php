<?php
/**
 * class-n2-semi-auto-data-importer.php
 *
 * @package neoneng
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'N2_Semi_Auto_Data_Importer' ) ) {
	new N2_Semi_Auto_Data_Importer();
	return;
}

/**
 * N2_Semi_Auto_Data_Importer
 */
class N2_Semi_Auto_Data_Importer {

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		add_action( 'wp_ajax_import_manicipal_data', array( $this, 'import_manicipal_data' ) );
		add_action( 'wp_ajax_get_manicipal_data', array( $this, 'get_manicipal_data' ) );
	}
	/**
	 * 自治体立ち上げ
	 *
	 * @param array $manicipal_data manicipal_data
	 * @return void
	 */
	public function import_manicipal_data( $manicipal_data ) {
		echo 'import?';
		die();
	}
	/**
	 * スプシ | CSVから取得
	 *
	 * @param array $manicipal_data manicipal_data
	 * @return void
	 */
	public function get_manicipal_data( $manicipal_data ) {
		echo 'get?';
		die();
	}
}