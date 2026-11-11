/**
 * Front-end style-variation switcher.
 *
 * The control is built here rather than printed by PHP, so a reader without
 * JavaScript is never shown a dropdown that cannot work. Without it the page
 * simply uses the variation the administrator chose.
 *
 * @package adz-world-2026
 */

( function () {
	'use strict';

	var data = window.adzWorldStyles;

	if ( ! data || ! data.variations || data.variations.length < 2 ) {
		return;
	}

	var mount = document.querySelector( '[data-adz-style-switcher]' );

	if ( ! mount ) {
		return;
	}

	var root = document.documentElement;

	/**
	 * Read the stored choice.
	 *
	 * Storage throws in a private window and in an iframe with third-party
	 * cookies blocked, so every access is guarded and an unreadable store
	 * simply means no stored choice.
	 *
	 * @return {string} Stored slug, or an empty string.
	 */
	function stored() {
		try {
			return window.localStorage.getItem( data.key ) || '';
		} catch ( error ) {
			return '';
		}
	}

	/**
	 * Remember a choice, or forget it when the reader returns to the default.
	 *
	 * @param {string} slug Variation slug, or an empty string.
	 * @return {void}
	 */
	function remember( slug ) {
		try {
			if ( slug ) {
				window.localStorage.setItem( data.key, slug );
			} else {
				window.localStorage.removeItem( data.key );
			}
		} catch ( error ) {
			/* A reader who blocks storage still gets the switch for this page. */
		}
	}

	/**
	 * Apply a variation by setting the attribute the generated stylesheet keys on.
	 *
	 * @param {string} slug Variation slug, or an empty string for the default.
	 * @return {void}
	 */
	function apply( slug ) {
		if ( slug ) {
			root.setAttribute( 'data-adz-style', slug );
		} else {
			root.removeAttribute( 'data-adz-style' );
		}
	}

	var known   = data.variations.map(
		function ( variation ) {
			return variation.slug;
		}
	);
	var current = stored();

	if ( current && known.indexOf( current ) === -1 ) {
		current = '';
		remember( '' );
	}

	var id    = 'adz-style-switcher';
	var label = document.createElement( 'label' );

	label.className = 'adz-switcher__label';
	label.setAttribute( 'for', id );
	label.textContent = data.label;

	var select = document.createElement( 'select' );

	select.className = 'adz-switcher__select';
	select.id        = id;

	var fallback = document.createElement( 'option' );

	fallback.value       = '';
	fallback.textContent = data.defaultLabel;
	select.appendChild( fallback );

	data.variations.forEach(
		function ( variation ) {
			var option = document.createElement( 'option' );

			option.value       = variation.slug;
			option.textContent = variation.title;
			select.appendChild( option );
		}
	);

	select.value = current;

	select.addEventListener(
		'change',
		function () {
			apply( select.value );
			remember( select.value );
		}
	);

	mount.appendChild( label );
	mount.appendChild( select );
	mount.hidden = false;
}() );
