<?php
/**
 * Get Paid: one card per provider, and the card establishes the connection.
 *
 * Every provider a site owner can earn from appears here once, whether it is
 * an advertising network, an affiliate programme or anything else. The old
 * split put the same seven affiliate networks on the screen twice -- once as a
 * registration shortcut and once as a configuration form -- and put AdSense's
 * registration steps on one tab and its fields on another. A provider is one
 * thing, so it gets one card.
 *
 * Providers are grouped by the only distinction that changes what a reader
 * sees: whether the words the reader typed actually reach the provider.
 *
 *   serves adz   the reader's own request is sent, so what comes back answers
 *                it. This is perbeya.
 *   serves sads  the provider is handed a slot and decides for itself from
 *                its own data. We place it; we do not steer it.
 *
 * @package adz-world
 */

defined( 'ABSPATH' ) || exit;

/**
 * Which affiliate providers are sent the reader's request.
 *
 * The authority for this is adzworld_affiliate_url() in
 * includes/adzworld-affiliates.php: a provider serves adz when that function
 * interpolates $request into the URL it returns. ClickBank deliberately does
 * not -- it is a marketplace of single products rather than a searchable
 * catalogue, and nothing derived from the reader is appended to a HopLink.
 *
 * Kept as data rather than inferred, so a reviewer can check it against that
 * function by reading two lists.
 *
 * @return array<string,bool> Affiliate id => whether the request is sent.
 */
function adzworld_getpaid_affiliate_serves_adz() {
	$providers = array(
		'amazon'    => true,
		'walmart'   => true,
		'awin'      => true,
		'cj'        => true,
		'rakuten'   => true,
		'ebay'      => true,
		'etsy'      => true,
		'clickbank' => false,
	);

	/**
	 * Filters whether affiliate providers receive the reader's request.
	 *
	 * @param array<string,bool> $providers Provider id and request behavior.
	 */
	return apply_filters( 'adzworld_getpaid_affiliate_serves_adz', $providers );
}

/**
 * Every provider, normalised into one shape.
 *
 * @return array<string,array<string,mixed>> Provider key => provider.
 */
function adzworld_getpaid_providers() {
	$registrations = function_exists( 'adzworld_registration_catalog' ) ? adzworld_registration_catalog() : array();
	$serves_adz    = adzworld_getpaid_affiliate_serves_adz();
	$providers     = array();

	foreach ( adzworld_affiliate_networks() as $id => $network ) {
		$registration = isset( $registrations[ $id ] ) ? $registrations[ $id ] : array();

		$providers[ 'affiliate:' . $id ] = array(
			'id'           => $id,
			'source'       => 'affiliate',
			'label'        => $network['label'],
			'serves'       => ! empty( $serves_adz[ $id ] ) ? 'adz' : 'sads',
			'note'         => isset( $network['note'] ) ? $network['note'] : '',
			'signup'       => isset( $registration['signup'] ) ? $registration['signup'] : ( isset( $network['account'] ) ? $network['account'] : '' ),
			'signup_label' => isset( $registration['signup_label'] ) ? $registration['signup_label'] : '',
			'pays'         => isset( $registration['pays'] ) ? $registration['pays'] : '',
			'steps'        => isset( $registration['steps'] ) ? $registration['steps'] : array(),
			'fields'       => $network['fields'],
		);
	}

	foreach ( adzworld_network_catalog() as $preset => $network ) {
		$registration = isset( $registrations[ $preset ] ) ? $registrations[ $preset ] : array();

		$providers[ 'network:' . $preset ] = array(
			'id'           => $preset,
			'source'       => 'network',
			'label'        => $network['label'],

			/*
			 * No listed advertising network accepts a search string. Each is
			 * handed a slot and a publisher identifier and decides for itself.
			 */
			'serves'       => 'sads',
			'note'         => isset( $network['note'] ) ? $network['note'] : '',
			'signup'       => isset( $registration['signup'] ) ? $registration['signup'] : ( isset( $network['signup'] ) ? $network['signup'] : '' ),
			'signup_label' => isset( $registration['signup_label'] ) ? $registration['signup_label'] : '',
			'pays'         => isset( $registration['pays'] ) ? $registration['pays'] : '',
			'steps'        => isset( $registration['steps'] ) ? $registration['steps'] : array(),
			'fields'       => isset( $network['fields'] ) ? $network['fields'] : array(),
			'host'         => isset( $network['host'] ) ? $network['host'] : '',
			'terms'        => isset( $network['terms'] ) ? $network['terms'] : '',
			'privacy'      => isset( $network['privacy'] ) ? $network['privacy'] : '',
		);
	}

	return $providers;
}

