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
		''                 => 'N2',
		'formula_delivery' => '寄附金額・送料',
		'ledghome'         => 'LedgHOME',
		'rakuten'          => '楽天市場',
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
		$html     = array(
			'nav'      => '',
			'contents' => '',
		);
		// n2_settings
		$n2_settings = get_option( 'n2_settings' );
		foreach ( $this->settings as $page => $name ) {
			$menu_slug    = implode( '_', array_filter( array( 'n2_settings', $page ) ) );
			$html['nav'] .= sprintf( '<a href="?page=%s" class="nav-tab%s">%s</a>', $menu_slug, $menu_slug === $template ? ' nav-tab-active' : '', $name );
			ob_start();
			$this->$menu_slug( $n2_settings );
			$html['contents'] .= sprintf( '<div style="display: %s;padding: 3em 0;">%s</div>', $menu_slug === $template ? 'block' : 'none', ob_get_clean() );
		}
		?>
		<div class="wrap">
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
		echo '<pre>';print_r( $n2_settings );echo '</pre>';

	}

	/**
	 * N2
	 *
	 * @param array $n2_settings オプション値 n2_settings
	 */
	private function n2_settings( $n2_settings ) {
		$portals = array(
			'ふるさとチョイス',
			'楽天市場',
			'ふるなび',
			'ANA',
		);
		?>
		<table class="form-table">
			<tr>
				<th>稼働状況</th>
				<td>
					<label>
						<input type="checkbox" name="n2_settings[n2][active]" value="1" <?php checked( $n2_settings['n2']['active'] ?? '' ); ?>> N2稼働中
					</label>
				</td>
			</tr>
			<tr>
				<th>出品ポータル</th>
				<td>
					<?php foreach ( $portals as $portal ) : ?>
					<label style="margin: 0 2em 0 0;">
						<input type="checkbox" name="n2_settings[n2][portal_sites][]" value="<?php echo esc_attr( $portal ); ?>" <?php checked( in_array( $portal, $n2_settings['n2']['portal_sites'] ?? array(), true ) ); ?>> <?php echo $portal; ?>
					</label>
					<?php endforeach; ?>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * 寄附金額・送料
	 *
	 * @param array $n2_settings オプション値 n2_settings
	 */
	private function n2_settings_formula_delivery( $n2_settings ) {
		?>
		<table class="form-table">
			<tr>
				<th>送料を寄附金額計算に含める</th>
				<td>
					<label style="margin: 0 2em 0 0;">
						<input type="radio" name="n2_settings[formula][送料乗数]" value="0" <?php checked( $n2_settings['formula']['送料乗数'] ?? 0, 0 ); ?>> 含めない
					</label>
					<label>
						<input type="radio" name="n2_settings[formula][送料乗数]" value="1" <?php checked( $n2_settings['formula']['送料乗数'] ?? 0, 1 ); ?>> 含める
					</label>
				</td>
			</tr>
			<tr>
				<th>寄附金額計算の除数</th>
				<td>
					<input type="number" step="0.01" name="n2_settings[formula][除数]" value="<?php echo esc_attr( $n2_settings['formula']['除数'] ); ?>">
				</td>
			</tr>
			<tr>
				<th>送料</th>
				<td>
					<?php for ( $i = 1; $i <= 8; $i++ ) : ?>
					<p><span style="display:inline-block; width: 7em;"><?php echo ( 20 * $i ) + 40; ?> サイズ : </span><input type="number" name="n2_settings[delivery_fee][010<?php echo $i; ?>]" value="<?php echo esc_attr( $n2_settings['delivery_fee'][ "010{$i}" ] ?? '' ); ?>" style="width: 7em;"></p>
					<?php endfor; ?>
				</td>
			</tr>
			<tr>
				<th>レターパック</th>
				<td>
					<label style="margin: 0 2em 0 0;">
						<input type="checkbox" name="n2_settings[delivery_fee][レターパックライト]" value="370" <?php checked( $n2_settings['delivery_fee']['レターパックライト'] ?? '', 370 ); ?>> レターパックライト
					</label>
					<label>
						<input type="checkbox" name="n2_settings[delivery_fee][レターパックプラス]" value="520" <?php checked( $n2_settings['delivery_fee']['レターパックプラス'] ?? '', 520 ); ?>> レターパックプラス
					</label>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * LedgHOME
	 *
	 * @param array $n2_settings オプション値 n2_settings
	 */
	private function n2_settings_ledghome( $n2_settings ) {
		?>
		<table class="form-table">
			<tr>
				<th>カテゴリー</th>
				<td>
					<textarea name="n2_settings[portal_setting][LedgHOME][カテゴリー]" rows="10" style="width: 100%;"><?php echo esc_attr( $n2_settings['portal_setting']['LedgHOME']['カテゴリー'] ); ?></textarea>
				</td>
			</tr>
			<tr>
				<th>レターパック送料反映</th>
				<td>
					<label style="margin: 0 2em 0 0;">
						<input type="radio" name="n2_settings[portal_setting][LedgHOME][レターパック送料反映]" value="反映する" <?php checked( $n2_settings['portal_setting']['LedgHOME']['レターパック送料反映'] ?? '', '反映する' ); ?>> 反映する
					</label>
					<label>
						<input type="radio" name="n2_settings[portal_setting][LedgHOME][レターパック送料反映]" value="反映しない" <?php checked( $n2_settings['portal_setting']['LedgHOME']['レターパック送料反映'] ?? '', '反映しない' ); ?>> 反映しない
					</label>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * 楽天市場
	 *
	 * @param array $n2_settings オプション値 n2_settings
	 */
	private function n2_settings_rakuten( $n2_settings ) {
		?>
		<table class="form-table">
			<tr>
				<th>FTPユーザー</th>
				<td>
					<input type="text" name="n2_settings[portal_setting][楽天][ftp_user]" value="<?php echo esc_attr( $n2_settings['portal_setting']['楽天']['ftp_user'] ); ?>">
				</td>
			</tr>
			<tr>
				<th>FTPパスワード</th>
				<td>
					<input type="text" name="n2_settings[portal_setting][楽天][ftp_pass]" value="<?php echo esc_attr( $n2_settings['portal_setting']['楽天']['ftp_pass'] ); ?>">
				</td>
			</tr>
			<tr>
				<th>楽天SPA</th>
				<td>
					<label>
						<input type="checkbox" name="n2_settings[portal_setting][楽天][spa]" value="1" <?php checked( $n2_settings['portal_setting']['楽天']['spa'] ?? '' ); ?>> 楽天SPA
					</label>
				</td>
			</tr>
			<tr>
				<th>商品画像ディレクトリ</th>
				<td>
					<input type="text" name="n2_settings[portal_setting][楽天][img_dir]" value="<?php echo esc_attr( $n2_settings['portal_setting']['楽天']['img_dir'] ); ?>">
				</td>
			</tr>
			<tr>
				<th>タグID</th>
				<td>
					<input type="text" name="n2_settings[portal_setting][楽天][tag_id]" value="<?php echo esc_attr( $n2_settings['portal_setting']['楽天']['tag_id'] ); ?>">
				</td>
			</tr>
			<tr>
				<th>説明文追加html</th>
				<td>
					<p>※商品説明文の最後に共通で追加される文言を設定できます(タグ使用可能)</p>
					<textarea name="n2_settings[portal_setting][楽天][html]" style="width: 100%;" rows="10"><?php echo esc_attr( $n2_settings['portal_setting']['楽天']['html'] ); ?></textarea>
				</td>
			</tr>
			<tr>
				<th>項目選択肢</th>
				<td>
					<p>※それぞれの項目選択肢は２つ以上の連続改行をして下さい</p>
					<p>※選択肢は最大16文字で１つの改行で区切って下さい</p>
					<textarea name="n2_settings[portal_setting][楽天][select]" style="width: 100%;" rows="30" placeholder="<?php echo "1.ふるさと納税専用ページです。注文内容確認画面に表示される「注文者情報」を住民票情報とみなします。\n理解した\n\n2.寄附金の用途を選択\n用途１\n用途２\n..."; ?>"><?php echo esc_attr( $n2_settings['portal_setting']['楽天']['select'] ); ?></textarea>
				</td>
			</tr>
		</table>
		<?php
	}
}
