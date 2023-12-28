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
		'rakuten_csv_name' => array( 'normal-item', 'item-cat' ),
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
		add_action( 'wp_ajax_n2_rakuten_sftp_upload_to_rakuten', array( $this, 'upload_to_rakuten' ) );
		add_action( 'wp_ajax_n2_rakuten_sftp_explorer', array( $this, 'explorer' ) );
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
				$args = match ( $page ) {
					'sftp-error-log' => $this->error_log_args(),
					'sftp-upload'    => $this->upload_args(),
					default     => null,
				};
				ob_start();
				get_template_part( "{$this->settings['template']}/{$page}", '', $args );
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
	 * SFTP EXPLORER　ARGS
	 */
	public function explorer_args() {
		$args = array();
		$this->connect();
		$args['connect'] = $this->data['connect'];
		if ( ! $args['connect'] ) {
			return $args;
		}
		$args['dirlist'] = $this->sftp->dirlist( '/', true, true );
		return $args;
	}

	/**
	 * エラーログテンプレート用の変数
	 */
	public function error_log_args() {
		$args = array();
		$this->connect();
		$args['connect'] = $this->data['connect'];

		if ( ! $args['connect'] ) {
			return $args;
		}
		$args['dir']  = 'ritem/logs';
		$args['logs'] = $this->sftp->dirlist( $args['dir'] );
		$args['logs'] = array_reverse( $args['logs'] );
		$args['logs'] = array_map(
			function ( $log ) use ( $args ) {
				$contents = $this->sftp->get_contents( "{$args['dir']}/{$log['name']}" );
				$contents = htmlspecialchars( mb_convert_encoding( $contents, 'utf-8', 'sjis' ) );
				return array(
					'name'     => $log['name'],
					'time'     => wp_date( 'Y M d', $log['lastmodunix'] ),
					'contents' => $contents,
				);
			},
			$args['logs']
		);
		return $args;
	}

	/**
	 * エラーログ
	 */
	public function error_log() {
		$this->connect();
		check_fatal_error( $this->data['connect'], '接続エラー' );
		$error_dir = 'ritem/logs';
		$logs      = $this->sftp->dirlist( $error_dir );
		return array_map(
			function ( $log ) use ( $args ) {
				$contents = $this->sftp->get_contents( "{$error_dir}/{$log['name']}" );
				$contents = htmlspecialchars( mb_convert_encoding( $contents, 'utf-8', 'sjis' ) );
				return array(
					'name'     => $log['name'],
					'time'     => wp_date( 'Y M d', $log['lastmodunix'] ),
					'contents' => $contents,
				);
			},
			array_reverse( $logs )
		);
	}

	/**
	 * アップロードテンプレート用の変数
	 */
	public function upload_args() {
		return array(
			'action'    => 'n2_rakuten_sftp_upload_to_rakuten',
			'radio'     => array(
				'img_upload' => '商品画像',
				'csv_upload' => '商品CSV',
			),
			'file'      => 'sftp_file[]',
			'post_type' => $this->data['post_type'],
		);
	}

	/**
	 * 楽天への転送機能（超突貫）
	 *
	 * @return void
	 */
	public function upload_to_rakuten() {
		$this->check_fatal_error( $this->connect(), 'パスワードが違います' );
		$this->set_params();
		$this->{$this->data['params']['judge']}();
		$this->log_output();
	}

	/**
	 * キャビアップ
	 */
	public function img_upload() {
		$this->set_files();
		global $n2;
		$name     = $this->data['files']['name'];
		$type     = $this->data['files']['type'];
		$tmp_name = $this->data['files']['tmp_name'];

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
			preg_match( '/^([0-9]{0,2}[a-z]{2,4})([0-9]{2,3})[-]*[0-9]*\.jpg/', $name[ $k ], $m );
			if ( ! ( $m[1] ) ) {
				$this->data['log'][] = array(
					'status'  => 'ファイル名が違います',
					'context' => $name[ $k ],
				);
				continue;
			}

			// 商品画像の場合
			$this->mkdir( $remote_dir );
			$remote_dir .= $m[1];
			$this->mkdir( $remote_dir );

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
		$this->insert_post();
	}

	/**
	 * CSVアップロード
	 */
	public function csv_upload() {
		$this->set_files();
		$name     = $this->data['files']['name'];
		$type     = $this->data['files']['type'];
		$tmp_name = $this->data['files']['tmp_name'];
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
			$remote_file         = "ritem/batch/{$name[ $k ]}";
			$file_data           = file_get_contents( $file );
			$uploaded            = $this->sftp->put_contents( $remote_file, $file_data );
			$this->data['log'][] = match ( $uploaded ) {
				true => array(
					'status'  => '転送成功',
					'context' => $this->data['files']['name'][ $k ],
				),
				default => array(
					'status'  => '転送失敗',
					'context' => $this->data['files']['name'][ $k ],
				),
			};
			if ( $uploaded ) {
				$this->n2data[ $this->data['files']['name'][ $k ] ][] = $this->data['files']['name'][ $k ];
			}
		}
		$this->insert_post();
	}

	/**
	 * アップロード
	 */
	private function upload() {
		$this->set_files();
		$name     = $this->data['files']['name'];
		$type     = $this->data['files']['type'];
		$tmp_name = $this->data['files']['tmp_name'];
		foreach ( $tmp_name as $k => $file ) {
			$remote_file         = "{$this->data['params']['path']}/{$name[ $k ]}";
			$file_data           = file_get_contents( $file );
			$uploaded            = $this->sftp->put_contents( $remote_file, $file_data );
			$this->data['log'][] = match ( $uploaded ) {
				true => array(
					'status'  => '転送成功',
					'context' => $this->data['files']['name'][ $k ],
				),
				default => array(
					'status'  => '転送失敗',
					'context' => $this->data['files']['name'][ $k ],
				),
			};
			if ( $uploaded ) {
				$this->n2data[ $this->data['files']['name'][ $k ] ][] = $this->data['files']['name'][ $k ];
			}
		}
	}

	/**
	 * Download
	 */
	private function download() {

		$tmp_zip_uri = stream_get_meta_data( tmpfile() )['uri'];
		$zip         = new ZipArchive();
		$zip->open( $tmp_zip_uri, ZipArchive::CREATE );
		$zip_name = 'sftp';

		foreach ( $this->data['params']['files'] as $file ) {
			$file_path = "{$this->data['params']['path']}/{$file}";
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

		$this->insert_post();
		exit;
	}

	/**
	 * mkdir
	 *
	 * @param string $path path
	 */
	private function mkdir( $path ) {
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
		header( 'Content-Type: application/json; charset=utf-8' );
		echo wp_json_encode( $data, JSON_UNESCAPED_UNICODE );
		exit;
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
		$this->data['files'] = $_FILES['sftp_file'];
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
	 * エクスプローラー操作一覧
	 */
	public function explorer() {
		$this->check_fatal_error( $this->connect(), 'パスワードが違います' );
		$this->set_params();
		$recursive = (bool) $this->data['params']['recursive'] ?? false;
		$overwrite = (bool) $this->data['params']['overwrite'] ?? false;

		switch ( $this->data['params']['judge'] ) {
			case 'mkdir':
				$this->check_fatal_error( $this->data['params']['path'], 'pathが未設定です' );
				$this->mkdir( $this->data['params']['path'] );
				break;
			case 'delete':
				$this->check_fatal_error( $this->data['params']['paths'], 'pathsが未設定です' );
				$this->delete( $this->data['params']['paths'], $recursive );
				break;
			case 'move':
				$this->check_fatal_error( $this->data['params']['source'], 'sourceが未設定です' );
				$this->check_fatal_error( $this->data['params']['destination'], 'destinationが未設定です' );
				$this->move( $this->data['params']['source'], $this->data['params']['destination'], $overwrite );
				break;
			case 'download':
				$this->check_fatal_error( $this->data['params']['path'], 'pathが未設定です' );
				$this->check_fatal_error( $this->data['params']['files'], 'filesが未設定です' );
				$this->download();
				break;
			case 'upload':
				$this->check_fatal_error( $this->data['params']['path'], 'pathが未設定です' );
				$this->upload();
				break;
			case 'get_contents':
				$this->check_fatal_error( $this->data['params']['path'], 'pathが未設定です' );
				$data = $this->sftp->get_contents( $this->data['params']['path'] );
				header( 'Content-Type: application/json; charset=utf-8' );
				echo wp_json_encode( $data, JSON_UNESCAPED_UNICODE );
				exit;
			case 'dirlist':
				$data = $this->sftp->dirlist( $this->data['params']['path'] ?? '', true, true );
				header( 'Content-Type: application/json; charset=utf-8' );
				echo wp_json_encode( $data, JSON_UNESCAPED_UNICODE );
				exit;
			default:
				$this->check_fatal_error( false, '未定義です' );
		}
		$this->insert_post();
		$this->log_output();
	}

	/**
	 * カスタム投稿を追加
	 */
	public function register_post_type() {
		$args = array(
			'label'    => 'sftp_log',
			'public'   => false,
			'supports' => array(
				'title',
				'revisions',
			),
		);
		register_post_type( $this->data['post_type'], $args );
	}

	/**
	 * insert
	 */
	public function insert_post() {
		global $n2;
		$now                            = wp_date( 'Y M d h:i:s A' );
		$judge                          = $this->data['params']['judge'];
		$post_content                   = array(
			'アップロード' => array(
				'data' => $this->n2data,
				'log'  => $this->data['log'],
				'date' => $now,
			),
			'転送モード'  => $judge,
		);
		$default                        = array(
			'ID'           => 0,
			'post_author'  => $n2->current_user->ID,
			'post_status'  => 'pending',
			'post_type'    => $this->data['post_type'],
			'post_title'   => "[$now] $judge",
			'post_content' => wp_json_encode( $post_content, JSON_UNESCAPED_UNICODE ),
		);
		$post_content['RMS商品画像']['変更後'] = null;
		// リビジョン生成用
		$this->update_post(
			array(
				'post_id'      => wp_insert_post( $default ),
				'post_content' => wp_json_encode( $post_content, JSON_UNESCAPED_UNICODE ),
			),
		);
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
		$this->data['log'] = array(
			'id'      => wp_update_post( $update_post ),
			'message' => '更新完了！',
		);
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
		$item_api   = new N2_RMS_Item_API();
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
			'ID'           => $revision->post_parent,
			'post_status'  => $revision->post_status,
			'post_type'    => $this->data['post_type'],
			'post_title'   => $revision->post_title,
			'post_content' => wp_json_encode( $data, JSON_UNESCAPED_UNICODE ),
		);
		$author = $this->get_userid_by_usermeta( 'last_name', $data['事業者コード'] ?? '' );
		if ( $author ) {
			$post['post_author'] = $author;
		}
		wp_insert_post( $post );
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
