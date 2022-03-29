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
		$widgets = array(
			array(
				"widget_name" => '事業者連絡先',
				"description" => "事業者さまとのやりとりに使用するメールアドレス・電話番号を記入してください。",
				"text" => "メールアドレス",
				"text2" => "電話番号",
			)
		);
		foreach($widgets as $v){
			wp_add_dashboard_widget('setup_widget', $v[widget_name], array($this,'setup_widget'),null,$v);
		}
	}

    function setup_widget( $var, $args ){
		?>
		<form>
		<?php
		foreach($args[args] as $v){
			switch(array_search($v,$args[args])){
				case "description":
					?>
					<p><?php echo $v ?></p>
					<?php
					break;
				case preg_match("/text[1-9]/u", array_search($v,$args[args])) === 1 :
					?>

					<?php
					break;
			}
		}
		?>
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
