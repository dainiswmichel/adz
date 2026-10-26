<?php
/**
 * Where a visitor says what they want, and where it is kept.
 *
 * Storage follows the audience definitions. A visitor is an unknown audience
 * member: their preference lives in a session cookie and is not guaranteed to
 * hold, because there is no profile to restore it from. A guest is accessing
 * the site with a profile; on this site that profile is the WordPress account,
 * so the preference is user meta and persists. A member is a guest who has made
 * an adz-world purchase; this plugin does not record that purchase and grants no
 * capability for it.
 *
 * The cookie holds a short restricted-charset string rather than JSON, for three
 * reasons: sanitize_text_field() is lossless and idempotent over that charset,
 * so the untrusted value is genuinely sanitised on the way in rather than
 * excused; no row is written anywhere for an anonymous visitor; and the visitor
 * can open their own cookie and read exactly what is stored, which is only what
 * they typed.
 *
 * @package adz-world
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/** Cookie holding an anonymous visitor's preference for the session. */
const ADZWORLD_PREFERENCE_COOKIE = 'adzworld_prefs';

/** User meta key holding a guest's saved preference. */
const ADZWORLD_PREFERENCE_META = 'adzworld_preference';

/**
 * Map of preference facets to their short cookie keys.
 *
 * @return array
 */
function adzworld_cookie_facets() {
	return array(
		'include'    => 'i',
		'exclude'    => 'x',
		'attributes' => 'a',
		'min_price'  => 'min',
		'max_price'  => 'max',
		'age'        => 'age',
		'capacity'   => 'cap',
		'intent'     => 't',
	);
}

/**
 * Encode a preference as a restricted-charset string.
 *
 * Only [a-z0-9,:|_-] can appear in the result. Spaces inside a term become
 * underscores, so an underscore already in a term is folded to a hyphen first.
 *
 * @param array $pref Sanitised preference.
 * @return string
 */
function adzworld_encode_preference( $pref ) {
	$parts = array();

	foreach ( adzworld_cookie_facets() as $facet => $short ) {
		if ( empty( $pref[ $facet ] ) ) {
			continue;
		}

		if ( is_array( $pref[ $facet ] ) ) {
			$terms = array();

			foreach ( $pref[ $facet ] as $term ) {
				$term = sanitize_key( str_replace( array( '_', ' ' ), array( '-', '_' ), $term ) );

				if ( '' !== $term ) {
					$terms[] = $term;
				}
			}

			if ( ! empty( $terms ) ) {
				$parts[] = $short . ':' . implode( ',', $terms );
			}

			continue;
		}

		$parts[] = $short . ':' . sanitize_key( (string) $pref[ $facet ] );
	}

	return implode( '|', $parts );
}

/**
 * Decode a cookie string back into a preference.
 *
 * @param string $encoded Cookie value.
 * @return array
 */
function adzworld_decode_preference( $encoded ) {
	$pref   = adzworld_empty_preference();
	$facets = array_flip( adzworld_cookie_facets() );

	foreach ( explode( '|', $encoded ) as $part ) {
		if ( ! str_contains( $part, ':' ) ) {
			continue;
		}

		list( $short, $value ) = explode( ':', $part, 2 );

		if ( ! isset( $facets[ $short ] ) ) {
			continue;
		}

		$facet = $facets[ $short ];

		if ( is_array( $pref[ $facet ] ) ) {
			foreach ( explode( ',', $value ) as $term ) {
				$pref[ $facet ][] = str_replace( '_', ' ', $term );
			}

			continue;
		}

		$pref[ $facet ] = $value;
	}

	return adzworld_sanitize_preference( $pref );
}

/**
 * The current visitor's preference.
 *
 * @return array
 */
