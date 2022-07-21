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
		get_template_part( 'template/progress' );
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

		$ss_fields      = parse_ini_file( get_template_directory() . '/config/n2-ss-fields.ini', true );
		$default_fields = parse_ini_file( get_template_directory() . '/config/n2-fields.ini', true );

		// 既存のフィールドの位置を変更したい際にプラグイン側からフィールドを削除するためのフック
		list($ss_fields,$default_fields) = apply_filters( 'n2_setpost_delete_customfields', array( $ss_fields, $default_fields ) );

		// 管理者のみSS管理フィールド表示(あとで変更予定)
		if ( current_user_can( 'ss_crew' ) ) {
			add_meta_box(
				'ss_setting',
				'SS管理',
				array( $this, 'show_customfields' ),
				'post',
				'normal',
				'default',
				// show_customfieldsメソッドに渡すパラメータ
				array( $ss_fields, 'ss' ),
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
			array( $default_fields, 'default' ),
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
		$post_data = N2_Functions::get_all_meta( $post );

		$fields = $args['args'][0]; // iniファイル内の配列
		$type   = $args['args'][1]; // ss or default

		// プラグインn2-developのn2_setpost_show_customfields呼び出し
		$fields = apply_filters( 'n2_setpost_show_customfields', $fields, $type );

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

			$fields[ $key ]['value'] = ! empty( $post_data[ $key ] ) ? $post_data[ $key ] : '';
		}

		// タグ管理(printfで使う)
		$input_tags = array(
			'text'             => '<input type="text" style="width:100%%" id="%1$s" name="%1$s" value="%2$s" maxlength="%3$s" placeholder="%4$s" class="n2-input %5$s">',
			'textarea'         => '<textarea style="width:100%%; height:200px" id="%1$s" name="%1$s" maxlength="%3$s" placeholder="%4$s" class="n2-input %5$s">%2$s</textarea>',
			'number'           => '<input type="number" id="%1$s" name="%1$s" value="%2$s" step="%3$s" class="n2-input %4$s">',
			'checkbox'         => '<li><label><input type=checkbox name="%1$s" value="%2$s" %3$s class="n2-input">%4$s</label></li>',
			'select'           => '<select id="%1$s" name="%1$s" class="n2-input %3$s">%2$s</select>',
			'option'           => '<option value="%1$s" %3$s>%2$s</option>',
			'image'            => '<input class="n2-input %1$s-image-input" type="hidden" name="%2$s" value="%3$s"><button class="button button-primary %1$s-media-toggle">画像選択</button><button class="button button-secondary %1$s-media-delete">削除</button>
							<div><img class="%1$s-image-url" src="%3$s" alt="" width="50%%" /></div>',
			'zip'              => '<input class="n2-input %1$s-zip-input" type="hidden" name="%2$s" value="%3$s"><button type="button" class="button button-primary %1$s-zip-toggle">zip選択</button><button type="button" class="button button-secondary %1$s-zip-delete">削除</button>
							<div><p class="%1$s-zip-url">%4$s</p></div>',
			'rakuten_genreid'  => '<button type="button" id="neo-neng-genreid-btn" class="button button-primary button-large">ディレクトリID検索</button><input type="hidden" id="%1$s" name="%1$s" value="%2$s"><input type="hidden" id="%3$s" name="%3$s" value="%4$s" class="%5$s">',
			'rakuten_tagid'    => '<button type="button" id="neo-neng-tagid-btn" class="button button-primary button-large">タグID検索</button><input type="hidden" id="%1$s" name="%1$s" value="%2$s"><input type="hidden" id="%3$s" name="%3$s" value="%4$s" class="%5$s">',
			'rakuten_category' => '<div><select id="neo-neng-rakutencategory"></select></div><div><textarea style="width:100%%; height:200px" id="%1$s" name="%1$s" maxlength="%3$s" placeholder="%4$s" class="n2-input %5$s">%2$s</textarea></div>',
		);

		// バリデーション付与用
		$validation_class = array(
			'必須'  => '-hissu',
			'0以外' => '-notzero',
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
						} elseif ( 'rakuten_genreid' === $detail['type'] ) {
							// 楽天ディレクトリID検索用
							$value      = '' !== $detail['value'] ? $detail['value'] : '';
							$text       = empty( $post_data['全商品ディレクトリID-text'] ) || '' === $post_data['全商品ディレクトリID-text'] ? '' : $post_data['全商品ディレクトリID-text'];
							$validation = ! empty( $detail['validation'] ) ? N2_THEME_NAME . $validation_class[ $detail['validation'] ] : '';
							printf( $input_tags[ $detail['type'] ], $field, $value, $field . '-text', $text, $validation );
						} elseif ( 'rakuten_tagid' === $detail['type'] ) {
							// 楽天ディレクトリID検索用
							$value      = '' !== $detail['value'] ? $detail['value'] : '';
							$text       = empty( $post_data['タグID-text'] ) || '' === $post_data['タグID-text'] ? '' : $post_data['タグID-text'];
							$validation = ! empty( $detail['validation'] ) ? N2_THEME_NAME . $validation_class[ $detail['validation'] ] : '';
							printf( $input_tags[ $detail['type'] ], $field, $value, $field . '-text', $text, $validation );
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

		foreach ( $_POST as $key => $value ) {
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
			'kifu_auto_pattern' => $this->kifu_auto_pattern(),
			'delivery_pattern'  => $this->delivery_pattern(),
		);

		echo json_encode( $arr );

		die();
	}

	/**
	 * 寄附金額計算式をそのままJSの構文として渡す
	 *
	 * @return string
	 */
	private function kifu_auto_pattern() {

		// パターンを配列で置いておく
		$pattern = array(
			'零号機' => 'Math.ceil((kakaku + souryou) / 300) * 1000',
			'初号機' => 'Math.ceil(kakaku / 300) * 1000',
			'弐号機' => 'Math.ceil((kakaku + souryou) / 350) * 1000',
		);

		$pattern['使徒'] = "{$pattern['初号機']}>{$pattern['弐号機']}?{$pattern['初号機']}:{$pattern['弐号機']}";

		$pattern_type = '初号機';
		$pattern_type = apply_filters( 'n2_setpost_change_kifu_pattern', $pattern_type );

		return $pattern[ $pattern_type ];
	}

	/**
	 * 配送パターンを渡す
	 *
	 * @return Array $pattern
	 */
	private function delivery_pattern() {

		$pattern = parse_ini_file( get_template_directory() . '/config/n2-delivery.ini', true, INI_SCANNER_TYPED );

		// プラグイン側で上書き
		$pattern = apply_filters( 'n2_setpost_change_delivary_pattern', $pattern );

		return $pattern;
	}
}
