/* exported CustomizeInputValidityConstraints */
/* eslint complexity: ["error", 6] */
/* eslint no-magic-numbers: ["error", {"ignore":[0,1]}] */

var CustomizeInputValidityConstraints = (function( $ ) {
	'use strict';

	var component = {
		api: null
	};

	/**
	 * Init.
	 *
	 * @param {object} api The wp.customize instance.
	 * @returns {void}
	 */
	component.init = function init( api ) {
		component.api = api;
		component.overrideElementInitialize();
		api.control.each( component.addInputValidityConstraintReporting );
		api.control.bind( 'add', component.addInputValidityConstraintReporting );
	};

	/**
	 * Use the 'input' event to listen for changes to inputs.
	 *
	 * @returns {void}
	 */
	component.overrideElementInitialize = function overrideElementInitialize() {
		var api = component.api;

		/*
		 * The following is forked from core at https://github.com/xwp/wordpress-develop/blob/bbd3e0174edaa0b855a2f3af044ca2272d9c7a2a/src/wp-includes/js/customize-base.js#L528-L571
		 *
		 * This should be committed to core in https://core.trac.wordpress.org/ticket/35832
		 */
		component.api.Element.prototype.initialize = function elementInitialize( element, options ) {
			var self = this,
				synchronizer = api.Element.synchronizer.html,
				type, update, refresh;

			this.element = api.ensure( element );
			this.events = '';

			if ( this.element.is( 'input, select, textarea' ) ) {
				if ( 'radio' === this.element.prop( 'type' ) || 'checkbox' === this.element.prop( 'type' ) ) {
					this.events += 'change';
				} else {
					this.events += 'input';
				}
				synchronizer = api.Element.synchronizer.val;

				if ( this.element.is( 'input' ) ) {
					type = this.element.prop( 'type' );
					if ( api.Element.synchronizer[type] ) {
						synchronizer = api.Element.synchronizer[type];
					}
				}
			}

			api.Value.prototype.initialize.call( this, null, $.extend( options || {}, synchronizer ) );
			this._value = this.get();

			update = this.update;
			refresh = this.refresh;

			this.update = function( to ) {
				if ( to !== refresh.call( self ) ) {
					update.apply( this, arguments );
				}
			};
			this.refresh = function() {
				self.set( refresh.call( self ) );
			};

			this.bind( this.update );
			this.element.bind( this.events, this.refresh );
		};
	};

	/**
	 * Add reporting of input validity constraints.
	 *
	 * @param {wp.customize.Control} control Control.
	 * @returns {void}
	 */
	component.addInputValidityConstraintReporting = function addInputValidityConstraintReporting( control ) {
		control.deferred.embedded.done( function() {
			var $input, input;
			if ( ! control.setting ) {
				return;
			}
			$input = control.container.find( 'input, textarea, select' ).first();
			if ( 1 !== $input.length ) {
				return;
			}
			input = $input[0];
			control.setting.bind( _.debounce( function() {
				try {
					if ( input.reportValidity ) {
						input.reportValidity();
					}
				} catch ( e ) {
					return;
				}
			}, component.api.settings.timeouts.windowRefresh ) );
		} );
	};

	// @todo Trigger update for other types of settings.

	return component;
})( jQuery );
