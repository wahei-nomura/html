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
		// ページングとかナビゲーションの設定
		add_action( 'init', [$this, 'create_posttype'] );
		// お知らせのタイトル入力欄のplaceholderを設定
		add_filter( 'enter_title_here', [$this, 'change_title'] );
		// お知らせの表示対象の入力欄を設定
		add_action( 'add_meta_boxes', [$this, 'add_customfields'] );
	}

	/**
	 * カスタム投稿とタクソノミーの設定
	 */
	function create_posttype() {
		// カスタム投稿
		register_post_type(
			'notification',
			array(
				'label' => 'お知らせ',
				'labels' => array(  //管理画面に表示されるラベルの文字を指定
					'add_new' => '新規追加',
					'edit_item' => '編集',
					'view_item' => '表示',
					'search_items' => '検索',
					'not_found' => '見つかりませんでした。',
					'not_found_in_trash' => 'ゴミ箱にはありませんでした。',
				),
				'public' => true, // 管理画面に表示しサイト上にも表示する
				'description' => '過去に犯した過ちを自分達で払拭しなくちゃ本当の未来は訪れない！だから、私は戦う！！', // 説明文
				'hierarchicla' => false, // コンテンツを階層構造にするかどうか
				'has_archive' => true,  // trueにすると投稿した記事の一覧ページを作成することができる
				'show_in_rest' => false,  //新エディタ Gutenberg を有効化（REST API を有効化）
				'menu_icon' => 'dashicons-bell', // 左ナビのアイコンをベルのアイコンに変更
				'supports' => array(
					'title',  //タイトル
					'editor',  //本文の編集機能
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
            'user-privilege',
            'ユーザー権限',
            [$this, 'display_customfields'], // コールバック関数を 'display_customfields' に変更
            'notification', // 投稿タイプを 'notification' に変更
            'side', // 表示する位置を右に変更
            'default' // 優先度 
        );
		// 自治体
        add_meta_box(
            'local-governments',
            '自治体',
            [$this, 'display_customfields2'], // コールバック関数を 'display_customfields' に変更
            'notification', // 投稿タイプを 'notification' に変更
            'side', // 表示する位置を右に変更
            'low' // 優先度 
        );
    }

    /**
     * 入力欄作成（メタボックスの内容を表示）
     *
     * @param WP_Post $post post
     * @param array   $metabox メタボックスのデータ
     */
    public function display_customfields( $post, $metabox ) {
		$privileges = array(
			'SSクルー' => 'ss-crew',
			'自治体' => 'local-government',
			'事業者' => 'business person',
		);
        ?>
		<?php foreach ( $privileges as $label => $value ) : ?>
        <div class="">
            <label>
				<input
					type="checkbox"
					name="user-privileges"
					value="<?php echo $value; ?>"
				/>
				<span><?php echo $label; ?></span>
			</label>
        </div>
		<?php endforeach; ?>
        <?php
    }
	/**
     * 入力欄作成
     *
     * @param WP_Post $post post
     * @param array   $metabox メタボックスのデータ
     */
    public function display_customfields2( $post, $metabox ) {
		?>
		<?php foreach ( get_sites() as $site ) : switch_to_blog( $site->blog_id ); ?>
		<div>
			<label>
				<input type="checkbox" name="local-government-checkbox[]" value="<?php echo esc_attr( $site->blog_id ); ?>">
				<span><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
			</label>
		</div>
		<?php restore_current_blog(); endforeach; ?>
		<?php
    }
}
