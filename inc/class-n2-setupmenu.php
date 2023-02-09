<?php
/**
 * class-n2-setupmenu.php
 *
 * @package neoneng
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'N2_Setupmenu' ) ) {
	new N2_Setupmenu();
	return;
}

/**
 * 各種セットアップ共通用
 */
class N2_Setupmenu {
	/**
	 * 自身のクラス名を格納
	 *
	 * @var string
	 */
	private $cls;

	/**
	 * 自治体インポーター取り込み時のkey
	 * $_FILE[$this->importer]
	 *
	 * @var string
	 */
	private $importer = 'importer';
	/**
	 * 　管理画面に各種セットアップメニュー追加
	 */
	public function __construct() {
		$this->cls = get_class( $this );
		add_action( 'admin_menu', array( $this, 'add_setup_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'setup_menu_style' ) );
		add_action( "wp_ajax_{$this->cls}", array( &$this, 'update_setupmenu' ) );
		add_action( "wp_ajax_n2_municipal_importer", array( &$this, 'output_n2_municipal_importer' ) );
	}
	/**
	 * ajaxでDBに登録する
	 *
	 * @return void
	 */
	public function update_setupmenu() {
		extract( $_POST );
		$opt = ${$this->cls} ?? array();
		if ( get_option( $this->cls ) ) {
			$opt = array( ...get_option( $this->cls ), ...$opt );
		}
		echo update_option( $this->cls, $opt ) ? '登録完了' : '登録失敗';
		die();
	}
	/**
	 * add_setup_menu
	 */
	public function add_setup_menu() {
		// 各種セットアップ管理ページを追加
		add_menu_page( '各種セットアップ', '各種セットアップ', 'ss_crew', 'n2_setup_menu', array( $this, 'add_crew_setup_menu_page' ), 'dashicons-list-view' );

		// エンジニア専用サブメニュー
		add_submenu_page( 'n2_setup_menu', 'エンジニア専用', 'エンジニア専用', 'administrator', 'n2_engineer_submenu', array( $this, 'add_engineer_setup_submenu_page' ) );
	}
	/**
	 * クルー用メニュー描画
	 *
	 * @return void
	 */
	public function add_crew_setup_menu_page() {
		$this->wrapping_contents( '事業者連絡先', 'contact_setup_menu' );
		$this->wrapping_contents( '各ポータル共通説明文', 'add_text_widget' );
		$this->wrapping_contents( '送料', 'add_postage_widget' );
		$this->wrapping_contents( '楽天セットアップ', 'rakuten_setup_widget' ); // 必要？
	}
	/**
	 * エンジニア専用サブメニュー描画
	 *
	 * @return void
	 */
	public function add_engineer_setup_submenu_page() {
		$this->wrapping_contents( '自治体インポーター', 'add_importer_widget', array( 'wp_ajax' => 'n2_municipal_importer' ) );
		$this->wrapping_contents( '自治体コード', 'towncode_setup_widget' );
	}

	/**
	 * 事業者連絡先
	 *
	 * @return void
	 */
	public function contact_setup_menu() {
		?>
		<form>
			<p>事業者さまとのやりとりに使用するメールアドレス・電話番号を記入してください。</p>
			<input type="hidden" name="action" value="<?php echo $this->cls; ?>">
			<input type="hidden" name="judge" value="option">
			<p class="input-text-wrap">
				<label>
					メールアドレス：
					<input type="text" name="<?php echo $this->cls; ?>[contact][email]" value="<?php echo get_option( $this->cls )['contact']['email'] ?? ''; ?>">
				</label>
			</p>
			<p class="input-text-wrap">
				<label>
					電話番号：
					<input type="text" name="<?php echo $this->cls; ?>[contact][tel]" value="<?php echo get_option( $this->cls )['contact']['tel'] ?? ''; ?>">
				</label>
			</p>
			<input type="submit" class="button button-primary sissubmit" value="　更新する　">
		</form>
		<?php
	}
	/**
	 * 各ポータル共通説明文
	 *
	 * @return void
	 */
	public function add_text_widget() {
		?>
		<form>
			<input type="hidden" name="action" value="<?php echo $this->cls; ?>">
			<input type="hidden" name="judge" value="option">
			<p class="textarea-wrap">
				<label>
					商品説明文の文末に追加したいテキスト：
					<textarea name="<?php echo $this->cls; ?>[add_text][<?php echo get_bloginfo( 'name' ); ?>]" rows="7" style="overflow-x: hidden;"><?php echo get_option( $this->cls )['add_text'][ get_bloginfo( 'name' ) ] ?? ''; ?></textarea>
				</label>
			</p>
			<input type="submit" class="button button-primary sissubmit" value="　更新する　">
		</form>
		<?php
	}
	/**
	 * 送料設定
	 *
	 * @return void
	 */
	public function add_postage_widget() {
		?>
		<form>
			<input type="hidden" name="action" value="<?php echo $this->cls; ?>">
			<input type="hidden" name="judge" value="option">
			<p class="input-header" style="font-weight:bold">計算式タイプ</p>
			<select name="<?php echo $this->cls; ?>[formula_type]">
				<option value="零号機" <?php echo !empty( get_option( $this->cls )['formula_type'] ) && get_option( $this->cls )['formula_type'] === "零号機" ? 'selected' : ''; ?>>タイプ⓪ (商品価格+送料)/0.3</option>
				<option value="初号機" <?php echo !empty( get_option( $this->cls )['formula_type'] ) && get_option( $this->cls )['formula_type'] === "初号機" ? 'selected' : ''; ?>>タイプ① 商品価格/0.3</option>
				<option value="弐号機" <?php echo !empty( get_option( $this->cls )['formula_type'] ) && get_option( $this->cls )['formula_type'] === "弐号機" ? 'selected' : ''; ?>>タイプ② (商品価格+送料)/0.35</option>
				<option value="使徒" <?php echo !empty( get_option( $this->cls )['formula_type'] ) && get_option( $this->cls )['formula_type'] === "使徒" ? 'selected' : ''; ?>>タイプ③ ①と②を比べて金額が大きい方を選択</option>
				<option value="十三号機" <?php echo !empty( get_option( $this->cls )['formula_type'] ) && get_option( $this->cls )['formula_type'] === "十三号機" ? 'selected' : ''; ?>>その他</option>
			</select>
			<p class="input-header" style="font-weight:bold">送料</p>
			<p class="input-text-wrap">
				60サイズ【必須】：
				<input type="number" name="<?php echo $this->cls; ?>[delivery_fee][0101]" value="<?php echo get_option( $this->cls )['delivery_fee']['0101'] ?? ''; ?>" required>
			</p>
			<p class="input-text-wrap">
				80サイズ【必須】：
				<input type="number" name="<?php echo $this->cls; ?>[delivery_fee][0102]" value="<?php echo get_option( $this->cls )['delivery_fee']['0102'] ?? ''; ?>" required>
			</p>
			<p class="input-text-wrap">
				100サイズ【必須】：
				<input type="number" name="<?php echo $this->cls; ?>[delivery_fee][0103]" value="<?php echo get_option( $this->cls )['delivery_fee']['0103'] ?? ''; ?>" required>
			</p>
			<p class="input-text-wrap">
				120サイズ【必須】：
				<input type="number" name="<?php echo $this->cls; ?>[delivery_fee][0104]" value="<?php echo get_option( $this->cls )['delivery_fee']['0104'] ?? ''; ?>" required>
			</p>
			<p class="input-text-wrap">
				140サイズ【必須】：
				<input type="number" name="<?php echo $this->cls; ?>[delivery_fee][0105]" value="<?php echo get_option( $this->cls )['delivery_fee']['0105'] ?? ''; ?>" required>
			</p>
			<p class="input-text-wrap">
				160サイズ【必須】：
				<input type="number" name="<?php echo $this->cls; ?>[delivery_fee][0106]" value="<?php echo get_option( $this->cls )['delivery_fee']['0106'] ?? ''; ?>" required>
			</p>
			<p class="input-text-wrap">
				180サイズ【必須】：
				<input type="number" name="<?php echo $this->cls; ?>[delivery_fee][0107]" value="<?php echo get_option( $this->cls )['delivery_fee']['0107'] ?? ''; ?>" required>
			</p>
			<p class="input-text-wrap">
				200サイズ【必須】：
				<input type="number" name="<?php echo $this->cls; ?>[delivery_fee][0108]" value="<?php echo get_option( $this->cls )['delivery_fee']['0108'] ?? ''; ?>" required>
			</p>
			<p class="input-header">レターパック(使用するものをチェックしてください)</p>
			<p class="input-text-wrap">
				レターパックライト：
				<input type="checkbox" name="<?php echo $this->cls; ?>[delivery_fee][レターパックライト]" value="370" <?php echo isset( get_option( $this->cls )['delivery_fee']['レターパックライト'] ) && get_option( $this->cls )['delivery_fee']['レターパックライト'] ? 'checked' : ''; ?>>
			</p>
			<p class="input-text-wrap">
				レターパックプラス：
				<input type="checkbox" name="<?php echo $this->cls; ?>[delivery_fee][レターパックプラス]" value="520" <?php echo isset( get_option( $this->cls )['delivery_fee']['レターパックプラス'] ) && get_option( $this->cls )['delivery_fee']['レターパックプラス'] ? 'checked' : ''; ?>>
			</p>

			<p class="input-header" style="font-weight:bold">クール加算</p>
			<p class="input-text-wrap">
				60サイズ【必須】：
				<input type="number" name="<?php echo $this->cls; ?>[delivery_fee][0101_cool]" value="<?php echo get_option( $this->cls )['delivery_fee']['0101_cool'] ?? ''; ?>" required>
			</p>
			<p class="input-text-wrap">
				80サイズ【必須】：
				<input type="number" name="<?php echo $this->cls; ?>[delivery_fee][0102_cool]" value="<?php echo get_option( $this->cls )['delivery_fee']['0102_cool'] ?? ''; ?>" required>
			</p>
			<p class="input-text-wrap">
				100サイズ【必須】：
				<input type="number" name="<?php echo $this->cls; ?>[delivery_fee][0103_cool]" value="<?php echo get_option( $this->cls )['delivery_fee']['0103_cool'] ?? ''; ?>" required>
			</p>
			<p class="input-text-wrap">
				120サイズ【必須】：
				<input type="number" name="<?php echo $this->cls; ?>[delivery_fee][0104_cool]" value="<?php echo get_option( $this->cls )['delivery_fee']['0104_cool'] ?? ''; ?>" required>
			</p>
			<input type="submit" class="button button-primary sissubmit" value="　更新する　">
		</form>
		<?php
	}
	/**
	 * 説明文追加html等
	 *
	 * @return void
	 */
	public function rakuten_setup_widget() {
		?>
		<form>
			<input type="hidden" name="action" value="<?php echo $this->cls; ?>">
			<input type="hidden" name="judge" value="option">
			<p class="input-text-wrap">
				FTPユーザー：<br>
				<input type="text" name="<?php echo $this->cls; ?>[rakuten][ftp_user]" value="<?php echo get_option( $this->cls )['rakuten']['ftp_user'] ?? ''; ?>">
			</p>
			<p class="input-text-wrap">
				FTPパスワード：<br>
				<input type="text" name="<?php echo $this->cls; ?>[rakuten][ftp_pass]" value="<?php echo get_option( $this->cls )['rakuten']['ftp_pass'] ?? ''; ?>">
			</p>
			<p class="input-text-wrap">
				画質（右に行くほど高画質）：<br>
				<input type="range" step="1" min="1" max="100" name="<?php echo $this->cls; ?>[rakuten][quality]" value="<?php echo get_option( $this->cls )['rakuten']['quality'] ?? ''; ?>">
			</p>
			<p class="textarea-wrap">
				item.csvヘッダー貼付（タブ区切り）：<br>
				<textarea name="<?php echo $this->cls; ?>[rakuten][item_csv]" rows="1" style="overflow-x: hidden;"><?php echo get_option( $this->cls )['rakuten']['item_csv'] ?? ''; ?></textarea>
			</p>
			<p class="textarea-wrap">
				select.csvヘッダー貼付（タブ区切り）：<br>
				<textarea name="<?php echo $this->cls; ?>[rakuten][select_csv]" rows="1" style="overflow-x: hidden;"><?php echo get_option( $this->cls )['rakuten']['select_csv'] ?? ''; ?></textarea>
			</p>
			<p class="input-text-wrap">
				商品画像ディレクトリ：<br>
				<input type="text" name="<?php echo $this->cls; ?>[rakuten][img_dir]" value="<?php echo get_option( $this->cls )['rakuten']['img_dir'] ?? ''; ?>">
			</p>
			<p class="input-text-wrap">
				タグID：<br>
				<input type="text" name="<?php echo $this->cls; ?>[rakuten][tag_id]" value="<?php echo get_option( $this->cls )['rakuten']['tag_id'] ?? ''; ?>">
			</p>
			<p class="textarea-wrap">
				<label>
					説明文追加html：<br>
					<textarea name="<?php echo $this->cls; ?>[rakuten][html]" rows="5" style="overflow-x: hidden;"><?php echo stripslashes_deep( get_option( $this->cls )['rakuten']['html'] ?? '' ); ?></textarea>
				</label>
			</p>
			<?php for ( $i = 0;$i < 6;$i++ ) : ?>
			<p class="textarea-wrap">
				<label>
					項目選択肢（改行区切）※選択肢は最大16文字：<br>
					<textarea name="<?php echo $this->cls; ?>[rakuten][select][]" rows="5" style="overflow-x: hidden;"><?php echo get_option( $this->cls )['rakuten']['select'][ $i ] ?? ''; ?></textarea>
				</label>
			</p>
			<?php endfor; ?>
			<input type="submit" class="button button-primary sissubmit" value="　更新する　">
		</form>
		<?php
	}
	/**
	 * ファイル取り込み用
	 *
	 * @param array $args array('wp_ajax' => {wp_ajax_hook} )
	 * @return void
	 */
	public function add_importer_widget( $args ) {
		?>
		<form action="./admin-ajax.php" method="post" enctype="multipart/form-data" target="_blank">
			<input type="hidden" name="action" value="<?php echo $args['wp_ajax']; ?>">
			<p class="input-text-wrap">
				<input type="file" name="<?php echo $this->importer; ?>">
				<input type="submit" class="button button-primary" value="読み込む">
			</p>
		</form>
		<?php
	}
	/**
	 * 自治体インポーター　取り込みテスト用
	 *
	 * @return void
	 */
	public function output_n2_municipal_importer(){
		global $n2;
		if ( $_FILES[ $this->importer ]['error'] ) {
			echo 'error upload !! lol';
			die();
		}
		// とりあえずymlをN2_options
		$manicipal_yaml = yaml_parse_file( $_FILES[ $this->importer ]['tmp_name'] );
		//
		// 登録処理はここに書く
		//
		//
	}

	/**
	 * 各種セットアップの各項目ラッピングして表示を整える
	 *
	 * @param string $header 見出し
	 * @param string $function_name 関数名
	 * @param array  $args 引数
	 * @return void
	 */
	public function wrapping_contents( $header, $function_name, $args = array() ) {
		?>
		<div>
			<div class="postbox-header">
				<h2><?php echo $header; ?></h2>
			</div>
			<div class="inside">
			<?php if ( $args ) : ?>
				<?php $this->{$function_name}( $args ); ?>
			<?php else : ?>
				<?php $this->{$function_name}(); ?>
			<?php endif; ?>
			</div>
		</div>
		<?php
	}
	/**
	 * このクラスで使用するassetsの読み込み
	 *
	 * @return void
	 */
	public function setup_menu_style() {
		// wp_enqueue_style( 'n2-setupmenu', get_theme_file_uri() . '/dist/setupmenu.css', array(), wp_get_theme()->get( 'Version' ) );
	}

	/**
	 *  自治体コード設定
	 */
	public function towncode_setup_widget() {
		global $n2;
		?>
		<form>
		<input type="hidden" name="action" value="<?php echo $this->cls; ?>">
		<input type="hidden" name="judge" value="option">
		<?php foreach ( $n2->town_code as $portal => $town_code ) : ?>
			<p class="input-text-wrap">
				<?php echo $n2->portals[ $portal ]; ?>：<br>
				<input type="text" name="<?php echo $this->cls; ?>[<?php echo $portal; ?>][town_code]" value="<?php echo $town_code; ?>">
			</p>
		<?php endforeach; ?>
		<input type="submit" class="button button-primary sissubmit" value="　更新する　">
		</form>
		<?php
	}
}