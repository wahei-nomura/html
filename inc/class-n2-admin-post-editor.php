<?php
/**
 * class-n2-admin-post-editor.php
 *
 * @package neoneng
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'N2_Admin_Post_Editor' ) ) {
	new N2_Admin_Post_Editor();
	return;
}

/**
 * N2_Admin_Post_Editor
 */
class N2_Admin_Post_Editor {
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
		add_action( 'init', array( $this, 'remove_editor_support' ) );
		add_action( 'admin_menu', array( $this, 'add_customfields' ) );
		add_action( 'save_post', array( $this, 'save_customfields' ) );
		add_filter( 'upload_mimes', array( $this, 'add_mimes' ) );
		add_action( 'ajax_query_attachments_args', array( $this, 'display_only_self_uploaded_medias' ) );
		add_filter( 'enter_title_here', array( $this, 'change_title' ) );
		add_filter( 'intermediate_image_sizes_advanced', array( $this, 'not_create_image' ) );
		add_filter( 'wp_handle_upload', array( $this, 'image_compression' ) );
		add_filter( 'post_link', array( $this, 'set_post_paermalink' ), 10, 3 );
	}

	/**
	 * remove_editor_support
	 * 詳細ページ内で余分な項目を削除している
	 */
	public function remove_editor_support() {
		global $n2;
		// ブロックエディタへようこそを削除
		$user_meta = get_user_meta( $n2->current_user->ID );
		if ( empty( $user_meta[ "{$n2->blog_prefix}persisted_preferences" ] ) ) {
			$user_meta[ "{$n2->blog_prefix}persisted_preferences" ] = array(
				'core/edit-post' => array(
					'welcomeGuide' => false, // ブロックエディタへようこそ非表示
				),
			);
			update_user_meta( $n2->current_user->ID, "{$n2->blog_prefix}persisted_preferences", $user_meta[ "{$n2->blog_prefix}persisted_preferences" ] );
		}

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
		global $n2;
		// 管理者のみSS管理フィールド表示(あとで変更予定)
		if ( current_user_can( 'ss_crew' ) ) {
			add_meta_box(
				'スチームシップ用',
				'スチームシップ用',
				array( $this, 'show_customfields' ),
				'post',
				'normal',
				'default',
				$n2->custom_field['スチームシップ用'], // show_customfieldsメソッドに渡すパラメータ
			);
		}
		add_meta_box(
			'事業者用',
			'事業者用',
			array( $this, 'show_customfields' ),
			'post',
			'normal',
			'default',
			$n2->custom_field['事業者用'], // show_customfieldsメソッドに渡すパラメータ
		);
	}

	/**
	 * show_customfields
	 * iniファイル内を配列化してフィールドを作っている
	 *
	 * @param Object $post post
	 * @param Array  $metabox 全データ
	 */
	public function show_customfields( $post, $metabox ) {
		global $n2;
		?>
		<!-- n2field保存の為のnonce -->
		<input type="hidden" name="n2nonce" value="<?php echo wp_create_nonce( 'n2nonce' ); ?>">
		<table class="n2-fields widefat fixed" style="border:none;">
			<?php foreach ( $metabox['args'] as $field => $detail ) : ?>
			<tr id="<?php echo $field; ?>" class="n2-fields-list" v-if="<?php echo $detail['v-if'] ?? ''; ?>">
				<th class="n2-fields-title" >
					<?php echo ! empty( $detail['label'] ) ? $detail['label'] : $field; ?>
				</th>
				<td class="n2-fields-value" data-description="<?php echo $detail['description'] ?? ''; ?>">
				<?php
					// templateに渡すために不純物を除去
					$settings = $detail;
					unset( $settings['description'], $settings['label'], $settings['v-if'] );
					$settings['name']  = sprintf( 'n2field[%s]', $settings['name'] ?? $field );
					$settings['value'] = $settings['value'] ?? '';
					$settings['value'] = get_post_meta( $post->ID, $field, true ) ?? $settings['value'];
					// プラグインでテンプレートを追加したい場合は、get_template_part_{$slug}フックでいける
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
		if ( ! wp_verify_nonce( $_POST['n2nonce'] ?? '', 'n2nonce' ) || ! isset( $_POST['n2field'] ) ) {
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
		$title = 'ここに返礼品の名前を入力してください';
		return $title;
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
