<?php
/**
 * class-n2-auto-redirect.php
 *
 * @package neoneng
 */

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
		add_action( 'after_setup_theme', array( $this, 'redirect_to_code_path' ) );
		add_action( 'admin_init', array( $this, 'redirect_to_same_page' ) );
	}

	/**
	 * 自治体コード付きにリダイレクト
	 */
	public function redirect_to_code_path() {
		// 正規URLじゃないログイン画面以外はスルーする
		if ( ! preg_match( '/^\/[a-z]*?\/(\?*MSN-06S|wp-admin)/', $_SERVER['REQUEST_URI'] ) ) {
			return;
		}
		preg_match( '/^\/(.*?)\//', $_SERVER['REQUEST_URI'], $search );
		$replace = array_filter(
			array_column( get_sites(), 'path' ),
			fn( $v ) => false !== strpos( $v, $search[1] ) && preg_match( '/^\/f[0-9]{6}-[a-z].*?\//', $v )
		);
		// マルチサイトに近しいものがない場合はスルー
		if ( empty( $replace ) ) {
			return;
		}
		$replace  = wp_unslash( array_values( $replace )[0] );
		$redirect = preg_replace( '/^\/(.*?)\//', $replace, $_SERVER['REQUEST_URI'] );
		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * 自治体移動時に同じページにリダイレクト
	 */
	public function redirect_to_same_page() {
		$now = $_SERVER['REQUEST_URI'];
		$ref = $_SERVER['HTTP_REFERER'] ?? '';
		// リダイレクトしてほしくないパターン
		if ( ! preg_match( '#/wp-admin/$#', $now ) || preg_match( '#(/wp-admin/$|/network/)#', $ref ) || ! preg_match( '#/wp-admin/#', $ref ) ) {
			return;
		}
		$redirect = preg_replace( '#.*?/wp-admin/#', $now, $ref );
		wp_safe_redirect( $redirect );
	}
}
