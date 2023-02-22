<?php
/**
 * class-n2-user-profile.php
 *
 * @package neoneng
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'N2_User_Profile_Custom' ) ) {
	new N2_User_Profile_Custom();
	return;
}

/**
 * N2_User_Profile_Custom
 */
class N2_User_Profile_Custom {

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		add_action( 'user_contactmethods', array( $this, 'add_user_custom_meta' ) ); // 項目の追加
		add_filter( 'gettext', array( $this, 'meta_name_rename' ), 10, 3 ); // ユーザー項目の編集
	}

	/**
	 * 項目の追加
	 *
	 * @param array $add_custom_meta
	 * @return array
	 */
	public function add_user_custom_meta( $add_custom_meta ) {
		$add_custom_meta['portal_site_display_name'] = 'ポータルサイトでの表示名';
		return $add_custom_meta;
	}

	/**
	 * 項目名の編集
	 *
	 * @param array $translation
	 * @param string $text
	 * @param string $domain
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
}