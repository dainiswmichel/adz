/**
 * Local rotation navigation and explicit preference capture.
 *
 * @package adz-world
 */

( function () {
	'use strict';

	document.querySelectorAll( '.adzworld-rotation' ).forEach(
		function ( rotation ) {
			var slides     = Array.from( rotation.querySelector( '.adzworld-slides' ).children );
			var navigation = rotation.querySelector( '.adzworld-navigation' );
			var current    = 0;
			var depth      = rotation.classList.contains( 'adzworld-rotation--3d' ) && slides.length > 1;
			rotation.classList.add( 'adzworld-enhanced' );

			function show( position ) {
					current = ( position + slides.length ) % slides.length;
					slides.forEach(
						function ( slide, index ) {
							var offset = ( index - current + slides.length ) % slides.length;
							if ( offset > slides.length / 2 ) {
								offset -= slides.length;
							}
							slide.hidden = ! depth && index !== current;
							slide.classList.toggle( 'adzworld-active', index === current );
							slide.setAttribute( 'aria-hidden', String( index !== current ) );
							slide.inert = index !== current;
							slide.style.setProperty( '--adzworld-offset', offset );
						}
					);
					rotation.querySelectorAll( '[data-adzworld-position]' ).forEach(
						function ( button ) {
							button.setAttribute( 'aria-pressed', String( Number( button.dataset.adzworldPosition ) === current ) );
						}
					);
			}

			rotation.addEventListener(
				'click',
				function ( event ) {
					var button = event.target.closest( 'button' );
					if ( ! button ) {
						return;
					}
					if ( button.hasAttribute( 'data-adzworld-step' ) ) {
							show( current + Number( button.dataset.adzworldStep ) );
					}
					if ( button.hasAttribute( 'data-adzworld-position' ) ) {
							show( Number( button.dataset.adzworldPosition ) );
					}
					if ( button.classList.contains( 'adzworld-clear-preference' ) ) {
							var form = button.closest( 'form' );
							form.querySelector( '.adzworld-preference-history' ).replaceChildren();
							form.querySelector( 'textarea' ).value                          = '';
							form.querySelector( '.adzworld-preference-status' ).textContent = form.dataset.cleared;
					}
				}
			);
			navigation.addEventListener(
				'keydown',
				function ( event ) {
					var positions = { ArrowLeft: current - 1, ArrowRight: current + 1, Home: 0, End: slides.length - 1 };
					if ( Object.prototype.hasOwnProperty.call( positions, event.key ) ) {
						event.preventDefault();
						show( positions[ event.key ] );
					}
				}
			);
			rotation.querySelectorAll( '.adzworld-preference' ).forEach(
				function ( form ) {
					form.querySelector( 'button[type="submit"]' ).disabled = false;
					form.addEventListener(
						'submit',
						function ( event ) {
							event.preventDefault();
							var input   = form.querySelector( 'textarea' );
							var text    = input.value.trim();
							var history = form.querySelector( '.adzworld-preference-history' );
							var status  = form.querySelector( '.adzworld-preference-status' );
							if ( ! text ) {
								input.focus();
								return;
							}
							if ( history.children.length >= 10 ) {
									status.textContent = form.dataset.limit;
									return;
							}
							var item         = document.createElement( 'li' );
							item.textContent = text;
							history.appendChild( item );
							status.textContent = form.dataset.noted;
							input.value        = '';
						}
					);
				}
			);
			show( 0 );
			navigation.hidden = slides.length < 2 || ! navigation.children.length;
		}
	);
}() );
