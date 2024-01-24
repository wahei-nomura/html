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
		if (get_site()->blog_id != 1) {
			// 自治体のページならナビゲーションに追加
			add_action( 'admin_menu', array( $this, 'add_menu' ) );
		}
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

	private static function get_notifications($current_blod_id) {
		global $n2;
		switch_to_blog(1); // メインサイトからのみ投稿してる
		$items = get_posts([
			'post_type' => 'notification',
			'post_status' => 'publish', // 公開中のみ
			'numberposts' => -1, // 全て
		]);
		$items = array_map(function($item) use($n2, $current_blod_id) {
			// カスタムフィールドの値を取得
			$meta = array_map(
				fn($v) => maybe_unserialize($v[0]),
				get_post_meta($item->ID)
			);
			// 自治体フィルター
			$has_target_region = in_array(
				$current_blod_id,
				$meta['notification-target-region']
			);
			if (!$has_target_region) return false;
			// 権限フィルター
			if (!is_admin()) {
				$has_target_role = in_array(
					$n2->current_user->roles[0],
					$meta['notification-target-role']
				);
				if (!$has_target_role) return false;
			}
			$item->post_meta = $meta;
			return $item;
		}, $items);
		$items = array_filter($items);
		$items = array_values($items);
		restore_current_blog(); // 戻す
		return $items;
	}
}
