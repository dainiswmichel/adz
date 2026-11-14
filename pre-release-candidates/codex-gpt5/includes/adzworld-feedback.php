<?php
/**
 * Explicit visitor evaluation of an adz.
 *
 * @package adz-world
 */

defined( 'ABSPATH' ) || exit;

/**
 * Return refinement pills attached to one category.
 *
 * @param array  $preference Current preference.
 * @param string $category   Advertisement category.
 * @return array Category refinement chips.
 */
function adzworld_category_refinement_chips( $preference, $category ) {
	if ( '' === $category ) {
		return array();
	}

	return array_values(
		array_filter(
			adzworld_preference_chips( $preference ),
			static function ( $chip ) use ( $category ) {
				$parts = adzworld_pair_split( $chip['value'] );

				return 'category_refinements' === $chip['facet'] && 2 === count( $parts ) && $category === $parts[0];
			}
		)
	);
}

/**
 * Render the compact improvement control below one adz.
 *
 * @param string $target       Stable adz or provider identifier.
 * @param string $category     Category represented by this adz.
 * @param bool   $can_evaluate Whether an actual advertisement is present to evaluate.
 * @param bool   $show_category Whether to repeat the category inside the expanded control.
 * @param string $provider      Provider used by this slot, when fixed.
 * @param string $rotation      Rotation name.
 * @param int    $slot          Category slot number.
 * @return string Evaluation markup.
 */
function adzworld_feedback_control( $target, $category, $can_evaluate = true, $show_category = true, $provider = '', $rotation = '', $slot = 0 ) {
	$target       = sanitize_key( $target );
	$category     = adzworld_journey_statement( $category );
	$preference   = adzworld_current_preference();
	$chips        = adzworld_category_refinement_chips( $preference, $category );
	$refine_id    = wp_unique_id( 'adzworld-refine-' );
	$known        = adzworld_preference_with_catalog( $preference );
	$can_refine   = '' !== $category && in_array( $category, $known['categories'], true );
	$refine_label = '' !== $category
		? sprintf(
			/* translators: %s: selected advertising category. */
			__( 'Refine “%s”', 'adz-world' ),
			$category
		)
		: __( 'Refine', 'adz-world' );

	ob_start();
	?>
	<details class="adzworld-evaluation"
		<?php if ( $can_evaluate ) : ?>
			data-adzworld-feedback-target="<?php echo esc_attr( $target ); ?>"
			data-adzworld-feedback-provider="<?php echo esc_attr( $provider ); ?>"
			data-adzworld-feedback-item-id="<?php echo esc_attr( $target ); ?>"
			data-adzworld-feedback-category="<?php echo esc_attr( $category ); ?>"
			data-adzworld-feedback-rotation="<?php echo esc_attr( $rotation ); ?>"
			data-adzworld-feedback-slot="<?php echo esc_attr( (string) absint( $slot ) ); ?>"
		<?php endif; ?>
	>
		<summary><?php esc_html_e( 'Refine', 'adz-world' ); ?></summary>
		<?php if ( $can_evaluate ) : ?>
		<div class="adzworld-evaluation__choices" role="group" aria-label="<?php esc_attr_e( 'Your response to this adz', 'adz-world' ); ?>">
			<button type="button" class="adzworld-evaluation__choice" data-adzworld-response="dislike"><?php esc_html_e( 'Dislike', 'adz-world' ); ?></button>
			<button type="button" class="adzworld-evaluation__choice" data-adzworld-response="like"><?php esc_html_e( 'Like', 'adz-world' ); ?></button>
		</div>
		<p class="adzworld-evaluation__status" role="status" aria-live="polite"></p>
		<?php endif; ?>
		<div class="adzworld-improve">
			<?php if ( $show_category && '' !== $category ) : ?>
				<p class="adzworld-improve__category">
					<span class="adz-panel__chip adz-panel__chip--categories adz-panel__chip--tone-0 is-active">
						<span class="adz-panel__chiptext"><?php echo esc_html( $category ); ?></span>
					</span>
				</p>
			<?php endif; ?>
			<div class="adzworld-improve__heading">
				<label for="<?php echo esc_attr( $refine_id ); ?>"><?php echo esc_html( $refine_label ); ?></label>
				<details class="adzworld-improve__information">
					<summary aria-label="<?php esc_attr_e( 'How to refine these adz', 'adz-world' ); ?>">i</summary>
					<p><?php esc_html_e( 'Add any useful detail: size, date range, colour, price range, brand, location, material, or anything else that matters. Remember: specific is terrific.', 'adz-world' ); ?></p>
				</details>
			</div>
			<form class="adzworld-improve__form" method="post" data-adz-panel data-adz-improve-target="<?php echo esc_attr( $target ); ?>" data-adzworld-provider="<?php echo esc_attr( $provider ); ?>" data-adzworld-category="<?php echo esc_attr( $category ); ?>" data-adzworld-rotation="<?php echo esc_attr( $rotation ); ?>" data-adzworld-slot="<?php echo esc_attr( (string) absint( $slot ) ); ?>">
				<div class="adz-panel__chips" data-adz-chips><?php adzworld_render_chips( $chips ); ?></div>
				<div class="adzworld-improve__row">
					<textarea id="<?php echo esc_attr( $refine_id ); ?>" class="adz-panel__input adzworld-improve__input" name="adzworld_refinement" rows="2" placeholder="<?php esc_attr_e( 'Be specific: size, dates, colour, price range, brands, location, or other preferences.', 'adz-world' ); ?>" <?php disabled( ! $can_refine ); ?>></textarea>
					<button type="submit" class="adz-panel__submit" name="adzworld_mode" value="improve" <?php disabled( ! $can_refine ); ?>><?php esc_html_e( 'Refine', 'adz-world' ); ?></button>
				</div>
				<p class="adz-panel__status" data-adz-status role="status" aria-live="polite"></p>
			</form>
		</div>
		<?php adzworld_render_request_panel( $category, $provider ); ?>
	</details>
	<?php
	return (string) ob_get_clean();
}

