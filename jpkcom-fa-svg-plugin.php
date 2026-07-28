<?php
/*
Plugin Name: JPKCom FA inline SVG shortcode
Plugin URI: https://github.com/JPKCom/jpkcom-fa-svg-plugin
Description: A plugin for loading inline SVGs from Font Awesome (Pro) v5.15.4 using a shortcode.
Version: 2.0.12
Author: Jean Pierre Kolb <jpk@jpkc.com>
Author URI: https://www.jpkc.com/
Contributors: JPKCom
Tags: FontAwesome, SVG, Inline, Shortcode, Gutenberg
Requires at least: 6.9
Tested up to: 7.0
Requires PHP: 8.3
Network: true
Stable tag: 2.0.12
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

declare(strict_types=1);

if ( ! defined( constant_name: 'WPINC' ) ) {
    die;
}


/**
 * Plugin Constants
 *
 * @since 2.0.9
 */
if ( ! defined( 'JPKCOM_FASVG_VERSION' ) ) {
    define( 'JPKCOM_FASVG_VERSION', '2.0.12' );
}


/**
 * Initialize Plugin Updater
 *
 * Loads and initializes the GitHub-based plugin updater with SHA256 checksum verification.
 *
 * @since 2.0.9
 *
 * @return void
 */
add_action( 'init', static function (): void {
    $updater_file = plugin_dir_path( __FILE__ ) . 'includes/class-plugin-updater.php';

    if ( file_exists( $updater_file ) ) {
        require_once $updater_file;

        if ( class_exists( 'JPKComFaSvgPluginGitUpdate\\JPKComGitPluginUpdater' ) ) {
            new \JPKComFaSvgPluginGitUpdate\JPKComGitPluginUpdater(
                plugin_file: __FILE__,
                current_version: JPKCOM_FASVG_VERSION,
                manifest_url: 'https://jpkcom.github.io/jpkcom-fa-svg-plugin/plugin_jpkcom-fa-svg-plugin.json'
            );
        }
    }
}, 5 );

/**
 * Plugin path, URL and Font Awesome upload directory constants.
 *
 * @since 1.0.0
 */
define( constant_name: 'JPKCOM_FASVG_PLUGIN_PATH', value: plugin_dir_path( __FILE__ ) );
define( constant_name: 'JPKCOM_FASVG_PLUGIN_URL', value: plugin_dir_url( __FILE__ ) );

( static function (): void {

    $jpkcom_fasvg_upload = wp_upload_dir();

    define( constant_name: 'JPKCOM_FASVG_PATH', value: $jpkcom_fasvg_upload['basedir'] . '/jpkcom_fasvg/' );
    define( constant_name: 'JPKCOM_FASVG_URL', value: $jpkcom_fasvg_upload['baseurl'] . '/jpkcom_fasvg/' );

} )();

/**
 * Enqueue the Font Awesome inline-SVG stylesheet on the front end.
 *
 * @since 1.0.0
 *
 * @return void
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
 * Enqueue the Font Awesome inline-SVG stylesheet in the block editor.
 *
 * @since 1.0.0
 *
 * @return void
 */
if ( ! function_exists( function: 'jpkcom_fasvg_enqueue_gutenberg_files' ) ) {

    function jpkcom_fasvg_enqueue_gutenberg_files(): void {

        wp_enqueue_style( 'jpkcom-fasvg-gutenberg-style', JPKCOM_FASVG_URL . 'css/svg-with-js.min.css', array(), '5.15.4', 'all' );
        $jpkcom_fa_inline_css = '.svg-inline--fa{color:inherit;fill:currentColor;}';
        wp_add_inline_style( 'jpkcom-fasvg-gutenberg-style', $jpkcom_fa_inline_css );

    }

}

add_action( 'enqueue_block_editor_assets', 'jpkcom_fasvg_enqueue_gutenberg_files' );


/**
 * Run shortcodes contained in navigation menu item titles.
 *
 * @since 1.0.0
 *
 * @param array $menu_items The navigation menu item objects.
 * @return array The menu items with shortcodes in their titles expanded.
 */
if ( ! function_exists( function: 'jpkcom_fasvg_navigation_fa' ) ) {

    function jpkcom_fasvg_navigation_fa( array $menu_items ): array {

        $jpkcom_fasvg_short_tag = '[jsvg';

        foreach ( $menu_items as $menu_item ) {

            if ( strpos( haystack: $menu_item->title, needle: $jpkcom_fasvg_short_tag ) !== false ) {

                $menu_item->title = do_shortcode( $menu_item->title );

            }

        }

        return $menu_items;

    }
}

add_filter( 'wp_nav_menu_objects', 'jpkcom_fasvg_navigation_fa' );


/**
 * Render the [jsvg] shortcode as an inline Font Awesome SVG.
 *
 * Supported attributes:
 * - type:  Font Awesome style folder (fas, fal, far, fad, fab). Default 'fas'.
 * - name:  Icon file name without extension. Default 'square-full'.
 * - class: Additional CSS classes for the <svg> element.
 * - style: Inline CSS for the <svg> element.
 * - title: Accessible title; sets aria-labelledby and a <title> element.
 *
 * @since 1.0.0
 *
 * @param array<string, string>|string $atts Shortcode attributes ( empty string when none are supplied ).
 * @return string The inline SVG markup.
 */
function jsvg_code( $atts ): string {

    $fa_svg_path = JPKCOM_FASVG_PATH . 'svgs/';
    $fa_svg_folder = 'solid/';
    $fa_svg_icon_name = 'square-full.svg';
    $fa_svg_title_id = 'svg-title-' . wp_rand( min: 10, max: 500000 );
    $fa_svg_title_aria = ' aria-hidden="true"';
    $fa_svg_attributes = ' role="img"';
    $fa_svg_source = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M512 512H0V0h512v512z"/></svg>';
    $classHTML = '';
    $styleHTML = '';
    $titleHTML = '';

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

        $fa_svg_icon_name = sanitize_file_name( basename( $atts['name'] ) ) . '.svg';

    } else {

        $fa_svg_folder = 'solid/';
        $fa_svg_icon_name = 'square-full.svg';

    }

    // Get file contents
    $fa_svg_file = $fa_svg_path . $fa_svg_folder . $fa_svg_icon_name;

    if ( file_exists( filename: $fa_svg_file ) ) {

        $fa_svg_contents = file_get_contents( filename: $fa_svg_file );

        if ( false !== $fa_svg_contents ) {

            $fa_svg_source = $fa_svg_contents;

        }

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
