<?php
/**
 * AdzLogs: what readers did with adz, one journey at a time.
 *
 * Nothing is recorded for a reader who only looks. A journey begins with the
 * reader's first action: a rating, a refinement, or a category change. It is
 * named by the reader's session identifier, which is the Michel Time Stamp of
 * that first action plus five random characters.
 *
 * A journey is a chronological list of events, like a chat:
 *
 *     adz     Rotation main · Slot 2 · ebay:110588014268
 *     reader  Category: "couch" → "brown leather couch"
 *     reader  Rating: 62, Like
 *     adz     Thank you. These adz now follow what you told us.
 *     adz     Rotation main · Slot 4 · ad:31
 *     reader  Rating: -40, Dislike
 *
 * The adz side names the exact adz the reader acted on. It is recorded only
 * when the reader acts on an adz that differs from the one before. An adz the
 * reader ignores is never recorded. Reader text is recorded exactly as it was
 * saved.
 *
 * Storage: one private post per journey, one post meta row per event. Rows are
 * only ever added, so the history cannot be rewritten, and deleting a journey
 * deletes its post. A journey is attached to a WordPress account only when the
 * adzadmin allows profile saving and that reader chose it.
 *
 * @package adz-world
 */

defined( 'ABSPATH' ) || exit;

/** Post type holding one journey per post. */
const ADZWORLD_JOURNEY_POST_TYPE = 'adzworld_journey';

/** Post meta key holding one event per row. */
const ADZWORLD_JOURNEY_EVENT_META = '_adzworld_event';

/** Option holding retention settings. */
const ADZWORLD_LOGS_OPTION = 'adzworld_logs_settings';

/** Daily cleanup hook. */
const ADZWORLD_LOGS_CLEANUP_HOOK = 'adzworld_logs_cleanup';

/** Journeys per AdzLogs page. */
const ADZWORLD_LOGS_PER_PAGE = 20;

/** Journeys removed per cleanup query. */
const ADZWORLD_LOGS_CLEANUP_BATCH = 100;

/** Session key naming the current journey of a reader saving to their profile. */
const ADZWORLD_JOURNEY_PROFILE_SESSION = 'adzworld_profile_journey';

/** Session key holding the adz the reader last acted on in this journey. */
const ADZWORLD_JOURNEY_LAST_AD_SESSION = 'adzworld_journey_last_ad';

/** Nonce action for the reader's profile choice. */
const ADZWORLD_PROFILE_CONSENT_NONCE = 'adzworld_profile_consent';

/**
 * Register the private journey post type.
 *
 * @return void
 */
function adzworld_register_journey_post_type() {
	register_post_type(
		ADZWORLD_JOURNEY_POST_TYPE,
		array(
			'label'               => __( 'Adz journeys', 'adz-world' ),
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => false,
			'show_in_rest'        => false,
			'rewrite'             => false,
			'query_var'           => false,
			'can_export'          => false,
			'delete_with_user'    => true,
			'supports'            => array( 'title', 'author' ),
		)
	);
}
add_action( 'init', 'adzworld_register_journey_post_type' );

/*
 * ---------------------------------------------------------------------------
 * Settings
 * ---------------------------------------------------------------------------
 */

/**
 * Retention settings.
 *
 * Zero days keeps journeys until an adzadmin deletes them. Zero journeys means
 * no limit on how many are kept.
 *
 * @return array{days:int,max:int}
 */
function adzworld_logs_settings() {
	$stored = get_option( ADZWORLD_LOGS_OPTION, array() );
	$stored = is_array( $stored ) ? $stored : array();

	return array(
		'days' => isset( $stored['days'] ) ? absint( $stored['days'] ) : 30,
		'max'  => isset( $stored['max'] ) ? absint( $stored['max'] ) : 0,
	);
}

/*
 * ---------------------------------------------------------------------------
 * Journey identity
 * ---------------------------------------------------------------------------
 */

/**
 * Identifier of the current reader's journey, created on first use.
 *
 * Call this only when the reader has acted. An anonymous reader, or a signed-in
 * reader who keeps nothing on their profile, uses the same session identifier
 * as their preferences. A reader saving to their profile gets one journey per
 * browser session.
 *
 * @return string Session identifier, or an empty string when no session can start.
 */
function adzworld_journey_key() {
	if ( ! adzworld_preference_uses_profile() ) {
		return adzworld_preference_ensure_session_id();
	}

	if ( PHP_SESSION_ACTIVE !== session_status() && ! headers_sent() ) {
		session_start();
	}
	if ( PHP_SESSION_ACTIVE !== session_status() ) {
		return '';
	}

	$current = isset( $_SESSION[ ADZWORLD_JOURNEY_PROFILE_SESSION ] ) && is_string( $_SESSION[ ADZWORLD_JOURNEY_PROFILE_SESSION ] )
		? sanitize_text_field( wp_unslash( $_SESSION[ ADZWORLD_JOURNEY_PROFILE_SESSION ] ) )
		: '';
	if ( ! adzworld_preference_valid_session_id( $current ) ) {
		$current                                      = adzworld_preference_new_session_id();
		$_SESSION[ ADZWORLD_JOURNEY_PROFILE_SESSION ] = $current;
	}

	return $current;
}

/**
 * Post ID of the journey with this identifier, created when asked.
 *
 * @param string $key    Session identifier.
 * @param bool   $create Whether to create the journey when it does not exist.
 * @return int Post ID, or 0.
 */
