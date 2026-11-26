<?php
/**
 * Administrator-authored messages for the Hub and adz panels.
 *
 * @package adz-world
 */

defined( 'ABSPATH' ) || exit;

/**
 * Audience labels used by the Display editor.
 *
 * @return array
 */
function adzworld_audience_labels() {
	return array(
		'visitor' => __( 'Visitors', 'adz-world' ),
		'guest'   => __( 'Guests', 'adz-world' ),
		'member'  => __( 'Members', 'adz-world' ),
	);
}

/**
 * Available placements for a message.
 *
 * @return array
 */
function adzworld_audience_placements() {
	return array(
		'hub'  => __( 'Hub only', 'adz-world' ),
		'adz'  => __( 'Adz only', 'adz-world' ),
		'both' => __( 'Hub or adz', 'adz-world' ),
	);
}

/**
 * Formatting allowed in an audience message.
 *
 * @return array
 */
function adzworld_audience_message_html() {
	return array(
		'a'          => array( 'href' => true ),
		'strong'     => array(),
		'em'         => array(),
		'p'          => array(),
		'br'         => array(),
		'ul'         => array(),
		'ol'         => array(),
		'li'         => array(),
		'blockquote' => array(),
	);
}

/**
 * Saved audience messages, or the built-in starting lists.
 *
 * @return array
 */
function adzworld_audience_message_settings() {
	$saved = get_option( 'adzworld_audience_messages', null );
	if ( is_array( $saved ) ) {
		return $saved;
	}

	return array(
		'order'    => 'ordered',
		'messages' => array(
			'visitor' => array(
				array(
					'placement' => 'both',
					'text'      => __( "Seeing advertisements you don't like? {signIn} so we can save your adz preferences and serve you offers that match your desires!", 'adz-world' ),
				),
				array(
					'placement' => 'both',
					'text'      => __( 'Seeing irrelevant ads? {signIn} so we can serve you adz that match your personal preferences!', 'adz-world' ),
				),
			),
			'guest'   => array(),
			'member'  => array(
				array(
					'placement' => 'both',
					'text'      => __( "As a Member of our community, you have the ability to refine your adz preferences in your wordpress profile here {link}. Please let us know how well we're doing by providing us with feedback here {messageToAdminFromUserProfile}", 'adz-world' ),
				),
			),
		),
	);
}

/**
 * Save the Display tab's audience-message form.
 *
 * @return true|WP_Error
 */
function adzworld_save_audience_messages() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return new WP_Error( 'adzworld_audience_permission', __( 'Administrator access is required.', 'adz-world' ) );
	}
	check_admin_referer( 'adzworld_audience_save', 'adzworld_audience_nonce' );

	$order = filter_input( INPUT_POST, 'adzworld_audience_order', FILTER_UNSAFE_RAW );
	$rows  = filter_input( INPUT_POST, 'adzworld_audience_rows', FILTER_UNSAFE_RAW, FILTER_REQUIRE_ARRAY );
	$clean = array(
		'order'    => 'shuffled' === $order ? 'shuffled' : 'ordered',
		'messages' => array(),
	);
	foreach ( adzworld_audience_labels() as $audience => $label ) {
		$clean['messages'][ $audience ] = array();
		$list                           = isset( $rows[ $audience ] ) && is_array( $rows[ $audience ] ) ? $rows[ $audience ] : array();
		foreach ( $list as $row ) {
			if ( ! is_array( $row ) || ! isset( $row['text'], $row['placement'] ) || ! is_string( $row['text'] ) || ! is_string( $row['placement'] ) ) {
				continue;
			}
			$text = wp_kses( trim( $row['text'] ), adzworld_audience_message_html() );
			if ( '' === trim( wp_strip_all_tags( $text ) ) ) {
				continue;
			}
			if ( mb_strlen( $text ) > 4000 || count( $clean['messages'][ $audience ] ) >= 20 || ! isset( adzworld_audience_placements()[ $row['placement'] ] ) ) {
				return new WP_Error( 'adzworld_audience_invalid', __( 'Use one of the listed placements and up to 20 messages per audience. Each message may contain up to 4,000 characters.', 'adz-world' ) );
			}
			$clean['messages'][ $audience ][] = array(
				'enabled'   => isset( $row['enabled'] ) && '1' === $row['enabled'],
				'placement' => $row['placement'],
				'text'      => $text,
			);
		}
	}

	if ( get_option( 'adzworld_audience_messages' ) !== $clean && ! update_option( 'adzworld_audience_messages', $clean, false ) ) {
		return new WP_Error( 'adzworld_audience_storage', __( 'The audience messages could not be saved.', 'adz-world' ) );
	}
	return true;
}

