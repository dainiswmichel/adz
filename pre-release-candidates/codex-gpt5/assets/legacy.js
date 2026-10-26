/**
 * Decode the legacy advertising endpoint's UTF-8 HTML transport.
 *
 * @package adz-world
 */

( function () {
	'use strict';

	window.adzworldDecodeAd = function ( encoded ) {
		var bytes = Uint8Array.from(
			window.atob( encoded ),
			function ( character ) {
				return character.charCodeAt( 0 );
			}
		);
		return new TextDecoder( 'utf-8', { fatal: true } ).decode( bytes );
	};
}() );
