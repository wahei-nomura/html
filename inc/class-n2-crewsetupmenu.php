<?php
/**
 * class-n2-crewsetupmenu.php
 *
 * @package neoneng
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'N2_Crewsetupmenu' ) ) {
	new N2_Crewsetupmenu();
	return;
}

/**
 * Hogehoge
 */
class N2_Crewsetupmenu extends N2_Setupmenu {

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
		add_menu_page( '各種セットアップ', '各種セットアップ', 'ss-crew', 'n2_setup_menu', array( $this, 'add_setup_menu_page' ), 'dashicons-list-view' );
		add_submenu_page('n2_setup_menu', 'SSクルー', 'SSクルー', 'ss-crew', 'crew_setup_menu', array( $this, 'add_setup_menu_page' ), 'dashicons-list-view' );
		remove_submenu_page('n2_setup_menu','n2_setup_menu');
	}

	 /**
	  * メニュー描画
	  *
	  * @return void
	  */
	public function add_setup_menu_page(){
		$this->wrapping_contents('contact_setup_widget','事業者連絡先');
		$this->wrapping_contents('add_text_widget','各ポータル共通説明文');
	}


	# (各ポータル共通)商品説明文文末テキストの登録
	public function add_text_widget(){
		?>
		<form>
			<input type="hidden" name="action" value="<?=NENG_DB_TABLENAME?>">
			<input type="hidden" name="judge" value="option">
			<p class="textarea-wrap">
				商品説明文の文末に追加したいテキスト：
				<textarea name="<?=NENG_DB_TABLENAME?>[add_text][<?=get_bloginfo('name')?>]" rows="7" style="overflow-x: hidden;"><?=NENG_OPTION['add_text'][get_bloginfo('name')]?></textarea>
			</p>
			<input type="submit" class="button button-primary sissubmit" value="　更新する　">
		</form>
		<?php
	}
	# 事業者連絡先（機能はajaxで$judge=="option"）
	public function contact_setup_widget(){
		?>
		<form>
			<p>事業者さまとのやりとりに使用するメールアドレス・電話番号を記入してください。</p>
			<input type="hidden" name="action" value="<?=NENG_DB_TABLENAME?>">
			<input type="hidden" name="judge" value="option">
			<p class="input-text-wrap">
				メールアドレス：
				<input type="text" name="<?=NENG_DB_TABLENAME?>[contact][email]" value="<?=NENG_OPTION['contact']['email']?>">
			</p>
			<p class="input-text-wrap">
				電話番号：
				<input type="text" name="<?=NENG_DB_TABLENAME?>[contact][tel]" value="<?=NENG_OPTION['contact']['tel']?>">
			</p>
			<input type="submit" class="button button-primary sissubmit" value="　更新する　">
		</form>
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
