<?php
/**
 * class-n2-page-redirect.php
 *
 * @package neoneng
 */

if ( class_exists( 'N2_Page_Redirect' ) ) {
	new N2_Page_Redirect();
	return;
}

/**
 * Page_Redirect
 */
class N2_Page_Redirect {
	/**
	 * コンストラクタ
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'page_redirect_other_municipality' ) );
	}

	/**
	 * not_edit_user
	 */
	public function page_redirect_other_municipality() {
		$referer = $_SERVER['HTTP_REFERER'];
		$now_url = $_SERVER['REQUEST_URI'];
		$ref_url_array = parse_url( $referer );
		$ref_path = $ref_url_array['path'];
		$now_url_array = parse_url( $now_url );
		$now_path = $now_url_array['path'];
		$ref_path_array = explode( '/',  $ref_path);
		$now_path_array = explode( '/',  $now_path);
		$jump_url = 'https://' . $_SERVER['HTTP_HOST'];
		if( 'wp-admin' === $ref_path_array[1] || 'wp-admin' === $now_path_array[1] || $ref_path_array[1] === $now_path_array[1] ){
			return;
		}
		foreach( $ref_path_array as $key => $refpath){
			if( 1 === $key ){
				$jump_url .= '/' . $now_path_array[1];
			}else{
				if( '' !== $refpath ){
					$jump_url .= '/' . $refpath;
				}	
			}
		}
		$check_url = 'https://ss:ss@' . $_SERVER['HTTP_HOST'] . '/' . $now_path_array[1];
		$response = @file_get_contents($check_url, NULL, NULL, 0, 1);
		if ($response !== false) {
			wp_redirect($jump_url);
		}
		return;
	}
}
