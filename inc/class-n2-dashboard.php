<?php
/**
 * class-n2-dashboard.php
 *
 * @package neoneng
 */

if ( class_exists( 'N2_Dashboard' ) ) {
	new N2_Dashboard();
	return;
}

/**
 * Dashboard
 */
class N2_Dashboard {
	/**
	 * コンストラクタ
	 */
	public function __construct() {
		add_action( 'wp_dashboard_setup', array( $this, 'remove_widgets' ) );
		add_action( 'wp_dashboard_setup', array( $this, 'add_widgets' ) );
		add_action( 'wp_dashboard_setup', array( $this, 'add_jichitai_widgets' ) );
	}

	/**
	 * remove_widgets
	 * デフォルトのダッシュボードをまっさらに
	 *
	 * @return void
	 */
	public function remove_widgets() {
		global $wp_meta_boxes;
		unset( $wp_meta_boxes['dashboard']['normal']['core']['dashboard_right_now'] ); // 現在の状況
		unset( $wp_meta_boxes['dashboard']['normal']['core']['dashboard_activity'] ); // アクティビティ
		unset( $wp_meta_boxes['dashboard']['normal']['core']['dashboard_recent_comments'] ); // 最近のコメント
		unset( $wp_meta_boxes['dashboard']['normal']['core']['dashboard_incoming_links'] ); // 被リンク
		unset( $wp_meta_boxes['dashboard']['normal']['core']['dashboard_plugins'] ); // プラグイン
		unset( $wp_meta_boxes['dashboard']['normal']['core']['dashboard_site_health'] ); // サイトヘルス
		unset( $wp_meta_boxes['dashboard']['side']['core']['dashboard_quick_press'] ); // クイック投稿
		unset( $wp_meta_boxes['dashboard']['side']['core']['dashboard_recent_drafts'] ); // 最近の下書き
		unset( $wp_meta_boxes['dashboard']['side']['core']['dashboard_primary'] ); // WordPressブログ

		// プラグインで追加された項目
		remove_meta_box( 'wp_mail_smtp_reports_widget_lite', 'dashboard', 'normal' ); // WP Mail SMTP
		remove_menu_page( 'wp-mail-smtp' );

		remove_action( 'welcome_panel', 'wp_welcome_panel' );
	}
	/**
	 * add_widgets
	 * ダッシュボードに項目追加
	 *
	 * @return void
	 */
	public function add_widgets() {
		if ( current_user_can( 'ss_crew' ) ) {
			wp_add_dashboard_widget( 'custom_help_widget', '返礼率規定オーバーリスト', array( $this, 'dashboard_text' ) );
		}
	}
	/**
	 * dashboard_text
	 * ダッシュボードウィジェットに追加するテキスト
	 *
	 * @return void
	 */
	public function dashboard_text() {
		$args = array(
			'post_type'      => 'post',
			'posts_per_page' => -1,
			'orderby'        => 'meta_value',
			'meta_key'       => '返礼品コード',
			'order'          => 'ASC',
		);

		$wp_query = new WP_Query( $args );

		$return_rate_list_text = '';
		if ( $wp_query->have_posts() ) {
			$return_rate_list_text .= '<ul>';
			$return_rate_list_text .= '<li style="border-bottom:1px solid #bbb; background:#ccc;display:flex;padding:10px 0;"><span style="display:inline-block; width:60px;flex-shrink: 0;text-align:center;">返礼率</span><span style="display:inline-block; width:100px;flex-shrink: 0;text-align:center;">返礼品コード</span><span style="display:inline-block;width:100%;text-align:center;">返礼品名</span></li>';
			while ( $wp_query->have_posts() ) {
				$wp_query->the_post();
				$post            = get_post( get_the_ID() );
				$post_data       = N2_Functions::get_all_meta( $post );
				$post_edit_url   = get_edit_post_link();
				$goods_price     = ! empty( $post_data['価格'] ) && 0 !== $post_data['価格'] ? number_format( $post_data['価格'] ) : '-';
				$donation_amount = ! empty( $post_data['寄附金額'] ) && 0 !== $post_data['寄附金額'] ? number_format( $post_data['寄附金額'] ) : '-';
				$code            = ! empty( $post_data['返礼品コード'] ) ? $post_data['返礼品コード'] : '未(id:' . $post->ID . ')';
				$return_rate     = N2_Donation_Amount_API::calc_return_rate( $post_data ); // 返礼率計算
				if ( $return_rate > 30 ) {
					$return_rate_list_text .= '<li style="border-bottom:1px solid #ccc;padding:5px 0;"><a href="' . $post_edit_url . '" style="display:flex;"><span style="display:inline-block; width:60px;flex-shrink: 0;text-align:center;">' . $return_rate . '</span><span style="display:inline-block; width:100px;flex-shrink: 0;text-align:center;">' . $code . '</span><span style="display:inline-block;">' . get_the_title() . '</span></a></li>';
				}
			}
			$return_rate_list_text .= '</ul>';
		}
		if ( '' !== $return_rate_list_text ) {
			echo $return_rate_list_text;
		} else {
			echo '<p>返礼率が規定を超えている返礼品はありません。</p>';
		}

		wp_reset_postdata();
	}
	/**
	 * add_jichitai_widgets
	 * ダッシュボードに項目追加
	 *
	 * @return void
	 */
	public function add_jichitai_widgets() {
		global $n2;
		if ( '1' === $n2->settings['N2']['自治体確認'] ) {
			wp_add_dashboard_widget( 'jichitai_widget', '自治体チェック未リスト', array( $this, 'dashboard_jichitai_check_list' ) );
		}
	}
	/**
	 * dashboard_jichitai_check_list
	 * ダッシュボードウィジェットに追加する自治体チェック未リスト
	 *
	 * @return void
	 */
	public function dashboard_jichitai_check_list() {
		$args = array(
			'post_type'      => 'post',
			'posts_per_page' => -1,
			'orderby'        => 'meta_value',
			'meta_key'       => '自治体確認',
			'order'          => 'DESC',
		);

		$wp_query = new WP_Query( $args );

		$return_rate_list_text = '';
		if ( $wp_query->have_posts() ) {
			$return_rate_list_text .= '<ul>';
			$return_rate_list_text .= '<li style="border-bottom:1px solid #bbb; background:#ccc;display:flex;padding:10px 0;"><span style="display:inline-block; width:40px;flex-shrink: 0;text-align:center;">確認</span><span style="display:inline-block; width:60px;flex-shrink: 0;text-align:center;">登録</span><span style="display:inline-block; width:100px;flex-shrink: 0;text-align:center;">返礼品コード</span><span style="display:inline-block;width:120px;flex-shrink: 0;text-align:center;">返礼品名</span><span style="display:inline-block;width:100%;text-align:center;">却下理由</span></li>';
			while ( $wp_query->have_posts() ) {
				$wp_query->the_post();
				$post            = get_post( get_the_ID() );
				$post_data       = N2_Functions::get_all_meta( $post );
				$post_edit_url   = get_edit_post_link();
				$goods_price     = ! empty( $post_data['価格'] ) && 0 !== $post_data['価格'] ? number_format( $post_data['価格'] ) : '-';
				$donation_amount = ! empty( $post_data['寄附金額'] ) && 0 !== $post_data['寄附金額'] ? number_format( $post_data['寄附金額'] ) : '-';
				$code            = ! empty( $post_data['返礼品コード'] ) ? $post_data['返礼品コード'] : '未(id:' . $post->ID . ')';
				$jichitai_check  = ! empty( $post_data['自治体確認'] ) ? $post_data['自治体確認'] : '-';
				$reject_reason   = ! empty( $post_data['却下理由'] ) ? $post_data['却下理由'] : '-';
				$post_status     = $post->post_status;
				$return_rate     = N2_Donation_Amount_API::calc_return_rate( $post_data ); // 返礼率計算
				if ( 'draft' === $post_status ) {
					$post_status_name = '入力中';
				} elseif ( 'pending' === $post_status ) {
					$post_status_name = 'SS確認待ち';
				} elseif ( 'publish' === $post_status ) {
					$post_status_name = '準備中';
				} elseif ( 'registered' === $post_status ) {
					$post_status_name = '済';
				} else {
					$post_status_name = '未登録';
				}
				if ( '承諾' !== $jichitai_check && ( 'publish' === $post_status || 'registered' === $post_status ) ) {
					$return_rate_list_text .= '<li style="border-bottom:1px solid #ccc;padding:5px 0;"><a href="' . $post_edit_url . '" style="display:flex;"><span style="display:inline-block; width:40px;flex-shrink: 0;text-align:center;">' . $jichitai_check . '</span><span style="display:inline-block; width:60px;flex-shrink: 0;text-align:center;">' . $post_status_name . '</span><span style="display:inline-block; width:100px;flex-shrink: 0;text-align:center;">' . $code . '</span><span style="display:inline-block;width:120px;flex-shrink: 0;">' . get_the_title() . '</span><span style="display:inline-block;">' . $reject_reason . '</span></a></li>';
				}
			}
			$return_rate_list_text .= '</ul>';
		}
		if ( '' !== $return_rate_list_text ) {
			echo $return_rate_list_text;
		} else {
			echo '<p>自治体チェックが済んでいない返礼品はありません。</p>';
		}

		wp_reset_postdata();
	}
}
