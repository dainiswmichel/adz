<?php
/**
 * Explicit visitor evaluation of an adz.
 *
 * @package adz-world
 */

defined( 'ABSPATH' ) || exit;

const ADZWORLD_FEEDBACK_COOKIE = 'adzworld_feedback';
const ADZWORLD_FEEDBACK_META   = 'adzworld_feedback';

/**
 * Validate a stored evaluation map.
 *
 * @param mixed $feedback Candidate evaluation map.
 * @return array<string,array{score:int,updated:int}> Valid evaluations.
 */
function adzworld_sanitize_feedback( $feedback ) {
	$clean = array();
	if ( ! is_array( $feedback ) ) {
		return $clean;
	}

	foreach ( array_slice( $feedback, -50, null, true ) as $target => $entry ) {
		$key = sanitize_key( (string) $target );
		if ( '' === $key || ! is_array( $entry ) || ! isset( $entry['score'] ) || ! is_numeric( $entry['score'] ) ) {
			continue;
		}
		$score = (int) $entry['score'];
		if ( $score < -100 || $score > 100 ) {
			continue;
		}
		$clean[ $key ] = array(
			'score'   => $score,
			'updated' => isset( $entry['updated'] ) ? absint( $entry['updated'] ) : 0,
		);
	}

	return $clean;
}

/**
 * Return the current visitor's explicit evaluations.
 *
 * @return array<string,array{score:int,updated:int}>
 */
function adzworld_current_feedback() {
	if ( is_user_logged_in() ) {
		$stored = get_user_meta( get_current_user_id(), ADZWORLD_FEEDBACK_META, true );
		return adzworld_sanitize_feedback( $stored );
	}

	if ( empty( $_COOKIE[ ADZWORLD_FEEDBACK_COOKIE ] ) || ! is_string( $_COOKIE[ ADZWORLD_FEEDBACK_COOKIE ] ) ) {
		return array();
	}

	$json = sanitize_text_field( wp_unslash( $_COOKIE[ ADZWORLD_FEEDBACK_COOKIE ] ) );
	if ( strlen( $json ) > 4096 ) {
		return array();
	}

	return adzworld_sanitize_feedback( json_decode( $json, true ) );
}

/**
 * Store the current visitor's explicit evaluations.
 *
 * @param array $feedback Evaluation map.
 * @return void
 */
function adzworld_store_feedback( $feedback ) {
	$feedback = adzworld_sanitize_feedback( $feedback );

	if ( is_user_logged_in() ) {
		update_user_meta( get_current_user_id(), ADZWORLD_FEEDBACK_META, $feedback );
		return;
	}

	$json = wp_json_encode( $feedback );
	if ( ! is_string( $json ) || headers_sent() ) {
		return;
	}

	$_COOKIE[ ADZWORLD_FEEDBACK_COOKIE ] = $json;

	setcookie(
		ADZWORLD_FEEDBACK_COOKIE,
		$json,
		array(
			'expires'  => 0,
			'path'     => COOKIEPATH ? COOKIEPATH : '/',
			'domain'   => COOKIE_DOMAIN,
			'secure'   => is_ssl(),
			'httponly' => true,
			'samesite' => 'Lax',
		)
	);
}

/**
 * Translate a numeric evaluation into clear language.
 *
 * @param int $score Score from -100 to 100.
 * @return string Human-readable evaluation.
 */
function adzworld_feedback_label( $score ) {
	if ( $score <= -75 ) {
		return __( 'Strongly dislike', 'adz-world' );
	}
	if ( $score <= -25 ) {
		return __( 'Dislike', 'adz-world' );
	}
	if ( $score < 25 ) {
		return __( 'Neutral', 'adz-world' );
	}
	if ( $score < 75 ) {
		return __( 'Like', 'adz-world' );
	}

	return __( 'Strongly like', 'adz-world' );
}

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

	$prefix = adzworld_pair_prefix( $category );
	return array_values(
		array_filter(
			adzworld_preference_chips( $preference ),
			static function ( $chip ) use ( $prefix ) {
				return 'category_refinements' === $chip['facet'] && 0 === strpos( $chip['value'], $prefix );
			}
		)
	);
}

/**
 * Render the compact improvement control below one adz.
 *
 * @param string $target   Stable adz or provider identifier.
 * @param string $category Category represented by this adz.
 * @return string Evaluation markup.
 */