/**
 * Whether a provider is currently connected and paying.
 *
 * @param array<string,mixed> $provider Provider from adzworld_getpaid_providers().
 * @return bool Whether it is connected.
 */
function adzworld_getpaid_is_connected( $provider ) {
	if ( 'affiliate' === $provider['source'] ) {
		$accounts = adzworld_affiliate_accounts();
		return isset( $accounts[ $provider['id'] ] ) && adzworld_affiliate_account_ready( $provider['id'], $accounts[ $provider['id'] ] );
	}

	foreach ( adzworld_network_rows() as $row ) {
		if ( $provider['id'] === $row['preset'] && '1' === $row['enabled'] ) {
			return true;
		}
	}

	return false;
}

/**
 * The registration shortcut and procedure for one provider.
 *
 * @param array<string,mixed> $provider Provider.
 * @return void
 */
function adzworld_getpaid_registration( $provider ) {
	if ( '' !== $provider['pays'] ) {
		echo '<p class="description adzworld-paid-pays">' . esc_html( $provider['pays'] ) . '</p>';
	}

	if ( '' !== $provider['signup'] ) {
		$label = '' !== $provider['signup_label']
			? $provider['signup_label']
			/* translators: %s: provider name. */
			: sprintf( __( 'Register at %s', 'adz-world' ), $provider['label'] );

		echo '<p><a class="button button-secondary" href="' . esc_url( $provider['signup'] ) . '" target="_blank" rel="noopener noreferrer">'
			. esc_html( $label )
			. '<span class="screen-reader-text">' . esc_html__( ' (opens in a new tab)', 'adz-world' ) . '</span></a></p>';
	}

	if ( ! empty( $provider['steps'] ) ) {
		echo '<details class="adzworld-paid-steps"><summary>' . esc_html__( 'How to register', 'adz-world' ) . '</summary><ol>';
		foreach ( $provider['steps'] as $step ) {
			echo '<li>' . esc_html( $step ) . '</li>';
		}
		echo '</ol></details>';
	}
}

/**
 * The header every provider card carries.
 *
 * @param array<string,mixed> $provider Provider.
 * @param bool                $connected Whether it is connected.
 * @param bool                $earning   Whether it can pay the publisher.
 * @return void
 */
function adzworld_getpaid_card_header( $provider, $connected, $earning = false ) {
	$serves = 'adz' === $provider['serves']
		? __( 'Sends the reader’s request', 'adz-world' )
		: __( 'Placement only', 'adz-world' );

	echo '<div class="postbox-header"><h2 class="hndle">';
	echo '<span class="adzworld-paid-name">' . esc_html( $provider['label'] ) . '</span>';
	echo '<span class="adzworld-paid-serves adzworld-paid-serves--' . esc_attr( $provider['serves'] ) . '">' . esc_html( $serves ) . '</span>';
	echo '</h2></div>';
	echo '<div class="inside">';

	if ( ! $connected ) {
		$tone  = 'info';
		$state = __( 'Not connected yet', 'adz-world' );
	} elseif ( ! $earning ) {
		$tone  = 'warning';
		$state = __( 'Serving — not earning yet', 'adz-world' );
	} else {
		$tone  = 'success';
		$state = __( 'Connected and earning', 'adz-world' );
	}

	printf(
		'<p class="adzworld-paid-state"><span class="notice notice-%1$s inline"><span>%2$s</span></span></p>',
		esc_attr( $tone ),
		esc_html( $state )
	);

	if ( '' !== $provider['note'] ) {
		echo '<p class="description">' . esc_html( $provider['note'] ) . '</p>';
	}
}

/**
 * One affiliate provider card, including the identifiers that connect it.
 *
 * @param array<string,mixed>  $provider Provider.
 * @param array<string,string> $account  Saved account for it.
 * @return void
 */
