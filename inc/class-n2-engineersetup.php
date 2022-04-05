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
		//add_action( 'admin_menu', array( $this, 'add_setup_menu' ) );
	}

	public function add_setup_menu(){
		//add_menu_page('各種セットアップ', '各種セットアップ', 'manage_options', 'setup_menu', 'html_setup_menu','dashicons-list-view');
	}

/* 	public function add_widgets() {
		$widgets = array(
			array(
				"widget_name" => '事業者連絡先',
				"description" => "事業者さまとのやりとりに使用するメールアドレス・電話番号を記入してください。",
				"input1" => array("メールアドレス","email","contact"),
				"input2" => array("電話番号","tel","contact"),
			),
			array(
				"widget_name" => '各ポータル共通説明文',
				"text1" => array("商品説明文の文末に追加したいテキスト","<?=get_bloginfo('name')?>","add_text"),
			),
		);
		foreach($widgets as $v){
			wp_add_dashboard_widget('setup_widget', $v[widget_name], array($this,'setup_widget'),null,$v[0]);
			wp_add_dashboard_widget('setup_widget', $v[widget_name], array($this,'setup_widget'),null,$v)[1];
		}
	} */

	function html_setup_menu(){
		echo "<h2>オリジナルメニューページ</h2>";
	}

    /* function setup_widget( $var, $args ){
		?>
		<form>
		<input type="hidden" name="action" value="<?=NENG_DB_TABLENAME?>">
		<input type="hidden" name="judge" value="option">
		<?php
		foreach($args[args] as $v){
			switch(array_search($v,$args[args])){
				case "description":
					?>
					<p><?php echo $v ?></p>
					<?php
					break;
				case preg_match("/input[1-9]/u", array_search($v,$args[args])) === 1 :
					?>
					<p class="input-text-wrap">
						<?php echo $v[0]?>：
						<input type="text" name="<?=NENG_DB_TABLENAME?>[<?php echo $v[2]?>][<?php echo $v[1]?>]" value="<?=NENG_OPTION['<?php echo $v[2]?>']['<?php echo $v[1]?>']?>">
					</p>
					<?php
					break;
				case preg_match("/text[1-9]/u", array_search($v,$args[args])) === 1 :
					?>
					<p class="textarea-wrap">
						<?php echo $v[0]?>：
						<textarea name="<?=NENG_DB_TABLENAME?>[<?php echo $v[2]?>][<?=get_bloginfo('name')?>]" rows="7" style="overflow-x: hidden;"><?=NENG_OPTION['<?php echo $v[2]?>'][get_bloginfo('name')]?></textarea>
					</p>
					<?php
			}
		}
		?>
		<input type="submit" class="button button-primary sissubmit" value="　更新する　">
		</form>
		<?php
	} */
}
