/**
 * Advertisement template settings controls.
 *
 * @package adz-world
 */

jQuery( document ).ready(
	function () {
		jQuery( '#insert-adz-iframe-button' ).on(
			'click',
			function () {
				var iframeUrl = window.prompt( adzworldAdmin.iframePrompt, 'https://' );
				var parsedUrl;

				if ( iframeUrl === null || iframeUrl.trim() === '' ) {
					return;
				}

				try {
					parsedUrl = new URL( iframeUrl.trim() );
				} catch ( error ) {
					window.alert( adzworldAdmin.iframeInvalid );
					return;
				}

				if ( [ 'http:', 'https:' ].indexOf( parsedUrl.protocol ) === -1 || typeof window.send_to_editor !== 'function' ) {
					window.alert( adzworldAdmin.iframeInvalid );
					return;
				}

				window.send_to_editor( '[adzworld_iframe adz_url="' + parsedUrl.href + '" height="600" width="100%"]' );
			}
		);

		jQuery( '#adz_template_list' ).change(
			function () {
				var selectedTemplate = jQuery( this ).val();
				jQuery( '.loading_data' ).removeClass( 'adzworld-is-hidden' );
				jQuery.ajax(
					{
						url: adzworldAdmin.ajaxUrl,
						type: 'POST',
						data: {
							action: 'get_template_settings',
							selected_template: selectedTemplate,
							nonce: adzworldAdmin.nonce,
						},
						success: function ( data ) {
							var obj = typeof data === 'string' ? JSON.parse( data ) : data;
							if ( obj.success === false ) {
								jQuery( '.loading_data' ).text( obj.data.message ).removeClass( 'adzworld-is-hidden' );
								return;
							}

							jQuery( '#header_text' ).val( obj.header_text );
							jQuery( '#footer_text' ).val( obj.footer_text );
							jQuery( '#header_' + obj.header_style ).prop( 'checked', true );
							jQuery( '#footer_' + obj.footer_style ).prop( 'checked', true );
							jQuery( '.loading_data' ).addClass( 'adzworld-is-hidden' );
						},
						error: function () {
							jQuery( '.loading_data' ).text( adzworldAdmin.settingsError ).removeClass( 'adzworld-is-hidden' );
						}
					}
				);
			}
		);
	}
);
