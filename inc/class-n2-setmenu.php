<?php
/**
 * class-n2-setmenu.php
 *
 * @package neoneng
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'N2_Setmenu' ) ) {
	new N2_Setmenu();
	return;
}

/**
 * Setmenu
 */
class N2_Setmenu {
	/**
	 * コンストラクタ
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'change_menulabel' ) );
		add_action( 'admin_menu', array( $this, 'remove_menulabel' ) );
	}

	/**
	 * change_menulabel
	 */
	public function change_menulabel() {
		global $menu;
		global $submenu;
		$name                      = '返礼品';
		$menu[5][0]                = $name;
		$submenu['edit.php'][5][0] = $name . '一覧';
	}

	/**
	 * remove_menulabel
	 */
	public function remove_menulabel() {
		$menus = array(
			'edit.php?post_type=page', // 固定ページ
			'edit-comments.php', // コメント
		);

		// ss-crewは外見メニュー削除
		if ( 'ss-crew' === wp_get_current_user()->roles[0] ) {
			array_push( $menus, 'themes.php' );
		}

		foreach ( $menus as $menu ) {
			remove_menu_page( $menu );
		}
	}
}
