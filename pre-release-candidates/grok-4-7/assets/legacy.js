/**
 * Legacy timed and gated adz: page rotations, popups, the [Adzworld]
 * shortcode, and the thru-page template.
 *
 * PHP no longer prints a script for each of these. Each one queues a plain
 * settings object with wp_add_inline_script() into
 * window.adzworldLegacyRequests, and this file runs every queued request:
 *
 *   mode      browse        page rotation: asks for an adz every 60 seconds
 *             browse-popup  the same, shown in the overlay
 *             gate          shortcode: the #show_adz link asks for one adz
 *             gate-popup    the same, shown in the overlay
 *             thru          thru-page template: the countdown starts at once
 *   ajaxUrl   admin-ajax.php URL
 *   seconds   countdown before the reader may continue
 *   continue  text of the continue link once the countdown ends
 *   category  browse only: replace the category listing with the adz
 *   request   POST fields that ask for an adz
 *   close     POST fields sent when the reader continues
 *   content   gate-popup only: the gated content shown after continuing
 *
 * @package adz-world
 */

( function ( $ ) {
	'use strict';

	window.adzworldDecodeAd = function ( markup ) {
		return markup;
	};

	/**
	 * POST one request to admin-ajax.php.
	 *
	 * @param {Object}   settings Request settings.
	 * @param {Object}   data     POST fields.
	 * @param {Function} done     Called with the response text.
	 */
	function send( settings, data, done ) {
		$.ajax(
			{
				url: settings.ajaxUrl,
				type: 'post',
				data: data,
				success: done
			}
		);
	}

	/**
	 * Count down in every .adz_timer, then turn .continue_reading into the
	 * link that lets the reader continue.
	 *
	 * @param {Object}   settings Request settings.
	 * @param {Function} ended    Called when the countdown reaches zero.
	 */
	function countdown( settings, ended ) {
		var remaining = Number( settings.seconds ) || 0;
		var timer = window.setInterval(
			function () {
				if ( remaining >= 0 ) {
					$( '.adz_timer' ).text( ' ' + remaining + ' ' );
					remaining--;
					return;
				}
				$( '.continue_reading' ).text( settings.continue ).addClass( 'close_adz' );
				window.clearInterval( timer );
				if ( ended ) {
					ended();
				}
			},
			1000
		);
	}

	/**
	 * Whether the endpoint answered with an adz rather than "continue".
	 *
	 * @param {string} data Response text.
	 * @return {boolean} True when an adz was returned.
	 */
	function isAdz( data ) {
		return 'continue' !== String( data ).trim();
	}

	/**
	 * Page rotation: ask for an adz every minute until one has been shown.
	 *
	 * @param {Object}  settings Request settings.
	 * @param {boolean} popup    Whether the adz opens in the overlay.
	 */
	function browse( settings, popup ) {
		var repeat = true;

		window.setInterval(
			function () {
				if ( ! repeat ) {
					return;
				}
				send(
					settings,
					settings.request,
					function ( data ) {
						var wrapper;

						if ( ! isAdz( data ) ) {
							return;
						}
						if ( popup ) {
							$( '#show_overlay' ).addClass( 'adzworld-is-visible' );
							$( '.adz_popup .content' ).html( window.adzworldDecodeAd( data ) );
						} else if ( settings.category ) {
							wrapper = $( '.adz-world-category-wapper' );
							if ( ! wrapper.length ) {
								$( '.page-header' ).after( '<div class="adz-world-category-wapper"></div>' );
								$( '.status-publish' ).remove();
								$( '.pagination' ).remove();
								wrapper = $( '.adz-world-category-wapper' );
							}
							wrapper.html( window.adzworldDecodeAd( data ) );
						} else {
							$( '.adz-world-container' ).html( window.adzworldDecodeAd( data ) );
						}
						countdown(
							settings,
							function () {
								repeat = false;
							}
						);
					}
				);
			},
			60000
		);

		$( document ).on(
			'click',
			'.close_adz',
			function () {
				send(
					settings,
					settings.close,
					function ( data ) {
						if ( popup ) {
							$( '#show_overlay' ).removeClass( 'adzworld-is-visible' );
							repeat = true;
						} else if ( isAdz( data ) ) {
							repeat = true;
							window.location.reload();
						}
					}
				);
			}
		);
	}

	/**
	 * Shortcode: the #show_adz link asks for one adz before the content.
	 *
	 * @param {Object}  settings Request settings.
	 * @param {boolean} popup    Whether the adz opens in the overlay.
	 */
	function gate( settings, popup ) {
		$( '#show_adz' ).on(
			'click',
			function () {
				if ( popup ) {
					$( '.adz_popup .close_adz' ).addClass( 'adzworld-is-hidden' );
				}
				send(
					settings,
					settings.request,
					function ( data ) {
						if ( ! isAdz( data ) ) {
							return;
						}
						if ( popup ) {
							$( '#show_overlay' ).addClass( 'adzworld-is-visible' );
							$( '.adz_popup .content' ).html( window.adzworldDecodeAd( data ) );
						} else {
							$( '#content_after_ad' ).html( window.adzworldDecodeAd( data ) );
						}
						countdown( settings );
					}
				);
			}
		);

		$( document ).on(
			'click',
			'.close_adz',
			function () {
				send(
					settings,
					settings.close,
					function ( data ) {
						if ( popup ) {
							$( '#show_overlay' ).removeClass( 'adzworld-is-visible' );
						} else if ( isAdz( data ) ) {
							window.location.reload();
						}
					}
				);
				if ( popup ) {
					$( '#content_after_ad' ).html( settings.content || '' );
				}
			}
		);
	}

	/**
	 * Thru-page template: the countdown starts at once.
	 *
	 * @param {Object} settings Request settings.
	 */
	function thru( settings ) {
		countdown( settings );
		$( document ).on(
			'click',
			'.close_adz',
			function () {
				send(
					settings,
					settings.close,
					function ( data ) {
						if ( isAdz( data ) ) {
							window.location.reload();
						}
					}
				);
			}
		);
	}

	$(
		function () {
			( window.adzworldLegacyRequests || [] ).forEach(
				function ( settings ) {
					if ( 'browse' === settings.mode || 'browse-popup' === settings.mode ) {
						browse( settings, 'browse-popup' === settings.mode );
					} else if ( 'gate' === settings.mode || 'gate-popup' === settings.mode ) {
						gate( settings, 'gate-popup' === settings.mode );
					} else if ( 'thru' === settings.mode ) {
						thru( settings );
					}
				}
			);
		}
	);
}( jQuery ) );
