<?php
// ユーザー登録項目の追加
function add_user_custom_meta($add_custom_meta){
	$add_custom_meta['portal_site_display_name'] = 'ポータルサイトでの表示名';

	return $add_custom_meta;
}

//ユーザー項目の削除
function user_profile_hide_style() {
	echo '
	<style>
		#your-profile .user-rich-editing-wrap, /* ビジュアルエディター */
		#your-profile .user-syntax-highlighting-wrap, /* シンタックスハイライト */
		#your-profile .user-admin-color-wrap, /* 管理画面の配色 */
		#your-profile .user-comment-shortcuts-wrap, /* キーボードショートカット */
		#your-profile .user-language-wrap, /* 言語 */
		#your-profile .user-url-wrap, /* サイト */
		#your-profile .user-description-wrap, /* プロフィール情報 */
		#your-profile .user-profile-picture, /* プロフィール写真 */
		#your-profile h2 {
			display: none;
		}
	</style>'.PHP_EOL;
}

//ユーザー項目の編集
function meta_name_rename( $translation, $text, $domain ) {
	global $pagenow;
	if ( 'profile.php' === $pagenow || 'user-edit.php' === $pagenow ) {
		if ( 'default' === $domain ) {
			$rename_columns = array (
				'Nickname' => 'ユーザー一覧での表示名',
				'First Name' => '事業者名',
				'Last Name' => '事業者コード',
				'Display name publicly as' => '基本表示名',
			);
			if ( isset( $rename_columns[$text] ) ) {
				$translation = $rename_columns[$text];
			}
		}
	}
	return $translation;
}

add_action('user_contactmethods', 'add_user_custom_meta'); //ユーザー項目の追加
add_action('admin_print_styles', 'user_profile_hide_style'); //ユーザー項目の削除
add_filter( 'gettext', 'meta_name_rename', 10, 3);//ユーザー項目の編集
?>