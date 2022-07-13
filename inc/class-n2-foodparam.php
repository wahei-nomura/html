<?php
/**
 * class-n2-foodparam.php
 *
 * @package neoneng
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'N2_Foodparam' ) ) {
	new N2_Foodparam();
	return;
}

/**
 * Foodparam
 */
class N2_Foodparam {
	/**
	 * クラス名
	 *
	 * @var string
	 */
	private $cls;
	/**
	 * コンストラクタ
	 */
	public function __construct() {
		$this->cls = get_class( $this );
	}

}
