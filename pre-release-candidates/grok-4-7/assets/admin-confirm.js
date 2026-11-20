/**
 * Ask "are you sure" before an adzadmin overrides a reader's choice or deletes
 * every journey.
 *
 * data-adzworld-confirm            On a button: confirm before it submits.
 * data-adzworld-confirm-unchecked  On a checkbox: confirm when it was on and is now off.
 * data-adzworld-confirm-change     On a checkbox: confirm when it differs from how it loaded.
 *
 * @package adz-world
 */

( function () {
	'use strict';

	var submitter = null;

	document.addEventListener(
		'click',
		function ( event ) {
			submitter = event.target && event.target.closest ? event.target.closest( 'button, input[type="submit"]' ) : null;
		},
		true
	);

	document.addEventListener(
		'submit',
		function ( event ) {
			var form = event.target;
			var button = event.submitter || submitter;
			var messages = [];

			if ( button && button.dataset.adzworldConfirm ) {
				messages.push( button.dataset.adzworldConfirm );
			}
			// form.elements includes fields placed outside the form with a form attribute.
			Array.from( form.elements ).forEach(
				function ( field ) {
					if ( 'checkbox' !== field.type ) {
						return;
					}
					if ( field.dataset.adzworldConfirmUnchecked && field.defaultChecked && ! field.checked ) {
						messages.push( field.dataset.adzworldConfirmUnchecked );
					}
					if ( field.dataset.adzworldConfirmChange && field.defaultChecked !== field.checked ) {
						messages.push( field.dataset.adzworldConfirmChange );
					}
				}
			);
			if ( messages.length && ! window.confirm( messages.join( '\n\n' ) ) ) {
				event.preventDefault();
			}
		},
		true
	);
}() );
