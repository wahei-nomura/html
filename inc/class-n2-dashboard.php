<?php
/**
 * class-n2-dashboard.php
 *
 * @package neoneng
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'N2_Dashboard' ) ) {
	new N2_Dashboard();
	return;
}

/**
 * Dashboard
 */
class N2_Dashboard {
	/**
	 * コンストラクタ
	 */
	public function __construct() {
		add_action( 'wp_dashboard_setup', array( $this, 'remove_widgets' ) );
	}

	/**
	 * remove_widgets
	 * デフォルトのダッシュボードをまっさらに
	 *
	 * @return void
	 */
	public function remove_widgets() {
		global $wp_meta_boxes;
		unset( $wp_meta_boxes['dashboard']['normal']['core']['dashboard_right_now'] ); // 現在の状況
		unset( $wp_meta_boxes['dashboard']['normal']['core']['dashboard_activity'] ); // アクティビティ
		unset( $wp_meta_boxes['dashboard']['normal']['core']['dashboard_recent_comments'] ); // 最近のコメント
		unset( $wp_meta_boxes['dashboard']['normal']['core']['dashboard_incoming_links'] ); // 被リンク
		unset( $wp_meta_boxes['dashboard']['normal']['core']['dashboard_plugins'] ); // プラグイン
		unset( $wp_meta_boxes['dashboard']['normal']['core']['dashboard_site_health'] ); // サイトヘルス
		unset( $wp_meta_boxes['dashboard']['side']['core']['dashboard_quick_press'] ); // クイック投稿
		unset( $wp_meta_boxes['dashboard']['side']['core']['dashboard_recent_drafts'] ); // 最近の下書き
		unset( $wp_meta_boxes['dashboard']['side']['core']['dashboard_primary'] ); // WordPressブログ

		// プラグインで追加された項目
		remove_meta_box( 'wp_mail_smtp_reports_widget_lite', 'dashboard', 'normal' ); // WP Mail SMTP

		remove_action( 'welcome_panel', 'wp_welcome_panel' );
	}
}
