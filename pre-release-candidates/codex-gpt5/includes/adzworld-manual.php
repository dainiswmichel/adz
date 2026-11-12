<?php
/**
 * Active setup manual. Account steps stay closed until that account is saved.
 *
 * @package adz-world
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether a direct-network or affiliate row has identifiers saved.
 *
 * Enabled is "live". Any other stored identifier is "saved". An empty row
 * is "apply": the manual shows only the account step.
 *
 * @param array<string,string> $row Stored account row.
 * @return string apply, saved, or live.
 */
function adzworld_manual_account_phase( $row ) {
	if ( ! is_array( $row ) ) {
		return 'apply';
	}
	if ( isset( $row['enabled'] ) && '1' === (string) $row['enabled'] ) {
		return 'live';
	}
	foreach ( $row as $key => $value ) {
		if ( in_array( $key, array( 'accepted', 'enabled' ), true ) ) {
			continue;
		}
		if ( is_string( $value ) && '' !== $value ) {
			return 'saved';
		}
	}
	return 'apply';
}

/**
 * Affiliate phase. Live requires a link the account can actually emit.
 *
 * @param string               $id      Network id.
 * @param array<string,string> $account Stored account.
 * @return string apply, saved, or live.
 */
function adzworld_manual_affiliate_phase( $id, $account ) {
	$url = '';
	if ( is_array( $account ) && function_exists( 'adzworld_affiliate_url' ) ) {
		$url = adzworld_affiliate_url( $id, $account, 'item' );
	}
	if ( is_array( $account ) && isset( $account['enabled'] ) && '1' === (string) $account['enabled'] && is_string( $url ) && '' !== $url ) {
		return 'live';
	}
	if ( ! is_array( $account ) ) {
		return 'apply';
	}
	foreach ( $account as $key => $value ) {
		if ( 'enabled' === $key ) {
			continue;
		}
		if ( is_string( $value ) && '' !== $value ) {
			return 'saved';
		}
	}
	return 'apply';
}

/**
 * Whether the installed rotation accepts one display name.
 *
 * @param string $name Display name.
 * @return bool
 */
function adzworld_manual_has_display( $name ) {
	if ( function_exists( 'adzworld_rotation_display_formats' ) ) {
		return isset( adzworld_rotation_display_formats()[ $name ] );
	}
	return in_array( $name, array( 'single', 'flat', '3d' ), true );
}

/**
 * Frame to recommend once a direct network account exists.
 *
 * @param string               $id  Network id.
 * @param array<string,string> $row Stored network row.
 * @return array{display:string,size:string}
 */
function adzworld_manual_network_frame( $id, $row ) {
	$display = adzworld_manual_has_display( 'inline-card' ) ? 'inline-card' : 'flat';
	$size    = '';
	if ( 'gam' !== $id || ! is_array( $row ) ) {
		return array(
			'display' => $display,
			'size'    => $size,
		);
	}
	$width  = isset( $row['width'] ) ? (int) $row['width'] : 0;
	$height = isset( $row['height'] ) ? (int) $row['height'] : 0;
	$token  = $width . 'x' . $height;
	if ( $width >= 468 && adzworld_manual_has_display( 'banner' ) ) {
		$display = 'banner';
	}
	if ( function_exists( 'adzworld_rotation_size_presets' ) && isset( adzworld_rotation_size_presets()[ $token ] ) ) {
		$size = $token;
	}
	return array(
		'display' => $display,
		'size'    => $size,
	);
}

/**
 * Page shortcode for one saved rotation and frame.
 *
 * @param string $name    Rotation key.
 * @param string $display Display name.
 * @param string $size    Size token, or an empty string.
 * @return string
 */
function adzworld_manual_shortcode( $name, $display, $size ) {
	$name = sanitize_key( $name );
	if ( '' === $name ) {
		$name = 'main';
	}
	$display = adzworld_manual_has_display( $display ) ? $display : 'flat';
	$allowed = function_exists( 'adzworld_rotation_size_presets' ) ? adzworld_rotation_size_presets() : array();
	$size    = is_string( $size ) && isset( $allowed[ $size ] ) ? $size : '';
	$text    = '[adz_rotation name="' . $name . '" display="' . $display . '" controls="both"';
	if ( '' !== $size ) {
		$text .= ' size="' . $size . '"';
	}
	return $text . ']';
}

/**
 * First saved rotation key, or an empty string.
 *
 * @return string
 */
function adzworld_manual_primary_rotation_key() {
	if ( ! function_exists( 'adzworld_named_rotations' ) ) {
		return '';
	}
	$key = array_key_first( adzworld_named_rotations() );
	return is_string( $key ) ? $key : '';
}

/**
 * Posts and pages whose content contains the rotation shortcode.
 *
 * @return array<int,WP_Post>
 */
