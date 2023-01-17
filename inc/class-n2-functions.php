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
	 * 寄附金額計算式をそのままJSの構文として渡す
	 *
	 * @param string $lang php or js
	 * @param Array  $price_data 価格 送料
	 * @return string
	 */
	public static function kifu_auto_pattern( $lang, $price_data = null ) {

		if ( 'php' === $lang ) {
			list( $kakaku, $souryou ) = $price_data;
		}

		$pattern = array(
			'零号機' => 'php' === $lang ? ceil( ( $kakaku + $souryou ) / 300 ) * 1000 : 'Math.ceil((kakaku + souryou) / 300) * 1000',
			'初号機' => 'php' === $lang ? ceil( $kakaku / 300 ) * 1000 : 'Math.ceil(kakaku / 300) * 1000',
			'弐号機' => 'php' === $lang ? ceil( ( $kakaku + $souryou ) / 350 ) * 1000 : 'Math.ceil((kakaku + souryou) / 350) * 1000',
		);

		if ( 'php' === $lang ) {
			$pattern['使徒'] = $pattern['初号機'] > $pattern['弐号機'] ? $pattern['初号機'] : $pattern['弐号機'];
		} else {
			$pattern['使徒'] = "{$pattern['初号機']}>{$pattern['弐号機']}?{$pattern['初号機']}:{$pattern['弐号機']}";
		}

		$pattern_type = '初号機';
		$pattern_type = apply_filters( 'n2_setpost_change_kifu_pattern', $pattern_type );

		return $pattern[ $pattern_type ];
	}

	/**
	 * カスタムフィールド全取得
	 *
	 * @param Object $object 現在の投稿の詳細データ
	 * @return Array 全カスタムフィールド情報
	 */
	public static function get_all_meta( $object ) {

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
	public static function special_str_convert( $str ) {

		// 絵文字除去
		$str = preg_replace( '/([0-9|#][\x{20E3}])|[\x{00ae}|\x{00a9}|\x{203C}|\x{2047}|\x{2048}|\x{2049}|\x{3030}|\x{303D}|\x{2139}|\x{2122}|\x{3297}|\x{3299}][\x{FE00}-\x{FEFF}]?|[\x{2190}-\x{21FF}][\x{FE00}-\x{FEFF}]?|[\x{2300}-\x{23FF}][\x{FE00}-\x{FEFF}]?|[\x{2900}-\x{297F}][\x{FE00}-\x{FEFF}]?|[\x{2B00}-\x{2BF0}][\x{FE00}-\x{FEFF}]?|[\x{1F000}-\x{1F6FF}][\x{FE00}-\x{FEFF}]?/u', '', $str );

		// 文字の変換
		$conv = yaml_parse_file( get_theme_file_path() . '/config/n2-special-str-comvert.yml' );
		$str  = str_replace( array_keys( $conv ), array_values( $conv ), $str );

		// ダブルクォーテーションが3つ以上連続する場合は2つに
		$str = preg_replace( '/\"{3,}/', '""', $str );

		// 全角ニョロをwinニョロに統一
		$str = json_decode( str_replace( '\u301c', '\uff5e', json_encode( $str ) ) );

		// [K]半角カタカナ=>全角カタカナ、[r]全角英=>半角英、[n]全角数=>半角数、[V]濁点付きの文字を一文字に、[s]全角スペース=>半角スペース
		return trim( mb_convert_kana( htmlspecialchars_decode( $str ), 'KrnVs' ) );
	}

	/**
	 * 管理画面、ページ指定、ユーザー権限指定判定
	 *
	 * @param string $page $pegenow
	 * @param string $type $post_type
	 * @param string $user current_user_can
	 * @return boolean
	 */
	public static function admin_param_judge( $page, $type = 'post', $user = 'ss_crew' ) {
		global $pagenow, $post_type;
		return ! is_admin() || ! current_user_can( $user ) || $page !== $pagenow || $type !== $post_type;
	}
	/**
	 * get_template_partのwrapper
	 *
	 * @param string $slug The slug name for the generic template.
	 * @param string $name The name of the specialised template.
	 * @param array  $args Optional. Additional arguments passed to the template.
	 *               	   Default empty array.
	 * @return void|false Void on success, false if the template does not exist.
	 */
	public static function get_template_part_with_args( $slug, $name, $args ) {
		if ( isset( $args ) && $args ) { // $argsが無ければ何もしない
			get_template_part( $slug, $name, $args );
		}
		return false;
	}

	/**
	 * download_csv
	 *
	 * @param string $name データ名
	 * @param Array  $header header
	 * @param Array  $items_arr 商品情報配列
	 * @param string $csv_title あれば連結する
	 * @return void
	 */
	public static function download_csv( $name, $header, $items_arr, $csv_title = '' ) {
		$csv  = $csv_title . PHP_EOL;
		$csv .= implode( ',', $header ) . PHP_EOL;

		// CSV文字列生成
		foreach ( $items_arr as $item ) {
			foreach ( $header as $head ) {
				$csv .= '"' . $item[ $head ] . '",';
			}
			$csv  = rtrim( $csv, ',' );
			$csv .= PHP_EOL;
		}

		// sjisに変換
		$csv = mb_convert_encoding( $csv, 'SJIS-win', 'utf-8' );

		header( 'Content-Type: application/octet-stream' );
		header( "Content-Disposition: attachment; filename={$name}.csv" );
		echo htmlspecialchars_decode( $csv );

		die();
	}
}
