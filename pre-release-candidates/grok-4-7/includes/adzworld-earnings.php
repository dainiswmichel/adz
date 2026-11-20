<?php
/**
 * Direct provider earnings links and voluntary plugin support.
 *
 * @package adz-world
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add the administrator-only earnings page.
 *
 * @return void
 */
function adzworld_earnings_menu() {
	add_submenu_page( 'edit.php?post_type=adz_ad', __( 'Earnings', 'adz-world' ), __( 'Earnings', 'adz-world' ), 'manage_options', 'adzworld-earnings', 'adzworld_earnings_page' );
}
add_action( 'admin_menu', 'adzworld_earnings_menu' );

/**
 * Public donation page for voluntary plugin support.
 *
 * The plugin does not request this URL. The administrator's browser visits it
 * only after the administrator clicks the support button.
 *
 * @return string
 */
function adzworld_donation_url() {
	return 'https://dainis.net/donate/';
}

/**
 * Render a restrained, settings-page-only support panel.
 *
 * @return void
 */
function adzworld_support_panel() {
	echo '<section class="card adzworld-support-card"><h2>' . esc_html(
		sprintf(
			/* translators: %s: product name. */
			__( 'Support %s', 'adz-world' ),
			ADZWORLD_NAME
		)
	) . '</h2>';
	echo '<p>' . esc_html(
		sprintf(
			/* translators: %s: product name. */
			__( '%s is free software. If it helps your site, a small voluntary contribution supports continued maintenance and WordPress.org updates.', 'adz-world' ),
			ADZWORLD_NAME
		)
	) . '</p>';
	echo '<p><a class="button button-primary" href="' . esc_url( adzworld_donation_url() ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Buy Dainis a coffee', 'adz-world' ) . '</a></p></section>';
}

/**
 * Render direct earning options and voluntary plugin support.
 *
 * @return void
 */
function adzworld_earnings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Administrator access is required.', 'adz-world' ) );
	}
	?>
	<div class="wrap adzworld-desk adzworld-earnings">
		<h1><?php esc_html_e( 'Earnings', 'adz-world' ); ?></h1>
		<p>
		<?php
		echo esc_html(
			sprintf(
				/* translators: %s: product name. */
				__( 'Choose established earning methods that pay the site owner through accounts the site owner already controls. %s does not receive or redistribute those earnings.', 'adz-world' ),
				ADZWORLD_NAME
			)
		);
		?>
		</p>
		<div class="adzworld-desk-grid adzworld-earnings-grid">
			<section class="adzworld-desk-card">
				<h2><?php esc_html_e( 'Affiliate earnings', 'adz-world' ); ?></h2>
				<?php
				/* translators: %d: number of enabled publisher affiliate accounts. */
				$affiliate_count = sprintf( _n( '%d account enabled', '%d accounts enabled', adzworld_affiliate_connected_count(), 'adz-world' ), adzworld_affiliate_connected_count() );
				?>
				<strong><?php echo esc_html( $affiliate_count ); ?></strong>
				<p><?php esc_html_e( 'Affiliate networks attribute qualifying activity to the publisher identifiers saved on this site and pay that publisher account under their terms.', 'adz-world' ); ?></p>
				<a class="button" href="<?php echo esc_url( adzworld_desk_url( 'affiliates' ) ); ?>"><?php esc_html_e( 'Manage affiliate attribution', 'adz-world' ); ?></a>
			</section>
			<section class="adzworld-desk-card">
				<h2><?php esc_html_e( 'Ad-network earnings', 'adz-world' ); ?></h2>
				<?php
				/* translators: %d: number of enabled publisher ad-network units. */
				$network_count = sprintf( _n( '%d unit enabled', '%d units enabled', adzworld_enabled_network_count(), 'adz-world' ), adzworld_enabled_network_count() );
				?>
				<strong><?php echo esc_html( $network_count ); ?></strong>
				<p><?php esc_html_e( 'Connected display networks use the site owner’s account identifiers and handle their own reporting and payments.', 'adz-world' ); ?></p>
				<a class="button" href="<?php echo esc_url( adzworld_desk_url( 'affiliates' ) ); ?>"><?php esc_html_e( 'Open Get Paid', 'adz-world' ); ?></a>
			</section>
		</div>
		<?php adzworld_support_panel(); ?>
	</div>
	<?php
}
