<?php
/**
 * class-n2-setusers.php
 *
 * @package neoneng
 */

if ( class_exists( 'N2_Setusers' ) ) {
	new N2_Setusers();
	return;
}

/**
 * Setusers
 */
class N2_Setusers {
	/**
	 * コンストラクタ
	 */
	public function __construct() {
		// テーマ有効化時にユーザー設定
		add_action( 'after_switch_theme', array( $this, 'update_user_roles' ) );
		add_action( 'admin_init', array( $this, 'update_user_roles' ) );
		
		// ユーザーロール全更新API
		add_action( 'wp_ajax_n2_update_all_site_user_roles', array( $this, 'update_all_site_user_roles' ) );
		// クルーは全サイトに参加
		add_action( 'wp_login', array( $this, 'crew_join_allsite' ), 10, 2 );
		// アバター付ける
		add_filter( 'get_avatar_data', array( $this, 'change_avatar' ), 10, 2 );
		// ユーザーログインID変更可能に
		add_filter( 'wp_pre_insert_user_data', array( $this, 'change_user_login' ), 10, 4 );
		// ユーザーメタに商品タイプを保存
		add_action( 'n2_items_api_after_insert_post_data', array( $this, 'update_user_meta_item_type' ), 10, 2 );
	}

	/**
	 * ユーザーログインID変更可能にする
	 *
	 * @param array    $data {
	 *     Values and keys for the user.
	 *
	 *     @type string $user_login      The user's login. Only included if $update == false
	 *     @type string $user_pass       The user's password.
	 *     @type string $user_email      The user's email.
	 *     @type string $user_url        The user's url.
	 *     @type string $user_nicename   The user's nice name. Defaults to a URL-safe version of user's login
	 *     @type string $display_name    The user's display name.
	 *     @type string $user_registered MySQL timestamp describing the moment when the user registered. Defaults to
	 *                                   the current UTC timestamp.
	 * }
	 * @param bool     $update   Whether the user is being updated rather than created.
	 * @param int|null $user_id  ID of the user to be updated, or NULL if the user is being created.
	 * @param array    $userdata The raw array of data passed to wp_insert_user().
	 */
	public function change_user_login( $data, $update, $user_id, $userdata ) {
		$user_login     = $userdata['user_login'] ?? '';
		$from_user_edit = wp_verify_nonce( $_POST['_wpnonce'] ?? '', "update-user_{$user_id}" );// user-edit.phpからかどうか
		$user_login     = $from_user_edit ? ( $_POST['user_login'] ?? '' ) : $user_login;
		if ( ( ! current_user_can( 'administrator' ) && ! $from_user_edit ) || empty( $user_login ) || username_exists( $user_login ) || mb_strlen( $user_login ) > 60 ) {
			return $data;
		}
		$data['user_login'] = $user_login;
		return $data;
	}

	/**
	 * update_user_roles
	 *
	 * @return void
	 */
	public function update_user_roles() {
		// デフォルトの権限削除
		remove_role( 'editor' ); // 編集者
		remove_role( 'subscriber' ); // 購読者
		remove_role( 'contributor' ); // 寄稿者
		remove_role( 'author' ); // 投稿者
		remove_role( 'municipal-office' ); // 故 役場権限
		// N2のユーザ権限
		foreach ( (array) yaml_parse_file( get_theme_file_path( 'config/user-roles.yml' ) ) as $display_name => $v ) {
			remove_role( $v['role'] ); // 初期化
			add_role( $v['role'], $display_name, $v['capabilities'] );
		}
	}

	/**
	 * ユーザーロール全更新
	 */
	public function update_all_site_user_roles() {
		foreach ( get_sites() as $site ) {
			switch_to_blog( (int) $site->blog_id );
			if ( 'neo-neng' === get_template() ) {
				$this->update_user_roles();
			}
		}
		restore_current_blog();
		echo 'ユーザーロール全更新完了';
		exit;
	}

	/**
	 * ss-crewは全自治体へ追加
	 *
	 * @param string $user_login ユーザー名
	 * @param object $current_user WP_User
	 */
	public function crew_join_allsite( $user_login, $current_user ) {
		$user  = $current_user ?? wp_get_current_user();
		$sites = array(
			array_values( array_map( fn( $v ) => $v->blog_id, get_sites() ) ),
			array_values( array_map( fn( $v ) => $v->userblog_id, get_blogs_of_user( $user->ID ) ) ),
		);
		$diff  = array_diff( ...$sites );
		if ( ! empty( $diff ) && 'ss-crew' === $user->roles[0] ) {
			foreach ( $diff as $blog_id ) {
				add_user_to_blog( $blog_id, $user->ID, 'ss-crew' );
			}
		}
	}

	/**
	 * アバターの変更
	 *
	 * @param array  $args アバターデータ
	 * @param string $id_or_email ユーザーID
	 */
	public function change_avatar( $args, $id_or_email ) {
		$user_data = get_userdata( $id_or_email );
		if ( ! $user_data ) {
			return $args;
		}
		$args['url'] = match ( $user_data->roles[0] ) {
			'administrator' => get_theme_file_uri( 'img/fullfrontal.jpg' ),
			default => $args['url'],
		};
		return $args;
	}

	/**
	 * ss-crewに自爆ボタン設置
	 *
	 * @param object $wp_admin_bar WP_Admin_Bar
	 */
	public function destruct_button( $wp_admin_bar ) {
		global $n2;
		$user = $n2->current_user;

		if ( ! ( in_array( 'ss-crew', $user->roles, true ) || in_array( 'administrator', $user->roles, true ) ) ) {
			return;
		}

		$href = get_theme_file_path( 'template/admin-bar-menu/destruct-self-account.php' );
		$wp_admin_bar->add_menu(
			array(
				'id'     => 'destruct-self',
				'title'  => '自爆ボタン',
				'parent' => 'user-actions',
				'href'   => '#' . wp_create_nonce( 'n2nonce' ),
			),
		);
	}

	/**
	 * 投稿保存時にuser_metaの商品タイプ更新
	 *
	 * @param array $data 投稿配列
	 * @param array $meta_input メタデータ
	 */
	public function update_user_meta_item_type( $data, $meta_input ) {
		global $n2;
		$user_item_types = $n2->current_user->data->meta['商品タイプ'] ?? array();
		$item_types      = array_filter( (array) $meta_input['商品タイプ'] ) ?: array( '' );
		foreach ( $item_types as $type ) {
			$user_item_types[ $type ] = ( (int) $user_item_types[ $type ] ?? 0 ) + 1;
		}
		update_user_meta( $n2->current_user->ID, '商品タイプ', $user_item_types );
	}
}
