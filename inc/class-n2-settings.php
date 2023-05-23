<?php
/**
 * class-n2-settings.php
 * N2設定
 *
 * @package neoneng
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
	protected $settings = array(
		''                => 'N2',
		'donation_amount' => '寄附金額・送料',
		'ledghome'        => 'LedgHOME',
		'rakuten'         => '楽天市場',
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
		add_menu_page( 'N2設定', 'N2設定', 'ss_crew', 'n2_settings', array( $this, 'ui' ), 'dashicons-admin-settings', 80 );
		foreach ( $this->settings as $page => $name ) {
			$menu_slug = implode( '_', array_filter( array( 'n2_settings', $page ) ) );
			add_submenu_page( 'n2_settings', $name, $name, 'ss_crew', $menu_slug, array( $this, 'ui' ) );
			register_setting( $menu_slug, $menu_slug );
		}
	}

	/**
	 * 統一のUI
	 */
	public function ui() {
		$template = $_GET['page'];
		?>
		<div class="wrap">
			<h1>N2設定</h1>
			<div id="crontrol-header">
				<nav class="nav-tab-wrapper">
					<?php
					foreach ( $this->settings as $page => $name ) {
						$menu_slug = implode( '_', array_filter( array( 'n2_settings', $page ) ) );
						printf( '<a href="?page=%s" class="nav-tab%s">%s</a>', $menu_slug, $menu_slug === $template ? ' nav-tab-active' : '', $name );
					}
					?>
				</nav>
			</div>
			<?php echo $this->$template(); ?>
		</div>
		<?php
	}

	/**
	 * N2
	 */
	private function n2_settings() {
		?>
		<form method="post" action="options.php">
			<?php settings_fields( $_GET['page'] ); ?>
			<button class="button button-primary">設定を保存</button>
		</form>
		<?php
	}

	/**
	 * 寄附金額・送料
	 */
	private function n2_settings_donation_amount() {
		?>
		<form method="post" action="options.php">
			<?php settings_fields( $_GET['page'] ); ?>
			<button class="button button-primary">設定を保存</button>
		</form>
		<?php
	}

	/**
	 * LedgHOME
	 */
	private function n2_settings_ledghome() {
		?>
		<form method="post" action="options.php">
			<?php settings_fields( $_GET['page'] ); ?>
			<button class="button button-primary">設定を保存</button>
		</form>
		<?php
	}

	/**
	 * 楽天市場
	 */
	private function n2_settings_rakuten() {
		?>
		<form method="post" action="options.php">
			<?php settings_fields( $_GET['page'] ); ?>
			<button class="button button-primary">設定を保存</button>
		</form>
		<?php
	}
}
