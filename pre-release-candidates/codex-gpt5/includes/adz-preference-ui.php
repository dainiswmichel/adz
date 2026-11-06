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
		'categories'           => 'c',
		'category_refinements' => 'cr',
		'category_slots'       => 'cs',
		'active_category'      => 'ac',
		'include'              => 'i',
		'exclude'              => 'x',
		'attributes'           => 'a',
		'min_price'            => 'min',
		'max_price'            => 'max',
		'age'                  => 'age',
		'capacity'             => 'cap',
		'intent'               => 't',
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
		if ( false === strpos( $part, ':' ) ) {
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

	foreach ( array( 'categories', 'category_refinements', 'include', 'exclude', 'attributes' ) as $list ) {
		if ( empty( $pref[ $list ] ) || ! is_array( $pref[ $list ] ) ) {
			continue;
		}

		$terms = array();

		$limit = 'categories' === $list ? 10 : 20;
		foreach ( array_slice( $pref[ $list ], 0, $limit ) as $term ) {
			$term = trim( sanitize_text_field( (string) $term ) );
			$term = mb_substr( $term, 0, 'category_refinements' === $list ? 160 : ADZWORLD_CATEGORY_MAX_LENGTH );

			if ( '' !== $term && ( 'categories' !== $list || adzworld_valid_category( $term ) ) ) {
				$terms[] = $term;
			}
		}

		$clean[ $list ] = array_values( array_unique( $terms ) );
	}

	if ( ! empty( $pref['category_slots'] ) && is_array( $pref['category_slots'] ) ) {
		foreach ( array_slice( $pref['category_slots'], 0, 10 ) as $stored ) {
			$parts = explode( '--', sanitize_text_field( (string) $stored ), 2 );
			if ( 2 !== count( $parts ) || ! in_array( $parts[0], $clean['categories'], true ) ) {
				continue;
			}
			$count = max( 1, min( ADZWORLD_CATEGORY_MAX_SLOTS, (int) $parts[1] ) );
			if ( $count > 1 ) {
				$clean['category_slots'][] = $parts[0] . '--' . $count;
			}
		}
	}

	if ( ! empty( $pref['active_category'] ) ) {
		$active = mb_substr( sanitize_text_field( (string) $pref['active_category'] ), 0, ADZWORLD_CATEGORY_MAX_LENGTH );
		if ( in_array( $active, $clean['categories'], true ) ) {
			$clean['active_category'] = $active;
		}
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
	foreach ( array( 'categories', 'category_refinements', 'include', 'exclude', 'attributes' ) as $list ) {
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
 * Shown above the input throughout the invitation-to-hub journey. It names
 * what the rotation is doing, because a visitor who does not know what an adz
 * is cannot meaningfully opt in to one.
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
				<li><?php esc_html_e( 'Your invitation is kept for this browser session. If you sign in, it can become part of your saved guest or member preferences.', 'adz-world' ); ?></li>
				<li><?php esc_html_e( 'Enter a category, then refine it with details such as colour, size, dates, or price range.', 'adz-world' ); ?></li>
				<li><?php esc_html_e( 'Connected advertising providers can compete to show the most useful matching offer.', 'adz-world' ); ?></li>
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
	$all_chips   = adzworld_preference_chips( $pref );
	$input_id    = wp_unique_id( 'adz-panel-input-' );
	$input_label = __( 'What are you looking to buy?', 'adz-world' );
	$panel_title = $has_pref ? __( 'Your adz hub', 'adz-world' ) : __( 'Advertising by invitation', 'adz-world' );
	$active      = isset( $pref['active_category'] ) ? (string) $pref['active_category'] : '';
	$prefix      = $active . '--';
	$chips       = array_values(
		array_filter(
			$all_chips,
			static function ( $chip ) use ( $prefix ) {
				return 'categories' === $chip['facet']
					|| ( 'category_refinements' === $chip['facet'] && '' !== $prefix && 0 === strpos( $chip['value'], $prefix ) );
			}
		)
	);
	$guidance_id = wp_unique_id( 'adz-panel-category-guidance-' );
	?>
	<form class="adz-panel" method="post" data-adz-panel>
		<?php wp_nonce_field( 'adzworld_preference', 'adzworld_preference_nonce' ); ?>
		<h3 class="adz-panel__title"><?php echo esc_html( $panel_title ); ?></h3>

		<?php
		if ( 'none' !== $intro ) {
			adzworld_render_intro( $has_pref ? 'short' : $intro );
		}
		?>

		<label class="adz-panel__label" for="<?php echo esc_attr( $input_id ); ?>">
			<?php echo esc_html( $input_label ); ?>
		</label>
		<p class="adz-panel__category-guidance" id="<?php echo esc_attr( $guidance_id ); ?>">
			<?php esc_html_e( 'Categories use up to 3 words and 48 characters.', 'adz-world' ); ?>
		</p>

		<div class="adz-panel__row">
			<textarea
				id="<?php echo esc_attr( $input_id ); ?>"
				class="adz-panel__input"
				name="adzworld_refinement"
				maxlength="48"
				rows="1"
				aria-describedby="<?php echo esc_attr( $guidance_id ); ?>"
				placeholder="<?php esc_attr_e( 'used electric cars', 'adz-world' ); ?>"
			></textarea>
			<button type="submit" class="adz-panel__submit" value="category" name="adzworld_mode">
				<?php esc_html_e( 'Show me', 'adz-world' ); ?>
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
 * Render user-authored categories and refinements.
 *
 * @param array $chips Chips from adzworld_preference_chips().
 * @return void
 */
function adzworld_render_chips( $chips ) {
	if ( empty( $chips ) ) {
		return;
	}

	$categories  = array_values(
		array_filter(
			$chips,
			static function ( $chip ) {
				return 'categories' === $chip['facet'];
			}
		)
	);
	$refinements = array_values(
		array_filter(
			$chips,
			static function ( $chip ) {
				return 'categories' !== $chip['facet'];
			}
		)
	);
	?>
	<?php if ( ! empty( $categories ) ) : ?>
		<details class="adz-panel__categories" open>
			<summary><?php esc_html_e( 'Categories', 'adz-world' ); ?></summary>
			<ul class="adz-panel__chiplist">
				<?php foreach ( $categories as $chip ) : ?>
					<?php
					$category_id = wp_unique_id( 'adz-category-' );
					$slots_label = sprintf(
						/* translators: %s: category name. */
						__( '%s adz slots', 'adz-world' ),
						$chip['label']
					);
					?>
					<li class="adz-panel__chip adz-panel__chip--categories<?php echo ! empty( $chip['active'] ) ? ' is-active' : ''; ?>">
						<div class="adz-panel__category-row">
							<button type="button" class="adz-panel__category" data-adz-category="<?php echo esc_attr( $chip['value'] ); ?>">
								<?php echo esc_html( $chip['label'] ); ?>
							</button>
							<button
								type="button"
								class="adz-panel__chipedit"
								data-adz-edit-control="<?php echo esc_attr( $category_id ); ?>"
								aria-controls="<?php echo esc_attr( $category_id ); ?>"
								aria-expanded="false"
							>
								<span class="screen-reader-text"><?php esc_html_e( 'Edit category', 'adz-world' ); ?></span>
								<span aria-hidden="true">&#9998;</span>
							</button>
							<input
								id="<?php echo esc_attr( $category_id ); ?>"
								class="adz-panel__category-name"
								type="text"
								value="<?php echo esc_attr( $chip['label'] ); ?>"
								maxlength="48"
								data-adz-category-original="<?php echo esc_attr( $chip['value'] ); ?>"
								hidden
							>
						</div>
						<div class="adz-panel__category-slots" aria-label="<?php echo esc_attr( $slots_label ); ?>">
							<?php for ( $slot = 0; $slot < $chip['slots']; ++$slot ) : ?>
								<?php
								$slot_label = sprintf(
									/* translators: 1: category name, 2: slot number. */
									__( 'Show %1$s adz %2$d', 'adz-world' ),
									$chip['label'],
									$slot + 1
								);
								?>
								<button
									type="button"
									class="adz-panel__category-slot"
									data-adz-category-slot-category="<?php echo esc_attr( $chip['value'] ); ?>"
									data-adz-category-slot="<?php echo esc_attr( (string) $slot ); ?>"
									aria-label="<?php echo esc_attr( $slot_label ); ?>"
								>
									<span class="screen-reader-text"><?php echo esc_html( (string) ( $slot + 1 ) ); ?></span>
								</button>
							<?php endfor; ?>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
		</details>
	<?php endif; ?>
	<?php if ( ! empty( $refinements ) ) : ?>
		<p class="adz-panel__section-label"><?php esc_html_e( 'Refinements', 'adz-world' ); ?></p>
		<ul class="adz-panel__chiplist">
			<?php foreach ( $refinements as $chip ) : ?>
				<?php $refinement_id = wp_unique_id( 'adz-refinement-' ); ?>
				<li class="adz-panel__chip adz-panel__chip--<?php echo esc_attr( $chip['facet'] ); ?>">
					<span class="adz-panel__chiptext"><?php echo esc_html( $chip['label'] ); ?></span>
					<button
						type="button"
						class="adz-panel__chipedit"
						data-adz-edit-control="<?php echo esc_attr( $refinement_id ); ?>"
						aria-controls="<?php echo esc_attr( $refinement_id ); ?>"
						aria-expanded="false"
					>
						<span class="screen-reader-text"><?php esc_html_e( 'Edit refinement', 'adz-world' ); ?></span>
						<span aria-hidden="true">&#9998;</span>
					</button>
					<label class="screen-reader-text" for="<?php echo esc_attr( $refinement_id ); ?>">
						<?php esc_html_e( 'Edit refinement', 'adz-world' ); ?>
					</label>
					<input
						id="<?php echo esc_attr( $refinement_id ); ?>"
						class="adz-panel__refinement-name"
						type="text"
						value="<?php echo esc_attr( $chip['label'] ); ?>"
						maxlength="120"
						data-adz-refinement-original="<?php echo esc_attr( $chip['value'] ); ?>"
						data-adz-refinement-text="<?php echo esc_attr( $chip['label'] ); ?>"
						hidden
					>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
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

	if ( 'select' === $action ) {
		$category = isset( $_POST['value'] ) ? sanitize_text_field( wp_unslash( $_POST['value'] ) ) : '';
		if ( ! in_array( $category, $pref['categories'], true ) ) {
			wp_send_json_error( array( 'message' => __( 'That category is not available.', 'adz-world' ) ), 400 );
		}
		$pref['active_category'] = $category;
		adzworld_store_preference( $pref );
		wp_send_json_success( array( 'message' => __( 'Category selected.', 'adz-world' ) ) );
	}

	if ( 'rename' === $action ) {
		$old      = isset( $_POST['value'] )
			? adzworld_normalize_category( sanitize_text_field( wp_unslash( $_POST['value'] ) ) )
			: '';
		$category = isset( $_POST['refinement'] )
			? adzworld_normalize_category( sanitize_text_field( wp_unslash( $_POST['refinement'] ) ) )
			: '';

		if ( ! in_array( $old, $pref['categories'], true ) || ! adzworld_valid_category( $category ) ) {
			wp_send_json_error( array( 'message' => __( 'Categories can use up to 3 words and 48 characters.', 'adz-world' ) ), 400 );
		}

		$pref = adzworld_rename_category( $pref, $old, $category );
		adzworld_store_preference( $pref );
		wp_send_json_success( array( 'message' => __( 'Category updated.', 'adz-world' ) ) );
	}

	if ( 'rename_refinement' === $action ) {
		$old        = isset( $_POST['value'] )
			? sanitize_text_field( wp_unslash( $_POST['value'] ) )
			: '';
		$refinement = isset( $_POST['refinement'] )
			? sanitize_text_field( wp_unslash( $_POST['refinement'] ) )
			: '';
		$refinement = trim( mb_substr( str_replace( '--', '-', $refinement ), 0, 120 ) );
		$parts      = explode( '--', $old, 2 );

		if ( ! in_array( $old, $pref['category_refinements'], true ) || 2 !== count( $parts ) || '' === $refinement ) {
			wp_send_json_error( array( 'message' => __( 'Enter a refinement to keep improving these adz.', 'adz-world' ) ), 400 );
		}

		$replacement = mb_substr( $parts[0] . '--' . $refinement, 0, 160 );
		foreach ( $pref['category_refinements'] as $index => $stored ) {
			if ( $old === $stored ) {
				$pref['category_refinements'][ $index ] = $replacement;
			}
		}
		$pref['category_refinements'] = array_values( array_unique( $pref['category_refinements'] ) );
		adzworld_store_preference( $pref );
		wp_send_json_success( array( 'message' => __( 'Refinement updated.', 'adz-world' ) ) );
	}

	if ( 'add_slot' === $action ) {
		$category = isset( $_POST['value'] )
			? adzworld_normalize_category( sanitize_text_field( wp_unslash( $_POST['value'] ) ) )
			: '';

		if ( ! in_array( $category, $pref['categories'], true ) ) {
			wp_send_json_error( array( 'message' => __( 'That category is not available.', 'adz-world' ) ), 400 );
		}

		$count = adzworld_category_slot_count( $pref, $category );
		if ( $count >= ADZWORLD_CATEGORY_MAX_SLOTS ) {
			wp_send_json_error( array( 'message' => __( 'This category already has the maximum number of adz slots.', 'adz-world' ) ), 400 );
		}

		++$count;
		$pref = adzworld_set_category_slot_count( $pref, $category, $count );
		adzworld_store_preference( $pref );
		wp_send_json_success(
			array(
				'count'   => $count,
				'message' => __( 'Adz slot added.', 'adz-world' ),
			)
		);
	}

	if ( 'drop' === $action ) {
		$facet = isset( $_POST['facet'] ) ? sanitize_key( wp_unslash( $_POST['facet'] ) ) : '';
		$value = isset( $_POST['value'] ) ? sanitize_text_field( wp_unslash( $_POST['value'] ) ) : '';

		if ( '' === $facet ) {
			wp_send_json_error( array( 'message' => __( 'Nothing to remove.', 'adz-world' ) ), 400 );
		}

		$pref    = adzworld_remove_facet( $pref, $facet, $value );
		$message = __( 'Removed.', 'adz-world' );
	} else {
		$text = isset( $_POST['refinement'] ) ? sanitize_textarea_field( wp_unslash( $_POST['refinement'] ) ) : '';
		$text = trim( mb_substr( $text, 0, 500 ) );

		if ( '' === $text ) {
			wp_send_json_error( array( 'message' => __( 'Nothing was entered.', 'adz-world' ) ), 400 );
		}

		if ( 'improve' === $action ) {
			$target   = isset( $_POST['target'] ) ? sanitize_key( wp_unslash( $_POST['target'] ) ) : '';
			$category = adzworld_refinement_category_for_target( $target, $pref );

			if ( '' === $category || ! in_array( $category, $pref['categories'], true ) ) {
				wp_send_json_error( array( 'message' => __( 'Add or select this category in the invitation hub before refining it.', 'adz-world' ) ), 400 );
			}

			$pair = mb_substr( $category . '--' . $text, 0, 160 );
			if ( ! in_array( $pair, $pref['category_refinements'], true ) ) {
				$pref['category_refinements'][] = $pair;
			}
			$message = __( 'Adz refinement added.', 'adz-world' );
		} elseif ( 'category' === $action ) {
			$category = adzworld_normalize_category( $text );
			if ( ! adzworld_valid_category( $category ) ) {
				wp_send_json_error( array( 'message' => __( 'Categories can use up to 3 words and 48 characters.', 'adz-world' ) ), 400 );
			}
			if ( ! in_array( $category, $pref['categories'], true ) ) {
				$pref['categories'][] = $category;
			}
			$pref['active_category'] = $category;
			$pref                    = adzworld_parse_refinement( $text, $pref );
			$message                 = __( 'Category added.', 'adz-world' );
		} elseif ( empty( $pref['categories'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Add a category in the invitation hub before refining it.', 'adz-world' ) ), 400 );
		} else {
			$active = isset( $pref['active_category'] ) ? $pref['active_category'] : $pref['categories'][0];
			$pair   = mb_substr( $active . '--' . $text, 0, 160 );
			if ( ! in_array( $pair, $pref['category_refinements'], true ) ) {
				$pref['category_refinements'][] = $pair;
			}
			$message = __( 'Refinement added.', 'adz-world' );
		}
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
