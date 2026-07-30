<?php
/**
 * Regression tests for jpkcom-fa-svg-plugin.
 *
 * Runs standalone (no WordPress): the WordPress functions the main file touches
 * are stubbed, a throwaway icon directory is created under the system temp dir,
 * the plugin file is required, and the shortcode is then rendered directly.
 *
 * Every case in the "naming" and "output" groups below is red against 2.0.15.
 *
 * @package JPKCom_FaSvg_Plugin
 * @since 2.0.16
 */

declare(strict_types=1);

if ( ! defined( constant_name: 'WPINC' ) ) {
    define( constant_name: 'WPINC', value: true );
}

/** Recorded hook registrations and shortcodes. */
$GLOBALS['jpkcom_hooks']      = array();
$GLOBALS['jpkcom_shortcodes'] = array();
$GLOBALS['jpkcom_deprecated'] = array();

/** Throwaway uploads root, cleaned up at the end. */
$GLOBALS['jpkcom_uploads'] = sys_get_temp_dir() . '/jpkcom-fasvg-test-' . getmypid();

mkdir( $GLOBALS['jpkcom_uploads'] . '/jpkcom_fasvg/svgs/solid', 0777, true );
mkdir( $GLOBALS['jpkcom_uploads'] . '/jpkcom_fasvg/svgs/brands', 0777, true );
file_put_contents(
    $GLOBALS['jpkcom_uploads'] . '/jpkcom_fasvg/svgs/solid/house.svg',
    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path d="M0 0h1v1H0z"/></svg>'
);
file_put_contents(
    $GLOBALS['jpkcom_uploads'] . '/jpkcom_fasvg/svgs/brands/github.svg',
    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 496 512"><path d="M1 1h1v1H1z"/></svg>'
);
/** Must never be reachable through the shortcode. */
file_put_contents( $GLOBALS['jpkcom_uploads'] . '/secret.svg', '<svg>LEAKED</svg>' );

if ( ! function_exists( function: 'add_action' ) ) {
    function add_action( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): void {
        $GLOBALS['jpkcom_hooks']['action'][ $hook ][] = array( $callback, $priority );
    }
}

if ( ! function_exists( function: 'add_filter' ) ) {
    function add_filter( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): void {
        $GLOBALS['jpkcom_hooks']['filter'][ $hook ][] = array( $callback, $priority );
    }
}

if ( ! function_exists( function: 'add_shortcode' ) ) {
    function add_shortcode( string $tag, callable $callback ): void {
        $GLOBALS['jpkcom_shortcodes'][ $tag ] = $callback;
    }
}

if ( ! function_exists( function: 'plugin_dir_path' ) ) {
    function plugin_dir_path( string $file ): string {
        return dirname( path: $file ) . DIRECTORY_SEPARATOR;
    }
}

if ( ! function_exists( function: 'plugin_dir_url' ) ) {
    function plugin_dir_url( string $file ): string {
        return 'https://example.test/wp-content/plugins/' . basename( path: dirname( path: $file ) ) . '/';
    }
}

if ( ! function_exists( function: 'wp_get_upload_dir' ) ) {
    function wp_get_upload_dir(): array {
        return array(
            'basedir' => $GLOBALS['jpkcom_uploads'],
            'baseurl' => 'https://example.test/wp-content/uploads',
        );
    }
}

/*
 * Only used by 2.0.15, which resolved the upload directory once at load time
 * through wp_upload_dir(). Stubbed so this suite can also run against that
 * revision and report failing assertions instead of a fatal error.
 */
if ( ! function_exists( function: 'wp_upload_dir' ) ) {
    function wp_upload_dir( ?string $time = null, bool $create_dir = true, bool $refresh_cache = false ): array {
        return wp_get_upload_dir();
    }
}

/*
 * Deliberately constant, not random: 2.0.15 built the <title> id from
 * wp_rand(), so pinning it makes the "distinct ids" assertion below fail
 * deterministically there instead of depending on a collision happening to
 * occur. 2.0.16 uses wp_unique_id() and is unaffected by this stub.
 */
if ( ! function_exists( function: 'wp_rand' ) ) {
    function wp_rand( int $min = 0, int $max = 0 ): int {
        return 42;
    }
}

if ( ! function_exists( function: 'wp_unique_id' ) ) {
    function wp_unique_id( string $prefix = '' ): string {
        static $id = 0;
        ++$id;
        return $prefix . (string) $id;
    }
}

if ( ! function_exists( function: 'shortcode_atts' ) ) {
    function shortcode_atts( array $pairs, mixed $atts, string $shortcode = '' ): array {
        $atts = (array) $atts;
        $out  = array();

        foreach ( $pairs as $name => $default ) {
            $out[ $name ] = array_key_exists( $name, $atts ) ? $atts[ $name ] : $default;
        }

        return $out;
    }
}

