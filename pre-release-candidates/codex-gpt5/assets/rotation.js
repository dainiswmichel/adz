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
	 * Remember the slide that is on screen, including across a reload.
	 *
	 * An empty category is the hub. A named category is that adz.
	 *
	 * @param {string} category Category name.
	 * @param {number} slot     Zero-based category slot.
	 */
	function rememberSlide( category, slot ) {
		try {
			window.sessionStorage.setItem( 'adzworldSlideCategory', category || '' );
			window.sessionStorage.setItem( 'adzworldSlideSlot', String( slot || 0 ) );
			window.sessionStorage.removeItem( 'adzworldReturnToHub' );
		} catch ( error ) {
			// The hub remains available when browser storage is unavailable.
		}
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
			var outerSlide;
			var status;
			var request;

			if ( ! evaluation || ! slide ) {
				return;
			}
			outerSlide = results.closest( '.adzworld-slide' );
			status = evaluation.querySelector( '.adzworld-evaluation__status' );
			request = results.dataset.adzworldRequest || '';
			evaluation.dataset.adzworldFeedbackTarget = slide.dataset.adzworldResultTarget || '';
			evaluation.dataset.adzworldFeedbackProvider = slide.dataset.adzworldProvider || '';
			evaluation.dataset.adzworldFeedbackItemId = slide.dataset.adzworldItemId || '';
			evaluation.dataset.adzworldFeedbackCategory = outerSlide ? outerSlide.dataset.adzworldCategory || '' : '';
			evaluation.dataset.adzworldFeedbackRotation = outerSlide ? outerSlide.dataset.adzworldRotation || '' : '';
			evaluation.dataset.adzworldFeedbackSlot = outerSlide ? outerSlide.dataset.adzworldCategorySlot || '0' : '0';
			evaluation.querySelectorAll( '[data-adzworld-response]' ).forEach(
				function ( button ) {
					button.classList.remove( 'is-selected' );
					button.removeAttribute( 'aria-pressed' );
				}
			);
			if ( status ) {
				status.textContent = '';
			}
			if ( ! slide.dataset.adzworldImpressionRecorded ) {
				slide.dataset.adzworldImpressionRecorded = '1';
				post(
					'adzworld_journey_impression',
					settings.feedbackNonce,
					{
						provider: evaluation.dataset.adzworldFeedbackProvider,
						item_id: evaluation.dataset.adzworldFeedbackItemId,
						category: evaluation.dataset.adzworldFeedbackCategory,
						rotation: evaluation.dataset.adzworldFeedbackRotation,
						slot: evaluation.dataset.adzworldFeedbackSlot,
						request: request
					}
				);
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
			rememberSlide( activeCategory, activeSlot );
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

		var startCategory = '';
		var startSlot = 0;

		try {
			window.sessionStorage.removeItem( 'adzworldReturnToHub' );
			if ( window.sessionStorage.getItem( 'adzworldReturnCategory' ) ) {
				startCategory = window.sessionStorage.getItem( 'adzworldReturnCategory' ) || '';
				startSlot = Number( window.sessionStorage.getItem( 'adzworldReturnCategorySlot' ) || 0 );
				window.sessionStorage.removeItem( 'adzworldReturnCategory' );
				window.sessionStorage.removeItem( 'adzworldReturnCategorySlot' );
			} else {
				startCategory = window.sessionStorage.getItem( 'adzworldSlideCategory' ) || '';
				startSlot = Number( window.sessionStorage.getItem( 'adzworldSlideSlot' ) || 0 );
			}
		} catch ( error ) {
			startCategory = '';
		}

		if ( startCategory ) {
			showCategory( startCategory, startSlot );
		}
		if ( ! rotation.querySelector( '.adzworld-slide.adzworld-active' ) ) {
			show( 0 );
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
				var evaluation = form.closest( '.adzworld-evaluation' );

				event.preventDefault();
				if ( ! input || ! input.value.trim() ) {
					return;
				}

				post(
					'adzworld_refine',
					settings.preferenceNonce,
					{
						mode: mode,
						refinement: input.value,
						target: improveTarget,
						provider: evaluation ? evaluation.dataset.adzworldFeedbackProvider || form.dataset.adzworldProvider || '' : form.dataset.adzworldProvider || '',
						item_id: evaluation ? evaluation.dataset.adzworldFeedbackItemId || '' : '',
						category: evaluation ? evaluation.dataset.adzworldFeedbackCategory || form.dataset.adzworldCategory || '' : form.dataset.adzworldCategory || '',
						rotation: evaluation ? evaluation.dataset.adzworldFeedbackRotation || form.dataset.adzworldRotation || '' : form.dataset.adzworldRotation || '',
						slot: evaluation ? evaluation.dataset.adzworldFeedbackSlot || form.dataset.adzworldSlot || '0' : form.dataset.adzworldSlot || '0'
					}
				).then(
					function ( payload ) {
						if ( payload && payload.success ) {
							window.location.reload();
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

			}
		);

		form.addEventListener(
			'click',
			function ( event ) {
				var category = event.target.closest( '.adz-panel__category' );
				var categorySlot = event.target.closest( '.adz-panel__category-slot' );
				var edit = event.target.closest( '.adz-panel__chipedit' );
				var refinementAction = event.target.closest( '[data-adz-refinement-action]' );
				var control;
				var refinementEditor;
				var refinementName;
				var rotation = form.closest( '.adzworld-rotation' );

				if ( refinementAction ) {
					event.preventDefault();
					refinementEditor = refinementAction.closest( '.adz-panel__refinement-editor' );
					refinementName = refinementEditor ? refinementEditor.querySelector( '.adz-panel__refinement-name' ) : null;
					if ( ! refinementEditor || ! refinementName ) {
						return;
					}
					if ( 'cancel' === refinementAction.dataset.adzRefinementAction ) {
						refinementName.value = refinementName.dataset.adzRefinementText || '';
						closeEditor( refinementEditor );
						return;
					}
					post(
						'adzworld_refine',
						settings.preferenceNonce,
						'delete' === refinementAction.dataset.adzRefinementAction
							? {
								mode: 'drop',
								facet: 'category_refinements',
								value: refinementName.dataset.adzRefinementOriginal || ''
							}
							: {
								mode: 'rename_refinement',
								refinement: refinementName.value,
								value: refinementName.dataset.adzRefinementOriginal || ''
							}
					).then(
						function ( payload ) {
							if ( payload && payload.success ) {
								window.location.reload();
							} else if ( status ) {
								status.textContent = payload && payload.data ? payload.data.message : '';
							}
						}
					);
					return;
				}

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
					event.preventDefault();
					rotation.dispatchEvent(
						new window.CustomEvent(
							'adzworld:show-category',
							{ detail: { category: category.dataset.adzCategory || '', slot: 0 } }
						)
					);
					post(
						'adzworld_refine',
						settings.preferenceNonce,
						{
							mode: 'select',
							value: category.dataset.adzCategory || ''
						}
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
						refinementName = control.querySelector ? control.querySelector( '.adz-panel__refinement-name' ) : null;
						( refinementName || control ).focus();
						( refinementName || control ).select();
					}
				}
			}
		);

		form.addEventListener(
			'keydown',
			function ( event ) {
				var editor = event.target.closest( '.adz-panel__category-name, .adz-panel__refinement-name' );
				var refinementEditor;
				var action;

				if ( ! editor ) {
					return;
				}
				if ( 'Enter' === event.key ) {
					event.preventDefault();
					refinementEditor = editor.closest( '.adz-panel__refinement-editor' );
					if ( refinementEditor ) {
						action = refinementEditor.querySelector( '[data-adz-refinement-action="save"]' );
						action.click();
					} else {
						editor.blur();
					}
				}
				if ( 'Escape' === event.key ) {
					event.preventDefault();
					editor.value = editor.dataset.adzCategoryOriginal || editor.dataset.adzRefinementText || '';
					closeEditor( editor.closest( '.adz-panel__refinement-editor' ) || editor );
				}
			}
		);
	}

	/**
	 * Wire one explicit evaluation control.
	 *
	 * @param {HTMLElement} control Evaluation root.
	 */
	function prepareEvaluation( control ) {
		var status = control.querySelector( '.adzworld-evaluation__status' );

		control.addEventListener(
			'click',
			function ( event ) {
				var button = event.target.closest( '[data-adzworld-response]' );
				var response;

				if ( ! button || ! control.contains( button ) ) {
					return;
				}
				response = button.dataset.adzworldResponse || '';
				button.disabled = true;
				post(
					'adzworld_evaluate',
					settings.feedbackNonce,
					{
						response: response,
						provider: control.dataset.adzworldFeedbackProvider || '',
						item_id: control.dataset.adzworldFeedbackItemId || '',
						category: control.dataset.adzworldFeedbackCategory || '',
						rotation: control.dataset.adzworldFeedbackRotation || '',
						slot: control.dataset.adzworldFeedbackSlot || '0'
					}
				).then(
					function ( payload ) {
						button.disabled = false;
						if ( payload && payload.success ) {
							control.querySelectorAll( '[data-adzworld-response]' ).forEach(
								function ( choice ) {
									var selected = choice === button;

									choice.classList.toggle( 'is-selected', selected );
									choice.setAttribute( 'aria-pressed', String( selected ) );
								}
							);
						}
						if ( status ) {
							status.textContent = payload && payload.data ? payload.data.message : '';
						}
						if ( payload && payload.success && 'dislike' === response ) {
							window.location.reload();
						}
					}
				);
			}
		);
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
