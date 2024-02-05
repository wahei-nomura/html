<?php
/**
 * お知らせ(管理用)
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
	 * カスタムフィールドのID
	 */
	const CUSTOMFIELD_ID_FORCE = 'notification-force';
	const CUSTOMFIELD_ID_READ = 'notification-read';
	const CUSTOMFIELD_ID_ROLES = 'notification-roles';
	const CUSTOMFIELD_ID_REGIONS = 'notification-regions';

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		// ページングとかナビゲーションの設定
		add_action( 'init', [$this, 'create_posttype'] );
		// お知らせのタイトル入力欄のplaceholderを設定
		add_filter( 'enter_title_here', [$this, 'change_title'] );
		// お知らせの表示対象の入力欄を設定
		add_action( 'add_meta_boxes', [$this, 'add_customfields'] );
		// カスタムフィールドの入力を保存
		add_action( 'save_post', [$this, 'save_customfields'], 10, 3 ); // 第四引数が必要!!
		// リスト(表)のカラムの設定
		add_filter('manage_notification_posts_columns', [$this, 'manage_notification_columns'], 10, 4);
		add_action('manage_notification_posts_custom_column', [$this, 'custom_notification_column'], 10, 4);
		// 投稿のステータスのラベルを修正
		add_filter( 'gettext', array( $this, 'change_status' ) );
		add_filter( 'ngettext', array( $this, 'change_status' ) );
	}

	/**
	 * カスタム投稿とタクソノミーの設定
	 */
	function create_posttype() {
		// 管理者でメインサイトを選択しているときだけ表示
		$is_admin = current_user_can( 'administrator' );
		$is_main_site = get_site()->blog_id == 1;
		$is_show_ui = $is_admin && $is_main_site;
		// カスタム投稿
		register_post_type(
			'notification',
			array(
				'label' => 'お知らせ(管理用)',
				'labels' => array(  //管理画面に表示されるラベルの文字を指定
					'add_new' => '新規追加',
					'edit_item' => '編集',
					'view_item' => '表示',
					'search_items' => '検索',
					'not_found' => '見つかりませんでした。',
					'not_found_in_trash' => 'ゴミ箱にはありませんでした。',
				),
				'public' => false, // サイト上では非表示
				'show_ui' => $is_show_ui, // 管理画面では表示
				'description' => '過去に犯した過ちを自分達で払拭しなくちゃ本当の未来は訪れない！だから、私は戦う！！', // 説明文
				'hierarchicla' => false, // コンテンツを階層構造にするかどうか
				'has_archive' => true,  // trueにすると投稿した記事の一覧ページを作成することができる
				'show_in_rest' => true,  //新エディタ Gutenberg を有効化（REST API を有効化）
				'menu_icon' => 'dashicons-bell', // 左ナビのアイコンをベルのアイコンに変更
				'supports' => array(
					'title',  //タイトル
					'editor',  //本文の編集機能
					'publish', // 公開日時の設定を有効にする
				),
			)
		);
	}

	/**
	 * タイトル変更。タイトルはplaceholderのこと。
	 *
	 * @param string $title タイトル
	 * @return string
	 */
	public function change_title( $title ) {
		$title = 'お知らせのタイトルを入力';
		return $title;
	}

    /**
     * お知らせの表示対象の入力欄を設定
     *
     * @return void
     */
    public function add_customfields() {
		// 強制表示
        add_meta_box(
            self::CUSTOMFIELD_ID_FORCE, // カスタムフィールドID
            '強制表示', // 表示名
            [$this, 'display_customfield_force'], // コールバック
            'notification', // 投稿タイプ
            'side', // 表示位置
            'default' // 優先度 
        );
        // ユーザー権限
        add_meta_box(
            self::CUSTOMFIELD_ID_ROLES,
            'ユーザー権限',
            [$this, 'display_customfield_roll'],
            'notification',
            'side',
            'default'
        );
		// 自治体
        add_meta_box(
            self::CUSTOMFIELD_ID_REGIONS,
            '自治体',
            [$this, 'display_customfield_region'],
            'notification',
            'side',
            'default'
        );
    }

	/**
	 * ユーザー権限の値と表示名を出力
	 *
	 * @return [$value, $label][]
	 */
	private static function get_role_options() {
		$yml = 'config/user-roles.yml';
		$roles = yaml_parse_file(get_theme_file_path($yml));
		$values = array_column($roles, 'role'); // 値
		$labels = array_keys($roles); // 表示名
		$options = array_map(fn($value, $label) => [$value, $label], $values, $labels);
		n2_log($options);
		return $options;
	}

	/**
	 * 強制表示の入力欄
	 *
     * @param WP_Post $post post
     * @param array   $metabox メタボックスのデータ
	 */
	public function display_customfield_force($post, $metabox) {
		global $pagenow;
		$is_checked = '';
		if ($pagenow !== 'post-new.php') {
			// 編集画面
			$is_checked = get_post_meta($post->ID, self::CUSTOMFIELD_ID_FORCE, true) ? 'checked' : '';
		}
        ?>
        <div>
			<p>オンにすると表示対象のユーザーの画面に点滅するバーが表示されされます。</p>
			<label>
				<input
					type="checkbox"
					name="<?php echo esc_attr($metabox['id']); ?>"
					<?php echo esc_attr($is_checked); ?>
				/>
				<span>表示を強制する</span>
			<label>
		</div>
        <?php
	}

    /**
     * ユーザー権限の入力欄作成
     *
     * @param WP_Post $post post
     * @param array   $metabox メタボックスのデータ
     */
    public function display_customfield_roll( $post, $metabox ) {
		global $pagenow;
		// チェックボックス生成用
		$options = self::get_role_options();
		$options = json_encode($options, JSON_UNESCAPED_UNICODE);
		// 新規追加と編集で初期値の取り方が変化する
		$initial = $pagenow === 'post-new.php'
			? $values // 新規追加なら全て選択でスタート
			: get_post_meta($post->ID, self::CUSTOMFIELD_ID_ROLES, true);
		$initial = json_encode($initial, JSON_UNESCAPED_UNICODE);
        ?>
        <div id="notification-input-roles">
			<custom-checkboxes
				name="<?php echo esc_attr($metabox['id']); ?>[]"
				:options="<?php echo esc_attr($options); ?>"
				:initial="<?php echo esc_attr($initial); ?>"
			>
				<template #label-all>全てのユーザー権限</template>
			</custom-checkboxes>
		</div>
        <?php
    }

	/**
     * 自治体の入力欄作成
     *
     * @param WP_Post $post post
     * @param array   $metabox メタボックスのデータ
     */
    public function display_customfield_region( $post, $metabox ) {
		global $n2;
		global $pagenow;
		// チェックボックス生成用
		$regions = $n2->get_regions();
		$values = array_keys($regions);
		$labels = array_column($regions, 'name');
		$options = array_map(fn($value, $label) => [$value, $label], $values, $labels);
		$options = json_encode($options, JSON_UNESCAPED_UNICODE);
		// 新規追加と編集で初期値の取り方が変化する
		$initial = $pagenow === 'post-new.php'
			? $values
			: get_post_meta($post->ID, self::CUSTOMFIELD_ID_REGIONS, true);
		$initial = json_encode($initial, JSON_UNESCAPED_UNICODE);
        ?>
        <div id="notification-input-regions">
			<custom-checkboxes
				name="<?php echo esc_attr($metabox['id']); ?>[]"
				:options="<?php echo esc_attr($options); ?>"
				:initial="<?php echo esc_attr($initial); ?>"
			>
				<template #label-all>全ての自治体</template>
			</custom-checkboxes>
		</div>
        <?php
    }

    /**
     * カスタムフィールドのデータを保存
     *
     * @param int     $post_id 投稿ID
     * @param WP_Post $post 投稿オブジェクト
     */
    public function save_customfields( $post_id, $post ) {
		// 強制表示
		$new_force = $_POST[self::CUSTOMFIELD_ID_FORCE] ? 1 : 0;
		update_post_meta($post_id, self::CUSTOMFIELD_ID_FORCE, $new_force);
		// ユーザー権限
		$new_roles = $_POST[self::CUSTOMFIELD_ID_ROLES] ?? [];
		update_post_meta($post_id, self::CUSTOMFIELD_ID_ROLES, $new_roles);
		// 自治体
		$new_regions = $_POST[self::CUSTOMFIELD_ID_REGIONS] ?? [];
		update_post_meta($post_id, self::CUSTOMFIELD_ID_REGIONS, $new_regions);
    }

	/**
	 * カラム調整
	 *
	 * @param array  $columns カラム名の配列
	 * @return array $columns
	 */
	public function manage_notification_columns($columns) {
		$columns['title'] = 'タイトル';
		$columns['date'] = '公開日時';
		$columns['roles'] = '対象権限';
		return $columns;
	}
	public function custom_notification_column( $column_name, $post_id ) {
		echo match($column_name) {
			'roles' => (function() use ($post_id) {
				$options = self::get_role_options();
				$options = array_column($options, 1, 0);
				$roles = get_post_meta($post_id, self::CUSTOMFIELD_ID_ROLES, true);
				$roles = array_map(fn($r) => $options[$r], $roles);
				$roles = implode(', ', $roles);
				return $roles;
			})(),
		};
	}

	/**
	 * 返礼品のページでの変更を戻す
	 *
	 * @param string $status ステータス
	 * @return string $status ステータス
	 */
	public function change_status( $status ) {
		$re = array(
			'入力中' => '下書き',
			'スチームシップ確認待ち' => 'レビュー待ち',
			'ポータル登録準備中' => '公開済み',
		);
		// 変換
		$status = str_replace( array_keys( $re ), $re, $status );
		return $status;
	}
}