/**
 * Render a message editor row, also used by the add-message template.
 *
 * @param string $audience Audience key.
 * @param string $index    Row index.
 * @param array  $message  Saved row.
 * @return void
 */
function adzworld_audience_message_row( $audience, $index, $message = array() ) {
	$field = 'adzworld_audience_rows[' . $audience . '][' . $index . ']';
	$id    = 'adzworld-message-' . $audience . '-' . $index;
	?>
	<fieldset class="adzworld-audience-row" data-adzworld-message-row>
		<legend><strong><?php esc_html_e( 'Message', 'adz-world' ); ?></strong></legend>
		<label class="screen-reader-text" for="<?php echo esc_attr( $id ); ?>"><?php esc_html_e( 'Message text', 'adz-world' ); ?></label>
		<textarea class="large-text" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $field . '[text]' ); ?>" rows="3" maxlength="4000"><?php echo esc_textarea( $message['text'] ?? '' ); ?></textarea>
		<div class="adzworld-audience-actions">
			<label><input type="checkbox" name="<?php echo esc_attr( $field . '[enabled]' ); ?>" value="1" <?php checked( $message['enabled'] ?? true ); ?>>
				<?php esc_html_e( 'Enabled', 'adz-world' ); ?></label>
			<label><?php esc_html_e( 'Show on', 'adz-world' ); ?>
				<select name="<?php echo esc_attr( $field . '[placement]' ); ?>">
					<?php foreach ( adzworld_audience_placements() as $placement => $name ) : ?>
						<option value="<?php echo esc_attr( $placement ); ?>" <?php selected( $message['placement'] ?? 'both', $placement ); ?>><?php echo esc_html( $name ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<button type="button" class="button" data-adzworld-message-move="up"><?php esc_html_e( 'Move up', 'adz-world' ); ?></button>
			<button type="button" class="button" data-adzworld-message-move="down"><?php esc_html_e( 'Move down', 'adz-world' ); ?></button>
			<button type="button" class="button-link-delete" data-adzworld-message-remove><?php esc_html_e( 'Remove', 'adz-world' ); ?></button>
		</div>
	</fieldset>
	<?php
}

/**
 * Render the audience message controls and save result.
 *
 * @return void
 */
function adzworld_audience_message_editor() {
	if ( null !== filter_input( INPUT_POST, 'adzworld_audience_submit' ) ) {
		$result = adzworld_save_audience_messages();
		echo '<div class="notice ' . esc_attr( is_wp_error( $result ) ? 'notice-error' : 'notice-success' ) . '"><p>'
			. esc_html( is_wp_error( $result ) ? $result->get_error_message() : __( 'Audience messages saved.', 'adz-world' ) ) . '</p></div>';
	}
	$saved = adzworld_audience_message_settings();
	?>
	<form method="post" data-adzworld-audience-editor>
		<?php wp_nonce_field( 'adzworld_audience_save', 'adzworld_audience_nonce' ); ?>
		<p><?php esc_html_e( 'Visitors are signed out. Guests are signed in. Members are marked by an adzAdmin on their WordPress user profile. Only enabled messages can appear.', 'adz-world' ); ?></p>
		<p><label for="adzworld-audience-order"><?php esc_html_e( 'Message order', 'adz-world' ); ?></label>
			<select name="adzworld_audience_order" id="adzworld-audience-order">
				<option value="ordered" <?php selected( $saved['order'], 'ordered' ); ?>><?php esc_html_e( 'In order', 'adz-world' ); ?></option>
				<option value="shuffled" <?php selected( $saved['order'], 'shuffled' ); ?>><?php esc_html_e( 'Shuffled', 'adz-world' ); ?></option>
			</select>
		</p>
		<p><?php esc_html_e( 'In order advances through the list as panels are rendered and changes the starting message each minute. Shuffled picks a new order when WordPress generates a page.', 'adz-world' ); ?></p>
		<p><?php esc_html_e( 'Use {signIn} for a sign-in link. Member messages can use {link} for profile adz preferences and {messageToAdminFromUserProfile} for feedback.', 'adz-world' ); ?></p>
		<?php foreach ( adzworld_audience_labels() as $audience => $label ) : ?>
			<section class="adzworld-audience-list" data-adzworld-audience-list>
				<h3><?php echo esc_html( $label ); ?></h3>
				<div data-adzworld-message-rows>
					<?php
					foreach ( $saved['messages'][ $audience ] ?? array() as $index => $message ) {
						adzworld_audience_message_row( $audience, (string) $index, $message );
					}
					?>
				</div>
				<template><?php adzworld_audience_message_row( $audience, '__index__' ); ?></template>
				<p><button type="button" class="button" data-adzworld-message-add><?php esc_html_e( 'Add message', 'adz-world' ); ?></button></p>
			</section>
		<?php endforeach; ?>
		<?php submit_button( __( 'Save audience messages', 'adz-world' ), 'primary', 'adzworld_audience_submit' ); ?>
	</form>
	<?php
}

