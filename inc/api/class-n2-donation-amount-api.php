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
		add_action( 'wp_ajax_n2_adjust_price_api', array( $this, 'adjust_price' ) );
		add_action( 'wp_ajax_nopriv_n2_adjust_price_api', array( $this, 'adjust_price' ) );
	}

	/**
	 * 寄附金額の計算のためのAPI
	 *
	 * @param array|string $args パラメータ
	 */
	public static function calc( $args ) {
		global $n2;
		$args    = $args ? wp_parse_args( $args ) : $_GET;
		$default = array(
			'price'               => 0,
			'subscription'        => 1,
			'delivery_fee'        => 0,
			'delivery_multiplier' => (int) $n2->settings['寄附金額・送料']['送料乗数'],
			'delivery_add_point'  => (int) $n2->settings['寄附金額・送料']['送料加算分岐点'],
			'divisor'             => (float) $n2->settings['寄附金額・送料']['除数'],
			'min_donation'        => (int) $n2->settings['寄附金額・送料']['下限寄附金額'],
			'action'              => false,
		);
		$args    = wp_parse_args( array_filter( $args ), $default );

		// ○○円未満は加算
		$args['delivery_multiplier'] = (int) ( $args['price'] < $args['delivery_add_point'] || 0 === $args['delivery_add_point'] ) * $args['delivery_multiplier'];
		$args['delivery_fee']        = $args['delivery_fee'] * $args['delivery_multiplier'];

		/**
		 * [hook] n2_donation_amount_api_args
		 *
		 * @param array $args 寄附金額計算素材
		*/
		$args = apply_filters( 'n2_donation_amount_api_args', $args );

		// 最大値を選択
		$donation_amount = max(
			// 下限寄附金額（3割ルール）
			ceil( $args['price'] * $args['subscription'] / 300 ) * 1000,
			// 下限寄附金額（N2設定値）
			$args['min_donation'],
			// 寄附金額
			ceil( ( $args['price'] + $args['delivery_fee'] ) * $args['subscription'] / ( $args['divisor'] * 1000 ) ) * 1000
		);

		// admin-ajax.phpアクセス時
		if ( $args['action'] ) {
			header( 'Content-Type: application/json; charset=utf-8' );
			echo wp_json_encode( $donation_amount );
			exit;
		}
		// N2_Donation_Amount_API::calc()呼び出し
		return $donation_amount;
	}

	/**
	 * 返礼率生成・しきい値チェック
	 *
	 * @param string  $meta 投稿データ
	 * @param boolean $threshold_flg 出力切り替えフラグ
	 */
	public static function calc_return_rate( $meta, $threshold_flg = false ) {
		global $n2;
		if ( 0 === (int) $meta['定期便'] * (int) ( $meta['寄附金額'] ?? 0 ) * (int) $meta['価格'] ) {
			return '-';
		}
		return ceil( $meta['価格'] * $meta['定期便'] / $meta['寄附金額'] * 1000 ) / 10;
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
		// 送料のキーを生成
		$delivery_code = $this->create_delivery_code(
			get_post_meta( $post_id, '発送サイズ', true ),
			get_post_meta( $post_id, '発送方法', true )
		);
		// 新送料
		$calc_delivery_fee = $n2->settings['寄附金額・送料']['送料'][ $delivery_code ] ?? false;
		// 旧送料
		$delivery_fee = get_post_meta( $post_id, '送料', true );
		// 新旧一致、または不明の場合は何もしない
		if ( (int) $delivery_fee === (int) $calc_delivery_fee || ! $calc_delivery_fee ) {
			return;
		}
		// 送料の更新
		update_post_meta( $post_id, '送料', $calc_delivery_fee );
		$title = get_the_title( $post_id );
		$code  = get_post_meta( $post_id, '返礼品コード', true );
		echo "「{$code} - {$title}」の送料を「{$delivery_fee} → {$calc_delivery_fee}」に更新。\n";
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

	/**
	 * 自動価格調整
	 *
	 * @param array|string $args 必要なデータ
	 * N2設定値を使いたくない場合は、独自にGETパラメータで渡す
	 *
	 * @return int $price 価格
	 */
	public static function adjust_price( $args ) {
		global $n2;
		$args    = $args ? wp_parse_args( $args ) : $_GET;
		$default = array(
			'adjust_type'         => $n2->settings['寄附金額・送料']['自動価格調整'],
			'price'               => 0,
			'subscription'        => 1,
			'divisor'             => min( $n2->settings['寄附金額・送料']['除数'], 0.3 ), // 0.3より大きい場合は0.3を利用
			'step'                => min( $n2->settings['寄附金額・送料']['除数'], 0.3 ) * 1000,
			'min_donation'        => $n2->settings['寄附金額・送料']['下限寄附金額'],
			'delivery_multiplier' => (int) $n2->settings['寄附金額・送料']['送料乗数'],
			'delivery_add_point'  => (int) $n2->settings['寄附金額・送料']['送料加算分岐点'],
			'action'              => false,
		);

		$args = wp_parse_args( $args, $default );

		// 送料乗数が1で送料加算分岐点以下は調整不要のフラグ追加
		$args['quit'] = (int) ( $args['price'] < $args['delivery_add_point'] || 0 === $args['delivery_add_point'] );
		$args['quit'] = (bool) ( $args['quit'] * $args['delivery_multiplier'] );

		// 自動価格調整
		if ( ! $args['quit'] ) {
			// 総額調整用の最小公倍数
			$lcm = (int) gmp_lcm( $args['step'], $args['subscription'] );
			// 価格の調整
			$args['price'] = match ( $args['adjust_type'] ) {
				1, '1回毎に調整する' => ceil( $args['price'] / $args['step'] ) * $args['step'],
				2, '総額で調整する' => ( ceil( ( $args['price'] * $args['subscription'] ) / $lcm ) * $lcm ) / $args['subscription'],
				default => $args['price'],
			};

			// この価格で下限寄付額を下回らないか調査
			if ( ( $args['price'] / $args['divisor'] ) < (int) $args['min_donation'] ) {
				$args['price'] = (int) $args['min_donation'] * $args['divisor'];
			}
		}

		// admin-ajax.phpアクセス時
		if ( $args['action'] ) {
			header( 'Content-Type: application/json; charset=utf-8' );
			echo wp_json_encode( (int) $args['price'] );
			exit;
		}
		// N2_Donation_Amount_API::adjust_price()呼び出し
		return (int) $args['price'];
	}
}
