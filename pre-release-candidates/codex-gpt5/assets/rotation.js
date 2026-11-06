/**
 * Invitation-first carousel, preference refinement, and adz evaluation.
 *
 * @package adz-world
 */

( function () {
	'use strict';

	var settings = window.adzworldRotation || {};

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
		} catch ( error ) {
			// Navigation still works when browser storage is unavailable.
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
	function prepareRotation( rotation ) {
		var slides = Array.from( rotation.querySelectorAll( '.adzworld-slide' ) );
		var current = 0;
		var navigation = rotation.querySelector( '.adzworld-navigation' );

		if ( ! slides.length ) {
			return;
		}

		rotation.classList.add( 'adzworld-enhanced' );

		function show( position ) {
			var activeCategory;
			var activeSlot;

			current = ( position + slides.length ) % slides.length;
			activeCategory = slides[ current ].dataset.adzworldCategory || '';
			activeSlot = Number( slides[ current ].dataset.adzworldCategorySlot || 0 );
			slides.forEach(
				function ( slide, index ) {
					var active = index === current;

					slide.hidden = ! active;
					slide.classList.toggle( 'adzworld-active', active );
					slide.setAttribute( 'aria-hidden', String( ! active ) );
					slide.inert = ! active;
				}
			);
			rotation.querySelectorAll( '[data-adzworld-position]' ).forEach(
				function ( button ) {
					button.setAttribute( 'aria-pressed', String( Number( button.dataset.adzworldPosition ) === current ) );
				}
			);
			rotation.querySelectorAll( '[data-adzworld-category-slot-category], [data-adz-category-slot-category]' ).forEach(
				function ( button ) {
					var category = button.dataset.adzworldCategorySlotCategory || button.dataset.adzCategorySlotCategory || '';
					var slot = Number( button.dataset.adzworldCategorySlot || button.dataset.adzCategorySlot || 0 );

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

				if ( ! button ) {
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

		show( 0 );
		try {
			if ( '1' === window.sessionStorage.getItem( 'adzworldReturnToHub' ) ) {
				window.sessionStorage.removeItem( 'adzworldReturnToHub' );
				window.requestAnimationFrame( showHub );
			} else if ( window.sessionStorage.getItem( 'adzworldReturnCategory' ) ) {
				window.requestAnimationFrame(
					function () {
						var category = window.sessionStorage.getItem( 'adzworldReturnCategory' ) || '';
						var slot = Number( window.sessionStorage.getItem( 'adzworldReturnCategorySlot' ) || 0 );

						window.sessionStorage.removeItem( 'adzworldReturnCategory' );
						window.sessionStorage.removeItem( 'adzworldReturnCategorySlot' );
						showCategory( category, slot );
					}
				);
			}
		} catch ( error ) {
			// The hub remains the first slide when browser storage is unavailable.
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
					{
						mode: mode,
						refinement: input.value,
						target: improveTarget
					}
				).then(
					function ( payload ) {
						if ( payload && payload.success ) {
							rememberHubReturn();
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
								rememberHubReturn();
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
								rememberHubReturn();
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
					{
						score: input.value,
						target: control.dataset.adzworldFeedbackTarget || ''
					}
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
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
