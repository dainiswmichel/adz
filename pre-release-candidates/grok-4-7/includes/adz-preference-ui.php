<?php
/**
 * Where a visitor says what they want, and where it is kept.
 *
 * A visitor has no profile. Refinements are appended to that visitor's PHP
 * session and end when the session ends. A guest and a member have WordPress
 * profiles, so their preferences are user meta on that profile. A member is a
 * guest who has made an adz-world purchase; this plugin does not record that
 * purchase and grants no capability for it.
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

/** User meta key holding a guest or member preference. */
const ADZWORLD_PREFERENCE_META = 'adzworld_preference';

/** Session key holding a visitor's refinements. */
const ADZWORLD_PREFERENCE_SESSION = 'adzworld_preference';

/** Session key recording when an anonymous preference window began. */
const ADZWORLD_PREFERENCE_SESSION_STARTED = 'adzworld_preference_started';

/** Browser-session marker written only after an anonymous reader engages. */
const ADZWORLD_PREFERENCE_SESSION_COOKIE = 'adzworld_session';

/** Anonymous preference windows start fresh after 24 hours. */
const ADZWORLD_PREFERENCE_SESSION_TTL = 24 * 60 * 60;

/** User meta key: '1' when the reader chose to keep adz requests on their profile. */
const ADZWORLD_PROFILE_CONSENT_META = 'adzworld_save_with_profile';

/** Site option: '1' when adzadmins allow readers to keep adz requests on their profiles. */
const ADZWORLD_PROFILE_SAVING_OPTION = 'adzworld_profile_saving';

/** Characters in the random part of a session identifier. */
const ADZWORLD_SESSION_SUFFIX_LENGTH = 5;

/**
 * Map of preference facets to their short cookie keys.
 *
 * @return array
 */
function adzworld_cookie_facets() {
	// Plugin Check otherwise mistakes this preference facet for a WP_Query argument.
	$exclude_key = 'exclude';

	return array(
		'categories'           => 'c',
		'category_refinements' => 'cr',
		'category_slots'       => 'cs',
		'active_category'      => 'ac',
		'include'              => 'i',
		$exclude_key           => 'x',
		'attributes'           => 'a',
		'min_price'            => 'min',
		'max_price'            => 'max',
		'age'                  => 'age',
		'capacity'             => 'cap',
		'intent'               => 't',
	);
}

/**
 * Encode one cookie value without using percent escapes.
 *
 * WordPress removes percent-encoded octets in sanitize_text_field(). A
 * hexadecimal value uses only characters that survive that sanitization and
 * cannot collide with the cookie's comma, colon or pipe separators.
 *
 * @param string $value Preference value.
 * @return string Encoded value.
 */
function adzworld_cookie_encode_value( $value ) {
	return 'v1_' . bin2hex( (string) $value );
}

/**
 * Decode one cookie value, while continuing to accept legacy plain values.
 *
 * @param string $value Encoded or legacy value.
 * @return string Decoded value.
 */
function adzworld_cookie_decode_value( $value ) {
	$value = (string) $value;

	if ( 0 !== strpos( $value, 'v1_' ) ) {
		return $value;
	}

	$payload = substr( $value, 3 );
	if ( ! preg_match( '/^(?:[0-9a-f]{2})*$/D', $payload ) ) {
		return '';
	}

	$decoded = hex2bin( $payload );

	return false === $decoded ? '' : $decoded;
}

