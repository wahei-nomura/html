<?php
/**
 * class-n2-setmenu.php
 *
 * @package neoneng
 */

if ( class_exists( 'N2_Setmenu' ) ) {
	new N2_Setmenu();
	return;
}

/**
 * Setmenu
 */
class N2_Setmenu {
	/**
	 * コンストラクタ
	 */
	public function __construct() {
		add_filter( 'admin_body_class', array( $this, 'admin_body_class' ) );
		add_action( 'admin_menu', array( $this, 'change_menulabel' ) );
		add_action( 'admin_menu', array( $this, 'remove_menulabel' ), 999 );
		add_action( 'admin_init', array( $this, 'not_edit_user' ) );
		add_filter( 'get_site_icon_url', array( $this, 'change_site_icon' ) );
		add_action( 'admin_head', array( $this, 'my_custom_logo' ) );
		add_action( 'admin_bar_menu', array( $this, 'change_admin_bar_menus' ), 999 );
		add_action( 'admin_head', array( $this, 'remove_help_tabs' ) );// ヘルプ削除
		add_filter( 'admin_footer_text', '__return_false' );// 「WordPress のご利用ありがとうございます。」を削除
		add_action( 'admin_menu', array( $this, 'add_loginbookmark' ) );
	}

	/**
	 * 管理画面のbodyにクラス付与
	 *
	 * @param string $classes クラス文字列
	 */
	public function admin_body_class( $classes ) {
		global $n2;
		$classes   = explode( ' ', $classes );
		$classes[] = $n2->current_user->roles[0];
		$classes   = implode( ' ', $classes );
		return $classes;
	}

	/**
	 * change_menulabel
	 */
	public function change_menulabel() {
		global $menu;
		global $submenu;
		$name                      = '返礼品';
		$menu[5][0]                = $name;
		$submenu['edit.php'][5][0] = $name . '一覧';
	}

	/**
	 * remove_menulabel
	 */
	public function remove_menulabel() {
		// クルー事業者共通で削除
		$menus    = array(
			'edit.php?post_type=page', // 固定ページ
			'tools.php',
			'upload.php',
			'profile.php',
		);
		$submenus = array();
		global $n2;

		$img_dir = rtrim( $n2->settings['楽天']['商品画像ディレクトリ'], '/' ) . '/';

		switch ( preg_match( '/ne\.jp/', $img_dir ) ) {
			case 1:// GOLDの場合はFTP
				$menus[] = 'n2_rakuten_sftp_upload';
				break;
			default:// CABINETはSFTP
				$menus[] = 'n2_rakuten_ftp_upload';
		}

		// ロール毎で削除するメニュー
		switch ( $n2->current_user->roles[0] ) {
			case 'ss-crew':
				$menus[] = 'themes.php';
				$menus[] = 'upload.php';
				$menus[] = 'edit-comments.php';
				$menus[] = 'options-general.php';
				$menus[] = 'aiowpsec'; // All In One WP Security
				break;
			case 'jigyousya':
				$menus[] = 'index.php';
				$menus[] = 'edit-comments.php';
				$menus[] = 'aiowpsec'; // All In One WP Security
				break;
			case 'local-government':
				// $menus[]  = 'index.php';
				$menus[]  = 'my-sites.php';
				$menus[]  = 'edit-comments.php';
				$menus[]  = 'aiowpsec'; // All In One WP Security
				$submenus = array(
					...$submenus,
					...array(
						'edit.php' => 'post-new.php',
					),
				);
				break;
		}

		foreach ( $menus as $menu ) {
			remove_menu_page( $menu );
		}
		foreach ( $submenus as $menu_slug => $submenu_slug ) {
			remove_submenu_page( $menu_slug, $submenu_slug );
		}
	}

	/**
	 * not_edit_user
	 */
	public function not_edit_user() {
		global $pagenow, $n2;
		// if ( 'edit.php' === $pagenow && ( empty( $n2->settings['寄附金額・送料']['除数'] ) || empty( $n2->settings['寄附金額・送料']['送料']['0101'] ) ) ) {
		// echo '送料の設定は必須です。';
		// <script>alertsetTimeout(function(){}, 2000);</script>
		// wp_safe_redirect( admin_url( 'admin.php?page=n2_settings_formula-delivery' ) );
		// exit;
		// }
		// echo '<pre>';print_r($n2);echo '</pre>';exit;
		if ( current_user_can( 'ss_crew' ) ) {
			return;
		}

		$hide_pages = array(
			'index.php',
			'tooles.php',
			'upload.php',
		);
		if ( current_user_can( 'local-government' ) ) { // 自治体アカウントはダッシュボードを許可
			$hide_pages = array(
				'my-sites.php',
				'tooles.php',
				'upload.php',
			);

		}
		if ( in_array( $pagenow, $hide_pages, true ) ) {
			wp_safe_redirect( admin_url( 'edit.php' ) );
		}
		if ( 'profile.php' === $pagenow ) {
			wp_die( 'ユーザープロフィールを変更したい場合は「Steamship」へお問い合わせください。<p><a class="button" href="' . admin_url( 'edit.php' ) . '">返礼品一覧へ戻る</a></p>' );
		}
	}

