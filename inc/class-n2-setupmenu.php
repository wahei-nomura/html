<?php
/**
 * class-n2-setupmenu.php
 *
 * @package neoneng
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// if ( class_exists( 'N2_Setupmenu' ) ) {
// 	new N2_Setupmenu();
// 	return;
// }

/**
 * 各種セットアップ共通用
 */
class N2_Setupmenu {
	/**
	 * 各種セットアップの各項目ラッピング用
	 *
	 * @param String $f_name　関数名
	 * @param String $header 見出し
	 * @return void
	 */
	public function wrapping_contents( $f_name, $header ) {
		?>
		<div id="<?= $f_name ?>">
			<div class="postbox-header">
				<h2><?= $header ?></h2>
			</div>
			<div class="inside">
				<?php $this->$f_name()?>
			</div>
		</div>
		<?php
	}
}