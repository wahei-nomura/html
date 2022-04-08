<?php
/**
 * class-n2-hogehoge.php
 *
 * @package neoneng
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'N2_Engineersetup' ) ) {
	new N2_Engineersetup();
	return;
}

/**
 * Hogehoge
 */
class N2_Engineersetup {

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_setup_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'setup_menu_style' ) );
	}

	/**
	 * add_setup_menu
	 * セットアップ管理ページを追加
	 */
	public function add_setup_menu() {
		add_menu_page( '各種セットアップ', '各種セットアップ', 'manage_options', 'setup_menu', array( $this, 'add_setup_menu_page' ), 'dashicons-list-view' );
	}

	 /**
	  * メニュー描画
	  *
	  * @return void
	  */
	public function add_setup_menu_page() {
		$menus = array(
			array(
				'menu_name'   => '事業者連絡先',
				'description' => '事業者さまとのやりとりに使用するメールアドレス・電話番号を記入してください。',
				'input1'      => array( 'メールアドレス', 'email', 'contact' ),
				'input2'      => array( '電話番号', 'tel', 'contact' ),
			),
			array(
				'menu_name' => '各ポータル共通説明文',
				'text1'     => array( '商品説明文の文末に追加したいテキスト', "<?=get_bloginfo('name')?>", 'add_text' ),
			),
		);
		?>
		<div class="wrap">
		<?php
		foreach ( $menus as $menu ) {
			?>
			<div class="row">
			<h2><?php echo $menu['menu_name']; ?></h2>
			<?php
			if ( array_key_exists( 'description', $menu ) ) {
				echo '<p>' . $menu['description'] . '</p>';
			}
			?>
			<div class="flex">
			<?php
			foreach ( $menu as $v ) {
				switch ( array_search( $v, $menu ) ) {
					case preg_match( '/input[1-9]/u', array_search( $v, $menu ) ) === 1:
						?>
						<div class="column">
							<p><?php echo $v[0]; ?>：</p>
							<input type="text" name="<?php echo NENG_DB_TABLENAME; ?>[<?php echo $v[2]; ?>][<?php echo $v[1]; ?>]" value="<?php echo NENG_OPTION['<?php echo $v[2]?>']['<?php echo $v[1]?>']; ?>">
						</div>
						<?php
						break;
					case preg_match( '/text[1-9]/u', array_search( $v, $menu ) ) === 1:
						?>
						<div class="column">
							<p><?php echo $v[0]; ?>：</p>
							<textarea name="<?php echo NENG_DB_TABLENAME; ?>[<?php echo $v[2]; ?>][<?php echo get_bloginfo( 'name' ); ?>]" rows="7" style="overflow-x: hidden;"><?php echo NENG_OPTION['<?php echo $v[2]?>'][ get_bloginfo( 'name' ) ]; ?></textarea>
						</div>
						<?php
				}
			}
			?>
				</div>
			</div>
			<?php
		}
		?>
		</div>
		<?php
	}

	/**
	 * このクラスで使用するassetsの読み込み
	 *
	 * @return void
	 */
	public function setup_menu_style() {
		wp_enqueue_style( 'n2-setupmenu', get_template_directory_uri() . '/dist/setupmenu.css', array(), wp_get_theme()->get( 'Version' ) );
	}
}
