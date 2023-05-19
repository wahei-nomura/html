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
	 * ポータル共通説明文
	 *
	 * @var array
	 */
	public $portal_common_discription;

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
	public $choice;

	/**
	 * レジホーム
	 *
	 * @var array
	 */
	public $ledghome;

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
	 * ポータル一覧
	 *
	 * @var object
	 */
	public $portals;

	/**
	 * ポータル毎の自治体コード
	 *
	 * @var object
	 */
	public $town_code;

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
	 * @param object $query クエリ
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
				foreach ( $arr as $name => $v ) {
					$value = $v['value'] ?? '';
					$value = get_post_meta( $post->ID, $name, true ) ?: $value;
					if ( '商品タイプ' === $name ) {
						if ( empty( $value ) ) {
							$user_meta = $this->current_user->data->meta;
							if ( ! empty( $user_meta['商品タイプ'] ) ) {
								$value = array_keys( array_filter( $user_meta['商品タイプ'], fn( $v ) => 'true' === $v ) );
							}
						}
					}
					$this->custom_field[ $id ][ $name ]['value'] = $value;
				}
			}
			// 寄附金額固定の初期化
			if ( ! isset( $this->custom_field['スチームシップ用']['寄附金額固定'] ) ) {
				$this->custom_field['スチームシップ用']['寄附金額固定']['value'] = array();
			}
			/**
			 * カスタムフィールドの値の変更
			 */
			$this->custom_field = apply_filters( 'n2_after_update_custom_field_value', $this->custom_field );
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
		$user_meta = (array) get_user_meta( $this->current_user->ID );
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
		// クールの自動加算
		$this->delivery_fee['0101_cool'] = (string) ( $this->delivery_fee['0101'] + 220 );
		$this->delivery_fee['0102_cool'] = (string) ( $this->delivery_fee['0102'] + 220 );
		$this->delivery_fee['0103_cool'] = (string) ( $this->delivery_fee['0103'] + 330 );
		$this->delivery_fee['0104_cool'] = (string) ( $this->delivery_fee['0104'] + 660 );

		// 寄附金額計算式タイプ
		$this->formula_type = $n2_option['formula_type'] ?? '';

		// ポータル一覧
		$this->portals   = array(
			'rakuten' => '楽天',
			'choice'  => 'チョイス',
		);
		$this->town_code = $this->get_portal_town_code_list();

		// ポータル共通説明文
		$this->portal_common_discription = $n2_option['add_text'][ get_bloginfo( 'name' ) ] ?? '';

		// 楽天
		$rakuten_common_yml = yaml_parse_file( get_theme_file_path( 'config/n2-rakuten-common.yml' ) );
		$this->rakuten      = $n2_option['rakuten'] ?? array();
		$this->rakuten      = array( ...$rakuten_common_yml, ...$this->rakuten );

		// チョイス
		$choice_yml   = yaml_parse_file( get_theme_file_path( 'config/n2-choice-tsv-header.yml' ) )['choice'];
		$this->choice = $n2_option['choice'] ?? array();
		$this->choice = array( ...$choice_yml, ...$this->choice );

		// レジホーム
		$this->ledghome['csv_header'] = apply_filters( 'n2_item_export_csv_header', yaml_parse_file( get_theme_file_path( 'config/n2-ledghome-csv-header.yml' ) ) );
		$this->ledghome['レターパック送料反映'] = '反映する';// get_optionで取るべき
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
			'rakuten' => $this->rakuten['town_code'] ?? $town_code[ $town_name ]['楽天'] ?? '',
			'choice'  => $this->choice['town_code'] ?? $this->town,
		);
	}
}
