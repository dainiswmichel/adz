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

		function adzworldTemplateLine( field, sample, alignName, row ) {
			sample.text( field.val() || '' );
			row.removeClass( 'adzworld-align-left adzworld-align-center adzworld-align-right' ).addClass( 'adzworld-align-' + ( jQuery( 'input[name="' + alignName + '"]:checked' ).val() || 'center' ) );
		}

		function adzworldTemplatePreview() {
			adzworldTemplateLine( jQuery( '#header_text' ), jQuery( '[data-adzworld-template-header]' ), 'header_style', jQuery( '[data-adzworld-template-header-row]' ) );
			adzworldTemplateLine( jQuery( '#footer_text' ), jQuery( '[data-adzworld-template-footer]' ), 'footer_style', jQuery( '[data-adzworld-template-footer-row]' ) );
		}
		jQuery( '#header_text, #footer_text' ).on( 'input', adzworldTemplatePreview );
		jQuery( 'input[name="header_style"], input[name="footer_style"]' ).on( 'change', adzworldTemplatePreview );
	}
);
