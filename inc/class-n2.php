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
	 * アクセス中のIP
	 *
	 * @var array
	 */
	public $ip_address;

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
	 * 自治体名
	 *
	 * @var string
	 */
	public $logo;

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
	 * N2設定
	 *
	 * @var array
	 */
	public $settings;

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
	 * @param object $query クエリ
	 */
	public function add_post_data( $query ) {
		remove_action( 'pre_get_posts', array( $this, 'add_post_data' ) );// 無限ループ回避
		// クエリ生データをセット
		if ( is_user_logged_in() ) {
			$this->query = $query;
		}
		// カスタムフィールドに値をセット
		global $post, $pagenow;
		if ( isset( $post->ID ) && in_array( $pagenow, array( 'post.php', 'post-new.php' ), true ) ) {
			foreach ( $this->custom_field as $id => $arr ) {
				foreach ( $arr as $name => $v ) {
					$value = $v['value'] ?? '';
					$value = get_post_meta( $post->ID, $name, true ) ?: $value;
					// ====== 新規登録時の初期化 ======
					if ( empty( $value ) ) {
						$user_meta = $this->current_user->data->meta;
						$value     = match ( $name ) {
							'商品タイプ' => $user_meta['商品タイプ'] && 'post-new.php' === $pagenow
								? array( array_search( max( $user_meta['商品タイプ'] ), $user_meta['商品タイプ'], true ) )
								: array(),
							'寄附金額固定', '商品画像' => array(),
							default => 'checkbox' === ( $v['type'] ?? false ) ? array() : '',
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
			// 提供事業者名の自動取得
			if ( 'ledghome' === $this->settings['N2']['LedgHOME'] ) {
				$option = array();
				$args   = array(
					'author'       => $post->post_author,
					'post_status'  => 'any',
					'numberposts'  => -1,
					'fields'       => 'ids',
					'meta_key'     => '提供事業者名',
					'meta_value'   => null,
					'meta_compare' => '!=',
				);
				foreach ( get_posts( $args ) as $id ) {
					$option[] = get_post_meta( $id, '提供事業者名', true );
				}
				$this->custom_field['事業者用']['提供事業者名']['option'] = array_values( array_unique( array_filter( $option ) ) );
			}

			/**
			 * カスタムフィールドの値の変更
			 */
			$this->custom_field = apply_filters( 'n2_after_update_custom_field_value', $this->custom_field );
		}
	}

	/**
	 * 旧設定と互換
	 */
	private function compatible_settings() {
		if ( isset( $this->settings['n2'] ) ) {
			$default = yaml_parse_file( get_theme_file_path( 'config/n2-settings.yml' ) );
			// N2　＝＝＝＝＝＝＝＝＝＝＝＝＝＝
			{
				$this->settings['N2'] = $default['N2'];
				$pair = array(
					'active'       => '稼働中',
					'portal_sites' => '出品ポータル',
				);
				foreach ( $pair as $old => $new ) {
					$this->settings['N2'][ $new ] = $this->settings['n2'][ $old ];
				}
				// 楽天ふるさと納税を楽天に変換
				$this->settings['N2']['出品ポータル'] = array_map(
					fn( $v ) => str_replace( 'ふるさと納税', '', $v ),
					$this->settings['N2']['出品ポータル']
				);
			}
			// 寄附金額・送料　＝＝＝＝＝＝＝＝＝＝＝＝＝＝
			{
				$this->settings['寄附金額・送料']['送料'] = $this->settings['delivery_fee'];
				$this->settings['寄附金額・送料'] = array(
					...$this->settings['寄附金額・送料'],
					...$this->settings['formula'],
				);
			}
			// LedgHOME　＝＝＝＝＝＝＝＝＝＝＝＝＝＝
			{
				$this->settings['LedgHOME'] = $default['LedgHOME'];
				// LedgHOMEカテゴリー
				$this->settings['LedgHOME']['カテゴリー'] = $this->settings['portal_setting']['LedgHOME']['カテゴリー'];
				// LedgHOME送料反映
				if ( '反映しない' === $this->settings['portal_setting']['LedgHOME']['レターパック送料反映'] ) {
					$this->settings['LedgHOME']['送料反映'] = array_values(
						array_filter(
							$this->settings['LedgHOME']['送料反映'],
							fn( $v ) => 'レターパック' !== $v
						)
					);
				}
			}
			// 注意書き　＝＝＝＝＝＝＝＝＝＝＝＝＝＝
			{
				$this->settings['注意書き']['共通'] = $this->settings['n2']['portal_common_description'];
			}
			// 楽天　＝＝＝＝＝＝＝＝＝＝＝＝＝＝
			{
				$this->settings['楽天'] = $default['楽天'];
				$pair = array(
					'html'    => '説明文追加html',
					'img_dir' => '商品画像ディレクトリ',
					'select'  => '項目選択肢',
					'spa'     => '楽天SPA',
					'tag_id'  => '共通タグID',
				);
				foreach ( $pair as $old => $new ) {
					$this->settings['楽天'][ $new ] = $this->settings['portal_setting']['楽天'][ $old ];
				}
				$this->settings['楽天']['FTP']['user'] = $this->settings['portal_setting']['楽天']['ftp_user'];
				$this->settings['楽天']['FTP']['pass'] = $this->settings['portal_setting']['楽天']['ftp_pass'];
			}
			// ふるさとチョイス　＝＝＝＝＝＝＝＝＝＝＝＝＝＝
			{
				$this->settings['ふるさとチョイス'] = $default['ふるさとチョイス'];
			}
			// 旧設定の破棄
			unset( $this->settings['n2'], $this->settings['delivery_fee'], $this->settings['formula'], $this->settings['portal_setting'] );
		}
	}

	/**
	 * プロパティのセット
	 */
	public function set_vars() {
		global $pagenow, $wpdb;
		// wp_options保存値
		// delete_option( 'n2_settings' );
		$this->settings = get_option(
			'n2_settings',
			yaml_parse_file( get_theme_file_path( 'config/n2-settings.yml' ) )
		);
		$this->compatible_settings();
		// ポータル設定のymlをマージ
		$this->settings = array_merge_recursive(
			$this->settings,
			apply_filters(
				'n2_portal_setting',
				yaml_parse_file( get_theme_file_path( 'config/portal-setting.yml' ) )
			),
		);

		// アクセス中のIP
		$this->ip_address = $_SERVER['REMOTE_ADDR'];

		// SSのIP
		$this->ss_ip_address = yaml_parse_file( get_theme_file_path( 'config/ss-ip-address.yml' ) );

		// 現在のモード
		$this->mode = preg_match( '/localhost/', wp_parse_url( get_bloginfo( 'url' ), PHP_URL_HOST ) ) ? 'develop' : 'production';

		// キャッシュバスター
		// $this->cash_buster = 'develop' === $this->mode ? time() : wp_get_theme()->get( 'Version' );
		$this->cash_buster = time();

		// サイト基本情報
		$this->blog_prefix = $wpdb->get_blog_prefix();
		$this->site_id     = get_current_blog_id();
		$this->town        = get_bloginfo( 'name' );
		$this->logo        = 'https://event.rakuten.co.jp/furusato/_pc/img/area/ico/ico_' . end( explode( '/', get_home_url() ) ) . '.png';
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
		$delivery_fee = $this->settings['寄附金額・送料']['送料'];
		if ( ! empty( $delivery_fee ) ) {
			// クールの自動加算
			foreach ( array( 200, 200, 300, 600 ) as $i => $cool ) {
				$i++;
				// 消費税
				$tax_rate = $this->settings['寄附金額・送料']['税込送料'] ? 1.1 : 1;// 設定できるようにする
				// クール加算
				$delivery_fee[ "010{$i}_cool" ] = (string) ( (int) $delivery_fee[ "010{$i}" ] + ( $cool * $tax_rate ) );
			}
		}
		$this->settings['寄附金額・送料']['送料'] = $delivery_fee;

		// カスタムフィールド
		{
			$this->custom_field = yaml_parse_file( get_theme_file_path( 'config/custom-field.yml' ) );
			// 出品しないポータルの場合はカスタムフィールドを削除
			foreach ( $this->custom_field as $key => $custom_field ) {
				foreach ( $custom_field as $name => $value ) {
					if ( isset( $value['portal'] ) && ! in_array( $value['portal'], $this->settings['N2']['出品ポータル'], true ) ) {
						unset( $this->custom_field[ $key ][ $name ] );
					}
				}
			}
			// 出品禁止ポータルから削除
			foreach ( $this->custom_field['スチームシップ用']['出品禁止ポータル']['option'] as $index => $option ) {
				if ( ! in_array( $option, $this->settings['N2']['出品ポータル'], true ) ) {
					unset( $this->custom_field['スチームシップ用']['出品禁止ポータル']['option'][ $index ] );
				}
			}
			// 商品タイプの選択肢制御
			$this->custom_field['事業者用']['商品タイプ']['option'] = array_filter( $this->settings['N2']['商品タイプ'] );
			if ( empty( $this->custom_field['事業者用']['商品タイプ']['option'] ) ) {
				$this->custom_field['事業者用']['商品タイプ']['type'] = 'hidden';
			}
			// オンライン決済限定の制御
			if ( empty( $this->settings['ふるさとチョイス']['オンライン決済限定'] ) ) {
				unset( $this->custom_field['スチームシップ用']['オンライン決済限定'] );
			}
			// LHカテゴリーの設定値を配列化
			$lh_category = $this->settings['LedgHOME']['カテゴリー'];
			$lh_category = preg_split( '/\r\n|\r|\n/', trim( $lh_category ) );
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
