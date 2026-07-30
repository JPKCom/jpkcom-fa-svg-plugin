<?php
/*
Plugin Name: JPKCom FA inline SVG shortcode
Plugin URI: https://github.com/JPKCom/jpkcom-fa-svg-plugin
Description: A plugin for loading inline SVGs from Font Awesome (Pro) v5.15.4 using a shortcode.
Version: 2.0.16
Author: Jean Pierre Kolb <jpk@jpkc.com>
Author URI: https://www.jpkc.com/
Contributors: JPKCom
Tags: FontAwesome, SVG, Inline, Shortcode, Gutenberg
Requires at least: 6.9
Tested up to: 7.1
Requires PHP: 8.3
Network: true
Stable tag: 2.0.16
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
    define( 'JPKCOM_FASVG_VERSION', '2.0.16' );
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
 * Plugin path and URL constants.
 *
 * @since 1.0.0
 * @since 2.0.16 Guarded with defined() so a second copy of the file cannot
 *               raise "constant already defined" warnings.
 */
if ( ! defined( 'JPKCOM_FASVG_PLUGIN_PATH' ) ) {
    define( constant_name: 'JPKCOM_FASVG_PLUGIN_PATH', value: plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'JPKCOM_FASVG_PLUGIN_URL' ) ) {
    define( constant_name: 'JPKCOM_FASVG_PLUGIN_URL', value: plugin_dir_url( __FILE__ ) );
}

if ( ! function_exists( function: 'jpkcom_fasvg_path' ) ) {

    /**
     * Filesystem base for the Font Awesome CSS and SVGs.
     *
     * Resolved on every call rather than once at load time. The plugin can be
     * network-activated, and on multisite each site has its own upload
     * directory: a value captured while the file was included would keep
     * pointing at the site that happened to be active then, which is wrong for
     * anything rendered after `switch_to_blog()`.
     *
     * Uses `wp_get_upload_dir()`, i.e. `wp_upload_dir( null, false )` — the
     * variant that does not try to create the directory. Reading icons never
     * needs that side effect. Both are statically cached, so the per-call cost
     * is a fraction of a microsecond.
     *
     * @since 2.0.16
     *
     * @return string Absolute path with a trailing slash.
     */
    function jpkcom_fasvg_path(): string {
        return wp_get_upload_dir()['basedir'] . '/jpkcom_fasvg/';
    }

}

if ( ! function_exists( function: 'jpkcom_fasvg_url' ) ) {

    /**
     * URL base for the Font Awesome CSS and SVGs.
     *
     * Same reasoning as {@see jpkcom_fasvg_path()}.
     *
     * @since 2.0.16
     *
     * @return string URL with a trailing slash.
     */
    function jpkcom_fasvg_url(): string {
        return wp_get_upload_dir()['baseurl'] . '/jpkcom_fasvg/';
    }

}

/**
 * Font Awesome upload directory constants.
 *
 * Kept for backwards compatibility: they are part of the documented surface and
 * may be referenced from themes or snippets. They hold the values of whichever
 * site was active when this file was included — use `jpkcom_fasvg_path()` and
 * `jpkcom_fasvg_url()` instead, which stay correct across `switch_to_blog()`.
 *
 * @since 1.0.0
 * @since 2.0.16 Guarded, and derived from the switch-safe helpers.
 */
if ( ! defined( 'JPKCOM_FASVG_PATH' ) ) {
    define( constant_name: 'JPKCOM_FASVG_PATH', value: jpkcom_fasvg_path() );
}

if ( ! defined( 'JPKCOM_FASVG_URL' ) ) {
    define( constant_name: 'JPKCOM_FASVG_URL', value: jpkcom_fasvg_url() );
}

/**
 * Register the Font Awesome inline-SVG stylesheet.
 *
 * Registration is idempotent: the block editor collects the canvas assets in a
 * separate `WP_Styles` instance (see `_wp_get_iframed_editor_assets()`), so this
 * runs more than once per request. Without the guard the inline rule would be
 * appended to the handle twice.
 *
 * @since 2.0.14
 *
 * @return void
 */
if ( ! function_exists( function: 'jpkcom_fasvg_register_style' ) ) {

    function jpkcom_fasvg_register_style(): void {

        if ( wp_style_is( 'jpkcom-fasvg-style', 'registered' ) ) {
            return;
        }

        wp_register_style( 'jpkcom-fasvg-style', jpkcom_fasvg_url() . 'css/svg-with-js.min.css', array(), '5.15.4', 'all' );
        wp_add_inline_style( 'jpkcom-fasvg-style', '.svg-inline--fa{color:inherit;fill:currentColor;}' );

    }

}


/**
 * Enqueue the Font Awesome inline-SVG stylesheet on the front end.
 *
 * @since 1.0.0
 *
 * @return void
 */
