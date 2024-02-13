<?php
/**
 * RMS NAVIGATION API
 * /wp-admin/admin-ajax.php?action=n2_rms_navigation_api_ajax&mode=debug&call=
 *
 * @package neoneng
 */

if ( class_exists( 'N2_RMS_Navigation_API' ) ) {
	new N2_RMS_Navigation_API();
	return;
}

/**
 * NavigationAPIから商品属性情報を取得するAPI
 */
class N2_RMS_Navigation_API extends N2_RMS_Base_API {

	/**
	 * 指定したジャンルIDの情報を取得
	 *
	 * @var    string $genreId     ジャンルID
	 * @return array
	 */
	public static function genres_get( $genreId = 0 ) {
		$url = static::$settings['endpoint'] . "/2.0/navigation/genres/{$genreId}";

		$response = static::request( $url );
		return $response;
	}

	/**
	 * 指定したジャンルIDに紐づく商品属性情報を取得する
	 *
	 * @var    string $genreId     ジャンルID
	 * @var    string $attributeId 商品属性ID
	 * @return array
	 */
	public static function genres_attributes_get( $genreId, $attributeId = '' ) {
		$url = static::$settings['endpoint'] . "/2.0/navigation/genres/{$genreId}/attributes/{$attributeId}";

		$response = static::request( $url );
		return $response;
	}

	/**
	 * 指定したジャンルIDに紐づく推奨値を含めた商品属性情報を取得する
	 *
	 * @var    string $genreId     ジャンルID
	 * @var    string $attributeId 商品属性ID
	 * @return array
	 */
	public static function genres_attributes_dictionary_values_get( $genreId, $attributeId = '-' ) {
		$url = static::$settings['endpoint'] . "/2.0/navigation/genres/{$genreId}/attributes/{$attributeId}/dictionaryValues";

		$response = static::request( $url );
		return $response['body'];
	}
}
