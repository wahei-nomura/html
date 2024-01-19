<?php
/**
 * お知らせ(管理用)
 *
 * @package neoneng
 */

if ( class_exists( 'N2_Notification_Read' ) ) {
	new N2_Notification_Read();
	return;
}

/**
 * ポータルスクレイピング
 */
class N2_Notification_Read {
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
		add_menu_page(
			'お知らせ',
			'お知らせ',
			'ss_crew',
			'n2_notification_read',
			[$this, 'display_page'],
			'dashicons-bell',
			25
		);
	}

	public function display_page() {
		// コンポーネントをタグにとして出力する時は小文字のケバブケースで書かないと認識されないよー
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline">お知らせ</h1>
			<div id="app">
				<notification-list />
			</div>
		</div>
		<?php
	}
}
