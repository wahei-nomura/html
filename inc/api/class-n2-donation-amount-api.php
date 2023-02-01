<?php
/**
 * class-n2-donation-amount-api.php
 * 寄附金額の計算のためのAPI（jsでもPHPでも）
 *
 * @package neoneng
 */

if ( class_exists( 'N2_Donation_Amount_API' ) ) {
	new N2_Donation_Amount_API();
	return;
}

/**
 * 寄附金額の計算のためのAPI
 */
class N2_Donation_Amount_API {

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		add_action( 'wp_ajax_n2_donation_amount_api', array( $this, 'calc' ) );
		add_action( 'wp_ajax_nopriv_n2_donation_amount_api', array( $this, 'calc' ) );
	}

	/**
	 * 寄附金額の計算のためのAPI
	 *
	 * @param array|string $args パラメータ
	 */
	public static function calc( $args ) {
		global $n2;
		$args = $args ? wp_parse_args( $args ) : $_GET;
		// タイプ・価格・送料
		$type         = $n2->formula_type ?? '初号機';
		$price        = $args['price'] ?? 0;
		$delivery_fee = $args['delivery_fee'] ?? 0;
		$action       = $args['action'] ?? false;

		// エヴァの出撃準備
		$eva = array(
			'零号機'  => ceil( ( $price + $delivery_fee ) / 300 ) * 1000,
			'初号機'  => ceil( $price / 300 ) * 1000,
			'弐号機'  => ceil( ( $price + $delivery_fee ) / 350 ) * 1000,
			'十三号機' => 9999999,
		);
		// 使徒襲来！　初号機と弐号機の強いほうが出撃だ！
		$eva['使徒'] = $eva['初号機'] > $eva['弐号機'] ? $eva['初号機'] : $eva['弐号機'];

		/**
		 * Filters the attached file based on the given ID.
		 *
		 * @since 2.1.0
		 *
		 * @param int $eva[ $type ] 寄附金額
		 * @param array compact( 'price', 'delivery_fee', 'eva' ) 寄附金額算出のための情報
		*/
		$donation_amount = apply_filters( 'n2_donation_amount_api', $eva[ $type ], compact( 'price', 'delivery_fee', 'eva' ) );

		// admin-ajax.phpアクセス時
		if ( $action ) {
			header( 'Content-Type: application/json; charset=utf-8' );
			echo wp_json_encode( $donation_amount );
			exit;
		}
		// N2_Donation_Amount_API::calc()呼び出し
		return $donation_amount;
	}
}
