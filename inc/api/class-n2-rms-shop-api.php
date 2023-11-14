<?php
/**
 * RMS SHOP API
 * 
 * debug
 * /wp-admin/admin-ajax.php?action=n2_rms_shop_api_ajax&mode=debug&request=
 *
 * @package neoneng
 */

if ( class_exists( 'N2_RMS_Shop_API' ) ) {
	new N2_RMS_Shop_API();
	return;
}

/**
 * Shop API
 */
class N2_RMS_Shop_API extends N2_RMS_Base_API {

	/**
	 * 店舗詳細情報を取得
	 *
	 * @return object
	 */
	public static function shop_master_get() {
		$url  = static::$settings['endpoint'] . '/1.0/shop/shopMaster';
		$data = wp_remote_get( $url, array( 'headers' => static::$data['header'] ) );
		if ( is_wp_error( $data ) ) {
			return array();
		}
		$result = (array) simplexml_load_string( $data['body'] )->result->shopMaster;
		return $result;
	}

	/**
	 * 納期情報を取得
	 *
	 * @return object
	 */
	public static function delvdate_master_get() {
		$url    = static::$settings['endpoint'] . '/1.0/shop/delvdateMaster';
		$data   = wp_remote_get( $url, array( 'headers' => static::$data['header'] ) );
		$result = (array) simplexml_load_string( $data['body'] )->result->delvdateMasterList;
		$master = $result['delvdateMaster'];
		usort( $master, fn( $a, $b ) => (int) $a->delvdateNumber > (int) $b->delvdateNumber ? 1 : -1 );
		return $master;
	}
}
