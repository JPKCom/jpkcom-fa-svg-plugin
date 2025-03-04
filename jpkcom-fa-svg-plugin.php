<?php
/*
Plugin Name: JPKCom FA inline SVG shortcode plugin
Plugin URI: https://github.com/JPKCom/jpkcom-fa-svg-plugin
Description: A plugin for loading inline SVGs from Font Awesome (Pro) v5.15.4 using a shortcode.
Version: 2.0.5
Author: Jean Pierre Kolb <jpk@jpkc.com>
Author URI: https://www.jpkc.com/
Contributors: JPKCom
Tags: Font, FA, FontAweseome, SVG, Inline, Shortcode, HTML, A11y, Gutenberg
Requires at least: 6.7
Tested up to: 6.7
Requires PHP: 8.3
Network: true
Stable tag: trunk
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
GitHub Plugin URI: JPKCom/jpkcom-fa-svg-plugin
Primary Branch: main
*/

if ( ! defined( constant_name: 'WPINC' ) ) {
    die;
}

define( constant_name: 'JPKCOM_FASVG_PLUGIN_PATH', value: plugin_dir_path( __FILE__ ) );
define( constant_name: 'JPKCOM_FASVG_PLUGIN_URL', value: plugin_dir_url( __FILE__ ) );

$jpkcom_fasvg_upload_dir = wp_upload_dir();
$jpkcom_fasvg_path = $jpkcom_fasvg_upload_dir['basedir'] . '/jpkcom_fasvg/';

define( constant_name: 'JPKCOM_FASVG_PATH', value: $jpkcom_fasvg_path );

$jpkcom_fasvg_upload_url = wp_upload_dir();
$jpkcom_fasvg_url = $jpkcom_fasvg_upload_url['baseurl'] . '/jpkcom_fasvg/';

define( constant_name: 'JPKCOM_FASVG_URL', value: $jpkcom_fasvg_url );

/**
 * Enqueue needed Font Awesome styles in head.
 */
if ( ! function_exists( function: 'jpkcom_fasvg_enqueue_files' ) ) {

    function jpkcom_fasvg_enqueue_files(): void {

        wp_enqueue_style( 'jpkcom-fasvg-style', JPKCOM_FASVG_URL . 'css/svg-with-js.min.css', array(), '5.15.4', 'all' );
        $jpkcom_fa_inline_css = '.svg-inline--fa{color:inherit;fill:currentColor;}';
        wp_add_inline_style( 'jpkcom-fasvg-style', $jpkcom_fa_inline_css );

	}

}

add_action( 'wp_enqueue_scripts', 'jpkcom_fasvg_enqueue_files' );


/**
 * Enqueue block editor assets.
 */
if ( ! function_exists( function: 'jpkcom_fasvg_enqueue_gutenberg_files' ) ) {

    function jpkcom_fasvg_enqueue_gutenberg_files(): void {

        wp_enqueue_style( 'jpkcom-fasvg-gutenberg-style', JPKCOM_FASVG_URL . 'css/svg-with-js.min.css', false );
        $jpkcom_fa_inline_css = '.svg-inline--fa{color:inherit;fill:currentColor;}';
        wp_add_inline_style( 'jpkcom-fasvg-gutenberg-style', $jpkcom_fa_inline_css );

    }

}

add_action( 'enqueue_block_editor_assets', 'jpkcom_fasvg_enqueue_gutenberg_files' );


/**
 * Enable shortcode support in menu items.
 */
if ( ! function_exists ( function: 'jpkcom_fasvg_navigation_fa' ) ) {

    function jpkcom_fasvg_navigation_fa( $menu_items ): mixed {

        $jpkcom_fasvg_short_tag = '[jsvg';

        foreach ( $menu_items as $menu_item ) {

            if ( strpos( haystack: $menu_item->title, needle: $jpkcom_fasvg_short_tag ) !== false ) {

                $menu_item->title = do_shortcode( $menu_item->title );

            } else {

                $menu_item->title = $menu_item->title;

            }

        }

        return $menu_items;

    }
}