/**
 * Encode a preference as a reversible restricted-charset string.
 *
 * Each value is encoded before the comma, colon and pipe separators are added,
 * so punctuation inside a category or refinement cannot become a structural
 * separator.
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
				$term = adzworld_cookie_encode_value( $term );

				if ( '' !== $term ) {
					$terms[] = $term;
				}
			}

			if ( ! empty( $terms ) ) {
				$parts[] = $short . ':' . implode( ',', $terms );
			}

			continue;
		}

		$parts[] = $short . ':' . adzworld_cookie_encode_value( $pref[ $facet ] );
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
				$decoded          = adzworld_cookie_decode_value( $term );
				$pref[ $facet ][] = $decoded === $term ? str_replace( '_', ' ', $term ) : $decoded;
			}

			continue;
		}

		$pref[ $facet ] = adzworld_cookie_decode_value( $value );
	}

	return adzworld_sanitize_preference( $pref );
}

/**
 * Whether adzadmins allow readers to keep adz requests on their profiles.
 *
 * This only makes the choice available. Nothing is kept on a profile until
 * the reader also chooses it for themselves.
 *
 * @return bool
 */
function adzworld_profile_saving_enabled() {
	return '0' !== get_option( ADZWORLD_PROFILE_SAVING_OPTION, '1' );
}

/**
 * Whether one reader chose to keep adz requests on their profile.
 *
 * @param int $user_id WordPress user ID.
 * @return bool
 */
function adzworld_profile_consent( $user_id ) {
	return $user_id > 0 && '1' === get_user_meta( $user_id, ADZWORLD_PROFILE_CONSENT_META, true );
}

/**
 * Whether this account keeps preferences on the WordPress profile.
 *
 * Two choices must both be yes: the adzadmin allows profile saving, and the
 * signed-in reader chose it. Everyone else, signed in or not, keeps
 * preferences in the short-lived browser session created by their first
 * preference or rating.
 *
 * @return bool
 */
function adzworld_preference_uses_profile() {
	return is_user_logged_in()
		&& adzworld_profile_saving_enabled()
		&& adzworld_profile_consent( get_current_user_id() );
}

/**
 * Full Michel Time Stamp, UTC, through seconds.
 *
 * @param int|null $unix Unix time. Null uses the current time.
 * @return string
 */
function adzworld_michel_timestamp( $unix = null ) {
	if ( null === $unix ) {
		$unix = time();
	}
	$unix = (int) $unix;

	return gmdate( 'Y', $unix ) . 'y-' . gmdate( 'm', $unix ) . 'm-' . gmdate( 'd', $unix ) . 'd-UTC' . gmdate( 'H', $unix ) . 'h-' . gmdate( 'i', $unix ) . 'm-' . gmdate( 's', $unix ) . 's';
}

/**
 * A new session identifier: the Michel Time Stamp of the first engagement,
 * then a hyphen and five random lowercase letters or digits.
 *
 * The time stamp says when the session began. The random part keeps two
 * readers who engage in the same second apart.
 *
 * @return string For example 2026y-11m-17d-UTC13h-37m-12s-a7k2q.
 */
function adzworld_preference_new_session_id() {
	return adzworld_michel_timestamp() . '-' . strtolower( wp_generate_password( ADZWORLD_SESSION_SUFFIX_LENGTH, false, false ) );
}

/**
 * Whether a value is an adz-world session identifier.
 *
 * @param mixed $value Candidate value.
 * @return bool
 */
function adzworld_preference_valid_session_id( $value ) {
	return is_string( $value ) && 1 === preg_match( '/^\d{4}y-\d{2}m-\d{2}d-UTC\d{2}h-\d{2}m-\d{2}s-[a-z0-9]{5}$/D', $value );
}

/**
 * Whether this request already carries the preference session cookie.
 *
 * A page view with no cookie does not start a session.
 *
 * @return bool
 */
function adzworld_preference_session_cookie_present() {
	$raw = isset( $_COOKIE[ ADZWORLD_PREFERENCE_SESSION_COOKIE ] )
		? sanitize_text_field( wp_unslash( $_COOKIE[ ADZWORLD_PREFERENCE_SESSION_COOKIE ] ) )
		: '';

	if ( adzworld_preference_valid_session_id( $raw ) ) {
		return true;
	}

	if ( PHP_SESSION_ACTIVE !== session_status() ) {
		return false;
	}

	$current = isset( $_SESSION['adzworld_session_id'] )
		? sanitize_text_field( wp_unslash( $_SESSION['adzworld_session_id'] ) )
		: '';

	return adzworld_preference_valid_session_id( $current );
}

