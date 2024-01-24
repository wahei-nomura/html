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
	const CUSTOMFIELD_ID_ROLE = 'notification-target-role';
	const CUSTOMFIELD_ID_REGION = 'notification-target-region';

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
		add_action( 'save_post', [$this, 'save_customfields'], 10, 2 ); // 第四引数が必要!!
		// リスト(表)のカラムの設定
		add_filter( 'manage_posts_columns', [$this, 'manage_posts_columns'], 10, 2 );
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
        // ユーザー権限
        add_meta_box(
            self::CUSTOMFIELD_ID_ROLE,
            'ユーザー権限',
            [$this, 'display_customfield_roll'], // コールバック関数を 'display_customfields' に変更
            'notification', // 投稿タイプを 'notification' に変更
            'side', // 表示する位置を右に変更
            'default' // 優先度 
        );
		// 自治体
        add_meta_box(
            self::CUSTOMFIELD_ID_REGION,
            '自治体',
            [$this, 'display_customfield_region'], // コールバック関数を 'display_customfields' に変更
            'notification', // 投稿タイプを 'notification' に変更
            'side', // 表示する位置を右に変更
            'low' // 優先度 
        );
    }

    /**
     * ユーザー権限の入力欄作成
     *
     * @param WP_Post $post post
     * @param array   $metabox メタボックスのデータ
     */
    public function display_customfield_roll( $post, $metabox ) {
		// ユーザー権限マスタ
		$user_roles = yaml_parse_file( get_theme_file_path( 'config/user-roles.yml' ) );
		// この投稿を表示するユーザー権限
		$post_roles = get_post_meta( $post->ID, self::CUSTOMFIELD_ID_ROLE, true );
        ?>
		<?php foreach ( $user_roles as $role_display_name => $role_detail ) : ?>
        <div>
            <label>
				<input
					type="checkbox"
					name="<?php echo $metabox['id']; ?>[]"
					value="<?php echo $role_detail['role']; ?>"
					<?php echo is_array($post_roles) && in_array($role_detail['role'], $post_roles) ? 'checked' : ''; ?>
				/>
				<span><?php echo $role_display_name; ?></span>
			</label>
        </div>
		<?php endforeach; ?>
        <?php
    }

	/**
     * 自治体の入力欄作成
     *
     * @param WP_Post $post post
     * @param array   $metabox メタボックスのデータ
     */
    public function display_customfield_region( $post, $metabox ) {
		// WPで管理している自治体のリスト
		$sites = get_sites();
		// この投稿を表示する自治体
		$post_regions = get_post_meta( $post->ID, self::CUSTOMFIELD_ID_REGION, true ); // カンマ区切りの文字列で格納してある
		?>
		<?php foreach ( $sites as $site ) : switch_to_blog( $site->blog_id ); ?>
		<div>
			<label>
				<input
					type="checkbox"
					name="<?php echo $metabox['id']; ?>[]"
					value="<?php echo $site->blog_id; ?>"
					<?php echo is_array($post_regions) && in_array($site->blog_id, $post_regions) ? 'checked' : ''; ?>
				/>
				<span><?php echo get_bloginfo( 'name' ); ?></span>
			</label>
		</div>
		<?php restore_current_blog(); endforeach; ?>
		<?php
    }

    /**
     * カスタムフィールドのデータを保存
     *
     * @param int     $post_id 投稿ID
     * @param WP_Post $post 投稿オブジェクト
     */
    public function save_customfields( $post_id, $post ) {
		// ユーザー権限
		update_post_meta(
			$post_id,
			self::CUSTOMFIELD_ID_ROLE,
			$_POST[self::CUSTOMFIELD_ID_ROLE] ?? [] // チェックが入ってないと何も値が来ないから置換する
		);
		// 自治体
		update_post_meta(
			$post_id,
			self::CUSTOMFIELD_ID_REGION,
			$_POST[self::CUSTOMFIELD_ID_REGION] ?? []
		);
    }

	/**
	 * カラム調整
	 *
	 * @param array  $columns カラム名の配列
	 * @param string $post_type 投稿タイプ
	 * @return array $columns
	 */
	public function manage_posts_columns( $columns, $post_type ) {
		if ($post_type === 'notification') {
			$columns['title'] = 'タイトル';
			$columns['date'] = '公開日時';
		}
		return $columns;
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
