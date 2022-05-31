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
	 * 自身のクラス名を格納
	 *
	 * @var string
	 */
	private $cls;

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		$this->cls = get_class( $this );
		add_action( "wp_ajax_{$this->cls}", array( $this, 'ajax' ) );
	}

	/**
	 * ajax
	 *
	 * @return void
	 */
	public function ajax() {
		$csv = 'ヘッダー1,ヘッダー2,ヘッダー3
値1,値2,値3
値4,値5,値6';

		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename=test.csv' );
		echo htmlspecialchars_decode( $csv );

		die();
	}
}
