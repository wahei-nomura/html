<?php
/**
 * class-n2-choice.php
 *
 * @package neoneng
 */

 if ( class_exists( 'N2_Choice' ) ) {
	new N2_Choice();
	return;
}

class N2_Choice {

    //コンストラクト
    public function __construct() {
        add_action( 'wp_ajax_choice', array( $this, 'create_tsv' ) );
    }

    /**
	 * チョイスのエクスポート用TSV生成
	 *
	 * @return void
	 */
    public function create_tsv() {
        $header_data = yaml_parse_file( get_theme_file_path( '/config/n2-file-header.yml' ) );
        $items_arr = array();
        $check_arr = array();
        $delimiter = '" "'; //tsvデータ
        $opt = get_option( 'N2_Setupmenu' );

        // あとでヘッダの上の連結するのに必要
		$tsv_title = $header_data[ 'choice' ][ 'tsv_header' ][ 'title' ];

		$header0 = $header_data[ 'choice' ][ 'tsv_header' ][ 'value0' ];
        $header1 = $header_data[ 'choice' ][ 'tsv_header' ][ 'value1' ];
        $auth = $header_data[ 'choice' ][ 'auth' ];

        // プラグイン側でヘッダーを編集
		$header0 = apply_filters( 'n2_export_choice_tsv_header', $header0 );
        $header1 = apply_filters( 'n2_export_choice_tsv_header', $header1 );

        // ajaxで渡ってきたpostidの配列
		$ids = explode( ',', filter_input( INPUT_POST, 'choice' ) );

        $sumple_header = trim( file_get_contents( str_replace( "//", "//{$auth[ 'user' ]}:{$auth[ 'pass' ]}@", "{$auth[ 'url' ]}" ) ) );
        $sumple_header = array_flip( explode( "\t", $sumple_header ) );

        foreach( $ids as $id ){
            $items_arr[ $id ] = array(...$sumple_header, ...get_post_meta( $id, '', false ) );
            foreach ( $items_arr[ $id ] as $k => $v ) {
                #初期化
                if ( in_array( $k, $header0 ) ) {
                    $items_arr[ $id ][ $k ] = 0;
                } else if ( in_array( $k, $header1 ) ) {
                    $items_arr[ $id ][ $k ] = 1;
                } else {
                    $items_arr[ $id ][ $k ] = "";
                }
            }

            $check_arr[ $id ][ '寄付金額エラー' ] = get_post_meta( $id, "寄附金額", true ) == 0 ? get_post_meta( $id, "返礼品コード", true ) : '';

            $arr = array(
                '管理コード'      => strtoupper( get_post_meta( $id, "返礼品コード", true ) ),
                '（必須）お礼の品名'       => N2_Functions::special_str_convert( get_the_title( $id ) ) . " [{$items_arr[$id]['管理コード']}]",
                // サイト表示事業者名：ポータル表示用名称が登録されてたら優先。「記載しない」になってたら空欄で出力。
                'サイト表示事業者名'      => get_post_meta( $id, "提供事業者名", true ) 
                                                ?: 
                                                    (
                                                        get_the_author_meta( "portal", get_post_field( "post_author", $id ) ) 
                                                        ? (
                                                            get_the_author_meta( "portal", get_post_field( "post_author", $id ) ) === '記載しない' 
                                                                ? '' 
                                                                : get_the_author_meta("portal", get_post_field( "post_author", $id ) ) 
                                                            ) 
                                                        : get_the_author_meta( "first_name", get_post_field( "post_author", $id ) )
                                                    ),
                '（条件付き必須）必要寄付金額'      => get_post_meta( $id, "寄附金額", true ),
                'キャッチコピー'  => N2_Functions::special_str_convert( get_post_meta( $id, "キャッチコピー１", true ) ),
                '説明'     => N2_Functions::special_str_convert( get_post_meta( $id, "説明文", true ) ) . 
                                (
                                    get_post_meta( $id, "検索キーワード", true )  
                                        ? "\n\n" . ( N2_Functions::special_str_convert( get_post_meta( $id, "検索キーワード", true ) ) ) 
                                        : ""
                                ) . 
                                (
                                    preg_match( '/hasami/', esc_url( $_SERVER[ 'REQUEST_URI' ] ) ) 
                                        ? ( get_post_meta($id, "楽天カテゴリー", true )  
                                            ? "\n\n" . ( N2_Functions::special_str_convert( get_post_meta( $id, "楽天カテゴリー", true ) ) ) 
                                            : '' ) 
                                        : ''
                                ) . "\n\n" . $opt['add_text'][get_bloginfo('name')],
                '容量'    => N2_Functions::special_str_convert( get_post_meta( $id, "内容量・規格等", true ) ) . 
                                (
                                    (
                                        preg_match( '/hasami|toki|ureshino|tajimi/', esc_url( $_SERVER[ 'REQUEST_URI' ] ) ) 
                                            ? (
                                                ( get_post_meta($id, "電子レンジ対応", true ) != "-" || get_post_meta( $id, "オーブン対応", true ) != "-" || get_post_meta( $id, "食洗機対応", true ) != "-" ) 
                                                    ? "\n\n【対応機器】\n電子レンジ" . get_post_meta( $id, "電子レンジ対応", true ) . " / オーブン" . get_post_meta( $id, "オーブン対応", true ) . " / 食器洗浄機" . get_post_meta( $id, "食洗機対応", true ) . 
                                                        (
                                                            ( get_post_meta( $id, "対応機器備考", true ) != "" ) 
                                                                ? "\n※" . get_post_meta( $id, "対応機器備考", true ) 
                                                                : ""
                                                        ) 
                                                    : ""
                                                ) 
                                            : ""
                                    ) . 
                                    (
                                        ( preg_match( '/hasami|toki|tajimi/', esc_url( $_SERVER[ 'REQUEST_URI' ] ) ) && get_post_meta( $id, "やきもの", true ) !== "やきものではない" ) 
                                            ? "\n\n【お申し込み前に必ずお読みください】\nお届けする返礼品には個体差が生じる場合があります。\n下部記載の「個体差について」、「破損・欠陥について」を必ずご一読のうえ、お申し込みください。" . 
                                                (
                                                    preg_match( '/toki|tajimi/', esc_url($_SERVER[ 'REQUEST_URI' ] ) ) 
                                                        ? "\n\n【色について】\n商品写真はできる限り実物の色に近づけるよう徹底しておりますが、 お使いのモニター設定、お部屋の照明等により実際の商品と色味が異なる場合がございます。" 
                                                        : ""
                                                ) 
                                            : ""
                                    )
                                ) . 
                                (
                                    get_post_meta($id, "原料原産地", true) 
                                        ? "\n\n【原料原産地】\n" . N2_Functions::special_str_convert(get_post_meta($id, "原料原産地", true)) 
                                        : ""
                                ) . 
                                (
                                    get_post_meta($id, "加工地", true) 
                                        ? "\n\n【加工地】\n" . N2_Functions::special_str_convert(get_post_meta($id, "加工地", true)) 
                                        : ""
                                ),
                '申込期日'      => get_post_meta($id, "申込期間", true),
                '発送期日'        => get_post_meta($id, "配送期間", true),
                '（必須）アレルギー表示'        => '',
                '地場産品類型番号'        => get_post_meta($id, "地場産品類型", true) 
                                                ? get_post_meta($id, "地場産品類型", true) . "|" . get_post_meta($id, "類型該当理由", true) 
                                                : "",
                '消費期限'        => (get_post_meta($id, "賞味期限", true) != "") 
                                        ? "【賞味期限】\n" . N2_Functions::special_str_convert(get_post_meta($id, "賞味期限", true)) . 
                                            (
                                                (get_post_meta($id, "消費期限", true) != "") 
                                                    ? "\n\n【消費期限】\n" . N2_Functions::special_str_convert(get_post_meta($id, "消費期限", true)) 
                                                    : ""
                                            ) 
                                        : N2_Functions::special_str_convert(get_post_meta($id, "消費期限", true)),
                '（必須）常温配送'        => (get_post_meta($id, "発送方法", true) == "常温") ? 1 : 0,
                '（必須）冷蔵配送'        => (get_post_meta($id, "発送方法", true) == "冷蔵") ? 1 : 0,
                '（必須）冷凍配送'        => (get_post_meta($id, "発送方法", true) == "冷凍") ? 1 : 0,
                '（必須）包装対応'        => (get_post_meta($id, "包装対応", true) == "冷凍") ? 1 : 0,
                '（必須）のし対応'        => (get_post_meta($id, "のし対応", true) == "冷凍") ? 1 : 0,
                '（必須）定期配送対応'        => (get_post_meta($id, "定期便", true) == 1 || get_post_meta($id, "定期便", true) == "") ? 0 : 1,
                '（必須）クレジット決済限定'        => (get_post_meta($id, "クレジット決済限定", true) == "クレジット決済限定") ? 1 : 0,
                'カテゴリー'        => "",
                'お礼の品画像'        => mb_strtolower(strtoupper( get_post_meta( $id, "返礼品コード", true ) )) . ".jpg",
                '受付開始日時'        => "2025/04/01 00:00",
                '（条件付き必須）還元率（%）'        => 30,
            );

            // 内容を追加、または上書きするためのフック
			$items_arr[ $id ] = array(...$items_arr[ $id ], ...apply_filters( 'n2_item_export_choice_items', $arr, $id ));

        }
        //var_dump($sumple_header);
        N2_Functions::download_csv( 'choice', array_keys($sumple_header), $items_arr, $tsv_title ,'tsv');
    }
}

