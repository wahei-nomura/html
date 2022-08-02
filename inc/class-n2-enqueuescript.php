<?php
/**
 * class-n2-enqueuescript.php
 *
 * @package neoneng
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'N2_enqueuescript' ) ) {
	new N2_Enqueuescript();
	return;
}

/**
 * enqueuescript
 */
class N2_Enqueuescript {
	/**
	 * コンストラクタ
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_setpost_script' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_front_script' ) );
	}

	/**
	 * js,cssの読み込み
	 *
	 * @return void
	 */
	public function enqueue_setpost_script() {
		wp_enqueue_media();
		wp_enqueue_script( 'n2-script', get_theme_file_uri( 'dist/admin.js' ), array( 'jquery' ), N2_CASH_BUSTER, true );
		wp_enqueue_script( 'jquery-touch-punch', false, array( 'jquery' ), N2_CASH_BUSTER, true );
		wp_enqueue_style( 'n2-style', get_theme_file_uri( 'dist/admin.css' ), array(), N2_CASH_BUSTER );

		// JS側に変数としてPHPのpathを渡す
		$tmp_path_arr = array(
			'tmp_url'  => get_template_directory_uri(),
			'ajax_url' => admin_url( 'admin-ajax.php' ),
		);
		wp_localize_script( 'n2-script', 'tmp_path', $tmp_path_arr );
	}

	/**
	 * フロントのjs,cssの読み込み
	 *
	 * @return void
	 */
	public function enqueue_front_script() {
		wp_enqueue_script( 'n2-front-script', get_theme_file_uri( 'dist/front.js' ), array( 'jquery' ), N2_CASH_BUSTER, true );
		wp_enqueue_style( 'n2-front-style', get_theme_file_uri( 'dist/front.css' ), array(), N2_CASH_BUSTER );
	}
}
