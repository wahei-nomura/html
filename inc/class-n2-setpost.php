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
	 * コンストラクタ
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'remove_editor_support' ) );
		add_action( 'admin_menu', array( $this, 'add_customfields' ) );
		add_action( 'save_post', array( $this, 'save_customfields' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_setpost_script' ) );
		add_filter( 'upload_mimes', array( $this, 'add_mimes' ) );
	}

	/**
	 * remove_editor_support
	 * 詳細ページ内で余分な項目を削除している
	 */
	public function remove_editor_support() {
		$supports = array(
			'editor',
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
		// 管理者のみSS管理フィールド表示(あとで変更予定)
		if ( current_user_can( 'edit_others_posts' ) ) {
			add_meta_box(
				'ss_setting',
				'SS管理',
				array( $this, 'show_customfields' ),
				'post',
				'normal',
				'default',
				// show_customfieldsメソッドに渡すパラメータ
				array( parse_ini_file( get_template_directory() . '/config/n2-ss-fields.ini', true ), 'ss' ),
			);
		}
		add_meta_box(
			'default_setting',
			'返礼品詳細',
			array( $this, 'show_customfields' ),
			'post',
			'normal',
			'default',
			// show_customfieldsメソッドに渡すパラメータ
			array( parse_ini_file( get_template_directory() . '/config/n2-fields.ini', true ), 'default' ),
		);
	}

	/**
	 * show_customfields
	 * iniファイル内を配列化してフィールドを作っている
	 */
	public function show_customfields( $post, $args ) {
		global $post;
		$post_data = get_post_meta( $post->ID, 'post_data', true );
		$fields    = $args['args'][0]; // iniファイル内の配列
		$type      = $args['args'][1]; // ss or default

		// プラグインn2-developのn2_setpost_show_customfields呼び出し
		list($fields,$type) = apply_filters( 'n2_setpost_show_customfields', array( $fields, $type ) );

		// optionを配列化、valueにDBの値をセット
		// 「,」で配列に分けて、「\」でkey=>valueにわけている
		foreach ( $fields as $key => $field ) {
			if ( isset( $fields[ $key ]['option'] ) ) {
				$new_options = array();
				$options     = explode( ',', $fields[ $key ]['option'] );
				foreach ( $options as $option ) {
					$new_options[ explode( '\\', $option )[0] ] = explode( '\\', $option )[1];
				}
				$fields[ $key ]['option'] = $new_options;
			} else {
				$fields[ $key ]['option'] = '';
			}

			$fields[ $key ]['value'] = isset( $post_data[ $key ] ) ? $post_data[ $key ] : '';
		}

		// タグ管理(printfで使う)
		$input_tags = array(
			'text'     => '<input type="text" id="%1$s" name="%1$s" value="%2$s" maxlength="%3$s" placeholder="%4$s" class="%5$s">',
			'textarea' => '<textarea style="display:block; width:100%; height:200px" id="%1$s" name="%1$s" maxlength="%3$s" placeholder="%4$s" class="%5$s">%2$s</textarea>',
			'number'   => '<input type="number" id="%1$s" name="%1$s" value="%2$s" step="%3$s" class="%4$s">',
			'checkbox' => '<li><label><input type=checkbox name="%1$s" value="%2$s" %3$s>%4$s</label></li>',
			'select'   => '<select id="%1$s" name="%1$s" class="%3$s">%2$s</select>',
			'option'   => '<option value="%1$s" %3$s>%2$s</option>',
			'image'    => '<input class="%1$s-image-input" type="hidden" name="%2$s" value="%3$s"><button class="button button-primary %1$s-media-toggle">画像選択</button>
							<div><img class="%1$s-image-url" src="%3$s" alt="" /></div>',
			'zip'      => '<input class="%1$s-zip-input" type="hidden" name="%2$s" value="%3$s"><button type="button" class="button button-primary %1$s-zip-toggle">zip選択</button>
							<div><p class="%1$s-zip-url">%4$s</p></div>',
		);

		// バリデーション付与用
		$validation_class = array(
			'必須' => '-hissu',
		);

		?>

			<div>
				<?php foreach ( $fields as $field => $detail ) : ?>
				<div>
					<!-- ラベル -->
					<p><label for="<?php echo $field; ?>"><?php echo ! empty( $detail['label'] ) ? $detail['label'] : $field; ?></label></p>
					<!-- 説明 -->
					<p><?php echo ! empty( $detail['description'] ) ? $detail['description'] : ''; ?></p>
					<div>
						<?php
						// optionを文字列連結してselectに挿入
						if ( 'select' === $detail['type'] ) {
							$options = '';
							foreach ( $detail['option'] as $key => $option ) {
								// DBのvalueと同じものにselectedをつける
								$selected = selected( ! empty( $detail['value'] ) && (string) $detail['value'] === (string) $key, true, false );
								$options .= sprintf( $input_tags['option'], $key, $option, $selected );
							}
							$validation = ! empty( $detail['validation'] ) ? N2_THEME_NAME . $validation_class[ $detail['validation'] ] : '';
							printf( $input_tags['select'], $field, $options, $validation );
						} elseif ( 'checkbox' === $detail['type'] ) {
							$checks = '';
							foreach ( $detail['option'] as $key => $check ) {
								// DB内の配列に選択肢が含まれればcheckd
								$checked = checked( ! empty( $detail['value'] ) && in_array( (string) $key, $detail['value'], true ), true, false );
								$checks .= sprintf( $input_tags['checkbox'], $field . '[]', $key, $checked, $check );
							}
							printf( '<ul>%1$s</ul>', $checks );
						} elseif ( 'number' === $detail['type'] ) {
							$value      = '' !== $detail['value'] ? $detail['value'] : 0;
							$step       = ! empty( $detail['step'] ) ? $detail['step'] : '';
							$validation = ! empty( $detail['validation'] ) ? N2_THEME_NAME . $validation_class[ $detail['validation'] ] : '';
							printf( $input_tags[ $detail['type'] ], $field, $value, $step, $validation );
						} elseif ( 'image' === $detail['type'] ) {
							$value = '' !== $detail['value'] ? $detail['value'] : '';
							printf( $input_tags[ $detail['type'] ], N2_THEME_NAME, $field, $value );
						} elseif ( 'zip' === $detail['type'] ) {
							$value = '' !== $detail['value'] ? $detail['value'] : '';
							$show  = $value ? explode( '/', $value ) : '';
							$show  = $show ? end( $show ) . 'を選択中' : '';
							printf( $input_tags[ $detail['type'] ], N2_THEME_NAME, $field, $value, $show );
						} else {
							// valueにデフォルト値やmaxlength,placeholderをセットするか判定
							$value       = '' !== $detail['value'] ? $detail['value'] : ( ! empty( $detail['default'] ) ? $detail['default'] : '' );
							$maxlength   = ! empty( $detail['maxlength'] ) ? $detail['maxlength'] : '';
							$placeholder = ! empty( $detail['placeholder'] ) ? $detail['placeholder'] : '';
							$validation  = ! empty( $detail['validation'] ) ? N2_THEME_NAME . $validation_class[ $detail['validation'] ] : '';
							printf( $input_tags[ $detail['type'] ], $field, $value, $maxlength, $placeholder, $validation );
						};
						?>
					</div>
				</div>
				<hr>
				<?php endforeach; ?>
			</div>
			<?php
	}

	/**
	 * save_customfields
	 *
	 * @param int $post_id first parameter
	 * @return void
	 */
	public function save_customfields( $post_id ) {

		if ( empty( $_POST ) ) {
			return;
		}
		$post_data = array_filter(
			$_POST,
			function( $val ) {
				return $this->h( $val );
			}
		);

		update_post_meta( $post_id, 'post_data', $post_data );
	}

	/**
	 * エスケープ処理の簡易関数
	 *
	 * @param string|array $arg 文字列or配列
	 * @return string
	 */
	public function h( $arg ) {
		if ( gettype( $arg ) === 'array' ) {
			$arr = array();
			foreach ( $arg as $str ) {
				array_push( $arr, htmlspecialchars( $str, ENT_QUOTES, 'UTF-8' ) );
			}
			return $arr;
		} else {
			return htmlspecialchars( $arg, ENT_QUOTES, 'UTF-8' );
		}
	}

	/**
	 * このクラスで使用するassetsの読み込み
	 *
	 * @return void
	 */
	public function enqueue_setpost_script() {
		wp_enqueue_media();
		// wp_enqueue_script( 'n2-setpost', get_template_directory_uri() . '/assets/js/n2-setpost.js', array( 'jquery' ), wp_get_theme()->get( 'Version' ), true );
		wp_enqueue_script( 'n2-setpost', get_template_directory_uri() . '/dist/index.js', array( 'jquery' ), wp_get_theme()->get( 'Version' ), true );
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
}
