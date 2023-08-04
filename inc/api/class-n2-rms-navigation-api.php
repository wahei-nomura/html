<?php
/**
 * RMS NAVIGATION API
 * /wp-admin/admin-ajax.php?action=n2_rms_navigation_api_ajax&mode=debug&request=
 *
 * @package neoneng
 */

if ( class_exists( 'N2_RMS_Navigation_API' ) ) {
   new N2_RMS_Navigation_API();
   return;
}

/**
 * N2からCABINETへ送信したりするAPI
 */
class N2_RMS_Navigation_API extends N2_RMS_Base_API {
	/**
	 * 指定したジャンルIDに紐づく商品属性情報を取得する
	 *
	 * @return array
	 */
	public static function genres_attributes_get() {
		static::check_fatal_error( static::$data['params']['genreId'], 'ジャンルIDが未設定です' );
		$url = static::$settings['endpoint'] . '2.0/navigation/genres/' . static::$data['params']['genreId'] . '/attributes/';

		// 商品属性IDがあれば追加する
		$url .= static::$data['params']['attributeId'] ?? '';

		$response = wp_remote_get( $url, array( 'headers' => static::$data['header'] ) );
		return $response;
	}
}
