/**
 * Invitation-first carousel, preference refinement, and adz evaluation.
 *
 * @package adz-world
 */

( function () {
	'use strict';

	var settings = window.adzworldRotation || {};

	/**
	 * Push one AdSense unit only once.
	 *
	 * @param {HTMLElement} unit AdSense element.
	 */
	function pushAdsense( unit ) {
		if ( '1' === unit.dataset.adzworldPushed ) {
			return;
		}
		unit.dataset.adzworldPushed = '1';
		try {
			( window.adsbygoogle = window.adsbygoogle || [] ).push( {} );
		} catch ( error ) {
			unit.dataset.adzworldError = error && error.message ? String( error.message ).slice( 0, 180 ) : '1';
		}
	}

	/**
	 * Mark a unit mounted and run its provider call.
	 *
	 * @param {HTMLElement} unit    Network element.
	 * @param {Function}    ready   Returns true when the provider can be called.
	 * @param {Function}    display Provider call.
	 */
	function mountWhenReady( unit, ready, display ) {
		if ( ! ready() ) {
			return;
		}
		unit.dataset.adzworldMounted = '1';
		display();
	}

	/**
	 * Mount an enabled publisher ad-network unit when its slide becomes active.
	 *
	 * @param {HTMLElement} slide Active carousel slide.
	 */
	function mountNetworkUnit( slide ) {
		var unit = slide.querySelector( '[data-adzworld-mount]' );
		var kind;
		var script;

		if ( ! unit || '1' === unit.dataset.adzworldMounted ) {
			return;
		}
		kind = unit.dataset.adzworldMount || '';

		if ( 'adsense' === kind ) {
			var ins = document.createElement( 'ins' );

			ins.className = 'adsbygoogle';
			ins.setAttribute( 'data-ad-client', unit.dataset.client || '' );
			ins.setAttribute( 'data-ad-slot', unit.dataset.slot || '' );
			ins.setAttribute( 'data-ad-format', 'auto' );
			ins.setAttribute( 'data-full-width-responsive', 'true' );
			unit.appendChild( ins );
			unit.dataset.adzworldMounted = '1';
			pushAdsense( ins );
			return;
		}

		if ( 'gam' === kind ) {
			window.googletag = window.googletag || { cmd: [] };
			unit.dataset.adzworldMounted = '1';
			window.googletag.cmd.push(
				function () {
					window.googletag.defineSlot(
						unit.dataset.path || '',
						[ Number( unit.dataset.width ), Number( unit.dataset.height ) ],
						unit.id
					).addService( window.googletag.pubads() );
					if ( ! window.adzworldGamServices ) {
						window.googletag.enableServices();
						window.adzworldGamServices = true;
					}
					window.googletag.display( unit.id );
				}
			);
			return;
		}

		if ( 'taboola' === kind && 'https://cdn.taboola.com/libtrc/' === settings.taboolaBase &&
			/^[a-z0-9][a-z0-9-]{1,48}$/.test( unit.dataset.publisher || '' ) ) {
			unit.dataset.adzworldMounted = '1';
			window._taboola = window._taboola || [];
			window._taboola.push(
				{
					container: unit.id,
					mode: unit.dataset.mode || 'thumbnails-a',
					placement: unit.dataset.placement || '',
					target_type: 'mix'
				}
			);
			if ( ! document.getElementById( 'adzworld-taboola-loader' ) ) {
				script = document.createElement( 'script' );
				script.id = 'adzworld-taboola-loader';
				script.async = true;
				script.src = settings.taboolaBase + encodeURIComponent( unit.dataset.publisher ) + '/loader.js';
				document.head.appendChild( script );
			}
			return;
		}

		if ( 'outbrain' === kind ) {
			unit.dataset.adzworldMounted = '1';
			if ( window.OBR && window.OBR.extern && 'function' === typeof window.OBR.extern.researchWidget ) {
				window.OBR.extern.researchWidget();
			}
			return;
		}

		if ( 'medianet' === kind && /^[0-9]{5,15}$/.test( unit.dataset.crid || '' ) && unit.id ) {
			mountWhenReady(
				unit,
				function () {
					return window._mNHandle && window._mNHandle.queue;
				},
				function () {
					window._mNHandle.queue.push(
						function () {
							if ( window._mNDetails && 'function' === typeof window._mNDetails.loadTag ) {
								window._mNDetails.loadTag( unit.dataset.crid, unit.dataset.width + 'x' + unit.dataset.height, unit.id );
							}
						}
					);
				}
			);
			return;
		}

		if ( 'amazonaps' === kind && unit.id && /^[A-Za-z0-9][A-Za-z0-9._~/-]{0,120}$/.test( unit.dataset.slotname || '' ) ) {
			mountWhenReady(
				unit,
				function () {
					return window.apstag && 'function' === typeof window.apstag.init && 'function' === typeof window.apstag.fetchBids;
				},
				function () {
					if ( ! window.adzworldApsReady ) {
						window.apstag.init(
							{
								pubID: unit.dataset.pub || '',
								adServer: 'googletag'
							}
						);
						window.adzworldApsReady = true;
					}
					window.apstag.fetchBids(
						{
							slots: [
								{
									slotID: unit.id,
									slotName: unit.dataset.slotname,
									sizes: [ [ Number( unit.dataset.width ), Number( unit.dataset.height ) ] ]
								}
							],
							timeout: 2000
						},
						function () {
							if ( 'function' === typeof window.apstag.setDisplayBids ) {
								window.apstag.setDisplayBids();
							}
						}
					);
				}
			);
			return;
		}

		if ( 'ezoic' === kind && /^[0-9]{1,6}$/.test( unit.dataset.placeholder || '' ) ) {
			window.ezstandalone = window.ezstandalone || {};
			window.ezstandalone.cmd = window.ezstandalone.cmd || [];
			unit.dataset.adzworldMounted = '1';
			window.ezstandalone.cmd.push(
				function () {
					if ( 'function' === typeof window.ezstandalone.showAds ) {
						window.ezstandalone.showAds( Number( unit.dataset.placeholder ) );
					}
				}
			);
			return;
		}

		if ( 'mgid' === kind ) {
			unit.dataset.adzworldMounted = '1';
			window._mgq = window._mgq || [];
			window._mgq.push( [ '_mgc.load' ] );
			return;
		}

		if ( 'carbon' === kind && /^[A-Z0-9]{8,10}$/.test( unit.dataset.serve || '' ) &&
			/^[a-z0-9][a-z0-9-]{1,40}$/.test( unit.dataset.placement || '' ) ) {
			script = document.createElement( 'script' );
			script.async = true;
			script.id = '_carbonads_js';
			script.src = 'https://cdn.carbonads.com/carbon.js?serve=' + encodeURIComponent( unit.dataset.serve ) + '&placement=' + encodeURIComponent( unit.dataset.placement );
			unit.dataset.adzworldMounted = '1';
			unit.appendChild( script );
			return;
		}

		if ( 'criteo' === kind && /^[0-9]{4,12}$/.test( unit.dataset.zone || '' ) && unit.id ) {
			window.Criteo = window.Criteo || {};
			window.Criteo.events = window.Criteo.events || [];
			unit.dataset.adzworldMounted = '1';
			window.Criteo.events.push(
				function () {
					if ( 'function' === typeof window.Criteo.DisplayAcceptableAdIfAdblocked ) {
						window.Criteo.DisplayAcceptableAdIfAdblocked(
							{
								zoneid: Number( unit.dataset.zone ),
								containerid: unit.id,
								overrideZoneFloor: false
							}
						);
					}
				}
			);
			return;
		}

		if ( 'teads' === kind || 'mediavine' === kind || 'raptive' === kind || 'monumetric' === kind ||
			'revcontent' === kind || 'dianomi' === kind || 'snigel' === kind || 'freestar' === kind ) {
			unit.dataset.adzworldMounted = '1';
		}
	}

	/**
	 * Rotation, slot, and outer slot for the slide that holds this node.
	 *
	 * A response is only useful in AdzLogs if it says where it was given, so
	 * every request that records one carries the place it came from.
	 *
	 * @param {HTMLElement} node Control inside a rotation.
	 * @return {Object} Place fields.
	 */
	function placeFields( node ) {
		var rotation = node ? node.closest( '.adzworld-rotation' ) : null;
		var slide = node ? node.closest( '.adzworld-slide' ) : null;
		var fields = {};

		if ( ! rotation ) {
			return fields;
		}

		fields.rotation = rotation.dataset.adzworldRotation || '';

		if ( slide && slide.dataset.adzworldSlot ) {
			fields.slot = slide.dataset.adzworldSlot;
		}

		if ( rotation.dataset.adzworldOuterRotation ) {
			fields.outer_rotation = rotation.dataset.adzworldOuterRotation;
			fields.outer_slot = rotation.dataset.adzworldOuterSlot || '0';
		}

		/*
		 * Which products this slide is showing. The response arrives in its
		 * own request, by which time the server no longer holds the list it
		 * built while rendering, so the page reports what it has on screen.
		 * Without this the round a reader's first input answers records the
		 * provider and not the product.
		 */
		if ( slide ) {
			var items = [];

			slide.querySelectorAll( '[data-adzworld-item]' ).forEach(
				function ( card ) {
					var id = card.dataset.adzworldItem || '';

					if ( id && -1 === items.indexOf( id ) ) {
						items.push( id );
					}
				}
			);

			if ( items.length ) {
				fields.items = items.join( ' ' );
			}
		}

		return fields;
	}

	/**
	 * Copy place fields onto a request.
	 *
	 * @param {Object}      values Request values.
	 * @param {HTMLElement} node   Control inside a rotation.
	 * @return {Object} Request values.
	 */
	function assignPlace( values, node ) {
		var place = placeFields( node );

		Object.keys( place ).forEach(
			function ( key ) {
				values[ key ] = place[ key ];
			}
		);

		return values;
	}

	/**
	 * Active slide that is serving an adzad.
	 *
	 * A category or refinement change is a response to the adzad the reader
	 * was looking at when they made it, not to the rotation as a whole.
	 *
	 * @param {HTMLElement} rotation Rotation root.
	 * @return {HTMLElement|null} Slide, or null when none is serving one.
	 */
	function showingAd( rotation ) {
		var slides = rotation ? rotation.querySelectorAll( '.adzworld-slide.adzworld-active' ) : [];
		var index;

		for ( index = slides.length - 1; index >= 0; index-- ) {
			if ( slides[ index ].dataset.adzworldAd ) {
				return slides[ index ];
			}
		}

		return null;
	}

	/**
	 * Post a small, nonce-protected request to WordPress.
	 *
	 * @param {string} action AJAX action.
	 * @param {string} nonce  Action nonce.
	 * @param {Object} values Request values.
	 * @return {Promise} Parsed JSON response.
	 */
	function post( action, nonce, values ) {
		var body = new window.FormData();

		body.append( 'action', action );
		body.append( 'nonce', nonce );
		Object.keys( values ).forEach(
			function ( key ) {
				body.append( key, values[ key ] );
			}
		);

		return window.fetch(
			settings.ajaxUrl,
			{
				body: body,
				credentials: 'same-origin',
				method: 'POST'
			}
		).then(
			function ( response ) {
				return response.json();
			}
		);
	}

	/**
	 * Remember that the next render must visibly return to the user hub.
	 */
	function rememberHubReturn() {
		try {
			window.sessionStorage.setItem( 'adzworldReturnToHub', '1' );
			window.sessionStorage.removeItem( 'adzworldReturnSlot' );
		} catch ( error ) {
			// Navigation still works when browser storage is unavailable.
		}
	}

	/**
	 * After a change made on one slot, reload that slot.
	 *
	 * A stored invitation flag would otherwise win and open the intro. A
	 * change made on the invitation itself still returns there.
	 *
	 * @param {HTMLElement} node Control inside the slot.
	 */
	function rememberRefinementReturn( node ) {
		var slide = node ? node.closest( '.adzworld-slide' ) : null;
		var rotation = node ? node.closest( '.adzworld-rotation' ) : null;

		if ( ! slide || ! rotation || ! slide.dataset.adzworldAd ) {
			rememberHubReturn();
			return;
		}

		try {
			window.sessionStorage.removeItem( 'adzworldReturnToHub' );
			window.sessionStorage.removeItem( 'adzworldReturnCategory' );
			window.sessionStorage.removeItem( 'adzworldReturnCategorySlot' );
			window.sessionStorage.setItem( 'adzworldReturnSlot', '1' );
		} catch ( error ) {
			// The stored slide is still the best record when a flag cannot be set.
		}

		rememberCurrentSlide( rotation, slide );
	}

	/**
	 * Remember the exact category slot that should remain visible after reload.
	 *
	 * @param {string} category Category name.
	 * @param {number} slot     Zero-based category slot.
	 */
	function rememberCategoryReturn( category, slot ) {
		try {
			window.sessionStorage.setItem( 'adzworldReturnCategory', category );
			window.sessionStorage.setItem( 'adzworldReturnCategorySlot', String( slot ) );
		} catch ( error ) {
			// The category remains reachable when browser storage is unavailable.
		}
	}

	/**
	 * Storage key for the slide a reader is looking at in one rotation.
	 *
	 * @param {HTMLElement} rotation Rotation root.
	 * @return {string} Session storage key.
	 */
	function currentSlideKey( rotation ) {
		return 'adzworldCurrentSlide:' + ( rotation.dataset.adzworldRotation || rotation.id || 'rotation' );
	}

	/**
	 * Keep the slide a reader is on, so a reload renders that slot again.
	 *
	 * The invitation is the first slide. Without this record every reload
	 * starts there, including a reload of the slot already on screen.
	 *
	 * @param {HTMLElement} rotation Rotation root.
	 * @param {HTMLElement} slide    Slide now showing.
	 */
	function rememberCurrentSlide( rotation, slide ) {
		var record;

		if ( ! rotation || ! slide ) {
			return;
		}

		record = {
			ad: slide.dataset.adzworldAd || '',
			category: slide.dataset.adzworldCategory || '',
			categorySlot: slide.dataset.adzworldCategorySlot || '',
			slot: slide.dataset.adzworldSlot || '',
			hub: slide.querySelector( '.adz-panel' ) ? '1' : ''
		};

		try {
			window.sessionStorage.setItem( currentSlideKey( rotation ), JSON.stringify( record ) );
		} catch ( error ) {
			// The next load starts at the invitation when storage is unavailable.
		}
	}

	/**
	 * Find the stored slide after a reload.
	 *
	 * @param {HTMLElement}   rotation Rotation root.
	 * @param {HTMLElement[]} slides   Slides in this rotation.
	 * @return {number} Slide index, or 0 when nothing stored still matches.
	 */
	function storedSlideIndex( rotation, slides ) {
		var raw = '';
		var record;
		var index = -1;

		try {
			raw = window.sessionStorage.getItem( currentSlideKey( rotation ) ) || '';
		} catch ( error ) {
			return 0;
		}

		if ( ! raw ) {
			return 0;
		}

		try {
			record = JSON.parse( raw );
		} catch ( error ) {
			return 0;
		}

		if ( ! record || 'object' !== typeof record ) {
			return 0;
		}

		if ( record.ad ) {
			index = slides.findIndex(
				function ( slide ) {
					return slide.dataset.adzworldAd === record.ad &&
						( ! record.category || slide.dataset.adzworldCategory === record.category ) &&
						( ! record.categorySlot || String( slide.dataset.adzworldCategorySlot || '0' ) === String( record.categorySlot ) );
				}
			);
			if ( index < 0 ) {
				index = slides.findIndex(
					function ( slide ) {
						return slide.dataset.adzworldAd === record.ad;
					}
				);
			}
		}

		if ( index < 0 && record.category ) {
			index = slides.findIndex(
				function ( slide ) {
					return slide.dataset.adzworldCategory === record.category &&
						String( slide.dataset.adzworldCategorySlot || '0' ) === String( record.categorySlot || '0' );
				}
			);
		}

		if ( index < 0 && '1' === record.hub ) {
			index = slides.findIndex(
				function ( slide ) {
					return Boolean( slide.querySelector( '.adz-panel' ) );
				}
			);
		}

		if ( index < 0 && record.slot ) {
			index = slides.findIndex(
				function ( slide ) {
					return String( slide.dataset.adzworldSlot || '' ) === String( record.slot );
				}
			);
		}

		return index >= 0 ? index : 0;
	}

	/**
	 * Activate one slide. Positions wrap continuously at both ends.
	 *
	 * @param {HTMLElement} rotation Rotation root.
	 */
	/**
	 * Move between search-result networks inside one category slide.
	 *
	 * Buttons here use their own step attribute. The category carousel reads a
	 * different attribute, and a click inside this carousel returns before that
	 * listener runs.
	 *
	 * @param {HTMLElement} results Results carousel.
	 */
	function prepareResults( results ) {
		var slides = Array.from( results.querySelectorAll( ':scope > .adzworld-results__slides > .adzworld-results__slide' ) );
		var navigation = results.querySelector( ':scope > .adzworld-results__navigation' );
		var evaluation = results.parentElement ? results.parentElement.querySelector( ':scope > .adzworld-evaluation' ) : null;
		var current = 0;

		if ( ! slides.length ) {
			return;
		}

		function selectEvaluation( slide ) {
			var input;
			var output;
			var status;
			var score;

			if ( ! evaluation || ! slide ) {
				return;
			}
			input = evaluation.querySelector( 'input[type="range"]' );
			output = evaluation.querySelector( 'output' );
			status = evaluation.querySelector( '.adzworld-evaluation__status' );
			score = Number( slide.dataset.adzworldResultScore || 0 );
			evaluation.dataset.adzworldFeedbackTarget = slide.dataset.adzworldResultTarget || '';
			if ( input ) {
				input.value = String( score );
				input.dispatchEvent( new window.Event( 'input', { bubbles: true } ) );
			}
			if ( output ) {
				output.textContent = evaluationLabel( score );
			}
			if ( status ) {
				status.textContent = '';
			}
		}

		function show( position ) {
			current = ( position + slides.length ) % slides.length;
			slides.forEach(
				function ( slide, index ) {
					var active = index === current;

					slide.hidden = ! active;
					slide.setAttribute( 'aria-hidden', String( ! active ) );
				}
			);
			selectEvaluation( slides[ current ] );
		}

		results.addEventListener(
			'click',
			function ( event ) {
				var button = event.target.closest( '[data-adzworld-result-step]' );

				if ( ! button || ! results.contains( button ) ) {
					return;
				}
				event.stopPropagation();
				show( current + Number( button.dataset.adzworldResultStep ) );
			}
		);

		show( 0 );
		if ( navigation && slides.length > 1 ) {
			navigation.hidden = false;
		}
	}

	function prepareRotation( rotation ) {
		var slides = Array.from( rotation.querySelectorAll( ':scope > .adzworld-carousel > .adzworld-slides > .adzworld-slide' ) );
		var current = 0;
		var navigation = rotation.querySelector( ':scope > .adzworld-carousel > .adzworld-navigation' );

		if ( ! slides.length ) {
			return;
		}

		rotation.querySelectorAll( '.adzworld-results' ).forEach( prepareResults );
		rotation.classList.add( 'adzworld-enhanced' );

		/*
		 * Most formats show one slot at a time, so the rest are hidden, marked
		 * aria-hidden and made inert. The multi-slot formats (grid, filmstrip,
		 * coverflow, card stack) show every slot at once: there the slots are
		 * real visible content, so none of them may be hidden from assistive
		 * technology and only "current" moves. PHP sets the flag from its
		 * format allowlist; with the flag absent this path is unchanged.
		 */
		var showsEverySlot = '1' === rotation.dataset.adzworldMultiple;

		function show( position ) {
			var activeCategory;
			var activeSlot;

			current = ( position + slides.length ) % slides.length;
			activeCategory = slides[ current ].dataset.adzworldCategory || '';
			activeSlot = Number( slides[ current ].dataset.adzworldCategorySlot || 0 );
			slides.forEach(
				function ( slide, index ) {
					var active = index === current;

					slide.hidden = showsEverySlot ? false : ! active;
					slide.classList.toggle( 'adzworld-active', active );
					slide.setAttribute( 'aria-hidden', showsEverySlot ? 'false' : String( ! active ) );
					slide.inert = showsEverySlot ? false : ! active;
				}
			);
			if ( navigation ) {
				navigation.querySelectorAll( '[data-adzworld-position]' ).forEach(
					function ( button ) {
						button.setAttribute( 'aria-pressed', String( Number( button.dataset.adzworldPosition ) === current ) );
					}
				);
			}
			if ( showsEverySlot ) {
				// Every slot is on screen, so every provider unit must mount.
				slides.forEach( mountNetworkUnit );
			} else {
				mountNetworkUnit( slides[ current ] );
			}
			rememberCurrentSlide( rotation, slides[ current ] );
			rotation.querySelectorAll( '[data-adzworld-category-slot-category], [data-adz-category-slot-category]' ).forEach(
				function ( button ) {
					var category;
					var slot;

					if ( button.closest( '.adzworld-rotation' ) !== rotation ) {
						return;
					}
					category = button.dataset.adzworldCategorySlotCategory || button.dataset.adzCategorySlotCategory || '';
					slot = Number( button.dataset.adzworldCategorySlot || button.dataset.adzCategorySlot || 0 );

					button.setAttribute( 'aria-pressed', String( category === activeCategory && slot === activeSlot ) );
				}
			);
		}

		function showHub() {
			var hubInput;

			show( 0 );
			hubInput = slides[ 0 ].querySelector( '.adz-panel__input' );
			slides[ 0 ].scrollIntoView( { behavior: 'smooth', block: 'center' } );
			if ( hubInput ) {
				hubInput.focus( { preventScroll: true } );
			}
		}

		function showCategory( category, categorySlot ) {
			var requestedSlot = Number.isFinite( Number( categorySlot ) ) ? Number( categorySlot ) : 0;
			var position = slides.findIndex(
				function ( slide ) {
					return slide.dataset.adzworldCategory === category &&
						Number( slide.dataset.adzworldCategorySlot || 0 ) === requestedSlot;
				}
			);

			if ( position < 0 ) {
				position = slides.findIndex(
					function ( slide ) {
						return slide.dataset.adzworldCategory === category;
					}
				);
			}

			if ( position >= 0 ) {
				show( position );
				slides[ position ].scrollIntoView( { behavior: 'smooth', block: 'center' } );
			}
		}

		rotation.addEventListener(
			'adzworld:show-category',
			function ( event ) {
				showCategory( event.detail.category || '', event.detail.slot || 0 );
			}
		);

		rotation.addEventListener(
			'click',
			function ( event ) {
				var button = event.target.closest( 'button' );
				var category;
				var categorySlot;
				var status;

				/*
				 * The showcase has its own previous and next buttons inside
				 * this rotation. They are handled where the showcase is
				 * prepared, so a click on one must not also move the
				 * rotation underneath it.
				 */
				if ( ! button || button.closest( '.adzworld-results' ) ) {
					return;
				}
				if ( button.hasAttribute( 'data-adzworld-category-first' ) ) {
					showCategory( button.dataset.adzworldCategoryFirst || '', 0 );
					return;
				}
				if ( button.hasAttribute( 'data-adzworld-category-slot-category' ) ) {
					showCategory(
						button.dataset.adzworldCategorySlotCategory || '',
						Number( button.dataset.adzworldCategorySlot || 0 )
					);
					return;
				}
				if ( button.hasAttribute( 'data-adzworld-add-category-slot' ) ) {
					category = button.dataset.adzworldAddCategorySlot || '';
					status = button.closest( '.adzworld-category-slots' ).querySelector( '[data-adzworld-category-slot-status]' );
					button.disabled = true;
					post(
						'adzworld_refine',
						settings.preferenceNonce,
						{
							mode: 'add_slot',
							value: category
						}
					).then(
						function ( payload ) {
							if ( payload && payload.success ) {
								categorySlot = Math.max( 0, Number( payload.data.count ) - 1 );
								rememberCategoryReturn( category, categorySlot );
								window.location.reload();
								return;
							}
							button.disabled = false;
							if ( status ) {
								status.textContent = payload && payload.data ? payload.data.message : '';
							}
						}
					);
					return;
				}
				if ( button.hasAttribute( 'data-adzworld-step' ) ) {
					show( current + Number( button.dataset.adzworldStep ) );
				}
				if ( button.hasAttribute( 'data-adzworld-position' ) ) {
					if ( 0 === Number( button.dataset.adzworldPosition ) ) {
						showHub();
					} else {
						show( Number( button.dataset.adzworldPosition ) );
					}
				}
			}
		);

		/*
		 * The anchored formats (bottom dock, top dock, corner card) can be
		 * dismissed. Dismissal sets the element's hidden property rather than
		 * writing a style, and is remembered for the tab only, so a reader who
		 * clears the frame does not meet it again on the next page. Storage can
		 * be unavailable or throw in a private window, so a failure there just
		 * means the frame returns on the next page.
		 */
		( function () {
			var dismiss = rotation.querySelector( '[data-adzworld-dismiss]' );
			var key = 'adzworld-dismissed-' + ( rotation.id || 'rotation' );

			if ( ! dismiss ) {
				return;
			}

			try {
				if ( '1' === window.sessionStorage.getItem( key ) ) {
					rotation.hidden = true;
					return;
				}
			} catch ( error ) {
				// Storage unavailable, so the frame shows. The reason is left on
				// the element, matching how this file records provider errors,
				// so a publisher can see why dismissal is not remembered.
				rotation.dataset.adzworldDismissStore = error && error.name ? String( error.name ) : 'unavailable';
			}

			dismiss.addEventListener(
				'click',
				function () {
					rotation.hidden = true;

					try {
						window.sessionStorage.setItem( key, '1' );
					} catch ( error ) {
						// The frame is dismissed for this page either way; it
						// returns on the next one.
						rotation.dataset.adzworldDismissStore = error && error.name ? String( error.name ) : 'unavailable';
					}
				}
			);
		}() );

		if ( navigation ) {
			navigation.addEventListener(
				'keydown',
				function ( event ) {
					var positions = {
						ArrowLeft: current - 1,
						ArrowRight: current + 1,
						End: slides.length - 1,
						Home: 0
					};

					if ( Object.prototype.hasOwnProperty.call( positions, event.key ) ) {
						event.preventDefault();
						show( positions[ event.key ] );
					}
				}
			);
			navigation.hidden = slides.length < 2 || ! navigation.children.length;
		}

		try {
			if ( '1' === window.sessionStorage.getItem( 'adzworldReturnSlot' ) ) {
				window.sessionStorage.removeItem( 'adzworldReturnSlot' );
				window.sessionStorage.removeItem( 'adzworldReturnToHub' );
				show( storedSlideIndex( rotation, slides ) );
				slides[ current ].scrollIntoView( { block: 'center' } );
			} else if ( '1' === window.sessionStorage.getItem( 'adzworldReturnToHub' ) ) {
				window.sessionStorage.removeItem( 'adzworldReturnToHub' );
				show( 0 );
				window.requestAnimationFrame( showHub );
			} else if ( window.sessionStorage.getItem( 'adzworldReturnCategory' ) ) {
				show( storedSlideIndex( rotation, slides ) );
				window.requestAnimationFrame(
					function () {
						var category = window.sessionStorage.getItem( 'adzworldReturnCategory' ) || '';
						var slot = Number( window.sessionStorage.getItem( 'adzworldReturnCategorySlot' ) || 0 );

						window.sessionStorage.removeItem( 'adzworldReturnCategory' );
						window.sessionStorage.removeItem( 'adzworldReturnCategorySlot' );
						showCategory( category, slot );
					}
				);
			} else {
				show( storedSlideIndex( rotation, slides ) );
			}
		} catch ( error ) {
			show( 0 );
		}

		paintFeedbackThanks( slides, show );
	}

	/**
	 * Show the internal-adz reply on the slot that was refined.
	 *
	 * The creative is unchanged. The note is the server's own sentence,
	 * written as text, and the disclosure is opened so it can be read.
	 *
	 * @param {HTMLElement[]} slides    Slides in this rotation.
	 * @param {Function}      showSlide Moves the rotation to a slide index.
	 */
	function paintFeedbackThanks( slides, showSlide ) {
		var raw = '';
		var note;
		var index;
		var slide = null;
		var evaluation;
		var status;

		try {
			raw = window.sessionStorage.getItem( 'adzworldFeedbackThanks' ) || '';
			window.sessionStorage.removeItem( 'adzworldFeedbackThanks' );
		} catch ( error ) {
			return;
		}

		if ( ! raw ) {
			return;
		}

		try {
			note = JSON.parse( raw );
		} catch ( error ) {
			return;
		}

		if ( ! note || 'string' !== typeof note.message || '' === note.message ) {
			return;
		}

		for ( index = 0; index < slides.length; index++ ) {
			if ( note.ad && slides[ index ].dataset.adzworldAd === note.ad ) {
				slide = slides[ index ];
				showSlide( index );
				slide.scrollIntoView( { block: 'center' } );
				break;
			}
		}

		if ( ! slide ) {
			return;
		}

		evaluation = slide.querySelector( '.adzworld-evaluation' );
		status = slide.querySelector( '.adzworld-evaluation [data-adz-status]' );

		if ( evaluation ) {
			evaluation.open = true;
		}

		if ( status ) {
			status.textContent = note.message;
		}
	}

	/**
	 * Wire the invitation and refinement panel.
	 *
	 * @param {HTMLFormElement} form Preference form.
	 */
	function preparePanel( form ) {
		var input = form.querySelector( '.adz-panel__input' );
		var status = form.querySelector( '[data-adz-status]' );
		var improveTarget = form.dataset.adzImproveTarget || '';

		function closeEditor( editor ) {
			var chip = editor.closest( '.adz-panel__chip' );
			var edit = chip ? chip.querySelector( '.adz-panel__chipedit' ) : null;
			var text = chip ? chip.querySelector( '.adz-panel__category, .adz-panel__chiptext' ) : null;

			editor.hidden = true;
			if ( edit ) {
				edit.hidden = false;
				edit.setAttribute( 'aria-expanded', 'false' );
			}
			if ( text ) {
				text.hidden = false;
			}
		}

		form.addEventListener(
			'submit',
			function ( event ) {
				var mode = improveTarget ? 'improve' : ( event.submitter && event.submitter.value ? event.submitter.value : 'refine' );

				event.preventDefault();
				if ( ! input || ! input.value.trim() ) {
					return;
				}

				post(
					'adzworld_refine',
					settings.preferenceNonce,
					assignPlace(
						{
							mode: mode,
							refinement: input.value,
							target: improveTarget
						},
						form
					)
				).then(
					function ( payload ) {
						if ( payload && payload.success ) {
							if ( improveTarget && payload.data && payload.data.dynamic ) {
								rememberRefinementReturn( form );
								window.location.reload();
							} else if ( improveTarget ) {
								rememberRefinementReturn( form );
								try {
									window.sessionStorage.setItem(
										'adzworldFeedbackThanks',
										JSON.stringify(
											{
												ad: improveTarget,
												message: payload.data && payload.data.message ? payload.data.message : 'Thank you for your feedback.'
											}
										)
									);
								} catch ( error ) {
									// The creative still stays; the sentence is what the reload cannot carry.
								}
								window.location.reload();
							} else {
								rememberHubReturn();
								window.location.reload();
							}
						} else if ( status ) {
							status.textContent = payload && payload.data ? payload.data.message : '';
						}
					}
				);
			}
		);

		form.addEventListener(
			'change',
			function ( event ) {
				var categoryName = event.target.closest( '.adz-panel__category-name' );
				var refinementName = event.target.closest( '.adz-panel__refinement-name' );

				if ( categoryName && categoryName.value.trim() === categoryName.dataset.adzCategoryOriginal ) {
					closeEditor( categoryName );
					return;
				}

				if ( categoryName ) {
					post(
						'adzworld_refine',
						settings.preferenceNonce,
						{
							mode: 'rename',
							refinement: categoryName.value,
							value: categoryName.dataset.adzCategoryOriginal || ''
						}
					).then(
						function ( payload ) {
							if ( payload && payload.success ) {
								rememberRefinementReturn( form );
								window.location.reload();
							} else if ( status ) {
								status.textContent = payload && payload.data ? payload.data.message : '';
								categoryName.value = categoryName.dataset.adzCategoryOriginal || '';
								closeEditor( categoryName );
							}
						}
					);
					return;
				}

				if ( refinementName && refinementName.value.trim() === refinementName.dataset.adzRefinementText ) {
					closeEditor( refinementName );
					return;
				}

				if ( refinementName ) {
					post(
						'adzworld_refine',
						settings.preferenceNonce,
						{
							mode: 'rename_refinement',
							refinement: refinementName.value,
							value: refinementName.dataset.adzRefinementOriginal || ''
						}
					).then(
						function ( payload ) {
							if ( payload && payload.success ) {
								rememberRefinementReturn( form );
								window.location.reload();
							} else if ( status ) {
								status.textContent = payload && payload.data ? payload.data.message : '';
								refinementName.value = refinementName.dataset.adzRefinementText || '';
								closeEditor( refinementName );
							}
						}
					);
				}
			}
		);

		form.addEventListener(
			'click',
			function ( event ) {
				var category = event.target.closest( '.adz-panel__category' );
				var categorySlot = event.target.closest( '.adz-panel__category-slot' );
				var edit = event.target.closest( '.adz-panel__chipedit' );
				var control;
				var rotation = form.closest( '.adzworld-rotation' );

				if ( categorySlot ) {
					event.preventDefault();
					rotation.dispatchEvent(
						new window.CustomEvent(
							'adzworld:show-category',
							{
								detail: {
									category: categorySlot.dataset.adzCategorySlotCategory || '',
									slot: Number( categorySlot.dataset.adzCategorySlot || 0 )
								}
							}
						)
					);
					return;
				}

				if ( category ) {
					var selected = category.dataset.adzCategory || '';
					var showing = showingAd( rotation );
					var selectValues = {
						mode: 'select',
						value: selected
					};

					event.preventDefault();

					/*
					 * Only a change counts as a response. Clicking the
					 * category an adzad is already in says nothing.
					 */
					if ( showing && ( showing.dataset.adzworldCategory || '' ) !== selected ) {
						assignPlace( selectValues, showing );
						selectValues.ad = showing.dataset.adzworldAd || '';
					}

					rotation.dispatchEvent(
						new window.CustomEvent(
							'adzworld:show-category',
							{ detail: { category: selected, slot: 0 } }
						)
					);
					post(
						'adzworld_refine',
						settings.preferenceNonce,
						selectValues
					);
					return;
				}

				if ( edit ) {
					event.preventDefault();
					control = document.getElementById( edit.dataset.adzEditControl || '' );
					if ( control ) {
						edit.hidden = true;
						edit.parentElement.querySelector( '.adz-panel__category, .adz-panel__chiptext' ).hidden = true;
						control.hidden = false;
						edit.setAttribute( 'aria-expanded', 'true' );
						control.focus();
						control.select();
					}
				}
			}
		);

		form.addEventListener(
			'keydown',
			function ( event ) {
				var editor = event.target.closest( '.adz-panel__category-name, .adz-panel__refinement-name' );

				if ( ! editor ) {
					return;
				}
				if ( 'Enter' === event.key ) {
					event.preventDefault();
					editor.blur();
				}
				if ( 'Escape' === event.key ) {
					event.preventDefault();
					editor.value = editor.dataset.adzCategoryOriginal || editor.dataset.adzRefinementText || '';
					closeEditor( editor );
				}
			}
		);
	}

	/**
	 * Human-readable label for immediate slider feedback.
	 *
	 * @param {number} score Evaluation score.
	 * @return {string} Evaluation label.
	 */
	function evaluationLabel( score ) {
		var labels = settings.labels || {};

		if ( score <= -75 ) {
			return labels.strongDislike || 'Strongly dislike';
		}
		if ( score <= -25 ) {
			return labels.dislike || 'Dislike';
		}
		if ( score < 25 ) {
			return labels.neutral || 'Neutral';
		}
		if ( score < 75 ) {
			return labels.like || 'Like';
		}
		return labels.strongLike || 'Strongly like';
	}

	/**
	 * Wire one explicit evaluation control.
	 *
	 * @param {HTMLElement} control Evaluation root.
	 */
	function prepareEvaluation( control ) {
		var input = control.querySelector( 'input[type="range"]' );
		var output = control.querySelector( 'output' );
		var status = control.querySelector( '.adzworld-evaluation__status' );

		if ( ! input ) {
			return;
		}

		function reflect() {
			var score = Number( input.value );
			var scoreClass = 'is-score-' + String( score ).replace( '-', 'minus-' );

			input.classList.toggle( 'is-negative', score < 0 );
			input.classList.toggle( 'is-positive', score > 0 );
			Array.from( input.classList ).forEach(
				function ( className ) {
					if ( 0 === className.indexOf( 'is-score-' ) ) {
						input.classList.remove( className );
					}
				}
			);
			input.classList.add( scoreClass );
			if ( output ) {
				output.textContent = evaluationLabel( score );
			}
		}

		input.addEventListener( 'input', reflect );
		input.addEventListener(
			'change',
			function () {
				post(
					'adzworld_evaluate',
					settings.feedbackNonce,
					assignPlace(
						{
							score: input.value,
							target: control.dataset.adzworldFeedbackTarget || ''
						},
						control
					)
				).then(
					function ( payload ) {
						if ( output && payload && payload.success ) {
							output.textContent = payload.data.label;
						}
						if ( status ) {
							status.textContent = payload && payload.data ? payload.data.message : '';
						}
					}
				);
			}
		);
		reflect();
	}

	function init() {
		if ( ! window.fetch || ! window.FormData || ! settings.ajaxUrl ) {
			return;
		}
		document.querySelectorAll( '.adzworld-rotation' ).forEach( prepareRotation );
		document.querySelectorAll( '[data-adz-panel]' ).forEach( preparePanel );
		document.querySelectorAll( '[data-adzworld-feedback-target]' ).forEach( prepareEvaluation );
		document.addEventListener(
			'adzworld:mount-network',
			function ( event ) {
				var root = event.detail && event.detail.root ? event.detail.root : event.target;

				if ( root && root.querySelector ) {
					mountNetworkUnit( root );
				}
			}
		);
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
