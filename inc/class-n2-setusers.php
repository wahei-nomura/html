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
		// ユーザーロール全更新API
		add_action( 'wp_ajax_n2_update_all_site_user_roles', array( $this, 'update_all_site_user_roles' ) );
		// クルーは全サイトに参加
		add_action( 'admin_init', array( $this, 'crew_join_allsite' ) );
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
	 */
	public function crew_join_allsite() {
		global $n2;
		$user  = $n2->current_user;
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
}
