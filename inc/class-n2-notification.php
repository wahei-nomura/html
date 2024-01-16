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
		add_action( 'init', fn() => $this->create_posttype() );
	}

	/**
	 * カスタム投稿のページのリスト
	 */
	function create_posttype() {
		register_post_type(
			'notification',
			array(
				'label' => 'お知らせ', // 管理画面上の表示（日本語でもOK）
				'public' => true, // 管理画面に表示するかどうかの指定
				'supports' => array(
				),
			)
		);
		register_taxonomy(
			'notification-target-authority',
			'notification',
			array(
				'label' => '表示対象のユーザー権限',
				'hierarchical' => true,
				'public' => true,
				'show_in_rest' => true,
			)
		);
		register_taxonomy(
			'notification-target-region',
			'notification',
			array(
				'label' => '表示対象の自治体',
				'hierarchical' => true,
				'public' => true,
				'show_in_rest' => true,
			)
		);
	}
}
