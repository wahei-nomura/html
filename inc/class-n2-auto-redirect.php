<?php
/**
 * class-n2-auto-redirect.php
 *
 * @package neoneng
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'N2_Auto_Redirect' ) ) {
	new N2_Auto_Redirect();
	return;
}

/**
 * N2_Auto_Redirect
 */
class N2_Auto_Redirect {

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		add_action( 'after_setup_theme', array( $this, 'redirect' ) ); // 項目の追加
	}

	/**
	 * オートリダイレクト
	 */
	public function redirect() {
		// 正規URLパターンの場合はスルー
		if ( preg_match( '/^\/f[0-9]{6}-[a-z].*?\//', $_SERVER['REQUEST_URI'] ) ) {
			return;
		}
		preg_match( '/^\/(.*?)\//', $_SERVER['REQUEST_URI'], $search );
		$replace  = array_filter( array_column( get_sites(), 'path' ), fn( $v ) => false !== strpos( $v, $search[1] ) );
		if ( empty( $replace ) ) {
			return;
		}
		$replace  = wp_unslash( array_values( $replace )[0] );
		$redirect = preg_replace( '/^\/(.*?)\//', $replace, $_SERVER['REQUEST_URI'] );
		wp_safe_redirect( $redirect );
		exit;
	}
}
