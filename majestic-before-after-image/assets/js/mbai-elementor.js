/**
 * Majestic Before After Image – Elementor frontend handler
 *
 * Hooks into the Elementor widget lifecycle and delegates rendering
 * to the core MBAISlider engine (mbai.js).
 *
 * @package MBAI
 */

'use strict';

class MBAIWidgetHandlerClass extends elementorModules.frontend.handlers.Base {

	getDefaultSettings() {
		return {
			selectors: {
				mainWrapper: '.mbai-before-after-wrap',
				container:   '.mbai-before-after-container',
			},
		};
	}

	getDefaultElements() {
		const selectors = this.getSettings( 'selectors' );
		return {
			$mainWrapper: this.$element.find( selectors.mainWrapper ),
			$container:   this.$element.find( selectors.container ),
		};
	}

	onInit() {
		super.onInit();
		this._initSlider();
	}

	onElementChange() {
		// Re-init when Elementor editor changes settings
		this._initSlider();
	}

	_initSlider() {
		const wrap = this.elements.$mainWrapper[0];
		if ( ! wrap ) { return; }

		// If the core engine (or a previous onElementChange) already initialised
		// this widget, tear that instance down first. Without this, Elementor's
		// re-init would build a second handle / divider on top of the first.
		if ( wrap._mbaiInstance && typeof wrap._mbaiInstance.destroy === 'function' ) {
			wrap._mbaiInstance.destroy();
			wrap._mbaiInstance = null;
		}

		// Clear the init flag so mbaiInitAll picks it up again.
		wrap.removeAttribute( 'data-mbai-init' );

		// Let the core engine handle (re)initialisation.
		if ( typeof window.mbaiInitAll === 'function' ) {
			window.mbaiInitAll( wrap.parentElement || document );
		}
	}
}

jQuery( window ).on( 'elementor/frontend/init', () => {
	const addMBAIWidgetHandler = ( $element ) => {
		elementorFrontend.elementsHandler.addHandler( MBAIWidgetHandlerClass, { $element } );
	};

	elementorFrontend.hooks.addAction(
		'frontend/element_ready/mbai-before-after-image.default',
		addMBAIWidgetHandler
	);
} );
