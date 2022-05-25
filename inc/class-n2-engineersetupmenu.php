<?php
/**
 * class-n2-hogehoge.php
 *
 * @package neoneng
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'N2_Engineersetupmenu' ) ) {
	new N2_Engineersetupmenu();
	return;
}

/**
 * Hogehoge
 */
class N2_Engineersetupmenu extends N2_Setupmenu {

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
		add_submenu_page('n2_setup_menu', 'エンジニア', 'エンジニア', 'manage_options', 'engineer_setup_menu', array( $this, 'add_setup_menu_page' ), 'dashicons-list-view' );
	}

	 /**
	  * メニュー描画
	  *
	  * @return void
	  */
	public function add_setup_menu_page() {
		$this->wrapping_contents('rakuten_setup_widget','楽天セットアップ');
		$this->wrapping_contents('choice_setup_widget','ふるさとチョイスセットアップ');
		$this->wrapping_contents('ledghome_setup_widget','レジホームセットアップ');
		$this->wrapping_contents('ana_setup_widget','ANAセットアップ');
		$this->wrapping_contents('import_widget','返礼品のインポート');
		$this->wrapping_contents('rakuten_setup_widget','楽天セットアップ');
		$this->wrapping_contents('price_widget','寄附金額・送料');
	}
	# 寄附金額・送料の登録
	public function price_widget(){
		$calc = array(
			'タイプ⓪ (商品価格+送料)/0.3',
			'タイプ① 商品価格/0.3',
			'タイプ② (商品価格+送料)/0.35',
			'タイプ③ ①と②を比べて金額が大きい方を選択',
		);
		?>
		<form>
			<input type="hidden" name="action" value="<?=NENG_DB_TABLENAME?>">
			<input type="hidden" name="judge" value="option">
			<p class="textarea-wrap">計算式タイプ：
				<select name="<?=NENG_DB_TABLENAME?>[price][formula]">
					<?php foreach($calc as $k=>$v):?>
					<option value="<?=$k?>"<?=selected(NENG_OPTION['price']['formula'], $k, false)?>><?=$v?></option>
					<?php endforeach;?>
				</select>
			</p>
			<p class="textarea-wrap">
				送料（改行と：区切）：
				<textarea name="<?=NENG_DB_TABLENAME?>[price][delivery]" rows="7" style="overflow-x: hidden;"><?=NENG_OPTION['price']['delivery']?></textarea>
			</p>
			<p class="textarea-wrap">
				クール加算（改行と：区切）：
				<textarea name="<?=NENG_DB_TABLENAME?>[price][cool]" rows="4" style="overflow-x: hidden;"><?=NENG_OPTION['price']['cool']?></textarea>
			</p>
			<input type="submit" class="button button-primary sissubmit" value="　更新する　">
		</form>
		<?php
	}
	
	# 楽天設定ウィジェット（機能はajaxで$judge=="option"）
	public function rakuten_setup_widget(){
		?>
		<form>
			<input type="hidden" name="action" value="<?=NENG_DB_TABLENAME?>">
			<input type="hidden" name="judge" value="option">
			<input type="hidden" name="<?=NENG_DB_TABLENAME?>[rakuten][ftp_server]" value="<?=NENG_OPTION['rakuten']['ftp_server']?>">
			<input type="hidden" name="<?=NENG_DB_TABLENAME?>[rakuten][ftp_server_port]" value="<?=NENG_OPTION['rakuten']['ftp_server_port']?>">
			<input type="hidden" name="<?=NENG_DB_TABLENAME?>[rakuten][upload_server]" value="<?=NENG_OPTION['rakuten']['upload_server']?>">
			<input type="hidden" name="<?=NENG_DB_TABLENAME?>[rakuten][upload_server_port]" value="<?=NENG_OPTION['rakuten']['upload_server_port']?>">
			<p class="input-text-wrap">
				FTPユーザー：
				<input type="text" name="<?=NENG_DB_TABLENAME?>[rakuten][ftp_user]" value="<?=NENG_OPTION['rakuten']['ftp_user']?>">
			</p>
			<p class="input-text-wrap">
				FTPパスワード：
				<input type="text" name="<?=NENG_DB_TABLENAME?>[rakuten][ftp_pass]" value="<?=NENG_OPTION['rakuten']['ftp_pass']?>">
			</p>
			<p class="input-text-wrap">
				画質（右に行くほど高画質）：
				<input type="range" step="1" min="1" max="100" name="<?=NENG_DB_TABLENAME?>[rakuten][quality]" value="<?=NENG_OPTION['rakuten']['quality']?>">
			</p>
			<p class="textarea-wrap">
				item.csvヘッダー貼付（タブ区切り）：
				<textarea name="<?=NENG_DB_TABLENAME?>[rakuten][item_csv]" rows="1" style="overflow-x: hidden;"><?=NENG_OPTION['rakuten']['item_csv']?></textarea>
			</p>
			<p class="textarea-wrap">
				select.csvヘッダー貼付（タブ区切り）：
				<textarea name="<?=NENG_DB_TABLENAME?>[rakuten][select_csv]" rows="1" style="overflow-x: hidden;"><?=NENG_OPTION['rakuten']['select_csv']?></textarea>
			</p>
			<p class="input-text-wrap">
				商品画像ディレクトリ：
				<input type="text" name="<?=NENG_DB_TABLENAME?>[rakuten][img_dir]" value="<?=NENG_OPTION['rakuten']['img_dir']?>">
			</p>
			<p class="input-text-wrap">
				タグID：
				<input type="text" name="<?=NENG_DB_TABLENAME?>[rakuten][tag_id]" value="<?=NENG_OPTION['rakuten']['tag_id']?>">
			</p>
			<p class="textarea-wrap">
				説明文追加html：
				<textarea name="<?=NENG_DB_TABLENAME?>[rakuten][html]" rows="5" style="overflow-x: hidden;"><?=stripslashes_deep(NENG_OPTION['rakuten']['html'])?></textarea>
			</p>
			<?php for($i=0;$i<5;$i++):?>
			<p class="textarea-wrap">
				項目選択肢（改行区切）※選択肢は最大16文字：
				<textarea name="<?=NENG_DB_TABLENAME?>[rakuten][select][]" rows="5" style="overflow-x: hidden;"><?=NENG_OPTION['rakuten']['select'][$i]?></textarea>
			</p>
			<?php endfor;?>
			<input type="submit" class="button button-primary sissubmit" value="　更新する　">
		</form>
		<?php
	}

	# ふるさとチョイス設定ウィジェット（機能はajaxで$judge=="option"）
	public function choice_setup_widget(){
		?>
		<p>ふるさとチョイスの<a href="<?=NENG_OPTION['choice']['csv_url']?>" target="_blank">サンプルTSV</a>のURL変更があった場合は、ここを変更して下さい。</p>
		<form>
			<input type="hidden" name="action" value="<?=NENG_DB_TABLENAME?>">
			<input type="hidden" name="judge" value="option">
			<p class="input-text-wrap">
				Basic認証ユーザー：
				<input type="text" name="<?=NENG_DB_TABLENAME?>[choice][basic_auth][user]" value="<?=NENG_OPTION['choice']['basic_auth']['user']?>">
			</p>
			<p class="input-text-wrap">
				Basic認証パスワード：
				<input type="text" name="<?=NENG_DB_TABLENAME?>[choice][basic_auth][pass]" value="<?=NENG_OPTION['choice']['basic_auth']['pass']?>">
			</p>
			<p class="input-text-wrap">
				サンプルTSVのURL：
				<input type="text" name="<?=NENG_DB_TABLENAME?>[choice][csv_url]" value="<?=NENG_OPTION['choice']['csv_url']?>">
			</p>
			<input type="submit" class="button button-primary sissubmit" value="　更新する　">
		</form>
		<?php
	}

	# Ledg HOME 設定ウィジェット（機能はajaxで$judge=="option"）
	public function ledghome_setup_widget(){
		?>
		<form>
			<input type="hidden" name="action" value="<?=NENG_DB_TABLENAME?>">
			<input type="hidden" name="judge" value="option">
			<p class="textarea-wrap">
				レジホームCSVヘッダーを貼付（タブ区切り）：
				<textarea name="<?=NENG_DB_TABLENAME?>[ledghome][csv_header]" rows="1" style="overflow-x: hidden;"><?=NENG_OPTION['ledghome']['csv_header']?></textarea>
			</p>
			<p>
				<label for="lh_normal"><input type="checkbox" name="<?=NENG_DB_TABLENAME?>[ledghome][normal]" value="1" <?=checked(@NENG_OPTION['ledghome']['normal'],1,false)?> id="lh_normal">LH通常版</label>
			</p>
			<p>
				<label>
					<input type="checkbox" name="<?=NENG_DB_TABLENAME?>[ledghome][souryouhanei]" value="1" <?=checked(@NENG_OPTION['ledghome']['souryouhanei'],1,false)?>> 請求書にヤマト運輸の送料反映する
				</label>
			</p>
			<p>
				<label>
					<input type="checkbox" name="<?=NENG_DB_TABLENAME?>[ledghome][teikiprice]" value="1" <?=checked(@NENG_OPTION['ledghome']['teikiprice'],1,false)?>> 定期便1回目に商品価格総額を登録する
				</label>
			</p>
			<input type="submit" class="button button-primary sissubmit" value="　更新する　">
		</form>
		<?php
	}

	# ANA 設定ウィジェット（機能はajaxで$judge=="option"）
	public function ana_setup_widget(){
		?>
		<form>
			<input type="hidden" name="action" value="<?=NENG_DB_TABLENAME?>">
			<input type="hidden" name="judge" value="option">
			<p class="textarea-wrap">
				ANA CSVヘッダーを貼付（タブ区切り）：
				<textarea name="<?=NENG_DB_TABLENAME?>[ana][csv_header]" rows="1" style="overflow-x: hidden;"><?=NENG_OPTION['ana']['csv_header']?></textarea>
			</p>
			<input type="submit" class="button button-primary sissubmit" value="　更新する　">
		</form>
		<?php
	}

	# CSV・TSV・XLSのインポートウィジェット（機能はajaxで$judge=="file"）
	public function import_widget(){
		?>
		<form class="sisfile">
			<p>.csv .tsv .xlsに対応</p>
			<p><input name="file" type="file"></p>
			<p><input type="submit" class="button button-primary" value="　インポートする　"></p>
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