	/**
	 * faviconを変更する
	 *
	 * @param string $url デフォルトURL
	 */
	public function change_site_icon( $url ) {
		$name           = end( explode( '/', get_home_url() ) );
		$n2_active      = get_option( 'n2_settings' )['N2']['稼働中'] ?? 0;
		$town_icon_name = "https://event.rakuten.co.jp/furusato/_pc/img/area/ico/ico_{$name}.png";
		if ( 'f422142-minamishimabara' === $name ) {
			$town_icon_name = 'https://event.rakuten.co.jp/furusato/_pc/img/area/ico/ico_f422142-minamisimabara.png';
		} elseif ( 'f424111-shinkamigoto' === $name ) {
			$town_icon_name = 'https://event.rakuten.co.jp/furusato/_pc/img/area/ico/ico_f424111-shinkamigoto.jpg';
		} elseif ( 'f212041-tajimi' === $name ) {
			$town_icon_name = 'https://event.rakuten.co.jp/furusato/_pc/img/area/ico/ico_f212041-tajimi.jpg';
		}
		return $n2_active ? $town_icon_name : $url;
	}
	/**
	 * 管理画面左上のロゴ変更
	 *
	 * @return void
	 */
	public function my_custom_logo() {
		echo '<style type="text/css">#wpadminbar #wp-admin-bar-wp-logo > .ab-item > .ab-icon:before { content: url(' . get_theme_file_uri( 'neo_neng_logo.svg' ) . ');}</style>';
	}
	/**
	 * 管理画面のヘッダーメニューバーで不要なものを除去 @yamasaki
	 *
	 * @param array $wp_admin_bar 管理バーの項目を格納
	 */
	public function change_admin_bar_menus( $wp_admin_bar ) {
		global $n2;
		$wp_admin_bar->remove_menu( 'wp-logo' ); // WordPressロゴ.
		$wp_admin_bar->remove_menu( 'comments' );     // コメント
		$wp_admin_bar->remove_menu( 'new-content' );  // 新規
		$wp_admin_bar->remove_menu( 'view-site' );    // サイト名 → サイトを表示
		$wp_admin_bar->remove_menu( 'edit-site' );    // サイト名 → サイトを表示
		$wp_admin_bar->remove_menu( 'wp-mail-smtp-menu' ); // WP Mail SMTP
		$wp_admin_bar->add_node(
			array(
				'id'   => 'site-name',
				'href' => admin_url(),
				'meta' => array(
					'class' => $n2->settings['N2']['稼働中'] ? 'n2-active' : '',
					'html'  => $n2->settings['N2']['稼働中'] ? "<style>#wp-admin-bar-site-name{background-image: url({$n2->logo}) !important;}</style>" : '',
				),
			),
		);
		$wp_admin_bar->add_node(
			array(
				'id'    => 'my-sites',
				'title' => '自治体',
			),
		);
		if ( ! current_user_can( 'administrator' ) ) {
			$wp_admin_bar->remove_menu( 'edit-profile' ); // ユーザー / プロフィールを編集.
		}
		if ( ! current_user_can( 'administrator' ) && ! current_user_can( 'ss-crew' ) ) {
			$wp_admin_bar->remove_menu( 'my-sites' ); // 参加サイト.
		}
		// adminerのリンク調整
		$wp_adminer = $wp_admin_bar->get_node( 'wp_adminer' );
		if ( $wp_adminer ) {
			$href = $wp_adminer->href;
			// サブメニュー追加
			$wp_adminer->parent = 'wp_adminer';
			foreach ( explode( '|', 'posts|postmeta|options' ) as $v ) {
				$wp_adminer->id    = "wp_adminer_{$v}";
				$wp_adminer->title = "wp_{$n2->site_id}_{$v}";
				$wp_adminer->href  = "{$href}?username=&select=wp_{$n2->site_id}_{$v}";
				$wp_admin_bar->add_menu( $wp_adminer );
			}
			// ユーザー系
			foreach ( explode( '|', 'users|usermeta' ) as $v ) {
				$wp_adminer->id    = "wp_adminer_{$v}";
				$wp_adminer->title = "wp_{$v}";
				$wp_adminer->href  = "{$href}?username=&select=wp_{$v}";
				$wp_admin_bar->add_menu( $wp_adminer );
			}
		}
	}
	/**
	 * ヘルプタブ非表示 @yamasaki
	 */
	public function remove_help_tabs() {
		global $current_screen;
		$current_screen->remove_help_tabs();
	}
	/**
	 * ログインブックマークページ
	 */
	public function add_loginbookmark() {
		global $n2;
		add_menu_page( 'ブックマークURL', 'ブックマークURL', '', 'add_bookmark', array( $this, 'display_addbookmark' ), 'dashicons-admin-site-alt2' );
	}
	/**
	 * ログインブックマーク表示
	 */
	public function display_addbookmark() {
		$site_details = get_blog_details();
		$bookmark_url = str_replace( '//', '//ss:ss@', wp_login_url() ) . '?auth=ss:ss'; 
		$html         = '<div class = "wrap">';
		$html        .= '<h1 id="copyTarget"> ログインページブックマーク用URL </h1>';
		$html        .= '<p>以下のリンクをブックマーク登録してください(ブックマークバーにドラッグドロップでも登録できます)。</p>';
		$html        .= '<a onclick="copyToClipboard()" id="bookmarkLink" href="' . $bookmark_url . '" class="pressthis-bookmarklet" style="padding:.5rem;">' . $site_details->blogname . 'ログイン</a>';
		$html        .= '</div>';
		echo $html;
		?>
		<script>
			function copyToClipboard() {
				event.preventDefault();
				// コピー対象をJavaScript上で変数として定義する
				let copyTarget = document.getElementById("bookmarkLink");
				let copyLink = copyTarget.getAttribute('href');
				navigator.clipboard.writeText(copyLink);
				// 選択しているテキストをクリップボードにコピーする
				document.execCommand("Copy");

				// コピーをお知らせする
				alert("URLをコピーしました : " + copyLink);
			}
		</script>
		<?php
	}
}
