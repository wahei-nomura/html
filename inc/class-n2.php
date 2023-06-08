<?php
/**
 * グローバル変数 $n2 オブジェクト
 *
 * @package neoneng
 */

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
	 * SSのIP
	 *
	 * @var array
	 */
	public $ss_ip_address;

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
	 * 寄附金額計算に必要な情報
	 *
	 * @var array
	 */
	public $formula;

	/**
	 * 文字変換
	 *
	 * @var array
	 */
	public $special_str_convert;

	/**
	 * アレルゲン文字列変換
	 *
	 * @var array
	 */
	public $allergen_convert;

	/**
	 * ポータル設定
	 *
	 * @var array
	 */
	public $portal_setting;

	/**
	 * ポータル共通説明文
	 *
	 * @var array
	 */
	public $portal_common_description;

	/**
	 * 出品ポータル
	 *
	 * @var array
	 */
	public $portal_sites;

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
	 * N2稼働状況
	 *
	 * @var object
	 */
	public $n2_active_flag;

	/**
	 * エクスポート機能
	 *
	 * @var object
	 */
	public $export;

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
					// ====== 新規登録時の初期化 ======
					if ( empty( $value ) ) {
						$user_meta = $this->current_user->data->meta;
						$value     = match ( $name ) {
							'商品タイプ' => array_keys( array_filter( $user_meta['商品タイプ'] ?? array(), fn( $v ) => 'true' === $v ) ),
							'寄附金額固定' => array(),
							default => '',
						};
					}
					// ====== 特殊フィールド系 ======
					if ( '楽天SPAカテゴリー' === $name && is_string( $value ) ) {
						$value = array(
							'text' => $value,
							'list' => array(),
						);
					}
					$this->custom_field[ $id ][ $name ]['value'] = $value;
				}
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
		$n2_settings = get_option( 'n2_settings', yaml_parse_file( get_theme_file_path( 'config/n2-settings.yml' ) ) );

		// SSのIP
		$this->ss_ip_address = yaml_parse_file( get_theme_file_path( 'config/ss-ip-address.yml' ) );

		// 現在のモード
		$this->mode = preg_match( '/localhost/', wp_parse_url( get_bloginfo( 'url' ), PHP_URL_HOST ) ) ? 'develop' : 'production';

		// キャッシュバスター
		// $this->cash_buster = 'develop' === $this->mode ? time() : wp_get_theme()->get( 'Version' );
		$this->cash_buster = time();

		// N2稼働状況
		$this->n2_active_flag = $n2_settings['n2']['active'];

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

		// プリントアウト
		$this->product_list_print = yaml_parse_file( get_theme_file_path( 'config/n2-product-list-print.yml' ) );

		// 文字列変換
		$this->special_str_convert = yaml_parse_file( get_theme_file_path( 'config/special-str-convert.yml' ) );

		// 文字列変換
		$this->allergen_convert = yaml_parse_file( get_theme_file_path( 'config/allergen-convert.yml' ) );

		// 送料設定
		$n2_settings['delivery_fee'] = $n2_settings['delivery_fee'] ?? array();
		$this->delivery_fee          = empty( array_filter( array_values( $n2_settings['delivery_fee'] ) ) ) ? false : $n2_settings['delivery_fee'];// 空の値が有る場合はfalseに;
		if ( $this->delivery_fee ) {
			// クールの自動加算
			$this->delivery_fee['0101_cool'] = (string) ( (int) $this->delivery_fee['0101'] + 220 );
			$this->delivery_fee['0102_cool'] = (string) ( (int) $this->delivery_fee['0102'] + 220 );
			$this->delivery_fee['0103_cool'] = (string) ( (int) $this->delivery_fee['0103'] + 330 );
			$this->delivery_fee['0104_cool'] = (string) ( (int) $this->delivery_fee['0104'] + 660 );
		}

		// 寄附金額計算式タイプ
		$this->formula = empty( array_filter( array_values( $n2_settings['formula'] ) ) ) ? false : $n2_settings['formula'];// 空の値が有る場合はfalseに;

		// ポータル共通説明文
		$this->portal_common_description = $n2_settings['portal_common_description'] ?? '';

		// 出品ポータル
		$this->portal_sites = $n2_settings['n2']['portal_sites'];

		// ポータル設定
		$this->portal_setting = array_merge_recursive(
			$n2_settings['portal_setting'] ?? array(),
			apply_filters(
				'n2_portal_setting',
				yaml_parse_file( get_theme_file_path( 'config/n2-portal-setting.yml' ) )
			),
		);

		// カスタムフィールド
		$this->custom_field = yaml_parse_file( get_theme_file_path( 'config/custom-field.yml' ) );
		// 出品しないポータルの場合はカスタムフィールドを削除
		foreach ( $this->custom_field as $key => $custom_field ) {
			foreach ( $custom_field as $name => $value ) {
				if ( isset( $value['portal'] ) && ! in_array( $value['portal'], $this->portal_sites, true ) ) {
					unset( $this->custom_field[ $key ][ $name ] );
				}
			}
		}
		// 出品禁止ポータルから削除
		foreach ( $this->custom_field['スチームシップ用']['出品禁止ポータル']['option'] as $index => $option ) {
			if ( ! in_array( $option, $this->portal_sites, true ) ) {
				unset( $this->custom_field['スチームシップ用']['出品禁止ポータル']['option'][ $index ] );
			}
		}

		// LHカテゴリー
		if ( ! empty( $this->portal_setting['LedgHOME']['カテゴリー'] ) ) {
			// LHカテゴリーの設定値を配列化
			$lh_category = $this->portal_setting['LedgHOME']['カテゴリー'];
			$lh_category = preg_replace( '/\r\n|\r|\n/', "\n", trim( $lh_category ) );
			$lh_category = explode( "\n", $lh_category );
			// portal_setting
			$this->portal_setting['LedgHOME']['カテゴリー'] = $lh_category;
			// option設定
			$this->custom_field['事業者用']['LHカテゴリー']['option'] = $lh_category;
		}
	}

	/**
	 * 全プロパティへフィルターフック
	 */
	public function set_filters() {
		foreach ( get_object_vars( $this ) as $key => $value ) {
			$this->$key = apply_filters( "n2_vars_{$key}", $value );
		}
	}

}
