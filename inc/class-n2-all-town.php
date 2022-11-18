<?php
/**
 * class-n2-all-town.php
 *
 * @package neoneng
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'N2_All_Town' ) ) {
	new N2_All_Town();
	return;
}

/**
 * AllTown
 */
class N2_All_Town {
	/**
	 * コンストラクタ
	 */
	public function __construct() {
		$this->cls = get_class( $this );
		add_action( "wp_ajax_{$this->cls}_getdata", array( $this, 'get_check_data' ) );
		add_action( "wp_ajax_nopriv_{$this->cls}_getdata", array( $this, 'get_check_data' ) );
	}

	/**
	 * サイトネットワークトップに表示するデータを返す
	 */
	public function get_check_data(){
		$town_name = filter_input( INPUT_GET, 'townName' );
		echo wp_json_encode( $town_name );
		exit;
	}
}
