<?php
/**
 * RMS ITEM API
 * /wp-admin/admin-ajax.php?action=n2_rms_item_api_ajax&mode=debug&call=
 *
 * @package neoneng
 */

if ( class_exists( 'N2_RMS_Item_API' ) ) {
	new N2_RMS_Item_API();
	return;
}

/**
 * N2からCABINETへ送信したりするAPI
 */
class N2_RMS_Item_API extends N2_RMS_Base_API {

	/**
	 * この機能を利用すると、商品管理番号を指定し、商品情報の部分更新をすることができます。
	 * リクエストに含まれない項目は更新対象にならないため、指定した項目のみ更新されます。
	 *
	 * @param string $manageNumber manageNumber
	 */
	public static function items_patch( $manageNumber, $body ) {
		static::check_fatal_error(
			! preg_match( '/^[a-Z0-9\-\_]+$/', $manageNumber ),
			'商品管理番号は英数字と「-」「_」のみ使用可能です'
		);
		$url = static::$settings['endpoint'] . '/2.0/items/manage-numbers/' . $manageNumber;
		return static::request(
			$url,
			array(
				'method'  => 'PATCH',
				'body'    => wp_unslash( $body ),
				'headers' => array(
					'Content-Type' => 'application/json',
				),
			),
		);
	}
}
