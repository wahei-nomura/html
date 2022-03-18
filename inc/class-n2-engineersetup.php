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
		add_action( 'wp_dashboard_setup', array( $this, 'add_widgets' ) );
	}

	public function add_widgets() {
		wp_add_dashboard_widget('contact_setup_widget', '事業者連絡先', array($this,'contact_setup_widget'));
	}

    function contact_setup_widget(){
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
}
