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
	 * ブログプレフィックス
	 *
	 * @var string
	 */
	public $blog_prefix;

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
	 * ajaxurl
	 *
	 * @var string
	 */
	public $ajaxurl;

	/**
	 * Cookie
	 *
	 * @var array
	 */
	public $cookie;

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
	public $delivery_fee;

	/**
	 * 寄附金額計算式タイプ
	 *
	 * @var array
	 */
	public $formula_type;

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
	public $custom_field;

	/**
	 * 商品プリントアウト
	 *
	 * @var array
	 */
	public $product_list_print;

	/**
	 * query（ログイン時のみ）
	 *
	 * @var object
	 */
	public $query;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->set_vars();
		$this->set_filters();
		add_action( 'pre_get_posts', array( $this, 'add_post_data' ) );
	}

	/**
	 * 投稿データをセット
	 *
	 * @params object $query クエリ
	 */
	public function add_post_data( $query ) {
		// クエリ生データをセット
		if ( is_user_logged_in() ) {
			$this->query = $query;
		}
		// カスタムフィールドに値をセット
		global $post;
		if ( isset( $post->ID ) ) {
			foreach ( $this->custom_field as $id => $arr ) {
				foreach ( $arr as $name => $value ) {
					$value = get_post_meta( $post->ID, $name, true );
					switch ( $name ) {
						case '出品禁止ポータル':
						case '取り扱い方法':
						case '商品画像':
							$value = $value ?: array();
							break;
						case '発送方法':
							$value = $value ?: '常温';
							break;
						case '商品タイプ':
							$value = $value ?: array( $this->current_user->data->meta['食品取り扱い'] == '有' ? '食品' : '' );
							break;
					}

					$this->custom_field[ $id ][ $name ]['value'] = $value;
				}
			}
			if ( ! isset( $this->custom_field['スチームシップ用']['寄附金額固定'] ) ) {
				$this->custom_field['スチームシップ用']['寄附金額固定']['value'] = array();
			}
		}
	}

	/**
	 * プロパティのセット
	 */
	public function set_vars() {
		global $pagenow, $wpdb;
		// wp_options保存値
		$n2_option = get_option( 'N2_Setupmenu' );

		// 現在のモード
		$this->mode = preg_match( '/localhost/', wp_parse_url( get_bloginfo( 'url' ), PHP_URL_HOST ) ) ? 'develop' : 'production';

		// キャッシュバスター
		$this->cash_buster = 'develop' === $this->mode ? time() : wp_get_theme()->get( 'Version' );

		// サイト基本情報

		$this->blog_prefix = $wpdb->get_blog_prefix();
		$this->site_id     = get_current_blog_id();
		$this->town        = get_bloginfo( 'name' );
		$this->ajaxurl     = admin_url( 'admin-ajax.php' );
		$this->cookie      = $_COOKIE;

		// ログインユーザーデータ
		$this->current_user = wp_get_current_user();
		// ユーザーメタ全取得
		$user_meta = get_user_meta( $this->current_user->ID );
		// 値が無駄に配列になるのを避ける
		foreach ( $user_meta as $key => $val ) {
			$user_meta[ $key ] = get_user_meta( $this->current_user->ID, $key, true );
		}

		// ユーザーメタ追加
		$this->current_user->__set( 'meta', $user_meta );

		// カスタムフィールド
		$this->custom_field = array(
			'スチームシップ用' => yaml_parse_file( get_theme_file_path( 'config/n2-ss-fields.yml' ) ),
			'事業者用'     => yaml_parse_file( get_theme_file_path( 'config/n2-fields.yml' ) ),
		);

		// プリントアウト
		$this->product_list_print = yaml_parse_file( get_theme_file_path( 'config/n2-product-list-print.yml' ) );

		// 文字列変換
		$this->special_str_convert = yaml_parse_file( get_theme_file_path( 'config/n2-special-str-comvert.yml' ) );

		// 送料設定
		$this->delivery_fee = $n2_option['delivery_fee'] ?? yaml_parse_file( get_theme_file_path( 'config/n2-delivery-fee.yml' ) );

		// 寄附金額計算式タイプ
		$this->formula_type = $n2_option['formula_type'] ?? '';

		// ポータル一覧
		$this->portals   = array(
			'rakuten'         => '楽天',
			'furusato_choice' => 'チョイス',
		);
		$this->town_code = $this->get_portal_town_code_list();

		// 楽天
		$this->rakuten = $n2_option['rakuten'] ?? array();
		// ftp_server,upload_serverを追加
		$this->rakuten = array( ...$this->rakuten, ...yaml_parse_file( get_theme_file_path( 'config/n2-rakuten-common.yml' ) ) );

		// チョイス
		$this->furusato_choice = $n2_option['furusato_choice'] ?? array();
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

	/**
	 * 自治体コードを一つの配列にまとめる
	 *
	 * @return array
	 */
	public function get_portal_town_code_list() {
		$town_code_list = array();
		// ポータル一覧
		$town_code = yaml_parse_file( get_theme_file_path( 'config/n2-towncode.yml' ) );
		$municipal = explode( '/', get_option( 'home' ) );
		$town_name = end( $municipal );
		return array(
			'rakuten'         => $n2_option['rakuten']['town_code'] ?? $town_code[ $town_name ]['楽天'] ?? '',
			'furusato_choice' => $n2_option['furusato_choice']['town_code'] ?? $this->town,
		);
	}
}
