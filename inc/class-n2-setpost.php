<?php
/**
 * class-n2-setpost.php
 *
 * @package neoneng
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'N2_Setpost' ) ) {
	new N2_Setpost();
	return;
}

/**
 * Setpost
 */
class N2_Setpost {
	/**
	 * クラス名
	 *
	 * @var string
	 */
	private $cls;

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		$this->cls = get_class( $this );
		add_action( 'nocache_headers', array( $this, 'editpage_redirect' ) );
		add_action( 'admin_head-post.php', array( $this, 'show_progress' ) );
		add_action( 'admin_head-post-new.php', array( $this, 'show_progress' ) );
		add_action( 'init', array( $this, 'remove_editor_support' ) );
		add_action( 'admin_menu', array( $this, 'add_customfields' ) );
		add_action( 'save_post', array( $this, 'save_customfields' ) );
		add_filter( 'upload_mimes', array( $this, 'add_mimes' ) );
		add_action( 'ajax_query_attachments_args', array( $this, 'display_only_self_uploaded_medias' ) );
		add_filter( 'enter_title_here', array( $this, 'change_title' ) );
		add_action( "wp_ajax_{$this->cls}", array( $this, 'ajax' ) );
		add_action( "wp_ajax_{$this->cls}_image", array( $this, 'ajax_imagedata' ) );
		add_filter( 'intermediate_image_sizes_advanced', array( $this, 'not_create_image' ) );
		add_filter( 'wp_handle_upload', array( $this, 'image_compression' ) );
		add_filter( 'post_link', array( $this, 'set_post_paermalink' ), 10, 3 );
	}

	/**
	 * editpage_redirect
	 * 事業者のSS確認待ちをリダイレクト
	 *
	 * @param Object $headers headers
	 * @return Object $headers headers
	 */
	public function editpage_redirect( $headers ) {
		// post.phpのaction=editページ
		if ( preg_match( '/post\.php/', $_SERVER['REQUEST_URI'] ) && ! empty( $_GET['action'] ) && 'edit' === $_GET['action'] ) {
			$post_id = ! empty( $_GET['post'] ) && '' !== $_GET['post'] ? $_GET['post'] : false;

			// $post_idが存在、かつ他記事編集権限がない、かつ事業者下書きじゃない
			if ( $post_id && ! current_user_can( 'ss_crew' ) && 'draft' !== get_post_status( $post_id ) ) {
				$headers['Location'] = home_url( "/?p={$post_id}" );
				return $headers;
			}
		}
	}

	/**
	 * show_progress
	 * 編集画面にてプログレストラッカー表示
	 *
	 * @return void
	 */
	public function show_progress() {
		global $post;
		?>
			<link href="//cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
			<script src="//cdn.jsdelivr.net/npm/vue@2.x"></script>
			<script src="//cdn.jsdelivr.net/npm/sortablejs@1.8.4/Sortable.min.js"></script>
			<script src="//cdnjs.cloudflare.com/ajax/libs/Vue.Draggable/2.20.0/vuedraggable.umd.min.js"></script>
			<script>
				window.n2 = {};
				window.n2.town = '<?php bloginfo( 'name' ); ?>';
				window.n2.field_value = <?php echo wp_json_encode( (array) N2_Functions::get_all_meta( $post ) ); ?>;
				window.n2.field_list = <?php echo wp_json_encode( (array) array_keys( N2_Functions::get_all_meta( $post ) ) ); ?>;
				// N1の商品画像をN2互換
				if ( ! n2.field_value.商品画像 ) {
					n2.field_value.商品画像 = [];
					Object.keys(n2.field_value).forEach((k) => {
						if ( k.match(/^商品画像[０-９]/) && n2.field_value[k] ) {
							n2.field_value.商品画像.push(n2.field_value[k]);
						}
					});
				}
				
				// このdataをプラグイン側で上書きする
				const data = {
					出品禁止ポータル: n2.field_value.出品禁止ポータル || [],
					食品確認: n2.field_value.食品確認 ? n2.field_value.食品確認[0] : false,// ※食品事業者はデフォルトでONにしとくのまだ
					アレルギー有無確認: n2.field_value.アレルギー有無確認 ? n2.field_value.アレルギー有無確認[0] : false,
					商品画像: n2.field_value.商品画像,
					全商品ディレクトリID: {
						text: n2.field_value.全商品ディレクトリID,
						list: [],
					},
					タグID: {
						text: n2.field_value.タグID,
						group: '',
						list: [],
					},
					楽天SPAカテゴリー: {
						text: n2.field_value.楽天SPAカテゴリー ? n2.field_value.楽天SPAカテゴリー.replace(/\r/g, ''): '',
						list: [],
					}
				};
				const components = {
					draggable: vuedraggable,
				}
				jQuery(function($){
					wp.data.subscribe(()=>{
						window.n2.status = wp.data.select("core/editor").getEditedPostAttribute("status");
					});
					$(".edit-post-layout__metaboxes").ready(() => {
						n2.vue = new Vue({
							el: '.edit-post-layout__metaboxes',
							data,
							methods: {
								// メディアアップローダー関連
								add_media(){
									// N1の画像データにはnoncesが無い
									const images = wp.media({
										title: "商品画像", 
										multiple: "add",
										library: {type: "image"}
									});
									images.on( 'open', () => {
										// N2のものだけに
										const add =  this.商品画像.filter( v => v.nonces );
										images.state().get('selection').add( add.map( v => wp.media.attachment(v.id) ) );
									});
									images.on( 'select', () => {
										this.商品画像 =  [
												...this.商品画像.filter( v => !v.nonces ),// N1のみ展開
												...images.state().get('selection').map( v => v.attributes )
											];
									});
									images.open();
								},
								// 楽天の全商品ディレクトリID取得（タグIDでも利用）
								async get_genreid( tagid_reset = false ){
									const settings = {
										url: '//app.rakuten.co.jp/services/api/IchibaGenre/Search/20140222',
										data: {
											applicationId: '1002772968546257164',
											genreId: this.全商品ディレクトリID.text || '0',
										},
									};
									this.全商品ディレクトリID.list = await $.ajax(settings);
									if ( tagid_reset && this.タグID.text ) {
										this.タグID.list = [];
										if ( confirm('全商品ディレクトリIDが変更されます。\nそれに伴い入力済みのタグIDをリセットしなければ楽天で地味にエラーがでます。\n\nタグIDをリセットしてよろしいでしょうか？') ) {
											this.タグID.text = '';
										}
									}
								},
								// 楽天SPAカテゴリーの取得
								async get_spa_category(){
									const folderCode = '1p7DlbhcIEVIaH7Rw2mTmqJJKVDZCumYK';
									const settings = {
										url: '//www.googleapis.com/drive/v3/files/',
										data: {
											key: 'AIzaSyDQ1Mu41-8S5kBpZED421bCP8NPE7pneNU',
											q: `'${folderCode}' in parents and name = '${n2.town}' and mimeType contains 'spreadsheet'`,
										}
									};
									const d = await $.ajax(settings);
									if ( ! d.files.length ) {
										alert('カテゴリー情報の取得失敗');
										return;
									}
									settings.url = `//sheets.googleapis.com/v4/spreadsheets/${d.files[0].id}/values/カテゴリー`;
									delete settings.data.q;
									const cat = await $.ajax(settings);
									delete cat.values[0];
									this.楽天SPAカテゴリー.list = cat.values.map( (v,k) => {
										v.forEach((e,i) => {
											v[i] = e || cat.values[k-1][i];
											v[i] = v[i].replace('.','');
										});
										return `#/${v.join('/')}/`;
									}).filter(v=>v);
								},
								// タグIDと楽天SPAカテゴリーで利用
								update_textarea(id, target = 'タグID', delimiter = '/'){
									// 重複削除
									const arr = this[target].text ? [...new Set( this[target].text.split( delimiter ) )]: [];
									// 削除
									if ( arr.includes( id.toString() ) ) {
										this[target].text = arr.filter( v => v != id ).join( delimiter )
									}
									// 追加
									else {
										// 楽天のタグIDの上限
										if ( target == 'タグID' && arr.length >= $('[type="rakuten-tagid"]').attr('maxlength')/8 ) return;
										this[target].text = [...arr, id].filter( v => v ).join( delimiter );
									}
								}
							},
							components,
						});
					});
					// 雑な目次
					$(".edit-post-header-toolbar__list-view-toggle").ready(() => {
						$('.edit-post-header-toolbar__list-view-toggle').on('click', function(){
							$(".edit-post-editor__list-view-panel-content").ready(() => {
								$.each(n2.field_list, (k,v) => {
									$('.edit-post-editor__list-view-panel-content').append(`<li><a href="#${v}">${v}</a></li>`)
								})
							});
						})
					});

				})
			</script>
		<?php
		// get_template_part( 'template/progress' );
	}

	/**
	 * remove_editor_support
	 * 詳細ページ内で余分な項目を削除している
	 */
	public function remove_editor_support() {
		$supports = array(
			'thumbnail',
			'excerpt',
			'trackbacks',
			'comments',
			'post-formats',
		);

		foreach ( $supports as $support ) {
			remove_post_type_support( 'post', $support );
		}

		$taxonomys = array(
			'category',
			'post_tag',
		);

		foreach ( $taxonomys as $taxonomy ) {
			unregister_taxonomy_for_object_type( $taxonomy, 'post' );
		}
	}

	/**
	 * add_customfields
	 * SS管理と返礼品詳細を追加
	 */
	public function add_customfields() {

		$ss_fields      = yaml_parse_file( get_theme_file_path() . '/config/n2-ss-fields.yml' );
		$default_fields = apply_filters( 'n2_setpost_plugin_portal', yaml_parse_file( get_theme_file_path() . '/config/n2-fields.yml' ) );

		// 既存のフィールドの位置を変更したい際にプラグイン側からフィールドを削除するためのフック
		list($ss_fields,$default_fields) = apply_filters( 'n2_setpost_delete_customfields', array( $ss_fields, $default_fields ) );

		// 管理者のみSS管理フィールド表示(あとで変更予定)
		if ( current_user_can( 'ss_crew' ) ) {
			add_meta_box(
				'ss_setting', // id
				'SS管理',
				array( $this, 'show_customfields' ),
				'post',
				'normal',
				'default',
				$ss_fields, // show_customfieldsメソッドに渡すパラメータ
			);
		}
		add_meta_box(
			'default_setting', // id
			'返礼品詳細',
			array( $this, 'show_customfields' ),
			'post',
			'normal',
			'default',
			$default_fields, // show_customfieldsメソッドに渡すパラメータ
		);
	}

	/**
	 * show_customfields
	 * iniファイル内を配列化してフィールドを作っている
	 *
	 * @param Object $post post
	 * @param Array  $args args
	 */
	public function show_customfields( $post, $args ) {
		// カスタムフィールド全取得
		$post_meta = N2_Functions::get_all_meta( $post );
		/**
		 * Filters カスタムフィールドメタボックス
		 *
		 * @param array $args add_meta_box情報
		*/
		$args = apply_filters( 'n2_setpost_show_customfields', $args );
		unset( $args['args']['事業者確認'] );
		?>
		<!-- n2field保存の為のnonce -->
		<input type="hidden" name="n2nonce" value="<?php echo wp_create_nonce( 'n2nonce' ); ?>">
		<table class="widefat fixed" style="border:none;">
			<?php foreach ( $args['args'] as $field => $detail ) : ?>
			<tr title="<?php echo $detail['description']; ?>" id="<?php echo $field; ?>" class="<?php echo $detail['class'] ?? ''; ?>" v-if="<?php echo $detail['v-if'] ?? ''; ?>">
				<th>
					<?php echo ! empty( $detail['label'] ) ? $detail['label'] : $field; ?>
					<?php if ( ! empty( $detail['description'] ) ) : ?>
					<span class="dashicons dashicons-info" style="color: #aaa;"></span>
					<?php endif; ?>
				</th>
				<td>
				<?php
					// templateに渡すために不純物を除去
					$settings = $detail;
					unset( $settings['description'], $settings['label'], $settings['validation'], $settings['class'], $settings['v-if'] );
					$settings['name']  = sprintf( 'n2field[%s]', $settings['name'] ?? $field );
					$settings['value'] = $post_meta[ $field ];
					get_template_part( "template/forms/{$detail['type']}", null, $settings );
					?>
				</td>
			</tr>
			<?php endforeach; ?>
		</table>
		<?php
	}

	/**
	 * カスタムフィールド「n2fields」の保存
	 * 「n2nonce」を渡さないと発火させない
	 *
	 * @param int $post_id first parameter
	 */
	public function save_customfields( $post_id ) {
		if ( ! wp_verify_nonce( $_POST['n2nonce'], 'n2nonce' ) || ! isset( $_POST['n2field'] ) ) {
			return;
		}
		// カスタムフィールド（n2field）の保存
		foreach ( (array) $_POST['n2field'] as $key => $value ) {
			// チェックボックスのデータ整形
			if ( array_key_exists( 'checkbox2', (array) $value ) ) {
				unset( $value['checkbox2'] );
				$value = array_filter( $value, fn( $v ) => array_key_exists( 'value', $v ) );
				$value = array_values( $value );
			}
			if ( '商品画像' === $key ) {
				$value = json_decode( stripslashes( $value ), true );
			}
			update_post_meta( $post_id, $key, $value );
		}
	}


	/**
	 * zip形式をuploadできるようにする
	 *
	 * @param array $mimes upload形式
	 * @return array
	 */
	public function add_mimes( $mimes ) {
		$mimes['zip'] = 'application/zip';
		return $mimes;
	}

	/**
	 * 管理者とSSクルー以外は自分がアップロードした画像しかライブラリに表示しない
	 *
	 * @param array $query global query
	 * @return array
	 */
	public function display_only_self_uploaded_medias( $query ) {
		if ( ! current_user_can( 'ss_crew' ) && wp_get_current_user() ) {
			$query['author'] = wp_get_current_user()->ID;
		}
		return $query;
	}

	/**
	 * タイトル変更
	 *
	 * @param string $title タイトル
	 * @return string
	 */
	public function change_title( $title ) {
		$title = '返礼品の名前を入力';
		return $title;
	}

	/**
	 * JSにユーザー権限判定を渡す
	 *
	 * @return void
	 */
	public function ajax() {

		$arr = array(
			'ss_crew'           => wp_get_current_user()->allcaps['ss_crew'] ? 'true' : 'false',
			'kifu_auto_pattern' => N2_Functions::kifu_auto_pattern( 'js' ),
			'delivery_pattern'  => $this->delivery_pattern(),
			'food_param'        => $this->food_param(),
		);

		echo json_encode( $arr );

		die();
	}

	/**
	 * 配送パターンを渡す
	 *
	 * @return Array $pattern
	 */
	private function delivery_pattern() {

		$pattern = yaml_parse_file( get_theme_file_path() . '/config/n2-delivery.yml' );

		// プラグイン側で上書き
		$pattern = apply_filters( 'n2_setpost_change_delivary_pattern', $pattern );

		return $pattern;
	}

	/**
	 * 食品取扱の有無を返す
	 *
	 * @return string
	 */
	private function food_param() {
		$user = wp_get_current_user();
		if ( 'jigyousya' !== $user->roles[0] ) {
			return '事業者ではない';
		}

		return empty( get_user_meta( $user->ID, '食品取り扱い', true ) ) ? '未設定' : get_user_meta( $user->ID, '食品取り扱い', true );
	}

	/**
	 * 画像のURLとID変換用ajax
	 */
	public function ajax_imagedata() {

		if ( empty( $_GET['imgurls'] ) ) {
			echo 'noselected';
			die();
		}

		$img_urls = $_GET['imgurls'];

		echo json_encode(
			array_map(
				function( $img_url ) {
					return attachment_url_to_postid( $img_url );
				},
				$img_urls
			)
		);

		die();
	}

	/**
	 * 画像アップロード時不要なサイズの自動生成をストップ
	 *
	 * @param Array $sizes デフォルトサイズ
	 * @return Array $sizes 加工後
	 */
	public function not_create_image( $sizes ) {
		unset( $sizes['medium'] );
		unset( $sizes['large'] );
		unset( $sizes['medium_large'] );
		unset( $sizes['1536x1536'] );
		unset( $sizes['2048x2048'] );
		return $sizes;
	}

	/**
	 * 画像アップロード時に自動圧縮
	 *
	 * @param Array $image_data アップロード画像データ
	 * @return Array $image_data 上に同じ
	 */
	public function image_compression( $image_data ) {
		$imagick = new Imagick( $image_data['file'] );
		// 写真拡張子取得
		$file_extension = pathinfo( $image_data['file'], PATHINFO_EXTENSION );
		$max_size       = 2000;

		// width heightリサイズ
		if ( $imagick->getImageGeometry()['width'] > $max_size || $imagick->getImageGeometry()['height'] > $max_size ) {
			$imagick->scaleImage( $max_size, $max_size, true );
		}

		// png
		if ( 'png' === $file_extension ) {
			$png_file = escapeshellarg( $image_data['file'] );
			exec( "pngquant --ext .png {$png_file} --force --quality 50-80" );
		} else {
			// jpg
			$imagick->setImageCompressionQuality( 80 );
			$imagick->writeImage( $image_data['file'] );
		}

		return $image_data;
	}

	/**
	 * 投稿パーマリンクをid=○○にする
	 *
	 * @param string $url url
	 * @param Object $post post
	 * @param string $leavename false
	 * @return string $url url
	 */
	public function set_post_paermalink( $url, $post, $leavename = false ) {

		return 'post' === $post->post_type ? home_url( '?p=' . $post->ID ) : $url;

	}
}
