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
		add_action( 'init', array( $this, 'change_postlabel' ) );
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
		$submenu['edit.php'][10][0] = '新しい' . $name;
	}

	/**
	 * remove_menulabel
	 */
	public function remove_menulabel() {
		$menus = array(
			'edit.php?post_type=page', // 固定ページ
			'edit-comments.php', // コメント
		);

		foreach ( $menus as $menu ) {
			remove_menu_page( $menu );
		}
	}

	/**
	 * change_postlabel
	 */
	public function change_postlabel() {
		global $wp_post_types;
		$name                       = '返礼品';
		$labels                     = &$wp_post_types['post']->labels;
		$labels->name               = $name;
		$labels->singular_name      = $name;
		$labels->add_new_item       = $name . 'の新規追加';
		$labels->edit_item          = $name . 'の編集';
		$labels->new_item           = '新規' . $name;
		$labels->view_item          = $name . 'を表示';
		$labels->search_items       = $name . 'を検索';
		$labels->not_found          = $name . 'が見つかりませんでした';
		$labels->not_found_in_trash = 'ゴミ箱に' . $name . 'は見つかりませんでした';
	}

}