if ( ! function_exists( function: 'sanitize_file_name' ) ) {
    function sanitize_file_name( string $filename ): string {
        $special_chars = array( '?', '[', ']', '/', '\\', '=', '<', '>', ':', ';', ',', "'", '"', '&', '$', '#', '*', '(', ')', '|', '~', '`', '!', '{', '}', '%', '+', chr( 0 ) );
        $filename      = str_replace( $special_chars, '', $filename );
        $filename      = (string) preg_replace( '/[\r\n\t -]+/', '-', $filename );

        return trim( $filename, '.-_' );
    }
}

if ( ! function_exists( function: 'esc_attr' ) ) {
    function esc_attr( string $text ): string {
        return htmlspecialchars( string: $text, flags: ENT_QUOTES | ENT_SUBSTITUTE, encoding: 'UTF-8' );
    }
}

if ( ! function_exists( function: 'esc_html' ) ) {
    function esc_html( string $text ): string {
        return htmlspecialchars( string: $text, flags: ENT_QUOTES | ENT_SUBSTITUTE, encoding: 'UTF-8' );
    }
}

if ( ! function_exists( function: '_deprecated_function' ) ) {
    function _deprecated_function( string $function_name, string $version, string $replacement = '' ): void {
        $GLOBALS['jpkcom_deprecated'][] = array( $function_name, $version, $replacement );
    }
}

require_once dirname( path: __DIR__ ) . '/jpkcom-fa-svg-plugin.php';

$failed = 0;
$passed = 0;

/**
 * Assert a condition and report it.
 *
 * @param string $name Case name.
 * @param bool   $ok   Whether the case passed.
 * @param string $note Extra detail printed on failure.
 * @return void
 */
function jpkcom_check( string $name, bool $ok, string $note = '' ): void {
    global $failed, $passed;

    if ( $ok ) {
        ++$passed;
        printf( "  ok   %s\n", $name );
        return;
    }

    ++$failed;
    printf( "  FAIL %s%s\n", $name, $note !== '' ? ' -- ' . $note : '' );
}

/**
 * Render the [jsvg] shortcode through the registered callback.
 *
 * @param array<string,string> $atts Attributes.
 * @return string Rendered markup.
 */
function jpkcom_render( array $atts ): string {
    return ( $GLOBALS['jpkcom_shortcodes']['jsvg'] )( $atts );
}

echo "jpkcom-fa-svg-plugin: regressions\n";

/* --- naming: 2.0.15 declared jsvg_code() unprefixed and unguarded --- */

jpkcom_check(
    'the shortcode callback is prefixed',
    function_exists( function: 'jpkcom_fasvg_shortcode' ),
    'jpkcom_fasvg_shortcode() missing'
);

jpkcom_check(
    'the shortcode is registered to the prefixed callback',
    ( $GLOBALS['jpkcom_shortcodes']['jsvg'] ?? null ) === 'jpkcom_fasvg_shortcode',
    'registered: ' . var_export( $GLOBALS['jpkcom_shortcodes']['jsvg'] ?? null, true )
);

jpkcom_check(
    'the old name survives as a deprecated shim',
    function_exists( function: 'jsvg_code' )
);

if ( function_exists( function: 'jsvg_code' ) ) {
    $shim = jsvg_code( array( 'name' => 'house' ) );

    jpkcom_check( 'the shim still renders', str_contains( haystack: $shim, needle: '<svg' ) );
    jpkcom_check(
        'the shim reports itself as deprecated',
        ( $GLOBALS['jpkcom_deprecated'][0][0] ?? '' ) === 'jsvg_code'
        && ( $GLOBALS['jpkcom_deprecated'][0][2] ?? '' ) === 'jpkcom_fasvg_shortcode()',
        'recorded: ' . var_export( $GLOBALS['jpkcom_deprecated'], true )
    );
}

/* --- paths: resolved per call, so switch_to_blog() cannot strand them --- */

jpkcom_check(
    'a switch-safe path helper exists',
    function_exists( function: 'jpkcom_fasvg_path' ) && function_exists( function: 'jpkcom_fasvg_url' )
);

if ( function_exists( function: 'jpkcom_fasvg_path' ) ) {
    $before = jpkcom_fasvg_path();
    $GLOBALS['jpkcom_uploads'] .= '-switched';
    $after = jpkcom_fasvg_path();
    $GLOBALS['jpkcom_uploads'] = substr( $GLOBALS['jpkcom_uploads'], 0, -9 );

    jpkcom_check(
        'the path helper follows a changed upload directory',
        $before !== $after,
        'both resolved to ' . $before
    );
}

/* --- output --- */

$plain = jpkcom_render( array( 'name' => 'house' ) );

jpkcom_check( 'renders the icon file contents', str_contains( haystack: $plain, needle: 'viewBox="0 0 576 512"' ) );
jpkcom_check( 'adds the base class', str_contains( haystack: $plain, needle: 'class="svg-inline--fa fa-house"' ) );
jpkcom_check( 'is aria-hidden without a title', str_contains( haystack: $plain, needle: 'aria-hidden="true"' ) );

