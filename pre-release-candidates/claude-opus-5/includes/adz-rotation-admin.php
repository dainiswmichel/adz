<?php
/**
 * The Rotation screen: where a publisher registers the ad code they already run.
 *
 * Nothing here contacts an ad network. The publisher pastes the code their own
 * account gave them, names it, and the rotation element presents it. Saving ad
 * code requires unfiltered_html, because that is the capability WordPress
 * already governs arbitrary markup and script with.
 *
 * @package adz-world
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

add_action( 'admin_menu', 'adzworld_rotation_menu', 13 );
/**
 * Add the Rotation screen under the adz post type.
 *
 * @return void
 */
function adzworld_rotation_menu() {
	add_submenu_page(
		'edit.php?post_type=adz_ad',
		__( 'Rotation', 'adz-world' ),
		__( 'Rotation', 'adz-world' ),
		'manage_options',
		'adz-rotation',
		'adzworld_rotation_render'
	);
}

/**
 * Handle a submission from the Rotation screen.
 *
 * Returns a notice to display, or an empty array when there was nothing to do.
 *
 * @return array{type:string,message:string}|array{}
 */
function adzworld_rotation_handle_post() {
	if ( ! isset( $_POST['adzworld_rotation_action'] ) ) {
		return array();
	}

	// A nonce proves the request came from the screen. It is not authorisation,
	// so the capability is checked separately below for each action.
	check_admin_referer( 'adzworld_rotation' );

	$action = sanitize_key( wp_unslash( $_POST['adzworld_rotation_action'] ) );

	if ( 'delete' === $action ) {
		if ( ! current_user_can( 'unfiltered_html' ) ) {
			return array(
				'type'    => 'error',
				'message' => __( 'Removing a slot requires the unfiltered_html capability.', 'adz-world' ),
			);
		}

		$key   = isset( $_POST['slot_key'] ) ? sanitize_key( wp_unslash( $_POST['slot_key'] ) ) : '';
		$slots = adzworld_get_network_slots();

		if ( '' === $key || ! isset( $slots[ $key ] ) ) {
			return array(
				'type'    => 'error',
				'message' => __( 'That slot no longer exists.', 'adz-world' ),
			);
		}

		unset( $slots[ $key ] );
		update_option( 'adzworld_network_slots', $slots, false );

		return array(
			'type'    => 'updated',
			'message' => __( 'Slot removed. The adz it served are no longer in the rotation.', 'adz-world' ),
		);
	}

	if ( 'save' !== $action ) {
		return array();
	}

	$preset = isset( $_POST['slot_preset'] ) ? sanitize_key( wp_unslash( $_POST['slot_preset'] ) ) : '';
	$key    = isset( $_POST['slot_key'] ) ? sanitize_key( wp_unslash( $_POST['slot_key'] ) ) : '';
	$label  = isset( $_POST['slot_label'] ) ? sanitize_text_field( wp_unslash( $_POST['slot_label'] ) ) : '';

	/*
	 * The ad code is deliberately not sanitised. It is script and markup from
	 * the publisher's own ad network, and filtering it would break every
	 * network's tag, which is the entire purpose of the slot. The gate is the
	 * capability: adzworld_save_network_slot() refuses without unfiltered_html.
	 */
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- ad code stored verbatim by design; gated on unfiltered_html in adzworld_save_network_slot().
	$code = isset( $_POST['slot_code'] ) ? (string) $_POST['slot_code'] : '';

	if ( '' === $key && '' !== $preset && 'custom' !== $preset ) {
		$key = $preset;
	}

	if ( '' === $key ) {
		return array(
			'type'    => 'error',
			'message' => __( 'A slot needs a name so the shortcode can refer to it.', 'adz-world' ),
		);
	}

	if ( '' === $label ) {
		$presets = adzworld_network_presets();
		$label   = isset( $presets[ $preset ] ) ? $presets[ $preset ]['label'] : $key;
	}

	if ( '' === trim( $code ) ) {
		return array(
			'type'    => 'error',
			'message' => __( 'Paste the ad code from your own network account.', 'adz-world' ),
		);
	}

	$saved = adzworld_save_network_slot( $key, $label, $code );

	if ( is_wp_error( $saved ) ) {
		return array(
			'type'    => 'error',
			'message' => $saved->get_error_message(),
		);
	}

	return array(
		'type'    => 'updated',
		'message' => sprintf(
			/* translators: %s: the slot name. */
			__( 'Saved. Add it to a page with [adz_rotation slots="%s"].', 'adz-world' ),
			$key
		),
	);
}

