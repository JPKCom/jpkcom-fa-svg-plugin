<?php
/*
Plugin Name: JPKCom FA inline SVG shortcode plugin
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

    function jpkcom_fasvg_enqueue_files() {

        wp_enqueue_style( 'jpkcom-fasvg-style', JPKCOM_FASVG_PLUGIN_URL . 'fa/css/svg-with-js.min.css', array(), '5.15.3', 'all' );
        $jpkcom_fa_inline_css = '.svg-inline--fa{color:inherit;fill:currentColor;}';
        wp_add_inline_style( 'jpkcom-fasvg-style', $jpkcom_fa_inline_css );

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


/**
 * Add 'jsvg' shortcode.
 */
function jsvg_code( $atts ) {

    $fa_svg_path = JPKCOM_FASVG_PLUGIN_PATH . 'fa/svgs/';
    $fa_svg_folder = 'solid/';
    $fa_svg_icon_name = 'square-full.svg';
    $fa_svg_title_id = 'svg-title-' . mt_rand( 10, 500000 );
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

    // Get file contens
    if( file_exists( $fa_svg_path . $fa_svg_folder . $fa_svg_icon_name ) ) {

        $fa_svg_source = file_get_contents( $fa_svg_path . $fa_svg_folder . $fa_svg_icon_name );

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
    $fa_svg_source = str_replace('<svg', '<svg' . $classHTML . $fa_svg_attributes . $fa_svg_title_aria . $styleHTML, $fa_svg_source);

    // Add SVG title tag
    if( $atts['title'] !== '' ) {

        $fa_svg_close_position = strpos( $fa_svg_source, '>' );

        if ( $fa_svg_close_position === false ) {

            $titleHTML = '';

        } else {

            $fa_svg_close_position = $fa_svg_close_position + 1;
            $fa_svg_source = substr_replace( $fa_svg_source, $titleHTML, $fa_svg_close_position, 0 );

        }

    }

    // Return SVG
    return $fa_svg_source;

}
add_shortcode( 'jsvg', 'jsvg_code' );