function adzworld_journey_post_id( $key, $create ) {
	if ( ! adzworld_preference_valid_session_id( $key ) ) {
		return 0;
	}

	$slug  = strtolower( $key );
	$found = get_posts(
		array(
			'post_type'        => ADZWORLD_JOURNEY_POST_TYPE,
			'post_status'      => 'private',
			'name'             => $slug,
			'numberposts'      => 1,
			'fields'           => 'ids',
			'suppress_filters' => false,
		)
	);
	if ( ! empty( $found ) ) {
		return (int) $found[0];
	}
	if ( ! $create ) {
		return 0;
	}

	$post_id = wp_insert_post(
		array(
			'post_type'   => ADZWORLD_JOURNEY_POST_TYPE,
			'post_status' => 'private',
			'post_title'  => $key,
			'post_name'   => $slug,
			'post_author' => adzworld_preference_uses_profile() ? get_current_user_id() : 0,
		),
		true
	);

	return is_wp_error( $post_id ) ? 0 : (int) $post_id;
}

/*
 * ---------------------------------------------------------------------------
 * Recording
 * ---------------------------------------------------------------------------
 */

/**
 * Validate where an action happened, as reported by the reader's browser.
 *
 * Request handlers read these values after verifying their nonce and pass
 * them here. Anything that does not name a saved rotation or a well-formed
 * adz identifier is dropped rather than recorded.
 *
 * @param mixed $rotation Rotation key.
 * @param mixed $slot     Visible slot number, starting at 1.
 * @param mixed $position Zero-based position of the slot in the saved rotation.
 * @param mixed $ad       Adz identifier: hub, ad-31, source-key or provider-key-item.
 * @return array{rotation:string,slot:int,position:int,ad:string}
 */
function adzworld_journey_context( $rotation, $slot, $position, $ad ) {
	$rotation  = is_string( $rotation ) ? sanitize_key( $rotation ) : '';
	$rotations = adzworld_named_rotations();
	if ( ! isset( $rotations[ $rotation ] ) ) {
		$rotation = '';
	}

	$slot     = is_scalar( $slot ) ? absint( $slot ) : 0;
	$position = is_scalar( $position ) && '' !== (string) $position ? absint( $position ) : -1;
	if ( '' === $rotation || $position >= count( $rotations[ $rotation ]['slots'] ) ) {
		$position = -1;
	}

	$ad = is_string( $ad ) ? sanitize_key( $ad ) : '';
	if ( ! preg_match( '/^(?:hub|ad-[1-9][0-9]*|source-[a-z0-9_-]{1,50}|provider-[a-z0-9_]{1,40}-[a-z0-9_-]{1,120})$/D', $ad ) ) {
		$ad = '';
	}

	return array(
		'rotation' => $rotation,
		'slot'     => $slot,
		'position' => $position,
		'ad'       => $ad,
	);
}

/**
 * Append one reader event to the current journey.
 *
 * When the reader acts on a different adz than last time, the adz is recorded
 * first so the journey shows what the reader was responding to.
 *
 * @param array $context Validated context from adzworld_journey_context().
 * @param array $event   Event fields. `type` is required.
 * @return void
 */
function adzworld_journey_record( $context, $event ) {
	$post_id = adzworld_journey_post_id( adzworld_journey_key(), true );
	if ( 0 === $post_id ) {
		return;
	}

	$where = array(
		'rotation' => isset( $context['rotation'] ) ? (string) $context['rotation'] : '',
		'slot'     => isset( $context['slot'] ) ? (int) $context['slot'] : 0,
		'ad'       => isset( $context['ad'] ) ? (string) $context['ad'] : '',
	);
	$last  = isset( $_SESSION[ ADZWORLD_JOURNEY_LAST_AD_SESSION ] ) && is_array( $_SESSION[ ADZWORLD_JOURNEY_LAST_AD_SESSION ] )
		? map_deep( wp_unslash( $_SESSION[ ADZWORLD_JOURNEY_LAST_AD_SESSION ] ), 'sanitize_text_field' )
		: array();
	$known = isset( $last['post'] ) && (int) $last['post'] === $post_id;
	$same  = $known && isset( $last['rotation'], $last['slot'], $last['ad'] )
		&& $last['rotation'] === $where['rotation'] && (int) $last['slot'] === $where['slot'] && $last['ad'] === $where['ad'];

	if ( '' !== $where['ad'] && ! $same ) {
		add_post_meta( $post_id, ADZWORLD_JOURNEY_EVENT_META, array_merge( array( 'type' => 'ad' ), $where, array( 'time' => time() ) ) );
		$_SESSION[ ADZWORLD_JOURNEY_LAST_AD_SESSION ] = array_merge( array( 'post' => $post_id ), $where );
	}

	add_post_meta( $post_id, ADZWORLD_JOURNEY_EVENT_META, array_merge( $event, $where, array( 'time' => time() ) ) );
}

/**
 * Record and return the slot's response message after a reader responded.
 *
 * @param array $context Validated context from adzworld_journey_context().
 * @return string Message to show, or an empty string.
 */
function adzworld_journey_response( $context ) {
	if ( '' === $context['rotation'] || $context['position'] < 0 ) {
		return '';
	}

	$message = adzworld_slot_response_message( $context['rotation'], $context['position'] );
	if ( '' !== $message ) {
		adzworld_journey_record(
			$context,
			array(
				'type' => 'response',
				'text' => $message,
			)
		);
	}

	return $message;
}

