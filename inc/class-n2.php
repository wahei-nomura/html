<?php
/**
 * グローバル変数 $n2 オブジェクト
 *
 * @package neoneng
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * N2オブジェクト
 */
class N2 {
	/**
	 * 現在のモード
	 *
	 * @var string develop or production.
	 */
	public $mode;

	/**
	 * キャッシュバスター（本番ではテーマバージョン）
	 *
	 * @var string
	 */
	public $cash_buster;

	/**
	 * サイトID
	 *
	 * @var int
	 */
	public $site_id;

	/**
	 * 自治体名
	 *
	 * @var string
	 */
	public $town;

	/**
	 * ユーザー情報
	 *
	 * @var array
	 */
	public $current_user;

	/**
	 * 送料設定
	 *
	 * @var array
	 */
	public $postage;

	/**
	 * 寄附金額計算式
	 *
	 * @var array
	 */
	public $formula;

	/**
	 * 機種依存文字変換
	 *
	 * @var array
	 */
	public $special_str_convert;

	/**
	 * 楽天市場
	 *
	 * @var array
	 */
	public $rakuten;

	/**
	 * ふるさとチョイス
	 *
	 * @var array
	 */
	public $furusato_choice;

	/**
	 * カスタムフィールド
	 *
	 * @var array
	 */
	public $custom_fields;

	/**
	 * カスタムフィールド　Steasmhip専用
	 *
	 * @var array
	 */
	public $custom_fields_ss;

	/**
	 * 商品プリントアウト
	 *
	 * @var array
	 */
	public $product_list_print;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->set_vars();
		$this->set_filters();
		add_action( 'wp_ajax_n2_calculate_donation_amount', array( $this, 'calculate_donation_amount' ) );
	}

	/**
	 * プロパティのセット
	 */
	public function set_vars() {

		// wp_options保存値
		$n2_option = get_option( 'N2_Setupmenu' );

		// 現在のモード
		$this->mode = preg_match( '/localhost/', wp_parse_url( get_bloginfo( 'url' ), PHP_URL_HOST ) ) ? 'develop' : 'production';

		// キャッシュバスター
		$this->cash_buster = 'develop' === $this->mode ? time() : wp_get_theme()->get( 'Version' );

		// サイト基本情報
		$this->site_id = get_current_blog_id();
		$this->town    = get_bloginfo( 'name' );

		// ログインユーザーデータ
		$this->current_user = wp_get_current_user();
		// jsに渡す場合にログイン情報は怖いので消しとく
		unset( $this->current_user->data->user_login, $this->current_user->data->user_pass );

		// カスタムフィールド
		$this->custom_fields    = yaml_parse_file( get_theme_file_path( 'config/n2-fields.yml' ) );
		$this->custom_fields_ss = yaml_parse_file( get_theme_file_path( 'config/n2-ss-fields.yml' ) );

		// プリントアウト
		$this->product_list_print = yaml_parse_file( get_theme_file_path( 'config/n2-product-list-print.yml' ) );

		// 文字列変換
		$this->special_str_convert = yaml_parse_file( get_theme_file_path( 'config/n2-special-str-comvert.yml' ) );

		// 送料設定
		$this->postage = $n2_option['postage'];

		// 寄附金額計算式タイプ
		$formula_arr   = array(
			'(商品価格+送料)/0.3',
			'(商品価格)/0.3',
			'(商品価格+送料)/0.35',
			'1と2の大きい方',
		);
		$this->formula = $formula_arr[ $n2_option['formula_type'] ];

		// 楽天
		$this->rakuten = $n2_option['rakuten'];
	}

	/**
	 * 全プロパティへフィルターフック
	 */
	public function set_filters() {
		foreach ( get_object_vars( $this ) as $key => $value ) {
			$this->$key = apply_filters( "n2_vars_{$key}", $value );
		}
	}

	/**
	 * 寄附金額の計算
	 */
	public function calculate_donation_amount() {
		// タイプ・価格・送料
		$type    = $_GET['type'] ?? 0;
		$price   = $_GET['price'] ?? 0;
		$postage = $_GET['postage'] ?? 0;

		// エヴァの出撃準備
		$eva = array(
			'零号機'  => ceil( ( $price + $postage ) / 300 ) * 1000,
			'初号機'  => ceil( $price / 300 ) * 1000,
			'弐号機'  => ceil( ( $price + $postage ) / 350 ) * 1000,
			'十三号機' => 9999999,
		);
		// 使徒襲来！　初号機と弐号機の強いほうが出撃だ！
		$eva['使徒'] = $eva['初号機'] > $eva['弐号機'] ? $eva['初号機'] : $eva['弐号機'];
		echo apply_filters( 'n2_calculate_donation_amount', $eva[ $type ], compact( 'price', 'postage', 'eva' ) );
		exit;
	}

	/**
	 * IPアドレス
	 */
	public function get_ss_ip_address() {
		return array(
			'219.111.49.195', // 波佐見
			'121.2.77.80', // 吉野ヶ里
			'202.241.189.211', // 糸島
			'219.111.24.202', // 有田
			'122.103.81.78', // 出島
			'183.177.128.173', // 土岐
			'217.178.116.13', // 大村
			'175.41.201.54', // SSVPN
		);
	}
}
