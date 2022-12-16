<?php
/**
 * class-n2-setusers.php
 *
 * @package neoneng
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
		add_action( 'init', array( $this, 'remove_usertype' ) );
		add_action( 'init', array( $this, 'add_usertype' ) );
	}

	/**
	 * remove_usertype
	 * デフォルトのユーザ権限を削除
	 *
	 * @return void
	 */
	public function remove_usertype() {
		global $wp_roles;
		if ( empty( $wp_roles ) ) {
			$wp_roles = new WP_Roles();
		}

		$wp_roles->remove_role( 'editor' ); // 編集者
		$wp_roles->remove_role( 'subscriber' ); // 購読者
		$wp_roles->remove_role( 'contributor' ); // 寄稿者
		$wp_roles->remove_role( 'author' ); // 投稿者
		$wp_roles->remove_cap( 'ss-crew', 'edit_theme_options' ); // テーマ編集不可
	}

	/**
	 * add_usertype
	 *
	 * @return void
	 */
	public function add_usertype() {
		global $wp_roles;
		if ( empty( $wp_roles ) ) {
			$wp_roles = new WP_Roles();
		}

		// 事業者
		$wp_roles->add_role( 'jigyousya', '事業者', array() );
		$wp_roles->add_cap( 'jigyousya', 'read' );
		$wp_roles->add_cap( 'jigyousya', 'edit_posts' );
		$wp_roles->add_cap( 'jigyousya', 'upload_files' );
		$wp_roles->add_cap( 'jigyousya', 'jigyousya' );

		// SSクルー
		$wp_roles->add_role( 'ss-crew', 'SSクルー', array() );
		$wp_roles->add_cap( 'ss-crew', 'read' );
		$wp_roles->add_cap( 'ss-crew', 'edit_posts' );
		$wp_roles->add_cap( 'ss-crew', 'delete_others_posts' );
		$wp_roles->add_cap( 'ss-crew', 'delete_posts' );
		$wp_roles->add_cap( 'ss-crew', 'delete_published_posts' );
		$wp_roles->add_cap( 'ss-crew', 'edit_others_posts' );
		$wp_roles->add_cap( 'ss-crew', 'edit_published_posts' );
		$wp_roles->add_cap( 'ss-crew', 'publish_posts' );
		$wp_roles->add_cap( 'ss-crew', 'upload_files' );
		$wp_roles->add_cap( 'ss-crew', 'ss_crew' ); // role判定用に追加

	}
}
