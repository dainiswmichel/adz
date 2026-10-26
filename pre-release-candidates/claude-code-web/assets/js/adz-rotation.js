/*
 * Rotation element and permission panel.
 *
 * No framework and no jQuery, so the script is registered with no dependencies
 * and adds nothing to a page that is not already there.
 *
 * The one rule the whole file is built around: the publisher's adz must render
 * whether or not this script runs. Every slide is already in the markup and
 * visible; the script adds "is-ready" and takes over presentation only once it
 * has successfully started. If it throws, the page keeps a plain stacked list
 * of working adz rather than an empty box.
 */

( function () {
	'use strict';

	var settings = window.adzWorldRotation || {};
	var reduceMotion = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	/**
	 * One rotation element.
	 *
	 * @param {HTMLElement} root Rotation container.
	 */
	function Rotation( root ) {
		this.root = root;
		this.track = root.querySelector( '.adz-rotation__track' );
		this.slides = [].slice.call( root.querySelectorAll( '.adz-rotation__slide' ) );
		this.dots = [].slice.call( root.querySelectorAll( '.adz-rotation__dot' ) );
		this.index = 0;
		this.timer = null;
		this.interval = parseInt( root.getAttribute( 'data-adz-interval' ), 10 ) || 0;

		if ( ! this.track || this.slides.length === 0 ) {
			return;
		}

		this.bind();
		root.classList.add( 'is-ready' );
		this.show( 0 );
		this.start();
	}

	Rotation.prototype.bind = function () {
		var self = this;

		var prev = this.root.querySelector( '.adz-rotation__arrow--prev' );
		var next = this.root.querySelector( '.adz-rotation__arrow--next' );

		if ( prev ) {
			prev.addEventListener( 'click', function () {
				self.stop();
				self.show( self.index - 1 );
			} );
		}

		if ( next ) {
			next.addEventListener( 'click', function () {
				self.stop();
				self.show( self.index + 1 );
			} );
		}

		this.dots.forEach( function ( dot ) {
			dot.addEventListener( 'click', function () {
				self.stop();
				self.show( parseInt( dot.getAttribute( 'data-adz-index' ), 10 ) || 0 );
			} );
		} );

		// Arrow keys move the rotation when it holds focus, not while the
		// visitor is typing a refinement into the panel.
		this.root.addEventListener( 'keydown', function ( event ) {
			if ( event.target.closest( '.adz-panel' ) ) {
				return;
			}

			if ( event.key === 'ArrowLeft' ) {
				self.stop();
				self.show( self.index - 1 );
			} else if ( event.key === 'ArrowRight' ) {
				self.stop();
				self.show( self.index + 1 );
			}
		} );

		// Auto-advance stops while the visitor is looking at or using it.
		[ 'mouseenter', 'focusin' ].forEach( function ( name ) {
			self.root.addEventListener( name, function () {
				self.stop();
			} );
		} );

		this.root.addEventListener( 'mouseleave', function () {
			self.start();
		} );
	};

	/**
	 * Move to a slide, wrapping at both ends.
	 *
	 * @param {number} target Requested index.
	 */
	Rotation.prototype.show = function ( target ) {
		var total = this.slides.length;
		var self = this;

		this.index = ( ( target % total ) + total ) % total;

		this.slides.forEach( function ( slide, i ) {
			var offset = i - self.index;

			slide.style.setProperty( '--adz-offset', String( offset ) );
			slide.style.setProperty( '--adz-distance', String( Math.abs( offset ) ) );

			if ( i === self.index ) {
				slide.removeAttribute( 'aria-hidden' );
			} else {
				slide.setAttribute( 'aria-hidden', 'true' );
			}
		} );

		if ( this.root.classList.contains( 'adz-rotation--carousel-flat' ) ) {
			this.track.style.transform = 'translateX(calc(' + ( -100 * this.index ) + '% - ' +
				( this.index ) + ' * var(--adz-gap)))';
		}

		this.dots.forEach( function ( dot, i ) {
			dot.setAttribute( 'aria-selected', i === self.index ? 'true' : 'false' );
		} );
	};

	Rotation.prototype.start = function () {
		if ( this.interval <= 0 || reduceMotion || this.slides.length < 2 ) {
			return;
		}

		var self = this;

		this.stop();

		this.timer = window.setInterval( function () {
			self.show( self.index + 1 );
		}, this.interval * 1000 );
	};

	Rotation.prototype.stop = function () {
		if ( this.timer ) {
			window.clearInterval( this.timer );
			this.timer = null;
		}
	};

	/**
	 * Post to the refinement endpoint.
	 *
	 * @param {HTMLFormElement} form Panel form.
	 * @param {Object}          data Fields to send.
	 */
	function send( form, data ) {
		if ( ! settings.ajaxUrl || ! settings.nonce ) {
			return;
		}

		var status = form.querySelector( '[data-adz-status]' );
		var submit = form.querySelector( '.adz-panel__submit' );
		var body = new window.FormData();

		body.append( 'action', 'adzworld_refine' );
		body.append( 'nonce', settings.nonce );

		Object.keys( data ).forEach( function ( key ) {
			body.append( key, data[ key ] );
		} );

		form.classList.add( 'is-busy' );

		if ( submit ) {
			submit.disabled = true;
		}

		window.fetch( settings.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: body
		} ).then( function ( response ) {
			return response.json();
		} ).then( function ( payload ) {
			var chips = form.querySelector( '[data-adz-chips]' );

			if ( payload && payload.success ) {
				if ( chips ) {
					chips.innerHTML = payload.data.markup || '';
				}

				if ( status ) {
					status.textContent = payload.data.message || '';
				}

				form.dispatchEvent( new window.CustomEvent( 'adzworld:refined', {
					bubbles: true,
					detail: payload.data
				} ) );
			} else if ( status ) {
				status.textContent = ( payload && payload.data && payload.data.message ) || '';
			}
		} ).catch( function () {
			if ( status ) {
				// Deliberately plain: a failed refinement is not an error the
				// visitor caused, and the adz on the page are unaffected.
				status.textContent = '';
			}
		} ).then( function () {
			form.classList.remove( 'is-busy' );

			if ( submit ) {
				submit.disabled = false;
			}
		} );
	}

	/**
	 * Wire a permission panel.
	 *
	 * @param {HTMLFormElement} form Panel form.
	 */
	function panel( form ) {
		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();

			var input = form.querySelector( '.adz-panel__input' );

			if ( ! input || input.value.trim() === '' ) {
				return;
			}

			send( form, { mode: 'refine', refinement: input.value } );
			input.value = '';
		} );

		// Chips are replaced wholesale after every change, so removal is bound
		// on the form rather than on each button.
		form.addEventListener( 'click', function ( event ) {
			var drop = event.target.closest( '.adz-panel__chipdrop' );

			if ( ! drop ) {
				return;
			}

			event.preventDefault();

			send( form, {
				mode: 'drop',
				facet: drop.getAttribute( 'data-adz-facet' ) || '',
				value: drop.getAttribute( 'data-adz-value' ) || ''
			} );
		} );
	}

	/**
	 * Start everything present on the page.
	 */
	function init() {
		if ( ! window.fetch || ! window.FormData ) {
			// The panel needs fetch; the adz do not. Leave the markup alone.
			return;
		}

		[].slice.call( document.querySelectorAll( '.adz-rotation' ) ).forEach( function ( root ) {
			try {
				new Rotation( root );
			} catch ( error ) {
				root.classList.remove( 'is-ready' );
			}
		} );

		[].slice.call( document.querySelectorAll( '[data-adz-panel]' ) ).forEach( panel );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
