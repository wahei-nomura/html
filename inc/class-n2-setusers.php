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

		// ここのフックを変更したら高速化できる
		add_action( 'init', array( $this, 'remove_usertype' ) );
		add_action( 'init', array( $this, 'add_usertype' ) );
		add_action( 'admin_init', array( $this, 'crew_in_allsite' ) );
	}

	/**
	 * remove_usertype
	 * 一旦全ユーザ権限を削除
	 *
	 * @return void
	 */
	public function remove_usertype() {
		global $wp_roles;

		$wp_roles->remove_role( 'municipal-office' ); // 役場初期化
		$wp_roles->remove_role( 'jigyousya' ); // 事業者初期化
		$wp_roles->remove_role( 'ss-crew' ); // SSクルー初期化
		$wp_roles->remove_role( 'editor' ); // 編集者
		$wp_roles->remove_role( 'subscriber' ); // 購読者
		$wp_roles->remove_role( 'contributor' ); // 寄稿者
		$wp_roles->remove_role( 'author' ); // 投稿者
	}

	/**
	 * add_usertype
	 *
	 * @return void
	 */
	public function add_usertype() {
		global $wp_roles;

		// 役場
		$wp_roles->add_role( 'municipal-office', '役場', array() );
		$wp_roles->add_cap( 'municipal-office', 'read' );
		$wp_roles->add_cap( 'municipal-office', 'edit_posts' );
		$wp_roles->add_cap( 'municipal-office', 'edit_others_posts' );
		$wp_roles->add_cap( 'municipal-office', 'edit_published_posts' );
		$wp_roles->add_cap( 'municipal-office', 'municipal-office' );

		// 事業者
		$wp_roles->add_role( 'jigyousya', '事業者', array() );
		$wp_roles->add_cap( 'jigyousya', 'read' );
		$wp_roles->add_cap( 'jigyousya', 'edit_posts' );
		$wp_roles->add_cap( 'jigyousya', 'edit_published_posts' );
		$wp_roles->add_cap( 'jigyousya', 'delete_posts' );
		$wp_roles->add_cap( 'jigyousya', 'upload_files' );
		$wp_roles->add_cap( 'jigyousya', 'jigyousya' );

		// SSクルー
		$wp_roles->add_role( 'ss-crew', 'SSクルー', array() );
		$wp_roles->add_cap( 'ss-crew', 'read' );
		$wp_roles->add_cap( 'ss-crew', 'edit_posts' );
		$wp_roles->add_cap( 'ss-crew', 'edit_others_posts' );
		$wp_roles->add_cap( 'ss-crew', 'edit_published_posts' );
		$wp_roles->add_cap( 'ss-crew', 'delete_posts' );
		$wp_roles->add_cap( 'ss-crew', 'delete_others_posts' );
		$wp_roles->add_cap( 'ss-crew', 'delete_published_posts' );
		$wp_roles->add_cap( 'ss-crew', 'publish_posts' );
		$wp_roles->add_cap( 'ss-crew', 'upload_files' );
		$wp_roles->add_cap( 'ss-crew', 'manage_options' );
		$wp_roles->add_cap( 'ss-crew', 'ss_crew' ); // role判定用に追加

		$user_caps = array(
			'list_users',
			'create_users',
			'delete_users',
			'edit_users',
			'remove_users',
			'promote_users',
			'manage_network_users',
		);
		foreach ( $user_caps as $cap ) {
			$wp_roles->add_cap( 'ss-crew', $cap );
		}

	}

	/**
	 * ss-crewは全自治体へ追加
	 */
	public function crew_in_allsite() {
		global $n2;
		$user = $n2->current_user;
		if ( count( get_sites() ) === count( get_blogs_of_user( $user->ID ) ) ) {
			return;
		}
		if ( 'ss-crew' !== $user->roles[0] ) {
			return;
		}
		$sites = get_sites();

		foreach ( $sites as $site ) {
			$blog_id = $site->blog_id;
			add_user_to_blog( $blog_id, $user->ID, 'ss-crew' );
		}
	}
}
