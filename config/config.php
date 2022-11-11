<?php
/**
 * config.php
 *
 * @package neoneng
 */

 // テーマ名をclass付与のプレフィックスに使いたいので定義
define( 'N2_THEME_NAME', get_template() );

// jsやcssの読み込みにパラメータつけてキャッシュ消すやつ
define( 'N2_CASH_BUSTER', preg_match( '/ore/', get_bloginfo( 'url' ) ) ? time() : wp_get_theme()->get( 'Version' ) );

define( 'N2_IPS', array(
		'219.111.49.195', // 波佐見
		'121.2.77.80', // 吉野ヶ里
		'202.241.189.211', // 糸島
		'219.111.24.202', // 有田
		'122.103.81.78', // 出島
		'183.177.128.173', // 土岐
		'217.178.116.13', // 大村
		'175.41.201.54', // SSVPN
	) 
);
