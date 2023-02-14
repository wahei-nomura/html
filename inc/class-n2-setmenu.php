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
		add_action( 'admin_menu', array( $this, 'remove_menulabel' ), 999 );
		add_action( 'admin_init', array( $this, 'not_edit_user' ) );
		add_filter( 'get_site_icon_url', array( $this, 'change_site_icon' ) );
		add_action( 'admin_head', array( $this, 'my_custom_logo' ) );

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
		// クルー事業者共通で削除
		$menus = array(
			'edit.php?post_type=page', // 固定ページ
			'tools.php',
			'upload.php',
			'profile.php',
		);

		// クルーのメニュー削除
		if ( 'ss-crew' === wp_get_current_user()->roles[0] ) {
			$menus[] = 'themes.php';
			$menus[] = 'upload.php';
			$menus[] = 'edit-comments.php';
			$menus[] = 'aiowpsec'; // All In One WP Security
		}

		// 事業者のメニュー削除
		if ( 'jigyousya' === wp_get_current_user()->roles[0] ) {
			$menus[] = 'index.php';
			$menus[] = 'edit-comments.php';
			$menus[] = 'aiowpsec'; // All In One WP Security
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
		if ( current_user_can( 'ss_crew' ) ) {
			return;
		}

		$hide_pages = array(
			'index.php',
			'tooles.php',
			'upload.php',
		);
		if ( in_array( $pagenow, $hide_pages, true ) ) {
			wp_redirect( admin_url( 'edit.php' ) );
		}
		if ( 'profile.php' === $pagenow ) {
			wp_die( 'ユーザープロフィールを変更したい場合は「Steamship」へお問い合わせください。<p><a class="button" href="' . admin_url( 'edit.php' ) . '">返礼品一覧へ戻る</a></p>' );
		}
	}

	/**
	 * faviconを変更する
	 */
	public function change_site_icon() {
		return get_theme_file_uri( 'neo_neng_logo.svg' );
	}
	/**
	 * 管理画面左上のロゴ変更
	 *
	 * @return void
	 */
	public function my_custom_logo() {
		echo '<style type="text/css">#wpadminbar #wp-admin-bar-wp-logo > .ab-item > .ab-icon:before { content: url(' . get_theme_file_uri( 'neo_neng_logo.svg' ) . ');}</style>';
	}
}
