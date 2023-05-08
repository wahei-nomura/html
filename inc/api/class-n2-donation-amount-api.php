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
		add_action( 'wp_ajax_n2_update_all_donation_amount', array( $this, 'update_all_donation_amount' ) );
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
		// 除数と送料乗数
		$divisor             = $n2->formula['除数'];// 0.3 0.35 0.4 など
		$delivery_multiplier = $n2->formula['送料乗数'];// 0 or 1
		// 価格・送料
		$price        = (int) ( $args['price'] ?? 0 );
		$delivery_fee = (int) ( $args['delivery_fee'] ?? 0 ) * $delivery_multiplier;
		$subscription = (int) ( $args['subscription'] ?? 1 );
		$action       = $args['action'] ?? false;

		// 下限寄附額（3割ルール）
		$min_donation_amount = ceil( $price * $subscription / 300 ) * 1000;
		// 寄附金額
		$donation_amount = ceil( ( $price + $delivery_fee ) * $subscription / ( $divisor * 1000 ) ) * 1000;
		// 下限寄附額（3割ルール）より高くなるように設定するので、下限寄附額と比較して高い方を選択
		$donation_amount = max( $min_donation_amount, $donation_amount );
		/**
		 * Filters the attached file based on the given ID.
		 *
		 * @since 2.1.0
		 *
		 * @param int $donation_amount 寄附金額
		 * @param array compact( 'price', 'delivery_fee', 'eva' ) 寄附金額算出のための情報
		*/
		$donation_amount = apply_filters( 'n2_donation_amount_api', $donation_amount, compact( 'price', 'delivery_fee', 'subscription' ) );

		// admin-ajax.phpアクセス時
		if ( $action ) {
			header( 'Content-Type: application/json; charset=utf-8' );
			echo wp_json_encode( $donation_amount );
			exit;
		}
		// N2_Donation_Amount_API::calc()呼び出し
		return $donation_amount;
	}

	/**
	 * 寄附金額一括自動計算
	 */
	public function update_all_donation_amount() {
		foreach ( get_posts( 'post_status=any&numberposts=-1' ) as $post ) {
			$fixed = array_filter( get_post_meta( $post->ID, '寄附金額固定', true ) ?: array() );
			if ( ! empty( $fixed ) ) {
				continue;
			}
			$price           = get_post_meta( $post->ID, '価格', true );
			$delivery_fee    = get_post_meta( $post->ID, '送料', true );
			$subscription    = get_post_meta( $post->ID, '定期便', true );
			$donation_amount = (int) get_post_meta( $post->ID, '寄附金額', true );
			// 自動計算
			$calc_donation_amount = (int) $this->calc( compact( 'price', 'delivery_fee', 'subscription' ) );
			if ( $donation_amount > 0 && $donation_amount !== $calc_donation_amount ) {
				update_post_meta( $post->ID, '寄附金額', $calc_donation_amount );
				echo "<pre>「{$post->post_title}」の寄附金額を更新</pre>";
			}
		}
		exit;
	}

	/**
	 * 
	 */
}
