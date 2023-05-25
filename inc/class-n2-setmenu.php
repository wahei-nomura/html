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
		add_action( 'admin_bar_menu', array( $this, 'remove_admin_bar_menus' ), 999 );
		add_action( 'admin_head', array( $this, 'remove_help_tabs' ) );
		add_filter( 'admin_footer_text', array( $this, 'custom_admin_footer' ) );
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

		// 各種セットアップ用
		$menu[90][0] = '各種セットアップ';
		if ( isset( $submenu['n2_crew_setup_menu'] ) ) {
			foreach ( $submenu['n2_crew_setup_menu'] as $index => $sub ) {
				if ( 'n2_setup_menus' === $sub[0] ) {
					$submenu['n2_crew_setup_menu'][ $index ][0] = 'クルー専用';
				}
			}
		}
	}

	/**
	 * remove_menulabel
	 */
	public function remove_menulabel() {
		// クルー事業者共通で削除
		$menus    = array(
			'edit.php?post_type=page', // 固定ページ
			'tools.php',
			'upload.php',
			'profile.php',
		);
		$submenus = array();
		global $n2;

		// ロール毎で削除するメニュー
		switch ( $n2->current_user->roles[0] ) {
			case 'ss-crew':
				$menus[] = 'themes.php';
				$menus[] = 'upload.php';
				$menus[] = 'edit-comments.php';
				$menus[] = 'options-general.php';
				$menus[] = 'aiowpsec'; // All In One WP Security
				break;
			case 'jigyousya':
				$menus[] = 'index.php';
				$menus[] = 'edit-comments.php';
				$menus[] = 'aiowpsec'; // All In One WP Security
				break;
			case 'municipal-office':
				$menus[]  = 'index.php';
				$menus[]  = 'edit-comments.php';
				$menus[]  = 'aiowpsec'; // All In One WP Security
				$submenus = array(
					...$submenus,
					...array(
						'edit.php' => 'post-new.php',
					),
				);
				break;
		}

		foreach ( $menus as $menu ) {
			remove_menu_page( $menu );
		}
		foreach ( $submenus as $menu_slug => $submenu_slug ) {
			remove_submenu_page( $menu_slug, $submenu_slug );
		}
	}

	/**
	 * not_edit_user
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
			wp_safe_redirect( admin_url( 'edit.php' ) );
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
	/**
	 * 管理画面のヘッダーメニューバーで不要なものを除去 @yamasaki
	 *
	 * @param array $wp_admin_bar 管理バーの項目を格納
	 */
	public function remove_admin_bar_menus( $wp_admin_bar ) {
		$wp_admin_bar->remove_menu( 'wp-logo' ); // WordPressロゴ.
		$wp_admin_bar->remove_menu( 'comments' );     // コメント
		$wp_admin_bar->remove_menu( 'new-content' );  // 新規
		$wp_admin_bar->remove_menu( 'view-site' );    // サイト名 → サイトを表示
		$dashboard_url = admin_url();
		$wp_admin_bar->add_node(
			array(
				'id'   => 'site-name',
				'href' => $dashboard_url,
			),
		);
		$wp_admin_bar->add_node(
			array(
				'id'    => 'my-sites',
				'title' => '自治体',
			),
		);
		if ( ! current_user_can( 'administrator' ) ) {
			$wp_admin_bar->remove_menu( 'edit-profile' ); // ユーザー / プロフィールを編集.
		}
		if ( ! current_user_can( 'administrator' ) && ! current_user_can( 'ss-crew' ) ) {
			$wp_admin_bar->remove_menu( 'my-sites' ); // 参加サイト.
		}
	}
	/**
	 * ヘルプタブ非表示 @yamasaki
	 */
	public function remove_help_tabs() {
		$screen = get_current_screen();
		$screen->remove_help_tabs();
	}
	/**
	 * 「WordPressのご利用ありがとうございます。」の削除 @yamasaki
	 */
	public function custom_admin_footer() {
	}
}
