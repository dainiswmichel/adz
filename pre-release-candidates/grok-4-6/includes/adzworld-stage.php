<?php
/**
 * Rotation stage.
 *
 * The shortcode serves the ad networks the administrator has already
 * connected. Adz are absent until the shortcode adds them. every="10" is one
 * mix: nine network serves, then one adz serve. It does nothing when no adz
 * were added. Until a purchase note is saved, an added adz serve is the
 * introduction panel. The note stays on this site.
 *
 * display="slot" is one visible item. display="flat" and display="3d" add
 * forward and back arrows and a dotted menu.
 *
 * @package adz-world
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sample notes the introduction offers. They fill the field. They do not submit it.
 *
 * @return string[]
 */
function adzworld_intent_examples() {
	return array(
		"yes, that's right but the couch should be a light color and for 2 ppl",
		'i do not want to lease a car i want to purchase a used car',
		'cars above 50k only',
		'bicycles for 9 year old girls only',
	);
}

/**
 * Words that do not help choose a local adz.
 *
 * @return string[]
 */
function adzworld_intent_stopwords() {
	return array(
		'the',
		'and',
		'for',
		'only',
		'you',
		'that',
		'this',
		'with',
		'but',
		'should',
		'yes',
		'right',
		'have',
		'would',
		'like',
		'near',
		'future',
		'what',
		'from',
		'into',
		'your',
		'are',
		'was',
	);
}

/**
 * Cap a purchase note.
 *
 * @param string $text Note.
 * @return string
 */
function adzworld_trim_intent( $text ) {
	$text = trim( $text );

	if ( function_exists( 'mb_substr' ) ) {
		return mb_substr( $text, 0, 280 );
	}

	return substr( $text, 0, 280 );
}

/**
 * Positive and excluded words from a purchase note.
 *
 * "do not want …" excludes that clause. A later "i want …" clause is kept.
 * A token that is asked for again is not excluded.
 *
 * @param string $text Note.
 * @return array{want:string[],skip:string[]}
 */
function adzworld_intent_parts( $text ) {
	$text = strtolower( $text );
	$text = str_replace( array( '’', '`' ), "'", $text );
	$skip = array();
	$want = array();

	$halves = preg_split( '/\bi want\b/', $text, 2 );
	$left   = isset( $halves[0] ) ? $halves[0] : '';
	$right  = isset( $halves[1] ) ? $halves[1] : '';

	if ( preg_match( "/do not want|don't want|dont want/", $left ) ) {
		$clause = preg_replace( "/^.*?do not want|^.*?don't want|^.*?dont want/", ' ', $left );
		$skip   = adzworld_intent_words( is_string( $clause ) ? $clause : '' );
		$want   = adzworld_intent_words( $right );
	} else {
		$want = adzworld_intent_words( $text );

		if ( preg_match_all( '/\bnot\s+([a-z0-9][a-z0-9-]{2,30})/', $text, $found ) ) {
			foreach ( $found[1] as $word ) {
				$skip[] = $word;
			}
		}
	}

	$skip = array_values( array_diff( array_unique( $skip ), $want ) );
	$want = array_values( array_unique( $want ) );

	return array(
		'want' => $want,
		'skip' => $skip,
	);
}

/**
 * Useful words from one clause.
 *
 * @param string $text Clause.
 * @return string[]
 */
function adzworld_intent_words( $text ) {
	$words = array();
	$stop  = adzworld_intent_stopwords();

	foreach ( preg_split( '/[^a-z0-9]+/', strtolower( $text ) ) as $word ) {
		if ( ! is_string( $word ) || strlen( $word ) < 3 ) {
			continue;
		}

		if ( in_array( $word, $stop, true ) ) {
			continue;
		}

		$words[] = $word;
	}

	return $words;
}

/**
 * Score title and body against a purchase note. A negative score is excluded.
 *
 * @param string                             $haystack Title and content.
 * @param array{want:string[],skip:string[]} $parts Note parts.
 * @return int
 */
function adzworld_score_ad_text( $haystack, $parts ) {
	$hay = strtolower( wp_strip_all_tags( $haystack ) );

	foreach ( $parts['skip'] as $word ) {
		if ( false !== strpos( $hay, $word ) ) {
			return -1;
		}
	}

	$score = 0;

	foreach ( $parts['want'] as $word ) {
		if ( false !== strpos( $hay, $word ) ) {
			++$score;
		}
	}

	return $score;
}

