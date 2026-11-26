<?php
/**
 * Explicit membership and the Member message's profile actions.
 *
 * @package adz-world
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether the current account was marked as an adz-world Member.
 *
 * @return bool
 */
function adzworld_audience_is_member() {
	$user_id = get_current_user_id();
	return $user_id > 0 && '1' === get_user_meta( $user_id, 'adzworld_audience_member', true );
}

/**
 * Show the explicit Member choice to administrators editing a user.
 *
 * @param WP_User $user Profile owner.
 * @return void
 */
function adzworld_audience_member_field( $user ) {
	if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'edit_user', $user->ID ) ) {
		return;
	}
	wp_nonce_field( 'adzworld_member_' . $user->ID, 'adzworld_member_nonce', false );
	?>
	<h2><?php esc_html_e( 'Adz-world membership', 'adz-world' ); ?></h2>
	<p><label><input type="checkbox" name="adzworld_audience_member" value="1" <?php checked( get_user_meta( $user->ID, 'adzworld_audience_member', true ), '1' ); ?>>
		<?php esc_html_e( 'Show Member messages to this account.', 'adz-world' ); ?></label></p>
	<?php
}
add_action( 'show_user_profile', 'adzworld_audience_member_field' );
add_action( 'edit_user_profile', 'adzworld_audience_member_field' );

/**
 * Save the administrator's membership choice.
 *
 * @param int $user_id Profile owner.
 * @return void
 */
function adzworld_save_audience_member( $user_id ) {
	if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'edit_user', $user_id ) ) {
		return;
	}
	$nonce = filter_input( INPUT_POST, 'adzworld_member_nonce' );
	if ( ! is_string( $nonce ) || ! wp_verify_nonce( sanitize_text_field( $nonce ), 'adzworld_member_' . $user_id ) ) {
		return;
	}
	if ( '1' === filter_input( INPUT_POST, 'adzworld_audience_member' ) ) {
		update_user_meta( $user_id, 'adzworld_audience_member', '1' );
	} else {
		delete_user_meta( $user_id, 'adzworld_audience_member' );
	}
}
add_action( 'personal_options_update', 'adzworld_save_audience_member' );
add_action( 'edit_user_profile_update', 'adzworld_save_audience_member' );

/**
 * Let a Member refine their own saved categories and contact the administrator.
 *
 * @param WP_User $user Profile owner.
 * @return void
 */
function adzworld_audience_member_profile( $user ) {
	if ( get_current_user_id() !== (int) $user->ID || ! adzworld_audience_is_member() ) {
		return;
	}
	if ( adzworld_preference_uses_profile() ) {
		$pref = adzworld_current_preference();
		wp_nonce_field( 'adzworld_profile_refine', 'adzworld_profile_refine_nonce', false );
		echo '<h2 id="adzworld-profile-preferences">' . esc_html__( 'Refine your adz preferences', 'adz-world' ) . '</h2>';
		if ( empty( $pref['categories'] ) ) {
			echo '<p>' . esc_html__( 'Add a category in the adz Hub to refine it here.', 'adz-world' ) . '</p>';
		} else {
			echo '<p>' . esc_html__( 'Enter one refinement per line, then choose Update Profile to save.', 'adz-world' ) . '</p>';
		}
		foreach ( $pref['categories'] as $category ) {
			$refinements = array();
			foreach ( $pref['category_refinements'] as $stored ) {
				$pair = adzworld_pair_split( $stored );
				if ( 2 === count( $pair ) && $pair[0] === $category ) {
					$refinements[] = $pair[1];
				}
			}
			$key = md5( $category );
			$id  = 'adzworld-profile-refinement-' . $key;
			echo '<p><label for="' . esc_attr( $id ) . '">' . esc_html( $category ) . '</label><br>';
			echo '<textarea class="large-text" rows="3" maxlength="' . esc_attr( (string) ADZWORLD_REFINEMENT_MAX_LENGTH ) . '" id="' . esc_attr( $id ) . '" name="adzworld_profile_refinements['
				. esc_attr( $key ) . ']">' . esc_textarea( implode( "\n", $refinements ) ) . '</textarea></p>';
		}
	}
	$status = filter_input( INPUT_GET, 'adzworld_feedback_status' );
	?>
	<h2 id="adzworld-profile-feedback"><?php esc_html_e( 'Send feedback to the site admin', 'adz-world' ); ?></h2>
	<?php if ( 'sent' === $status ) : ?>
		<p role="status"><?php esc_html_e( 'Your feedback was sent.', 'adz-world' ); ?></p>
	<?php elseif ( 'failed' === $status ) : ?>
		<p role="alert"><?php esc_html_e( 'Your feedback could not be sent. Please try again.', 'adz-world' ); ?></p>
	<?php endif; ?>
	<p><label for="adzworld-profile-feedback-text"><?php esc_html_e( 'Your feedback', 'adz-world' ); ?></label><br>
		<textarea class="large-text" rows="4" maxlength="4000" required form="adzworld-profile-feedback-form" name="adzworld_profile_feedback" id="adzworld-profile-feedback-text"></textarea></p>
	<p><button class="button" type="submit" form="adzworld-profile-feedback-form"><?php esc_html_e( 'Send feedback', 'adz-world' ); ?></button></p>
	<?php
}
add_action( 'show_user_profile', 'adzworld_audience_member_profile' );

