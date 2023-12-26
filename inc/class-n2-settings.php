<?php
/**
 * class-n2-settings.php
 * N2設定
 * template/settings/xxxxxxx.phpをいい感じに読み込む
 *
 * @package neoneng
 */

if ( class_exists( 'N2_Settings' ) ) {
	new N2_Settings();
	return;
}

/**
 * N2設定
 */
class N2_Settings {

	/**
	 * 設定項目
	 *
	 * @var array
	 */
	public static $settings = array(
		'n2'               => 'N2',
		'formula-delivery' => '寄附金額・送料',
		'ledghome'         => 'LedgHOME',
		'warning'          => '注意書き',
		'furusato-choice'  => 'ふるさとチョイス',
		'rakuten'          => '楽天',
		'furunavi'         => 'ふるなび',
		'ana'              => 'ANA',
	);

	/**
	 * 全ポータルサイト
	 *
	 * @var array
	 */
	public $portal_sites = array(
		'ふるさとチョイス',
		'楽天',
		'ふるなび',
		'ANA',
	);

	/**
	 * その他データ
	 *
	 * @var array
	 */
	public $data = array(
		'商品タイプ' => array( '食品', '酒', 'やきもの', 'eチケット' ),
	);

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
	}

	/**
	 * メニュー追加
	 */
	public function add_menu() {
		// delete_option( 'n2_settings' );
		if ( ! WP_Filesystem() ) {
			return;
		}
		global $wp_filesystem;
		add_menu_page( 'N2設定', 'N2設定', 'ss_crew', 'n2_settings', array( $this, 'ui' ), 'dashicons-admin-tools', 80 );
		foreach ( self::$settings as $page => $name ) {
			// 設定テンプレートの存在を確認して、ない場合は破棄してスキップする
			if ( ! $wp_filesystem->exists( get_theme_file_path( "template/settings/{$page}.php" ) ) ) {
				unset( self::$settings[ $page ] );
				continue;
			}
			// 出品しないポータルの場合はスキップする
			if ( $this->is_hide_menu( $name ) ) {
				continue;
			}
			$menu_slug = $this->create_menu_slug( $page );
			add_submenu_page( 'n2_settings', $name, $name, 'ss_crew', $menu_slug, array( $this, 'ui' ) );
			register_setting( $menu_slug, $menu_slug );
		}
	}

	/**
	 * 統一のUI
	 */
	public function ui() {
		global $n2;
		$template = $_GET['page'];
		$html     = array(
			'nav'      => '',
			'contents' => '',
		);
		// n2_settings
		foreach ( self::$settings as $page => $name ) {
			$menu_slug = $this->create_menu_slug( $page );
			if ( ! $this->is_hide_menu( $name ) ) {
				// ナビゲーション
				$html['nav'] .= sprintf( '<a href="?page=%s" class="nav-tab%s">%s</a>', $menu_slug, $menu_slug === $template ? ' nav-tab-active' : '', $name );
			}
			// フォームコンテンツ
			ob_start();
			get_template_part( "template/settings/{$page}", null, $this );
			$html['contents'] .= sprintf( '<div style="display: %s;padding: 3em 0;">%s</div>', $menu_slug === $template ? 'block' : 'none', ob_get_clean() );
		}
		?>
		<div class="wrap n2-setting-form">
			<h1><span class="dashicons dashicons-admin-settings" style="transform: scale(2) translateY(.1em);"></span>　N2設定</h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'n2_settings' ); ?>
				<div id="crontrol-header">
					<nav class="nav-tab-wrapper"><?php echo $html['nav']; ?></nav>
				</div>
				<?php echo $html['contents']; ?>
				<button class="button button-primary">設定を保存</button>
			</form>
		</div>
		<?php
	}

	/**
	 * メニュースラッグの作成
	 * ?page=n2_settings_xxxx
	 *
	 * @param string $page ページ
	 */
	private function create_menu_slug( $page ) {
		return 'n2_settings' . ( 'n2' === $page ? '' : "_{$page}" );
	}

	/**
	 * 表示しないメニュー判定
	 * ?page=n2_settings_xxxx
	 *
	 * @param string $name メニュー名
	 */
	private function is_hide_menu( $name ) {
		global $n2;
		$hide = array_diff( $this->portal_sites, $n2->settings['N2']['出品ポータル'] );
		return in_array( $name, $hide, true );
	}
}