function adzworld_getpaid_affiliate_card( $provider, $account ) {
	$id      = $provider['id'];
	$enabled = isset( $account['enabled'] ) ? (string) $account['enabled'] : '0';

	echo '<div class="postbox adzworld-paid-card" id="adzworld-paid-affiliate-' . esc_attr( $id ) . '">';
	adzworld_getpaid_card_header( $provider, '1' === $enabled, adzworld_getpaid_account_earns( $id, $account ) );
	adzworld_getpaid_registration( $provider );

	echo '<table class="form-table" role="presentation"><tbody>';
	foreach ( $provider['fields'] as $field => $rule ) {
		$value = isset( $account[ $field ] ) ? $account[ $field ] : '';
		$input = 'adzworld_affiliate_' . $id . '_' . $field;

		echo '<tr><th scope="row"><label for="' . esc_attr( $input ) . '">' . esc_html( $rule['label'] ) . '</label></th><td>';
		if ( isset( $rule['choices'] ) ) {
			echo '<select id="' . esc_attr( $input ) . '" name="' . esc_attr( $input ) . '">';
			echo '<option value="">' . esc_html__( 'Not connected', 'adz-world' ) . '</option>';
			foreach ( $rule['choices'] as $choice ) {
				echo '<option value="' . esc_attr( $choice ) . '"' . selected( $value, $choice, false ) . '>' . esc_html( $choice ) . '</option>';
			}
			echo '</select>';
		} elseif ( ! empty( $rule['secret'] ) ) {
			$saved = '' !== $value;
			echo '<input type="password" class="regular-text code" id="' . esc_attr( $input ) . '" name="' . esc_attr( $input ) . '" value=""'
				. ( $saved ? ' placeholder="' . esc_attr( str_repeat( '•', 24 ) ) . '"' : '' )
				. ' autocomplete="off">';
			if ( $saved ) {
				echo '<span class="adzworld-paid-hint">' . esc_html__( 'Saved. Leave blank to keep it, or paste a new one to replace it.', 'adz-world' ) . '</span>';
			}
		} else {
			echo '<input type="text" class="regular-text code" id="' . esc_attr( $input ) . '" name="' . esc_attr( $input ) . '" value="' . esc_attr( $value ) . '">';
		}
		echo '</td></tr>';
	}

	echo '<tr><th scope="row">' . esc_html__( 'Connection', 'adz-world' ) . '</th><td><label>';
	echo '<input type="checkbox" name="' . esc_attr( 'adzworld_affiliate_' . $id . '_enabled' ) . '" value="1"' . checked( $enabled, '1', false ) . '> ';
	echo esc_html__( 'Active', 'adz-world' ) . '</label>';
	echo '<span class="adzworld-paid-hint">' . esc_html__( 'Clear this to pause the account without losing what you pasted.', 'adz-world' ) . '</span></td></tr>';
	echo '</tbody></table>';

	echo '<p class="adzworld-paid-save">';
	submit_button( __( 'Save', 'adz-world' ), 'primary', 'submit', false );
	echo '</p>';

	echo '</div></div>';
}

/**
 * Whether a saved account can actually pay the publisher.
 *
 * Serving a product and earning from it are two different credentials. Every
 * fetcher here deliberately serves on the developer credential alone, so that
 * an application pending at a network does not leave a slot empty. That is a
 * stopgap, not the goal: a slot that serves without earning hands a reader to
 * a merchant for nothing, which is worse for a publisher than showing no
 * adzad at all. A provider answers here for its own saved fields.
 *
 * @param string               $id      Provider id.
 * @param array<string,string> $account Saved provider fields.
 * @return bool Whether a click on this provider's adz is credited.
 */
function adzworld_getpaid_account_earns( $id, $account ) {
	/**
	 * Whether a provider's saved account is credited for a sale.
	 *
	 * A provider file returns true only when the identifier its network pays
	 * against is present. The default is false, so a provider that says
	 * nothing is reported as not earning rather than assumed to be.
	 *
	 * @param bool                 $earns   Whether the account earns.
	 * @param string               $id      Provider id.
	 * @param array<string,string> $account Saved provider fields.
	 */
	return (bool) apply_filters( 'adzworld_affiliate_account_earns', false, $id, $account );
}

/**
 * One advertising-network card, holding every unit configured for that network.
 *
 * A publisher may run more than one unit from the same network, so the card
 * holds each saved row rather than assuming one. The row markup is the
 * existing adzworld_network_render_row(), so the fields, validation and saved
 * values are unchanged by this screen.
 *
 * @param array<string,mixed>            $provider Provider.
 * @param array<int,array<string,mixed>> $rows     Rows for this preset.
 * @return void
 */
function adzworld_getpaid_network_card( $provider, $rows ) {
	$connected = false;
	foreach ( $rows as $row ) {
		if ( '1' === $row['enabled'] ) {
			$connected = true;
			break;
		}
	}

	echo '<div class="postbox adzworld-paid-card" id="adzworld-paid-network-' . esc_attr( $provider['id'] ) . '">';
	adzworld_getpaid_card_header( $provider, $connected, $connected );
	adzworld_getpaid_registration( $provider );

	if ( '' !== $provider['host'] ) {
		echo '<p class="description">';
		echo esc_html__( 'Loads its script from', 'adz-world' ) . ' <code>' . esc_html( $provider['host'] ) . '</code> ';
		echo esc_html__( 'in the reader’s browser.', 'adz-world' );
		echo '</p>';
	}

	if ( empty( $rows ) ) {
		echo '<p class="description">' . esc_html__( 'No unit configured yet. Add one below, then paste the identifiers this network gave you.', 'adz-world' ) . '</p>';
		echo '<p><button type="submit" class="button button-secondary" name="adzworld_add_network" value="' . esc_attr( $provider['id'] ) . '" formnovalidate>'
			. esc_html__( 'Add a unit', 'adz-world' ) . '</button>';
		echo '</p>';
	} else {
		foreach ( $rows as $row ) {
			adzworld_network_render_row( $row );
		}
	}

	echo '</div></div>';
}