/**
 * Whether this serve is the adz place in the count.
 *
 * @param int $serve 1-based serve count.
 * @param int $every Cycle length. 10 means the 10th, 20th, and so on.
 * @return bool
 */
function adzworld_stage_is_adz_turn( $serve, $every ) {
	$serve = absint( $serve );
	$every = absint( $every );

	if ( $serve < 1 || $every < 2 ) {
		return false;
	}

	return 0 === ( $serve % $every );
}

/**
 * Mix length from the shortcode. Empty or below 2 means the items are even.
 *
 * @param mixed $every Attribute.
 * @return int
 */
function adzworld_stage_every( $every ) {
	if ( ! is_scalar( $every ) || '' === (string) $every ) {
		return 0;
	}

	$every = absint( $every );

	if ( $every < 2 ) {
		return 0;
	}

	if ( $every > 100 ) {
		return 100;
	}

	return $every;
}

/**
 * How many adz the shortcode puts in the rotation. Zero means none.
 *
 * An id list wins over the count. The count stops at five.
 *
 * @param mixed $adz     Count attribute.
 * @param mixed $id_list Id attribute.
 * @return int
 */
function adzworld_stage_adz_count( $adz, $id_list ) {
	$ids = adzworld_id_list( $id_list );

	if ( $ids ) {
		return count( $ids );
	}

	$count = absint( $adz );

	return ( $count > 5 ) ? 5 : $count;
}

/**
 * Indexes to render for this serve.
 *
 * Browsing returns every configured item. A slot with one item returns it.
 * A slot whose list contains an adz and a mix walks the network items, then
 * one adz item when the count lands. A list with no adz cannot return one.
 *
 * @param string[] $kinds  Item kinds, network or anything else for an adz.
 * @param int      $serve  1-based serve count.
 * @param int      $every  Mix length, or 0 when the items are even.
 * @param bool     $one    True for the single-item display.
 * @return int[]
 */
function adzworld_stage_pick_indexes( $kinds, $serve, $every, $one ) {
	$indexes = array_keys( $kinds );

	if ( ! $indexes ) {
		return array();
	}

	if ( ! $one || 1 === count( $indexes ) ) {
		return $indexes;
	}

	$serve = absint( $serve );

	if ( $serve < 1 ) {
		$serve = 1;
	}

	$network_indexes = array();
	$adz_indexes     = array();

	foreach ( $kinds as $index => $kind ) {
		if ( 'network' === $kind ) {
			$network_indexes[] = $index;
		} else {
			$adz_indexes[] = $index;
		}
	}

	if ( $network_indexes && $adz_indexes && adzworld_stage_is_adz_turn( $serve, $every ) ) {
		$turn = (int) floor( $serve / absint( $every ) ) - 1;

		if ( $turn < 0 ) {
			$turn = 0;
		}

		return array( $adz_indexes[ $turn % count( $adz_indexes ) ] );
	}

	if ( $network_indexes && $adz_indexes && absint( $every ) >= 2 ) {
		return array( $network_indexes[ ( $serve - 1 ) % count( $network_indexes ) ] );
	}

	return array( $indexes[ ( $serve - 1 ) % count( $indexes ) ] );
}

/**
 * Slot, flat carousel, or 3D carousel.
 *
 * @param mixed $display Attribute.
 * @return string
 */
function adzworld_stage_display( $display ) {
	$display = is_string( $display ) ? strtolower( $display ) : '';

	if ( in_array( $display, array( 'slot', 'flat', '3d' ), true ) ) {
		return $display;
	}

	return 'slot';
}

/**
 * Comma-separated ad ids, capped.
 *
 * @param mixed $id_list Attribute.
 * @return int[]
 */
function adzworld_id_list( $id_list ) {
	$out = array();

	if ( ! is_string( $id_list ) || '' === $id_list ) {
		return $out;
	}

	foreach ( explode( ',', $id_list ) as $part ) {
		$id = absint( $part );

		if ( $id ) {
			$out[] = $id;
		}

		if ( count( $out ) >= 20 ) {
			break;
		}
	}

	return $out;
}

/**
 * Cookie arguments shared by the serve count and the purchase note.
 *
 * @param int $expires Unix time, or 0 for a session cookie.
 * @return array<string,mixed>
 */