function adzworld_manual_shortcode_posts() {
	static $found = null;
	if ( is_array( $found ) ) {
		return $found;
	}
	$found = array();
	$query = new WP_Query(
		array(
			'no_found_rows'          => true,
			'post_status'            => array( 'publish', 'draft', 'private' ),
			'post_type'              => array( 'post', 'page' ),
			'posts_per_page'         => 20,
			's'                      => 'adz_rotation',
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);
	foreach ( $query->posts as $post ) {
		if ( $post instanceof WP_Post && false !== strpos( $post->post_content, '[adz_rotation' ) ) {
			$found[] = $post;
		}
	}
	return $found;
}

/**
 * Whether a slot token is already in a saved rotation or a page shortcode.
 *
 * @param string $token Slot token without a weight.
 * @return bool
 */
function adzworld_manual_token_used( $token ) {
	$token = sanitize_text_field( $token );
	if ( '' === $token || ! function_exists( 'adzworld_named_rotations' ) ) {
		return false;
	}
	foreach ( adzworld_named_rotations() as $rotation ) {
		$slots = isset( $rotation['slots'] ) && is_array( $rotation['slots'] ) ? $rotation['slots'] : array();
		foreach ( $slots as $slot ) {
			if ( ! is_string( $slot ) ) {
				continue;
			}
			if ( $slot === $token || 0 === strpos( $slot, $token . ':' ) ) {
				return true;
			}
		}
	}
	foreach ( adzworld_manual_shortcode_posts() as $post ) {
		if ( false !== strpos( $post->post_content, $token ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Comma-separated labels for one account's fields.
 *
 * @param array<string,array<string,mixed>> $fields Field rules.
 * @return string
 */
function adzworld_manual_field_labels( $fields ) {
	$labels = array();
	foreach ( $fields as $rule ) {
		if ( is_array( $rule ) && isset( $rule['label'] ) && is_string( $rule['label'] ) && '' !== $rule['label'] ) {
			$labels[] = $rule['label'];
		}
	}
	return implode( ', ', $labels );
}

/**
 * Visible status for one manual section.
 *
 * @param string $state is-done, is-open, or is-waiting.
 * @return string
 */
function adzworld_manual_state_label( $state ) {
	if ( 'is-done' === $state ) {
		return __( 'Done', 'adz-world' );
	}
	if ( 'is-open' === $state ) {
		return __( 'In progress', 'adz-world' );
	}
	return __( 'Waiting', 'adz-world' );
}

/**
 * Open one manual section.
 *
 * @param string $state   is-done, is-open, or is-waiting.
 * @param string $heading Section heading.
 * @return void
 */
function adzworld_manual_open( $state, $heading ) {
	if ( ! in_array( $state, array( 'is-done', 'is-open', 'is-waiting' ), true ) ) {
		$state = 'is-waiting';
	}
	echo '<section class="adzworld-manual-step ' . esc_attr( $state ) . '">';
	echo '<p class="adzworld-manual-state">' . esc_html( adzworld_manual_state_label( $state ) ) . '</p>';
	echo '<h3>' . esc_html( $heading ) . '</h3>';
}

/**
 * Status word for one saved account.
 *
 * @param string $phase apply, saved, or live.
 * @return string
 */
function adzworld_manual_phase_label( $phase ) {
	if ( 'live' === $phase ) {
		return __( 'Enabled', 'adz-world' );
	}
	if ( 'saved' === $phase ) {
		return __( 'Identifiers saved', 'adz-world' );
	}
	return __( 'Not saved', 'adz-world' );
}

/**
 * Providers that are named and are not given a rotation slot.
 *
 * @return array<string,string> Label, then the reason it is not a slot.
 */
function adzworld_manual_unoffered_providers() {
	return array(
		'Carbon'       => __( 'The creative replaces the script element, so it is not a rotation slot.', 'adz-world' ),
		'MGID'         => __( 'The site script is a path, not one checked identifier.', 'adz-world' ),
		'Infolinks'    => __( 'Infolinks writes into article text. It is not a rotation slot.', 'adz-world' ),
		'Publift'      => __( 'The contract places tags for the whole page. Those tags are not put inside a rotation.', 'adz-world' ),
		'PropellerAds' => __( 'Pop and push formats are not a content slot.', 'adz-world' ),
		'Adsterra'     => __( 'Pop and social-bar formats are not a content slot.', 'adz-world' ),
		'Monetag'      => __( 'Pop and push formats are not a content slot.', 'adz-world' ),
	);
}

/**
 * One row of the advertisement provider list.
 *
 * @return array<int,array{label:string,kind:string,status:string,note:string,desk:string}>
 */
function adzworld_manual_provider_rows() {
	$rows = array();
	if ( function_exists( 'adzworld_network_catalog' ) && function_exists( 'adzworld_network_settings' ) ) {
		$settings = adzworld_network_settings();
		foreach ( adzworld_network_catalog() as $id => $network ) {
			$stored = isset( $settings[ $id ] ) && is_array( $settings[ $id ] ) ? $settings[ $id ] : array();
			$rows[] = array(
				'label'  => (string) $network['label'],
				'kind'   => __( 'Direct unit', 'adz-world' ),
				'status' => adzworld_manual_phase_label( adzworld_manual_account_phase( $stored ) ),
				'note'   => __( 'Official unit. It becomes a rotation slot when the connection is enabled. Visitor words are not sent to the network.', 'adz-world' ),
				'desk'   => 'networks',
			);
		}
	}
	if ( function_exists( 'adzworld_affiliate_networks' ) && function_exists( 'adzworld_affiliate_accounts' ) ) {
		$accounts = adzworld_affiliate_accounts();
		foreach ( adzworld_affiliate_networks() as $id => $network ) {
			$stored = isset( $accounts[ $id ] ) && is_array( $accounts[ $id ] ) ? $accounts[ $id ] : array();
			$note   = 'clickbank' === $id
				? __( 'One vendor HopLink. The visitor’s words are not added to the URL.', 'adz-world' )
				: __( 'Search link under a local ad that matches the visitor’s words. The link is not a rotation slot.', 'adz-world' );
			$rows[] = array(
				'label'  => (string) $network['label'],
				'kind'   => __( 'Affiliate link', 'adz-world' ),
				'status' => adzworld_manual_phase_label( adzworld_manual_affiliate_phase( (string) $id, $stored ) ),
				'note'   => $note,
				'desk'   => 'affiliates',
			);
		}
	}
	if ( function_exists( 'adzworld_managed_network_catalog' ) ) {
		foreach ( adzworld_managed_network_catalog() as $network ) {
			$rows[] = array(
				'label'  => (string) $network['label'],
				'kind'   => __( 'Managed implementation', 'adz-world' ),
				'status' => __( 'Separate', 'adz-world' ),
				'note'   => isset( $network['method'] ) ? (string) $network['method'] : '',
				'desk'   => '',
			);
		}
	}
	foreach ( adzworld_manual_unoffered_providers() as $label => $reason ) {
		$rows[] = array(
			'label'  => (string) $label,
			'kind'   => __( 'Not a slot', 'adz-world' ),
			'status' => __( 'Not offered', 'adz-world' ),
			'note'   => (string) $reason,
			'desk'   => '',
		);
	}
	return $rows;
}

/**
 * Advertisement provider list for this site.
 *
 * @return void
 */
function adzworld_manual_render_providers() {
	echo '<h2>' . esc_html__( 'Advertisement providers', 'adz-world' ) . '</h2>';
	echo '<p>' . esc_html__( 'Every provider this plugin knows. The status column is this site. It stays at Not saved until that account is entered.', 'adz-world' ) . '</p>';
	echo '<table class="adzworld-manual-table"><thead><tr><th>' . esc_html__( 'Provider', 'adz-world' ) . '</th><th>' . esc_html__( 'Kind', 'adz-world' ) . '</th><th>' . esc_html__( 'On this site', 'adz-world' ) . '</th><th>' . esc_html__( 'Treatment', 'adz-world' ) . '</th></tr></thead><tbody>';
	foreach ( adzworld_manual_provider_rows() as $row ) {
		echo '<tr><td>' . esc_html( $row['label'] ) . '</td><td>' . esc_html( $row['kind'] ) . '</td><td>' . esc_html( $row['status'] ) . '</td><td>' . esc_html( $row['note'] );
		if ( 'networks' === $row['desk'] ) {
			echo ' <a href="' . esc_url( adzworld_desk_url( 'networks' ) ) . '">' . esc_html__( 'Connections', 'adz-world' ) . '</a>';
		} elseif ( 'affiliates' === $row['desk'] ) {
			echo ' <a href="' . esc_url( adzworld_desk_url( 'affiliates' ) ) . '">' . esc_html__( 'Affiliate programs', 'adz-world' ) . '</a>';
		}
		echo '</td></tr>';
	}
	echo '</tbody></table>';
}

/**
 * Setup steps that do not require an outside account.
 *
 * @return void
 */
function adzworld_manual_render_setup() {
	$counts    = wp_count_posts( 'adz_ad' );
	$published = isset( $counts->publish ) ? (int) $counts->publish : 0;
	$drafts    = isset( $counts->draft ) ? (int) $counts->draft : 0;
	$ads       = array();
	if ( $published > 0 ) {
		$ads = get_posts(
			array(
				'numberposts'      => 12,
				'order'            => 'ASC',
				'orderby'          => 'title',
				'post_status'      => 'publish',
				'post_type'        => 'adz_ad',
				'suppress_filters' => false,
			)
		);
	}

	echo '<h2>' . esc_html__( 'Setup', 'adz-world' ) . '</h2>';
	echo '<p>' . esc_html__( 'This manual reads the accounts saved on this site. A network section stays on the account step until those identifiers are saved. The rotation steps for that account appear after that.', 'adz-world' ) . '</p>';

	adzworld_manual_open( $published > 0 ? 'is-done' : 'is-open', __( 'Publish a local ad', 'adz-world' ) );
	echo '<p>' . esc_html__( 'One ad is one thing. The title is that thing in ordinary words. The product image goes in the post body. The featured image is not read. A draft is ignored.', 'adz-world' ) . '</p>';
	if ( $published > 0 ) {
		echo '<ul>';
		foreach ( $ads as $ad ) {
			if ( $ad instanceof WP_Post ) {
				echo '<li>' . esc_html( get_the_title( $ad ) ) . '</li>';
			}
		}
		echo '</ul>';
	} else {
		echo '<p><a class="button" href="' . esc_url( admin_url( 'post-new.php?post_type=adz_ad' ) ) . '">' . esc_html__( 'Add a local ad', 'adz-world' ) . '</a></p>';
		if ( $drafts > 0 ) {
			echo '<p>' . esc_html__( 'Drafts exist. Publish one before it can occupy a slot.', 'adz-world' ) . '</p>';
		}
	}
	echo '</section>';

	$key = adzworld_manual_primary_rotation_key();
	adzworld_manual_open( '' !== $key ? 'is-done' : 'is-open', __( 'Save a rotation', 'adz-world' ) );
	echo '<p>' . esc_html__( 'The page names a rotation. The slot list stays in that saved rotation. Add the invitation hub and each published ad, then save. Post ids change from one site to the next, so a page that hard-codes them has to be rewritten on the next install.', 'adz-world' ) . '</p>';
	if ( '' !== $key && function_exists( 'adzworld_named_rotations' ) ) {
		echo '<ul>';
		foreach ( adzworld_named_rotations() as $rotation_key => $rotation ) {
			$slots = isset( $rotation['slots'] ) && is_array( $rotation['slots'] ) ? $rotation['slots'] : array();
			echo '<li><code>' . esc_html( (string) $rotation_key ) . '</code> — ' . esc_html( implode( ', ', $slots ) ) . '</li>';
		}
		echo '</ul>';
	}
	echo '<p><a class="button" href="' . esc_url( adzworld_desk_url( 'rotation' ) ) . '">' . esc_html__( 'Open Rotation', 'adz-world' ) . '</a></p>';
	echo '</section>';

	$pages = adzworld_manual_shortcode_posts();
	adzworld_manual_open( $pages ? 'is-done' : 'is-open', __( 'Place the shortcode', 'adz-world' ) );
	$example_name = '' !== $key ? $key : 'main';
	$display      = adzworld_manual_has_display( 'inline-card' ) ? 'inline-card' : 'flat';
	$column_size  = function_exists( 'adzworld_rotation_size_presets' ) && isset( adzworld_rotation_size_presets()['300x250'] ) ? '300x250' : '';
	echo '<p>' . esc_html__( 'For the article column:', 'adz-world' ) . ' <code>' . esc_html( adzworld_manual_shortcode( $example_name, $display, $column_size ) ) . '</code></p>';
	if ( '' === $key ) {
		echo '<p>' . esc_html__( 'The key main in that example is not saved yet. Save the rotation first, then use its key.', 'adz-world' ) . '</p>';
	}
	if ( adzworld_manual_has_display( 'flat' ) ) {
		echo '<p>' . esc_html__( 'For the left-to-right cycle, use display flat. For one drawn slot, use display single. controls both draws the arrows and the dots. Neither attribute chooses which ad is eligible.', 'adz-world' ) . '</p>';
	}
	if ( $pages ) {
		echo '<ul>';
		foreach ( $pages as $post ) {
			$markup = '';
			if ( 1 === preg_match( '/\[adz_rotation[^\]]*\]/', $post->post_content, $match ) ) {
				$markup = $match[0];
			}
			echo '<li>' . esc_html( get_the_title( $post ) );
			if ( '' !== $markup ) {
				echo ' <code>' . esc_html( $markup ) . '</code>';
			}
			echo '</li>';
		}
		echo '</ul>';
	}
	echo '</section>';

	adzworld_manual_open( 'is-open', __( 'What the visitor types', 'adz-world' ) );
	echo '<p>' . esc_html__( 'The hub question is “What are you looking to buy?”. Three words, 48 characters. An ad whose title, body, or Ad Type contains those words moves ahead. The hub stays first. Ads that do not match stay later. An ad that is not in the slot list never appears.', 'adz-world' ) . '</p>';
	echo '</section>';
}

/**
 * Direct network sections. Closed until that account is saved.
 *
 * @return void
 */
function adzworld_manual_render_networks() {
	if ( ! function_exists( 'adzworld_network_catalog' ) || ! function_exists( 'adzworld_network_settings' ) ) {
		return;
	}
	echo '<h2>' . esc_html__( 'Direct ad networks', 'adz-world' ) . '</h2>';
	$settings = adzworld_network_settings();
	foreach ( adzworld_network_catalog() as $id => $network ) {
		$row    = isset( $settings[ $id ] ) && is_array( $settings[ $id ] ) ? $settings[ $id ] : array();
		$phase  = adzworld_manual_account_phase( $row );
		$token  = 'source:network_' . $id;
		$used   = 'live' === $phase && adzworld_manual_token_used( $token );
		$class  = 'apply' === $phase ? 'is-waiting' : ( $used ? 'is-done' : 'is-open' );
		$labels = adzworld_manual_field_labels( isset( $network['fields'] ) && is_array( $network['fields'] ) ? $network['fields'] : array() );
		$signup = isset( $network['signup'] ) ? (string) $network['signup'] : '';
		adzworld_manual_open( $class, (string) $network['label'] );
		echo '<p>' . esc_html(
			sprintf(
				/* translators: 1: network name, 2: comma-separated field labels. */
				__( 'You need an account with %1$s. Input %2$s on Connections.', 'adz-world' ),
				(string) $network['label'],
				$labels
			)
		) . '</p>';
		echo '<p>';
		if ( '' !== $signup ) {
			echo '<a class="button" href="' . esc_url( $signup ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Create or open the account', 'adz-world' ) . '</a> ';
		}
		echo '<a class="button" href="' . esc_url( adzworld_desk_url( 'networks' ) ) . '">' . esc_html__( 'Open Connections', 'adz-world' ) . '</a></p>';
		if ( 'apply' === $phase ) {
			echo '</section>';
			continue;
		}
		if ( 'saved' === $phase ) {
			echo '<p>' . esc_html__( 'The identifiers are saved. Enable the connection. The rotation picker offers the provider slot after that.', 'adz-world' ) . '</p>';
		} else {
			echo '<p>' . esc_html__( 'The connection is enabled.', 'adz-world' ) . '</p>';
		}
		echo '<p>' . esc_html__( 'Add this slot on the Rotation screen:', 'adz-world' ) . ' <code>' . esc_html( $token ) . '</code></p>';
		if ( $used ) {
			echo '<p>' . esc_html__( 'A saved rotation or a page shortcode already contains that slot.', 'adz-world' ) . '</p>';
		}
		$frame   = adzworld_manual_network_frame( $id, $row );
		$example = adzworld_manual_shortcode( adzworld_manual_primary_rotation_key(), $frame['display'], $frame['size'] );
		echo '<p>' . esc_html__( 'Page shortcode for this unit:', 'adz-world' ) . ' <code>' . esc_html( $example ) . '</code></p>';
		if ( 'gam' === $id && '' !== $frame['size'] ) {
			echo '<p>' . esc_html(
				sprintf(
					/* translators: %s: size token such as 300x250. */
					__( 'The saved Ad Manager unit matches the size %s.', 'adz-world' ),
					$frame['size']
				)
			) . '</p>';
		} elseif ( 'gam' === $id ) {
			echo '<p>' . esc_html__( 'Leave size empty unless the saved width and height are one of the standard sizes. The unit keeps the dimensions saved on Connections.', 'adz-world' ) . '</p>';
		} else {
			echo '<p>' . esc_html__( 'Leave size empty. This unit is responsive and takes the width of its frame.', 'adz-world' ) . '</p>';
		}
		if ( adzworld_manual_has_display( 'dock-bottom' ) ) {
			echo '<p>' . esc_html__( 'For a phone, display dock-bottom is the dismissible bottom anchor. A sticky that covers about 30 percent of the screen is outside the Better Ads line.', 'adz-world' ) . '</p>';
		}
		echo '<p>' . esc_html__( 'The words a visitor types are not sent to this network.', 'adz-world' ) . '</p>';
		echo '</section>';
	}
}

/**
 * Affiliate sections. Closed until that account can emit its link.
 *
 * @return void
 */
function adzworld_manual_render_affiliates() {
	if ( ! function_exists( 'adzworld_affiliate_networks' ) || ! function_exists( 'adzworld_affiliate_accounts' ) ) {
		return;
	}
	echo '<h2>' . esc_html__( 'Affiliate programs', 'adz-world' ) . '</h2>';
	$accounts = adzworld_affiliate_accounts();
	foreach ( adzworld_affiliate_networks() as $id => $network ) {
		$account = isset( $accounts[ $id ] ) && is_array( $accounts[ $id ] ) ? $accounts[ $id ] : array();
		$phase   = adzworld_manual_affiliate_phase( (string) $id, $account );
		$class   = 'apply' === $phase ? 'is-waiting' : ( 'live' === $phase ? 'is-done' : 'is-open' );
		$labels  = adzworld_manual_field_labels( isset( $network['fields'] ) && is_array( $network['fields'] ) ? $network['fields'] : array() );
		$signup  = isset( $network['account'] ) ? (string) $network['account'] : '';
		adzworld_manual_open( $class, (string) $network['label'] );
		echo '<p>' . esc_html(
			sprintf(
				/* translators: 1: program name, 2: comma-separated field labels. */
				__( 'You need an account with %1$s. Input %2$s on Affiliate programs.', 'adz-world' ),
				(string) $network['label'],
				$labels
			)
		) . '</p>';
		echo '<p>';
		if ( '' !== $signup ) {
			echo '<a class="button" href="' . esc_url( $signup ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Create or open the account', 'adz-world' ) . '</a> ';
		}
		echo '<a class="button" href="' . esc_url( adzworld_desk_url( 'affiliates' ) ) . '">' . esc_html__( 'Open Affiliate programs', 'adz-world' ) . '</a></p>';
		if ( 'apply' === $phase ) {
			echo '</section>';
			continue;
		}
		if ( 'saved' === $phase ) {
			echo '<p>' . esc_html__( 'Some identifiers are saved. The link is not drawn until the account is complete and enabled.', 'adz-world' ) . '</p>';
		}
		if ( 'clickbank' === $id ) {
			echo '<p>' . esc_html__( 'The HopLink uses the saved account nickname and vendor nickname. The words a visitor types are not added to that URL.', 'adz-world' ) . '</p>';
		} else {
			echo '<p>' . esc_html__( 'A sponsored search link is drawn under a local ad that matches the words the visitor typed. The link is not a rotation slot. The matching ad has to be in the rotation.', 'adz-world' ) . '</p>';
		}
		echo '</section>';
	}
}

/**
 * Managed networks, plus any provider shortcode already saved.
 *
 * @return void
 */
function adzworld_manual_render_managed() {
	if ( ! function_exists( 'adzworld_managed_network_catalog' ) ) {
		return;
	}
	echo '<h2>' . esc_html__( 'Managed networks', 'adz-world' ) . '</h2>';
	echo '<p>' . esc_html__( 'These providers do not hand out a unit id this plugin can turn into a slot. Apply, then use the implementation that provider supplies.', 'adz-world' ) . '</p>';
	foreach ( adzworld_managed_network_catalog() as $network ) {
		$signup = isset( $network['signup'] ) ? (string) $network['signup'] : '';
		adzworld_manual_open( 'is-waiting', (string) $network['label'] );
		echo '<p>' . esc_html(
			sprintf(
				/* translators: 1: network name, 2: how that network is installed. */
				__( 'You need an account with %1$s. %2$s', 'adz-world' ),
				(string) $network['label'],
				isset( $network['method'] ) ? (string) $network['method'] : ''
			)
		) . '</p>';
		if ( '' !== $signup ) {
			echo '<p><a class="button" href="' . esc_url( $signup ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Create or open the account', 'adz-world' ) . '</a></p>';
		}
		echo '</section>';
	}

	if ( ! function_exists( 'adzworld_rotation_sources' ) || ! function_exists( 'adzworld_network_catalog' ) ) {
		return;
	}
	$custom = array();
	foreach ( adzworld_rotation_sources() as $key => $source ) {
		$network_id = is_string( $key ) ? substr( $key, 8 ) : '';
		if ( is_string( $key ) && 0 === strpos( $key, 'network_' ) && isset( adzworld_network_catalog()[ $network_id ] ) ) {
			continue;
		}
		if ( is_array( $source ) && isset( $source['label'] ) ) {
			$custom[ (string) $key ] = (string) $source['label'];
		}
	}
	if ( ! $custom ) {
		echo '<p>' . esc_html__( 'No provider shortcode is saved. When a managed network supplies one, save it on Connections and this section names the slot.', 'adz-world' ) . '</p>';
		return;
	}
	adzworld_manual_open( 'is-done', __( 'Saved provider shortcodes', 'adz-world' ) );
	echo '<ul>';
	foreach ( $custom as $key => $label ) {
		echo '<li>' . esc_html( $label ) . ' <code>' . esc_html( 'source:' . $key ) . '</code></li>';
	}
	echo '</ul></section>';
}

/**
 * Parameter reference for the rotation shortcode.
 *
 * @return void
 */
function adzworld_manual_render_parameters() {
	echo '<h2>' . esc_html__( 'Parameters', 'adz-world' ) . '</h2>';
	echo '<p>' . esc_html__( 'Content is the slot list. Frame is display, controls, size, and label. The visitor’s typed category is not a shortcode attribute.', 'adz-world' ) . '</p>';
	echo '<table class="adzworld-manual-table"><thead><tr><th>' . esc_html__( 'Attribute', 'adz-world' ) . '</th><th>' . esc_html__( 'Default', 'adz-world' ) . '</th><th>' . esc_html__( 'Job', 'adz-world' ) . '</th></tr></thead><tbody>';
	$rows = array(
		array( 'name', __( 'empty', 'adz-world' ), __( 'Replaces slots with the list stored for that key.', 'adz-world' ) ),
		array( 'slots', __( 'empty', 'adz-world' ), __( 'Comma-separated tokens. Required when name is empty. Maximum 200 slots.', 'adz-world' ) ),
		array( 'display', 'single', __( 'The method, and for several names the location.', 'adz-world' ) ),
		array( 'controls', 'both', __( 'arrows, dots, both, or none.', 'adz-world' ) ),
		array( 'size', __( 'empty', 'adz-world' ), __( 'A standard creative size. Empty means the frame is fluid.', 'adz-world' ) ),
		array( 'label', __( 'Advertising', 'adz-world' ), __( 'The accessible name of the region.', 'adz-world' ) ),
	);
	foreach ( $rows as $row ) {
		echo '<tr><td><code>' . esc_html( $row[0] ) . '</code></td><td><code>' . esc_html( $row[1] ) . '</code></td><td>' . esc_html( $row[2] ) . '</td></tr>';
	}
	echo '</tbody></table>';
	echo '<p>' . esc_html__( 'Slot tokens are intro, ad:id, ad:id:weight, source:key, and source:key:weight. Weight is 1 to 100 and is used only when display is single. On flat, left-to-right order is the list order and the weight is ignored. The invitation hub is rendered first.', 'adz-world' ) . '</p>';
	$displays = function_exists( 'adzworld_rotation_display_formats' ) ? implode( ', ', array_keys( adzworld_rotation_display_formats() ) ) : 'single, flat, 3d';
	$sizes    = function_exists( 'adzworld_rotation_size_presets' ) ? implode( ', ', array_keys( adzworld_rotation_size_presets() ) ) : '';
	echo '<p>' . esc_html__( 'Display names:', 'adz-world' ) . ' <code>' . esc_html( $displays ) . '</code></p>';
	if ( '' !== $sizes ) {
		echo '<p>' . esc_html__( 'Sizes:', 'adz-world' ) . ' <code>' . esc_html( $sizes ) . '</code></p>';
	}
	$tiers = array();
	foreach ( array( 'flat', 'single', 'inline-card', 'dock-bottom', 'sidebar', 'banner', 'fade', 'filmstrip', 'grid' ) as $tier_name ) {
		if ( adzworld_manual_has_display( $tier_name ) ) {
			$tiers[] = $tier_name;
		}
	}
	echo '<p>' . esc_html(
		sprintf(
			/* translators: %s: comma-separated display names accepted by this install. */
			__( 'Frames accepted here, in decision order: %s. 3d, coverflow, and stack do not change which ad is eligible.', 'adz-world' ),
			implode( ', ', $tiers )
		)
	) . '</p>';
	echo '<p>' . esc_html__( 'count, every, slides, panel, intro as an attribute, interval, carousel-flat, and carousel-3d are retired. The slot r:key is one saved rotation, one level deep, and that rotation cannot itself contain an r: slot. A second level fails closed.', 'adz-world' ) . '</p>';
}

/**
 * Register the manual chapters. They are not public.
 *
 * @return void
 */
function adzworld_manual_register_type() {
	register_post_type(
		'adz_manual',
		array(
			'labels'              => array(
				'name'          => __( 'Manual', 'adz-world' ),
				'singular_name' => __( 'Manual chapter', 'adz-world' ),
			),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => false,
			'show_in_rest'        => true,
			'exclude_from_search' => true,
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
			'supports'            => array( 'title', 'editor', 'excerpt', 'page-attributes' ),
			'rewrite'             => false,
		)
	);
}
add_action( 'init', 'adzworld_manual_register_type' );

/**
 * Stable chapters. Links do not repopulate.
 *
 * @return array<int,array{slug:string,title:string,excerpt:string,content:string}>
 */
function adzworld_manual_chapters() {
	return array(
		array(
			'slug'    => 'publish-a-local-ad',
			'title'   => __( 'Publish a local ad', 'adz-world' ),
			'excerpt' => __( 'One ad is one thing. The image goes in the post body.', 'adz-world' ),
			'content' => '<p>' . esc_html__( 'The title is the thing in ordinary words. The product image goes in the post body. The featured image is not read. A draft is ignored.', 'adz-world' ) . '</p><p><a href="' . esc_url( admin_url( 'post-new.php?post_type=adz_ad' ) ) . '">' . esc_html__( 'Add a local ad', 'adz-world' ) . '</a></p>',
		),
		array(
			'slug'    => 'save-a-rotation',
			'title'   => __( 'Save a rotation', 'adz-world' ),
			'excerpt' => __( 'The page names a rotation. The slot list stays on that rotation.', 'adz-world' ),
			'content' => '<p>' . esc_html__( 'A page should use the name attribute. Post ids change from one site to the next.', 'adz-world' ) . '</p><p><a href="' . esc_url( adzworld_desk_url( 'rotation' ) ) . '">' . esc_html__( 'Open Rotation', 'adz-world' ) . '</a></p>',
		),
		array(
			'slug'    => 'display-elements',
			'title'   => __( 'Display elements networks use', 'adz-world' ),
			'excerpt' => __( 'Manual AdSense units, anchors, and the formats that are outside the Better Ads Standard.', 'adz-world' ),
			'content' => '<p>' . esc_html__( 'A manual Google AdSense unit is a display unit, an in-feed unit, an in-article unit, or a multiplex grid. Those are the units that fit a widget or the article column. Anchor ads, vignette ads, and side rails are Auto ads. Google does not hand those out as a unit id.', 'adz-world' ) . '</p>'
				. '<p>' . esc_html__( 'The formats beneath the Better Ads Standard include pop-ups, auto-playing video with sound, prestitial ads with a countdown, postitial ads with a countdown, large sticky ads, and high ad density. A countdown taskbar is that prestitial or postitial experience. This plugin does not add one. The dismissible bottom dock is the bottom bar, without a countdown.', 'adz-world' ) . '</p>'
				. '<p>' . esc_html__( 'Static leaderboards are the weak format. In-article units, widgets, multiplex grids, and a small dismissible bottom bar are the ones still placed on purpose. Mid-roll ads inside short video are on the Better Ads list. Social-platform mid-roll is a different product and is not a slot here.', 'adz-world' ) . '</p>'
				. '<p><a href="https://support.google.com/adsense/answer/10734935" target="_blank" rel="noopener noreferrer">' . esc_html__( 'AdSense ad formats', 'adz-world' ) . '</a><br>'
				. '<a href="https://www.betterads.org/standards/" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Better Ads Standards', 'adz-world' ) . '</a></p>',
		),
		array(
			'slug'    => 'parameters',
			'title'   => __( 'Rotation parameters', 'adz-world' ),
			'excerpt' => __( 'Six attributes. The visitor’s words are not one of them.', 'adz-world' ),
			'content' => '<p>' . esc_html__( 'name, slots, display, controls, size, and label. Slot tokens are intro, ad:id, source:key, and r:key. Weight is used only when display is single. r:key is one saved rotation and cannot contain another r: slot.', 'adz-world' ) . '</p>',
		),
	);
}

/**
 * Insert a missing chapter. An existing chapter is left as the administrator saved it.
 *
 * @return void
 */
function adzworld_manual_seed_chapters() {
	if ( ! post_type_exists( 'adz_manual' ) || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	foreach ( adzworld_manual_chapters() as $index => $chapter ) {
		$found = get_posts(
			array(
				'name'           => $chapter['slug'],
				'post_status'    => 'any',
				'post_type'      => 'adz_manual',
				'posts_per_page' => 1,
			)
		);
		if ( $found ) {
			continue;
		}
		wp_insert_post(
			array(
				'post_type'    => 'adz_manual',
				'post_status'  => 'private',
				'post_title'   => $chapter['title'],
				'post_name'    => $chapter['slug'],
				'post_excerpt' => $chapter['excerpt'],
				'post_content' => $chapter['content'],
				'menu_order'   => $index + 1,
			)
		);
	}
}
add_action( 'admin_init', 'adzworld_manual_seed_chapters' );

/**
 * Chapter list.
 *
 * @return void
 */
function adzworld_manual_render_chapters() {
	$posts = get_posts(
		array(
			'order'          => 'ASC',
			'orderby'        => 'menu_order',
			'post_status'    => array( 'private', 'publish', 'draft' ),
			'post_type'      => 'adz_manual',
			'posts_per_page' => 20,
		)
	);
	echo '<h2>' . esc_html__( 'Chapters', 'adz-world' ) . '</h2>';
	echo '<p>' . esc_html__( 'These chapters are links and explanations. They do not rebuild themselves.', 'adz-world' ) . '</p><ul class="adzworld-manual-chapters">';
	foreach ( $posts as $post ) {
		if ( ! $post instanceof WP_Post ) {
			continue;
		}
		echo '<li><a href="' . esc_url( (string) get_edit_post_link( $post ) ) . '">' . esc_html( get_the_title( $post ) ) . '</a>';
		if ( '' !== $post->post_excerpt ) {
			echo ' — ' . esc_html( $post->post_excerpt );
		}
		echo '</li>';
	}
	echo '</ul>';
}

/**
 * Save the published local ads as the rotation key local-offers when it is missing.
 *
 * @return string Rotation key, or an empty string when no ad is published.
 */
function adzworld_manual_ensure_local_offers() {
	$existing = function_exists( 'adzworld_named_rotations' ) ? adzworld_named_rotations() : array();
	if ( isset( $existing['local-offers'] ) ) {
		return 'local-offers';
	}
	$ads = get_posts(
		array(
			'fields'           => 'ids',
			'numberposts'      => 50,
			'order'            => 'ASC',
			'orderby'          => 'title',
			'post_status'      => 'publish',
			'post_type'        => 'adz_ad',
			'suppress_filters' => false,
		)
	);
	if ( ! $ads ) {
		return '';
	}
	$slots = array();
	foreach ( $ads as $ad_id ) {
		$slots[] = 'ad:' . (int) $ad_id;
	}
	$stored = get_option( 'adzworld_named_rotations', array() );
	if ( ! is_array( $stored ) ) {
		$stored = array();
	}
	$stored['local-offers'] = array(
		'name'  => __( 'Local offers', 'adz-world' ),
		'slots' => $slots,
	);
	update_option( 'adzworld_named_rotations', $stored, false );
	return 'local-offers';
}

/**
 * Display names an administrator can apply to the pattern, recommended first.
 *
 * @return array<string,string>
 */
function adzworld_manual_display_choices() {
	$recommended = array( 'inline-card', 'dock-bottom', 'sidebar', 'banner', 'grid', 'filmstrip', 'flat', 'single' );
	$available   = array();
	if ( function_exists( 'adzworld_rotation_display_formats' ) ) {
		foreach ( adzworld_rotation_display_formats() as $name => $meta ) {
			$available[ $name ] = isset( $meta['label'] ) ? (string) $meta['label'] : (string) $name;
		}
	} else {
		$available = array(
			'flat'   => __( 'Flat carousel', 'adz-world' ),
			'single' => __( 'Single', 'adz-world' ),
			'3d'     => __( '3D carousel', 'adz-world' ),
		);
	}
	$ordered = array();
	foreach ( $recommended as $name ) {
		if ( isset( $available[ $name ] ) ) {
			$ordered[ $name ] = $available[ $name ];
		}
	}
	foreach ( $available as $name => $label ) {
		if ( ! isset( $ordered[ $name ] ) ) {
			$ordered[ $name ] = $label;
		}
	}
	return $ordered;
}

/**
 * Build the alternating shortcode from the builder fields.
 *
 * @param string $display  Display name.
 * @param string $source   Source token, including the source: prefix.
 * @param string $rotation Rotation key.
 * @param int    $repeats  How many pairs.
 * @return string|WP_Error
 */
function adzworld_manual_pattern_shortcode( $display, $source, $rotation, $repeats ) {
	$choices = adzworld_manual_display_choices();
	if ( ! isset( $choices[ $display ] ) ) {
		return new WP_Error( 'adzworld_manual_display', __( 'That display is not available.', 'adz-world' ) );
	}
	if ( ! is_string( $source ) || ! preg_match( '/^source:network_[a-z0-9_-]{1,40}$/D', $source ) ) {
		return new WP_Error( 'adzworld_manual_source', __( 'Choose a provider unit.', 'adz-world' ) );
	}
	$rotation = sanitize_key( $rotation );
	$named    = function_exists( 'adzworld_named_rotations' ) ? adzworld_named_rotations() : array();
	if ( '' === $rotation || ! isset( $named[ $rotation ] ) ) {
		return new WP_Error( 'adzworld_manual_rotation', __( 'Choose a saved rotation.', 'adz-world' ) );
	}
	$repeats = max( 1, min( 8, (int) $repeats ) );
	$parts   = array();
	for ( $pair = 0; $pair < $repeats; $pair++ ) {
		$parts[] = $source;
		$parts[] = 'r:' . $rotation;
	}
	return '[adz_rotation slots="' . implode( ',', $parts ) . '" display="' . $display . '" controls="both"]';
}

/**
 * Enqueue the rotation preview on the manual tab.
 *
 * @return void
 */
function adzworld_manual_assets() {
	if ( ! function_exists( 'adzworld_desk_tab' ) || 'manual' !== adzworld_desk_tab() ) {
		return;
	}
	if ( function_exists( 'adzworld_enqueue_rotation_assets' ) ) {
		adzworld_enqueue_rotation_assets();
	}
}
add_action( 'admin_enqueue_scripts', 'adzworld_manual_assets' );

/**
 * Store the builder fields and return to the manual.
 *
 * @return void
 */
function adzworld_manual_save_build() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Administrator permission is required.', 'adz-world' ), '', array( 'response' => 403 ) );
	}
	check_admin_referer( 'adzworld_manual_build' );
	$display  = isset( $_POST['display'] ) && is_string( $_POST['display'] ) ? sanitize_key( wp_unslash( $_POST['display'] ) ) : '';
	$source   = isset( $_POST['source'] ) && is_string( $_POST['source'] ) ? sanitize_text_field( wp_unslash( $_POST['source'] ) ) : '';
	$rotation = isset( $_POST['rotation'] ) && is_string( $_POST['rotation'] ) ? sanitize_key( wp_unslash( $_POST['rotation'] ) ) : '';
	$repeats  = isset( $_POST['repeats'] ) ? (int) $_POST['repeats'] : 5;
	if ( '' === $rotation ) {
		$rotation = adzworld_manual_ensure_local_offers();
	}
	set_transient(
		'adzworld_manual_build_' . get_current_user_id(),
		array(
			'display'  => $display,
			'source'   => $source,
			'rotation' => $rotation,
			'repeats'  => max( 1, min( 8, $repeats ) ),
		),
		HOUR_IN_SECONDS
	);
	wp_safe_redirect( adzworld_desk_url( 'manual' ) );
	exit;
}
add_action( 'admin_post_adzworld_manual_build', 'adzworld_manual_save_build' );

/**
 * Builder: one provider unit alternating with one saved rotation.
 *
 * @return void
 */
function adzworld_manual_render_builder() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$displays = adzworld_manual_display_choices();
	$saved    = get_transient( 'adzworld_manual_build_' . get_current_user_id() );
	$saved    = is_array( $saved ) ? $saved : array();
	$display  = isset( $saved['display'] ) && is_string( $saved['display'] ) ? $saved['display'] : '';
	if ( ! isset( $displays[ $display ] ) ) {
		$display = isset( $displays['inline-card'] ) ? 'inline-card' : (string) array_key_first( $displays );
	}
	$source   = isset( $saved['source'] ) && is_string( $saved['source'] ) ? $saved['source'] : 'source:network_adsense';
	$rotation = isset( $saved['rotation'] ) && is_string( $saved['rotation'] ) ? $saved['rotation'] : '';
	$repeats  = isset( $saved['repeats'] ) ? (int) $saved['repeats'] : 5;
	if ( '' === $rotation ) {
		$rotation = adzworld_manual_ensure_local_offers();
	}
	$named = function_exists( 'adzworld_named_rotations' ) ? adzworld_named_rotations() : array();
	if ( ! isset( $named[ $rotation ] ) ) {
		$rotation = (string) array_key_first( $named );
	}
	echo '<section class="adzworld-manual-builder" id="adzworld-build">';
	echo '<h2>' . esc_html__( 'Build a display', 'adz-world' ) . '</h2>';
	echo '<p>' . esc_html__( 'Pick the display. Slot 1 is the provider unit. Slot 2 is a saved rotation of local offers. The pattern repeats 1, 2, 1, 2. The shortcode and the preview below are that pattern.', 'adz-world' ) . '</p>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="adzworld_manual_build">';
	wp_nonce_field( 'adzworld_manual_build' );
	echo '<table class="form-table"><tbody>';
	echo '<tr><th scope="row"><label for="adzworld-build-display">' . esc_html__( 'Display', 'adz-world' ) . '</label></th><td><select id="adzworld-build-display" name="display">';
	foreach ( $displays as $name => $label ) {
		echo '<option value="' . esc_attr( $name ) . '"' . selected( $display, $name, false ) . '>' . esc_html( $label ) . '</option>';
	}
	echo '</select></td></tr>';
	echo '<tr><th scope="row"><label for="adzworld-build-source">' . esc_html__( 'Slot 1, provider unit', 'adz-world' ) . '</label></th><td><select id="adzworld-build-source" name="source">';
	if ( function_exists( 'adzworld_network_catalog' ) && function_exists( 'adzworld_network_settings' ) ) {
		$settings = adzworld_network_settings();
		foreach ( adzworld_network_catalog() as $id => $network ) {
			$token = 'source:network_' . $id;
			$row   = isset( $settings[ $id ] ) && is_array( $settings[ $id ] ) ? $settings[ $id ] : array();
			$phase = adzworld_manual_phase_label( adzworld_manual_account_phase( $row ) );
			echo '<option value="' . esc_attr( $token ) . '"' . selected( $source, $token, false ) . '>' . esc_html( $network['label'] . ' — ' . $phase ) . '</option>';
		}
	}
	echo '</select></td></tr>';
	echo '<tr><th scope="row"><label for="adzworld-build-rotation">' . esc_html__( 'Slot 2, saved rotation', 'adz-world' ) . '</label></th><td><select id="adzworld-build-rotation" name="rotation">';
	foreach ( $named as $key => $item ) {
		$label = isset( $item['name'] ) && is_string( $item['name'] ) && '' !== $item['name'] ? $item['name'] : (string) $key;
		echo '<option value="' . esc_attr( (string) $key ) . '"' . selected( $rotation, (string) $key, false ) . '>' . esc_html( $label ) . '</option>';
	}
	echo '</select></td></tr>';
	echo '<tr><th scope="row"><label for="adzworld-build-repeats">' . esc_html__( 'Pairs', 'adz-world' ) . '</label></th><td><input id="adzworld-build-repeats" name="repeats" type="number" min="1" max="8" value="' . esc_attr( (string) max( 1, min( 8, $repeats ) ) ) . '"></td></tr>';
	echo '</tbody></table>';
	submit_button( __( 'Update the shortcode and preview', 'adz-world' ) );
	echo '</form>';
	$shortcode = adzworld_manual_pattern_shortcode( $display, $source, $rotation, $repeats );
	if ( is_wp_error( $shortcode ) ) {
		echo '<p class="adzworld-rotation-error" role="alert">' . esc_html( $shortcode->get_error_message() ) . '</p></section>';
		return;
	}
	echo '<h3>' . esc_html__( 'Shortcode', 'adz-world' ) . '</h3>';
	echo '<code class="adzworld-manual-shortcode">' . esc_html( $shortcode ) . '</code>';
	echo '<p>' . esc_html__( 'A public page shows the provider unit after that account is connected. Until then, this preview names the unit and the saved rotation still plays.', 'adz-world' ) . '</p>';
	echo '<h3>' . esc_html__( 'Preview', 'adz-world' ) . '</h3><div class="adzworld-manual-preview">';
	$GLOBALS['adzworld_manual_placeholder'] = true;
	echo do_shortcode( $shortcode );
	unset( $GLOBALS['adzworld_manual_placeholder'] );
	echo '</div></section>';
}

/**
 * Render the active setup manual.
 *
 * @return void
 */
function adzworld_manual_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Administrator permission is required.', 'adz-world' ), '', array( 'response' => 403 ) );
	}
	echo '<div class="adzworld-manual">';
	adzworld_manual_render_builder();
	adzworld_manual_render_chapters();
	adzworld_manual_render_providers();
	adzworld_manual_render_setup();
	adzworld_manual_render_networks();
	adzworld_manual_render_affiliates();
	adzworld_manual_render_managed();
	adzworld_manual_render_parameters();
	echo '</div>';
}
