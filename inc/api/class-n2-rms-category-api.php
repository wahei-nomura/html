<?php
/**
 * RMS CATEGORY API
 *
 * @package neoneng
 */

 if ( class_exists( 'N2_RMS_Category_API' ) ) {
	new N2_RMS_Category_API();
	return;
}


/**
 * CATEGORY情報を取得するAPI
 */
class N2_RMS_Category_API extends N2_RMS_Base_API {
	/**
	 * カテゴリ一覧
	 *
	 * @var array
	 */
	protected $categories = array();

	/**
	 * フォルダ一覧取得
	 *
	 * @return array フォルダ一覧
	 */
	public static function categories_get() {
		// $url     = static::$settings['endpoint'] . '/1.0/cabinet/folders/get?' . http_build_query( $params );
		// $url     = static::$settings['endpoint'] . '/2.0/categories/item-mappings/manage-numbers/eag031';
		// print_r($url);
		// $data    = wp_remote_get( $url, array( 'headers' => static::$data['header'] ) );
		// print_r($data);
		// $categories = simplexml_load_string( $data['body'] )->categories;
		// $categories = json_decode( wp_json_encode( $categories ), true )['folder'];

		// return $categories;
		$url = 'test';
		return $url;
	}

}