function adzworld_current_preference() {
	if ( is_user_logged_in() ) {
		$stored = get_user_meta( get_current_user_id(), ADZWORLD_PREFERENCE_META, true );

		if ( is_array( $stored ) ) {
			return adzworld_sanitize_preference( $stored );
		}

		return adzworld_empty_preference();
	}

	if ( empty( $_COOKIE[ ADZWORLD_PREFERENCE_COOKIE ] ) ) {
		return adzworld_empty_preference();
	}

	return adzworld_decode_preference( sanitize_text_field( wp_unslash( $_COOKIE[ ADZWORLD_PREFERENCE_COOKIE ] ) ) );
}

/**
 * Store a preference for the current visitor.
 *
 * @param array $pref Preference.
 * @return void
 */
function adzworld_store_preference( $pref ) {
	$pref = adzworld_sanitize_preference( $pref );

	if ( is_user_logged_in() ) {
		update_user_meta( get_current_user_id(), ADZWORLD_PREFERENCE_META, $pref );

		return;
	}

	$encoded = adzworld_encode_preference( $pref );

	// Keep the running copy for the rest of this request.
	$_COOKIE[ ADZWORLD_PREFERENCE_COOKIE ] = $encoded;

	if ( headers_sent() ) {
		return;
	}

	setcookie(
		ADZWORLD_PREFERENCE_COOKIE,
		$encoded,
		array(
			'expires'  => 0, // Session cookie. It is not guaranteed to hold.
			'path'     => COOKIEPATH ? COOKIEPATH : '/',
			'domain'   => COOKIE_DOMAIN,
			'secure'   => is_ssl(),
			'httponly' => true,
			'samesite' => 'Lax',
		)
	);
}

/**
 * Forget the current visitor's preference entirely.
 *
 * @return void
 */
function adzworld_clear_preference() {
	if ( is_user_logged_in() ) {
		delete_user_meta( get_current_user_id(), ADZWORLD_PREFERENCE_META );

		return;
	}

	unset( $_COOKIE[ ADZWORLD_PREFERENCE_COOKIE ] );

	if ( headers_sent() ) {
		return;
	}

	setcookie(
		ADZWORLD_PREFERENCE_COOKIE,
		'',
		array(
			'expires'  => time() - YEAR_IN_SECONDS,
			'path'     => COOKIEPATH ? COOKIEPATH : '/',
			'domain'   => COOKIE_DOMAIN,
			'secure'   => is_ssl(),
			'httponly' => true,
			'samesite' => 'Lax',
		)
	);
}

/**
 * Clamp an incoming preference to known facets and safe values.
 *
 * A preference can arrive from a cookie, which the visitor controls, so every
 * field is rebuilt rather than trusted.
 *
 * @param array $pref Candidate.
 * @return array
 */
function adzworld_sanitize_preference( $pref ) {
	$clean = adzworld_empty_preference();
	$pref  = (array) $pref;

	foreach ( array( 'include', 'exclude', 'attributes' ) as $list ) {
		if ( empty( $pref[ $list ] ) || ! is_array( $pref[ $list ] ) ) {
			continue;
		}

		$terms = array();

		foreach ( array_slice( $pref[ $list ], 0, 20 ) as $term ) {
			$term = trim( sanitize_text_field( (string) $term ) );
			$term = mb_substr( $term, 0, 40 );

			if ( '' !== $term ) {
				$terms[] = $term;
			}
		}

		$clean[ $list ] = array_values( array_unique( $terms ) );
	}

	foreach ( array( 'min_price', 'max_price', 'age', 'capacity' ) as $number ) {
		if ( isset( $pref[ $number ] ) && is_numeric( $pref[ $number ] ) ) {
			$clean[ $number ] = max( 0, (int) $pref[ $number ] );
		}
	}

	if ( ! empty( $pref['intent'] ) ) {
		$intent = sanitize_key( (string) $pref['intent'] );

		if ( array_key_exists( $intent, adzworld_intent_vocabulary() ) ) {
			$clean['intent'] = $intent;
		}
	}

	if ( ! empty( $pref['raw'] ) && is_array( $pref['raw'] ) ) {
		foreach ( array_slice( $pref['raw'], -5 ) as $line ) {
			$clean['raw'][] = mb_substr( sanitize_text_field( (string) $line ), 0, 200 );
		}
	}

	return $clean;
}