/**
 * Render the Get Paid screen.
 *
 * Two sections, because the two kinds of provider are saved by two different
 * handlers and each needs its own form. Every card states whether it sends the
 * reader's request, so that distinction stays visible inside both sections.
 *
 * @return void
 */
function adzworld_getpaid_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Administrator permission is required.', 'adz-world' ), '', array( 'response' => 403 ) );
	}

	$providers = adzworld_getpaid_providers();
	usort(
		$providers,
		static function ( $first, $second ) {
			return strcasecmp( $first['label'], $second['label'] );
		}
	);
	$serving   = 0;
	$connected = 0;
	foreach ( $providers as $provider ) {
		if ( 'adz' === $provider['serves'] ) {
			++$serving;
		}
		if ( adzworld_getpaid_is_connected( $provider ) ) {
			++$connected;
		}
	}

	echo '<h1>' . esc_html__( 'Get Paid', 'adz-world' ) . '</h1>';
	echo '<p class="description">' . esc_html__( 'Every provider this site can earn from, one card each. Register with the provider, paste the identifiers it gives you, and switch the connection on. A provider pays this site only once its card says it does.', 'adz-world' ) . '</p>';

	echo '<p class="adzworld-paid-summary">';
	printf(
		/* translators: 1: connected count, 2: total providers, 3: count that accept a request. */
		esc_html__( '%1$d of %2$d providers connected. %3$d of them are sent the reader’s own request; the rest are handed a slot and decide for themselves.', 'adz-world' ),
		absint( $connected ),
		absint( count( $providers ) ),
		absint( $serving )
	);
	echo '</p>';

	$accounts = adzworld_affiliate_accounts();
	$notice   = 'adzworld_affiliate_saved_' . get_current_user_id();
	if ( get_transient( $notice ) ) {
		delete_transient( $notice );
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Affiliate connections saved.', 'adz-world' ) . '</p></div>';
	}

	echo '<h2 class="adzworld-paid-group">' . esc_html__( 'Affiliate programmes', 'adz-world' ) . '</h2>';
	echo '<p class="description">' . esc_html__( 'These pay a commission when a reader buys. Most are sent the reader’s own request, so what they return answers what the reader asked for.', 'adz-world' ) . '</p>';

	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="adzworld_save_affiliates">';
	wp_nonce_field( 'adzworld_affiliate_accounts', 'adzworld_affiliate_nonce' );
	echo '<div class="adzworld-paid-actions">';
	submit_button( __( 'Save affiliate connections', 'adz-world' ) );
	echo '</div>';
	echo '<div class="adzworld-paid-grid">';
	foreach ( $providers as $provider ) {
		if ( 'affiliate' !== $provider['source'] ) {
			continue;
		}
		$account = isset( $accounts[ $provider['id'] ] ) ? $accounts[ $provider['id'] ] : array();
		adzworld_getpaid_affiliate_card( $provider, $account );
	}
	echo '</div>';
	submit_button( __( 'Save affiliate connections', 'adz-world' ) );
	echo '</form>';

	echo '<h2 class="adzworld-paid-group">' . esc_html__( 'Advertising networks', 'adz-world' ) . '</h2>';
	echo '<p class="description">' . esc_html__( 'These pay for the placement itself. None of them is sent the reader’s request: each is handed a slot and a publisher identifier and decides what to show from its own data. The unit renders exactly as the network built it.', 'adz-world' ) . '</p>';

	$rows_by_preset = array();
	foreach ( adzworld_network_rows() as $row ) {
		$rows_by_preset[ $row['preset'] ][] = $row;
	}

	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="adzworld_save_networks">';
	wp_nonce_field( 'adzworld_save_networks', 'adzworld_network_nonce' );
	echo '<div class="adzworld-paid-actions">';
	submit_button( __( 'Save network connections', 'adz-world' ) );
	echo '</div>';
	echo '<div class="adzworld-paid-grid">';
	foreach ( $providers as $provider ) {
		if ( 'network' !== $provider['source'] ) {
			continue;
		}
		$rows = isset( $rows_by_preset[ $provider['id'] ] ) ? $rows_by_preset[ $provider['id'] ] : array();
		adzworld_getpaid_network_card( $provider, $rows );
	}
	echo '</div>';
	submit_button( __( 'Save network connections', 'adz-world' ) );
	echo '</form>';
}