function adzworld_cookie_args( $expires ) {
	$args = array(
		'expires'  => $expires,
		'path'     => ( defined( 'COOKIEPATH' ) && COOKIEPATH ) ? COOKIEPATH : '/',
		'secure'   => function_exists( 'is_ssl' ) && is_ssl(),
		'httponly' => true,
		'samesite' => 'Lax',
	);

	if ( defined( 'COOKIE_DOMAIN' ) && is_string( COOKIE_DOMAIN ) && '' !== COOKIE_DOMAIN ) {
		$args['domain'] = COOKIE_DOMAIN;
	}

	return $args;
}

/**
 * Serve number for this request. The cookie is advanced once.
 *
 * @return int
 */
function adzworld_stage_serve_number() {
	static $number = 0;

	if ( $number > 0 ) {
		return $number;
	}

	$current = 0;

	if ( isset( $_COOKIE['adzworld_stage_n'] ) ) {
		$current = absint( wp_unslash( $_COOKIE['adzworld_stage_n'] ) );
	}

	$number = $current + 1;

	if ( $number > 1000000 ) {
		$number = 1;
	}

	if ( ! headers_sent() ) {
		setcookie( 'adzworld_stage_n', (string) $number, adzworld_cookie_args( 0 ) );
	}

	return $number;
}

/**
 * Advance the serve count before output when the singular entry contains the stage.
 *
 * @return void
 */
function adzworld_stage_prepare_serve() {
	if ( is_admin() || ! is_singular() ) {
		return;
	}

	$post = get_post();

	if ( ! $post instanceof WP_Post ) {
		return;
	}

	if ( has_shortcode( $post->post_content, 'adzworld_stage' ) ) {
		adzworld_stage_serve_number();
	}
}
add_action( 'template_redirect', 'adzworld_stage_prepare_serve' );

/**
 * Purchase note for this access. A profile uses user meta. Otherwise the session cookie.
 *
 * @return string
 */
function adzworld_current_intent() {
	if ( is_user_logged_in() ) {
		$saved = get_user_meta( get_current_user_id(), 'adzworld_intent', true );

		return is_string( $saved ) ? adzworld_trim_intent( $saved ) : '';
	}

	if ( ! isset( $_COOKIE['adzworld_intent'] ) ) {
		return '';
	}

	return adzworld_trim_intent( sanitize_textarea_field( wp_unslash( $_COOKIE['adzworld_intent'] ) ) );
}

/**
 * Store or clear the session note.
 *
 * @param string $text Note.
 * @return void
 */
function adzworld_set_intent_cookie( $text ) {
	if ( '' === $text ) {
		setcookie( 'adzworld_intent', '', adzworld_cookie_args( time() - DAY_IN_SECONDS ) );
		return;
	}

	setcookie( 'adzworld_intent', $text, adzworld_cookie_args( 0 ) );
}

/**
 * Save a purchase note for this access only.
 *
 * @return void
 */
function adzworld_save_intent() {
	check_admin_referer( 'adzworld_save_intent', 'adzworld_intent_nonce' );

	$text = '';

	if ( isset( $_POST['adzworld_intent'] ) ) {
		$text = adzworld_trim_intent( sanitize_textarea_field( wp_unslash( $_POST['adzworld_intent'] ) ) );
	}

	if ( is_user_logged_in() ) {
		if ( '' === $text ) {
			delete_user_meta( get_current_user_id(), 'adzworld_intent' );
		} else {
			update_user_meta( get_current_user_id(), 'adzworld_intent', $text );
		}

		adzworld_set_intent_cookie( '' );
	} else {
		adzworld_set_intent_cookie( $text );
	}

	$back = wp_get_referer();

	if ( ! is_string( $back ) || '' === $back ) {
		$back = home_url( '/' );
	}

	wp_safe_redirect( $back );
	exit;
}
add_action( 'admin_post_adzworld_save_intent', 'adzworld_save_intent' );
add_action( 'admin_post_nopriv_adzworld_save_intent', 'adzworld_save_intent' );

/**
 * The introduction form.
 *
 * @param string $intent    Saved note.
 * @param bool   $collapsed Whether the form starts closed.
 * @return string
 */
