<?php
/**
 * class-n2-functions.php
 * グローバルに使い回す関数を保管
 *
 * @package neoneng
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Functions
 */
class N2_Functions {

	/**
	 * カスタムフィールド全取得
	 *
	 * @param Object $object 現在の投稿の詳細データ
	 * @return Array 全カスタムフィールド情報
	 */
	public function get_all_meta( $object ) {

		$all = get_post_meta( $object->ID );
		foreach ( $all as $k => $v ) {
			if ( preg_match( '/^_/', $k ) ) {
				unset( $all[ $k ] );
				continue;
			}
			$all[ $k ] = get_post_meta( $object->ID, $k, true );
		}
		return $all;
	}
}
