/**
 * Majestic Before After Image - Vanilla JS Engine
 *
 * Replaces the third-party event.move.js + twentytwenty.js libraries.
 * Zero dependencies – works with or without jQuery / Elementor.
 *
 * Backwards compatibility: every element also receives its legacy
 * .twentytwenty-* class name alongside the new .mbai-* name, so any
 * custom CSS written against v2 sites continues to work after upgrade.
 *
 * @package MBAI
 */

( function () {
	'use strict';

	/* ------------------------------------------------------------------
	 * Utility: clamp a value between min and max
	 * ------------------------------------------------------------------ */
	function clamp( val, min, max ) {
		return Math.max( min, Math.min( max, val ) );
	}

	/* ------------------------------------------------------------------
	 * MBAISlider – manages a single before/after container
	 * ------------------------------------------------------------------ */
	function MBAISlider( container, options ) {
		this.container        = container;
		this.wrap             = container.closest( '.mbai-before-after-wrap' ) || container.parentElement;
		this.options          = options;
		// Support both key names: handle_offset (PHP/shortcode) and default_offset_pct (legacy).
		this.sliderPct        = parseFloat( options.handle_offset || options.default_offset_pct || 0.5 );
		this.orientation      = options.orientation || 'horizontal';
		this.active           = false;
		this.beforeImg        = null;
		this.afterImg         = null;
		this.handle           = null;
		this.resizeObserver   = null;
		this.intersectionObserver = null;
		this.createdWrapper   = null;
		// Snapshot the original markup so destroy() can fully restore it.
		this._originalHTML    = container.innerHTML;

		this._init();
	}

	MBAISlider.prototype = {

		_init: function () {
			var self       = this;
			var c          = this.container;
			var opts       = this.options;
			var isVertical = ( this.orientation === 'vertical' );

			/* ---- Wrap container ---- */
			var wrapper = document.createElement( 'div' );
			// New class names + legacy twentytwenty-* aliases for backwards compatibility.
			wrapper.className =
				'mbai-wrapper mbai-' + this.orientation +
				' mbai-labels-status-' + ( opts.labels_status || 'hover' ) +
				// Legacy aliases (v2 custom CSS continues to work after upgrade).
				' twentytwenty-wrapper twentytwenty-' + this.orientation +
				' twentytwenty-labels-status-' + ( opts.labels_status || 'hover' );
			c.parentNode.insertBefore( wrapper, c );
			wrapper.appendChild( c );
			// Track the wrapper we created so destroy() can unwrap cleanly.
			this.createdWrapper = wrapper;

			/* ---- Classify images ---- */
			var imgs   = c.querySelectorAll( 'img' );
			this.beforeImg = imgs[0] || null;
			this.afterImg  = imgs[1] || null;

			// Add both new and legacy class names to images.
			if ( this.beforeImg ) { this.beforeImg.classList.add( 'mbai-before', 'twentytwenty-before' ); }
			if ( this.afterImg )  { this.afterImg.classList.add( 'mbai-after', 'twentytwenty-after' ); }

			c.classList.add( 'mbai-container', 'twentytwenty-container' );

			/* ---- Overlay ---- */
			if ( opts.overlay_status !== false ) {
				var overlayEnabled = opts.overlay_status ? 'overlay-enabled' : 'overlay-disabled';
				var overlay = document.createElement( 'div' );
				overlay.className = 'mbai-overlay twentytwenty-overlay ' + overlayEnabled;
				c.appendChild( overlay );
			}

			/* ---- Labels (always outside overlay so they show when overlay is off) ---- */
			if ( opts.labels_status !== 'never' ) {
				var bLabel = document.createElement( 'div' );
				bLabel.className = 'mbai-before-label twentytwenty-before-label';
				bLabel.setAttribute( 'data-content', opts.before_label || 'Before' );

				var aLabel = document.createElement( 'div' );
				aLabel.className = 'mbai-after-label twentytwenty-after-label';
				aLabel.setAttribute( 'data-content', opts.after_label || 'After' );

				c.appendChild( bLabel );
				c.appendChild( aLabel );
			}

			/* ---- Handle ---- */
			var handle = document.createElement( 'div' );
			// Both new and legacy class names on the handle.
			handle.className = 'mbai-handle twentytwenty-handle';
			this.handle = handle;

			if ( opts.handle_type === 'text' ) {
				var txt = document.createElement( 'span' );
				txt.className   = 'mbai-handle-text twentytwenty-handle-text';
				txt.textContent = opts.handle_label || 'Drag';
				handle.appendChild( txt );
			} else {
				var arrowBefore = document.createElement( 'span' );
				var arrowAfter  = document.createElement( 'span' );
				if ( isVertical ) {
					arrowBefore.className = 'mbai-up-arrow twentytwenty-up-arrow';
					arrowAfter.className  = 'mbai-down-arrow twentytwenty-down-arrow';
				} else {
					arrowBefore.className = 'mbai-left-arrow twentytwenty-left-arrow';
					arrowAfter.className  = 'mbai-right-arrow twentytwenty-right-arrow';
				}
				handle.appendChild( arrowBefore );
				handle.appendChild( arrowAfter );
			}
			c.appendChild( handle );

			/* ---- Bind interactions ---- */
			this._bindDrag();

			if ( opts.move_slider_on_hover ) {
				this._bindHover();
			}

			/* ---- Resize handling ---- */
			if ( typeof ResizeObserver !== 'undefined' ) {
				this.resizeObserver = new ResizeObserver( function () {
					self._adjustSlider( self.sliderPct );
				} );
				this.resizeObserver.observe( c );
			} else {
				window.addEventListener( 'resize', function () {
					self._adjustSlider( self.sliderPct );
				} );
			}

			/* ---- Visibility handling ----
			 * When the slider starts hidden (e.g. inside an inactive WooCommerce
			 * gallery slide), its images report 0 size. Recalculate once it
			 * becomes visible so it sizes correctly when its slide is shown. */
			if ( typeof IntersectionObserver !== 'undefined' ) {
				this.intersectionObserver = new IntersectionObserver( function ( entries ) {
					entries.forEach( function ( entry ) {
						if ( entry.isIntersecting && entry.intersectionRatio > 0 ) {
							self._adjustSlider( self.sliderPct );
						}
					} );
				}, { threshold: 0.01 } );
				this.intersectionObserver.observe( c );
			}

			/* ---- Initial position – wait for images to load ---- */
			this._waitForImages( function () {
				self._adjustSlider( self.sliderPct );
			} );
		},

		_waitForImages: function ( cb ) {
			var imgs    = [ this.beforeImg, this.afterImg ].filter( Boolean );
			var pending = imgs.length;

			if ( pending === 0 ) { cb(); return; }

			function done() {
				pending--;
				if ( pending === 0 ) { cb(); }
			}

			imgs.forEach( function ( img ) {
				if ( img.complete ) {
					done();
				} else {
					img.addEventListener( 'load',  done );
					img.addEventListener( 'error', done );
				}
			} );
		},

		_calcOffset: function ( pct ) {
			var img = this.beforeImg;
			var w   = img ? img.offsetWidth  : this.container.offsetWidth;
			var h   = img ? img.offsetHeight : this.container.offsetHeight;

			// Fallback to natural dimensions if not yet painted.
			if ( ( ! w || ! h ) && img && img.naturalWidth ) {
				w = img.naturalWidth;
				h = img.naturalHeight;
			}

			return { wRaw: w || 0, hRaw: h || 0 };
		},

		_adjustSlider: function ( pct ) {
			var offset     = this._calcOffset( pct );
			var isVertical = ( this.orientation === 'vertical' );
			var w  = offset.wRaw;
			var h  = offset.hRaw;
			var cw = pct * w;
			var ch = pct * h;

			// Reveal using clip-path inset() — works on in-flow (relative) elements,
			// unlike the old clip:rect() which required absolute positioning.
			if ( this.beforeImg ) {
				this.beforeImg.style.clipPath = isVertical
					? 'inset(0 0 ' + ( h - ch ) + 'px 0)'   // show top portion [0..ch]
					: 'inset(0 ' + ( w - cw ) + 'px 0 0)';  // show left portion [0..cw]
			}
			if ( this.afterImg ) {
				this.afterImg.style.clipPath = isVertical
					? 'inset(' + ch + 'px 0 0 0)'           // show bottom portion [ch..h]
					: 'inset(0 0 0 ' + cw + 'px)';          // show right portion [cw..w]
			}

			// Position the handle.
			if ( this.handle ) {
				if ( isVertical ) {
					this.handle.style.top  = ch + 'px';
					this.handle.style.left = '';
				} else {
					this.handle.style.left = cw + 'px';
					this.handle.style.top  = '';
				}
			}
		},

		_getPct: function ( pageX, pageY ) {
			// Measure against the before image (the in-flow sizer element).
			var el   = this.beforeImg || this.container;
			var rect = el.getBoundingClientRect();
			var scrollX = window.pageXOffset || document.documentElement.scrollLeft;
			var scrollY = window.pageYOffset || document.documentElement.scrollTop;
			var offsetX = rect.left + scrollX;
			var offsetY = rect.top  + scrollY;
			var w = rect.width  || 1;
			var h = rect.height || 1;

			return ( this.orientation === 'vertical' )
				? clamp( ( pageY - offsetY ) / h, 0, 1 )
				: clamp( ( pageX - offsetX ) / w, 0, 1 );
		},

		_bindDrag: function () {
			var self   = this;
			var handle = this.handle;
			var target = handle || this.container;

			/* ---- Pointer / Mouse ---- */
			function onPointerDown( e ) {
				if ( e.button !== undefined && e.button !== 0 ) { return; } // left only
				self.active = true;
				self.container.classList.add( 'active' );
				// Stop the event reaching parent sliders (e.g. WooCommerce
				// FlexSlider) so dragging the handle doesn't swipe the gallery.
				e.stopPropagation();
				// Prevent text selection
				e.preventDefault();
			}

			function onPointerMove( e ) {
				if ( ! self.active ) { return; }
				var pageX, pageY;
				if ( e.touches ) {
					pageX = e.touches[0].pageX;
					pageY = e.touches[0].pageY;
				} else {
					pageX = e.pageX;
					pageY = e.pageY;
				}
				self.sliderPct = self._getPct( pageX, pageY );
				self._adjustSlider( self.sliderPct );
			}

			function onPointerUp() {
				if ( ! self.active ) { return; }
				self.active = false;
				self.container.classList.remove( 'active' );
			}

			target.addEventListener( 'mousedown',  onPointerDown );
			target.addEventListener( 'touchstart', onPointerDown, { passive: false } );

			document.addEventListener( 'mousemove',  onPointerMove );
			document.addEventListener( 'touchmove',  onPointerMove, { passive: false } );
			document.addEventListener( 'mouseup',    onPointerUp );
			document.addEventListener( 'touchend',   onPointerUp );

			// Keep references so destroy() can remove the document-level listeners.
			this._docHandlers = {
				move: onPointerMove,
				up:   onPointerUp
			};

			/* Prevent image drag */
			this.container.querySelectorAll( 'img' ).forEach( function ( img ) {
				img.addEventListener( 'mousedown', function ( e ) { e.preventDefault(); } );
			} );
		},

		_bindHover: function () {
			var self = this;

			this.container.addEventListener( 'mouseenter', function () {
				self.active = true;
				self.container.classList.add( 'active' );
			} );

			this.container.addEventListener( 'mousemove', function ( e ) {
				if ( ! self.active ) { return; }
				self.sliderPct = self._getPct( e.pageX, e.pageY );
				self._adjustSlider( self.sliderPct );
			} );

			this.container.addEventListener( 'mouseleave', function () {
				self.active = false;
				self.container.classList.remove( 'active' );
			} );
		},

		destroy: function () {
			if ( this.resizeObserver ) {
				this.resizeObserver.disconnect();
			}
			if ( this.intersectionObserver ) {
				this.intersectionObserver.disconnect();
			}
			if ( this._docHandlers ) {
				document.removeEventListener( 'mousemove', this._docHandlers.move );
				document.removeEventListener( 'touchmove', this._docHandlers.move );
				document.removeEventListener( 'mouseup',   this._docHandlers.up );
				document.removeEventListener( 'touchend',  this._docHandlers.up );
			}

			// Restore the original markup so re-initialising doesn't leave
			// behind a duplicate handle / divider. During _init the container
			// was wrapped in a .mbai-wrapper and had the handle + overlay
			// appended; undo both here.
			if ( this.container && typeof this._originalHTML === 'string' ) {
				this.container.innerHTML = this._originalHTML;
				this.container.className = 'mbai-before-after-container';
				this.container.removeAttribute( 'style' );

				// Unwrap: move the container back out of the wrapper we created,
				// then remove the now-empty wrapper.
				var wrapper = this.createdWrapper;
				if ( wrapper && wrapper.parentNode ) {
					wrapper.parentNode.insertBefore( this.container, wrapper );
					wrapper.parentNode.removeChild( wrapper );
				}
			}
		}
	};

	/* ------------------------------------------------------------------
	 * Public API: initialise all .mbai-before-after-wrap elements
	 * ------------------------------------------------------------------ */
	function initAll( scope ) {
		var instances = [];
		var wraps = ( scope || document ).querySelectorAll( '.mbai-before-after-wrap:not([data-mbai-init])' );

		wraps.forEach( function ( wrap ) {
			wrap.setAttribute( 'data-mbai-init', '1' );

			var rawData  = wrap.getAttribute( 'data-mbai' );
			var opts     = {};

			try {
				opts = rawData ? JSON.parse( rawData ) : {};
			} catch ( e ) {
				// ignore bad JSON
			}

			var container = wrap.querySelector( '.mbai-before-after-container' );
			if ( container ) {
				var instance = new MBAISlider( container, opts );
				// Store the instance on the wrap so it can be destroyed later
				// (e.g. when Elementor re-initialises the widget in the editor).
				wrap._mbaiInstance = instance;
				instances.push( instance );
			}
		} );

		return instances;
	}

	/* ------------------------------------------------------------------
	 * Boot
	 * ------------------------------------------------------------------ */
	function boot() {
		initAll( document );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}

	/* Expose so Elementor / other integrations can call initAll() */
	window.MBAISlider = MBAISlider;
	window.mbaiInitAll = initAll;

}() );