/**
 * Format one eligible message for an audience and placement, without visitor history.
 *
 * @param string $surface Hub or adz.
 * @return string Safe message markup, or an empty string.
 */
function adzworld_audience_message( $surface ) {
	static $sequences = array();
	$audience         = is_user_logged_in() ? ( adzworld_audience_is_member() ? 'member' : 'guest' ) : 'visitor';
	$key              = $audience . '_' . $surface;
	if ( ! isset( $sequences[ $key ] ) ) {
		$saved   = adzworld_audience_message_settings();
		$choices = array();
		foreach ( $saved['messages'][ $audience ] ?? array() as $row ) {
			if ( ( $row['enabled'] ?? true ) && in_array( $row['placement'], array( 'both', $surface ), true )
				&& ( false === strpos( $row['text'], '{link}' ) || ( 'member' === $audience && adzworld_preference_uses_profile() ) )
				&& ( false === strpos( $row['text'], '{messageToAdminFromUserProfile}' ) || 'member' === $audience ) ) {
				$choices[] = $row['text'];
			}
		}
		$shuffled = 'shuffled' === $saved['order'];
		if ( $shuffled ) {
			shuffle( $choices );
		}
		$sequences[ $key ] = array(
			'choices' => $choices,
			'index'   => $shuffled ? 0 : intdiv( time(), MINUTE_IN_SECONDS ),
		);
	}
	$count = count( $sequences[ $key ]['choices'] );
	if ( 0 === $count ) {
		return '';
	}
	$index = $sequences[ $key ]['index'] % $count;
	++$sequences[ $key ]['index'];
	$text = $sequences[ $key ]['choices'][ $index ];

	$permalink = get_permalink();
	$links     = array(
		'{signIn}' => 'visitor' === $audience ? '<a href="' . esc_url( wp_login_url( $permalink ? $permalink : home_url( '/' ) ) ) . '">' . esc_html__( 'Sign in', 'adz-world' ) . '</a>' : esc_html__( 'Sign in', 'adz-world' ),
	);
	if ( 'member' === $audience ) {
		$links['{link}']                          = '<a href="' . esc_url( get_edit_profile_url() . '#adzworld-profile-preferences' ) . '">' . esc_html__( 'Edit adz preferences', 'adz-world' ) . '</a>';
		$links['{messageToAdminFromUserProfile}'] = '<a href="' . esc_url( get_edit_profile_url() . '#adzworld-profile-feedback' ) . '">' . esc_html__( 'Send feedback', 'adz-world' ) . '</a>';
	}
	$parts   = wp_html_split( $text );
	$in_link = false;
	foreach ( $parts as $part_index => $part ) {
		if ( preg_match( '/^<a(?:\s|>)/i', $part ) ) {
			$in_link = true;
		} elseif ( 0 === stripos( $part, '</a' ) ) {
			$in_link = false;
		} elseif ( ! $in_link && 0 !== strpos( $part, '<' ) ) {
			$parts[ $part_index ] = strtr( $part, $links );
		}
	}

	return '<div class="adzworld-audience-message">' . wp_kses( implode( '', $parts ), adzworld_audience_message_html() ) . '</div>';
}