function adzworld_intent_form( $intent, $collapsed ) {
	static $form_number = 0;

	++$form_number;
	$field_id = 'adzworld-intent-' . $form_number;
	$html     = '<form class="adzworld-intent" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	$html    .= '<input type="hidden" name="action" value="adzworld_save_intent" />';
	$html    .= wp_nonce_field( 'adzworld_save_intent', 'adzworld_intent_nonce', true, false );
	$html    .= '<label for="' . esc_attr( $field_id ) . '">' . esc_html__( 'What you would like to purchase in the near future', 'adz-world' ) . '</label>';
	$html    .= '<textarea id="' . esc_attr( $field_id ) . '" name="adzworld_intent" maxlength="280" rows="4">' . esc_textarea( $intent ) . '</textarea>';
	$html    .= '<p class="adzworld-intent__examples">';

	foreach ( adzworld_intent_examples() as $example ) {
		$html .= '<button type="button" class="adzworld-intent__example" data-adzworld-example="' . esc_attr( $example ) . '">' . esc_html( $example ) . '</button>';
	}

	$html .= '</p>';
	$html .= '<button type="submit">' . esc_html__( 'Save purchase note', 'adz-world' ) . '</button>';
	$html .= '<p class="adzworld-intent__note">' . esc_html__( 'This note stays on this site. It is not sent to an ad network. It does not record a purchase, and it does not decide that this access is human.', 'adz-world' ) . '</p>';
	$html .= '</form>';

	if ( ! $collapsed ) {
		return $html;
	}

	return '<details class="adzworld-intent__details"><summary>' . esc_html__( 'Change what you would like to purchase', 'adz-world' ) . '</summary>' . $html . '</details>';
}

/**
 * Introduction panel.
 *
 * @param string $intent Saved note.
 * @return string
 */
function adzworld_intro_html( $intent ) {
	$html  = '<div class="adzworld-intro">';
	$html .= '<p>' . esc_html__( 'You have the option to receive permission-based adz by filling in what you would like to purchase in the near future below.', 'adz-world' ) . '</p>';
	$html .= adzworld_intent_form( $intent, false );
	$html .= '</div>';

	return $html;
}

/**
 * Connected networks for this shortcode.
 *
 * A named network that is not enabled is not replaced with a different one.
 * An empty name uses every enabled slot network.
 *
 * @param mixed $requested Attribute.
 * @return string[]
 */
function adzworld_stage_network_ids( $requested ) {
	$requested = is_string( $requested ) ? sanitize_key( $requested ) : '';
	$settings  = adzworld_network_settings();
	$ids       = array();

	if ( '' !== $requested ) {
		if ( isset( $settings[ $requested ] ) && '1' === $settings[ $requested ]['enabled'] ) {
			return array( $requested );
		}

		return array();
	}

	foreach ( adzworld_slot_network_ids() as $id ) {
		if ( isset( $settings[ $id ] ) && '1' === $settings[ $id ]['enabled'] ) {
			$ids[] = $id;
		}
	}

	return $ids;
}

/**
 * Local adz for the stage, best note match first.
 *
 * @param mixed  $id_list Optional id list.
 * @param string $intent  Purchase note.
 * @return WP_Post[]
 */
function adzworld_stage_ads( $id_list, $intent ) {
	$args = array(
		'post_type'      => 'adz_ad',
		'post_status'    => 'publish',
		'posts_per_page' => 20,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'no_found_rows'  => true,
	);
	$ids  = adzworld_id_list( $id_list );

	if ( $ids ) {
		$args['post__in'] = $ids;
	}

	$posts = get_posts( $args );

	if ( ! is_array( $posts ) || ! $posts ) {
		return array();
	}

	if ( '' === $intent ) {
		return array_slice( $posts, 0, 5 );
	}

	$parts  = adzworld_intent_parts( $intent );
	$scored = array();

	foreach ( $posts as $post ) {
		if ( ! $post instanceof WP_Post ) {
			continue;
		}

		$score = adzworld_score_ad_text( $post->post_title . ' ' . $post->post_content, $parts );

		if ( $score < 0 ) {
			continue;
		}

		$scored[] = array(
			'post'  => $post,
			'score' => $score,
		);
	}

	usort(
		$scored,
		static function ( $left, $right ) {
			if ( $left['score'] === $right['score'] ) {
				return 0;
			}

			return ( $left['score'] > $right['score'] ) ? -1 : 1;
		}
	);

	if ( ! $scored ) {
		return array_slice( $posts, 0, 5 );
	}

	$out = array();

	foreach ( $scored as $row ) {
		$out[] = $row['post'];

		if ( count( $out ) >= 5 ) {
			break;
		}
	}

	return $out;
}

