<?php
/**
 * class-n2-rakuten-sftp.php
 * SFTP対応版
 *
 * @package neoneng
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'N2_Rakuten_SFTP' ) ) {
	new N2_Rakuten_SFTP();
	return;
}


/**
 * 楽天SFTP
 */
class N2_Rakuten_SFTP {

	/**
	 * 設定項目
	 *
	 * @var array
	 */
	public $settings = array(
		'main_menu' => 'n2_rakuten_menu',
		'sub_menu'  => array(
			'sftp-explorer'  => 'SFTP',
			'sftp-upload'    => 'SFTPログ',
			'sftp-error-log' => 'SFTPエラーログ',
			'rms-cabinet'    => 'CABINET',
			'cabinet-renho'  => 'キャビ蓮舫',
		),
		'template'  => 'template/admin-rakuten-menu',
	);

	/**
	 * データ
	 *
	 * @var array
	 */
	protected $data = array(
		'connect'          => null,
		'params'           => array(),
		'files'            => null,
		'n2data'           => array(),
		'error'            => array(),
		'log'              => array(),
		'rakuten_csv_name' => array( 'normal-item', 'item-cat', 'item-delete' ),
		'extensions'       => '.csv',
		'insert_post'      => null,
		'post_type'        => 'n2_sftp',
	);

