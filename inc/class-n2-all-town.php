<?php
/**
 * class-n2-all-town.php
 *
 * @package neoneng
 */

if ( class_exists( 'N2_All_Town' ) ) {
	new N2_All_Town();
	return;
}

/**
 * AllTown
 */
class N2_All_Town {
	/**
	 * コンストラクタ
	 */
	public function __construct() {
		$this->cls = get_class( $this );
		add_action( "wp_ajax_{$this->cls}_getdata", array( $this, 'get_check_data' ) );
		add_action( "wp_ajax_nopriv_{$this->cls}_getdata", array( $this, 'get_check_data' ) );
	}

	/**
	 * サイトネットワークトップに表示するデータを返す
	 */
	public function get_check_data(){
		$town_name = filter_input( INPUT_GET, 'townName' );
		$site_url  = filter_input( INPUT_GET, 'siteUrl' );

		$ids = get_posts(
			array(
				'post_type'     => 'post',
				'posts_per_page' => -1,
				'fields'        => 'ids',
				'meta_key'      => '事業者確認',
				'meta_value'    => '確認済',
				'meta_compare'  => 'LIKE',
			)
		);

		$posts = get_posts(
			array(
				'post_type'      => 'post',
				'posts_per_page' => -1,
				'exclude'        => implode( ',', $ids ),
				'fields'         => 'ids',
			)
		);

		$comments = get_comments(
			array(
				'type'    => 'comment',
				'orderby' => 'comment_post_ID',
			)
		);

		$comment_count = 0;
		$now_id = '';
		foreach ( $comments as $comment ) {
			if ( $now_id === $comment->comment_post_ID ) {
				continue;
			}
			$now_id = $comment->comment_post_ID;
			if ( 'スチームシップ' !== $comment->comment_author ) {
				$comment_count ++;
			}
		}

		$result = array(
			'townName'     => $town_name,
			'count'        => count( $posts ),
			'townUrl'      => $site_url,
			'commentCount' => $comment_count,
		);

		echo wp_json_encode( $result );
		exit;
	}
}