/**
 * Show the accumulated category and refinements sent to connected providers.
 *
 * @param string $category Category represented by this adz.
 * @param string $provider Provider used by this slot, when fixed.
 * @return void
 */
function adzworld_render_request_panel( $category, $provider = '' ) {
	$category = trim( (string) $category );
	$provider = sanitize_key( (string) $provider );
	if ( '' === $category ) {
		return;
	}

	$preference = adzworld_current_preference();
	$request    = adzworld_affiliate_request_string( $preference, $category );
	if ( '' === $request ) {
		return;
	}

	$links  = '' === $provider && function_exists( 'adzworld_affiliate_links' ) ? adzworld_affiliate_links( $request ) : array();
	$labels = function_exists( 'adzworld_products_provider_labels' ) ? adzworld_products_provider_labels() : array();
	?>
	<div class="adzworld-request">
		<p class="adzworld-request__head"><?php esc_html_e( 'Request string', 'adz-world' ); ?></p>
		<ol class="adzworld-request__parts">
			<li class="adzworld-request__part">
				<span class="adzworld-request__kind"><?php esc_html_e( 'category', 'adz-world' ); ?></span>
				<span class="adzworld-request__text"><?php echo esc_html( $category ); ?></span>
			</li>
			<?php
			foreach ( adzworld_category_refinement_chips( $preference, $category ) as $chip ) {
				$split = adzworld_pair_split( (string) $chip['value'] );
				$text  = isset( $split[1] ) ? trim( $split[1] ) : '';
				if ( '' === $text ) {
					continue;
				}
				?>
				<li class="adzworld-request__part">
					<span class="adzworld-request__kind"><?php esc_html_e( 'refine', 'adz-world' ); ?></span>
					<span class="adzworld-request__text"><?php echo esc_html( $text ); ?></span>
				</li>
				<?php
			}
			?>
		</ol>
		<p class="adzworld-request__result"><?php echo esc_html( $request ); ?></p>
		<?php if ( '' !== $provider && isset( $labels[ $provider ] ) ) : ?>
			<p class="adzworld-request__sent">
				<span class="adzworld-request__kind"><?php esc_html_e( 'sent to', 'adz-world' ); ?></span>
				<?php echo esc_html( $labels[ $provider ] ); ?>
			</p>
		<?php elseif ( $links ) : ?>
			<ul class="adzworld-request__sent">
				<?php foreach ( $links as $link ) : ?>
					<li>
						<span class="adzworld-request__kind"><?php esc_html_e( 'sent to', 'adz-world' ); ?></span>
						<a href="<?php echo esc_url( $link['url'] ); ?>" rel="sponsored nofollow" target="_blank"><?php echo esc_html( $link['label'] ); ?></a>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<p class="adzworld-request__sent adzworld-request__sent--none">
				<?php esc_html_e( 'No provider is connected yet, so this string is not being sent anywhere.', 'adz-world' ); ?>
			</p>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Resolve an Improve request to a category without trusting browser input.
 *
 * Local adz resolve from their saved content and ad-type terms. Provider adz
 * use the category explicitly selected in the invitation hub because their
 * remote creative is not available to WordPress for reliable inspection.
 *
 * @param string $target Stable adz or provider identifier.
 * @param array  $pref   Current preference hierarchy.
 * @return string Resolved category, or an empty string.
 */
function adzworld_refinement_category_for_target( $target, $pref ) {
	if ( preg_match( '/^ad-([1-9][0-9]*)$/D', $target, $match ) ) {
		$post = get_post( (int) $match[1] );

		if ( ! $post instanceof WP_Post || 'adz_ad' !== $post->post_type || 'publish' !== $post->post_status || '' !== $post->post_password ) {
			return '';
		}

		return adzworld_ad_category( $post, $pref );
	}

	if ( preg_match( '/^source-[a-z0-9_-]{1,50}$/D', $target ) ) {
		$active = isset( $pref['active_category'] ) ? (string) $pref['active_category'] : '';

		return in_array( $active, $pref['categories'], true ) ? $active : '';
	}

	return '';
}

add_action( 'wp_ajax_adzworld_evaluate', 'adzworld_ajax_evaluate' );
add_action( 'wp_ajax_nopriv_adzworld_evaluate', 'adzworld_ajax_evaluate' );

/**
 * Save one explicit response to an exact provider item.
 *
 * @return void
 */
function adzworld_ajax_evaluate() {
	check_ajax_referer( 'adzworld_feedback', 'nonce' );

	$responses = adzworld_journey_responses();
	$response  = isset( $_POST['response'] ) ? sanitize_key( wp_unslash( $_POST['response'] ) ) : '';
	$provider  = isset( $_POST['provider'] ) ? sanitize_key( wp_unslash( $_POST['provider'] ) ) : '';
	$item_id   = isset( $_POST['item_id'] ) ? sanitize_text_field( wp_unslash( $_POST['item_id'] ) ) : '';
	$category  = isset( $_POST['category'] ) ? adzworld_journey_statement( sanitize_text_field( wp_unslash( $_POST['category'] ) ) ) : '';
	$rotation  = isset( $_POST['rotation'] ) ? sanitize_key( wp_unslash( $_POST['rotation'] ) ) : '';
	$slot      = isset( $_POST['slot'] ) ? absint( wp_unslash( $_POST['slot'] ) ) : 0;

	if ( '' === $provider || '' === $item_id || '' === $category || ! isset( $responses[ $response ] ) ) {
		wp_send_json_error( array( 'message' => __( 'That evaluation is invalid.', 'adz-world' ) ), 400 );
	}

	if ( ! adzworld_journey_record_response( $rotation, $slot, $category, $provider, $item_id, $response ) ) {
		wp_send_json_error( array( 'message' => __( 'That response could not be saved.', 'adz-world' ) ), 400 );
	}

	wp_send_json_success(
		array(
			'label'   => $responses[ $response ],
			'message' => __( 'Evaluation saved.', 'adz-world' ),
		)
	);
}
