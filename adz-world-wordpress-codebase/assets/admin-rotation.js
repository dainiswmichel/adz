/**
 * Vertical administrator rotation editor and live slot previews.
 *
 * @package adz-world
 */

( function () {
	'use strict';

	var settings = window.adzworldAdminRotation || {};
	var list = document.querySelector( '[data-adzworld-admin-slots]' );
	var template = document.getElementById( 'adzworld-slot-template' );

	if ( ! list || ! template || ! window.fetch || ! window.FormData ) {
		return;
	}

	function renumber() {
		list.querySelectorAll( '[data-adzworld-admin-slot]' ).forEach(
			function ( row, index ) {
				var number = row.querySelector( '[data-adzworld-slot-number]' );

				if ( number ) {
					number.textContent = 'Slot ' + String( index + 1 );
				}
			}
		);
	}

	function mountProvider( row ) {
		row.dispatchEvent(
			new window.CustomEvent(
				'adzworld:mount-network',
				{
					bubbles: true,
					detail: { root: row }
				}
			)
		);
	}

	function refresh( row ) {
		var select = row.querySelector( '[data-adzworld-slot-select]' );
		var body = row.querySelector( '[data-adzworld-slot-preview-body]' );
		var button = row.querySelector( '[data-adzworld-refresh-slot]' );
		var spinner = row.querySelector( '[data-adzworld-slot-spinner]' );
		var request = new window.FormData();

		if ( ! select || ! body || ! button ) {
			return;
		}
		request.append( 'action', 'adzworld_refresh_rotation_slot' );
		request.append( 'nonce', settings.nonce || '' );
		request.append( 'slot', select.value );
		button.disabled = true;
		button.textContent = settings.labels.loading || 'Refreshing…';
		if ( spinner ) {
			spinner.classList.add( 'is-active' );
		}

		window.fetch(
			settings.ajaxUrl,
			{
				body: request,
				credentials: 'same-origin',
				method: 'POST'
			}
		).then(
			function ( response ) {
				return response.json();
			}
		).then(
			function ( payload ) {
				if ( ! payload || ! payload.success || ! payload.data || ! payload.data.html ) {
					throw new Error( 'preview' );
				}
				body.innerHTML = payload.data.html;
				mountProvider( row );
			}
		).catch(
			function () {
				body.textContent = settings.labels.failed || 'The preview could not be refreshed.';
			}
		).finally(
			function () {
				button.disabled = false;
				button.textContent = settings.labels.refresh || 'Refresh this adz preview';
				if ( spinner ) {
					spinner.classList.remove( 'is-active' );
				}
			}
		);
	}

	list.addEventListener(
		'click',
		function ( event ) {
			var row = event.target.closest( '[data-adzworld-admin-slot]' );

			if ( ! row ) {
				return;
			}
			if ( event.target.closest( '[data-adzworld-refresh-slot]' ) ) {
				refresh( row );
			} else if ( event.target.closest( '[data-adzworld-slot-remove]' ) ) {
				if ( 1 < list.querySelectorAll( '[data-adzworld-admin-slot]' ).length ) {
					row.remove();
					renumber();
				}
			} else if ( event.target.closest( '[data-adzworld-slot-up]' ) && row.previousElementSibling ) {
				list.insertBefore( row, row.previousElementSibling );
				renumber();
			} else if ( event.target.closest( '[data-adzworld-slot-down]' ) && row.nextElementSibling ) {
				list.insertBefore( row.nextElementSibling, row );
				renumber();
			}
		}
	);

	document.querySelector( '[data-adzworld-add-slot]' ).addEventListener(
		'click',
		function () {
			var row;

			if ( list.querySelectorAll( '[data-adzworld-admin-slot]' ).length >= Number( settings.maxSlots || 200 ) ) {
				return;
			}
			row = template.content.firstElementChild.cloneNode( true );
			list.appendChild( row );
			renumber();
			mountProvider( row );
		}
	);

	list.querySelectorAll( '[data-adzworld-admin-slot]' ).forEach( mountProvider );
	renumber();
}() );