/**
 * Place the independent feedback form outside WordPress's profile form.
 *
 * @return void
 */
function adzworld_audience_feedback_form() {
	if ( ! adzworld_audience_is_member() ) {
		return;
	}
	echo '<form method="post" id="adzworld-profile-feedback-form" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="adzworld_send_profile_feedback">';
	wp_nonce_field( 'adzworld_send_profile_feedback', 'adzworld_feedback_nonce', false );
	echo '</form>';
}
add_action( 'admin_footer-profile.php', 'adzworld_audience_feedback_form' );

/**
 * Update the current Member's refinements in the existing consented profile.
 *
 * @param int $user_id Profile owner.
 * @return void
 */
function adzworld_audience_save_profile_refinements( $user_id ) {
	if ( get_current_user_id() !== (int) $user_id || ! current_user_can( 'edit_user', $user_id ) || ! adzworld_audience_is_member() || ! adzworld_preference_uses_profile() ) {
		return;
	}
	$nonce = filter_input( INPUT_POST, 'adzworld_profile_refine_nonce' );
	$rows  = filter_input( INPUT_POST, 'adzworld_profile_refinements', FILTER_UNSAFE_RAW, FILTER_REQUIRE_ARRAY );
	if ( ! is_string( $nonce ) || ! wp_verify_nonce( sanitize_text_field( $nonce ), 'adzworld_profile_refine' ) || ! is_array( $rows ) ) {
		return;
	}
	$pref = adzworld_current_preference();
	foreach ( $pref['categories'] as $category ) {
		$key = md5( $category );
		if ( ! isset( $rows[ $key ] ) || ! is_string( $rows[ $key ] ) || mb_strlen( $rows[ $key ] ) > ADZWORLD_REFINEMENT_MAX_LENGTH ) {
			continue;
		}
		$pref['category_refinements'] = array_values(
			array_filter(
				$pref['category_refinements'],
				static function ( $stored ) use ( $category ) {
					$pair = adzworld_pair_split( $stored );
					return 2 !== count( $pair ) || $pair[0] !== $category;
				}
			)
		);
		foreach ( preg_split( '/\R/', sanitize_textarea_field( $rows[ $key ] ) ) as $line ) {
			$line = trim( $line );
			if ( '' !== $line ) {
				$pref['category_refinements'][] = adzworld_pair_join( $category, $line );
			}
		}
	}
	adzworld_store_preference( $pref );
}
add_action( 'personal_options_update', 'adzworld_audience_save_profile_refinements', 20 );

/**
 * Send Member-authored feedback to the configured site administrator.
 *
 * @return void
 */
function adzworld_send_audience_profile_feedback() {
	if ( ! adzworld_audience_is_member() || ! current_user_can( 'read' ) ) {
		wp_die( esc_html__( 'Member access is required.', 'adz-world' ), '', array( 'response' => 403 ) );
	}
	check_admin_referer( 'adzworld_send_profile_feedback', 'adzworld_feedback_nonce' );
	$raw  = filter_input( INPUT_POST, 'adzworld_profile_feedback' );
	$text = is_string( $raw ) && mb_strlen( $raw ) <= 4000 ? sanitize_textarea_field( $raw ) : '';
	$sent = '' !== $text && wp_mail( get_option( 'admin_email' ), __( 'Adz-world Member feedback', 'adz-world' ), $text );
	wp_safe_redirect( add_query_arg( 'adzworld_feedback_status', $sent ? 'sent' : 'failed', get_edit_profile_url() ) . '#adzworld-profile-feedback' );
	exit;
}
add_action( 'admin_post_adzworld_send_profile_feedback', 'adzworld_send_audience_profile_feedback' );
