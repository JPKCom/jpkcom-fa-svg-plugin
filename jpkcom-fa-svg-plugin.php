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

