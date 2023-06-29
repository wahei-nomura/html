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
	 * 返礼率生成
	 *
	 * @param string $post_data 投稿データ
	 */
	public static function calc_return_rate( $post_data ) {
		global $n2;
		$delivery_multiplier = $n2->formula['送料乗数'];// 0 or 1
		$flg = '設定しない' === $post_data['発送サイズ'] ? 1 : 0;
		$delivery_size       = ! empty( $post_data['発送サイズ'] ) ? '常温' !== $post_data['発送方法'] ? $post_data['発送サイズ'] . '_cool' : $post_data['発送サイズ'] : '-';
		$delivery_fee        = ( ! empty( $post_data['発送サイズ'] ) && '設定しない' !== $post_data['発送サイズ'] ) ? $n2->delivery_fee[ $delivery_size ] : 0;
		$teiki_no            = ! empty( $post_data['定期便'] ) && 1 !== (int) $post_data['定期便'] ? $post_data['定期便'] : 1;
		$return_rate         = ! empty( $post_data['寄附金額'] && $post_data['価格'] ) ? '1' === $delivery_multiplier ? ceil( ( $post_data['価格'] / ( $post_data['寄附金額'] + $delivery_fee / $teiki_no ) ) * 100 ) / 100 : ceil( ( $post_data['価格'] / ( $post_data['寄附金額'] / $teiki_no ) ) * 100 ) / 100 : '-';
		// N2_Donation_Amount_API::calc_return_rate()呼び出し
		return $return_rate;
	}

	/**
	 * 寄附金額一括自動計算
	 */
	public function update_all_donation_amount() {
		echo '<pre>';
		ob_start();
		foreach ( get_posts( 'post_status=any&numberposts=-1' ) as $post ) {
			// 送料をアップデート
			$this->update_dellivery_fee( $post->ID );
			// 寄附金額固定の場合はここでループ抜ける
			$fixed = array_filter( get_post_meta( $post->ID, '寄附金額固定', true ) ?: array() );
			if ( ! empty( $fixed ) ) {
				continue;
			}
			// 寄附金額計算の素材集め
			$price           = get_post_meta( $post->ID, '価格', true );
			$delivery_fee    = get_post_meta( $post->ID, '送料', true );
			$subscription    = get_post_meta( $post->ID, '定期便', true );
			$donation_amount = get_post_meta( $post->ID, '寄附金額', true );
			// 自動計算
			$calc_donation_amount = $this->calc( compact( 'price', 'delivery_fee', 'subscription' ) );
			// 新旧一致の場合は何もしない
			if ( (int) $donation_amount === (int) $calc_donation_amount ) {
				continue;
			}
			update_post_meta( $post->ID, '寄附金額', $calc_donation_amount );
			echo "「{$post->post_title}」の寄附金額を「{$donation_amount} → {$calc_donation_amount}」に更新。\n";
		}
		echo ob_get_clean() ?: '更新する項目がありませんでした。';
		exit;
	}

	/**
	 * 送料未設定の場合の送料を設定
	 *
	 * @param int $post_id 投稿ID
	 */
	private function update_dellivery_fee( $post_id ) {
		global $n2;
		// $n2->delivery_feeのキーを生成
		$delivery_code = $this->create_delivery_code(
			get_post_meta( $post_id, '発送サイズ', true ),
			get_post_meta( $post_id, '発送方法', true )
		);
		// 新送料
		$calc_delivery_fee = $n2->delivery_fee[ $delivery_code ] ?? false;
		// 旧送料
		$delivery_fee = get_post_meta( $post_id, '送料', true );
		// 新旧一致、または不明の場合は何もしない
		if ( (int) $delivery_fee === (int) $calc_delivery_fee || ! $calc_delivery_fee ) {
			return;
		}
		// 送料の更新
		update_post_meta( $post_id, '送料', $calc_delivery_fee );
		$title = get_the_title( $post_id );
		echo "「{$title}」の送料を「{$delivery_fee} → {$calc_delivery_fee}」に更新。\n";
	}

	/**
	 * 発送サイズキー生成
	 *
	 * @param string $size 発送サイズ
	 * @param string $method 発送方法
	 *
	 * @return string $size 0101_coolなど
	 */
	public static function create_delivery_code( $size, $method ) {
		// 発送サイズを元に送料計算
		$delivery_code = array(
			$size,
			'常温' !== $method ? 'cool' : '',
		);
		$delivery_code = array_filter( $delivery_code );// 空削除
		$delivery_code = implode( '_', $delivery_code );// 0101_coolなど
		return $delivery_code;
	}

}
