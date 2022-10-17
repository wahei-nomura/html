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
		add_action( 'init', array( $this, 'not_edit_user' ) );

	}

	/**
	 * change_menulabel
	 */
	public function change_menulabel() {
		global $menu;
		global $submenu;
		$name                       = '返礼品';
		$menu[5][0]                 = $name;
		$submenu['edit.php'][5][0]  = $name . '一覧';
	}

	/**
	 * remove_menulabel
	 */
	public function remove_menulabel() {
		// クルー事業者共通で削除
		$menus = array(
			'edit.php?post_type=page', // 固定ページ
			'edit-comments.php', // コメント
			'tools.php',
			'upload.php',
			'profile.php',
		);

		// クルーのメニュー削除
		if ( 'ss-crew' === wp_get_current_user()->roles[0] ) {
			array_push( $menus, 'themes.php' );
			array_push( $menus, 'upload.php' );
		}

		// 事業者のメニュー削除
		if ( 'jigyousya' === wp_get_current_user()->roles[0] ) {
			array_push( $menus, 'index.php' );
		}

		foreach ( $menus as $menu ) {
			remove_menu_page( $menu );
		}
	}


	/**
	 * not_edhit_user
	 */
	public function not_edit_user() {
		global $pagenow;
		if ( ! current_user_can( 'jigyousya' ) ) {
			return;
		}
		if ( 'index.php' === $pagenow || 'tools.php' === $pagenow || 'upload.php' === $pagenow ) {
			wp_redirect( admin_url( 'edit.php' ) );
		}
		if ( 'profile.php' === $pagenow ) {
			wp_die( 'ユーザープロフィールを変更したい場合は「Steamship」へお問い合わせください。<p><a class="button" href="' . $_SERVER['HTTP_REFERER'] . '">前に戻る</a></p>' );
		}
	}
}
