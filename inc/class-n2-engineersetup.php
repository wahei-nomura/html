<?php
/**
 * class-n2-hogehoge.php
 *
 * @package neoneng
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'N2_Engineersetup' ) ) {
	new N2_Engineersetup();
	return;
}

/**
 * Hogehoge
 */
class N2_Engineersetup {
	/**
	 * コンストラクタ
	 */
	public function __construct() {
		add_action( 'admin_notices', array( $this, 'hoge' ) );
	}

	/**
	 * hoge
	 */
	public function addwidegets() {
		$message = array(
			'hello',
			'world',
			'!!!',
		);
		$message = apply_filters( 'n2_hello', $message );
		echo implode( ' ', $message );
	}
}
