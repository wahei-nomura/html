<?php
/**
 * ふるさとチョイス商品API
 * https://www.furusato-tax.jp/ajax/city/product/42201?page=3
 *
 * @package neoneng
 */

 if ( class_exists( 'N2_Output_Gift_API' ) ) {
	new N2_Output_Gift_API();
	return;
}

class N2_Output_Gift_API {

public function __construct() {
	add_action( 'wp_ajax_n2_output_gift_api', array( $this, 'get' ) );
}

/**
 * 外部用API
 */
public function get() {
	echo 'hello, world';
	exit;
}

}