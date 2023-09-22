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
	 * 納期情報を取得
	 * 
	 * @return object
	 */
	public static function delvdate_master_get() {
		$url = static::$settings['endpoint'] . '/1.0/shop/delvdateMaster';
		$data    = wp_remote_get( $url, array( 'headers' => static::$data['header'] ) );
		$result = (array) simplexml_load_string( $data['body'] )->result->delvdateMasterList;
		$master = $result['delvdateMaster'];
		return $master;
	}
}
