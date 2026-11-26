/**
 * WordPress formatting toolbars and ordering for Display messages.
 *
 * @package adz-world
 */

( function () {
	'use strict';

	const editor = document.querySelector( '[data-adzworld-audience-editor]' );
	if ( ! editor ) {
		return;
	}

	let nextIndex = 0;
	const addToolbar = ( textarea ) => {
		window.quicktags( { id: textarea.id, buttons: 'strong,em,link,ul,ol,li,block' } );
	};
	const updateButtons = ( rows ) => {
		Array.from( rows.children ).forEach( ( row, index ) => {
			row.querySelector( '[data-adzworld-message-move="up"]' ).disabled = index === 0;
			row.querySelector( '[data-adzworld-message-move="down"]' ).disabled = index === rows.children.length - 1;
		} );
		rows.parentElement.querySelector( '[data-adzworld-message-add]' ).disabled = rows.children.length >= 20;
	};

	editor.querySelectorAll( '[data-adzworld-message-rows]' ).forEach( ( rows ) => {
		rows.querySelectorAll( 'textarea' ).forEach( addToolbar );
		updateButtons( rows );
	} );
	editor.addEventListener( 'click', ( event ) => {
		const button = event.target.closest( '[data-adzworld-message-add], [data-adzworld-message-remove], [data-adzworld-message-move]' );
		if ( ! button ) {
			return;
		}
		const list = button.closest( '[data-adzworld-audience-list]' );
		const rows = list.querySelector( '[data-adzworld-message-rows]' );
		const row = button.closest( '[data-adzworld-message-row]' );

		if ( button.hasAttribute( 'data-adzworld-message-add' ) ) {
			const fragment = list.querySelector( 'template' ).content.cloneNode( true );
			const index = 'new-' + nextIndex++;
			fragment.querySelectorAll( '[id], [for], [name]' ).forEach( ( element ) => {
				[ 'id', 'for', 'name' ].forEach( ( attribute ) => {
					if ( element.hasAttribute( attribute ) ) {
						element.setAttribute( attribute, element.getAttribute( attribute ).replace( '__index__', index ) );
					}
				} );
			} );
			const textarea = fragment.querySelector( 'textarea' );
			rows.append( fragment );
			addToolbar( textarea );
			textarea.focus();
		} else if ( button.hasAttribute( 'data-adzworld-message-remove' ) ) {
			const next = row.nextElementSibling || row.previousElementSibling;
			delete window.QTags.instances[ row.querySelector( 'textarea' ).id ];
			row.remove();
			( next ? next.querySelector( 'textarea' ) : list.querySelector( '[data-adzworld-message-add]' ) ).focus();
		} else if ( 'up' === button.dataset.adzworldMessageMove && row.previousElementSibling ) {
			rows.insertBefore( row, row.previousElementSibling );
			row.querySelector( 'textarea' ).focus();
		} else if ( 'down' === button.dataset.adzworldMessageMove && row.nextElementSibling ) {
			rows.insertBefore( row.nextElementSibling, row );
			row.querySelector( 'textarea' ).focus();
		}
		updateButtons( rows );
	} );
}() );
