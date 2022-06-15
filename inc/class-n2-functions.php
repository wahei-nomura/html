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

	/**
	 * 文字列をルール通りにする
	 *
	 * @param string $str きれいにしたい文字列
	 * @return string $str
	 */
	public function _s( $str ) {

		// 絵文字除去
		$str = preg_replace( '/([0-9|#][\x{20E3}])|[\x{00ae}|\x{00a9}|\x{203C}|\x{2047}|\x{2048}|\x{2049}|\x{3030}|\x{303D}|\x{2139}|\x{2122}|\x{3297}|\x{3299}][\x{FE00}-\x{FEFF}]?|[\x{2190}-\x{21FF}][\x{FE00}-\x{FEFF}]?|[\x{2300}-\x{23FF}][\x{FE00}-\x{FEFF}]?|[\x{2900}-\x{297F}][\x{FE00}-\x{FEFF}]?|[\x{2B00}-\x{2BF0}][\x{FE00}-\x{FEFF}]?|[\x{1F000}-\x{1F6FF}][\x{FE00}-\x{FEFF}]?/u', '', $str );

		// 文字の変換
		$conv = array(
			'"' => '""',
			'㎝' => 'cm',
			'㎜' => 'mm',
			'㎏' => 'kg',
			'㏄' => 'cc',
			'㎖' => 'ml',
			'ℓ' => 'l',
			'!' => '！',
			'?' => '？',
			'<' => '＜',
			'>' => '＞',
			'(' => '（',
			')' => '）',
			':' => '：',
			'~' => '～', // winニョロ
		);
		$str  = str_replace( array_keys( $conv ), array_values( $conv ), $str );

		// ダブルクォーテーションが3つ以上連続する場合は2つに
		$str = preg_replace( '/\"{3,}/', '""', $str );

		// 全角ニョロをwinニョロに統一
		$str = json_decode( str_replace( '\u301c', '\uff5e', json_encode( $str ) ) );

		// [K]半角カタカナ=>全角カタカナ、[r]全角英=>半角英、[n]全角数=>半角数、[V]濁点付きの文字を一文字に、[s]全角スペース=>半角スペース
		return trim( mb_convert_kana( htmlspecialchars_decode( $str ), 'KrnVs' ) );
	}
}