function adzworld_feedback_control( $target, $category ) {
	$target       = sanitize_key( $target );
	$category     = mb_substr( sanitize_text_field( $category ), 0, 80 );
	$feedback     = adzworld_current_feedback();
	$preference   = adzworld_current_preference();
	$chips        = adzworld_category_refinement_chips( $preference, $category );
	$score        = isset( $feedback[ $target ]['score'] ) ? (int) $feedback[ $target ]['score'] : 0;
	$input_id     = wp_unique_id( 'adzworld-evaluation-' );
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
	<details class="adzworld-evaluation" data-adzworld-feedback-target="<?php echo esc_attr( $target ); ?>">
		<summary><?php esc_html_e( 'Improve', 'adz-world' ); ?></summary>
		<div class="adzworld-evaluation__control">
			<span aria-hidden="true"><?php esc_html_e( 'Dislike', 'adz-world' ); ?></span>
			<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>">
				<?php esc_html_e( 'How useful is this adz?', 'adz-world' ); ?>
			</label>
			<input id="<?php echo esc_attr( $input_id ); ?>" type="range" min="-100" max="100" step="1" value="<?php echo esc_attr( (string) $score ); ?>">
			<span aria-hidden="true"><?php esc_html_e( 'Like', 'adz-world' ); ?></span>
		</div>
		<output class="adzworld-evaluation__value" for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_html( adzworld_feedback_label( $score ) ); ?></output>
		<p class="adzworld-evaluation__status" role="status" aria-live="polite"></p>
		<div class="adzworld-improve">
			<div class="adzworld-improve__heading">
				<label for="<?php echo esc_attr( $refine_id ); ?>"><?php echo esc_html( $refine_label ); ?></label>
				<details class="adzworld-improve__information">
					<summary aria-label="<?php esc_attr_e( 'How to refine these adz', 'adz-world' ); ?>">i</summary>
					<p><?php esc_html_e( 'Add any useful detail: size, date range, colour, price range, brand, location, material, or anything else that matters. Remember: specific is terrific.', 'adz-world' ); ?></p>
				</details>
			</div>
			<form class="adzworld-improve__form" method="post" data-adz-panel data-adz-improve-target="<?php echo esc_attr( $target ); ?>">
				<div class="adzworld-improve__row">
					<textarea id="<?php echo esc_attr( $refine_id ); ?>" class="adz-panel__input adzworld-improve__input" name="adzworld_refinement" maxlength="500" rows="2" placeholder="<?php esc_attr_e( 'Be specific: size, dates, colour, price range, brands, location, or other preferences.', 'adz-world' ); ?>" <?php disabled( ! $can_refine ); ?>></textarea>
					<button type="submit" class="adz-panel__submit" name="adzworld_mode" value="improve" <?php disabled( ! $can_refine ); ?>><?php esc_html_e( 'Refine', 'adz-world' ); ?></button>
				</div>
				<div class="adz-panel__chips" data-adz-chips><?php adzworld_render_chips( $chips ); ?></div>
				<p class="adz-panel__status" data-adz-status role="status" aria-live="polite"></p>
			</form>
		</div>
	</details>
	<?php
	return (string) ob_get_clean();
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
 * Save one explicit evaluation.
 *
 * An untouched slider creates no record. Moving it to zero records an explicit
 * neutral response, which remains distinct from no response.
 *
 * @return void
 */
function adzworld_ajax_evaluate() {
	check_ajax_referer( 'adzworld_feedback', 'nonce' );

	$target = isset( $_POST['target'] ) && is_string( $_POST['target'] )
		? sanitize_key( wp_unslash( $_POST['target'] ) )
		: '';
	$score  = isset( $_POST['score'] ) && is_scalar( $_POST['score'] )
		? filter_var( wp_unslash( $_POST['score'] ), FILTER_VALIDATE_INT )
		: false;

	if ( '' === $target || strlen( $target ) > 100 || false === $score || $score < -100 || $score > 100 ) {
		wp_send_json_error( array( 'message' => __( 'That evaluation is invalid.', 'adz-world' ) ), 400 );
	}

	$feedback            = adzworld_current_feedback();
	$feedback[ $target ] = array(
		'score'   => (int) $score,
		'updated' => time(),
	);
	adzworld_store_feedback( $feedback );

	wp_send_json_success(
		array(
			'label'   => adzworld_feedback_label( (int) $score ),
			'message' => __( 'Evaluation saved.', 'adz-world' ),
		)
	);
}
