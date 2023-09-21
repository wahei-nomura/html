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
	 * @param int $categorySetId カテゴリセットID
	 *
	 * @return array カテゴリ一覧
	 */
	public static function category_trees_get( $categorySetId = 0, $categorysetfields = 'TITLE', $categoryfields = 'TITLE' ) {
		$params = array(
			'categorysetfields' => $categorysetfields,
			'categoryfields'    => $categoryfields,
		);
		$url    = static::$settings['endpoint'] . '/2.0/categories/shop-category-trees/category-set-ids/' . $categorySetId . '?' . http_build_query( $params );
		$data   = wp_remote_get( $url, array( 'headers' => static::$data['header'] ) );

		if ( is_wp_error( $data ) ) {
			return array();
		}

		$categories = json_decode( $data['body'], true );

		return $categories;
	}
}