/*
 * ---------------------------------------------------------------------------
 * Cleanup
 * ---------------------------------------------------------------------------
 */

/**
 * Delete journeys past the retention settings.
 *
 * @return void
 */
function adzworld_logs_cleanup() {
	$settings = adzworld_logs_settings();

	if ( $settings['days'] > 0 ) {
		do {
			$expired = get_posts(
				array(
					'post_type'        => ADZWORLD_JOURNEY_POST_TYPE,
					'post_status'      => 'private',
					'numberposts'      => ADZWORLD_LOGS_CLEANUP_BATCH,
					'fields'           => 'ids',
					'date_query'       => array(
						array(
							'column' => 'post_date_gmt',
							'before' => gmdate( 'Y-m-d H:i:s', time() - $settings['days'] * DAY_IN_SECONDS ),
						),
					),
					'suppress_filters' => false,
				)
			);
			foreach ( $expired as $post_id ) {
				wp_delete_post( (int) $post_id, true );
			}
			$more = ADZWORLD_LOGS_CLEANUP_BATCH === count( $expired );
		} while ( $more );
	}

	if ( $settings['max'] > 0 ) {
		do {
			$surplus = get_posts(
				array(
					'post_type'        => ADZWORLD_JOURNEY_POST_TYPE,
					'post_status'      => 'private',
					'numberposts'      => ADZWORLD_LOGS_CLEANUP_BATCH,
					'offset'           => $settings['max'],
					'orderby'          => 'date',
					'order'            => 'DESC',
					'fields'           => 'ids',
					'suppress_filters' => false,
				)
			);
			foreach ( $surplus as $post_id ) {
				wp_delete_post( (int) $post_id, true );
			}
			$more = ADZWORLD_LOGS_CLEANUP_BATCH === count( $surplus );
		} while ( $more );
	}
}
add_action( ADZWORLD_LOGS_CLEANUP_HOOK, 'adzworld_logs_cleanup' );

/**
 * Schedule the daily cleanup once.
 *
 * @return void
 */
function adzworld_logs_schedule_cleanup() {
	if ( ! wp_next_scheduled( ADZWORLD_LOGS_CLEANUP_HOOK ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', ADZWORLD_LOGS_CLEANUP_HOOK );
	}
}
add_action( 'init', 'adzworld_logs_schedule_cleanup' );

/**
 * Stop the daily cleanup when the plugin is deactivated.
 *
 * @return void
 */
function adzworld_logs_unschedule_cleanup() {
	wp_clear_scheduled_hook( ADZWORLD_LOGS_CLEANUP_HOOK );
}
register_deactivation_hook( dirname( __DIR__ ) . '/adz-world.php', 'adzworld_logs_unschedule_cleanup' );

/*
 * ---------------------------------------------------------------------------
 * Administrator actions
 * ---------------------------------------------------------------------------
 */

/**
 * Save AdzLogs settings.
 *
 * @return void
 */
function adzworld_logs_save_settings() {
	check_admin_referer( 'adzworld_logs_settings', 'adzworld_logs_nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Administrator permission is required.', 'adz-world' ), '', array( 'response' => 403 ) );
	}

	$days = isset( $_POST['adzworld_logs_days'] ) && is_scalar( $_POST['adzworld_logs_days'] ) ? absint( wp_unslash( $_POST['adzworld_logs_days'] ) ) : 0;
	$max  = isset( $_POST['adzworld_logs_max'] ) && is_scalar( $_POST['adzworld_logs_max'] ) ? absint( wp_unslash( $_POST['adzworld_logs_max'] ) ) : 0;
	update_option(
		ADZWORLD_LOGS_OPTION,
		array(
			'days' => $days,
			'max'  => $max,
		),
		false
	);
	update_option( ADZWORLD_PROFILE_SAVING_OPTION, isset( $_POST['adzworld_profile_saving'] ) ? '1' : '0', false );
	adzworld_logs_cleanup();

	set_transient( 'adzworld_logs_notice_' . get_current_user_id(), __( 'AdzLogs settings saved.', 'adz-world' ), MINUTE_IN_SECONDS );
	wp_safe_redirect( adzworld_desk_url( 'logs' ) );
	exit;
}
add_action( 'admin_post_adzworld_logs_settings', 'adzworld_logs_save_settings' );

/**
 * Delete selected journeys, or every journey.
 *
 * @return void
 */
