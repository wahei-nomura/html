<?php
/**
 * お知らせ
 *
 * @package neoneng
 */

if ( class_exists( 'N2_Notification' ) ) {
	new N2_Notification();
	return;
}

/**
 * ポータルスクレイピング
 */
class N2_Notification {
	/**
	 * コンストラクタ
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
	}

	/**
	 * メニュー追加
	 */
	public function add_menu() {
		add_menu_page( 'お知らせ', 'お知らせ', 'ss_crew', 'n2_notification', array( $this, 'display_page_read' ), 'dashicons-bell', 81 );
	}

	/**
	 * 検索ページを表示
	 */
	public function display_page_read() {
		get_template_part( "template/notification/read", null, $this );
	}
}
