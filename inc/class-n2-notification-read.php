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
		add_action( 'admin_init', array($this, 'custom_redirect_on_permission_error'));
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
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
		$posts = self::get_notifications(get_site()->blog_id);
		$posts = json_encode($posts, JSON_UNESCAPED_UNICODE);
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline">お知らせ</h1>
			<p>NENGシステムに関するお知らせをこのページから確認できます。</p>
			<div id="app">
				<n2-notification-read :custom-posts="<?php echo esc_attr($posts); ?>"  />
			</div>
		</div>
		<?php
	}

	private static function get_notifications() {
		global $n2;
		switch_to_blog(1); // メインサイトからのみ投稿してる
		$items = get_posts([
			'post_type' => 'notification',
			'post_status' => 'publish', // 公開中のみ
			'numberposts' => -1, // 全て
		]);
		$items = array_map(function($item) use($n2) {
			// 自治体フィルター
			$regiosn = get_post_meta($item->ID, 'notification-regions', true);
			if (!in_array($n2->site_id, $regiosn)) return false;
			// 権限フィルター
			if (!is_admin()) {
				$roles = get_post_meta($item->ID, 'notification-roles', true);
				if (!in_array($n2->current_user->roles[0], $roles)) return false;
			}
			// 強制表示
			$force = get_post_meta($item->ID, 'notification-force', true);
			$item->is_force = $force;
			// 確認が必要か
			$read = get_post_meta($item->ID, 'notification-read', true);
			$item->is_read = is_array($read) ? in_array($n2->site_id, $read) : false;
			return $item;
		}, $items);
		$items = array_filter($items);
		$items = array_values($items);
		restore_current_blog(); // 戻す
		return $items;
	}
}