function adzworld_logs_delete() {
	check_admin_referer( 'adzworld_logs_delete', 'adzworld_logs_delete_nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Administrator permission is required.', 'adz-world' ), '', array( 'response' => 403 ) );
	}

	$deleted = 0;
	if ( isset( $_POST['adzworld_logs_delete_all'] ) ) {
		do {
			$batch = get_posts(
				array(
					'post_type'        => ADZWORLD_JOURNEY_POST_TYPE,
					'post_status'      => 'private',
					'numberposts'      => ADZWORLD_LOGS_CLEANUP_BATCH,
					'fields'           => 'ids',
					'suppress_filters' => false,
				)
			);
			foreach ( $batch as $post_id ) {
				$deleted += wp_delete_post( (int) $post_id, true ) ? 1 : 0;
			}
			$more = ADZWORLD_LOGS_CLEANUP_BATCH === count( $batch );
		} while ( $more );
	} else {
		$ids = isset( $_POST['adzworld_journeys'] ) && is_array( $_POST['adzworld_journeys'] )
			? array_map( 'absint', wp_unslash( $_POST['adzworld_journeys'] ) )
			: array();
		foreach ( $ids as $post_id ) {
			if ( ADZWORLD_JOURNEY_POST_TYPE === get_post_type( $post_id ) ) {
				$deleted += wp_delete_post( $post_id, true ) ? 1 : 0;
			}
		}
	}

	set_transient(
		'adzworld_logs_notice_' . get_current_user_id(),
		sprintf(
			/* translators: %d: number of journeys deleted. */
			_n( '%d journey deleted.', '%d journeys deleted.', $deleted, 'adz-world' ),
			$deleted
		),
		MINUTE_IN_SECONDS
	);
	wp_safe_redirect( adzworld_desk_url( 'logs' ) );
	exit;
}
add_action( 'admin_post_adzworld_logs_delete', 'adzworld_logs_delete' );

/**
 * Load the confirmation script on AdzLogs and on profile screens.
 *
 * @param string $hook_suffix Current administrator screen.
 * @return void
 */
function adzworld_logs_admin_assets( $hook_suffix ) {
	$on_logs    = function_exists( 'adzworld_desk_tab' ) && 'logs' === adzworld_desk_tab();
	$on_profile = in_array( $hook_suffix, array( 'profile.php', 'user-edit.php' ), true );
	if ( ! $on_logs && ! $on_profile ) {
		return;
	}

	$path = dirname( __DIR__ ) . '/assets/admin-confirm.js';
	wp_enqueue_script(
		'adzworld-admin-confirm',
		plugins_url( 'assets/admin-confirm.js', dirname( __DIR__ ) . '/adz-world.php' ),
		array(),
		(string) filemtime( $path ),
		true
	);
}
add_action( 'admin_enqueue_scripts', 'adzworld_logs_admin_assets' );

/*
 * ---------------------------------------------------------------------------
 * The AdzLogs tab
 * ---------------------------------------------------------------------------
 */

/**
 * The Michel Time Stamp at the start of a session identifier.
 *
 * @param string $key Session identifier.
 * @return string
 */
function adzworld_journey_started( $key ) {
	return (string) preg_replace( '/-[a-z0-9]{5}$/D', '', $key );
}

/**
 * The time of day of one event, in the Michel Time Stamp style.
 *
 * @param int $time Unix time.
 * @return string For example UTC13h-37m-12s.
 */
function adzworld_journey_time( $time ) {
	return 'UTC' . gmdate( 'H', $time ) . 'h-' . gmdate( 'i', $time ) . 'm-' . gmdate( 's', $time ) . 's';
}

/**
 * A readable name for one adz identifier.
 *
 * @param string $ad Adz identifier.
 * @return string
 */
function adzworld_journey_ad_label( $ad ) {
	if ( 'hub' === $ad ) {
		return __( 'adz hub', 'adz-world' );
	}
	if ( preg_match( '/^ad-([1-9][0-9]*)$/D', $ad, $match ) ) {
		$title = get_the_title( (int) $match[1] );
		return '' !== $title ? 'ad:' . $match[1] . ' · ' . $title : 'ad:' . $match[1];
	}
	if ( preg_match( '/^source-(.+)$/D', $ad, $match ) ) {
		return 'source:' . $match[1];
	}
	if ( preg_match( '/^provider-([a-z0-9_]+)-(.+)$/D', $ad, $match ) ) {
		return $match[1] . ':' . $match[2];
	}

	return $ad;
}

/**
 * Where an event happened, as one line.
 *
 * @param array $event Stored event.
 * @return string
 */
function adzworld_journey_where( $event ) {
	$parts = array();
	if ( ! empty( $event['rotation'] ) ) {
		$rotations = adzworld_named_rotations();
		$parts[]   = isset( $rotations[ $event['rotation'] ] ) ? $rotations[ $event['rotation'] ]['name'] : $event['rotation'];
	}
	if ( ! empty( $event['slot'] ) ) {
		$parts[] = sprintf(
			/* translators: %d: slot number, starting at 1. */
			__( 'Slot %d', 'adz-world' ),
			(int) $event['slot']
		);
	}

	return implode( ' · ', $parts );
}

/**
 * Text of one reader event.
 *
 * @param array $event Stored event.
 * @return string
 */
