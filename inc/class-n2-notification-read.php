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
		add_action( 'init', [$this, 'set_read']);
		add_action( 'admin_init', array($this, 'custom_redirect_on_permission_error'));
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
	}

	public function set_read() {
		$has_all_params = isset($_POST['user']) || isset($_POST['post']);
		if (!$has_all_params) return;
		switch_to_blog(1);
		$wp_post = get_post((int)$_POST['post']);
		if (!is_null($wp_post)) {
			$notification_read = get_post_meta($wp_post->ID, 'notification-read', true);
			if ($notification_read === '') $notification_read = [];
			if (!in_array($_POST['user'], $notification_read)) {
				$notification_read[] = $_POST['user'];
				update_post_meta($wp_post->ID, 'notification-read', $notification_read);
			}
		}
		restore_current_blog();
		exit;
	}

	/**
	 * メニュー追加
	 */
	public function add_menu() {
		// 自治体のページならナビゲーションに追加
		if (get_site()->blog_id != 1) {
			add_menu_page(
				'お知らせ',
				'お知らせ',
				'ss_crew',
				'n2_notification_read',
				[$this, 'display_page'],
				'dashicons-bell',
				25
			);
		} else {
			// wp_redirect('edit.php?post_type=notification');
		}
	}

	public function custom_redirect_on_permission_error() {
		// メインサイトには閲覧用ページがないから管理用のページにリダイレクト
		if (get_site()->blog_id == 1) {
			// wp_redirect('/wp-admin/edit.php?post_type=notification');
		}
	}

	public function display_page() {
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline">お知らせ</h1>
			<p>NENGシステムに関するお知らせをこのページから確認できます。</p>
			<div id="app">
				<!-- この中はVueでよしなにやる -->
			</div>
		</div>
		<?php
	}
}
