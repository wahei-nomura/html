<?php
/**
 * class-n2-hogehoge.php
 *
 * @package neoneng
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'N2_Hogehoge' ) ) {
	new N2_Hogehoge();
	return;
}

/**
 * Hogehoge
 */
class N2_Hogehoge {
	/**
	 * コンストラクタ
	 */
	public function __construct() {
		add_action( 'admin_notices', array( $this, 'hoge' ) );
	}

	/**
	 * hoge
	 */
	public function hoge() {
		$message = array(
			'hello',
			'world',
			'!!!',
		);
		$message = apply_filters( 'n2_hello', $message );
		// echo implode( ' ', $message );

	}
}