function adzworld_journey_event_text( $event ) {
	$from     = isset( $event['from'] ) ? (string) $event['from'] : '';
	$to       = isset( $event['to'] ) ? (string) $event['to'] : '';
	$category = isset( $event['category'] ) ? (string) $event['category'] : '';
	$action   = isset( $event['action'] ) ? (string) $event['action'] : '';
	$type     = isset( $event['type'] ) ? (string) $event['type'] : '';

	if ( 'rating' === $type ) {
		$text = sprintf(
			/* translators: 1: score from -100 to 100, 2: words of the ratings band. */
			__( 'Rating: %1$d · %2$s', 'adz-world' ),
			isset( $event['score'] ) ? (int) $event['score'] : 0,
			isset( $event['label'] ) ? (string) $event['label'] : ''
		);
		if ( isset( $event['previous'] ) && '' !== (string) $event['previous'] ) {
			$text .= ' ' . sprintf(
				/* translators: %d: previous score. */
				__( '(was %d)', 'adz-world' ),
				(int) $event['previous']
			);
		}
		return $text;
	}

	if ( 'category' === $type ) {
		$texts = array(
			/* translators: 1: earlier category, 2: new category. */
			'renamed'    => sprintf( __( 'Category changed: “%1$s” → “%2$s”', 'adz-world' ), $from, $to ),
			/* translators: %s: category. */
			'selected'   => sprintf( __( 'Category selected: “%s”', 'adz-world' ), $to ),
			/* translators: %s: category. */
			'removed'    => sprintf( __( 'Category removed: “%s”', 'adz-world' ), $from ),
			/* translators: %s: category. */
			'slot_added' => sprintf( __( 'Asked for another adz slot for “%s”', 'adz-world' ), $to ),
			/* translators: %s: category. */
			'added'      => sprintf( __( 'Category added: “%s”', 'adz-world' ), $to ),
		);
		return isset( $texts[ $action ] ) ? $texts[ $action ] : $texts['added'];
	}

	if ( 'refinement' === $type ) {
		$texts = array(
			/* translators: 1: category, 2: earlier refinement, 3: new refinement. */
			'edited'  => sprintf( __( 'Refinement changed in “%1$s”: “%2$s” → “%3$s”', 'adz-world' ), $category, $from, $to ),
			/* translators: 1: category, 2: refinement. */
			'removed' => sprintf( __( 'Refinement removed from “%1$s”: “%2$s”', 'adz-world' ), $category, $from ),
			/* translators: 1: category, 2: refinement. */
			'added'   => sprintf( __( 'Refinement added to “%1$s”: “%2$s”', 'adz-world' ), $category, $to ),
		);
		return isset( $texts[ $action ] ) ? $texts[ $action ] : $texts['added'];
	}

	if ( 'removed' === $type ) {
		/* translators: %s: removed preference. */
		return sprintf( __( 'Removed: “%s”', 'adz-world' ), $from );
	}

	if ( 'forget' === $type ) {
		return __( 'Asked to forget every preference', 'adz-world' );
	}

	return '';
}

/**
 * Render one event as a chat line.
 *
 * Adz speak on the left; the reader speaks on the right.
 *
 * @param array $event Stored event.
 * @return void
 */
function adzworld_journey_render_event( $event ) {
	$type = isset( $event['type'] ) ? (string) $event['type'] : '';
	$time = isset( $event['time'] ) ? adzworld_journey_time( (int) $event['time'] ) : '';

	if ( 'ad' === $type ) {
		echo '<div class="adzworld-msg adzworld-msg--adz"><div class="adzworld-msg__meta"><span class="adzworld-msg__who">' . esc_html__( 'adz', 'adz-world' ) . '</span> · '
			. esc_html( $time ) . '</div><div class="adzworld-msg__body"><span class="adzworld-msg__label">' . esc_html__( 'Adz engaged with', 'adz-world' ) . '</span> '
			. '<code class="adzworld-msg__id">' . esc_html( adzworld_journey_ad_label( isset( $event['ad'] ) ? (string) $event['ad'] : '' ) ) . '</code></div></div>';
		return;
	}
	if ( 'response' === $type ) {
		echo '<div class="adzworld-msg adzworld-msg--adz adzworld-msg--response"><div class="adzworld-msg__meta"><span class="adzworld-msg__who">' . esc_html__( 'adz', 'adz-world' ) . '</span> · '
			. esc_html( $time ) . '</div><div class="adzworld-msg__body">' . esc_html( isset( $event['text'] ) ? (string) $event['text'] : '' ) . '</div></div>';
		return;
	}

	echo '<div class="adzworld-msg adzworld-msg--reader"><div class="adzworld-msg__meta">' . esc_html( $time ) . ' · <span class="adzworld-msg__who">'
		. esc_html__( 'reader', 'adz-world' ) . '</span></div><div class="adzworld-msg__body">' . esc_html( adzworld_journey_event_text( $event ) ) . '</div></div>';
}

/**
 * Whether an event is something the reader did.
 *
 * @param mixed $event Stored event.
 * @return bool
 */
function adzworld_journey_is_reader_event( $event ) {
	return is_array( $event ) && isset( $event['type'] ) && ! in_array( $event['type'], array( 'ad', 'response' ), true );
}

/**
 * Render one journey as an expandable card.
 *
 * @param WP_Post $journey Journey post.
 * @return void
 */
