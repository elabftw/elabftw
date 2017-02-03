//
// ChemDoodle Web Components 7.0.2
//
// http://web.chemdoodle.com
//
// Copyright 2009-2015 iChemLabs, LLC.  All rights reserved.
//
// The ChemDoodle Web Components library is licensed under version 3
// of the GNU GENERAL PUBLIC LICENSE.
//
// You may redistribute it and/or modify it under the terms of the
// GNU General Public License as published by the Free Software Foundation,
// either version 3 of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
// Please contact iChemLabs <http://www.ichemlabs.com/contact-us> for
// alternate licensing options.
//
/*! jQuery UI - v1.10.3 - 2014-01-01
* http://jqueryui.com
* Includes: jquery.ui.core.js, jquery.ui.widget.js, jquery.ui.mouse.js, jquery.ui.position.js, jquery.ui.draggable.js, jquery.ui.droppable.js, jquery.ui.resizable.js, jquery.ui.selectable.js, jquery.ui.sortable.js, jquery.ui.button.js, jquery.ui.dialog.js, jquery.ui.menu.js, jquery.ui.progressbar.js, jquery.ui.slider.js, jquery.ui.spinner.js, jquery.ui.tabs.js, jquery.ui.tooltip.js
* Copyright 2014 jQuery Foundation and other contributors; Licensed MIT */
(function(jQuery) {
(function( $, undefined ) {

var uuid = 0,
	runiqueId = /^ui-id-\d+$/;

// $.ui might exist from components with no dependencies, e.g., $.ui.position
$.ui = $.ui || {};

$.extend( $.ui, {
	version: "1.10.3",

	keyCode: {
		BACKSPACE: 8,
		COMMA: 188,
		DELETE: 46,
		DOWN: 40,
		END: 35,
		ENTER: 13,
		ESCAPE: 27,
		HOME: 36,
		LEFT: 37,
		NUMPAD_ADD: 107,
		NUMPAD_DECIMAL: 110,
		NUMPAD_DIVIDE: 111,
		NUMPAD_ENTER: 108,
		NUMPAD_MULTIPLY: 106,
		NUMPAD_SUBTRACT: 109,
		PAGE_DOWN: 34,
		PAGE_UP: 33,
		PERIOD: 190,
		RIGHT: 39,
		SPACE: 32,
		TAB: 9,
		UP: 38
	}
});

// plugins
$.fn.extend({
	focus: (function( orig ) {
		return function( delay, fn ) {
			return typeof delay === "number" ?
				this.each(function() {
					var elem = this;
					setTimeout(function() {
						$( elem ).focus();
						if ( fn ) {
							fn.call( elem );
						}
					}, delay );
				}) :
				orig.apply( this, arguments );
		};
	})( $.fn.focus ),

	scrollParent: function() {
		var scrollParent;
		if (($.ui.ie && (/(static|relative)/).test(this.css("position"))) || (/absolute/).test(this.css("position"))) {
			scrollParent = this.parents().filter(function() {
				return (/(relative|absolute|fixed)/).test($.css(this,"position")) && (/(auto|scroll)/).test($.css(this,"overflow")+$.css(this,"overflow-y")+$.css(this,"overflow-x"));
			}).eq(0);
		} else {
			scrollParent = this.parents().filter(function() {
				return (/(auto|scroll)/).test($.css(this,"overflow")+$.css(this,"overflow-y")+$.css(this,"overflow-x"));
			}).eq(0);
		}

		return (/fixed/).test(this.css("position")) || !scrollParent.length ? $(document) : scrollParent;
	},

	zIndex: function( zIndex ) {
		if ( zIndex !== undefined ) {
			return this.css( "zIndex", zIndex );
		}

		if ( this.length ) {
			var elem = $( this[ 0 ] ), position, value;
			while ( elem.length && elem[ 0 ] !== document ) {
				// Ignore z-index if position is set to a value where z-index is ignored by the browser
				// This makes behavior of this function consistent across browsers
				// WebKit always returns auto if the element is positioned
				position = elem.css( "position" );
				if ( position === "absolute" || position === "relative" || position === "fixed" ) {
					// IE returns 0 when zIndex is not specified
					// other browsers return a string
					// we ignore the case of nested elements with an explicit value of 0
					// <div style="z-index: -10;"><div style="z-index: 0;"></div></div>
					value = parseInt( elem.css( "zIndex" ), 10 );
					if ( !isNaN( value ) && value !== 0 ) {
						return value;
					}
				}
				elem = elem.parent();
			}
		}

		return 0;
	},

	uniqueId: function() {
		return this.each(function() {
			if ( !this.id ) {
				this.id = "ui-id-" + (++uuid);
			}
		});
	},

	removeUniqueId: function() {
		return this.each(function() {
			if ( runiqueId.test( this.id ) ) {
				$( this ).removeAttr( "id" );
			}
		});
	}
});

// selectors
function focusable( element, isTabIndexNotNaN ) {
	var map, mapName, img,
		nodeName = element.nodeName.toLowerCase();
	if ( "area" === nodeName ) {
		map = element.parentNode;
		mapName = map.name;
		if ( !element.href || !mapName || map.nodeName.toLowerCase() !== "map" ) {
			return false;
		}
		img = $( "img[usemap=#" + mapName + "]" )[0];
		return !!img && visible( img );
	}
	return ( /input|select|textarea|button|object/.test( nodeName ) ?
		!element.disabled :
		"a" === nodeName ?
			element.href || isTabIndexNotNaN :
			isTabIndexNotNaN) &&
		// the element and all of its ancestors must be visible
		visible( element );
}

function visible( element ) {
	return $.expr.filters.visible( element ) &&
		!$( element ).parents().addBack().filter(function() {
			return $.css( this, "visibility" ) === "hidden";
		}).length;
}

$.extend( $.expr[ ":" ], {
	data: $.expr.createPseudo ?
		$.expr.createPseudo(function( dataName ) {
			return function( elem ) {
				return !!$.data( elem, dataName );
			};
		}) :
		// support: jQuery <1.8
		function( elem, i, match ) {
			return !!$.data( elem, match[ 3 ] );
		},

	focusable: function( element ) {
		return focusable( element, !isNaN( $.attr( element, "tabindex" ) ) );
	},

	tabbable: function( element ) {
		var tabIndex = $.attr( element, "tabindex" ),
			isTabIndexNaN = isNaN( tabIndex );
		return ( isTabIndexNaN || tabIndex >= 0 ) && focusable( element, !isTabIndexNaN );
	}
});

// support: jQuery <1.8
if ( !$( "<a>" ).outerWidth( 1 ).jquery ) {
	$.each( [ "Width", "Height" ], function( i, name ) {
		var side = name === "Width" ? [ "Left", "Right" ] : [ "Top", "Bottom" ],
			type = name.toLowerCase(),
			orig = {
				innerWidth: $.fn.innerWidth,
				innerHeight: $.fn.innerHeight,
				outerWidth: $.fn.outerWidth,
				outerHeight: $.fn.outerHeight
			};

		function reduce( elem, size, border, margin ) {
			$.each( side, function() {
				size -= parseFloat( $.css( elem, "padding" + this ) ) || 0;
				if ( border ) {
					size -= parseFloat( $.css( elem, "border" + this + "Width" ) ) || 0;
				}
				if ( margin ) {
					size -= parseFloat( $.css( elem, "margin" + this ) ) || 0;
				}
			});
			return size;
		}

		$.fn[ "inner" + name ] = function( size ) {
			if ( size === undefined ) {
				return orig[ "inner" + name ].call( this );
			}

			return this.each(function() {
				$( this ).css( type, reduce( this, size ) + "px" );
			});
		};

		$.fn[ "outer" + name] = function( size, margin ) {
			if ( typeof size !== "number" ) {
				return orig[ "outer" + name ].call( this, size );
			}

			return this.each(function() {
				$( this).css( type, reduce( this, size, true, margin ) + "px" );
			});
		};
	});
}

// support: jQuery <1.8
if ( !$.fn.addBack ) {
	$.fn.addBack = function( selector ) {
		return this.add( selector == null ?
			this.prevObject : this.prevObject.filter( selector )
		);
	};
}

// support: jQuery 1.6.1, 1.6.2 (http://bugs.jquery.com/ticket/9413)
if ( $( "<a>" ).data( "a-b", "a" ).removeData( "a-b" ).data( "a-b" ) ) {
	$.fn.removeData = (function( removeData ) {
		return function( key ) {
			if ( arguments.length ) {
				return removeData.call( this, $.camelCase( key ) );
			} else {
				return removeData.call( this );
			}
		};
	})( $.fn.removeData );
}





// deprecated
$.ui.ie = !!/msie [\w.]+/.exec( navigator.userAgent.toLowerCase() );

$.support.selectstart = "onselectstart" in document.createElement( "div" );
$.fn.extend({
	disableSelection: function() {
		return this.bind( ( $.support.selectstart ? "selectstart" : "mousedown" ) +
			".ui-disableSelection", function( event ) {
				event.preventDefault();
			});
	},

	enableSelection: function() {
		return this.unbind( ".ui-disableSelection" );
	}
});

$.extend( $.ui, {
	// $.ui.plugin is deprecated. Use $.widget() extensions instead.
	plugin: {
		add: function( module, option, set ) {
			var i,
				proto = $.ui[ module ].prototype;
			for ( i in set ) {
				proto.plugins[ i ] = proto.plugins[ i ] || [];
				proto.plugins[ i ].push( [ option, set[ i ] ] );
			}
		},
		call: function( instance, name, args ) {
			var i,
				set = instance.plugins[ name ];
			if ( !set || !instance.element[ 0 ].parentNode || instance.element[ 0 ].parentNode.nodeType === 11 ) {
				return;
			}

			for ( i = 0; i < set.length; i++ ) {
				if ( instance.options[ set[ i ][ 0 ] ] ) {
					set[ i ][ 1 ].apply( instance.element, args );
				}
			}
		}
	},

	// only used by resizable
	hasScroll: function( el, a ) {

		//If overflow is hidden, the element might have extra content, but the user wants to hide it
		if ( $( el ).css( "overflow" ) === "hidden") {
			return false;
		}

		var scroll = ( a && a === "left" ) ? "scrollLeft" : "scrollTop",
			has = false;

		if ( el[ scroll ] > 0 ) {
			return true;
		}

		// TODO: determine which cases actually cause this to happen
		// if the element doesn't have the scroll set, see if it's possible to
		// set the scroll
		el[ scroll ] = 1;
		has = ( el[ scroll ] > 0 );
		el[ scroll ] = 0;
		return has;
	}
});

})( jQuery );
(function( $, undefined ) {

var uuid = 0,
	slice = Array.prototype.slice,
	_cleanData = $.cleanData;
$.cleanData = function( elems ) {
	for ( var i = 0, elem; (elem = elems[i]) != null; i++ ) {
		try {
			$( elem ).triggerHandler( "remove" );
		// http://bugs.jquery.com/ticket/8235
		} catch( e ) {}
	}
	_cleanData( elems );
};

$.widget = function( name, base, prototype ) {
	var fullName, existingConstructor, constructor, basePrototype,
		// proxiedPrototype allows the provided prototype to remain unmodified
		// so that it can be used as a mixin for multiple widgets (#8876)
		proxiedPrototype = {},
		namespace = name.split( "." )[ 0 ];

	name = name.split( "." )[ 1 ];
	fullName = namespace + "-" + name;

	if ( !prototype ) {
		prototype = base;
		base = $.Widget;
	}

	// create selector for plugin
	$.expr[ ":" ][ fullName.toLowerCase() ] = function( elem ) {
		return !!$.data( elem, fullName );
	};

	$[ namespace ] = $[ namespace ] || {};
	existingConstructor = $[ namespace ][ name ];
	constructor = $[ namespace ][ name ] = function( options, element ) {
		// allow instantiation without "new" keyword
		if ( !this._createWidget ) {
			return new constructor( options, element );
		}

		// allow instantiation without initializing for simple inheritance
		// must use "new" keyword (the code above always passes args)
		if ( arguments.length ) {
			this._createWidget( options, element );
		}
	};
	// extend with the existing constructor to carry over any static properties
	$.extend( constructor, existingConstructor, {
		version: prototype.version,
		// copy the object used to create the prototype in case we need to
		// redefine the widget later
		_proto: $.extend( {}, prototype ),
		// track widgets that inherit from this widget in case this widget is
		// redefined after a widget inherits from it
		_childConstructors: []
	});

	basePrototype = new base();
	// we need to make the options hash a property directly on the new instance
	// otherwise we'll modify the options hash on the prototype that we're
	// inheriting from
	basePrototype.options = $.widget.extend( {}, basePrototype.options );
	$.each( prototype, function( prop, value ) {
		if ( !$.isFunction( value ) ) {
			proxiedPrototype[ prop ] = value;
			return;
		}
		proxiedPrototype[ prop ] = (function() {
			var _super = function() {
					return base.prototype[ prop ].apply( this, arguments );
				},
				_superApply = function( args ) {
					return base.prototype[ prop ].apply( this, args );
				};
			return function() {
				var __super = this._super,
					__superApply = this._superApply,
					returnValue;

				this._super = _super;
				this._superApply = _superApply;

				returnValue = value.apply( this, arguments );

				this._super = __super;
				this._superApply = __superApply;

				return returnValue;
			};
		})();
	});
	constructor.prototype = $.widget.extend( basePrototype, {
		// TODO: remove support for widgetEventPrefix
		// always use the name + a colon as the prefix, e.g., draggable:start
		// don't prefix for widgets that aren't DOM-based
		widgetEventPrefix: existingConstructor ? basePrototype.widgetEventPrefix : name
	}, proxiedPrototype, {
		constructor: constructor,
		namespace: namespace,
		widgetName: name,
		widgetFullName: fullName
	});

	// If this widget is being redefined then we need to find all widgets that
	// are inheriting from it and redefine all of them so that they inherit from
	// the new version of this widget. We're essentially trying to replace one
	// level in the prototype chain.
	if ( existingConstructor ) {
		$.each( existingConstructor._childConstructors, function( i, child ) {
			var childPrototype = child.prototype;

			// redefine the child widget using the same prototype that was
			// originally used, but inherit from the new version of the base
			$.widget( childPrototype.namespace + "." + childPrototype.widgetName, constructor, child._proto );
		});
		// remove the list of existing child constructors from the old constructor
		// so the old child constructors can be garbage collected
		delete existingConstructor._childConstructors;
	} else {
		base._childConstructors.push( constructor );
	}

	$.widget.bridge( name, constructor );
};

$.widget.extend = function( target ) {
	var input = slice.call( arguments, 1 ),
		inputIndex = 0,
		inputLength = input.length,
		key,
		value;
	for ( ; inputIndex < inputLength; inputIndex++ ) {
		for ( key in input[ inputIndex ] ) {
			value = input[ inputIndex ][ key ];
			if ( input[ inputIndex ].hasOwnProperty( key ) && value !== undefined ) {
				// Clone objects
				if ( $.isPlainObject( value ) ) {
					target[ key ] = $.isPlainObject( target[ key ] ) ?
						$.widget.extend( {}, target[ key ], value ) :
						// Don't extend strings, arrays, etc. with objects
						$.widget.extend( {}, value );
				// Copy everything else by reference
				} else {
					target[ key ] = value;
				}
			}
		}
	}
	return target;
};

$.widget.bridge = function( name, object ) {
	var fullName = object.prototype.widgetFullName || name;
	$.fn[ name ] = function( options ) {
		var isMethodCall = typeof options === "string",
			args = slice.call( arguments, 1 ),
			returnValue = this;

		// allow multiple hashes to be passed on init
		options = !isMethodCall && args.length ?
			$.widget.extend.apply( null, [ options ].concat(args) ) :
			options;

		if ( isMethodCall ) {
			this.each(function() {
				var methodValue,
					instance = $.data( this, fullName );
				if ( !instance ) {
					return $.error( "cannot call methods on " + name + " prior to initialization; " +
						"attempted to call method '" + options + "'" );
				}
				if ( !$.isFunction( instance[options] ) || options.charAt( 0 ) === "_" ) {
					return $.error( "no such method '" + options + "' for " + name + " widget instance" );
				}
				methodValue = instance[ options ].apply( instance, args );
				if ( methodValue !== instance && methodValue !== undefined ) {
					returnValue = methodValue && methodValue.jquery ?
						returnValue.pushStack( methodValue.get() ) :
						methodValue;
					return false;
				}
			});
		} else {
			this.each(function() {
				var instance = $.data( this, fullName );
				if ( instance ) {
					instance.option( options || {} )._init();
				} else {
					$.data( this, fullName, new object( options, this ) );
				}
			});
		}

		return returnValue;
	};
};

$.Widget = function( /* options, element */ ) {};
$.Widget._childConstructors = [];

$.Widget.prototype = {
	widgetName: "widget",
	widgetEventPrefix: "",
	defaultElement: "<div>",
	options: {
		disabled: false,

		// callbacks
		create: null
	},
	_createWidget: function( options, element ) {
		element = $( element || this.defaultElement || this )[ 0 ];
		this.element = $( element );
		this.uuid = uuid++;
		this.eventNamespace = "." + this.widgetName + this.uuid;
		this.options = $.widget.extend( {},
			this.options,
			this._getCreateOptions(),
			options );

		this.bindings = $();
		this.hoverable = $();
		this.focusable = $();

		if ( element !== this ) {
			$.data( element, this.widgetFullName, this );
			this._on( true, this.element, {
				remove: function( event ) {
					if ( event.target === element ) {
						this.destroy();
					}
				}
			});
			this.document = $( element.style ?
				// element within the document
				element.ownerDocument :
				// element is window or document
				element.document || element );
			this.window = $( this.document[0].defaultView || this.document[0].parentWindow );
		}

		this._create();
		this._trigger( "create", null, this._getCreateEventData() );
		this._init();
	},
	_getCreateOptions: $.noop,
	_getCreateEventData: $.noop,
	_create: $.noop,
	_init: $.noop,

	destroy: function() {
		this._destroy();
		// we can probably remove the unbind calls in 2.0
		// all event bindings should go through this._on()
		this.element
			.unbind( this.eventNamespace )
			// 1.9 BC for #7810
			// TODO remove dual storage
			.removeData( this.widgetName )
			.removeData( this.widgetFullName )
			// support: jquery <1.6.3
			// http://bugs.jquery.com/ticket/9413
			.removeData( $.camelCase( this.widgetFullName ) );
		this.widget()
			.unbind( this.eventNamespace )
			.removeAttr( "aria-disabled" )
			.removeClass(
				this.widgetFullName + "-disabled " +
				"ui-state-disabled" );

		// clean up events and states
		this.bindings.unbind( this.eventNamespace );
		this.hoverable.removeClass( "ui-state-hover" );
		this.focusable.removeClass( "ui-state-focus" );
	},
	_destroy: $.noop,

	widget: function() {
		return this.element;
	},

	option: function( key, value ) {
		var options = key,
			parts,
			curOption,
			i;

		if ( arguments.length === 0 ) {
			// don't return a reference to the internal hash
			return $.widget.extend( {}, this.options );
		}

		if ( typeof key === "string" ) {
			// handle nested keys, e.g., "foo.bar" => { foo: { bar: ___ } }
			options = {};
			parts = key.split( "." );
			key = parts.shift();
			if ( parts.length ) {
				curOption = options[ key ] = $.widget.extend( {}, this.options[ key ] );
				for ( i = 0; i < parts.length - 1; i++ ) {
					curOption[ parts[ i ] ] = curOption[ parts[ i ] ] || {};
					curOption = curOption[ parts[ i ] ];
				}
				key = parts.pop();
				if ( value === undefined ) {
					return curOption[ key ] === undefined ? null : curOption[ key ];
				}
				curOption[ key ] = value;
			} else {
				if ( value === undefined ) {
					return this.options[ key ] === undefined ? null : this.options[ key ];
				}
				options[ key ] = value;
			}
		}

		this._setOptions( options );

		return this;
	},
	_setOptions: function( options ) {
		var key;

		for ( key in options ) {
			this._setOption( key, options[ key ] );
		}

		return this;
	},
	_setOption: function( key, value ) {
		this.options[ key ] = value;

		if ( key === "disabled" ) {
			this.widget()
				.toggleClass( this.widgetFullName + "-disabled ui-state-disabled", !!value )
				.attr( "aria-disabled", value );
			this.hoverable.removeClass( "ui-state-hover" );
			this.focusable.removeClass( "ui-state-focus" );
		}

		return this;
	},

	enable: function() {
		return this._setOption( "disabled", false );
	},
	disable: function() {
		return this._setOption( "disabled", true );
	},

	_on: function( suppressDisabledCheck, element, handlers ) {
		var delegateElement,
			instance = this;

		// no suppressDisabledCheck flag, shuffle arguments
		if ( typeof suppressDisabledCheck !== "boolean" ) {
			handlers = element;
			element = suppressDisabledCheck;
			suppressDisabledCheck = false;
		}

		// no element argument, shuffle and use this.element
		if ( !handlers ) {
			handlers = element;
			element = this.element;
			delegateElement = this.widget();
		} else {
			// accept selectors, DOM elements
			element = delegateElement = $( element );
			this.bindings = this.bindings.add( element );
		}

		$.each( handlers, function( event, handler ) {
			function handlerProxy() {
				// allow widgets to customize the disabled handling
				// - disabled as an array instead of boolean
				// - disabled class as method for disabling individual parts
				if ( !suppressDisabledCheck &&
						( instance.options.disabled === true ||
							$( this ).hasClass( "ui-state-disabled" ) ) ) {
					return;
				}
				return ( typeof handler === "string" ? instance[ handler ] : handler )
					.apply( instance, arguments );
			}

			// copy the guid so direct unbinding works
			if ( typeof handler !== "string" ) {
				handlerProxy.guid = handler.guid =
					handler.guid || handlerProxy.guid || $.guid++;
			}

			var match = event.match( /^(\w+)\s*(.*)$/ ),
				eventName = match[1] + instance.eventNamespace,
				selector = match[2];
			if ( selector ) {
				delegateElement.delegate( selector, eventName, handlerProxy );
			} else {
				element.bind( eventName, handlerProxy );
			}
		});
	},

	_off: function( element, eventName ) {
		eventName = (eventName || "").split( " " ).join( this.eventNamespace + " " ) + this.eventNamespace;
		element.unbind( eventName ).undelegate( eventName );
	},

	_delay: function( handler, delay ) {
		function handlerProxy() {
			return ( typeof handler === "string" ? instance[ handler ] : handler )
				.apply( instance, arguments );
		}
		var instance = this;
		return setTimeout( handlerProxy, delay || 0 );
	},

	_hoverable: function( element ) {
		this.hoverable = this.hoverable.add( element );
		this._on( element, {
			mouseenter: function( event ) {
				$( event.currentTarget ).addClass( "ui-state-hover" );
			},
			mouseleave: function( event ) {
				$( event.currentTarget ).removeClass( "ui-state-hover" );
			}
		});
	},

	_focusable: function( element ) {
		this.focusable = this.focusable.add( element );
		this._on( element, {
			focusin: function( event ) {
				$( event.currentTarget ).addClass( "ui-state-focus" );
			},
			focusout: function( event ) {
				$( event.currentTarget ).removeClass( "ui-state-focus" );
			}
		});
	},

	_trigger: function( type, event, data ) {
		var prop, orig,
			callback = this.options[ type ];

		data = data || {};
		event = $.Event( event );
		event.type = ( type === this.widgetEventPrefix ?
			type :
			this.widgetEventPrefix + type ).toLowerCase();
		// the original event may come from any element
		// so we need to reset the target on the new event
		event.target = this.element[ 0 ];

		// copy original event properties over to the new event
		orig = event.originalEvent;
		if ( orig ) {
			for ( prop in orig ) {
				if ( !( prop in event ) ) {
					event[ prop ] = orig[ prop ];
				}
			}
		}

		this.element.trigger( event, data );
		return !( $.isFunction( callback ) &&
			callback.apply( this.element[0], [ event ].concat( data ) ) === false ||
			event.isDefaultPrevented() );
	}
};

$.each( { show: "fadeIn", hide: "fadeOut" }, function( method, defaultEffect ) {
	$.Widget.prototype[ "_" + method ] = function( element, options, callback ) {
		if ( typeof options === "string" ) {
			options = { effect: options };
		}
		var hasOptions,
			effectName = !options ?
				method :
				options === true || typeof options === "number" ?
					defaultEffect :
					options.effect || defaultEffect;
		options = options || {};
		if ( typeof options === "number" ) {
			options = { duration: options };
		}
		hasOptions = !$.isEmptyObject( options );
		options.complete = callback;
		if ( options.delay ) {
			element.delay( options.delay );
		}
		if ( hasOptions && $.effects && $.effects.effect[ effectName ] ) {
			element[ method ]( options );
		} else if ( effectName !== method && element[ effectName ] ) {
			element[ effectName ]( options.duration, options.easing, callback );
		} else {
			element.queue(function( next ) {
				$( this )[ method ]();
				if ( callback ) {
					callback.call( element[ 0 ] );
				}
				next();
			});
		}
	};
});

})( jQuery );
(function( $, undefined ) {

var mouseHandled = false;
$( document ).mouseup( function() {
	mouseHandled = false;
});

$.widget("ui.mouse", {
	version: "1.10.3",
	options: {
		cancel: "input,textarea,button,select,option",
		distance: 1,
		delay: 0
	},
	_mouseInit: function() {
		var that = this;

		this.element
			.bind("mousedown."+this.widgetName, function(event) {
				return that._mouseDown(event);
			})
			.bind("click."+this.widgetName, function(event) {
				if (true === $.data(event.target, that.widgetName + ".preventClickEvent")) {
					$.removeData(event.target, that.widgetName + ".preventClickEvent");
					event.stopImmediatePropagation();
					return false;
				}
			});

		this.started = false;
	},

	// TODO: make sure destroying one instance of mouse doesn't mess with
	// other instances of mouse
	_mouseDestroy: function() {
		this.element.unbind("."+this.widgetName);
		if ( this._mouseMoveDelegate ) {
			$(document)
				.unbind("mousemove."+this.widgetName, this._mouseMoveDelegate)
				.unbind("mouseup."+this.widgetName, this._mouseUpDelegate);
		}
	},

	_mouseDown: function(event) {
		// don't let more than one widget handle mouseStart
		if( mouseHandled ) { return; }

		// we may have missed mouseup (out of window)
		(this._mouseStarted && this._mouseUp(event));

		this._mouseDownEvent = event;

		var that = this,
			btnIsLeft = (event.which === 1),
			// event.target.nodeName works around a bug in IE 8 with
			// disabled inputs (#7620)
			elIsCancel = (typeof this.options.cancel === "string" && event.target.nodeName ? $(event.target).closest(this.options.cancel).length : false);
		if (!btnIsLeft || elIsCancel || !this._mouseCapture(event)) {
			return true;
		}

		this.mouseDelayMet = !this.options.delay;
		if (!this.mouseDelayMet) {
			this._mouseDelayTimer = setTimeout(function() {
				that.mouseDelayMet = true;
			}, this.options.delay);
		}

		if (this._mouseDistanceMet(event) && this._mouseDelayMet(event)) {
			this._mouseStarted = (this._mouseStart(event) !== false);
			if (!this._mouseStarted) {
				event.preventDefault();
				return true;
			}
		}

		// Click event may never have fired (Gecko & Opera)
		if (true === $.data(event.target, this.widgetName + ".preventClickEvent")) {
			$.removeData(event.target, this.widgetName + ".preventClickEvent");
		}

		// these delegates are required to keep context
		this._mouseMoveDelegate = function(event) {
			return that._mouseMove(event);
		};
		this._mouseUpDelegate = function(event) {
			return that._mouseUp(event);
		};
		$(document)
			.bind("mousemove."+this.widgetName, this._mouseMoveDelegate)
			.bind("mouseup."+this.widgetName, this._mouseUpDelegate);

		event.preventDefault();

		mouseHandled = true;
		return true;
	},

	_mouseMove: function(event) {
		// IE mouseup check - mouseup happened when mouse was out of window
		if ($.ui.ie && ( !document.documentMode || document.documentMode < 9 ) && !event.button) {
			return this._mouseUp(event);
		}

		if (this._mouseStarted) {
			this._mouseDrag(event);
			return event.preventDefault();
		}

		if (this._mouseDistanceMet(event) && this._mouseDelayMet(event)) {
			this._mouseStarted =
				(this._mouseStart(this._mouseDownEvent, event) !== false);
			(this._mouseStarted ? this._mouseDrag(event) : this._mouseUp(event));
		}

		return !this._mouseStarted;
	},

	_mouseUp: function(event) {
		$(document)
			.unbind("mousemove."+this.widgetName, this._mouseMoveDelegate)
			.unbind("mouseup."+this.widgetName, this._mouseUpDelegate);

		if (this._mouseStarted) {
			this._mouseStarted = false;

			if (event.target === this._mouseDownEvent.target) {
				$.data(event.target, this.widgetName + ".preventClickEvent", true);
			}

			this._mouseStop(event);
		}

		return false;
	},

	_mouseDistanceMet: function(event) {
		return (Math.max(
				Math.abs(this._mouseDownEvent.pageX - event.pageX),
				Math.abs(this._mouseDownEvent.pageY - event.pageY)
			) >= this.options.distance
		);
	},

	_mouseDelayMet: function(/* event */) {
		return this.mouseDelayMet;
	},

	// These are placeholder methods, to be overriden by extending plugin
	_mouseStart: function(/* event */) {},
	_mouseDrag: function(/* event */) {},
	_mouseStop: function(/* event */) {},
	_mouseCapture: function(/* event */) { return true; }
});

})(jQuery);
(function( $, undefined ) {

$.ui = $.ui || {};

var cachedScrollbarWidth,
	max = Math.max,
	abs = Math.abs,
	round = Math.round,
	rhorizontal = /left|center|right/,
	rvertical = /top|center|bottom/,
	roffset = /[\+\-]\d+(\.[\d]+)?%?/,
	rposition = /^\w+/,
	rpercent = /%$/,
	_position = $.fn.position;

function getOffsets( offsets, width, height ) {
	return [
		parseFloat( offsets[ 0 ] ) * ( rpercent.test( offsets[ 0 ] ) ? width / 100 : 1 ),
		parseFloat( offsets[ 1 ] ) * ( rpercent.test( offsets[ 1 ] ) ? height / 100 : 1 )
	];
}

function parseCss( element, property ) {
	return parseInt( $.css( element, property ), 10 ) || 0;
}

function getDimensions( elem ) {
	var raw = elem[0];
	if ( raw.nodeType === 9 ) {
		return {
			width: elem.width(),
			height: elem.height(),
			offset: { top: 0, left: 0 }
		};
	}
	if ( $.isWindow( raw ) ) {
		return {
			width: elem.width(),
			height: elem.height(),
			offset: { top: elem.scrollTop(), left: elem.scrollLeft() }
		};
	}
	if ( raw.preventDefault ) {
		return {
			width: 0,
			height: 0,
			offset: { top: raw.pageY, left: raw.pageX }
		};
	}
	return {
		width: elem.outerWidth(),
		height: elem.outerHeight(),
		offset: elem.offset()
	};
}

$.position = {
	scrollbarWidth: function() {
		if ( cachedScrollbarWidth !== undefined ) {
			return cachedScrollbarWidth;
		}
		var w1, w2,
			div = $( "<div style='display:block;width:50px;height:50px;overflow:hidden;'><div style='height:100px;width:auto;'></div></div>" ),
			innerDiv = div.children()[0];

		$( "body" ).append( div );
		w1 = innerDiv.offsetWidth;
		div.css( "overflow", "scroll" );

		w2 = innerDiv.offsetWidth;

		if ( w1 === w2 ) {
			w2 = div[0].clientWidth;
		}

		div.remove();

		return (cachedScrollbarWidth = w1 - w2);
	},
	getScrollInfo: function( within ) {
		var overflowX = within.isWindow ? "" : within.element.css( "overflow-x" ),
			overflowY = within.isWindow ? "" : within.element.css( "overflow-y" ),
			hasOverflowX = overflowX === "scroll" ||
				( overflowX === "auto" && within.width < within.element[0].scrollWidth ),
			hasOverflowY = overflowY === "scroll" ||
				( overflowY === "auto" && within.height < within.element[0].scrollHeight );
		return {
			width: hasOverflowY ? $.position.scrollbarWidth() : 0,
			height: hasOverflowX ? $.position.scrollbarWidth() : 0
		};
	},
	getWithinInfo: function( element ) {
		var withinElement = $( element || window ),
			isWindow = $.isWindow( withinElement[0] );
		return {
			element: withinElement,
			isWindow: isWindow,
			offset: withinElement.offset() || { left: 0, top: 0 },
			scrollLeft: withinElement.scrollLeft(),
			scrollTop: withinElement.scrollTop(),
			width: isWindow ? withinElement.width() : withinElement.outerWidth(),
			height: isWindow ? withinElement.height() : withinElement.outerHeight()
		};
	}
};

$.fn.position = function( options ) {
	if ( !options || !options.of ) {
		return _position.apply( this, arguments );
	}

	// make a copy, we don't want to modify arguments
	options = $.extend( {}, options );

	var atOffset, targetWidth, targetHeight, targetOffset, basePosition, dimensions,
		target = $( options.of ),
		within = $.position.getWithinInfo( options.within ),
		scrollInfo = $.position.getScrollInfo( within ),
		collision = ( options.collision || "flip" ).split( " " ),
		offsets = {};

	dimensions = getDimensions( target );
	if ( target[0].preventDefault ) {
		// force left top to allow flipping
		options.at = "left top";
	}
	targetWidth = dimensions.width;
	targetHeight = dimensions.height;
	targetOffset = dimensions.offset;
	// clone to reuse original targetOffset later
	basePosition = $.extend( {}, targetOffset );

	// force my and at to have valid horizontal and vertical positions
	// if a value is missing or invalid, it will be converted to center
	$.each( [ "my", "at" ], function() {
		var pos = ( options[ this ] || "" ).split( " " ),
			horizontalOffset,
			verticalOffset;

		if ( pos.length === 1) {
			pos = rhorizontal.test( pos[ 0 ] ) ?
				pos.concat( [ "center" ] ) :
				rvertical.test( pos[ 0 ] ) ?
					[ "center" ].concat( pos ) :
					[ "center", "center" ];
		}
		pos[ 0 ] = rhorizontal.test( pos[ 0 ] ) ? pos[ 0 ] : "center";
		pos[ 1 ] = rvertical.test( pos[ 1 ] ) ? pos[ 1 ] : "center";

		// calculate offsets
		horizontalOffset = roffset.exec( pos[ 0 ] );
		verticalOffset = roffset.exec( pos[ 1 ] );
		offsets[ this ] = [
			horizontalOffset ? horizontalOffset[ 0 ] : 0,
			verticalOffset ? verticalOffset[ 0 ] : 0
		];

		// reduce to just the positions without the offsets
		options[ this ] = [
			rposition.exec( pos[ 0 ] )[ 0 ],
			rposition.exec( pos[ 1 ] )[ 0 ]
		];
	});

	// normalize collision option
	if ( collision.length === 1 ) {
		collision[ 1 ] = collision[ 0 ];
	}

	if ( options.at[ 0 ] === "right" ) {
		basePosition.left += targetWidth;
	} else if ( options.at[ 0 ] === "center" ) {
		basePosition.left += targetWidth / 2;
	}

	if ( options.at[ 1 ] === "bottom" ) {
		basePosition.top += targetHeight;
	} else if ( options.at[ 1 ] === "center" ) {
		basePosition.top += targetHeight / 2;
	}

	atOffset = getOffsets( offsets.at, targetWidth, targetHeight );
	basePosition.left += atOffset[ 0 ];
	basePosition.top += atOffset[ 1 ];

	return this.each(function() {
		var collisionPosition, using,
			elem = $( this ),
			elemWidth = elem.outerWidth(),
			elemHeight = elem.outerHeight(),
			marginLeft = parseCss( this, "marginLeft" ),
			marginTop = parseCss( this, "marginTop" ),
			collisionWidth = elemWidth + marginLeft + parseCss( this, "marginRight" ) + scrollInfo.width,
			collisionHeight = elemHeight + marginTop + parseCss( this, "marginBottom" ) + scrollInfo.height,
			position = $.extend( {}, basePosition ),
			myOffset = getOffsets( offsets.my, elem.outerWidth(), elem.outerHeight() );

		if ( options.my[ 0 ] === "right" ) {
			position.left -= elemWidth;
		} else if ( options.my[ 0 ] === "center" ) {
			position.left -= elemWidth / 2;
		}

		if ( options.my[ 1 ] === "bottom" ) {
			position.top -= elemHeight;
		} else if ( options.my[ 1 ] === "center" ) {
			position.top -= elemHeight / 2;
		}

		position.left += myOffset[ 0 ];
		position.top += myOffset[ 1 ];

		// if the browser doesn't support fractions, then round for consistent results
		if ( !$.support.offsetFractions ) {
			position.left = round( position.left );
			position.top = round( position.top );
		}

		collisionPosition = {
			marginLeft: marginLeft,
			marginTop: marginTop
		};

		$.each( [ "left", "top" ], function( i, dir ) {
			if ( $.ui.position[ collision[ i ] ] ) {
				$.ui.position[ collision[ i ] ][ dir ]( position, {
					targetWidth: targetWidth,
					targetHeight: targetHeight,
					elemWidth: elemWidth,
					elemHeight: elemHeight,
					collisionPosition: collisionPosition,
					collisionWidth: collisionWidth,
					collisionHeight: collisionHeight,
					offset: [ atOffset[ 0 ] + myOffset[ 0 ], atOffset [ 1 ] + myOffset[ 1 ] ],
					my: options.my,
					at: options.at,
					within: within,
					elem : elem
				});
			}
		});

		if ( options.using ) {
			// adds feedback as second argument to using callback, if present
			using = function( props ) {
				var left = targetOffset.left - position.left,
					right = left + targetWidth - elemWidth,
					top = targetOffset.top - position.top,
					bottom = top + targetHeight - elemHeight,
					feedback = {
						target: {
							element: target,
							left: targetOffset.left,
							top: targetOffset.top,
							width: targetWidth,
							height: targetHeight
						},
						element: {
							element: elem,
							left: position.left,
							top: position.top,
							width: elemWidth,
							height: elemHeight
						},
						horizontal: right < 0 ? "left" : left > 0 ? "right" : "center",
						vertical: bottom < 0 ? "top" : top > 0 ? "bottom" : "middle"
					};
				if ( targetWidth < elemWidth && abs( left + right ) < targetWidth ) {
					feedback.horizontal = "center";
				}
				if ( targetHeight < elemHeight && abs( top + bottom ) < targetHeight ) {
					feedback.vertical = "middle";
				}
				if ( max( abs( left ), abs( right ) ) > max( abs( top ), abs( bottom ) ) ) {
					feedback.important = "horizontal";
				} else {
					feedback.important = "vertical";
				}
				options.using.call( this, props, feedback );
			};
		}

		elem.offset( $.extend( position, { using: using } ) );
	});
};

$.ui.position = {
	fit: {
		left: function( position, data ) {
			var within = data.within,
				withinOffset = within.isWindow ? within.scrollLeft : within.offset.left,
				outerWidth = within.width,
				collisionPosLeft = position.left - data.collisionPosition.marginLeft,
				overLeft = withinOffset - collisionPosLeft,
				overRight = collisionPosLeft + data.collisionWidth - outerWidth - withinOffset,
				newOverRight;

			// element is wider than within
			if ( data.collisionWidth > outerWidth ) {
				// element is initially over the left side of within
				if ( overLeft > 0 && overRight <= 0 ) {
					newOverRight = position.left + overLeft + data.collisionWidth - outerWidth - withinOffset;
					position.left += overLeft - newOverRight;
				// element is initially over right side of within
				} else if ( overRight > 0 && overLeft <= 0 ) {
					position.left = withinOffset;
				// element is initially over both left and right sides of within
				} else {
					if ( overLeft > overRight ) {
						position.left = withinOffset + outerWidth - data.collisionWidth;
					} else {
						position.left = withinOffset;
					}
				}
			// too far left -> align with left edge
			} else if ( overLeft > 0 ) {
				position.left += overLeft;
			// too far right -> align with right edge
			} else if ( overRight > 0 ) {
				position.left -= overRight;
			// adjust based on position and margin
			} else {
				position.left = max( position.left - collisionPosLeft, position.left );
			}
		},
		top: function( position, data ) {
			var within = data.within,
				withinOffset = within.isWindow ? within.scrollTop : within.offset.top,
				outerHeight = data.within.height,
				collisionPosTop = position.top - data.collisionPosition.marginTop,
				overTop = withinOffset - collisionPosTop,
				overBottom = collisionPosTop + data.collisionHeight - outerHeight - withinOffset,
				newOverBottom;

			// element is taller than within
			if ( data.collisionHeight > outerHeight ) {
				// element is initially over the top of within
				if ( overTop > 0 && overBottom <= 0 ) {
					newOverBottom = position.top + overTop + data.collisionHeight - outerHeight - withinOffset;
					position.top += overTop - newOverBottom;
				// element is initially over bottom of within
				} else if ( overBottom > 0 && overTop <= 0 ) {
					position.top = withinOffset;
				// element is initially over both top and bottom of within
				} else {
					if ( overTop > overBottom ) {
						position.top = withinOffset + outerHeight - data.collisionHeight;
					} else {
						position.top = withinOffset;
					}
				}
			// too far up -> align with top
			} else if ( overTop > 0 ) {
				position.top += overTop;
			// too far down -> align with bottom edge
			} else if ( overBottom > 0 ) {
				position.top -= overBottom;
			// adjust based on position and margin
			} else {
				position.top = max( position.top - collisionPosTop, position.top );
			}
		}
	},
	flip: {
		left: function( position, data ) {
			var within = data.within,
				withinOffset = within.offset.left + within.scrollLeft,
				outerWidth = within.width,
				offsetLeft = within.isWindow ? within.scrollLeft : within.offset.left,
				collisionPosLeft = position.left - data.collisionPosition.marginLeft,
				overLeft = collisionPosLeft - offsetLeft,
				overRight = collisionPosLeft + data.collisionWidth - outerWidth - offsetLeft,
				myOffset = data.my[ 0 ] === "left" ?
					-data.elemWidth :
					data.my[ 0 ] === "right" ?
						data.elemWidth :
						0,
				atOffset = data.at[ 0 ] === "left" ?
					data.targetWidth :
					data.at[ 0 ] === "right" ?
						-data.targetWidth :
						0,
				offset = -2 * data.offset[ 0 ],
				newOverRight,
				newOverLeft;

			if ( overLeft < 0 ) {
				newOverRight = position.left + myOffset + atOffset + offset + data.collisionWidth - outerWidth - withinOffset;
				if ( newOverRight < 0 || newOverRight < abs( overLeft ) ) {
					position.left += myOffset + atOffset + offset;
				}
			}
			else if ( overRight > 0 ) {
				newOverLeft = position.left - data.collisionPosition.marginLeft + myOffset + atOffset + offset - offsetLeft;
				if ( newOverLeft > 0 || abs( newOverLeft ) < overRight ) {
					position.left += myOffset + atOffset + offset;
				}
			}
		},
		top: function( position, data ) {
			var within = data.within,
				withinOffset = within.offset.top + within.scrollTop,
				outerHeight = within.height,
				offsetTop = within.isWindow ? within.scrollTop : within.offset.top,
				collisionPosTop = position.top - data.collisionPosition.marginTop,
				overTop = collisionPosTop - offsetTop,
				overBottom = collisionPosTop + data.collisionHeight - outerHeight - offsetTop,
				top = data.my[ 1 ] === "top",
				myOffset = top ?
					-data.elemHeight :
					data.my[ 1 ] === "bottom" ?
						data.elemHeight :
						0,
				atOffset = data.at[ 1 ] === "top" ?
					data.targetHeight :
					data.at[ 1 ] === "bottom" ?
						-data.targetHeight :
						0,
				offset = -2 * data.offset[ 1 ],
				newOverTop,
				newOverBottom;
			if ( overTop < 0 ) {
				newOverBottom = position.top + myOffset + atOffset + offset + data.collisionHeight - outerHeight - withinOffset;
				if ( ( position.top + myOffset + atOffset + offset) > overTop && ( newOverBottom < 0 || newOverBottom < abs( overTop ) ) ) {
					position.top += myOffset + atOffset + offset;
				}
			}
			else if ( overBottom > 0 ) {
				newOverTop = position.top -  data.collisionPosition.marginTop + myOffset + atOffset + offset - offsetTop;
				if ( ( position.top + myOffset + atOffset + offset) > overBottom && ( newOverTop > 0 || abs( newOverTop ) < overBottom ) ) {
					position.top += myOffset + atOffset + offset;
				}
			}
		}
	},
	flipfit: {
		left: function() {
			$.ui.position.flip.left.apply( this, arguments );
			$.ui.position.fit.left.apply( this, arguments );
		},
		top: function() {
			$.ui.position.flip.top.apply( this, arguments );
			$.ui.position.fit.top.apply( this, arguments );
		}
	}
};

// fraction support test
(function () {
	var testElement, testElementParent, testElementStyle, offsetLeft, i,
		body = document.getElementsByTagName( "body" )[ 0 ],
		div = document.createElement( "div" );

	//Create a "fake body" for testing based on method used in jQuery.support
	testElement = document.createElement( body ? "div" : "body" );
	testElementStyle = {
		visibility: "hidden",
		width: 0,
		height: 0,
		border: 0,
		margin: 0,
		background: "none"
	};
	if ( body ) {
		$.extend( testElementStyle, {
			position: "absolute",
			left: "-1000px",
			top: "-1000px"
		});
	}
	for ( i in testElementStyle ) {
		testElement.style[ i ] = testElementStyle[ i ];
	}
	testElement.appendChild( div );
	testElementParent = body || document.documentElement;
	testElementParent.insertBefore( testElement, testElementParent.firstChild );

	div.style.cssText = "position: absolute; left: 10.7432222px;";

	offsetLeft = $( div ).offset().left;
	$.support.offsetFractions = offsetLeft > 10 && offsetLeft < 11;

	testElement.innerHTML = "";
	testElementParent.removeChild( testElement );
})();

}( jQuery ) );
(function( $, undefined ) {

$.widget("ui.draggable", $.ui.mouse, {
	version: "1.10.3",
	widgetEventPrefix: "drag",
	options: {
		addClasses: true,
		appendTo: "parent",
		axis: false,
		connectToSortable: false,
		containment: false,
		cursor: "auto",
		cursorAt: false,
		grid: false,
		handle: false,
		helper: "original",
		iframeFix: false,
		opacity: false,
		refreshPositions: false,
		revert: false,
		revertDuration: 500,
		scope: "default",
		scroll: true,
		scrollSensitivity: 20,
		scrollSpeed: 20,
		snap: false,
		snapMode: "both",
		snapTolerance: 20,
		stack: false,
		zIndex: false,

		// callbacks
		drag: null,
		start: null,
		stop: null
	},
	_create: function() {

		if (this.options.helper === "original" && !(/^(?:r|a|f)/).test(this.element.css("position"))) {
			this.element[0].style.position = "relative";
		}
		if (this.options.addClasses){
			this.element.addClass("ui-draggable");
		}
		if (this.options.disabled){
			this.element.addClass("ui-draggable-disabled");
		}

		this._mouseInit();

	},

	_destroy: function() {
		this.element.removeClass( "ui-draggable ui-draggable-dragging ui-draggable-disabled" );
		this._mouseDestroy();
	},

	_mouseCapture: function(event) {

		var o = this.options;

		// among others, prevent a drag on a resizable-handle
		if (this.helper || o.disabled || $(event.target).closest(".ui-resizable-handle").length > 0) {
			return false;
		}

		//Quit if we're not on a valid handle
		this.handle = this._getHandle(event);
		if (!this.handle) {
			return false;
		}

		$(o.iframeFix === true ? "iframe" : o.iframeFix).each(function() {
			$("<div class='ui-draggable-iframeFix' style='background: #fff;'></div>")
			.css({
				width: this.offsetWidth+"px", height: this.offsetHeight+"px",
				position: "absolute", opacity: "0.001", zIndex: 1000
			})
			.css($(this).offset())
			.appendTo("body");
		});

		return true;

	},

	_mouseStart: function(event) {

		var o = this.options;

		//Create and append the visible helper
		this.helper = this._createHelper(event);

		this.helper.addClass("ui-draggable-dragging");

		//Cache the helper size
		this._cacheHelperProportions();

		//If ddmanager is used for droppables, set the global draggable
		if($.ui.ddmanager) {
			$.ui.ddmanager.current = this;
		}

		/*
		 * - Position generation -
		 * This block generates everything position related - it's the core of draggables.
		 */

		//Cache the margins of the original element
		this._cacheMargins();

		//Store the helper's css position
		this.cssPosition = this.helper.css( "position" );
		this.scrollParent = this.helper.scrollParent();
		this.offsetParent = this.helper.offsetParent();
		this.offsetParentCssPosition = this.offsetParent.css( "position" );

		//The element's absolute position on the page minus margins
		this.offset = this.positionAbs = this.element.offset();
		this.offset = {
			top: this.offset.top - this.margins.top,
			left: this.offset.left - this.margins.left
		};

		//Reset scroll cache
		this.offset.scroll = false;

		$.extend(this.offset, {
			click: { //Where the click happened, relative to the element
				left: event.pageX - this.offset.left,
				top: event.pageY - this.offset.top
			},
			parent: this._getParentOffset(),
			relative: this._getRelativeOffset() //This is a relative to absolute position minus the actual position calculation - only used for relative positioned helper
		});

		//Generate the original position
		this.originalPosition = this.position = this._generatePosition(event);
		this.originalPageX = event.pageX;
		this.originalPageY = event.pageY;

		//Adjust the mouse offset relative to the helper if "cursorAt" is supplied
		(o.cursorAt && this._adjustOffsetFromHelper(o.cursorAt));

		//Set a containment if given in the options
		this._setContainment();

		//Trigger event + callbacks
		if(this._trigger("start", event) === false) {
			this._clear();
			return false;
		}

		//Recache the helper size
		this._cacheHelperProportions();

		//Prepare the droppable offsets
		if ($.ui.ddmanager && !o.dropBehaviour) {
			$.ui.ddmanager.prepareOffsets(this, event);
		}


		this._mouseDrag(event, true); //Execute the drag once - this causes the helper not to be visible before getting its correct position

		//If the ddmanager is used for droppables, inform the manager that dragging has started (see #5003)
		if ( $.ui.ddmanager ) {
			$.ui.ddmanager.dragStart(this, event);
		}

		return true;
	},

	_mouseDrag: function(event, noPropagation) {
		// reset any necessary cached properties (see #5009)
		if ( this.offsetParentCssPosition === "fixed" ) {
			this.offset.parent = this._getParentOffset();
		}

		//Compute the helpers position
		this.position = this._generatePosition(event);
		this.positionAbs = this._convertPositionTo("absolute");

		//Call plugins and callbacks and use the resulting position if something is returned
		if (!noPropagation) {
			var ui = this._uiHash();
			if(this._trigger("drag", event, ui) === false) {
				this._mouseUp({});
				return false;
			}
			this.position = ui.position;
		}

		if(!this.options.axis || this.options.axis !== "y") {
			this.helper[0].style.left = this.position.left+"px";
		}
		if(!this.options.axis || this.options.axis !== "x") {
			this.helper[0].style.top = this.position.top+"px";
		}
		if($.ui.ddmanager) {
			$.ui.ddmanager.drag(this, event);
		}

		return false;
	},

	_mouseStop: function(event) {

		//If we are using droppables, inform the manager about the drop
		var that = this,
			dropped = false;
		if ($.ui.ddmanager && !this.options.dropBehaviour) {
			dropped = $.ui.ddmanager.drop(this, event);
		}

		//if a drop comes from outside (a sortable)
		if(this.dropped) {
			dropped = this.dropped;
			this.dropped = false;
		}

		//if the original element is no longer in the DOM don't bother to continue (see #8269)
		if ( this.options.helper === "original" && !$.contains( this.element[ 0 ].ownerDocument, this.element[ 0 ] ) ) {
			return false;
		}

		if((this.options.revert === "invalid" && !dropped) || (this.options.revert === "valid" && dropped) || this.options.revert === true || ($.isFunction(this.options.revert) && this.options.revert.call(this.element, dropped))) {
			$(this.helper).animate(this.originalPosition, parseInt(this.options.revertDuration, 10), function() {
				if(that._trigger("stop", event) !== false) {
					that._clear();
				}
			});
		} else {
			if(this._trigger("stop", event) !== false) {
				this._clear();
			}
		}

		return false;
	},

	_mouseUp: function(event) {
		//Remove frame helpers
		$("div.ui-draggable-iframeFix").each(function() {
			this.parentNode.removeChild(this);
		});

		//If the ddmanager is used for droppables, inform the manager that dragging has stopped (see #5003)
		if( $.ui.ddmanager ) {
			$.ui.ddmanager.dragStop(this, event);
		}

		return $.ui.mouse.prototype._mouseUp.call(this, event);
	},

	cancel: function() {

		if(this.helper.is(".ui-draggable-dragging")) {
			this._mouseUp({});
		} else {
			this._clear();
		}

		return this;

	},

	_getHandle: function(event) {
		return this.options.handle ?
			!!$( event.target ).closest( this.element.find( this.options.handle ) ).length :
			true;
	},

	_createHelper: function(event) {

		var o = this.options,
			helper = $.isFunction(o.helper) ? $(o.helper.apply(this.element[0], [event])) : (o.helper === "clone" ? this.element.clone().removeAttr("id") : this.element);

		if(!helper.parents("body").length) {
			helper.appendTo((o.appendTo === "parent" ? this.element[0].parentNode : o.appendTo));
		}

		if(helper[0] !== this.element[0] && !(/(fixed|absolute)/).test(helper.css("position"))) {
			helper.css("position", "absolute");
		}

		return helper;

	},

	_adjustOffsetFromHelper: function(obj) {
		if (typeof obj === "string") {
			obj = obj.split(" ");
		}
		if ($.isArray(obj)) {
			obj = {left: +obj[0], top: +obj[1] || 0};
		}
		if ("left" in obj) {
			this.offset.click.left = obj.left + this.margins.left;
		}
		if ("right" in obj) {
			this.offset.click.left = this.helperProportions.width - obj.right + this.margins.left;
		}
		if ("top" in obj) {
			this.offset.click.top = obj.top + this.margins.top;
		}
		if ("bottom" in obj) {
			this.offset.click.top = this.helperProportions.height - obj.bottom + this.margins.top;
		}
	},

	_getParentOffset: function() {

		//Get the offsetParent and cache its position
		var po = this.offsetParent.offset();

		// This is a special case where we need to modify a offset calculated on start, since the following happened:
		// 1. The position of the helper is absolute, so it's position is calculated based on the next positioned parent
		// 2. The actual offset parent is a child of the scroll parent, and the scroll parent isn't the document, which means that
		//    the scroll is included in the initial calculation of the offset of the parent, and never recalculated upon drag
		if(this.cssPosition === "absolute" && this.scrollParent[0] !== document && $.contains(this.scrollParent[0], this.offsetParent[0])) {
			po.left += this.scrollParent.scrollLeft();
			po.top += this.scrollParent.scrollTop();
		}

		//This needs to be actually done for all browsers, since pageX/pageY includes this information
		//Ugly IE fix
		if((this.offsetParent[0] === document.body) ||
			(this.offsetParent[0].tagName && this.offsetParent[0].tagName.toLowerCase() === "html" && $.ui.ie)) {
			po = { top: 0, left: 0 };
		}

		return {
			top: po.top + (parseInt(this.offsetParent.css("borderTopWidth"),10) || 0),
			left: po.left + (parseInt(this.offsetParent.css("borderLeftWidth"),10) || 0)
		};

	},

	_getRelativeOffset: function() {

		if(this.cssPosition === "relative") {
			var p = this.element.position();
			return {
				top: p.top - (parseInt(this.helper.css("top"),10) || 0) + this.scrollParent.scrollTop(),
				left: p.left - (parseInt(this.helper.css("left"),10) || 0) + this.scrollParent.scrollLeft()
			};
		} else {
			return { top: 0, left: 0 };
		}

	},

	_cacheMargins: function() {
		this.margins = {
			left: (parseInt(this.element.css("marginLeft"),10) || 0),
			top: (parseInt(this.element.css("marginTop"),10) || 0),
			right: (parseInt(this.element.css("marginRight"),10) || 0),
			bottom: (parseInt(this.element.css("marginBottom"),10) || 0)
		};
	},

	_cacheHelperProportions: function() {
		this.helperProportions = {
			width: this.helper.outerWidth(),
			height: this.helper.outerHeight()
		};
	},

	_setContainment: function() {

		var over, c, ce,
			o = this.options;

		if ( !o.containment ) {
			this.containment = null;
			return;
		}

		if ( o.containment === "window" ) {
			this.containment = [
				$( window ).scrollLeft() - this.offset.relative.left - this.offset.parent.left,
				$( window ).scrollTop() - this.offset.relative.top - this.offset.parent.top,
				$( window ).scrollLeft() + $( window ).width() - this.helperProportions.width - this.margins.left,
				$( window ).scrollTop() + ( $( window ).height() || document.body.parentNode.scrollHeight ) - this.helperProportions.height - this.margins.top
			];
			return;
		}

		if ( o.containment === "document") {
			this.containment = [
				0,
				0,
				$( document ).width() - this.helperProportions.width - this.margins.left,
				( $( document ).height() || document.body.parentNode.scrollHeight ) - this.helperProportions.height - this.margins.top
			];
			return;
		}

		if ( o.containment.constructor === Array ) {
			this.containment = o.containment;
			return;
		}

		if ( o.containment === "parent" ) {
			o.containment = this.helper[ 0 ].parentNode;
		}

		c = $( o.containment );
		ce = c[ 0 ];

		if( !ce ) {
			return;
		}

		over = c.css( "overflow" ) !== "hidden";

		this.containment = [
			( parseInt( c.css( "borderLeftWidth" ), 10 ) || 0 ) + ( parseInt( c.css( "paddingLeft" ), 10 ) || 0 ),
			( parseInt( c.css( "borderTopWidth" ), 10 ) || 0 ) + ( parseInt( c.css( "paddingTop" ), 10 ) || 0 ) ,
			( over ? Math.max( ce.scrollWidth, ce.offsetWidth ) : ce.offsetWidth ) - ( parseInt( c.css( "borderRightWidth" ), 10 ) || 0 ) - ( parseInt( c.css( "paddingRight" ), 10 ) || 0 ) - this.helperProportions.width - this.margins.left - this.margins.right,
			( over ? Math.max( ce.scrollHeight, ce.offsetHeight ) : ce.offsetHeight ) - ( parseInt( c.css( "borderBottomWidth" ), 10 ) || 0 ) - ( parseInt( c.css( "paddingBottom" ), 10 ) || 0 ) - this.helperProportions.height - this.margins.top  - this.margins.bottom
		];
		this.relative_container = c;
	},

	_convertPositionTo: function(d, pos) {

		if(!pos) {
			pos = this.position;
		}

		var mod = d === "absolute" ? 1 : -1,
			scroll = this.cssPosition === "absolute" && !( this.scrollParent[ 0 ] !== document && $.contains( this.scrollParent[ 0 ], this.offsetParent[ 0 ] ) ) ? this.offsetParent : this.scrollParent;

		//Cache the scroll
		if (!this.offset.scroll) {
			this.offset.scroll = {top : scroll.scrollTop(), left : scroll.scrollLeft()};
		}

		return {
			top: (
				pos.top	+																// The absolute mouse position
				this.offset.relative.top * mod +										// Only for relative positioned nodes: Relative offset from element to offset parent
				this.offset.parent.top * mod -										// The offsetParent's offset without borders (offset + border)
				( ( this.cssPosition === "fixed" ? -this.scrollParent.scrollTop() : this.offset.scroll.top ) * mod )
			),
			left: (
				pos.left +																// The absolute mouse position
				this.offset.relative.left * mod +										// Only for relative positioned nodes: Relative offset from element to offset parent
				this.offset.parent.left * mod	-										// The offsetParent's offset without borders (offset + border)
				( ( this.cssPosition === "fixed" ? -this.scrollParent.scrollLeft() : this.offset.scroll.left ) * mod )
			)
		};

	},

	_generatePosition: function(event) {

		var containment, co, top, left,
			o = this.options,
			scroll = this.cssPosition === "absolute" && !( this.scrollParent[ 0 ] !== document && $.contains( this.scrollParent[ 0 ], this.offsetParent[ 0 ] ) ) ? this.offsetParent : this.scrollParent,
			pageX = event.pageX,
			pageY = event.pageY;

		//Cache the scroll
		if (!this.offset.scroll) {
			this.offset.scroll = {top : scroll.scrollTop(), left : scroll.scrollLeft()};
		}

		/*
		 * - Position constraining -
		 * Constrain the position to a mix of grid, containment.
		 */

		// If we are not dragging yet, we won't check for options
		if ( this.originalPosition ) {
			if ( this.containment ) {
				if ( this.relative_container ){
					co = this.relative_container.offset();
					containment = [
						this.containment[ 0 ] + co.left,
						this.containment[ 1 ] + co.top,
						this.containment[ 2 ] + co.left,
						this.containment[ 3 ] + co.top
					];
				}
				else {
					containment = this.containment;
				}

				if(event.pageX - this.offset.click.left < containment[0]) {
					pageX = containment[0] + this.offset.click.left;
				}
				if(event.pageY - this.offset.click.top < containment[1]) {
					pageY = containment[1] + this.offset.click.top;
				}
				if(event.pageX - this.offset.click.left > containment[2]) {
					pageX = containment[2] + this.offset.click.left;
				}
				if(event.pageY - this.offset.click.top > containment[3]) {
					pageY = containment[3] + this.offset.click.top;
				}
			}

			if(o.grid) {
				//Check for grid elements set to 0 to prevent divide by 0 error causing invalid argument errors in IE (see ticket #6950)
				top = o.grid[1] ? this.originalPageY + Math.round((pageY - this.originalPageY) / o.grid[1]) * o.grid[1] : this.originalPageY;
				pageY = containment ? ((top - this.offset.click.top >= containment[1] || top - this.offset.click.top > containment[3]) ? top : ((top - this.offset.click.top >= containment[1]) ? top - o.grid[1] : top + o.grid[1])) : top;

				left = o.grid[0] ? this.originalPageX + Math.round((pageX - this.originalPageX) / o.grid[0]) * o.grid[0] : this.originalPageX;
				pageX = containment ? ((left - this.offset.click.left >= containment[0] || left - this.offset.click.left > containment[2]) ? left : ((left - this.offset.click.left >= containment[0]) ? left - o.grid[0] : left + o.grid[0])) : left;
			}

		}

		return {
			top: (
				pageY -																	// The absolute mouse position
				this.offset.click.top	-												// Click offset (relative to the element)
				this.offset.relative.top -												// Only for relative positioned nodes: Relative offset from element to offset parent
				this.offset.parent.top +												// The offsetParent's offset without borders (offset + border)
				( this.cssPosition === "fixed" ? -this.scrollParent.scrollTop() : this.offset.scroll.top )
			),
			left: (
				pageX -																	// The absolute mouse position
				this.offset.click.left -												// Click offset (relative to the element)
				this.offset.relative.left -												// Only for relative positioned nodes: Relative offset from element to offset parent
				this.offset.parent.left +												// The offsetParent's offset without borders (offset + border)
				( this.cssPosition === "fixed" ? -this.scrollParent.scrollLeft() : this.offset.scroll.left )
			)
		};

	},

	_clear: function() {
		this.helper.removeClass("ui-draggable-dragging");
		if(this.helper[0] !== this.element[0] && !this.cancelHelperRemoval) {
			this.helper.remove();
		}
		this.helper = null;
		this.cancelHelperRemoval = false;
	},

	// From now on bulk stuff - mainly helpers

	_trigger: function(type, event, ui) {
		ui = ui || this._uiHash();
		$.ui.plugin.call(this, type, [event, ui]);
		//The absolute position has to be recalculated after plugins
		if(type === "drag") {
			this.positionAbs = this._convertPositionTo("absolute");
		}
		return $.Widget.prototype._trigger.call(this, type, event, ui);
	},

	plugins: {},

	_uiHash: function() {
		return {
			helper: this.helper,
			position: this.position,
			originalPosition: this.originalPosition,
			offset: this.positionAbs
		};
	}

});

$.ui.plugin.add("draggable", "connectToSortable", {
	start: function(event, ui) {

		var inst = $(this).data("ui-draggable"), o = inst.options,
			uiSortable = $.extend({}, ui, { item: inst.element });
		inst.sortables = [];
		$(o.connectToSortable).each(function() {
			var sortable = $.data(this, "ui-sortable");
			if (sortable && !sortable.options.disabled) {
				inst.sortables.push({
					instance: sortable,
					shouldRevert: sortable.options.revert
				});
				sortable.refreshPositions();	// Call the sortable's refreshPositions at drag start to refresh the containerCache since the sortable container cache is used in drag and needs to be up to date (this will ensure it's initialised as well as being kept in step with any changes that might have happened on the page).
				sortable._trigger("activate", event, uiSortable);
			}
		});

	},
	stop: function(event, ui) {

		//If we are still over the sortable, we fake the stop event of the sortable, but also remove helper
		var inst = $(this).data("ui-draggable"),
			uiSortable = $.extend({}, ui, { item: inst.element });

		$.each(inst.sortables, function() {
			if(this.instance.isOver) {

				this.instance.isOver = 0;

				inst.cancelHelperRemoval = true; //Don't remove the helper in the draggable instance
				this.instance.cancelHelperRemoval = false; //Remove it in the sortable instance (so sortable plugins like revert still work)

				//The sortable revert is supported, and we have to set a temporary dropped variable on the draggable to support revert: "valid/invalid"
				if(this.shouldRevert) {
					this.instance.options.revert = this.shouldRevert;
				}

				//Trigger the stop of the sortable
				this.instance._mouseStop(event);

				this.instance.options.helper = this.instance.options._helper;

				//If the helper has been the original item, restore properties in the sortable
				if(inst.options.helper === "original") {
					this.instance.currentItem.css({ top: "auto", left: "auto" });
				}

			} else {
				this.instance.cancelHelperRemoval = false; //Remove the helper in the sortable instance
				this.instance._trigger("deactivate", event, uiSortable);
			}

		});

	},
	drag: function(event, ui) {

		var inst = $(this).data("ui-draggable"), that = this;

		$.each(inst.sortables, function() {

			var innermostIntersecting = false,
				thisSortable = this;

			//Copy over some variables to allow calling the sortable's native _intersectsWith
			this.instance.positionAbs = inst.positionAbs;
			this.instance.helperProportions = inst.helperProportions;
			this.instance.offset.click = inst.offset.click;

			if(this.instance._intersectsWith(this.instance.containerCache)) {
				innermostIntersecting = true;
				$.each(inst.sortables, function () {
					this.instance.positionAbs = inst.positionAbs;
					this.instance.helperProportions = inst.helperProportions;
					this.instance.offset.click = inst.offset.click;
					if (this !== thisSortable &&
						this.instance._intersectsWith(this.instance.containerCache) &&
						$.contains(thisSortable.instance.element[0], this.instance.element[0])
					) {
						innermostIntersecting = false;
					}
					return innermostIntersecting;
				});
			}


			if(innermostIntersecting) {
				//If it intersects, we use a little isOver variable and set it once, so our move-in stuff gets fired only once
				if(!this.instance.isOver) {

					this.instance.isOver = 1;
					//Now we fake the start of dragging for the sortable instance,
					//by cloning the list group item, appending it to the sortable and using it as inst.currentItem
					//We can then fire the start event of the sortable with our passed browser event, and our own helper (so it doesn't create a new one)
					this.instance.currentItem = $(that).clone().removeAttr("id").appendTo(this.instance.element).data("ui-sortable-item", true);
					this.instance.options._helper = this.instance.options.helper; //Store helper option to later restore it
					this.instance.options.helper = function() { return ui.helper[0]; };

					event.target = this.instance.currentItem[0];
					this.instance._mouseCapture(event, true);
					this.instance._mouseStart(event, true, true);

					//Because the browser event is way off the new appended portlet, we modify a couple of variables to reflect the changes
					this.instance.offset.click.top = inst.offset.click.top;
					this.instance.offset.click.left = inst.offset.click.left;
					this.instance.offset.parent.left -= inst.offset.parent.left - this.instance.offset.parent.left;
					this.instance.offset.parent.top -= inst.offset.parent.top - this.instance.offset.parent.top;

					inst._trigger("toSortable", event);
					inst.dropped = this.instance.element; //draggable revert needs that
					//hack so receive/update callbacks work (mostly)
					inst.currentItem = inst.element;
					this.instance.fromOutside = inst;

				}

				//Provided we did all the previous steps, we can fire the drag event of the sortable on every draggable drag, when it intersects with the sortable
				if(this.instance.currentItem) {
					this.instance._mouseDrag(event);
				}

			} else {

				//If it doesn't intersect with the sortable, and it intersected before,
				//we fake the drag stop of the sortable, but make sure it doesn't remove the helper by using cancelHelperRemoval
				if(this.instance.isOver) {

					this.instance.isOver = 0;
					this.instance.cancelHelperRemoval = true;

					//Prevent reverting on this forced stop
					this.instance.options.revert = false;

					// The out event needs to be triggered independently
					this.instance._trigger("out", event, this.instance._uiHash(this.instance));

					this.instance._mouseStop(event, true);
					this.instance.options.helper = this.instance.options._helper;

					//Now we remove our currentItem, the list group clone again, and the placeholder, and animate the helper back to it's original size
					this.instance.currentItem.remove();
					if(this.instance.placeholder) {
						this.instance.placeholder.remove();
					}

					inst._trigger("fromSortable", event);
					inst.dropped = false; //draggable revert needs that
				}

			}

		});

	}
});

$.ui.plugin.add("draggable", "cursor", {
	start: function() {
		var t = $("body"), o = $(this).data("ui-draggable").options;
		if (t.css("cursor")) {
			o._cursor = t.css("cursor");
		}
		t.css("cursor", o.cursor);
	},
	stop: function() {
		var o = $(this).data("ui-draggable").options;
		if (o._cursor) {
			$("body").css("cursor", o._cursor);
		}
	}
});

$.ui.plugin.add("draggable", "opacity", {
	start: function(event, ui) {
		var t = $(ui.helper), o = $(this).data("ui-draggable").options;
		if(t.css("opacity")) {
			o._opacity = t.css("opacity");
		}
		t.css("opacity", o.opacity);
	},
	stop: function(event, ui) {
		var o = $(this).data("ui-draggable").options;
		if(o._opacity) {
			$(ui.helper).css("opacity", o._opacity);
		}
	}
});

$.ui.plugin.add("draggable", "scroll", {
	start: function() {
		var i = $(this).data("ui-draggable");
		if(i.scrollParent[0] !== document && i.scrollParent[0].tagName !== "HTML") {
			i.overflowOffset = i.scrollParent.offset();
		}
	},
	drag: function( event ) {

		var i = $(this).data("ui-draggable"), o = i.options, scrolled = false;

		if(i.scrollParent[0] !== document && i.scrollParent[0].tagName !== "HTML") {

			if(!o.axis || o.axis !== "x") {
				if((i.overflowOffset.top + i.scrollParent[0].offsetHeight) - event.pageY < o.scrollSensitivity) {
					i.scrollParent[0].scrollTop = scrolled = i.scrollParent[0].scrollTop + o.scrollSpeed;
				} else if(event.pageY - i.overflowOffset.top < o.scrollSensitivity) {
					i.scrollParent[0].scrollTop = scrolled = i.scrollParent[0].scrollTop - o.scrollSpeed;
				}
			}

			if(!o.axis || o.axis !== "y") {
				if((i.overflowOffset.left + i.scrollParent[0].offsetWidth) - event.pageX < o.scrollSensitivity) {
					i.scrollParent[0].scrollLeft = scrolled = i.scrollParent[0].scrollLeft + o.scrollSpeed;
				} else if(event.pageX - i.overflowOffset.left < o.scrollSensitivity) {
					i.scrollParent[0].scrollLeft = scrolled = i.scrollParent[0].scrollLeft - o.scrollSpeed;
				}
			}

		} else {

			if(!o.axis || o.axis !== "x") {
				if(event.pageY - $(document).scrollTop() < o.scrollSensitivity) {
					scrolled = $(document).scrollTop($(document).scrollTop() - o.scrollSpeed);
				} else if($(window).height() - (event.pageY - $(document).scrollTop()) < o.scrollSensitivity) {
					scrolled = $(document).scrollTop($(document).scrollTop() + o.scrollSpeed);
				}
			}

			if(!o.axis || o.axis !== "y") {
				if(event.pageX - $(document).scrollLeft() < o.scrollSensitivity) {
					scrolled = $(document).scrollLeft($(document).scrollLeft() - o.scrollSpeed);
				} else if($(window).width() - (event.pageX - $(document).scrollLeft()) < o.scrollSensitivity) {
					scrolled = $(document).scrollLeft($(document).scrollLeft() + o.scrollSpeed);
				}
			}

		}

		if(scrolled !== false && $.ui.ddmanager && !o.dropBehaviour) {
			$.ui.ddmanager.prepareOffsets(i, event);
		}

	}
});

$.ui.plugin.add("draggable", "snap", {
	start: function() {

		var i = $(this).data("ui-draggable"),
			o = i.options;

		i.snapElements = [];

		$(o.snap.constructor !== String ? ( o.snap.items || ":data(ui-draggable)" ) : o.snap).each(function() {
			var $t = $(this),
				$o = $t.offset();
			if(this !== i.element[0]) {
				i.snapElements.push({
					item: this,
					width: $t.outerWidth(), height: $t.outerHeight(),
					top: $o.top, left: $o.left
				});
			}
		});

	},
	drag: function(event, ui) {

		var ts, bs, ls, rs, l, r, t, b, i, first,
			inst = $(this).data("ui-draggable"),
			o = inst.options,
			d = o.snapTolerance,
			x1 = ui.offset.left, x2 = x1 + inst.helperProportions.width,
			y1 = ui.offset.top, y2 = y1 + inst.helperProportions.height;

		for (i = inst.snapElements.length - 1; i >= 0; i--){

			l = inst.snapElements[i].left;
			r = l + inst.snapElements[i].width;
			t = inst.snapElements[i].top;
			b = t + inst.snapElements[i].height;

			if ( x2 < l - d || x1 > r + d || y2 < t - d || y1 > b + d || !$.contains( inst.snapElements[ i ].item.ownerDocument, inst.snapElements[ i ].item ) ) {
				if(inst.snapElements[i].snapping) {
					(inst.options.snap.release && inst.options.snap.release.call(inst.element, event, $.extend(inst._uiHash(), { snapItem: inst.snapElements[i].item })));
				}
				inst.snapElements[i].snapping = false;
				continue;
			}

			if(o.snapMode !== "inner") {
				ts = Math.abs(t - y2) <= d;
				bs = Math.abs(b - y1) <= d;
				ls = Math.abs(l - x2) <= d;
				rs = Math.abs(r - x1) <= d;
				if(ts) {
					ui.position.top = inst._convertPositionTo("relative", { top: t - inst.helperProportions.height, left: 0 }).top - inst.margins.top;
				}
				if(bs) {
					ui.position.top = inst._convertPositionTo("relative", { top: b, left: 0 }).top - inst.margins.top;
				}
				if(ls) {
					ui.position.left = inst._convertPositionTo("relative", { top: 0, left: l - inst.helperProportions.width }).left - inst.margins.left;
				}
				if(rs) {
					ui.position.left = inst._convertPositionTo("relative", { top: 0, left: r }).left - inst.margins.left;
				}
			}

			first = (ts || bs || ls || rs);

			if(o.snapMode !== "outer") {
				ts = Math.abs(t - y1) <= d;
				bs = Math.abs(b - y2) <= d;
				ls = Math.abs(l - x1) <= d;
				rs = Math.abs(r - x2) <= d;
				if(ts) {
					ui.position.top = inst._convertPositionTo("relative", { top: t, left: 0 }).top - inst.margins.top;
				}
				if(bs) {
					ui.position.top = inst._convertPositionTo("relative", { top: b - inst.helperProportions.height, left: 0 }).top - inst.margins.top;
				}
				if(ls) {
					ui.position.left = inst._convertPositionTo("relative", { top: 0, left: l }).left - inst.margins.left;
				}
				if(rs) {
					ui.position.left = inst._convertPositionTo("relative", { top: 0, left: r - inst.helperProportions.width }).left - inst.margins.left;
				}
			}

			if(!inst.snapElements[i].snapping && (ts || bs || ls || rs || first)) {
				(inst.options.snap.snap && inst.options.snap.snap.call(inst.element, event, $.extend(inst._uiHash(), { snapItem: inst.snapElements[i].item })));
			}
			inst.snapElements[i].snapping = (ts || bs || ls || rs || first);

		}

	}
});

$.ui.plugin.add("draggable", "stack", {
	start: function() {
		var min,
			o = this.data("ui-draggable").options,
			group = $.makeArray($(o.stack)).sort(function(a,b) {
				return (parseInt($(a).css("zIndex"),10) || 0) - (parseInt($(b).css("zIndex"),10) || 0);
			});

		if (!group.length) { return; }

		min = parseInt($(group[0]).css("zIndex"), 10) || 0;
		$(group).each(function(i) {
			$(this).css("zIndex", min + i);
		});
		this.css("zIndex", (min + group.length));
	}
});

$.ui.plugin.add("draggable", "zIndex", {
	start: function(event, ui) {
		var t = $(ui.helper), o = $(this).data("ui-draggable").options;
		if(t.css("zIndex")) {
			o._zIndex = t.css("zIndex");
		}
		t.css("zIndex", o.zIndex);
	},
	stop: function(event, ui) {
		var o = $(this).data("ui-draggable").options;
		if(o._zIndex) {
			$(ui.helper).css("zIndex", o._zIndex);
		}
	}
});

})(jQuery);
(function( $, undefined ) {

function isOverAxis( x, reference, size ) {
	return ( x > reference ) && ( x < ( reference + size ) );
}

$.widget("ui.droppable", {
	version: "1.10.3",
	widgetEventPrefix: "drop",
	options: {
		accept: "*",
		activeClass: false,
		addClasses: true,
		greedy: false,
		hoverClass: false,
		scope: "default",
		tolerance: "intersect",

		// callbacks
		activate: null,
		deactivate: null,
		drop: null,
		out: null,
		over: null
	},
	_create: function() {

		var o = this.options,
			accept = o.accept;

		this.isover = false;
		this.isout = true;

		this.accept = $.isFunction(accept) ? accept : function(d) {
			return d.is(accept);
		};

		//Store the droppable's proportions
		this.proportions = { width: this.element[0].offsetWidth, height: this.element[0].offsetHeight };

		// Add the reference and positions to the manager
		$.ui.ddmanager.droppables[o.scope] = $.ui.ddmanager.droppables[o.scope] || [];
		$.ui.ddmanager.droppables[o.scope].push(this);

		(o.addClasses && this.element.addClass("ui-droppable"));

	},

	_destroy: function() {
		var i = 0,
			drop = $.ui.ddmanager.droppables[this.options.scope];

		for ( ; i < drop.length; i++ ) {
			if ( drop[i] === this ) {
				drop.splice(i, 1);
			}
		}

		this.element.removeClass("ui-droppable ui-droppable-disabled");
	},

	_setOption: function(key, value) {

		if(key === "accept") {
			this.accept = $.isFunction(value) ? value : function(d) {
				return d.is(value);
			};
		}
		$.Widget.prototype._setOption.apply(this, arguments);
	},

	_activate: function(event) {
		var draggable = $.ui.ddmanager.current;
		if(this.options.activeClass) {
			this.element.addClass(this.options.activeClass);
		}
		if(draggable){
			this._trigger("activate", event, this.ui(draggable));
		}
	},

	_deactivate: function(event) {
		var draggable = $.ui.ddmanager.current;
		if(this.options.activeClass) {
			this.element.removeClass(this.options.activeClass);
		}
		if(draggable){
			this._trigger("deactivate", event, this.ui(draggable));
		}
	},

	_over: function(event) {

		var draggable = $.ui.ddmanager.current;

		// Bail if draggable and droppable are same element
		if (!draggable || (draggable.currentItem || draggable.element)[0] === this.element[0]) {
			return;
		}

		if (this.accept.call(this.element[0],(draggable.currentItem || draggable.element))) {
			if(this.options.hoverClass) {
				this.element.addClass(this.options.hoverClass);
			}
			this._trigger("over", event, this.ui(draggable));
		}

	},

	_out: function(event) {

		var draggable = $.ui.ddmanager.current;

		// Bail if draggable and droppable are same element
		if (!draggable || (draggable.currentItem || draggable.element)[0] === this.element[0]) {
			return;
		}

		if (this.accept.call(this.element[0],(draggable.currentItem || draggable.element))) {
			if(this.options.hoverClass) {
				this.element.removeClass(this.options.hoverClass);
			}
			this._trigger("out", event, this.ui(draggable));
		}

	},

	_drop: function(event,custom) {

		var draggable = custom || $.ui.ddmanager.current,
			childrenIntersection = false;

		// Bail if draggable and droppable are same element
		if (!draggable || (draggable.currentItem || draggable.element)[0] === this.element[0]) {
			return false;
		}

		this.element.find(":data(ui-droppable)").not(".ui-draggable-dragging").each(function() {
			var inst = $.data(this, "ui-droppable");
			if(
				inst.options.greedy &&
				!inst.options.disabled &&
				inst.options.scope === draggable.options.scope &&
				inst.accept.call(inst.element[0], (draggable.currentItem || draggable.element)) &&
				$.ui.intersect(draggable, $.extend(inst, { offset: inst.element.offset() }), inst.options.tolerance)
			) { childrenIntersection = true; return false; }
		});
		if(childrenIntersection) {
			return false;
		}

		if(this.accept.call(this.element[0],(draggable.currentItem || draggable.element))) {
			if(this.options.activeClass) {
				this.element.removeClass(this.options.activeClass);
			}
			if(this.options.hoverClass) {
				this.element.removeClass(this.options.hoverClass);
			}
			this._trigger("drop", event, this.ui(draggable));
			return this.element;
		}

		return false;

	},

	ui: function(c) {
		return {
			draggable: (c.currentItem || c.element),
			helper: c.helper,
			position: c.position,
			offset: c.positionAbs
		};
	}

});

$.ui.intersect = function(draggable, droppable, toleranceMode) {

	if (!droppable.offset) {
		return false;
	}

	var draggableLeft, draggableTop,
		x1 = (draggable.positionAbs || draggable.position.absolute).left, x2 = x1 + draggable.helperProportions.width,
		y1 = (draggable.positionAbs || draggable.position.absolute).top, y2 = y1 + draggable.helperProportions.height,
		l = droppable.offset.left, r = l + droppable.proportions.width,
		t = droppable.offset.top, b = t + droppable.proportions.height;

	switch (toleranceMode) {
		case "fit":
			return (l <= x1 && x2 <= r && t <= y1 && y2 <= b);
		case "intersect":
			return (l < x1 + (draggable.helperProportions.width / 2) && // Right Half
				x2 - (draggable.helperProportions.width / 2) < r && // Left Half
				t < y1 + (draggable.helperProportions.height / 2) && // Bottom Half
				y2 - (draggable.helperProportions.height / 2) < b ); // Top Half
		case "pointer":
			draggableLeft = ((draggable.positionAbs || draggable.position.absolute).left + (draggable.clickOffset || draggable.offset.click).left);
			draggableTop = ((draggable.positionAbs || draggable.position.absolute).top + (draggable.clickOffset || draggable.offset.click).top);
			return isOverAxis( draggableTop, t, droppable.proportions.height ) && isOverAxis( draggableLeft, l, droppable.proportions.width );
		case "touch":
			return (
				(y1 >= t && y1 <= b) ||	// Top edge touching
				(y2 >= t && y2 <= b) ||	// Bottom edge touching
				(y1 < t && y2 > b)		// Surrounded vertically
			) && (
				(x1 >= l && x1 <= r) ||	// Left edge touching
				(x2 >= l && x2 <= r) ||	// Right edge touching
				(x1 < l && x2 > r)		// Surrounded horizontally
			);
		default:
			return false;
		}

};

/*
	This manager tracks offsets of draggables and droppables
*/
$.ui.ddmanager = {
	current: null,
	droppables: { "default": [] },
	prepareOffsets: function(t, event) {

		var i, j,
			m = $.ui.ddmanager.droppables[t.options.scope] || [],
			type = event ? event.type : null, // workaround for #2317
			list = (t.currentItem || t.element).find(":data(ui-droppable)").addBack();

		droppablesLoop: for (i = 0; i < m.length; i++) {

			//No disabled and non-accepted
			if(m[i].options.disabled || (t && !m[i].accept.call(m[i].element[0],(t.currentItem || t.element)))) {
				continue;
			}

			// Filter out elements in the current dragged item
			for (j=0; j < list.length; j++) {
				if(list[j] === m[i].element[0]) {
					m[i].proportions.height = 0;
					continue droppablesLoop;
				}
			}

			m[i].visible = m[i].element.css("display") !== "none";
			if(!m[i].visible) {
				continue;
			}

			//Activate the droppable if used directly from draggables
			if(type === "mousedown") {
				m[i]._activate.call(m[i], event);
			}

			m[i].offset = m[i].element.offset();
			m[i].proportions = { width: m[i].element[0].offsetWidth, height: m[i].element[0].offsetHeight };

		}

	},
	drop: function(draggable, event) {

		var dropped = false;
		// Create a copy of the droppables in case the list changes during the drop (#9116)
		$.each(($.ui.ddmanager.droppables[draggable.options.scope] || []).slice(), function() {

			if(!this.options) {
				return;
			}
			if (!this.options.disabled && this.visible && $.ui.intersect(draggable, this, this.options.tolerance)) {
				dropped = this._drop.call(this, event) || dropped;
			}

			if (!this.options.disabled && this.visible && this.accept.call(this.element[0],(draggable.currentItem || draggable.element))) {
				this.isout = true;
				this.isover = false;
				this._deactivate.call(this, event);
			}

		});
		return dropped;

	},
	dragStart: function( draggable, event ) {
		//Listen for scrolling so that if the dragging causes scrolling the position of the droppables can be recalculated (see #5003)
		draggable.element.parentsUntil( "body" ).bind( "scroll.droppable", function() {
			if( !draggable.options.refreshPositions ) {
				$.ui.ddmanager.prepareOffsets( draggable, event );
			}
		});
	},
	drag: function(draggable, event) {

		//If you have a highly dynamic page, you might try this option. It renders positions every time you move the mouse.
		if(draggable.options.refreshPositions) {
			$.ui.ddmanager.prepareOffsets(draggable, event);
		}

		//Run through all droppables and check their positions based on specific tolerance options
		$.each($.ui.ddmanager.droppables[draggable.options.scope] || [], function() {

			if(this.options.disabled || this.greedyChild || !this.visible) {
				return;
			}

			var parentInstance, scope, parent,
				intersects = $.ui.intersect(draggable, this, this.options.tolerance),
				c = !intersects && this.isover ? "isout" : (intersects && !this.isover ? "isover" : null);
			if(!c) {
				return;
			}

			if (this.options.greedy) {
				// find droppable parents with same scope
				scope = this.options.scope;
				parent = this.element.parents(":data(ui-droppable)").filter(function () {
					return $.data(this, "ui-droppable").options.scope === scope;
				});

				if (parent.length) {
					parentInstance = $.data(parent[0], "ui-droppable");
					parentInstance.greedyChild = (c === "isover");
				}
			}

			// we just moved into a greedy child
			if (parentInstance && c === "isover") {
				parentInstance.isover = false;
				parentInstance.isout = true;
				parentInstance._out.call(parentInstance, event);
			}

			this[c] = true;
			this[c === "isout" ? "isover" : "isout"] = false;
			this[c === "isover" ? "_over" : "_out"].call(this, event);

			// we just moved out of a greedy child
			if (parentInstance && c === "isout") {
				parentInstance.isout = false;
				parentInstance.isover = true;
				parentInstance._over.call(parentInstance, event);
			}
		});

	},
	dragStop: function( draggable, event ) {
		draggable.element.parentsUntil( "body" ).unbind( "scroll.droppable" );
		//Call prepareOffsets one final time since IE does not fire return scroll events when overflow was caused by drag (see #5003)
		if( !draggable.options.refreshPositions ) {
			$.ui.ddmanager.prepareOffsets( draggable, event );
		}
	}
};

})(jQuery);
(function( $, undefined ) {

function num(v) {
	return parseInt(v, 10) || 0;
}

function isNumber(value) {
	return !isNaN(parseInt(value, 10));
}

$.widget("ui.resizable", $.ui.mouse, {
	version: "1.10.3",
	widgetEventPrefix: "resize",
	options: {
		alsoResize: false,
		animate: false,
		animateDuration: "slow",
		animateEasing: "swing",
		aspectRatio: false,
		autoHide: false,
		containment: false,
		ghost: false,
		grid: false,
		handles: "e,s,se",
		helper: false,
		maxHeight: null,
		maxWidth: null,
		minHeight: 10,
		minWidth: 10,
		// See #7960
		zIndex: 90,

		// callbacks
		resize: null,
		start: null,
		stop: null
	},
	_create: function() {

		var n, i, handle, axis, hname,
			that = this,
			o = this.options;
		this.element.addClass("ui-resizable");

		$.extend(this, {
			_aspectRatio: !!(o.aspectRatio),
			aspectRatio: o.aspectRatio,
			originalElement: this.element,
			_proportionallyResizeElements: [],
			_helper: o.helper || o.ghost || o.animate ? o.helper || "ui-resizable-helper" : null
		});

		//Wrap the element if it cannot hold child nodes
		if(this.element[0].nodeName.match(/canvas|textarea|input|select|button|img/i)) {

			//Create a wrapper element and set the wrapper to the new current internal element
			this.element.wrap(
				$("<div class='ui-wrapper' style='overflow: hidden;'></div>").css({
					position: this.element.css("position"),
					width: this.element.outerWidth(),
					height: this.element.outerHeight(),
					top: this.element.css("top"),
					left: this.element.css("left")
				})
			);

			//Overwrite the original this.element
			this.element = this.element.parent().data(
				"ui-resizable", this.element.data("ui-resizable")
			);

			this.elementIsWrapper = true;

			//Move margins to the wrapper
			this.element.css({ marginLeft: this.originalElement.css("marginLeft"), marginTop: this.originalElement.css("marginTop"), marginRight: this.originalElement.css("marginRight"), marginBottom: this.originalElement.css("marginBottom") });
			this.originalElement.css({ marginLeft: 0, marginTop: 0, marginRight: 0, marginBottom: 0});

			//Prevent Safari textarea resize
			this.originalResizeStyle = this.originalElement.css("resize");
			this.originalElement.css("resize", "none");

			//Push the actual element to our proportionallyResize internal array
			this._proportionallyResizeElements.push(this.originalElement.css({ position: "static", zoom: 1, display: "block" }));

			// avoid IE jump (hard set the margin)
			this.originalElement.css({ margin: this.originalElement.css("margin") });

			// fix handlers offset
			this._proportionallyResize();

		}

		this.handles = o.handles || (!$(".ui-resizable-handle", this.element).length ? "e,s,se" : { n: ".ui-resizable-n", e: ".ui-resizable-e", s: ".ui-resizable-s", w: ".ui-resizable-w", se: ".ui-resizable-se", sw: ".ui-resizable-sw", ne: ".ui-resizable-ne", nw: ".ui-resizable-nw" });
		if(this.handles.constructor === String) {

			if ( this.handles === "all") {
				this.handles = "n,e,s,w,se,sw,ne,nw";
			}

			n = this.handles.split(",");
			this.handles = {};

			for(i = 0; i < n.length; i++) {

				handle = $.trim(n[i]);
				hname = "ui-resizable-"+handle;
				axis = $("<div class='ui-resizable-handle " + hname + "'></div>");

				// Apply zIndex to all handles - see #7960
				axis.css({ zIndex: o.zIndex });

				//TODO : What's going on here?
				if ("se" === handle) {
					axis.addClass("ui-icon ui-icon-gripsmall-diagonal-se");
				}

				//Insert into internal handles object and append to element
				this.handles[handle] = ".ui-resizable-"+handle;
				this.element.append(axis);
			}

		}

		this._renderAxis = function(target) {

			var i, axis, padPos, padWrapper;

			target = target || this.element;

			for(i in this.handles) {

				if(this.handles[i].constructor === String) {
					this.handles[i] = $(this.handles[i], this.element).show();
				}

				//Apply pad to wrapper element, needed to fix axis position (textarea, inputs, scrolls)
				if (this.elementIsWrapper && this.originalElement[0].nodeName.match(/textarea|input|select|button/i)) {

					axis = $(this.handles[i], this.element);

					//Checking the correct pad and border
					padWrapper = /sw|ne|nw|se|n|s/.test(i) ? axis.outerHeight() : axis.outerWidth();

					//The padding type i have to apply...
					padPos = [ "padding",
						/ne|nw|n/.test(i) ? "Top" :
						/se|sw|s/.test(i) ? "Bottom" :
						/^e$/.test(i) ? "Right" : "Left" ].join("");

					target.css(padPos, padWrapper);

					this._proportionallyResize();

				}

				//TODO: What's that good for? There's not anything to be executed left
				if(!$(this.handles[i]).length) {
					continue;
				}
			}
		};

		//TODO: make renderAxis a prototype function
		this._renderAxis(this.element);

		this._handles = $(".ui-resizable-handle", this.element)
			.disableSelection();

		//Matching axis name
		this._handles.mouseover(function() {
			if (!that.resizing) {
				if (this.className) {
					axis = this.className.match(/ui-resizable-(se|sw|ne|nw|n|e|s|w)/i);
				}
				//Axis, default = se
				that.axis = axis && axis[1] ? axis[1] : "se";
			}
		});

		//If we want to auto hide the elements
		if (o.autoHide) {
			this._handles.hide();
			$(this.element)
				.addClass("ui-resizable-autohide")
				.mouseenter(function() {
					if (o.disabled) {
						return;
					}
					$(this).removeClass("ui-resizable-autohide");
					that._handles.show();
				})
				.mouseleave(function(){
					if (o.disabled) {
						return;
					}
					if (!that.resizing) {
						$(this).addClass("ui-resizable-autohide");
						that._handles.hide();
					}
				});
		}

		//Initialize the mouse interaction
		this._mouseInit();

	},

	_destroy: function() {

		this._mouseDestroy();

		var wrapper,
			_destroy = function(exp) {
				$(exp).removeClass("ui-resizable ui-resizable-disabled ui-resizable-resizing")
					.removeData("resizable").removeData("ui-resizable").unbind(".resizable").find(".ui-resizable-handle").remove();
			};

		//TODO: Unwrap at same DOM position
		if (this.elementIsWrapper) {
			_destroy(this.element);
			wrapper = this.element;
			this.originalElement.css({
				position: wrapper.css("position"),
				width: wrapper.outerWidth(),
				height: wrapper.outerHeight(),
				top: wrapper.css("top"),
				left: wrapper.css("left")
			}).insertAfter( wrapper );
			wrapper.remove();
		}

		this.originalElement.css("resize", this.originalResizeStyle);
		_destroy(this.originalElement);

		return this;
	},

	_mouseCapture: function(event) {
		var i, handle,
			capture = false;

		for (i in this.handles) {
			handle = $(this.handles[i])[0];
			if (handle === event.target || $.contains(handle, event.target)) {
				capture = true;
			}
		}

		return !this.options.disabled && capture;
	},

	_mouseStart: function(event) {

		var curleft, curtop, cursor,
			o = this.options,
			iniPos = this.element.position(),
			el = this.element;

		this.resizing = true;

		// bugfix for http://dev.jquery.com/ticket/1749
		if ( (/absolute/).test( el.css("position") ) ) {
			el.css({ position: "absolute", top: el.css("top"), left: el.css("left") });
		} else if (el.is(".ui-draggable")) {
			el.css({ position: "absolute", top: iniPos.top, left: iniPos.left });
		}

		this._renderProxy();

		curleft = num(this.helper.css("left"));
		curtop = num(this.helper.css("top"));

		if (o.containment) {
			curleft += $(o.containment).scrollLeft() || 0;
			curtop += $(o.containment).scrollTop() || 0;
		}

		//Store needed variables
		this.offset = this.helper.offset();
		this.position = { left: curleft, top: curtop };
		this.size = this._helper ? { width: el.outerWidth(), height: el.outerHeight() } : { width: el.width(), height: el.height() };
		this.originalSize = this._helper ? { width: el.outerWidth(), height: el.outerHeight() } : { width: el.width(), height: el.height() };
		this.originalPosition = { left: curleft, top: curtop };
		this.sizeDiff = { width: el.outerWidth() - el.width(), height: el.outerHeight() - el.height() };
		this.originalMousePosition = { left: event.pageX, top: event.pageY };

		//Aspect Ratio
		this.aspectRatio = (typeof o.aspectRatio === "number") ? o.aspectRatio : ((this.originalSize.width / this.originalSize.height) || 1);

		cursor = $(".ui-resizable-" + this.axis).css("cursor");
		$("body").css("cursor", cursor === "auto" ? this.axis + "-resize" : cursor);

		el.addClass("ui-resizable-resizing");
		this._propagate("start", event);
		return true;
	},

	_mouseDrag: function(event) {

		//Increase performance, avoid regex
		var data,
			el = this.helper, props = {},
			smp = this.originalMousePosition,
			a = this.axis,
			prevTop = this.position.top,
			prevLeft = this.position.left,
			prevWidth = this.size.width,
			prevHeight = this.size.height,
			dx = (event.pageX-smp.left)||0,
			dy = (event.pageY-smp.top)||0,
			trigger = this._change[a];

		if (!trigger) {
			return false;
		}

		// Calculate the attrs that will be change
		data = trigger.apply(this, [event, dx, dy]);

		// Put this in the mouseDrag handler since the user can start pressing shift while resizing
		this._updateVirtualBoundaries(event.shiftKey);
		if (this._aspectRatio || event.shiftKey) {
			data = this._updateRatio(data, event);
		}

		data = this._respectSize(data, event);

		this._updateCache(data);

		// plugins callbacks need to be called first
		this._propagate("resize", event);

		if (this.position.top !== prevTop) {
			props.top = this.position.top + "px";
		}
		if (this.position.left !== prevLeft) {
			props.left = this.position.left + "px";
		}
		if (this.size.width !== prevWidth) {
			props.width = this.size.width + "px";
		}
		if (this.size.height !== prevHeight) {
			props.height = this.size.height + "px";
		}
		el.css(props);

		if (!this._helper && this._proportionallyResizeElements.length) {
			this._proportionallyResize();
		}

		// Call the user callback if the element was resized
		if ( ! $.isEmptyObject(props) ) {
			this._trigger("resize", event, this.ui());
		}

		return false;
	},

	_mouseStop: function(event) {

		this.resizing = false;
		var pr, ista, soffseth, soffsetw, s, left, top,
			o = this.options, that = this;

		if(this._helper) {

			pr = this._proportionallyResizeElements;
			ista = pr.length && (/textarea/i).test(pr[0].nodeName);
			soffseth = ista && $.ui.hasScroll(pr[0], "left") /* TODO - jump height */ ? 0 : that.sizeDiff.height;
			soffsetw = ista ? 0 : that.sizeDiff.width;

			s = { width: (that.helper.width()  - soffsetw), height: (that.helper.height() - soffseth) };
			left = (parseInt(that.element.css("left"), 10) + (that.position.left - that.originalPosition.left)) || null;
			top = (parseInt(that.element.css("top"), 10) + (that.position.top - that.originalPosition.top)) || null;

			if (!o.animate) {
				this.element.css($.extend(s, { top: top, left: left }));
			}

			that.helper.height(that.size.height);
			that.helper.width(that.size.width);

			if (this._helper && !o.animate) {
				this._proportionallyResize();
			}
		}

		$("body").css("cursor", "auto");

		this.element.removeClass("ui-resizable-resizing");

		this._propagate("stop", event);

		if (this._helper) {
			this.helper.remove();
		}

		return false;

	},

	_updateVirtualBoundaries: function(forceAspectRatio) {
		var pMinWidth, pMaxWidth, pMinHeight, pMaxHeight, b,
			o = this.options;

		b = {
			minWidth: isNumber(o.minWidth) ? o.minWidth : 0,
			maxWidth: isNumber(o.maxWidth) ? o.maxWidth : Infinity,
			minHeight: isNumber(o.minHeight) ? o.minHeight : 0,
			maxHeight: isNumber(o.maxHeight) ? o.maxHeight : Infinity
		};

		if(this._aspectRatio || forceAspectRatio) {
			// We want to create an enclosing box whose aspect ration is the requested one
			// First, compute the "projected" size for each dimension based on the aspect ratio and other dimension
			pMinWidth = b.minHeight * this.aspectRatio;
			pMinHeight = b.minWidth / this.aspectRatio;
			pMaxWidth = b.maxHeight * this.aspectRatio;
			pMaxHeight = b.maxWidth / this.aspectRatio;

			if(pMinWidth > b.minWidth) {
				b.minWidth = pMinWidth;
			}
			if(pMinHeight > b.minHeight) {
				b.minHeight = pMinHeight;
			}
			if(pMaxWidth < b.maxWidth) {
				b.maxWidth = pMaxWidth;
			}
			if(pMaxHeight < b.maxHeight) {
				b.maxHeight = pMaxHeight;
			}
		}
		this._vBoundaries = b;
	},

	_updateCache: function(data) {
		this.offset = this.helper.offset();
		if (isNumber(data.left)) {
			this.position.left = data.left;
		}
		if (isNumber(data.top)) {
			this.position.top = data.top;
		}
		if (isNumber(data.height)) {
			this.size.height = data.height;
		}
		if (isNumber(data.width)) {
			this.size.width = data.width;
		}
	},

	_updateRatio: function( data ) {

		var cpos = this.position,
			csize = this.size,
			a = this.axis;

		if (isNumber(data.height)) {
			data.width = (data.height * this.aspectRatio);
		} else if (isNumber(data.width)) {
			data.height = (data.width / this.aspectRatio);
		}

		if (a === "sw") {
			data.left = cpos.left + (csize.width - data.width);
			data.top = null;
		}
		if (a === "nw") {
			data.top = cpos.top + (csize.height - data.height);
			data.left = cpos.left + (csize.width - data.width);
		}

		return data;
	},

	_respectSize: function( data ) {

		var o = this._vBoundaries,
			a = this.axis,
			ismaxw = isNumber(data.width) && o.maxWidth && (o.maxWidth < data.width), ismaxh = isNumber(data.height) && o.maxHeight && (o.maxHeight < data.height),
			isminw = isNumber(data.width) && o.minWidth && (o.minWidth > data.width), isminh = isNumber(data.height) && o.minHeight && (o.minHeight > data.height),
			dw = this.originalPosition.left + this.originalSize.width,
			dh = this.position.top + this.size.height,
			cw = /sw|nw|w/.test(a), ch = /nw|ne|n/.test(a);
		if (isminw) {
			data.width = o.minWidth;
		}
		if (isminh) {
			data.height = o.minHeight;
		}
		if (ismaxw) {
			data.width = o.maxWidth;
		}
		if (ismaxh) {
			data.height = o.maxHeight;
		}

		if (isminw && cw) {
			data.left = dw - o.minWidth;
		}
		if (ismaxw && cw) {
			data.left = dw - o.maxWidth;
		}
		if (isminh && ch) {
			data.top = dh - o.minHeight;
		}
		if (ismaxh && ch) {
			data.top = dh - o.maxHeight;
		}

		// fixing jump error on top/left - bug #2330
		if (!data.width && !data.height && !data.left && data.top) {
			data.top = null;
		} else if (!data.width && !data.height && !data.top && data.left) {
			data.left = null;
		}

		return data;
	},

	_proportionallyResize: function() {

		if (!this._proportionallyResizeElements.length) {
			return;
		}

		var i, j, borders, paddings, prel,
			element = this.helper || this.element;

		for ( i=0; i < this._proportionallyResizeElements.length; i++) {

			prel = this._proportionallyResizeElements[i];

			if (!this.borderDif) {
				this.borderDif = [];
				borders = [prel.css("borderTopWidth"), prel.css("borderRightWidth"), prel.css("borderBottomWidth"), prel.css("borderLeftWidth")];
				paddings = [prel.css("paddingTop"), prel.css("paddingRight"), prel.css("paddingBottom"), prel.css("paddingLeft")];

				for ( j = 0; j < borders.length; j++ ) {
					this.borderDif[ j ] = ( parseInt( borders[ j ], 10 ) || 0 ) + ( parseInt( paddings[ j ], 10 ) || 0 );
				}
			}

			prel.css({
				height: (element.height() - this.borderDif[0] - this.borderDif[2]) || 0,
				width: (element.width() - this.borderDif[1] - this.borderDif[3]) || 0
			});

		}

	},

	_renderProxy: function() {

		var el = this.element, o = this.options;
		this.elementOffset = el.offset();

		if(this._helper) {

			this.helper = this.helper || $("<div style='overflow:hidden;'></div>");

			this.helper.addClass(this._helper).css({
				width: this.element.outerWidth() - 1,
				height: this.element.outerHeight() - 1,
				position: "absolute",
				left: this.elementOffset.left +"px",
				top: this.elementOffset.top +"px",
				zIndex: ++o.zIndex //TODO: Don't modify option
			});

			this.helper
				.appendTo("body")
				.disableSelection();

		} else {
			this.helper = this.element;
		}

	},

	_change: {
		e: function(event, dx) {
			return { width: this.originalSize.width + dx };
		},
		w: function(event, dx) {
			var cs = this.originalSize, sp = this.originalPosition;
			return { left: sp.left + dx, width: cs.width - dx };
		},
		n: function(event, dx, dy) {
			var cs = this.originalSize, sp = this.originalPosition;
			return { top: sp.top + dy, height: cs.height - dy };
		},
		s: function(event, dx, dy) {
			return { height: this.originalSize.height + dy };
		},
		se: function(event, dx, dy) {
			return $.extend(this._change.s.apply(this, arguments), this._change.e.apply(this, [event, dx, dy]));
		},
		sw: function(event, dx, dy) {
			return $.extend(this._change.s.apply(this, arguments), this._change.w.apply(this, [event, dx, dy]));
		},
		ne: function(event, dx, dy) {
			return $.extend(this._change.n.apply(this, arguments), this._change.e.apply(this, [event, dx, dy]));
		},
		nw: function(event, dx, dy) {
			return $.extend(this._change.n.apply(this, arguments), this._change.w.apply(this, [event, dx, dy]));
		}
	},

	_propagate: function(n, event) {
		$.ui.plugin.call(this, n, [event, this.ui()]);
		(n !== "resize" && this._trigger(n, event, this.ui()));
	},

	plugins: {},

	ui: function() {
		return {
			originalElement: this.originalElement,
			element: this.element,
			helper: this.helper,
			position: this.position,
			size: this.size,
			originalSize: this.originalSize,
			originalPosition: this.originalPosition
		};
	}

});

/*
 * Resizable Extensions
 */

$.ui.plugin.add("resizable", "animate", {

	stop: function( event ) {
		var that = $(this).data("ui-resizable"),
			o = that.options,
			pr = that._proportionallyResizeElements,
			ista = pr.length && (/textarea/i).test(pr[0].nodeName),
			soffseth = ista && $.ui.hasScroll(pr[0], "left") /* TODO - jump height */ ? 0 : that.sizeDiff.height,
			soffsetw = ista ? 0 : that.sizeDiff.width,
			style = { width: (that.size.width - soffsetw), height: (that.size.height - soffseth) },
			left = (parseInt(that.element.css("left"), 10) + (that.position.left - that.originalPosition.left)) || null,
			top = (parseInt(that.element.css("top"), 10) + (that.position.top - that.originalPosition.top)) || null;

		that.element.animate(
			$.extend(style, top && left ? { top: top, left: left } : {}), {
				duration: o.animateDuration,
				easing: o.animateEasing,
				step: function() {

					var data = {
						width: parseInt(that.element.css("width"), 10),
						height: parseInt(that.element.css("height"), 10),
						top: parseInt(that.element.css("top"), 10),
						left: parseInt(that.element.css("left"), 10)
					};

					if (pr && pr.length) {
						$(pr[0]).css({ width: data.width, height: data.height });
					}

					// propagating resize, and updating values for each animation step
					that._updateCache(data);
					that._propagate("resize", event);

				}
			}
		);
	}

});

$.ui.plugin.add("resizable", "containment", {

	start: function() {
		var element, p, co, ch, cw, width, height,
			that = $(this).data("ui-resizable"),
			o = that.options,
			el = that.element,
			oc = o.containment,
			ce = (oc instanceof $) ? oc.get(0) : (/parent/.test(oc)) ? el.parent().get(0) : oc;

		if (!ce) {
			return;
		}

		that.containerElement = $(ce);

		if (/document/.test(oc) || oc === document) {
			that.containerOffset = { left: 0, top: 0 };
			that.containerPosition = { left: 0, top: 0 };

			that.parentData = {
				element: $(document), left: 0, top: 0,
				width: $(document).width(), height: $(document).height() || document.body.parentNode.scrollHeight
			};
		}

		// i'm a node, so compute top, left, right, bottom
		else {
			element = $(ce);
			p = [];
			$([ "Top", "Right", "Left", "Bottom" ]).each(function(i, name) { p[i] = num(element.css("padding" + name)); });

			that.containerOffset = element.offset();
			that.containerPosition = element.position();
			that.containerSize = { height: (element.innerHeight() - p[3]), width: (element.innerWidth() - p[1]) };

			co = that.containerOffset;
			ch = that.containerSize.height;
			cw = that.containerSize.width;
			width = ($.ui.hasScroll(ce, "left") ? ce.scrollWidth : cw );
			height = ($.ui.hasScroll(ce) ? ce.scrollHeight : ch);

			that.parentData = {
				element: ce, left: co.left, top: co.top, width: width, height: height
			};
		}
	},

	resize: function( event ) {
		var woset, hoset, isParent, isOffsetRelative,
			that = $(this).data("ui-resizable"),
			o = that.options,
			co = that.containerOffset, cp = that.position,
			pRatio = that._aspectRatio || event.shiftKey,
			cop = { top:0, left:0 }, ce = that.containerElement;

		if (ce[0] !== document && (/static/).test(ce.css("position"))) {
			cop = co;
		}

		if (cp.left < (that._helper ? co.left : 0)) {
			that.size.width = that.size.width + (that._helper ? (that.position.left - co.left) : (that.position.left - cop.left));
			if (pRatio) {
				that.size.height = that.size.width / that.aspectRatio;
			}
			that.position.left = o.helper ? co.left : 0;
		}

		if (cp.top < (that._helper ? co.top : 0)) {
			that.size.height = that.size.height + (that._helper ? (that.position.top - co.top) : that.position.top);
			if (pRatio) {
				that.size.width = that.size.height * that.aspectRatio;
			}
			that.position.top = that._helper ? co.top : 0;
		}

		that.offset.left = that.parentData.left+that.position.left;
		that.offset.top = that.parentData.top+that.position.top;

		woset = Math.abs( (that._helper ? that.offset.left - cop.left : (that.offset.left - cop.left)) + that.sizeDiff.width );
		hoset = Math.abs( (that._helper ? that.offset.top - cop.top : (that.offset.top - co.top)) + that.sizeDiff.height );

		isParent = that.containerElement.get(0) === that.element.parent().get(0);
		isOffsetRelative = /relative|absolute/.test(that.containerElement.css("position"));

		if(isParent && isOffsetRelative) {
			woset -= that.parentData.left;
		}

		if (woset + that.size.width >= that.parentData.width) {
			that.size.width = that.parentData.width - woset;
			if (pRatio) {
				that.size.height = that.size.width / that.aspectRatio;
			}
		}

		if (hoset + that.size.height >= that.parentData.height) {
			that.size.height = that.parentData.height - hoset;
			if (pRatio) {
				that.size.width = that.size.height * that.aspectRatio;
			}
		}
	},

	stop: function(){
		var that = $(this).data("ui-resizable"),
			o = that.options,
			co = that.containerOffset,
			cop = that.containerPosition,
			ce = that.containerElement,
			helper = $(that.helper),
			ho = helper.offset(),
			w = helper.outerWidth() - that.sizeDiff.width,
			h = helper.outerHeight() - that.sizeDiff.height;

		if (that._helper && !o.animate && (/relative/).test(ce.css("position"))) {
			$(this).css({ left: ho.left - cop.left - co.left, width: w, height: h });
		}

		if (that._helper && !o.animate && (/static/).test(ce.css("position"))) {
			$(this).css({ left: ho.left - cop.left - co.left, width: w, height: h });
		}

	}
});

$.ui.plugin.add("resizable", "alsoResize", {

	start: function () {
		var that = $(this).data("ui-resizable"),
			o = that.options,
			_store = function (exp) {
				$(exp).each(function() {
					var el = $(this);
					el.data("ui-resizable-alsoresize", {
						width: parseInt(el.width(), 10), height: parseInt(el.height(), 10),
						left: parseInt(el.css("left"), 10), top: parseInt(el.css("top"), 10)
					});
				});
			};

		if (typeof(o.alsoResize) === "object" && !o.alsoResize.parentNode) {
			if (o.alsoResize.length) { o.alsoResize = o.alsoResize[0]; _store(o.alsoResize); }
			else { $.each(o.alsoResize, function (exp) { _store(exp); }); }
		}else{
			_store(o.alsoResize);
		}
	},

	resize: function (event, ui) {
		var that = $(this).data("ui-resizable"),
			o = that.options,
			os = that.originalSize,
			op = that.originalPosition,
			delta = {
				height: (that.size.height - os.height) || 0, width: (that.size.width - os.width) || 0,
				top: (that.position.top - op.top) || 0, left: (that.position.left - op.left) || 0
			},

			_alsoResize = function (exp, c) {
				$(exp).each(function() {
					var el = $(this), start = $(this).data("ui-resizable-alsoresize"), style = {},
						css = c && c.length ? c : el.parents(ui.originalElement[0]).length ? ["width", "height"] : ["width", "height", "top", "left"];

					$.each(css, function (i, prop) {
						var sum = (start[prop]||0) + (delta[prop]||0);
						if (sum && sum >= 0) {
							style[prop] = sum || null;
						}
					});

					el.css(style);
				});
			};

		if (typeof(o.alsoResize) === "object" && !o.alsoResize.nodeType) {
			$.each(o.alsoResize, function (exp, c) { _alsoResize(exp, c); });
		}else{
			_alsoResize(o.alsoResize);
		}
	},

	stop: function () {
		$(this).removeData("resizable-alsoresize");
	}
});

$.ui.plugin.add("resizable", "ghost", {

	start: function() {

		var that = $(this).data("ui-resizable"), o = that.options, cs = that.size;

		that.ghost = that.originalElement.clone();
		that.ghost
			.css({ opacity: 0.25, display: "block", position: "relative", height: cs.height, width: cs.width, margin: 0, left: 0, top: 0 })
			.addClass("ui-resizable-ghost")
			.addClass(typeof o.ghost === "string" ? o.ghost : "");

		that.ghost.appendTo(that.helper);

	},

	resize: function(){
		var that = $(this).data("ui-resizable");
		if (that.ghost) {
			that.ghost.css({ position: "relative", height: that.size.height, width: that.size.width });
		}
	},

	stop: function() {
		var that = $(this).data("ui-resizable");
		if (that.ghost && that.helper) {
			that.helper.get(0).removeChild(that.ghost.get(0));
		}
	}

});

$.ui.plugin.add("resizable", "grid", {

	resize: function() {
		var that = $(this).data("ui-resizable"),
			o = that.options,
			cs = that.size,
			os = that.originalSize,
			op = that.originalPosition,
			a = that.axis,
			grid = typeof o.grid === "number" ? [o.grid, o.grid] : o.grid,
			gridX = (grid[0]||1),
			gridY = (grid[1]||1),
			ox = Math.round((cs.width - os.width) / gridX) * gridX,
			oy = Math.round((cs.height - os.height) / gridY) * gridY,
			newWidth = os.width + ox,
			newHeight = os.height + oy,
			isMaxWidth = o.maxWidth && (o.maxWidth < newWidth),
			isMaxHeight = o.maxHeight && (o.maxHeight < newHeight),
			isMinWidth = o.minWidth && (o.minWidth > newWidth),
			isMinHeight = o.minHeight && (o.minHeight > newHeight);

		o.grid = grid;

		if (isMinWidth) {
			newWidth = newWidth + gridX;
		}
		if (isMinHeight) {
			newHeight = newHeight + gridY;
		}
		if (isMaxWidth) {
			newWidth = newWidth - gridX;
		}
		if (isMaxHeight) {
			newHeight = newHeight - gridY;
		}

		if (/^(se|s|e)$/.test(a)) {
			that.size.width = newWidth;
			that.size.height = newHeight;
		} else if (/^(ne)$/.test(a)) {
			that.size.width = newWidth;
			that.size.height = newHeight;
			that.position.top = op.top - oy;
		} else if (/^(sw)$/.test(a)) {
			that.size.width = newWidth;
			that.size.height = newHeight;
			that.position.left = op.left - ox;
		} else {
			that.size.width = newWidth;
			that.size.height = newHeight;
			that.position.top = op.top - oy;
			that.position.left = op.left - ox;
		}
	}

});

})(jQuery);
(function( $, undefined ) {

$.widget("ui.selectable", $.ui.mouse, {
	version: "1.10.3",
	options: {
		appendTo: "body",
		autoRefresh: true,
		distance: 0,
		filter: "*",
		tolerance: "touch",

		// callbacks
		selected: null,
		selecting: null,
		start: null,
		stop: null,
		unselected: null,
		unselecting: null
	},
	_create: function() {
		var selectees,
			that = this;

		this.element.addClass("ui-selectable");

		this.dragged = false;

		// cache selectee children based on filter
		this.refresh = function() {
			selectees = $(that.options.filter, that.element[0]);
			selectees.addClass("ui-selectee");
			selectees.each(function() {
				var $this = $(this),
					pos = $this.offset();
				$.data(this, "selectable-item", {
					element: this,
					$element: $this,
					left: pos.left,
					top: pos.top,
					right: pos.left + $this.outerWidth(),
					bottom: pos.top + $this.outerHeight(),
					startselected: false,
					selected: $this.hasClass("ui-selected"),
					selecting: $this.hasClass("ui-selecting"),
					unselecting: $this.hasClass("ui-unselecting")
				});
			});
		};
		this.refresh();

		this.selectees = selectees.addClass("ui-selectee");

		this._mouseInit();

		this.helper = $("<div class='ui-selectable-helper'></div>");
	},

	_destroy: function() {
		this.selectees
			.removeClass("ui-selectee")
			.removeData("selectable-item");
		this.element
			.removeClass("ui-selectable ui-selectable-disabled");
		this._mouseDestroy();
	},

	_mouseStart: function(event) {
		var that = this,
			options = this.options;

		this.opos = [event.pageX, event.pageY];

		if (this.options.disabled) {
			return;
		}

		this.selectees = $(options.filter, this.element[0]);

		this._trigger("start", event);

		$(options.appendTo).append(this.helper);
		// position helper (lasso)
		this.helper.css({
			"left": event.pageX,
			"top": event.pageY,
			"width": 0,
			"height": 0
		});

		if (options.autoRefresh) {
			this.refresh();
		}

		this.selectees.filter(".ui-selected").each(function() {
			var selectee = $.data(this, "selectable-item");
			selectee.startselected = true;
			if (!event.metaKey && !event.ctrlKey) {
				selectee.$element.removeClass("ui-selected");
				selectee.selected = false;
				selectee.$element.addClass("ui-unselecting");
				selectee.unselecting = true;
				// selectable UNSELECTING callback
				that._trigger("unselecting", event, {
					unselecting: selectee.element
				});
			}
		});

		$(event.target).parents().addBack().each(function() {
			var doSelect,
				selectee = $.data(this, "selectable-item");
			if (selectee) {
				doSelect = (!event.metaKey && !event.ctrlKey) || !selectee.$element.hasClass("ui-selected");
				selectee.$element
					.removeClass(doSelect ? "ui-unselecting" : "ui-selected")
					.addClass(doSelect ? "ui-selecting" : "ui-unselecting");
				selectee.unselecting = !doSelect;
				selectee.selecting = doSelect;
				selectee.selected = doSelect;
				// selectable (UN)SELECTING callback
				if (doSelect) {
					that._trigger("selecting", event, {
						selecting: selectee.element
					});
				} else {
					that._trigger("unselecting", event, {
						unselecting: selectee.element
					});
				}
				return false;
			}
		});

	},

	_mouseDrag: function(event) {

		this.dragged = true;

		if (this.options.disabled) {
			return;
		}

		var tmp,
			that = this,
			options = this.options,
			x1 = this.opos[0],
			y1 = this.opos[1],
			x2 = event.pageX,
			y2 = event.pageY;

		if (x1 > x2) { tmp = x2; x2 = x1; x1 = tmp; }
		if (y1 > y2) { tmp = y2; y2 = y1; y1 = tmp; }
		this.helper.css({left: x1, top: y1, width: x2-x1, height: y2-y1});

		this.selectees.each(function() {
			var selectee = $.data(this, "selectable-item"),
				hit = false;

			//prevent helper from being selected if appendTo: selectable
			if (!selectee || selectee.element === that.element[0]) {
				return;
			}

			if (options.tolerance === "touch") {
				hit = ( !(selectee.left > x2 || selectee.right < x1 || selectee.top > y2 || selectee.bottom < y1) );
			} else if (options.tolerance === "fit") {
				hit = (selectee.left > x1 && selectee.right < x2 && selectee.top > y1 && selectee.bottom < y2);
			}

			if (hit) {
				// SELECT
				if (selectee.selected) {
					selectee.$element.removeClass("ui-selected");
					selectee.selected = false;
				}
				if (selectee.unselecting) {
					selectee.$element.removeClass("ui-unselecting");
					selectee.unselecting = false;
				}
				if (!selectee.selecting) {
					selectee.$element.addClass("ui-selecting");
					selectee.selecting = true;
					// selectable SELECTING callback
					that._trigger("selecting", event, {
						selecting: selectee.element
					});
				}
			} else {
				// UNSELECT
				if (selectee.selecting) {
					if ((event.metaKey || event.ctrlKey) && selectee.startselected) {
						selectee.$element.removeClass("ui-selecting");
						selectee.selecting = false;
						selectee.$element.addClass("ui-selected");
						selectee.selected = true;
					} else {
						selectee.$element.removeClass("ui-selecting");
						selectee.selecting = false;
						if (selectee.startselected) {
							selectee.$element.addClass("ui-unselecting");
							selectee.unselecting = true;
						}
						// selectable UNSELECTING callback
						that._trigger("unselecting", event, {
							unselecting: selectee.element
						});
					}
				}
				if (selectee.selected) {
					if (!event.metaKey && !event.ctrlKey && !selectee.startselected) {
						selectee.$element.removeClass("ui-selected");
						selectee.selected = false;

						selectee.$element.addClass("ui-unselecting");
						selectee.unselecting = true;
						// selectable UNSELECTING callback
						that._trigger("unselecting", event, {
							unselecting: selectee.element
						});
					}
				}
			}
		});

		return false;
	},

	_mouseStop: function(event) {
		var that = this;

		this.dragged = false;

		$(".ui-unselecting", this.element[0]).each(function() {
			var selectee = $.data(this, "selectable-item");
			selectee.$element.removeClass("ui-unselecting");
			selectee.unselecting = false;
			selectee.startselected = false;
			that._trigger("unselected", event, {
				unselected: selectee.element
			});
		});
		$(".ui-selecting", this.element[0]).each(function() {
			var selectee = $.data(this, "selectable-item");
			selectee.$element.removeClass("ui-selecting").addClass("ui-selected");
			selectee.selecting = false;
			selectee.selected = true;
			selectee.startselected = true;
			that._trigger("selected", event, {
				selected: selectee.element
			});
		});
		this._trigger("stop", event);

		this.helper.remove();

		return false;
	}

});

})(jQuery);
(function( $, undefined ) {

/*jshint loopfunc: true */

function isOverAxis( x, reference, size ) {
	return ( x > reference ) && ( x < ( reference + size ) );
}

function isFloating(item) {
	return (/left|right/).test(item.css("float")) || (/inline|table-cell/).test(item.css("display"));
}

$.widget("ui.sortable", $.ui.mouse, {
	version: "1.10.3",
	widgetEventPrefix: "sort",
	ready: false,
	options: {
		appendTo: "parent",
		axis: false,
		connectWith: false,
		containment: false,
		cursor: "auto",
		cursorAt: false,
		dropOnEmpty: true,
		forcePlaceholderSize: false,
		forceHelperSize: false,
		grid: false,
		handle: false,
		helper: "original",
		items: "> *",
		opacity: false,
		placeholder: false,
		revert: false,
		scroll: true,
		scrollSensitivity: 20,
		scrollSpeed: 20,
		scope: "default",
		tolerance: "intersect",
		zIndex: 1000,

		// callbacks
		activate: null,
		beforeStop: null,
		change: null,
		deactivate: null,
		out: null,
		over: null,
		receive: null,
		remove: null,
		sort: null,
		start: null,
		stop: null,
		update: null
	},
	_create: function() {

		var o = this.options;
		this.containerCache = {};
		this.element.addClass("ui-sortable");

		//Get the items
		this.refresh();

		//Let's determine if the items are being displayed horizontally
		this.floating = this.items.length ? o.axis === "x" || isFloating(this.items[0].item) : false;

		//Let's determine the parent's offset
		this.offset = this.element.offset();

		//Initialize mouse events for interaction
		this._mouseInit();

		//We're ready to go
		this.ready = true;

	},

	_destroy: function() {
		this.element
			.removeClass("ui-sortable ui-sortable-disabled");
		this._mouseDestroy();

		for ( var i = this.items.length - 1; i >= 0; i-- ) {
			this.items[i].item.removeData(this.widgetName + "-item");
		}

		return this;
	},

	_setOption: function(key, value){
		if ( key === "disabled" ) {
			this.options[ key ] = value;

			this.widget().toggleClass( "ui-sortable-disabled", !!value );
		} else {
			// Don't call widget base _setOption for disable as it adds ui-state-disabled class
			$.Widget.prototype._setOption.apply(this, arguments);
		}
	},

	_mouseCapture: function(event, overrideHandle) {
		var currentItem = null,
			validHandle = false,
			that = this;

		if (this.reverting) {
			return false;
		}

		if(this.options.disabled || this.options.type === "static") {
			return false;
		}

		//We have to refresh the items data once first
		this._refreshItems(event);

		//Find out if the clicked node (or one of its parents) is a actual item in this.items
		$(event.target).parents().each(function() {
			if($.data(this, that.widgetName + "-item") === that) {
				currentItem = $(this);
				return false;
			}
		});
		if($.data(event.target, that.widgetName + "-item") === that) {
			currentItem = $(event.target);
		}

		if(!currentItem) {
			return false;
		}
		if(this.options.handle && !overrideHandle) {
			$(this.options.handle, currentItem).find("*").addBack().each(function() {
				if(this === event.target) {
					validHandle = true;
				}
			});
			if(!validHandle) {
				return false;
			}
		}

		this.currentItem = currentItem;
		this._removeCurrentsFromItems();
		return true;

	},

	_mouseStart: function(event, overrideHandle, noActivation) {

		var i, body,
			o = this.options;

		this.currentContainer = this;

		//We only need to call refreshPositions, because the refreshItems call has been moved to mouseCapture
		this.refreshPositions();

		//Create and append the visible helper
		this.helper = this._createHelper(event);

		//Cache the helper size
		this._cacheHelperProportions();

		/*
		 * - Position generation -
		 * This block generates everything position related - it's the core of draggables.
		 */

		//Cache the margins of the original element
		this._cacheMargins();

		//Get the next scrolling parent
		this.scrollParent = this.helper.scrollParent();

		//The element's absolute position on the page minus margins
		this.offset = this.currentItem.offset();
		this.offset = {
			top: this.offset.top - this.margins.top,
			left: this.offset.left - this.margins.left
		};

		$.extend(this.offset, {
			click: { //Where the click happened, relative to the element
				left: event.pageX - this.offset.left,
				top: event.pageY - this.offset.top
			},
			parent: this._getParentOffset(),
			relative: this._getRelativeOffset() //This is a relative to absolute position minus the actual position calculation - only used for relative positioned helper
		});

		// Only after we got the offset, we can change the helper's position to absolute
		// TODO: Still need to figure out a way to make relative sorting possible
		this.helper.css("position", "absolute");
		this.cssPosition = this.helper.css("position");

		//Generate the original position
		this.originalPosition = this._generatePosition(event);
		this.originalPageX = event.pageX;
		this.originalPageY = event.pageY;

		//Adjust the mouse offset relative to the helper if "cursorAt" is supplied
		(o.cursorAt && this._adjustOffsetFromHelper(o.cursorAt));

		//Cache the former DOM position
		this.domPosition = { prev: this.currentItem.prev()[0], parent: this.currentItem.parent()[0] };

		//If the helper is not the original, hide the original so it's not playing any role during the drag, won't cause anything bad this way
		if(this.helper[0] !== this.currentItem[0]) {
			this.currentItem.hide();
		}

		//Create the placeholder
		this._createPlaceholder();

		//Set a containment if given in the options
		if(o.containment) {
			this._setContainment();
		}

		if( o.cursor && o.cursor !== "auto" ) { // cursor option
			body = this.document.find( "body" );

			// support: IE
			this.storedCursor = body.css( "cursor" );
			body.css( "cursor", o.cursor );

			this.storedStylesheet = $( "<style>*{ cursor: "+o.cursor+" !important; }</style>" ).appendTo( body );
		}

		if(o.opacity) { // opacity option
			if (this.helper.css("opacity")) {
				this._storedOpacity = this.helper.css("opacity");
			}
			this.helper.css("opacity", o.opacity);
		}

		if(o.zIndex) { // zIndex option
			if (this.helper.css("zIndex")) {
				this._storedZIndex = this.helper.css("zIndex");
			}
			this.helper.css("zIndex", o.zIndex);
		}

		//Prepare scrolling
		if(this.scrollParent[0] !== document && this.scrollParent[0].tagName !== "HTML") {
			this.overflowOffset = this.scrollParent.offset();
		}

		//Call callbacks
		this._trigger("start", event, this._uiHash());

		//Recache the helper size
		if(!this._preserveHelperProportions) {
			this._cacheHelperProportions();
		}


		//Post "activate" events to possible containers
		if( !noActivation ) {
			for ( i = this.containers.length - 1; i >= 0; i-- ) {
				this.containers[ i ]._trigger( "activate", event, this._uiHash( this ) );
			}
		}

		//Prepare possible droppables
		if($.ui.ddmanager) {
			$.ui.ddmanager.current = this;
		}

		if ($.ui.ddmanager && !o.dropBehaviour) {
			$.ui.ddmanager.prepareOffsets(this, event);
		}

		this.dragging = true;

		this.helper.addClass("ui-sortable-helper");
		this._mouseDrag(event); //Execute the drag once - this causes the helper not to be visible before getting its correct position
		return true;

	},

	_mouseDrag: function(event) {
		var i, item, itemElement, intersection,
			o = this.options,
			scrolled = false;

		//Compute the helpers position
		this.position = this._generatePosition(event);
		this.positionAbs = this._convertPositionTo("absolute");

		if (!this.lastPositionAbs) {
			this.lastPositionAbs = this.positionAbs;
		}

		//Do scrolling
		if(this.options.scroll) {
			if(this.scrollParent[0] !== document && this.scrollParent[0].tagName !== "HTML") {

				if((this.overflowOffset.top + this.scrollParent[0].offsetHeight) - event.pageY < o.scrollSensitivity) {
					this.scrollParent[0].scrollTop = scrolled = this.scrollParent[0].scrollTop + o.scrollSpeed;
				} else if(event.pageY - this.overflowOffset.top < o.scrollSensitivity) {
					this.scrollParent[0].scrollTop = scrolled = this.scrollParent[0].scrollTop - o.scrollSpeed;
				}

				if((this.overflowOffset.left + this.scrollParent[0].offsetWidth) - event.pageX < o.scrollSensitivity) {
					this.scrollParent[0].scrollLeft = scrolled = this.scrollParent[0].scrollLeft + o.scrollSpeed;
				} else if(event.pageX - this.overflowOffset.left < o.scrollSensitivity) {
					this.scrollParent[0].scrollLeft = scrolled = this.scrollParent[0].scrollLeft - o.scrollSpeed;
				}

			} else {

				if(event.pageY - $(document).scrollTop() < o.scrollSensitivity) {
					scrolled = $(document).scrollTop($(document).scrollTop() - o.scrollSpeed);
				} else if($(window).height() - (event.pageY - $(document).scrollTop()) < o.scrollSensitivity) {
					scrolled = $(document).scrollTop($(document).scrollTop() + o.scrollSpeed);
				}

				if(event.pageX - $(document).scrollLeft() < o.scrollSensitivity) {
					scrolled = $(document).scrollLeft($(document).scrollLeft() - o.scrollSpeed);
				} else if($(window).width() - (event.pageX - $(document).scrollLeft()) < o.scrollSensitivity) {
					scrolled = $(document).scrollLeft($(document).scrollLeft() + o.scrollSpeed);
				}

			}

			if(scrolled !== false && $.ui.ddmanager && !o.dropBehaviour) {
				$.ui.ddmanager.prepareOffsets(this, event);
			}
		}

		//Regenerate the absolute position used for position checks
		this.positionAbs = this._convertPositionTo("absolute");

		//Set the helper position
		if(!this.options.axis || this.options.axis !== "y") {
			this.helper[0].style.left = this.position.left+"px";
		}
		if(!this.options.axis || this.options.axis !== "x") {
			this.helper[0].style.top = this.position.top+"px";
		}

		//Rearrange
		for (i = this.items.length - 1; i >= 0; i--) {

			//Cache variables and intersection, continue if no intersection
			item = this.items[i];
			itemElement = item.item[0];
			intersection = this._intersectsWithPointer(item);
			if (!intersection) {
				continue;
			}

			// Only put the placeholder inside the current Container, skip all
			// items form other containers. This works because when moving
			// an item from one container to another the
			// currentContainer is switched before the placeholder is moved.
			//
			// Without this moving items in "sub-sortables" can cause the placeholder to jitter
			// beetween the outer and inner container.
			if (item.instance !== this.currentContainer) {
				continue;
			}

			// cannot intersect with itself
			// no useless actions that have been done before
			// no action if the item moved is the parent of the item checked
			if (itemElement !== this.currentItem[0] &&
				this.placeholder[intersection === 1 ? "next" : "prev"]()[0] !== itemElement &&
				!$.contains(this.placeholder[0], itemElement) &&
				(this.options.type === "semi-dynamic" ? !$.contains(this.element[0], itemElement) : true)
			) {

				this.direction = intersection === 1 ? "down" : "up";

				if (this.options.tolerance === "pointer" || this._intersectsWithSides(item)) {
					this._rearrange(event, item);
				} else {
					break;
				}

				this._trigger("change", event, this._uiHash());
				break;
			}
		}

		//Post events to containers
		this._contactContainers(event);

		//Interconnect with droppables
		if($.ui.ddmanager) {
			$.ui.ddmanager.drag(this, event);
		}

		//Call callbacks
		this._trigger("sort", event, this._uiHash());

		this.lastPositionAbs = this.positionAbs;
		return false;

	},

	_mouseStop: function(event, noPropagation) {

		if(!event) {
			return;
		}

		//If we are using droppables, inform the manager about the drop
		if ($.ui.ddmanager && !this.options.dropBehaviour) {
			$.ui.ddmanager.drop(this, event);
		}

		if(this.options.revert) {
			var that = this,
				cur = this.placeholder.offset(),
				axis = this.options.axis,
				animation = {};

			if ( !axis || axis === "x" ) {
				animation.left = cur.left - this.offset.parent.left - this.margins.left + (this.offsetParent[0] === document.body ? 0 : this.offsetParent[0].scrollLeft);
			}
			if ( !axis || axis === "y" ) {
				animation.top = cur.top - this.offset.parent.top - this.margins.top + (this.offsetParent[0] === document.body ? 0 : this.offsetParent[0].scrollTop);
			}
			this.reverting = true;
			$(this.helper).animate( animation, parseInt(this.options.revert, 10) || 500, function() {
				that._clear(event);
			});
		} else {
			this._clear(event, noPropagation);
		}

		return false;

	},

	cancel: function() {

		if(this.dragging) {

			this._mouseUp({ target: null });

			if(this.options.helper === "original") {
				this.currentItem.css(this._storedCSS).removeClass("ui-sortable-helper");
			} else {
				this.currentItem.show();
			}

			//Post deactivating events to containers
			for (var i = this.containers.length - 1; i >= 0; i--){
				this.containers[i]._trigger("deactivate", null, this._uiHash(this));
				if(this.containers[i].containerCache.over) {
					this.containers[i]._trigger("out", null, this._uiHash(this));
					this.containers[i].containerCache.over = 0;
				}
			}

		}

		if (this.placeholder) {
			//$(this.placeholder[0]).remove(); would have been the jQuery way - unfortunately, it unbinds ALL events from the original node!
			if(this.placeholder[0].parentNode) {
				this.placeholder[0].parentNode.removeChild(this.placeholder[0]);
			}
			if(this.options.helper !== "original" && this.helper && this.helper[0].parentNode) {
				this.helper.remove();
			}

			$.extend(this, {
				helper: null,
				dragging: false,
				reverting: false,
				_noFinalSort: null
			});

			if(this.domPosition.prev) {
				$(this.domPosition.prev).after(this.currentItem);
			} else {
				$(this.domPosition.parent).prepend(this.currentItem);
			}
		}

		return this;

	},

	serialize: function(o) {

		var items = this._getItemsAsjQuery(o && o.connected),
			str = [];
		o = o || {};

		$(items).each(function() {
			var res = ($(o.item || this).attr(o.attribute || "id") || "").match(o.expression || (/(.+)[\-=_](.+)/));
			if (res) {
				str.push((o.key || res[1]+"[]")+"="+(o.key && o.expression ? res[1] : res[2]));
			}
		});

		if(!str.length && o.key) {
			str.push(o.key + "=");
		}

		return str.join("&");

	},

	toArray: function(o) {

		var items = this._getItemsAsjQuery(o && o.connected),
			ret = [];

		o = o || {};

		items.each(function() { ret.push($(o.item || this).attr(o.attribute || "id") || ""); });
		return ret;

	},

	/* Be careful with the following core functions */
	_intersectsWith: function(item) {

		var x1 = this.positionAbs.left,
			x2 = x1 + this.helperProportions.width,
			y1 = this.positionAbs.top,
			y2 = y1 + this.helperProportions.height,
			l = item.left,
			r = l + item.width,
			t = item.top,
			b = t + item.height,
			dyClick = this.offset.click.top,
			dxClick = this.offset.click.left,
			isOverElementHeight = ( this.options.axis === "x" ) || ( ( y1 + dyClick ) > t && ( y1 + dyClick ) < b ),
			isOverElementWidth = ( this.options.axis === "y" ) || ( ( x1 + dxClick ) > l && ( x1 + dxClick ) < r ),
			isOverElement = isOverElementHeight && isOverElementWidth;

		if ( this.options.tolerance === "pointer" ||
			this.options.forcePointerForContainers ||
			(this.options.tolerance !== "pointer" && this.helperProportions[this.floating ? "width" : "height"] > item[this.floating ? "width" : "height"])
		) {
			return isOverElement;
		} else {

			return (l < x1 + (this.helperProportions.width / 2) && // Right Half
				x2 - (this.helperProportions.width / 2) < r && // Left Half
				t < y1 + (this.helperProportions.height / 2) && // Bottom Half
				y2 - (this.helperProportions.height / 2) < b ); // Top Half

		}
	},

	_intersectsWithPointer: function(item) {

		var isOverElementHeight = (this.options.axis === "x") || isOverAxis(this.positionAbs.top + this.offset.click.top, item.top, item.height),
			isOverElementWidth = (this.options.axis === "y") || isOverAxis(this.positionAbs.left + this.offset.click.left, item.left, item.width),
			isOverElement = isOverElementHeight && isOverElementWidth,
			verticalDirection = this._getDragVerticalDirection(),
			horizontalDirection = this._getDragHorizontalDirection();

		if (!isOverElement) {
			return false;
		}

		return this.floating ?
			( ((horizontalDirection && horizontalDirection === "right") || verticalDirection === "down") ? 2 : 1 )
			: ( verticalDirection && (verticalDirection === "down" ? 2 : 1) );

	},

	_intersectsWithSides: function(item) {

		var isOverBottomHalf = isOverAxis(this.positionAbs.top + this.offset.click.top, item.top + (item.height/2), item.height),
			isOverRightHalf = isOverAxis(this.positionAbs.left + this.offset.click.left, item.left + (item.width/2), item.width),
			verticalDirection = this._getDragVerticalDirection(),
			horizontalDirection = this._getDragHorizontalDirection();

		if (this.floating && horizontalDirection) {
			return ((horizontalDirection === "right" && isOverRightHalf) || (horizontalDirection === "left" && !isOverRightHalf));
		} else {
			return verticalDirection && ((verticalDirection === "down" && isOverBottomHalf) || (verticalDirection === "up" && !isOverBottomHalf));
		}

	},

	_getDragVerticalDirection: function() {
		var delta = this.positionAbs.top - this.lastPositionAbs.top;
		return delta !== 0 && (delta > 0 ? "down" : "up");
	},

	_getDragHorizontalDirection: function() {
		var delta = this.positionAbs.left - this.lastPositionAbs.left;
		return delta !== 0 && (delta > 0 ? "right" : "left");
	},

	refresh: function(event) {
		this._refreshItems(event);
		this.refreshPositions();
		return this;
	},

	_connectWith: function() {
		var options = this.options;
		return options.connectWith.constructor === String ? [options.connectWith] : options.connectWith;
	},

	_getItemsAsjQuery: function(connected) {

		var i, j, cur, inst,
			items = [],
			queries = [],
			connectWith = this._connectWith();

		if(connectWith && connected) {
			for (i = connectWith.length - 1; i >= 0; i--){
				cur = $(connectWith[i]);
				for ( j = cur.length - 1; j >= 0; j--){
					inst = $.data(cur[j], this.widgetFullName);
					if(inst && inst !== this && !inst.options.disabled) {
						queries.push([$.isFunction(inst.options.items) ? inst.options.items.call(inst.element) : $(inst.options.items, inst.element).not(".ui-sortable-helper").not(".ui-sortable-placeholder"), inst]);
					}
				}
			}
		}

		queries.push([$.isFunction(this.options.items) ? this.options.items.call(this.element, null, { options: this.options, item: this.currentItem }) : $(this.options.items, this.element).not(".ui-sortable-helper").not(".ui-sortable-placeholder"), this]);

		for (i = queries.length - 1; i >= 0; i--){
			queries[i][0].each(function() {
				items.push(this);
			});
		}

		return $(items);

	},

	_removeCurrentsFromItems: function() {

		var list = this.currentItem.find(":data(" + this.widgetName + "-item)");

		this.items = $.grep(this.items, function (item) {
			for (var j=0; j < list.length; j++) {
				if(list[j] === item.item[0]) {
					return false;
				}
			}
			return true;
		});

	},

	_refreshItems: function(event) {

		this.items = [];
		this.containers = [this];

		var i, j, cur, inst, targetData, _queries, item, queriesLength,
			items = this.items,
			queries = [[$.isFunction(this.options.items) ? this.options.items.call(this.element[0], event, { item: this.currentItem }) : $(this.options.items, this.element), this]],
			connectWith = this._connectWith();

		if(connectWith && this.ready) { //Shouldn't be run the first time through due to massive slow-down
			for (i = connectWith.length - 1; i >= 0; i--){
				cur = $(connectWith[i]);
				for (j = cur.length - 1; j >= 0; j--){
					inst = $.data(cur[j], this.widgetFullName);
					if(inst && inst !== this && !inst.options.disabled) {
						queries.push([$.isFunction(inst.options.items) ? inst.options.items.call(inst.element[0], event, { item: this.currentItem }) : $(inst.options.items, inst.element), inst]);
						this.containers.push(inst);
					}
				}
			}
		}

		for (i = queries.length - 1; i >= 0; i--) {
			targetData = queries[i][1];
			_queries = queries[i][0];

			for (j=0, queriesLength = _queries.length; j < queriesLength; j++) {
				item = $(_queries[j]);

				item.data(this.widgetName + "-item", targetData); // Data for target checking (mouse manager)

				items.push({
					item: item,
					instance: targetData,
					width: 0, height: 0,
					left: 0, top: 0
				});
			}
		}

	},

	refreshPositions: function(fast) {

		//This has to be redone because due to the item being moved out/into the offsetParent, the offsetParent's position will change
		if(this.offsetParent && this.helper) {
			this.offset.parent = this._getParentOffset();
		}

		var i, item, t, p;

		for (i = this.items.length - 1; i >= 0; i--){
			item = this.items[i];

			//We ignore calculating positions of all connected containers when we're not over them
			if(item.instance !== this.currentContainer && this.currentContainer && item.item[0] !== this.currentItem[0]) {
				continue;
			}

			t = this.options.toleranceElement ? $(this.options.toleranceElement, item.item) : item.item;

			if (!fast) {
				item.width = t.outerWidth();
				item.height = t.outerHeight();
			}

			p = t.offset();
			item.left = p.left;
			item.top = p.top;
		}

		if(this.options.custom && this.options.custom.refreshContainers) {
			this.options.custom.refreshContainers.call(this);
		} else {
			for (i = this.containers.length - 1; i >= 0; i--){
				p = this.containers[i].element.offset();
				this.containers[i].containerCache.left = p.left;
				this.containers[i].containerCache.top = p.top;
				this.containers[i].containerCache.width	= this.containers[i].element.outerWidth();
				this.containers[i].containerCache.height = this.containers[i].element.outerHeight();
			}
		}

		return this;
	},

	_createPlaceholder: function(that) {
		that = that || this;
		var className,
			o = that.options;

		if(!o.placeholder || o.placeholder.constructor === String) {
			className = o.placeholder;
			o.placeholder = {
				element: function() {

					var nodeName = that.currentItem[0].nodeName.toLowerCase(),
						element = $( "<" + nodeName + ">", that.document[0] )
							.addClass(className || that.currentItem[0].className+" ui-sortable-placeholder")
							.removeClass("ui-sortable-helper");

					if ( nodeName === "tr" ) {
						that.currentItem.children().each(function() {
							$( "<td>&#160;</td>", that.document[0] )
								.attr( "colspan", $( this ).attr( "colspan" ) || 1 )
								.appendTo( element );
						});
					} else if ( nodeName === "img" ) {
						element.attr( "src", that.currentItem.attr( "src" ) );
					}

					if ( !className ) {
						element.css( "visibility", "hidden" );
					}

					return element;
				},
				update: function(container, p) {

					// 1. If a className is set as 'placeholder option, we don't force sizes - the class is responsible for that
					// 2. The option 'forcePlaceholderSize can be enabled to force it even if a class name is specified
					if(className && !o.forcePlaceholderSize) {
						return;
					}

					//If the element doesn't have a actual height by itself (without styles coming from a stylesheet), it receives the inline height from the dragged item
					if(!p.height()) { p.height(that.currentItem.innerHeight() - parseInt(that.currentItem.css("paddingTop")||0, 10) - parseInt(that.currentItem.css("paddingBottom")||0, 10)); }
					if(!p.width()) { p.width(that.currentItem.innerWidth() - parseInt(that.currentItem.css("paddingLeft")||0, 10) - parseInt(that.currentItem.css("paddingRight")||0, 10)); }
				}
			};
		}

		//Create the placeholder
		that.placeholder = $(o.placeholder.element.call(that.element, that.currentItem));

		//Append it after the actual current item
		that.currentItem.after(that.placeholder);

		//Update the size of the placeholder (TODO: Logic to fuzzy, see line 316/317)
		o.placeholder.update(that, that.placeholder);

	},

	_contactContainers: function(event) {
		var i, j, dist, itemWithLeastDistance, posProperty, sizeProperty, base, cur, nearBottom, floating,
			innermostContainer = null,
			innermostIndex = null;

		// get innermost container that intersects with item
		for (i = this.containers.length - 1; i >= 0; i--) {

			// never consider a container that's located within the item itself
			if($.contains(this.currentItem[0], this.containers[i].element[0])) {
				continue;
			}

			if(this._intersectsWith(this.containers[i].containerCache)) {

				// if we've already found a container and it's more "inner" than this, then continue
				if(innermostContainer && $.contains(this.containers[i].element[0], innermostContainer.element[0])) {
					continue;
				}

				innermostContainer = this.containers[i];
				innermostIndex = i;

			} else {
				// container doesn't intersect. trigger "out" event if necessary
				if(this.containers[i].containerCache.over) {
					this.containers[i]._trigger("out", event, this._uiHash(this));
					this.containers[i].containerCache.over = 0;
				}
			}

		}

		// if no intersecting containers found, return
		if(!innermostContainer) {
			return;
		}

		// move the item into the container if it's not there already
		if(this.containers.length === 1) {
			if (!this.containers[innermostIndex].containerCache.over) {
				this.containers[innermostIndex]._trigger("over", event, this._uiHash(this));
				this.containers[innermostIndex].containerCache.over = 1;
			}
		} else {

			//When entering a new container, we will find the item with the least distance and append our item near it
			dist = 10000;
			itemWithLeastDistance = null;
			floating = innermostContainer.floating || isFloating(this.currentItem);
			posProperty = floating ? "left" : "top";
			sizeProperty = floating ? "width" : "height";
			base = this.positionAbs[posProperty] + this.offset.click[posProperty];
			for (j = this.items.length - 1; j >= 0; j--) {
				if(!$.contains(this.containers[innermostIndex].element[0], this.items[j].item[0])) {
					continue;
				}
				if(this.items[j].item[0] === this.currentItem[0]) {
					continue;
				}
				if (floating && !isOverAxis(this.positionAbs.top + this.offset.click.top, this.items[j].top, this.items[j].height)) {
					continue;
				}
				cur = this.items[j].item.offset()[posProperty];
				nearBottom = false;
				if(Math.abs(cur - base) > Math.abs(cur + this.items[j][sizeProperty] - base)){
					nearBottom = true;
					cur += this.items[j][sizeProperty];
				}

				if(Math.abs(cur - base) < dist) {
					dist = Math.abs(cur - base); itemWithLeastDistance = this.items[j];
					this.direction = nearBottom ? "up": "down";
				}
			}

			//Check if dropOnEmpty is enabled
			if(!itemWithLeastDistance && !this.options.dropOnEmpty) {
				return;
			}

			if(this.currentContainer === this.containers[innermostIndex]) {
				return;
			}

			itemWithLeastDistance ? this._rearrange(event, itemWithLeastDistance, null, true) : this._rearrange(event, null, this.containers[innermostIndex].element, true);
			this._trigger("change", event, this._uiHash());
			this.containers[innermostIndex]._trigger("change", event, this._uiHash(this));
			this.currentContainer = this.containers[innermostIndex];

			//Update the placeholder
			this.options.placeholder.update(this.currentContainer, this.placeholder);

			this.containers[innermostIndex]._trigger("over", event, this._uiHash(this));
			this.containers[innermostIndex].containerCache.over = 1;
		}


	},

	_createHelper: function(event) {

		var o = this.options,
			helper = $.isFunction(o.helper) ? $(o.helper.apply(this.element[0], [event, this.currentItem])) : (o.helper === "clone" ? this.currentItem.clone() : this.currentItem);

		//Add the helper to the DOM if that didn't happen already
		if(!helper.parents("body").length) {
			$(o.appendTo !== "parent" ? o.appendTo : this.currentItem[0].parentNode)[0].appendChild(helper[0]);
		}

		if(helper[0] === this.currentItem[0]) {
			this._storedCSS = { width: this.currentItem[0].style.width, height: this.currentItem[0].style.height, position: this.currentItem.css("position"), top: this.currentItem.css("top"), left: this.currentItem.css("left") };
		}

		if(!helper[0].style.width || o.forceHelperSize) {
			helper.width(this.currentItem.width());
		}
		if(!helper[0].style.height || o.forceHelperSize) {
			helper.height(this.currentItem.height());
		}

		return helper;

	},

	_adjustOffsetFromHelper: function(obj) {
		if (typeof obj === "string") {
			obj = obj.split(" ");
		}
		if ($.isArray(obj)) {
			obj = {left: +obj[0], top: +obj[1] || 0};
		}
		if ("left" in obj) {
			this.offset.click.left = obj.left + this.margins.left;
		}
		if ("right" in obj) {
			this.offset.click.left = this.helperProportions.width - obj.right + this.margins.left;
		}
		if ("top" in obj) {
			this.offset.click.top = obj.top + this.margins.top;
		}
		if ("bottom" in obj) {
			this.offset.click.top = this.helperProportions.height - obj.bottom + this.margins.top;
		}
	},

	_getParentOffset: function() {


		//Get the offsetParent and cache its position
		this.offsetParent = this.helper.offsetParent();
		var po = this.offsetParent.offset();

		// This is a special case where we need to modify a offset calculated on start, since the following happened:
		// 1. The position of the helper is absolute, so it's position is calculated based on the next positioned parent
		// 2. The actual offset parent is a child of the scroll parent, and the scroll parent isn't the document, which means that
		//    the scroll is included in the initial calculation of the offset of the parent, and never recalculated upon drag
		if(this.cssPosition === "absolute" && this.scrollParent[0] !== document && $.contains(this.scrollParent[0], this.offsetParent[0])) {
			po.left += this.scrollParent.scrollLeft();
			po.top += this.scrollParent.scrollTop();
		}

		// This needs to be actually done for all browsers, since pageX/pageY includes this information
		// with an ugly IE fix
		if( this.offsetParent[0] === document.body || (this.offsetParent[0].tagName && this.offsetParent[0].tagName.toLowerCase() === "html" && $.ui.ie)) {
			po = { top: 0, left: 0 };
		}

		return {
			top: po.top + (parseInt(this.offsetParent.css("borderTopWidth"),10) || 0),
			left: po.left + (parseInt(this.offsetParent.css("borderLeftWidth"),10) || 0)
		};

	},

	_getRelativeOffset: function() {

		if(this.cssPosition === "relative") {
			var p = this.currentItem.position();
			return {
				top: p.top - (parseInt(this.helper.css("top"),10) || 0) + this.scrollParent.scrollTop(),
				left: p.left - (parseInt(this.helper.css("left"),10) || 0) + this.scrollParent.scrollLeft()
			};
		} else {
			return { top: 0, left: 0 };
		}

	},

	_cacheMargins: function() {
		this.margins = {
			left: (parseInt(this.currentItem.css("marginLeft"),10) || 0),
			top: (parseInt(this.currentItem.css("marginTop"),10) || 0)
		};
	},

	_cacheHelperProportions: function() {
		this.helperProportions = {
			width: this.helper.outerWidth(),
			height: this.helper.outerHeight()
		};
	},

	_setContainment: function() {

		var ce, co, over,
			o = this.options;
		if(o.containment === "parent") {
			o.containment = this.helper[0].parentNode;
		}
		if(o.containment === "document" || o.containment === "window") {
			this.containment = [
				0 - this.offset.relative.left - this.offset.parent.left,
				0 - this.offset.relative.top - this.offset.parent.top,
				$(o.containment === "document" ? document : window).width() - this.helperProportions.width - this.margins.left,
				($(o.containment === "document" ? document : window).height() || document.body.parentNode.scrollHeight) - this.helperProportions.height - this.margins.top
			];
		}

		if(!(/^(document|window|parent)$/).test(o.containment)) {
			ce = $(o.containment)[0];
			co = $(o.containment).offset();
			over = ($(ce).css("overflow") !== "hidden");

			this.containment = [
				co.left + (parseInt($(ce).css("borderLeftWidth"),10) || 0) + (parseInt($(ce).css("paddingLeft"),10) || 0) - this.margins.left,
				co.top + (parseInt($(ce).css("borderTopWidth"),10) || 0) + (parseInt($(ce).css("paddingTop"),10) || 0) - this.margins.top,
				co.left+(over ? Math.max(ce.scrollWidth,ce.offsetWidth) : ce.offsetWidth) - (parseInt($(ce).css("borderLeftWidth"),10) || 0) - (parseInt($(ce).css("paddingRight"),10) || 0) - this.helperProportions.width - this.margins.left,
				co.top+(over ? Math.max(ce.scrollHeight,ce.offsetHeight) : ce.offsetHeight) - (parseInt($(ce).css("borderTopWidth"),10) || 0) - (parseInt($(ce).css("paddingBottom"),10) || 0) - this.helperProportions.height - this.margins.top
			];
		}

	},

	_convertPositionTo: function(d, pos) {

		if(!pos) {
			pos = this.position;
		}
		var mod = d === "absolute" ? 1 : -1,
			scroll = this.cssPosition === "absolute" && !(this.scrollParent[0] !== document && $.contains(this.scrollParent[0], this.offsetParent[0])) ? this.offsetParent : this.scrollParent,
			scrollIsRootNode = (/(html|body)/i).test(scroll[0].tagName);

		return {
			top: (
				pos.top	+																// The absolute mouse position
				this.offset.relative.top * mod +										// Only for relative positioned nodes: Relative offset from element to offset parent
				this.offset.parent.top * mod -											// The offsetParent's offset without borders (offset + border)
				( ( this.cssPosition === "fixed" ? -this.scrollParent.scrollTop() : ( scrollIsRootNode ? 0 : scroll.scrollTop() ) ) * mod)
			),
			left: (
				pos.left +																// The absolute mouse position
				this.offset.relative.left * mod +										// Only for relative positioned nodes: Relative offset from element to offset parent
				this.offset.parent.left * mod	-										// The offsetParent's offset without borders (offset + border)
				( ( this.cssPosition === "fixed" ? -this.scrollParent.scrollLeft() : scrollIsRootNode ? 0 : scroll.scrollLeft() ) * mod)
			)
		};

	},

	_generatePosition: function(event) {

		var top, left,
			o = this.options,
			pageX = event.pageX,
			pageY = event.pageY,
			scroll = this.cssPosition === "absolute" && !(this.scrollParent[0] !== document && $.contains(this.scrollParent[0], this.offsetParent[0])) ? this.offsetParent : this.scrollParent, scrollIsRootNode = (/(html|body)/i).test(scroll[0].tagName);

		// This is another very weird special case that only happens for relative elements:
		// 1. If the css position is relative
		// 2. and the scroll parent is the document or similar to the offset parent
		// we have to refresh the relative offset during the scroll so there are no jumps
		if(this.cssPosition === "relative" && !(this.scrollParent[0] !== document && this.scrollParent[0] !== this.offsetParent[0])) {
			this.offset.relative = this._getRelativeOffset();
		}

		/*
		 * - Position constraining -
		 * Constrain the position to a mix of grid, containment.
		 */

		if(this.originalPosition) { //If we are not dragging yet, we won't check for options

			if(this.containment) {
				if(event.pageX - this.offset.click.left < this.containment[0]) {
					pageX = this.containment[0] + this.offset.click.left;
				}
				if(event.pageY - this.offset.click.top < this.containment[1]) {
					pageY = this.containment[1] + this.offset.click.top;
				}
				if(event.pageX - this.offset.click.left > this.containment[2]) {
					pageX = this.containment[2] + this.offset.click.left;
				}
				if(event.pageY - this.offset.click.top > this.containment[3]) {
					pageY = this.containment[3] + this.offset.click.top;
				}
			}

			if(o.grid) {
				top = this.originalPageY + Math.round((pageY - this.originalPageY) / o.grid[1]) * o.grid[1];
				pageY = this.containment ? ( (top - this.offset.click.top >= this.containment[1] && top - this.offset.click.top <= this.containment[3]) ? top : ((top - this.offset.click.top >= this.containment[1]) ? top - o.grid[1] : top + o.grid[1])) : top;

				left = this.originalPageX + Math.round((pageX - this.originalPageX) / o.grid[0]) * o.grid[0];
				pageX = this.containment ? ( (left - this.offset.click.left >= this.containment[0] && left - this.offset.click.left <= this.containment[2]) ? left : ((left - this.offset.click.left >= this.containment[0]) ? left - o.grid[0] : left + o.grid[0])) : left;
			}

		}

		return {
			top: (
				pageY -																// The absolute mouse position
				this.offset.click.top -													// Click offset (relative to the element)
				this.offset.relative.top	-											// Only for relative positioned nodes: Relative offset from element to offset parent
				this.offset.parent.top +												// The offsetParent's offset without borders (offset + border)
				( ( this.cssPosition === "fixed" ? -this.scrollParent.scrollTop() : ( scrollIsRootNode ? 0 : scroll.scrollTop() ) ))
			),
			left: (
				pageX -																// The absolute mouse position
				this.offset.click.left -												// Click offset (relative to the element)
				this.offset.relative.left	-											// Only for relative positioned nodes: Relative offset from element to offset parent
				this.offset.parent.left +												// The offsetParent's offset without borders (offset + border)
				( ( this.cssPosition === "fixed" ? -this.scrollParent.scrollLeft() : scrollIsRootNode ? 0 : scroll.scrollLeft() ))
			)
		};

	},

	_rearrange: function(event, i, a, hardRefresh) {

		a ? a[0].appendChild(this.placeholder[0]) : i.item[0].parentNode.insertBefore(this.placeholder[0], (this.direction === "down" ? i.item[0] : i.item[0].nextSibling));

		//Various things done here to improve the performance:
		// 1. we create a setTimeout, that calls refreshPositions
		// 2. on the instance, we have a counter variable, that get's higher after every append
		// 3. on the local scope, we copy the counter variable, and check in the timeout, if it's still the same
		// 4. this lets only the last addition to the timeout stack through
		this.counter = this.counter ? ++this.counter : 1;
		var counter = this.counter;

		this._delay(function() {
			if(counter === this.counter) {
				this.refreshPositions(!hardRefresh); //Precompute after each DOM insertion, NOT on mousemove
			}
		});

	},

	_clear: function(event, noPropagation) {

		this.reverting = false;
		// We delay all events that have to be triggered to after the point where the placeholder has been removed and
		// everything else normalized again
		var i,
			delayedTriggers = [];

		// We first have to update the dom position of the actual currentItem
		// Note: don't do it if the current item is already removed (by a user), or it gets reappended (see #4088)
		if(!this._noFinalSort && this.currentItem.parent().length) {
			this.placeholder.before(this.currentItem);
		}
		this._noFinalSort = null;

		if(this.helper[0] === this.currentItem[0]) {
			for(i in this._storedCSS) {
				if(this._storedCSS[i] === "auto" || this._storedCSS[i] === "static") {
					this._storedCSS[i] = "";
				}
			}
			this.currentItem.css(this._storedCSS).removeClass("ui-sortable-helper");
		} else {
			this.currentItem.show();
		}

		if(this.fromOutside && !noPropagation) {
			delayedTriggers.push(function(event) { this._trigger("receive", event, this._uiHash(this.fromOutside)); });
		}
		if((this.fromOutside || this.domPosition.prev !== this.currentItem.prev().not(".ui-sortable-helper")[0] || this.domPosition.parent !== this.currentItem.parent()[0]) && !noPropagation) {
			delayedTriggers.push(function(event) { this._trigger("update", event, this._uiHash()); }); //Trigger update callback if the DOM position has changed
		}

		// Check if the items Container has Changed and trigger appropriate
		// events.
		if (this !== this.currentContainer) {
			if(!noPropagation) {
				delayedTriggers.push(function(event) { this._trigger("remove", event, this._uiHash()); });
				delayedTriggers.push((function(c) { return function(event) { c._trigger("receive", event, this._uiHash(this)); };  }).call(this, this.currentContainer));
				delayedTriggers.push((function(c) { return function(event) { c._trigger("update", event, this._uiHash(this));  }; }).call(this, this.currentContainer));
			}
		}


		//Post events to containers
		for (i = this.containers.length - 1; i >= 0; i--){
			if(!noPropagation) {
				delayedTriggers.push((function(c) { return function(event) { c._trigger("deactivate", event, this._uiHash(this)); };  }).call(this, this.containers[i]));
			}
			if(this.containers[i].containerCache.over) {
				delayedTriggers.push((function(c) { return function(event) { c._trigger("out", event, this._uiHash(this)); };  }).call(this, this.containers[i]));
				this.containers[i].containerCache.over = 0;
			}
		}

		//Do what was originally in plugins
		if ( this.storedCursor ) {
			this.document.find( "body" ).css( "cursor", this.storedCursor );
			this.storedStylesheet.remove();
		}
		if(this._storedOpacity) {
			this.helper.css("opacity", this._storedOpacity);
		}
		if(this._storedZIndex) {
			this.helper.css("zIndex", this._storedZIndex === "auto" ? "" : this._storedZIndex);
		}

		this.dragging = false;
		if(this.cancelHelperRemoval) {
			if(!noPropagation) {
				this._trigger("beforeStop", event, this._uiHash());
				for (i=0; i < delayedTriggers.length; i++) {
					delayedTriggers[i].call(this, event);
				} //Trigger all delayed events
				this._trigger("stop", event, this._uiHash());
			}

			this.fromOutside = false;
			return false;
		}

		if(!noPropagation) {
			this._trigger("beforeStop", event, this._uiHash());
		}

		//$(this.placeholder[0]).remove(); would have been the jQuery way - unfortunately, it unbinds ALL events from the original node!
		this.placeholder[0].parentNode.removeChild(this.placeholder[0]);

		if(this.helper[0] !== this.currentItem[0]) {
			this.helper.remove();
		}
		this.helper = null;

		if(!noPropagation) {
			for (i=0; i < delayedTriggers.length; i++) {
				delayedTriggers[i].call(this, event);
			} //Trigger all delayed events
			this._trigger("stop", event, this._uiHash());
		}

		this.fromOutside = false;
		return true;

	},

	_trigger: function() {
		if ($.Widget.prototype._trigger.apply(this, arguments) === false) {
			this.cancel();
		}
	},

	_uiHash: function(_inst) {
		var inst = _inst || this;
		return {
			helper: inst.helper,
			placeholder: inst.placeholder || $([]),
			position: inst.position,
			originalPosition: inst.originalPosition,
			offset: inst.positionAbs,
			item: inst.currentItem,
			sender: _inst ? _inst.element : null
		};
	}

});

})(jQuery);
(function( $, undefined ) {

var lastActive, startXPos, startYPos, clickDragged,
	baseClasses = "ui-button ui-widget ui-state-default ui-corner-all",
	stateClasses = "ui-state-hover ui-state-active ",
	typeClasses = "ui-button-icons-only ui-button-icon-only ui-button-text-icons ui-button-text-icon-primary ui-button-text-icon-secondary ui-button-text-only",
	formResetHandler = function() {
		var form = $( this );
		setTimeout(function() {
			form.find( ":ui-button" ).button( "refresh" );
		}, 1 );
	},
	radioGroup = function( radio ) {
		var name = radio.name,
			form = radio.form,
			radios = $( [] );
		if ( name ) {
			name = name.replace( /'/g, "\\'" );
			if ( form ) {
				radios = $( form ).find( "[name='" + name + "']" );
			} else {
				radios = $( "[name='" + name + "']", radio.ownerDocument )
					.filter(function() {
						return !this.form;
					});
			}
		}
		return radios;
	};

$.widget( "ui.button", {
	version: "1.10.3",
	defaultElement: "<button>",
	options: {
		disabled: null,
		text: true,
		label: null,
		icons: {
			primary: null,
			secondary: null
		}
	},
	_create: function() {
		this.element.closest( "form" )
			.unbind( "reset" + this.eventNamespace )
			.bind( "reset" + this.eventNamespace, formResetHandler );

		if ( typeof this.options.disabled !== "boolean" ) {
			this.options.disabled = !!this.element.prop( "disabled" );
		} else {
			this.element.prop( "disabled", this.options.disabled );
		}

		this._determineButtonType();
		this.hasTitle = !!this.buttonElement.attr( "title" );

		var that = this,
			options = this.options,
			toggleButton = this.type === "checkbox" || this.type === "radio",
			activeClass = !toggleButton ? "ui-state-active" : "",
			focusClass = "ui-state-focus";

		if ( options.label === null ) {
			options.label = (this.type === "input" ? this.buttonElement.val() : this.buttonElement.html());
		}

		this._hoverable( this.buttonElement );

		this.buttonElement
			.addClass( baseClasses )
			.attr( "role", "button" )
			.bind( "mouseenter" + this.eventNamespace, function() {
				if ( options.disabled ) {
					return;
				}
				if ( this === lastActive ) {
					$( this ).addClass( "ui-state-active" );
				}
			})
			.bind( "mouseleave" + this.eventNamespace, function() {
				if ( options.disabled ) {
					return;
				}
				$( this ).removeClass( activeClass );
			})
			.bind( "click" + this.eventNamespace, function( event ) {
				if ( options.disabled ) {
					event.preventDefault();
					event.stopImmediatePropagation();
				}
			});

		this.element
			.bind( "focus" + this.eventNamespace, function() {
				// no need to check disabled, focus won't be triggered anyway
				that.buttonElement.addClass( focusClass );
			})
			.bind( "blur" + this.eventNamespace, function() {
				that.buttonElement.removeClass( focusClass );
			});

		if ( toggleButton ) {
			this.element.bind( "change" + this.eventNamespace, function() {
				if ( clickDragged ) {
					return;
				}
				that.refresh();
			});
			// if mouse moves between mousedown and mouseup (drag) set clickDragged flag
			// prevents issue where button state changes but checkbox/radio checked state
			// does not in Firefox (see ticket #6970)
			this.buttonElement
				.bind( "mousedown" + this.eventNamespace, function( event ) {
					if ( options.disabled ) {
						return;
					}
					clickDragged = false;
					startXPos = event.pageX;
					startYPos = event.pageY;
				})
				.bind( "mouseup" + this.eventNamespace, function( event ) {
					if ( options.disabled ) {
						return;
					}
					if ( startXPos !== event.pageX || startYPos !== event.pageY ) {
						clickDragged = true;
					}
			});
		}

		if ( this.type === "checkbox" ) {
			this.buttonElement.bind( "click" + this.eventNamespace, function() {
				if ( options.disabled || clickDragged ) {
					return false;
				}
			});
		} else if ( this.type === "radio" ) {
			this.buttonElement.bind( "click" + this.eventNamespace, function() {
				if ( options.disabled || clickDragged ) {
					return false;
				}
				$( this ).addClass( "ui-state-active" );
				that.buttonElement.attr( "aria-pressed", "true" );

				var radio = that.element[ 0 ];
				radioGroup( radio )
					.not( radio )
					.map(function() {
						return $( this ).button( "widget" )[ 0 ];
					})
					.removeClass( "ui-state-active" )
					.attr( "aria-pressed", "false" );
			});
		} else {
			this.buttonElement
				.bind( "mousedown" + this.eventNamespace, function() {
					if ( options.disabled ) {
						return false;
					}
					$( this ).addClass( "ui-state-active" );
					lastActive = this;
					that.document.one( "mouseup", function() {
						lastActive = null;
					});
				})
				.bind( "mouseup" + this.eventNamespace, function() {
					if ( options.disabled ) {
						return false;
					}
					$( this ).removeClass( "ui-state-active" );
				})
				.bind( "keydown" + this.eventNamespace, function(event) {
					if ( options.disabled ) {
						return false;
					}
					if ( event.keyCode === $.ui.keyCode.SPACE || event.keyCode === $.ui.keyCode.ENTER ) {
						$( this ).addClass( "ui-state-active" );
					}
				})
				// see #8559, we bind to blur here in case the button element loses
				// focus between keydown and keyup, it would be left in an "active" state
				.bind( "keyup" + this.eventNamespace + " blur" + this.eventNamespace, function() {
					$( this ).removeClass( "ui-state-active" );
				});

			if ( this.buttonElement.is("a") ) {
				this.buttonElement.keyup(function(event) {
					if ( event.keyCode === $.ui.keyCode.SPACE ) {
						// TODO pass through original event correctly (just as 2nd argument doesn't work)
						$( this ).click();
					}
				});
			}
		}

		// TODO: pull out $.Widget's handling for the disabled option into
		// $.Widget.prototype._setOptionDisabled so it's easy to proxy and can
		// be overridden by individual plugins
		this._setOption( "disabled", options.disabled );
		this._resetButton();
	},

	_determineButtonType: function() {
		var ancestor, labelSelector, checked;

		if ( this.element.is("[type=checkbox]") ) {
			this.type = "checkbox";
		} else if ( this.element.is("[type=radio]") ) {
			this.type = "radio";
		} else if ( this.element.is("input") ) {
			this.type = "input";
		} else {
			this.type = "button";
		}

		if ( this.type === "checkbox" || this.type === "radio" ) {
			// we don't search against the document in case the element
			// is disconnected from the DOM
			ancestor = this.element.parents().last();
			labelSelector = "label[for='" + this.element.attr("id") + "']";
			this.buttonElement = ancestor.find( labelSelector );
			if ( !this.buttonElement.length ) {
				ancestor = ancestor.length ? ancestor.siblings() : this.element.siblings();
				this.buttonElement = ancestor.filter( labelSelector );
				if ( !this.buttonElement.length ) {
					this.buttonElement = ancestor.find( labelSelector );
				}
			}
			this.element.addClass( "ui-helper-hidden-accessible" );

			checked = this.element.is( ":checked" );
			if ( checked ) {
				this.buttonElement.addClass( "ui-state-active" );
			}
			this.buttonElement.prop( "aria-pressed", checked );
		} else {
			this.buttonElement = this.element;
		}
	},

	widget: function() {
		return this.buttonElement;
	},

	_destroy: function() {
		this.element
			.removeClass( "ui-helper-hidden-accessible" );
		this.buttonElement
			.removeClass( baseClasses + " " + stateClasses + " " + typeClasses )
			.removeAttr( "role" )
			.removeAttr( "aria-pressed" )
			.html( this.buttonElement.find(".ui-button-text").html() );

		if ( !this.hasTitle ) {
			this.buttonElement.removeAttr( "title" );
		}
	},

	_setOption: function( key, value ) {
		this._super( key, value );
		if ( key === "disabled" ) {
			if ( value ) {
				this.element.prop( "disabled", true );
			} else {
				this.element.prop( "disabled", false );
			}
			return;
		}
		this._resetButton();
	},

	refresh: function() {
		//See #8237 & #8828
		var isDisabled = this.element.is( "input, button" ) ? this.element.is( ":disabled" ) : this.element.hasClass( "ui-button-disabled" );

		if ( isDisabled !== this.options.disabled ) {
			this._setOption( "disabled", isDisabled );
		}
		if ( this.type === "radio" ) {
			radioGroup( this.element[0] ).each(function() {
				if ( $( this ).is( ":checked" ) ) {
					$( this ).button( "widget" )
						.addClass( "ui-state-active" )
						.attr( "aria-pressed", "true" );
				} else {
					$( this ).button( "widget" )
						.removeClass( "ui-state-active" )
						.attr( "aria-pressed", "false" );
				}
			});
		} else if ( this.type === "checkbox" ) {
			if ( this.element.is( ":checked" ) ) {
				this.buttonElement
					.addClass( "ui-state-active" )
					.attr( "aria-pressed", "true" );
			} else {
				this.buttonElement
					.removeClass( "ui-state-active" )
					.attr( "aria-pressed", "false" );
			}
		}
	},

	_resetButton: function() {
		if ( this.type === "input" ) {
			if ( this.options.label ) {
				this.element.val( this.options.label );
			}
			return;
		}
		var buttonElement = this.buttonElement.removeClass( typeClasses ),
			buttonText = $( "<span></span>", this.document[0] )
				.addClass( "ui-button-text" )
				.html( this.options.label )
				.appendTo( buttonElement.empty() )
				.text(),
			icons = this.options.icons,
			multipleIcons = icons.primary && icons.secondary,
			buttonClasses = [];

		if ( icons.primary || icons.secondary ) {
			if ( this.options.text ) {
				buttonClasses.push( "ui-button-text-icon" + ( multipleIcons ? "s" : ( icons.primary ? "-primary" : "-secondary" ) ) );
			}

			if ( icons.primary ) {
				buttonElement.prepend( "<span class='ui-button-icon-primary ui-icon " + icons.primary + "'></span>" );
			}

			if ( icons.secondary ) {
				buttonElement.append( "<span class='ui-button-icon-secondary ui-icon " + icons.secondary + "'></span>" );
			}

			if ( !this.options.text ) {
				buttonClasses.push( multipleIcons ? "ui-button-icons-only" : "ui-button-icon-only" );

				if ( !this.hasTitle ) {
					buttonElement.attr( "title", $.trim( buttonText ) );
				}
			}
		} else {
			buttonClasses.push( "ui-button-text-only" );
		}
		buttonElement.addClass( buttonClasses.join( " " ) );
	}
});

$.widget( "ui.buttonset", {
	version: "1.10.3",
	options: {
		items: "button, input[type=button], input[type=submit], input[type=reset], input[type=checkbox], input[type=radio], a, :data(ui-button)"
	},

	_create: function() {
		this.element.addClass( "ui-buttonset" );
	},

	_init: function() {
		this.refresh();
	},

	_setOption: function( key, value ) {
		if ( key === "disabled" ) {
			this.buttons.button( "option", key, value );
		}

		this._super( key, value );
	},

	refresh: function() {
		var rtl = this.element.css( "direction" ) === "rtl";

		this.buttons = this.element.find( this.options.items )
			.filter( ":ui-button" )
				.button( "refresh" )
			.end()
			.not( ":ui-button" )
				.button()
			.end()
			.map(function() {
				return $( this ).button( "widget" )[ 0 ];
			})
				.removeClass( "ui-corner-all ui-corner-left ui-corner-right" )
				.filter( ":first" )
					.addClass( rtl ? "ui-corner-right" : "ui-corner-left" )
				.end()
				.filter( ":last" )
					.addClass( rtl ? "ui-corner-left" : "ui-corner-right" )
				.end()
			.end();
	},

	_destroy: function() {
		this.element.removeClass( "ui-buttonset" );
		this.buttons
			.map(function() {
				return $( this ).button( "widget" )[ 0 ];
			})
				.removeClass( "ui-corner-left ui-corner-right" )
			.end()
			.button( "destroy" );
	}
});

}( jQuery ) );
(function( $, undefined ) {

var sizeRelatedOptions = {
		buttons: true,
		height: true,
		maxHeight: true,
		maxWidth: true,
		minHeight: true,
		minWidth: true,
		width: true
	},
	resizableRelatedOptions = {
		maxHeight: true,
		maxWidth: true,
		minHeight: true,
		minWidth: true
	};

$.widget( "ui.dialog", {
	version: "1.10.3",
	options: {
		appendTo: "body",
		autoOpen: true,
		buttons: [],
		closeOnEscape: true,
		closeText: "close",
		dialogClass: "",
		draggable: true,
		hide: null,
		height: "auto",
		maxHeight: null,
		maxWidth: null,
		minHeight: 150,
		minWidth: 150,
		modal: false,
		position: {
			my: "center",
			at: "center",
			of: window,
			collision: "fit",
			// Ensure the titlebar is always visible
			using: function( pos ) {
				var topOffset = $( this ).css( pos ).offset().top;
				if ( topOffset < 0 ) {
					$( this ).css( "top", pos.top - topOffset );
				}
			}
		},
		resizable: true,
		show: null,
		title: null,
		width: 300,

		// callbacks
		beforeClose: null,
		close: null,
		drag: null,
		dragStart: null,
		dragStop: null,
		focus: null,
		open: null,
		resize: null,
		resizeStart: null,
		resizeStop: null
	},

	_create: function() {
		this.originalCss = {
			display: this.element[0].style.display,
			width: this.element[0].style.width,
			minHeight: this.element[0].style.minHeight,
			maxHeight: this.element[0].style.maxHeight,
			height: this.element[0].style.height
		};
		this.originalPosition = {
			parent: this.element.parent(),
			index: this.element.parent().children().index( this.element )
		};
		this.originalTitle = this.element.attr("title");
		this.options.title = this.options.title || this.originalTitle;

		this._createWrapper();

		this.element
			.show()
			.removeAttr("title")
			.addClass("ui-dialog-content ui-widget-content")
			.appendTo( this.uiDialog );

		this._createTitlebar();
		this._createButtonPane();

		if ( this.options.draggable && $.fn.draggable ) {
			this._makeDraggable();
		}
		if ( this.options.resizable && $.fn.resizable ) {
			this._makeResizable();
		}

		this._isOpen = false;
	},

	_init: function() {
		if ( this.options.autoOpen ) {
			this.open();
		}
	},

	_appendTo: function() {
		var element = this.options.appendTo;
		if ( element && (element.jquery || element.nodeType) ) {
			return $( element );
		}
		return this.document.find( element || "body" ).eq( 0 );
	},

	_destroy: function() {
		var next,
			originalPosition = this.originalPosition;

		this._destroyOverlay();

		this.element
			.removeUniqueId()
			.removeClass("ui-dialog-content ui-widget-content")
			.css( this.originalCss )
			// Without detaching first, the following becomes really slow
			.detach();

		this.uiDialog.stop( true, true ).remove();

		if ( this.originalTitle ) {
			this.element.attr( "title", this.originalTitle );
		}

		next = originalPosition.parent.children().eq( originalPosition.index );
		// Don't try to place the dialog next to itself (#8613)
		if ( next.length && next[0] !== this.element[0] ) {
			next.before( this.element );
		} else {
			originalPosition.parent.append( this.element );
		}
	},

	widget: function() {
		return this.uiDialog;
	},

	disable: $.noop,
	enable: $.noop,

	close: function( event ) {
		var that = this;

		if ( !this._isOpen || this._trigger( "beforeClose", event ) === false ) {
			return;
		}

		this._isOpen = false;
		this._destroyOverlay();

		if ( !this.opener.filter(":focusable").focus().length ) {
			// Hiding a focused element doesn't trigger blur in WebKit
			// so in case we have nothing to focus on, explicitly blur the active element
			// https://bugs.webkit.org/show_bug.cgi?id=47182
			$( this.document[0].activeElement ).blur();
		}

		this._hide( this.uiDialog, this.options.hide, function() {
			that._trigger( "close", event );
		});
	},

	isOpen: function() {
		return this._isOpen;
	},

	moveToTop: function() {
		this._moveToTop();
	},

	_moveToTop: function( event, silent ) {
		var moved = !!this.uiDialog.nextAll(":visible").insertBefore( this.uiDialog ).length;
		if ( moved && !silent ) {
			this._trigger( "focus", event );
		}
		return moved;
	},

	open: function() {
		var that = this;
		if ( this._isOpen ) {
			if ( this._moveToTop() ) {
				this._focusTabbable();
			}
			return;
		}

		this._isOpen = true;
		this.opener = $( this.document[0].activeElement );

		this._size();
		this._position();
		this._createOverlay();
		this._moveToTop( null, true );
		this._show( this.uiDialog, this.options.show, function() {
			that._focusTabbable();
			that._trigger("focus");
		});

		this._trigger("open");
	},

	_focusTabbable: function() {
		// Set focus to the first match:
		// 1. First element inside the dialog matching [autofocus]
		// 2. Tabbable element inside the content element
		// 3. Tabbable element inside the buttonpane
		// 4. The close button
		// 5. The dialog itself
		var hasFocus = this.element.find("[autofocus]");
		if ( !hasFocus.length ) {
			hasFocus = this.element.find(":tabbable");
		}
		if ( !hasFocus.length ) {
			hasFocus = this.uiDialogButtonPane.find(":tabbable");
		}
		if ( !hasFocus.length ) {
			hasFocus = this.uiDialogTitlebarClose.filter(":tabbable");
		}
		if ( !hasFocus.length ) {
			hasFocus = this.uiDialog;
		}
		hasFocus.eq( 0 ).focus();
	},

	_keepFocus: function( event ) {
		function checkFocus() {
			var activeElement = this.document[0].activeElement,
				isActive = this.uiDialog[0] === activeElement ||
					$.contains( this.uiDialog[0], activeElement );
			if ( !isActive ) {
				this._focusTabbable();
			}
		}
		event.preventDefault();
		checkFocus.call( this );
		// support: IE
		// IE <= 8 doesn't prevent moving focus even with event.preventDefault()
		// so we check again later
		this._delay( checkFocus );
	},

	_createWrapper: function() {
		this.uiDialog = $("<div>")
			.addClass( "ui-dialog ui-widget ui-widget-content ui-corner-all ui-front " +
				this.options.dialogClass )
			.hide()
			.attr({
				// Setting tabIndex makes the div focusable
				tabIndex: -1,
				role: "dialog"
			})
			.appendTo( this._appendTo() );

		this._on( this.uiDialog, {
			keydown: function( event ) {
				if ( this.options.closeOnEscape && !event.isDefaultPrevented() && event.keyCode &&
						event.keyCode === $.ui.keyCode.ESCAPE ) {
					event.preventDefault();
					this.close( event );
					return;
				}

				// prevent tabbing out of dialogs
				if ( event.keyCode !== $.ui.keyCode.TAB ) {
					return;
				}
				var tabbables = this.uiDialog.find(":tabbable"),
					first = tabbables.filter(":first"),
					last  = tabbables.filter(":last");

				if ( ( event.target === last[0] || event.target === this.uiDialog[0] ) && !event.shiftKey ) {
					first.focus( 1 );
					event.preventDefault();
				} else if ( ( event.target === first[0] || event.target === this.uiDialog[0] ) && event.shiftKey ) {
					last.focus( 1 );
					event.preventDefault();
				}
			},
			mousedown: function( event ) {
				if ( this._moveToTop( event ) ) {
					this._focusTabbable();
				}
			}
		});

		// We assume that any existing aria-describedby attribute means
		// that the dialog content is marked up properly
		// otherwise we brute force the content as the description
		if ( !this.element.find("[aria-describedby]").length ) {
			this.uiDialog.attr({
				"aria-describedby": this.element.uniqueId().attr("id")
			});
		}
	},

	_createTitlebar: function() {
		var uiDialogTitle;

		this.uiDialogTitlebar = $("<div>")
			.addClass("ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix")
			.prependTo( this.uiDialog );
		this._on( this.uiDialogTitlebar, {
			mousedown: function( event ) {
				// Don't prevent click on close button (#8838)
				// Focusing a dialog that is partially scrolled out of view
				// causes the browser to scroll it into view, preventing the click event
				if ( !$( event.target ).closest(".ui-dialog-titlebar-close") ) {
					// Dialog isn't getting focus when dragging (#8063)
					this.uiDialog.focus();
				}
			}
		});

		this.uiDialogTitlebarClose = $("<button></button>")
			.button({
				label: this.options.closeText,
				icons: {
					primary: "ui-icon-closethick"
				},
				text: false
			})
			.addClass("ui-dialog-titlebar-close")
			.appendTo( this.uiDialogTitlebar );
		this._on( this.uiDialogTitlebarClose, {
			click: function( event ) {
				event.preventDefault();
				this.close( event );
			}
		});

		uiDialogTitle = $("<span>")
			.uniqueId()
			.addClass("ui-dialog-title")
			.prependTo( this.uiDialogTitlebar );
		this._title( uiDialogTitle );

		this.uiDialog.attr({
			"aria-labelledby": uiDialogTitle.attr("id")
		});
	},

	_title: function( title ) {
		if ( !this.options.title ) {
			title.html("&#160;");
		}
		title.text( this.options.title );
	},

	_createButtonPane: function() {
		this.uiDialogButtonPane = $("<div>")
			.addClass("ui-dialog-buttonpane ui-widget-content ui-helper-clearfix");

		this.uiButtonSet = $("<div>")
			.addClass("ui-dialog-buttonset")
			.appendTo( this.uiDialogButtonPane );

		this._createButtons();
	},

	_createButtons: function() {
		var that = this,
			buttons = this.options.buttons;

		// if we already have a button pane, remove it
		this.uiDialogButtonPane.remove();
		this.uiButtonSet.empty();

		if ( $.isEmptyObject( buttons ) || ($.isArray( buttons ) && !buttons.length) ) {
			this.uiDialog.removeClass("ui-dialog-buttons");
			return;
		}

		$.each( buttons, function( name, props ) {
			var click, buttonOptions;
			props = $.isFunction( props ) ?
				{ click: props, text: name } :
				props;
			// Default to a non-submitting button
			props = $.extend( { type: "button" }, props );
			// Change the context for the click callback to be the main element
			click = props.click;
			props.click = function() {
				click.apply( that.element[0], arguments );
			};
			buttonOptions = {
				icons: props.icons,
				text: props.showText
			};
			delete props.icons;
			delete props.showText;
			$( "<button></button>", props )
				.button( buttonOptions )
				.appendTo( that.uiButtonSet );
		});
		this.uiDialog.addClass("ui-dialog-buttons");
		this.uiDialogButtonPane.appendTo( this.uiDialog );
	},

	_makeDraggable: function() {
		var that = this,
			options = this.options;

		function filteredUi( ui ) {
			return {
				position: ui.position,
				offset: ui.offset
			};
		}

		this.uiDialog.draggable({
			cancel: ".ui-dialog-content, .ui-dialog-titlebar-close",
			handle: ".ui-dialog-titlebar",
			containment: "document",
			start: function( event, ui ) {
				$( this ).addClass("ui-dialog-dragging");
				that._blockFrames();
				that._trigger( "dragStart", event, filteredUi( ui ) );
			},
			drag: function( event, ui ) {
				that._trigger( "drag", event, filteredUi( ui ) );
			},
			stop: function( event, ui ) {
				options.position = [
					ui.position.left - that.document.scrollLeft(),
					ui.position.top - that.document.scrollTop()
				];
				$( this ).removeClass("ui-dialog-dragging");
				that._unblockFrames();
				that._trigger( "dragStop", event, filteredUi( ui ) );
			}
		});
	},

	_makeResizable: function() {
		var that = this,
			options = this.options,
			handles = options.resizable,
			// .ui-resizable has position: relative defined in the stylesheet
			// but dialogs have to use absolute or fixed positioning
			position = this.uiDialog.css("position"),
			resizeHandles = typeof handles === "string" ?
				handles	:
				"n,e,s,w,se,sw,ne,nw";

		function filteredUi( ui ) {
			return {
				originalPosition: ui.originalPosition,
				originalSize: ui.originalSize,
				position: ui.position,
				size: ui.size
			};
		}

		this.uiDialog.resizable({
			cancel: ".ui-dialog-content",
			containment: "document",
			alsoResize: this.element,
			maxWidth: options.maxWidth,
			maxHeight: options.maxHeight,
			minWidth: options.minWidth,
			minHeight: this._minHeight(),
			handles: resizeHandles,
			start: function( event, ui ) {
				$( this ).addClass("ui-dialog-resizing");
				that._blockFrames();
				that._trigger( "resizeStart", event, filteredUi( ui ) );
			},
			resize: function( event, ui ) {
				that._trigger( "resize", event, filteredUi( ui ) );
			},
			stop: function( event, ui ) {
				options.height = $( this ).height();
				options.width = $( this ).width();
				$( this ).removeClass("ui-dialog-resizing");
				that._unblockFrames();
				that._trigger( "resizeStop", event, filteredUi( ui ) );
			}
		})
		.css( "position", position );
	},

	_minHeight: function() {
		var options = this.options;

		return options.height === "auto" ?
			options.minHeight :
			Math.min( options.minHeight, options.height );
	},

	_position: function() {
		// Need to show the dialog to get the actual offset in the position plugin
		var isVisible = this.uiDialog.is(":visible");
		if ( !isVisible ) {
			this.uiDialog.show();
		}
		this.uiDialog.position( this.options.position );
		if ( !isVisible ) {
			this.uiDialog.hide();
		}
	},

	_setOptions: function( options ) {
		var that = this,
			resize = false,
			resizableOptions = {};

		$.each( options, function( key, value ) {
			that._setOption( key, value );

			if ( key in sizeRelatedOptions ) {
				resize = true;
			}
			if ( key in resizableRelatedOptions ) {
				resizableOptions[ key ] = value;
			}
		});

		if ( resize ) {
			this._size();
			this._position();
		}
		if ( this.uiDialog.is(":data(ui-resizable)") ) {
			this.uiDialog.resizable( "option", resizableOptions );
		}
	},

	_setOption: function( key, value ) {
		/*jshint maxcomplexity:15*/
		var isDraggable, isResizable,
			uiDialog = this.uiDialog;

		if ( key === "dialogClass" ) {
			uiDialog
				.removeClass( this.options.dialogClass )
				.addClass( value );
		}

		if ( key === "disabled" ) {
			return;
		}

		this._super( key, value );

		if ( key === "appendTo" ) {
			this.uiDialog.appendTo( this._appendTo() );
		}

		if ( key === "buttons" ) {
			this._createButtons();
		}

		if ( key === "closeText" ) {
			this.uiDialogTitlebarClose.button({
				// Ensure that we always pass a string
				label: "" + value
			});
		}

		if ( key === "draggable" ) {
			isDraggable = uiDialog.is(":data(ui-draggable)");
			if ( isDraggable && !value ) {
				uiDialog.draggable("destroy");
			}

			if ( !isDraggable && value ) {
				this._makeDraggable();
			}
		}

		if ( key === "position" ) {
			this._position();
		}

		if ( key === "resizable" ) {
			// currently resizable, becoming non-resizable
			isResizable = uiDialog.is(":data(ui-resizable)");
			if ( isResizable && !value ) {
				uiDialog.resizable("destroy");
			}

			// currently resizable, changing handles
			if ( isResizable && typeof value === "string" ) {
				uiDialog.resizable( "option", "handles", value );
			}

			// currently non-resizable, becoming resizable
			if ( !isResizable && value !== false ) {
				this._makeResizable();
			}
		}

		if ( key === "title" ) {
			this._title( this.uiDialogTitlebar.find(".ui-dialog-title") );
		}
	},

	_size: function() {
		// If the user has resized the dialog, the .ui-dialog and .ui-dialog-content
		// divs will both have width and height set, so we need to reset them
		var nonContentHeight, minContentHeight, maxContentHeight,
			options = this.options;

		// Reset content sizing
		this.element.show().css({
			width: "auto",
			minHeight: 0,
			maxHeight: "none",
			height: 0
		});

		if ( options.minWidth > options.width ) {
			options.width = options.minWidth;
		}

		// reset wrapper sizing
		// determine the height of all the non-content elements
		nonContentHeight = this.uiDialog.css({
				height: "auto",
				width: options.width
			})
			.outerHeight();
		minContentHeight = Math.max( 0, options.minHeight - nonContentHeight );
		maxContentHeight = typeof options.maxHeight === "number" ?
			Math.max( 0, options.maxHeight - nonContentHeight ) :
			"none";

		if ( options.height === "auto" ) {
			this.element.css({
				minHeight: minContentHeight,
				maxHeight: maxContentHeight,
				height: "auto"
			});
		} else {
			this.element.height( Math.max( 0, options.height - nonContentHeight ) );
		}

		if (this.uiDialog.is(":data(ui-resizable)") ) {
			this.uiDialog.resizable( "option", "minHeight", this._minHeight() );
		}
	},

	_blockFrames: function() {
		this.iframeBlocks = this.document.find( "iframe" ).map(function() {
			var iframe = $( this );

			return $( "<div>" )
				.css({
					position: "absolute",
					width: iframe.outerWidth(),
					height: iframe.outerHeight()
				})
				.appendTo( iframe.parent() )
				.offset( iframe.offset() )[0];
		});
	},

	_unblockFrames: function() {
		if ( this.iframeBlocks ) {
			this.iframeBlocks.remove();
			delete this.iframeBlocks;
		}
	},

	_allowInteraction: function( event ) {
		if ( $( event.target ).closest(".ui-dialog").length ) {
			return true;
		}

		// TODO: Remove hack when datepicker implements
		// the .ui-front logic (#8989)
		return !!$( event.target ).closest(".ui-datepicker").length;
	},

	_createOverlay: function() {
		if ( !this.options.modal ) {
			return;
		}

		var that = this,
			widgetFullName = this.widgetFullName;
		if ( !$.ui.dialog.overlayInstances ) {
			// Prevent use of anchors and inputs.
			// We use a delay in case the overlay is created from an
			// event that we're going to be cancelling. (#2804)
			this._delay(function() {
				// Handle .dialog().dialog("close") (#4065)
				if ( $.ui.dialog.overlayInstances ) {
					this.document.bind( "focusin.dialog", function( event ) {
						if ( !that._allowInteraction( event ) ) {
							event.preventDefault();
							$(".ui-dialog:visible:last .ui-dialog-content")
								.data( widgetFullName )._focusTabbable();
						}
					});
				}
			});
		}

		this.overlay = $("<div>")
			.addClass("ui-widget-overlay ui-front")
			.appendTo( this._appendTo() );
		this._on( this.overlay, {
			mousedown: "_keepFocus"
		});
		$.ui.dialog.overlayInstances++;
	},

	_destroyOverlay: function() {
		if ( !this.options.modal ) {
			return;
		}

		if ( this.overlay ) {
			$.ui.dialog.overlayInstances--;

			if ( !$.ui.dialog.overlayInstances ) {
				this.document.unbind( "focusin.dialog" );
			}
			this.overlay.remove();
			this.overlay = null;
		}
	}
});

$.ui.dialog.overlayInstances = 0;

// DEPRECATED
if ( $.uiBackCompat !== false ) {
	// position option with array notation
	// just override with old implementation
	$.widget( "ui.dialog", $.ui.dialog, {
		_position: function() {
			var position = this.options.position,
				myAt = [],
				offset = [ 0, 0 ],
				isVisible;

			if ( position ) {
				if ( typeof position === "string" || (typeof position === "object" && "0" in position ) ) {
					myAt = position.split ? position.split(" ") : [ position[0], position[1] ];
					if ( myAt.length === 1 ) {
						myAt[1] = myAt[0];
					}

					$.each( [ "left", "top" ], function( i, offsetPosition ) {
						if ( +myAt[ i ] === myAt[ i ] ) {
							offset[ i ] = myAt[ i ];
							myAt[ i ] = offsetPosition;
						}
					});

					position = {
						my: myAt[0] + (offset[0] < 0 ? offset[0] : "+" + offset[0]) + " " +
							myAt[1] + (offset[1] < 0 ? offset[1] : "+" + offset[1]),
						at: myAt.join(" ")
					};
				}

				position = $.extend( {}, $.ui.dialog.prototype.options.position, position );
			} else {
				position = $.ui.dialog.prototype.options.position;
			}

			// need to show the dialog to get the actual offset in the position plugin
			isVisible = this.uiDialog.is(":visible");
			if ( !isVisible ) {
				this.uiDialog.show();
			}
			this.uiDialog.position( position );
			if ( !isVisible ) {
				this.uiDialog.hide();
			}
		}
	});
}

}( jQuery ) );
(function( $, undefined ) {

$.widget( "ui.menu", {
	version: "1.10.3",
	defaultElement: "<ul>",
	delay: 300,
	options: {
		icons: {
			submenu: "ui-icon-carat-1-e"
		},
		menus: "ul",
		position: {
			my: "left top",
			at: "right top"
		},
		role: "menu",

		// callbacks
		blur: null,
		focus: null,
		select: null
	},

	_create: function() {
		this.activeMenu = this.element;
		// flag used to prevent firing of the click handler
		// as the event bubbles up through nested menus
		this.mouseHandled = false;
		this.element
			.uniqueId()
			.addClass( "ui-menu ui-widget ui-widget-content ui-corner-all" )
			.toggleClass( "ui-menu-icons", !!this.element.find( ".ui-icon" ).length )
			.attr({
				role: this.options.role,
				tabIndex: 0
			})
			// need to catch all clicks on disabled menu
			// not possible through _on
			.bind( "click" + this.eventNamespace, $.proxy(function( event ) {
				if ( this.options.disabled ) {
					event.preventDefault();
				}
			}, this ));

		if ( this.options.disabled ) {
			this.element
				.addClass( "ui-state-disabled" )
				.attr( "aria-disabled", "true" );
		}

		this._on({
			// Prevent focus from sticking to links inside menu after clicking
			// them (focus should always stay on UL during navigation).
			"mousedown .ui-menu-item > a": function( event ) {
				event.preventDefault();
			},
			"click .ui-state-disabled > a": function( event ) {
				event.preventDefault();
			},
			"click .ui-menu-item:has(a)": function( event ) {
				var target = $( event.target ).closest( ".ui-menu-item" );
				if ( !this.mouseHandled && target.not( ".ui-state-disabled" ).length ) {
					this.mouseHandled = true;

					this.select( event );
					// Open submenu on click
					if ( target.has( ".ui-menu" ).length ) {
						this.expand( event );
					} else if ( !this.element.is( ":focus" ) ) {
						// Redirect focus to the menu
						this.element.trigger( "focus", [ true ] );

						// If the active item is on the top level, let it stay active.
						// Otherwise, blur the active item since it is no longer visible.
						if ( this.active && this.active.parents( ".ui-menu" ).length === 1 ) {
							clearTimeout( this.timer );
						}
					}
				}
			},
			"mouseenter .ui-menu-item": function( event ) {
				var target = $( event.currentTarget );
				// Remove ui-state-active class from siblings of the newly focused menu item
				// to avoid a jump caused by adjacent elements both having a class with a border
				target.siblings().children( ".ui-state-active" ).removeClass( "ui-state-active" );
				this.focus( event, target );
			},
			mouseleave: "collapseAll",
			"mouseleave .ui-menu": "collapseAll",
			focus: function( event, keepActiveItem ) {
				// If there's already an active item, keep it active
				// If not, activate the first item
				var item = this.active || this.element.children( ".ui-menu-item" ).eq( 0 );

				if ( !keepActiveItem ) {
					this.focus( event, item );
				}
			},
			blur: function( event ) {
				this._delay(function() {
					if ( !$.contains( this.element[0], this.document[0].activeElement ) ) {
						this.collapseAll( event );
					}
				});
			},
			keydown: "_keydown"
		});

		this.refresh();

		// Clicks outside of a menu collapse any open menus
		this._on( this.document, {
			click: function( event ) {
				if ( !$( event.target ).closest( ".ui-menu" ).length ) {
					this.collapseAll( event );
				}

				// Reset the mouseHandled flag
				this.mouseHandled = false;
			}
		});
	},

	_destroy: function() {
		// Destroy (sub)menus
		this.element
			.removeAttr( "aria-activedescendant" )
			.find( ".ui-menu" ).addBack()
				.removeClass( "ui-menu ui-widget ui-widget-content ui-corner-all ui-menu-icons" )
				.removeAttr( "role" )
				.removeAttr( "tabIndex" )
				.removeAttr( "aria-labelledby" )
				.removeAttr( "aria-expanded" )
				.removeAttr( "aria-hidden" )
				.removeAttr( "aria-disabled" )
				.removeUniqueId()
				.show();

		// Destroy menu items
		this.element.find( ".ui-menu-item" )
			.removeClass( "ui-menu-item" )
			.removeAttr( "role" )
			.removeAttr( "aria-disabled" )
			.children( "a" )
				.removeUniqueId()
				.removeClass( "ui-corner-all ui-state-hover" )
				.removeAttr( "tabIndex" )
				.removeAttr( "role" )
				.removeAttr( "aria-haspopup" )
				.children().each( function() {
					var elem = $( this );
					if ( elem.data( "ui-menu-submenu-carat" ) ) {
						elem.remove();
					}
				});

		// Destroy menu dividers
		this.element.find( ".ui-menu-divider" ).removeClass( "ui-menu-divider ui-widget-content" );
	},

	_keydown: function( event ) {
		/*jshint maxcomplexity:20*/
		var match, prev, character, skip, regex,
			preventDefault = true;

		function escape( value ) {
			return value.replace( /[\-\[\]{}()*+?.,\\\^$|#\s]/g, "\\$&" );
		}

		switch ( event.keyCode ) {
		case $.ui.keyCode.PAGE_UP:
			this.previousPage( event );
			break;
		case $.ui.keyCode.PAGE_DOWN:
			this.nextPage( event );
			break;
		case $.ui.keyCode.HOME:
			this._move( "first", "first", event );
			break;
		case $.ui.keyCode.END:
			this._move( "last", "last", event );
			break;
		case $.ui.keyCode.UP:
			this.previous( event );
			break;
		case $.ui.keyCode.DOWN:
			this.next( event );
			break;
		case $.ui.keyCode.LEFT:
			this.collapse( event );
			break;
		case $.ui.keyCode.RIGHT:
			if ( this.active && !this.active.is( ".ui-state-disabled" ) ) {
				this.expand( event );
			}
			break;
		case $.ui.keyCode.ENTER:
		case $.ui.keyCode.SPACE:
			this._activate( event );
			break;
		case $.ui.keyCode.ESCAPE:
			this.collapse( event );
			break;
		default:
			preventDefault = false;
			prev = this.previousFilter || "";
			character = String.fromCharCode( event.keyCode );
			skip = false;

			clearTimeout( this.filterTimer );

			if ( character === prev ) {
				skip = true;
			} else {
				character = prev + character;
			}

			regex = new RegExp( "^" + escape( character ), "i" );
			match = this.activeMenu.children( ".ui-menu-item" ).filter(function() {
				return regex.test( $( this ).children( "a" ).text() );
			});
			match = skip && match.index( this.active.next() ) !== -1 ?
				this.active.nextAll( ".ui-menu-item" ) :
				match;

			// If no matches on the current filter, reset to the last character pressed
			// to move down the menu to the first item that starts with that character
			if ( !match.length ) {
				character = String.fromCharCode( event.keyCode );
				regex = new RegExp( "^" + escape( character ), "i" );
				match = this.activeMenu.children( ".ui-menu-item" ).filter(function() {
					return regex.test( $( this ).children( "a" ).text() );
				});
			}

			if ( match.length ) {
				this.focus( event, match );
				if ( match.length > 1 ) {
					this.previousFilter = character;
					this.filterTimer = this._delay(function() {
						delete this.previousFilter;
					}, 1000 );
				} else {
					delete this.previousFilter;
				}
			} else {
				delete this.previousFilter;
			}
		}

		if ( preventDefault ) {
			event.preventDefault();
		}
	},

	_activate: function( event ) {
		if ( !this.active.is( ".ui-state-disabled" ) ) {
			if ( this.active.children( "a[aria-haspopup='true']" ).length ) {
				this.expand( event );
			} else {
				this.select( event );
			}
		}
	},

	refresh: function() {
		var menus,
			icon = this.options.icons.submenu,
			submenus = this.element.find( this.options.menus );

		// Initialize nested menus
		submenus.filter( ":not(.ui-menu)" )
			.addClass( "ui-menu ui-widget ui-widget-content ui-corner-all" )
			.hide()
			.attr({
				role: this.options.role,
				"aria-hidden": "true",
				"aria-expanded": "false"
			})
			.each(function() {
				var menu = $( this ),
					item = menu.prev( "a" ),
					submenuCarat = $( "<span>" )
						.addClass( "ui-menu-icon ui-icon " + icon )
						.data( "ui-menu-submenu-carat", true );

				item
					.attr( "aria-haspopup", "true" )
					.prepend( submenuCarat );
				menu.attr( "aria-labelledby", item.attr( "id" ) );
			});

		menus = submenus.add( this.element );

		// Don't refresh list items that are already adapted
		menus.children( ":not(.ui-menu-item):has(a)" )
			.addClass( "ui-menu-item" )
			.attr( "role", "presentation" )
			.children( "a" )
				.uniqueId()
				.addClass( "ui-corner-all" )
				.attr({
					tabIndex: -1,
					role: this._itemRole()
				});

		// Initialize unlinked menu-items containing spaces and/or dashes only as dividers
		menus.children( ":not(.ui-menu-item)" ).each(function() {
			var item = $( this );
			// hyphen, em dash, en dash
			if ( !/[^\-\u2014\u2013\s]/.test( item.text() ) ) {
				item.addClass( "ui-widget-content ui-menu-divider" );
			}
		});

		// Add aria-disabled attribute to any disabled menu item
		menus.children( ".ui-state-disabled" ).attr( "aria-disabled", "true" );

		// If the active item has been removed, blur the menu
		if ( this.active && !$.contains( this.element[ 0 ], this.active[ 0 ] ) ) {
			this.blur();
		}
	},

	_itemRole: function() {
		return {
			menu: "menuitem",
			listbox: "option"
		}[ this.options.role ];
	},

	_setOption: function( key, value ) {
		if ( key === "icons" ) {
			this.element.find( ".ui-menu-icon" )
				.removeClass( this.options.icons.submenu )
				.addClass( value.submenu );
		}
		this._super( key, value );
	},

	focus: function( event, item ) {
		var nested, focused;
		this.blur( event, event && event.type === "focus" );

		this._scrollIntoView( item );

		this.active = item.first();
		focused = this.active.children( "a" ).addClass( "ui-state-focus" );
		// Only update aria-activedescendant if there's a role
		// otherwise we assume focus is managed elsewhere
		if ( this.options.role ) {
			this.element.attr( "aria-activedescendant", focused.attr( "id" ) );
		}

		// Highlight active parent menu item, if any
		this.active
			.parent()
			.closest( ".ui-menu-item" )
			.children( "a:first" )
			.addClass( "ui-state-active" );

		if ( event && event.type === "keydown" ) {
			this._close();
		} else {
			this.timer = this._delay(function() {
				this._close();
			}, this.delay );
		}

		nested = item.children( ".ui-menu" );
		if ( nested.length && ( /^mouse/.test( event.type ) ) ) {
			this._startOpening(nested);
		}
		this.activeMenu = item.parent();

		this._trigger( "focus", event, { item: item } );
	},

	_scrollIntoView: function( item ) {
		var borderTop, paddingTop, offset, scroll, elementHeight, itemHeight;
		if ( this._hasScroll() ) {
			borderTop = parseFloat( $.css( this.activeMenu[0], "borderTopWidth" ) ) || 0;
			paddingTop = parseFloat( $.css( this.activeMenu[0], "paddingTop" ) ) || 0;
			offset = item.offset().top - this.activeMenu.offset().top - borderTop - paddingTop;
			scroll = this.activeMenu.scrollTop();
			elementHeight = this.activeMenu.height();
			itemHeight = item.height();

			if ( offset < 0 ) {
				this.activeMenu.scrollTop( scroll + offset );
			} else if ( offset + itemHeight > elementHeight ) {
				this.activeMenu.scrollTop( scroll + offset - elementHeight + itemHeight );
			}
		}
	},

	blur: function( event, fromFocus ) {
		if ( !fromFocus ) {
			clearTimeout( this.timer );
		}

		if ( !this.active ) {
			return;
		}

		this.active.children( "a" ).removeClass( "ui-state-focus" );
		this.active = null;

		this._trigger( "blur", event, { item: this.active } );
	},

	_startOpening: function( submenu ) {
		clearTimeout( this.timer );

		// Don't open if already open fixes a Firefox bug that caused a .5 pixel
		// shift in the submenu position when mousing over the carat icon
		if ( submenu.attr( "aria-hidden" ) !== "true" ) {
			return;
		}

		this.timer = this._delay(function() {
			this._close();
			this._open( submenu );
		}, this.delay );
	},

	_open: function( submenu ) {
		var position = $.extend({
			of: this.active
		}, this.options.position );

		clearTimeout( this.timer );
		this.element.find( ".ui-menu" ).not( submenu.parents( ".ui-menu" ) )
			.hide()
			.attr( "aria-hidden", "true" );

		submenu
			.show()
			.removeAttr( "aria-hidden" )
			.attr( "aria-expanded", "true" )
			.position( position );
	},

	collapseAll: function( event, all ) {
		clearTimeout( this.timer );
		this.timer = this._delay(function() {
			// If we were passed an event, look for the submenu that contains the event
			var currentMenu = all ? this.element :
				$( event && event.target ).closest( this.element.find( ".ui-menu" ) );

			// If we found no valid submenu ancestor, use the main menu to close all sub menus anyway
			if ( !currentMenu.length ) {
				currentMenu = this.element;
			}

			this._close( currentMenu );

			this.blur( event );
			this.activeMenu = currentMenu;
		}, this.delay );
	},

	// With no arguments, closes the currently active menu - if nothing is active
	// it closes all menus.  If passed an argument, it will search for menus BELOW
	_close: function( startMenu ) {
		if ( !startMenu ) {
			startMenu = this.active ? this.active.parent() : this.element;
		}

		startMenu
			.find( ".ui-menu" )
				.hide()
				.attr( "aria-hidden", "true" )
				.attr( "aria-expanded", "false" )
			.end()
			.find( "a.ui-state-active" )
				.removeClass( "ui-state-active" );
	},

	collapse: function( event ) {
		var newItem = this.active &&
			this.active.parent().closest( ".ui-menu-item", this.element );
		if ( newItem && newItem.length ) {
			this._close();
			this.focus( event, newItem );
		}
	},

	expand: function( event ) {
		var newItem = this.active &&
			this.active
				.children( ".ui-menu " )
				.children( ".ui-menu-item" )
				.first();

		if ( newItem && newItem.length ) {
			this._open( newItem.parent() );

			// Delay so Firefox will not hide activedescendant change in expanding submenu from AT
			this._delay(function() {
				this.focus( event, newItem );
			});
		}
	},

	next: function( event ) {
		this._move( "next", "first", event );
	},

	previous: function( event ) {
		this._move( "prev", "last", event );
	},

	isFirstItem: function() {
		return this.active && !this.active.prevAll( ".ui-menu-item" ).length;
	},

	isLastItem: function() {
		return this.active && !this.active.nextAll( ".ui-menu-item" ).length;
	},

	_move: function( direction, filter, event ) {
		var next;
		if ( this.active ) {
			if ( direction === "first" || direction === "last" ) {
				next = this.active
					[ direction === "first" ? "prevAll" : "nextAll" ]( ".ui-menu-item" )
					.eq( -1 );
			} else {
				next = this.active
					[ direction + "All" ]( ".ui-menu-item" )
					.eq( 0 );
			}
		}
		if ( !next || !next.length || !this.active ) {
			next = this.activeMenu.children( ".ui-menu-item" )[ filter ]();
		}

		this.focus( event, next );
	},

	nextPage: function( event ) {
		var item, base, height;

		if ( !this.active ) {
			this.next( event );
			return;
		}
		if ( this.isLastItem() ) {
			return;
		}
		if ( this._hasScroll() ) {
			base = this.active.offset().top;
			height = this.element.height();
			this.active.nextAll( ".ui-menu-item" ).each(function() {
				item = $( this );
				return item.offset().top - base - height < 0;
			});

			this.focus( event, item );
		} else {
			this.focus( event, this.activeMenu.children( ".ui-menu-item" )
				[ !this.active ? "first" : "last" ]() );
		}
	},

	previousPage: function( event ) {
		var item, base, height;
		if ( !this.active ) {
			this.next( event );
			return;
		}
		if ( this.isFirstItem() ) {
			return;
		}
		if ( this._hasScroll() ) {
			base = this.active.offset().top;
			height = this.element.height();
			this.active.prevAll( ".ui-menu-item" ).each(function() {
				item = $( this );
				return item.offset().top - base + height > 0;
			});

			this.focus( event, item );
		} else {
			this.focus( event, this.activeMenu.children( ".ui-menu-item" ).first() );
		}
	},

	_hasScroll: function() {
		return this.element.outerHeight() < this.element.prop( "scrollHeight" );
	},

	select: function( event ) {
		// TODO: It should never be possible to not have an active item at this
		// point, but the tests don't trigger mouseenter before click.
		this.active = this.active || $( event.target ).closest( ".ui-menu-item" );
		var ui = { item: this.active };
		if ( !this.active.has( ".ui-menu" ).length ) {
			this.collapseAll( event, true );
		}
		this._trigger( "select", event, ui );
	}
});

}( jQuery ));
(function( $, undefined ) {

$.widget( "ui.progressbar", {
	version: "1.10.3",
	options: {
		max: 100,
		value: 0,

		change: null,
		complete: null
	},

	min: 0,

	_create: function() {
		// Constrain initial value
		this.oldValue = this.options.value = this._constrainedValue();

		this.element
			.addClass( "ui-progressbar ui-widget ui-widget-content ui-corner-all" )
			.attr({
				// Only set static values, aria-valuenow and aria-valuemax are
				// set inside _refreshValue()
				role: "progressbar",
				"aria-valuemin": this.min
			});

		this.valueDiv = $( "<div class='ui-progressbar-value ui-widget-header ui-corner-left'></div>" )
			.appendTo( this.element );

		this._refreshValue();
	},

	_destroy: function() {
		this.element
			.removeClass( "ui-progressbar ui-widget ui-widget-content ui-corner-all" )
			.removeAttr( "role" )
			.removeAttr( "aria-valuemin" )
			.removeAttr( "aria-valuemax" )
			.removeAttr( "aria-valuenow" );

		this.valueDiv.remove();
	},

	value: function( newValue ) {
		if ( newValue === undefined ) {
			return this.options.value;
		}

		this.options.value = this._constrainedValue( newValue );
		this._refreshValue();
	},

	_constrainedValue: function( newValue ) {
		if ( newValue === undefined ) {
			newValue = this.options.value;
		}

		this.indeterminate = newValue === false;

		// sanitize value
		if ( typeof newValue !== "number" ) {
			newValue = 0;
		}

		return this.indeterminate ? false :
			Math.min( this.options.max, Math.max( this.min, newValue ) );
	},

	_setOptions: function( options ) {
		// Ensure "value" option is set after other values (like max)
		var value = options.value;
		delete options.value;

		this._super( options );

		this.options.value = this._constrainedValue( value );
		this._refreshValue();
	},

	_setOption: function( key, value ) {
		if ( key === "max" ) {
			// Don't allow a max less than min
			value = Math.max( this.min, value );
		}

		this._super( key, value );
	},

	_percentage: function() {
		return this.indeterminate ? 100 : 100 * ( this.options.value - this.min ) / ( this.options.max - this.min );
	},

	_refreshValue: function() {
		var value = this.options.value,
			percentage = this._percentage();

		this.valueDiv
			.toggle( this.indeterminate || value > this.min )
			.toggleClass( "ui-corner-right", value === this.options.max )
			.width( percentage.toFixed(0) + "%" );

		this.element.toggleClass( "ui-progressbar-indeterminate", this.indeterminate );

		if ( this.indeterminate ) {
			this.element.removeAttr( "aria-valuenow" );
			if ( !this.overlayDiv ) {
				this.overlayDiv = $( "<div class='ui-progressbar-overlay'></div>" ).appendTo( this.valueDiv );
			}
		} else {
			this.element.attr({
				"aria-valuemax": this.options.max,
				"aria-valuenow": value
			});
			if ( this.overlayDiv ) {
				this.overlayDiv.remove();
				this.overlayDiv = null;
			}
		}

		if ( this.oldValue !== value ) {
			this.oldValue = value;
			this._trigger( "change" );
		}
		if ( value === this.options.max ) {
			this._trigger( "complete" );
		}
	}
});

})( jQuery );
(function( $, undefined ) {

// number of pages in a slider
// (how many times can you page up/down to go through the whole range)
var numPages = 5;

$.widget( "ui.slider", $.ui.mouse, {
	version: "1.10.3",
	widgetEventPrefix: "slide",

	options: {
		animate: false,
		distance: 0,
		max: 100,
		min: 0,
		orientation: "horizontal",
		range: false,
		step: 1,
		value: 0,
		values: null,

		// callbacks
		change: null,
		slide: null,
		start: null,
		stop: null
	},

	_create: function() {
		this._keySliding = false;
		this._mouseSliding = false;
		this._animateOff = true;
		this._handleIndex = null;
		this._detectOrientation();
		this._mouseInit();

		this.element
			.addClass( "ui-slider" +
				" ui-slider-" + this.orientation +
				" ui-widget" +
				" ui-widget-content" +
				" ui-corner-all");

		this._refresh();
		this._setOption( "disabled", this.options.disabled );

		this._animateOff = false;
	},

	_refresh: function() {
		this._createRange();
		this._createHandles();
		this._setupEvents();
		this._refreshValue();
	},

	_createHandles: function() {
		var i, handleCount,
			options = this.options,
			existingHandles = this.element.find( ".ui-slider-handle" ).addClass( "ui-state-default ui-corner-all" ),
			handle = "<a class='ui-slider-handle ui-state-default ui-corner-all' href='#'></a>",
			handles = [];

		handleCount = ( options.values && options.values.length ) || 1;

		if ( existingHandles.length > handleCount ) {
			existingHandles.slice( handleCount ).remove();
			existingHandles = existingHandles.slice( 0, handleCount );
		}

		for ( i = existingHandles.length; i < handleCount; i++ ) {
			handles.push( handle );
		}

		this.handles = existingHandles.add( $( handles.join( "" ) ).appendTo( this.element ) );

		this.handle = this.handles.eq( 0 );

		this.handles.each(function( i ) {
			$( this ).data( "ui-slider-handle-index", i );
		});
	},

	_createRange: function() {
		var options = this.options,
			classes = "";

		if ( options.range ) {
			if ( options.range === true ) {
				if ( !options.values ) {
					options.values = [ this._valueMin(), this._valueMin() ];
				} else if ( options.values.length && options.values.length !== 2 ) {
					options.values = [ options.values[0], options.values[0] ];
				} else if ( $.isArray( options.values ) ) {
					options.values = options.values.slice(0);
				}
			}

			if ( !this.range || !this.range.length ) {
				this.range = $( "<div></div>" )
					.appendTo( this.element );

				classes = "ui-slider-range" +
				// note: this isn't the most fittingly semantic framework class for this element,
				// but worked best visually with a variety of themes
				" ui-widget-header ui-corner-all";
			} else {
				this.range.removeClass( "ui-slider-range-min ui-slider-range-max" )
					// Handle range switching from true to min/max
					.css({
						"left": "",
						"bottom": ""
					});
			}

			this.range.addClass( classes +
				( ( options.range === "min" || options.range === "max" ) ? " ui-slider-range-" + options.range : "" ) );
		} else {
			this.range = $([]);
		}
	},

	_setupEvents: function() {
		var elements = this.handles.add( this.range ).filter( "a" );
		this._off( elements );
		this._on( elements, this._handleEvents );
		this._hoverable( elements );
		this._focusable( elements );
	},

	_destroy: function() {
		this.handles.remove();
		this.range.remove();

		this.element
			.removeClass( "ui-slider" +
				" ui-slider-horizontal" +
				" ui-slider-vertical" +
				" ui-widget" +
				" ui-widget-content" +
				" ui-corner-all" );

		this._mouseDestroy();
	},

	_mouseCapture: function( event ) {
		var position, normValue, distance, closestHandle, index, allowed, offset, mouseOverHandle,
			that = this,
			o = this.options;

		if ( o.disabled ) {
			return false;
		}

		this.elementSize = {
			width: this.element.outerWidth(),
			height: this.element.outerHeight()
		};
		this.elementOffset = this.element.offset();

		position = { x: event.pageX, y: event.pageY };
		normValue = this._normValueFromMouse( position );
		distance = this._valueMax() - this._valueMin() + 1;
		this.handles.each(function( i ) {
			var thisDistance = Math.abs( normValue - that.values(i) );
			if (( distance > thisDistance ) ||
				( distance === thisDistance &&
					(i === that._lastChangedValue || that.values(i) === o.min ))) {
				distance = thisDistance;
				closestHandle = $( this );
				index = i;
			}
		});

		allowed = this._start( event, index );
		if ( allowed === false ) {
			return false;
		}
		this._mouseSliding = true;

		this._handleIndex = index;

		closestHandle
			.addClass( "ui-state-active" )
			.focus();

		offset = closestHandle.offset();
		mouseOverHandle = !$( event.target ).parents().addBack().is( ".ui-slider-handle" );
		this._clickOffset = mouseOverHandle ? { left: 0, top: 0 } : {
			left: event.pageX - offset.left - ( closestHandle.width() / 2 ),
			top: event.pageY - offset.top -
				( closestHandle.height() / 2 ) -
				( parseInt( closestHandle.css("borderTopWidth"), 10 ) || 0 ) -
				( parseInt( closestHandle.css("borderBottomWidth"), 10 ) || 0) +
				( parseInt( closestHandle.css("marginTop"), 10 ) || 0)
		};

		if ( !this.handles.hasClass( "ui-state-hover" ) ) {
			this._slide( event, index, normValue );
		}
		this._animateOff = true;
		return true;
	},

	_mouseStart: function() {
		return true;
	},

	_mouseDrag: function( event ) {
		var position = { x: event.pageX, y: event.pageY },
			normValue = this._normValueFromMouse( position );

		this._slide( event, this._handleIndex, normValue );

		return false;
	},

	_mouseStop: function( event ) {
		this.handles.removeClass( "ui-state-active" );
		this._mouseSliding = false;

		this._stop( event, this._handleIndex );
		this._change( event, this._handleIndex );

		this._handleIndex = null;
		this._clickOffset = null;
		this._animateOff = false;

		return false;
	},

	_detectOrientation: function() {
		this.orientation = ( this.options.orientation === "vertical" ) ? "vertical" : "horizontal";
	},

	_normValueFromMouse: function( position ) {
		var pixelTotal,
			pixelMouse,
			percentMouse,
			valueTotal,
			valueMouse;

		if ( this.orientation === "horizontal" ) {
			pixelTotal = this.elementSize.width;
			pixelMouse = position.x - this.elementOffset.left - ( this._clickOffset ? this._clickOffset.left : 0 );
		} else {
			pixelTotal = this.elementSize.height;
			pixelMouse = position.y - this.elementOffset.top - ( this._clickOffset ? this._clickOffset.top : 0 );
		}

		percentMouse = ( pixelMouse / pixelTotal );
		if ( percentMouse > 1 ) {
			percentMouse = 1;
		}
		if ( percentMouse < 0 ) {
			percentMouse = 0;
		}
		if ( this.orientation === "vertical" ) {
			percentMouse = 1 - percentMouse;
		}

		valueTotal = this._valueMax() - this._valueMin();
		valueMouse = this._valueMin() + percentMouse * valueTotal;

		return this._trimAlignValue( valueMouse );
	},

	_start: function( event, index ) {
		var uiHash = {
			handle: this.handles[ index ],
			value: this.value()
		};
		if ( this.options.values && this.options.values.length ) {
			uiHash.value = this.values( index );
			uiHash.values = this.values();
		}
		return this._trigger( "start", event, uiHash );
	},

	_slide: function( event, index, newVal ) {
		var otherVal,
			newValues,
			allowed;

		if ( this.options.values && this.options.values.length ) {
			otherVal = this.values( index ? 0 : 1 );

			if ( ( this.options.values.length === 2 && this.options.range === true ) &&
					( ( index === 0 && newVal > otherVal) || ( index === 1 && newVal < otherVal ) )
				) {
				newVal = otherVal;
			}

			if ( newVal !== this.values( index ) ) {
				newValues = this.values();
				newValues[ index ] = newVal;
				// A slide can be canceled by returning false from the slide callback
				allowed = this._trigger( "slide", event, {
					handle: this.handles[ index ],
					value: newVal,
					values: newValues
				} );
				otherVal = this.values( index ? 0 : 1 );
				if ( allowed !== false ) {
					this.values( index, newVal, true );
				}
			}
		} else {
			if ( newVal !== this.value() ) {
				// A slide can be canceled by returning false from the slide callback
				allowed = this._trigger( "slide", event, {
					handle: this.handles[ index ],
					value: newVal
				} );
				if ( allowed !== false ) {
					this.value( newVal );
				}
			}
		}
	},

	_stop: function( event, index ) {
		var uiHash = {
			handle: this.handles[ index ],
			value: this.value()
		};
		if ( this.options.values && this.options.values.length ) {
			uiHash.value = this.values( index );
			uiHash.values = this.values();
		}

		this._trigger( "stop", event, uiHash );
	},

	_change: function( event, index ) {
		if ( !this._keySliding && !this._mouseSliding ) {
			var uiHash = {
				handle: this.handles[ index ],
				value: this.value()
			};
			if ( this.options.values && this.options.values.length ) {
				uiHash.value = this.values( index );
				uiHash.values = this.values();
			}

			//store the last changed value index for reference when handles overlap
			this._lastChangedValue = index;

			this._trigger( "change", event, uiHash );
		}
	},

	value: function( newValue ) {
		if ( arguments.length ) {
			this.options.value = this._trimAlignValue( newValue );
			this._refreshValue();
			this._change( null, 0 );
			return;
		}

		return this._value();
	},

	values: function( index, newValue ) {
		var vals,
			newValues,
			i;

		if ( arguments.length > 1 ) {
			this.options.values[ index ] = this._trimAlignValue( newValue );
			this._refreshValue();
			this._change( null, index );
			return;
		}

		if ( arguments.length ) {
			if ( $.isArray( arguments[ 0 ] ) ) {
				vals = this.options.values;
				newValues = arguments[ 0 ];
				for ( i = 0; i < vals.length; i += 1 ) {
					vals[ i ] = this._trimAlignValue( newValues[ i ] );
					this._change( null, i );
				}
				this._refreshValue();
			} else {
				if ( this.options.values && this.options.values.length ) {
					return this._values( index );
				} else {
					return this.value();
				}
			}
		} else {
			return this._values();
		}
	},

	_setOption: function( key, value ) {
		var i,
			valsLength = 0;

		if ( key === "range" && this.options.range === true ) {
			if ( value === "min" ) {
				this.options.value = this._values( 0 );
				this.options.values = null;
			} else if ( value === "max" ) {
				this.options.value = this._values( this.options.values.length-1 );
				this.options.values = null;
			}
		}

		if ( $.isArray( this.options.values ) ) {
			valsLength = this.options.values.length;
		}

		$.Widget.prototype._setOption.apply( this, arguments );

		switch ( key ) {
			case "orientation":
				this._detectOrientation();
				this.element
					.removeClass( "ui-slider-horizontal ui-slider-vertical" )
					.addClass( "ui-slider-" + this.orientation );
				this._refreshValue();
				break;
			case "value":
				this._animateOff = true;
				this._refreshValue();
				this._change( null, 0 );
				this._animateOff = false;
				break;
			case "values":
				this._animateOff = true;
				this._refreshValue();
				for ( i = 0; i < valsLength; i += 1 ) {
					this._change( null, i );
				}
				this._animateOff = false;
				break;
			case "min":
			case "max":
				this._animateOff = true;
				this._refreshValue();
				this._animateOff = false;
				break;
			case "range":
				this._animateOff = true;
				this._refresh();
				this._animateOff = false;
				break;
		}
	},

	//internal value getter
	// _value() returns value trimmed by min and max, aligned by step
	_value: function() {
		var val = this.options.value;
		val = this._trimAlignValue( val );

		return val;
	},

	//internal values getter
	// _values() returns array of values trimmed by min and max, aligned by step
	// _values( index ) returns single value trimmed by min and max, aligned by step
	_values: function( index ) {
		var val,
			vals,
			i;

		if ( arguments.length ) {
			val = this.options.values[ index ];
			val = this._trimAlignValue( val );

			return val;
		} else if ( this.options.values && this.options.values.length ) {
			// .slice() creates a copy of the array
			// this copy gets trimmed by min and max and then returned
			vals = this.options.values.slice();
			for ( i = 0; i < vals.length; i+= 1) {
				vals[ i ] = this._trimAlignValue( vals[ i ] );
			}

			return vals;
		} else {
			return [];
		}
	},

	// returns the step-aligned value that val is closest to, between (inclusive) min and max
	_trimAlignValue: function( val ) {
		if ( val <= this._valueMin() ) {
			return this._valueMin();
		}
		if ( val >= this._valueMax() ) {
			return this._valueMax();
		}
		var step = ( this.options.step > 0 ) ? this.options.step : 1,
			valModStep = (val - this._valueMin()) % step,
			alignValue = val - valModStep;

		if ( Math.abs(valModStep) * 2 >= step ) {
			alignValue += ( valModStep > 0 ) ? step : ( -step );
		}

		// Since JavaScript has problems with large floats, round
		// the final value to 5 digits after the decimal point (see #4124)
		return parseFloat( alignValue.toFixed(5) );
	},

	_valueMin: function() {
		return this.options.min;
	},

	_valueMax: function() {
		return this.options.max;
	},

	_refreshValue: function() {
		var lastValPercent, valPercent, value, valueMin, valueMax,
			oRange = this.options.range,
			o = this.options,
			that = this,
			animate = ( !this._animateOff ) ? o.animate : false,
			_set = {};

		if ( this.options.values && this.options.values.length ) {
			this.handles.each(function( i ) {
				valPercent = ( that.values(i) - that._valueMin() ) / ( that._valueMax() - that._valueMin() ) * 100;
				_set[ that.orientation === "horizontal" ? "left" : "bottom" ] = valPercent + "%";
				$( this ).stop( 1, 1 )[ animate ? "animate" : "css" ]( _set, o.animate );
				if ( that.options.range === true ) {
					if ( that.orientation === "horizontal" ) {
						if ( i === 0 ) {
							that.range.stop( 1, 1 )[ animate ? "animate" : "css" ]( { left: valPercent + "%" }, o.animate );
						}
						if ( i === 1 ) {
							that.range[ animate ? "animate" : "css" ]( { width: ( valPercent - lastValPercent ) + "%" }, { queue: false, duration: o.animate } );
						}
					} else {
						if ( i === 0 ) {
							that.range.stop( 1, 1 )[ animate ? "animate" : "css" ]( { bottom: ( valPercent ) + "%" }, o.animate );
						}
						if ( i === 1 ) {
							that.range[ animate ? "animate" : "css" ]( { height: ( valPercent - lastValPercent ) + "%" }, { queue: false, duration: o.animate } );
						}
					}
				}
				lastValPercent = valPercent;
			});
		} else {
			value = this.value();
			valueMin = this._valueMin();
			valueMax = this._valueMax();
			valPercent = ( valueMax !== valueMin ) ?
					( value - valueMin ) / ( valueMax - valueMin ) * 100 :
					0;
			_set[ this.orientation === "horizontal" ? "left" : "bottom" ] = valPercent + "%";
			this.handle.stop( 1, 1 )[ animate ? "animate" : "css" ]( _set, o.animate );

			if ( oRange === "min" && this.orientation === "horizontal" ) {
				this.range.stop( 1, 1 )[ animate ? "animate" : "css" ]( { width: valPercent + "%" }, o.animate );
			}
			if ( oRange === "max" && this.orientation === "horizontal" ) {
				this.range[ animate ? "animate" : "css" ]( { width: ( 100 - valPercent ) + "%" }, { queue: false, duration: o.animate } );
			}
			if ( oRange === "min" && this.orientation === "vertical" ) {
				this.range.stop( 1, 1 )[ animate ? "animate" : "css" ]( { height: valPercent + "%" }, o.animate );
			}
			if ( oRange === "max" && this.orientation === "vertical" ) {
				this.range[ animate ? "animate" : "css" ]( { height: ( 100 - valPercent ) + "%" }, { queue: false, duration: o.animate } );
			}
		}
	},

	_handleEvents: {
		keydown: function( event ) {
			/*jshint maxcomplexity:25*/
			var allowed, curVal, newVal, step,
				index = $( event.target ).data( "ui-slider-handle-index" );

			switch ( event.keyCode ) {
				case $.ui.keyCode.HOME:
				case $.ui.keyCode.END:
				case $.ui.keyCode.PAGE_UP:
				case $.ui.keyCode.PAGE_DOWN:
				case $.ui.keyCode.UP:
				case $.ui.keyCode.RIGHT:
				case $.ui.keyCode.DOWN:
				case $.ui.keyCode.LEFT:
					event.preventDefault();
					if ( !this._keySliding ) {
						this._keySliding = true;
						$( event.target ).addClass( "ui-state-active" );
						allowed = this._start( event, index );
						if ( allowed === false ) {
							return;
						}
					}
					break;
			}

			step = this.options.step;
			if ( this.options.values && this.options.values.length ) {
				curVal = newVal = this.values( index );
			} else {
				curVal = newVal = this.value();
			}

			switch ( event.keyCode ) {
				case $.ui.keyCode.HOME:
					newVal = this._valueMin();
					break;
				case $.ui.keyCode.END:
					newVal = this._valueMax();
					break;
				case $.ui.keyCode.PAGE_UP:
					newVal = this._trimAlignValue( curVal + ( (this._valueMax() - this._valueMin()) / numPages ) );
					break;
				case $.ui.keyCode.PAGE_DOWN:
					newVal = this._trimAlignValue( curVal - ( (this._valueMax() - this._valueMin()) / numPages ) );
					break;
				case $.ui.keyCode.UP:
				case $.ui.keyCode.RIGHT:
					if ( curVal === this._valueMax() ) {
						return;
					}
					newVal = this._trimAlignValue( curVal + step );
					break;
				case $.ui.keyCode.DOWN:
				case $.ui.keyCode.LEFT:
					if ( curVal === this._valueMin() ) {
						return;
					}
					newVal = this._trimAlignValue( curVal - step );
					break;
			}

			this._slide( event, index, newVal );
		},
		click: function( event ) {
			event.preventDefault();
		},
		keyup: function( event ) {
			var index = $( event.target ).data( "ui-slider-handle-index" );

			if ( this._keySliding ) {
				this._keySliding = false;
				this._stop( event, index );
				this._change( event, index );
				$( event.target ).removeClass( "ui-state-active" );
			}
		}
	}

});

}(jQuery));
(function( $ ) {

function modifier( fn ) {
	return function() {
		var previous = this.element.val();
		fn.apply( this, arguments );
		this._refresh();
		if ( previous !== this.element.val() ) {
			this._trigger( "change" );
		}
	};
}

$.widget( "ui.spinner", {
	version: "1.10.3",
	defaultElement: "<input>",
	widgetEventPrefix: "spin",
	options: {
		culture: null,
		icons: {
			down: "ui-icon-triangle-1-s",
			up: "ui-icon-triangle-1-n"
		},
		incremental: true,
		max: null,
		min: null,
		numberFormat: null,
		page: 10,
		step: 1,

		change: null,
		spin: null,
		start: null,
		stop: null
	},

	_create: function() {
		// handle string values that need to be parsed
		this._setOption( "max", this.options.max );
		this._setOption( "min", this.options.min );
		this._setOption( "step", this.options.step );

		// format the value, but don't constrain
		this._value( this.element.val(), true );

		this._draw();
		this._on( this._events );
		this._refresh();

		// turning off autocomplete prevents the browser from remembering the
		// value when navigating through history, so we re-enable autocomplete
		// if the page is unloaded before the widget is destroyed. #7790
		this._on( this.window, {
			beforeunload: function() {
				this.element.removeAttr( "autocomplete" );
			}
		});
	},

	_getCreateOptions: function() {
		var options = {},
			element = this.element;

		$.each( [ "min", "max", "step" ], function( i, option ) {
			var value = element.attr( option );
			if ( value !== undefined && value.length ) {
				options[ option ] = value;
			}
		});

		return options;
	},

	_events: {
		keydown: function( event ) {
			if ( this._start( event ) && this._keydown( event ) ) {
				event.preventDefault();
			}
		},
		keyup: "_stop",
		focus: function() {
			this.previous = this.element.val();
		},
		blur: function( event ) {
			if ( this.cancelBlur ) {
				delete this.cancelBlur;
				return;
			}

			this._stop();
			this._refresh();
			if ( this.previous !== this.element.val() ) {
				this._trigger( "change", event );
			}
		},
		mousewheel: function( event, delta ) {
			if ( !delta ) {
				return;
			}
			if ( !this.spinning && !this._start( event ) ) {
				return false;
			}

			this._spin( (delta > 0 ? 1 : -1) * this.options.step, event );
			clearTimeout( this.mousewheelTimer );
			this.mousewheelTimer = this._delay(function() {
				if ( this.spinning ) {
					this._stop( event );
				}
			}, 100 );
			event.preventDefault();
		},
		"mousedown .ui-spinner-button": function( event ) {
			var previous;

			// We never want the buttons to have focus; whenever the user is
			// interacting with the spinner, the focus should be on the input.
			// If the input is focused then this.previous is properly set from
			// when the input first received focus. If the input is not focused
			// then we need to set this.previous based on the value before spinning.
			previous = this.element[0] === this.document[0].activeElement ?
				this.previous : this.element.val();
			function checkFocus() {
				var isActive = this.element[0] === this.document[0].activeElement;
				if ( !isActive ) {
					this.element.focus();
					this.previous = previous;
					// support: IE
					// IE sets focus asynchronously, so we need to check if focus
					// moved off of the input because the user clicked on the button.
					this._delay(function() {
						this.previous = previous;
					});
				}
			}

			// ensure focus is on (or stays on) the text field
			event.preventDefault();
			checkFocus.call( this );

			// support: IE
			// IE doesn't prevent moving focus even with event.preventDefault()
			// so we set a flag to know when we should ignore the blur event
			// and check (again) if focus moved off of the input.
			this.cancelBlur = true;
			this._delay(function() {
				delete this.cancelBlur;
				checkFocus.call( this );
			});

			if ( this._start( event ) === false ) {
				return;
			}

			this._repeat( null, $( event.currentTarget ).hasClass( "ui-spinner-up" ) ? 1 : -1, event );
		},
		"mouseup .ui-spinner-button": "_stop",
		"mouseenter .ui-spinner-button": function( event ) {
			// button will add ui-state-active if mouse was down while mouseleave and kept down
			if ( !$( event.currentTarget ).hasClass( "ui-state-active" ) ) {
				return;
			}

			if ( this._start( event ) === false ) {
				return false;
			}
			this._repeat( null, $( event.currentTarget ).hasClass( "ui-spinner-up" ) ? 1 : -1, event );
		},
		// TODO: do we really want to consider this a stop?
		// shouldn't we just stop the repeater and wait until mouseup before
		// we trigger the stop event?
		"mouseleave .ui-spinner-button": "_stop"
	},

	_draw: function() {
		var uiSpinner = this.uiSpinner = this.element
			.addClass( "ui-spinner-input" )
			.attr( "autocomplete", "off" )
			.wrap( this._uiSpinnerHtml() )
			.parent()
				// add buttons
				.append( this._buttonHtml() );

		this.element.attr( "role", "spinbutton" );

		// button bindings
		this.buttons = uiSpinner.find( ".ui-spinner-button" )
			.attr( "tabIndex", -1 )
			.button()
			.removeClass( "ui-corner-all" );

		// IE 6 doesn't understand height: 50% for the buttons
		// unless the wrapper has an explicit height
		if ( this.buttons.height() > Math.ceil( uiSpinner.height() * 0.5 ) &&
				uiSpinner.height() > 0 ) {
			uiSpinner.height( uiSpinner.height() );
		}

		// disable spinner if element was already disabled
		if ( this.options.disabled ) {
			this.disable();
		}
	},

	_keydown: function( event ) {
		var options = this.options,
			keyCode = $.ui.keyCode;

		switch ( event.keyCode ) {
		case keyCode.UP:
			this._repeat( null, 1, event );
			return true;
		case keyCode.DOWN:
			this._repeat( null, -1, event );
			return true;
		case keyCode.PAGE_UP:
			this._repeat( null, options.page, event );
			return true;
		case keyCode.PAGE_DOWN:
			this._repeat( null, -options.page, event );
			return true;
		}

		return false;
	},

	_uiSpinnerHtml: function() {
		return "<span class='ui-spinner ui-widget ui-widget-content ui-corner-all'></span>";
	},

	_buttonHtml: function() {
		return "" +
			"<a class='ui-spinner-button ui-spinner-up ui-corner-tr'>" +
				"<span class='ui-icon " + this.options.icons.up + "'>&#9650;</span>" +
			"</a>" +
			"<a class='ui-spinner-button ui-spinner-down ui-corner-br'>" +
				"<span class='ui-icon " + this.options.icons.down + "'>&#9660;</span>" +
			"</a>";
	},

	_start: function( event ) {
		if ( !this.spinning && this._trigger( "start", event ) === false ) {
			return false;
		}

		if ( !this.counter ) {
			this.counter = 1;
		}
		this.spinning = true;
		return true;
	},

	_repeat: function( i, steps, event ) {
		i = i || 500;

		clearTimeout( this.timer );
		this.timer = this._delay(function() {
			this._repeat( 40, steps, event );
		}, i );

		this._spin( steps * this.options.step, event );
	},

	_spin: function( step, event ) {
		var value = this.value() || 0;

		if ( !this.counter ) {
			this.counter = 1;
		}

		value = this._adjustValue( value + step * this._increment( this.counter ) );

		if ( !this.spinning || this._trigger( "spin", event, { value: value } ) !== false) {
			this._value( value );
			this.counter++;
		}
	},

	_increment: function( i ) {
		var incremental = this.options.incremental;

		if ( incremental ) {
			return $.isFunction( incremental ) ?
				incremental( i ) :
				Math.floor( i*i*i/50000 - i*i/500 + 17*i/200 + 1 );
		}

		return 1;
	},

	_precision: function() {
		var precision = this._precisionOf( this.options.step );
		if ( this.options.min !== null ) {
			precision = Math.max( precision, this._precisionOf( this.options.min ) );
		}
		return precision;
	},

	_precisionOf: function( num ) {
		var str = num.toString(),
			decimal = str.indexOf( "." );
		return decimal === -1 ? 0 : str.length - decimal - 1;
	},

	_adjustValue: function( value ) {
		var base, aboveMin,
			options = this.options;

		// make sure we're at a valid step
		// - find out where we are relative to the base (min or 0)
		base = options.min !== null ? options.min : 0;
		aboveMin = value - base;
		// - round to the nearest step
		aboveMin = Math.round(aboveMin / options.step) * options.step;
		// - rounding is based on 0, so adjust back to our base
		value = base + aboveMin;

		// fix precision from bad JS floating point math
		value = parseFloat( value.toFixed( this._precision() ) );

		// clamp the value
		if ( options.max !== null && value > options.max) {
			return options.max;
		}
		if ( options.min !== null && value < options.min ) {
			return options.min;
		}

		return value;
	},

	_stop: function( event ) {
		if ( !this.spinning ) {
			return;
		}

		clearTimeout( this.timer );
		clearTimeout( this.mousewheelTimer );
		this.counter = 0;
		this.spinning = false;
		this._trigger( "stop", event );
	},

	_setOption: function( key, value ) {
		if ( key === "culture" || key === "numberFormat" ) {
			var prevValue = this._parse( this.element.val() );
			this.options[ key ] = value;
			this.element.val( this._format( prevValue ) );
			return;
		}

		if ( key === "max" || key === "min" || key === "step" ) {
			if ( typeof value === "string" ) {
				value = this._parse( value );
			}
		}
		if ( key === "icons" ) {
			this.buttons.first().find( ".ui-icon" )
				.removeClass( this.options.icons.up )
				.addClass( value.up );
			this.buttons.last().find( ".ui-icon" )
				.removeClass( this.options.icons.down )
				.addClass( value.down );
		}

		this._super( key, value );

		if ( key === "disabled" ) {
			if ( value ) {
				this.element.prop( "disabled", true );
				this.buttons.button( "disable" );
			} else {
				this.element.prop( "disabled", false );
				this.buttons.button( "enable" );
			}
		}
	},

	_setOptions: modifier(function( options ) {
		this._super( options );
		this._value( this.element.val() );
	}),

	_parse: function( val ) {
		if ( typeof val === "string" && val !== "" ) {
			val = window.Globalize && this.options.numberFormat ?
				Globalize.parseFloat( val, 10, this.options.culture ) : +val;
		}
		return val === "" || isNaN( val ) ? null : val;
	},

	_format: function( value ) {
		if ( value === "" ) {
			return "";
		}
		return window.Globalize && this.options.numberFormat ?
			Globalize.format( value, this.options.numberFormat, this.options.culture ) :
			value;
	},

	_refresh: function() {
		this.element.attr({
			"aria-valuemin": this.options.min,
			"aria-valuemax": this.options.max,
			// TODO: what should we do with values that can't be parsed?
			"aria-valuenow": this._parse( this.element.val() )
		});
	},

	// update the value without triggering change
	_value: function( value, allowAny ) {
		var parsed;
		if ( value !== "" ) {
			parsed = this._parse( value );
			if ( parsed !== null ) {
				if ( !allowAny ) {
					parsed = this._adjustValue( parsed );
				}
				value = this._format( parsed );
			}
		}
		this.element.val( value );
		this._refresh();
	},

	_destroy: function() {
		this.element
			.removeClass( "ui-spinner-input" )
			.prop( "disabled", false )
			.removeAttr( "autocomplete" )
			.removeAttr( "role" )
			.removeAttr( "aria-valuemin" )
			.removeAttr( "aria-valuemax" )
			.removeAttr( "aria-valuenow" );
		this.uiSpinner.replaceWith( this.element );
	},

	stepUp: modifier(function( steps ) {
		this._stepUp( steps );
	}),
	_stepUp: function( steps ) {
		if ( this._start() ) {
			this._spin( (steps || 1) * this.options.step );
			this._stop();
		}
	},

	stepDown: modifier(function( steps ) {
		this._stepDown( steps );
	}),
	_stepDown: function( steps ) {
		if ( this._start() ) {
			this._spin( (steps || 1) * -this.options.step );
			this._stop();
		}
	},

	pageUp: modifier(function( pages ) {
		this._stepUp( (pages || 1) * this.options.page );
	}),

	pageDown: modifier(function( pages ) {
		this._stepDown( (pages || 1) * this.options.page );
	}),

	value: function( newVal ) {
		if ( !arguments.length ) {
			return this._parse( this.element.val() );
		}
		modifier( this._value ).call( this, newVal );
	},

	widget: function() {
		return this.uiSpinner;
	}
});

}( jQuery ) );
(function( $, undefined ) {

var tabId = 0,
	rhash = /#.*$/;

function getNextTabId() {
	return ++tabId;
}

function isLocal( anchor ) {
	return anchor.hash.length > 1 &&
		decodeURIComponent( anchor.href.replace( rhash, "" ) ) ===
			decodeURIComponent( location.href.replace( rhash, "" ) );
}

$.widget( "ui.tabs", {
	version: "1.10.3",
	delay: 300,
	options: {
		active: null,
		collapsible: false,
		event: "click",
		heightStyle: "content",
		hide: null,
		show: null,

		// callbacks
		activate: null,
		beforeActivate: null,
		beforeLoad: null,
		load: null
	},

	_create: function() {
		var that = this,
			options = this.options;

		this.running = false;

		this.element
			.addClass( "ui-tabs ui-widget ui-widget-content ui-corner-all" )
			.toggleClass( "ui-tabs-collapsible", options.collapsible )
			// Prevent users from focusing disabled tabs via click
			.delegate( ".ui-tabs-nav > li", "mousedown" + this.eventNamespace, function( event ) {
				if ( $( this ).is( ".ui-state-disabled" ) ) {
					event.preventDefault();
				}
			})
			// support: IE <9
			// Preventing the default action in mousedown doesn't prevent IE
			// from focusing the element, so if the anchor gets focused, blur.
			// We don't have to worry about focusing the previously focused
			// element since clicking on a non-focusable element should focus
			// the body anyway.
			.delegate( ".ui-tabs-anchor", "focus" + this.eventNamespace, function() {
				if ( $( this ).closest( "li" ).is( ".ui-state-disabled" ) ) {
					this.blur();
				}
			});

		this._processTabs();
		options.active = this._initialActive();

		// Take disabling tabs via class attribute from HTML
		// into account and update option properly.
		if ( $.isArray( options.disabled ) ) {
			options.disabled = $.unique( options.disabled.concat(
				$.map( this.tabs.filter( ".ui-state-disabled" ), function( li ) {
					return that.tabs.index( li );
				})
			) ).sort();
		}

		// check for length avoids error when initializing empty list
		if ( this.options.active !== false && this.anchors.length ) {
			this.active = this._findActive( options.active );
		} else {
			this.active = $();
		}

		this._refresh();

		if ( this.active.length ) {
			this.load( options.active );
		}
	},

	_initialActive: function() {
		var active = this.options.active,
			collapsible = this.options.collapsible,
			locationHash = location.hash.substring( 1 );

		if ( active === null ) {
			// check the fragment identifier in the URL
			if ( locationHash ) {
				this.tabs.each(function( i, tab ) {
					if ( $( tab ).attr( "aria-controls" ) === locationHash ) {
						active = i;
						return false;
					}
				});
			}

			// check for a tab marked active via a class
			if ( active === null ) {
				active = this.tabs.index( this.tabs.filter( ".ui-tabs-active" ) );
			}

			// no active tab, set to false
			if ( active === null || active === -1 ) {
				active = this.tabs.length ? 0 : false;
			}
		}

		// handle numbers: negative, out of range
		if ( active !== false ) {
			active = this.tabs.index( this.tabs.eq( active ) );
			if ( active === -1 ) {
				active = collapsible ? false : 0;
			}
		}

		// don't allow collapsible: false and active: false
		if ( !collapsible && active === false && this.anchors.length ) {
			active = 0;
		}

		return active;
	},

	_getCreateEventData: function() {
		return {
			tab: this.active,
			panel: !this.active.length ? $() : this._getPanelForTab( this.active )
		};
	},

	_tabKeydown: function( event ) {
		/*jshint maxcomplexity:15*/
		var focusedTab = $( this.document[0].activeElement ).closest( "li" ),
			selectedIndex = this.tabs.index( focusedTab ),
			goingForward = true;

		if ( this._handlePageNav( event ) ) {
			return;
		}

		switch ( event.keyCode ) {
			case $.ui.keyCode.RIGHT:
			case $.ui.keyCode.DOWN:
				selectedIndex++;
				break;
			case $.ui.keyCode.UP:
			case $.ui.keyCode.LEFT:
				goingForward = false;
				selectedIndex--;
				break;
			case $.ui.keyCode.END:
				selectedIndex = this.anchors.length - 1;
				break;
			case $.ui.keyCode.HOME:
				selectedIndex = 0;
				break;
			case $.ui.keyCode.SPACE:
				// Activate only, no collapsing
				event.preventDefault();
				clearTimeout( this.activating );
				this._activate( selectedIndex );
				return;
			case $.ui.keyCode.ENTER:
				// Toggle (cancel delayed activation, allow collapsing)
				event.preventDefault();
				clearTimeout( this.activating );
				// Determine if we should collapse or activate
				this._activate( selectedIndex === this.options.active ? false : selectedIndex );
				return;
			default:
				return;
		}

		// Focus the appropriate tab, based on which key was pressed
		event.preventDefault();
		clearTimeout( this.activating );
		selectedIndex = this._focusNextTab( selectedIndex, goingForward );

		// Navigating with control key will prevent automatic activation
		if ( !event.ctrlKey ) {
			// Update aria-selected immediately so that AT think the tab is already selected.
			// Otherwise AT may confuse the user by stating that they need to activate the tab,
			// but the tab will already be activated by the time the announcement finishes.
			focusedTab.attr( "aria-selected", "false" );
			this.tabs.eq( selectedIndex ).attr( "aria-selected", "true" );

			this.activating = this._delay(function() {
				this.option( "active", selectedIndex );
			}, this.delay );
		}
	},

	_panelKeydown: function( event ) {
		if ( this._handlePageNav( event ) ) {
			return;
		}

		// Ctrl+up moves focus to the current tab
		if ( event.ctrlKey && event.keyCode === $.ui.keyCode.UP ) {
			event.preventDefault();
			this.active.focus();
		}
	},

	// Alt+page up/down moves focus to the previous/next tab (and activates)
	_handlePageNav: function( event ) {
		if ( event.altKey && event.keyCode === $.ui.keyCode.PAGE_UP ) {
			this._activate( this._focusNextTab( this.options.active - 1, false ) );
			return true;
		}
		if ( event.altKey && event.keyCode === $.ui.keyCode.PAGE_DOWN ) {
			this._activate( this._focusNextTab( this.options.active + 1, true ) );
			return true;
		}
	},

	_findNextTab: function( index, goingForward ) {
		var lastTabIndex = this.tabs.length - 1;

		function constrain() {
			if ( index > lastTabIndex ) {
				index = 0;
			}
			if ( index < 0 ) {
				index = lastTabIndex;
			}
			return index;
		}

		while ( $.inArray( constrain(), this.options.disabled ) !== -1 ) {
			index = goingForward ? index + 1 : index - 1;
		}

		return index;
	},

	_focusNextTab: function( index, goingForward ) {
		index = this._findNextTab( index, goingForward );
		this.tabs.eq( index ).focus();
		return index;
	},

	_setOption: function( key, value ) {
		if ( key === "active" ) {
			// _activate() will handle invalid values and update this.options
			this._activate( value );
			return;
		}

		if ( key === "disabled" ) {
			// don't use the widget factory's disabled handling
			this._setupDisabled( value );
			return;
		}

		this._super( key, value);

		if ( key === "collapsible" ) {
			this.element.toggleClass( "ui-tabs-collapsible", value );
			// Setting collapsible: false while collapsed; open first panel
			if ( !value && this.options.active === false ) {
				this._activate( 0 );
			}
		}

		if ( key === "event" ) {
			this._setupEvents( value );
		}

		if ( key === "heightStyle" ) {
			this._setupHeightStyle( value );
		}
	},

	_tabId: function( tab ) {
		return tab.attr( "aria-controls" ) || "ui-tabs-" + getNextTabId();
	},

	_sanitizeSelector: function( hash ) {
		return hash ? hash.replace( /[!"$%&'()*+,.\/:;<=>?@\[\]\^`{|}~]/g, "\\$&" ) : "";
	},

	refresh: function() {
		var options = this.options,
			lis = this.tablist.children( ":has(a[href])" );

		// get disabled tabs from class attribute from HTML
		// this will get converted to a boolean if needed in _refresh()
		options.disabled = $.map( lis.filter( ".ui-state-disabled" ), function( tab ) {
			return lis.index( tab );
		});

		this._processTabs();

		// was collapsed or no tabs
		if ( options.active === false || !this.anchors.length ) {
			options.active = false;
			this.active = $();
		// was active, but active tab is gone
		} else if ( this.active.length && !$.contains( this.tablist[ 0 ], this.active[ 0 ] ) ) {
			// all remaining tabs are disabled
			if ( this.tabs.length === options.disabled.length ) {
				options.active = false;
				this.active = $();
			// activate previous tab
			} else {
				this._activate( this._findNextTab( Math.max( 0, options.active - 1 ), false ) );
			}
		// was active, active tab still exists
		} else {
			// make sure active index is correct
			options.active = this.tabs.index( this.active );
		}

		this._refresh();
	},

	_refresh: function() {
		this._setupDisabled( this.options.disabled );
		this._setupEvents( this.options.event );
		this._setupHeightStyle( this.options.heightStyle );

		this.tabs.not( this.active ).attr({
			"aria-selected": "false",
			tabIndex: -1
		});
		this.panels.not( this._getPanelForTab( this.active ) )
			.hide()
			.attr({
				"aria-expanded": "false",
				"aria-hidden": "true"
			});

		// Make sure one tab is in the tab order
		if ( !this.active.length ) {
			this.tabs.eq( 0 ).attr( "tabIndex", 0 );
		} else {
			this.active
				.addClass( "ui-tabs-active ui-state-active" )
				.attr({
					"aria-selected": "true",
					tabIndex: 0
				});
			this._getPanelForTab( this.active )
				.show()
				.attr({
					"aria-expanded": "true",
					"aria-hidden": "false"
				});
		}
	},

	_processTabs: function() {
		var that = this;

		this.tablist = this._getList()
			.addClass( "ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all" )
			.attr( "role", "tablist" );

		this.tabs = this.tablist.find( "> li:has(a[href])" )
			.addClass( "ui-state-default ui-corner-top" )
			.attr({
				role: "tab",
				tabIndex: -1
			});

		this.anchors = this.tabs.map(function() {
				return $( "a", this )[ 0 ];
			})
			.addClass( "ui-tabs-anchor" )
			.attr({
				role: "presentation",
				tabIndex: -1
			});

		this.panels = $();

		this.anchors.each(function( i, anchor ) {
			var selector, panel, panelId,
				anchorId = $( anchor ).uniqueId().attr( "id" ),
				tab = $( anchor ).closest( "li" ),
				originalAriaControls = tab.attr( "aria-controls" );

			// inline tab
			if ( isLocal( anchor ) ) {
				selector = anchor.hash;
				panel = that.element.find( that._sanitizeSelector( selector ) );
			// remote tab
			} else {
				panelId = that._tabId( tab );
				selector = "#" + panelId;
				panel = that.element.find( selector );
				if ( !panel.length ) {
					panel = that._createPanel( panelId );
					panel.insertAfter( that.panels[ i - 1 ] || that.tablist );
				}
				panel.attr( "aria-live", "polite" );
			}

			if ( panel.length) {
				that.panels = that.panels.add( panel );
			}
			if ( originalAriaControls ) {
				tab.data( "ui-tabs-aria-controls", originalAriaControls );
			}
			tab.attr({
				"aria-controls": selector.substring( 1 ),
				"aria-labelledby": anchorId
			});
			panel.attr( "aria-labelledby", anchorId );
		});

		this.panels
			.addClass( "ui-tabs-panel ui-widget-content ui-corner-bottom" )
			.attr( "role", "tabpanel" );
	},

	// allow overriding how to find the list for rare usage scenarios (#7715)
	_getList: function() {
		return this.element.find( "ol,ul" ).eq( 0 );
	},

	_createPanel: function( id ) {
		return $( "<div>" )
			.attr( "id", id )
			.addClass( "ui-tabs-panel ui-widget-content ui-corner-bottom" )
			.data( "ui-tabs-destroy", true );
	},

	_setupDisabled: function( disabled ) {
		if ( $.isArray( disabled ) ) {
			if ( !disabled.length ) {
				disabled = false;
			} else if ( disabled.length === this.anchors.length ) {
				disabled = true;
			}
		}

		// disable tabs
		for ( var i = 0, li; ( li = this.tabs[ i ] ); i++ ) {
			if ( disabled === true || $.inArray( i, disabled ) !== -1 ) {
				$( li )
					.addClass( "ui-state-disabled" )
					.attr( "aria-disabled", "true" );
			} else {
				$( li )
					.removeClass( "ui-state-disabled" )
					.removeAttr( "aria-disabled" );
			}
		}

		this.options.disabled = disabled;
	},

	_setupEvents: function( event ) {
		var events = {
			click: function( event ) {
				event.preventDefault();
			}
		};
		if ( event ) {
			$.each( event.split(" "), function( index, eventName ) {
				events[ eventName ] = "_eventHandler";
			});
		}

		this._off( this.anchors.add( this.tabs ).add( this.panels ) );
		this._on( this.anchors, events );
		this._on( this.tabs, { keydown: "_tabKeydown" } );
		this._on( this.panels, { keydown: "_panelKeydown" } );

		this._focusable( this.tabs );
		this._hoverable( this.tabs );
	},

	_setupHeightStyle: function( heightStyle ) {
		var maxHeight,
			parent = this.element.parent();

		if ( heightStyle === "fill" ) {
			maxHeight = parent.height();
			maxHeight -= this.element.outerHeight() - this.element.height();

			this.element.siblings( ":visible" ).each(function() {
				var elem = $( this ),
					position = elem.css( "position" );

				if ( position === "absolute" || position === "fixed" ) {
					return;
				}
				maxHeight -= elem.outerHeight( true );
			});

			this.element.children().not( this.panels ).each(function() {
				maxHeight -= $( this ).outerHeight( true );
			});

			this.panels.each(function() {
				$( this ).height( Math.max( 0, maxHeight -
					$( this ).innerHeight() + $( this ).height() ) );
			})
			.css( "overflow", "auto" );
		} else if ( heightStyle === "auto" ) {
			maxHeight = 0;
			this.panels.each(function() {
				maxHeight = Math.max( maxHeight, $( this ).height( "" ).height() );
			}).height( maxHeight );
		}
	},

	_eventHandler: function( event ) {
		var options = this.options,
			active = this.active,
			anchor = $( event.currentTarget ),
			tab = anchor.closest( "li" ),
			clickedIsActive = tab[ 0 ] === active[ 0 ],
			collapsing = clickedIsActive && options.collapsible,
			toShow = collapsing ? $() : this._getPanelForTab( tab ),
			toHide = !active.length ? $() : this._getPanelForTab( active ),
			eventData = {
				oldTab: active,
				oldPanel: toHide,
				newTab: collapsing ? $() : tab,
				newPanel: toShow
			};

		event.preventDefault();

		if ( tab.hasClass( "ui-state-disabled" ) ||
				// tab is already loading
				tab.hasClass( "ui-tabs-loading" ) ||
				// can't switch durning an animation
				this.running ||
				// click on active header, but not collapsible
				( clickedIsActive && !options.collapsible ) ||
				// allow canceling activation
				( this._trigger( "beforeActivate", event, eventData ) === false ) ) {
			return;
		}

		options.active = collapsing ? false : this.tabs.index( tab );

		this.active = clickedIsActive ? $() : tab;
		if ( this.xhr ) {
			this.xhr.abort();
		}

		if ( !toHide.length && !toShow.length ) {
			$.error( "jQuery UI Tabs: Mismatching fragment identifier." );
		}

		if ( toShow.length ) {
			this.load( this.tabs.index( tab ), event );
		}
		this._toggle( event, eventData );
	},

	// handles show/hide for selecting tabs
	_toggle: function( event, eventData ) {
		var that = this,
			toShow = eventData.newPanel,
			toHide = eventData.oldPanel;

		this.running = true;

		function complete() {
			that.running = false;
			that._trigger( "activate", event, eventData );
		}

		function show() {
			eventData.newTab.closest( "li" ).addClass( "ui-tabs-active ui-state-active" );

			if ( toShow.length && that.options.show ) {
				that._show( toShow, that.options.show, complete );
			} else {
				toShow.show();
				complete();
			}
		}

		// start out by hiding, then showing, then completing
		if ( toHide.length && this.options.hide ) {
			this._hide( toHide, this.options.hide, function() {
				eventData.oldTab.closest( "li" ).removeClass( "ui-tabs-active ui-state-active" );
				show();
			});
		} else {
			eventData.oldTab.closest( "li" ).removeClass( "ui-tabs-active ui-state-active" );
			toHide.hide();
			show();
		}

		toHide.attr({
			"aria-expanded": "false",
			"aria-hidden": "true"
		});
		eventData.oldTab.attr( "aria-selected", "false" );
		// If we're switching tabs, remove the old tab from the tab order.
		// If we're opening from collapsed state, remove the previous tab from the tab order.
		// If we're collapsing, then keep the collapsing tab in the tab order.
		if ( toShow.length && toHide.length ) {
			eventData.oldTab.attr( "tabIndex", -1 );
		} else if ( toShow.length ) {
			this.tabs.filter(function() {
				return $( this ).attr( "tabIndex" ) === 0;
			})
			.attr( "tabIndex", -1 );
		}

		toShow.attr({
			"aria-expanded": "true",
			"aria-hidden": "false"
		});
		eventData.newTab.attr({
			"aria-selected": "true",
			tabIndex: 0
		});
	},

	_activate: function( index ) {
		var anchor,
			active = this._findActive( index );

		// trying to activate the already active panel
		if ( active[ 0 ] === this.active[ 0 ] ) {
			return;
		}

		// trying to collapse, simulate a click on the current active header
		if ( !active.length ) {
			active = this.active;
		}

		anchor = active.find( ".ui-tabs-anchor" )[ 0 ];
		this._eventHandler({
			target: anchor,
			currentTarget: anchor,
			preventDefault: $.noop
		});
	},

	_findActive: function( index ) {
		return index === false ? $() : this.tabs.eq( index );
	},

	_getIndex: function( index ) {
		// meta-function to give users option to provide a href string instead of a numerical index.
		if ( typeof index === "string" ) {
			index = this.anchors.index( this.anchors.filter( "[href$='" + index + "']" ) );
		}

		return index;
	},

	_destroy: function() {
		if ( this.xhr ) {
			this.xhr.abort();
		}

		this.element.removeClass( "ui-tabs ui-widget ui-widget-content ui-corner-all ui-tabs-collapsible" );

		this.tablist
			.removeClass( "ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all" )
			.removeAttr( "role" );

		this.anchors
			.removeClass( "ui-tabs-anchor" )
			.removeAttr( "role" )
			.removeAttr( "tabIndex" )
			.removeUniqueId();

		this.tabs.add( this.panels ).each(function() {
			if ( $.data( this, "ui-tabs-destroy" ) ) {
				$( this ).remove();
			} else {
				$( this )
					.removeClass( "ui-state-default ui-state-active ui-state-disabled " +
						"ui-corner-top ui-corner-bottom ui-widget-content ui-tabs-active ui-tabs-panel" )
					.removeAttr( "tabIndex" )
					.removeAttr( "aria-live" )
					.removeAttr( "aria-busy" )
					.removeAttr( "aria-selected" )
					.removeAttr( "aria-labelledby" )
					.removeAttr( "aria-hidden" )
					.removeAttr( "aria-expanded" )
					.removeAttr( "role" );
			}
		});

		this.tabs.each(function() {
			var li = $( this ),
				prev = li.data( "ui-tabs-aria-controls" );
			if ( prev ) {
				li
					.attr( "aria-controls", prev )
					.removeData( "ui-tabs-aria-controls" );
			} else {
				li.removeAttr( "aria-controls" );
			}
		});

		this.panels.show();

		if ( this.options.heightStyle !== "content" ) {
			this.panels.css( "height", "" );
		}
	},

	enable: function( index ) {
		var disabled = this.options.disabled;
		if ( disabled === false ) {
			return;
		}

		if ( index === undefined ) {
			disabled = false;
		} else {
			index = this._getIndex( index );
			if ( $.isArray( disabled ) ) {
				disabled = $.map( disabled, function( num ) {
					return num !== index ? num : null;
				});
			} else {
				disabled = $.map( this.tabs, function( li, num ) {
					return num !== index ? num : null;
				});
			}
		}
		this._setupDisabled( disabled );
	},

	disable: function( index ) {
		var disabled = this.options.disabled;
		if ( disabled === true ) {
			return;
		}

		if ( index === undefined ) {
			disabled = true;
		} else {
			index = this._getIndex( index );
			if ( $.inArray( index, disabled ) !== -1 ) {
				return;
			}
			if ( $.isArray( disabled ) ) {
				disabled = $.merge( [ index ], disabled ).sort();
			} else {
				disabled = [ index ];
			}
		}
		this._setupDisabled( disabled );
	},

	load: function( index, event ) {
		index = this._getIndex( index );
		var that = this,
			tab = this.tabs.eq( index ),
			anchor = tab.find( ".ui-tabs-anchor" ),
			panel = this._getPanelForTab( tab ),
			eventData = {
				tab: tab,
				panel: panel
			};

		// not remote
		if ( isLocal( anchor[ 0 ] ) ) {
			return;
		}

		this.xhr = $.ajax( this._ajaxSettings( anchor, event, eventData ) );

		// support: jQuery <1.8
		// jQuery <1.8 returns false if the request is canceled in beforeSend,
		// but as of 1.8, $.ajax() always returns a jqXHR object.
		if ( this.xhr && this.xhr.statusText !== "canceled" ) {
			tab.addClass( "ui-tabs-loading" );
			panel.attr( "aria-busy", "true" );

			this.xhr
				.success(function( response ) {
					// support: jQuery <1.8
					// http://bugs.jquery.com/ticket/11778
					setTimeout(function() {
						panel.html( response );
						that._trigger( "load", event, eventData );
					}, 1 );
				})
				.complete(function( jqXHR, status ) {
					// support: jQuery <1.8
					// http://bugs.jquery.com/ticket/11778
					setTimeout(function() {
						if ( status === "abort" ) {
							that.panels.stop( false, true );
						}

						tab.removeClass( "ui-tabs-loading" );
						panel.removeAttr( "aria-busy" );

						if ( jqXHR === that.xhr ) {
							delete that.xhr;
						}
					}, 1 );
				});
		}
	},

	_ajaxSettings: function( anchor, event, eventData ) {
		var that = this;
		return {
			url: anchor.attr( "href" ),
			beforeSend: function( jqXHR, settings ) {
				return that._trigger( "beforeLoad", event,
					$.extend( { jqXHR : jqXHR, ajaxSettings: settings }, eventData ) );
			}
		};
	},

	_getPanelForTab: function( tab ) {
		var id = $( tab ).attr( "aria-controls" );
		return this.element.find( this._sanitizeSelector( "#" + id ) );
	}
});

})( jQuery );
(function( $ ) {

var increments = 0;

function addDescribedBy( elem, id ) {
	var describedby = (elem.attr( "aria-describedby" ) || "").split( /\s+/ );
	describedby.push( id );
	elem
		.data( "ui-tooltip-id", id )
		.attr( "aria-describedby", $.trim( describedby.join( " " ) ) );
}

function removeDescribedBy( elem ) {
	var id = elem.data( "ui-tooltip-id" ),
		describedby = (elem.attr( "aria-describedby" ) || "").split( /\s+/ ),
		index = $.inArray( id, describedby );
	if ( index !== -1 ) {
		describedby.splice( index, 1 );
	}

	elem.removeData( "ui-tooltip-id" );
	describedby = $.trim( describedby.join( " " ) );
	if ( describedby ) {
		elem.attr( "aria-describedby", describedby );
	} else {
		elem.removeAttr( "aria-describedby" );
	}
}

$.widget( "ui.tooltip", {
	version: "1.10.3",
	options: {
		content: function() {
			// support: IE<9, Opera in jQuery <1.7
			// .text() can't accept undefined, so coerce to a string
			var title = $( this ).attr( "title" ) || "";
			// Escape title, since we're going from an attribute to raw HTML
			return $( "<a>" ).text( title ).html();
		},
		hide: true,
		// Disabled elements have inconsistent behavior across browsers (#8661)
		items: "[title]:not([disabled])",
		position: {
			my: "left top+15",
			at: "left bottom",
			collision: "flipfit flip"
		},
		show: true,
		tooltipClass: null,
		track: false,

		// callbacks
		close: null,
		open: null
	},

	_create: function() {
		this._on({
			mouseover: "open",
			focusin: "open"
		});

		// IDs of generated tooltips, needed for destroy
		this.tooltips = {};
		// IDs of parent tooltips where we removed the title attribute
		this.parents = {};

		if ( this.options.disabled ) {
			this._disable();
		}
	},

	_setOption: function( key, value ) {
		var that = this;

		if ( key === "disabled" ) {
			this[ value ? "_disable" : "_enable" ]();
			this.options[ key ] = value;
			// disable element style changes
			return;
		}

		this._super( key, value );

		if ( key === "content" ) {
			$.each( this.tooltips, function( id, element ) {
				that._updateContent( element );
			});
		}
	},

	_disable: function() {
		var that = this;

		// close open tooltips
		$.each( this.tooltips, function( id, element ) {
			var event = $.Event( "blur" );
			event.target = event.currentTarget = element[0];
			that.close( event, true );
		});

		// remove title attributes to prevent native tooltips
		this.element.find( this.options.items ).addBack().each(function() {
			var element = $( this );
			if ( element.is( "[title]" ) ) {
				element
					.data( "ui-tooltip-title", element.attr( "title" ) )
					.attr( "title", "" );
			}
		});
	},

	_enable: function() {
		// restore title attributes
		this.element.find( this.options.items ).addBack().each(function() {
			var element = $( this );
			if ( element.data( "ui-tooltip-title" ) ) {
				element.attr( "title", element.data( "ui-tooltip-title" ) );
			}
		});
	},

	open: function( event ) {
		var that = this,
			target = $( event ? event.target : this.element )
				// we need closest here due to mouseover bubbling,
				// but always pointing at the same event target
				.closest( this.options.items );

		// No element to show a tooltip for or the tooltip is already open
		if ( !target.length || target.data( "ui-tooltip-id" ) ) {
			return;
		}

		if ( target.attr( "title" ) ) {
			target.data( "ui-tooltip-title", target.attr( "title" ) );
		}

		target.data( "ui-tooltip-open", true );

		// kill parent tooltips, custom or native, for hover
		if ( event && event.type === "mouseover" ) {
			target.parents().each(function() {
				var parent = $( this ),
					blurEvent;
				if ( parent.data( "ui-tooltip-open" ) ) {
					blurEvent = $.Event( "blur" );
					blurEvent.target = blurEvent.currentTarget = this;
					that.close( blurEvent, true );
				}
				if ( parent.attr( "title" ) ) {
					parent.uniqueId();
					that.parents[ this.id ] = {
						element: this,
						title: parent.attr( "title" )
					};
					parent.attr( "title", "" );
				}
			});
		}

		this._updateContent( target, event );
	},

	_updateContent: function( target, event ) {
		var content,
			contentOption = this.options.content,
			that = this,
			eventType = event ? event.type : null;

		if ( typeof contentOption === "string" ) {
			return this._open( event, target, contentOption );
		}

		content = contentOption.call( target[0], function( response ) {
			// ignore async response if tooltip was closed already
			if ( !target.data( "ui-tooltip-open" ) ) {
				return;
			}
			// IE may instantly serve a cached response for ajax requests
			// delay this call to _open so the other call to _open runs first
			that._delay(function() {
				// jQuery creates a special event for focusin when it doesn't
				// exist natively. To improve performance, the native event
				// object is reused and the type is changed. Therefore, we can't
				// rely on the type being correct after the event finished
				// bubbling, so we set it back to the previous value. (#8740)
				if ( event ) {
					event.type = eventType;
				}
				this._open( event, target, response );
			});
		});
		if ( content ) {
			this._open( event, target, content );
		}
	},

	_open: function( event, target, content ) {
		var tooltip, events, delayedShow,
			positionOption = $.extend( {}, this.options.position );

		if ( !content ) {
			return;
		}

		// Content can be updated multiple times. If the tooltip already
		// exists, then just update the content and bail.
		tooltip = this._find( target );
		if ( tooltip.length ) {
			tooltip.find( ".ui-tooltip-content" ).html( content );
			return;
		}

		// if we have a title, clear it to prevent the native tooltip
		// we have to check first to avoid defining a title if none exists
		// (we don't want to cause an element to start matching [title])
		//
		// We use removeAttr only for key events, to allow IE to export the correct
		// accessible attributes. For mouse events, set to empty string to avoid
		// native tooltip showing up (happens only when removing inside mouseover).
		if ( target.is( "[title]" ) ) {
			if ( event && event.type === "mouseover" ) {
				target.attr( "title", "" );
			} else {
				target.removeAttr( "title" );
			}
		}

		tooltip = this._tooltip( target );
		addDescribedBy( target, tooltip.attr( "id" ) );
		tooltip.find( ".ui-tooltip-content" ).html( content );

		function position( event ) {
			positionOption.of = event;
			if ( tooltip.is( ":hidden" ) ) {
				return;
			}
			tooltip.position( positionOption );
		}
		if ( this.options.track && event && /^mouse/.test( event.type ) ) {
			this._on( this.document, {
				mousemove: position
			});
			// trigger once to override element-relative positioning
			position( event );
		} else {
			tooltip.position( $.extend({
				of: target
			}, this.options.position ) );
		}

		tooltip.hide();

		this._show( tooltip, this.options.show );
		// Handle tracking tooltips that are shown with a delay (#8644). As soon
		// as the tooltip is visible, position the tooltip using the most recent
		// event.
		if ( this.options.show && this.options.show.delay ) {
			delayedShow = this.delayedShow = setInterval(function() {
				if ( tooltip.is( ":visible" ) ) {
					position( positionOption.of );
					clearInterval( delayedShow );
				}
			}, $.fx.interval );
		}

		this._trigger( "open", event, { tooltip: tooltip } );

		events = {
			keyup: function( event ) {
				if ( event.keyCode === $.ui.keyCode.ESCAPE ) {
					var fakeEvent = $.Event(event);
					fakeEvent.currentTarget = target[0];
					this.close( fakeEvent, true );
				}
			},
			remove: function() {
				this._removeTooltip( tooltip );
			}
		};
		if ( !event || event.type === "mouseover" ) {
			events.mouseleave = "close";
		}
		if ( !event || event.type === "focusin" ) {
			events.focusout = "close";
		}
		this._on( true, target, events );
	},

	close: function( event ) {
		var that = this,
			target = $( event ? event.currentTarget : this.element ),
			tooltip = this._find( target );

		// disabling closes the tooltip, so we need to track when we're closing
		// to avoid an infinite loop in case the tooltip becomes disabled on close
		if ( this.closing ) {
			return;
		}

		// Clear the interval for delayed tracking tooltips
		clearInterval( this.delayedShow );

		// only set title if we had one before (see comment in _open())
		if ( target.data( "ui-tooltip-title" ) ) {
			target.attr( "title", target.data( "ui-tooltip-title" ) );
		}

		removeDescribedBy( target );

		tooltip.stop( true );
		this._hide( tooltip, this.options.hide, function() {
			that._removeTooltip( $( this ) );
		});

		target.removeData( "ui-tooltip-open" );
		this._off( target, "mouseleave focusout keyup" );
		// Remove 'remove' binding only on delegated targets
		if ( target[0] !== this.element[0] ) {
			this._off( target, "remove" );
		}
		this._off( this.document, "mousemove" );

		if ( event && event.type === "mouseleave" ) {
			$.each( this.parents, function( id, parent ) {
				$( parent.element ).attr( "title", parent.title );
				delete that.parents[ id ];
			});
		}

		this.closing = true;
		this._trigger( "close", event, { tooltip: tooltip } );
		this.closing = false;
	},

	_tooltip: function( element ) {
		var id = "ui-tooltip-" + increments++,
			tooltip = $( "<div>" )
				.attr({
					id: id,
					role: "tooltip"
				})
				.addClass( "ui-tooltip ui-widget ui-corner-all ui-widget-content " +
					( this.options.tooltipClass || "" ) );
		$( "<div>" )
			.addClass( "ui-tooltip-content" )
			.appendTo( tooltip );
		tooltip.appendTo( this.document[0].body );
		this.tooltips[ id ] = element;
		return tooltip;
	},

	_find: function( target ) {
		var id = target.data( "ui-tooltip-id" );
		return id ? $( "#" + id ) : $();
	},

	_removeTooltip: function( tooltip ) {
		tooltip.remove();
		delete this.tooltips[ tooltip.attr( "id" ) ];
	},

	_destroy: function() {
		var that = this;

		// close open tooltips
		$.each( this.tooltips, function( id, element ) {
			// Delegate to close method to handle common cleanup
			var event = $.Event( "blur" );
			event.target = event.currentTarget = element[0];
			that.close( event, true );

			// Remove immediately; destroying an open tooltip doesn't use the
			// hide animation
			$( "#" + id ).remove();

			// Restore the title
			if ( element.data( "ui-tooltip-title" ) ) {
				element.attr( "title", element.data( "ui-tooltip-title" ) );
				element.removeData( "ui-tooltip-title" );
			}
		});
	}
});

}( jQuery ) );
})(ChemDoodle.lib.jQuery);
/*
 * jQuery simple-color plugin
 * @requires jQuery v1.4.2 or later
 *
 * See http://recursive-design.com/projects/jquery-simple-color/
 *
 * Licensed under the MIT license:
 *   http://www.opensource.org/licenses/mit-license.php
 *
 * Version: 1.2.0 (201310121400)
 */
 (function($) {
/**
 * simpleColor() provides a mechanism for displaying simple color-choosers.
 *
 * If an options Object is provided, the following attributes are supported:
 *
 *  defaultColor:       Default (initially selected) color.
 *                      Default value: '#FFF'
 *
 *  cellWidth:          Width of each individual color cell.
 *                      Default value: 10
 *
 *  cellHeight:         Height of each individual color cell.
 *                      Default value: 10
 *
 *  cellMargin:         Margin of each individual color cell.
 *                      Default value: 1
 *
 *  boxWidth:           Width of the color display box.
 *                      Default value: 115px
 *
 *  boxHeight:          Height of the color display box.
 *                      Default value: 20px
 *
 *  columns:            Number of columns to display. Color order may look strange if this is altered.
 *                      Default value: 16
 *
 *  insert:             The position to insert the color chooser. 'before' or 'after'.
 *                      Default value: 'after'
 *
 *  colors:             An array of colors to display, if you want to customize the default color set.
 *                      Default value: default color set - see 'defaultColors' below.
 *
 *  displayColorCode:   Display the color code (eg #333333) as text inside the button. true or false.
 *                      Default value: false
 *
 *  colorCodeAlign:     Text alignment used to display the color code inside the button. Only used if
 *                      'displayColorCode' is true. 'left', 'center' or 'right'
 *                      Default value: 'center'
 *
 *  colorCodeColor:     Text color of the color code inside the button. Only used if 'displayColorCode'
 *                      is true.
 *                      Default value: '#FFF'
 *
 *  onSelect:           Callback function to call after a color has been chosen. The callback
 *                      function will be passed two arguments - the hex code of the selected color,
 *                      and the input element that triggered the chooser.
 *                      Default value: null
 *                      Returns:       hex value
 *
 *  onCellEnter:        Callback function that excecutes when the mouse enters a cell. The callback
 *                      function will be passed two arguments - the hex code of the current color,
 *                      and the input element that triggered the chooser.
 *                      Default value: null
 *                      Returns:       hex value
 *
 *  onClose:            Callback function that executes when the chooser is closed. The callback
 *                      function will be passed one argument - the input element that triggered
 *                      the chooser.
 *                      Default value: null
 *
 *  livePreview:        The color display will change to show the color of the hovered color cell.
 *                      The display will revert if no color is selected.
 *                      Default value: false
 *
 *  chooserCSS:         An associative array of CSS properties that will be applied to the pop-up
 *                      color chooser.
 *                      Default value: see options.chooserCSS in the source
 *
 *  displayCSS:         An associative array of CSS properties that will be applied to the color
 *                      display box.
 *                      Default value: see options.displayCSS in the source
 */
  $.fn.simpleColor = function(options) {

    var element = this;

    var defaultColors = [
      '990033', 'ff3366', 'cc0033', 'ff0033', 'ff9999', 'cc3366', 'ffccff', 'cc6699',
      '993366', '660033', 'cc3399', 'ff99cc', 'ff66cc', 'ff99ff', 'ff6699', 'cc0066',
      'ff0066', 'ff3399', 'ff0099', 'ff33cc', 'ff00cc', 'ff66ff', 'ff33ff', 'ff00ff',
      'cc0099', '990066', 'cc66cc', 'cc33cc', 'cc99ff', 'cc66ff', 'cc33ff', '993399',
      'cc00cc', 'cc00ff', '9900cc', '990099', 'cc99cc', '996699', '663366', '660099',
      '9933cc', '660066', '9900ff', '9933ff', '9966cc', '330033', '663399', '6633cc',
      '6600cc', '9966ff', '330066', '6600ff', '6633ff', 'ccccff', '9999ff', '9999cc',
      '6666cc', '6666ff', '666699', '333366', '333399', '330099', '3300cc', '3300ff',
      '3333ff', '3333cc', '0066ff', '0033ff', '3366ff', '3366cc', '000066', '000033',
      '0000ff', '000099', '0033cc', '0000cc', '336699', '0066cc', '99ccff', '6699ff',
      '003366', '6699cc', '006699', '3399cc', '0099cc', '66ccff', '3399ff', '003399',
      '0099ff', '33ccff', '00ccff', '99ffff', '66ffff', '33ffff', '00ffff', '00cccc',
      '009999', '669999', '99cccc', 'ccffff', '33cccc', '66cccc', '339999', '336666',
      '006666', '003333', '00ffcc', '33ffcc', '33cc99', '00cc99', '66ffcc', '99ffcc',
      '00ff99', '339966', '006633', '336633', '669966', '66cc66', '99ff99', '66ff66',
      '339933', '99cc99', '66ff99', '33ff99', '33cc66', '00cc66', '66cc99', '009966',
      '009933', '33ff66', '00ff66', 'ccffcc', 'ccff99', '99ff66', '99ff33', '00ff33',
      '33ff33', '00cc33', '33cc33', '66ff33', '00ff00', '66cc33', '006600', '003300',
      '009900', '33ff00', '66ff00', '99ff00', '66cc00', '00cc00', '33cc00', '339900',
      '99cc66', '669933', '99cc33', '336600', '669900', '99cc00', 'ccff66', 'ccff33',
      'ccff00', '999900', 'cccc00', 'cccc33', '333300', '666600', '999933', 'cccc66',
      '666633', '999966', 'cccc99', 'ffffcc', 'ffff99', 'ffff66', 'ffff33', 'ffff00',
      'ffcc00', 'ffcc66', 'ffcc33', 'cc9933', '996600', 'cc9900', 'ff9900', 'cc6600',
      '993300', 'cc6633', '663300', 'ff9966', 'ff6633', 'ff9933', 'ff6600', 'cc3300',
      '996633', '330000', '663333', '996666', 'cc9999', '993333', 'cc6666', 'ffcccc',
      'ff3333', 'cc3333', 'ff6666', '660000', '990000', 'cc0000', 'ff0000', 'ff3300',
      'cc9966', 'ffcc99', 'ffffff', 'cccccc', '999999', '666666', '333333', '000000',
      '000000', '000000', '000000', '000000', '000000', '000000', '000000', '000000'
    ];

    // Option defaults
    options = $.extend({
      defaultColor:     this.attr('defaultColor') || '#FFF',
      cellWidth:        this.attr('cellWidth') || 10,
      cellHeight:       this.attr('cellHeight') || 10,
      cellMargin:       this.attr('cellMargin') || 1,
      boxWidth:         this.attr('boxWidth') || '115px',
      boxHeight:        this.attr('boxHeight') || '20px',
      columns:          this.attr('columns') || 16,
      insert:           this.attr('insert') || 'after',
      colors:           this.attr('colors') || defaultColors,
      displayColorCode: this.attr('displayColorCode') || false,
      colorCodeAlign:   this.attr('colorCodeAlign') || 'center',
      colorCodeColor:   this.attr('colorCodeColor') || '#FFF',
      onSelect:         null,
      onCellEnter:      null,
      onClose:          null,
      livePreview:      false
    }, options || {});

    // Figure out the cell dimensions
    options.totalWidth = options.columns * (options.cellWidth + (2 * options.cellMargin));

    // Custom CSS for the chooser, which relies on previously defined options.
    options.chooserCSS = $.extend({
      'border':           '1px solid #000',
      'margin':           '0 0 0 5px',
      'width':            options.totalWidth,
      'height':           options.totalHeight,
      'top':              0,
      'left':             options.boxWidth,
      'position':         'absolute',
      'background-color': '#fff'
    }, options.chooserCSS || {});

    // Custom CSS for the display box, which relies on previously defined options.
    options.displayCSS = $.extend({
      'background-color': options.defaultColor,
      'border':           '1px solid #000',
      'width':            options.boxWidth,
      'height':           options.boxHeight,
      'line-height':      options.boxHeight + 'px',
      'cursor':           'pointer'
    }, options.displayCSS || {});

    // Hide the input
    this.hide();

    // this should probably do feature detection - I don't know why we need +2 for IE
    // but this works for jQuery 1.9.1
    if (navigator.userAgent.indexOf("MSIE")!=-1){
      options.totalWidth += 2;
    }

    options.totalHeight = Math.ceil(options.colors.length / options.columns) * (options.cellHeight + (2 * options.cellMargin));

    // Store these options so they'll be available to the other functions
    // TODO - must be a better way to do this, not sure what the 'official'
    // jQuery method is. Ideally i want to pass these as a parameter to the
    // each() function but i'm not sure how
    $.simpleColorOptions = options;

    function buildChooser(index) {
      options = $.simpleColorOptions;

      // Create a container to hold everything
      var container = $("<div class='simpleColorContainer' />");

      // Absolutely positioned child elements now 'work'.
			container.css('position', 'relative');

      // Create the color display box
      var defaultColor = (this.value && this.value != '') ? this.value : options.defaultColor;

      var displayBox = $("<div class='simpleColorDisplay' />");
      displayBox.css($.extend(options.displayCSS, { 'background-color': defaultColor }));
      displayBox.data('color', defaultColor);
      container.append(displayBox);

      // If 'displayColorCode' is turned on, display the currently selected color code as text inside the button.
      if (options.displayColorCode) {
        displayBox.data('displayColorCode', true);
        displayBox.text(this.value);
        displayBox.css({
          'color':     options.colorCodeColor,
          'textAlign': options.colorCodeAlign
        });
      }

      var selectCallback = function (event) {
        // Bind and namespace the click listener only when the chooser is
        // displayed. Unbind when the chooser is closed.
        $('html').bind("click.simpleColorDisplay", function(e) {
          $('html').unbind("click.simpleColorDisplay");
          $('.simpleColorChooser').hide();

          // If the user has not selected a new color, then revert the display.
          // Makes sure the selected cell is within the current color chooser.
          var target = $(e.target);
          if (target.is('.simpleColorCell') === false || $.contains( $(event.target).closest('.simpleColorContainer')[0], target[0]) === false) {
            displayBox.css('background-color', displayBox.data('color'));
            if (options.displayColorCode) {
              displayBox.text(displayBox.data('color'));
            }
          }
          // Execute onClose callback whenever the color chooser is closed.
          if (options.onClose) {
            options.onClose(element);
          }
        });

        // Use an existing chooser if there is one
        if (event.data.container.chooser) {
          event.data.container.chooser.toggle();

        // Build the chooser.
        } else {
          // Make a chooser div to hold the cells
          var chooser = $("<div class='simpleColorChooser'/>");
          chooser.css(options.chooserCSS);

          event.data.container.chooser = chooser;
          event.data.container.append(chooser);

          // Create the cells
          for (var i=0; i<options.colors.length; i++) {
            var cell = $("<div class='simpleColorCell' id='" + options.colors[i] + "'/>");
            cell.css({
              'width':            options.cellWidth + 'px',
              'height':           options.cellHeight + 'px',
              'margin':           options.cellMargin + 'px',
              'cursor':           'pointer',
              'lineHeight':       options.cellHeight + 'px',
              'fontSize':         '1px',
              'float':            'left',
              'background-color': '#'+options.colors[i]
            });
            chooser.append(cell);
            if (options.onCellEnter || options.livePreview) {
              cell.bind('mouseenter', function(event) {
                if (options.onCellEnter) {
                  options.onCellEnter(this.id, element);
                }
                if (options.livePreview) {
                  displayBox.css('background-color', '#' + this.id);
                  if (options.displayColorCode) {
                    displayBox.text('#' + this.id);
                  }
                }
              });
            }
            cell.bind('click', {
              input: event.data.input,
              chooser: chooser,
              displayBox: displayBox
            },
            function(event) {
              var color = '#' + this.id;
              event.data.input.value = color;
              $(event.data.input).change();
              $(event.data.displayBox).data('color', color);
              event.data.displayBox.css('background-color', color);
              event.data.chooser.hide();

              // If 'displayColorCode' is turned on, display the currently selected color code as text inside the button.
              if (options.displayColorCode) {
                event.data.displayBox.text(color);
              }

              // If an onSelect callback function is defined then excecute it.
              if (options.onSelect) {
                options.onSelect(color, element);
              }
            });
          }
        }
      };

      // Also bind the display box button to display the chooser.
      var callbackParams = {
        input:      this,
        container:  container,
        displayBox: displayBox
      };
      displayBox.bind('click', callbackParams, selectCallback);

      $(this).after(container);
      $(this).data('container', container);
    };

    this.each(buildChooser);

    $('.simpleColorDisplay').each(function() {
      $(this).click(function(e){
        e.stopPropagation();
      });
    });

    return this;
  };

  /*
   * Close the given color choosers.
   */
  $.fn.closeChooser = function() {
    this.each( function(index) {
      $(this).data('container').find('.simpleColorChooser').hide();
    });

    return this;
  };

  /*
   * Set the color of the given color choosers.
   */
  $.fn.setColor = function(color) {
    this.each( function(index) {
      var displayBox = $(this).data('container').find('.simpleColorDisplay');
      displayBox.css('background-color', color).data('color', color);
      if (displayBox.data('displayColorCode') === true) {
        displayBox.text(color);
      }
    });

    return this;
  };

})(ChemDoodle.lib.jQuery);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//
ChemDoodle.uis = (function() {
	'use strict';
	var p = {};

	p.actions = {};
	p.gui = {};
	p.gui.desktop = {};
	p.gui.mobile = {};
	p.states = {};
	p.tools = {};

	return p;

})();
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(actions) {
	'use strict';
	actions._Action = function() {
	};
	var _ = actions._Action.prototype;
	_.forward = function(sketcher) {
		this.innerForward();
		this.checks(sketcher);
	};
	_.reverse = function(sketcher) {
		this.innerReverse();
		this.checks(sketcher);
	};
	_.checks = function(sketcher) {
		for ( var i = 0, ii = sketcher.molecules.length; i < ii; i++) {
			sketcher.molecules[i].check();
		}
		if (sketcher.lasso && sketcher.lasso.isActive()) {
			sketcher.lasso.setBounds();
		}
		sketcher.repaint();
	};

})(ChemDoodle.uis.actions);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(informatics, structures, actions) {
	'use strict';
	actions.AddAction = function(sketcher, a, as, bs) {
		this.sketcher = sketcher;
		this.a = a;
		this.as = as;
		this.bs = bs;
	};
	var _ = actions.AddAction.prototype = new actions._Action();
	_.innerForward = function() {
		var mol = this.sketcher.getMoleculeByAtom(this.a);
		if (!mol) {
			mol = new structures.Molecule();
			this.sketcher.molecules.push(mol);
		}
		if (this.as) {
			for ( var i = 0, ii = this.as.length; i < ii; i++) {
				mol.atoms.push(this.as[i]);
			}
		}
		if (this.bs) {
			var merging = [];
			for ( var i = 0, ii = this.bs.length; i < ii; i++) {
				var b = this.bs[i];
				if (mol.atoms.indexOf(b.a1) === -1) {
					var otherMol = this.sketcher.getMoleculeByAtom(b.a1);
					if (merging.indexOf(otherMol) === -1) {
						merging.push(otherMol);
					}
				}
				if (mol.atoms.indexOf(b.a2) === -1) {
					var otherMol = this.sketcher.getMoleculeByAtom(b.a2);
					if (merging.indexOf(otherMol) === -1) {
						merging.push(otherMol);
					}
				}
				mol.bonds.push(b);
			}
			for ( var i = 0, ii = merging.length; i < ii; i++) {
				var molRemoving = merging[i];
				this.sketcher.removeMolecule(molRemoving);
				mol.atoms = mol.atoms.concat(molRemoving.atoms);
				mol.bonds = mol.bonds.concat(molRemoving.bonds);
			}
		}
	};
	_.innerReverse = function() {
		var mol = this.sketcher.getMoleculeByAtom(this.a);
		if (this.as) {
			var aKeep = [];
			for ( var i = 0, ii = mol.atoms.length; i < ii; i++) {
				if (this.as.indexOf(mol.atoms[i]) === -1) {
					aKeep.push(mol.atoms[i]);
				}
			}
			mol.atoms = aKeep;
		}
		if (this.bs) {
			var bKeep = [];
			for ( var i = 0, ii = mol.bonds.length; i < ii; i++) {
				if (this.bs.indexOf(mol.bonds[i]) === -1) {
					bKeep.push(mol.bonds[i]);
				}
			}
			mol.bonds = bKeep;
		}
		if (mol.atoms.length === 0) {
			// remove molecule if it is empty
			this.sketcher.removeMolecule(mol);
		} else {
			var split = new informatics.Splitter().split(mol);
			if (split.length > 1) {
				this.sketcher.removeMolecule(mol);
				for ( var i = 0, ii = split.length; i < ii; i++) {
					this.sketcher.molecules.push(split[i]);
				}
			}
		}
	};

})(ChemDoodle.informatics, ChemDoodle.structures, ChemDoodle.uis.actions);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(actions) {
	'use strict';
	actions.AddShapeAction = function(sketcher, s) {
		this.sketcher = sketcher;
		this.s = s;
	};
	var _ = actions.AddShapeAction.prototype = new actions._Action();
	_.innerForward = function() {
		this.sketcher.shapes.push(this.s);
	};
	_.innerReverse = function() {
		this.sketcher.removeShape(this.s);
	};

})(ChemDoodle.uis.actions);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//
(function(actions, Bond, m) {
	'use strict';
	actions.ChangeBondAction = function(b, orderAfter, stereoAfter) {
		this.b = b;
		this.orderBefore = b.bondOrder;
		this.stereoBefore = b.stereo;
		if (orderAfter) {
			this.orderAfter = orderAfter;
			this.stereoAfter = stereoAfter;
		} else {
			// make sure to floor so half bond types increment correctly
			this.orderAfter = m.floor(b.bondOrder + 1);
			if (this.orderAfter > 3) {
				this.orderAfter = 1;
			}
			this.stereoAfter = Bond.STEREO_NONE;
		}
	};
	var _ = actions.ChangeBondAction.prototype = new actions._Action();
	_.innerForward = function() {
		this.b.bondOrder = this.orderAfter;
		this.b.stereo = this.stereoAfter;
	};
	_.innerReverse = function() {
		this.b.bondOrder = this.orderBefore;
		this.b.stereo = this.stereoBefore;
	};

})(ChemDoodle.uis.actions, ChemDoodle.structures.Bond, Math);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//
(function(actions, m) {
	'use strict';
	actions.ChangeBracketAttributeAction = function(s, type) {
		this.s = s;
		this.type = type;
	};
	var _ = actions.ChangeBracketAttributeAction.prototype = new actions._Action();
	_.innerForward = function() {
		var c = this.type > 0 ? 1 : -1;
		switch (m.abs(this.type)) {
		case 1:
			this.s.charge += c;
			break;
		case 2:
			this.s.repeat += c;
			break;
		case 3:
			this.s.mult += c;
			break;
		}
	};
	_.innerReverse = function() {
		var c = this.type > 0 ? -1 : 1;
		switch (m.abs(this.type)) {
		case 1:
			this.s.charge += c;
			break;
		case 2:
			this.s.repeat += c;
			break;
		case 3:
			this.s.mult += c;
			break;
		}
	};

})(ChemDoodle.uis.actions, Math);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//
(function(actions) {
	'use strict';
	actions.ChangeChargeAction = function(a, delta) {
		this.a = a;
		this.delta = delta;
	};
	var _ = actions.ChangeChargeAction.prototype = new actions._Action();
	_.innerForward = function() {
		this.a.charge += this.delta;
	};
	_.innerReverse = function() {
		this.a.charge -= this.delta;
	};

})(ChemDoodle.uis.actions);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//
(function(actions) {
	'use strict';
	actions.ChangeCoordinatesAction = function(as, newCoords) {
		this.as = as;
		this.recs = [];
		for ( var i = 0, ii = this.as.length; i < ii; i++) {
			this.recs[i] = {
				'xo' : this.as[i].x,
				'yo' : this.as[i].y,
				'xn' : newCoords[i].x,
				'yn' : newCoords[i].y
			};
		}
	};
	var _ = actions.ChangeCoordinatesAction.prototype = new actions._Action();
	_.innerForward = function() {
		for ( var i = 0, ii = this.as.length; i < ii; i++) {
			this.as[i].x = this.recs[i].xn;
			this.as[i].y = this.recs[i].yn;
		}
	};
	_.innerReverse = function() {
		for ( var i = 0, ii = this.as.length; i < ii; i++) {
			this.as[i].x = this.recs[i].xo;
			this.as[i].y = this.recs[i].yo;
		}
	};

})(ChemDoodle.uis.actions);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//
(function(actions) {
	'use strict';
	actions.ChangeLabelAction = function(a, after) {
		this.a = a;
		this.before = a.label;
		this.after = after;
	};
	var _ = actions.ChangeLabelAction.prototype = new actions._Action();
	_.innerForward = function() {
		this.a.label = this.after;
	};
	_.innerReverse = function() {
		this.a.label = this.before;
	};

})(ChemDoodle.uis.actions);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//
(function(actions) {
	'use strict';
	actions.ChangeLonePairAction = function(a, delta) {
		this.a = a;
		this.delta = delta;
	};
	var _ = actions.ChangeLonePairAction.prototype = new actions._Action();
	_.innerForward = function() {
		this.a.numLonePair += this.delta;
	};
	_.innerReverse = function() {
		this.a.numLonePair -= this.delta;
	};

})(ChemDoodle.uis.actions);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//
(function(actions) {
	'use strict';
	actions.ChangeQueryAction = function(o, after) {
		this.o = o;
		this.before = o.query;
		this.after = after;
	};
	var _ = actions.ChangeQueryAction.prototype = new actions._Action();
	_.innerForward = function() {
		this.o.query = this.after;
	};
	_.innerReverse = function() {
		this.o.query = this.before;
	};

})(ChemDoodle.uis.actions);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//
(function(actions) {
	'use strict';
	actions.ChangeRadicalAction = function(a, delta) {
		this.a = a;
		this.delta = delta;
	};
	var _ = actions.ChangeRadicalAction.prototype = new actions._Action();
	_.innerForward = function() {
		this.a.numRadical += this.delta;
	};
	_.innerReverse = function() {
		this.a.numRadical -= this.delta;
	};

})(ChemDoodle.uis.actions);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//
(function(actions) {
	'use strict';
	actions.ChangeRgroupAction = function(a, rafter) {
		this.a = a;
		this.rbefore = a.rgroup;
		this.rafter = rafter;
	};
	var _ = actions.ChangeRgroupAction.prototype = new actions._Action();
	_.innerForward = function() {
		this.a.rgroup = this.rafter;
	};
	_.innerReverse = function() {
		this.a.rgroup = this.rbefore;
	};

})(ChemDoodle.uis.actions);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//
(function(structures, actions) {
	'use strict';
	actions.ClearAction = function(sketcher) {
		this.sketcher = sketcher;
		this.beforeMols = this.sketcher.molecules;
		this.beforeShapes = this.sketcher.shapes;
		this.sketcher.clear();
		if (this.sketcher.oneMolecule && !this.sketcher.setupScene) {
			this.afterMol = new structures.Molecule();
			this.afterMol.atoms.push(new structures.Atom());
			this.sketcher.molecules.push(this.afterMol);
			this.sketcher.center();
			this.sketcher.repaint();
		}
	};
	var _ = actions.ClearAction.prototype = new actions._Action();
	_.innerForward = function() {
		this.sketcher.molecules = [];
		this.sketcher.shapes = [];
		if (this.sketcher.oneMolecule && !this.sketcher.setupScene) {
			this.sketcher.molecules.push(this.afterMol);
		}
	};
	_.innerReverse = function() {
		this.sketcher.molecules = this.beforeMols;
		this.sketcher.shapes = this.beforeShapes;
	};

})(ChemDoodle.structures, ChemDoodle.uis.actions);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//
(function(actions) {
	'use strict';
	actions.DeleteAction = function(sketcher, a, as, bs) {
		this.sketcher = sketcher;
		this.a = a;
		this.as = as;
		this.bs = bs;
		this.ss = [];
	};
	var _ = actions.DeleteAction.prototype = new actions._Action();
	_.innerForwardAReverse = actions.AddAction.prototype.innerReverse;
	_.innerReverseAForward = actions.AddAction.prototype.innerForward;
	_.innerForward = function() {
		this.innerForwardAReverse();
		for ( var i = 0, ii = this.ss.length; i < ii; i++) {
			this.sketcher.removeShape(this.ss[i]);
		}
	};
	_.innerReverse = function() {
		this.innerReverseAForward();
		if (this.ss.length > 0) {
			this.sketcher.shapes = this.sketcher.shapes.concat(this.ss);
		}
	};

})(ChemDoodle.uis.actions);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(informatics, actions) {
	'use strict';
	actions.DeleteContentAction = function(sketcher, as, ss) {
		this.sketcher = sketcher;
		this.as = as;
		this.ss = ss;
		this.bs = [];
		for ( var i = 0, ii = this.sketcher.molecules.length; i < ii; i++) {
			var mol = this.sketcher.molecules[i];
			for ( var j = 0, jj = mol.bonds.length; j < jj; j++) {
				var b = mol.bonds[j];
				if (b.a1.isLassoed && b.a2.isLassoed) {
					this.bs.push(b);
				}
			}
		}
	};
	var _ = actions.DeleteContentAction.prototype = new actions._Action();
	_.innerForward = function() {
		for ( var i = 0, ii = this.ss.length; i < ii; i++) {
			this.sketcher.removeShape(this.ss[i]);
		}
		var asKeep = [];
		var bsKeep = [];
		for ( var i = 0, ii = this.sketcher.molecules.length; i < ii; i++) {
			var mol = this.sketcher.molecules[i];
			for ( var j = 0, jj = mol.atoms.length; j < jj; j++) {
				var a = mol.atoms[j];
				if (this.as.indexOf(a) === -1) {
					asKeep.push(a);
				}
			}
			for ( var j = 0, jj = mol.bonds.length; j < jj; j++) {
				var b = mol.bonds[j];
				if (this.bs.indexOf(b) === -1) {
					bsKeep.push(b);
				}
			}
		}
		this.sketcher.molecules = new informatics.Splitter().split({
			atoms : asKeep,
			bonds : bsKeep
		});
	};
	_.innerReverse = function() {
		this.sketcher.shapes = this.sketcher.shapes.concat(this.ss);
		var asKeep = [];
		var bsKeep = [];
		for ( var i = 0, ii = this.sketcher.molecules.length; i < ii; i++) {
			var mol = this.sketcher.molecules[i];
			asKeep = asKeep.concat(mol.atoms);
			bsKeep = bsKeep.concat(mol.bonds);
		}
		this.sketcher.molecules = new informatics.Splitter().split({
			atoms : asKeep.concat(this.as),
			bonds : bsKeep.concat(this.bs)
		});
	};

})(ChemDoodle.informatics, ChemDoodle.uis.actions);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//
(function(actions) {
	'use strict';
	actions.DeleteShapeAction = function(sketcher, s) {
		this.sketcher = sketcher;
		this.s = s;
	};
	var _ = actions.DeleteShapeAction.prototype = new actions._Action();
	_.innerForward = actions.AddShapeAction.prototype.innerReverse;
	_.innerReverse = actions.AddShapeAction.prototype.innerForward;

})(ChemDoodle.uis.actions);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//
(function(actions) {
	'use strict';
	actions.FlipBondAction = function(b) {
		this.b = b;
	};
	var _ = actions.FlipBondAction.prototype = new actions._Action();
	_.innerForward = function() {
		var temp = this.b.a1;
		this.b.a1 = this.b.a2;
		this.b.a2 = temp;
	};
	_.innerReverse = function() {
		this.innerForward();
	};

})(ChemDoodle.uis.actions);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//
(function(actions) {
	'use strict';
	actions.MoveAction = function(ps, dif) {
		this.ps = ps;
		this.dif = dif;
	};
	var _ = actions.MoveAction.prototype = new actions._Action();
	_.innerForward = function() {
		for ( var i = 0, ii = this.ps.length; i < ii; i++) {
			this.ps[i].add(this.dif);
		}
	};
	_.innerReverse = function() {
		for ( var i = 0, ii = this.ps.length; i < ii; i++) {
			this.ps[i].sub(this.dif);
		}
	};

})(ChemDoodle.uis.actions);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(structures, actions) {
	'use strict';
	actions.NewMoleculeAction = function(sketcher, as, bs) {
		this.sketcher = sketcher;
		this.as = as;
		this.bs = bs;
	};
	var _ = actions.NewMoleculeAction.prototype = new actions._Action();
	_.innerForward = function() {
		var mol = new structures.Molecule();
		mol.atoms = mol.atoms.concat(this.as);
		mol.bonds = mol.bonds.concat(this.bs);
		mol.check();
		this.sketcher.addMolecule(mol);
	};
	_.innerReverse = function() {
		this.sketcher.removeMolecule(this.sketcher.getMoleculeByAtom(this.as[0]));
	};

})(ChemDoodle.structures, ChemDoodle.uis.actions);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//
(function(actions, m) {
	'use strict';
	actions.RotateAction = function(ps, dif, center) {
		this.ps = ps;
		this.dif = dif;
		this.center = center;
	};
	var _ = actions.RotateAction.prototype = new actions._Action();
	_.innerForward = function() {
		for ( var i = 0, ii = this.ps.length; i < ii; i++) {
			var p = this.ps[i];
			var dist = this.center.distance(p);
			var angle = this.center.angle(p) + this.dif;
			p.x = this.center.x + dist * m.cos(angle);
			p.y = this.center.y - dist * m.sin(angle);
		}
	};
	_.innerReverse = function() {
		for ( var i = 0, ii = this.ps.length; i < ii; i++) {
			var p = this.ps[i];
			var dist = this.center.distance(p);
			var angle = this.center.angle(p) - this.dif;
			p.x = this.center.x + dist * m.cos(angle);
			p.y = this.center.y - dist * m.sin(angle);
		}
	};

})(ChemDoodle.uis.actions, Math);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(actions) {
	'use strict';
	actions.SwitchContentAction = function(sketcher, mols, shapes) {
		this.sketcher = sketcher;
		this.beforeMols = this.sketcher.molecules;
		this.beforeShapes = this.sketcher.shapes;
		this.molsA = mols;
		this.shapesA = shapes;
	};
	var _ = actions.SwitchContentAction.prototype = new actions._Action();
	_.innerForward = function() {
		this.sketcher.loadContent(this.molsA, this.shapesA);
	};
	_.innerReverse = function() {
		this.sketcher.molecules = this.beforeMols;
		this.sketcher.shapes = this.beforeShapes;
	};

})(ChemDoodle.uis.actions);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(actions) {
	'use strict';
	actions.SwitchMoleculeAction = function(sketcher, mol) {
		this.sketcher = sketcher;
		this.beforeMols = this.sketcher.molecules;
		this.beforeShapes = this.sketcher.shapes;
		this.molA = mol;
	};
	var _ = actions.SwitchMoleculeAction.prototype = new actions._Action();
	_.innerForward = function() {
		this.sketcher.loadMolecule(this.molA);
	};
	_.innerReverse = function() {
		this.sketcher.molecules = this.beforeMols;
		this.sketcher.shapes = this.beforeShapes;
	};

})(ChemDoodle.uis.actions);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//
(function(actions) {
	'use strict';
	actions.ToggleAnyAtomAction = function(a) {
		this.a = a;
	};
	var _ = actions.ToggleAnyAtomAction.prototype = new actions._Action();
	_.innerForward = function() {
		this.a.any = !this.a.any;
	};
	_.innerReverse = actions.ToggleAnyAtomAction.prototype.innerForward;

})(ChemDoodle.uis.actions);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(actions) {
	'use strict';
	actions.HistoryManager = function(sketcher) {
		this.sketcher = sketcher;
		this.undoStack = [];
		this.redoStack = [];
	};
	var _ = actions.HistoryManager.prototype;
	_.undo = function() {
		if (this.undoStack.length !== 0) {
			if (this.sketcher.lasso && this.sketcher.lasso.isActive()) {
				this.sketcher.lasso.empty();
			}
			var a = this.undoStack.pop();
			a.reverse(this.sketcher);
			this.redoStack.push(a);
			if (this.undoStack.length === 0) {
				this.sketcher.toolbarManager.buttonUndo.disable();
			}
			this.sketcher.toolbarManager.buttonRedo.enable();
		}
	};
	_.redo = function() {
		if (this.redoStack.length !== 0) {
			if (this.sketcher.lasso && this.sketcher.lasso.isActive()) {
				this.sketcher.lasso.empty();
			}
			var a = this.redoStack.pop();
			a.forward(this.sketcher);
			this.undoStack.push(a);
			this.sketcher.toolbarManager.buttonUndo.enable();
			if (this.redoStack.length === 0) {
				this.sketcher.toolbarManager.buttonRedo.disable();
			}
		}
	};
	_.pushUndo = function(a) {
		a.forward(this.sketcher);
		this.undoStack.push(a);
		if (this.redoStack.length !== 0) {
			this.redoStack = [];
		}
		this.sketcher.toolbarManager.buttonUndo.enable();
		this.sketcher.toolbarManager.buttonRedo.disable();
	};
	_.clear = function() {
		if (this.undoStack.length !== 0) {
			this.undoStack = [];
			this.sketcher.toolbarManager.buttonUndo.disable();
		}
		if (this.redoStack.length !== 0) {
			this.redoStack = [];
			this.sketcher.toolbarManager.buttonRedo.disable();
		}
	};

})(ChemDoodle.uis.actions);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(math, monitor, actions, states, structures, SYMBOLS, m) {
	'use strict';
	states._State = function() {
	};
	var _ = states._State.prototype;
	_.setup = function(sketcher) {
		this.sketcher = sketcher;
	};

	_.clearHover = function() {
		if (this.sketcher.hovering) {
			this.sketcher.hovering.isHover = false;
			this.sketcher.hovering.isSelected = false;
			this.sketcher.hovering = undefined;
		}
	};
	_.findHoveredObject = function(e, includeAtoms, includeBonds, includeShapes) {
		this.clearHover();
		var min = Infinity;
		var hovering;
		var hoverdist = this.sketcher.specs.bondLength_2D;
		if (!this.sketcher.isMobile) {
			hoverdist /= this.sketcher.specs.scale;
		}
		if (includeAtoms) {
			for ( var i = 0, ii = this.sketcher.molecules.length; i < ii; i++) {
				var mol = this.sketcher.molecules[i];
				for ( var j = 0, jj = mol.atoms.length; j < jj; j++) {
					var a = mol.atoms[j];
					a.isHover = false;
					var dist = e.p.distance(a);
					if (dist < hoverdist && dist < min) {
						min = dist;
						hovering = a;
					}
				}
			}
		}
		if (includeBonds) {
			for ( var i = 0, ii = this.sketcher.molecules.length; i < ii; i++) {
				var mol = this.sketcher.molecules[i];
				for ( var j = 0, jj = mol.bonds.length; j < jj; j++) {
					var b = mol.bonds[j];
					b.isHover = false;
					var dist = e.p.distance(b.getCenter());
					if (dist < hoverdist && dist < min) {
						min = dist;
						hovering = b;
					}
				}
			}
		}
		if (includeShapes) {
			for ( var i = 0, ii = this.sketcher.shapes.length; i < ii; i++) {
				var s = this.sketcher.shapes[i];
				s.isHover = false;
				s.hoverPoint = undefined;
				var sps = s.getPoints();
				for ( var j = 0, jj = sps.length; j < jj; j++) {
					var p = sps[j];
					var dist = e.p.distance(p);
					if (dist < hoverdist && dist < min) {
						min = dist;
						hovering = s;
						s.hoverPoint = p;
					}
				}
			}
			if (!hovering) {
				// find smallest shape pointer is over
				for ( var i = 0, ii = this.sketcher.shapes.length; i < ii; i++) {
					var s = this.sketcher.shapes[i];
					if (s.isOver(e.p, hoverdist)) {
						hovering = s;
					}
				}
			}
		}
		if (hovering) {
			hovering.isHover = true;
			this.sketcher.hovering = hovering;
		}
	};
	_.getOptimumAngle = function(a, order) {
		var mol = this.sketcher.getMoleculeByAtom(a);
		var angles = mol.getAngles(a);
		var angle = 0;
		if (angles.length === 0) {
			angle = m.PI / 6;
		} else if (angles.length === 1) {
			var b;
			for ( var j = 0, jj = mol.bonds.length; j < jj; j++) {
				if (mol.bonds[j].contains(this.sketcher.hovering)) {
					b = mol.bonds[j];
				}
			}
			if (b.bondOrder >= 3 || order>=3) {
				angle = angles[0] + m.PI;
			} else {
				var concerned = angles[0] % m.PI * 2;
				if (math.isBetween(concerned, 0, m.PI / 2) || math.isBetween(concerned, m.PI, 3 * m.PI / 2)) {
					angle = angles[0] + 2 * m.PI / 3;
				} else {
					angle = angles[0] - 2 * m.PI / 3;
				}
			}
		} else {
			// avoid inside rings
			var modded;
			for ( var j = 0, jj = mol.rings.length; j < jj; j++) {
				var r = mol.rings[j];
				if(r.atoms.indexOf(a)!==-1){
					angles.push(a.angle(r.getCenter()));
					modded = true;
				}
			}
			if(modded){
				angles.sort();
			}
			angle = math.angleBetweenLargest(angles).angle;
		}
		return angle;
	};
	_.removeStartAtom = function() {
		if (this.sketcher.startAtom) {
			this.sketcher.startAtom.x = -10;
			this.sketcher.startAtom.y = -10;
			this.sketcher.repaint();
		}
	};

	_.enter = function() {
		if (this.innerenter) {
			this.innerenter();
		}
	};
	_.exit = function() {
		if (this.innerexit) {
			this.innerexit();
		}
	};
	_.click = function(e) {
		if (this.innerclick) {
			this.innerclick(e);
		}
	};
	_.rightclick = function(e) {
		if (this.innerrightclick) {
			this.innerrightclick(e);
		}
	};
	_.dblclick = function(e) {
		if (this.innerdblclick) {
			this.innerdblclick(e);
		}
		if (!this.sketcher.hovering && this.sketcher.oneMolecule) {
			// center structure
			var dif = new structures.Point(this.sketcher.width / 2, this.sketcher.height / 2);
			var bounds = this.sketcher.getContentBounds();
			dif.x -= (bounds.maxX + bounds.minX) / 2;
			dif.y -= (bounds.maxY + bounds.minY) / 2;
			this.sketcher.historyManager.pushUndo(new actions.MoveAction(this.sketcher.getAllPoints(), dif));
		}
	};
	_.mousedown = function(e) {
		this.sketcher.lastPoint = e.p;
		// must also check for mobile hits here to the help button
		if (this.sketcher.isHelp || this.sketcher.isMobile && e.op.distance(new structures.Point(this.sketcher.width - 20, 20)) < 10) {
			this.sketcher.isHelp = false;
			this.sketcher.lastPoint = undefined;
			this.sketcher.repaint();
			window.open('http://web.chemdoodle.com/demos/sketcher');
		} else if (this.innermousedown) {
			this.innermousedown(e);
		}
	};
	_.rightmousedown = function(e) {
		if (this.innerrightmousedown) {
			this.innerrightmousedown(e);
		}
	};
	_.mousemove = function(e) {
		if (this.innermousemove) {
			this.innermousemove(e);
		}
		// call the repaint here to repaint the help button, also this is called
		// by other functions, so the repaint must be here
		this.sketcher.repaint();
	};
	_.mouseout = function(e) {
		if (this.innermouseout) {
			this.innermouseout(e);
		}
		if (this.sketcher.isHelp) {
			this.sketcher.isHelp = false;
			this.sketcher.repaint();
		}
		if (this.sketcher.hovering && monitor.CANVAS_DRAGGING != this.sketcher) {
			this.sketcher.hovering = undefined;
			this.sketcher.repaint();
		}
	};
	_.mouseover = function(e) {
		if (this.innermouseover) {
			this.innermouseover(e);
		}
	};
	_.mouseup = function(e) {
		this.parentAction = undefined;
		if (this.innermouseup) {
			this.innermouseup(e);
		}
	};
	_.rightmouseup = function(e) {
		if (this.innerrightmouseup) {
			this.innerrightmouseup(e);
		}
	};
	_.mousewheel = function(e, delta) {
		if (this.innermousewheel) {
			this.innermousewheel(e);
		}
		this.sketcher.specs.scale += delta / 50;
		this.sketcher.checkScale();
		this.sketcher.repaint();
	};
	_.drag = function(e) {
		if (this.innerdrag) {
			this.innerdrag(e);
		}
		if (!this.sketcher.hovering && this !== this.sketcher.stateManager.STATE_LASSO && this !== this.sketcher.stateManager.STATE_SHAPE && this !== this.sketcher.stateManager.STATE_PUSHER) {
			if (monitor.SHIFT) {
				// rotate structure
				if (this.parentAction) {
					var center = this.parentAction.center;
					var oldAngle = center.angle(this.sketcher.lastPoint);
					var newAngle = center.angle(e.p);
					var rot = newAngle - oldAngle;
					this.parentAction.dif += rot;
					for ( var i = 0, ii = this.parentAction.ps.length; i < ii; i++) {
						var a = this.parentAction.ps[i];
						var dist = center.distance(a);
						var angle = center.angle(a) + rot;
						a.x = center.x + dist * m.cos(angle);
						a.y = center.y - dist * m.sin(angle);
					}
					// must check here as change is outside of an action
					for ( var i = 0, ii = this.sketcher.molecules.length; i < ii; i++) {
						this.sketcher.molecules[i].check();
					}
				} else {
					var center = new structures.Point(this.sketcher.width / 2, this.sketcher.height / 2);
					var oldAngle = center.angle(this.sketcher.lastPoint);
					var newAngle = center.angle(e.p);
					var rot = newAngle - oldAngle;
					this.parentAction = new actions.RotateAction(this.sketcher.getAllPoints(), rot, center);
					this.sketcher.historyManager.pushUndo(this.parentAction);
				}
			} else {
				if (!this.sketcher.lastPoint) {
					// this prevents the structure from being rotated and
					// translated at the same time while a gesture is occuring,
					// which is preferable based on use cases since the rotation
					// center is the canvas center
					return;
				}
				// move structure
				var dif = new structures.Point(e.p.x, e.p.y);
				dif.sub(this.sketcher.lastPoint);
				if (this.parentAction) {
					this.parentAction.dif.add(dif);
					for ( var i = 0, ii = this.parentAction.ps.length; i < ii; i++) {
						this.parentAction.ps[i].add(dif);
					}
					if (this.sketcher.lasso && this.sketcher.lasso.isActive()) {
						this.sketcher.lasso.bounds.minX += dif.x;
						this.sketcher.lasso.bounds.maxX += dif.x;
						this.sketcher.lasso.bounds.minY += dif.y;
						this.sketcher.lasso.bounds.maxY += dif.y;
					}
					// must check here as change is outside of an action
					for ( var i = 0, ii = this.sketcher.molecules.length; i < ii; i++) {
						this.sketcher.molecules[i].check();
					}
				} else {
					this.parentAction = new actions.MoveAction(this.sketcher.getAllPoints(), dif);
					this.sketcher.historyManager.pushUndo(this.parentAction);
				}
			}
			this.sketcher.repaint();
		}
		this.sketcher.lastPoint = e.p;
	};
	_.keydown = function(e) {
		if (monitor.CANVAS_DRAGGING === this.sketcher) {
			if (this.sketcher.lastPoint) {
				e.p = this.sketcher.lastPoint;
				this.drag(e);
			}
		} else if (monitor.META) {
			if (e.which === 90) {
				// z
				this.sketcher.historyManager.undo();
			} else if (e.which === 89) {
				// y
				this.sketcher.historyManager.redo();
			} else if (e.which === 83) {
				// s
				this.sketcher.toolbarManager.buttonSave.getElement().click();
			} else if (e.which === 79) {
				// o
				this.sketcher.toolbarManager.buttonOpen.getElement().click();
			} else if (e.which === 78) {
				// n
				this.sketcher.toolbarManager.buttonClear.getElement().click();
			} else if (e.which === 187 || e.which === 61) {
				// +
				this.sketcher.toolbarManager.buttonScalePlus.getElement().click();
			} else if (e.which === 189 || e.which === 109) {
				// -
				this.sketcher.toolbarManager.buttonScaleMinus.getElement().click();
			} else if (e.which === 65) {
				// a
				if (!this.sketcher.oneMolecule) {
					this.sketcher.toolbarManager.buttonLasso.getElement().click();
					this.sketcher.lasso.select(this.sketcher.getAllAtoms(), this.sketcher.shapes);
				}
			}
		} else if (e.which === 9) {
			// tab
			if (!this.sketcher.oneMolecule) {
				this.sketcher.lasso.block = true;
				this.sketcher.toolbarManager.buttonLasso.getElement().click();
				this.sketcher.lasso.block = false;
				if (monitor.SHIFT) {
					if (this.sketcher.shapes.length > 0) {
						var nextShapeIndex = this.sketcher.shapes.length - 1;
						if (this.sketcher.lasso.shapes.length > 0) {
							nextShapeIndex = this.sketcher.shapes.indexOf(this.sketcher.lasso.shapes[0]) + 1;
						}
						if (nextShapeIndex === this.sketcher.shapes.length) {
							nextShapeIndex = 0;
						}
						// have to manually empty because shift modifier key
						// is down
						this.sketcher.lasso.empty();
						this.sketcher.lasso.select([], [ this.sketcher.shapes[nextShapeIndex] ]);
					}
				} else {
					if (this.sketcher.molecules.length > 0) {
						var nextMolIndex = this.sketcher.molecules.length - 1;
						if (this.sketcher.lasso.atoms.length > 0) {
							var curMol = this.sketcher.getMoleculeByAtom(this.sketcher.lasso.atoms[0]);
							nextMolIndex = this.sketcher.molecules.indexOf(curMol) + 1;
						}
						if (nextMolIndex === this.sketcher.molecules.length) {
							nextMolIndex = 0;
						}
						this.sketcher.lasso.select(this.sketcher.molecules[nextMolIndex].atoms, []);
					}
				}
			}
		} else if (e.which === 32) {
			// space key
			if (this.sketcher.lasso) {
				this.sketcher.lasso.empty();
			}
			this.sketcher.toolbarManager.buttonSingle.getElement().click();
		} else if (e.which >= 37 && e.which <= 40) {
			// arrow keys
			var dif = new structures.Point();
			switch (e.which) {
			case 37:
				dif.x = -10;
				break;
			case 38:
				dif.y = -10;
				break;
			case 39:
				dif.x = 10;
				break;
			case 40:
				dif.y = 10;
				break;
			}
			this.sketcher.historyManager.pushUndo(new actions.MoveAction(this.sketcher.lasso && this.sketcher.lasso.isActive() ? this.sketcher.lasso.getAllPoints() : this.sketcher.getAllPoints(), dif));
		} else if (e.which === 187 || e.which === 189 || e.which === 61 || e.which === 109) {
			// plus or minus
			if (this.sketcher.hovering && this.sketcher.hovering instanceof structures.Atom) {
				this.sketcher.historyManager.pushUndo(new actions.ChangeChargeAction(this.sketcher.hovering, e.which === 187 || e.which === 61 ? 1 : -1));
			}
		} else if (e.which === 8 || e.which === 127) {
			// delete or backspace
			this.sketcher.stateManager.STATE_ERASE.handleDelete();
		} else if (e.which >= 48 && e.which <= 57) {
			// digits
			if (this.sketcher.hovering) {
				var number = e.which - 48;
				var molIdentifier;
				var as = [];
				var bs = [];
				if (this.sketcher.hovering instanceof structures.Atom) {
					molIdentifier = this.sketcher.hovering;
					if (monitor.SHIFT) {
						if (number > 2 && number < 9) {
							var mol = this.sketcher.getMoleculeByAtom(this.sketcher.hovering);
							var angles = mol.getAngles(this.sketcher.hovering);
							var angle = 3 * m.PI / 2;
							if (angles.length !== 0) {
								angle = math.angleBetweenLargest(angles).angle;
							}
							var ring = this.sketcher.stateManager.STATE_NEW_RING.getRing(this.sketcher.hovering, number, this.sketcher.specs.bondLength_2D, angle, false);
							if (mol.atoms.indexOf(ring[0]) === -1) {
								as.push(ring[0]);
							}
							if (!this.sketcher.bondExists(this.sketcher.hovering, ring[0])) {
								bs.push(new structures.Bond(this.sketcher.hovering, ring[0]));
							}
							for ( var i = 1, ii = ring.length; i < ii; i++) {
								if (mol.atoms.indexOf(ring[i]) === -1) {
									as.push(ring[i]);
								}
								if (!this.sketcher.bondExists(ring[i - 1], ring[i])) {
									bs.push(new structures.Bond(ring[i - 1], ring[i]));
								}
							}
							if (!this.sketcher.bondExists(ring[ring.length - 1], this.sketcher.hovering)) {
								bs.push(new structures.Bond(ring[ring.length - 1], this.sketcher.hovering));
							}
						}
					} else {
						if (number === 0) {
							number = 10;
						}
						var p = new structures.Point(this.sketcher.hovering.x, this.sketcher.hovering.y);
						var a = this.getOptimumAngle(this.sketcher.hovering);
						var prev = this.sketcher.hovering;
						for ( var k = 0; k < number; k++) {
							var ause = a + (k % 2 === 1 ? m.PI / 3 : 0);
							p.x += this.sketcher.specs.bondLength_2D * m.cos(ause);
							p.y -= this.sketcher.specs.bondLength_2D * m.sin(ause);
							var use = new structures.Atom('C', p.x, p.y);
							var minDist = Infinity;
							var closest;
							for ( var i = 0, ii = this.sketcher.molecules.length; i < ii; i++) {
								var mol = this.sketcher.molecules[i];
								for ( var j = 0, jj = mol.atoms.length; j < jj; j++) {
									var at = mol.atoms[j];
									var dist = at.distance(use);
									if (dist < minDist) {
										minDist = dist;
										closest = at;
									}
								}
							}
							if (minDist < 5) {
								use = closest;
							} else {
								as.push(use);
							}
							if (!this.sketcher.bondExists(prev, use)) {
								bs.push(new structures.Bond(prev, use));
							}
							prev = use;
						}
					}
				} else if (this.sketcher.hovering instanceof structures.Bond) {
					molIdentifier = this.sketcher.hovering.a1;
					if (monitor.SHIFT) {
						if (number > 2 && number < 9) {
							var ring = this.sketcher.stateManager.STATE_NEW_RING.getOptimalRing(this.sketcher.hovering, number);
							var start = this.sketcher.hovering.a2;
							var end = this.sketcher.hovering.a1;
							var mol = this.sketcher.getMoleculeByAtom(start);
							if (ring[0] === this.sketcher.hovering.a1) {
								start = this.sketcher.hovering.a1;
								end = this.sketcher.hovering.a2;
							}
							if (mol.atoms.indexOf(ring[1]) === -1) {
								as.push(ring[1]);
							}
							if (!this.sketcher.bondExists(start, ring[1])) {
								bs.push(new structures.Bond(start, ring[1]));
							}
							for ( var i = 2, ii = ring.length; i < ii; i++) {
								if (mol.atoms.indexOf(ring[i]) === -1) {
									as.push(ring[i]);
								}
								if (!this.sketcher.bondExists(ring[i - 1], ring[i])) {
									bs.push(new structures.Bond(ring[i - 1], ring[i]));
								}
							}
							if (!this.sketcher.bondExists(ring[ring.length - 1], end)) {
								bs.push(new structures.Bond(ring[ring.length - 1], end));
							}
						}
					} else if (number > 0 && number < 4 && this.sketcher.hovering.bondOrder !== number) {
						this.sketcher.historyManager.pushUndo(new actions.ChangeBondAction(this.sketcher.hovering, number, structures.Bond.STEREO_NONE));
					} else if (number === 7 || number === 8) {
						var stereo = structures.Bond.STEREO_RECESSED;
						if(number===7){
							stereo = structures.Bond.STEREO_PROTRUDING;
						}
						this.sketcher.historyManager.pushUndo(new actions.ChangeBondAction(this.sketcher.hovering, 1, stereo));
					}
				}
				if (as.length !== 0 || bs.length !== 0) {
					this.sketcher.historyManager.pushUndo(new actions.AddAction(this.sketcher, molIdentifier, as, bs));
				}
			}
		} else if (e.which >= 65 && e.which <= 90) {
			// alphabet
			if (this.sketcher.hovering) {
				if (this.sketcher.hovering instanceof structures.Atom) {
					var check = String.fromCharCode(e.which);
					var firstMatch;
					var firstAfterMatch;
					var found = false;
					for ( var j = 0, jj = SYMBOLS.length; j < jj; j++) {
						if (this.sketcher.hovering.label.charAt(0) === check) {
							if (SYMBOLS[j] === this.sketcher.hovering.label) {
								found = true;
							} else if (SYMBOLS[j].charAt(0) === check) {
								if (found && !firstAfterMatch) {
									firstAfterMatch = SYMBOLS[j];
								} else if (!firstMatch) {
									firstMatch = SYMBOLS[j];
								}
							}
						} else {
							if (SYMBOLS[j].charAt(0) === check) {
								firstMatch = SYMBOLS[j];
								break;
							}
						}
					}
					var use = 'C';
					if (firstAfterMatch) {
						use = firstAfterMatch;
					} else if (firstMatch) {
						use = firstMatch;
					}
					if (use !== this.sketcher.hovering.label) {
						this.sketcher.historyManager.pushUndo(new actions.ChangeLabelAction(this.sketcher.hovering, use));
					}
				} else if (this.sketcher.hovering instanceof structures.Bond) {
					if (e.which === 70) {
						// f
						this.sketcher.historyManager.pushUndo(new actions.FlipBondAction(this.sketcher.hovering));
					}
				}
			}
		}
		if (this.innerkeydown) {
			this.innerkeydown(e);
		}
	};
	_.keypress = function(e) {
		if (this.innerkeypress) {
			this.innerkeypress(e);
		}
	};
	_.keyup = function(e) {
		if (monitor.CANVAS_DRAGGING === this.sketcher) {
			if (this.sketcher.lastPoint) {
				e.p = this.sketcher.lastPoint;
				this.sketcher.drag(e);
			}
		}
		if (this.innerkeyup) {
			this.innerkeyup(e);
		}
	};

})(ChemDoodle.math, ChemDoodle.monitor, ChemDoodle.uis.actions, ChemDoodle.uis.states, ChemDoodle.structures, ChemDoodle.SYMBOLS, Math);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(actions, states) {
	'use strict';
	states.ChargeState = function(sketcher) {
		this.setup(sketcher);
	};
	var _ = states.ChargeState.prototype = new states._State();
	_.delta = 1;
	_.innermouseup = function(e) {
		if (this.sketcher.hovering) {
			this.sketcher.historyManager.pushUndo(new actions.ChangeChargeAction(this.sketcher.hovering, this.delta));
		}
	};
	_.innermousemove = function(e) {
		this.findHoveredObject(e, true, false);
	};

})(ChemDoodle.uis.actions, ChemDoodle.uis.states);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(actions, states, structures, d2) {
	'use strict';
	states.EraseState = function(sketcher) {
		this.setup(sketcher);
	};
	var _ = states.EraseState.prototype = new states._State();
	_.handleDelete = function() {
		if (this.sketcher.lasso && this.sketcher.lasso.isActive()) {
			this.sketcher.historyManager.pushUndo(new actions.DeleteContentAction(this.sketcher, this.sketcher.lasso.atoms, this.sketcher.lasso.shapes));
			this.sketcher.lasso.empty();
		} else if (this.sketcher.hovering) {
			if (this.sketcher.hovering instanceof structures.Atom) {
				if (this.sketcher.oneMolecule) {
					var mol = this.sketcher.molecules[0];
					for ( var j = 0, jj = mol.atoms.length; j < jj; j++) {
						mol.atoms[j].visited = false;
					}
					var connectionsA = [];
					var connectionsB = [];
					this.sketcher.hovering.visited = true;
					for ( var j = 0, jj = mol.bonds.length; j < jj; j++) {
						var bj = mol.bonds[j];
						if (bj.contains(this.sketcher.hovering)) {
							var atoms = [];
							var bonds = [];
							var q = new structures.Queue();
							q.enqueue(bj.getNeighbor(this.sketcher.hovering));
							while (!q.isEmpty()) {
								var a = q.dequeue();
								if (!a.visited) {
									a.visited = true;
									atoms.push(a);
									for ( var k = 0, kk = mol.bonds.length; k < kk; k++) {
										var bk = mol.bonds[k];
										if (bk.contains(a) && !bk.getNeighbor(a).visited) {
											q.enqueue(bk.getNeighbor(a));
											bonds.push(bk);
										}
									}
								}
							}
							connectionsA.push(atoms);
							connectionsB.push(bonds);
						}
					}
					var largest = -1;
					var index = -1;
					for ( var j = 0, jj = connectionsA.length; j < jj; j++) {
						if (connectionsA[j].length > largest) {
							index = j;
							largest = connectionsA[j].length;
						}
					}
					if (index > -1) {
						var as = [];
						var bs = [];
						var hold;
						for ( var i = 0, ii = mol.atoms.length; i < ii; i++) {
							var a = mol.atoms[i];
							if (connectionsA[index].indexOf(a) === -1) {
								as.push(a);
							} else if (!hold) {
								hold = a;
							}
						}
						for ( var i = 0, ii = mol.bonds.length; i < ii; i++) {
							var b = mol.bonds[i];
							if (connectionsB[index].indexOf(b) === -1) {
								bs.push(b);
							}
						}
						this.sketcher.historyManager.pushUndo(new actions.DeleteAction(this.sketcher, hold, as, bs));
					} else {
						this.sketcher.historyManager.pushUndo(new actions.ClearAction(this.sketcher));
					}
				} else {
					var mol = this.sketcher.getMoleculeByAtom(this.sketcher.hovering);
					this.sketcher.historyManager.pushUndo(new actions.DeleteAction(this.sketcher, mol.atoms[0], [ this.sketcher.hovering ], mol.getBonds(this.sketcher.hovering)));
				}
			} else if (this.sketcher.hovering instanceof structures.Bond) {
				if (!this.sketcher.oneMolecule || this.sketcher.hovering.ring) {
					this.sketcher.historyManager.pushUndo(new actions.DeleteAction(this.sketcher, this.sketcher.hovering.a1, undefined, [ this.sketcher.hovering ]));
				}
			} else if (this.sketcher.hovering instanceof d2._Shape) {
				this.sketcher.historyManager.pushUndo(new actions.DeleteShapeAction(this.sketcher, this.sketcher.hovering));
			}
			this.sketcher.hovering = undefined;
			this.sketcher.repaint();
		}
		for ( var i = this.sketcher.shapes.length - 1; i >= 0; i--) {
			var s = this.sketcher.shapes[i];
			if (s instanceof d2.Pusher) {
				var remains1 = false, remains2 = false;
				for ( var j = 0, jj = this.sketcher.molecules.length; j < jj; j++) {
					var mol = this.sketcher.molecules[j];
					for ( var k = 0, kk = mol.atoms.length; k < kk; k++) {
						var a = mol.atoms[k];
						if (a === s.o1) {
							remains1 = true;
						} else if (a === s.o2) {
							remains2 = true;
						}
					}
					for ( var k = 0, kk = mol.bonds.length; k < kk; k++) {
						var b = mol.bonds[k];
						if (b === s.o1) {
							remains1 = true;
						} else if (b === s.o2) {
							remains2 = true;
						}
					}
				}
				if (!remains1 || !remains2) {
					this.sketcher.historyManager.undoStack[this.sketcher.historyManager.undoStack.length - 1].ss.push(s);
					this.sketcher.removeShape(s);
				}
			}
		}
	};
	_.innermouseup = function(e) {
		this.handleDelete();
	};
	_.innermousemove = function(e) {
		this.findHoveredObject(e, true, true, true);
	};

})(ChemDoodle.uis.actions, ChemDoodle.uis.states, ChemDoodle.structures, ChemDoodle.structures.d2);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//
(function(monitor, structures, actions, states, m) {
	'use strict';
	states.LabelState = function(sketcher) {
		this.setup(sketcher);
	};
	var _ = states.LabelState.prototype = new states._State();
	_.label = 'C';
	_.innermousedown = function(e) {
		this.newMolAllowed = true;
		if(this.sketcher.hovering){
			this.sketcher.hovering.isHover = false;
			this.sketcher.hovering.isSelected = true;
			this.sketcher.repaint();
		}
	};
	_.innermouseup = function(e) {
		if (this.sketcher.hovering) {
			this.sketcher.hovering.isSelected = false;
			if(this.sketcher.tempAtom){
				var b = new structures.Bond(this.sketcher.hovering, this.sketcher.tempAtom);
				this.sketcher.historyManager.pushUndo(new actions.AddAction(this.sketcher, b.a1, [b.a2], [b]));
				this.sketcher.tempAtom = undefined;
			}else if (this.label !== this.sketcher.hovering.label) {
				this.sketcher.historyManager.pushUndo(new actions.ChangeLabelAction(this.sketcher.hovering, this.label));
			}
		} else if (!this.sketcher.oneMolecule && this.newMolAllowed) {
			this.sketcher.historyManager.pushUndo(new actions.NewMoleculeAction(this.sketcher, [ new structures.Atom(this.label, e.p.x, e.p.y) ], []));
		}
		if (!this.sketcher.isMobile) {
			this.mousemove(e);
		}
	};
	_.innermousemove = function(e) {
		this.findHoveredObject(e, true, false);
	};
	_.innerdrag = function(e) {
		this.newMolAllowed = false;
		if(this.sketcher.hovering){
			var dist = this.sketcher.hovering.distance(e.p);
			if(dist<9){
				this.sketcher.tempAtom = undefined;
			}else if (e.p.distance(this.sketcher.hovering) < 15) {
				var angle = this.getOptimumAngle(this.sketcher.hovering);
				var x = this.sketcher.hovering.x + this.sketcher.specs.bondLength_2D * m.cos(angle);
				var y = this.sketcher.hovering.y - this.sketcher.specs.bondLength_2D * m.sin(angle);
				this.sketcher.tempAtom = new structures.Atom(this.label, x, y, 0);
			} else {
				if (monitor.ALT && monitor.SHIFT) {
					this.sketcher.tempAtom = new structures.Atom(this.label, e.p.x, e.p.y, 0);
				} else {
					var angle = this.sketcher.hovering.angle(e.p);
					var length = this.sketcher.hovering.distance(e.p);
					if (!monitor.SHIFT) {
						length = this.sketcher.specs.bondLength_2D;
					}
					if (!monitor.ALT) {
						var increments = m.floor((angle + m.PI / 12) / (m.PI / 6));
						angle = increments * m.PI / 6;
					}
					this.sketcher.tempAtom = new structures.Atom(this.label, this.sketcher.hovering.x + length * m.cos(angle), this.sketcher.hovering.y - length * m.sin(angle), 0);
				}
			}
			this.sketcher.repaint();
		}
	};

})(ChemDoodle.monitor, ChemDoodle.structures, ChemDoodle.uis.actions, ChemDoodle.uis.states, Math);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//
(function(math, monitor, structures, d2, actions, states, tools, m) {
	'use strict';
	states.LassoState = function(sketcher) {
		this.setup(sketcher);
	};
	var TRANSLATE = 1;
	var ROTATE = 2;
	//var SCALE = 3;
	var transformType = TRANSLATE;
	var paintRotate = false;

	var _ = states.LassoState.prototype = new states._State();
	_.innerdrag = function(e) {
		this.inDrag = true;
		if (this.sketcher.lasso.isActive() && transformType) {
			if (!this.sketcher.lastPoint) {
				// this prevents the structure from being rotated and
				// translated at the same time while a gesture is occurring,
				// which is preferable based on use cases since the rotation
				// center is the canvas center
				return;
			}
			if (transformType === TRANSLATE) {
				// move selection
				var dif = new structures.Point(e.p.x, e.p.y);
				dif.sub(this.sketcher.lastPoint);
				if (this.parentAction) {
					this.parentAction.dif.add(dif);
					for ( var i = 0, ii = this.parentAction.ps.length; i < ii; i++) {
						this.parentAction.ps[i].add(dif);
					}
					// must check here as change is outside of an action
					for ( var i = 0, ii = this.sketcher.molecules.length; i < ii; i++) {
						this.sketcher.molecules[i].check();
					}
					this.sketcher.lasso.bounds.minX += dif.x;
					this.sketcher.lasso.bounds.maxX += dif.x;
					this.sketcher.lasso.bounds.minY += dif.y;
					this.sketcher.lasso.bounds.maxY += dif.y;
					this.sketcher.repaint();
				} else {
					this.parentAction = new actions.MoveAction(this.sketcher.lasso.getAllPoints(), dif);
					this.sketcher.historyManager.pushUndo(this.parentAction);
				}
			} else if (transformType === ROTATE) {
				// rotate structure
				if (this.parentAction) {
					var center = this.parentAction.center;
					var oldAngle = center.angle(this.sketcher.lastPoint);
					var newAngle = center.angle(e.p);
					var rot = newAngle - oldAngle;
					this.parentAction.dif += rot;
					for ( var i = 0, ii = this.parentAction.ps.length; i < ii; i++) {
						var a = this.parentAction.ps[i];
						var dist = center.distance(a);
						var angle = center.angle(a) + rot;
						a.x = center.x + dist * m.cos(angle);
						a.y = center.y - dist * m.sin(angle);
					}
					// must check here as change is outside of an action
					for ( var i = 0, ii = this.sketcher.molecules.length; i < ii; i++) {
						this.sketcher.molecules[i].check();
					}
					this.sketcher.lasso.setBounds();
					this.sketcher.repaint();
				} else {
					var center = new structures.Point((this.sketcher.lasso.bounds.minX + this.sketcher.lasso.bounds.maxX) / 2, (this.sketcher.lasso.bounds.minY + this.sketcher.lasso.bounds.maxY) / 2);
					var oldAngle = center.angle(this.sketcher.lastPoint);
					var newAngle = center.angle(e.p);
					var rot = newAngle - oldAngle;
					this.parentAction = new actions.RotateAction(this.sketcher.lasso.getAllPoints(), rot, center);
					this.sketcher.historyManager.pushUndo(this.parentAction);
				}
			}
		} else if (this.sketcher.hovering) {
			if (!this.sketcher.lastPoint) {
				// this prevents the structure from being rotated and
				// translated at the same time while a gesture is occurring,
				// which is preferable based on use cases since the rotation
				// center is the canvas center
				return;
			}
			// move structure
			var dif = new structures.Point(e.p.x, e.p.y);
			dif.sub(this.sketcher.lastPoint);
			if (!this.parentAction) {
				var ps;
				if (this.sketcher.hovering instanceof structures.Atom) {
					ps = monitor.SHIFT ? [ this.sketcher.hovering ] : this.sketcher.getMoleculeByAtom(this.sketcher.hovering).atoms;
				} else if (this.sketcher.hovering instanceof structures.Bond) {
					ps = [ this.sketcher.hovering.a1, this.sketcher.hovering.a2 ];
				} else if (this.sketcher.hovering instanceof d2._Shape) {
					ps = this.sketcher.hovering.hoverPoint ? [ this.sketcher.hovering.hoverPoint ] : this.sketcher.hovering.getPoints();
				}
				this.parentAction = new actions.MoveAction(ps, dif);
				this.sketcher.historyManager.pushUndo(this.parentAction);
			} else {
				this.parentAction.dif.add(dif);
				for ( var i = 0, ii = this.parentAction.ps.length; i < ii; i++) {
					this.parentAction.ps[i].add(dif);
				}
				// must check here as change is outside of an action
				for ( var i = 0, ii = this.sketcher.molecules.length; i < ii; i++) {
					this.sketcher.molecules[i].check();
				}
				this.sketcher.repaint();
			}
		} else {
			// must check against undefined as lastGestureRotate can be 0, in
			// mobile mode it is set during gestures, don't use lasso
			this.sketcher.lasso.addPoint(e.p);
			this.sketcher.repaint();
		}
	};
	_.innermousedown = function(e) {
		this.inDrag = false;
		if (this.sketcher.lasso.isActive() && !monitor.SHIFT) {
			transformType = undefined;
			var rotateBuffer = 25 / this.sketcher.specs.scale;
			if (math.isBetween(e.p.x, this.sketcher.lasso.bounds.minX, this.sketcher.lasso.bounds.maxX) && math.isBetween(e.p.y, this.sketcher.lasso.bounds.minY, this.sketcher.lasso.bounds.maxY)) {
				transformType = TRANSLATE;
			} else if (math.isBetween(e.p.x, this.sketcher.lasso.bounds.minX - rotateBuffer, this.sketcher.lasso.bounds.maxX + rotateBuffer) && math.isBetween(e.p.y, this.sketcher.lasso.bounds.minY - rotateBuffer, this.sketcher.lasso.bounds.maxY + rotateBuffer)) {
				transformType = ROTATE;
			}
		} else if (!this.sketcher.hovering) {
			this.sketcher.lastPoint = undefined;
			this.sketcher.lasso.addPoint(e.p);
			this.sketcher.repaint();
		}
	};
	_.innermouseup = function(e) {
		if (!transformType) {
			if (!this.sketcher.hovering) {
				this.sketcher.lasso.select();
			}
		}
		this.innermousemove(e);
	};
	_.innerclick = function(e) {
		if (!transformType && !this.inDrag) {
			if (this.sketcher.hovering) {
				var as = [];
				var ss = [];
				if (this.sketcher.hovering instanceof structures.Atom) {
					as.push(this.sketcher.hovering);
				} else if (this.sketcher.hovering instanceof structures.Bond) {
					as.push(this.sketcher.hovering.a1);
					as.push(this.sketcher.hovering.a2);
				} else if (this.sketcher.hovering instanceof d2._Shape) {
					ss.push(this.sketcher.hovering);
				}
				this.sketcher.lasso.select(as, ss);
			} else if (this.sketcher.lasso.isActive()) {
				this.sketcher.lasso.empty();
			}
		}
		transformType = undefined;
	};
	_.innermousemove = function(e) {
		if (!this.sketcher.lasso.isActive()) {
			var includeMol = this.sketcher.lasso.mode !== tools.Lasso.MODE_LASSO_SHAPES;
			this.findHoveredObject(e, includeMol, includeMol, true);
		} else if (!monitor.SHIFT) {
			var p = false;
			var rotateBuffer = 25 / this.sketcher.specs.scale;
			if (!(math.isBetween(e.p.x, this.sketcher.lasso.bounds.minX, this.sketcher.lasso.bounds.maxX) && math.isBetween(e.p.y, this.sketcher.lasso.bounds.minY, this.sketcher.lasso.bounds.maxY)) && math.isBetween(e.p.x, this.sketcher.lasso.bounds.minX - rotateBuffer, this.sketcher.lasso.bounds.maxX + rotateBuffer) && math.isBetween(e.p.y, this.sketcher.lasso.bounds.minY - rotateBuffer, this.sketcher.lasso.bounds.maxY + rotateBuffer)) {
				p = true;
			}
			if (p != paintRotate) {
				paintRotate = p;
				this.sketcher.repaint();
			}
		}
	};
	_.innerdblclick = function(e) {
		if (this.sketcher.lasso.isActive()) {
			this.sketcher.lasso.empty();
		}
	};
	_.draw = function(ctx) {
		if (paintRotate && this.sketcher.lasso.bounds) {
			ctx.fillStyle = 'rgba(0,0,255,.1)';
			var rotateBuffer = 25 / this.sketcher.specs.scale;
			var b = this.sketcher.lasso.bounds;
			ctx.beginPath();
			ctx.rect(b.minX - rotateBuffer, b.minY - rotateBuffer, b.maxX - b.minX + 2 * rotateBuffer, rotateBuffer);
			ctx.rect(b.minX - rotateBuffer, b.maxY, b.maxX - b.minX + 2 * rotateBuffer, rotateBuffer);
			ctx.rect(b.minX - rotateBuffer, b.minY, rotateBuffer, b.maxY - b.minY);
			ctx.rect(b.maxX, b.minY, rotateBuffer, b.maxY - b.minY);
			ctx.fill();
		}
	};

})(ChemDoodle.math, ChemDoodle.monitor, ChemDoodle.structures, ChemDoodle.structures.d2, ChemDoodle.uis.actions, ChemDoodle.uis.states, ChemDoodle.uis.tools, Math);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(actions, states) {
	'use strict';
	states.LonePairState = function(sketcher) {
		this.setup(sketcher);
	};
	var _ = states.LonePairState.prototype = new states._State();
	_.delta = 1;
	_.innermouseup = function(e) {
		if (this.delta < 0 && this.sketcher.hovering.numLonePair < 1) {
			return;
		}
		if (this.sketcher.hovering) {
			this.sketcher.historyManager.pushUndo(new actions.ChangeLonePairAction(this.sketcher.hovering, this.delta));
		}
	};
	_.innermousemove = function(e) {
		this.findHoveredObject(e, true, false);
	};

})(ChemDoodle.uis.actions, ChemDoodle.uis.states);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//
(function(actions, states, structures) {
	'use strict';
	states.MoveState = function(sketcher) {
		this.setup(sketcher);
	};
	var _ = states.MoveState.prototype = new states._State();
	_.action = undefined;
	_.innerdrag = function(e) {
		if (this.sketcher.hovering) {
			if (!this.action) {
				var ps = [];
				var dif = new structures.Point(e.p.x, e.p.y);
				if (this.sketcher.hovering instanceof structures.Atom) {
					dif.sub(this.sketcher.hovering);
					ps[0] = this.sketcher.hovering;
				} else if (this.sketcher.hovering instanceof structures.Bond) {
					dif.sub(this.sketcher.lastPoint);
					ps[0] = this.sketcher.hovering.a1;
					ps[1] = this.sketcher.hovering.a2;
				}
				this.action = new actions.MoveAction(ps, dif);
				this.sketcher.historyManager.pushUndo(this.action);
			} else {
				var dif = new structures.Point(e.p.x, e.p.y);
				dif.sub(this.sketcher.lastPoint);
				this.action.dif.add(dif);
				for ( var i = 0, ii = this.action.ps.length; i < ii; i++) {
					this.action.ps[i].add(dif);
				}
				for ( var i = 0, ii = this.sketcher.molecules.length; i < ii; i++) {
					this.sketcher.molecules[i].check();
				}
				this.sketcher.repaint();
			}
		}
	};
	_.innermousemove = function(e) {
		this.findHoveredObject(e, true, true);
	};
	_.innermouseup = function(e) {
		this.action = undefined;
	};

})(ChemDoodle.uis.actions, ChemDoodle.uis.states, ChemDoodle.structures);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//
(function(monitor, actions, states, structures, m) {
	'use strict';
	states.NewBondState = function(sketcher) {
		this.setup(sketcher);
	};
	var _ = states.NewBondState.prototype = new states._State();
	_.bondOrder = 1;
	_.stereo = structures.Bond.STEREO_NONE;
	_.incrementBondOrder = function(b) {
		this.newMolAllowed = false;
		if (this.bondOrder === 1 && this.stereo === structures.Bond.STEREO_NONE) {
			this.sketcher.historyManager.pushUndo(new actions.ChangeBondAction(b));
		} else {
			if (b.bondOrder === this.bondOrder && b.stereo === this.stereo) {
				if (b.bondOrder === 1 && b.stereo !== structures.Bond.STEREO_NONE || b.bondOrder === 2 && b.stereo === structures.Bond.STEREO_NONE) {
					this.sketcher.historyManager.pushUndo(new actions.FlipBondAction(b));
				}
			} else {
				this.sketcher.historyManager.pushUndo(new actions.ChangeBondAction(b, this.bondOrder, this.stereo));
			}
		}
	};

	_.innerexit = function() {
		this.removeStartAtom();
	};
	_.innerdrag = function(e) {
		this.newMolAllowed = false;
		this.removeStartAtom();
		if (this.sketcher.hovering instanceof structures.Atom) {
			if (e.p.distance(this.sketcher.hovering) < 15) {
				var angle = this.getOptimumAngle(this.sketcher.hovering, this.bondOrder);
				var x = this.sketcher.hovering.x + this.sketcher.specs.bondLength_2D * m.cos(angle);
				var y = this.sketcher.hovering.y - this.sketcher.specs.bondLength_2D * m.sin(angle);
				this.sketcher.tempAtom = new structures.Atom('C', x, y, 0);
			} else {
				var closest;
				var distMin = 1000;
				for ( var i = 0, ii = this.sketcher.molecules.length; i < ii; i++) {
					var mol = this.sketcher.molecules[i];
					for ( var j = 0, jj = mol.atoms.length; j < jj; j++) {
						var a = mol.atoms[j];
						var dist = a.distance(e.p);
						if (dist < 5 && (!closest || dist < distMin)) {
							closest = a;
							distMin = dist;
						}
					}
				}
				if (closest) {
					this.sketcher.tempAtom = new structures.Atom('C', closest.x, closest.y, 0);
				} else if (monitor.ALT && monitor.SHIFT) {
					this.sketcher.tempAtom = new structures.Atom('C', e.p.x, e.p.y, 0);
				} else {
					var angle = this.sketcher.hovering.angle(e.p);
					var length = this.sketcher.hovering.distance(e.p);
					if (!monitor.SHIFT) {
						length = this.sketcher.specs.bondLength_2D;
					}
					if (!monitor.ALT) {
						var increments = m.floor((angle + m.PI / 12) / (m.PI / 6));
						angle = increments * m.PI / 6;
					}
					this.sketcher.tempAtom = new structures.Atom('C', this.sketcher.hovering.x + length * m.cos(angle), this.sketcher.hovering.y - length * m.sin(angle), 0);
				}
			}
			for ( var i = 0, ii = this.sketcher.molecules.length; i < ii; i++) {
				var mol = this.sketcher.molecules[i];
				for ( var j = 0, jj = mol.atoms.length; j < jj; j++) {
					var a = mol.atoms[j];
					if (a.distance(this.sketcher.tempAtom) < 5) {
						this.sketcher.tempAtom.x = a.x;
						this.sketcher.tempAtom.y = a.y;
						this.sketcher.tempAtom.isOverlap = true;
					}
				}
			}
			this.sketcher.repaint();
		}
	};
	_.innerclick = function(e) {
		if (!this.sketcher.hovering && !this.sketcher.oneMolecule && this.newMolAllowed) {
			this.sketcher.historyManager.pushUndo(new actions.NewMoleculeAction(this.sketcher, [ new structures.Atom('C', e.p.x, e.p.y) ], []));
			if (!this.sketcher.isMobile) {
				this.mousemove(e);
			}
			this.newMolAllowed = false;
		}
	};
	_.innermousedown = function(e) {
		this.newMolAllowed = true;
		if (this.sketcher.hovering instanceof structures.Atom) {
			this.sketcher.hovering.isHover = false;
			this.sketcher.hovering.isSelected = true;
			this.drag(e);
		} else if (this.sketcher.hovering instanceof structures.Bond) {
			this.sketcher.hovering.isHover = false;
			this.incrementBondOrder(this.sketcher.hovering);
			for ( var i = 0, ii = this.sketcher.molecules.length; i < ii; i++) {
				this.sketcher.molecules[i].check();
			}
			this.sketcher.repaint();
		}
	};
	_.innermouseup = function(e) {
		if (this.sketcher.tempAtom && this.sketcher.hovering) {
			var as = [];
			var bs = [];
			var makeBond = true;
			if (this.sketcher.tempAtom.isOverlap) {
				for ( var i = 0, ii = this.sketcher.molecules.length; i < ii; i++) {
					var mol = this.sketcher.molecules[i];
					for ( var j = 0, jj = mol.atoms.length; j < jj; j++) {
						var a = mol.atoms[j];
						if (a.distance(this.sketcher.tempAtom) < 5) {
							this.sketcher.tempAtom = a;
						}
					}
				}
				var bond = this.sketcher.getBond(this.sketcher.hovering, this.sketcher.tempAtom);
				if (bond) {
					this.incrementBondOrder(bond);
					makeBond = false;
				}
			} else {
				as.push(this.sketcher.tempAtom);
			}
			if (makeBond) {
				bs[0] = new structures.Bond(this.sketcher.hovering, this.sketcher.tempAtom, this.bondOrder);
				bs[0].stereo = this.stereo;
				this.sketcher.historyManager.pushUndo(new actions.AddAction(this.sketcher, bs[0].a1, as, bs));
			}
		}
		this.sketcher.tempAtom = undefined;
		if (!this.sketcher.isMobile) {
			this.mousemove(e);
		}
	};
	_.innermousemove = function(e) {
		if (this.sketcher.tempAtom) {
			return;
		}
		this.findHoveredObject(e, true, true);
		if (this.sketcher.startAtom) {
			if (this.sketcher.hovering) {
				this.sketcher.startAtom.x = -10;
				this.sketcher.startAtom.y = -10;
			} else {
				this.sketcher.startAtom.x = e.p.x;
				this.sketcher.startAtom.y = e.p.y;
			}
		}
	};
	_.innermouseout = function(e) {
		this.removeStartAtom();
	};

})(ChemDoodle.monitor, ChemDoodle.uis.actions, ChemDoodle.uis.states, ChemDoodle.structures, Math);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//
(function(math, monitor, actions, states, structures, m) {
	'use strict';
	states.NewRingState = function(sketcher) {
		this.setup(sketcher);
	};
	var _ = states.NewRingState.prototype = new states._State();
	_.numSides = 6;
	_.unsaturated = false;
	_.getRing = function(a, numSides, bondLength, angle, setOverlaps) {
		var innerAngle = m.PI - 2 * m.PI / numSides;
		angle += innerAngle / 2;
		var ring = [];
		for ( var i = 0; i < numSides - 1; i++) {
			var p = i === 0 ? new structures.Atom('C', a.x, a.y) : new structures.Atom('C', ring[ring.length - 1].x, ring[ring.length - 1].y);
			p.x += bondLength * m.cos(angle);
			p.y -= bondLength * m.sin(angle);
			ring.push(p);
			angle += m.PI + innerAngle;
		}
		for ( var i = 0, ii = this.sketcher.molecules.length; i < ii; i++) {
			var mol = this.sketcher.molecules[i];
			for ( var j = 0, jj = mol.atoms.length; j < jj; j++) {
				mol.atoms[j].isOverlap = false;
			}
		}
		for ( var i = 0, ii = ring.length; i < ii; i++) {
			var minDist = Infinity;
			var closest;
			for ( var k = 0, kk = this.sketcher.molecules.length; k < kk; k++) {
				var mol = this.sketcher.molecules[k];
				for ( var j = 0, jj = mol.atoms.length; j < jj; j++) {
					var dist = mol.atoms[j].distance(ring[i]);
					if (dist < minDist) {
						minDist = dist;
						closest = mol.atoms[j];
					}
				}
			}
			if (minDist < 5) {
				ring[i] = closest;
				if (setOverlaps) {
					closest.isOverlap = true;
				}
			}
		}
		return ring;
	};
	_.getOptimalRing = function(b, numSides) {
		var innerAngle = m.PI / 2 - m.PI / numSides;
		var bondLength = b.a1.distance(b.a2);
		var ring1 = this.getRing(b.a1, numSides, bondLength, b.a1.angle(b.a2) - innerAngle, false);
		var ring2 = this.getRing(b.a2, numSides, bondLength, b.a2.angle(b.a1) - innerAngle, false);
		var dist1 = 0, dist2 = 0;
		for ( var i = 1, ii = ring1.length; i < ii; i++) {
			for ( var k = 0, kk = this.sketcher.molecules.length; k < kk; k++) {
				var mol = this.sketcher.molecules[k];
				for ( var j = 0, jj = mol.atoms.length; j < jj; j++) {
					var d1 = mol.atoms[j].distance(ring1[i]);
					var d2 = mol.atoms[j].distance(ring2[i]);
					dist1 += m.min(1E8, 1 / (d1 * d1));
					dist2 += m.min(1E8, 1 / (d2 * d2));
				}
			}
		}
		if (dist1 < dist2) {
			return ring1;
		} else {
			return ring2;
		}
	};

	_.innerexit = function() {
		this.removeStartAtom();
	};
	_.innerdrag = function(e) {
		this.newMolAllowed = false;
		this.removeStartAtom();
		if (this.sketcher.hovering instanceof structures.Atom) {
			var a = 0;
			var l = 0;
			if (e.p.distance(this.sketcher.hovering) < 15) {
				var angles = this.sketcher.getMoleculeByAtom(this.sketcher.hovering).getAngles(this.sketcher.hovering);
				if (angles.length === 0) {
					a = 3 * m.PI / 2;
				} else {
					a = math.angleBetweenLargest(angles).angle;
				}
				l = this.sketcher.specs.bondLength_2D;
			} else {
				a = this.sketcher.hovering.angle(e.p);
				l = this.sketcher.hovering.distance(e.p);
				if (!(monitor.ALT && monitor.SHIFT)) {
					if (!monitor.SHIFT) {
						l = this.sketcher.specs.bondLength_2D;
					}
					if (!monitor.ALT) {
						var increments = m.floor((a + m.PI / 12) / (m.PI / 6));
						a = increments * m.PI / 6;
					}
				}
			}
			this.sketcher.tempRing = this.getRing(this.sketcher.hovering, this.numSides, l, a, true);
			this.sketcher.repaint();
		} else if (this.sketcher.hovering instanceof structures.Bond) {
			var dist = math.distanceFromPointToLineInclusive(e.p, this.sketcher.hovering.a1, this.sketcher.hovering.a2);
			var ringUse;
			if (dist !== -1 && dist <= 7) {
				ringUse = this.getOptimalRing(this.sketcher.hovering, this.numSides);
			} else {
				var innerAngle = m.PI / 2 - m.PI / this.numSides;
				var bondLength = this.sketcher.hovering.a1.distance(this.sketcher.hovering.a2);
				var ring1 = this.getRing(this.sketcher.hovering.a1, this.numSides, bondLength, this.sketcher.hovering.a1.angle(this.sketcher.hovering.a2) - innerAngle, false);
				var ring2 = this.getRing(this.sketcher.hovering.a2, this.numSides, bondLength, this.sketcher.hovering.a2.angle(this.sketcher.hovering.a1) - innerAngle, false);
				var center1 = new structures.Point();
				var center2 = new structures.Point();
				for ( var i = 1, ii = ring1.length; i < ii; i++) {
					center1.add(ring1[i]);
					center2.add(ring2[i]);
				}
				center1.x /= (ring1.length - 1);
				center1.y /= (ring1.length - 1);
				center2.x /= (ring2.length - 1);
				center2.y /= (ring2.length - 1);
				var dist1 = center1.distance(e.p);
				var dist2 = center2.distance(e.p);
				ringUse = ring2;
				if (dist1 < dist2) {
					ringUse = ring1;
				}
			}
			for ( var j = 1, jj = ringUse.length; j < jj; j++) {
				if (this.sketcher.getAllAtoms().indexOf(ringUse[j]) !== -1) {
					ringUse[j].isOverlap = true;
				}
			}
			this.sketcher.tempRing = ringUse;
			this.sketcher.repaint();
		}
	};
	_.innerclick = function(e) {
		if (!this.sketcher.hovering && !this.sketcher.oneMolecule && this.newMolAllowed) {
			this.sketcher.historyManager.pushUndo(new actions.NewMoleculeAction(this.sketcher, [ new structures.Atom('C', e.p.x, e.p.y) ], []));
			if (!this.sketcher.isMobile) {
				this.mousemove(e);
			}
			this.newMolAllowed = false;
		}
	};
	_.innermousedown = function(e) {
		this.newMolAllowed = true;
		if (this.sketcher.hovering) {
			this.sketcher.hovering.isHover = false;
			this.sketcher.hovering.isSelected = true;
			this.drag(e);
		}
	};
	_.innermouseup = function(e) {
		if (this.sketcher.tempRing && this.sketcher.hovering) {
			var as = [];
			var bs = [];
			var allAs = this.sketcher.getAllAtoms();
			if (this.sketcher.hovering instanceof structures.Atom) {
				if (allAs.indexOf(this.sketcher.tempRing[0]) === -1) {
					as.push(this.sketcher.tempRing[0]);
				}
				if (!this.sketcher.bondExists(this.sketcher.hovering, this.sketcher.tempRing[0])) {
					bs.push(new structures.Bond(this.sketcher.hovering, this.sketcher.tempRing[0]));
				}
				for ( var i = 1, ii = this.sketcher.tempRing.length; i < ii; i++) {
					if (allAs.indexOf(this.sketcher.tempRing[i]) === -1) {
						as.push(this.sketcher.tempRing[i]);
					}
					if (!this.sketcher.bondExists(this.sketcher.tempRing[i - 1], this.sketcher.tempRing[i])) {
						bs.push(new structures.Bond(this.sketcher.tempRing[i - 1], this.sketcher.tempRing[i], i % 2 === 1 && this.unsaturated ? 2 : 1));
					}
				}
				if (!this.sketcher.bondExists(this.sketcher.tempRing[this.sketcher.tempRing.length - 1], this.sketcher.hovering)) {
					bs.push(new structures.Bond(this.sketcher.tempRing[this.sketcher.tempRing.length - 1], this.sketcher.hovering, this.unsaturated ? 2 : 1));
				}
			} else if (this.sketcher.hovering instanceof structures.Bond) {
				var start = this.sketcher.hovering.a2;
				var end = this.sketcher.hovering.a1;
				if (this.sketcher.tempRing[0] === this.sketcher.hovering.a1) {
					start = this.sketcher.hovering.a1;
					end = this.sketcher.hovering.a2;
				}
				if (allAs.indexOf(this.sketcher.tempRing[1]) === -1) {
					as.push(this.sketcher.tempRing[1]);
				}
				if (!this.sketcher.bondExists(start, this.sketcher.tempRing[1])) {
					bs.push(new structures.Bond(start, this.sketcher.tempRing[1]));
				}
				for ( var i = 2, ii = this.sketcher.tempRing.length; i < ii; i++) {
					if (allAs.indexOf(this.sketcher.tempRing[i]) === -1) {
						as.push(this.sketcher.tempRing[i]);
					}
					if (!this.sketcher.bondExists(this.sketcher.tempRing[i - 1], this.sketcher.tempRing[i])) {
						bs.push(new structures.Bond(this.sketcher.tempRing[i - 1], this.sketcher.tempRing[i], i % 2 === 0 && this.unsaturated ? 2 : 1));
					}
				}
				if (!this.sketcher.bondExists(this.sketcher.tempRing[this.sketcher.tempRing.length - 1], end)) {
					bs.push(new structures.Bond(this.sketcher.tempRing[this.sketcher.tempRing.length - 1], end));
				}
			}
			if (as.length !== 0 || bs.length !== 0) {
				this.sketcher.historyManager.pushUndo(new actions.AddAction(this.sketcher, bs[0].a1, as, bs));
			}
			for ( var j = 0, jj = allAs.length; j < jj; j++) {
				allAs[j].isOverlap = false;
			}
		}
		this.sketcher.tempRing = undefined;
		if (!this.sketcher.isMobile) {
			this.mousemove(e);
		}
	};
	_.innermousemove = function(e) {
		if (this.sketcher.tempAtom) {
			return;
		}
		this.findHoveredObject(e, true, true);
		if (this.sketcher.startAtom) {
			if (this.sketcher.hovering) {
				this.sketcher.startAtom.x = -10;
				this.sketcher.startAtom.y = -10;
			} else {
				this.sketcher.startAtom.x = e.p.x;
				this.sketcher.startAtom.y = e.p.y;
			}
		}
	};
	_.innermouseout = function(e) {
		this.removeStartAtom();
	};

})(ChemDoodle.math, ChemDoodle.monitor, ChemDoodle.uis.actions, ChemDoodle.uis.states, ChemDoodle.structures, Math);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(extensions, structures, d2, actions, states) {
	'use strict';
	states.PusherState = function(sketcher) {
		this.setup(sketcher);
	};
	var _ = states.PusherState.prototype = new states._State();
	_.numElectron = 1;
	_.innermousedown = function(e) {
		if (this.sketcher.hovering) {
			this.start = this.sketcher.hovering;
		}
	};
	_.innerdrag = function(e) {
		if (this.start) {
			this.end = new structures.Point(e.p.x, e.p.y);
			this.findHoveredObject(e, true, true);
			this.sketcher.repaint();
		}
	};
	_.innermouseup = function(e) {
		if (this.start && this.sketcher.hovering && this.sketcher.hovering !== this.start) {
			var dup;
			var remove = false;
			for ( var i = 0, ii = this.sketcher.shapes.length; i < ii; i++) {
				var s = this.sketcher.shapes[i];
				if (s instanceof d2.Pusher) {
					if (s.o1 === this.start && s.o2 === this.sketcher.hovering) {
						dup = s;
					} else if (s.o2 === this.start && s.o1 === this.sketcher.hovering) {
						dup = s;
						remove = true;
					}
				}
			}
			if (dup) {
				if (remove) {
					this.sketcher.historyManager.pushUndo(new actions.DeleteShapeAction(this.sketcher, dup));
				}
				this.start = undefined;
				this.end = undefined;
				this.sketcher.repaint();
			} else {
				var shape = new d2.Pusher(this.start, this.sketcher.hovering, this.numElectron);
				this.start = undefined;
				this.end = undefined;
				this.sketcher.historyManager.pushUndo(new actions.AddShapeAction(this.sketcher, shape));
			}
		} else {
			this.start = undefined;
			this.end = undefined;
			this.sketcher.repaint();
		}
	};
	_.innermousemove = function(e) {
		this.findHoveredObject(e, true, true);
		this.sketcher.repaint();
	};
	_.draw = function(ctx) {
		if (this.start && this.end) {
			ctx.strokeStyle = '#00FF00';
			ctx.fillStyle = '#00FF00';
			ctx.lineWidth = 1;
			var p1 = this.start instanceof structures.Atom ? this.start : this.start.getCenter();
			var p2 = this.end;
			if (this.sketcher.hovering && this.sketcher.hovering !== this.start) {
				p2 = this.sketcher.hovering instanceof structures.Atom ? this.sketcher.hovering : this.sketcher.hovering.getCenter();
			}
			ctx.beginPath();
			ctx.moveTo(p1.x, p1.y);
			extensions.contextHashTo(ctx, p1.x, p1.y, p2.x, p2.y, 2, 2);
			ctx.stroke();
		}
	};

})(ChemDoodle.extensions, ChemDoodle.structures, ChemDoodle.structures.d2, ChemDoodle.uis.actions, ChemDoodle.uis.states);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(actions, states, structures, d2) {
	'use strict';
	states.QueryState = function(sketcher) {
		this.setup(sketcher);
	};
	var _ = states.QueryState.prototype = new states._State();
	_.innermouseup = function(e) {
		if (this.sketcher.hovering) {
			if(this.sketcher.hovering instanceof structures.Atom){
				this.sketcher.dialogManager.atomQueryDialog.setAtom(this.sketcher.hovering);
				this.sketcher.dialogManager.atomQueryDialog.getElement().dialog('open');
			}else if(this.sketcher.hovering instanceof structures.Bond){
				this.sketcher.dialogManager.bondQueryDialog.setBond(this.sketcher.hovering);
				this.sketcher.dialogManager.bondQueryDialog.getElement().dialog('open');
			}
		}
	};
	_.innermousemove = function(e) {
		this.findHoveredObject(e, true, true, false);
	};

})(ChemDoodle.uis.actions, ChemDoodle.uis.states, ChemDoodle.structures, ChemDoodle.structures.d2);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(actions, states) {
	'use strict';
	states.RadicalState = function(sketcher) {
		this.setup(sketcher);
	};
	var _ = states.RadicalState.prototype = new states._State();
	_.delta = 1;
	_.innermouseup = function(e) {
		if (this.delta < 0 && this.sketcher.hovering.numRadical < 1) {
			return;
		}
		if (this.sketcher.hovering) {
			this.sketcher.historyManager.pushUndo(new actions.ChangeRadicalAction(this.sketcher.hovering, this.delta));
		}
	};
	_.innermousemove = function(e) {
		this.findHoveredObject(e, true, false);
	};

})(ChemDoodle.uis.actions, ChemDoodle.uis.states);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(extensions, math, monitor, structures, d2, actions, states, m) {
	'use strict';
	states.ShapeState = function(sketcher) {
		this.setup(sketcher);
	};
	var _ = states.ShapeState.prototype = new states._State();
	_.shapeType = states.ShapeState.LINE;
	_.superDoubleClick = _.dblclick;
	_.dblclick = function(e) {
		// override double click not to center when editing shapes
		if (!this.control) {
			this.superDoubleClick(e);
		}
	};
	_.innerexit = function(e) {
		// set it back to line to remove graphical controls for other shapes
		this.shapeType = states.ShapeState.LINE;
		this.sketcher.repaint();
	};
	_.innermousemove = function(e) {
		this.control = undefined;
		if (this.shapeType === states.ShapeState.BRACKET) {
			var size = 6;
			for ( var i = 0, ii = this.sketcher.shapes.length; i < ii; i++) {
				var s = this.sketcher.shapes[i];
				if (s instanceof d2.Bracket) {
					var minX = m.min(s.p1.x, s.p2.x);
					var maxX = m.max(s.p1.x, s.p2.x);
					var minY = m.min(s.p1.y, s.p2.y);
					var maxY = m.max(s.p1.y, s.p2.y);
					var hits = [];
					hits.push({
						x : maxX + 5,
						y : minY + 15,
						v : 1
					});
					hits.push({
						x : maxX + 5,
						y : maxY + 15,
						v : 2
					});
					hits.push({
						x : minX - 17,
						y : (minY + maxY) / 2 + 15,
						v : 3
					});
					for ( var j = 0, jj = hits.length; j < jj; j++) {
						var h = hits[j];
						if (math.isBetween(e.p.x, h.x, h.x + size * 2) && math.isBetween(e.p.y, h.y - size, h.y)) {
							this.control = {
								s : s,
								t : h.v
							};
							break;
						} else if (math.isBetween(e.p.x, h.x, h.x + size * 2) && math.isBetween(e.p.y, h.y + size, h.y + size * 2)) {
							this.control = {
								s : s,
								t : -1 * h.v
							};
							break;
						}
					}
					if (this.control) {
						break;
					}
				}
			}
			this.sketcher.repaint();
		}
	};
	_.innermousedown = function(e) {
		if (this.control) {
			this.sketcher.historyManager.pushUndo(new actions.ChangeBracketAttributeAction(this.control.s, this.control.t));
			this.sketcher.repaint();
		} else {
			this.start = new structures.Point(e.p.x, e.p.y);
			this.end = this.start;
		}
	};
	_.innerdrag = function(e) {
		this.end = new structures.Point(e.p.x, e.p.y);
		if (this.shapeType === states.ShapeState.BRACKET) {
			if (monitor.SHIFT) {
				var difx = this.end.x - this.start.x;
				var dify = this.end.y - this.start.y;
				if (difx < 0 && dify > 0) {
					dify *= -1;
				} else if (difx > 0 && dify < 0) {
					difx *= -1;
				}
				var difuse = dify;
				if (m.abs(difx) < m.abs(dify)) {
					difuse = difx;
				}
				this.end.x = this.start.x + difuse;
				this.end.y = this.start.y + difuse;
			}
		} else {
			if (!monitor.ALT) {
				var angle = this.start.angle(this.end);
				var length = this.start.distance(this.end);
				if (!monitor.ALT) {
					var increments = m.floor((angle + m.PI / 12) / (m.PI / 6));
					angle = increments * m.PI / 6;
				}
				this.end.x = this.start.x + length * m.cos(angle);
				this.end.y = this.start.y - length * m.sin(angle);
			}
		}
		this.sketcher.repaint();
	};
	_.innermouseup = function(e) {
		if (this.start && this.end) {
			var shape;
			if (this.start.distance(this.end) > 5) {
				if (this.shapeType >= states.ShapeState.LINE && this.shapeType <= states.ShapeState.ARROW_EQUILIBRIUM) {
					shape = new d2.Line(this.start, this.end);
					if (this.shapeType === states.ShapeState.ARROW_SYNTHETIC) {
						shape.arrowType = d2.Line.ARROW_SYNTHETIC;
					} else if (this.shapeType === states.ShapeState.ARROW_RETROSYNTHETIC) {
						shape.arrowType = d2.Line.ARROW_RETROSYNTHETIC;
					} else if (this.shapeType === states.ShapeState.ARROW_RESONANCE) {
						shape.arrowType = d2.Line.ARROW_RESONANCE;
					} else if (this.shapeType === states.ShapeState.ARROW_EQUILIBRIUM) {
						shape.arrowType = d2.Line.ARROW_EQUILIBRIUM;
					}
				} else if (this.shapeType === states.ShapeState.BRACKET) {
					shape = new d2.Bracket(this.start, this.end);
				}
			}
			this.start = undefined;
			this.end = undefined;
			if (shape) {
				this.sketcher.historyManager.pushUndo(new actions.AddShapeAction(this.sketcher, shape));
			}
		}
	};
	function drawBracketControl(ctx, x, y, control, type) {
		var size = 6;
		if (control && m.abs(control.t) === type) {
			ctx.fillStyle = '#885110';
			ctx.beginPath();
			if (control.t > 0) {
				ctx.moveTo(x, y);
				ctx.lineTo(x + size, y - size);
				ctx.lineTo(x + size * 2, y);
			} else {
				ctx.moveTo(x, y + size);
				ctx.lineTo(x + size, y + size * 2);
				ctx.lineTo(x + size * 2, y + size);
			}
			ctx.closePath();
			ctx.fill();
		}
		ctx.strokeStyle = 'blue';
		ctx.beginPath();
		ctx.moveTo(x, y);
		ctx.lineTo(x + size, y - size);
		ctx.lineTo(x + size * 2, y);
		ctx.moveTo(x, y + size);
		ctx.lineTo(x + size, y + size * 2);
		ctx.lineTo(x + size * 2, y + size);
		ctx.stroke();
	}
	_.draw = function(ctx) {
		if (this.start && this.end) {
			ctx.strokeStyle = '#00FF00';
			ctx.fillStyle = '#00FF00';
			ctx.lineWidth = 1;
			ctx.beginPath();
			ctx.moveTo(this.start.x, this.start.y);
			if (this.shapeType === states.ShapeState.BRACKET) {
				extensions.contextHashTo(ctx, this.start.x, this.start.y, this.end.x, this.start.y, 2, 2);
				extensions.contextHashTo(ctx, this.end.x, this.start.y, this.end.x, this.end.y, 2, 2);
				extensions.contextHashTo(ctx, this.end.x, this.end.y, this.start.x, this.end.y, 2, 2);
				extensions.contextHashTo(ctx, this.start.x, this.end.y, this.start.x, this.start.y, 2, 2);
			} else {
				extensions.contextHashTo(ctx, this.start.x, this.start.y, this.end.x, this.end.y, 2, 2);
			}
			ctx.stroke();
		} else if (this.shapeType === states.ShapeState.BRACKET) {
			ctx.lineWidth = 2;
			ctx.lineJoin = 'miter';
			ctx.lineCap = 'butt';
			for ( var i = 0, ii = this.sketcher.shapes.length; i < ii; i++) {
				var s = this.sketcher.shapes[i];
				if (s instanceof d2.Bracket) {
					var minX = m.min(s.p1.x, s.p2.x);
					var maxX = m.max(s.p1.x, s.p2.x);
					var minY = m.min(s.p1.y, s.p2.y);
					var maxY = m.max(s.p1.y, s.p2.y);
					var c = this.control && this.control.s === s ? this.control : undefined;
					drawBracketControl(ctx, maxX + 5, minY + 15, c, 1);
					drawBracketControl(ctx, maxX + 5, maxY + 15, c, 2);
					drawBracketControl(ctx, minX - 17, (minY + maxY) / 2 + 15, c, 3);
				}
			}
		}

	};

	states.ShapeState.LINE = 1;
	states.ShapeState.ARROW_SYNTHETIC = 2;
	states.ShapeState.ARROW_RETROSYNTHETIC = 3;
	states.ShapeState.ARROW_RESONANCE = 4;
	states.ShapeState.ARROW_EQUILIBRIUM = 5;
	states.ShapeState.BRACKET = 10;

})(ChemDoodle.extensions, ChemDoodle.math, ChemDoodle.monitor, ChemDoodle.structures, ChemDoodle.structures.d2, ChemDoodle.uis.actions, ChemDoodle.uis.states, Math);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(states) {
	'use strict';
	states.StateManager = function(sketcher) {
		this.STATE_NEW_BOND = new states.NewBondState(sketcher);
		this.STATE_NEW_RING = new states.NewRingState(sketcher);
		this.STATE_CHARGE = new states.ChargeState(sketcher);
		this.STATE_LONE_PAIR = new states.LonePairState(sketcher);
		this.STATE_RADICAL = new states.RadicalState(sketcher);
		this.STATE_MOVE = new states.MoveState(sketcher);
		this.STATE_ERASE = new states.EraseState(sketcher);
		this.STATE_LABEL = new states.LabelState(sketcher);
		this.STATE_LASSO = new states.LassoState(sketcher);
		this.STATE_SHAPE = new states.ShapeState(sketcher);
		this.STATE_PUSHER = new states.PusherState(sketcher);
		this.STATE_QUERY = new states.QueryState(sketcher);
		var currentState = this.STATE_NEW_BOND;
		this.setState = function(nextState) {
			if (nextState !== currentState) {
				currentState.exit();
				currentState = nextState;
				currentState.enter();
			}
		};
		this.getCurrentState = function() {
			return currentState;
		};
	};

})(ChemDoodle.uis.states);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

ChemDoodle.uis.gui.imageDepot = (function() {
	'use strict';
	var d = {};
	d.getURI = function(s) {
		return 'data:image/png;base64,' + s;
	};

	d.ADD_LONE_PAIR = 'iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAANElEQVR42mNgGAWjYHACGyB+DMTPgdiFDHkMAFL8H4qfkyFPewNtoApB2IMM+VEwCgYcAADjvBhZpYZJbQAAAABJRU5ErkJggg==';
	d.ADD_RADICAL = 'iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAAL0lEQVR42mNgGAWjYGgAGyB+DMTPgdiFGgaCDPsPxc8HpYE2UINA2GM0BYyCoQAAdQgMLdlWmzIAAAAASUVORK5CYII=';
	d.ANGLE = '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';
	d.ANIMATION = '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';
	d.ARROW_DOWN = 'iVBORw0KGgoAAAANSUhEUgAAAAkAAAAUCAYAAABf2RdVAAAKQ2lDQ1BJQ0MgUHJvZmlsZQAAeAGdlndUU1kTwO97L73QEkKREnoNTUoAkRJ6kV5FJSQBQgkYErBXRAVXFBVpiiKLIi64uhRZK6JYWBQUsC/IIqCsi6uIimVf9Bxl/9j9vrPzx5zfmztz79yZuec8ACi+gUJRJqwAQIZIIg7z8WDGxMYx8d0ABkSAA9YAcHnZWUHh3hEAFT8vDjMbdZKxTKDP+nX/F7jF8g1hMj+b/n+lyMsSS9CdQtCQuXxBNg/lPJTTcyVZMvskyvTENBnDGBmL0QRRVpVx8hc2/+zzhd1kzM8Q8VEfWc5Z/Ay+jDtQ3pIjFaCMBKKcnyMU5KJ8G2X9dGmGEOU3KNMzBNxsADAUmV0i4KWgbIUyRRwRxkF5HgAESvIsTpzFEsEyNE8AOJlZy8XC5BQJ05hnwrR2dGQzfQW56QKJhBXC5aVxxXwmJzMjiytaDsCXO8uigJKstky0yPbWjvb2LBsLtPxf5V8Xv3r9O8h6+8XjZejnnkGMrm+2b7HfbJnVALCn0Nrs+GZLLAOgZRMAqve+2fQPACCfB0DzjVn3YcjmJUUiyXKytMzNzbUQCngWsoJ+lf/p8NXzn2HWeRay877WjukpSOJK0yVMWVF5memZUjEzO4vLEzBZfxtidOv/HDgrrVl5mIcJkgRigQg9KgqdMqEoGW23iC+UCDNFTKHonzr8H8Nm5SDDL3ONAq3mI6AvsQAKN+gA+b0LYGhkgMTvR1egr30LJEYB2cuL1h79Mvcoo+uf9d8UXIR+wtnCZKbMzAmLYPKk4hwZo29CprCABOQBHagBLaAHjAEL2AAH4AzcgBfwB8EgAsSCxYAHUkAGEINcsAqsB/mgEOwAe0A5qAI1oA40gBOgBZwGF8BlcB3cBH3gPhgEI+AZmASvwQwEQXiICtEgNUgbMoDMIBuIDc2HvKBAKAyKhRKgZEgESaFV0EaoECqGyqGDUB30I3QKugBdhXqgu9AQNA79Cb2DEZgC02FN2BC2hNmwOxwAR8CL4GR4KbwCzoO3w6VwNXwMboYvwNfhPngQfgZPIQAhIwxEB2EhbISDBCNxSBIiRtYgBUgJUo00IG1IJ3ILGUQmkLcYHIaGYWJYGGeMLyYSw8MsxazBbMOUY45gmjEdmFuYIcwk5iOWitXAmmGdsH7YGGwyNhebjy3B1mKbsJewfdgR7GscDsfAGeEccL64WFwqbiVuG24frhF3HteDG8ZN4fF4NbwZ3gUfjOfiJfh8fBn+GP4cvhc/gn9DIBO0CTYEb0IcQUTYQCghHCWcJfQSRgkzRAWiAdGJGEzkE5cTi4g1xDbiDeIIcYakSDIiuZAiSKmk9aRSUgPpEukB6SWZTNYlO5JDyULyOnIp+Tj5CnmI/JaiRDGlcCjxFCllO+Uw5TzlLuUllUo1pLpR46gS6nZqHfUi9RH1jRxNzkLOT44vt1auQq5ZrlfuuTxR3kDeXX6x/Ar5EvmT8jfkJxSICoYKHAWuwhqFCoVTCgMKU4o0RWvFYMUMxW2KRxWvKo4p4ZUMlbyU+Ep5SoeULioN0xCaHo1D49E20mpol2gjdBzdiO5HT6UX0n+gd9MnlZWUbZWjlJcpVyifUR5kIAxDhh8jnVHEOMHoZ7xT0VRxVxGobFVpUOlVmVado+qmKlAtUG1U7VN9p8ZU81JLU9up1qL2UB2jbqoeqp6rvl/9kvrEHPoc5zm8OQVzTsy5pwFrmGqEaazUOKTRpTGlqaXpo5mlWaZ5UXNCi6HlppWqtVvrrNa4Nk17vrZQe7f2Oe2nTGWmOzOdWcrsYE7qaOj46kh1Dup068zoGulG6m7QbdR9qEfSY+sl6e3Wa9eb1NfWD9JfpV+vf8+AaMA2SDHYa9BpMG1oZBhtuNmwxXDMSNXIz2iFUb3RA2OqsavxUuNq49smOBO2SZrJPpObprCpnWmKaYXpDTPYzN5MaLbPrMcca+5oLjKvNh9gUVjurBxWPWvIgmERaLHBosXiuaW+ZZzlTstOy49WdlbpVjVW962VrP2tN1i3Wf9pY2rDs6mwuT2XOtd77tq5rXNf2JrZCmz3296xo9kF2W22a7f7YO9gL7ZvsB930HdIcKh0GGDT2SHsbewrjlhHD8e1jqcd3zrZO0mcTjj94cxyTnM+6jw2z2ieYF7NvGEXXReuy0GXwfnM+QnzD8wfdNVx5bpWuz5203Pju9W6jbqbuKe6H3N/7mHlIfZo8pjmOHFWc857Ip4+ngWe3V5KXpFe5V6PvHW9k73rvSd97HxW+pz3xfoG+O70HfDT9OP51flN+jv4r/bvCKAEhAeUBzwONA0UB7YFwUH+QbuCHiwwWCBa0BIMgv2CdwU/DDEKWRrycyguNCS0IvRJmHXYqrDOcFr4kvCj4a8jPCKKIu5HGkdKI9uj5KPio+qipqM9o4ujB2MsY1bHXI9VjxXGtsbh46LiauOmFnot3LNwJN4uPj++f5HRomWLri5WX5y++MwS+SXcJScTsAnRCUcT3nODudXcqUS/xMrESR6Ht5f3jO/G380fF7gIigWjSS5JxUljyS7Ju5LHU1xTSlImhBxhufBFqm9qVep0WnDa4bRP6dHpjRmEjISMUyIlUZqoI1Mrc1lmT5ZZVn7W4FKnpXuWTooDxLXZUPai7FYJHf2Z6pIaSzdJh3Lm51TkvMmNyj25THGZaFnXctPlW5ePrvBe8f1KzEreyvZVOqvWrxpa7b764BpoTeKa9rV6a/PWjqzzWXdkPWl92vpfNlhtKN7wamP0xrY8zbx1ecObfDbV58vli/MHNjtvrtqC2SLc0r117tayrR8L+AXXCq0KSwrfb+Ntu/ad9Xel333anrS9u8i+aP8O3A7Rjv6drjuPFCsWryge3hW0q3k3c3fB7ld7luy5WmJbUrWXtFe6d7A0sLS1TL9sR9n78pTyvgqPisZKjcqtldP7+Pt697vtb6jSrCqsendAeODOQZ+DzdWG1SWHcIdyDj2piarp/J79fV2tem1h7YfDosODR8KOdNQ51NUd1ThaVA/XS+vHj8Ufu/mD5w+tDayGg42MxsLj4Lj0+NMfE37sPxFwov0k+2TDTwY/VTbRmgqaoeblzZMtKS2DrbGtPaf8T7W3Obc1/Wzx8+HTOqcrziifKTpLOpt39tO5Feemzmedn7iQfGG4fUn7/YsxF293hHZ0Xwq4dOWy9+WLne6d5664XDl91enqqWvsay3X7a83d9l1Nf1i90tTt3138w2HG603HW+29czrOdvr2nvhluety7f9bl/vW9DX0x/Zf2cgfmDwDv/O2N30uy/u5dybub/uAfZBwUOFhyWPNB5V/2rya+Og/eCZIc+hrsfhj+8P84af/Zb92/uRvCfUJyWj2qN1YzZjp8e9x28+Xfh05FnWs5mJ/N8Vf698bvz8pz/c/uiajJkceSF+8enPbS/VXh5+ZfuqfSpk6tHrjNcz0wVv1N4cect+2/ku+t3oTO57/PvSDyYf2j4GfHzwKePTp78AA5vz/OzO54oAAAAJcEhZcwAACxMAAAsTAQCanBgAAACFSURBVCgV1VExDoAgDLRoHEhM/IaLT3DyDU5O/n9xV/GuVAeiYabJ5Wh7hdJKCKHKmcsJmC9WJGi+/fgh58KcWmO8g0/gMq7BFJF7QG8awKxOMVpeKzycKRHN8L1uxNbCqztgMeFqvrwiE3L6fH8zdowTUYkoTUTYKI3xIx6j85x/udgF35c6Mkzf7cF3AAAAAElFTkSuQmCC';
	d.ARROW_EQUILIBRIUM = 'iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAAV0lEQVR42mNgGAXDArBQ28AKIL4GxAFAzEMtF/6H4s9AfBKIq4DYBFnRfyrhx0Mj1h5T0ctwYADERUB8FYh/APEvqAKqxCIHEHsA8Xkgbhj0iXgUDAAAAG9tMdQezXJsAAAAAElFTkSuQmCC';
	d.ARROW_RESONANCE = 'iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAAWUlEQVR42mNgGAWjYPACFiBWwSOvAcQc+AzgAWIXIJ4BxP+h2AOPehMkdduAuAiIbaDmMNQgSVIDo7hwERkuPADEFUDsAHMhriBwgIYlNgDynshoUhsFVAIA/dMiIBsQRGUAAAAASUVORK5CYII=';
	d.ARROW_RETROSYNTHETIC = 'iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAAYElEQVR42mNgGI5AhZqGKQDxbSA2oaahBrQ01ILahr4HYhtskv8pwF9xGUouAHn7MRA7UNNQG6hrPahtoA+1vPwc3XWURooPtZLNc2qFG8iw+9Q2zIFahQPV05zG0C59AY3IMME0CTYYAAAAAElFTkSuQmCC';
	d.ARROW_SYNTHETIC = 'iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAAUklEQVR42mNgGAWjgC5ABoh5qGmgAhD/B+I7QJwAxALUMPQg1NAHQPwdiPegG/6fipgssA+q+REQ/wDivUCcAsQi5EYKLAzJNgQ9UmRGE/tIBgDIaCG7b3KulAAAAABJRU5ErkJggg==';
	d.BENZENE = 'iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAA5klEQVR42s2VPwrCMBSH06k0o1MX6R2c7A08hDfRRdDVgp5BR71GvUKdtHNvIMRf4FcoIdgkzeCDD0r+fLyQl1ch3GMmIkUOzuADDkBOEVWgA0ewABfQgjVIQkU5x/vMlqAGD1CGiFKwBS+QcSxhli2znpsyvVAZIh0r8AQ3UFiS0FnvuDczJ9XgW2++g4bSsVC/BiWPt+FxXSRqbEHmmZVyTvtvhTKmUMvesS+lYO0Fl01f2JWlsJvQws75UjpDnPL4Xk/PRdzLSjaGmo3Cq30Nxbp9XXlhXu3LJj6xwe6nNNigX8AXVupH9hGtsNcAAAAASUVORK5CYII=';
	d.BOND_ANY = 'iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAA3UlEQVR42mNgGMRAgJqGeQDxc2oZ6gLE76GGUs0wH2oYZkNNw0Auew3EEQMS/fOB2AGJbwQVg4FsIFYA4glArAkVEwfiemyGqUOjfw2SGMjwv0AcB+V3Qg0CheFaqNhUqF4M0A3E/kB8H4hFkQyciiTWieSyDUCcAhXDAGxAfB6I+YG4C4hLkAwsAuIkIF6GZiCIfgPEfNgMDAPi9UDcCMTNQHwJzUAGqPx5JANB4AKuyNgMxMJI/FVAbI1moAg0jLWIMZCmifY9tRItVfOmBzUNE4AGtgc1sxxVCkcAqLktfrI9I0gAAAAASUVORK5CYII=';
	d.BOND_DOUBLE = 'iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAAQUlEQVR42mNgoA7gZqAicADi+0DMRS3DXgGxPTVcim4YzKVUMwyZP2rYqGGjhg0Dw7ihpQRVDIMBLmoahsulZAEA2GgvCVlTJIIAAAAASUVORK5CYII=';
	d.BOND_DOUBLE_AMBIGUOUS = 'iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAAwElEQVR42q2UsQ2DQBAE38EnBDQAJUBGA6YM04UD3n1AYhdhMkqAHpw4owgkJNbSfuLw904aIZLV3t/tOWdTmTOsGnytRHPwATcLpxcwgYH/HZ0mVwALXY10WqWKtWADDVjBm+0nVUmxO79BadPT0UyxqzrVF4V+b1eoYg+wgyedymIH6FUhzzZ3C7GSA9jYplRxz2YOwCtxCn97VihBn+ioUfesZhYHZnNVEpDxSsQTNDKbJhe3o9PcGVR0WikiJ/j5KxJqecPNAAAAAElFTkSuQmCC';
	d.BOND_HALF = 'iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAAYklEQVR42mNgGAVkAA8g3k4tw0yA+DXUUIqBC9SwGGq57jYQ+1Az7FioYYgCtQwCAQMgvg/EKtQy7DG1YtMB6jKqRUAFtVxGVeBATVeBIuA5tcIMFptUMUyH2rHJAXXh4AIAvQ0O0wCO68MAAAAASUVORK5CYII=';
	d.BOND_PROTRUDING = 'iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAAl0lEQVR42mNgoAwEAHEDA5WAChC/B+L/1DCUBYivQw37Tw1Dl6MZRpGhBTgM+w3EFaQaZgPEn7EY9hiIPUg1TAaI72Mx7DAQK5ATCbuxGNYPxDzkhFs3lvCKoCTxIht2G4g1qJF4QXg9uV5ET7xkJQlciZesJIEr8ZKVJHAl3n5Kwgs58d6mJEmgR8J0SpIENq/yMAx7AADDzz/MOB6JagAAAABJRU5ErkJggg==';
	d.BOND_QUADRUPLE = 'iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAAYElEQVR42mNgIA9wM1AROADxfSDmopZhr4DYnhouRTeMIpdiM4xslxIyjCSXEmMY0S4l1TCYS6lmGDJ/1DAKDeOGxg5VDIMBLmoaRq5LiQJc1DSMWJeSBbioaRgulxIFAGBAR5Vp19YFAAAAAElFTkSuQmCC';
	d.BOND_QUINTUPLE = 'iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAAcElEQVR42q3UsQ2AMAxEUdfJUGb/JdwzBRYChKIkzsV3kguap98QEWxViDv8zK+wsNNPGaUtlirtYdulEQaVrmDLpSg2Ld3BhqVZ7C2lYd93fWQl4fcKE/v/+MbCsqXhE2UsDC2FH1NjYVFpam3pdBfQk0tlqZTZlgAAAABJRU5ErkJggg==';
	d.BOND_RECESSED = 'iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAAQUlEQVR42mNgGGQghJqGBQDxbyBOoaahCYPSUGyaYYYOI28y4PDm4IxNBmp6k2JAE28OPvAdiBuoaaALwyggBQAA+tATdpIiCMcAAAAASUVORK5CYII=';
	d.BOND_RESONANCE = 'iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAAfUlEQVR42mNgoA7gZqAicADi+0DMRS3DXgGx/aA3zAOIt1PLMBMgfg01lGLDXKCGxVArzG4DsQ81I4CFGoYpkGsQNsMMoIlYhVqGPaZWbMKyF9UioIJaLqNqdnIg11W4IuA5uWHGDQ1w9Nj0ocS7sMJRh5LYxAY4oC4kGwAAJbAmYdoaIPoAAAAASUVORK5CYII=';
	d.BOND_SEXTUPLE = 'iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAAZElEQVR42s3OMQ6AMAxDUc/uocr9L8HOKZqBCaXgBiNhKevPA7Q1GLfF7XF0xY647pBeY6+kWawsfYotSZWYLF2N3Uorsam0nZ+6U8o/S/GFFE5pOjpjVak0OmOqtDQ6YzNpugFelEmRwpAbowAAAABJRU5ErkJggg==';
	d.BOND_SINGLE = 'iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAAMklEQVR42mNgGMSAm5qGOQDxfSDmopZhr4DYftSwUcNGDRs1jP6GcUPLM6oYBgNUKRwBiE8XjxDJvZUAAAAASUVORK5CYII=';
	d.BOND_TRIPLE = 'iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAAUUlEQVR42mNgoAxwM1AROADxfSDmopZhr4DYnhouRTeMIpdiM4xslxIyDOZSqhmGzB81bNSwATeMG5rCqWIYDHBR0zBiXUoW4KKmYbhcShIAAA2MPiFy45L3AAAAAElFTkSuQmCC';
	d.BOND_ZERO = 'iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAAH0lEQVR42mNgGAVkgPtQPHgNHA2z0TAbDbPRMBsAAADVkQ3x7nq43wAAAABJRU5ErkJggg==';
	d.BROMINE = 'iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAA3UlEQVR42mNgGAVUB8s0NDYu09T8D8LLNTVvA+lZKzQ1dSg18MRSTU2X5RoarUD2dyC+SKmBu+B8Tc0FQPx3prExK1UMXK6lNRPo2rNIFnQB1UwCikUs09K6DuQXEGXgQg0NYaCGGCD7xVItLX8kA5cD8R1QsADDOBZosCoxBv6F4v9AQxNR5CEGPl2kpydGkpdXqalJA+ksIH4AxLNRDASqITsMgV6yA7kUlnQoNhCYdBRABgINzqTEwINAA+SXq6ubANnrgfgfiE2Jgf+h+C0QrwSKBZMdhqNg4AEAUSSF6Clvq/4AAAAASUVORK5CYII=';
	d.CALCULATE = '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';
	d.CARBON = 'iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAA2klEQVR42mNgGAXYwMyZM1knTZqk1dvbK9vQ0MBEtkETJ06UmzBhwi4g/gXEn4D4L5R9jGTDQK4BanwPxNeArtP///8/4/z58zmAfFcg7iHZQKCmaUD8derUqRIUh9mqVauYgYb9ABlKlUiYPHmyItCw//39/QlUMRBokAPIQFB4UcXAvr4+XaiB4VQxsLu7mxtqYDPVEjM0/b0BJhk+qhgINMgQaOBvID4LS4fAXMIC5JsDw3gCuZFjBDTgPNT7n4H4O1DsApDuoMi1HR0d/MBsaAyiR0s22gMANJ6AxDvp00kAAAAASUVORK5CYII=';
	d.CHARGE_BRACKET = 'iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAAYUlEQVR42mNgGAnACYhF0AX/Q3EDDk0NSGrQwUogNsZmIDEApk4MiOWheAsQ+yLxyTJwIhBfgOIPQHwLiU+WgVT3Mk0NlAZiDkoNXAjEz3Bgil1IkcJRA4e6geSWNkMMAAAxJTQf078zGQAAAABJRU5ErkJggg==';
	d.CHLORINE = 'iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAA9klEQVR42mNgGAXIwPi/MavcZzkt5bfKsgz/GZiQ5RTeKyTIfZBLI8ogpXdKcgofFHbJf5D/BcSfgPgvlH0MpgbIXq7wUWEjQcNArgEqfg/E1xTfK+oDXcao8F+BA+gaV6BYD8kGAhVOA+KvCl8UJAioI8LA/wzMQIU/QIYSYTFhA4GKFIH4PyjAqWIg0CAHkIGg8KKKgTLvZHTBLvygEE4VA8VfiHODDATGcTNVDAR7G5L+3qi8VeGjioFA1xkCFf8G4rOwdAjELEDN5kA8gWQDoYqNgPg82Psf5D8D8XcgvgB0fQdZBiJlQX5gjBuD6NGSjX4AAER4jBfAQ3QdAAAAAElFTkSuQmCC';
	d.CLEAR = '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';
	d.CYCLOBUTANE = 'iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAAiklEQVR42q3UwQ2AIAwF0E7hOv4bTCN7uwIXG0MTYgRa2iYNF3lB+ECkr4MCK3NX7hSBgfvmvtp4RmCCnB4Ug8lbKBaTTCiUH6tQGH9nimJzw3/R7IyEoEluQG0581RpzlvJuUL0KyRnaKd7b0VVB6lFTalYoVsRG6HwHOAXRcQTJmiJwPqcql7sB1sQMyMuYZLDAAAAAElFTkSuQmCC';
	d.CYCLOHEPTANE = 'iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAA+UlEQVR42mNgIB4IMFABMAOxJxCvAeK/QLwAiA3IMUgTiDuB+CkQnwDiDCBWAuIKIH4ExIeAOASIWfAZIgzEmUB8EmpQBxBr4HA1yLCDUMMrgVgcXREPEP8D4pVA7AHVRAzQB+J5UL08yBKKQPyQgrAGuVQBWcARiA9QYCDI+w7IAonQGCQXLATiBGSBRiBuoMBADP0YNpAIMHyIEQYkAow4wIglEgFKKuGFpiMTCgw0gZrBCxMIgdogQYZhElC9IegS1dB8y0GCYRzQrFqNS8FSKCYWLCOkngPqymoiDKsh1kewMAnCoyaY1DA3BOL/BLAhqbHHRY4cAH9nN15emqC1AAAAAElFTkSuQmCC';
	d.CYCLOHEXANE = 'iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAAoElEQVR42mNgIB4IMVAJSADxZCD+A8StQMxFiUH9QPwWiPuA2AiIlwLxYyCOAWJGcg2SQJO3AOITQHwSiC0pMQgZMEJd+Rjqall0BZxA/J8Ig9ABKDwboXo50SX/UxBx/4kWHDVw1EDcemEJu5+aCVsCmlPeEmEwwaxHisGW0ILhBLSgIKn4QjYYVHwtA+JHpBZf2AyeBC1gWygpYMmqAgB+TzRkG9cEtwAAAABJRU5ErkJggg==';
	d.CYCLOOCTANE = 'iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAABBElEQVR42q2UvQ6CMBSFSXS1g4PRzVUXeRGdHH0KWfQlINGB1VcgzJLokxB2B3dIPDWnSYO1QutJvkB/ONxL7yUIumsU/EFLEIECNCAHOyC6GozBBqSgJPJ+DaY0y8CTVzme2MxkFFewBwvLiwXNcj4zM23a0KyvLuBgWkgZWV+FoAKD9kL5I02b7mDbPs3Soxqk2U2fiJiyq4ZMe6UmCpaGj448oHcHNKwz3yao1UB1gI8ScFID1QGuktk99CwF20l4RBe3JzPHtD+i09POHQzPpugC/jUaHn3YMbIzT3b+bdOMjV5p7TQ0GCVMM7aZ6Rpo7VSxaJctI+e6XfEz1Kwzq9ELFZA4hr9lYwQAAAAASUVORK5CYII=';
	d.CYCLOPENTANE = 'iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAA0klEQVR42mNgIB6IMFAJMAFxKRD/BuI0Sg1TAeJDUOwOxFeBeAEQc5Hjqnwgfg3EeUDMCBUHGTQfarAWOa5SxqEmHohfAXECOa7CBbTwBYEWEa7CBpCDwAAmKAjEf6ExyUhm5KUD8R8gFoAJ7AHiIApSA0jvbmSBHKjTyQUgvdnIAvJA/BIaMeQksZdQM1DAJSC2IMNAS6heDNAKxaQCnPpw2kQA4PQZzrDAAwiGPUZsEQCg1DGPUHraQYKBOwmlXx4g/k8i5iFkKxsJLmRjoDUAAID6NQMa+if+AAAAAElFTkSuQmCC';
	d.CYCLOPROPANE = 'iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAAjElEQVR42mNgGE5AgZqGiQDxfyCWoJaBk4H4ORBPp4ZhGkB8G4h5gPg6EBtQauB2IA6Asj2AeD8Qs5BrGDYDkC0gCbDg8CIsCDhINbAATySAIqmC1GRyH08yISRPlgsKiE1GxIYRC7HJiJRYJJiMyElnOB3AQmZOwBlEBRTkVYxIlICWJpRimcFfCgMA4CwtbAP2SjIAAAAASUVORK5CYII=';
	d.DECREASE_CHARGE = 'iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAAs0lEQVR42mNgGAU4gAAQK0BpsgELEGcA8WUg/g3E/6H4NhCXQOVJMmw/EL8G4gYgdoC60AKIK4D4ORAfB2IeYg07DMT3gVgFhxoZID4PNZSDkIE5UJepEFAnA3VpCSEDL0O9SQyogIYp3tj8DQ0zUPhI4sE80DD9jy/2FaAKFKCu/I8H16Opp48LqR6GNIllWDq8jidsZKBqiEqHyIZSJacgG5oDdSly7N6HepNjwEubwQEAQdI454gPA8EAAAAASUVORK5CYII=';
	d.DISTANCE = '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';
	d.ERASE = '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';
	d.FLUORINE = 'iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAAUElEQVR42mNgGAU0AxPuB26c8CDgPxr+SqmBpyc+DDCG4UkP/AwpNXAvtb08+A38A8SfYbj/ob8LpQae6Hvoqw3DU1+F8oxGygAaOAoGDgAAN7dbSHln+I0AAAAASUVORK5CYII=';
	d.HYDROGEN = 'iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAANElEQVR42mNgGAW0BBuBeC8W8SIg/jtqINEGngBiTTTcSYmB/4D4Jxr+Mxopw8jAUTBAAADIhCT11Q14ZwAAAABJRU5ErkJggg==';
	d.INCREASE_CHARGE = 'iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAAyklEQVR42mNgGAU4gAAQK0BpsgELEGcA8WUg/g3E/6H4NhCXQOVJMmw/EL8G4gYgdoC60AKIK4D4ORAfB2IeYg07DMT3gVgFKsYDNRhmgAwQn4caykHIwByoy1SQxCSh3pVEEpOBurSEkIGXoa5hIGAgA9T7twnF5m9omPFADQBhA6iBBkhiPNAw/Y8v9hWgChSgrvyPB9ejqaePC6kehjSJZVg6vI4UNjzQMENOh4eJTYfIhlIlpyAbmgN1KXLs3od6k2PAS5vBAQCFSEECjKrjagAAAABJRU5ErkJggg==';
	d.IODINE = 'iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAAI0lEQVR42mNgGAV0AVMYpmyeyjB196iBowaOGkhXA0cBfQEADcspQU08dAAAAAAASUVORK5CYII=';
	d.LASSO = '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';
	d.LASSO_SHAPES = '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';
	d.MARQUEE = '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';
	d.MOVE = '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';
	d.NITROGEN = 'iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAAoElEQVR42mNgGAU0AwaBPzYaBPx4Z+z7SQRZXN/vlyFQ/L9l6H9Ocgz8b+D/YxY1DfwJxH8N/X6ZUMdAoOuAml8ZBPw8wcDwn5EaLuw09P8eBzJA3/97IlUMBLMDfuwH4pfGof/5qWKgUeBPTWh4TqCKgRD+91Yg/zcsCCg2EGQAkH8PiJ9SxUAQMAz44QlOm9QyEBzLAT/WkGXgKBg4AABF1poQYk+4pAAAAABJRU5ErkJggg==';
	d.OPEN = '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';
	d.OPTIMIZE = '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';
	d.OXYGEN = 'iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAABCklEQVR42mNgGAXYwH8pKa7/PDxa/xkY2CgzSFBQ7j8v7wEg/gPEn4H4JxBvAGJh0g3j4pICanwDxCf+8/MrgcW4uXWB/FtAfA/katIM5OGZCNT44b+AgACKOD+/MtTFxcQbxsDADNTwDWQoVnle3nVAfJt4A/n5FYEa/v/n44vF4fpqoPxfoiMJqMEBbCA3twsOFyaC5aFhS4yBOmANPDwhOAwsAssLCfERG4acQA3/gLgKqzwf33Sg3CvSYpmXdy0Q3wEazoQiLirKAxR/jyvC8BmoAY3pZTCvAcNUHCi2F5w+eXhESU/ckLC8CE13T8Axy8u7D5SDKMuCvLwiwBg1Bnl3tGSjPQAAoX15+BfxFYQAAAAASUVORK5CYII=';
	d.PERIODIC_TABLE = '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';
	d.PERSPECTIVE = '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';
	d.PHOSPHORUS = 'iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAAeElEQVR42mNgGAU0A/8bGDYC8X8o/gLEV/43MqRSZmA9wwkgtgAalA/kXwSy//1vYtCnxIW74PxGBnewa+sZMqhjYD2DCtTAHOoY2MDQAjXQjBIDrwANKALS+4H4D5A9g9JY/gA1bC4Q+1Ej2eyidjocSQaOgoEDAIsaZcCSspZYAAAAAElFTkSuQmCC';
	d.PUSHER_BOND_FORMING = 'iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAABAUlEQVR42mNgQAAdIH4PxJeBOIaBOBABxNeB+DkQm6BLdgNxPxBbAPFjIGYhwsD7QOwAxM1APBld8jRUkgHqSg0ChilAXccA1XceXQHI2TJQ9mwgziFgYAoQL4ayJYD4NbqC/0jeLADi6QQMBHmxBE0/hoHIgb2agIGr0SLvPz7FFtAwxQdOQ9XhjK37aAF+n4gYVsBlBrqBEtBIwgeeQ9XhMoMBW5iCwugaUviA6IdAnEAozHAZ+ARKb4aKrUcylCwDvwLxDyBWgYqB6F9QObIMPAnE89HElwDxH3IN/I7kOgYkV5Jt4HwcchvJNVAFh5wKOQYOLtCAlBQowfVUNxAAeY1sopoKHG8AAAAASUVORK5CYII=';
	d.PUSHER_DOUBLE = 'iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAA8klEQVR42mNgGACgA8QxQBwAxCKUGOQAxKeB+DwQzwfiw0D8HohzyDEM5JrXQBwBxCxI4hpQC7pJ9eJjIDbBIS8BxLeBOIFYA/cDcQUBNSZQS3mICbfbaN7EBZYDcQEhRYuJcB0MeEDDE6flPNBYVCDSQBYCYQ327mUSUwPI2ym4JEuAeDaRBskAsQpUz3SoK1WwhV8JkQaCguc/NOHfg7IxYhyUE1xI8O5zqEEwjAFuY3M2HpAJxP+ghu3FpgAUwwIkGAhS+xdqYC82Bf/JyPM3ofpiqGVgGVSfCbXKSgFsMXwfinHxCYF+dAFKDZRB5gAAj/I5E2fZy9MAAAAASUVORK5CYII=';
	d.PUSHER_SINGLE = 'iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAA6ElEQVR42mNgGACgA8QxQBwAxCKUGOQAxKeB+DwQzwfiw0D8HohzyDEM5JrXQBwBxCxI4hpQC7pJ9eJjIDbBIS8BxLeBOIFYA/cDcQUBNSZQS3mICbfbaN7EBZYDcQEhRYuJcB0MeEDDE6flPNBYVCDSQBYCYQ327mUSUwPI2ym4JEuAeDaJBoL0TIeyZdB9txiqgBTgAU38O4H4P9RQOADlBBcSDVSAGvQPiO+gS4KSiwqJBgpADbyKLSzfQxWQCkAGfsFWcPwnsxAB6duOS4JcA1OoWV7+x1VO3odiUsFxXBLkGoiSdgEanzHP7ILArQAAAABJRU5ErkJggg==';
	d.QUERY = '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';
	d.REDO = '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';
	d.REMOVE_LONE_PAIR = 'iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAAN0lEQVR42mNgGAWjYHACBSDeD8T/gfg4lE+KPAYAKTaBsg2gmkiRxwD/KeTT3oVUD8NRMAoIAAADoBa5tWLP/wAAAABJRU5ErkJggg==';
	d.REMOVE_RADICAL = 'iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAAM0lEQVR42mNgGAWjYGgABSDeD8T/gfg4lE8RABlmAmUbQA2lCPwnwB94F1I9DEfBKEADAAT6C11yCuPwAAAAAElFTkSuQmCC';
	d.SAVE = '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';
	d.SEARCH = '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';
	d.SETTINGS = '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';
	d.SULFUR = 'iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAAwUlEQVR42mNgGAXo4P9/Y7n///Wl//9nYKTQIAP///8NzwIN+w5k/wHir0BsQK6ruICa3wHxEiCbH8I3jPv/X1eJTAP1jICG/Qe6zoxK4WYmDDTwNxDXUzEyDOZBXGkw+/9/cz4qGMjACHIhEP8D4gdkRwgWl1oD8UNoLFtTKy2KAA17DcTzqRimhjuABh4jU7O+IdCAiP//VdihhpkADfsJpJvJdY0z0ICP0KTzDBoxi2EWUJiPDUxBYThaqtEPAAAQY5TwZ4cDHAAAAABJRU5ErkJggg==';
	d.TORSION = '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';
	d.UNDO = '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';
	d.ZOOM_IN = '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';
	d.ZOOM_OUT = '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';

	return d;

})();
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(desktop, imageDepot, q) {
	'use strict';
	desktop.Button = function(id, icon, tooltip, func) {
		this.id = id;
		this.icon = icon;
		this.toggle = false;
		this.tooltip = tooltip ? tooltip : '';
		this.func = func ? func : undefined;
	};
	var _ = desktop.Button.prototype;
	_.getElement = function() {
		return q('#' + this.id);
	};
	_.getSource = function(buttonGroup) {
		var sb = [];
		if (this.toggle) {
			sb.push('<input type="radio" name="');
			sb.push(buttonGroup);
			sb.push('" id="');
			sb.push(this.id);
			sb.push('" title="');
			sb.push(this.tooltip);
			sb.push('" /><label for="');
			sb.push(this.id);
			sb.push('"><img id="');
			sb.push(this.id);
			sb.push('_icon" title="');
			sb.push(this.tooltip);
			sb.push('" width="20" height="20" src="');
			sb.push(imageDepot.getURI(this.icon));
			sb.push('"></label>');
		} else {
			sb.push('<button id="');
			sb.push(this.id);
			sb.push('" onclick="return false;" title="');
			sb.push(this.tooltip);
			sb.push('"><img title="');
			sb.push(this.tooltip);
			sb.push('" width="20" height="20" src="');
			sb.push(imageDepot.getURI(this.icon));
			sb.push('"></button>');
		}
		return sb.join('');
	};
	_.setup = function(lone) {
		var element = this.getElement();
		if (!this.toggle || lone) {
			element.button();
		}
		element.click(this.func);
	};
	_.disable = function() {
		var element = this.getElement();
		element.mouseout();
		element.button('disable');
	};
	_.enable = function() {
		this.getElement().button('enable');
	};
	_.select = function() {
		var element = this.getElement();
		element.attr('checked', true);
		element.button('refresh');
	};

})(ChemDoodle.uis.gui.desktop, ChemDoodle.uis.gui.imageDepot, ChemDoodle.lib.jQuery);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//
(function(desktop, q) {
	'use strict';
	desktop.ButtonSet = function(id) {
		this.id = id;
		this.buttons = [];
		this.toggle = true;
	};
	var _ = desktop.ButtonSet.prototype;
	_.getElement = function() {
		return q('#' + this.id);
	};
	_.getSource = function(buttonGroup) {
		var sb = [];
		sb.push('<span id="');
		sb.push(this.id);
		sb.push('">');
		for ( var i = 0, ii = this.buttons.length; i < ii; i++) {
			if (this.toggle) {
				this.buttons[i].toggle = true;
			}
			sb.push(this.buttons[i].getSource(buttonGroup));
		}
		if (this.dropDown) {
			sb.push(this.dropDown.getButtonSource());
		}
		sb.push('</span>');
		if (this.dropDown) {
			sb.push(this.dropDown.getHiddenSource());
		}
		return sb.join('');
	};
	_.setup = function() {
		this.getElement().buttonset();
		for ( var i = 0, ii = this.buttons.length; i < ii; i++) {
			this.buttons[i].setup(false);
		}
		if (this.dropDown) {
			this.dropDown.setup();
		}
	};
	_.addDropDown = function(tooltip) {
		this.dropDown = new desktop.DropDown(this.id + '_dd', tooltip, this.buttons[this.buttons.length - 1]);
	};
	
	_.disable = function() {
		for (var i = 0, ii = this.buttons.length; i < ii; i++) {
			this.buttons[i].disable();
		}
	};
	
	_.enable = function() {
		for (var i = 0, ii = this.buttons.length; i < ii; i++) {
			this.buttons[i].enable();
		}
	};

})(ChemDoodle.uis.gui.desktop, ChemDoodle.lib.jQuery);
//
// Copyright 2009-2015 iChemLabs, LLC. All rights reserved.
//
(function(desktop, q) {
	'use strict';
	desktop.CheckBox = function(id, tooltip, func, checked) {
		this.id = id;
		this.checked = checked ? checked : false;
		this.tooltip = tooltip ? tooltip : '';
		this.func = func ? func : undefined;
	};
	var _ = desktop.CheckBox.prototype = new desktop.Button();
	_.getSource = function() {
		var sb = [];
		sb.push('<input type="checkbox" id="');
		sb.push(this.id);
		sb.push('" ');
		if (this.checked) {
			sb.push('checked="" ');
		}
		sb.push('><label for="');
		sb.push(this.id);
		sb.push('">');
		sb.push(this.tooltip);
		sb.push('</label>');
		return sb.join('');
	};
	_.setup = function() {
		this.getElement().click(this.func);
	};
	
	_.check = function() {
		this.checked = true;
		this.getElement().prop('checked', true);
	};
	
	_.uncheck = function() {
		this.checked = false;
		this.getElement().removeAttr('checked');
	};
})(ChemDoodle.uis.gui.desktop, ChemDoodle.lib.jQuery);
//
// Copyright 2009-2015 iChemLabs, LLC. All rights reserved.
//
(function(desktop, q) {
	'use strict';
	desktop.ColorPicker = function (id, tooltip, func) {
		this.id = id;
		this.tooltip = tooltip ? tooltip : '';
		this.func = func ? func : undefined;
	};
	var _ = desktop.ColorPicker.prototype;
	_.getElement = function() {
		return q('#' + this.id);
	};
	_.getSource = function() {
		var sb = [];
		sb.push('<table style="font-size:12px;text-align:left;border-spacing:0px"><tr><td><p>');
		sb.push(this.tooltip);
		sb.push('</p></td><td><input id="');
		sb.push(this.id);
		sb.push('" class="simple_color" value="#000000" /></td></tr></table>');
		return sb.join('');
	};
	_.setup = function() {
		this.getElement().simpleColor({
			boxWidth : 20,
			livePreview : true,
			chooserCSS: { 'z-index' : '900'},
			onSelect : this.func });
	};
	_.setColor = function(color) {
		this.getElement().setColor(color);
	};
})(ChemDoodle.uis.gui.desktop, ChemDoodle.lib.jQuery);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(desktop, q, document) {
	'use strict';
	desktop.Dialog = function(sketcherid, subid, title) {
		// sketcherid is the DOM element id everything will be anchored around
		// when adding dynamically.
		this.sketcherid = sketcherid;
		this.id = sketcherid + subid;
		this.title = title ? title : 'Information';
	};
	var _ = desktop.Dialog.prototype;
	_.buttons = undefined;
	_.message = undefined;
	_.afterMessage = undefined;
	_.includeTextArea = false;
	_.includeTextField = false;
	_.getElement = function() {
		return q('#' + this.id);
	};
	_.getTextArea = function() {
		return q('#' + this.id + '_ta');
	};
	_.getTextField = function() {
		return q('#' + this.id + '_tf');
	};
	_.setup = function() {
		var sb = [];
		sb.push('<div style="font-size:12px;" id="');
		sb.push(this.id);
		sb.push('" title="');
		sb.push(this.title);
		sb.push('">');
		if (this.message) {
			sb.push('<p>');
			sb.push(this.message);
			sb.push('</p>');
		}
		if (this.includeTextField) {
			sb.push('<input type="text" style="font-family:\'Courier New\';" id="');
			sb.push(this.id);
			sb.push('_tf" autofocus></input>');
		}
		if (this.includeTextArea) {
			sb.push('<textarea style="font-family:\'Courier New\';" id="');
			sb.push(this.id);
			sb.push('_ta" cols="55" rows="10"></textarea>');
		}
		if (this.afterMessage) {
			sb.push('<p>');
			sb.push(this.afterMessage);
			sb.push('</p>');
		}
		sb.push('</div>');
		if (document.getElementById(this.sketcherid)) {
			var canvas = q('#' + this.sketcherid);
			canvas.before(sb.join(''));
		} else {
			document.writeln(sb.join(''));
		}
		var self = this;
		this.getElement().dialog({
			autoOpen : false,
			width : 435,
			buttons : self.buttons
		});
	};

})(ChemDoodle.uis.gui.desktop, ChemDoodle.lib.jQuery, document);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(c, structures, actions, desktop, q, document) {
	'use strict';

	var makeRow = function(id, name, tag, description, component) {
		var sb = ['<tr>'];
		// checkbox for include
		sb.push('<td>');
		if(id.indexOf('_elements')===-1){
			sb.push('<input type="checkbox" id="');
			sb.push(id);
			sb.push('_include">');
		}
		sb.push('</td>');
		// name and tag
		sb.push('<td>');
		sb.push(name);
		if(tag){
			sb.push('<br>(<strong>');
			sb.push(tag);
			sb.push('</strong>)');
		}
		sb.push('</td>');
		// component
		sb.push('<td style="padding-left:20px;padding-right:20px;">');
		sb.push(description);
		if(component){
			if(component===1){
				sb.push('<br>');
				sb.push('<input type="text" id="');
				sb.push(id);
				sb.push('_value">');
			}else{
				sb.push(component);
			}
		}
		sb.push('</td>');
		// checkbox for not
		sb.push('<td><input type="checkbox" id="');
		sb.push(id);
		sb.push('_not"><br><strong>NOT</strong>');
		sb.push('</td>');
		// close
		sb.push('</tr>');
		return sb.join('');
	};
	
	desktop.AtomQueryDialog = function(sketcher, subid) {
		this.sketcher = sketcher;
		this.id = sketcher.id + subid;
	};
	var _ = desktop.AtomQueryDialog.prototype = new desktop.Dialog();
	_.title = 'Atom Query';
	_.setAtom = function(a) {
		this.a = a;
		var use = a.query;
		if(!use){
			use = new structures.Query(structures.Query.TYPE_ATOM);
			use.elements.v.push(a.label);
		}
		for(var i = 0, ii = this.periodicTable.cells.length; i<ii; i++){
			this.periodicTable.cells[i].selected = use.elements.v.indexOf(this.periodicTable.cells[i].element.symbol)!==-1;
		}
		this.periodicTable.repaint();
		q('#'+this.id+'_el_any').prop("checked", use.elements.v.indexOf('a')!==-1);
		q('#'+this.id+'_el_noth').prop("checked", use.elements.v.indexOf('r')!==-1);
		q('#'+this.id+'_el_het').prop("checked", use.elements.v.indexOf('q')!==-1);
		q('#'+this.id+'_el_hal').prop("checked", use.elements.v.indexOf('x')!==-1);
		q('#'+this.id+'_el_met').prop("checked", use.elements.v.indexOf('m')!==-1);
		q('#'+this.id+'_elements_not').prop("checked", use.elements.not);
		
		q('#'+this.id+'_aromatic_include').prop("checked", use.aromatic!==undefined);
		q('#'+this.id+'_aromatic_not').prop("checked", use.aromatic!==undefined&&use.aromatic.not);
		q('#'+this.id+'_charge_include').prop("checked", use.charge!==undefined);
		q('#'+this.id+'_charge_value').val(use.charge?use.outputRange(use.charge.v):'');
		q('#'+this.id+'_charge_not').prop("checked", use.charge!==undefined&&use.charge.not);
		q('#'+this.id+'_hydrogens_include').prop("checked", use.hydrogens!==undefined);
		q('#'+this.id+'_hydrogens_value').val(use.hydrogens?use.outputRange(use.hydrogens.v):'');
		q('#'+this.id+'_hydrogens_not').prop("checked", use.charge!==undefined&&use.charge.not);
		q('#'+this.id+'_ringCount_include').prop("checked", use.ringCount!==undefined);
		q('#'+this.id+'_ringCount_value').val(use.ringCount?use.outputRange(use.ringCount.v):'');
		q('#'+this.id+'_ringCount_not').prop("checked", use.ringCount!==undefined&&use.ringCount.not);
		q('#'+this.id+'_saturation_include').prop("checked", use.saturation!==undefined);
		q('#'+this.id+'_saturation_not').prop("checked", use.saturation!==undefined&&use.saturation.not);
		q('#'+this.id+'_connectivity_include').prop("checked", use.connectivity!==undefined);
		q('#'+this.id+'_connectivity_value').val(use.connectivity?use.outputRange(use.connectivity.v):'');
		q('#'+this.id+'_connectivity_not').prop("checked", use.connectivity!==undefined&&use.connectivity.not);
		q('#'+this.id+'_connectivityNoH_include').prop("checked", use.connectivityNoH!==undefined);
		q('#'+this.id+'_connectivityNoH_value').val(use.connectivityNoH?use.outputRange(use.connectivityNoH.v):'');
		q('#'+this.id+'_connectivityNoH_not').prop("checked", use.connectivityNoH!==undefined&&use.connectivityNoH.not);
		q('#'+this.id+'_chirality_include').prop("checked", use.chirality!==undefined);
		if(!use.chirality || use.chirality.v === 'R'){
			q('#'+this.id+'_chiral_r').prop('checked', true).button('refresh');
		}else if(!use.chirality || use.chirality.v === 'S'){
			q('#'+this.id+'_chiral_s').prop('checked', true).button('refresh');
		}else if(!use.chirality || use.chirality.v === 'A'){
			q('#'+this.id+'_chiral_a').prop('checked', true).button('refresh');
		}
		q('#'+this.id+'_chirality_not').prop("checked", use.chirality!==undefined&&use.chirality.not);
	};
	_.setup = function() {
		var sb = [];
		sb.push('<div style="font-size:12px;text-align:center;height:300px;overflow-y:scroll;" id="');
		sb.push(this.id);
		sb.push('" title="');
		sb.push(this.title);
		sb.push('">');
		sb.push('<p>Set the following form to define the atom query.</p>');
		sb.push('<table>');
		sb.push(makeRow(this.id+'_elements', 'Identity', undefined, 'Select any number of elements and/or wildcards.', '<canvas class="ChemDoodleWebComponent" id="'+this.id+'_pt"></canvas><br><input type="checkbox" id="'+this.id+'_el_any">Any (a)<input type="checkbox" id="'+this.id+'_el_noth">!Hydrogen (r)<input type="checkbox" id="'+this.id+'_el_het">Heteroatom (q)<br><input type="checkbox" id="'+this.id+'_el_hal">Halide (x)<input type="checkbox" id="'+this.id+'_el_met">Metal (m)'));
		sb.push('<tr><td colspan="4"><hr style="width:100%"></td></tr>');
		sb.push(makeRow(this.id+'_aromatic', 'Aromatic', 'A', 'Specifies that the matched atom should be aromatic. Use the NOT modifier to specify not aromatic or anti-aromatic.'));
		sb.push(makeRow(this.id+'_charge', 'Charge', 'C', 'Defines the allowed charge for the matched atom.', 1));
		sb.push(makeRow(this.id+'_hydrogens', 'Hydrogens', 'H', 'Defines the total number of hydrogens attached to the atom, implicit and explicit.', 1));
		sb.push(makeRow(this.id+'_ringCount', 'Ring Count', 'R', 'Defines the total number of rings this atom is a member of. (SSSR)', 1));
		sb.push(makeRow(this.id+'_saturation', 'Saturation', 'S', 'Specifies that the matched atom should be saturated. Use the NOT modifier to specify unsaturation.'));
		sb.push(makeRow(this.id+'_connectivity', 'Connectivity', 'X', 'Defines the total number of bonds connected to the atom, including all hydrogens.', 1));
		sb.push(makeRow(this.id+'_connectivityNoH', 'Connectivity (No H)', 'x', 'Defines the total number of bonds connected to the atom, excluding all hydrogens.', 1));
		sb.push(makeRow(this.id+'_chirality', 'Chirality', '@', 'Defines the stereochemical configuration of the atom.', '<div id="'+this.id+'_radio"><input type="radio" id="'+this.id+'_chiral_a" name="radio"><label for="'+this.id+'_chiral_a">Any (A)</label><input type="radio" id="'+this.id+'_chiral_r" name="radio"><label for="'+this.id+'_chiral_r">Rectus (R)</label><input type="radio" id="'+this.id+'_chiral_s" name="radio"><label for="'+this.id+'_chiral_s">Sinestra (S)</label></div>'));
		sb.push('</table>');
		sb.push('</div>');
		if (document.getElementById(this.id)) {
			var canvas = q('#' + this.id);
			canvas.before(sb.join(''));
		} else {
			document.writeln(sb.join(''));
		}
		this.periodicTable = new c.PeriodicTableCanvas(this.id + '_pt', 16);
		this.periodicTable.allowMultipleSelections = true;
		this.periodicTable.drawCell = function(ctx, specs, cell){
		    //if hovered, then show a red background
		    if(this.hovered===cell){
		      ctx.fillStyle='blue';
		      ctx.fillRect(cell.x, cell.y, cell.dimension, cell.dimension);
		    }else if(cell.selected){
			    ctx.fillStyle='#c10000';
			    ctx.fillRect(cell.x, cell.y, cell.dimension, cell.dimension);
			}
		    //draw the main cells
		    ctx.strokeStyle='black';
		    ctx.strokeRect(cell.x, cell.y, cell.dimension, cell.dimension);
		    ctx.font = '10px Sans-serif';
		    ctx.fillStyle='black';
		    ctx.textAlign = 'center';
		    ctx.textBaseline = 'middle';
		    ctx.fillText(cell.element.symbol, cell.x+cell.dimension/2, cell.y+cell.dimension/2);
		};
		this.periodicTable.repaint();
		var self = this;
		function setNewQuery(){
			var query = new structures.Query(structures.Query.TYPE_ATOM);
			
			if(q('#'+self.id+'_el_any').is(':checked')){
				query.elements.v.push('a');
			}
			if(q('#'+self.id+'_el_noth').is(':checked')){
				query.elements.v.push('r');
			}
			if(q('#'+self.id+'_el_het').is(':checked')){
				query.elements.v.push('q');
			}
			if(q('#'+self.id+'_el_hal').is(':checked')){
				query.elements.v.push('x');
			}
			if(q('#'+self.id+'_el_met').is(':checked')){
				query.elements.v.push('m');
			}
			for(var i = 0, ii = self.periodicTable.cells.length; i<ii; i++){
				if(self.periodicTable.cells[i].selected){
					query.elements.v.push(self.periodicTable.cells[i].element.symbol);
				}
			}
			if(q('#'+self.id+'_elements_not').is(':checked')){
				query.elements.not = true;
			}
			
			if(q('#'+self.id+'_aromatic_include').is(':checked')){
				query.aromatic = {v:true,not:q('#'+self.id+'_aromatic_not').is(':checked')};
			}
			if(q('#'+self.id+'_charge_include').is(':checked')){
				query.charge = {v:query.parseRange(q('#'+self.id+'_charge_value').val()),not:q('#'+self.id+'_charge_not').is(':checked')};
			}
			if(q('#'+self.id+'_hydrogens_include').is(':checked')){
				query.hydrogens = {v:query.parseRange(q('#'+self.id+'_hydrogens_value').val()),not:q('#'+self.id+'_hydrogens_not').is(':checked')};
			}
			if(q('#'+self.id+'_ringCount_include').is(':checked')){
				query.ringCount = {v:query.parseRange(q('#'+self.id+'_ringCount_value').val()),not:q('#'+self.id+'_ringCount_not').is(':checked')};
			}
			if(q('#'+self.id+'_saturation_include').is(':checked')){
				query.saturation = {v:true,not:q('#'+self.id+'_saturation_not').is(':checked')};
			}
			if(q('#'+self.id+'_connectivity_include').is(':checked')){
				query.connectivity = {v:query.parseRange(q('#'+self.id+'_connectivity_value').val()),not:q('#'+self.id+'_connectivity_not').is(':checked')};
			}
			if(q('#'+self.id+'_connectivityNoH_include').is(':checked')){
				query.connectivityNoH = {v:query.parseRange(q('#'+self.id+'_connectivityNoH_value').val()),not:q('#'+self.id+'_connectivityNoH_not').is(':checked')};
			}
			if(q('#'+self.id+'_chirality_include').is(':checked')){
				var val = 'R';
				if(q('#'+self.id+'_chiral_a').is(':checked')){
					val = 'A';
				}else if(q('#'+self.id+'_chiral_s').is(':checked')){
					val = 'S';
				}
				query.chirality = {v:val,not:q('#'+self.id+'_chirity_not').is(':checked')};
			}
			
			self.sketcher.historyManager.pushUndo(new actions.ChangeQueryAction(self.a, query));
			q(this).dialog('close');
		};
		q('#'+this.id+'_radio').buttonset();
		var self = this;
		this.getElement().dialog({
			autoOpen : false,
			width : 500,
			height: 300,
			buttons : {
				'Cancel' : function(){q(this).dialog('close');},
				'Remove' : function(){self.sketcher.historyManager.pushUndo(new actions.ChangeQueryAction(self.a));q(this).dialog('close');},
				'Set' : setNewQuery
			},
			open : function(event, ui) {
				q("#"+self.id).animate({ scrollTop: 0 }, "fast");
			}
		});
	};

})(ChemDoodle, ChemDoodle.structures, ChemDoodle.uis.actions, ChemDoodle.uis.gui.desktop, ChemDoodle.lib.jQuery, document);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(c, structures, actions, desktop, imageDepot, q, document) {
	'use strict';

	var makeRow = function(id, name, tag, description, component) {
		var sb = ['<tr>'];
		// checkbox for include
		sb.push('<td>');
		if(id.indexOf('_orders')===-1){
			sb.push('<input type="checkbox" id="');
			sb.push(id);
			sb.push('_include">');
		}
		sb.push('</td>');
		// name and tag
		sb.push('<td>');
		sb.push(name);
		if(tag){
			sb.push('<br>(<strong>');
			sb.push(tag);
			sb.push('</strong>)');
		}
		sb.push('</td>');
		// component
		sb.push('<td style="padding-left:20px;padding-right:20px;">');
		sb.push(description);
		if(component){
			if(component===1){
				sb.push('<br>');
				sb.push('<input type="text" id="');
				sb.push(id);
				sb.push('_value">');
			}else{
				sb.push(component);
			}
		}
		sb.push('</td>');
		// checkbox for not
		sb.push('<td><input type="checkbox" id="');
		sb.push(id);
		sb.push('_not"><br><strong>NOT</strong>');
		sb.push('</td>');
		// close
		sb.push('</tr>');
		return sb.join('');
	};
	
	desktop.BondQueryDialog = function(sketcher, subid) {
		this.sketcher = sketcher;
		this.id = sketcher.id + subid;
	};
	var _ = desktop.BondQueryDialog.prototype = new desktop.Dialog();
	_.title = 'Bond Query';
	_.setBond = function(b) {
		this.b = b;
		var use = b.query;
		if(!use){
			use = new structures.Query(structures.Query.TYPE_BOND);
			switch(b.bondOrder){
			case 0:
				use.orders.v.push('0');
				break;
			case 0.5:
				use.orders.v.push('h');
				break;
			case 1:
				use.orders.v.push('1');
				break;
			case 1.5:
				use.orders.v.push('r');
				break;
			case 2:
				use.orders.v.push('2');
				break;
			case 3:
				use.orders.v.push('3');
				break;
			}
		}
		
		q('#'+this.id+'_type_0').prop("checked", use.orders.v.indexOf('0')!==-1).button('refresh');
		q('#'+this.id+'_type_1').prop("checked", use.orders.v.indexOf('1')!==-1).button('refresh');
		q('#'+this.id+'_type_2').prop("checked", use.orders.v.indexOf('2')!==-1).button('refresh');
		q('#'+this.id+'_type_3').prop("checked", use.orders.v.indexOf('3')!==-1).button('refresh');
		q('#'+this.id+'_type_4').prop("checked", use.orders.v.indexOf('4')!==-1).button('refresh');
		q('#'+this.id+'_type_5').prop("checked", use.orders.v.indexOf('5')!==-1).button('refresh');
		q('#'+this.id+'_type_6').prop("checked", use.orders.v.indexOf('6')!==-1).button('refresh');
		q('#'+this.id+'_type_h').prop("checked", use.orders.v.indexOf('h')!==-1).button('refresh');
		q('#'+this.id+'_type_r').prop("checked", use.orders.v.indexOf('r')!==-1).button('refresh');
		q('#'+this.id+'_type_a').prop("checked", use.orders.v.indexOf('a')!==-1).button('refresh');
		q('#'+this.id+'_orders_not').prop("checked", use.orders.not);
		
		q('#'+this.id+'_aromatic_include').prop("checked", use.aromatic!==undefined);
		q('#'+this.id+'_aromatic_not').prop("checked", use.aromatic!==undefined&&use.aromatic.not);
		q('#'+this.id+'_ringCount_include').prop("checked", use.ringCount!==undefined);
		q('#'+this.id+'_ringCount_value').val(use.ringCount?use.outputRange(use.ringCount.v):'');
		q('#'+this.id+'_ringCount_not').prop("checked", use.ringCount!==undefined&&use.ringCount.not);
		q('#'+this.id+'_stereo_include').prop("checked", use.stereo!==undefined);
		if(!use.stereo || use.stereo.v === 'E'){
			q('#'+this.id+'_stereo_e').prop('checked', true).button('refresh');
		}else if(!use.stereo || use.stereo.v === 'Z'){
			q('#'+this.id+'_stereo_z').prop('checked', true).button('refresh');
		}else if(!use.stereo || use.stereo.v === 'A'){
			q('#'+this.id+'_stereo_a').prop('checked', true).button('refresh');
		}
		q('#'+this.id+'_stereo_not').prop("checked", use.stereo!==undefined&&use.stereo.not);
	};
	_.setup = function() {
		var sb = [];
		sb.push('<div style="font-size:12px;text-align:center;height:300px;overflow-y:scroll;" id="');
		sb.push(this.id);
		sb.push('" title="');
		sb.push(this.title);
		sb.push('">');
		sb.push('<p>Set the following form to define the bond query.</p>');
		sb.push('<table>');
		sb.push(makeRow(this.id+'_orders', 'Identity', undefined, 'Select any number of bond types.', '<div id="'+this.id+'_radioTypes"><input type="checkbox" id="'+this.id+'_type_0"><label for="'+this.id+'_type_0"><img width="20" height="20" src="'+imageDepot.getURI(imageDepot.BOND_ZERO)+'" /></label><input type="checkbox" id="'+this.id+'_type_1"><label for="'+this.id+'_type_1"><img width="20" height="20" src="'+imageDepot.getURI(imageDepot.BOND_SINGLE)+'" /></label><input type="checkbox" id="'+this.id+'_type_2"><label for="'+this.id+'_type_2"><img width="20" height="20" src="'+imageDepot.getURI(imageDepot.BOND_DOUBLE)+'" /></label><input type="checkbox" id="'+this.id+'_type_3"><label for="'+this.id+'_type_3"><img width="20" height="20" src="'+imageDepot.getURI(imageDepot.BOND_TRIPLE)+'" /></label><input type="checkbox" id="'+this.id+'_type_4"><label for="'+this.id+'_type_4"><img width="20" height="20" src="'+imageDepot.getURI(imageDepot.BOND_QUADRUPLE)+'" /></label><input type="checkbox" id="'+this.id+'_type_5"><label for="'+this.id+'_type_5"><img width="20" height="20" src="'+imageDepot.getURI(imageDepot.BOND_QUINTUPLE)+'" /></label><input type="checkbox" id="'+this.id+'_type_6"><label for="'+this.id+'_type_6"><img width="20" height="20" src="'+imageDepot.getURI(imageDepot.BOND_SEXTUPLE)+'" /></label><input type="checkbox" id="'+this.id+'_type_h"><label for="'+this.id+'_type_h"><img width="20" height="20" src="'+imageDepot.getURI(imageDepot.BOND_HALF)+'" /></label><input type="checkbox" id="'+this.id+'_type_r"><label for="'+this.id+'_type_r"><img width="20" height="20" src="'+imageDepot.getURI(imageDepot.BOND_RESONANCE)+'" /></label><input type="checkbox" id="'+this.id+'_type_a"><label for="'+this.id+'_type_a"><img width="20" height="20" src="'+imageDepot.getURI(imageDepot.BOND_ANY)+'" /></label></div>'));
		sb.push('<tr><td colspan="4"><hr style="width:100%"></td></tr>');
		sb.push(makeRow(this.id+'_aromatic', 'Aromatic', 'A', 'Specifies that the matched bond should be aromatic. Use the NOT modifier to specify not aromatic or anti-aromatic.'));
		sb.push(makeRow(this.id+'_ringCount', 'Ring Count', 'R', 'Defines the total number of rings this bond is a member of. (SSSR)', 1));
		sb.push(makeRow(this.id+'_stereo', 'Stereochemistry', '@', 'Defines the stereochemical configuration of the bond.', '<div id="'+this.id+'_radio"><input type="radio" id="'+this.id+'_stereo_a" name="radio"><label for="'+this.id+'_stereo_a">Any (A)</label><input type="radio" id="'+this.id+'_stereo_e" name="radio"><label for="'+this.id+'_stereo_e">Entgegen (E)</label><input type="radio" id="'+this.id+'_stereo_z" name="radio"><label for="'+this.id+'_stereo_z">Zusammen (Z)</label></div>'));
		sb.push('</table>');
		sb.push('</div>');
		if (document.getElementById(this.id)) {
			var canvas = q('#' + this.id);
			canvas.before(sb.join(''));
		} else {
			document.writeln(sb.join(''));
		}
		var self = this;
		function setNewQuery(){
			var query = new structures.Query(structures.Query.TYPE_BOND);

			if(q('#'+self.id+'_type_0').is(':checked')){
				query.orders.v.push('0');
			}
			if(q('#'+self.id+'_type_1').is(':checked')){
				query.orders.v.push('1');
			}
			if(q('#'+self.id+'_type_2').is(':checked')){
				query.orders.v.push('2');
			}
			if(q('#'+self.id+'_type_3').is(':checked')){
				query.orders.v.push('3');
			}
			if(q('#'+self.id+'_type_4').is(':checked')){
				query.orders.v.push('4');
			}
			if(q('#'+self.id+'_type_5').is(':checked')){
				query.orders.v.push('5');
			}
			if(q('#'+self.id+'_type_6').is(':checked')){
				query.orders.v.push('6');
			}
			if(q('#'+self.id+'_type_h').is(':checked')){
				query.orders.v.push('h');
			}
			if(q('#'+self.id+'_type_r').is(':checked')){
				query.orders.v.push('r');
			}
			if(q('#'+self.id+'_type_a').is(':checked')){
				query.orders.v.push('a');
			}
			if(q('#'+self.id+'_orders_not').is(':checked')){
				query.orders.not = true;
			}
			
			if(q('#'+self.id+'_aromatic_include').is(':checked')){
				query.aromatic = {v:true,not:q('#'+self.id+'_aromatic_not').is(':checked')};
			}
			if(q('#'+self.id+'_ringCount_include').is(':checked')){
				query.ringCount = {v:query.parseRange(q('#'+self.id+'_ringCount_value').val()),not:q('#'+self.id+'_ringCount_not').is(':checked')};
			}
			if(q('#'+self.id+'_stereo_include').is(':checked')){
				var val = 'E';
				if(q('#'+self.id+'_stereo_a').is(':checked')){
					val = 'A';
				}else if(q('#'+self.id+'_stereo_z').is(':checked')){
					val = 'Z';
				}
				query.stereo = {v:val,not:q('#'+self.id+'_stereo_not').is(':checked')};
			}
			
			self.sketcher.historyManager.pushUndo(new actions.ChangeQueryAction(self.b, query));
			q(this).dialog('close');
		};
		q('#'+this.id+'_radioTypes').buttonset();
		q('#'+this.id+'_radio').buttonset();
		this.getElement().dialog({
			autoOpen : false,
			width : 520,
			height: 300,
			buttons : {
				'Cancel' : function(){q(this).dialog('close');},
				'Remove' : function(){self.sketcher.historyManager.pushUndo(new actions.ChangeQueryAction(self.b));q(this).dialog('close');},
				'Set' : setNewQuery
			},
			open : function(event, ui) {
				q("#"+self.id).animate({ scrollTop: 0 }, "fast");
			}
		});
	};

})(ChemDoodle, ChemDoodle.structures, ChemDoodle.uis.actions, ChemDoodle.uis.gui.desktop, ChemDoodle.uis.gui.imageDepot, ChemDoodle.lib.jQuery, document);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(c, desktop, q, document) {
	'use strict';
	desktop.MolGrabberDialog = function(sketcherid, subid) {
		this.sketcherid = sketcherid;
		this.id = sketcherid + subid;
	};
	var _ = desktop.MolGrabberDialog.prototype = new desktop.Dialog();
	_.title = 'MolGrabber';
	_.setup = function() {
		var sb = [];
		sb.push('<div style="font-size:12px;text-align:center;" id="');
		sb.push(this.id);
		sb.push('" title="');
		sb.push(this.title);
		sb.push('">');
		if (this.message) {
			sb.push('<p>');
			sb.push(this.message);
			sb.push('</p>');
		}
		// Next is the MolGrabberCanvas, whose constructor will be called AFTER
		// the elements are in the DOM.
		sb.push('<canvas class="ChemDoodleWebComponent" id="');
		sb.push(this.id);
		sb.push('_mg"></canvas>');
		if (this.afterMessage) {
			sb.push('<p>');
			sb.push(this.afterMessage);
			sb.push('</p>');
		}
		sb.push('</div>');
		if (document.getElementById(this.sketcherid)) {
			var canvas = q('#' + this.sketcherid);
			canvas.before(sb.join(''));
		} else {
			document.writeln(sb.join(''));
		}
		this.canvas = new c.MolGrabberCanvas(this.id + '_mg', 200, 200);
		this.canvas.specs.backgroundColor = '#fff';
		this.canvas.repaint();
		var self = this;
		this.getElement().dialog({
			autoOpen : false,
			width : 250,
			buttons : self.buttons
		});
	};

})(ChemDoodle, ChemDoodle.uis.gui.desktop, ChemDoodle.lib.jQuery, document);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(c, desktop, q, document) {
	'use strict';
	desktop.PeriodicTableDialog = function(sketcherid, subid) {
		this.sketcherid = sketcherid;
		this.id = sketcherid + subid;
	};
	var _ = desktop.PeriodicTableDialog.prototype = new desktop.Dialog();
	_.title = 'Periodic Table';
	_.setup = function() {
		var sb = [];
		sb.push('<div style="text-align:center;" id="');
		sb.push(this.id);
		sb.push('" title="');
		sb.push(this.title);
		sb.push('">');
		sb.push('<canvas class="ChemDoodleWebComponents" id="');
		sb.push(this.id);
		sb.push('_pt"></canvas></div>');
		if (document.getElementById(this.sketcherid)) {
			var canvas = q('#' + this.sketcherid);
			canvas.before(sb.join(''));
		} else {
			document.writeln(sb.join(''));
		}
		this.canvas = new ChemDoodle.PeriodicTableCanvas(this.id + '_pt', 20);
		var self = this;
		this.getElement().dialog({
			autoOpen : false,
			width : 400,
			buttons : self.buttons
		});
	};

})(ChemDoodle, ChemDoodle.uis.gui.desktop, ChemDoodle.lib.jQuery, document);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(c, desktop, q, document) {
	'use strict';
	desktop.SaveFileDialog = function(id, sketcher) {
		this.id = id;
		this.sketcher = sketcher;
	};
	var _ = desktop.SaveFileDialog.prototype = new desktop.Dialog();
	_.title = 'Save File';
	_.clear = function() {
		q('#' + this.id + '_link').html('The file link will appear here.');
	};
	_.setup = function() {
		var sb = [];
		sb.push('<div style="font-size:12px;" id="');
		sb.push(this.id);
		sb.push('" title="');
		sb.push(this.title);
		sb.push('">');
		sb.push('<p>Select the file format to save your structure to and click on the <strong>Generate File</strong> button.</p>');
		sb.push('<select id="');
		sb.push(this.id);
		sb.push('_select">');
		sb.push('<option value="sk2">ACD/ChemSketch Document {sk2}');
		sb.push('<option value="ros">Beilstein ROSDAL {ros}');
		sb.push('<option value="cdx">Cambridgesoft ChemDraw Exchange {cdx}');
		sb.push('<option value="cdxml">Cambridgesoft ChemDraw XML {cdxml}');
		sb.push('<option value="mrv">ChemAxon Marvin Document {mrv}');
		sb.push('<option value="cml">Chemical Markup Language {cml}');
		sb.push('<option value="smiles">Daylight SMILES {smiles}');
		sb.push('<option value="icl" selected>iChemLabs ChemDoodle Document {icl}');
		sb.push('<option value="inchi">IUPAC InChI {inchi}');
		sb.push('<option value="jdx">IUPAC JCAMP-DX {jdx}');
		sb.push('<option value="skc">MDL ISIS Sketch {skc}');
		sb.push('<option value="tgf">MDL ISIS Sketch Transportable Graphics File {tgf}');
		sb.push('<option value="mol">MDL MOLFile {mol}');
		// sb.push('<option value="rdf">MDL RDFile {rdf}');
		// sb.push('<option value="rxn">MDL RXNFile {rxn}');
		sb.push('<option value="sdf">MDL SDFile {sdf}');
		sb.push('<option value="jme">Molinspiration JME String {jme}');
		sb.push('<option value="pdb">RCSB Protein Data Bank {pdb}');
		sb.push('<option value="mmd">Schr&ouml;dinger Macromodel {mmd}');
		sb.push('<option value="mae">Schr&ouml;dinger Maestro {mae}');
		sb.push('<option value="smd">Standard Molecular Data {smd}');
		sb.push('<option value="mol2">Tripos Mol2 {mol2}');
		sb.push('<option value="sln">Tripos SYBYL SLN {sln}');
		sb.push('<option value="xyz">XYZ {xyz}');
		sb.push('</select>');
		sb.push('<button id="');
		sb.push(this.id);
		sb.push('_button">');
		sb.push('Generate File</button>');
		sb.push('<p>When the file is written, a link will appear in the red-bordered box below, right-click on the link and choose the browser\'s <strong>Save As...</strong> function to save the file to your computer.</p>');
		sb.push('<div style="width:100%;height:30px;border:1px solid #c10000;text-align:center;" id="');
		sb.push(this.id);
		sb.push('_link">The file link will appear here.</div>');
		sb.push('<p><a href="http://www.chemdoodle.com" target="_blank">How do I use these files?</a></p>');
		sb.push('</div>');
		if (document.getElementById(this.sketcher.id)) {
			var canvas = q('#' + this.sketcher.id);
			canvas.before(sb.join(''));
		} else {
			document.writeln(sb.join(''));
		}
		var self = this;
		q('#' + this.id + '_button').click(function() {
			q('#' + self.id + '_link').html('Generating file, please wait...');
			ChemDoodle.iChemLabs.saveFile(self.sketcher.oneMolecule ? self.sketcher.molecules[0] : self.sketcher.lasso.getFirstMolecule(), {
				ext : q('#' + self.id + '_select').val()
			}, function(link) {
				q('#' + self.id + '_link').html('<a href="' + link + '"><span style="text-decoration:underline;">File is generated. Right-click on this link and Save As...</span></a>');
			});
		});
		this.getElement().dialog({
			autoOpen : false,
			width : 435,
			buttons : self.buttons
		});
	};

})(ChemDoodle, ChemDoodle.uis.gui.desktop, ChemDoodle.lib.jQuery, document);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(c, actions, gui, desktop, q) {
	'use strict';
	gui.DialogManager = function(sketcher) {
		if (sketcher.useServices) {
			this.saveDialog = new desktop.SaveFileDialog(sketcher.id + '_save_dialog', sketcher);
		} else {
			this.saveDialog = new desktop.Dialog(sketcher.id, '_save_dialog', 'Save Molecule');
			this.saveDialog.message = 'Copy and paste the content of the textarea into a file and save it with the extension <strong>.mol</strong>.';
			this.saveDialog.includeTextArea = true;
			// You must keep this link displayed at all times to abide by the
			// license
			// Contact us for permission to remove it,
			// http://www.ichemlabs.com/contact-us
			this.saveDialog.afterMessage = '<a href="http://www.chemdoodle.com" target="_blank">How do I use MOLFiles?</a>';
		}
		this.saveDialog.setup();

		this.loadDialog = new desktop.Dialog(sketcher.id, '_load_dialog', 'Load Molecule');
		var sb = [ 'Copy and paste the contents of a MOLFile (<strong>.mol</strong>)' ];
		// if (sketcher.useServices) {
		// sb.push(', a SMILES string');
		// }
		sb.push(' or ChemDoodle JSON in the textarea below and then press the <strong>Load</strong> button.');
		this.loadDialog.message = sb.join('');
		this.loadDialog.includeTextArea = true;
		// You must keep this link displayed at all times to abide by the
		// license
		// Contact us for permission to remove it,
		// http://www.ichemlabs.com/contact-us
		this.loadDialog.afterMessage = '<a href="http://www.chemdoodle.com" target="_blank">Where do I get MOLFiles or ChemDoodle JSON?</a>';
		var self = this;
		this.loadDialog.buttons = {
			'Load' : function() {
				q(this).dialog('close');
				var s = self.loadDialog.getTextArea().val();
				var newContent;
				if (s.indexOf('v2000') !== -1 || s.indexOf('V2000') !== -1) {
					newContent = {
						molecules : [ c.readMOL(s) ],
						shapes : []
					};
				} else if (s.charAt(0) == '{') {
					newContent = new c.readJSON(s);
				}
				if (sketcher.oneMolecule && newContent && newContent.molecules.length > 0 && newContent.molecules[0].atoms.length > 0) {
					sketcher.historyManager.pushUndo(new actions.SwitchMoleculeAction(sketcher, newContent.molecules[0]));
				} else if (!sketcher.oneMolecule && newContent && (newContent.molecules.length > 0 || newContent.shapes.length > 0)) {
					sketcher.historyManager.pushUndo(new actions.SwitchContentAction(sketcher, newContent.molecules, newContent.shapes));
				} else {
					alert('No chemical content was recognized.');
				}
			}
		};
		this.loadDialog.setup();

		this.atomQueryDialog = new desktop.AtomQueryDialog(sketcher, '_atom_query_dialog');
		this.atomQueryDialog.setup();

		this.bondQueryDialog = new desktop.BondQueryDialog(sketcher, '_bond_query_dialog');
		this.bondQueryDialog.setup();

		this.searchDialog = new desktop.MolGrabberDialog(sketcher.id, '_search_dialog');
		this.searchDialog.buttons = {
			'Load' : function() {
				q(this).dialog('close');
				var newMol = self.searchDialog.canvas.molecules[0];
				if (newMol && newMol.atoms.length > 0) {
					if (sketcher.oneMolecule) {
						if (newMol !== sketcher.molecule) {
							sketcher.historyManager.pushUndo(new actions.SwitchMoleculeAction(sketcher, newMol));
						}
					} else {
						sketcher.historyManager.pushUndo(new actions.NewMoleculeAction(sketcher, newMol.atoms, newMol.bonds));
						sketcher.toolbarManager.buttonLasso.getElement().click();
						sketcher.lasso.select(newMol.atoms, []);
					}
				}
			}
		};
		this.searchDialog.setup();

		if (sketcher.setupScene) {
			this.specsDialog = new desktop.SpecsDialog(sketcher, '_specs_dialog');
			this.specsDialog.buttons = {
				'Done' : function() {
					q(this).dialog('close');
				}
			};
			this.specsDialog.setup(this.specsDialog, sketcher);
		}

		this.periodicTableDialog = new desktop.PeriodicTableDialog(sketcher.id, '_periodicTable_dialog');
		this.periodicTableDialog.buttons = {
			'Close' : function() {
				q(this).dialog('close');
			}
		};
		this.periodicTableDialog.setup();
		this.periodicTableDialog.canvas.click = function(evt) {
			if (this.hovered) {
				this.selected = this.hovered;
				var e = this.getHoveredElement();
				sketcher.stateManager.setState(sketcher.stateManager.STATE_LABEL);
				sketcher.stateManager.STATE_LABEL.label = e.symbol;
				sketcher.toolbarManager.buttonLabel.select();
				this.repaint();
			}
		};

		this.calculateDialog = new desktop.Dialog(sketcher.id, '_calculate_dialog', 'Calculations');
		this.calculateDialog.includeTextArea = true;
		// You must keep this link displayed at all times to abide by the
		// license
		// Contact us for permission to remove it,
		// http://www.ichemlabs.com/contact-us
		this.calculateDialog.afterMessage = '<a href="http://www.chemdoodle.com" target="_blank">Want more calculations?</a>';
		this.calculateDialog.setup();

		this.inputDialog = new desktop.Dialog(sketcher.id, '_input_dialog', 'Input');
		this.inputDialog.message = 'Please input the rgroup number (must be a positive integer). Input "-1" to remove the rgroup.';
		this.inputDialog.includeTextField = true;
		this.inputDialog.buttons = {
			'Done' : function() {
				q(this).dialog('close');
				if (self.inputDialog.doneFunction) {
					self.inputDialog.doneFunction(self.inputDialog.getTextField().val());
				}
			}
		};
		this.inputDialog.setup();
	};

})(ChemDoodle, ChemDoodle.uis.actions, ChemDoodle.uis.gui, ChemDoodle.uis.gui.desktop, ChemDoodle.lib.jQuery);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//
(function(desktop, imageDepot, q, document) {
	'use strict';
	desktop.DropDown = function(id, tooltip, dummy) {
		this.id = id;
		this.tooltip = tooltip;
		this.dummy = dummy;
		this.buttonSet = new desktop.ButtonSet(id + '_set');
		this.buttonSet.buttonGroup = tooltip;
		this.defaultButton = undefined;
	};
	var _ = desktop.DropDown.prototype;
	_.getButtonSource = function() {
		var sb = [];
		sb.push('<button id="');
		sb.push(this.id);
		sb.push('" onclick="return false;" title="');
		sb.push(this.tooltip);
		sb.push('"><img title="');
		sb.push(this.tooltip);
		sb.push('" width="9" height="20" src="');
		sb.push(imageDepot.getURI(imageDepot.ARROW_DOWN));
		sb.push('"></button>');
		return sb.join('');
	};
	_.getHiddenSource = function() {
		var sb = [];
		sb.push('<div style="display:none;position:absolute;z-index:10;border:1px #C1C1C1 solid;background:#F5F5F5;padding:5px;border-bottom-left-radius:5px;-moz-border-radius-bottomleft:5px;border-bottom-right-radius:5px;-moz-border-radius-bottomright:5px;" id="');
		sb.push(this.id);
		sb.push('_hidden">');
		sb.push(this.buttonSet.getSource(this.id + '_popup_set'));
		sb.push('</div>');
		return sb.join('');
	};
	_.setup = function() {
		if (!this.defaultButton) {
			this.defaultButton = this.buttonSet.buttons[0];
		}
		var tag = '#' + this.id;
		q(tag).button();
		q(tag + '_hidden').hide();
		q(tag).click(function() {
			// mobile safari doesn't allow clicks to be triggered
			q(document).trigger('click');
			var component = q(tag + '_hidden');
			component.show().position({
				my : 'center top',
				at : 'center bottom',
				of : this,
				collision : 'fit'
			});
			q(document).one('click', function() {
				component.hide();
			});
			return false;
		});
		this.buttonSet.setup();
		var self = this;
		q.each(this.buttonSet.buttons, function(index, value) {
			self.buttonSet.buttons[index].getElement().click(function() {
				self.dummy.absorb(self.buttonSet.buttons[index]);
				self.dummy.select();
				self.dummy.func();
			});
		});
		self.dummy.absorb(this.defaultButton);
		this.defaultButton.select();
	};

})(ChemDoodle.uis.gui.desktop, ChemDoodle.uis.gui.imageDepot, ChemDoodle.lib.jQuery, document);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(desktop, imageDepot, q) {
	'use strict';
	desktop.DummyButton = function(id, icon, tooltip) {
		this.id = id;
		this.icon = icon;
		this.toggle = false;
		this.tooltip = tooltip ? tooltip : '';
		this.func = undefined;
	};
	var _ = desktop.DummyButton.prototype = new desktop.Button();
	_.setup = function() {
		var self = this;
		this.getElement().click(function() {
			self.func();
		});
	};
	_.absorb = function(button) {
		q('#' + this.id + '_icon').attr('src', imageDepot.getURI(button.icon));
		this.func = button.func;
	};

})(ChemDoodle.uis.gui.desktop, ChemDoodle.uis.gui.imageDepot, ChemDoodle.lib.jQuery);
//
// Copyright 2009-2015 iChemLabs, LLC. All rights reserved.
//
(function(desktop, q) {
	'use strict';
	desktop.TextButton = function(id, tooltip, func) {
		this.id = id;
		this.toggle = false;
		this.tooltip = tooltip ? tooltip : '';
		this.func = func ? func : undefined;
	};
	var _ = desktop.TextButton.prototype = new desktop.Button();
	_.getSource = function(buttonGroup) {
		var sb = [];
		if (this.toggle) {
			sb.push('<input type="radio" name="');
			sb.push(buttonGroup);
			sb.push('" id="');
			sb.push(this.id);
			sb.push('" title="');
			sb.push(this.tooltip);
			sb.push('" /><label for="');
			sb.push(this.id);
			sb.push('">');
			sb.push(this.tooltip);
			sb.push('</label>');
		} else {
			sb.push('<button id="');
			sb.push(this.id);
			sb.push('" onclick="return false;" title="');
			sb.push(this.tooltip);
			sb.push('"><label for="');
			sb.push(this.id);
			sb.push('">');
			sb.push(this.tooltip);
			sb.push('</label></button>');
		}
		return sb.join('');
	};
	
	_.check = function() {
		var element = this.getElement();
		element.prop('checked', true);
		element.button('refresh');
	};
	
	_.uncheck = function() {
		var element = this.getElement();
		element.removeAttr('checked');
		element.button('refresh');
	};

})(ChemDoodle.uis.gui.desktop, ChemDoodle.lib.jQuery);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//
(function(c, iChemLabs, io, structures, actions, gui, imageDepot, desktop, tools, states, q, document) {
	'use strict';
	gui.ToolbarManager = function(sketcher) {
		this.sketcher = sketcher;

		// open
		this.buttonOpen = new desktop.Button(sketcher.id + '_button_open', imageDepot.OPEN, 'Open', function() {
			sketcher.dialogManager.loadDialog.getTextArea().val('');
			sketcher.dialogManager.loadDialog.getElement().dialog('open');
		});
		// save
		this.buttonSave = new desktop.Button(sketcher.id + '_button_save', imageDepot.SAVE, 'Save', function() {
            // elabftw
            // save directly in a file and upload it
            var query = getQueryParams(document.location.search);
            var item = query.id;
            var page = location.pathname.substring(1);
            var type = 'experiments';
            if (page === 'database.php') {
                type = 'items';
            }
            // only if we are editing an experiment, not from team page
            if (item % 1 === 0) {
                $.post('app/controllers/EntityController.php', {
                    addFromString: true,
                    fileType: 'mol',
                    type: type,
                    id: item,
                    string: c.writeMOL(sketcher.molecules[0])
                }).done(function() {
                    $("#filesdiv").load(page + "?mode=edit&id=" + item + " #filesdiv");
                });
            } else if (sketcher.useServices) {
				sketcher.dialogManager.saveDialog.clear();
			} else if (sketcher.oneMolecule) {
				sketcher.dialogManager.saveDialog.getTextArea().val(c.writeMOL(sketcher.molecules[0]));
                sketcher.dialogManager.saveDialog.getElement().dialog('open');
			} else if (sketcher.lasso.isActive()) {
				sketcher.dialogManager.saveDialog.getTextArea().val(c.writeMOL(sketcher.lasso.getFirstMolecule()));
                sketcher.dialogManager.saveDialog.getElement().dialog('open');
			}
		});
		// search
		this.buttonSearch = new desktop.Button(sketcher.id + '_button_search', imageDepot.SEARCH, 'Search', function() {
			sketcher.dialogManager.searchDialog.getElement().dialog('open');
		});
		// calculate
		this.buttonCalculate = new desktop.Button(sketcher.id + '_button_calculate', imageDepot.CALCULATE, 'Calculate', function() {
			var mol = sketcher.oneMolecule ? sketcher.molecules[0] : sketcher.lasso.getFirstMolecule();
			if (mol) {
				iChemLabs.calculate(mol, {
					descriptors : [ 'mf', 'ef', 'mw', 'miw', 'deg_unsat', 'hba', 'hbd', 'rot', 'electron', 'pol_miller', 'cmr', 'tpsa', 'vabc', 'xlogp2', 'bertz' ]
				}, function(content) {
					var sb = [];
					function addDatum(title, value, unit) {
						sb.push(title);
						sb.push(': ');
						for ( var i = title.length + 2; i < 30; i++) {
							sb.push(' ');
						}
						sb.push(value);
						sb.push(' ');
						sb.push(unit);
						sb.push('\n');
					}
					addDatum('Molecular Formula', content.mf, '');
					addDatum('Empirical Formula', content.ef, '');
					addDatum('Molecular Mass', content.mw, 'amu');
					addDatum('Monoisotopic Mass', content.miw, 'amu');
					addDatum('Degree of Unsaturation', content.deg_unsat, '');
					addDatum('Hydrogen Bond Acceptors', content.hba, '');
					addDatum('Hydrogen Bond Donors', content.hbd, '');
					addDatum('Rotatable Bonds', content.rot, '');
					addDatum('Total Electrons', content.rot, '');
					addDatum('Molecular Polarizability', content.pol_miller, 'A^3');
					addDatum('Molar Refractivity', content.cmr, 'cm^3/mol');
					addDatum('Polar Surface Area', content.tpsa, 'A^2');
					addDatum('vdW Volume', content.vabc, 'A^3');
					addDatum('logP', content.xlogp2, '');
					addDatum('Complexity', content.bertz, '');
					sketcher.dialogManager.calculateDialog.getTextArea().val(sb.join(''));
					sketcher.dialogManager.calculateDialog.getElement().dialog('open');
				});
			}
		});

		// move
		this.buttonMove = new desktop.Button(sketcher.id + '_button_move', imageDepot.MOVE, 'Move', function() {
			sketcher.stateManager.setState(sketcher.stateManager.STATE_MOVE);
		});
		this.buttonMove.toggle = true;
		// erase
		this.buttonErase = new desktop.Button(sketcher.id + '_button_erase', imageDepot.ERASE, 'Erase', function() {
			sketcher.stateManager.setState(sketcher.stateManager.STATE_ERASE);
		});
		this.buttonErase.toggle = true;

		// clear
		this.buttonClear = new desktop.Button(sketcher.id + '_button_clear', imageDepot.CLEAR, 'Clear', function() {
			var clear = true;
			if (sketcher.oneMolecule) {
				if (sketcher.molecules[0].atoms.length === 1) {
					var a = sketcher.molecules[0].atoms[0];
					if (a.label === 'C' && a.charge === 0 && a.mass === -1) {
						clear = false;
					}
				}
			} else {
				if (sketcher.molecules.length === 0 && sketcher.shapes.length === 0) {
					clear = false;
				}
			}
			if (clear) {
				sketcher.stateManager.getCurrentState().clearHover();
				if (sketcher.lasso && sketcher.lasso.isActive()) {
					sketcher.lasso.empty();
				}
				sketcher.historyManager.pushUndo(new actions.ClearAction(sketcher));
			}
		});
		// clean
		this.buttonClean = new desktop.Button(sketcher.id + '_button_clean', imageDepot.OPTIMIZE, 'Clean', function() {
			var mol = sketcher.oneMolecule ? sketcher.molecules[0] : sketcher.lasso.getFirstMolecule();
			if (mol) {
				var json = new io.JSONInterpreter();
				iChemLabs._contactServer('optimize', {
					'mol' : json.molTo(mol)
				}, {
					dimension : 2
				}, function(content) {
					var optimized = json.molFrom(content.mol);
					var optCenter = optimized.getCenter();
					var dif = sketcher.oneMolecule ? new structures.Point(sketcher.width / 2, sketcher.height / 2) : mol.getCenter();
					dif.sub(optCenter);
					for ( var i = 0, ii = optimized.atoms.length; i < ii; i++) {
						optimized.atoms[i].add(dif);
					}
					sketcher.historyManager.pushUndo(new actions.ChangeCoordinatesAction(mol.atoms, optimized.atoms));
				});
			}
		});

		// lasso set
		this.makeLassoSet(this);

		// scale set
		this.makeScaleSet(this);

		// history set
		this.makeHistorySet(this);

		// label set
		this.makeLabelSet(this);
		
		// query
		this.buttonQuery = new desktop.Button(sketcher.id + '_button_query', imageDepot.QUERY, 'Set Query to Atom or Bond', function() {
			sketcher.stateManager.setState(sketcher.stateManager.STATE_QUERY);
		});
		this.buttonQuery.toggle = true;

		// bond set
		this.makeBondSet(this);

		// ring set
		this.makeRingSet(this);

		// attribute set
		this.makeAttributeSet(this);

		// shape set
		this.makeShapeSet(this);
	};
	var _ = gui.ToolbarManager.prototype;
	_.write = function() {
		var sb = ['<div style="font-size:10px;">'];
		var bg = this.sketcher.id + '_main_group';
		if (this.sketcher.oneMolecule) {
			sb.push(this.buttonMove.getSource(bg));
		} else {
			sb.push(this.lassoSet.getSource(bg));
		}
		sb.push(this.buttonClear.getSource());
		sb.push(this.buttonErase.getSource(bg));
		if (this.sketcher.useServices) {
			sb.push(this.buttonClean.getSource());
		}
		sb.push(this.historySet.getSource());
		sb.push(this.scaleSet.getSource());
		sb.push(this.buttonOpen.getSource());
		sb.push(this.buttonSave.getSource());
		if (this.sketcher.useServices) {
			sb.push(this.buttonSearch.getSource());
			sb.push(this.buttonCalculate.getSource());
		}
		sb.push('<br>');
		sb.push(this.labelSet.getSource(bg));
		if (this.sketcher.includeQuery) {
			sb.push(this.buttonQuery.getSource(bg));
		}
		sb.push(this.attributeSet.getSource(bg));
		sb.push(this.bondSet.getSource(bg));
		sb.push(this.ringSet.getSource(bg));
		if (!this.sketcher.oneMolecule) {
			sb.push(this.shapeSet.getSource(bg));
		}
		sb.push('</div>');

		if (document.getElementById(this.sketcher.id)) {
			var canvas = q('#' + this.sketcher.id);
			canvas.before(sb.join(''));
		} else {
			document.write(sb.join(''));
		}
	};
	_.setup = function() {
		if (this.sketcher.oneMolecule) {
			this.buttonMove.setup(true);
		} else {
			this.lassoSet.setup();
		}
		this.buttonClear.setup();
		this.buttonErase.setup(true);
		if (this.sketcher.useServices) {
			this.buttonClean.setup();
		}
		this.historySet.setup();
		this.scaleSet.setup();
		this.buttonOpen.setup();
		this.buttonSave.setup();
		if (this.sketcher.useServices) {
			this.buttonSearch.setup();
			this.buttonCalculate.setup();
		}
		this.labelSet.setup();
		if (this.sketcher.includeQuery) {
			this.buttonQuery.setup(true);
		}
		this.attributeSet.setup();
		this.bondSet.setup();
		this.ringSet.setup();
		if (!this.sketcher.oneMolecule) {
			this.shapeSet.setup();
		}

		this.buttonSingle.select();
		this.buttonUndo.disable();
		this.buttonRedo.disable();
		if (!this.sketcher.oneMolecule) {
			if (this.sketcher.useServices) {
				this.buttonClean.disable();
				this.buttonCalculate.disable();
				this.buttonSave.disable();
			}
		}
	};

	_.makeScaleSet = function(self) {
		this.scaleSet = new desktop.ButtonSet(self.sketcher.id + '_buttons_scale');
		this.scaleSet.toggle = false;
		this.buttonScalePlus = new desktop.Button(self.sketcher.id + '_button_scale_plus', imageDepot.ZOOM_IN, 'Increase Scale', function() {
			self.sketcher.specs.scale *= 1.5;
			self.sketcher.checkScale();
			self.sketcher.repaint();
		});
		this.scaleSet.buttons.push(this.buttonScalePlus);
		this.buttonScaleMinus = new desktop.Button(self.sketcher.id + '_button_scale_minus', imageDepot.ZOOM_OUT, 'Decrease Scale', function() {
			self.sketcher.specs.scale /= 1.5;
			self.sketcher.checkScale();
			self.sketcher.repaint();
		});
		this.scaleSet.buttons.push(this.buttonScaleMinus);
	};
	_.makeLassoSet = function(self) {
		this.lassoSet = new desktop.ButtonSet(self.sketcher.id + '_buttons_lasso');
		this.buttonLasso = new desktop.DummyButton(self.sketcher.id + '_button_lasso', imageDepot.LASSO, 'Selection Tool');
		this.lassoSet.buttons.push(this.buttonLasso);
		this.lassoSet.addDropDown('More Selection Tools');
		this.lassoSet.dropDown.buttonSet.buttons.push(new desktop.Button(self.sketcher.id + '_button_lasso_lasso', imageDepot.LASSO, 'Lasso Tool', function() {
			self.sketcher.stateManager.setState(self.sketcher.stateManager.STATE_LASSO);
			self.sketcher.lasso.mode = tools.Lasso.MODE_LASSO;
			if (self.sketcher.molecules.length > 0 && !self.sketcher.lasso.isActive()) {
				self.sketcher.lasso.select(self.sketcher.molecules[self.sketcher.molecules.length - 1].atoms, []);
			}
		}));
		this.lassoSet.dropDown.buttonSet.buttons.push(new desktop.Button(self.sketcher.id + '_button_lasso_shapes', imageDepot.LASSO_SHAPES, 'Lasso Tool (shapes only)', function() {
			self.sketcher.stateManager.setState(self.sketcher.stateManager.STATE_LASSO);
			self.sketcher.lasso.mode = tools.Lasso.MODE_LASSO_SHAPES;
			if (self.sketcher.shapes.length > 0 && !self.sketcher.lasso.isActive()) {
				self.sketcher.lasso.select([], [ self.sketcher.shapes[self.sketcher.shapes.length - 1] ]);
			}
		}));
		this.lassoSet.dropDown.buttonSet.buttons.push(new desktop.Button(self.sketcher.id + '_button_lasso_marquee', imageDepot.MARQUEE, 'Marquee Tool', function() {
			self.sketcher.stateManager.setState(self.sketcher.stateManager.STATE_LASSO);
			self.sketcher.lasso.mode = tools.Lasso.MODE_RECTANGLE_MARQUEE;
			if (self.sketcher.molecules.length > 0 && !self.sketcher.lasso.isActive()) {
				self.sketcher.lasso.select(self.sketcher.molecules[self.sketcher.molecules.length - 1].atoms, []);
			}
		}));
	};
	_.makeHistorySet = function(self) {
		this.historySet = new desktop.ButtonSet(self.sketcher.id + '_buttons_history');
		this.historySet.toggle = false;
		this.buttonUndo = new desktop.Button(self.sketcher.id + '_button_undo', imageDepot.UNDO, 'Undo', function() {
			self.sketcher.historyManager.undo();
		});
		this.historySet.buttons.push(this.buttonUndo);
		this.buttonRedo = new desktop.Button(self.sketcher.id + '_button_redo', imageDepot.REDO, 'Redo', function() {
			self.sketcher.historyManager.redo();
		});
		this.historySet.buttons.push(this.buttonRedo);
	};
	_.makeLabelSet = function(self) {
		this.labelSet = new desktop.ButtonSet(self.sketcher.id + '_buttons_label');
		this.buttonLabel = new desktop.DummyButton(self.sketcher.id + '_button_label', imageDepot.CARBON, 'Set Label');
		this.labelSet.buttons.push(this.buttonLabel);
		this.labelSet.addDropDown('More Labels');
		this.labelSet.dropDown.buttonSet.buttons.push(new desktop.Button(self.sketcher.id + '_button_label_h', imageDepot.HYDROGEN, 'Hydrogen', function() {
			self.sketcher.stateManager.setState(self.sketcher.stateManager.STATE_LABEL);
			self.sketcher.stateManager.STATE_LABEL.label = 'H';
		}));
		this.labelSet.dropDown.buttonSet.buttons.push(new desktop.Button(self.sketcher.id + '_button_label_c', imageDepot.CARBON, 'Carbon', function() {
			self.sketcher.stateManager.setState(self.sketcher.stateManager.STATE_LABEL);
			self.sketcher.stateManager.STATE_LABEL.label = 'C';
		}));
		this.labelSet.dropDown.defaultButton = this.labelSet.dropDown.buttonSet.buttons[this.labelSet.dropDown.buttonSet.buttons.length - 1];
		this.labelSet.dropDown.buttonSet.buttons.push(new desktop.Button(self.sketcher.id + '_button_label_n', imageDepot.NITROGEN, 'Nitrogen', function() {
			self.sketcher.stateManager.setState(self.sketcher.stateManager.STATE_LABEL);
			self.sketcher.stateManager.STATE_LABEL.label = 'N';
		}));
		this.labelSet.dropDown.buttonSet.buttons.push(new desktop.Button(self.sketcher.id + '_button_label_o', imageDepot.OXYGEN, 'Oxygen', function() {
			self.sketcher.stateManager.setState(self.sketcher.stateManager.STATE_LABEL);
			self.sketcher.stateManager.STATE_LABEL.label = 'O';
		}));
		this.labelSet.dropDown.buttonSet.buttons.push(new desktop.Button(self.sketcher.id + '_button_label_f', imageDepot.FLUORINE, 'Fluorine', function() {
			self.sketcher.stateManager.setState(self.sketcher.stateManager.STATE_LABEL);
			self.sketcher.stateManager.STATE_LABEL.label = 'F';
		}));
		this.labelSet.dropDown.buttonSet.buttons.push(new desktop.Button(self.sketcher.id + '_button_label_cl', imageDepot.CHLORINE, 'Chlorine', function() {
			self.sketcher.stateManager.setState(self.sketcher.stateManager.STATE_LABEL);
			self.sketcher.stateManager.STATE_LABEL.label = 'Cl';
		}));
		this.labelSet.dropDown.buttonSet.buttons.push(new desktop.Button(self.sketcher.id + '_button_label_br', imageDepot.BROMINE, 'Bromine', function() {
			self.sketcher.stateManager.setState(self.sketcher.stateManager.STATE_LABEL);
			self.sketcher.stateManager.STATE_LABEL.label = 'Br';
		}));
		this.labelSet.dropDown.buttonSet.buttons.push(new desktop.Button(self.sketcher.id + '_button_label_i', imageDepot.IODINE, 'Iodine', function() {
			self.sketcher.stateManager.setState(self.sketcher.stateManager.STATE_LABEL);
			self.sketcher.stateManager.STATE_LABEL.label = 'I';
		}));
		this.labelSet.dropDown.buttonSet.buttons.push(new desktop.Button(self.sketcher.id + '_button_label_p', imageDepot.PHOSPHORUS, 'Phosphorus', function() {
			self.sketcher.stateManager.setState(self.sketcher.stateManager.STATE_LABEL);
			self.sketcher.stateManager.STATE_LABEL.label = 'P';
		}));
		this.labelSet.dropDown.buttonSet.buttons.push(new desktop.Button(self.sketcher.id + '_button_label_s', imageDepot.SULFUR, 'Sulfur', function() {
			self.sketcher.stateManager.setState(self.sketcher.stateManager.STATE_LABEL);
			self.sketcher.stateManager.STATE_LABEL.label = 'S';
		}));
		this.labelSet.dropDown.buttonSet.buttons.push(new desktop.Button(self.sketcher.id + '_button_label_pt', imageDepot.PERIODIC_TABLE, 'Choose Symbol', function() {
			for ( var i = 0, ii = self.sketcher.dialogManager.periodicTableDialog.canvas.cells.length; i < ii; i++) {
				var cell = self.sketcher.dialogManager.periodicTableDialog.canvas.cells[i];
				if (cell.element.symbol === self.sketcher.stateManager.STATE_LABEL.label) {
					self.sketcher.dialogManager.periodicTableDialog.canvas.selected = cell;
					self.sketcher.dialogManager.periodicTableDialog.canvas.repaint();
					break;
				}
			}
			self.sketcher.dialogManager.periodicTableDialog.getElement().dialog('open');
		}));
	};
	_.makeBondSet = function(self) {
		this.bondSet = new desktop.ButtonSet(self.sketcher.id + '_buttons_bond');
		this.buttonSingle = new desktop.Button(self.sketcher.id + '_button_bond_single', imageDepot.BOND_SINGLE, 'Single Bond', function() {
			self.sketcher.stateManager.setState(self.sketcher.stateManager.STATE_NEW_BOND);
			self.sketcher.stateManager.STATE_NEW_BOND.bondOrder = 1;
			self.sketcher.stateManager.STATE_NEW_BOND.stereo = structures.Bond.STEREO_NONE;
		});
		this.bondSet.buttons.push(this.buttonSingle);
		this.buttonRecessed = new desktop.Button(self.sketcher.id + '_button_bond_recessed', imageDepot.BOND_RECESSED, 'Recessed Bond', function() {
			self.sketcher.stateManager.setState(self.sketcher.stateManager.STATE_NEW_BOND);
			self.sketcher.stateManager.STATE_NEW_BOND.bondOrder = 1;
			self.sketcher.stateManager.STATE_NEW_BOND.stereo = structures.Bond.STEREO_RECESSED;
		});
		this.bondSet.buttons.push(this.buttonRecessed);
		this.buttonProtruding = new desktop.Button(self.sketcher.id + '_button_bond_protruding', imageDepot.BOND_PROTRUDING, 'Protruding Bond', function() {
			self.sketcher.stateManager.setState(self.sketcher.stateManager.STATE_NEW_BOND);
			self.sketcher.stateManager.STATE_NEW_BOND.bondOrder = 1;
			self.sketcher.stateManager.STATE_NEW_BOND.stereo = structures.Bond.STEREO_PROTRUDING;
		});
		this.bondSet.buttons.push(this.buttonProtruding);
		this.buttonDouble = new desktop.Button(self.sketcher.id + '_button_bond_double', imageDepot.BOND_DOUBLE, 'Double Bond', function() {
			self.sketcher.stateManager.setState(self.sketcher.stateManager.STATE_NEW_BOND);
			self.sketcher.stateManager.STATE_NEW_BOND.bondOrder = 2;
			self.sketcher.stateManager.STATE_NEW_BOND.stereo = structures.Bond.STEREO_NONE;
		});
		this.bondSet.buttons.push(this.buttonDouble);
		this.buttonBond = new desktop.DummyButton(self.sketcher.id + '_button_bond', imageDepot.BOND_TRIPLE, 'Other Bond');
		this.bondSet.buttons.push(this.buttonBond);
		this.bondSet.addDropDown('More Bonds');
		this.bondSet.dropDown.buttonSet.buttons.push(new desktop.Button(self.sketcher.id + '_button_bond_zero', imageDepot.BOND_ZERO, 'Zero Bond (Ionic/Hydrogen)', function() {
			self.sketcher.stateManager.setState(self.sketcher.stateManager.STATE_NEW_BOND);
			self.sketcher.stateManager.STATE_NEW_BOND.bondOrder = 0;
			self.sketcher.stateManager.STATE_NEW_BOND.stereo = structures.Bond.STEREO_NONE;
		}));
		this.bondSet.dropDown.buttonSet.buttons.push(new desktop.Button(self.sketcher.id + '_button_bond_half', imageDepot.BOND_HALF, 'Half Bond', function() {
			self.sketcher.stateManager.setState(self.sketcher.stateManager.STATE_NEW_BOND);
			self.sketcher.stateManager.STATE_NEW_BOND.bondOrder = 0.5;
			self.sketcher.stateManager.STATE_NEW_BOND.stereo = structures.Bond.STEREO_NONE;
		}));
		this.bondSet.dropDown.buttonSet.buttons.push(new desktop.Button(self.sketcher.id + '_button_bond_resonance', imageDepot.BOND_RESONANCE, 'Resonance Bond', function() {
			self.sketcher.stateManager.setState(self.sketcher.stateManager.STATE_NEW_BOND);
			self.sketcher.stateManager.STATE_NEW_BOND.bondOrder = 1.5;
			self.sketcher.stateManager.STATE_NEW_BOND.stereo = structures.Bond.STEREO_NONE;
		}));
		this.bondSet.dropDown.buttonSet.buttons.push(new desktop.Button(self.sketcher.id + '_button_bond_ambiguous_double', imageDepot.BOND_DOUBLE_AMBIGUOUS, 'Ambiguous Double Bond', function() {
			self.sketcher.stateManager.setState(self.sketcher.stateManager.STATE_NEW_BOND);
			self.sketcher.stateManager.STATE_NEW_BOND.bondOrder = 2;
			self.sketcher.stateManager.STATE_NEW_BOND.stereo = structures.Bond.STEREO_AMBIGUOUS;
		}));
		this.bondSet.dropDown.buttonSet.buttons.push(new desktop.Button(self.sketcher.id + '_button_bond_triple', imageDepot.BOND_TRIPLE, 'Triple Bond', function() {
			self.sketcher.stateManager.setState(self.sketcher.stateManager.STATE_NEW_BOND);
			self.sketcher.stateManager.STATE_NEW_BOND.bondOrder = 3;
			self.sketcher.stateManager.STATE_NEW_BOND.stereo = structures.Bond.STEREO_NONE;
		}));
		this.bondSet.dropDown.defaultButton = this.bondSet.dropDown.buttonSet.buttons[this.bondSet.dropDown.buttonSet.buttons.length - 1];
	};
	_.makeRingSet = function(self) {
		this.ringSet = new desktop.ButtonSet(self.sketcher.id + '_buttons_ring');
		this.buttonCyclohexane = new desktop.Button(self.sketcher.id + '_button_ring_cyclohexane', imageDepot.CYCLOHEXANE, 'Cyclohexane Ring', function() {
			self.sketcher.stateManager.setState(self.sketcher.stateManager.STATE_NEW_RING);
			self.sketcher.stateManager.STATE_NEW_RING.numSides = 6;
			self.sketcher.stateManager.STATE_NEW_RING.unsaturated = false;
		});
		this.ringSet.buttons.push(this.buttonCyclohexane);
		this.buttonBenzene = new desktop.Button(self.sketcher.id + '_button_ring_benzene', imageDepot.BENZENE, 'Benzene Ring', function() {
			self.sketcher.stateManager.setState(self.sketcher.stateManager.STATE_NEW_RING);
			self.sketcher.stateManager.STATE_NEW_RING.numSides = 6;
			self.sketcher.stateManager.STATE_NEW_RING.unsaturated = true;
		});
		this.ringSet.buttons.push(this.buttonBenzene);
		this.buttonRing = new desktop.DummyButton(self.sketcher.id + '_button_ring', imageDepot.CYCLOPENTANE, 'Other Ring');
		this.ringSet.buttons.push(this.buttonRing);
		this.ringSet.addDropDown('More Rings');
		this.ringSet.dropDown.buttonSet.buttons.push(new desktop.Button(self.sketcher.id + '_button_ring_cyclopropane', imageDepot.CYCLOPROPANE, 'Cyclopropane Ring', function() {
			self.sketcher.stateManager.setState(self.sketcher.stateManager.STATE_NEW_RING);
			self.sketcher.stateManager.STATE_NEW_RING.numSides = 3;
			self.sketcher.stateManager.STATE_NEW_RING.unsaturated = false;
		}));
		this.ringSet.dropDown.buttonSet.buttons.push(new desktop.Button(self.sketcher.id + '_button_ring_cyclobutane', imageDepot.CYCLOBUTANE, 'Cyclobutane Ring', function() {
			self.sketcher.stateManager.setState(self.sketcher.stateManager.STATE_NEW_RING);
			self.sketcher.stateManager.STATE_NEW_RING.numSides = 4;
			self.sketcher.stateManager.STATE_NEW_RING.unsaturated = false;
		}));
		this.ringSet.dropDown.buttonSet.buttons.push(new desktop.Button(self.sketcher.id + '_button_ring_cyclopentane', imageDepot.CYCLOPENTANE, 'Cyclopentane Ring', function() {
			self.sketcher.stateManager.setState(self.sketcher.stateManager.STATE_NEW_RING);
			self.sketcher.stateManager.STATE_NEW_RING.numSides = 5;
			self.sketcher.stateManager.STATE_NEW_RING.unsaturated = false;
		}));
		this.ringSet.dropDown.defaultButton = this.ringSet.dropDown.buttonSet.buttons[this.ringSet.dropDown.buttonSet.buttons.length - 1];
		this.ringSet.dropDown.buttonSet.buttons.push(new desktop.Button(self.sketcher.id + '_button_ring_cycloheptane', imageDepot.CYCLOHEPTANE, 'Cycloheptane Ring', function() {
			self.sketcher.stateManager.setState(self.sketcher.stateManager.STATE_NEW_RING);
			self.sketcher.stateManager.STATE_NEW_RING.numSides = 7;
			self.sketcher.stateManager.STATE_NEW_RING.unsaturated = false;
		}));
		this.ringSet.dropDown.buttonSet.buttons.push(new desktop.Button(self.sketcher.id + '_button_ring_cyclooctane', imageDepot.CYCLOOCTANE, 'Cyclooctane Ring', function() {
			self.sketcher.stateManager.setState(self.sketcher.stateManager.STATE_NEW_RING);
			self.sketcher.stateManager.STATE_NEW_RING.numSides = 8;
			self.sketcher.stateManager.STATE_NEW_RING.unsaturated = false;
		}));
	};
	_.makeAttributeSet = function(self) {
		this.attributeSet = new desktop.ButtonSet(self.sketcher.id + '_buttons_attribute');
		this.buttonAttribute = new desktop.DummyButton(self.sketcher.id + '_button_attribute', imageDepot.INCREASE_CHARGE, 'Attributes');
		this.attributeSet.buttons.push(this.buttonAttribute);
		this.attributeSet.addDropDown('More Attributes');
		this.attributeSet.dropDown.buttonSet.buttons.push(new desktop.Button(self.sketcher.id + '_button_attribute_charge_increment', imageDepot.INCREASE_CHARGE, 'Increase Charge', function() {
			self.sketcher.stateManager.setState(self.sketcher.stateManager.STATE_CHARGE);
			self.sketcher.stateManager.STATE_CHARGE.delta = 1;
		}));
		this.attributeSet.dropDown.buttonSet.buttons.push(new desktop.Button(self.sketcher.id + '_button_attribute_charge_decrement', imageDepot.DECREASE_CHARGE, 'Decrease Charge', function() {
			self.sketcher.stateManager.setState(self.sketcher.stateManager.STATE_CHARGE);
			self.sketcher.stateManager.STATE_CHARGE.delta = -1;
		}));
		this.attributeSet.dropDown.buttonSet.buttons.push(new desktop.Button(self.sketcher.id + '_button_attribute_lonePair_increment', imageDepot.ADD_LONE_PAIR, 'Add Lone Pair', function() {
			self.sketcher.stateManager.setState(self.sketcher.stateManager.STATE_LONE_PAIR);
			self.sketcher.stateManager.STATE_LONE_PAIR.delta = 1;
		}));
		this.attributeSet.dropDown.buttonSet.buttons.push(new desktop.Button(self.sketcher.id + '_button_attribute_lonePair_decrement', imageDepot.REMOVE_LONE_PAIR, 'Remove Lone Pair', function() {
			self.sketcher.stateManager.setState(self.sketcher.stateManager.STATE_LONE_PAIR);
			self.sketcher.stateManager.STATE_LONE_PAIR.delta = -1;
		}));
		this.attributeSet.dropDown.buttonSet.buttons.push(new desktop.Button(self.sketcher.id + '_button_attribute_radical_increment', imageDepot.ADD_RADICAL, 'Add Radical', function() {
			self.sketcher.stateManager.setState(self.sketcher.stateManager.STATE_RADICAL);
			self.sketcher.stateManager.STATE_RADICAL.delta = 1;
		}));
		this.attributeSet.dropDown.buttonSet.buttons.push(new desktop.Button(self.sketcher.id + '_button_attribute_radical_decrement', imageDepot.REMOVE_RADICAL, 'Remove Radical', function() {
			self.sketcher.stateManager.setState(self.sketcher.stateManager.STATE_RADICAL);
			self.sketcher.stateManager.STATE_RADICAL.delta = -1;
		}));
	};
	_.makeShapeSet = function(self) {
		this.shapeSet = new desktop.ButtonSet(self.sketcher.id + '_buttons_shape');
		this.buttonShape = new desktop.DummyButton(self.sketcher.id + '_button_shape', imageDepot.ARROW_SYNTHETIC, 'Add Shape');
		this.shapeSet.buttons.push(this.buttonShape);
		this.shapeSet.addDropDown('More Shapes');
		this.shapeSet.dropDown.buttonSet.buttons.push(new desktop.Button(self.sketcher.id + '_button_shape_arrow_synthetic', imageDepot.ARROW_SYNTHETIC, 'Synthetic Arrow', function() {
			self.sketcher.stateManager.setState(self.sketcher.stateManager.STATE_SHAPE);
			self.sketcher.stateManager.STATE_SHAPE.shapeType = states.ShapeState.ARROW_SYNTHETIC;
		}));
		this.shapeSet.dropDown.buttonSet.buttons.push(new desktop.Button(self.sketcher.id + '_button_shape_arrow_retrosynthetic', imageDepot.ARROW_RETROSYNTHETIC, 'Retrosynthetic Arrow', function() {
			self.sketcher.stateManager.setState(self.sketcher.stateManager.STATE_SHAPE);
			self.sketcher.stateManager.STATE_SHAPE.shapeType = states.ShapeState.ARROW_RETROSYNTHETIC;
		}));
		this.shapeSet.dropDown.buttonSet.buttons.push(new desktop.Button(self.sketcher.id + '_button_shape_arrow_resonance', imageDepot.ARROW_RESONANCE, 'Resonance Arrow', function() {
			self.sketcher.stateManager.setState(self.sketcher.stateManager.STATE_SHAPE);
			self.sketcher.stateManager.STATE_SHAPE.shapeType = states.ShapeState.ARROW_RESONANCE;
		}));
		this.shapeSet.dropDown.buttonSet.buttons.push(new desktop.Button(self.sketcher.id + '_button_shape_arrow_equilibrium', imageDepot.ARROW_EQUILIBRIUM, 'Equilibrium Arrow', function() {
			self.sketcher.stateManager.setState(self.sketcher.stateManager.STATE_SHAPE);
			self.sketcher.stateManager.STATE_SHAPE.shapeType = states.ShapeState.ARROW_EQUILIBRIUM;
		}));
		this.shapeSet.dropDown.buttonSet.buttons.push(new desktop.Button(self.sketcher.id + '_button_shape_pusher_1', imageDepot.PUSHER_SINGLE, 'Single Electron Pusher', function() {
			self.sketcher.stateManager.setState(self.sketcher.stateManager.STATE_PUSHER);
			self.sketcher.stateManager.STATE_PUSHER.numElectron = 1;
		}));
		this.shapeSet.dropDown.buttonSet.buttons.push(new desktop.Button(self.sketcher.id + '_button_shape_pusher_2', imageDepot.PUSHER_DOUBLE, 'Electron Pair Pusher', function() {
			self.sketcher.stateManager.setState(self.sketcher.stateManager.STATE_PUSHER);
			self.sketcher.stateManager.STATE_PUSHER.numElectron = 2;
		}));
		this.shapeSet.dropDown.buttonSet.buttons.push(new desktop.Button(self.sketcher.id + '_button_shape_pusher_bond_forming', imageDepot.PUSHER_BOND_FORMING, 'Bond Forming Pusher', function() {
			self.sketcher.stateManager.setState(self.sketcher.stateManager.STATE_PUSHER);
			self.sketcher.stateManager.STATE_PUSHER.numElectron = -1;
		}));
		this.shapeSet.dropDown.buttonSet.buttons.push(new desktop.Button(self.sketcher.id + '_button_shape_charge_bracket', imageDepot.CHARGE_BRACKET, 'Bracket', function() {
			self.sketcher.stateManager.setState(self.sketcher.stateManager.STATE_SHAPE);
			self.sketcher.stateManager.STATE_SHAPE.shapeType = states.ShapeState.BRACKET;
			self.sketcher.repaint();
		}));
	};

})(ChemDoodle, ChemDoodle.iChemLabs, ChemDoodle.io, ChemDoodle.structures, ChemDoodle.uis.actions, ChemDoodle.uis.gui, ChemDoodle.uis.gui.imageDepot, ChemDoodle.uis.gui.desktop, ChemDoodle.uis.tools, ChemDoodle.uis.states, ChemDoodle.lib.jQuery, document);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(math, monitor, tools) {
	'use strict';
	tools.Lasso = function(sketcher) {
		this.sketcher = sketcher;
		this.atoms = [];
		this.shapes = [];
		this.bounds = undefined;
		this.mode = tools.Lasso.MODE_LASSO;
		this.points = [];
	};
	tools.Lasso.MODE_LASSO = 'lasso';
	tools.Lasso.MODE_LASSO_SHAPES = 'shapes';
	tools.Lasso.MODE_RECTANGLE_MARQUEE = 'rectangle';
	var _ = tools.Lasso.prototype;
	_.select = function(atoms, shapes) {
		if (this.block) {
			return;
		}
		if (!monitor.SHIFT) {
			this.empty();
		}
		if (atoms) {
			this.atoms = atoms.slice(0);
			this.shapes = shapes.slice(0);
		} else {
			if (this.mode !== tools.Lasso.MODE_LASSO_SHAPES) {
				var asAdd = [];
				for ( var i = 0, ii = this.sketcher.molecules.length; i < ii; i++) {
					var mol = this.sketcher.molecules[i];
					for ( var j = 0, jj = mol.atoms.length; j < jj; j++) {
						var a = mol.atoms[j];
						if (this.mode === tools.Lasso.MODE_RECTANGLE_MARQUEE) {
							if (this.points.length === 2) {
								if (math.isBetween(a.x, this.points[0].x, this.points[1].x) && math.isBetween(a.y, this.points[0].y, this.points[1].y)) {
									asAdd.push(a);
								}
							}
						} else {
							if (this.points.length > 1) {
								if (math.isPointInPoly(this.points, a)) {
									asAdd.push(a);
								}
							}
						}
					}
				}
				if (this.atoms.length === 0) {
					this.atoms = asAdd;
				} else {
					var asFinal = [];
					for ( var i = 0, ii = this.atoms.length; i < ii; i++) {
						var a = this.atoms[i];
						if (asAdd.indexOf(a) === -1) {
							asFinal.push(a);
						} else {
							a.isLassoed = false;
						}
					}
					for ( var i = 0, ii = asAdd.length; i < ii; i++) {
						if (this.atoms.indexOf(asAdd[i]) === -1) {
							asFinal.push(asAdd[i]);
						}
					}
					this.atoms = asFinal;
				}
			}
			var ssAdd = [];
			for ( var i = 0, ii = this.sketcher.shapes.length; i < ii; i++) {
				var s = this.sketcher.shapes[i];
				var sps = s.getPoints();
				var contained = sps.length>0;
				for ( var j = 0, jj = sps.length; j < jj; j++) {
					var p = sps[j];
					if (this.mode === tools.Lasso.MODE_RECTANGLE_MARQUEE) {
						if (this.points.length === 2) {
							if (!math.isBetween(p.x, this.points[0].x, this.points[1].x) || !math.isBetween(p.y, this.points[0].y, this.points[1].y)) {
								contained = false;
								break;
							}
						} else {
							contained = false;
							break;
						}
					} else {
						if (this.points.length > 1) {
							if (!math.isPointInPoly(this.points, p)) {
								contained = false;
								break;
							}
						} else {
							contained = false;
							break;
						}
					}
				}
				if (contained) {
					ssAdd.push(s);
				}
			}
			if (this.shapes.length === 0) {
				this.shapes = ssAdd;
			} else {
				var ssFinal = [];
				for ( var i = 0, ii = this.shapes.length; i < ii; i++) {
					var s = this.shapes[i];
					if (ssAdd.indexOf(s) === -1) {
						asFinal.push(s);
					} else {
						s.isLassoed = false;
					}
				}
				for ( var i = 0, ii = ssAdd.length; i < ii; i++) {
					if (this.shapes.indexOf(ssAdd[i]) === -1) {
						ssFinal.push(ssAdd[i]);
					}
				}
				this.shapes = ssFinal;
			}
		}
		for ( var i = 0, ii = this.atoms.length; i < ii; i++) {
			this.atoms[i].isLassoed = true;
		}
		for ( var i = 0, ii = this.shapes.length; i < ii; i++) {
			this.shapes[i].isLassoed = true;
		}
		this.setBounds();
		if (this.bounds && this.bounds.minX === Infinity) {
			this.empty();
		}
		this.points = [];
		this.sketcher.stateManager.getCurrentState().clearHover();
		this.enableButtons();
		this.sketcher.repaint();
	};
	_.enableButtons = function() {
		if (this.sketcher.useServices) {
			if (this.atoms.length > 0) {
				this.sketcher.toolbarManager.buttonClean.enable();
				this.sketcher.toolbarManager.buttonCalculate.enable();
				this.sketcher.toolbarManager.buttonSave.enable();
			} else {
				this.sketcher.toolbarManager.buttonClean.disable();
				this.sketcher.toolbarManager.buttonCalculate.disable();
				this.sketcher.toolbarManager.buttonSave.disable();
			}
		}
	};
	_.setBounds = function() {
		if (this.isActive()) {
			this.sketcher.repaint();
			this.bounds = new math.Bounds();
			for ( var i = 0, ii = this.atoms.length; i < ii; i++) {
				var a = this.atoms[i];
				this.bounds.expand(a.getBounds());
			}
			for ( var i = 0, ii = this.shapes.length; i < ii; i++) {
				this.bounds.expand(this.shapes[i].getBounds());
			}
			var buffer = 5;
			this.bounds.minX -= buffer;
			this.bounds.minY -= buffer;
			this.bounds.maxX += buffer;
			this.bounds.maxY += buffer;
		} else {
			this.bounds = undefined;
		}
	};
	_.empty = function() {
		for ( var i = 0, ii = this.atoms.length; i < ii; i++) {
			this.atoms[i].isLassoed = false;
		}
		for ( var i = 0, ii = this.shapes.length; i < ii; i++) {
			this.shapes[i].isLassoed = false;
		}
		this.atoms = [];
		this.shapes = [];
		this.bounds = undefined;
		this.enableButtons();
		this.sketcher.repaint();
	};
	_.draw = function(ctx, specs) {
		ctx.strokeStyle = 'blue';
		ctx.lineWidth = 0.5 / specs.scale;
		/*
		 * if(ctx.setLineDash){ // new feature in HTML5, not yet supported
		 * everywhere, so don't use as it is unstable ctx.setLineDash([5]); }
		 */
		if (this.points.length > 0) {
			if (this.mode === tools.Lasso.MODE_RECTANGLE_MARQUEE) {
				if (this.points.length === 2) {
					var p1 = this.points[0];
					var p2 = this.points[1];
					ctx.beginPath();
					ctx.rect(p1.x, p1.y, p2.x - p1.x, p2.y - p1.y);
					ctx.stroke();
				}
			} else {
				if (this.points.length > 1) {
					ctx.beginPath();
					ctx.moveTo(this.points[0].x, this.points[0].y);
					for ( var i = 1, ii = this.points.length; i < ii; i++) {
						ctx.lineTo(this.points[i].x, this.points[i].y);
					}
					ctx.closePath();
					ctx.stroke();
				}
			}
		}
		if (this.bounds) {
			ctx.beginPath();
			ctx.rect(this.bounds.minX, this.bounds.minY, this.bounds.maxX - this.bounds.minX, this.bounds.maxY - this.bounds.minY);
			ctx.stroke();
		}
	};
	_.isActive = function() {
		return this.atoms.length > 0 || this.shapes.length > 0;
	};
	_.getFirstMolecule = function() {
		if (this.atoms.length > 0) {
			return this.sketcher.getMoleculeByAtom(this.atoms[0]);
		}
		return undefined;
	};
	_.getAllPoints = function() {
		var ps = this.atoms;
		for ( var i = 0, ii = this.shapes.length; i < ii; i++) {
			ps = ps.concat(this.shapes[i].getPoints());
		}
		return ps;
	};
	_.addPoint = function(p) {
		if (this.mode === tools.Lasso.MODE_RECTANGLE_MARQUEE) {
			if (this.points.length < 2) {
				this.points.push(p);
			} else {
				var changing = this.points[1];
				changing.x = p.x;
				changing.y = p.y;
			}
		} else {
			this.points.push(p);
		}
	};

})(ChemDoodle.math, ChemDoodle.monitor, ChemDoodle.uis.tools);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(c, extensions, featureDetection, sketcherPack, structures, tools, q, m, window) {
	'use strict';
	c.SketcherCanvas = function(id, width, height, options) {
		// keep checks to undefined here as these are booleans
		this.isMobile = options.isMobile === undefined ? featureDetection.supports_touch() : options.isMobile;
		this.useServices = options.useServices === undefined ? false : options.useServices;
		this.oneMolecule = options.oneMolecule === undefined ? false : options.oneMolecule;
		this.includeToolbar = options.includeToolbar === undefined ? true : options.includeToolbar;
		this.includeQuery = options.includeQuery === undefined ? false : options.includeQuery;
		// toolbar manager needs the sketcher id to make it unique to this
		// canvas
		this.id = id;
		this.toolbarManager = new sketcherPack.gui.ToolbarManager(this);
		if (this.includeToolbar) {
			this.toolbarManager.write();
			// If pre-created, wait until the last button image loads before
			// calling setup.
			var self = this;
			if (document.getElementById(this.id)) {
				q('#' + id + '_button_attribute_lonePair_decrement_icon').load(function() {
					self.toolbarManager.setup();
				});
			} else {
				q(window).load(function() {
					self.toolbarManager.setup();
				});
			}
			this.dialogManager = new sketcherPack.gui.DialogManager(this);
		}
		this.stateManager = new sketcherPack.states.StateManager(this);
		this.historyManager = new sketcherPack.actions.HistoryManager(this);
		if (id) {
			this.create(id, width, height);
		}
		this.specs.atoms_circleDiameter_2D = 7;
		this.specs.atoms_circleBorderWidth_2D = 0;
		this.isHelp = false;
		this.lastPinchScale = 1;
		this.lastGestureRotate = 0;
		this.inGesture = false;
		if (this.oneMolecule) {
			var startMol = new structures.Molecule();
			startMol.atoms.push(new structures.Atom());
			this.loadMolecule(startMol);
		} else {
			this.startAtom = new structures.Atom('C', -10, -10);
			this.startAtom.isLone = true;
			this.lasso = new tools.Lasso(this);
		}
	};
	var _ = c.SketcherCanvas.prototype = new c._Canvas();
	_.drawSketcherDecorations = function(ctx) {
		ctx.save();
		ctx.translate(this.width / 2, this.height / 2);
		ctx.rotate(this.specs.rotateAngle);
		ctx.scale(this.specs.scale, this.specs.scale);
		ctx.translate(-this.width / 2, -this.height / 2);
		if (this.hovering) {
			this.hovering.drawDecorations(ctx, this.specs);
		}
		if (this.startAtom && this.startAtom.x != -10 && !this.isMobile) {
			this.startAtom.draw(ctx, this.specs);
		}
		if (this.tempAtom) {
			ctx.strokeStyle = '#00FF00';
			ctx.fillStyle = '#00FF00';
			ctx.lineWidth = 1;
			ctx.beginPath();
			ctx.moveTo(this.hovering.x, this.hovering.y);
			extensions.contextHashTo(ctx, this.hovering.x, this.hovering.y, this.tempAtom.x, this.tempAtom.y, 2, 2);
			ctx.stroke();
			if (this.tempAtom.label === 'C') {
				ctx.beginPath();
				ctx.arc(this.tempAtom.x, this.tempAtom.y, 3, 0, m.PI * 2, false);
				ctx.fill();
			}else{
				ctx.textAlign = 'center';
				ctx.textBaseline = 'middle';
				ctx.font = extensions.getFontString(this.specs.atoms_font_size_2D, this.specs.atoms_font_families_2D, this.specs.atoms_font_bold_2D, this.specs.atoms_font_italic_2D);
				ctx.fillText(this.tempAtom.label, this.tempAtom.x, this.tempAtom.y);
			}
			if (this.tempAtom.isOverlap) {
				ctx.strokeStyle = '#C10000';
				ctx.lineWidth = 1.2;
				ctx.beginPath();
				ctx.arc(this.tempAtom.x, this.tempAtom.y, 7, 0, m.PI * 2, false);
				ctx.stroke();
			}
		}
		if (this.tempRing) {
			ctx.strokeStyle = '#00FF00';
			ctx.fillStyle = '#00FF00';
			ctx.lineWidth = 1;
			ctx.beginPath();
			if (this.hovering instanceof structures.Atom) {
				ctx.moveTo(this.hovering.x, this.hovering.y);
				extensions.contextHashTo(ctx, this.hovering.x, this.hovering.y, this.tempRing[0].x, this.tempRing[0].y, 2, 2);
				for ( var i = 1, ii = this.tempRing.length; i < ii; i++) {
					extensions.contextHashTo(ctx, this.tempRing[i - 1].x, this.tempRing[i - 1].y, this.tempRing[i].x, this.tempRing[i].y, 2, 2);
				}
				extensions.contextHashTo(ctx, this.tempRing[this.tempRing.length - 1].x, this.tempRing[this.tempRing.length - 1].y, this.hovering.x, this.hovering.y, 2, 2);
			} else if (this.hovering instanceof structures.Bond) {
				var start = this.hovering.a2;
				var end = this.hovering.a1;
				if (this.tempRing[0] === this.hovering.a1) {
					start = this.hovering.a1;
					end = this.hovering.a2;
				}
				ctx.moveTo(start.x, start.y);
				extensions.contextHashTo(ctx, start.x, start.y, this.tempRing[1].x, this.tempRing[1].y, 2, 2);
				for ( var i = 2, ii = this.tempRing.length; i < ii; i++) {
					extensions.contextHashTo(ctx, this.tempRing[i - 1].x, this.tempRing[i - 1].y, this.tempRing[i].x, this.tempRing[i].y, 2, 2);
				}
				extensions.contextHashTo(ctx, this.tempRing[this.tempRing.length - 1].x, this.tempRing[this.tempRing.length - 1].y, end.x, end.y, 2, 2);
			}
			ctx.stroke();
			ctx.strokeStyle = '#C10000';
			ctx.lineWidth = 1.2;
			for ( var i = 0, ii = this.tempRing.length; i < ii; i++) {
				if (this.tempRing[i].isOverlap) {
					ctx.beginPath();
					ctx.arc(this.tempRing[i].x, this.tempRing[i].y, 7, 0, m.PI * 2, false);
					ctx.stroke();
				}
			}
		}
		if (this.lasso) {
			this.lasso.draw(ctx, this.specs);
		}
		if (this.stateManager.getCurrentState().draw) {
			this.stateManager.getCurrentState().draw(ctx);
		}
		ctx.restore();
	};
	_.drawChildExtras = function(ctx) {
		this.drawSketcherDecorations(ctx);
		if (!this.hideHelp) {
			// help and tutorial
			var helpPos = new structures.Point(this.width - 20, 20);
			var radgrad = ctx.createRadialGradient(helpPos.x, helpPos.y, 10, helpPos.x, helpPos.y, 2);
			radgrad.addColorStop(0, '#00680F');
			radgrad.addColorStop(1, '#FFFFFF');
			ctx.fillStyle = radgrad;
			ctx.beginPath();
			ctx.arc(helpPos.x, helpPos.y, 10, 0, m.PI * 2, false);
			ctx.fill();
			if (this.isHelp) {
				ctx.lineWidth = 2;
				ctx.strokeStyle = 'black';
				ctx.stroke();
			}
			ctx.fillStyle = this.isHelp ? 'red' : 'black';
			ctx.textAlign = 'center';
			ctx.textBaseline = 'middle';
			ctx.font = '14px sans-serif';
			ctx.fillText('?', helpPos.x, helpPos.y);
		}
		if (!this.paidToHideTrademark) {
			// You must keep this name displayed at all times to abide by the license
			// Contact us for permission to remove it,
			// http://www.ichemlabs.com/contact-us
			ctx.font = '14px sans-serif';
			var x = '\x43\x68\x65\x6D\x44\x6F\x6F\x64\x6C\x65';
			var width = ctx.measureText(x).width;
			ctx.textAlign = 'left';
			ctx.textBaseline = 'bottom';
			ctx.fillStyle = 'rgba(0, 60, 0, 0.5)';
			ctx.fillText(x, this.width - width - 13, this.height - 4);
			ctx.font = '8px sans-serif';
			ctx.fillText('\u00AE', this.width - 13, this.height - 12);
		}
	};
	_.scaleEvent = function(e) {
		e.op = new structures.Point(e.p.x, e.p.y);
		if (this.specs.scale !== 1) {
			e.p.x = this.width / 2 + (e.p.x - this.width / 2) / this.specs.scale;
			e.p.y = this.height / 2 + (e.p.y - this.height / 2) / this.specs.scale;
		}
	};
	_.checkScale = function() {
		if (this.specs.scale < .5) {
			this.specs.scale = .5;
		} else if (this.specs.scale > 10) {
			this.specs.scale = 10;
		}
	};
	// desktop events
	_.click = function(e) {
		this.scaleEvent(e);
		this.stateManager.getCurrentState().click(e);
	};
	_.rightclick = function(e) {
		this.scaleEvent(e);
		this.stateManager.getCurrentState().rightclick(e);
	};
	_.dblclick = function(e) {
		this.scaleEvent(e);
		this.stateManager.getCurrentState().dblclick(e);
	};
	_.mousedown = function(e) {
		this.scaleEvent(e);
		this.stateManager.getCurrentState().mousedown(e);
	};
	_.rightmousedown = function(e) {
		this.scaleEvent(e);
		this.stateManager.getCurrentState().rightmousedown(e);
	};
	_.mousemove = function(e) {
		// link to tutorial
		this.isHelp = false;
		if (e.p.distance(new structures.Point(this.width - 20, 20)) < 10) {
			this.isHelp = true;
		}
		this.scaleEvent(e);
		this.stateManager.getCurrentState().mousemove(e);
		// repaint is called in the state mousemove event
	};
	_.mouseout = function(e) {
		this.scaleEvent(e);
		this.stateManager.getCurrentState().mouseout(e);
	};
	_.mouseover = function(e) {
		this.scaleEvent(e);
		this.stateManager.getCurrentState().mouseover(e);
	};
	_.mouseup = function(e) {
		this.scaleEvent(e);
		this.stateManager.getCurrentState().mouseup(e);
	};
	_.rightmouseup = function(e) {
		this.scaleEvent(e);
		this.stateManager.getCurrentState().rightmouseup(e);
	};
	_.mousewheel = function(e, delta) {
		this.scaleEvent(e);
		this.stateManager.getCurrentState().mousewheel(e, delta);
	};
	_.drag = function(e) {
		this.scaleEvent(e);
		this.stateManager.getCurrentState().drag(e);
	};
	_.keydown = function(e) {
		this.scaleEvent(e);
		this.stateManager.getCurrentState().keydown(e);
	};
	_.keypress = function(e) {
		this.scaleEvent(e);
		this.stateManager.getCurrentState().keypress(e);
	};
	_.keyup = function(e) {
		this.scaleEvent(e);
		this.stateManager.getCurrentState().keyup(e);
	};
	_.touchstart = function(e) {
		if (e.originalEvent.touches && e.originalEvent.touches.length > 1) {
			if (this.tempAtom || this.tempRing) {
				this.tempAtom = undefined;
				this.tempRing = undefined;
				this.hovering = undefined;
				this.repaint();
			}
			this.lastPoint = undefined;
		} else {
			this.scaleEvent(e);
			this.stateManager.getCurrentState().mousemove(e);
			this.stateManager.getCurrentState().mousedown(e);
		}
	};
	_.touchmove = function(e) {
		this.scaleEvent(e);
		if (!this.inGesture) {
			this.stateManager.getCurrentState().drag(e);
		}
	};
	_.touchend = function(e) {
		this.scaleEvent(e);
		this.stateManager.getCurrentState().mouseup(e);
		if (this.hovering) {
			this.stateManager.getCurrentState().clearHover();
			this.repaint();
		}
	};
	_.gesturechange = function(e) {
		this.inGesture = true;
		if (e.originalEvent.scale - this.lastPinchScale !== 1) {
			if (!(this.lasso && this.lasso.isActive())) {
				this.specs.scale *= e.originalEvent.scale / this.lastPinchScale;
				this.checkScale();
			}
			this.lastPinchScale = e.originalEvent.scale;
		}
		if (this.lastGestureRotate - e.originalEvent.rotation !== 0) {
			var rot = (this.lastGestureRotate - e.originalEvent.rotation) / 180 * m.PI;
			if (!this.parentAction) {
				var ps = (this.lasso && this.lasso.isActive()) ? this.lasso.getAllPoints() : this.getAllPoints();
				var center = (this.lasso && this.lasso.isActive()) ? new structures.Point((this.lasso.bounds.minX + this.lasso.bounds.maxX) / 2, (this.lasso.bounds.minY + this.lasso.bounds.maxY) / 2) : new structures.Point(this.width / 2, this.height / 2);
				this.parentAction = new sketcherPack.actions.RotateAction(ps, rot, center);
				this.historyManager.pushUndo(this.parentAction);
			} else {
				this.parentAction.dif += rot;
				for ( var i = 0, ii = this.parentAction.ps.length; i < ii; i++) {
					var p = this.parentAction.ps[i];
					var dist = this.parentAction.center.distance(p);
					var angle = this.parentAction.center.angle(p) + rot;
					p.x = this.parentAction.center.x + dist * m.cos(angle);
					p.y = this.parentAction.center.y - dist * m.sin(angle);
				}
				// must check here as change is outside of an action
				for ( var i = 0, ii = this.molecules.length; i < ii; i++) {
					this.molecules[i].check();
				}
				if (this.lasso && this.lasso.isActive()) {
					this.lasso.setBounds();
				}
			}
			this.lastGestureRotate = e.originalEvent.rotation;
		}
		this.repaint();
	};
	_.gestureend = function(e) {
		this.inGesture = false;
		this.lastPinchScale = 1;
		this.lastGestureRotate = 0;
		this.parentAction = undefined;
	};

})(ChemDoodle, ChemDoodle.extensions, ChemDoodle.featureDetection, ChemDoodle.uis, ChemDoodle.structures, ChemDoodle.uis.tools, ChemDoodle.lib.jQuery, Math, window);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(c, math, monitor, actions, states, structures, SYMBOLS, m, m4) {
	'use strict';
	states._State3D = function() {
	};
	var _ = states._State3D.prototype;
	_.setup = function(editor) {
		this.editor = editor;
	};

	_.enter = function() {
		if (this.innerenter) {
			this.innerenter();
		}
	};
	_.exit = function() {
		if (this.innerexit) {
			this.innerexit();
		}
	};
	_.click = function(e) {
		if (this.innerclick) {
			this.innerclick(e);
		}
	};
	_.rightclick = function(e) {
		if (this.innerrightclick) {
			this.innerrightclick(e);
		}
	};
	_.dblclick = function(e) {
		if (this.innerdblclick) {
			this.innerdblclick(e);
		}
	};
	_.mousedown = function(e) {
		this.editor.defaultmousedown(e);
		// must also check for mobile hits here to the help button
		if (this.editor.isHelp || this.editor.isMobile && e.p.distance(new structures.Point(this.editor.width - 20, 20)) < 10) {
			this.editor.isHelp = false;
			this.editor.lastPoint = undefined;
			this.editor.repaint();
			window.open('http://web.chemdoodle.com/demos/3d-editor');
		} else if (this.innermousedown) {
			this.innermousedown(e);
		}
	};
	_.rightmousedown = function(e) {
		if (this.innerrightmousedown) {
			this.innerrightmousedown(e);
		}
		this.editor.defaultrightmousedown(e);
	};
	_.mousemove = function(e) {
		if (this.innermousemove) {
			this.innermousemove(e);
		}
		// call the repaint here to repaint the help button, also this is called
		// by other functions, so the repaint must be here
		this.editor.repaint();
	};
	_.mouseout = function(e) {
		if (this.innermouseout) {
			this.innermouseout(e);
		}
	};
	_.mouseover = function(e) {
		if (this.innermouseover) {
			this.innermouseover(e);
		}
	};
	_.mouseup = function(e) {
		if (this.innermouseup) {
			this.innermouseup(e);
		}
		this.editor.defaultmouseup(e);
	};
	_.rightmouseup = function(e) {
		if (this.innerrightmouseup) {
			this.innerrightmouseup(e);
		}
	};
	_.mousewheel = function(e, delta) {
		if (this.innermousewheel) {
			this.innermousewheel(e);
		} else {
			this.editor.defaultmousewheel(e, delta);
		}
	};
	_.drag = function(e) {
		if (this.innerdrag) {
			this.innerdrag(e);
		} else {
			this.editor.defaultdrag(e);
		}
	};
	_.keydown = function(e) {
		if (monitor.META) {
			if (e.which === 90) {
				// z
				this.editor.historyManager.undo();
			} else if (e.which === 89) {
				// y
				this.editor.historyManager.redo();
			} else if (e.which === 83) {
				// s
				this.editor.toolbarManager.buttonSave.getElement().click();
			} else if (e.which === 79) {
				// o
				this.editor.toolbarManager.buttonOpen.getElement().click();
			} else if (e.which === 78) {
				// n
				this.editor.toolbarManager.buttonClear.getElement().click();
			} else if (e.which === 187 || e.which === 61) {
				// +
				this.editor.toolbarManager.buttonScalePlus.getElement().click();
			} else if (e.which === 189 || e.which === 109) {
				// -
				this.editor.toolbarManager.buttonScaleMinus.getElement().click();
			}
		}
		if (this.innerkeydown) {
			this.innerkeydown(e);
		}
	};
	_.keypress = function(e) {
		if (this.innerkeypress) {
			this.innerkeypress(e);
		}
	};
	_.keyup = function(e) {
		if (this.innerkeyup) {
			this.innerkeyup(e);
		}
	};

})(ChemDoodle, ChemDoodle.math, ChemDoodle.monitor, ChemDoodle.uis.actions, ChemDoodle.uis.states, ChemDoodle.structures, ChemDoodle.SYMBOLS, Math, ChemDoodle.lib.mat4);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//
(function(actions, states, structures, d3, q) {
	'use strict';
	states.MeasureState3D = function(editor) {
		this.setup(editor);
		this.selectedAtoms = [];
	};
	var _ = states.MeasureState3D.prototype = new states._State3D();
	_.numToSelect = 2;

	_.reset = function(){
		for(var i = 0, ii = this.selectedAtoms.length; i<ii; i++){
			this.selectedAtoms[i].isSelected = false;
		}
		this.selectedAtoms = [];
		this.editor.repaint();
	};
	_.innerenter = function(e) {
		this.reset();
	};
	_.innerexit = function(e) {
		this.reset();
	};
	_.innermousemove = function(e) {
		if (this.hoveredAtom) {
			this.hoveredAtom.isHover = false;
			this.hoveredAtom = undefined;
		}
		var obj = this.editor.pick(e.p.x, e.p.y, true, false);
		if (obj && obj instanceof structures.Atom) {
			this.hoveredAtom = obj;
			obj.isHover = true;
		}
		this.editor.repaint();
	};
	_.innermousedown = function(e) {
		// don't use click as that doesn't work on android
		if(this.editor.isMobile){
			this.innermousemove(e);
		}
		if (this.hoveredAtom) {
			this.hoveredAtom.isHover = false;
			if (this.hoveredAtom.isSelected) {
				var a = this.hoveredAtom;
				this.selectedAtoms = q.grep(this.selectedAtoms, function(value) {
					return value !== a;
				});
			} else {
				this.selectedAtoms.push(this.hoveredAtom);
			}
			this.hoveredAtom.isSelected = !this.hoveredAtom.isSelected;
			this.hoveredAtom = undefined;
			this.editor.repaint();
		}
		if (this.selectedAtoms.length === this.numToSelect) {
			var shape;
			switch(this.numToSelect){
			case 2:
				shape = new d3.Distance(this.selectedAtoms[0], this.selectedAtoms[1]);
				break;
			case 3:
				shape = new d3.Angle(this.selectedAtoms[0], this.selectedAtoms[1], this.selectedAtoms[2]);
				break;
			case 4:
				shape = new d3.Torsion(this.selectedAtoms[0], this.selectedAtoms[1], this.selectedAtoms[2], this.selectedAtoms[3]);
				break;
			}
			this.reset();
			if(shape){
				this.editor.historyManager.pushUndo(new actions.AddShapeAction(this.editor, shape));
			}
		}
	};

})(ChemDoodle.uis.actions, ChemDoodle.uis.states, ChemDoodle.structures, ChemDoodle.structures.d3, ChemDoodle.lib.jQuery);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//
(function(states) {
	'use strict';
	states.ViewState3D = function(editor) {
		this.setup(editor);
	};
	var _ = states.ViewState3D.prototype = new states._State3D();

})(ChemDoodle.uis.states);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(states) {
	'use strict';
	states.StateManager3D = function(editor) {
		this.STATE_VIEW = new states.ViewState3D(editor);
		this.STATE_MEASURE = new states.MeasureState3D(editor);
		var currentState = this.STATE_VIEW;
		this.setState = function(nextState) {
			if (nextState !== currentState) {
				currentState.exit();
				currentState = nextState;
				currentState.enter();
			}
		};
		this.getCurrentState = function() {
			return currentState;
		};
	};

})(ChemDoodle.uis.states);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//
(function(c, iChemLabs, io, structures, actions, gui, imageDepot, desktop, tools, states, q, document) {
	'use strict';
	gui.ToolbarManager3D = function(editor) {
		this.editor = editor;

		// open
		this.buttonOpen = new desktop.Button(editor.id + '_button_open', imageDepot.OPEN, 'Open', function() {
			editor.dialogManager.loadDialog.getTextArea().val('');
			editor.dialogManager.loadDialog.getElement().dialog('open');
		});
		// save
		this.buttonSave = new desktop.Button(editor.id + '_button_save', imageDepot.SAVE, 'Save', function() {
			if (editor.useServices) {
				editor.dialogManager.saveDialog.clear();
			} else {
				editor.dialogManager.saveDialog.getTextArea().val(c.writeMOL(editor.molecules[0]));
			}
			editor.dialogManager.saveDialog.getElement().dialog('open');
		});
		// search
		this.buttonSearch = new desktop.Button(editor.id + '_button_search', imageDepot.SEARCH, 'Search', function() {
			editor.dialogManager.searchDialog.getElement().dialog('open');
		});
		// calculate
		this.buttonCalculate = new desktop.Button(editor.id + '_button_calculate', imageDepot.CALCULATE, 'Calculate', function() {
			var mol = editor.molecules[0];
			if (mol) {
				iChemLabs.calculate(mol, {
					descriptors : [ 'mf', 'ef', 'mw', 'miw', 'deg_unsat', 'hba', 'hbd', 'rot', 'electron', 'pol_miller', 'cmr', 'tpsa', 'vabc', 'xlogp2', 'bertz' ]
				}, function(content) {
					var sb = [];
					function addDatum(title, value, unit) {
						sb.push(title);
						sb.push(': ');
						for ( var i = title.length + 2; i < 30; i++) {
							sb.push(' ');
						}
						sb.push(value);
						sb.push(' ');
						sb.push(unit);
						sb.push('\n');
					}
					addDatum('Molecular Formula', content.mf, '');
					addDatum('Empirical Formula', content.ef, '');
					addDatum('Molecular Mass', content.mw, 'amu');
					addDatum('Monoisotopic Mass', content.miw, 'amu');
					addDatum('Degree of Unsaturation', content.deg_unsat, '');
					addDatum('Hydrogen Bond Acceptors', content.hba, '');
					addDatum('Hydrogen Bond Donors', content.hbd, '');
					addDatum('Rotatable Bonds', content.rot, '');
					addDatum('Total Electrons', content.rot, '');
					addDatum('Molecular Polarizability', content.pol_miller, 'A^3');
					addDatum('Molar Refractivity', content.cmr, 'cm^3/mol');
					addDatum('Polar Surface Area', content.tpsa, 'A^2');
					addDatum('vdW Volume', content.vabc, 'A^3');
					addDatum('logP', content.xlogp2, '');
					addDatum('Complexity', content.bertz, '');
					editor.dialogManager.calculateDialog.getTextArea().val(sb.join(''));
					editor.dialogManager.calculateDialog.getElement().dialog('open');
				});
			}
		});

		// transform
		this.buttonTransform = new desktop.Button(editor.id + '_button_transform', imageDepot.PERSPECTIVE, 'Transform', function() {
			editor.stateManager.setState(editor.stateManager.STATE_VIEW);
		});
		this.buttonTransform.toggle = true;

		// visual specifications
		this.buttonSettings = new desktop.Button(editor.id + '_button_specifications', imageDepot.SETTINGS, 'Visual Specifications', function() {
			editor.dialogManager.specsDialog.update(editor.specs);
			editor.dialogManager.specsDialog.getElement().dialog('open');
		});

		// animations
		this.buttonAnimation = new desktop.Button(editor.id + '_button_animation', imageDepot.ANIMATION, 'Animations', function() {
			editor.stateManager.setState(editor.stateManager.STATE_MOVE);
		});

		// clear
		this.buttonClear = new desktop.Button(editor.id + '_button_clear', imageDepot.CLEAR, 'Clear', function() {
			editor.historyManager.pushUndo(new actions.ClearAction(editor));
		});
		// clean
		this.buttonClean = new desktop.Button(editor.id + '_button_clean', imageDepot.OPTIMIZE, 'Clean', function() {
			var mol = editor.molecules[0];
			if (mol) {
				iChemLabs.optimize(mol, {
					dimension : 3
				}, function(mol) {
					editor.historyManager.pushUndo(new actions.SwitchMoleculeAction(editor, mol));
				});
			}
		});

		// scale set
		this.makeScaleSet(this);

		// history set
		this.makeHistorySet(this);

		// history set
		this.makeMeasurementsSet(this);
	};
	var _ = gui.ToolbarManager3D.prototype;
	_.write = function() {
		var sb = [ '<div style="font-size:10px;">' ];
		var bg = this.editor.id + '_main_group';
		sb.push(this.historySet.getSource());
		sb.push(this.scaleSet.getSource());
		sb.push(this.buttonOpen.getSource());
		sb.push(this.buttonSave.getSource());
		if (this.editor.useServices) {
			sb.push(this.buttonSearch.getSource());
			sb.push(this.buttonCalculate.getSource());
		}
		sb.push('<br>');
		sb.push(this.buttonTransform.getSource(bg));
		sb.push(this.buttonSettings.getSource());
		//sb.push(this.buttonAnimation.getSource());
		sb.push(this.measurementsSet.getSource(bg));
		sb.push(this.buttonClear.getSource());
		if (this.editor.useServices) {
			sb.push(this.buttonClean.getSource());
		}
		sb.push('</div>');

		if (document.getElementById(this.editor.id)) {
			var canvas = q('#' + this.editor.id);
			canvas.before(sb.join(''));
		} else {
			document.write(sb.join(''));
		}
	};
	_.setup = function() {
		this.buttonTransform.setup(true);
		this.buttonSettings.setup();
		//this.buttonAnimation.setup();
		this.measurementsSet.setup();
		this.buttonClear.setup();
		if (this.editor.useServices) {
			this.buttonClean.setup();
		}
		this.historySet.setup();
		this.scaleSet.setup();
		this.buttonOpen.setup();
		this.buttonSave.setup();
		if (this.editor.useServices) {
			this.buttonSearch.setup();
			this.buttonCalculate.setup();
		}

		this.buttonTransform.select();
		this.buttonUndo.disable();
		this.buttonRedo.disable();
	};

	_.makeScaleSet = function(self) {
		this.scaleSet = new desktop.ButtonSet(self.editor.id + '_buttons_scale');
		this.scaleSet.toggle = false;
		this.buttonScalePlus = new desktop.Button(self.editor.id + '_button_scale_plus', imageDepot.ZOOM_IN, 'Increase Scale', function() {
			self.editor.mousewheel(null, -10);
		});
		this.scaleSet.buttons.push(this.buttonScalePlus);
		this.buttonScaleMinus = new desktop.Button(self.editor.id + '_button_scale_minus', imageDepot.ZOOM_OUT, 'Decrease Scale', function() {
			self.editor.mousewheel(null, 10);
		});
		this.scaleSet.buttons.push(this.buttonScaleMinus);
	};
	_.makeHistorySet = function(self) {
		this.historySet = new desktop.ButtonSet(self.editor.id + '_buttons_history');
		this.historySet.toggle = false;
		this.buttonUndo = new desktop.Button(self.editor.id + '_button_undo', imageDepot.UNDO, 'Undo', function() {
			self.editor.historyManager.undo();
		});
		this.historySet.buttons.push(this.buttonUndo);
		this.buttonRedo = new desktop.Button(self.editor.id + '_button_redo', imageDepot.REDO, 'Redo', function() {
			self.editor.historyManager.redo();
		});
		this.historySet.buttons.push(this.buttonRedo);
	};
	_.makeMeasurementsSet = function(self) {
		this.measurementsSet = new desktop.ButtonSet(self.editor.id + '_buttons_measurements');
		this.buttonDistance = new desktop.Button(self.editor.id + '_button_distance', imageDepot.DISTANCE, 'Distance', function() {
			self.editor.stateManager.STATE_MEASURE.numToSelect = 2;
			self.editor.stateManager.STATE_MEASURE.reset();
			self.editor.stateManager.setState(self.editor.stateManager.STATE_MEASURE);
		});
		this.measurementsSet.buttons.push(this.buttonDistance);
		this.buttonAngle = new desktop.Button(self.editor.id + '_button_angle', imageDepot.ANGLE, 'Angle', function() {
			self.editor.stateManager.STATE_MEASURE.numToSelect = 3;
			self.editor.stateManager.STATE_MEASURE.reset();
			self.editor.stateManager.setState(self.editor.stateManager.STATE_MEASURE);
		});
		this.measurementsSet.buttons.push(this.buttonAngle);
		this.buttonTorsion = new desktop.Button(self.editor.id + '_button_torsion', imageDepot.TORSION, 'Torsion', function() {
			self.editor.stateManager.STATE_MEASURE.numToSelect = 4;
			self.editor.stateManager.STATE_MEASURE.reset();
			self.editor.stateManager.setState(self.editor.stateManager.STATE_MEASURE);
		});
		this.measurementsSet.buttons.push(this.buttonTorsion);
	};

})(ChemDoodle, ChemDoodle.iChemLabs, ChemDoodle.io, ChemDoodle.structures, ChemDoodle.uis.actions, ChemDoodle.uis.gui, ChemDoodle.uis.gui.imageDepot, ChemDoodle.uis.gui.desktop, ChemDoodle.uis.tools, ChemDoodle.uis.states, ChemDoodle.lib.jQuery, document);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(c, desktop, q, document) {
	'use strict';
	desktop.SpecsDialog = function(editor, subid) {
		this.editor = editor;
		this.id = this.editor.id + subid;
	};
	var _ = desktop.SpecsDialog.prototype = new desktop.Dialog();
	_.title = 'Visual Specifications';
	
	_.makeProjectionSet = function(self) {
		this.projectionSet = new desktop.ButtonSet(self.id + '_projection_group');
		this.buttonPerspective = new desktop.TextButton(self.id + '_button_Perspective', 'Perspective',function() {
			self.editor.specs.projectionPerspective_3D = true;
			self.editor.updateScene();
			self.update(editor.specs);
		});
		this.projectionSet.buttons.push(this.buttonPerspective);
		this.buttonOrthographic = new desktop.TextButton(self.id + '_button_Orthographic', 'Orthographic',function() {
			self.editor.specs.projectionPerspective_3D = false;
			self.editor.updateScene(self);
			self.update(editor.specs);
		});
		this.projectionSet.buttons.push(this.buttonOrthographic);
	};
	
	_.makeAtomColorSet = function(self) {
		this.atomColorSet = new desktop.ButtonSet(self.id + '_atom_color_group');
		this.atomColorSet.toggle = true;
		this.buttonJmolColors = new desktop.TextButton(self.id + '_button_Jmol_Colors', 'Jmol', function() {
			self.editor.specs.atoms_useJMOLColors = true;
			self.editor.specs.atoms_usePYMOLColors = false;
			self.editor.updateScene();
			self.update(editor.specs);
		});
		this.atomColorSet.buttons.push(this.buttonJmolColors);
		this.buttonPymolColors = new desktop.TextButton(self.id + '_button_PyMOL_Colors', 'PyMOL', function() {
			self.editor.specs.atoms_usePYMOLColors = true;
			self.editor.specs.atoms_useJMOLColors = false;
			self.editor.updateScene();
			self.update(editor.specs);
		});
		this.atomColorSet.buttons.push(this.buttonPymolColors);
	};
	
	_.makeBondColorSet = function(self) {
		this.bondColorSet = new desktop.ButtonSet(self.id + '_bond_color_group');
		this.bondColorSet.toggle = true;
		this.buttonJmolBondColors = new desktop.TextButton(self.id + '_button_Jmol_Bond_Colors', 'Jmol', function() {
			self.editor.specs.bonds_useJMOLColors = true;
			self.editor.specs.bonds_usePYMOLColors = false;
			self.editor.updateScene();
			self.update(editor.specs);
		});
		this.bondColorSet.buttons.push(this.buttonJmolBondColors);
		this.buttonPymolBondColors = new desktop.TextButton(self.id + '_button_PyMOL_Bond_Colors', 'PyMOL', function() {
			self.editor.specs.bonds_usePYMOLColors = true;
			self.editor.specs.bonds_useJMOLColors = false;
			self.editor.updateScene();
			self.update(editor.specs);
		});
		this.bondColorSet.buttons.push(this.buttonPymolBondColors);
	};
	
	_.makeCompassPositionSet = function(self) {
		this.compassPositionSet = new desktop.ButtonSet(self.id + '_compass_position_group');
		this.buttonCompassCorner = new desktop.TextButton(self.id + '_button_compass_corner', 'Corner',function() {
			self.editor.specs.compass_type_3D = 0;
			self.editor.specs.compass_size_3D = 50;
			self.editor.setupScene();
			self.editor.updateScene();
			self.update(editor.specs);
		});
		this.compassPositionSet.buttons.push(this.buttonCompassCorner);
		this.buttonCompassOrigin = new desktop.TextButton(self.id + '_button_compass_origin', 'Origin',function() {
			self.editor.specs.compass_type_3D = 1;
			self.editor.specs.compass_size_3D = 150;
			self.editor.setupScene();
			self.editor.updateScene();
			self.update(editor.specs);
		});
		this.compassPositionSet.buttons.push(this.buttonCompassOrigin);
	};
	
	_.makeFogModeSet = function(self) {
		this.fogModeSet = new desktop.ButtonSet(self.id + '_fog_mode_group');
		this.buttonFogMode0 = new desktop.TextButton(self.id + '_button_fog_mode_0', 'No Fogging', function() {
			self.editor.specs.fog_mode_3D = 0;
			self.editor.updateScene();
			self.update(editor.specs);
		});
		this.fogModeSet.buttons.push(this.buttonFogMode0);
		this.buttonFogMode1 = new desktop.TextButton(self.id + '_button_fog_mode_1', 'Linear', function() {
			self.editor.specs.fog_mode_3D = 1;
			self.editor.updateScene();
			self.update(editor.specs);
		});
		this.fogModeSet.buttons.push(this.buttonFogMode1);
		this.buttonFogMode2 = new desktop.TextButton(self.id + '_button_fog_mode_2', 'Exponential', function() {
			self.editor.specs.fog_mode_3D = 2;
			self.editor.updateScene();
			self.update(editor.specs);
		});
		this.fogModeSet.buttons.push(this.buttonFogMode2);
		this.buttonFogMode3 = new desktop.TextButton(self.id + '_button_fog_mode_3', 'Exponential&sup2;', function() {
			self.editor.specs.fog_mode_3D = 3;
			self.editor.updateScene();
			self.update(editor.specs);
		});
		this.fogModeSet.buttons.push(this.buttonFogMode3);
	};
	
	_.setup = function(self, editor) {
		// canvas
		this.makeProjectionSet(this);
		this.bgcolor = new desktop.ColorPicker(this.id + '_bgcolor', 'Background Color: ', function(hex) {editor.specs.backgroundColor = hex;editor.setupScene();editor.repaint();self.update(editor.specs);});
		this.makeFogModeSet(this);
		this.fogcolor = new desktop.ColorPicker(this.id + '_fogcolor', 'Fog Color: ', function(hex) {editor.specs.fog_color_3D = hex;editor.setupScene();editor.repaint();self.update(editor.specs);});
		
		// atoms
		this.atomsDisplayToggle = new desktop.CheckBox(this.id + '_atoms_display_toggle', 'Display atoms', function() { editor.specs.atoms_display=!editor.specs.atoms_display;editor.updateScene();self.update(editor.specs);}, true);
		this.atomcolor = new desktop.ColorPicker(this.id + '_atomcolor', 'Atom Color: ', function(hex) {editor.specs.atoms_color = hex;editor.setupScene();editor.repaint();self.update(editor.specs);});
		this.makeAtomColorSet(this);
		this.atomColorSetToggle = new desktop.CheckBox(this.id + '_atom_color_group_toggle', 'Color Schemes', function() { 
				if (self.buttonJmolColors.getElement().prop('disabled')) { 
					self.atomColorSet.enable();
					editor.specs.atoms_useJMOLColors = true;
				} else { 
					self.atomColorSet.disable();
					editor.specs.atoms_useJMOLColors = false;
					editor.specs.atoms_usePYMOLColors = false;
					self.buttonJmolColors.uncheck();
					self.buttonPymolColors.uncheck();
				}
				editor.updateScene();
				self.update(editor.specs);
			}, false);
		this.vdwToggle = new desktop.CheckBox(this.id + '_vdw_toggle', 'Use VDW Diameters', function() { editor.specs.atoms_useVDWDiameters_3D=!editor.specs.atoms_useVDWDiameters_3D;editor.updateScene();self.update(editor.specs); }, false);
		this.atomsNonBondedAsStarsToggle = new desktop.CheckBox(this.id + '_non_bonded_as_stars_toggle', 'Non-bonded as stars', function() { editor.specs.atoms_nonBondedAsStars_3D=!editor.specs.atoms_nonBondedAsStars_3D;editor.updateScene();self.update(editor.specs); }, false);
		this.displayLabelsToggle = new desktop.CheckBox(this.id + '_display_labels_toggle', 'Atom labels', function() { editor.specs.atoms_displayLabels_3D=!editor.specs.atoms_displayLabels_3D;editor.updateScene();self.update(editor.specs); }, false);
		
		//bonds
		this.bondsDisplayToggle = new desktop.CheckBox(this.id + '_bonds_display_toggle', 'Display bonds', function() { editor.specs.bonds_display=!editor.specs.bonds_display;editor.updateScene();self.update(editor.specs);}, true);
		this.bondcolor = new desktop.ColorPicker(this.id + '_bondcolor', 'Bond Color: ', function(hex) {editor.specs.bonds_color = hex;editor.setupScene();editor.repaint();self.update(editor.specs);});
		this.makeBondColorSet(this);
		this.bondColorSetToggle =  new desktop.CheckBox(this.id + '_bond_color_group_toggle', 'Color Schemes', function() { 
			if (self.buttonJmolBondColors.getElement().prop('disabled')) { 
				self.bondColorSet.enable(); 
				editor.specs.bonds_useJMOLColors = true;
			} else { 
				self.bondColorSet.disable();
				editor.specs.bonds_useJMOLColors = false;
				editor.specs.bonds_usePYMOLColors = false;
				self.buttonJmolBondColors.uncheck();
				self.buttonPymolBondColors.uncheck();
				
			} 
			editor.updateScene();
			self.update(editor.specs);
		}, false);
		this.bondOrderToggle = new desktop.CheckBox(this.id + '_bond_order_toggle', 'Show order', function() { editor.specs.bonds_showBondOrders_3D=!editor.specs.bonds_showBondOrders_3D;editor.updateScene();self.update(editor.specs); }, false);
		this.bondsRenderAsLinesToggle = new desktop.CheckBox(this.id + '_bonds_render_as_lines_toggle', 'Render as lines', function() { editor.specs.bonds_renderAsLines_3D=!editor.specs.bonds_renderAsLines_3D;editor.updateScene();self.update(editor.specs);}, false);
		
		// proteins
		this.ribbonsToggle = new desktop.CheckBox(this.id + '_ribbons_toggle', 'Ribbons', function() { editor.specs.proteins_displayRibbon=!editor.specs.proteins_displayRibbon;editor.updateScene();self.update(editor.specs); }, false);
		this.backboneToggle = new desktop.CheckBox(this.id + '_backbone_toggle', 'Backbone', function() { editor.specs.proteins_displayBackbone=!editor.specs.proteins_displayBackbone;editor.updateScene();self.update(editor.specs); }, false);
		this.pipeplankToggle = new desktop.CheckBox(this.id + '_pipeplank_toggle', 'Pipe and Plank', function() { editor.specs.proteins_displayPipePlank=!editor.specs.proteins_displayPipePlank;editor.updateScene();self.update(editor.specs); }, false);
		this.cartoonizeToggle = new desktop.CheckBox(this.id + '_cartoonize_toggle', 'Cartoonize', function() { editor.specs.proteins_ribbonCartoonize=!editor.specs.proteins_ribbonCartoonize;editor.updateScene();self.update(editor.specs); }, false);
		this.colorByChainToggle = new desktop.CheckBox(this.id + '_color_by_chain_toggle', 'Color by Chain', function() { editor.specs.macro_colorByChain=!editor.specs.macro_colorByChain;editor.updateScene();self.update(editor.specs); }, false);
		this.proteinColorToggle = new desktop.CheckBox(this.id + '_protein_color_toggle', 'Color by Segment', function() { 
			if (self.proteinColorToggle.checked) {
				editor.specs.proteins_residueColor = 'none';
				self.proteinColorToggle.uncheck();
				q('#proteinColors').prop('disabled', true);
			} else {
				self.proteinColorToggle.check();
				q('#proteinColors').removeAttr('disabled');
				editor.specs.proteins_residueColor = q('#proteinColors').val();
			}
			editor.updateScene();
			self.update(editor.specs);}, false);
		
		//nucleics
		this.nucleicAcidColorToggle = new desktop.CheckBox(this.id + '_nucleic_acid_color_toggle', 'Color by Segment', function() { 
			if (self.nucleicAcidColorToggle.checked) {
				editor.specs.nucleics_residueColor = 'none';
				self.nucleicAcidColorToggle.uncheck();
				q('#nucleicColors').prop('disabled', true);
			} else {
				self.nucleicAcidColorToggle.check();
				q('#nucleicColors').removeAttr('disabled');
				editor.specs.nucleics_residueColor = q('#nucleicColors').val();
			}
			editor.updateScene();
			self.update(editor.specs);}, false);
		
		// text
		//this.boldTextToggle = new desktop.CheckBox(this.id + '_bold_text_toggle', 'Bold', function() { editor.specs.text_font_bold=!editor.specs.text_font_bold;editor.updateScene();self.update(editor.specs); }, false);
		//this.italicTextToggle = new desktop.CheckBox(this.id + '_italic_text_toggle', 'Italic', function() { editor.specs.text_font_italics=!editor.specs.text_font_italics;editor.updateScene();self.update(editor.specs); }, false);
		
		// shapes
		this.shapecolor = new desktop.ColorPicker(this.id + '_shapecolor', 'Shape Color: ', function(hex) {editor.specs.shapes_color = hex;editor.setupScene();editor.repaint();self.update(editor.specs);});
		
		// compass
		this.displayCompassToggle = new desktop.CheckBox(this.id + '_display_compass_toggle', 'Display Compass', function() { 
			if (self.displayCompassToggle.checked) { 
				editor.specs.compass_display = false;
				editor.setupScene();
				editor.updateScene();
				self.compassPositionSet.disable();
				self.buttonCompassCorner.uncheck();
				self.displayCompassToggle.uncheck();
				self.update(editor.specs);
			} else { 
				editor.specs.compass_display = true;
				editor.specs.compass_type_3D = 0;
				editor.specs.compass_size_3D = 50;
				self.compassPositionSet.enable();
				self.displayCompassToggle.check();
				self.buttonCompassCorner.check();
				editor.setupScene();
				editor.updateScene();
				self.update(editor.specs);
			} 
		}, false);
		this.makeCompassPositionSet(this);
		//this.axisLabelsToggle = new desktop.CheckBox(this.id + '_axis_labels_toggle', 'Axis Labels', function() { editor.specs.compass_displayText_3D=!editor.specs.compass_displayText_3D;editor.updateScene();self.update(editor.specs); }, false);
		
		var sb = [];
		sb.push('<div style="font-size:12px;text-align:left;overflow-y:scroll;height:300px;" id="');
		sb.push(this.id);
		sb.push('" title="');
		sb.push(this.title);
		sb.push('">');
		if (this.message) {
			sb.push('<p>');
			sb.push(this.message);
			sb.push('</p>');
		}
		sb.push('<p><strong>Representation</strong>');
		sb.push('<p><select id="reps"><option value="Ball and Stick">Ball and Stick</option><option value="van der Waals Spheres">vdW Spheres</option><option value="Stick">Stick</option><option value="Wireframe">Wireframe</option><option value="Line">Line</option></select></p>');
		sb.push('<hr><strong>Canvas</strong>');
		sb.push(this.bgcolor.getSource());
		sb.push('<p>Projection: ');
		sb.push(this.projectionSet.getSource(this.id + '_projection_group'));
		sb.push('</p><p>Fog Mode: ');
		sb.push(this.fogModeSet.getSource(this.id + '_fog_mode_group'));
		sb.push(this.fogcolor.getSource());
		sb.push('</p><p>Fog start: <input type="number" id="fogstart" min="0" max="100" value="0"> %</p>');
		sb.push('</p><p>Fog end: <input type="number" id="fogend" min="0" max="100" value="100"> %</p>');
		sb.push('</p><p>Fog density: <input type="number" id="fogdensity" min="0" max="100" value="100"> %</p>');
		sb.push('<hr><strong>Atoms</strong><p>');
		sb.push(this.atomsDisplayToggle.getSource());
		sb.push('</p><p>');
		sb.push(this.atomcolor.getSource());
		sb.push('</p><p>Sphere diameter: <input type="number" id="spherediameter" min="0" max="40" value="0.8" step="0.01"> Angstroms</p>');
		sb.push(this.vdwToggle.getSource());
		sb.push('</p><p>VDW Multiplier: <input type="number" id="vdwMultiplier" min="0" max="100" value="100"> %</p>');
		sb.push(this.atomsNonBondedAsStarsToggle.getSource());
		sb.push('</p><p>');
		sb.push(this.displayLabelsToggle.getSource());
		sb.push('</p><p>');
		sb.push(this.atomColorSetToggle.getSource());
		sb.push(': ');
		sb.push(this.atomColorSet.getSource(this.id + '_atom_color_group'));
		sb.push('</p><hr><strong>Bonds</strong><p>');
		sb.push(this.bondsDisplayToggle.getSource());
		sb.push('</p><p>');
		sb.push(this.bondcolor.getSource());
		sb.push(this.bondColorSetToggle.getSource());
		sb.push(': ');
		sb.push(this.bondColorSet.getSource(this.id + '_bond_color_group'));
		sb.push('</p><p>');
		sb.push(this.bondOrderToggle.getSource());
		sb.push('</p><p>Cylinder diameter: <input type="number" id="cylinderdiameter" min="0" max="40" value="0.3" step="0.01"> Angstroms</p>');
		sb.push('</p><hr><strong>Proteins</strong>');
		sb.push('<p>');
		sb.push(this.ribbonsToggle.getSource());
		sb.push('</p><p>');
		sb.push(this.backboneToggle.getSource());
		sb.push('</p><p>');
		sb.push(this.pipeplankToggle.getSource());
		sb.push('</p><p>');
		sb.push(this.cartoonizeToggle.getSource());
		sb.push('</p><p>');
		sb.push(this.colorByChainToggle.getSource());
		sb.push('</p><p>');
		sb.push(this.proteinColorToggle.getSource());
		sb.push('<select id="proteinColors" disabled><option value="amino">Amino</option><option value="shapely">Shapely</option><option value="polarity">Polarity</option><option value="rainbow">Rainbow</option><option value="acidity">Acidity</option></select></p>');
		sb.push('<hr><strong>Nucleic Acids</strong><p>');
		sb.push(this.nucleicAcidColorToggle.getSource());
		sb.push(': ');
		sb.push('<select id="nucleicColors" disabled><option value="shapely">Shapely</option><option value="rainbow">Rainbow</option></select></p>');
		//sb.push('<hr><strong>Text</strong>');
		//sb.push('<p><table style="font-size:12px;text-align:left;border-spacing:0px"><tr><td><p>Text Color: </p></td><td><input id="textcolor" name="textcolor" class="simple_color" value="#000000" /></td></tr></table></p>');
		//sb.push('<p>Font Styles: ');
		//sb.push(this.boldTextToggle.getSource());
		//sb.push(this.italicTextToggle.getSource());
		//sb.push('</p>');
		sb.push('<hr><strong>Shapes</strong><p>');
		sb.push(this.shapecolor.getSource());
		sb.push('</p><hr><strong>Compass</strong>');
		sb.push('<p>');
		sb.push(this.displayCompassToggle.getSource());
		sb.push(': ');
		sb.push(this.compassPositionSet.getSource(this.id + '_compass_position_group'));
		//sb.push('</p><p>');
		sb.push('</p>');
		//sb.push(this.axisLabelsToggle.getSource());
		//sb.push('</p><table style="font-size:12px;text-align:left;border-spacing:0px"><tr><td>Axis Colors: </td><td><label for="xaxis">X</label></td><td><input id="xaxis" name="xaxis" class="simple_color" value="#FF0000" /></td><td><label for="yaxis">Y</label></td><td><input id="yaxis" name="yaxis" class="simple_color" value="#00FF00" /></td><td><label for="zaxis">Z</label></td><td><input id="zaxis" name="zaxis" class="simple_color" value="#0000FF" /></td></tr></table>');
		sb.push('</div>');
		if (this.afterMessage) {
			sb.push('<p>');
			sb.push(this.afterMessage);
			sb.push('</p>');
		}
		document.writeln(sb.join(''));
		this.getElement().dialog({
			autoOpen : false,
			position : {my: "center", at:"center", of:document },
			buttons : self.buttons,
			width : 500,
			height: 300,
			open : function(event, ui) {
				q(this).height(300);
				q(this).width(478);
				q(this).dialog('option', 'position', 'center');
			}
		});
		this.bgcolor.setup();
		this.fogcolor.setup();
		this.atomcolor.setup();
		this.bondcolor.setup();
		this.shapecolor.setup();	
		q('#reps').change(function() {
			var i = this.selectedIndex;
			var ops = this.options;
			editor.specs.set3DRepresentation(ops[i].value);
			editor.updateScene();
			self.update(editor.specs);
		});
		q('#proteinColors').change(function() {
			var i = this.selectedIndex;
			switch(i) {
			case 0:
				editor.specs.proteins_residueColor = 'amino';
				break;
			case 1:
				editor.specs.proteins_residueColor = 'shapely';
				break;
			case 2:
				editor.specs.proteins_residueColor = 'polarity';
				break;
			case 3:
				editor.specs.proteins_residueColor = 'rainbow';
				break;
			case 4:
				editor.specs.proteins_residueColor = 'acidity';
				break;
			}
				
			editor.updateScene();
			self.update(editor.specs);
		});
		q('#nucleicColors').change(function() {
			var i = this.selectedIndex;
			switch(i) {
			case 0:
				editor.specs.nucleics_residueColor = 'shapely';
				break;
			case 1:
				editor.specs.nucleics_residueColor = 'rainbow';
				break;
			}
				
			editor.updateScene();
			self.update(editor.specs);
		});
		
		q('#fogstart').change(function() {
			editor.specs.fog_start_3D = parseInt(this.value)/100;
			editor.updateScene();
		});
		q('#fogend').change(function() {
			editor.specs.fog_end_3D = parseInt(this.value)/100;
			editor.updateScene();
		});
		q('#fogdensity').change(function() {
			editor.specs.fog_density_3D = parseInt(this.value)/100;
			editor.updateScene();
		});
		q('#vdwMultiplier').change(function() {
			editor.specs.atoms_vdwMultiplier_3D = parseInt(this.value)/100;
			editor.updateScene();
		});
		q('#spherediameter').change(function() {
			editor.specs.atoms_sphereDiameter_3D = parseFloat(this.value);
			editor.updateScene();
		});
		q('#cylinderdiameter').change(function() {
			editor.specs.bonds_cylinderDiameter_3D = parseFloat(this.value);
			editor.updateScene();
		});
		
		this.projectionSet.setup();
		this.fogModeSet.setup();
		this.atomsDisplayToggle.setup();
		this.vdwToggle.setup();
		this.atomsNonBondedAsStarsToggle.setup();
		this.displayLabelsToggle.setup();
		this.atomColorSet.setup();
		this.atomColorSet.disable();
		this.atomColorSetToggle.setup();
		this.bondsDisplayToggle.setup();
		this.bondColorSet.setup();
		this.bondColorSet.disable();
		this.bondColorSetToggle.setup();
		this.bondOrderToggle.setup();
		this.ribbonsToggle.setup();
		this.backboneToggle.setup();
		this.pipeplankToggle.setup();
		this.cartoonizeToggle.setup();
		this.colorByChainToggle.setup();
		this.proteinColorToggle.setup();
		this.nucleicAcidColorToggle.setup();
		//this.boldTextToggle.setup();
		//this.italicTextToggle.setup();
		this.displayCompassToggle.setup();
		this.compassPositionSet.setup();
		this.compassPositionSet.disable();
		//this.axisLabelsToggle.setup();
	};
	_.update = function(specs){
		this.bgcolor.setColor(specs.backgroundColor);
		this.fogcolor.setColor(specs.fog_color_3D);
		this.atomcolor.setColor(specs.atoms_color);
		this.bondcolor.setColor(specs.bonds_color);
		this.shapecolor.setColor(specs.shapes_color);
		if (specs.projectionPerspective_3D) {
			this.buttonPerspective.select();
		} else {
			this.buttonOrthographic.select();
		}
		switch(specs.fog_mode_3D) {
		case 1:
			this.buttonFogMode0.uncheck();
			this.buttonFogMode1.check();
			this.buttonFogMode2.uncheck();
			this.buttonFogMode3.uncheck();
			break;
		case 2:
			this.buttonFogMode0.uncheck();
			this.buttonFogMode1.uncheck();
			this.buttonFogMode2.check();
			this.buttonFogMode3.uncheck();
			break;
		case 3:
			this.buttonFogMode0.uncheck();
			this.buttonFogMode1.uncheck();
			this.buttonFogMode2.uncheck();
			this.buttonFogMode3.check();
			break;
		default:
			this.buttonFogMode0.check();
			this.buttonFogMode1.uncheck();
			this.buttonFogMode2.uncheck();
			this.buttonFogMode3.uncheck();
			break;
		}
		q('#fogstart').val(specs.fog_start_3D * 100);
		q('#fogend').val(specs.fog_end_3D * 100);
		q('#fogdensity').val(specs.fog_density_3D * 100);
		if (specs.atoms_display) {
			this.atomsDisplayToggle.check();
		} else {
			this.atomsDisplayToggle.uncheck();
		}
		if (specs.atoms_useVDWDiameters_3D) {
			this.vdwToggle.check();
			q('#spherediameter').prop('disabled', true);
			q('#vdwMultiplier').prop('disabled', false);
			q('#vdwMultiplier').val(specs.atoms_vdwMultiplier_3D * 100);
		} else {
			this.vdwToggle.uncheck();
			q('#spherediameter').prop('disabled', false);
			q('#spherediameter').val(specs.atoms_sphereDiameter_3D);
			q('#vdwMultiplier').prop('disabled', true);
		}
		if (specs.atoms_useJMOLColors || specs.atoms_usePYMOLColors) {
			this.atomColorSetToggle.check();
			this.atomColorSet.enable();
			if (specs.atoms_useJMOLColors) {
				this.buttonJmolColors.check();
				this.buttonPymolColors.uncheck();
			} else if (specs.atoms_usePYMOLColors) {
				this.buttonJmolColors.uncheck();
				this.buttonPymolColors.check();
			}
		} else {
			this.atomColorSetToggle.uncheck();
			this.buttonPymolColors.uncheck();
			this.buttonJmolColors.uncheck();
			this.atomColorSet.disable();
		}
		if (specs.atoms_nonBondedAsStars_3D) {
			this.atomsNonBondedAsStarsToggle.check();
		} else {
			this.atomsNonBondedAsStarsToggle.uncheck();
		}
		if (specs.atoms_displayLabels_3D) {
			this.displayLabelsToggle.check();
		} else {
			this.displayLabelsToggle.uncheck();
		}
		if (specs.bonds_display) {
			this.bondsDisplayToggle.check();
		} else {
			this.bondsDisplayToggle.uncheck();
		}
		if (specs.bonds_useJMOLColors || specs.bonds_usePYMOLColors) {
			this.bondColorSetToggle.check();
			this.bondColorSet.enable();
			if (specs.bonds_useJMOLColors) {
				this.buttonJmolBondColors.check();
				this.buttonPymolBondColors.uncheck();
			} else if (specs.atoms_usePYMOLColors) {
				this.buttonJmolBondColors.uncheck();
				this.buttonPymolBondColors.check();
			}
		} else {
			this.bondColorSetToggle.uncheck();
			this.buttonPymolBondColors.uncheck();
			this.buttonJmolBondColors.uncheck();
			this.bondColorSet.disable();
		}
		if (specs.bonds_showBondOrders_3D) {
			this.bondOrderToggle.check();
		} else {
			this.bondOrderToggle.uncheck();
		}
		q('#cylinderdiameter').val(specs.bonds_cylinderDiameter_3D);
		if (specs.proteins_displayRibbon) {
			this.ribbonsToggle.check();
		} else {
			this.ribbonsToggle.uncheck();
		}
		if (specs.proteins_displayBackbone) {
			this.backboneToggle.check();
		} else {
			this.backboneToggle.uncheck();
		}
		if (specs.proteins_displayPipePlank) {
			this.pipeplankToggle.check();
		} else {
			this.pipeplankToggle.uncheck();
		}
		if (specs.proteins_ribbonCartoonize) {
			this.cartoonizeToggle.check();
		} else {
			this.cartoonizeToggle.uncheck();
		}
		if (specs.macro_colorByChain) {
			this.colorByChainToggle.check();
		} else {
			this.colorByChainToggle.uncheck();
		}
		switch (specs.proteins_residueColor) {
		case 'amino':
			this.proteinColorToggle.check();
			q('#proteinColors').val('amino');
			break;
		case 'shapely':
			this.proteinColorToggle.check();
			q('#proteinColors').val('shapely');
			break;
		case 'polarity':
			this.proteinColorToggle.check();
			q('#proteinColors').val('polarity');
			break;
		case 'rainbow':
			this.proteinColorToggle.check();
			q('#proteinColors').val('rainbow');
			break;
		case 'acidity':
			this.proteinColorToggle.check();
			q('#proteinColors').val('acidity');
			break;
		case 'none':
		default:
			this.proteinColorToggle.uncheck();
			q('#proteinColors').prop('disabled', true);
			break;
		}
		switch (specs.nucleics_residueColor) {
		case 'shapely':
			this.nucleicAcidColorToggle.check();
			q('#nucleicColors').val('shapely');
			break;
		case 'rainbow':
			this.nucleicAcidColorToggle.check();
			q('#nucleicColors').val('rainbow');
			break;
		case 'none':
		default:
			this.nucleicAcidColorToggle.uncheck();
			q('#nucleicColors').prop('disabled', true);
			break;
		}
		/*
		if (specs.text_font_bold) {
			this.boldTextToggle.check();
		}
		if (specs.text_font_italic) {
			this.italicTextToggle.check();
		}*/
		if (specs.compass_display == true) {
			this.compassPositionSet.enable();
			if (specs.compass_type_3D == 0) {
				this.buttonCompassCorner.check();
				this.buttonCompassOrigin.uncheck();
			} else {
				this.buttonCompassOrigin.check();
				this.buttonCompassCorner.uncheck();
			}
		} else {
			this.compassPositionSet.disable();
			this.buttonCompassCorner.uncheck();
			this.buttonCompassOrigin.uncheck();
		}
		/*if (specs.compass_display_text_3D) {
			this.axisLabelsToggle.check();
		} else {
			this.axisLabelsToggle.uncheck();
		} */
	};

})(ChemDoodle, ChemDoodle.uis.gui.desktop, ChemDoodle.lib.jQuery, document);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(c, extensions, featureDetection, d3, sketcherPack, structures, tools, q, m, m4, window) {
	'use strict';
	c.EditorCanvas3D = function(id, width, height, options) {
		// keep checks to undefined here as these are booleans
		this.isMobile = options.isMobile === undefined ? featureDetection.supports_touch() : options.isMobile;
		this.useServices = options.useServices === undefined ? false : options.useServices;
		this.includeToolbar = options.includeToolbar === undefined ? true : options.includeToolbar;
		this.oneMolecule = true;
		// toolbar manager needs the editor id to make it unique to this
		// canvas
		this.id = id;
		this.toolbarManager = new sketcherPack.gui.ToolbarManager3D(this);
		if (this.includeToolbar) {
			this.toolbarManager.write();
			// If pre-created, wait until the last button image loads before
			// calling setup.
			var self = this;
			if (document.getElementById(this.id)) {
				q('#' + id + '_button_calculate').load(function() {
					self.toolbarManager.setup();
				});
			} else {
				q(window).load(function() {
					self.toolbarManager.setup();
				});
			}
			this.dialogManager = new sketcherPack.gui.DialogManager(this);
		}
		this.stateManager = new sketcherPack.states.StateManager3D(this);
		this.historyManager = new sketcherPack.actions.HistoryManager(this);
		if (id) {
			this.create(id, width, height);
		}
		// specs for draw "help" atom
		var helpSpecs = new structures.VisualSpecifications();
		helpSpecs.atoms_useVDWDiameters_3D = false;
		helpSpecs.atoms_sphereDiameter_3D = 2;
		this.helpButton = new structures.Atom('C', 0, 0, 0);
		this.helpButton.isHover = true;
		this.helpButton.specs = helpSpecs;
		this.specs.backgroundColor = '#000';
		this.specs.shapes_color = '#fff';
		this.isHelp = false;
		this.setupScene();
		this.repaint();
	};
	var _ = c.EditorCanvas3D.prototype = new c._Canvas3D();
	// saves of default behavior
	_.defaultmousedown = _.mousedown;
	_.defaultmouseup = _.mouseup;
	_.defaultrightmousedown = _.rightmousedown;
	_.defaultdrag = _.drag;
	_.defaultmousewheel = _.mousewheel;
	_.drawChildExtras = function(gl) {

		// NOTE: gl and this.gl is same object because "EditorCanvas3D" inherit
		// from "_Canvas3D"

		var pUniform = gl.getUniformLocation(gl.program, 'u_projection_matrix');

		var translationMatrix = m4.create();

		var textImage = new d3.TextImage();
		textImage.init(gl);

		var textMesh = new d3.TextMesh();
		textMesh.init(gl);

		var height = this.height / 20;
		var tanTheta = m.tan(this.specs.projectionPerspectiveVerticalFieldOfView_3D / 360 * m.PI);
		var depth = height / tanTheta;
		var near = m.max(depth - height, 0.1);
		var far = depth + height;

		var aspec = this.width / this.height;

		var nearRatio = depth / this.height * tanTheta;
		var top = tanTheta * depth;
		var bottom = -top;
		var left = aspec * bottom;
		var right = aspec * top;

		var projectionMatrix = m4.ortho(left, right, bottom, top, near, far, []);

		gl.uniformMatrix4fv(pUniform, false, projectionMatrix);

		gl.fogging.setMode(0);

		if (!this.hideHelp) {
			// help and tutorial

			var posX = (this.width - 40) * nearRatio;
			var posY = (this.height - 40) * nearRatio;

			m4.translate(m4.identity([]), [ posX, posY, -depth ], translationMatrix);

			// setting "help" button color
			gl.material.setTempColors(this.specs.bonds_materialAmbientColor_3D, undefined, this.specs.bonds_materialSpecularColor_3D, this.specs.bonds_materialShininess_3D);
			gl.material.setDiffuseColor('#00ff00');

			// this "gl.modelViewMatrix" must be set because it used by Atom
			// when rendered
			gl.modelViewMatrix = m4.multiply(translationMatrix, gl.rotationMatrix, []);

			gl.sphereBuffer.bindBuffers(this.gl);
			this.helpButton.render(gl, undefined, true);
			if(this.isHelp){
				gl.sphereBuffer.bindBuffers(gl);
				// colors
				gl.blendFunc(gl.SRC_ALPHA, gl.ONE);
				gl.material.setTempColors('#000000', undefined, '#000000', 0);
				gl.enable(gl.BLEND);
				gl.depthMask(false);
				gl.material.setAlpha(.4);
				this.helpButton.renderHighlight(gl, undefined);
				gl.depthMask(true);
				gl.disable(gl.BLEND);
				gl.blendFuncSeparate(gl.SRC_ALPHA, gl.ONE_MINUS_SRC_ALPHA, gl.ONE, gl.ONE_MINUS_SRC_ALPHA);
			}

			// enable blend and depth mask set to false
			gl.enable(gl.BLEND);
			gl.depthMask(false);

			// prepare for the "?" part
			textImage.updateFont(gl, 14.1, [ 'sans-serif' ], false, false, true);
			textImage.useTexture(gl);

			// vertex data for draw "?"
			var vertexData = {
				position : [],
				texCoord : [],
				translation : []
			};

			textImage.pushVertexData('?', [ 0, 0, 0 ], 0, vertexData);
			textMesh.storeData(gl, vertexData.position, vertexData.texCoord, vertexData.translation);

			// enable vertex for draw text
			gl.enableVertexAttribArray(gl.shader.vertexTexCoordAttribute);

			var modelMatrix = m4.multiply(translationMatrix, m4.identity([]), []);
			gl.setMatrixUniforms(modelMatrix);

			textImage.useTexture(gl);
			textMesh.render(gl);

			// disable vertex for draw text
			gl.disableVertexAttribArray(gl.shader.vertexTexCoordAttribute);

			// disable blend and depth mask set to true
			gl.disable(gl.BLEND);
			gl.depthMask(true);
		}

		if (!this.paidToHideTrademark) {
			// You must keep this name displayed at all times to abide by the
			// license
			// Contact us for permission to remove it,
			// http://www.ichemlabs.com/contact-us
			var x = '\x43\x68\x65\x6D\x44\x6F\x6F\x64\x6C\x65';

			// enable blend for transparancy
			gl.enable(this.gl.BLEND);

			// enable vertex for draw text
			gl.enableVertexAttribArray(gl.shader.vertexTexCoordAttribute);

			// Draw the "ChemDoodle" part
			textImage.updateFont(gl, 14.1, [ 'sans-serif' ], false, false, true);
			textImage.useTexture(gl);

			var width = textImage.textWidth(x);

			var vertexData = {
				position : [],
				texCoord : [],
				translation : []
			};

			textImage.pushVertexData(x, [ 0, 0, 0 ], 0, vertexData);
			textMesh.storeData(gl, vertexData.position, vertexData.texCoord, vertexData.translation);

			var posX = (this.width - width - 30) * nearRatio;
			var posY = (-this.height + 24) * nearRatio;

			m4.translate(m4.identity([]), [ posX, posY, -depth ], translationMatrix);
			var modelMatrix = m4.multiply(translationMatrix, gl.rotationMatrix, []);
			gl.setMatrixUniforms(modelMatrix);

			textMesh.render(gl);

			// Draw the (R) part
			textImage.updateFont(gl, 8, [ 'sans-serif' ], false, false, true);
			textImage.useTexture(gl);

			var vertexData = {
				position : [],
				texCoord : [],
				translation : []
			};

			textImage.pushVertexData('\u00AE', [ 0, 0, 0 ], 0, vertexData);

			textMesh.storeData(gl, vertexData.position, vertexData.texCoord, vertexData.translation);

			var posX = (this.width - 24) * nearRatio;
			var posY = (-this.height + 30) * nearRatio;

			m4.translate(m4.identity([]), [ posX, posY, -depth ], translationMatrix);
			var modelMatrix = m4.multiply(translationMatrix, gl.rotationMatrix, []);
			gl.setMatrixUniforms(modelMatrix);

			textMesh.render(gl);

			// disable vertex for draw text
			gl.disableVertexAttribArray(gl.shader.vertexTexCoordAttribute);

			// disable blend
			gl.disable(gl.BLEND);
		}
	};
	// desktop events
	_.click = function(e) {
		this.stateManager.getCurrentState().click(e);
	};
	_.rightclick = function(e) {
		this.stateManager.getCurrentState().rightclick(e);
	};
	_.dblclick = function(e) {
		this.stateManager.getCurrentState().dblclick(e);
	};
	_.mousedown = function(e) {
		this.stateManager.getCurrentState().mousedown(e);
	};
	_.rightmousedown = function(e) {
		this.stateManager.getCurrentState().rightmousedown(e);
	};
	_.mousemove = function(e) {
		this.isHelp = false;
		if (e.p.distance(new structures.Point(this.width - 20, 20)) < 10) {
			this.isHelp = true;
		}
		this.stateManager.getCurrentState().mousemove(e);
	};
	_.mouseout = function(e) {
		this.stateManager.getCurrentState().mouseout(e);
	};
	_.mouseover = function(e) {
		this.stateManager.getCurrentState().mouseover(e);
	};
	_.mouseup = function(e) {
		this.stateManager.getCurrentState().mouseup(e);
	};
	_.rightmouseup = function(e) {
		this.stateManager.getCurrentState().rightmouseup(e);
	};
	_.mousewheel = function(e, delta) {
		this.stateManager.getCurrentState().mousewheel(e, delta);
	};
	_.drag = function(e) {
		this.stateManager.getCurrentState().drag(e);
	};
	_.keydown = function(e) {
		this.stateManager.getCurrentState().keydown(e);
	};
	_.keypress = function(e) {
		this.stateManager.getCurrentState().keypress(e);
	};
	_.keyup = function(e) {
		this.stateManager.getCurrentState().keyup(e);
	};

})(ChemDoodle, ChemDoodle.extensions, ChemDoodle.featureDetection, ChemDoodle.structures.d3, ChemDoodle.uis, ChemDoodle.structures, ChemDoodle.uis.tools, ChemDoodle.lib.jQuery, Math, ChemDoodle.lib.mat4, window);
