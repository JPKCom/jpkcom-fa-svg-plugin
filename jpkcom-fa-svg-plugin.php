<?php
/*
Plugin Name: JPKCom Font Awesome shortcode plugin
Plugin URI: https://github.com/JPKCom/jpkcom-fa-svg-plugin
Description: A plugin for loading inline SVGs from Font Awesome (Pro) using a shortcode
Version: 1.0.0
Author: Jean Pierre Kolb
Author URI: https://www.jpkc.com/
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
*/

if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'JPKCOM_FASVG_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'JPKCOM_FASVG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Enqueue needed Font Awesome styles in head.
 */
if ( ! function_exists( 'jpkcom_fasvg_enqueue_files' ) ) {

	function jpkcom_enqueue_files() {

		wp_enqueue_style( 'jpkcom-fasvg-style', JPKCOM_FASVG_PLUGIN_URL . 'fa/css/svg-with-js.min.css', array(), '5.15.3', 'all' );

	}

}
add_action( 'wp_enqueue_scripts', 'jpkcom_fasvg_enqueue_files' );

/**
 * Enable shortcode support in menu items.
 */
if ( ! function_exists ( 'jpkcom_fasvg_navigation_fa' ) ) {

    function jpkcom_fasvg_navigation_fa( $menu_items ) {

        $jpkcom_fasvg_short_tag = '[jsvg';

        foreach ( $menu_items as $menu_item ) {

            if ( strpos( $menu_item->title, $jpkcom_fasvg_short_tag ) !== false ) {

                $menu_item->title = do_shortcode( $menu_item->title );

            } else {

                $menu_item->title = $menu_item->title;

            }

        }

        return $menu_items;

    }
}
add_filter( 'wp_nav_menu_objects', 'jpkcom_fasvg_navigation_fa' );