function adzworld_journey_render( $journey ) {
	$events  = array_values( array_filter( get_post_meta( $journey->ID, ADZWORLD_JOURNEY_EVENT_META, false ), 'is_array' ) );
	$actions = array_values( array_filter( $events, 'adzworld_journey_is_reader_event' ) );
	$latest  = empty( $actions ) ? '' : adzworld_journey_event_text( $actions[ count( $actions ) - 1 ] );
	$key     = $journey->post_title;
	$reader  = __( 'Session', 'adz-world' );
	if ( (int) $journey->post_author > 0 ) {
		$user   = get_userdata( (int) $journey->post_author );
		$reader = $user
			? sprintf(
				/* translators: %s: reader's display name. */
				__( 'Profile: %s', 'adz-world' ),
				$user->display_name
			)
			: __( 'Profile', 'adz-world' );
	}

	echo '<div class="adzworld-journey-row"><input type="checkbox" class="adzworld-journey-row__select" form="adzworld-logs-delete" name="adzworld_journeys[]" value="' . esc_attr( (string) $journey->ID ) . '" aria-label="'
		. esc_attr(
			sprintf(
				/* translators: %s: session identifier. */
				__( 'Select journey %s', 'adz-world' ),
				$key
			)
		) . '">';
	echo '<details class="adzworld-journey"><summary class="adzworld-journey__head"><span class="adzworld-journey__chevron" aria-hidden="true">&#9656;</span>'
		. '<span class="adzworld-journey__title">' . esc_html( adzworld_journey_started( $key ) ) . '</span>'
		. '<code class="adzworld-journey__id">' . esc_html( (string) substr( $key, -5 ) ) . '</code>'
		. '<span class="adzworld-journey__meta">' . esc_html( $reader ) . ' · ' . esc_html(
			sprintf(
				/* translators: %d: number of reader actions. */
				_n( '%d action', '%d actions', count( $actions ), 'adz-world' ),
				count( $actions )
			)
		) . '</span>'
		. '<span class="adzworld-journey__preview">' . esc_html( $latest ) . '</span></summary>';
	echo '<div class="adzworld-journey__body">';

	$where = '';
	foreach ( $events as $event ) {
		$here = adzworld_journey_where( $event );
		if ( isset( $event['type'] ) && 'ad' === $event['type'] && '' !== $here && $here !== $where ) {
			echo '<div class="adzworld-msg-state"><span class="adzworld-msg-state__rule"></span><span class="adzworld-msg-state__label">' . esc_html( $here ) . '</span><span class="adzworld-msg-state__rule"></span></div>';
			$where = $here;
		}
		adzworld_journey_render_event( $event );
	}
	echo '</div></details></div>';
}

/**
 * Render the AdzLogs tab.
 *
 * @return void
 */
function adzworld_logs_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Administrator permission is required.', 'adz-world' ), '', array( 'response' => 403 ) );
	}

	$notice = 'adzworld_logs_notice_' . get_current_user_id();
	if ( get_transient( $notice ) ) {
		echo '<div class="notice notice-success"><p>' . esc_html( get_transient( $notice ) ) . '</p></div>';
		delete_transient( $notice );
	}

	$page  = filter_input( INPUT_GET, 'paged', FILTER_VALIDATE_INT );
	$page  = is_int( $page ) && $page > 0 ? $page : 1;
	$query = new WP_Query(
		array(
			'post_type'      => ADZWORLD_JOURNEY_POST_TYPE,
			'post_status'    => 'private',
			'posts_per_page' => ADZWORLD_LOGS_PER_PAGE,
			'paged'          => $page,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);

	echo '<div class="adzworld-logs">';
	echo '<div class="adzworld-logs__toolbar"><h2>' . esc_html__( 'AdzLogs', 'adz-world' ) . '</h2><span class="adzworld-logs__count">' . esc_html(
		sprintf(
			/* translators: %d: number of journeys. */
			_n( '%d journey', '%d journeys', (int) $query->found_posts, 'adz-world' ),
			(int) $query->found_posts
		)
	) . '</span></div>';
	echo '<p class="description">' . esc_html__( 'Each journey begins at the Michel Time Stamp of a reader’s first action. Adz are on the left, the reader on the right. Adz that readers only look at are never recorded.', 'adz-world' ) . '</p>';

	if ( ! $query->have_posts() ) {
		echo '<p class="adzworld-logs__empty">' . esc_html__( 'No journeys yet. A journey appears after a reader rates an adz, refines it, or chooses a category.', 'adz-world' ) . '</p>';
	} else {
		echo '<form id="adzworld-logs-delete" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="adzworld_logs_delete">';
		wp_nonce_field( 'adzworld_logs_delete', 'adzworld_logs_delete_nonce' );
		echo '</form><div class="adzworld-logs__list">';
		foreach ( $query->posts as $journey ) {
			adzworld_journey_render( $journey );
		}
		echo '</div><p class="adzworld-logs__actions"><button type="submit" class="button" form="adzworld-logs-delete">' . esc_html__( 'Delete selected journeys', 'adz-world' ) . '</button> '
			. '<button type="submit" class="button button-link-delete" form="adzworld-logs-delete" name="adzworld_logs_delete_all" value="1" data-adzworld-confirm="'
			. esc_attr__( 'Delete every journey? This cannot be undone.', 'adz-world' ) . '">' . esc_html__( 'Delete all journeys', 'adz-world' ) . '</button></p>';
		$links = paginate_links(
			array(
				'base'    => add_query_arg( 'paged', '%#%', adzworld_desk_url( 'logs' ) ),
				'format'  => '',
				'current' => $page,
				'total'   => (int) $query->max_num_pages,
			)
		);
		if ( $links ) {
			echo '<nav class="adzworld-logs__pages" aria-label="' . esc_attr__( 'Journey pages', 'adz-world' ) . '">' . wp_kses_post( $links ) . '</nav>';
		}
	}

	adzworld_logs_settings_form();
	echo '</div>';
}

/**
 * Render the AdzLogs settings form.
 *
 * @return void
 */
