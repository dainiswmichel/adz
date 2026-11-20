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

		jQuery( '.adzworld-bands__hex' ).each(
			function () {
				var row    = jQuery( this );
				var picker = row.find( 'input[type="color"]' );
				var text   = row.find( 'input[type="text"]' );

				picker.on(
					'input',
					function () {
						text.val( picker.val().toUpperCase() );
					}
				);
				text.on(
					'input',
					function () {
						var value = text.val().trim();
						if ( /^#[0-9A-Fa-f]{6}$/.test( value ) ) {
							picker.val( value );
						}
					}
				);
			}
		);

		function adzworldTemplateLine( field, sample, alignName, row ) {
			var text = field.val();
			if ( text ) {
				sample.text( text ).removeClass( 'adzworld-template-lines__empty' );
			} else {
				sample.text( sample.data( 'emptyLabel' ) ).addClass( 'adzworld-template-lines__empty' );
			}
			row.removeClass( 'adzworld-align-left adzworld-align-center adzworld-align-right' ).addClass( 'adzworld-align-' + ( jQuery( 'input[name="' + alignName + '"]:checked' ).val() || 'center' ) );
		}

		function adzworldTemplatePreview() {
			adzworldTemplateLine( jQuery( '#header_text' ), jQuery( '[data-adzworld-template-header]' ), 'header_style', jQuery( '[data-adzworld-template-header-row]' ) );
			adzworldTemplateLine( jQuery( '#footer_text' ), jQuery( '[data-adzworld-template-footer]' ), 'footer_style', jQuery( '[data-adzworld-template-footer-row]' ) );
		}

		function adzworldTemplateSaved( key, value ) {
			var cell  = jQuery( '[data-adzworld-saved="' + key + '"]' );
			var empty = cell.data( 'emptyLabel' );
			var prefix;
			var label;
			if ( ! cell.length ) {
				return;
			}
			if ( 'align' === cell.data( 'kind' ) ) {
				prefix = 'header_style' === key ? 'header_' : 'footer_';
				label  = jQuery( '#' + prefix + value ).closest( 'label' ).text();
				cell.text( label ? jQuery.trim( label ) : value );
				return;
			}
			cell.text( value ? value : ( empty ? empty : '' ) );
		}

		jQuery( '#header_text, #footer_text' ).on( 'input', adzworldTemplatePreview );
		jQuery( 'input[name="header_style"], input[name="footer_style"]' ).on( 'change', adzworldTemplatePreview );

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
							adzworldTemplateSaved( 'header_text', obj.header_text );
							adzworldTemplateSaved( 'footer_text', obj.footer_text );
							adzworldTemplateSaved( 'header_style', obj.header_style );
							adzworldTemplateSaved( 'footer_style', obj.footer_style );
							adzworldTemplateSaved( 'adz_template', selectedTemplate );
							adzworldTemplateSaved( 'option', 'template_' + selectedTemplate );
							adzworldTemplatePreview();
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