/**
 * Open the visitor session.
 *
 * Called when a preference is stored, or when a session cookie is already present.
 *
 * @return void
 */
function adzworld_preference_open_session() {
	if ( adzworld_preference_uses_profile() || headers_sent() ) {
		return;
	}

	if ( PHP_SESSION_ACTIVE !== session_status() ) {
		session_start();
	}

	if ( PHP_SESSION_ACTIVE !== session_status() ) {
		return;
	}

	$now     = time();
	$started = isset( $_SESSION[ ADZWORLD_PREFERENCE_SESSION_STARTED ] )
		? absint( $_SESSION[ ADZWORLD_PREFERENCE_SESSION_STARTED ] )
		: 0;

	if ( 0 === $started || $now - $started >= ADZWORLD_PREFERENCE_SESSION_TTL ) {
		unset( $_SESSION[ ADZWORLD_PREFERENCE_SESSION ] );
		unset( $_SESSION['adzworld_session_id'] );
		$_SESSION[ ADZWORLD_PREFERENCE_SESSION_STARTED ] = $now;
	}
}

/**
 * Mark the browser as having started an anonymous adz-world session.
 *
 * The marker is a session cookie, so it expires when the browser session ends.
 * It is never written for a passive reader.
 *
 * @param string $session_id Michel Time Stamp session identifier.
 * @return void
 */
