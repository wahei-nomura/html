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
