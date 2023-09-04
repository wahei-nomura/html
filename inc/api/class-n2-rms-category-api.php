<?php
/**
 * RMS CABINET API
 *
 * @package neoneng
 */

if ( class_exists( 'N2_RMS_Category_API' ) ) {
	new N2_RMS_Category_API();
	return;
}

/**
 * N2からCABINETへ送信したりするAPI
 */
class N2_RMS_Category_API extends N2_RMS_Base_API {
	/**
	 * フォルダ一覧
	 *
	 * @var array
	 */
	protected $folders = array();

	/**
	 * カテゴリ一覧取得
	 *
	 * @param string $control_number 商品管理番号
	 *
	 * @return array カテゴリ一覧
	 */
	public static function categories_get( $control_number ) {
		$params = array(
			'breadcrumb'        => 'true',
			'categorysetfields' => 'TITLE',
			'categoryfields'    => 'TITLE',
		);
		$url    = static::$settings['endpoint'] . '/2.0/categories/item-mappings/manage-numbers/' . $control_number . '?' . http_build_query( $params );
		$data   = wp_remote_get( $url, array( 'headers' => static::$data['header'] ) );

		if ( is_wp_error( $data ) ) {
			return array();
		}

		$categories = json_decode( $data['body'], true );

		return $categories;
	}
}
