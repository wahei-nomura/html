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
		add_action( 'admin_menu', array( $this, 'add_crew_setup_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'setup_menu_style' ) );
		add_action( "wp_ajax_{$this->cls}", array( &$this, 'update_setupmenu' ) );
	}
	/**
	 * ajaxでDBに登録用
	 *
	 * @return void
	 */
	public function update_setupmenu() {
		$opt = get_option( $this->cls );
		extract( $_POST );
		$opt = array_merge( (array) $opt, ${$this->cls} );
		update_option( $this->cls, $opt );
		echo '登録完了';
		die();
	}
	/**
	 * add_setup_menu
	 * クルー用セットアップ管理ページを追加
	 */
	public function add_crew_setup_menu() {
		add_menu_page( '各種セットアップ', '各種セットアップ', 'ss_crew', 'n2_setup_menu', array( $this, 'add_crew_setup_menu_page' ), 'dashicons-list-view' );
	}

	 /**
	  * クルー用メニュー描画
	  *
	  * @return void
	  */
	public function add_crew_setup_menu_page() {
		$crew_menus = array(
			array(
				'header'      => '事業者連絡先',
				'description' => '事業者さまとのやりとりに使用するメールアドレス・電話番号を記入してください。',
				'query1'      => 'contact',
				'input1'      => array(
					'title'      => 'メールアドレス',
					'query2'     => 'email',
					'attributes' => array(
						'type' => 'text',
					),
				),
				'input2'      => array(
					'title'      => '電話番号',
					'query2'     => 'tel',
					'attributes' => array(
						'type' => 'text',
					),
				),
			),
			array(
				'header' => '各ポータル共通説明文',
				'query1' => 'add_text',
				'text1'  => array(
					'title'      => '商品説明文の文末に追加したいテキスト',
					'query2'     => get_bloginfo( 'name' ),
					'attributes' => array(
						'rows'  => '7',
						'style' => 'overflow-x: hidden;',
					),
				),
			),
			array(
				'header' => '楽天セットアップ',
				'query1' => 'rakuten',
				'text1'  => array(
					'title'      => '説明文追加html',
					'attributes' => array(
						'rows'  => '5',
						'style' => 'overflow-x: hidden;',
					),
					'query2'     => 'html',
					'repeat'     => '5',
				),
			),
		);
		$this->wrapping_contents( $crew_menus );
	}

	/**
	 * 各種セットアップの各項目ラッピング用
	 *
	 * @param Array $menus メニュー一覧
	 * @return void
	 */
	public function wrapping_contents( $menus ) {
		foreach ( $menus as $menu ) : ?>
		<div>
			<div class="postbox-header">
				<h2><?php echo $menu['header']; ?></h2>
			</div>
			<div class="inside">
				<?php $this->auto_make_form( $menu ); ?>
			</div>
		</div>
			<?php
		endforeach;
	}
	/**
	 *
	 * form自動生成
	 *
	 * @param Array $menu メニュー内容
	 *
	 * @return void
	 */
	public function auto_make_form( &$menu ) {
		?>
		<form>
			<input type="hidden" name="action" value="<?php echo $this->cls; ?>">
			<input type="hidden" name="judge" value="option">
			<?php if ( 'rakuten' === $menu['query1'] ) : ?>
			<input type="hidden" name="<?php echo $this->cls; ?>[rakuten][ftp_server]" value="<?php echo get_option( $this->cls )['rakuten']['ftp_server']; ?>">
			<input type="hidden" name="<?php echo $this->cls; ?>[rakuten][ftp_server_port]" value="<?php echo get_option( $this->cls )['rakuten']['ftp_server_port']; ?>">
			<input type="hidden" name="<?php echo $this->cls; ?>[rakuten][upload_server]" value="<?php echo get_option( $this->cls )['rakuten']['upload_server']; ?>">
			<input type="hidden" name="<?php echo $this->cls; ?>[rakuten][upload_server_port]" value="<?php echo get_option( $this->cls )['rakuten']['upload_server_port']; ?>">
				<?php
			endif;
			if ( array_key_exists( 'description', $menu ) ) :
				?>
			<p><?php echo $menu['description']; ?></p>
				<?php
			endif;
			?>
			<div class="flex">
			<?php foreach ( $menu as $node ) : ?>
				<div>
					<?php
					switch ( array_search( $node, $menu, true ) ) :
						// input(ckeckbox以外)
						case 1 === preg_match( '/input[1-9]/u', array_search( $node, $menu, true ) ):
							?>
						<label>
							<p><?php echo $node['title']; ?>：</p>
							<input name="<?php echo $this->cls; ?>[<?php echo $menu['query1']; ?>][<?php echo $node['query2']; ?>]" value="<?php echo get_option( $this->cls )[ $menu['query1'] ][ $node['query2'] ]; ?>" 
													<?php
													foreach ( $node['attributes'] as $key => $val ) {
														echo $key . '="' . $val . '" ';}
													?>
							>
						</label>
							<?php
							break;
						case 1 === preg_match( '/checkbox[1-9]/u', array_search( $node, $menu, true ) ):
							?>
						<label>
							<input name="<?php echo $this->cls; ?>[<?php echo $menu['query1']; ?>][<?php echo $node['query2']; ?>]" value="<?php echo get_option( $this->cls )[ $menu['query1'] ][ $node['query2'] ]; ?>" 
													<?php
													foreach ( $node['attributes'] as $key => $val ) {
														echo $key . '="' . $val . '" ';}
													?>
							>
							<p><?php echo $node['title']; ?>：</p>
						</label>
							<?php
							break;
						// テキストアリア
						case 1 === preg_match( '/text[1-9]/u', array_search( $node, $menu, true ) ):
							if ( $node['repeat'] ) {
								$text_len = $node['repeat'];
							} else {
								$text_len = 1;
							}
							for ( $i = 0; $i < $text_len; ++$i ) :
								?>
						<label>
							<p><?php echo $node['title']; ?>：</p>
							<textarea name="<?php echo $this->cls; ?>[<?php echo $menu['query1']; ?>][<?php echo $node['query2']; ?>][<?php echo $i; ?>]" 
								<?php
								foreach ( $node['attributes'] as $key => $val ) {
									echo $key . '="' . $val . '" ';}
								?>
							><?php echo get_option( $this->cls )[ $menu['query1'] ][ $node['query2'] ][ $i ]; ?></textarea>
						</label>
								<?php
						endfor;
							break;
						// プルダウン
						case 1 === preg_match( '/select[1-9]/u', array_search( $node, $menu, true ) ):
							?>
						<label>
							<p><?php echo $node['title']; ?>：</p>
							<select name="<?php echo $this->cls; ?>[<?php echo $menu['query1']; ?>][<?php echo $node['query2']; ?>]">
								<?php foreach ( $node['calc'] as $key => $val ) : ?>
								<option value="<?php echo $key; ?>"<?php echo selected( get_option( $this->cls )[ $menu['query1'] ][ $node['query2'] ], $key, false ); ?>><?php echo $val; ?></option>
								<?php endforeach; ?>
							</select>
						</label>
							<?php
							break;
						endswitch;
					?>
				</div>
			<?php endforeach; ?>
				<input type="submit" class="button button-primary sissubmit" value="　更新する　">
			</div>
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