function adzworld_logs_settings_form() {
	$settings = adzworld_logs_settings();
	$enabled  = adzworld_profile_saving_enabled();
	$confirm  = __( 'Readers who chose to keep adz requests on their profile will have that choice overridden. Are you sure?', 'adz-world' );

	echo '<section class="adzworld-logs__settings"><h2>' . esc_html__( 'AdzLogs settings', 'adz-world' ) . '</h2>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="adzworld_logs_settings">';
	wp_nonce_field( 'adzworld_logs_settings', 'adzworld_logs_nonce' );
	echo '<table class="form-table" role="presentation"><tbody>';
	echo '<tr><th scope="row"><label for="adzworld-logs-days">' . esc_html__( 'Keep journeys for', 'adz-world' ) . '</label></th><td>'
		. '<input class="small-text" type="number" min="0" step="1" id="adzworld-logs-days" name="adzworld_logs_days" value="' . esc_attr( (string) $settings['days'] ) . '"> '
		. esc_html__( 'days', 'adz-world' ) . '<p class="description">' . esc_html__( '0 keeps journeys until you delete them. Older journeys are deleted once a day.', 'adz-world' ) . '</p></td></tr>';
	echo '<tr><th scope="row"><label for="adzworld-logs-max">' . esc_html__( 'Keep at most', 'adz-world' ) . '</label></th><td>'
		. '<input class="small-text" type="number" min="0" step="1" id="adzworld-logs-max" name="adzworld_logs_max" value="' . esc_attr( (string) $settings['max'] ) . '"> '
		. esc_html__( 'journeys', 'adz-world' ) . '<p class="description">' . esc_html__( '0 means no limit. When there are more, the oldest are deleted first. This keeps the database from growing on busy sites.', 'adz-world' ) . '</p></td></tr>';
	echo '<tr><th scope="row">' . esc_html__( 'Profiles', 'adz-world' ) . '</th><td><label><input type="checkbox" name="adzworld_profile_saving" value="1"' . checked( $enabled, true, false )
		. ( $enabled ? ' data-adzworld-confirm-unchecked="' . esc_attr( $confirm ) . '"' : '' ) . '> '
		. esc_html__( 'When a reader has a profile, let them save adz requests with it', 'adz-world' ) . '</label><p class="description">'
		. esc_html__( 'Each reader still decides for themselves on their profile page. Readers who do not choose it keep adz requests only for their browser session.', 'adz-world' ) . '</p></td></tr>';
	echo '</tbody></table>';
	submit_button( __( 'Save AdzLogs settings', 'adz-world' ), 'secondary', 'submit', false );
	echo '</form></section>';
}

/*
 * ---------------------------------------------------------------------------
 * The reader's own choice, on their profile page
 * ---------------------------------------------------------------------------
 */

/**
 * Render the profile-saving choice on a profile page.
 *
 * When an adzadmin edits another reader's profile, changing the choice asks
 * for confirmation, because the choice belongs to that reader.
 *
 * @param WP_User $user Profile being edited.
 * @return void
 */
function adzworld_profile_consent_field( $user ) {
	if ( ! adzworld_profile_saving_enabled() ) {
		return;
	}

	$own     = get_current_user_id() === (int) $user->ID;
	$confirm = $own ? '' : __( 'This reader made this choice for themselves. Change it anyway?', 'adz-world' );

	echo '<h2 id="adzworld-profile">' . esc_html( ADZWORLD_NAME ) . '</h2><table class="form-table" role="presentation"><tbody><tr><th scope="row">'
		. esc_html__( 'Adz requests', 'adz-world' ) . '</th><td>';
	wp_nonce_field( ADZWORLD_PROFILE_CONSENT_NONCE, 'adzworld_profile_consent_nonce' );
	echo '<label><input type="checkbox" name="adzworld_save_with_profile" value="1"' . checked( adzworld_profile_consent( (int) $user->ID ), true, false )
		. ( '' !== $confirm ? ' data-adzworld-confirm-change="' . esc_attr( $confirm ) . '"' : '' ) . '> '
		. esc_html__( 'Save my adz requests with my profile', 'adz-world' ) . '</label><p class="description">'
		. esc_html__( 'When this is on, the categories, refinements and ratings you give adz are kept with your profile on this site, and your adz journeys are attached to it. When it is off, they last only for your browser session. Turning it off deletes what was kept on your profile.', 'adz-world' )
		. '</p></td></tr></tbody></table>';
}
add_action( 'show_user_profile', 'adzworld_profile_consent_field' );
add_action( 'edit_user_profile', 'adzworld_profile_consent_field' );

/**
 * Save the profile-saving choice.
 *
 * @param int $user_id Profile being saved.
 * @return void
 */