function adzworld_preference_set_session_cookie( $session_id ) {
	if ( headers_sent() || ! adzworld_preference_valid_session_id( $session_id ) ) {
		return;
	}

	$_COOKIE[ ADZWORLD_PREFERENCE_SESSION_COOKIE ] = $session_id;

	setcookie(
		ADZWORLD_PREFERENCE_SESSION_COOKIE,
		$session_id,
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
 * Michel Time Stamp for this browser session.
 *
 * Written the first time the reader states a preference or a rating.
 * A guest or member account does not receive one. The value is not an address,
 * a name, or an email address.
 *
 * @return string Empty when this account stores preferences on the profile.
 */
function adzworld_preference_ensure_session_id() {
	if ( adzworld_preference_uses_profile() ) {
		return '';
	}

	adzworld_preference_open_session();

	if ( PHP_SESSION_ACTIVE !== session_status() ) {
		return '';
	}

	$current = isset( $_SESSION['adzworld_session_id'] ) && is_string( $_SESSION['adzworld_session_id'] )
		? sanitize_text_field( wp_unslash( $_SESSION['adzworld_session_id'] ) )
		: '';

	if ( adzworld_preference_valid_session_id( $current ) ) {
		adzworld_preference_set_session_cookie( $current );
		return $current;
	}

	$current                         = adzworld_preference_new_session_id();
	$_SESSION['adzworld_session_id'] = $current;
	adzworld_preference_set_session_cookie( $current );

	return $current;
}

/**
 * Return the existing anonymous preference-session identifier.
 *
 * This reader does not create a session. It exposes only a session that was
 * already created when the reader stated a preference or rating.
 *
 * @return string Michel Time Stamp, or an empty string when none exists.
 */
function adzworld_preference_session_id() {
	if ( adzworld_preference_uses_profile() || ! adzworld_preference_session_cookie_present() ) {
		return '';
	}

	adzworld_preference_open_session();

	if ( PHP_SESSION_ACTIVE !== session_status() ) {
		return '';
	}

	$current = isset( $_SESSION['adzworld_session_id'] ) && is_string( $_SESSION['adzworld_session_id'] )
		? sanitize_text_field( wp_unslash( $_SESSION['adzworld_session_id'] ) )
		: '';

	return adzworld_preference_valid_session_id( $current ) ? $current : '';
}

/**
 * The current visitor's preference.
 *
 * @return array
 */
function adzworld_current_preference() {
	if ( adzworld_preference_uses_profile() ) {
		$stored = get_user_meta( get_current_user_id(), ADZWORLD_PREFERENCE_META, true );

		return is_array( $stored ) ? adzworld_sanitize_preference( $stored ) : adzworld_empty_preference();
	}

	if ( ! adzworld_preference_session_cookie_present() ) {
		return adzworld_empty_preference();
	}

	adzworld_preference_open_session();

	if ( ! isset( $_SESSION[ ADZWORLD_PREFERENCE_SESSION ] ) || ! is_array( $_SESSION[ ADZWORLD_PREFERENCE_SESSION ] ) ) {
		return adzworld_empty_preference();
	}

	$session = map_deep( wp_unslash( $_SESSION[ ADZWORLD_PREFERENCE_SESSION ] ), 'sanitize_text_field' );

	return adzworld_sanitize_preference( $session );
}

/**
 * Store a preference for the current visitor.
 *
 * Guest and member preferences are written to their profile. Visitor
 * refinements are appended in the session.
 *
 * @param array $pref Preference.
 * @return void
 */
function adzworld_store_preference( $pref ) {
	$pref = adzworld_sanitize_preference( $pref );

	if ( adzworld_preference_uses_profile() ) {
		update_user_meta( get_current_user_id(), ADZWORLD_PREFERENCE_META, $pref );

		return;
	}

	adzworld_preference_ensure_session_id();
	$_SESSION[ ADZWORLD_PREFERENCE_SESSION ] = $pref;
}

/**
 * Forget the current visitor's preference entirely.
 *
 * @return void
 */
function adzworld_clear_preference() {
	if ( adzworld_preference_uses_profile() ) {
		delete_user_meta( get_current_user_id(), ADZWORLD_PREFERENCE_META );

		return;
	}

	if ( ! adzworld_preference_session_cookie_present() ) {
		return;
	}

	adzworld_preference_open_session();
	unset( $_SESSION[ ADZWORLD_PREFERENCE_SESSION ] );
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

	foreach ( array( 'categories', 'include', 'exclude', 'attributes' ) as $list ) {
		if ( empty( $pref[ $list ] ) || ! is_array( $pref[ $list ] ) ) {
			continue;
		}

		$terms = array();

		$limit = 'categories' === $list ? 10 : 20;
		foreach ( array_slice( $pref[ $list ], 0, $limit ) as $term ) {
			$term = trim( sanitize_text_field( (string) $term ) );
			$term = mb_substr( $term, 0, ADZWORLD_CATEGORY_MAX_LENGTH );

			if ( '' !== $term && ( 'categories' !== $list || adzworld_valid_category( $term ) ) ) {
				$terms[] = $term;
			}
		}

		$clean[ $list ] = array_values( array_unique( $terms ) );
	}

	$known = $clean['categories'];

	if ( function_exists( 'adzworld_catalog_categories' ) ) {
		$known = array_values( array_unique( array_merge( $known, adzworld_catalog_categories() ) ) );
	}

	if ( ! empty( $pref['category_refinements'] ) && is_array( $pref['category_refinements'] ) ) {
		foreach ( array_slice( $pref['category_refinements'], 0, 20 ) as $stored ) {
			$parts = adzworld_pair_split( sanitize_text_field( (string) $stored ) );
			if ( 2 !== count( $parts ) ) {
				continue;
			}

			$category   = adzworld_normalize_category( $parts[0] );
			$refinement = trim( mb_substr( sanitize_text_field( $parts[1] ), 0, ADZWORLD_REFINEMENT_MAX_LENGTH ) );
			if ( '' === $refinement || ! in_array( $category, $known, true ) ) {
				continue;
			}

			$clean['category_refinements'][] = adzworld_pair_join( $category, $refinement );
		}
		$clean['category_refinements'] = array_values( array_unique( $clean['category_refinements'] ) );
	}

	if ( ! empty( $pref['category_slots'] ) && is_array( $pref['category_slots'] ) ) {
		foreach ( array_slice( $pref['category_slots'], 0, 10 ) as $stored ) {
			$parts = adzworld_pair_split( sanitize_text_field( (string) $stored ) );
			if ( 2 !== count( $parts ) || ! in_array( $parts[0], $known, true ) ) {
				continue;
			}
			$count = max( 1, min( ADZWORLD_CATEGORY_MAX_SLOTS, (int) $parts[1] ) );
			if ( $count > 1 ) {
				$clean['category_slots'][] = adzworld_pair_join( $parts[0], (string) $count );
			}
		}
	}

	if ( ! empty( $pref['active_category'] ) ) {
		$active = mb_substr( sanitize_text_field( (string) $pref['active_category'] ), 0, ADZWORLD_CATEGORY_MAX_LENGTH );

		if ( in_array( $active, $known, true ) ) {
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
				<li><?php esc_html_e( 'Your invitation is kept for this browser session. If you have a profile here, you can choose to save it with your profile.', 'adz-world' ); ?></li>
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
	$chips       = array_values(
		array_filter(
			$all_chips,
			static function ( $chip ) {
				return 'categories' === $chip['facet'] || 'category_refinements' === $chip['facet'];
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
		if ( is_user_logged_in() && adzworld_profile_saving_enabled() ) :
			$profile_url = get_edit_profile_url() . '#adzworld-profile';
			?>
			<p class="adz-panel__profile">
				<?php if ( adzworld_preference_uses_profile() ) : ?>
					<?php esc_html_e( 'Your adz requests are saved with your profile.', 'adz-world' ); ?>
					<a href="<?php echo esc_url( $profile_url ); ?>"><?php esc_html_e( 'Change this', 'adz-world' ); ?></a>
				<?php else : ?>
					<?php esc_html_e( 'Your adz requests last for this browser session.', 'adz-world' ); ?>
					<a href="<?php echo esc_url( $profile_url ); ?>"><?php esc_html_e( 'Save them with your profile', 'adz-world' ); ?></a>
				<?php endif; ?>
			</p>
			<?php
		endif;
		?>

		<label class="adz-panel__label" for="<?php echo esc_attr( $input_id ); ?>">
			<?php echo esc_html( $input_label ); ?>
		</label>
		<p class="adz-panel__category-guidance" id="<?php echo esc_attr( $guidance_id ); ?>">
			<?php esc_html_e( 'Enter as much detail as you like. Use a colon before refinements, or a pipe when the category itself contains a colon.', 'adz-world' ); ?>
		</p>

		<div class="adz-panel__row">
			<textarea
				id="<?php echo esc_attr( $input_id ); ?>"
				class="adz-panel__input"
				name="adzworld_refinement"
				rows="2"
				aria-describedby="<?php echo esc_attr( $guidance_id ); ?>"
				placeholder="<?php esc_attr_e( 'couch: light fabric, two-seater', 'adz-world' ); ?>"
			></textarea>
			<button type="submit" class="adz-panel__submit" value="category" name="adzworld_mode">
				<?php esc_html_e( 'Search', 'adz-world' ); ?>
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
							<?php if ( ! empty( $chip['authored'] ) ) : ?>
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
									data-adz-category-original="<?php echo esc_attr( $chip['value'] ); ?>"
									hidden
								>
							<?php endif; ?>
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
				<?php
				$refinement_id       = wp_unique_id( 'adz-refinement-' );
				$refinement_input_id = $refinement_id . '-input';
				?>
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
				<div id="<?php echo esc_attr( $refinement_id ); ?>" class="adz-panel__refinement-editor" hidden>
					<label class="screen-reader-text" for="<?php echo esc_attr( $refinement_input_id ); ?>">
						<?php esc_html_e( 'Edit refinement', 'adz-world' ); ?>
					</label>
					<input
						id="<?php echo esc_attr( $refinement_input_id ); ?>"
						class="adz-panel__refinement-name"
						type="text"
						value="<?php echo esc_attr( $chip['label'] ); ?>"
						maxlength="<?php echo esc_attr( (string) ADZWORLD_REFINEMENT_MAX_LENGTH ); ?>"
						data-adz-refinement-original="<?php echo esc_attr( $chip['value'] ); ?>"
						data-adz-refinement-text="<?php echo esc_attr( $chip['label'] ); ?>"
					>
					<div class="adz-panel__refinement-actions">
						<button type="button" class="adz-panel__refinement-action" data-adz-refinement-action="cancel"><?php esc_html_e( 'Cancel', 'adz-world' ); ?></button>
						<button type="button" class="adz-panel__refinement-action adz-panel__refinement-action--delete" data-adz-refinement-action="delete"><?php esc_html_e( 'Delete', 'adz-world' ); ?></button>
						<button type="button" class="adz-panel__refinement-action adz-panel__refinement-action--save" data-adz-refinement-action="save"><?php esc_html_e( 'Save', 'adz-world' ); ?></button>
					</div>
				</div>
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

	$action  = isset( $_POST['mode'] ) ? sanitize_key( wp_unslash( $_POST['mode'] ) ) : 'refine';
	$context = adzworld_journey_context(
		isset( $_POST['journey_rotation'] ) ? sanitize_key( wp_unslash( $_POST['journey_rotation'] ) ) : '',
		isset( $_POST['journey_slot'] ) ? absint( wp_unslash( $_POST['journey_slot'] ) ) : 0,
		isset( $_POST['journey_position'] ) ? sanitize_text_field( wp_unslash( $_POST['journey_position'] ) ) : '',
		isset( $_POST['journey_ad'] ) ? sanitize_key( wp_unslash( $_POST['journey_ad'] ) ) : ''
	);

	if ( 'forget' === $action ) {
		adzworld_clear_preference();
		adzworld_journey_record( $context, array( 'type' => 'forget' ) );

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
		$known    = adzworld_preference_with_catalog( $pref );
		if ( ! in_array( $category, $known['categories'], true ) ) {
			wp_send_json_error( array( 'message' => __( 'That category is not available.', 'adz-world' ) ), 400 );
		}
		$previous                = isset( $pref['active_category'] ) ? (string) $pref['active_category'] : '';
		$pref['active_category'] = $category;
		adzworld_store_preference( $pref );
		adzworld_journey_record(
			$context,
			array(
				'type'   => 'category',
				'action' => 'selected',
				'from'   => $previous,
				'to'     => $category,
			)
		);
		wp_send_json_success(
			array(
				'message'  => __( 'Category selected.', 'adz-world' ),
				'response' => adzworld_journey_response( $context ),
			)
		);
	}

	if ( 'rename' === $action ) {
		$old      = isset( $_POST['value'] )
			? adzworld_normalize_category( sanitize_text_field( wp_unslash( $_POST['value'] ) ) )
			: '';
		$parsed   = isset( $_POST['refinement'] )
			? adzworld_parse_category_input( sanitize_textarea_field( wp_unslash( $_POST['refinement'] ) ) )
			: array(
				'category'    => '',
				'refinements' => array(),
			);
		$category = $parsed['category'];

		if ( ! in_array( $old, $pref['categories'], true ) || ! adzworld_valid_category( $category ) ) {
			wp_send_json_error( array( 'message' => __( 'Enter a category.', 'adz-world' ) ), 400 );
		}

		$pref = adzworld_rename_category( $pref, $old, $category );
		foreach ( $parsed['refinements'] as $refinement ) {
			$pref['category_refinements'][] = adzworld_pair_join( $category, $refinement );
		}
		adzworld_store_preference( $pref );
		adzworld_journey_record(
			$context,
			array(
				'type'   => 'category',
				'action' => 'renamed',
				'from'   => $old,
				'to'     => $category,
			)
		);
		foreach ( $parsed['refinements'] as $refinement ) {
			adzworld_journey_record(
				$context,
				array(
					'type'     => 'refinement',
					'action'   => 'added',
					'category' => $category,
					'to'       => $refinement,
				)
			);
		}
		wp_send_json_success(
			array(
				'message'  => __( 'Category updated.', 'adz-world' ),
				'response' => adzworld_journey_response( $context ),
			)
		);
	}

	if ( 'rename_refinement' === $action ) {
		$old        = isset( $_POST['value'] )
			? sanitize_text_field( wp_unslash( $_POST['value'] ) )
			: '';
		$refinement = isset( $_POST['refinement'] )
			? sanitize_text_field( wp_unslash( $_POST['refinement'] ) )
			: '';
		$refinement = trim( mb_substr( $refinement, 0, ADZWORLD_REFINEMENT_MAX_LENGTH ) );
		$parts      = adzworld_pair_split( $old );

		if ( ! in_array( $old, $pref['category_refinements'], true ) || 2 !== count( $parts ) || '' === $refinement ) {
			wp_send_json_error( array( 'message' => __( 'Enter a refinement to keep improving these adz.', 'adz-world' ) ), 400 );
		}

		$replacement = adzworld_pair_join( $parts[0], $refinement );
		foreach ( $pref['category_refinements'] as $index => $stored ) {
			if ( $old === $stored ) {
				$pref['category_refinements'][ $index ] = $replacement;
			}
		}
		$pref['category_refinements'] = array_values( array_unique( $pref['category_refinements'] ) );
		adzworld_store_preference( $pref );
		adzworld_journey_record(
			$context,
			array(
				'type'     => 'refinement',
				'action'   => 'edited',
				'category' => $parts[0],
				'from'     => $parts[1],
				'to'       => $refinement,
			)
		);
		wp_send_json_success(
			array(
				'message'  => __( 'Refinement updated.', 'adz-world' ),
				'response' => adzworld_journey_response( $context ),
			)
		);
	}

	if ( 'add_slot' === $action ) {
		$category = isset( $_POST['value'] )
			? adzworld_normalize_category( sanitize_text_field( wp_unslash( $_POST['value'] ) ) )
			: '';

		$known = adzworld_preference_with_catalog( $pref );
		if ( ! in_array( $category, $known['categories'], true ) ) {
			wp_send_json_error( array( 'message' => __( 'That category is not available.', 'adz-world' ) ), 400 );
		}

		$count = adzworld_category_slot_count( $pref, $category );
		if ( $count >= ADZWORLD_CATEGORY_MAX_SLOTS ) {
			wp_send_json_error( array( 'message' => __( 'This category already has the maximum number of adz slots.', 'adz-world' ) ), 400 );
		}

		++$count;
		$pref = adzworld_set_category_slot_count( $pref, $category, $count );
		adzworld_store_preference( $pref );
		adzworld_journey_record(
			$context,
			array(
				'type'   => 'category',
				'action' => 'slot_added',
				'to'     => $category,
			)
		);
		wp_send_json_success(
			array(
				'count'    => $count,
				'message'  => __( 'adz slot added.', 'adz-world' ),
				'response' => adzworld_journey_response( $context ),
			)
		);
	}

	$extra = array();
	if ( 'drop' === $action ) {
		$facet = isset( $_POST['facet'] ) ? sanitize_key( wp_unslash( $_POST['facet'] ) ) : '';
		$value = isset( $_POST['value'] ) ? sanitize_text_field( wp_unslash( $_POST['value'] ) ) : '';

		if ( '' === $facet ) {
			wp_send_json_error( array( 'message' => __( 'Nothing to remove.', 'adz-world' ) ), 400 );
		}

		$pref    = adzworld_remove_facet( $pref, $facet, $value );
		$message = __( 'Removed.', 'adz-world' );
		$pair    = adzworld_pair_split( $value );
		if ( 'category_refinements' === $facet && 2 === count( $pair ) ) {
			$event = array(
				'type'     => 'refinement',
				'action'   => 'removed',
				'category' => $pair[0],
				'from'     => $pair[1],
			);
		} elseif ( 'categories' === $facet ) {
			$event = array(
				'type'   => 'category',
				'action' => 'removed',
				'from'   => $value,
			);
		} else {
			$event = array(
				'type' => 'removed',
				'from' => $value,
			);
		}
	} else {
		$text = isset( $_POST['refinement'] ) ? sanitize_textarea_field( wp_unslash( $_POST['refinement'] ) ) : '';
		$text = trim( mb_substr( $text, 0, ADZWORLD_REFINEMENT_MAX_LENGTH ) );

		if ( '' === $text ) {
			wp_send_json_error( array( 'message' => __( 'Nothing was entered.', 'adz-world' ) ), 400 );
		}

		if ( 'improve' === $action ) {
			$target   = isset( $_POST['target'] ) ? sanitize_key( wp_unslash( $_POST['target'] ) ) : '';
			$known    = adzworld_preference_with_catalog( $pref );
			$category = adzworld_refinement_category_for_target( $target, $known );

			if ( '' === $category || ! in_array( $category, $known['categories'], true ) ) {
				wp_send_json_error( array( 'message' => __( 'Add or select this category in the invitation hub before refining it.', 'adz-world' ) ), 400 );
			}

			$pair = adzworld_pair_join( $category, $text );
			if ( ! in_array( $pair, $pref['category_refinements'], true ) ) {
				$pref['category_refinements'][] = $pair;
			}
			$message = __( 'adz refinement added.', 'adz-world' );
			$event   = array(
				'type'     => 'refinement',
				'action'   => 'added',
				'category' => $category,
				'to'       => $text,
			);
		} elseif ( 'category' === $action ) {
			$parsed   = adzworld_parse_category_input( $text );
			$category = $parsed['category'];
			if ( ! adzworld_valid_category( $category ) ) {
				wp_send_json_error( array( 'message' => __( 'Enter a category.', 'adz-world' ) ), 400 );
			}
			if ( ! in_array( $category, $pref['categories'], true ) ) {
				$pref['categories'][] = $category;
			}
			$pref['active_category'] = $category;
			$pref                    = adzworld_parse_refinement( $category, $pref );
			foreach ( $parsed['refinements'] as $refinement ) {
				$pair = adzworld_pair_join( $category, $refinement );
				if ( ! in_array( $pair, $pref['category_refinements'], true ) ) {
					$pref['category_refinements'][] = $pair;
				}
			}
			$message = __( 'Category added.', 'adz-world' );
			$event   = array(
				'type'   => 'category',
				'action' => 'added',
				'to'     => $category,
			);
			$extra   = $parsed['refinements'];
		} elseif ( empty( $pref['categories'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Add a category in the invitation hub before refining it.', 'adz-world' ) ), 400 );
		} else {
			$active = isset( $pref['active_category'] ) ? $pref['active_category'] : $pref['categories'][0];
			$pair   = adzworld_pair_join( $active, $text );
			if ( ! in_array( $pair, $pref['category_refinements'], true ) ) {
				$pref['category_refinements'][] = $pair;
			}
			$message = __( 'Refinement added.', 'adz-world' );
			$event   = array(
				'type'     => 'refinement',
				'action'   => 'added',
				'category' => $active,
				'to'       => $text,
			);
		}
	}

	adzworld_store_preference( $pref );
	adzworld_journey_record( $context, $event );
	foreach ( $extra as $refinement ) {
		adzworld_journey_record(
			$context,
			array(
				'type'     => 'refinement',
				'action'   => 'added',
				'category' => $event['to'],
				'to'       => $refinement,
			)
		);
	}

	$pref  = adzworld_sanitize_preference( $pref );
	$chips = adzworld_preference_chips( $pref );

	ob_start();
	adzworld_render_chips( $chips );
	$markup = (string) ob_get_clean();

	wp_send_json_success(
		array(
			'chips'    => $chips,
			'markup'   => $markup,
			'matches'  => count( adzworld_match_adz( $pref, 24 ) ),
			'message'  => $message,
			'response' => adzworld_journey_response( $context ),
		)
	);
}
