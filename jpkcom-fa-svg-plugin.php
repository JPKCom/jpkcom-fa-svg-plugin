<?php
/*
Plugin Name: JPKCom Font Awesome shortcode plugin
Plugin URI: https://github.com/JPKCom/jpkcom-wptheme-plugin
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