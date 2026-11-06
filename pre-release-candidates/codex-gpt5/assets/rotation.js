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
			current = ( position + slides.length ) % slides.length;
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

				if ( ! categoryName || categoryName.value.trim() === categoryName.dataset.adzCategoryOriginal ) {
					return;
				}

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
						}
					}
				);
			}
		);

		form.addEventListener(
			'click',
			function ( event ) {
				var remove = event.target.closest( '.adz-panel__chipdrop' );

				if ( ! remove ) {
					return;
				}
				event.preventDefault();
				post(
					'adzworld_refine',
					settings.preferenceNonce,
					{
						facet: remove.dataset.adzFacet || '',
						mode: 'drop',
						value: remove.dataset.adzValue || ''
					}
				).then(
					function ( payload ) {
						if ( payload && payload.success ) {
							rememberHubReturn();
							window.location.reload();
						}
					}
				);
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
