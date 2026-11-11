<?php
/**
 * Title: adz.world three cards
 * Slug: adz-world-2026/value-cards
 * Categories: columns, featured
 * Description: Three cards explaining the reader, the rotation and the networks.
 *
 * @package adz-world-2026
 */

$adzworld_cards = array(
	array(
		__( 'The reader asks', 'adz-world-2026' ),
		__( 'A short panel in the rotation. Three words, no account, and nothing leaves the browser.', 'adz-world-2026' ),
	),
	array(
		__( 'The rotation serves', 'adz-world-2026' ),
		__( 'Slots cycle and repeat. AdSense in one, an invitation in the next, in whatever order you configured.', 'adz-world-2026' ),
	),
	array(
		__( 'You keep the networks', 'adz-world-2026' ),
		__( 'Your existing units render untouched, at the size the network built them.', 'adz-world-2026' ),
	),
);

?>
<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"bottom":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide" style="padding-bottom:var(--wp--preset--spacing--40)">
	<!-- wp:columns {"style":{"spacing":{"blockGap":{"top":"var:preset|spacing|20","left":"var:preset|spacing|20"}}}} -->
	<div class="wp-block-columns">
	<?php foreach ( $adzworld_cards as $adzworld_card ) : ?>
		<!-- wp:column {"className":"is-style-card"} -->
		<div class="wp-block-column is-style-card">
			<!-- wp:heading {"level":3,"fontSize":"large"} -->
			<h3 class="wp-block-heading has-large-font-size"><?php echo esc_html( $adzworld_card[0] ); ?></h3>
			<!-- /wp:heading -->
			<!-- wp:paragraph -->
			<p><?php echo esc_html( $adzworld_card[1] ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->
	<?php endforeach; ?>
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
