<?php
/**
 * Title: adz.world live rotation
 * Slug: adz-world-2026/rotation-demo
 * Categories: featured
 * Description: A heading and a live rotation, placed by name. Edit the rotation name to match one on Adz > Rotation.
 *
 * @package adz-world-2026
 */

?>
<!-- wp:group {"align":"full","anchor":"how","className":"is-style-deferred","style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
<div id="how" class="wp-block-group alignfull is-style-deferred" style="padding-top:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40)">

	<!-- wp:heading {"level":2} -->
	<h2 class="wp-block-heading"><?php esc_html_e( 'Any theme. Untouched creatives.', 'adz-world-2026' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"className":"is-style-lead"} -->
	<p class="is-style-lead"><?php esc_html_e( 'The rotation below is the live plugin. It takes its colour and type from whatever surrounds it, including this page. Switch the theme at the bottom right and watch it follow.', 'adz-world-2026' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:shortcode -->
	[adz_rotation name="adzworld_showcase_single"]
	<!-- /wp:shortcode -->

</div>
<!-- /wp:group -->
