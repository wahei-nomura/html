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
		add_action( 'wp_ajax_n2_municipal_importer', array( &$this, 'output_n2_municipal_importer' ) );
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
		add_menu_page( '各種セットアップ', 'n2_setup_menus', 'ss_crew', 'n2_crew_setup_menu', array( $this, 'add_crew_setup_menu_page' ), 'dashicons-list-view', 90 );
		// エンジニア専用サブメニュー
		add_submenu_page( 'n2_crew_setup_menu', 'エンジニア専用', 'エンジニア専用', 'administrator', 'n2_engineer_setup_menu', array( $this, 'add_engineer_setup_submenu_page' ) );
	}
	/**
	 * クルー用メニュー描画
	 *
	 * @return void
	 */
	public function add_crew_setup_menu_page() {
		$this->wrapping_contents( 'クルー専用セットアップ', 'contact_setup_menu' );
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
		 <main class="p-3">
	  <!-- 4個分のタブ -->
	  <ul class="nav nav-pills" role="tablist">
		<li class="nav-item" role="presentation">
		  <a href="#set-menu1" id="tab1" class="nav-link active" role="tab" data-bs-toggle="tab" aria-selected="true">事業者連絡先</a>
		</li>
		<li class="nav-item" role="presentation">
		  <a href="#set-menu2" id="tab2" class="nav-link" role="tab" data-bs-toggle="tab" aria-selected="false">各ポータル共通説明文</a>
		</li>
		<li class="nav-item" role="presentation">
		  <a href="#set-menu3" id="tab3" class="nav-link" role="tab" data-bs-toggle="tab" aria-selected="false">送料</a>
		</li>
		<li class="nav-item" role="presentation">
		  <a href="#set-menu4" id="tab4" class="nav-link" role="tab" data-bs-toggle="tab" aria-selected="false">楽天セットアップ</a>
		</li>
	  </ul>

	  <!-- 写真部分 -->
	  <div class="tab-content">
		<div id="set-menu1" class="tab-pane fade show active" role="tabpanel" aria-labelledby="tab1">
		<form>
			<p>事業者さまとのやりとりに使用するメールアドレス・電話番号を記入してください。</p>
			<input type="hidden" name="action" value="<?php echo $this->cls; ?>">
			<input type="hidden" name="judge" value="option">
			<div class="mb-3 col-md-4">
				<label for="input-email" class="form-label">メールアドレス</label>
				<input type="email" class="form-control" id="input-email" name="<?php echo $this->cls; ?>[contact][email]" value="<?php echo get_option( $this->cls )['contact']['email'] ?? ''; ?>">
			</div>
			<div class="mb-3 col-md-4">
				<label for="input-tel" class="form-label">電話番号</label>
				<input type="tel" class="form-control" id="input-tel" name="<?php echo $this->cls; ?>[contact][tel]" value="<?php echo get_option( $this->cls )['contact']['tel'] ?? ''; ?>">
			</div>
			<input type="submit" class="btn btn-primary sissubmit" value="更新する">
		</form>
		</div>
		<div id="set-menu2" class="tab-pane fade" role="tabpanel" aria-labelledby="tab2">
		<form>
			<p>各ポータル共通で使用する文末に追加したいテキストを記入してください。</p>
			<input type="hidden" name="action" value="<?php echo $this->cls; ?>">
			<input type="hidden" name="judge" value="option">
			<div class="mb-3">
				<label for="add-text" class="form-label">商品説明文の文末に追加したいテキスト</label>
				<textarea class="form-control" id="add-text" name="<?php echo $this->cls; ?>[add_text][<?php echo get_bloginfo( 'name' ); ?>]" rows="7" style="overflow-x: hidden;"><?php echo get_option( $this->cls )['add_text'][ get_bloginfo( 'name' ) ] ?? ''; ?></textarea>
			</div>
			<input type="submit" class="btn btn-primary sissubmit" value="更新する">
		</form>
		
		</div>
		<div id="set-menu3" class="tab-pane fade" role="tabpanel" aria-labelledby="tab3">
		<form>
		<p>「クール加算」は自動計算されるため設定不要です。</p>
			<div class="d-flex">
			<div class="input-half input-left flex-fill">
				<input type="hidden" name="action" value="<?php echo $this->cls; ?>">
				<input type="hidden" name="judge" value="option">
				<p class="input-header" style="font-weight:bold">計算式タイプ</p>
				<div class="mb-3">
				<select name="<?php echo $this->cls; ?>[formula_type]" class="form-select">
					<option value="零号機" <?php echo ! empty( get_option( $this->cls )['formula_type'] ) && get_option( $this->cls )['formula_type'] === '零号機' ? 'selected' : ''; ?>>タイプ⓪ (商品価格+送料)/0.3</option>
					<option value="初号機" <?php echo ! empty( get_option( $this->cls )['formula_type'] ) && get_option( $this->cls )['formula_type'] === '初号機' ? 'selected' : ''; ?>>タイプ① 商品価格/0.3</option>
					<option value="弐号機" <?php echo ! empty( get_option( $this->cls )['formula_type'] ) && get_option( $this->cls )['formula_type'] === '弐号機' ? 'selected' : ''; ?>>タイプ② (商品価格+送料)/0.35</option>
					<option value="使徒" <?php echo ! empty( get_option( $this->cls )['formula_type'] ) && get_option( $this->cls )['formula_type'] === '使徒' ? 'selected' : ''; ?>>タイプ③ ①と②を比べて金額が大きい方を選択</option>
					<option value="十三号機" <?php echo ! empty( get_option( $this->cls )['formula_type'] ) && get_option( $this->cls )['formula_type'] === '十三号機' ? 'selected' : ''; ?>>その他</option>
				</select>
				</div>
				<p class="input-header" style="font-weight:bold">送料</p>
				<div class="mb-3 col-md-3">
					<label for="input-postage-0101" class="form-label">60サイズ【必須】</label>
					<input type="number" class="form-control" id="input-postage-0101" name="<?php echo $this->cls; ?>[delivery_fee][0101]" value="<?php echo get_option( $this->cls )['delivery_fee']['0101'] ?? ''; ?>" required>
				</div>
				<div class="mb-3 col-md-3">
					<label for="input-postage-0102" class="form-label">80サイズ【必須】</label>
					<input type="number" class="form-control" id="input-postage-0102" name="<?php echo $this->cls; ?>[delivery_fee][0102]" value="<?php echo get_option( $this->cls )['delivery_fee']['0102'] ?? ''; ?>" required>
				</div>
				<div class="mb-3 col-md-3">
					<label for="input-postage-0103" class="form-label">100サイズ【必須】</label>
					<input type="number" class="form-control" id="input-postage-0103" name="<?php echo $this->cls; ?>[delivery_fee][0103]" value="<?php echo get_option( $this->cls )['delivery_fee']['0103'] ?? ''; ?>" required>
				</div>
				<div class="mb-3 col-md-3">
					<label for="input-postage-0104" class="form-label">120サイズ【必須】</label>
					<input type="number" class="form-control" id="input-postage-0104" name="<?php echo $this->cls; ?>[delivery_fee][0104]" value="<?php echo get_option( $this->cls )['delivery_fee']['0104'] ?? ''; ?>" required>
				</div>
				<div class="mb-3 col-md-3">
					<label for="input-postage-0105" class="form-label">140サイズ【必須】</label>
					<input type="number" class="form-control" id="input-postage-0105" name="<?php echo $this->cls; ?>[delivery_fee][0105]" value="<?php echo get_option( $this->cls )['delivery_fee']['0105'] ?? ''; ?>" required>
				</div>
				<div class="mb-3 col-md-3">
					<label for="input-postage-0106" class="form-label">160サイズ【必須】</label>
					<input type="number" class="form-control" id="input-postage-0106" name="<?php echo $this->cls; ?>[delivery_fee][0106]" value="<?php echo get_option( $this->cls )['delivery_fee']['0106'] ?? ''; ?>" required>
				</div>
				<div class="mb-3 col-md-3">
					<label for="input-postage-0107" class="form-label">180サイズ【必須】</label>
					<input type="number" class="form-control" id="input-postage-0107" name="<?php echo $this->cls; ?>[delivery_fee][0107]" value="<?php echo get_option( $this->cls )['delivery_fee']['0107'] ?? ''; ?>" required>
				</div>
				<div class="mb-3 col-md-3">
					<label for="input-postage-0108" class="form-label">200サイズ【必須】</label>
					<input type="number" class="form-control" id="input-postage-0108" name="<?php echo $this->cls; ?>[delivery_fee][0108]" value="<?php echo get_option( $this->cls )['delivery_fee']['0108'] ?? ''; ?>" required>
				</div>
			</div>
			<div class="input-half input-right flex-fill">
			<p class="input-header" style="font-weight:bold">レターパック(使用するものをチェックしてください)</p>
			<div class="form-check">
				<input type="checkbox" id="flexCheckDefault" name="<?php echo $this->cls; ?>[delivery_fee][レターパックライト]" value="370" <?php echo isset( get_option( $this->cls )['delivery_fee']['レターパックライト'] ) && get_option( $this->cls )['delivery_fee']['レターパックライト'] ? 'checked' : ''; ?>>
				<label class="form-check-label <?php print_r(get_option( $this->cls )['delivery_fee']); ?>" for="flexCheckDefault">
					レターパックライト
				</label>
				</div>
				<div class="form-check">
				<input type="checkbox" id="flexCheckChecked" name="<?php echo $this->cls; ?>[delivery_fee][レターパックプラス]" value="520" <?php echo isset( get_option( $this->cls )['delivery_fee']['レターパックプラス'] ) && get_option( $this->cls )['delivery_fee']['レターパックプラス'] ? 'checked' : ''; ?>>
				<label class="form-check-label" for="flexCheckChecked">
					レターパックプラス
				</label>
			</div>
			</div>
			</div>
			<input type="submit" class="btn btn-primary sissubmit" value="更新する">
		</form>
		</div>
		<div id="set-menu4" class="tab-pane fade" role="tabpanel" aria-labelledby="tab4">
		<form>
			<input type="hidden" name="action" value="<?php echo $this->cls; ?>">
			<input type="hidden" name="judge" value="option">
			<div class="mb-3 col-md-4">
				<label for="input-ftp_user" class="form-label">FTPユーザー</label>
				<input type="text" class="form-control" id="input-ftp_user" name="<?php echo $this->cls; ?>[rakuten][ftp_user]" value="<?php echo get_option( $this->cls )['rakuten']['ftp_user'] ?? ''; ?>">
			</div>
			<div class="mb-3 col-md-4">
				<label for="input-ftp_pass" class="form-label">FTPパスワード</label>
				<input type="text" class="form-control" id="input-ftp_pass" name="<?php echo $this->cls; ?>[rakuten][ftp_pass]" value="<?php echo get_option( $this->cls )['rakuten']['ftp_pass'] ?? ''; ?>">
			</div>
			<div class="mb-3 col-md-4">
				<label for="input-quality" class="form-label">画質（右に行くほど高画質）</label>
				<input type="range" class="form-range" id="input-quality" name="<?php echo $this->cls; ?>[rakuten][quality]" value="<?php echo get_option( $this->cls )['rakuten']['quality'] ?? ''; ?>">
			</div>
			<div class="mb-3">
				<label for="input-item_csv" class="form-label">item.csvヘッダー貼付（タブ区切り）</label>
				<textarea class="form-control" id="input-item_csv" name="<?php echo $this->cls; ?>[rakuten][item_csv]" rows="2" style="overflow-x: hidden;"><?php echo get_option( $this->cls )['rakuten']['item_csv'] ?? ''; ?></textarea>
			</div>
			<div class="mb-3">
				<label for="input-select_csv" class="form-label">select.csvヘッダー貼付（タブ区切り）</label>
				<textarea class="form-control" id="input-select_csv" name="<?php echo $this->cls; ?>[rakuten][select_csv]" rows="2" style="overflow-x: hidden;"><?php echo get_option( $this->cls )['rakuten']['select_csv'] ?? ''; ?></textarea>
			</div>
			<div class="mb-3 col-md-4">
				<label for="input-img_dir" class="form-label">商品画像ディレクトリ</label>
				<input type="text" class="form-control" id="input-img_dir" name="<?php echo $this->cls; ?>[rakuten][img_dir]" value="<?php echo get_option( $this->cls )['rakuten']['img_dir'] ?? ''; ?>">
			</div>
			<div class="mb-3">
				<label for="input-tag_id" class="form-label">タグID</label>
				<input type="text" class="form-control" id="input-tag_id" name="<?php echo $this->cls; ?>[rakuten][tag_id]" value="<?php echo get_option( $this->cls )['rakuten']['tag_id'] ?? ''; ?>">
			</div>
			<div class="mb-3">
				<label for="input-html" class="form-label">説明文追加html ※商品説明文の最後に共通で追加される文言を設定できます(タグ使用可能)</label>
				<textarea class="form-control" id="input-html" name="<?php echo $this->cls; ?>[rakuten][html]" rows="5" style="overflow-x: hidden;"><?php echo get_option( $this->cls )['rakuten']['html'] ?? ''; ?></textarea>
			</div>
			<p class="input-header" style="font-weight:bold">項目選択肢※寄附申込み前に表示する選択肢を最大6パターン作成できます。1行目：説明　2行目以降：選択肢</p>
			<?php for ( $i = 0;$i < 6;$i++ ) : ?>
				<div class="mb-3">
					<label for="input-select-<?php echo $i; ?>" class="form-label">項目選択肢（改行区切）※選択肢は最大16文字</label>
					<textarea class="form-control" id="input-select-<?php echo $i; ?>" name="<?php echo $this->cls; ?>[rakuten][select][]" rows="5" style="overflow-x: hidden;"><?php echo get_option( $this->cls )['rakuten']['select'][ $i ] ?? ''; ?></textarea>
				</div>
			<?php endfor; ?>
			<input type="submit" class="btn btn-primary sissubmit" value="更新する">
		</form>
		</div>
	  </div>
	</main>

		
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
	public function output_n2_municipal_importer() {
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
		<div class="postbox-wrap">
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
		<input type="submit" class="button button-primary sissubmit" value="更新する">
		</form>
		<?php
	}
}