$titled = jpkcom_render( array( 'name' => 'house', 'title' => 'Zur Startseite' ) );

jpkcom_check( 'a title becomes a <title> element', str_contains( haystack: $titled, needle: '<title id="' ) );
jpkcom_check( 'and is wired via aria-labelledby', str_contains( haystack: $titled, needle: 'aria-labelledby="' ) );

/*
 * 2.0.15 escaped the <title> text with esc_attr(). Safe, but the wrong helper
 * for element text.
 */
$amp = jpkcom_render( array( 'name' => 'house', 'title' => 'Kaffee & Kuchen' ) );
jpkcom_check(
    'the title text is HTML-escaped',
    str_contains( haystack: $amp, needle: 'Kaffee &amp; Kuchen' ),
    'got: ' . $amp
);

/*
 * 2.0.15 built the id from wp_rand( 10, 500000 ), so two icons on one page could
 * share it - duplicate ids break aria-labelledby and are invalid HTML. The
 * wp_rand() stub above is constant, so this is a deterministic check of the
 * mechanism rather than a gamble on a collision.
 */
$ids = array();

for ( $i = 0; $i < 10; $i++ ) {
    if ( preg_match( '/id="([^"]+)"/', jpkcom_render( array( 'name' => 'house', 'title' => 'X' ) ), $m ) ) {
        $ids[] = $m[1];
    }
}

jpkcom_check(
    'ten titled icons produce ten distinct ids',
    count( $ids ) === 10 && count( array_unique( $ids ) ) === 10,
    sprintf( '%d ids, %d distinct', count( $ids ), count( array_unique( $ids ) ) )
);

/* --- the style folder whitelist --- */

jpkcom_check(
    'a known style maps to its folder',
    str_contains( haystack: jpkcom_render( array( 'type' => 'fab', 'name' => 'github' ) ), needle: 'viewBox="0 0 496 512"' )
);

jpkcom_check(
    'an unknown style falls back to solid',
    str_contains( haystack: jpkcom_render( array( 'type' => '../', 'name' => 'house' ) ), needle: 'viewBox="0 0 576 512"' )
);

/* --- path traversal must stay impossible --- */

$fallback = '<path d="M512 512H0V0h512v512z"/>';

foreach ( array( '../secret', '../../secret', 'svgs/../../secret', '../secret.svg', "..\0/secret" ) as $evil ) {
    $out = jpkcom_render( array( 'name' => $evil ) );

    jpkcom_check(
        sprintf( 'name=%s reads no file outside the icon folder', var_export( $evil, true ) ),
        ! str_contains( haystack: $out, needle: 'LEAKED' ) && str_contains( haystack: $out, needle: $fallback ),
        'output: ' . substr( $out, 0, 120 )
    );
}

/* --- hooks --- */

foreach ( array( 'wp_enqueue_scripts', 'enqueue_block_assets' ) as $hook ) {
    jpkcom_check(
        sprintf( '%s is hooked', $hook ),
        ( $GLOBALS['jpkcom_hooks']['action'][ $hook ] ?? array() ) !== array()
    );
}

jpkcom_check(
    'wp_nav_menu_objects is filtered',
    ( $GLOBALS['jpkcom_hooks']['filter']['wp_nav_menu_objects'] ?? array() ) !== array()
);

$init = $GLOBALS['jpkcom_hooks']['action']['init'] ?? array();
jpkcom_check(
    'the updater bootstrap runs on init at priority 5',
    $init !== array() && $init[0][1] === 5
);

/* --- version --- */

$header = array();
preg_match(
    '/^Version:\s*(\S+)/m',
    (string) file_get_contents( dirname( path: __DIR__ ) . '/jpkcom-fa-svg-plugin.php' ),
    $header
);

jpkcom_check(
    'the version constant matches the plugin header',
    defined( constant_name: 'JPKCOM_FASVG_VERSION' )
    && constant( 'JPKCOM_FASVG_VERSION' ) === ( $header[1] ?? '' ),
    sprintf(
        'constant %s vs header %s',
        defined( constant_name: 'JPKCOM_FASVG_VERSION' ) ? (string) constant( 'JPKCOM_FASVG_VERSION' ) : 'undefined',
        $header[1] ?? 'not found'
    )
);

/* --- cleanup --- */

$root = $GLOBALS['jpkcom_uploads'];
$it   = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ),
    RecursiveIteratorIterator::CHILD_FIRST
);

foreach ( $it as $entry ) {
    $entry->isDir() ? rmdir( $entry->getPathname() ) : unlink( $entry->getPathname() );
}

rmdir( $root );

printf( "\n  %d passed, %d failed\n", $passed, $failed );

exit( $failed > 0 ? 1 : 0 );