if ( ! function_exists( function: 'jpkcom_fasvg_enqueue_files' ) ) {

    function jpkcom_fasvg_enqueue_files(): void {

        jpkcom_fasvg_register_style();
        wp_enqueue_style( 'jpkcom-fasvg-style' );

	}

}

add_action( 'wp_enqueue_scripts', 'jpkcom_fasvg_enqueue_files' );


/**
 * Enqueue the Font Awesome inline-SVG stylesheet in the block editor.
 *
 * Hooked to `enqueue_block_assets`, not `enqueue_block_editor_assets`. From
 * WordPress 7.1 the post editor always renders its canvas in an iframe, and
 * `enqueue_block_editor_assets` only reaches the surrounding admin document —
 * the icons inside the canvas would lose the Font Awesome sizing rules
 * (`height: 1em`, `display: inline-block`, `overflow: visible`) and render at
 * their intrinsic SVG size.
 *
 * `enqueue_block_assets` fires in both places: `_wp_get_iframed_editor_assets()`
 * runs it to build the canvas assets, and `wp_common_block_scripts_and_styles()`
 * runs it on `admin_enqueue_scripts` for the admin document, which keeps the
 * behaviour of the non-iframed editor (WordPress <= 7.0) unchanged.
 *
 * The hook also fires on the front end, where `wp_enqueue_scripts` above already
 * handles the stylesheet — hence the `is_admin()` guard. Enqueuing it here as
 * well would be harmless (same handle), but the front end must not depend on
 * this path: optimisation plugins routinely unhook
 * `wp_common_block_scripts_and_styles` to strip the core block library.
 *
 * @since 1.0.0
 *
 * @return void
 */
if ( ! function_exists( function: 'jpkcom_fasvg_enqueue_gutenberg_files' ) ) {

    function jpkcom_fasvg_enqueue_gutenberg_files(): void {

        if ( ! is_admin() ) {
            return;
        }

        jpkcom_fasvg_register_style();
        wp_enqueue_style( 'jpkcom-fasvg-style' );

    }

}

add_action( 'enqueue_block_assets', 'jpkcom_fasvg_enqueue_gutenberg_files' );


/**
 * Run shortcodes contained in navigation menu item titles.
 *
 * Note that `do_shortcode()` expands *every* shortcode in a title that contains
 * `[jsvg`, not only that one. Editing menus requires `edit_theme_options`, so
 * this is admin-only, but the scope is wider than the function name suggests.
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


if ( ! function_exists( function: 'jpkcom_fasvg_shortcode' ) ) {

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
     * The file name is reduced to a flat name with `basename()` and
     * `sanitize_file_name()`, and `.svg` is always appended, so no path outside
     * `<uploads>/jpkcom_fasvg/svgs/<style>/` can be reached. The style folder is
     * whitelisted. The raw file contents are returned inline — the trust
     * boundary is that directory, so only vetted Font Awesome SVGs belong there.
     *
     * @since 1.0.0
     * @since 2.0.16 Renamed from the unprefixed `jsvg_code()` and wrapped in a
     *               `function_exists()` guard, matching every other function in
     *               this file. `jsvg_code()` remains as a deprecated shim.
     *
     * @param array<string, string>|string $atts Shortcode attributes ( empty string when none are supplied ).
     * @return string The inline SVG markup.
     */
    function jpkcom_fasvg_shortcode( $atts ): string {

        $fa_svg_path = jpkcom_fasvg_path() . 'svgs/';
        $fa_svg_folder = 'solid/';
        $fa_svg_icon_name = 'square-full.svg';
        $fa_svg_title_id = wp_unique_id( 'svg-title-' );
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

            // esc_html(), not esc_attr(): this is element text, not an attribute value.
            $titleHTML = '<title id="' . esc_attr( $fa_svg_title_id ) . '">' . esc_html( $atts['title'] ) . '</title>';
            $fa_svg_title_aria = ' aria-labelledby="' . esc_attr( $fa_svg_title_id ) . '"';

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

}

add_shortcode( 'jsvg', 'jpkcom_fasvg_shortcode' );


if ( ! function_exists( function: 'jsvg_code' ) ) {

    /**
     * Deprecated alias of {@see jpkcom_fasvg_shortcode()}.
     *
     * The original name carried no vendor prefix and no `function_exists()`
     * guard, so any theme or plugin declaring `jsvg_code` produced a fatal
     * redeclare error on load. Kept as a shim for code calling it directly.
     *
     * @since      1.0.0
     * @deprecated 2.0.16 Use jpkcom_fasvg_shortcode() instead.
     *
     * @param array<string, string>|string $atts Shortcode attributes.
     * @return string The inline SVG markup.
     */
    function jsvg_code( $atts ): string {
        _deprecated_function( __FUNCTION__, '2.0.16', 'jpkcom_fasvg_shortcode()' );

        return jpkcom_fasvg_shortcode( $atts );
    }

}