add_filter( 'wp_nav_menu_objects', 'jpkcom_fasvg_navigation_fa' );


/**
 * Add 'jsvg' shortcode.
 */
function jsvg_code( $atts ): array|string {

    $fa_svg_path = JPKCOM_FASVG_PATH . 'svgs/';
    $fa_svg_folder = 'solid/';
    $fa_svg_icon_name = 'square-full.svg';
    $fa_svg_title_id = 'svg-title-' . mt_rand( min: 10, max: 500000 );
    $fa_svg_title_aria = ' aria-hidden="true"';
    $fa_svg_attributes = ' role="img"';
    $fa_svg_source = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M512 512H0V0h512v512z"/></svg>';

    // Attributes
    $atts = shortcode_atts(
        array (
            'type' => '',
            'name' => '',
            'class' => '',
            'style' => '',
            'title' => '',
        ),
        $atts,
        'jsvg'
    );

    // Folder selection
    if( $atts['type'] !== '' ) {

        if( $atts['type'] === 'fas' ) {

            $fa_svg_folder = 'solid/';

        } elseif( $atts['type'] === 'fal' ) {

            $fa_svg_folder = 'light/';

        } elseif( $atts['type'] === 'far' ) {

            $fa_svg_folder = 'regular/';

        } elseif( $atts['type'] === 'fad' ) {

            $fa_svg_folder = 'duotone/';

        } elseif( $atts['type'] === 'fab' ) {

            $fa_svg_folder = 'brands/';

        } else {

            $fa_svg_folder = 'solid/';

        }

    }

    // File name selection
    if( $atts['name'] !== '' ) {

        $fa_svg_icon_name = esc_attr( $atts['name'] ) . '.svg';

    } else {

        $fa_svg_folder = 'solid/';
        $fa_svg_icon_name = 'square-full.svg';

    }

    // Get file contents
    if( file_exists( filename: $fa_svg_path . $fa_svg_folder . $fa_svg_icon_name ) ) {

        $fa_svg_source = file_get_contents( filename: $fa_svg_path . $fa_svg_folder . $fa_svg_icon_name );

    }

    // Set class attribute
    if( $atts['class'] !== '' ) {

        $classHTML = ' class="svg-inline--fa fa-' . esc_attr( $atts['name'] ) . ' ' . esc_attr( $atts['class'] ) . '"';

    } else {

        $classHTML = ' class="svg-inline--fa fa-' . esc_attr( $atts['name'] ) . '"';
    }

    // Set style attribute
    if( $atts['style'] !== '' ) {

        $styleHTML = ' style="' . esc_attr( $atts['style'] ) . '"';

    }

    // Set SVG title and ARIA label attribute
    if( $atts['title'] !== '' ) {

        $titleHTML = '<title id="' . $fa_svg_title_id . '">' . esc_attr( $atts['title'] ) . '</title>';
        $fa_svg_title_aria = ' aria-labelledby="' .  $fa_svg_title_id . '"';

    }

    // Add attributes to SVG
    $fa_svg_source = str_replace(search: '<svg', replace: '<svg' . $classHTML . $fa_svg_attributes . $fa_svg_title_aria . $styleHTML, subject: $fa_svg_source);

    // Add SVG title tag
    if( $atts['title'] !== '' ) {

        $fa_svg_close_position = strpos( haystack: $fa_svg_source, needle: '>' );

        if ( $fa_svg_close_position === false ) {

            $titleHTML = '';

        } else {

            $fa_svg_close_position = $fa_svg_close_position + 1;
            $fa_svg_source = substr_replace( string: $fa_svg_source, replace: $titleHTML, offset: $fa_svg_close_position, length: 0 );

        }

    }

    // Return SVG
    return $fa_svg_source;

}

add_shortcode( 'jsvg', 'jsvg_code' );
