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
	 * 自身のクラス名を格納
	 *
	 * @var string
	 */
	/**
	 * 　管理画面に各種セットアップメニュー追加
	 */
	public function __construct() {
		$this->cls = get_class( $this );
		add_action( 'admin_menu', array( $this, 'add_setup_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'setup_menu_style' ) );
		add_action( "wp_ajax_{$this->cls}", array( &$this, 'update_setupmenu' ) );
	}
	/**
	 * ajaxでDBに登録する
	 *
	 * @return void
	 */
	public function update_setupmenu() {
		// $url_parse      = explode( '/', get_option( 'home' ) );
		// $town_name      = end( $url_parse ); // urlから自治体を取得
		$opt = get_option( $this->cls );
		extract( $_POST );
		// $common_yaml = get_theme_file_path( '/config/n2-towninfo.yml' );
		// $plugins_yaml_url = WP_PLUGIN_DIR . '/n2-' . $town_name . '/config/n2-setup-menu.yml';
		// $n2_setupmenu_common = array_slice( $N2_Setupmenu[ rakuten ], 0, 4, true );
		// $n2_setupmenu_personal_original = array_slice( $N2_Setupmenu[ rakuten ], 4, count( $N2_Setupmenu[ rakuten ] ), true );
		// $array_item_csv = explode( '	', $n2_setupmenu_personal_original['item_csv'] );
		// $array_select_csv = explode( '	', $n2_setupmenu_personal_original['select_csv'] );
		// $n2_setupmenu_personal['rakuten']['ftp']['user'] = $n2_setupmenu_personal_original['ftp_user'];
		// $n2_setupmenu_personal['rakuten']['ftp']['pass'] = $n2_setupmenu_personal_original['ftp_pass'];
		// $n2_setupmenu_personal['rakuten']['item_csv_header'] = $array_item_csv;
		// $n2_setupmenu_personal['rakuten']['select_csv_header'] = $array_select_csv;
		// $n2_setupmenu_personal['rakuten']['img_dir'] = $n2_setupmenu_personal_original['img_dir'];
		// $n2_setupmenu_personal['rakuten']['tag_id'] = $n2_setupmenu_personal_original['tag_id'];
		// $n2_setupmenu_personal['rakuten']['html'] = $n2_setupmenu_personal_original['html'];
		// for ( $i = 0; $i < 5; $i++ ) {
		// 	$array_select_original = str_replace( array( "\r\n", "\r", "\n" ), "\n", $n2_setupmenu_personal_original['select'][$i] );
		// 	$array_select = explode( "\n", $array_select_original );
		// 	$array_select_title = array_slice( $array_select, 0, 1, true );
		// 	$array_select_title_nono = str_replace( $i + 1 . '.', '', $array_select_title[0] );
		// 	$array_select_selector = array_slice( $array_select, 1, count( $array_select ), false );
		// 	$n2_setupmenu_personal['rakuten']['項目選択肢（改行区切）'][$i + 1]['内容'] = $array_select_title_nono;
		// 	if ( count( $array_select ) <= 1 ) {
		// 		$array_select_selector = '';
		// 	}
		// 	$n2_setupmenu_personal['rakuten']['項目選択肢（改行区切）'][$i + 1]['選択肢'] = $array_select_selector;
		// }
		// if ( yaml_emit_file( $write_common_yaml, $n2_setupmenu_common ) ) {
		// 	echo 'cyml成功';
		// } else {
		// 	echo 'cyml失敗';
		// }
		// if ( yaml_emit_file( $write_plugins_yaml_url, $n2_setupmenu_personal, $encoding = YAML_UTF8_ENCODING ) ) {
		// 	echo 'pyml成功';
		// } else {
		// 	echo 'pyml失敗';
		// }
		$opt = array_merge( (array) $opt, ${$this->cls} );
		echo update_option( $this->cls, $opt ) ? '登録完了' : '登録失敗';
		die();
	}
	/**
	 * add_setup_menu
	 * クルー用セットアップ管理ページを追加
	 */
	public function add_setup_menu() {
		add_menu_page( '各種セットアップ', '各種セットアップ', 'ss_crew', 'n2_setup_menu', array( $this, 'add_crew_setup_menu_page' ), 'dashicons-list-view' );
	}
	/**
	 * クルー用メニュー描画
	 *
	 * @return void
	 */
	public function add_crew_setup_menu_page() {
		$this->wrapping_contents( '事業者連絡先', 'contact_setup_menu' );
		$this->wrapping_contents( '各ポータル共通説明文', 'add_text_widget' );
		$this->wrapping_contents( '楽天セットアップ', 'rakuten_setup_widget' ); // 必要？
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
	 * 説明文追加html等
	 *
	 * @return void
	 */
	public function rakuten_setup_widget() {

		$url_parse      = explode( '/', get_option( 'home' ) );
		$town_name      = end( $url_parse ); // urlから自治体を取得
		$common_yaml = get_theme_file_path( '/config/n2-towninfo.yml' );
		$plugins_yaml = WP_PLUGIN_DIR . '/n2-' . $town_name . '/config/n2-setup-menu.yml';
		$common_yaml_array = yaml_parse_file($common_yaml);
		$plugins_yaml_array = yaml_parse_file($plugins_yaml);
		$item_csv = implode('	', $plugins_yaml_array['rakuten']['item_csv_header']);
		$select_csv = implode('	', $plugins_yaml_array['rakuten']['select_csv_header']);
		$rakuten_select_array = $plugins_yaml_array['rakuten']['項目選択肢（改行区切）'];
		print_r($rakuten_select_array);

	?>
		<form>
			<input type="hidden" name="action" value="<?php echo $this->cls; ?>">
			<input type="hidden" name="judge" value="option">
			<input type="hidden" name="<?php echo $this->cls; ?>[ rakuten ][ftp_server]" value="<?php echo $common_yaml_array['ftp_server'] ?? 'ftp.rakuten.ne.jp'; ?>" disabled="disabled">
			<input type="hidden" name="<?php echo $this->cls; ?>[ rakuten ][ftp_server_port]" value="<?php echo $common_yaml_array['ftp_server_port'] ?? '16910'; ?>" disabled="disabled">
			<input type="hidden" name="<?php echo $this->cls; ?>[ rakuten ][upload_server]" value="<?php echo $common_yaml_array['upload_server'] ?? 'upload.rakuten.ne.jp'; ?>" disabled="disabled">
			<input type="hidden" name="<?php echo $this->cls; ?>[ rakuten ][upload_server_port]" value="<?php echo $common_yaml_array['upload_server'] ?? '21'; ?>" disabled="disabled">
			<p class="input-text-wrap">
				FTPユーザー：<br>
				<input type="text" name="<?php echo $this->cls; ?>[ rakuten ][ftp_user]" value="<?php echo $plugins_yaml_array['rakuten']['ftp']['user'] ?? ''; ?>" disabled="disabled">
			</p>
			<p class="input-text-wrap">
				FTPパスワード：<br>
				<input type="text" name="<?php echo $this->cls; ?>[ rakuten ][ftp_pass]" value="<?php echo $plugins_yaml_array['rakuten']['ftp']['pass'] ?? ''; ?>">
			</p>
			<p class="input-text-wrap">
				画質（右に行くほど高画質）：<br>
				<input type="range" step="1" min="1" max="100" name="<?php echo $this->cls; ?>[ rakuten ][quality]" value="<?php echo get_option( $this->cls )['rakuten']['quality'] ?? ''; ?>">
			</p>
			<p class="textarea-wrap">
				item.csvヘッダー貼付（タブ区切り）：<br>
				<textarea name="<?php echo $this->cls; ?>[ rakuten ][item_csv]" rows="1" style="overflow-x: hidden;" disabled="disabled"><?php echo $item_csv ?? ''; ?></textarea>
			</p>
			<p class="textarea-wrap">
				select.csvヘッダー貼付（タブ区切り）：<br>
				<textarea name="<?php echo $this->cls; ?>[ rakuten ][select_csv]" rows="1" style="overflow-x: hidden;" disabled="disabled"><?php echo $select_csv ?? ''; ?></textarea>
			</p>
			<p class="input-text-wrap">
				商品画像ディレクトリ：<br>
				<input type="text" name="<?php echo $this->cls; ?>[ rakuten ][img_dir]" value="<?php echo $plugins_yaml_array['rakuten']['img_dir'] ?? ''; ?>" disabled="disabled">
			</p>
			<p class="input-text-wrap">
				タグID：<br>
				<input type="text" name="<?php echo $this->cls; ?>[ rakuten ][tag_id]" value="<?php echo $plugins_yaml_array['rakuten']['tag_id'] ?? ''; ?>" disabled="disabled">
			</p>
			<p class="textarea-wrap">
				<label>
					説明文追加html：<br>
					<textarea name="<?php echo $this->cls; ?>[ rakuten ][html]" rows="5" style="overflow-x: hidden;" disabled="disabled"><?php echo $plugins_yaml_array['rakuten']['html'] ?? '' ; ?></textarea>
				</label>
			</p>
			<?php foreach ( $rakuten_select_array as $key => $rakuten_select ) : ?>
			<p class="textarea-wrap">
				<label>
					項目選択肢（改行区切）※選択肢は最大16文字：<br>
					<textarea name="<?php echo $this->cls; ?>[ rakuten ][select][]" rows="5" style="overflow-x: hidden;" disabled="disabled"><?php 
					echo $rakuten_select_array[$key]['内容'] ? $key . '.' . $rakuten_select_array[$key]['内容'] . "\n" : "\n";
					echo $rakuten_select_array[$key]['選択肢'] ? implode("\n", $rakuten_select_array[$key]['選択肢']) : '';
					?></textarea>
				</label>
			</p>
			<?php endforeach; ?>
			<input type="submit" class="button button-primary sissubmit" value="　更新する　">
		</form>
		<?php
	}
	/**
	 * 各種セットアップの各項目ラッピングして表示を整える
	 *
	 * @param string $header 見出し
	 * @param string $function_name 関数名
	 * @return void
	 */
	public function wrapping_contents( $header, $function_name ) {
		?>
		<div>
			<div class="postbox-header">
				<h2><?php echo $header; ?></h2>
			</div>
			<div class="inside">
				<?php $this->{$function_name}(); ?>
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
		wp_enqueue_style( 'n2-setupmenu', get_theme_file_uri() . '/dist/setupmenu.css', array(), wp_get_theme()->get( 'Version' ) );
	}
}