/**
 * Whether a preference carries anything the visitor actually asked for.
 *
 * @param array $pref Preference.
 * @return bool
 */
function adzworld_has_preference( $pref ) {
	foreach ( array( 'include', 'exclude', 'attributes' ) as $list ) {
		if ( ! empty( $pref[ $list ] ) ) {
			return true;
		}
	}

	foreach ( array( 'min_price', 'max_price', 'age', 'capacity', 'intent' ) as $single ) {
		if ( ! empty( $pref[ $single ] ) ) {
			return true;
		}
	}

	return false;
}

/**
 * The introduction panel: what this is, before anything is asked of the visitor.
 *
 * Shown above the input when no preference is set. It names what the rotation
 * is doing, because a visitor who does not know what an adz is cannot
 * meaningfully opt in to one.
 *
 * @param string $depth full | short.
 * @return void
 */
function adzworld_render_intro( $depth = 'full' ) {
	?>
	<div class="adz-intro adz-intro--<?php echo esc_attr( $depth ); ?>">
		<p class="adz-intro__lead">
			<?php
			esc_html_e(
				'You have the option to receive permission-based adz by filling in what you would like to purchase in the near future below.',
				'adz-world'
			);
			?>
		</p>

		<?php if ( 'full' === $depth ) : ?>
			<ul class="adz-intro__points">
				<li><?php esc_html_e( 'Nothing is tracked. There is no profile of you here, and nothing follows you to another site.', 'adz-world' ); ?></li>
				<li><?php esc_html_e( 'You see what you asked for, in your own words, and you can remove any part of it.', 'adz-world' ); ?></li>
				<li><?php esc_html_e( 'Leave it blank and the adz in this space stay exactly as they were.', 'adz-world' ); ?></li>
			</ul>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * The panel: the invitation, the input, and what was understood.
 *
 * @param array  $pref     Current preference.
 * @param bool   $has_pref Whether anything has been set yet.
 * @param string $intro    full | short | none.
 * @return void
 */
function adzworld_render_preference_panel( $pref, $has_pref, $intro = 'full' ) {
	$chips    = adzworld_preference_chips( $pref );
	$input_id = wp_unique_id( 'adz-panel-input-' );
	?>
	<form class="adz-panel" method="post" data-adz-panel>
		<?php wp_nonce_field( 'adzworld_preference', 'adzworld_preference_nonce' ); ?>

		<?php
		if ( ! $has_pref && 'none' !== $intro ) {
			adzworld_render_intro( $intro );
		}
		?>

		<label class="adz-panel__label" for="<?php echo esc_attr( $input_id ); ?>">
			<?php
			echo $has_pref
				? esc_html__( 'Add to this, or correct it', 'adz-world' )
				: esc_html__( 'What are you looking to buy?', 'adz-world' );
			?>
		</label>

		<div class="adz-panel__row">
			<input
				type="text"
				id="<?php echo esc_attr( $input_id ); ?>"
				class="adz-panel__input"
				name="adzworld_refinement"
				autocomplete="off"
				maxlength="200"
				placeholder="<?php esc_attr_e( 'a two-seater couch, light colour, not leather', 'adz-world' ); ?>"
			>
			<button type="submit" class="adz-panel__submit">
				<?php echo $has_pref ? esc_html__( 'Refine', 'adz-world' ) : esc_html__( 'Show me', 'adz-world' ); ?>
			</button>
		</div>

		<div class="adz-panel__chips" data-adz-chips>
			<?php adzworld_render_chips( $chips ); ?>
		</div>

		<p class="adz-panel__status" data-adz-status role="status" aria-live="polite"></p>
	</form>
	<?php
}

/**
 * The chip list: everything the plugin believes it understood, each removable.
 *
 * @param array $chips Chips from adzworld_preference_chips().
 * @return void
 */
function adzworld_render_chips( $chips ) {
	if ( empty( $chips ) ) {
		return;
	}
	?>
	<p class="adz-panel__understood"><?php esc_html_e( 'Understood as:', 'adz-world' ); ?></p>
	<ul class="adz-panel__chiplist">
		<?php foreach ( $chips as $chip ) : ?>
			<li class="adz-panel__chip adz-panel__chip--<?php echo esc_attr( $chip['facet'] ); ?>">
				<span class="adz-panel__chiptext"><?php echo esc_html( $chip['label'] ); ?></span>
				<button
					type="button"
					class="adz-panel__chipdrop"
					data-adz-facet="<?php echo esc_attr( $chip['facet'] ); ?>"
					data-adz-value="<?php echo esc_attr( $chip['value'] ); ?>"
				>
					<span class="screen-reader-text">
						<?php
						echo esc_html(
							sprintf(
								/* translators: %s: the chip label being removed. */
								__( 'Remove %s', 'adz-world' ),
								$chip['label']
							)
						);
						?>
					</span>
					<span aria-hidden="true">&times;</span>
				</button>
			</li>
		<?php endforeach; ?>
	</ul>
	<p class="adz-panel__note">
		<?php esc_html_e( 'Wrong? Remove anything that does not belong. None of this was guessed from your browsing.', 'adz-world' ); ?>
	</p>
	<?php
}

add_action( 'wp_ajax_adzworld_refine', 'adzworld_ajax_refine' );
add_action( 'wp_ajax_nopriv_adzworld_refine', 'adzworld_ajax_refine' );
/**
 * Add a refinement, drop one facet, or forget everything.
 *
 * Open to logged-out visitors by design: an anonymous visitor setting their own
 * preference is the whole point. It reads and writes nothing but that visitor's
 * own preference, so there is no privileged action to protect. The nonce is here
 * against cross-site submission, not as authorisation.
 *
 * @return void
 */
function adzworld_ajax_refine() {
	check_ajax_referer( 'adzworld_preference', 'nonce' );

	$action = isset( $_POST['mode'] ) ? sanitize_key( wp_unslash( $_POST['mode'] ) ) : 'refine';

	if ( 'forget' === $action ) {
		adzworld_clear_preference();

		wp_send_json_success(
			array(
				'chips'   => array(),
				'matches' => 0,
				'message' => __( 'Forgotten. Nothing was kept.', 'adz-world' ),
			)
		);
	}

	$pref = adzworld_current_preference();

	if ( 'drop' === $action ) {
		$facet = isset( $_POST['facet'] ) ? sanitize_key( wp_unslash( $_POST['facet'] ) ) : '';
		$value = isset( $_POST['value'] ) ? sanitize_text_field( wp_unslash( $_POST['value'] ) ) : '';

		if ( '' === $facet ) {
			wp_send_json_error( array( 'message' => __( 'Nothing to remove.', 'adz-world' ) ), 400 );
		}

		$pref    = adzworld_remove_facet( $pref, $facet, $value );
		$message = __( 'Removed.', 'adz-world' );
	} else {
		$text = isset( $_POST['refinement'] ) ? sanitize_text_field( wp_unslash( $_POST['refinement'] ) ) : '';
		$text = trim( mb_substr( $text, 0, 200 ) );

		if ( '' === $text ) {
			wp_send_json_error( array( 'message' => __( 'Nothing was entered.', 'adz-world' ) ), 400 );
		}

		$pref    = adzworld_parse_refinement( $text, $pref );
		$message = __( 'Updated.', 'adz-world' );
	}

	adzworld_store_preference( $pref );

	$pref  = adzworld_sanitize_preference( $pref );
	$chips = adzworld_preference_chips( $pref );

	ob_start();
	adzworld_render_chips( $chips );
	$markup = (string) ob_get_clean();

	wp_send_json_success(
		array(
			'chips'   => $chips,
			'markup'  => $markup,
			'matches' => count( adzworld_match_adz( $pref, 24 ) ),
			'message' => $message,
		)
	);
}