/**
 * One slide.
 *
 * @param string $kind   network, intro, or adz.
 * @param bool   $active Whether it is the visible item.
 * @param string $label  Kicker. Empty for the introduction.
 * @param string $inner  Already escaped markup.
 * @return string
 */
function adzworld_stage_slide( $kind, $active, $label, $inner ) {
	$class = 'adzworld-stage__slide' . ( $active ? ' is-active' : '' );
	$html  = '<article class="' . esc_attr( $class ) . '" data-kind="' . esc_attr( $kind ) . '">';

	if ( '' !== $label ) {
		$html .= '<p class="adzworld-stage__kicker">' . esc_html( $label ) . '</p>';
	}

	$html .= $inner;
	$html .= '</article>';

	return $html;
}

/**
 * Local adz slide body.
 *
 * @param WP_Post $post Ad.
 * @return string
 */
function adzworld_stage_ad_html( $post ) {
	$body  = '<h3 class="adzworld-adz__title">' . esc_html( get_the_title( $post ) ) . '</h3>';
	$body .= '<div class="adzworld-adz__body">' . wp_kses( $post->post_content, adzworld_ad_allowed_html() ) . '</div>';

	return $body;
}

/**
 * Remember that the footer should print the stage script.
 *
 * @param string|null $key Flag, or null to read the flags.
 * @return array<string,bool>|void
 */
function adzworld_stage_flag( $key = null ) {
	static $flags = array();

	if ( null === $key ) {
		return $flags;
	}

	$flags[ $key ] = true;
}

/**
 * Print stage assets after the shortcode has decided which libraries this page needs.
 *
 * @return void
 */
function adzworld_stage_footer_assets() {
	$flags = adzworld_stage_flag();

	if ( empty( $flags['script'] ) ) {
		return;
	}

	$deps = array();

	foreach ( array( 'adzworld-adsense', 'adzworld-gpt', 'adzworld-taboola' ) as $handle ) {
		if ( wp_script_is( $handle, 'enqueued' ) ) {
			$deps[] = $handle;
		}
	}

	wp_enqueue_script(
		'adzworld-stage',
		plugin_dir_url( adzworld_plugin_file() ) . 'js/adzworld-stage.js',
		$deps,
		'1.1.0',
		true
	);
	wp_localize_script(
		'adzworld-stage',
		'adzworldStage',
		array(
			'taboolaBase' => 'https://cdn.taboola.com/libtrc/',
		)
	);
}
add_action( 'wp_footer', 'adzworld_stage_footer_assets', 1 );

/**
 * Stage shortcode.
 *
 * @param array<string,string>|string $atts Attributes.
 * @return string
 */
