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
	const CUSTOMFIELD_ID_FORCE   = 'notification-force';
	const CUSTOMFIELD_ID_READ    = 'notification-read';
	const CUSTOMFIELD_ID_ROLES   = 'notification-roles';
	const CUSTOMFIELD_ID_REGIONS = 'notification-regions';

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'create_posttype' ) );
		// お知らせのタイトル入力欄のplaceholderを設定
		add_filter( 'enter_title_here', array( $this, 'change_title' ) );
		// お知らせの表示対象の入力欄を設定
		add_action( 'add_meta_boxes', array( $this, 'add_customfields' ) );
		// カスタムフィールドの入力を保存
		add_action( 'wp_after_insert_post', array( $this, 'save_customfields' ), 99999, 4 );
		// リスト(表)のカラムの設定
		add_filter( 'manage_notification_posts_columns', array( $this, 'manage_notification_columns' ), 10, 4 );
		// リスト(表)のフィールドの設定
		add_action( 'manage_notification_posts_custom_column', array( $this, 'custom_notification_column' ), 10, 4 );
	}

	/**
	 * サイトIDと自治体の名前を出力
	 */
	private static function get_region_options() {
		return array_map(
			function ( $s ) {
				switch_to_blog( $s->blog_id );
				$name = get_bloginfo( 'name' );
				restore_current_blog();
				return array( $s->blog_id, $name );
			},
			get_sites()
		);
	}

	/**
	 * ユーザー権限の値と表示名を出力
	 */
	private static function get_role_options() {
		$file_path = 'config/user-roles.yml';
		$roles     = yaml_parse_file( get_theme_file_path( $file_path ) );
		$values    = array_column( $roles, 'role' ); // 値
		$labels    = array_keys( $roles ); // 表示名
		$options   = array_map( fn( $value, $label ) => array( $value, $label ), $values, $labels );
		return $options;
	}

	/**
	 * カスタム投稿とタクソノミーの設定
	 */
	public function create_posttype() {
		register_post_type(
			'notification',
			array(
				'label'        => 'お知らせ(管理用)',
				'labels'       => array( // 管理画面に表示されるラベルの文字を指定
					'add_new'            => '新規追加',
					'edit_item'          => '編集',
					'view_item'          => '表示',
					'search_items'       => '検索',
					'not_found'          => '見つかりませんでした。',
					'not_found_in_trash' => 'ゴミ箱にはありませんでした。',
				),
				'public'       => false, // サイト上では非表示
				'show_ui'      => is_admin() && is_main_site(), // 管理画面では表示
				'description'  => '', // 説明文
				'hierarchicla' => false, // コンテンツを階層構造にするかどうか
				'has_archive'  => true,  // trueにすると投稿した記事の一覧ページを作成することができる
				'show_in_rest' => true,  // 新エディタ Gutenberg を有効化（REST API を有効化）
				'menu_icon'    => 'dashicons-bell', // 左ナビのアイコンをベルのアイコンに変更
				'supports'     => array(
					'title',  // タイトル
					'editor',  // 本文の編集機能
					'publish', // 公開日時の設定を有効にする
				),
			)
		);
	}

	/**
	 * タイトルを変更する。ここでのタイトルとはプレースホルダーのこと。
	 *
	 * @param string $title タイトル
	 * @return string 変更されたタイトル
	 */
	public function change_title( $title ) {
		$screen = get_current_screen();
		return 'notification' === $screen->post_type
			? 'お知らせのタイトルを入力'
			: $title;
	}

	/**
	 * お知らせの表示対象の入力欄を設定
	 */
	public function add_customfields() {
		// 強制表示
		add_meta_box(
			self::CUSTOMFIELD_ID_FORCE, // カスタムフィールドID
			'強制表示', // 表示名
			array( $this, 'display_customfield_force' ), // コールバック
			'notification', // 投稿タイプ
			'side', // 表示位置
			'default' // 優先度
		);
		// ユーザー権限
		add_meta_box(
			self::CUSTOMFIELD_ID_ROLES,
			'ユーザー権限',
			array( $this, 'display_customfield_roll' ),
			'notification',
			'side',
			'default'
		);
		// 自治体
		add_meta_box(
			self::CUSTOMFIELD_ID_REGIONS,
			'自治体',
			array( $this, 'display_customfield_region' ),
			'notification',
			'side',
			'default'
		);
	}

	/**
	 * 強制表示の入力欄を表示する
	 *
	 * @param WP_Post $post 投稿オブジェクト
	 * @param array   $metabox メタボックス情報
	 */
	public function display_customfield_force( $post, $metabox ) {
		global $pagenow;
		$is_checked = '';
		if ( 'post-new.php' !== $pagenow ) {
			// 編集画面
			$is_checked = get_post_meta( $post->ID, self::CUSTOMFIELD_ID_FORCE, true ) ? 'checked' : '';
		}
		// nonce
		// ３つあるカスタムフィールドの代表
		wp_nonce_field(
			'n2nonce-customfield',
			'n2nonce-customfield'
		);
		?>
		<div>
			<p>オンにすると表示対象のユーザーの画面に点滅するバーが表示されされます。</p>
			<label>
				<input
					type="checkbox"
					name="<?php echo esc_attr( $metabox['id'] ); ?>"
					<?php echo esc_attr( $is_checked ); ?>
				/>
				<span>表示を強制する</span>
			<label>
		</div>
		<?php
	}

	/**
	 * ユーザー権限の入力欄を表示する
	 *
	 * @param WP_Post $post 投稿オブジェクト
	 * @param array   $metabox メタボックス情報
	 */
	public function display_customfield_roll( $post, $metabox ) {
		global $pagenow;
		// チェックボックス生成用
		$options = self::get_role_options();
		// 新規追加と編集で初期値の取り方が変化する
		$initial = 'post-new.php' === $pagenow
			? array_column( $options, 0 ) // 新規追加なら全て選択でスタート
			: get_post_meta( $post->ID, self::CUSTOMFIELD_ID_ROLES, true );
		// json文字列に変換
		$options = wp_json_encode( $options, JSON_UNESCAPED_UNICODE );
		$initial = wp_json_encode( $initial, JSON_UNESCAPED_UNICODE );
		?>
		<div id="notification-input-roles">
			<custom-checkboxes
			name="<?php echo esc_attr( $metabox['id'] ); ?>[]"
			:options="<?php echo esc_attr( $options ); ?>"
			:initial="<?php echo esc_attr( $initial ); ?>"
			>
				<template #label-all>全てのユーザー権限</template>
			</custom-checkboxes>
		</div>
		<?php
	}

	/**
	 * 自治体の入力欄を表示する
	 *
	 * @param WP_Post $post 投稿オブジェクト
	 * @param array   $metabox メタボックス情報
	 */
	public function display_customfield_region( $post, $metabox ) {
		global $pagenow;
		// チェックボックス生成用
		$options = self::get_region_options();
		// 新規追加と編集で初期値の取り方が変化する
		$initial = 'post-new.php' === $pagenow
			? array_column( $options, 0 )
			: get_post_meta( $post->ID, self::CUSTOMFIELD_ID_REGIONS, true );
		// json文字列に変換
		$options = wp_json_encode( $options, JSON_UNESCAPED_UNICODE );
		$initial = wp_json_encode( $initial, JSON_UNESCAPED_UNICODE );
		?>
			<div id="notification-input-regions">
				<custom-checkboxes
				name="<?php echo esc_attr( $metabox['id'] ); ?>[]"
				:options="<?php echo esc_attr( $options ); ?>"
				:initial="<?php echo esc_attr( $initial ); ?>"
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
	 * @param bool    $update 更新かどうか
	 * @param WP_Post $post_before 更新前の投稿オブジェクト
	 */
	public function save_customfields( $post_id, $post, $update, $post_before ) {
		// お知らせの投稿の時だけOK
		if ( 'notification' !== $post->post_type ) {
			return;
		}
		// nonce
		if ( false === wp_verify_nonce( $_POST['n2nonce-customfield'], 'n2nonce-customfield' ) ) {
			return;
		}
		// 強制表示
		$new_force = $_POST[ self::CUSTOMFIELD_ID_FORCE ] ? 1 : 0;
		update_post_meta( $post_id, self::CUSTOMFIELD_ID_FORCE, $new_force );
		// ユーザー権限
		$new_roles = $_POST[ self::CUSTOMFIELD_ID_ROLES ] ?? array();
		update_post_meta( $post_id, self::CUSTOMFIELD_ID_ROLES, $new_roles );
		// 自治体
		$new_regions = $_POST[ self::CUSTOMFIELD_ID_REGIONS ] ?? array();
		update_post_meta( $post_id, self::CUSTOMFIELD_ID_REGIONS, $new_regions );
		// 本文の画像のsrcを正規化して再保存
		$post->post_content_filtered = get_the_content( null, false, $post );
		wp_insert_post( $post, false, false );
	}

	/**
	 * 通知のカラムを設定
	 */
	public function manage_notification_columns() {
		return array(
			'cb'      => 'ID',
			'post_id' => 'ID',
			'title'   => 'タイトル',
			'roles'   => '対象権限',
			'regions' => '対象自治体',
			'date'    => '公開日時',
		);
	}

	/**
	 * カスタム通知カラムを表示
	 *
	 * @param string $column_name カラム名
	 * @param int    $post_id     投稿ID
	 */
	public function custom_notification_column( $column_name, $post_id ) {
		echo match ( $column_name ) {
			'post_id' => $post_id,
			'roles' => ( function () use ( $post_id ) {
				$options = self::get_role_options();
				$options = array_column( $options, 1, 0 );
				$roles   = get_post_meta( $post_id, self::CUSTOMFIELD_ID_ROLES, true );
				if ( count( $roles ) === count( $options ) ) {
					return 'すべて';
				}
				$roles = array_map( fn( $r ) => $options[ $r ], $roles );
				$roles = implode( ',', $roles );
				return $roles;
			} )(),
			'regions' => ( function () use ( $post_id ) {
				$options = self::get_region_options();
				$options = array_column( $options, 1, 0 );
				$regions = get_post_meta( $post_id, self::CUSTOMFIELD_ID_REGIONS, true );
				if ( count( $regions ) === count( $options ) ) {
					return 'すべて';
				}
				$regions = array_map( fn( $r ) => $options[ $r ], $regions );
				$regions = implode( ',', $regions );
				return $regions;
			} )(),
		};
	}
}
