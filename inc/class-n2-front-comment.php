<?php
/**
 * class-n2-front-comment.php
 *
 * @package neoneng
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'N2_Front_Comment' ) ) {
	new N2_Front_Comment();
	return;
}

/**
 * Front
 */
class N2_Front_Comment {
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

	}
}