function adzworld_profile_consent_save( $user_id ) {
	$nonce = isset( $_POST['adzworld_profile_consent_nonce'] ) && is_string( $_POST['adzworld_profile_consent_nonce'] )
		? sanitize_text_field( wp_unslash( $_POST['adzworld_profile_consent_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, ADZWORLD_PROFILE_CONSENT_NONCE ) || ! current_user_can( 'edit_user', $user_id ) ) {
		return;
	}

	if ( isset( $_POST['adzworld_save_with_profile'] ) ) {
		update_user_meta( $user_id, ADZWORLD_PROFILE_CONSENT_META, '1' );
		return;
	}

	delete_user_meta( $user_id, ADZWORLD_PROFILE_CONSENT_META );
	delete_user_meta( $user_id, ADZWORLD_PREFERENCE_META );
	delete_user_meta( $user_id, ADZWORLD_FEEDBACK_META );
}
add_action( 'personal_options_update', 'adzworld_profile_consent_save' );
add_action( 'edit_user_profile_update', 'adzworld_profile_consent_save' );

/*
 * ---------------------------------------------------------------------------
 * Privacy
 * ---------------------------------------------------------------------------
 */

/**
 * Suggest privacy-policy text describing what AdzLogs keep.
 *
 * @return void
 */
function adzworld_logs_privacy_policy() {
	if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
		return;
	}

	$content = '<p>' . esc_html__( 'Adz on this site record nothing about a reader who only looks at them. When a reader rates an adz, refines it, or chooses a category, the site starts a browser session named by the time of that first action and five random characters. It keeps a journey of those actions: which rotation and slot, which adz, the rating, and the words the reader entered. No IP address, name or email address is kept in it.', 'adz-world' ) . '</p>'
		. '<p>' . esc_html__( 'A signed-in reader may choose on their profile page to keep adz requests with their profile. Only then are their preferences and journeys attached to their account. Journeys are deleted after the number of days the site owner sets.', 'adz-world' ) . '</p>';
	wp_add_privacy_policy_content( ADZWORLD_NAME, wp_kses_post( $content ) );
}
add_action( 'admin_init', 'adzworld_logs_privacy_policy' );

/**
 * Journeys attached to one account.
 *
 * @param int $user_id WordPress user ID.
 * @return int[] Post IDs.
 */
function adzworld_profile_journeys( $user_id ) {
	return array_map(
		'intval',
		get_posts(
			array(
				'post_type'        => ADZWORLD_JOURNEY_POST_TYPE,
				'post_status'      => 'private',
				'author'           => $user_id,
				'numberposts'      => ADZWORLD_LOGS_CLEANUP_BATCH,
				'fields'           => 'ids',
				'suppress_filters' => false,
			)
		)
	);
}

/**
 * Register the AdzLogs personal-data exporter.
 *
 * @param array $exporters Registered exporters.
 * @return array
 */
function adzworld_register_privacy_exporter( $exporters ) {
	$exporters['adz-world'] = array(
		'exporter_friendly_name' => ADZWORLD_NAME,
		'callback'               => 'adzworld_privacy_export',
	);

	return $exporters;
}
add_filter( 'wp_privacy_personal_data_exporters', 'adzworld_register_privacy_exporter' );

/**
 * Export one account's adz journeys.
 *
 * @param string $email Account email address.
 * @return array{data:array,done:bool}
 */
function adzworld_privacy_export( $email ) {
	$user = get_user_by( 'email', $email );
	$data = array();
	if ( $user ) {
		foreach ( adzworld_profile_journeys( (int) $user->ID ) as $post_id ) {
			$lines = array();
			foreach ( get_post_meta( $post_id, ADZWORLD_JOURNEY_EVENT_META, false ) as $event ) {
				if ( ! is_array( $event ) || ! isset( $event['type'] ) ) {
					continue;
				}
				$time    = isset( $event['time'] ) ? adzworld_journey_time( (int) $event['time'] ) : '';
				$text    = 'ad' === $event['type'] && isset( $event['ad'] ) ? adzworld_journey_ad_label( (string) $event['ad'] ) : adzworld_journey_event_text( $event );
				$lines[] = $time . ' ' . $text;
			}
			$data[] = array(
				'group_id'    => 'adz-world-journeys',
				'group_label' => __( 'Adz journeys', 'adz-world' ),
				'item_id'     => 'adz-world-journey-' . $post_id,
				'data'        => array(
					array(
						'name'  => __( 'Journey', 'adz-world' ),
						'value' => get_the_title( $post_id ),
					),
					array(
						'name'  => __( 'Actions', 'adz-world' ),
						'value' => implode( "\n", $lines ),
					),
				),
			);
		}
	}

	return array(
		'data' => $data,
		'done' => true,
	);
}

/**
 * Register the AdzLogs personal-data eraser.
 *
 * @param array $erasers Registered erasers.
 * @return array
 */
function adzworld_register_privacy_eraser( $erasers ) {
	$erasers['adz-world'] = array(
		'eraser_friendly_name' => ADZWORLD_NAME,
		'callback'             => 'adzworld_privacy_erase',
	);

	return $erasers;
}
add_filter( 'wp_privacy_personal_data_erasers', 'adzworld_register_privacy_eraser' );

/**
 * Erase one account's adz journeys and saved adz requests.
 *
 * @param string $email Account email address.
 * @return array{items_removed:bool,items_retained:bool,messages:array,done:bool}
 */
function adzworld_privacy_erase( $email ) {
	$user    = get_user_by( 'email', $email );
	$removed = false;
	$done    = true;
	if ( $user ) {
		$journeys = adzworld_profile_journeys( (int) $user->ID );
		foreach ( $journeys as $post_id ) {
			$removed = wp_delete_post( $post_id, true ) ? true : $removed;
		}
		$done = count( $journeys ) < ADZWORLD_LOGS_CLEANUP_BATCH;
		if ( $done ) {
			$removed = delete_user_meta( (int) $user->ID, ADZWORLD_PREFERENCE_META ) || $removed;
			$removed = delete_user_meta( (int) $user->ID, ADZWORLD_FEEDBACK_META ) || $removed;
		}
	}

	return array(
		'items_removed'  => $removed,
		'items_retained' => false,
		'messages'       => array(),
		'done'           => $done,
	);
}