function adzworld_stage_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'display' => 'slot',
			'every'   => '',
			'arrows'  => '1',
			'dots'    => '1',
			'network' => '',
			'ads'     => '',
			'adz'     => '0',
		),
		$atts,
		'adzworld_stage'
	);

	$display   = adzworld_stage_display( $atts['display'] );
	$every     = adzworld_stage_every( $atts['every'] );
	$browse    = ( 'slot' !== $display );
	$adz_count = adzworld_stage_adz_count( $atts['adz'], $atts['ads'] );
	$kinds     = array();
	$payloads  = array();
	$intent    = '';
	$ads       = array();

	foreach ( adzworld_stage_network_ids( $atts['network'] ) as $network_id ) {
		$kinds[]    = 'network';
		$payloads[] = array( 'id' => $network_id );
	}

	if ( $adz_count > 0 ) {
		$intent = adzworld_current_intent();

		if ( '' !== $intent ) {
			$ads = array_slice( adzworld_stage_ads( $atts['ads'], $intent ), 0, $adz_count );
		}

		if ( '' === $intent || ! $ads ) {
			$kinds[]    = 'intro';
			$payloads[] = array();
		} else {
			foreach ( $ads as $post ) {
				$kinds[]    = 'adz';
				$payloads[] = array( 'post' => $post );
			}
		}
	}

	if ( ! $kinds ) {
		if ( current_user_can( 'manage_options' ) ) {
			return '<p class="adzworld-stage__empty">' . esc_html__( 'This stage has no network unit and no adz in the rotation.', 'adz-world' ) . '</p>';
		}

		return '';
	}

	$chosen       = adzworld_stage_pick_indexes( $kinds, adzworld_stage_serve_number(), $every, ! $browse );
	$slides       = array();
	$active_index = 0;
	$have_active  = false;

	foreach ( $chosen as $source_index ) {
		$kind   = $kinds[ $source_index ];
		$active = ! $have_active;

		if ( 'network' === $kind ) {
			$network_id = $payloads[ $source_index ]['id'];
			$load       = ( 'taboola' !== $network_id || $active );
			$unit       = adzworld_network_slide_html( $network_id, $active, $load );

			if ( '' === $unit ) {
				continue;
			}

			$slides[] = adzworld_stage_slide( 'network', $active, __( 'Advertisement', 'adz-world' ), $unit );
		} elseif ( 'intro' === $kind ) {
			$slides[] = adzworld_stage_slide( 'intro', $active, '', adzworld_intro_html( $intent ) );
		} else {
			$post     = $payloads[ $source_index ]['post'];
			$slides[] = adzworld_stage_slide(
				'adz',
				$active,
				__( 'adz', 'adz-world' ),
				adzworld_stage_ad_html( $post ) . ( $active ? adzworld_intent_form( $intent, true ) : '' )
			);
		}

		if ( $active && $slides ) {
			$have_active = true;
		}
	}

	if ( ! $slides ) {
		if ( current_user_can( 'manage_options' ) ) {
			return '<p class="adzworld-stage__empty">' . esc_html__( 'This stage has no network unit and no adz in the rotation.', 'adz-world' ) . '</p>';
		}

		return '';
	}

	wp_enqueue_style(
		'adzworld-stage',
		plugin_dir_url( adzworld_plugin_file() ) . 'css/adzworld-stage.css',
		array(),
		'1.1.0'
	);
	adzworld_stage_flag( 'script' );

	$class = 'adzworld-stage adzworld-stage--' . $display;
	$html  = '<section class="' . esc_attr( $class ) . '" data-adzworld-stage data-display="' . esc_attr( $display ) . '" tabindex="0">';
	$html .= '<div class="adzworld-stage__viewport"><div class="adzworld-stage__track">';
	$html .= implode( '', $slides );
	$html .= '</div></div>';

	if ( $browse && '0' !== (string) $atts['arrows'] ) {
		$html .= '<button type="button" class="adzworld-stage__prev" aria-label="' . esc_attr__( 'Previous item', 'adz-world' ) . '">&#8249;</button>';
		$html .= '<button type="button" class="adzworld-stage__next" aria-label="' . esc_attr__( 'Next item', 'adz-world' ) . '">&#8250;</button>';
	}

	if ( $browse && '0' !== (string) $atts['dots'] ) {
		$count = count( $slides );
		$html .= '<div class="adzworld-stage__dots" role="tablist">';

		foreach ( array_keys( $slides ) as $index ) {
			$dot_on = ( $active_index === $index );
			$html  .= '<button type="button" class="adzworld-stage__dot' . ( $dot_on ? ' is-active' : '' ) . '" data-adzworld-index="' . esc_attr( (string) $index ) . '" aria-label="' . esc_attr(
				sprintf(
					/* translators: 1: item number, 2: number of items */
					__( 'Show item %1$d of %2$d', 'adz-world' ),
					$index + 1,
					$count
				)
			) . '"' . ( $dot_on ? ' aria-current="true"' : '' ) . '></button>';
		}

		$html .= '</div>';
	}

	$html .= '</section>';

	return $html;
}
add_shortcode( 'adzworld_stage', 'adzworld_stage_shortcode' );

/**
 * The introduction panel on its own.
 *
 * @return string
 */
function adzworld_intro_shortcode() {
	wp_enqueue_style(
		'adzworld-stage',
		plugin_dir_url( adzworld_plugin_file() ) . 'css/adzworld-stage.css',
		array(),
		'1.1.0'
	);
	adzworld_stage_flag( 'script' );

	return adzworld_intro_html( adzworld_current_intent() );
}
add_shortcode( 'adzworld_intro', 'adzworld_intro_shortcode' );
