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
	 * GPTテンプレート
	 *
	 * @var array
	 */
	public $openai_template;

	/**
	 * query（ログイン時のみ）
	 *
	 * @var object
	 */
	public $query;

	/**
	 * 未読のお知らせがあるか
	 */
	public $unread_notification_count;

	/**
	 * 正規表現
	 *
	 * @var array
	 */
	public $regex = array(
		'item_code' => array(
			/**
			 * 厳格モード（ただし全体マッチ以外は使いにくい）
			 * [1] 返礼品コード全体にマッチ
			 */
			'strict' => '([0-4]{1}[0-9]{1}[A-Z]{4}[0-9]{3}|[A-Z]{2,4}[0-9]{2,3})',
			/**
			 * ノーマルモード（厳格モードより正しくないがほぼ大丈夫であり、マッチパターンも多い）
			 * [1] 返礼品コード全体にマッチ
			 * [2] 事業者コードにマッチ
			 * [3] 県の場合県コード（数字）にマッチ
			 * [4] 返礼品番号にマッチ
			 */
			'normal' => '((([0-9]{0,2})[A-Z]{2,4})([0-9]{2,3}))',
		),
	);

	/**
	 * 一時ファイル
	 *
	 * @var array
	 */
	public $tmp = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->set_vars();
		$this->set_filters();
		$this->set_unread_notification_count();
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
					$meta  = get_post_meta( $post->ID, $name, true );
					$value = '' !== $meta ? $meta : $value;// こうしないと0が初期化されてしまう
					// ====== 新規登録時の初期化 ======
					if ( empty( $value ) && 0 !== $value && '0' !== $value ) {
						$user_meta = $this->current_user->data->meta;
						$value     = match ( $name ) {
							'商品タイプ' => $user_meta['商品タイプ'] && 'post-new.php' === $pagenow
								? array( array_search( max( $user_meta['商品タイプ'] ), $user_meta['商品タイプ'], true ) )
								: array(),
							'寄附金額固定', '商品画像' => array(),
							default => 'checkbox' === ( $v['type'] ?? false ) ? array() : '',
						};
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
				$pair                 = array(
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
				$this->settings['寄附金額・送料']       = array(
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
				$pair                 = array(
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
		// 無駄な空の設定を排除
		$this->settings = array_map(
			fn( $v ) => array_map( fn( $a ) => is_array( $a ) ? array_filter( $a ) : $a, $v ),
			$this->settings
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
		$jichitai_name     = end( explode( '/', get_home_url() ) );
		if ( 'f424111-shinkamigoto' === $jichitai_name ) {
			$this->logo = 'https://event.rakuten.co.jp/furusato/_pc/img/area/ico/ico_' . end( explode( '/', get_home_url() ) ) . '.jpg';
		} elseif ( 'f422142-minamishimabara' === $jichitai_name ) {
			$this->logo = 'https://event.rakuten.co.jp/furusato/_pc/img/area/ico/ico_f422142-minamisimabara.png';
		} elseif ( 'f212041-tajimi' === $jichitai_name ) {
			$this->logo = 'https://event.rakuten.co.jp/furusato/_pc/img/area/ico/ico_f212041-tajimi.jpg';
		} else {
			$this->logo = 'https://event.rakuten.co.jp/furusato/_pc/img/area/ico/ico_' . end( explode( '/', get_home_url() ) ) . '.png';
		}
		$this->ajaxurl = admin_url( 'admin-ajax.php' );
		$this->cookie  = $_COOKIE;

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

		// GPTテンプレート
		$this->openai_template = yaml_parse_file( get_theme_file_path( 'config/openai-template.yml' ) );

		// 文字列変換
		$this->special_str_convert = yaml_parse_file( get_theme_file_path( 'config/special-str-convert.yml' ) );

		// 文字列変換
		$this->allergen_convert = yaml_parse_file( get_theme_file_path( 'config/allergen-convert.yml' ) );

		// 送料設定
		$delivery_fee = $this->settings['寄附金額・送料']['送料'];
		if ( ! empty( $delivery_fee ) ) {
			// クールの自動加算
			foreach ( array( 200, 200, 300, 600 ) as $i => $cool ) {
				++$i;
				// 消費税
				$tax_rate = $this->settings['寄附金額・送料']['税込送料'] ? 1.1 : 1;// 設定できるようにする
				// クール加算
				$delivery_fee[ "010{$i}_cool" ] = (string) ( (int) $delivery_fee[ "010{$i}" ] + ( $cool * $tax_rate ) );
			}
		}
		$this->settings['寄附金額・送料']['送料'] = $delivery_fee;

		// N2設定操作の可否
		$this->settings_access = ( $this->settings['N2']['稼働中'] && ( isset( $this->current_user->roles[0] ) && 'administrator' !== $this->current_user->roles[0] ) ) ? false : true;
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

	/**
	 * 確認が必要なお知らせ件数を取得
	 *
	 * @return int
	 */
	public function set_unread_notification_count() {
		global $wpdb;
		switch_to_blog(1);
		// お知らせのメタデータをすべて取得
		$query = $wpdb->prepare("
			SELECT *
			FROM $wpdb->postmeta 
			WHERE meta_key LIKE 'notification-%'
		");
		// 投稿IDでグループ化
		$notification_post_meta = array_reduce(
			$wpdb->get_results($query) ?? [],
			function($carry, $meta) {
				$carry[$meta->post_id][$meta->meta_key] = maybe_unserialize($meta->meta_value);
				return $carry;
			},
			[]
		);
		// 条件に当てはまる投稿IDを取得
		$filtered_post_ids = array_keys(
			array_filter($notification_post_meta, function($meta) {
				// 既読
				if (in_array($this->current_user->ID, $meta['notification-read'] ?? [])) return false;
				// 強制表示
				if (!$meta['notification-force']) return false;
				// 権限
				if (!is_admin()) {
					if (!in_array($this->current_user->roles[0], $meta['notification-roles'] ?? [])) return false;
				}
				// 自治体
				if (!in_array($this->site_id, $meta['notification-regions'] ?? [])) return false;
				return true;
			})
		);
		if (count($filtered_post_ids) === 0) {
			$this->unread_notification_count = 0;
			restore_current_blog(); // 戻す
			return; // 'post__in'に空配列を渡すとWHERE INが無効化されるから早期リターン
		}
		// お知らせの投稿を検索
		$query = new WP_Query([
			'post_type' => 'notification',
			'post_status' => 'publish', // 公開中のみ
			'posts_per_page' => -1, // すべての投稿を対象
			'fields' => 'ids', // 投稿IDだけ
			'date_query' => [ // 特定の日付範囲
				[
					'after' => $this->current_user->user_registered, // ユーザー登録以降の投稿
					'inclusive' => true,
				],
			],
			'post__in' => $filtered_post_ids,
		]);
		restore_current_blog(); // 戻す
		// 件数をメンバ変数に代入
		$this->unread_notification_count = $query->found_posts;
	}
}