/**
 * Render the Rotation screen.
 *
 * @return void
 */
function adzworld_rotation_render() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to manage the rotation.', 'adz-world' ), 403 );
	}

	$notice   = adzworld_rotation_handle_post();
	$slots    = adzworld_get_network_slots();
	$presets  = adzworld_network_presets();
	$can_edit = current_user_can( 'unfiltered_html' );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Rotation', 'adz-world' ); ?></h1>

		<?php if ( ! empty( $notice ) ) : ?>
			<div class="notice notice-<?php echo esc_attr( $notice['type'] ); ?> is-dismissible">
				<p><?php echo esc_html( $notice['message'] ); ?></p>
			</div>
		<?php endif; ?>

		<p>
			<?php
			esc_html_e(
				'Register the ad code you already run. The rotation presents it exactly as your own network supplied it, in the same place, through your own account. Nothing here contacts an ad network.',
				'adz-world'
			);
			?>
		</p>

		<?php if ( ! $can_edit ) : ?>
			<div class="notice notice-warning">
				<p>
					<?php
					esc_html_e(
						'Your account cannot save ad code. That requires the unfiltered_html capability, which on a multisite network is held by super admins only.',
						'adz-world'
					);
					?>
				</p>
			</div>
		<?php endif; ?>

		<h2><?php esc_html_e( 'Registered slots', 'adz-world' ); ?></h2>

		<?php if ( empty( $slots ) ) : ?>
			<p>
				<?php
				esc_html_e(
					'No slots yet. With none registered the rotation shows only adz a visitor has asked for.',
					'adz-world'
				);
				?>
			</p>
		<?php else : ?>
			<table class="widefat striped">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Slot', 'adz-world' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Name', 'adz-world' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Code', 'adz-world' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Remove', 'adz-world' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $slots as $key => $slot ) : ?>
						<tr>
							<td><code><?php echo esc_html( $key ); ?></code></td>
							<td><?php echo esc_html( $slot['label'] ); ?></td>
							<td>
								<?php
								// Shown as text, never executed, so the admin can confirm what is stored.
								printf(
									'<code>%s</code>',
									esc_html( mb_strimwidth( (string) $slot['code'], 0, 90, '...' ) )
								);
								?>
							</td>
							<td>
								<?php if ( $can_edit ) : ?>
									<form method="post">
										<?php wp_nonce_field( 'adzworld_rotation' ); ?>
										<input type="hidden" name="adzworld_rotation_action" value="delete">
										<input type="hidden" name="slot_key" value="<?php echo esc_attr( $key ); ?>">
										<button type="submit" class="button button-link-delete">
											<?php esc_html_e( 'Remove', 'adz-world' ); ?>
										</button>
									</form>
								<?php else : ?>
									&mdash;
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<?php if ( $can_edit ) : ?>
			<h2><?php esc_html_e( 'Add a slot', 'adz-world' ); ?></h2>

			<form method="post">
				<?php wp_nonce_field( 'adzworld_rotation' ); ?>
				<input type="hidden" name="adzworld_rotation_action" value="save">

				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row">
								<label for="adzworld-slot-preset"><?php esc_html_e( 'Network', 'adz-world' ); ?></label>
							</th>
							<td>
								<select name="slot_preset" id="adzworld-slot-preset">
									<?php foreach ( $presets as $preset_key => $preset ) : ?>
										<option value="<?php echo esc_attr( $preset_key ); ?>">
											<?php echo esc_html( $preset['label'] ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<p class="description">
									<?php
									esc_html_e(
										'Picking a network only names the slot. The plugin makes no request to any of them.',
										'adz-world'
									);
									?>
								</p>
								<ul>
									<?php foreach ( $presets as $preset_key => $preset ) : ?>
										<li>
											<strong><?php echo esc_html( $preset['label'] ); ?></strong>
											&mdash; <?php echo esc_html( $preset['note'] ); ?>
										</li>
									<?php endforeach; ?>
								</ul>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="adzworld-slot-key"><?php esc_html_e( 'Slot name', 'adz-world' ); ?></label>
							</th>
							<td>
								<input
									type="text"
									name="slot_key"
									id="adzworld-slot-key"
									class="regular-text"
									placeholder="<?php esc_attr_e( 'sidebar_top', 'adz-world' ); ?>"
								>
								<p class="description">
									<?php esc_html_e( 'How the shortcode refers to this slot. Lowercase letters, numbers, hyphens and underscores.', 'adz-world' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="adzworld-slot-label"><?php esc_html_e( 'Label', 'adz-world' ); ?></label>
							</th>
							<td>
								<input type="text" name="slot_label" id="adzworld-slot-label" class="regular-text">
								<p class="description">
									<?php esc_html_e( 'For your own reference on this screen. Defaults to the network name.', 'adz-world' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="adzworld-slot-code"><?php esc_html_e( 'Ad code', 'adz-world' ); ?></label>
							</th>
							<td>
								<textarea
									name="slot_code"
									id="adzworld-slot-code"
									rows="8"
									class="large-text code"
								></textarea>
								<p class="description">
									<?php
									esc_html_e(
										'Pasted and stored exactly as given, because filtering it would break the network tag. Each slot renders once per page: a network keys its own code on a slot id and will not serve the same id twice.',
										'adz-world'
									);
									?>
								</p>
							</td>
						</tr>
					</tbody>
				</table>

				<?php submit_button( __( 'Save slot', 'adz-world' ) ); ?>
			</form>
		<?php endif; ?>

		<h2><?php esc_html_e( 'Using it', 'adz-world' ); ?></h2>

		<table class="widefat striped">
			<tbody>
				<tr>
					<td><code>[adz_rotation slots="sidebar_top" every="0"]</code></td>
					<td><?php esc_html_e( 'Your adz only. Exactly what you serve now, presented by the plugin.', 'adz-world' ); ?></td>
				</tr>
				<tr>
					<td><code>[adz_rotation slots="sidebar_top,sidebar_mid" every="10"]</code></td>
					<td><?php esc_html_e( 'Your adz, plus one adz a visitor asked for on one render in ten.', 'adz-world' ); ?></td>
				</tr>
				<tr>
					<td><code>[adz_rotation display="carousel-3d" controls="both" interval="7"]</code></td>
					<td><?php esc_html_e( '3D carousel with arrows and a dotted menu, advancing every seven seconds.', 'adz-world' ); ?></td>
				</tr>
				<tr>
					<td><code>[adz_rotation display="carousel-flat" intro="short"]</code></td>
					<td><?php esc_html_e( 'Flat carousel with a one-line explanation instead of the full panel.', 'adz-world' ); ?></td>
				</tr>
				<tr>
					<td><code>[adz_rotation panel="no"]</code></td>
					<td><?php esc_html_e( 'Rotation with no panel, for when the panel lives elsewhere on the page.', 'adz-world' ); ?></td>
				</tr>
			</tbody>
		</table>

		<p>
			<?php
			esc_html_e(
				'The one-in-N decision is made when the page is built, so a full-page cache caches it with the page. On a cached site either leave this shortcode uncached or use every="1" and let the rotation itself do the spacing.',
				'adz-world'
			);
			?>
		</p>
	</div>
	<?php
}
