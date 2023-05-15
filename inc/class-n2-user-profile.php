<?php
/**
 * class-n2-user-profile.php
 *
 * @package neoneng
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'N2_User_Profile' ) ) {
	new N2_User_Profile();
	return;
}

/**
 * N2_User_Profile
 */
class N2_User_Profile {

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		add_action( 'user_contactmethods', array( $this, 'add_user_custom_meta' ) ); // 項目の追加
		add_filter( 'gettext', array( $this, 'meta_name_rename' ), 10, 3 ); // ユーザー項目の編集
		add_action( 'show_user_profile', array( $this, 'add_profile_caution' ) ); // ユーザープロフィール編集画面にフィールドを追加する
	}

	/**
	 * 項目の追加
	 *
	 * @param array $add_custom_meta 追加するメタデータを格納した配列
	 * @return array
	 */
	public function add_user_custom_meta( $add_custom_meta ) {
		$add_custom_meta['portal_site_display_name'] = 'ポータルサイトでの表示名';
		return $add_custom_meta;
	}

	/**
	 * 項目名の編集
	 *
	 * @param array  $translation 翻訳時に表示される文言
	 * @param string $text テキスト
	 * @param string $domain ドメイン名。省略の為default。
	 * @return array
	 */
	public function meta_name_rename( $translation, $text, $domain ) {
		global $pagenow;
		if ( 'profile.php' === $pagenow || 'user-edit.php' === $pagenow ) {
			if ( 'default' === $domain ) {
				$rename_columns = array(
					'Nickname'                 => 'ユーザー一覧での表示名',
					'First Name'               => '事業者名',
					'Last Name'                => '事業者コード',
					'Display name publicly as' => '基本表示名',
				);
				if ( isset( $rename_columns[ $text ] ) ) {
					$translation = $rename_columns[ $text ];
				}
			}
		}
		return $translation;
	}
	/**
	 * プロフィールに任意のフィールドを追加するサンプル
	 */
	public function add_profile_caution() {
		echo '<h2 style="display:block">※ポータルサイトに事業者名を表示したくない場合は「ポータルサイトでの表示名」に「記入しない」と入力してください。</h2>';
	}
}
