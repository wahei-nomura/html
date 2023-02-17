<?php
// ユーザー登録項目の追加
function add_user_custom_meta($add_custom_meta){
	$add_custom_meta['portal_site_display_name'] = 'ポータル表示名';

	return $add_custom_meta;
}

//ユーザー項目の削除
function user_profile_hide_style() {
	echo '<style>
	#your-profile .user-rich-editing-wrap, /* ビジュアルエディター */
	#your-profile .user-syntax-highlighting-wrap, /* シンタックスハイライト */
	#your-profile .user-admin-color-wrap, /* 管理画面の配色 */
	#your-profile .user-comment-shortcuts-wrap, /* キーボードショートカット */
	#your-profile .show-admin-bar, /* ツールバー */
	#your-profile .user-language-wrap, /* 言語 */
	#your-profile .user-description-wrap, /* プロフィール情報 */
	#your-profile .user-profile-picture, /* プロフィール写真 */
	#your-profile .user-sessions-wrap /* セッション */ {
	  display: none;
	}
	</style>'.PHP_EOL;
}

add_action('user_contactmethods', 'add_user_custom_meta'); //ユーザー項目の追加
//add_action('admin_print_styles', 'user_profile_hide_style'); //ユーザー項目の削除。決定するまで一旦保留
?>