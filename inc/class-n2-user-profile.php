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
		#your-profile .user-comment-shortcuts-wrap /* キーボードショートカット */ {
		display: none;
		}
	</style>'.PHP_EOL;
}

//ユーザー項目の編集
function meta_name_rename( $translation, $text, $domain ) {
	global $pagenow;
	if ( 'profile.php' === $pagenow || 'user-edit.php' === $pagenow ) {
		if ( 'default' === $domain ) {
			$texts = array (
				'Nickname' => '管理画面表示名',
				'First Name' => '事業者コード(英大字)',
				'Last Name' => '事業者名',
			);
			if ( isset( $texts[$text] ) ) {
				$translation = $texts[$text];
			}
		}
	}
	return $translation;
}

add_action('user_contactmethods', 'add_user_custom_meta'); //ユーザー項目の追加
add_action('admin_print_styles', 'user_profile_hide_style'); //ユーザー項目の削除
add_filter( 'gettext', 'meta_name_rename', 10, 3);//ユーザー項目の編集
?>