	/**
	 * SFTP
	 *
	 * @var WP_Filesystem_SSH2|null
	 */
	public $sftp = null;

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'wp_ajax_n2_rakuten_sftp_api', array( $this, 'api' ) );
		add_action( 'wp_ajax_n2_rakuten_sftp_insert_cabi_renho_log', array( $this, 'insert_cabi_renho_log' ) );
		add_action( 'init', array( $this, 'register_post_type' ) );
	}
	/**
	 * デストラクタ
	 */
	public function __destruct() {
	}

	/**
	 * N2 SFTP　メニューの追加
	 */
	public function add_menu() {
		global $n2, $wp_filesystem;
		if ( isset( $n2->settings['楽天'] ) ) {
			add_menu_page( '楽天', '楽天', 'ss_crew', $this->settings['main_menu'], array( $this, 'display_ui' ), 'dashicons-admin-site-alt3' );
			foreach ( $this->settings['sub_menu'] as $page => $name ) {
				if ( ! current_user_can( 'administrator' ) && 'cabinet-renho' === $page ) {
					unset( $this->settings['sub_menu'][ $page ] );
					continue;
				}
				// 設定テンプレートの存在を確認して、ない場合は破棄してスキップする
				if ( ! $wp_filesystem->exists( get_theme_file_path( "{$this->settings['template']}/{$page}.php" ) ) ) {
					unset( $this->settings['sub_menu'][ $page ] );
					continue;
				}
				$menu_slug = $this->create_menu_slug( $page );
				add_submenu_page( $this->settings['main_menu'], $name, $name, 'ss_crew', $menu_slug, array( $this, 'display_ui' ) );
				register_setting( $menu_slug, $menu_slug );
			}
		}
	}

	/**
	 * メニュースラッグの作成
	 * ?page=n2_settings_xxxx
	 *
	 * @param string $page ページ
	 */
	private function create_menu_slug( $page ) {
		return $this->settings['main_menu'] . ( array_keys( $this->settings['sub_menu'] )[0] === $page ? '' : "_{$page}" );
	}

	/**
	 * SFTP UI
	 */
	public function display_ui() {
		$template = $_GET['page'];
		$html     = array(
			'nav'      => '',
			'contents' => '',
			'args'     => null,
		);
		foreach ( $this->settings['sub_menu'] as $page => $name ) {
			$menu_slug    = $this->create_menu_slug( $page );
			$html['nav'] .= sprintf( '<a href="?page=%s" class="nav-tab%s">%s</a>', $menu_slug, $menu_slug === $template ? ' nav-tab-active' : '', $name );
			if ( $menu_slug === $template ) {
				ob_start();
				get_template_part( "{$this->settings['template']}/{$page}", '' );
				$html['contents'] = ob_get_clean();
			}
		}
		?>
		<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
		<div class="wrap">
			<h1>楽天</h1>
			<div id="crontrol-header">
				<nav class="nav-tab-wrapper"><?php echo $html['nav']; ?></nav>
			</div>
			<?php echo $html['contents']; ?>
		</div>
		<?php
	}

	/**
	 * SFTP CONNECT
	 *
	 * @param array|null $args args
	 */
	public function connect( $args = null ) {
		// 初回時のみ接続確認
		if ( null !== $this->data['connect'] ) {
			return $this->data['connect'];
		}
		global $n2;

		$user = $args['user'] ?? $n2->settings['楽天']['FTP']['user'] ?? '';
		$pass = $args['pass'] ?? $n2->settings['楽天']['FTP']['pass'] ?? '';
		$this->check_fatal_error( $user && $pass, '楽天セットアップが完了していません。' );

		WP_Filesystem();
		require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-ssh2.php';

		$opt        = array(
			'hostname' => $args['hostname'] ?? $n2->settings['楽天']['FTP']['upload_server'],
			'username' => $user,
			'password' => $pass,
		);
		$this->sftp = new WP_Filesystem_SSH2( $opt );

		if ( ! $this->sftp->connect() ) {
			$opt['password'] = rtrim( $opt['password'], 1 ) . '2';
			$this->sftp      = new WP_Filesystem_SSH2( $opt );
		}

		$this->data['connect'] = $this->sftp->connect();

		return $this->data['connect'];
	}

	/**
	 * キャビアップ
	 */
	public function img_upload( $files ) {
		global $n2;
		$name     = $files['name'];
		$type     = $files['type'];
		$tmp_name = $files['tmp_name'];

		$img_dir = rtrim( $n2->settings['楽天']['商品画像ディレクトリ'], '/' ) . '/';

		// テンポラリディレクトリ作成
		$tmp = wp_tempnam( __CLASS__, dirname( __DIR__ ) . '/' );
		unlink( $tmp );
		mkdir( $tmp );

		foreach ( $tmp_name as $k => $file ) {
			// 画像圧縮処理
			$quality = isset( $quality ) ? $quality : 50;
			move_uploaded_file( $file, "{$tmp}/{$name[$k]}" );
			exec( "mogrify -quality {$quality} {$tmp}/{$name[$k]}" );

			// jpg以外はエラー
			if ( strpos( $name[ $k ], '.jpg' ) === false ) {
				$this->data['log'][] = array(
					'status'  => 'ファイル形式(jpeg)が違います',
					'context' => $name[ $k ],
				);
				continue;
			}
			// $img_dir からキャビネットのディレクトリ構造を作成
			$remote_dir = preg_replace( '/^.*cabinet/', 'cabinet/images', $img_dir );
			preg_match( '/^([0-9]{0,2}[a-z]{2,4})([0-9]{2,3})[-]*([0-9]|sku)*\.jpg/', $name[ $k ], $m );
			if ( ! ( $m[1] ) ) {
				$this->data['log'][] = array(
					'status'  => 'ファイル名が違います',
					'context' => $name[ $k ],
				);
				continue;
			}

			// 商品画像の場合
			$this->mkdir( $remote_dir );

			// 事業者コードでフォルダ作成
			$remote_dir .= $m[1];
			$this->mkdir( $remote_dir );

			// 数字でサブフォルダ作成
			if ( (int) $m[2] >= 100 ) {
				$remote_dir .= "/{$m[2][0]}";
				$this->mkdir( $remote_dir );
			}
			$remote_file         = "{$remote_dir}/{$name[$k]}";
			$image_data          = file_get_contents( "{$tmp}/{$name[$k]}" );
			$uploaded            = $this->sftp->put_contents( $remote_file, $image_data );
			$this->data['log'][] = match ( $uploaded ) {
				true => array(
					'status'  => '転送成功',
					'context' => $name[ $k ],
				),
				default => array(
					'status'  => '転送失敗',
					'context' => $name[ $k ],
				),
			};
			if ( $uploaded ) {
				$this->n2data[ $m[1] . $m[2] ][] = str_replace( 'cabinet/images', '', $remote_file );
			}
		}
		exec( "rm -Rf {$tmp}" );
	}

	/**
	 * CSVアップロード
	 */
	public function csv_upload( $files ) {
		$name     = $files['name'];
		$type     = $files['type'];
		$tmp_name = $files['tmp_name'];
		$name     = array_map(
			function ( $n ) {
				foreach ( $this->data['rakuten_csv_name'] as $file_name ) {
					// リネーム処理
					if ( str_contains( $n, $file_name ) ) {
						$n = $file_name . $this->data['extensions'];
						break;
					}
				}
				return $n;
			},
			$name,
		);

		foreach ( $tmp_name as $k => $file ) {
			if ( ! str_contains( $name[ $k ], $this->data['extensions'] ) ) {
				$this->data['log'][] = array(
					'status'  => 'ファイル形式(csv)が違います',
					'context' => $name[ $k ],
				);
				continue;
			}
			if ( ! in_array( preg_replace( "/\\{$this->data['extensions']}/", '', $name[ $k ] ), $this->data['rakuten_csv_name'], true ) ) {
				$this->data['log'][] = array(
					'status'  => 'ファイル名に指定のワード(' . implode( ',', $this->data['rakuten_csv_name'] ) . ')が含まれていません',
					'context' => $name[ $k ],
				);
				continue;
			}
			$remote_file = 'ritem/batch/' . $name[ $k ];
			$file_data   = ( new WP_Filesystem_Direct( '' ) )->get_contents( $file );
			// 削除エラーチェック
			if ( str_contains( $name[ $k ], 'item-delete' ) ) {
				$this->check_fatal_error( ! $this->count_item_delete_row( $file_data ), '商品削除する行が含まれているため、アップロードを中止しました' );
			}
			$uploaded            = $this->sftp->put_contents( $remote_file, $file_data );
			$this->data['log'][] = match ( $uploaded ) {
				true => array(
					'status'  => '転送成功',
					'context' => $name[ $k ],
				),
				default => array(
					'status'  => '転送失敗',
					'context' => $name[ $k ],
				),
			};
			if ( $uploaded ) {
				$this->n2data[ $name[ $k ] ][] = $name[ $k ];
			}
			wp_delete_file( $file );
		}
	}

	/**
	 * get_contents
	 *
	 * @param string $path path
	 */
	private function get_contents( $path ) {
		$this->check_fatal_error( $path, 'pathが未設定です' );
		return $this->sftp->get_contents( $path );
	}

	/**
	 * dirlist
	 *
	 * @param string  $path           path
	 * @param boolean $include_hidden include_hidden
	 * @param boolean $recursive      recursive
	 */
	private function dirlist( $path = '', $include_hidden = true, $recursive = false ) {
		$recursive = $recursive ?: $this->data['params']['recursive'] ?? false;
		return $this->sftp->dirlist( $path, $include_hidden, $recursive );
	}


	/**
	 * アップロード
	 */
	private function upload( $path, $files ) {
		$this->check_fatal_error( $path, 'pathが未設定です' );
		$name     = $files['name'];
		$tmp_name = $files['tmp_name'];
		foreach ( $tmp_name as $k => $file ) {
			$remote_file         = "{$path}/{$name[ $k ]}";
			$file_data           = file_get_contents( $file );
			$uploaded            = $this->sftp->put_contents( $remote_file, $file_data );
			$this->data['log'][] = match ( $uploaded ) {
				true => array(
					'status'  => '転送成功',
					'context' => $name[ $k ],
				),
				default => array(
					'status'  => '転送失敗',
					'context' => $name[ $k ],
				),
			};
			if ( $uploaded ) {
				$this->n2data[ $name[ $k ] ][] = $name[ $k ];
			}
		}
	}

	/**
	 * Download
	 */
	private function download( $path, $files ) {
		$this->check_fatal_error( $path, 'pathが未設定です' );
		$this->check_fatal_error( $files, 'filesが未設定です' );

		$tmp_zip_uri = stream_get_meta_data( tmpfile() )['uri'];
		$zip         = new ZipArchive();
		$zip->open( $tmp_zip_uri, ZipArchive::CREATE );
		$zip_name = 'sftp';

		foreach ( $files as $file ) {
			$file_path = "{$path}/{$file}";
			$zip->addFromString( "{$zip_name}/{$file}", $this->sftp->get_contents( $file_path ) );
			$this->data['log'][] = array(
				'status'  => 'DL成功',
				'context' => $file,
			);
		}
		$zip->close();

		header( 'Content-Type: application/zip' );
		header( 'Content-Transfer-Encoding: Binary' );
		header( 'Content-Length: ' . filesize( $tmp_zip_uri ) );
		header( "Content-Disposition:attachment; filename = {$zip_name}" );
		while ( ob_get_level() ) {
			ob_end_clean(); // 出力バッファの無効化
		}
		// 出力処理
		readfile( $tmp_zip_uri );
	}

	/**
	 * mkdir
	 *
	 * @param string $path path
	 */
	private function mkdir( $path ) {
		$this->check_fatal_error( $path, 'pathが未設定です' );
		if ( $this->sftp->mkdir( $path ) ) {
			$this->data['log'][] = array(
				'status'  => '作成',
				'context' => $path,
			);
		}
	}

	/**
	 * delete
	 *
	 * @param array $paths paths
	 */
	private function delete( $paths, $recursive = false ) {
		$recursive = $recursive ?: $this?->data['params']['paths'] ?? false;
		foreach ( $paths as $path ) {
			$this->data['log'][] = match ( $this->sftp->delete( $path, $recursive ) ) {
				true => array(
					'status'  => '削除成功',
					'context' => $path,
				),
				default => array(
					'status'  => '削除失敗',
					'context' => "{$path}",
				),
			};
		}
	}

	/**
	 * move
	 *
	 * @param string  $source 　　　path
	 * @param string  $destination path
	 * @param boolean $overwrite   overwrite
	 */
	private function move( $source, $destination, $overwrite = false ) {
		$this->check_fatal_error( $source, 'sourceが未設定です' );
		$this->check_fatal_error( $destination, 'destinationが未設定です' );
		$overwrite           = $overwrite ?: $this?->data['params']['overwrite'] ?? false;
		$this->data['log'][] = match ( $this->sftp->move( $source, $destination, $overwrite ) ) {
			true => array(
				'status'  => '移動完了',
				'context' => "{$source} → {$destination}",
			),
			default => array(
				'status'  => '移動失敗',
				'context' => "{$source} → {$destination}",
			),
		};
	}


	/**
	 * log output
	 */
	public function log_output() {
		$data = array(
			'log' => $this->data['log'],
		);
		switch ( $this->data['params']['mode'] ) {
			case 'text':
				header( 'Content-Type: application/json; charset=utf-8' );
				echo implode(
					PHP_EOL,
					array_reduce(
						$data['log'],
						function ( $messages, $log ) {
							if ( isset( $log['status'] ) && isset( $log['context'] ) ) {
								$messages = array( ...$messages, "{$log['status']} {$log['context']}" );
							}
							return $messages;
						},
						array()
					)
				);
				exit;
			default:
				header( 'Content-Type: application/json; charset=utf-8' );
				echo wp_json_encode( $data, JSON_UNESCAPED_UNICODE );
				exit;
		}
	}

	/**
	 * 商品管理番号のみか判定
	 * ※商品管理番号（商品URL）のみの行があると、商品自体が削除されますのでご注意ください。
	 *
	 * @param string $csv_content csv content
	 */
	public function count_item_delete_row( $csv_content ) {
		$rows            = array_map( fn( $row ) => explode( ',', $row ), preg_split( "/\r\n|\n/", $csv_content ) );
		$header          = array_shift( $rows );
		$item_code_index = array_search( '商品管理番号（商品URL）', $header, true );
		// 商品管理番号（商品URL）のみの行をカウント
		return array_reduce(
			$rows,
			function ( $carry, $row ) use ( $item_code_index ) {
				$carry += $row[ $item_code_index ] && count( array_filter( $row ) ) === 1;
				return $carry;
			}
		);
	}


	/**
	 * 各パラメータ配列の作成
	 *
	 * $args > $_GET > $_POST > $default
	 *
	 * @param array $args args
	 */
	private function set_params( $args = array() ) {
		// $_GETを引数で上書き
		$params = wp_parse_args( $args, $_GET );
		// $_POSTを$paramsで上書き
		if ( wp_verify_nonce( $_POST['n2nonce'] ?? '', 'n2nonce' ) ) {
			$params = wp_parse_args( $params, $_POST );
		}

		$default = array(
			'mode' => 'json',
		);

		// $defaultを$paramsで上書き
		$params = wp_parse_args( $params, $default );

		/**
		 * [hook] n2_rakuten_sftp_set_params
		 */
		$this->data['params'] = apply_filters( mb_strtolower( get_called_class() ) . '_set_params', $params );
	}
	/**
	 * ファイル配列の作成
	 */
	private function set_files() {
		setlocale( LC_ALL, 'ja_JP.UTF-8' );
		$this->check_fatal_error( $this->data['params']['judge'], '転送モードが設定されていません' );
		/**
		 * [hook] n2_rakuten_sftp_set_files
		 */
		$this->data['files'] = apply_filters( mb_strtolower( get_called_class() ) . '_set_files' ,$_FILES['sftp_file'] );
		$this->check_fatal_error( $this->data['files']['tmp_name'][0], 'ファイルをセットしてください。' );
	}

	/**
	 * 致命的なエラーのチェック
	 *
	 * @param array  $data チェックするデータ
	 * @param string $message メッセージ
	 */
	protected function check_fatal_error( $data, $message ) {
		if ( ! $data ) {
			header( 'Content-Type: application/json; charset=utf-8' );
			http_response_code( 400 );
			echo wp_json_encode( array( 'message' => $message ), JSON_UNESCAPED_UNICODE );
			die;
		}
	}

	/**
	 * https://developer.wordpress.org/reference/classes/wp_filesystem_ssh2/
	 * API
	 *
	 * @return void
	 */
	public function api() {
		$this->check_fatal_error( $this->connect(), 'パスワードが違います。パスワードの有効期限が切れていないかRMSでご確認ください。' );
		$this->set_params();

		switch ( $this->data['params']['judge'] ) {
			case 'csv_upload':
				$this->set_files();
				$this->csv_upload( $this->data['files'] );
				break;
			case 'img_upload':
				$this->set_files();
				$this->img_upload( $this->data['files'] );
				break;
			case 'mkdir':
				$this->mkdir( $this->data['params']['path'] );
				break;
			case 'delete':
				$this->delete( $this->data['params']['paths'] );
				break;
			case 'move':
				$this->move( $this->data['params']['source'], $this->data['params']['destination'] );
				break;
			case 'download':
				$this->download( $this->data['params']['path'], $this->data['params']['files'] );
				break;
			case 'upload':
				$this->set_files();
				$this->upload( $this->data['params']['path'], $this->data['files'] );
				break;
			case 'get_contents':
				$this->data['log'] = $this->get_contents( $this->data['params']['path'] );
				$this->log_output();
				break;
			case 'dirlist':
				$this->data['log'] = $this->dirlist( $this->data['params']['path'] ?? '' );
				$this->log_output();
				break;
			default:
				$this->check_fatal_error( false, '未定義です' );
		}
		$this->insert_post();
		$this->log_output();
	}

	/**
	 * キャビ蓮舫用
	 */
	public function rakuten_auto_update() {
		global $n2;
		$img_dir = rtrim( $n2->settings['楽天']['商品画像ディレクトリ'], '/' );
		?>
		<div id="ss-rakuten-auto-update">
			<input id="n2nonce" type="hidden" name="n2nonce" value="<?php echo esc_attr( wp_create_nonce( 'n2nonce' ) ); ?>">
			<input id="n2nonce" type="hidden" name="imgDir" value="<?php echo esc_attr( $img_dir ); ?>">
			Loading...
		</div>
		<?php
	}

	/**
	 * カスタム投稿を追加
	 */
	public function register_post_type() {
		$args = array(
			'public'   => false,
			'supports' => array(
				'title',
				'revisions',
			),
		);
		register_post_type( $this->data['post_type'], $args );

		// キャビ蓮舫用カスタム投稿
		register_post_type( 'rakuten_auto_update', $args );
	}

	/**
	 * キャビ蓮舫用カスタム投稿
	 */
	public function insert_cabi_renho_log() {
		global $n2;
		$this->check_fatal_error( wp_verify_nonce( $_POST['n2nonce'] ?? '', 'n2nonce' ), '不正なパラメータ' );
		$this->data['params'] = $this->data['params'] ?: $_POST;
		$this->check_fatal_error( isset( $this->data['params']['title'] ), 'titleがありません' );
		$this->check_fatal_error( isset( $this->data['params']['post_content'] ), 'post_contentがありません' );

		$now       = date_i18n( 'Y M d h:i:s A' );
		$default   = array(
			'ID'           => $this->data['params']['post_id'] ?? 0,
			'post_author'  => $n2->current_user->ID,
			'post_status'  => 'pending',
			'post_type'    => 'rakuten_auto_update',
			'post_title'   => "[$now]: {$this->data['params']['title']}",
			'post_content' => wp_json_encode( $this->data['params']['post_content'], JSON_UNESCAPED_UNICODE ),
		);
		$insert_id = wp_insert_post( $default );
		// 初回はリビジョン作成
		if ( ! ( $this->data['params']['post_id'] ?? 0 ) ) {
			$this->data['params']['post_id'] = $insert_id;
			$this->insert_cabi_renho_log();
			return;
		}
		header( 'Content-Type: application/json; charset=utf-8' );
		echo wp_json_encode(
			array(
				'id'      => $insert_id,
				'message' => 'insert',
			)
		);
		exit;
	}

	/**
	 * insert
	 */
	public function insert_post() {
		global $n2;
		$timezone     = new DateTimeZone( 'Asia/Tokyo' );
		$now          = wp_date( 'Y M d h:i:s A', null, $timezone );
		$judge        = $this->data['params']['judge'];
		$post_content = array(
			'アップロード' => array(
				'data' => $this->n2data,
				'log'  => $this->data['log'],
				'date' => $now,
			),
			'転送モード'  => $judge,
		);
		if ( 'img_upload' === $judge ) {
			$post_content['RMS商品画像']['変更後'] = null;
		}
		$default = array(
			'ID'           => 0,
			'post_author'  => $n2->current_user->ID,
			'post_status'  => 'pending',
			'post_type'    => $this->data['post_type'],
			'post_title'   => "[$now] $judge",
			'post_content' => wp_json_encode( $post_content, JSON_UNESCAPED_UNICODE ),
		);
		wp_insert_post( $default );
	}

	/**
	 * update_post
	 *
	 * @param array $args args
	 */
	public function update_post( $args = array() ) {
		// $this->data['params']を$argsで上書き
		$this->data['params'] = wp_parse_args( $args, $this->data['params'] );
		$this->check_fatal_error( $this->data['params']['post_id'] ?? '', 'IDが未設定です' );
		$this->check_fatal_error( $this->data['params']['post_content'] ?? '', 'contentが未設定です' );
		$update_post = array(
			'ID'           => $this->data['params']['post_id'],
			'post_content' => $this->data['params']['post_content'],
		);
		$post        = get_post( $this->data['params']['post_id'] );
		if ( $post->post_parent ) {
			$update_post['post_parent'] = $post->post_parent;
		}
		wp_update_post( $update_post );
	}

	/**
	 * 時を戻すためのAPI
	 */
	public function checkout_revision() {
		// id check
		$this->check_fatal_error( isset( $this->data['params']['post_id'] ), 'ERROR: idが不正です' );

		// revision check
		$revision = get_post( $this->data['params']['post_id'] );
		$this->check_fatal_error( $revision, 'ERROR: データがありません' );

		// update check
		$data = json_decode( $revision->post_content, true );
		$this->check_fatal_error( $this->data['params']['update'] ?? '', wp_json_encode( $data, JSON_UNESCAPED_UNICODE ) );

		// 最新情報に更新
		$item_api   = new N2_RMS_Items_API();
		$rms_images = array_map(
			fn ( $item_code ) => array_map(
				fn( $image ) => $image['location'],
				$item_api->items_get( $item_code )['images'],
			),
			array_keys( $data['RMS商品画像']['変更後'] ),
		);
		$rms_images = array_combine( array_keys( $data['RMS商品画像']['変更後'] ), $rms_images );
		foreach ( $data['RMS商品画像']['変更前'] as $item_code => $path_arr ) {
			if ( empty( array_diff( $path_arr, $rms_images[ $item_code ] ) ) &&
				empty( array_diff( $rms_images[ $item_code ], $path_arr ) )
			) {
				continue;
			}
			$body                            = array(
				'images' => array_map(
					fn( $path ) => array(
						'type'     => 'CABINET',
						'location' => $path,
					),
					$path_arr,
				),
			);
			$this->data['log'][ $item_code ] = $item_api->items_patch( $item_code, wp_json_encode( $body ) );
		}

		if ( empty( $this->data['log'] ) ) {
			$this->data['log'] = array(
				'message' => '更新不要です',
			);
			return;
		}

		// 更新情報をリセット
		$data['RMS商品画像']['変更後'] = null;
		unset( $data['RMS商品画像']['変更前'] );
		$post   = array(
			'post_id'      => $revision->post_parent,
			'post_content' => wp_json_encode( $data, JSON_UNESCAPED_UNICODE ),
		);
		$author = $this->get_userid_by_usermeta( 'last_name', $data['事業者コード'] ?? '' );
		if ( $author ) {
			$post['post_author'] = $author;
		}
		$this->data['log'] = array(
			'id'      => wp_update_post( $update_post ),
			'message' => '更新完了！',
		);
	}

	/**
	 * usermetaからユーザーIDゲットだぜ
	 *
	 * @param string $field 名
	 * @param string $value 名
	 */
	protected function get_userid_by_usermeta( $field, $value ) {
		global $wpdb;
		$id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT user_id FROM $wpdb->usermeta WHERE meta_key = %s AND meta_value = %s LIMIT 1",
				$field,
				$value
			)
		);
		return $id;
	}
}
