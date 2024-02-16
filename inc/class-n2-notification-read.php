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
		// パラメーターがあれば既読の登録
		add_action( 'init', [$this, 'set_read']);
		// 左ナビに出す
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
	}

	/**
	 * パラメーター揃ってたら既読残す
	 */
	public function set_read() {
		global $n2;
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
				$n2->set_unread_notification_count(); // 未読件数を再取得
			}
			unset($_POST['user'], $_POST['post']); // リロード再送対策
		}
		restore_current_blog();
	}

	/**
	 * メニュー追加
	 */
	public function add_menu() {
		global $n2;
		$label = $n2->unread_notification_count > 0
			? "お知らせ ($n2->unread_notification_count)"
			: 'お知らせ';
		add_menu_page(
			'お知らせ',
			$label,
			'read',
			'n2_notification_read',
			[$this, 'display_page'],
			'dashicons-bell',
			25
		);
	}

	/**
	 * お知らせページ(閲覧用)を表示
	 *
	 * @return void
	 */
	public function display_page() {
		// この中で検索しないと別ページでも検索されて必要以上に重くなる
		$notifications = $this->get_notifications();
		$notifications = json_encode($notifications, JSON_UNESCAPED_UNICODE);
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline">お知らせ</h1>
			<div id="app">
				<!-- Vue -->
				<notification-read :posts="<?php echo esc_attr($notifications); ?>" />
			</div>
		</div>
		<?php
	}

	/**
	 * お知らせ検索
	 *
	 * @return void
	 */
	private function get_notifications() {
		global $n2;
		switch_to_blog(1); // メインサイトからのみ投稿してる
		// 全てのお知らせを取得
		$posts = get_posts([
			'post_type' => 'notification',
			'post_status' => 'publish', // 公開中のみ
			'numberposts' => -1, // 全て
			'orderby' => 'date', // 公開日で並び替え
			'order' => 'DESC', // 降順に並び替え
		]);
		// フィルター
		$posts = array_filter($posts, function($p) use($n2) {
			// 自治体フィルター
			$regions = get_post_meta($p->ID, 'notification-regions', true);
			if (!is_array($regions)) return false; // get_post_meta()で取得できなかったら空文字が返り値になる
			if (!in_array($n2->site_id, $regions)) return false;
			// 権限フィルター
			if (!is_admin()) {
				$roles = get_post_meta($p->ID, 'notification-roles', true);
				if (!in_array($n2->current_user->roles[0], $roles)) return false;
			}
			return true;
		});
		// マップ
		$posts = array_map(function($p) use($n2) {
			// 強制表示
			// フラグが立っていても投稿日時がユーザーの登録より前なら強制表示はしない
			$force = (int) get_post_meta($p->ID, 'notification-force', true);
			$force &= strtotime($p->post_date) > strtotime($n2->current_user->user_registered);
			$p->is_force = $force;
			$p->post_content = get_the_content( null, false, $p->ID );
			// 確認が必要か
			$read = get_post_meta($p->ID, 'notification-read', true);
			$p->is_read = is_array($read) ? in_array($n2->current_user->ID, $read) : false;
			return $p;
		}, $posts);
		$posts = array_values($posts);
		restore_current_blog(); // 戻す
		return $posts;
	}
}
