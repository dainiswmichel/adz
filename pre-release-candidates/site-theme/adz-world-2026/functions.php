<?php
/**
 * adz.world 2026 theme setup.
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
