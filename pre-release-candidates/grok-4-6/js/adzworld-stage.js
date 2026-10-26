/**
 * Stage controls. Network units mount when their slide is the visible one.
 *
 * @package adz-world
 */

( function () {
	'use strict';

	/**
	 * Wrap an index.
	 *
	 * @param {number} value Index.
	 * @param {number} count Slide count.
	 * @return {number} Wrapped index.
	 */
	function wrap( value, count ) {
		return ( ( value % count ) + count ) % count;
	}

	/**
	 * Push one AdSense unit.
	 *
	 * @param {HTMLElement} ins Unit.
	 */
	function pushAdsense( ins ) {
		if ( '1' === ins.getAttribute( 'data-adzworld-pushed' ) ) {
			return;
		}

		ins.setAttribute( 'data-adzworld-pushed', '1' );

		try {
			( window.adsbygoogle = window.adsbygoogle || [] ).push( {} );
		} catch ( error ) {
			ins.setAttribute( 'data-adzworld-error', '1' );
		}
	}

	/**
	 * Mount the network unit inside a slide, once.
	 *
	 * @param {HTMLElement} slide Slide.
	 */
	function mount( slide ) {
		var unit = slide.querySelector( '[data-adzworld-mount]' );

		if ( ! unit || '1' === unit.getAttribute( 'data-adzworld-mounted' ) ) {
			if ( unit && 'INS' === unit.tagName ) {
				pushAdsense( unit );
			}
			return;
		}

		var kind = unit.getAttribute( 'data-adzworld-mount' );

		if ( 'adsense' === kind ) {
			mountAdsense( unit );
		}

		if ( 'gam' === kind ) {
			mountGam( unit );
		}

		if ( 'taboola' === kind ) {
			mountTaboola( unit );
		}
	}

	/**
	 * Build the AdSense ins element when the slide was not the first one.
	 *
	 * @param {HTMLElement} unit Placeholder.
	 */
	function mountAdsense( unit ) {
		var ins;

		if ( 'INS' === unit.tagName ) {
			pushAdsense( unit );
			unit.setAttribute( 'data-adzworld-mounted', '1' );
			return;
		}

		ins           = document.createElement( 'ins' );
		ins.className = 'adsbygoogle';
		ins.setAttribute( 'data-ad-client', unit.getAttribute( 'data-client' ) || '' );
		ins.setAttribute( 'data-ad-slot', unit.getAttribute( 'data-slot' ) || '' );
		ins.setAttribute( 'data-ad-format', 'auto' );
		ins.setAttribute( 'data-full-width-responsive', 'true' );
		ins.style.display = 'block';
		unit.appendChild( ins );
		unit.setAttribute( 'data-adzworld-mounted', '1' );
		pushAdsense( ins );
	}

	/**
	 * Ask Google Publisher Tag to fill one div.
	 *
	 * @param {HTMLElement} unit Slot.
	 */
	function mountGam( unit ) {
		var width  = parseInt( unit.getAttribute( 'data-width' ), 10 );
		var height = parseInt( unit.getAttribute( 'data-height' ), 10 );
		var path   = unit.getAttribute( 'data-path' );

		if ( ! window.googletag || ! window.googletag.cmd || ! path ) {
			return;
		}

		unit.setAttribute( 'data-adzworld-mounted', '1' );
		window.googletag.cmd.push(
			function () {
				window.googletag.defineSlot( path, [ width, height ], unit.id ).addService( window.googletag.pubads() );

				if ( ! window.adzworldGamServices ) {
						window.googletag.enableServices();
						window.adzworldGamServices = true;
				}

				window.googletag.display( unit.id );
			}
		);
	}

	/**
	 * Load the Taboola loader when its slide is shown.
	 *
	 * @param {HTMLElement} unit Widget container.
	 */
	function mountTaboola( unit ) {
		var publisher = unit.getAttribute( 'data-publisher' ) || '';
		var base      = ( window.adzworldStage && window.adzworldStage.taboolaBase ) ? window.adzworldStage.taboolaBase : '';
		var script;

		if ( ! /^[a-z0-9][a-z0-9-]{1,48}$/.test( publisher ) || 'https://cdn.taboola.com/libtrc/' !== base ) {
			return;
		}

		unit.setAttribute( 'data-adzworld-mounted', '1' );
		window._taboola = window._taboola || [];
		window._taboola.push(
			{
				mode: unit.getAttribute( 'data-mode' ) || 'thumbnails-a',
				container: unit.id,
				placement: unit.getAttribute( 'data-placement' ) || '',
				target_type: 'mix'
			}
		);

		if ( document.getElementById( 'adzworld-taboola-loader' ) ) {
			return;
		}

		script       = document.createElement( 'script' );
		script.id    = 'adzworld-taboola-loader';
		script.async = true;
		script.src   = base + encodeURIComponent( publisher ) + '/loader.js';
		document.head.appendChild( script );
	}

	/**
	 * Show one slide and mount its unit.
	 *
	 * @param {HTMLElement}   stage  Stage.
	 * @param {NodeList}      slides Slides.
	 * @param {number}        index  Active index.
	 */
	function show( stage, slides, index ) {
		var display  = stage.getAttribute( 'data-display' );
		var track    = stage.querySelector( '.adzworld-stage__track' );
		var dots     = stage.querySelectorAll( '.adzworld-stage__dot' );
		var count    = slides.length;
		var dotCount = dots.length;
		var i;

		for ( i = 0; i < count; i++ ) {
			slides[ i ].classList.toggle( 'is-active', i === index );
			slides[ i ].classList.toggle( 'is-prev', count > 1 && i === wrap( index - 1, count ) );
			slides[ i ].classList.toggle( 'is-next', count > 1 && i === wrap( index + 1, count ) );

			if ( i === index ) {
				mount( slides[ i ] );
			}
		}

		if ( 'flat' === display && track ) {
			track.style.transform = 'translateX(' + ( -100 * index ) + '%)';
		}

		for ( i = 0; i < dotCount; i++ ) {
			dots[ i ].classList.toggle( 'is-active', i === index );

			if ( i === index ) {
				dots[ i ].setAttribute( 'aria-current', 'true' );
			} else {
				dots[ i ].removeAttribute( 'aria-current' );
			}
		}
	}

	/**
	 * Wire one stage.
	 *
	 * @param {HTMLElement} stage Stage.
	 */
	function boot( stage ) {
		var slides     = stage.querySelectorAll( '.adzworld-stage__slide' );
		var state      = { index: 0 };
		var prev       = stage.querySelector( '.adzworld-stage__prev' );
		var next       = stage.querySelector( '.adzworld-stage__next' );
		var dots       = stage.querySelectorAll( '.adzworld-stage__dot' );
		var slideCount = slides.length;
		var dotCount   = dots.length;
		var i;

		if ( ! slideCount ) {
			return;
		}

		for ( i = 0; i < slideCount; i++ ) {
			if ( slides[ i ].classList.contains( 'is-active' ) ) {
				state.index = i;
			}
		}

		stage.classList.add( 'is-ready' );
		show( stage, slides, state.index );

		if ( prev ) {
			prev.addEventListener(
				'click',
				function () {
					state.index = wrap( state.index - 1, slides.length );
					show( stage, slides, state.index );
				}
			);
		}

		if ( next ) {
			next.addEventListener(
				'click',
				function () {
					state.index = wrap( state.index + 1, slides.length );
					show( stage, slides, state.index );
				}
			);
		}

		for ( i = 0; i < dotCount; i++ ) {
			dots[ i ].addEventListener(
				'click',
				function () {
					var index = parseInt( this.getAttribute( 'data-adzworld-index' ), 10 );

					if ( isNaN( index ) ) {
						return;
					}

					state.index = index;
					show( stage, slides, state.index );
				}
			);
		}

		stage.addEventListener(
			'keydown',
			function ( event ) {
				var tag = event.target && event.target.tagName;

				if ( 'TEXTAREA' === tag || 'INPUT' === tag ) {
					return;
				}

				if ( 'ArrowLeft' === event.key ) {
					state.index = wrap( state.index - 1, slides.length );
					show( stage, slides, state.index );
				}

				if ( 'ArrowRight' === event.key ) {
					state.index = wrap( state.index + 1, slides.length );
					show( stage, slides, state.index );
				}
			}
		);
	}

	document.addEventListener(
		'DOMContentLoaded',
		function () {
			var stages     = document.querySelectorAll( '[data-adzworld-stage]' );
			var stageCount = stages.length;
			var i;

			for ( i = 0; i < stageCount; i++ ) {
				boot( stages[ i ] );
			}
		}
	);

	document.addEventListener(
		'click',
		function ( event ) {
			var button = event.target;
			var form;
			var field;

			while ( button && button !== document && ! button.getAttribute( 'data-adzworld-example' ) ) {
				button = button.parentNode;
			}

			if ( ! button || button === document ) {
				return;
			}

			form = button.parentNode;

			while ( form && form !== document && 'FORM' !== form.tagName ) {
				form = form.parentNode;
			}

			if ( ! form || 'FORM' !== form.tagName ) {
				return;
			}

			field = form.querySelector( 'textarea[name="adzworld_intent"]' );

			if ( ! field ) {
				return;
			}

			field.value = button.getAttribute( 'data-adzworld-example' ) || '';
			field.focus();
		}
	);
}() );
