<?php
/**
 * Theme setup for adz.world 2026.
 *
 * @package adz-world-2026
 */

defined( 'ABSPATH' ) || exit;

/**
 * Load the parent stylesheet, then the shared mechanics.
 *
 * The mechanics sheet carries no colour of its own. Every value it paints with
 * is a custom property that theme.json emits, so switching a style variation
 * repaints the whole site without this file changing.
 *
 * @return void
 */
function adzworld_2026_styles() {
	$theme = wp_get_theme();

	wp_enqueue_style(
		'adzworld-2026',
		get_stylesheet_directory_uri() . '/assets/adz-world.css',
		array(),
		$theme->get( 'Version' )
	);
}
add_action( 'wp_enqueue_scripts', 'adzworld_2026_styles' );

/**
 * Load the same mechanics inside the block editor, so an editing session looks
 * like the published page.
 *
 * @return void
 */
function adzworld_2026_editor_styles() {
	add_editor_style( 'assets/adz-world.css' );
}
add_action( 'after_setup_theme', 'adzworld_2026_editor_styles' );

/**
 * The style variations a reader may switch between.
 *
 * Read from the same styles/*.json files the Site Editor uses, so a variation
 * added there appears in the switcher without this file changing. Results are
 * cached for a day; re-running tools/build-variations.py and bumping the theme
 * version clears it, because the cache key carries the version.
 *
 * @return array<int,array<string,string>> Slug and title for each variation.
 */
function adzworld_2026_variations() {
	$version = wp_get_theme()->get( 'Version' );
	$key     = 'adzworld_2026_variations_' . md5( (string) $version );
	$cached  = get_transient( $key );

	if ( is_array( $cached ) ) {
		return $cached;
	}

	$found = array();
	$files = glob( get_stylesheet_directory() . '/styles/*.json' );

	foreach ( is_array( $files ) ? $files : array() as $file ) {
		$data = wp_json_file_decode( $file, array( 'associative' => true ) );
		$slug = sanitize_key( basename( $file, '.json' ) );

		if ( ! is_array( $data ) || '' === $slug || empty( $data['title'] ) ) {
			continue;
		}

		$found[] = array(
			'slug'  => $slug,
			'title' => sanitize_text_field( (string) $data['title'] ),
		);
	}

	set_transient( $key, $found, DAY_IN_SECONDS );

	return $found;
}

/**
 * Register the switcher stylesheet and script.
 *
 * The generated stylesheet carries all three palettes at once, scoped to a data
 * attribute. It is only enqueued when there is more than one variation to
 * choose between, so a site that settles on one pays nothing for it.
 *
 * @return void
 */
function adzworld_2026_switcher_assets() {
	$variations = adzworld_2026_variations();

	if ( count( $variations ) < 2 ) {
		return;
	}

	$theme = wp_get_theme();
	$dir   = get_stylesheet_directory_uri();

	wp_enqueue_style(
		'adzworld-2026-variations',
		$dir . '/assets/variations.css',
		array( 'adzworld-2026' ),
		$theme->get( 'Version' )
	);

	wp_enqueue_script(
		'adzworld-2026-switcher',
		$dir . '/assets/style-switcher.js',
		array(),
		$theme->get( 'Version' ),
		true
	);

	wp_localize_script(
		'adzworld-2026-switcher',
		'adzWorldStyles',
		array(
			'key'        => 'adz-world-style',
			'label'      => __( 'Theme', 'adz-world' ),
			'default'    => 'midnight',
			'variations' => $variations,
		)
	);
}
add_action( 'wp_enqueue_scripts', 'adzworld_2026_switcher_assets' );

/**
 * Apply a stored choice before the first paint.
 *
 * This has to run before the browser paints, or the page renders in the
 * administrator's variation and then jumps to the reader's. There is no way to
 * do that from a deferred script, and loading a separate file render-blocking
 * in the head would cost a round trip to save under 300 bytes. So this one
 * script is printed inline -- a script, never a style: every colour in this
 * theme lives in theme.json and reaches the page through a stylesheet.
 *
 * @return void
 */
function adzworld_2026_switcher_boot() {
	if ( count( adzworld_2026_variations() ) < 2 ) {
		return;
	}

	$script = 'try{var s=localStorage.getItem("adz-world-style");'
		. 'if(!s||!/^[a-z0-9_-]{1,32}$/.test(s)){s="midnight";}'
		. 'document.documentElement.setAttribute("data-adz-style",s);}'
		. 'catch(e){document.documentElement.setAttribute("data-adz-style","midnight");}';

	wp_print_inline_script_tag( $script, array( 'id' => 'adzworld-2026-boot' ) );
}
add_action( 'wp_head', 'adzworld_2026_switcher_boot', 1 );

/**
 * The switcher's mount point.
 *
 * Printed hidden and revealed by the script, so a reader without JavaScript is
 * never shown a control that cannot do anything.
 *
 * @return void
 */
function adzworld_2026_switcher_mount() {
	if ( count( adzworld_2026_variations() ) < 2 ) {
		return;
	}

	echo '<div class="adz-switcher" data-adz-style-switcher hidden></div>';
}
add_action( 'wp_footer', 'adzworld_2026_switcher_mount' );
