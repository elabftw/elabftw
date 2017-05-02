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
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//
var ChemDoodle = (function() {
	'use strict';
	var c = {};

	c.iChemLabs = {};
	c.informatics = {};
	c.io = {};
	c.lib = {};
	c.notations = {};
	c.structures = {};
	c.structures.d2 = {};
	c.structures.d3 = {};

	var VERSION = '7.0.2';

	c.getVersion = function() {
		return VERSION;
	};

	return c;

})();
/*!
 * jQuery JavaScript Library v1.10.2
 * http://jquery.com/
 *
 * Includes Sizzle.js
 * http://sizzlejs.com/
 *
 * Copyright 2005, 2013 jQuery Foundation, Inc. and other contributors
 * Released under the MIT license
 * http://jquery.org/license
 *
 * Date: 2013-07-03T13:48Z
 */
(function( window, lib, undefined ) {

// Can't do this because several apps including ASP.NET trace
// the stack via arguments.caller.callee and Firefox dies if
// you try to trace through "use strict" call chains. (#13335)
// Support: Firefox 18+
//"use strict";
var
	// The deferred used on DOM ready
	readyList,

	// A central reference to the root jQuery(document)
	rootjQuery,

	// Support: IE<10
	// For `typeof xmlNode.method` instead of `xmlNode.method !== undefined`
	core_strundefined = typeof undefined,

	// Use the correct document accordingly with window argument (sandbox)
	location = window.location,
	document = window.document,
	docElem = document.documentElement,

	// Map over jQuery in case of overwrite
	_jQuery = window.jQuery,

	// Map over the $ in case of overwrite
	_$ = window.$,

	// [[Class]] -> type pairs
	class2type = {},

	// List of deleted data cache ids, so we can reuse them
	core_deletedIds = [],

	core_version = "1.10.2",

	// Save a reference to some core methods
	core_concat = core_deletedIds.concat,
	core_push = core_deletedIds.push,
	core_slice = core_deletedIds.slice,
	core_indexOf = core_deletedIds.indexOf,
	core_toString = class2type.toString,
	core_hasOwn = class2type.hasOwnProperty,
	core_trim = core_version.trim,

	// Define a local copy of jQuery
	jQuery = function( selector, context ) {
		// The jQuery object is actually just the init constructor 'enhanced'
		return new jQuery.fn.init( selector, context, rootjQuery );
	},

	// Used for matching numbers
	core_pnum = /[+-]?(?:\d*\.|)\d+(?:[eE][+-]?\d+|)/.source,

	// Used for splitting on whitespace
	core_rnotwhite = /\S+/g,

	// Make sure we trim BOM and NBSP (here's looking at you, Safari 5.0 and IE)
	rtrim = /^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g,

	// A simple way to check for HTML strings
	// Prioritize #id over <tag> to avoid XSS via location.hash (#9521)
	// Strict HTML recognition (#11290: must start with <)
	rquickExpr = /^(?:\s*(<[\w\W]+>)[^>]*|#([\w-]*))$/,

	// Match a standalone tag
	rsingleTag = /^<(\w+)\s*\/?>(?:<\/\1>|)$/,

	// JSON RegExp
	rvalidchars = /^[\],:{}\s]*$/,
	rvalidbraces = /(?:^|:|,)(?:\s*\[)+/g,
	rvalidescape = /\\(?:["\\\/bfnrt]|u[\da-fA-F]{4})/g,
	rvalidtokens = /"[^"\\\r\n]*"|true|false|null|-?(?:\d+\.|)\d+(?:[eE][+-]?\d+|)/g,

	// Matches dashed string for camelizing
	rmsPrefix = /^-ms-/,
	rdashAlpha = /-([\da-z])/gi,

	// Used by jQuery.camelCase as callback to replace()
	fcamelCase = function( all, letter ) {
		return letter.toUpperCase();
	},

	// The ready event handler
	completed = function( event ) {

		// readyState === "complete" is good enough for us to call the dom ready in oldIE
		if ( document.addEventListener || event.type === "load" || document.readyState === "complete" ) {
			detach();
			jQuery.ready();
		}
	},
	// Clean-up method for dom ready events
	detach = function() {
		if ( document.addEventListener ) {
			document.removeEventListener( "DOMContentLoaded", completed, false );
			window.removeEventListener( "load", completed, false );

		} else {
			document.detachEvent( "onreadystatechange", completed );
			window.detachEvent( "onload", completed );
		}
	};

jQuery.fn = jQuery.prototype = {
	// The current version of jQuery being used
	jquery: core_version,

	constructor: jQuery,
	init: function( selector, context, rootjQuery ) {
		var match, elem;

		// HANDLE: $(""), $(null), $(undefined), $(false)
		if ( !selector ) {
			return this;
		}

		// Handle HTML strings
		if ( typeof selector === "string" ) {
			if ( selector.charAt(0) === "<" && selector.charAt( selector.length - 1 ) === ">" && selector.length >= 3 ) {
				// Assume that strings that start and end with <> are HTML and skip the regex check
				match = [ null, selector, null ];

			} else {
				match = rquickExpr.exec( selector );
			}

			// Match html or make sure no context is specified for #id
			if ( match && (match[1] || !context) ) {

				// HANDLE: $(html) -> $(array)
				if ( match[1] ) {
					context = context instanceof jQuery ? context[0] : context;

					// scripts is true for back-compat
					jQuery.merge( this, jQuery.parseHTML(
						match[1],
						context && context.nodeType ? context.ownerDocument || context : document,
						true
					) );

					// HANDLE: $(html, props)
					if ( rsingleTag.test( match[1] ) && jQuery.isPlainObject( context ) ) {
						for ( match in context ) {
							// Properties of context are called as methods if possible
							if ( jQuery.isFunction( this[ match ] ) ) {
								this[ match ]( context[ match ] );

							// ...and otherwise set as attributes
							} else {
								this.attr( match, context[ match ] );
							}
						}
					}

					return this;

				// HANDLE: $(#id)
				} else {
					elem = document.getElementById( match[2] );

					// Check parentNode to catch when Blackberry 4.6 returns
					// nodes that are no longer in the document #6963
					if ( elem && elem.parentNode ) {
						// Handle the case where IE and Opera return items
						// by name instead of ID
						if ( elem.id !== match[2] ) {
							return rootjQuery.find( selector );
						}

						// Otherwise, we inject the element directly into the jQuery object
						this.length = 1;
						this[0] = elem;
					}

					this.context = document;
					this.selector = selector;
					return this;
				}

			// HANDLE: $(expr, $(...))
			} else if ( !context || context.jquery ) {
				return ( context || rootjQuery ).find( selector );

			// HANDLE: $(expr, context)
			// (which is just equivalent to: $(context).find(expr)
			} else {
				return this.constructor( context ).find( selector );
			}

		// HANDLE: $(DOMElement)
		} else if ( selector.nodeType ) {
			this.context = this[0] = selector;
			this.length = 1;
			return this;

		// HANDLE: $(function)
		// Shortcut for document ready
		} else if ( jQuery.isFunction( selector ) ) {
			return rootjQuery.ready( selector );
		}

		if ( selector.selector !== undefined ) {
			this.selector = selector.selector;
			this.context = selector.context;
		}

		return jQuery.makeArray( selector, this );
	},

	// Start with an empty selector
	selector: "",

	// The default length of a jQuery object is 0
	length: 0,

	toArray: function() {
		return core_slice.call( this );
	},

	// Get the Nth element in the matched element set OR
	// Get the whole matched element set as a clean array
	get: function( num ) {
		return num == null ?

			// Return a 'clean' array
			this.toArray() :

			// Return just the object
			( num < 0 ? this[ this.length + num ] : this[ num ] );
	},

	// Take an array of elements and push it onto the stack
	// (returning the new matched element set)
	pushStack: function( elems ) {

		// Build a new jQuery matched element set
		var ret = jQuery.merge( this.constructor(), elems );

		// Add the old object onto the stack (as a reference)
		ret.prevObject = this;
		ret.context = this.context;

		// Return the newly-formed element set
		return ret;
	},

	// Execute a callback for every element in the matched set.
	// (You can seed the arguments with an array of args, but this is
	// only used internally.)
	each: function( callback, args ) {
		return jQuery.each( this, callback, args );
	},

	ready: function( fn ) {
		// Add the callback
		jQuery.ready.promise().done( fn );

		return this;
	},

	slice: function() {
		return this.pushStack( core_slice.apply( this, arguments ) );
	},

	first: function() {
		return this.eq( 0 );
	},

	last: function() {
		return this.eq( -1 );
	},

	eq: function( i ) {
		var len = this.length,
			j = +i + ( i < 0 ? len : 0 );
		return this.pushStack( j >= 0 && j < len ? [ this[j] ] : [] );
	},

	map: function( callback ) {
		return this.pushStack( jQuery.map(this, function( elem, i ) {
			return callback.call( elem, i, elem );
		}));
	},

	end: function() {
		return this.prevObject || this.constructor(null);
	},

	// For internal use only.
	// Behaves like an Array's method, not like a jQuery method.
	push: core_push,
	sort: [].sort,
	splice: [].splice
};

// Give the init function the jQuery prototype for later instantiation
jQuery.fn.init.prototype = jQuery.fn;

jQuery.extend = jQuery.fn.extend = function() {
	var src, copyIsArray, copy, name, options, clone,
		target = arguments[0] || {},
		i = 1,
		length = arguments.length,
		deep = false;

	// Handle a deep copy situation
	if ( typeof target === "boolean" ) {
		deep = target;
		target = arguments[1] || {};
		// skip the boolean and the target
		i = 2;
	}

	// Handle case when target is a string or something (possible in deep copy)
	if ( typeof target !== "object" && !jQuery.isFunction(target) ) {
		target = {};
	}

	// extend jQuery itself if only one argument is passed
	if ( length === i ) {
		target = this;
		--i;
	}

	for ( ; i < length; i++ ) {
		// Only deal with non-null/undefined values
		if ( (options = arguments[ i ]) != null ) {
			// Extend the base object
			for ( name in options ) {
				src = target[ name ];
				copy = options[ name ];

				// Prevent never-ending loop
				if ( target === copy ) {
					continue;
				}

				// Recurse if we're merging plain objects or arrays
				if ( deep && copy && ( jQuery.isPlainObject(copy) || (copyIsArray = jQuery.isArray(copy)) ) ) {
					if ( copyIsArray ) {
						copyIsArray = false;
						clone = src && jQuery.isArray(src) ? src : [];

					} else {
						clone = src && jQuery.isPlainObject(src) ? src : {};
					}

					// Never move original objects, clone them
					target[ name ] = jQuery.extend( deep, clone, copy );

				// Don't bring in undefined values
				} else if ( copy !== undefined ) {
					target[ name ] = copy;
				}
			}
		}
	}

	// Return the modified object
	return target;
};

jQuery.extend({
	// Unique for each copy of jQuery on the page
	// Non-digits removed to match rinlinejQuery
	expando: "jQuery" + ( core_version + Math.random() ).replace( /\D/g, "" ),

	noConflict: function( deep ) {
		if ( window.$ === jQuery ) {
			window.$ = _$;
		}

		if ( deep && window.jQuery === jQuery ) {
			window.jQuery = _jQuery;
		}

		return jQuery;
	},

	// Is the DOM ready to be used? Set to true once it occurs.
	isReady: false,

	// A counter to track how many items to wait for before
	// the ready event fires. See #6781
	readyWait: 1,

	// Hold (or release) the ready event
	holdReady: function( hold ) {
		if ( hold ) {
			jQuery.readyWait++;
		} else {
			jQuery.ready( true );
		}
	},

	// Handle when the DOM is ready
	ready: function( wait ) {

		// Abort if there are pending holds or we're already ready
		if ( wait === true ? --jQuery.readyWait : jQuery.isReady ) {
			return;
		}

		// Make sure body exists, at least, in case IE gets a little overzealous (ticket #5443).
		if ( !document.body ) {
			return setTimeout( jQuery.ready );
		}

		// Remember that the DOM is ready
		jQuery.isReady = true;

		// If a normal DOM Ready event fired, decrement, and wait if need be
		if ( wait !== true && --jQuery.readyWait > 0 ) {
			return;
		}

		// If there are functions bound, to execute
		readyList.resolveWith( document, [ jQuery ] );

		// Trigger any bound ready events
		if ( jQuery.fn.trigger ) {
			jQuery( document ).trigger("ready").off("ready");
		}
	},

	// See test/unit/core.js for details concerning isFunction.
	// Since version 1.3, DOM methods and functions like alert
	// aren't supported. They return false on IE (#2968).
	isFunction: function( obj ) {
		return jQuery.type(obj) === "function";
	},

	isArray: Array.isArray || function( obj ) {
		return jQuery.type(obj) === "array";
	},

	isWindow: function( obj ) {
		/* jshint eqeqeq: false */
		return obj != null && obj == obj.window;
	},

	isNumeric: function( obj ) {
		return !isNaN( parseFloat(obj) ) && isFinite( obj );
	},

	type: function( obj ) {
		if ( obj == null ) {
			return String( obj );
		}
		return typeof obj === "object" || typeof obj === "function" ?
			class2type[ core_toString.call(obj) ] || "object" :
			typeof obj;
	},

	isPlainObject: function( obj ) {
		var key;

		// Must be an Object.
		// Because of IE, we also have to check the presence of the constructor property.
		// Make sure that DOM nodes and window objects don't pass through, as well
		if ( !obj || jQuery.type(obj) !== "object" || obj.nodeType || jQuery.isWindow( obj ) ) {
			return false;
		}

		try {
			// Not own constructor property must be Object
			if ( obj.constructor &&
				!core_hasOwn.call(obj, "constructor") &&
				!core_hasOwn.call(obj.constructor.prototype, "isPrototypeOf") ) {
				return false;
			}
		} catch ( e ) {
			// IE8,9 Will throw exceptions on certain host objects #9897
			return false;
		}

		// Support: IE<9
		// Handle iteration over inherited properties before own properties.
		if ( jQuery.support.ownLast ) {
			for ( key in obj ) {
				return core_hasOwn.call( obj, key );
			}
		}

		// Own properties are enumerated firstly, so to speed up,
		// if last one is own, then all properties are own.
		for ( key in obj ) {}

		return key === undefined || core_hasOwn.call( obj, key );
	},

	isEmptyObject: function( obj ) {
		var name;
		for ( name in obj ) {
			return false;
		}
		return true;
	},

	error: function( msg ) {
		throw new Error( msg );
	},

	// data: string of html
	// context (optional): If specified, the fragment will be created in this context, defaults to document
	// keepScripts (optional): If true, will include scripts passed in the html string
	parseHTML: function( data, context, keepScripts ) {
		if ( !data || typeof data !== "string" ) {
			return null;
		}
		if ( typeof context === "boolean" ) {
			keepScripts = context;
			context = false;
		}
		context = context || document;

		var parsed = rsingleTag.exec( data ),
			scripts = !keepScripts && [];

		// Single tag
		if ( parsed ) {
			return [ context.createElement( parsed[1] ) ];
		}

		parsed = jQuery.buildFragment( [ data ], context, scripts );
		if ( scripts ) {
			jQuery( scripts ).remove();
		}
		return jQuery.merge( [], parsed.childNodes );
	},

	parseJSON: function( data ) {
		// Attempt to parse using the native JSON parser first
		if ( window.JSON && window.JSON.parse ) {
			return window.JSON.parse( data );
		}

		if ( data === null ) {
			return data;
		}

		if ( typeof data === "string" ) {

			// Make sure leading/trailing whitespace is removed (IE can't handle it)
			data = jQuery.trim( data );

			if ( data ) {
				// Make sure the incoming data is actual JSON
				// Logic borrowed from http://json.org/json2.js
				if ( rvalidchars.test( data.replace( rvalidescape, "@" )
					.replace( rvalidtokens, "]" )
					.replace( rvalidbraces, "")) ) {

					return ( new Function( "return " + data ) )();
				}
			}
		}

		jQuery.error( "Invalid JSON: " + data );
	},

	// Cross-browser xml parsing
	parseXML: function( data ) {
		var xml, tmp;
		if ( !data || typeof data !== "string" ) {
			return null;
		}
		try {
			if ( window.DOMParser ) { // Standard
				tmp = new DOMParser();
				xml = tmp.parseFromString( data , "text/xml" );
			} else { // IE
				xml = new ActiveXObject( "Microsoft.XMLDOM" );
				xml.async = "false";
				xml.loadXML( data );
			}
		} catch( e ) {
			xml = undefined;
		}
		if ( !xml || !xml.documentElement || xml.getElementsByTagName( "parsererror" ).length ) {
			jQuery.error( "Invalid XML: " + data );
		}
		return xml;
	},

	noop: function() {},

	// Evaluates a script in a global context
	// Workarounds based on findings by Jim Driscoll
	// http://weblogs.java.net/blog/driscoll/archive/2009/09/08/eval-javascript-global-context
	globalEval: function( data ) {
		if ( data && jQuery.trim( data ) ) {
			// We use execScript on Internet Explorer
			// We use an anonymous function so that context is window
			// rather than jQuery in Firefox
			( window.execScript || function( data ) {
				window[ "eval" ].call( window, data );
			} )( data );
		}
	},

	// Convert dashed to camelCase; used by the css and data modules
	// Microsoft forgot to hump their vendor prefix (#9572)
	camelCase: function( string ) {
		return string.replace( rmsPrefix, "ms-" ).replace( rdashAlpha, fcamelCase );
	},

	nodeName: function( elem, name ) {
		return elem.nodeName && elem.nodeName.toLowerCase() === name.toLowerCase();
	},

	// args is for internal usage only
	each: function( obj, callback, args ) {
		var value,
			i = 0,
			length = obj.length,
			isArray = isArraylike( obj );

		if ( args ) {
			if ( isArray ) {
				for ( ; i < length; i++ ) {
					value = callback.apply( obj[ i ], args );

					if ( value === false ) {
						break;
					}
				}
			} else {
				for ( i in obj ) {
					value = callback.apply( obj[ i ], args );

					if ( value === false ) {
						break;
					}
				}
			}

		// A special, fast, case for the most common use of each
		} else {
			if ( isArray ) {
				for ( ; i < length; i++ ) {
					value = callback.call( obj[ i ], i, obj[ i ] );

					if ( value === false ) {
						break;
					}
				}
			} else {
				for ( i in obj ) {
					value = callback.call( obj[ i ], i, obj[ i ] );

					if ( value === false ) {
						break;
					}
				}
			}
		}

		return obj;
	},

	// Use native String.trim function wherever possible
	trim: core_trim && !core_trim.call("\uFEFF\xA0") ?
		function( text ) {
			return text == null ?
				"" :
				core_trim.call( text );
		} :

		// Otherwise use our own trimming functionality
		function( text ) {
			return text == null ?
				"" :
				( text + "" ).replace( rtrim, "" );
		},

	// results is for internal usage only
	makeArray: function( arr, results ) {
		var ret = results || [];

		if ( arr != null ) {
			if ( isArraylike( Object(arr) ) ) {
				jQuery.merge( ret,
					typeof arr === "string" ?
					[ arr ] : arr
				);
			} else {
				core_push.call( ret, arr );
			}
		}

		return ret;
	},

	inArray: function( elem, arr, i ) {
		var len;

		if ( arr ) {
			if ( core_indexOf ) {
				return core_indexOf.call( arr, elem, i );
			}

			len = arr.length;
			i = i ? i < 0 ? Math.max( 0, len + i ) : i : 0;

			for ( ; i < len; i++ ) {
				// Skip accessing in sparse arrays
				if ( i in arr && arr[ i ] === elem ) {
					return i;
				}
			}
		}

		return -1;
	},

	merge: function( first, second ) {
		var l = second.length,
			i = first.length,
			j = 0;

		if ( typeof l === "number" ) {
			for ( ; j < l; j++ ) {
				first[ i++ ] = second[ j ];
			}
		} else {
			while ( second[j] !== undefined ) {
				first[ i++ ] = second[ j++ ];
			}
		}

		first.length = i;

		return first;
	},

	grep: function( elems, callback, inv ) {
		var retVal,
			ret = [],
			i = 0,
			length = elems.length;
		inv = !!inv;

		// Go through the array, only saving the items
		// that pass the validator function
		for ( ; i < length; i++ ) {
			retVal = !!callback( elems[ i ], i );
			if ( inv !== retVal ) {
				ret.push( elems[ i ] );
			}
		}

		return ret;
	},

	// arg is for internal usage only
	map: function( elems, callback, arg ) {
		var value,
			i = 0,
			length = elems.length,
			isArray = isArraylike( elems ),
			ret = [];

		// Go through the array, translating each of the items to their
		if ( isArray ) {
			for ( ; i < length; i++ ) {
				value = callback( elems[ i ], i, arg );

				if ( value != null ) {
					ret[ ret.length ] = value;
				}
			}

		// Go through every key on the object,
		} else {
			for ( i in elems ) {
				value = callback( elems[ i ], i, arg );

				if ( value != null ) {
					ret[ ret.length ] = value;
				}
			}
		}

		// Flatten any nested arrays
		return core_concat.apply( [], ret );
	},

	// A global GUID counter for objects
	guid: 1,

	// Bind a function to a context, optionally partially applying any
	// arguments.
	proxy: function( fn, context ) {
		var args, proxy, tmp;

		if ( typeof context === "string" ) {
			tmp = fn[ context ];
			context = fn;
			fn = tmp;
		}

		// Quick check to determine if target is callable, in the spec
		// this throws a TypeError, but we will just return undefined.
		if ( !jQuery.isFunction( fn ) ) {
			return undefined;
		}

		// Simulated bind
		args = core_slice.call( arguments, 2 );
		proxy = function() {
			return fn.apply( context || this, args.concat( core_slice.call( arguments ) ) );
		};

		// Set the guid of unique handler to the same of original handler, so it can be removed
		proxy.guid = fn.guid = fn.guid || jQuery.guid++;

		return proxy;
	},

	// Multifunctional method to get and set values of a collection
	// The value/s can optionally be executed if it's a function
	access: function( elems, fn, key, value, chainable, emptyGet, raw ) {
		var i = 0,
			length = elems.length,
			bulk = key == null;

		// Sets many values
		if ( jQuery.type( key ) === "object" ) {
			chainable = true;
			for ( i in key ) {
				jQuery.access( elems, fn, i, key[i], true, emptyGet, raw );
			}

		// Sets one value
		} else if ( value !== undefined ) {
			chainable = true;

			if ( !jQuery.isFunction( value ) ) {
				raw = true;
			}

			if ( bulk ) {
				// Bulk operations run against the entire set
				if ( raw ) {
					fn.call( elems, value );
					fn = null;

				// ...except when executing function values
				} else {
					bulk = fn;
					fn = function( elem, key, value ) {
						return bulk.call( jQuery( elem ), value );
					};
				}
			}

			if ( fn ) {
				for ( ; i < length; i++ ) {
					fn( elems[i], key, raw ? value : value.call( elems[i], i, fn( elems[i], key ) ) );
				}
			}
		}

		return chainable ?
			elems :

			// Gets
			bulk ?
				fn.call( elems ) :
				length ? fn( elems[0], key ) : emptyGet;
	},

	now: function() {
		return ( new Date() ).getTime();
	},

	// A method for quickly swapping in/out CSS properties to get correct calculations.
	// Note: this method belongs to the css module but it's needed here for the support module.
	// If support gets modularized, this method should be moved back to the css module.
	swap: function( elem, options, callback, args ) {
		var ret, name,
			old = {};

		// Remember the old values, and insert the new ones
		for ( name in options ) {
			old[ name ] = elem.style[ name ];
			elem.style[ name ] = options[ name ];
		}

		ret = callback.apply( elem, args || [] );

		// Revert the old values
		for ( name in options ) {
			elem.style[ name ] = old[ name ];
		}

		return ret;
	}
});

jQuery.ready.promise = function( obj ) {
	if ( !readyList ) {

		readyList = jQuery.Deferred();

		// Catch cases where $(document).ready() is called after the browser event has already occurred.
		// we once tried to use readyState "interactive" here, but it caused issues like the one
		// discovered by ChrisS here: http://bugs.jquery.com/ticket/12282#comment:15
		if ( document.readyState === "complete" ) {
			// Handle it asynchronously to allow scripts the opportunity to delay ready
			setTimeout( jQuery.ready );

		// Standards-based browsers support DOMContentLoaded
		} else if ( document.addEventListener ) {
			// Use the handy event callback
			document.addEventListener( "DOMContentLoaded", completed, false );

			// A fallback to window.onload, that will always work
			window.addEventListener( "load", completed, false );

		// If IE event model is used
		} else {
			// Ensure firing before onload, maybe late but safe also for iframes
			document.attachEvent( "onreadystatechange", completed );

			// A fallback to window.onload, that will always work
			window.attachEvent( "onload", completed );

			// If IE and not a frame
			// continually check to see if the document is ready
			var top = false;

			try {
				top = window.frameElement == null && document.documentElement;
			} catch(e) {}

			if ( top && top.doScroll ) {
				(function doScrollCheck() {
					if ( !jQuery.isReady ) {

						try {
							// Use the trick by Diego Perini
							// http://javascript.nwbox.com/IEContentLoaded/
							top.doScroll("left");
						} catch(e) {
							return setTimeout( doScrollCheck, 50 );
						}

						// detach all dom ready events
						detach();

						// and execute any waiting functions
						jQuery.ready();
					}
				})();
			}
		}
	}
	return readyList.promise( obj );
};

// Populate the class2type map
jQuery.each("Boolean Number String Function Array Date RegExp Object Error".split(" "), function(i, name) {
	class2type[ "[object " + name + "]" ] = name.toLowerCase();
});

function isArraylike( obj ) {
	var length = obj.length,
		type = jQuery.type( obj );

	if ( jQuery.isWindow( obj ) ) {
		return false;
	}

	if ( obj.nodeType === 1 && length ) {
		return true;
	}

	return type === "array" || type !== "function" &&
		( length === 0 ||
		typeof length === "number" && length > 0 && ( length - 1 ) in obj );
}

// All jQuery objects should point back to these
rootjQuery = jQuery(document);
/*!
 * Sizzle CSS Selector Engine v1.10.2
 * http://sizzlejs.com/
 *
 * Copyright 2013 jQuery Foundation, Inc. and other contributors
 * Released under the MIT license
 * http://jquery.org/license
 *
 * Date: 2013-07-03
 */
(function( window, undefined ) {

var i,
	support,
	cachedruns,
	Expr,
	getText,
	isXML,
	compile,
	outermostContext,
	sortInput,

	// Local document vars
	setDocument,
	document,
	docElem,
	documentIsHTML,
	rbuggyQSA,
	rbuggyMatches,
	matches,
	contains,

	// Instance-specific data
	expando = "sizzle" + -(new Date()),
	preferredDoc = window.document,
	dirruns = 0,
	done = 0,
	classCache = createCache(),
	tokenCache = createCache(),
	compilerCache = createCache(),
	hasDuplicate = false,
	sortOrder = function( a, b ) {
		if ( a === b ) {
			hasDuplicate = true;
			return 0;
		}
		return 0;
	},

	// General-purpose constants
	strundefined = typeof undefined,
	MAX_NEGATIVE = 1 << 31,

	// Instance methods
	hasOwn = ({}).hasOwnProperty,
	arr = [],
	pop = arr.pop,
	push_native = arr.push,
	push = arr.push,
	slice = arr.slice,
	// Use a stripped-down indexOf if we can't use a native one
	indexOf = arr.indexOf || function( elem ) {
		var i = 0,
			len = this.length;
		for ( ; i < len; i++ ) {
			if ( this[i] === elem ) {
				return i;
			}
		}
		return -1;
	},

	booleans = "checked|selected|async|autofocus|autoplay|controls|defer|disabled|hidden|ismap|loop|multiple|open|readonly|required|scoped",

	// Regular expressions

	// Whitespace characters http://www.w3.org/TR/css3-selectors/#whitespace
	whitespace = "[\\x20\\t\\r\\n\\f]",
	// http://www.w3.org/TR/css3-syntax/#characters
	characterEncoding = "(?:\\\\.|[\\w-]|[^\\x00-\\xa0])+",

	// Loosely modeled on CSS identifier characters
	// An unquoted value should be a CSS identifier http://www.w3.org/TR/css3-selectors/#attribute-selectors
	// Proper syntax: http://www.w3.org/TR/CSS21/syndata.html#value-def-identifier
	identifier = characterEncoding.replace( "w", "w#" ),

	// Acceptable operators http://www.w3.org/TR/selectors/#attribute-selectors
	attributes = "\\[" + whitespace + "*(" + characterEncoding + ")" + whitespace +
		"*(?:([*^$|!~]?=)" + whitespace + "*(?:(['\"])((?:\\\\.|[^\\\\])*?)\\3|(" + identifier + ")|)|)" + whitespace + "*\\]",

	// Prefer arguments quoted,
	//   then not containing pseudos/brackets,
	//   then attribute selectors/non-parenthetical expressions,
	//   then anything else
	// These preferences are here to reduce the number of selectors
	//   needing tokenize in the PSEUDO preFilter
	pseudos = ":(" + characterEncoding + ")(?:\\(((['\"])((?:\\\\.|[^\\\\])*?)\\3|((?:\\\\.|[^\\\\()[\\]]|" + attributes.replace( 3, 8 ) + ")*)|.*)\\)|)",

	// Leading and non-escaped trailing whitespace, capturing some non-whitespace characters preceding the latter
	rtrim = new RegExp( "^" + whitespace + "+|((?:^|[^\\\\])(?:\\\\.)*)" + whitespace + "+$", "g" ),

	rcomma = new RegExp( "^" + whitespace + "*," + whitespace + "*" ),
	rcombinators = new RegExp( "^" + whitespace + "*([>+~]|" + whitespace + ")" + whitespace + "*" ),

	rsibling = new RegExp( whitespace + "*[+~]" ),
	rattributeQuotes = new RegExp( "=" + whitespace + "*([^\\]'\"]*)" + whitespace + "*\\]", "g" ),

	rpseudo = new RegExp( pseudos ),
	ridentifier = new RegExp( "^" + identifier + "$" ),

	matchExpr = {
		"ID": new RegExp( "^#(" + characterEncoding + ")" ),
		"CLASS": new RegExp( "^\\.(" + characterEncoding + ")" ),
		"TAG": new RegExp( "^(" + characterEncoding.replace( "w", "w*" ) + ")" ),
		"ATTR": new RegExp( "^" + attributes ),
		"PSEUDO": new RegExp( "^" + pseudos ),
		"CHILD": new RegExp( "^:(only|first|last|nth|nth-last)-(child|of-type)(?:\\(" + whitespace +
			"*(even|odd|(([+-]|)(\\d*)n|)" + whitespace + "*(?:([+-]|)" + whitespace +
			"*(\\d+)|))" + whitespace + "*\\)|)", "i" ),
		"bool": new RegExp( "^(?:" + booleans + ")$", "i" ),
		// For use in libraries implementing .is()
		// We use this for POS matching in `select`
		"needsContext": new RegExp( "^" + whitespace + "*[>+~]|:(even|odd|eq|gt|lt|nth|first|last)(?:\\(" +
			whitespace + "*((?:-\\d)?\\d*)" + whitespace + "*\\)|)(?=[^-]|$)", "i" )
	},

	rnative = /^[^{]+\{\s*\[native \w/,

	// Easily-parseable/retrievable ID or TAG or CLASS selectors
	rquickExpr = /^(?:#([\w-]+)|(\w+)|\.([\w-]+))$/,

	rinputs = /^(?:input|select|textarea|button)$/i,
	rheader = /^h\d$/i,

	rescape = /'|\\/g,

	// CSS escapes http://www.w3.org/TR/CSS21/syndata.html#escaped-characters
	runescape = new RegExp( "\\\\([\\da-f]{1,6}" + whitespace + "?|(" + whitespace + ")|.)", "ig" ),
	funescape = function( _, escaped, escapedWhitespace ) {
		var high = "0x" + escaped - 0x10000;
		// NaN means non-codepoint
		// Support: Firefox
		// Workaround erroneous numeric interpretation of +"0x"
		return high !== high || escapedWhitespace ?
			escaped :
			// BMP codepoint
			high < 0 ?
				String.fromCharCode( high + 0x10000 ) :
				// Supplemental Plane codepoint (surrogate pair)
				String.fromCharCode( high >> 10 | 0xD800, high & 0x3FF | 0xDC00 );
	};

// Optimize for push.apply( _, NodeList )
try {
	push.apply(
		(arr = slice.call( preferredDoc.childNodes )),
		preferredDoc.childNodes
	);
	// Support: Android<4.0
	// Detect silently failing push.apply
	arr[ preferredDoc.childNodes.length ].nodeType;
} catch ( e ) {
	push = { apply: arr.length ?

		// Leverage slice if possible
		function( target, els ) {
			push_native.apply( target, slice.call(els) );
		} :

		// Support: IE<9
		// Otherwise append directly
		function( target, els ) {
			var j = target.length,
				i = 0;
			// Can't trust NodeList.length
			while ( (target[j++] = els[i++]) ) {}
			target.length = j - 1;
		}
	};
}

function Sizzle( selector, context, results, seed ) {
	var match, elem, m, nodeType,
		// QSA vars
		i, groups, old, nid, newContext, newSelector;

	if ( ( context ? context.ownerDocument || context : preferredDoc ) !== document ) {
		setDocument( context );
	}

	context = context || document;
	results = results || [];

	if ( !selector || typeof selector !== "string" ) {
		return results;
	}

	if ( (nodeType = context.nodeType) !== 1 && nodeType !== 9 ) {
		return [];
	}

	if ( documentIsHTML && !seed ) {

		// Shortcuts
		if ( (match = rquickExpr.exec( selector )) ) {
			// Speed-up: Sizzle("#ID")
			if ( (m = match[1]) ) {
				if ( nodeType === 9 ) {
					elem = context.getElementById( m );
					// Check parentNode to catch when Blackberry 4.6 returns
					// nodes that are no longer in the document #6963
					if ( elem && elem.parentNode ) {
						// Handle the case where IE, Opera, and Webkit return items
						// by name instead of ID
						if ( elem.id === m ) {
							results.push( elem );
							return results;
						}
					} else {
						return results;
					}
				} else {
					// Context is not a document
					if ( context.ownerDocument && (elem = context.ownerDocument.getElementById( m )) &&
						contains( context, elem ) && elem.id === m ) {
						results.push( elem );
						return results;
					}
				}

			// Speed-up: Sizzle("TAG")
			} else if ( match[2] ) {
				push.apply( results, context.getElementsByTagName( selector ) );
				return results;

			// Speed-up: Sizzle(".CLASS")
			} else if ( (m = match[3]) && support.getElementsByClassName && context.getElementsByClassName ) {
				push.apply( results, context.getElementsByClassName( m ) );
				return results;
			}
		}

		// QSA path
		if ( support.qsa && (!rbuggyQSA || !rbuggyQSA.test( selector )) ) {
			nid = old = expando;
			newContext = context;
			newSelector = nodeType === 9 && selector;

			// qSA works strangely on Element-rooted queries
			// We can work around this by specifying an extra ID on the root
			// and working up from there (Thanks to Andrew Dupont for the technique)
			// IE 8 doesn't work on object elements
			if ( nodeType === 1 && context.nodeName.toLowerCase() !== "object" ) {
				groups = tokenize( selector );

				if ( (old = context.getAttribute("id")) ) {
					nid = old.replace( rescape, "\\$&" );
				} else {
					context.setAttribute( "id", nid );
				}
				nid = "[id='" + nid + "'] ";

				i = groups.length;
				while ( i-- ) {
					groups[i] = nid + toSelector( groups[i] );
				}
				newContext = rsibling.test( selector ) && context.parentNode || context;
				newSelector = groups.join(",");
			}

			if ( newSelector ) {
				try {
					push.apply( results,
						newContext.querySelectorAll( newSelector )
					);
					return results;
				} catch(qsaError) {
				} finally {
					if ( !old ) {
						context.removeAttribute("id");
					}
				}
			}
		}
	}

	// All others
	return select( selector.replace( rtrim, "$1" ), context, results, seed );
}

/**
 * Create key-value caches of limited size
 * @returns {Function(string, Object)} Returns the Object data after storing it on itself with
 *	property name the (space-suffixed) string and (if the cache is larger than Expr.cacheLength)
 *	deleting the oldest entry
 */
function createCache() {
	var keys = [];

	function cache( key, value ) {
		// Use (key + " ") to avoid collision with native prototype properties (see Issue #157)
		if ( keys.push( key += " " ) > Expr.cacheLength ) {
			// Only keep the most recent entries
			delete cache[ keys.shift() ];
		}
		return (cache[ key ] = value);
	}
	return cache;
}

/**
 * Mark a function for special use by Sizzle
 * @param {Function} fn The function to mark
 */
function markFunction( fn ) {
	fn[ expando ] = true;
	return fn;
}

/**
 * Support testing using an element
 * @param {Function} fn Passed the created div and expects a boolean result
 */
function assert( fn ) {
	var div = document.createElement("div");

	try {
		return !!fn( div );
	} catch (e) {
		return false;
	} finally {
		// Remove from its parent by default
		if ( div.parentNode ) {
			div.parentNode.removeChild( div );
		}
		// release memory in IE
		div = null;
	}
}

/**
 * Adds the same handler for all of the specified attrs
 * @param {String} attrs Pipe-separated list of attributes
 * @param {Function} handler The method that will be applied
 */
function addHandle( attrs, handler ) {
	var arr = attrs.split("|"),
		i = attrs.length;

	while ( i-- ) {
		Expr.attrHandle[ arr[i] ] = handler;
	}
}

/**
 * Checks document order of two siblings
 * @param {Element} a
 * @param {Element} b
 * @returns {Number} Returns less than 0 if a precedes b, greater than 0 if a follows b
 */
function siblingCheck( a, b ) {
	var cur = b && a,
		diff = cur && a.nodeType === 1 && b.nodeType === 1 &&
			( ~b.sourceIndex || MAX_NEGATIVE ) -
			( ~a.sourceIndex || MAX_NEGATIVE );

	// Use IE sourceIndex if available on both nodes
	if ( diff ) {
		return diff;
	}

	// Check if b follows a
	if ( cur ) {
		while ( (cur = cur.nextSibling) ) {
			if ( cur === b ) {
				return -1;
			}
		}
	}

	return a ? 1 : -1;
}

/**
 * Returns a function to use in pseudos for input types
 * @param {String} type
 */
function createInputPseudo( type ) {
	return function( elem ) {
		var name = elem.nodeName.toLowerCase();
		return name === "input" && elem.type === type;
	};
}

/**
 * Returns a function to use in pseudos for buttons
 * @param {String} type
 */
function createButtonPseudo( type ) {
	return function( elem ) {
		var name = elem.nodeName.toLowerCase();
		return (name === "input" || name === "button") && elem.type === type;
	};
}

/**
 * Returns a function to use in pseudos for positionals
 * @param {Function} fn
 */
function createPositionalPseudo( fn ) {
	return markFunction(function( argument ) {
		argument = +argument;
		return markFunction(function( seed, matches ) {
			var j,
				matchIndexes = fn( [], seed.length, argument ),
				i = matchIndexes.length;

			// Match elements found at the specified indexes
			while ( i-- ) {
				if ( seed[ (j = matchIndexes[i]) ] ) {
					seed[j] = !(matches[j] = seed[j]);
				}
			}
		});
	});
}

/**
 * Detect xml
 * @param {Element|Object} elem An element or a document
 */
isXML = Sizzle.isXML = function( elem ) {
	// documentElement is verified for cases where it doesn't yet exist
	// (such as loading iframes in IE - #4833)
	var documentElement = elem && (elem.ownerDocument || elem).documentElement;
	return documentElement ? documentElement.nodeName !== "HTML" : false;
};

// Expose support vars for convenience
support = Sizzle.support = {};

/**
 * Sets document-related variables once based on the current document
 * @param {Element|Object} [doc] An element or document object to use to set the document
 * @returns {Object} Returns the current document
 */
setDocument = Sizzle.setDocument = function( node ) {
	var doc = node ? node.ownerDocument || node : preferredDoc,
		parent = doc.defaultView;

	// If no document and documentElement is available, return
	if ( doc === document || doc.nodeType !== 9 || !doc.documentElement ) {
		return document;
	}

	// Set our document
	document = doc;
	docElem = doc.documentElement;

	// Support tests
	documentIsHTML = !isXML( doc );

	// Support: IE>8
	// If iframe document is assigned to "document" variable and if iframe has been reloaded,
	// IE will throw "permission denied" error when accessing "document" variable, see jQuery #13936
	// IE6-8 do not support the defaultView property so parent will be undefined
	if ( parent && parent.attachEvent && parent !== parent.top ) {
		parent.attachEvent( "onbeforeunload", function() {
			setDocument();
		});
	}

	/* Attributes
	---------------------------------------------------------------------- */

	// Support: IE<8
	// Verify that getAttribute really returns attributes and not properties (excepting IE8 booleans)
	support.attributes = assert(function( div ) {
		div.className = "i";
		return !div.getAttribute("className");
	});

	/* getElement(s)By*
	---------------------------------------------------------------------- */

	// Check if getElementsByTagName("*") returns only elements
	support.getElementsByTagName = assert(function( div ) {
		div.appendChild( doc.createComment("") );
		return !div.getElementsByTagName("*").length;
	});

	// Check if getElementsByClassName can be trusted
	support.getElementsByClassName = assert(function( div ) {
		div.innerHTML = "<div class='a'></div><div class='a i'></div>";

		// Support: Safari<4
		// Catch class over-caching
		div.firstChild.className = "i";
		// Support: Opera<10
		// Catch gEBCN failure to find non-leading classes
		return div.getElementsByClassName("i").length === 2;
	});

	// Support: IE<10
	// Check if getElementById returns elements by name
	// The broken getElementById methods don't pick up programatically-set names,
	// so use a roundabout getElementsByName test
	support.getById = assert(function( div ) {
		docElem.appendChild( div ).id = expando;
		return !doc.getElementsByName || !doc.getElementsByName( expando ).length;
	});

	// ID find and filter
	if ( support.getById ) {
		Expr.find["ID"] = function( id, context ) {
			if ( typeof context.getElementById !== strundefined && documentIsHTML ) {
				var m = context.getElementById( id );
				// Check parentNode to catch when Blackberry 4.6 returns
				// nodes that are no longer in the document #6963
				return m && m.parentNode ? [m] : [];
			}
		};
		Expr.filter["ID"] = function( id ) {
			var attrId = id.replace( runescape, funescape );
			return function( elem ) {
				return elem.getAttribute("id") === attrId;
			};
		};
	} else {
		// Support: IE6/7
		// getElementById is not reliable as a find shortcut
		delete Expr.find["ID"];

		Expr.filter["ID"] =  function( id ) {
			var attrId = id.replace( runescape, funescape );
			return function( elem ) {
				var node = typeof elem.getAttributeNode !== strundefined && elem.getAttributeNode("id");
				return node && node.value === attrId;
			};
		};
	}

	// Tag
	Expr.find["TAG"] = support.getElementsByTagName ?
		function( tag, context ) {
			if ( typeof context.getElementsByTagName !== strundefined ) {
				return context.getElementsByTagName( tag );
			}
		} :
		function( tag, context ) {
			var elem,
				tmp = [],
				i = 0,
				results = context.getElementsByTagName( tag );

			// Filter out possible comments
			if ( tag === "*" ) {
				while ( (elem = results[i++]) ) {
					if ( elem.nodeType === 1 ) {
						tmp.push( elem );
					}
				}

				return tmp;
			}
			return results;
		};

	// Class
	Expr.find["CLASS"] = support.getElementsByClassName && function( className, context ) {
		if ( typeof context.getElementsByClassName !== strundefined && documentIsHTML ) {
			return context.getElementsByClassName( className );
		}
	};

	/* QSA/matchesSelector
	---------------------------------------------------------------------- */

	// QSA and matchesSelector support

	// matchesSelector(:active) reports false when true (IE9/Opera 11.5)
	rbuggyMatches = [];

	// qSa(:focus) reports false when true (Chrome 21)
	// We allow this because of a bug in IE8/9 that throws an error
	// whenever `document.activeElement` is accessed on an iframe
	// So, we allow :focus to pass through QSA all the time to avoid the IE error
	// See http://bugs.jquery.com/ticket/13378
	rbuggyQSA = [];

	if ( (support.qsa = rnative.test( doc.querySelectorAll )) ) {
		// Build QSA regex
		// Regex strategy adopted from Diego Perini
		assert(function( div ) {
			// Select is set to empty string on purpose
			// This is to test IE's treatment of not explicitly
			// setting a boolean content attribute,
			// since its presence should be enough
			// http://bugs.jquery.com/ticket/12359
			div.innerHTML = "<select><option selected=''></option></select>";

			// Support: IE8
			// Boolean attributes and "value" are not treated correctly
			if ( !div.querySelectorAll("[selected]").length ) {
				rbuggyQSA.push( "\\[" + whitespace + "*(?:value|" + booleans + ")" );
			}

			// Webkit/Opera - :checked should return selected option elements
			// http://www.w3.org/TR/2011/REC-css3-selectors-20110929/#checked
			// IE8 throws error here and will not see later tests
			if ( !div.querySelectorAll(":checked").length ) {
				rbuggyQSA.push(":checked");
			}
		});

		assert(function( div ) {

			// Support: Opera 10-12/IE8
			// ^= $= *= and empty values
			// Should not select anything
			// Support: Windows 8 Native Apps
			// The type attribute is restricted during .innerHTML assignment
			var input = doc.createElement("input");
			input.setAttribute( "type", "hidden" );
			div.appendChild( input ).setAttribute( "t", "" );

			if ( div.querySelectorAll("[t^='']").length ) {
				rbuggyQSA.push( "[*^$]=" + whitespace + "*(?:''|\"\")" );
			}

			// FF 3.5 - :enabled/:disabled and hidden elements (hidden elements are still enabled)
			// IE8 throws error here and will not see later tests
			if ( !div.querySelectorAll(":enabled").length ) {
				rbuggyQSA.push( ":enabled", ":disabled" );
			}

			// Opera 10-11 does not throw on post-comma invalid pseudos
			div.querySelectorAll("*,:x");
			rbuggyQSA.push(",.*:");
		});
	}

	if ( (support.matchesSelector = rnative.test( (matches = docElem.webkitMatchesSelector ||
		docElem.mozMatchesSelector ||
		docElem.oMatchesSelector ||
		docElem.msMatchesSelector) )) ) {

		assert(function( div ) {
			// Check to see if it's possible to do matchesSelector
			// on a disconnected node (IE 9)
			support.disconnectedMatch = matches.call( div, "div" );

			// This should fail with an exception
			// Gecko does not error, returns false instead
			matches.call( div, "[s!='']:x" );
			rbuggyMatches.push( "!=", pseudos );
		});
	}

	rbuggyQSA = rbuggyQSA.length && new RegExp( rbuggyQSA.join("|") );
	rbuggyMatches = rbuggyMatches.length && new RegExp( rbuggyMatches.join("|") );

	/* Contains
	---------------------------------------------------------------------- */

	// Element contains another
	// Purposefully does not implement inclusive descendent
	// As in, an element does not contain itself
	contains = rnative.test( docElem.contains ) || docElem.compareDocumentPosition ?
		function( a, b ) {
			var adown = a.nodeType === 9 ? a.documentElement : a,
				bup = b && b.parentNode;
			return a === bup || !!( bup && bup.nodeType === 1 && (
				adown.contains ?
					adown.contains( bup ) :
					a.compareDocumentPosition && a.compareDocumentPosition( bup ) & 16
			));
		} :
		function( a, b ) {
			if ( b ) {
				while ( (b = b.parentNode) ) {
					if ( b === a ) {
						return true;
					}
				}
			}
			return false;
		};

	/* Sorting
	---------------------------------------------------------------------- */

	// Document order sorting
	sortOrder = docElem.compareDocumentPosition ?
	function( a, b ) {

		// Flag for duplicate removal
		if ( a === b ) {
			hasDuplicate = true;
			return 0;
		}

		var compare = b.compareDocumentPosition && a.compareDocumentPosition && a.compareDocumentPosition( b );

		if ( compare ) {
			// Disconnected nodes
			if ( compare & 1 ||
				(!support.sortDetached && b.compareDocumentPosition( a ) === compare) ) {

				// Choose the first element that is related to our preferred document
				if ( a === doc || contains(preferredDoc, a) ) {
					return -1;
				}
				if ( b === doc || contains(preferredDoc, b) ) {
					return 1;
				}

				// Maintain original order
				return sortInput ?
					( indexOf.call( sortInput, a ) - indexOf.call( sortInput, b ) ) :
					0;
			}

			return compare & 4 ? -1 : 1;
		}

		// Not directly comparable, sort on existence of method
		return a.compareDocumentPosition ? -1 : 1;
	} :
	function( a, b ) {
		var cur,
			i = 0,
			aup = a.parentNode,
			bup = b.parentNode,
			ap = [ a ],
			bp = [ b ];

		// Exit early if the nodes are identical
		if ( a === b ) {
			hasDuplicate = true;
			return 0;

		// Parentless nodes are either documents or disconnected
		} else if ( !aup || !bup ) {
			return a === doc ? -1 :
				b === doc ? 1 :
				aup ? -1 :
				bup ? 1 :
				sortInput ?
				( indexOf.call( sortInput, a ) - indexOf.call( sortInput, b ) ) :
				0;

		// If the nodes are siblings, we can do a quick check
		} else if ( aup === bup ) {
			return siblingCheck( a, b );
		}

		// Otherwise we need full lists of their ancestors for comparison
		cur = a;
		while ( (cur = cur.parentNode) ) {
			ap.unshift( cur );
		}
		cur = b;
		while ( (cur = cur.parentNode) ) {
			bp.unshift( cur );
		}

		// Walk down the tree looking for a discrepancy
		while ( ap[i] === bp[i] ) {
			i++;
		}

		return i ?
			// Do a sibling check if the nodes have a common ancestor
			siblingCheck( ap[i], bp[i] ) :

			// Otherwise nodes in our document sort first
			ap[i] === preferredDoc ? -1 :
			bp[i] === preferredDoc ? 1 :
			0;
	};

	return doc;
};

Sizzle.matches = function( expr, elements ) {
	return Sizzle( expr, null, null, elements );
};

Sizzle.matchesSelector = function( elem, expr ) {
	// Set document vars if needed
	if ( ( elem.ownerDocument || elem ) !== document ) {
		setDocument( elem );
	}

	// Make sure that attribute selectors are quoted
	expr = expr.replace( rattributeQuotes, "='$1']" );

	if ( support.matchesSelector && documentIsHTML &&
		( !rbuggyMatches || !rbuggyMatches.test( expr ) ) &&
		( !rbuggyQSA     || !rbuggyQSA.test( expr ) ) ) {

		try {
			var ret = matches.call( elem, expr );

			// IE 9's matchesSelector returns false on disconnected nodes
			if ( ret || support.disconnectedMatch ||
					// As well, disconnected nodes are said to be in a document
					// fragment in IE 9
					elem.document && elem.document.nodeType !== 11 ) {
				return ret;
			}
		} catch(e) {}
	}

	return Sizzle( expr, document, null, [elem] ).length > 0;
};

Sizzle.contains = function( context, elem ) {
	// Set document vars if needed
	if ( ( context.ownerDocument || context ) !== document ) {
		setDocument( context );
	}
	return contains( context, elem );
};

Sizzle.attr = function( elem, name ) {
	// Set document vars if needed
	if ( ( elem.ownerDocument || elem ) !== document ) {
		setDocument( elem );
	}

	var fn = Expr.attrHandle[ name.toLowerCase() ],
		// Don't get fooled by Object.prototype properties (jQuery #13807)
		val = fn && hasOwn.call( Expr.attrHandle, name.toLowerCase() ) ?
			fn( elem, name, !documentIsHTML ) :
			undefined;

	return val === undefined ?
		support.attributes || !documentIsHTML ?
			elem.getAttribute( name ) :
			(val = elem.getAttributeNode(name)) && val.specified ?
				val.value :
				null :
		val;
};

Sizzle.error = function( msg ) {
	throw new Error( "Syntax error, unrecognized expression: " + msg );
};

/**
 * Document sorting and removing duplicates
 * @param {ArrayLike} results
 */
Sizzle.uniqueSort = function( results ) {
	var elem,
		duplicates = [],
		j = 0,
		i = 0;

	// Unless we *know* we can detect duplicates, assume their presence
	hasDuplicate = !support.detectDuplicates;
	sortInput = !support.sortStable && results.slice( 0 );
	results.sort( sortOrder );

	if ( hasDuplicate ) {
		while ( (elem = results[i++]) ) {
			if ( elem === results[ i ] ) {
				j = duplicates.push( i );
			}
		}
		while ( j-- ) {
			results.splice( duplicates[ j ], 1 );
		}
	}

	return results;
};

/**
 * Utility function for retrieving the text value of an array of DOM nodes
 * @param {Array|Element} elem
 */
getText = Sizzle.getText = function( elem ) {
	var node,
		ret = "",
		i = 0,
		nodeType = elem.nodeType;

	if ( !nodeType ) {
		// If no nodeType, this is expected to be an array
		for ( ; (node = elem[i]); i++ ) {
			// Do not traverse comment nodes
			ret += getText( node );
		}
	} else if ( nodeType === 1 || nodeType === 9 || nodeType === 11 ) {
		// Use textContent for elements
		// innerText usage removed for consistency of new lines (see #11153)
		if ( typeof elem.textContent === "string" ) {
			return elem.textContent;
		} else {
			// Traverse its children
			for ( elem = elem.firstChild; elem; elem = elem.nextSibling ) {
				ret += getText( elem );
			}
		}
	} else if ( nodeType === 3 || nodeType === 4 ) {
		return elem.nodeValue;
	}
	// Do not include comment or processing instruction nodes

	return ret;
};

Expr = Sizzle.selectors = {

	// Can be adjusted by the user
	cacheLength: 50,

	createPseudo: markFunction,

	match: matchExpr,

	attrHandle: {},

	find: {},

	relative: {
		">": { dir: "parentNode", first: true },
		" ": { dir: "parentNode" },
		"+": { dir: "previousSibling", first: true },
		"~": { dir: "previousSibling" }
	},

	preFilter: {
		"ATTR": function( match ) {
			match[1] = match[1].replace( runescape, funescape );

			// Move the given value to match[3] whether quoted or unquoted
			match[3] = ( match[4] || match[5] || "" ).replace( runescape, funescape );

			if ( match[2] === "~=" ) {
				match[3] = " " + match[3] + " ";
			}

			return match.slice( 0, 4 );
		},

		"CHILD": function( match ) {
			/* matches from matchExpr["CHILD"]
				1 type (only|nth|...)
				2 what (child|of-type)
				3 argument (even|odd|\d*|\d*n([+-]\d+)?|...)
				4 xn-component of xn+y argument ([+-]?\d*n|)
				5 sign of xn-component
				6 x of xn-component
				7 sign of y-component
				8 y of y-component
			*/
			match[1] = match[1].toLowerCase();

			if ( match[1].slice( 0, 3 ) === "nth" ) {
				// nth-* requires argument
				if ( !match[3] ) {
					Sizzle.error( match[0] );
				}

				// numeric x and y parameters for Expr.filter.CHILD
				// remember that false/true cast respectively to 0/1
				match[4] = +( match[4] ? match[5] + (match[6] || 1) : 2 * ( match[3] === "even" || match[3] === "odd" ) );
				match[5] = +( ( match[7] + match[8] ) || match[3] === "odd" );

			// other types prohibit arguments
			} else if ( match[3] ) {
				Sizzle.error( match[0] );
			}

			return match;
		},

		"PSEUDO": function( match ) {
			var excess,
				unquoted = !match[5] && match[2];

			if ( matchExpr["CHILD"].test( match[0] ) ) {
				return null;
			}

			// Accept quoted arguments as-is
			if ( match[3] && match[4] !== undefined ) {
				match[2] = match[4];

			// Strip excess characters from unquoted arguments
			} else if ( unquoted && rpseudo.test( unquoted ) &&
				// Get excess from tokenize (recursively)
				(excess = tokenize( unquoted, true )) &&
				// advance to the next closing parenthesis
				(excess = unquoted.indexOf( ")", unquoted.length - excess ) - unquoted.length) ) {

				// excess is a negative index
				match[0] = match[0].slice( 0, excess );
				match[2] = unquoted.slice( 0, excess );
			}

			// Return only captures needed by the pseudo filter method (type and argument)
			return match.slice( 0, 3 );
		}
	},

	filter: {

		"TAG": function( nodeNameSelector ) {
			var nodeName = nodeNameSelector.replace( runescape, funescape ).toLowerCase();
			return nodeNameSelector === "*" ?
				function() { return true; } :
				function( elem ) {
					return elem.nodeName && elem.nodeName.toLowerCase() === nodeName;
				};
		},

		"CLASS": function( className ) {
			var pattern = classCache[ className + " " ];

			return pattern ||
				(pattern = new RegExp( "(^|" + whitespace + ")" + className + "(" + whitespace + "|$)" )) &&
				classCache( className, function( elem ) {
					return pattern.test( typeof elem.className === "string" && elem.className || typeof elem.getAttribute !== strundefined && elem.getAttribute("class") || "" );
				});
		},

		"ATTR": function( name, operator, check ) {
			return function( elem ) {
				var result = Sizzle.attr( elem, name );

				if ( result == null ) {
					return operator === "!=";
				}
				if ( !operator ) {
					return true;
				}

				result += "";

				return operator === "=" ? result === check :
					operator === "!=" ? result !== check :
					operator === "^=" ? check && result.indexOf( check ) === 0 :
					operator === "*=" ? check && result.indexOf( check ) > -1 :
					operator === "$=" ? check && result.slice( -check.length ) === check :
					operator === "~=" ? ( " " + result + " " ).indexOf( check ) > -1 :
					operator === "|=" ? result === check || result.slice( 0, check.length + 1 ) === check + "-" :
					false;
			};
		},

		"CHILD": function( type, what, argument, first, last ) {
			var simple = type.slice( 0, 3 ) !== "nth",
				forward = type.slice( -4 ) !== "last",
				ofType = what === "of-type";

			return first === 1 && last === 0 ?

				// Shortcut for :nth-*(n)
				function( elem ) {
					return !!elem.parentNode;
				} :

				function( elem, context, xml ) {
					var cache, outerCache, node, diff, nodeIndex, start,
						dir = simple !== forward ? "nextSibling" : "previousSibling",
						parent = elem.parentNode,
						name = ofType && elem.nodeName.toLowerCase(),
						useCache = !xml && !ofType;

					if ( parent ) {

						// :(first|last|only)-(child|of-type)
						if ( simple ) {
							while ( dir ) {
								node = elem;
								while ( (node = node[ dir ]) ) {
									if ( ofType ? node.nodeName.toLowerCase() === name : node.nodeType === 1 ) {
										return false;
									}
								}
								// Reverse direction for :only-* (if we haven't yet done so)
								start = dir = type === "only" && !start && "nextSibling";
							}
							return true;
						}

						start = [ forward ? parent.firstChild : parent.lastChild ];

						// non-xml :nth-child(...) stores cache data on `parent`
						if ( forward && useCache ) {
							// Seek `elem` from a previously-cached index
							outerCache = parent[ expando ] || (parent[ expando ] = {});
							cache = outerCache[ type ] || [];
							nodeIndex = cache[0] === dirruns && cache[1];
							diff = cache[0] === dirruns && cache[2];
							node = nodeIndex && parent.childNodes[ nodeIndex ];

							while ( (node = ++nodeIndex && node && node[ dir ] ||

								// Fallback to seeking `elem` from the start
								(diff = nodeIndex = 0) || start.pop()) ) {

								// When found, cache indexes on `parent` and break
								if ( node.nodeType === 1 && ++diff && node === elem ) {
									outerCache[ type ] = [ dirruns, nodeIndex, diff ];
									break;
								}
							}

						// Use previously-cached element index if available
						} else if ( useCache && (cache = (elem[ expando ] || (elem[ expando ] = {}))[ type ]) && cache[0] === dirruns ) {
							diff = cache[1];

						// xml :nth-child(...) or :nth-last-child(...) or :nth(-last)?-of-type(...)
						} else {
							// Use the same loop as above to seek `elem` from the start
							while ( (node = ++nodeIndex && node && node[ dir ] ||
								(diff = nodeIndex = 0) || start.pop()) ) {

								if ( ( ofType ? node.nodeName.toLowerCase() === name : node.nodeType === 1 ) && ++diff ) {
									// Cache the index of each encountered element
									if ( useCache ) {
										(node[ expando ] || (node[ expando ] = {}))[ type ] = [ dirruns, diff ];
									}

									if ( node === elem ) {
										break;
									}
								}
							}
						}

						// Incorporate the offset, then check against cycle size
						diff -= last;
						return diff === first || ( diff % first === 0 && diff / first >= 0 );
					}
				};
		},

		"PSEUDO": function( pseudo, argument ) {
			// pseudo-class names are case-insensitive
			// http://www.w3.org/TR/selectors/#pseudo-classes
			// Prioritize by case sensitivity in case custom pseudos are added with uppercase letters
			// Remember that setFilters inherits from pseudos
			var args,
				fn = Expr.pseudos[ pseudo ] || Expr.setFilters[ pseudo.toLowerCase() ] ||
					Sizzle.error( "unsupported pseudo: " + pseudo );

			// The user may use createPseudo to indicate that
			// arguments are needed to create the filter function
			// just as Sizzle does
			if ( fn[ expando ] ) {
				return fn( argument );
			}

			// But maintain support for old signatures
			if ( fn.length > 1 ) {
				args = [ pseudo, pseudo, "", argument ];
				return Expr.setFilters.hasOwnProperty( pseudo.toLowerCase() ) ?
					markFunction(function( seed, matches ) {
						var idx,
							matched = fn( seed, argument ),
							i = matched.length;
						while ( i-- ) {
							idx = indexOf.call( seed, matched[i] );
							seed[ idx ] = !( matches[ idx ] = matched[i] );
						}
					}) :
					function( elem ) {
						return fn( elem, 0, args );
					};
			}

			return fn;
		}
	},

	pseudos: {
		// Potentially complex pseudos
		"not": markFunction(function( selector ) {
			// Trim the selector passed to compile
			// to avoid treating leading and trailing
			// spaces as combinators
			var input = [],
				results = [],
				matcher = compile( selector.replace( rtrim, "$1" ) );

			return matcher[ expando ] ?
				markFunction(function( seed, matches, context, xml ) {
					var elem,
						unmatched = matcher( seed, null, xml, [] ),
						i = seed.length;

					// Match elements unmatched by `matcher`
					while ( i-- ) {
						if ( (elem = unmatched[i]) ) {
							seed[i] = !(matches[i] = elem);
						}
					}
				}) :
				function( elem, context, xml ) {
					input[0] = elem;
					matcher( input, null, xml, results );
					return !results.pop();
				};
		}),

		"has": markFunction(function( selector ) {
			return function( elem ) {
				return Sizzle( selector, elem ).length > 0;
			};
		}),

		"contains": markFunction(function( text ) {
			return function( elem ) {
				return ( elem.textContent || elem.innerText || getText( elem ) ).indexOf( text ) > -1;
			};
		}),

		// "Whether an element is represented by a :lang() selector
		// is based solely on the element's language value
		// being equal to the identifier C,
		// or beginning with the identifier C immediately followed by "-".
		// The matching of C against the element's language value is performed case-insensitively.
		// The identifier C does not have to be a valid language name."
		// http://www.w3.org/TR/selectors/#lang-pseudo
		"lang": markFunction( function( lang ) {
			// lang value must be a valid identifier
			if ( !ridentifier.test(lang || "") ) {
				Sizzle.error( "unsupported lang: " + lang );
			}
			lang = lang.replace( runescape, funescape ).toLowerCase();
			return function( elem ) {
				var elemLang;
				do {
					if ( (elemLang = documentIsHTML ?
						elem.lang :
						elem.getAttribute("xml:lang") || elem.getAttribute("lang")) ) {

						elemLang = elemLang.toLowerCase();
						return elemLang === lang || elemLang.indexOf( lang + "-" ) === 0;
					}
				} while ( (elem = elem.parentNode) && elem.nodeType === 1 );
				return false;
			};
		}),

		// Miscellaneous
		"target": function( elem ) {
			var hash = window.location && window.location.hash;
			return hash && hash.slice( 1 ) === elem.id;
		},

		"root": function( elem ) {
			return elem === docElem;
		},

		"focus": function( elem ) {
			return elem === document.activeElement && (!document.hasFocus || document.hasFocus()) && !!(elem.type || elem.href || ~elem.tabIndex);
		},

		// Boolean properties
		"enabled": function( elem ) {
			return elem.disabled === false;
		},

		"disabled": function( elem ) {
			return elem.disabled === true;
		},

		"checked": function( elem ) {
			// In CSS3, :checked should return both checked and selected elements
			// http://www.w3.org/TR/2011/REC-css3-selectors-20110929/#checked
			var nodeName = elem.nodeName.toLowerCase();
			return (nodeName === "input" && !!elem.checked) || (nodeName === "option" && !!elem.selected);
		},

		"selected": function( elem ) {
			// Accessing this property makes selected-by-default
			// options in Safari work properly
			if ( elem.parentNode ) {
				elem.parentNode.selectedIndex;
			}

			return elem.selected === true;
		},

		// Contents
		"empty": function( elem ) {
			// http://www.w3.org/TR/selectors/#empty-pseudo
			// :empty is only affected by element nodes and content nodes(including text(3), cdata(4)),
			//   not comment, processing instructions, or others
			// Thanks to Diego Perini for the nodeName shortcut
			//   Greater than "@" means alpha characters (specifically not starting with "#" or "?")
			for ( elem = elem.firstChild; elem; elem = elem.nextSibling ) {
				if ( elem.nodeName > "@" || elem.nodeType === 3 || elem.nodeType === 4 ) {
					return false;
				}
			}
			return true;
		},

		"parent": function( elem ) {
			return !Expr.pseudos["empty"]( elem );
		},

		// Element/input types
		"header": function( elem ) {
			return rheader.test( elem.nodeName );
		},

		"input": function( elem ) {
			return rinputs.test( elem.nodeName );
		},

		"button": function( elem ) {
			var name = elem.nodeName.toLowerCase();
			return name === "input" && elem.type === "button" || name === "button";
		},

		"text": function( elem ) {
			var attr;
			// IE6 and 7 will map elem.type to 'text' for new HTML5 types (search, etc)
			// use getAttribute instead to test this case
			return elem.nodeName.toLowerCase() === "input" &&
				elem.type === "text" &&
				( (attr = elem.getAttribute("type")) == null || attr.toLowerCase() === elem.type );
		},

		// Position-in-collection
		"first": createPositionalPseudo(function() {
			return [ 0 ];
		}),

		"last": createPositionalPseudo(function( matchIndexes, length ) {
			return [ length - 1 ];
		}),

		"eq": createPositionalPseudo(function( matchIndexes, length, argument ) {
			return [ argument < 0 ? argument + length : argument ];
		}),

		"even": createPositionalPseudo(function( matchIndexes, length ) {
			var i = 0;
			for ( ; i < length; i += 2 ) {
				matchIndexes.push( i );
			}
			return matchIndexes;
		}),

		"odd": createPositionalPseudo(function( matchIndexes, length ) {
			var i = 1;
			for ( ; i < length; i += 2 ) {
				matchIndexes.push( i );
			}
			return matchIndexes;
		}),

		"lt": createPositionalPseudo(function( matchIndexes, length, argument ) {
			var i = argument < 0 ? argument + length : argument;
			for ( ; --i >= 0; ) {
				matchIndexes.push( i );
			}
			return matchIndexes;
		}),

		"gt": createPositionalPseudo(function( matchIndexes, length, argument ) {
			var i = argument < 0 ? argument + length : argument;
			for ( ; ++i < length; ) {
				matchIndexes.push( i );
			}
			return matchIndexes;
		})
	}
};

Expr.pseudos["nth"] = Expr.pseudos["eq"];

// Add button/input type pseudos
for ( i in { radio: true, checkbox: true, file: true, password: true, image: true } ) {
	Expr.pseudos[ i ] = createInputPseudo( i );
}
for ( i in { submit: true, reset: true } ) {
	Expr.pseudos[ i ] = createButtonPseudo( i );
}

// Easy API for creating new setFilters
function setFilters() {}
setFilters.prototype = Expr.filters = Expr.pseudos;
Expr.setFilters = new setFilters();

function tokenize( selector, parseOnly ) {
	var matched, match, tokens, type,
		soFar, groups, preFilters,
		cached = tokenCache[ selector + " " ];

	if ( cached ) {
		return parseOnly ? 0 : cached.slice( 0 );
	}

	soFar = selector;
	groups = [];
	preFilters = Expr.preFilter;

	while ( soFar ) {

		// Comma and first run
		if ( !matched || (match = rcomma.exec( soFar )) ) {
			if ( match ) {
				// Don't consume trailing commas as valid
				soFar = soFar.slice( match[0].length ) || soFar;
			}
			groups.push( tokens = [] );
		}

		matched = false;

		// Combinators
		if ( (match = rcombinators.exec( soFar )) ) {
			matched = match.shift();
			tokens.push({
				value: matched,
				// Cast descendant combinators to space
				type: match[0].replace( rtrim, " " )
			});
			soFar = soFar.slice( matched.length );
		}

		// Filters
		for ( type in Expr.filter ) {
			if ( (match = matchExpr[ type ].exec( soFar )) && (!preFilters[ type ] ||
				(match = preFilters[ type ]( match ))) ) {
				matched = match.shift();
				tokens.push({
					value: matched,
					type: type,
					matches: match
				});
				soFar = soFar.slice( matched.length );
			}
		}

		if ( !matched ) {
			break;
		}
	}

	// Return the length of the invalid excess
	// if we're just parsing
	// Otherwise, throw an error or return tokens
	return parseOnly ?
		soFar.length :
		soFar ?
			Sizzle.error( selector ) :
			// Cache the tokens
			tokenCache( selector, groups ).slice( 0 );
}

function toSelector( tokens ) {
	var i = 0,
		len = tokens.length,
		selector = "";
	for ( ; i < len; i++ ) {
		selector += tokens[i].value;
	}
	return selector;
}

function addCombinator( matcher, combinator, base ) {
	var dir = combinator.dir,
		checkNonElements = base && dir === "parentNode",
		doneName = done++;

	return combinator.first ?
		// Check against closest ancestor/preceding element
		function( elem, context, xml ) {
			while ( (elem = elem[ dir ]) ) {
				if ( elem.nodeType === 1 || checkNonElements ) {
					return matcher( elem, context, xml );
				}
			}
		} :

		// Check against all ancestor/preceding elements
		function( elem, context, xml ) {
			var data, cache, outerCache,
				dirkey = dirruns + " " + doneName;

			// We can't set arbitrary data on XML nodes, so they don't benefit from dir caching
			if ( xml ) {
				while ( (elem = elem[ dir ]) ) {
					if ( elem.nodeType === 1 || checkNonElements ) {
						if ( matcher( elem, context, xml ) ) {
							return true;
						}
					}
				}
			} else {
				while ( (elem = elem[ dir ]) ) {
					if ( elem.nodeType === 1 || checkNonElements ) {
						outerCache = elem[ expando ] || (elem[ expando ] = {});
						if ( (cache = outerCache[ dir ]) && cache[0] === dirkey ) {
							if ( (data = cache[1]) === true || data === cachedruns ) {
								return data === true;
							}
						} else {
							cache = outerCache[ dir ] = [ dirkey ];
							cache[1] = matcher( elem, context, xml ) || cachedruns;
							if ( cache[1] === true ) {
								return true;
							}
						}
					}
				}
			}
		};
}

function elementMatcher( matchers ) {
	return matchers.length > 1 ?
		function( elem, context, xml ) {
			var i = matchers.length;
			while ( i-- ) {
				if ( !matchers[i]( elem, context, xml ) ) {
					return false;
				}
			}
			return true;
		} :
		matchers[0];
}

function condense( unmatched, map, filter, context, xml ) {
	var elem,
		newUnmatched = [],
		i = 0,
		len = unmatched.length,
		mapped = map != null;

	for ( ; i < len; i++ ) {
		if ( (elem = unmatched[i]) ) {
			if ( !filter || filter( elem, context, xml ) ) {
				newUnmatched.push( elem );
				if ( mapped ) {
					map.push( i );
				}
			}
		}
	}

	return newUnmatched;
}

function setMatcher( preFilter, selector, matcher, postFilter, postFinder, postSelector ) {
	if ( postFilter && !postFilter[ expando ] ) {
		postFilter = setMatcher( postFilter );
	}
	if ( postFinder && !postFinder[ expando ] ) {
		postFinder = setMatcher( postFinder, postSelector );
	}
	return markFunction(function( seed, results, context, xml ) {
		var temp, i, elem,
			preMap = [],
			postMap = [],
			preexisting = results.length,

			// Get initial elements from seed or context
			elems = seed || multipleContexts( selector || "*", context.nodeType ? [ context ] : context, [] ),

			// Prefilter to get matcher input, preserving a map for seed-results synchronization
			matcherIn = preFilter && ( seed || !selector ) ?
				condense( elems, preMap, preFilter, context, xml ) :
				elems,

			matcherOut = matcher ?
				// If we have a postFinder, or filtered seed, or non-seed postFilter or preexisting results,
				postFinder || ( seed ? preFilter : preexisting || postFilter ) ?

					// ...intermediate processing is necessary
					[] :

					// ...otherwise use results directly
					results :
				matcherIn;

		// Find primary matches
		if ( matcher ) {
			matcher( matcherIn, matcherOut, context, xml );
		}

		// Apply postFilter
		if ( postFilter ) {
			temp = condense( matcherOut, postMap );
			postFilter( temp, [], context, xml );

			// Un-match failing elements by moving them back to matcherIn
			i = temp.length;
			while ( i-- ) {
				if ( (elem = temp[i]) ) {
					matcherOut[ postMap[i] ] = !(matcherIn[ postMap[i] ] = elem);
				}
			}
		}

		if ( seed ) {
			if ( postFinder || preFilter ) {
				if ( postFinder ) {
					// Get the final matcherOut by condensing this intermediate into postFinder contexts
					temp = [];
					i = matcherOut.length;
					while ( i-- ) {
						if ( (elem = matcherOut[i]) ) {
							// Restore matcherIn since elem is not yet a final match
							temp.push( (matcherIn[i] = elem) );
						}
					}
					postFinder( null, (matcherOut = []), temp, xml );
				}

				// Move matched elements from seed to results to keep them synchronized
				i = matcherOut.length;
				while ( i-- ) {
					if ( (elem = matcherOut[i]) &&
						(temp = postFinder ? indexOf.call( seed, elem ) : preMap[i]) > -1 ) {

						seed[temp] = !(results[temp] = elem);
					}
				}
			}

		// Add elements to results, through postFinder if defined
		} else {
			matcherOut = condense(
				matcherOut === results ?
					matcherOut.splice( preexisting, matcherOut.length ) :
					matcherOut
			);
			if ( postFinder ) {
				postFinder( null, results, matcherOut, xml );
			} else {
				push.apply( results, matcherOut );
			}
		}
	});
}

function matcherFromTokens( tokens ) {
	var checkContext, matcher, j,
		len = tokens.length,
		leadingRelative = Expr.relative[ tokens[0].type ],
		implicitRelative = leadingRelative || Expr.relative[" "],
		i = leadingRelative ? 1 : 0,

		// The foundational matcher ensures that elements are reachable from top-level context(s)
		matchContext = addCombinator( function( elem ) {
			return elem === checkContext;
		}, implicitRelative, true ),
		matchAnyContext = addCombinator( function( elem ) {
			return indexOf.call( checkContext, elem ) > -1;
		}, implicitRelative, true ),
		matchers = [ function( elem, context, xml ) {
			return ( !leadingRelative && ( xml || context !== outermostContext ) ) || (
				(checkContext = context).nodeType ?
					matchContext( elem, context, xml ) :
					matchAnyContext( elem, context, xml ) );
		} ];

	for ( ; i < len; i++ ) {
		if ( (matcher = Expr.relative[ tokens[i].type ]) ) {
			matchers = [ addCombinator(elementMatcher( matchers ), matcher) ];
		} else {
			matcher = Expr.filter[ tokens[i].type ].apply( null, tokens[i].matches );

			// Return special upon seeing a positional matcher
			if ( matcher[ expando ] ) {
				// Find the next relative operator (if any) for proper handling
				j = ++i;
				for ( ; j < len; j++ ) {
					if ( Expr.relative[ tokens[j].type ] ) {
						break;
					}
				}
				return setMatcher(
					i > 1 && elementMatcher( matchers ),
					i > 1 && toSelector(
						// If the preceding token was a descendant combinator, insert an implicit any-element `*`
						tokens.slice( 0, i - 1 ).concat({ value: tokens[ i - 2 ].type === " " ? "*" : "" })
					).replace( rtrim, "$1" ),
					matcher,
					i < j && matcherFromTokens( tokens.slice( i, j ) ),
					j < len && matcherFromTokens( (tokens = tokens.slice( j )) ),
					j < len && toSelector( tokens )
				);
			}
			matchers.push( matcher );
		}
	}

	return elementMatcher( matchers );
}

function matcherFromGroupMatchers( elementMatchers, setMatchers ) {
	// A counter to specify which element is currently being matched
	var matcherCachedRuns = 0,
		bySet = setMatchers.length > 0,
		byElement = elementMatchers.length > 0,
		superMatcher = function( seed, context, xml, results, expandContext ) {
			var elem, j, matcher,
				setMatched = [],
				matchedCount = 0,
				i = "0",
				unmatched = seed && [],
				outermost = expandContext != null,
				contextBackup = outermostContext,
				// We must always have either seed elements or context
				elems = seed || byElement && Expr.find["TAG"]( "*", expandContext && context.parentNode || context ),
				// Use integer dirruns iff this is the outermost matcher
				dirrunsUnique = (dirruns += contextBackup == null ? 1 : Math.random() || 0.1);

			if ( outermost ) {
				outermostContext = context !== document && context;
				cachedruns = matcherCachedRuns;
			}

			// Add elements passing elementMatchers directly to results
			// Keep `i` a string if there are no elements so `matchedCount` will be "00" below
			for ( ; (elem = elems[i]) != null; i++ ) {
				if ( byElement && elem ) {
					j = 0;
					while ( (matcher = elementMatchers[j++]) ) {
						if ( matcher( elem, context, xml ) ) {
							results.push( elem );
							break;
						}
					}
					if ( outermost ) {
						dirruns = dirrunsUnique;
						cachedruns = ++matcherCachedRuns;
					}
				}

				// Track unmatched elements for set filters
				if ( bySet ) {
					// They will have gone through all possible matchers
					if ( (elem = !matcher && elem) ) {
						matchedCount--;
					}

					// Lengthen the array for every element, matched or not
					if ( seed ) {
						unmatched.push( elem );
					}
				}
			}

			// Apply set filters to unmatched elements
			matchedCount += i;
			if ( bySet && i !== matchedCount ) {
				j = 0;
				while ( (matcher = setMatchers[j++]) ) {
					matcher( unmatched, setMatched, context, xml );
				}

				if ( seed ) {
					// Reintegrate element matches to eliminate the need for sorting
					if ( matchedCount > 0 ) {
						while ( i-- ) {
							if ( !(unmatched[i] || setMatched[i]) ) {
								setMatched[i] = pop.call( results );
							}
						}
					}

					// Discard index placeholder values to get only actual matches
					setMatched = condense( setMatched );
				}

				// Add matches to results
				push.apply( results, setMatched );

				// Seedless set matches succeeding multiple successful matchers stipulate sorting
				if ( outermost && !seed && setMatched.length > 0 &&
					( matchedCount + setMatchers.length ) > 1 ) {

					Sizzle.uniqueSort( results );
				}
			}

			// Override manipulation of globals by nested matchers
			if ( outermost ) {
				dirruns = dirrunsUnique;
				outermostContext = contextBackup;
			}

			return unmatched;
		};

	return bySet ?
		markFunction( superMatcher ) :
		superMatcher;
}

compile = Sizzle.compile = function( selector, group /* Internal Use Only */ ) {
	var i,
		setMatchers = [],
		elementMatchers = [],
		cached = compilerCache[ selector + " " ];

	if ( !cached ) {
		// Generate a function of recursive functions that can be used to check each element
		if ( !group ) {
			group = tokenize( selector );
		}
		i = group.length;
		while ( i-- ) {
			cached = matcherFromTokens( group[i] );
			if ( cached[ expando ] ) {
				setMatchers.push( cached );
			} else {
				elementMatchers.push( cached );
			}
		}

		// Cache the compiled function
		cached = compilerCache( selector, matcherFromGroupMatchers( elementMatchers, setMatchers ) );
	}
	return cached;
};

function multipleContexts( selector, contexts, results ) {
	var i = 0,
		len = contexts.length;
	for ( ; i < len; i++ ) {
		Sizzle( selector, contexts[i], results );
	}
	return results;
}

function select( selector, context, results, seed ) {
	var i, tokens, token, type, find,
		match = tokenize( selector );

	if ( !seed ) {
		// Try to minimize operations if there is only one group
		if ( match.length === 1 ) {

			// Take a shortcut and set the context if the root selector is an ID
			tokens = match[0] = match[0].slice( 0 );
			if ( tokens.length > 2 && (token = tokens[0]).type === "ID" &&
					support.getById && context.nodeType === 9 && documentIsHTML &&
					Expr.relative[ tokens[1].type ] ) {

				context = ( Expr.find["ID"]( token.matches[0].replace(runescape, funescape), context ) || [] )[0];
				if ( !context ) {
					return results;
				}
				selector = selector.slice( tokens.shift().value.length );
			}

			// Fetch a seed set for right-to-left matching
			i = matchExpr["needsContext"].test( selector ) ? 0 : tokens.length;
			while ( i-- ) {
				token = tokens[i];

				// Abort if we hit a combinator
				if ( Expr.relative[ (type = token.type) ] ) {
					break;
				}
				if ( (find = Expr.find[ type ]) ) {
					// Search, expanding context for leading sibling combinators
					if ( (seed = find(
						token.matches[0].replace( runescape, funescape ),
						rsibling.test( tokens[0].type ) && context.parentNode || context
					)) ) {

						// If seed is empty or no tokens remain, we can return early
						tokens.splice( i, 1 );
						selector = seed.length && toSelector( tokens );
						if ( !selector ) {
							push.apply( results, seed );
							return results;
						}

						break;
					}
				}
			}
		}
	}

	// Compile and execute a filtering function
	// Provide `match` to avoid retokenization if we modified the selector above
	compile( selector, match )(
		seed,
		context,
		!documentIsHTML,
		results,
		rsibling.test( selector )
	);
	return results;
}

// One-time assignments

// Sort stability
support.sortStable = expando.split("").sort( sortOrder ).join("") === expando;

// Support: Chrome<14
// Always assume duplicates if they aren't passed to the comparison function
support.detectDuplicates = hasDuplicate;

// Initialize against the default document
setDocument();

// Support: Webkit<537.32 - Safari 6.0.3/Chrome 25 (fixed in Chrome 27)
// Detached nodes confoundingly follow *each other*
support.sortDetached = assert(function( div1 ) {
	// Should return 1, but returns 4 (following)
	return div1.compareDocumentPosition( document.createElement("div") ) & 1;
});

// Support: IE<8
// Prevent attribute/property "interpolation"
// http://msdn.microsoft.com/en-us/library/ms536429%28VS.85%29.aspx
if ( !assert(function( div ) {
	div.innerHTML = "<a href='#'></a>";
	return div.firstChild.getAttribute("href") === "#" ;
}) ) {
	addHandle( "type|href|height|width", function( elem, name, isXML ) {
		if ( !isXML ) {
			return elem.getAttribute( name, name.toLowerCase() === "type" ? 1 : 2 );
		}
	});
}

// Support: IE<9
// Use defaultValue in place of getAttribute("value")
if ( !support.attributes || !assert(function( div ) {
	div.innerHTML = "<input/>";
	div.firstChild.setAttribute( "value", "" );
	return div.firstChild.getAttribute( "value" ) === "";
}) ) {
	addHandle( "value", function( elem, name, isXML ) {
		if ( !isXML && elem.nodeName.toLowerCase() === "input" ) {
			return elem.defaultValue;
		}
	});
}

// Support: IE<9
// Use getAttributeNode to fetch booleans when getAttribute lies
if ( !assert(function( div ) {
	return div.getAttribute("disabled") == null;
}) ) {
	addHandle( booleans, function( elem, name, isXML ) {
		var val;
		if ( !isXML ) {
			return (val = elem.getAttributeNode( name )) && val.specified ?
				val.value :
				elem[ name ] === true ? name.toLowerCase() : null;
		}
	});
}

jQuery.find = Sizzle;
jQuery.expr = Sizzle.selectors;
jQuery.expr[":"] = jQuery.expr.pseudos;
jQuery.unique = Sizzle.uniqueSort;
jQuery.text = Sizzle.getText;
jQuery.isXMLDoc = Sizzle.isXML;
jQuery.contains = Sizzle.contains;


})( window );
// String to Object options format cache
var optionsCache = {};

// Convert String-formatted options into Object-formatted ones and store in cache
function createOptions( options ) {
	var object = optionsCache[ options ] = {};
	jQuery.each( options.match( core_rnotwhite ) || [], function( _, flag ) {
		object[ flag ] = true;
	});
	return object;
}

/*
 * Create a callback list using the following parameters:
 *
 *	options: an optional list of space-separated options that will change how
 *			the callback list behaves or a more traditional option object
 *
 * By default a callback list will act like an event callback list and can be
 * "fired" multiple times.
 *
 * Possible options:
 *
 *	once:			will ensure the callback list can only be fired once (like a Deferred)
 *
 *	memory:			will keep track of previous values and will call any callback added
 *					after the list has been fired right away with the latest "memorized"
 *					values (like a Deferred)
 *
 *	unique:			will ensure a callback can only be added once (no duplicate in the list)
 *
 *	stopOnFalse:	interrupt callings when a callback returns false
 *
 */
jQuery.Callbacks = function( options ) {

	// Convert options from String-formatted to Object-formatted if needed
	// (we check in cache first)
	options = typeof options === "string" ?
		( optionsCache[ options ] || createOptions( options ) ) :
		jQuery.extend( {}, options );

	var // Flag to know if list is currently firing
		firing,
		// Last fire value (for non-forgettable lists)
		memory,
		// Flag to know if list was already fired
		fired,
		// End of the loop when firing
		firingLength,
		// Index of currently firing callback (modified by remove if needed)
		firingIndex,
		// First callback to fire (used internally by add and fireWith)
		firingStart,
		// Actual callback list
		list = [],
		// Stack of fire calls for repeatable lists
		stack = !options.once && [],
		// Fire callbacks
		fire = function( data ) {
			memory = options.memory && data;
			fired = true;
			firingIndex = firingStart || 0;
			firingStart = 0;
			firingLength = list.length;
			firing = true;
			for ( ; list && firingIndex < firingLength; firingIndex++ ) {
				if ( list[ firingIndex ].apply( data[ 0 ], data[ 1 ] ) === false && options.stopOnFalse ) {
					memory = false; // To prevent further calls using add
					break;
				}
			}
			firing = false;
			if ( list ) {
				if ( stack ) {
					if ( stack.length ) {
						fire( stack.shift() );
					}
				} else if ( memory ) {
					list = [];
				} else {
					self.disable();
				}
			}
		},
		// Actual Callbacks object
		self = {
			// Add a callback or a collection of callbacks to the list
			add: function() {
				if ( list ) {
					// First, we save the current length
					var start = list.length;
					(function add( args ) {
						jQuery.each( args, function( _, arg ) {
							var type = jQuery.type( arg );
							if ( type === "function" ) {
								if ( !options.unique || !self.has( arg ) ) {
									list.push( arg );
								}
							} else if ( arg && arg.length && type !== "string" ) {
								// Inspect recursively
								add( arg );
							}
						});
					})( arguments );
					// Do we need to add the callbacks to the
					// current firing batch?
					if ( firing ) {
						firingLength = list.length;
					// With memory, if we're not firing then
					// we should call right away
					} else if ( memory ) {
						firingStart = start;
						fire( memory );
					}
				}
				return this;
			},
			// Remove a callback from the list
			remove: function() {
				if ( list ) {
					jQuery.each( arguments, function( _, arg ) {
						var index;
						while( ( index = jQuery.inArray( arg, list, index ) ) > -1 ) {
							list.splice( index, 1 );
							// Handle firing indexes
							if ( firing ) {
								if ( index <= firingLength ) {
									firingLength--;
								}
								if ( index <= firingIndex ) {
									firingIndex--;
								}
							}
						}
					});
				}
				return this;
			},
			// Check if a given callback is in the list.
			// If no argument is given, return whether or not list has callbacks attached.
			has: function( fn ) {
				return fn ? jQuery.inArray( fn, list ) > -1 : !!( list && list.length );
			},
			// Remove all callbacks from the list
			empty: function() {
				list = [];
				firingLength = 0;
				return this;
			},
			// Have the list do nothing anymore
			disable: function() {
				list = stack = memory = undefined;
				return this;
			},
			// Is it disabled?
			disabled: function() {
				return !list;
			},
			// Lock the list in its current state
			lock: function() {
				stack = undefined;
				if ( !memory ) {
					self.disable();
				}
				return this;
			},
			// Is it locked?
			locked: function() {
				return !stack;
			},
			// Call all callbacks with the given context and arguments
			fireWith: function( context, args ) {
				if ( list && ( !fired || stack ) ) {
					args = args || [];
					args = [ context, args.slice ? args.slice() : args ];
					if ( firing ) {
						stack.push( args );
					} else {
						fire( args );
					}
				}
				return this;
			},
			// Call all the callbacks with the given arguments
			fire: function() {
				self.fireWith( this, arguments );
				return this;
			},
			// To know if the callbacks have already been called at least once
			fired: function() {
				return !!fired;
			}
		};

	return self;
};
jQuery.extend({

	Deferred: function( func ) {
		var tuples = [
				// action, add listener, listener list, final state
				[ "resolve", "done", jQuery.Callbacks("once memory"), "resolved" ],
				[ "reject", "fail", jQuery.Callbacks("once memory"), "rejected" ],
				[ "notify", "progress", jQuery.Callbacks("memory") ]
			],
			state = "pending",
			promise = {
				state: function() {
					return state;
				},
				always: function() {
					deferred.done( arguments ).fail( arguments );
					return this;
				},
				then: function( /* fnDone, fnFail, fnProgress */ ) {
					var fns = arguments;
					return jQuery.Deferred(function( newDefer ) {
						jQuery.each( tuples, function( i, tuple ) {
							var action = tuple[ 0 ],
								fn = jQuery.isFunction( fns[ i ] ) && fns[ i ];
							// deferred[ done | fail | progress ] for forwarding actions to newDefer
							deferred[ tuple[1] ](function() {
								var returned = fn && fn.apply( this, arguments );
								if ( returned && jQuery.isFunction( returned.promise ) ) {
									returned.promise()
										.done( newDefer.resolve )
										.fail( newDefer.reject )
										.progress( newDefer.notify );
								} else {
									newDefer[ action + "With" ]( this === promise ? newDefer.promise() : this, fn ? [ returned ] : arguments );
								}
							});
						});
						fns = null;
					}).promise();
				},
				// Get a promise for this deferred
				// If obj is provided, the promise aspect is added to the object
				promise: function( obj ) {
					return obj != null ? jQuery.extend( obj, promise ) : promise;
				}
			},
			deferred = {};

		// Keep pipe for back-compat
		promise.pipe = promise.then;

		// Add list-specific methods
		jQuery.each( tuples, function( i, tuple ) {
			var list = tuple[ 2 ],
				stateString = tuple[ 3 ];

			// promise[ done | fail | progress ] = list.add
			promise[ tuple[1] ] = list.add;

			// Handle state
			if ( stateString ) {
				list.add(function() {
					// state = [ resolved | rejected ]
					state = stateString;

				// [ reject_list | resolve_list ].disable; progress_list.lock
				}, tuples[ i ^ 1 ][ 2 ].disable, tuples[ 2 ][ 2 ].lock );
			}

			// deferred[ resolve | reject | notify ]
			deferred[ tuple[0] ] = function() {
				deferred[ tuple[0] + "With" ]( this === deferred ? promise : this, arguments );
				return this;
			};
			deferred[ tuple[0] + "With" ] = list.fireWith;
		});

		// Make the deferred a promise
		promise.promise( deferred );

		// Call given func if any
		if ( func ) {
			func.call( deferred, deferred );
		}

		// All done!
		return deferred;
	},

	// Deferred helper
	when: function( subordinate /* , ..., subordinateN */ ) {
		var i = 0,
			resolveValues = core_slice.call( arguments ),
			length = resolveValues.length,

			// the count of uncompleted subordinates
			remaining = length !== 1 || ( subordinate && jQuery.isFunction( subordinate.promise ) ) ? length : 0,

			// the master Deferred. If resolveValues consist of only a single Deferred, just use that.
			deferred = remaining === 1 ? subordinate : jQuery.Deferred(),

			// Update function for both resolve and progress values
			updateFunc = function( i, contexts, values ) {
				return function( value ) {
					contexts[ i ] = this;
					values[ i ] = arguments.length > 1 ? core_slice.call( arguments ) : value;
					if( values === progressValues ) {
						deferred.notifyWith( contexts, values );
					} else if ( !( --remaining ) ) {
						deferred.resolveWith( contexts, values );
					}
				};
			},

			progressValues, progressContexts, resolveContexts;

		// add listeners to Deferred subordinates; treat others as resolved
		if ( length > 1 ) {
			progressValues = new Array( length );
			progressContexts = new Array( length );
			resolveContexts = new Array( length );
			for ( ; i < length; i++ ) {
				if ( resolveValues[ i ] && jQuery.isFunction( resolveValues[ i ].promise ) ) {
					resolveValues[ i ].promise()
						.done( updateFunc( i, resolveContexts, resolveValues ) )
						.fail( deferred.reject )
						.progress( updateFunc( i, progressContexts, progressValues ) );
				} else {
					--remaining;
				}
			}
		}

		// if we're not waiting on anything, resolve the master
		if ( !remaining ) {
			deferred.resolveWith( resolveContexts, resolveValues );
		}

		return deferred.promise();
	}
});
jQuery.support = (function( support ) {

	var all, a, input, select, fragment, opt, eventName, isSupported, i,
		div = document.createElement("div");

	// Setup
	div.setAttribute( "className", "t" );
	div.innerHTML = "  <link/><table></table><a href='/a'>a</a><input type='checkbox'/>";

	// Finish early in limited (non-browser) environments
	all = div.getElementsByTagName("*") || [];
	a = div.getElementsByTagName("a")[ 0 ];
	if ( !a || !a.style || !all.length ) {
		return support;
	}

	// First batch of tests
	select = document.createElement("select");
	opt = select.appendChild( document.createElement("option") );
	input = div.getElementsByTagName("input")[ 0 ];

	a.style.cssText = "top:1px;float:left;opacity:.5";

	// Test setAttribute on camelCase class. If it works, we need attrFixes when doing get/setAttribute (ie6/7)
	support.getSetAttribute = div.className !== "t";

	// IE strips leading whitespace when .innerHTML is used
	support.leadingWhitespace = div.firstChild.nodeType === 3;

	// Make sure that tbody elements aren't automatically inserted
	// IE will insert them into empty tables
	support.tbody = !div.getElementsByTagName("tbody").length;

	// Make sure that link elements get serialized correctly by innerHTML
	// This requires a wrapper element in IE
	support.htmlSerialize = !!div.getElementsByTagName("link").length;

	// Get the style information from getAttribute
	// (IE uses .cssText instead)
	support.style = /top/.test( a.getAttribute("style") );

	// Make sure that URLs aren't manipulated
	// (IE normalizes it by default)
	support.hrefNormalized = a.getAttribute("href") === "/a";

	// Make sure that element opacity exists
	// (IE uses filter instead)
	// Use a regex to work around a WebKit issue. See #5145
	support.opacity = /^0.5/.test( a.style.opacity );

	// Verify style float existence
	// (IE uses styleFloat instead of cssFloat)
	support.cssFloat = !!a.style.cssFloat;

	// Check the default checkbox/radio value ("" on WebKit; "on" elsewhere)
	support.checkOn = !!input.value;

	// Make sure that a selected-by-default option has a working selected property.
	// (WebKit defaults to false instead of true, IE too, if it's in an optgroup)
	support.optSelected = opt.selected;

	// Tests for enctype support on a form (#6743)
	support.enctype = !!document.createElement("form").enctype;

	// Makes sure cloning an html5 element does not cause problems
	// Where outerHTML is undefined, this still works
	support.html5Clone = document.createElement("nav").cloneNode( true ).outerHTML !== "<:nav></:nav>";

	// Will be defined later
	support.inlineBlockNeedsLayout = false;
	support.shrinkWrapBlocks = false;
	support.pixelPosition = false;
	support.deleteExpando = true;
	support.noCloneEvent = true;
	support.reliableMarginRight = true;
	support.boxSizingReliable = true;

	// Make sure checked status is properly cloned
	input.checked = true;
	support.noCloneChecked = input.cloneNode( true ).checked;

	// Make sure that the options inside disabled selects aren't marked as disabled
	// (WebKit marks them as disabled)
	select.disabled = true;
	support.optDisabled = !opt.disabled;

	// Support: IE<9
	try {
		delete div.test;
	} catch( e ) {
		support.deleteExpando = false;
	}

	// Check if we can trust getAttribute("value")
	input = document.createElement("input");
	input.setAttribute( "value", "" );
	support.input = input.getAttribute( "value" ) === "";

	// Check if an input maintains its value after becoming a radio
	input.value = "t";
	input.setAttribute( "type", "radio" );
	support.radioValue = input.value === "t";

	// #11217 - WebKit loses check when the name is after the checked attribute
	input.setAttribute( "checked", "t" );
	input.setAttribute( "name", "t" );

	fragment = document.createDocumentFragment();
	fragment.appendChild( input );

	// Check if a disconnected checkbox will retain its checked
	// value of true after appended to the DOM (IE6/7)
	support.appendChecked = input.checked;

	// WebKit doesn't clone checked state correctly in fragments
	support.checkClone = fragment.cloneNode( true ).cloneNode( true ).lastChild.checked;

	// Support: IE<9
	// Opera does not clone events (and typeof div.attachEvent === undefined).
	// IE9-10 clones events bound via attachEvent, but they don't trigger with .click()
	if ( div.attachEvent ) {
		div.attachEvent( "onclick", function() {
			support.noCloneEvent = false;
		});

		div.cloneNode( true ).click();
	}

	// Support: IE<9 (lack submit/change bubble), Firefox 17+ (lack focusin event)
	// Beware of CSP restrictions (https://developer.mozilla.org/en/Security/CSP)
	for ( i in { submit: true, change: true, focusin: true }) {
		div.setAttribute( eventName = "on" + i, "t" );

		support[ i + "Bubbles" ] = eventName in window || div.attributes[ eventName ].expando === false;
	}

	div.style.backgroundClip = "content-box";
	div.cloneNode( true ).style.backgroundClip = "";
	support.clearCloneStyle = div.style.backgroundClip === "content-box";

	// Support: IE<9
	// Iteration over object's inherited properties before its own.
	for ( i in jQuery( support ) ) {
		break;
	}
	support.ownLast = i !== "0";

	// Run tests that need a body at doc ready
	jQuery(function() {
		var container, marginDiv, tds,
			divReset = "padding:0;margin:0;border:0;display:block;box-sizing:content-box;-moz-box-sizing:content-box;-webkit-box-sizing:content-box;",
			body = document.getElementsByTagName("body")[0];

		if ( !body ) {
			// Return for frameset docs that don't have a body
			return;
		}

		container = document.createElement("div");
		container.style.cssText = "border:0;width:0;height:0;position:absolute;top:0;left:-9999px;margin-top:1px";

		body.appendChild( container ).appendChild( div );

		// Support: IE8
		// Check if table cells still have offsetWidth/Height when they are set
		// to display:none and there are still other visible table cells in a
		// table row; if so, offsetWidth/Height are not reliable for use when
		// determining if an element has been hidden directly using
		// display:none (it is still safe to use offsets if a parent element is
		// hidden; don safety goggles and see bug #4512 for more information).
		div.innerHTML = "<table><tr><td></td><td>t</td></tr></table>";
		tds = div.getElementsByTagName("td");
		tds[ 0 ].style.cssText = "padding:0;margin:0;border:0;display:none";
		isSupported = ( tds[ 0 ].offsetHeight === 0 );

		tds[ 0 ].style.display = "";
		tds[ 1 ].style.display = "none";

		// Support: IE8
		// Check if empty table cells still have offsetWidth/Height
		support.reliableHiddenOffsets = isSupported && ( tds[ 0 ].offsetHeight === 0 );

		// Check box-sizing and margin behavior.
		div.innerHTML = "";
		div.style.cssText = "box-sizing:border-box;-moz-box-sizing:border-box;-webkit-box-sizing:border-box;padding:1px;border:1px;display:block;width:4px;margin-top:1%;position:absolute;top:1%;";

		// Workaround failing boxSizing test due to offsetWidth returning wrong value
		// with some non-1 values of body zoom, ticket #13543
		jQuery.swap( body, body.style.zoom != null ? { zoom: 1 } : {}, function() {
			support.boxSizing = div.offsetWidth === 4;
		});

		// Use window.getComputedStyle because jsdom on node.js will break without it.
		if ( window.getComputedStyle ) {
			support.pixelPosition = ( window.getComputedStyle( div, null ) || {} ).top !== "1%";
			support.boxSizingReliable = ( window.getComputedStyle( div, null ) || { width: "4px" } ).width === "4px";

			// Check if div with explicit width and no margin-right incorrectly
			// gets computed margin-right based on width of container. (#3333)
			// Fails in WebKit before Feb 2011 nightlies
			// WebKit Bug 13343 - getComputedStyle returns wrong value for margin-right
			marginDiv = div.appendChild( document.createElement("div") );
			marginDiv.style.cssText = div.style.cssText = divReset;
			marginDiv.style.marginRight = marginDiv.style.width = "0";
			div.style.width = "1px";

			support.reliableMarginRight =
				!parseFloat( ( window.getComputedStyle( marginDiv, null ) || {} ).marginRight );
		}

		if ( typeof div.style.zoom !== core_strundefined ) {
			// Support: IE<8
			// Check if natively block-level elements act like inline-block
			// elements when setting their display to 'inline' and giving
			// them layout
			div.innerHTML = "";
			div.style.cssText = divReset + "width:1px;padding:1px;display:inline;zoom:1";
			support.inlineBlockNeedsLayout = ( div.offsetWidth === 3 );

			// Support: IE6
			// Check if elements with layout shrink-wrap their children
			div.style.display = "block";
			div.innerHTML = "<div></div>";
			div.firstChild.style.width = "5px";
			support.shrinkWrapBlocks = ( div.offsetWidth !== 3 );

			if ( support.inlineBlockNeedsLayout ) {
				// Prevent IE 6 from affecting layout for positioned elements #11048
				// Prevent IE from shrinking the body in IE 7 mode #12869
				// Support: IE<8
				body.style.zoom = 1;
			}
		}

		body.removeChild( container );

		// Null elements to avoid leaks in IE
		container = div = tds = marginDiv = null;
	});

	// Null elements to avoid leaks in IE
	all = select = fragment = opt = a = input = null;

	return support;
})({});

var rbrace = /(?:\{[\s\S]*\}|\[[\s\S]*\])$/,
	rmultiDash = /([A-Z])/g;

function internalData( elem, name, data, pvt /* Internal Use Only */ ){
	if ( !jQuery.acceptData( elem ) ) {
		return;
	}

	var ret, thisCache,
		internalKey = jQuery.expando,

		// We have to handle DOM nodes and JS objects differently because IE6-7
		// can't GC object references properly across the DOM-JS boundary
		isNode = elem.nodeType,

		// Only DOM nodes need the global jQuery cache; JS object data is
		// attached directly to the object so GC can occur automatically
		cache = isNode ? jQuery.cache : elem,

		// Only defining an ID for JS objects if its cache already exists allows
		// the code to shortcut on the same path as a DOM node with no cache
		id = isNode ? elem[ internalKey ] : elem[ internalKey ] && internalKey;

	// Avoid doing any more work than we need to when trying to get data on an
	// object that has no data at all
	if ( (!id || !cache[id] || (!pvt && !cache[id].data)) && data === undefined && typeof name === "string" ) {
		return;
	}

	if ( !id ) {
		// Only DOM nodes need a new unique ID for each element since their data
		// ends up in the global cache
		if ( isNode ) {
			id = elem[ internalKey ] = core_deletedIds.pop() || jQuery.guid++;
		} else {
			id = internalKey;
		}
	}

	if ( !cache[ id ] ) {
		// Avoid exposing jQuery metadata on plain JS objects when the object
		// is serialized using JSON.stringify
		cache[ id ] = isNode ? {} : { toJSON: jQuery.noop };
	}

	// An object can be passed to jQuery.data instead of a key/value pair; this gets
	// shallow copied over onto the existing cache
	if ( typeof name === "object" || typeof name === "function" ) {
		if ( pvt ) {
			cache[ id ] = jQuery.extend( cache[ id ], name );
		} else {
			cache[ id ].data = jQuery.extend( cache[ id ].data, name );
		}
	}

	thisCache = cache[ id ];

	// jQuery data() is stored in a separate object inside the object's internal data
	// cache in order to avoid key collisions between internal data and user-defined
	// data.
	if ( !pvt ) {
		if ( !thisCache.data ) {
			thisCache.data = {};
		}

		thisCache = thisCache.data;
	}

	if ( data !== undefined ) {
		thisCache[ jQuery.camelCase( name ) ] = data;
	}

	// Check for both converted-to-camel and non-converted data property names
	// If a data property was specified
	if ( typeof name === "string" ) {

		// First Try to find as-is property data
		ret = thisCache[ name ];

		// Test for null|undefined property data
		if ( ret == null ) {

			// Try to find the camelCased property
			ret = thisCache[ jQuery.camelCase( name ) ];
		}
	} else {
		ret = thisCache;
	}

	return ret;
}

function internalRemoveData( elem, name, pvt ) {
	if ( !jQuery.acceptData( elem ) ) {
		return;
	}

	var thisCache, i,
		isNode = elem.nodeType,

		// See jQuery.data for more information
		cache = isNode ? jQuery.cache : elem,
		id = isNode ? elem[ jQuery.expando ] : jQuery.expando;

	// If there is already no cache entry for this object, there is no
	// purpose in continuing
	if ( !cache[ id ] ) {
		return;
	}

	if ( name ) {

		thisCache = pvt ? cache[ id ] : cache[ id ].data;

		if ( thisCache ) {

			// Support array or space separated string names for data keys
			if ( !jQuery.isArray( name ) ) {

				// try the string as a key before any manipulation
				if ( name in thisCache ) {
					name = [ name ];
				} else {

					// split the camel cased version by spaces unless a key with the spaces exists
					name = jQuery.camelCase( name );
					if ( name in thisCache ) {
						name = [ name ];
					} else {
						name = name.split(" ");
					}
				}
			} else {
				// If "name" is an array of keys...
				// When data is initially created, via ("key", "val") signature,
				// keys will be converted to camelCase.
				// Since there is no way to tell _how_ a key was added, remove
				// both plain key and camelCase key. #12786
				// This will only penalize the array argument path.
				name = name.concat( jQuery.map( name, jQuery.camelCase ) );
			}

			i = name.length;
			while ( i-- ) {
				delete thisCache[ name[i] ];
			}

			// If there is no data left in the cache, we want to continue
			// and let the cache object itself get destroyed
			if ( pvt ? !isEmptyDataObject(thisCache) : !jQuery.isEmptyObject(thisCache) ) {
				return;
			}
		}
	}

	// See jQuery.data for more information
	if ( !pvt ) {
		delete cache[ id ].data;

		// Don't destroy the parent cache unless the internal data object
		// had been the only thing left in it
		if ( !isEmptyDataObject( cache[ id ] ) ) {
			return;
		}
	}

	// Destroy the cache
	if ( isNode ) {
		jQuery.cleanData( [ elem ], true );

	// Use delete when supported for expandos or `cache` is not a window per isWindow (#10080)
	/* jshint eqeqeq: false */
	} else if ( jQuery.support.deleteExpando || cache != cache.window ) {
		/* jshint eqeqeq: true */
		delete cache[ id ];

	// When all else fails, null
	} else {
		cache[ id ] = null;
	}
}

jQuery.extend({
	cache: {},

	// The following elements throw uncatchable exceptions if you
	// attempt to add expando properties to them.
	noData: {
		"applet": true,
		"embed": true,
		// Ban all objects except for Flash (which handle expandos)
		"object": "clsid:D27CDB6E-AE6D-11cf-96B8-444553540000"
	},

	hasData: function( elem ) {
		elem = elem.nodeType ? jQuery.cache[ elem[jQuery.expando] ] : elem[ jQuery.expando ];
		return !!elem && !isEmptyDataObject( elem );
	},

	data: function( elem, name, data ) {
		return internalData( elem, name, data );
	},

	removeData: function( elem, name ) {
		return internalRemoveData( elem, name );
	},

	// For internal use only.
	_data: function( elem, name, data ) {
		return internalData( elem, name, data, true );
	},

	_removeData: function( elem, name ) {
		return internalRemoveData( elem, name, true );
	},

	// A method for determining if a DOM node can handle the data expando
	acceptData: function( elem ) {
		// Do not set data on non-element because it will not be cleared (#8335).
		if ( elem.nodeType && elem.nodeType !== 1 && elem.nodeType !== 9 ) {
			return false;
		}

		var noData = elem.nodeName && jQuery.noData[ elem.nodeName.toLowerCase() ];

		// nodes accept data unless otherwise specified; rejection can be conditional
		return !noData || noData !== true && elem.getAttribute("classid") === noData;
	}
});

jQuery.fn.extend({
	data: function( key, value ) {
		var attrs, name,
			data = null,
			i = 0,
			elem = this[0];

		// Special expections of .data basically thwart jQuery.access,
		// so implement the relevant behavior ourselves

		// Gets all values
		if ( key === undefined ) {
			if ( this.length ) {
				data = jQuery.data( elem );

				if ( elem.nodeType === 1 && !jQuery._data( elem, "parsedAttrs" ) ) {
					attrs = elem.attributes;
					for ( ; i < attrs.length; i++ ) {
						name = attrs[i].name;

						if ( name.indexOf("data-") === 0 ) {
							name = jQuery.camelCase( name.slice(5) );

							dataAttr( elem, name, data[ name ] );
						}
					}
					jQuery._data( elem, "parsedAttrs", true );
				}
			}

			return data;
		}

		// Sets multiple values
		if ( typeof key === "object" ) {
			return this.each(function() {
				jQuery.data( this, key );
			});
		}

		return arguments.length > 1 ?

			// Sets one value
			this.each(function() {
				jQuery.data( this, key, value );
			}) :

			// Gets one value
			// Try to fetch any internally stored data first
			elem ? dataAttr( elem, key, jQuery.data( elem, key ) ) : null;
	},

	removeData: function( key ) {
		return this.each(function() {
			jQuery.removeData( this, key );
		});
	}
});

function dataAttr( elem, key, data ) {
	// If nothing was found internally, try to fetch any
	// data from the HTML5 data-* attribute
	if ( data === undefined && elem.nodeType === 1 ) {

		var name = "data-" + key.replace( rmultiDash, "-$1" ).toLowerCase();

		data = elem.getAttribute( name );

		if ( typeof data === "string" ) {
			try {
				data = data === "true" ? true :
					data === "false" ? false :
					data === "null" ? null :
					// Only convert to a number if it doesn't change the string
					+data + "" === data ? +data :
					rbrace.test( data ) ? jQuery.parseJSON( data ) :
						data;
			} catch( e ) {}

			// Make sure we set the data so it isn't changed later
			jQuery.data( elem, key, data );

		} else {
			data = undefined;
		}
	}

	return data;
}

// checks a cache object for emptiness
function isEmptyDataObject( obj ) {
	var name;
	for ( name in obj ) {

		// if the public data object is empty, the private is still empty
		if ( name === "data" && jQuery.isEmptyObject( obj[name] ) ) {
			continue;
		}
		if ( name !== "toJSON" ) {
			return false;
		}
	}

	return true;
}
jQuery.extend({
	queue: function( elem, type, data ) {
		var queue;

		if ( elem ) {
			type = ( type || "fx" ) + "queue";
			queue = jQuery._data( elem, type );

			// Speed up dequeue by getting out quickly if this is just a lookup
			if ( data ) {
				if ( !queue || jQuery.isArray(data) ) {
					queue = jQuery._data( elem, type, jQuery.makeArray(data) );
				} else {
					queue.push( data );
				}
			}
			return queue || [];
		}
	},

	dequeue: function( elem, type ) {
		type = type || "fx";

		var queue = jQuery.queue( elem, type ),
			startLength = queue.length,
			fn = queue.shift(),
			hooks = jQuery._queueHooks( elem, type ),
			next = function() {
				jQuery.dequeue( elem, type );
			};

		// If the fx queue is dequeued, always remove the progress sentinel
		if ( fn === "inprogress" ) {
			fn = queue.shift();
			startLength--;
		}

		if ( fn ) {

			// Add a progress sentinel to prevent the fx queue from being
			// automatically dequeued
			if ( type === "fx" ) {
				queue.unshift( "inprogress" );
			}

			// clear up the last queue stop function
			delete hooks.stop;
			fn.call( elem, next, hooks );
		}

		if ( !startLength && hooks ) {
			hooks.empty.fire();
		}
	},

	// not intended for public consumption - generates a queueHooks object, or returns the current one
	_queueHooks: function( elem, type ) {
		var key = type + "queueHooks";
		return jQuery._data( elem, key ) || jQuery._data( elem, key, {
			empty: jQuery.Callbacks("once memory").add(function() {
				jQuery._removeData( elem, type + "queue" );
				jQuery._removeData( elem, key );
			})
		});
	}
});

jQuery.fn.extend({
	queue: function( type, data ) {
		var setter = 2;

		if ( typeof type !== "string" ) {
			data = type;
			type = "fx";
			setter--;
		}

		if ( arguments.length < setter ) {
			return jQuery.queue( this[0], type );
		}

		return data === undefined ?
			this :
			this.each(function() {
				var queue = jQuery.queue( this, type, data );

				// ensure a hooks for this queue
				jQuery._queueHooks( this, type );

				if ( type === "fx" && queue[0] !== "inprogress" ) {
					jQuery.dequeue( this, type );
				}
			});
	},
	dequeue: function( type ) {
		return this.each(function() {
			jQuery.dequeue( this, type );
		});
	},
	// Based off of the plugin by Clint Helfers, with permission.
	// http://blindsignals.com/index.php/2009/07/jquery-delay/
	delay: function( time, type ) {
		time = jQuery.fx ? jQuery.fx.speeds[ time ] || time : time;
		type = type || "fx";

		return this.queue( type, function( next, hooks ) {
			var timeout = setTimeout( next, time );
			hooks.stop = function() {
				clearTimeout( timeout );
			};
		});
	},
	clearQueue: function( type ) {
		return this.queue( type || "fx", [] );
	},
	// Get a promise resolved when queues of a certain type
	// are emptied (fx is the type by default)
	promise: function( type, obj ) {
		var tmp,
			count = 1,
			defer = jQuery.Deferred(),
			elements = this,
			i = this.length,
			resolve = function() {
				if ( !( --count ) ) {
					defer.resolveWith( elements, [ elements ] );
				}
			};

		if ( typeof type !== "string" ) {
			obj = type;
			type = undefined;
		}
		type = type || "fx";

		while( i-- ) {
			tmp = jQuery._data( elements[ i ], type + "queueHooks" );
			if ( tmp && tmp.empty ) {
				count++;
				tmp.empty.add( resolve );
			}
		}
		resolve();
		return defer.promise( obj );
	}
});
var nodeHook, boolHook,
	rclass = /[\t\r\n\f]/g,
	rreturn = /\r/g,
	rfocusable = /^(?:input|select|textarea|button|object)$/i,
	rclickable = /^(?:a|area)$/i,
	ruseDefault = /^(?:checked|selected)$/i,
	getSetAttribute = jQuery.support.getSetAttribute,
	getSetInput = jQuery.support.input;

jQuery.fn.extend({
	attr: function( name, value ) {
		return jQuery.access( this, jQuery.attr, name, value, arguments.length > 1 );
	},

	removeAttr: function( name ) {
		return this.each(function() {
			jQuery.removeAttr( this, name );
		});
	},

	prop: function( name, value ) {
		return jQuery.access( this, jQuery.prop, name, value, arguments.length > 1 );
	},

	removeProp: function( name ) {
		name = jQuery.propFix[ name ] || name;
		return this.each(function() {
			// try/catch handles cases where IE balks (such as removing a property on window)
			try {
				this[ name ] = undefined;
				delete this[ name ];
			} catch( e ) {}
		});
	},

	addClass: function( value ) {
		var classes, elem, cur, clazz, j,
			i = 0,
			len = this.length,
			proceed = typeof value === "string" && value;

		if ( jQuery.isFunction( value ) ) {
			return this.each(function( j ) {
				jQuery( this ).addClass( value.call( this, j, this.className ) );
			});
		}

		if ( proceed ) {
			// The disjunction here is for better compressibility (see removeClass)
			classes = ( value || "" ).match( core_rnotwhite ) || [];

			for ( ; i < len; i++ ) {
				elem = this[ i ];
				cur = elem.nodeType === 1 && ( elem.className ?
					( " " + elem.className + " " ).replace( rclass, " " ) :
					" "
				);

				if ( cur ) {
					j = 0;
					while ( (clazz = classes[j++]) ) {
						if ( cur.indexOf( " " + clazz + " " ) < 0 ) {
							cur += clazz + " ";
						}
					}
					elem.className = jQuery.trim( cur );

				}
			}
		}

		return this;
	},

	removeClass: function( value ) {
		var classes, elem, cur, clazz, j,
			i = 0,
			len = this.length,
			proceed = arguments.length === 0 || typeof value === "string" && value;

		if ( jQuery.isFunction( value ) ) {
			return this.each(function( j ) {
				jQuery( this ).removeClass( value.call( this, j, this.className ) );
			});
		}
		if ( proceed ) {
			classes = ( value || "" ).match( core_rnotwhite ) || [];

			for ( ; i < len; i++ ) {
				elem = this[ i ];
				// This expression is here for better compressibility (see addClass)
				cur = elem.nodeType === 1 && ( elem.className ?
					( " " + elem.className + " " ).replace( rclass, " " ) :
					""
				);

				if ( cur ) {
					j = 0;
					while ( (clazz = classes[j++]) ) {
						// Remove *all* instances
						while ( cur.indexOf( " " + clazz + " " ) >= 0 ) {
							cur = cur.replace( " " + clazz + " ", " " );
						}
					}
					elem.className = value ? jQuery.trim( cur ) : "";
				}
			}
		}

		return this;
	},

	toggleClass: function( value, stateVal ) {
		var type = typeof value;

		if ( typeof stateVal === "boolean" && type === "string" ) {
			return stateVal ? this.addClass( value ) : this.removeClass( value );
		}

		if ( jQuery.isFunction( value ) ) {
			return this.each(function( i ) {
				jQuery( this ).toggleClass( value.call(this, i, this.className, stateVal), stateVal );
			});
		}

		return this.each(function() {
			if ( type === "string" ) {
				// toggle individual class names
				var className,
					i = 0,
					self = jQuery( this ),
					classNames = value.match( core_rnotwhite ) || [];

				while ( (className = classNames[ i++ ]) ) {
					// check each className given, space separated list
					if ( self.hasClass( className ) ) {
						self.removeClass( className );
					} else {
						self.addClass( className );
					}
				}

			// Toggle whole class name
			} else if ( type === core_strundefined || type === "boolean" ) {
				if ( this.className ) {
					// store className if set
					jQuery._data( this, "__className__", this.className );
				}

				// If the element has a class name or if we're passed "false",
				// then remove the whole classname (if there was one, the above saved it).
				// Otherwise bring back whatever was previously saved (if anything),
				// falling back to the empty string if nothing was stored.
				this.className = this.className || value === false ? "" : jQuery._data( this, "__className__" ) || "";
			}
		});
	},

	hasClass: function( selector ) {
		var className = " " + selector + " ",
			i = 0,
			l = this.length;
		for ( ; i < l; i++ ) {
			if ( this[i].nodeType === 1 && (" " + this[i].className + " ").replace(rclass, " ").indexOf( className ) >= 0 ) {
				return true;
			}
		}

		return false;
	},

	val: function( value ) {
		var ret, hooks, isFunction,
			elem = this[0];

		if ( !arguments.length ) {
			if ( elem ) {
				hooks = jQuery.valHooks[ elem.type ] || jQuery.valHooks[ elem.nodeName.toLowerCase() ];

				if ( hooks && "get" in hooks && (ret = hooks.get( elem, "value" )) !== undefined ) {
					return ret;
				}

				ret = elem.value;

				return typeof ret === "string" ?
					// handle most common string cases
					ret.replace(rreturn, "") :
					// handle cases where value is null/undef or number
					ret == null ? "" : ret;
			}

			return;
		}

		isFunction = jQuery.isFunction( value );

		return this.each(function( i ) {
			var val;

			if ( this.nodeType !== 1 ) {
				return;
			}

			if ( isFunction ) {
				val = value.call( this, i, jQuery( this ).val() );
			} else {
				val = value;
			}

			// Treat null/undefined as ""; convert numbers to string
			if ( val == null ) {
				val = "";
			} else if ( typeof val === "number" ) {
				val += "";
			} else if ( jQuery.isArray( val ) ) {
				val = jQuery.map(val, function ( value ) {
					return value == null ? "" : value + "";
				});
			}

			hooks = jQuery.valHooks[ this.type ] || jQuery.valHooks[ this.nodeName.toLowerCase() ];

			// If set returns undefined, fall back to normal setting
			if ( !hooks || !("set" in hooks) || hooks.set( this, val, "value" ) === undefined ) {
				this.value = val;
			}
		});
	}
});

jQuery.extend({
	valHooks: {
		option: {
			get: function( elem ) {
				// Use proper attribute retrieval(#6932, #12072)
				var val = jQuery.find.attr( elem, "value" );
				return val != null ?
					val :
					elem.text;
			}
		},
		select: {
			get: function( elem ) {
				var value, option,
					options = elem.options,
					index = elem.selectedIndex,
					one = elem.type === "select-one" || index < 0,
					values = one ? null : [],
					max = one ? index + 1 : options.length,
					i = index < 0 ?
						max :
						one ? index : 0;

				// Loop through all the selected options
				for ( ; i < max; i++ ) {
					option = options[ i ];

					// oldIE doesn't update selected after form reset (#2551)
					if ( ( option.selected || i === index ) &&
							// Don't return options that are disabled or in a disabled optgroup
							( jQuery.support.optDisabled ? !option.disabled : option.getAttribute("disabled") === null ) &&
							( !option.parentNode.disabled || !jQuery.nodeName( option.parentNode, "optgroup" ) ) ) {

						// Get the specific value for the option
						value = jQuery( option ).val();

						// We don't need an array for one selects
						if ( one ) {
							return value;
						}

						// Multi-Selects return an array
						values.push( value );
					}
				}

				return values;
			},

			set: function( elem, value ) {
				var optionSet, option,
					options = elem.options,
					values = jQuery.makeArray( value ),
					i = options.length;

				while ( i-- ) {
					option = options[ i ];
					if ( (option.selected = jQuery.inArray( jQuery(option).val(), values ) >= 0) ) {
						optionSet = true;
					}
				}

				// force browsers to behave consistently when non-matching value is set
				if ( !optionSet ) {
					elem.selectedIndex = -1;
				}
				return values;
			}
		}
	},

	attr: function( elem, name, value ) {
		var hooks, ret,
			nType = elem.nodeType;

		// don't get/set attributes on text, comment and attribute nodes
		if ( !elem || nType === 3 || nType === 8 || nType === 2 ) {
			return;
		}

		// Fallback to prop when attributes are not supported
		if ( typeof elem.getAttribute === core_strundefined ) {
			return jQuery.prop( elem, name, value );
		}

		// All attributes are lowercase
		// Grab necessary hook if one is defined
		if ( nType !== 1 || !jQuery.isXMLDoc( elem ) ) {
			name = name.toLowerCase();
			hooks = jQuery.attrHooks[ name ] ||
				( jQuery.expr.match.bool.test( name ) ? boolHook : nodeHook );
		}

		if ( value !== undefined ) {

			if ( value === null ) {
				jQuery.removeAttr( elem, name );

			} else if ( hooks && "set" in hooks && (ret = hooks.set( elem, value, name )) !== undefined ) {
				return ret;

			} else {
				elem.setAttribute( name, value + "" );
				return value;
			}

		} else if ( hooks && "get" in hooks && (ret = hooks.get( elem, name )) !== null ) {
			return ret;

		} else {
			ret = jQuery.find.attr( elem, name );

			// Non-existent attributes return null, we normalize to undefined
			return ret == null ?
				undefined :
				ret;
		}
	},

	removeAttr: function( elem, value ) {
		var name, propName,
			i = 0,
			attrNames = value && value.match( core_rnotwhite );

		if ( attrNames && elem.nodeType === 1 ) {
			while ( (name = attrNames[i++]) ) {
				propName = jQuery.propFix[ name ] || name;

				// Boolean attributes get special treatment (#10870)
				if ( jQuery.expr.match.bool.test( name ) ) {
					// Set corresponding property to false
					if ( getSetInput && getSetAttribute || !ruseDefault.test( name ) ) {
						elem[ propName ] = false;
					// Support: IE<9
					// Also clear defaultChecked/defaultSelected (if appropriate)
					} else {
						elem[ jQuery.camelCase( "default-" + name ) ] =
							elem[ propName ] = false;
					}

				// See #9699 for explanation of this approach (setting first, then removal)
				} else {
					jQuery.attr( elem, name, "" );
				}

				elem.removeAttribute( getSetAttribute ? name : propName );
			}
		}
	},

	attrHooks: {
		type: {
			set: function( elem, value ) {
				if ( !jQuery.support.radioValue && value === "radio" && jQuery.nodeName(elem, "input") ) {
					// Setting the type on a radio button after the value resets the value in IE6-9
					// Reset value to default in case type is set after value during creation
					var val = elem.value;
					elem.setAttribute( "type", value );
					if ( val ) {
						elem.value = val;
					}
					return value;
				}
			}
		}
	},

	propFix: {
		"for": "htmlFor",
		"class": "className"
	},

	prop: function( elem, name, value ) {
		var ret, hooks, notxml,
			nType = elem.nodeType;

		// don't get/set properties on text, comment and attribute nodes
		if ( !elem || nType === 3 || nType === 8 || nType === 2 ) {
			return;
		}

		notxml = nType !== 1 || !jQuery.isXMLDoc( elem );

		if ( notxml ) {
			// Fix name and attach hooks
			name = jQuery.propFix[ name ] || name;
			hooks = jQuery.propHooks[ name ];
		}

		if ( value !== undefined ) {
			return hooks && "set" in hooks && (ret = hooks.set( elem, value, name )) !== undefined ?
				ret :
				( elem[ name ] = value );

		} else {
			return hooks && "get" in hooks && (ret = hooks.get( elem, name )) !== null ?
				ret :
				elem[ name ];
		}
	},

	propHooks: {
		tabIndex: {
			get: function( elem ) {
				// elem.tabIndex doesn't always return the correct value when it hasn't been explicitly set
				// http://fluidproject.org/blog/2008/01/09/getting-setting-and-removing-tabindex-values-with-javascript/
				// Use proper attribute retrieval(#12072)
				var tabindex = jQuery.find.attr( elem, "tabindex" );

				return tabindex ?
					parseInt( tabindex, 10 ) :
					rfocusable.test( elem.nodeName ) || rclickable.test( elem.nodeName ) && elem.href ?
						0 :
						-1;
			}
		}
	}
});

// Hooks for boolean attributes
boolHook = {
	set: function( elem, value, name ) {
		if ( value === false ) {
			// Remove boolean attributes when set to false
			jQuery.removeAttr( elem, name );
		} else if ( getSetInput && getSetAttribute || !ruseDefault.test( name ) ) {
			// IE<8 needs the *property* name
			elem.setAttribute( !getSetAttribute && jQuery.propFix[ name ] || name, name );

		// Use defaultChecked and defaultSelected for oldIE
		} else {
			elem[ jQuery.camelCase( "default-" + name ) ] = elem[ name ] = true;
		}

		return name;
	}
};
jQuery.each( jQuery.expr.match.bool.source.match( /\w+/g ), function( i, name ) {
	var getter = jQuery.expr.attrHandle[ name ] || jQuery.find.attr;

	jQuery.expr.attrHandle[ name ] = getSetInput && getSetAttribute || !ruseDefault.test( name ) ?
		function( elem, name, isXML ) {
			var fn = jQuery.expr.attrHandle[ name ],
				ret = isXML ?
					undefined :
					/* jshint eqeqeq: false */
					(jQuery.expr.attrHandle[ name ] = undefined) !=
						getter( elem, name, isXML ) ?

						name.toLowerCase() :
						null;
			jQuery.expr.attrHandle[ name ] = fn;
			return ret;
		} :
		function( elem, name, isXML ) {
			return isXML ?
				undefined :
				elem[ jQuery.camelCase( "default-" + name ) ] ?
					name.toLowerCase() :
					null;
		};
});

// fix oldIE attroperties
if ( !getSetInput || !getSetAttribute ) {
	jQuery.attrHooks.value = {
		set: function( elem, value, name ) {
			if ( jQuery.nodeName( elem, "input" ) ) {
				// Does not return so that setAttribute is also used
				elem.defaultValue = value;
			} else {
				// Use nodeHook if defined (#1954); otherwise setAttribute is fine
				return nodeHook && nodeHook.set( elem, value, name );
			}
		}
	};
}

// IE6/7 do not support getting/setting some attributes with get/setAttribute
if ( !getSetAttribute ) {

	// Use this for any attribute in IE6/7
	// This fixes almost every IE6/7 issue
	nodeHook = {
		set: function( elem, value, name ) {
			// Set the existing or create a new attribute node
			var ret = elem.getAttributeNode( name );
			if ( !ret ) {
				elem.setAttributeNode(
					(ret = elem.ownerDocument.createAttribute( name ))
				);
			}

			ret.value = value += "";

			// Break association with cloned elements by also using setAttribute (#9646)
			return name === "value" || value === elem.getAttribute( name ) ?
				value :
				undefined;
		}
	};
	jQuery.expr.attrHandle.id = jQuery.expr.attrHandle.name = jQuery.expr.attrHandle.coords =
		// Some attributes are constructed with empty-string values when not defined
		function( elem, name, isXML ) {
			var ret;
			return isXML ?
				undefined :
				(ret = elem.getAttributeNode( name )) && ret.value !== "" ?
					ret.value :
					null;
		};
	jQuery.valHooks.button = {
		get: function( elem, name ) {
			var ret = elem.getAttributeNode( name );
			return ret && ret.specified ?
				ret.value :
				undefined;
		},
		set: nodeHook.set
	};

	// Set contenteditable to false on removals(#10429)
	// Setting to empty string throws an error as an invalid value
	jQuery.attrHooks.contenteditable = {
		set: function( elem, value, name ) {
			nodeHook.set( elem, value === "" ? false : value, name );
		}
	};

	// Set width and height to auto instead of 0 on empty string( Bug #8150 )
	// This is for removals
	jQuery.each([ "width", "height" ], function( i, name ) {
		jQuery.attrHooks[ name ] = {
			set: function( elem, value ) {
				if ( value === "" ) {
					elem.setAttribute( name, "auto" );
					return value;
				}
			}
		};
	});
}


// Some attributes require a special call on IE
// http://msdn.microsoft.com/en-us/library/ms536429%28VS.85%29.aspx
if ( !jQuery.support.hrefNormalized ) {
	// href/src property should get the full normalized URL (#10299/#12915)
	jQuery.each([ "href", "src" ], function( i, name ) {
		jQuery.propHooks[ name ] = {
			get: function( elem ) {
				return elem.getAttribute( name, 4 );
			}
		};
	});
}

if ( !jQuery.support.style ) {
	jQuery.attrHooks.style = {
		get: function( elem ) {
			// Return undefined in the case of empty string
			// Note: IE uppercases css property names, but if we were to .toLowerCase()
			// .cssText, that would destroy case senstitivity in URL's, like in "background"
			return elem.style.cssText || undefined;
		},
		set: function( elem, value ) {
			return ( elem.style.cssText = value + "" );
		}
	};
}

// Safari mis-reports the default selected property of an option
// Accessing the parent's selectedIndex property fixes it
if ( !jQuery.support.optSelected ) {
	jQuery.propHooks.selected = {
		get: function( elem ) {
			var parent = elem.parentNode;

			if ( parent ) {
				parent.selectedIndex;

				// Make sure that it also works with optgroups, see #5701
				if ( parent.parentNode ) {
					parent.parentNode.selectedIndex;
				}
			}
			return null;
		}
	};
}

jQuery.each([
	"tabIndex",
	"readOnly",
	"maxLength",
	"cellSpacing",
	"cellPadding",
	"rowSpan",
	"colSpan",
	"useMap",
	"frameBorder",
	"contentEditable"
], function() {
	jQuery.propFix[ this.toLowerCase() ] = this;
});

// IE6/7 call enctype encoding
if ( !jQuery.support.enctype ) {
	jQuery.propFix.enctype = "encoding";
}

// Radios and checkboxes getter/setter
jQuery.each([ "radio", "checkbox" ], function() {
	jQuery.valHooks[ this ] = {
		set: function( elem, value ) {
			if ( jQuery.isArray( value ) ) {
				return ( elem.checked = jQuery.inArray( jQuery(elem).val(), value ) >= 0 );
			}
		}
	};
	if ( !jQuery.support.checkOn ) {
		jQuery.valHooks[ this ].get = function( elem ) {
			// Support: Webkit
			// "" is returned instead of "on" if a value isn't specified
			return elem.getAttribute("value") === null ? "on" : elem.value;
		};
	}
});
var rformElems = /^(?:input|select|textarea)$/i,
	rkeyEvent = /^key/,
	rmouseEvent = /^(?:mouse|contextmenu)|click/,
	rfocusMorph = /^(?:focusinfocus|focusoutblur)$/,
	rtypenamespace = /^([^.]*)(?:\.(.+)|)$/;

function returnTrue() {
	return true;
}

function returnFalse() {
	return false;
}

function safeActiveElement() {
	try {
		return document.activeElement;
	} catch ( err ) { }
}

/*
 * Helper functions for managing events -- not part of the public interface.
 * Props to Dean Edwards' addEvent library for many of the ideas.
 */
jQuery.event = {

	global: {},

	add: function( elem, types, handler, data, selector ) {
		var tmp, events, t, handleObjIn,
			special, eventHandle, handleObj,
			handlers, type, namespaces, origType,
			elemData = jQuery._data( elem );

		// Don't attach events to noData or text/comment nodes (but allow plain objects)
		if ( !elemData ) {
			return;
		}

		// Caller can pass in an object of custom data in lieu of the handler
		if ( handler.handler ) {
			handleObjIn = handler;
			handler = handleObjIn.handler;
			selector = handleObjIn.selector;
		}

		// Make sure that the handler has a unique ID, used to find/remove it later
		if ( !handler.guid ) {
			handler.guid = jQuery.guid++;
		}

		// Init the element's event structure and main handler, if this is the first
		if ( !(events = elemData.events) ) {
			events = elemData.events = {};
		}
		if ( !(eventHandle = elemData.handle) ) {
			eventHandle = elemData.handle = function( e ) {
				// Discard the second event of a jQuery.event.trigger() and
				// when an event is called after a page has unloaded
				return typeof jQuery !== core_strundefined && (!e || jQuery.event.triggered !== e.type) ?
					jQuery.event.dispatch.apply( eventHandle.elem, arguments ) :
					undefined;
			};
			// Add elem as a property of the handle fn to prevent a memory leak with IE non-native events
			eventHandle.elem = elem;
		}

		// Handle multiple events separated by a space
		types = ( types || "" ).match( core_rnotwhite ) || [""];
		t = types.length;
		while ( t-- ) {
			tmp = rtypenamespace.exec( types[t] ) || [];
			type = origType = tmp[1];
			namespaces = ( tmp[2] || "" ).split( "." ).sort();

			// There *must* be a type, no attaching namespace-only handlers
			if ( !type ) {
				continue;
			}

			// If event changes its type, use the special event handlers for the changed type
			special = jQuery.event.special[ type ] || {};

			// If selector defined, determine special event api type, otherwise given type
			type = ( selector ? special.delegateType : special.bindType ) || type;

			// Update special based on newly reset type
			special = jQuery.event.special[ type ] || {};

			// handleObj is passed to all event handlers
			handleObj = jQuery.extend({
				type: type,
				origType: origType,
				data: data,
				handler: handler,
				guid: handler.guid,
				selector: selector,
				needsContext: selector && jQuery.expr.match.needsContext.test( selector ),
				namespace: namespaces.join(".")
			}, handleObjIn );

			// Init the event handler queue if we're the first
			if ( !(handlers = events[ type ]) ) {
				handlers = events[ type ] = [];
				handlers.delegateCount = 0;

				// Only use addEventListener/attachEvent if the special events handler returns false
				if ( !special.setup || special.setup.call( elem, data, namespaces, eventHandle ) === false ) {
					// Bind the global event handler to the element
					if ( elem.addEventListener ) {
						elem.addEventListener( type, eventHandle, false );

					} else if ( elem.attachEvent ) {
						elem.attachEvent( "on" + type, eventHandle );
					}
				}
			}

			if ( special.add ) {
				special.add.call( elem, handleObj );

				if ( !handleObj.handler.guid ) {
					handleObj.handler.guid = handler.guid;
				}
			}

			// Add to the element's handler list, delegates in front
			if ( selector ) {
				handlers.splice( handlers.delegateCount++, 0, handleObj );
			} else {
				handlers.push( handleObj );
			}

			// Keep track of which events have ever been used, for event optimization
			jQuery.event.global[ type ] = true;
		}

		// Nullify elem to prevent memory leaks in IE
		elem = null;
	},

	// Detach an event or set of events from an element
	remove: function( elem, types, handler, selector, mappedTypes ) {
		var j, handleObj, tmp,
			origCount, t, events,
			special, handlers, type,
			namespaces, origType,
			elemData = jQuery.hasData( elem ) && jQuery._data( elem );

		if ( !elemData || !(events = elemData.events) ) {
			return;
		}

		// Once for each type.namespace in types; type may be omitted
		types = ( types || "" ).match( core_rnotwhite ) || [""];
		t = types.length;
		while ( t-- ) {
			tmp = rtypenamespace.exec( types[t] ) || [];
			type = origType = tmp[1];
			namespaces = ( tmp[2] || "" ).split( "." ).sort();

			// Unbind all events (on this namespace, if provided) for the element
			if ( !type ) {
				for ( type in events ) {
					jQuery.event.remove( elem, type + types[ t ], handler, selector, true );
				}
				continue;
			}

			special = jQuery.event.special[ type ] || {};
			type = ( selector ? special.delegateType : special.bindType ) || type;
			handlers = events[ type ] || [];
			tmp = tmp[2] && new RegExp( "(^|\\.)" + namespaces.join("\\.(?:.*\\.|)") + "(\\.|$)" );

			// Remove matching events
			origCount = j = handlers.length;
			while ( j-- ) {
				handleObj = handlers[ j ];

				if ( ( mappedTypes || origType === handleObj.origType ) &&
					( !handler || handler.guid === handleObj.guid ) &&
					( !tmp || tmp.test( handleObj.namespace ) ) &&
					( !selector || selector === handleObj.selector || selector === "**" && handleObj.selector ) ) {
					handlers.splice( j, 1 );

					if ( handleObj.selector ) {
						handlers.delegateCount--;
					}
					if ( special.remove ) {
						special.remove.call( elem, handleObj );
					}
				}
			}

			// Remove generic event handler if we removed something and no more handlers exist
			// (avoids potential for endless recursion during removal of special event handlers)
			if ( origCount && !handlers.length ) {
				if ( !special.teardown || special.teardown.call( elem, namespaces, elemData.handle ) === false ) {
					jQuery.removeEvent( elem, type, elemData.handle );
				}

				delete events[ type ];
			}
		}

		// Remove the expando if it's no longer used
		if ( jQuery.isEmptyObject( events ) ) {
			delete elemData.handle;

			// removeData also checks for emptiness and clears the expando if empty
			// so use it instead of delete
			jQuery._removeData( elem, "events" );
		}
	},

	trigger: function( event, data, elem, onlyHandlers ) {
		var handle, ontype, cur,
			bubbleType, special, tmp, i,
			eventPath = [ elem || document ],
			type = core_hasOwn.call( event, "type" ) ? event.type : event,
			namespaces = core_hasOwn.call( event, "namespace" ) ? event.namespace.split(".") : [];

		cur = tmp = elem = elem || document;

		// Don't do events on text and comment nodes
		if ( elem.nodeType === 3 || elem.nodeType === 8 ) {
			return;
		}

		// focus/blur morphs to focusin/out; ensure we're not firing them right now
		if ( rfocusMorph.test( type + jQuery.event.triggered ) ) {
			return;
		}

		if ( type.indexOf(".") >= 0 ) {
			// Namespaced trigger; create a regexp to match event type in handle()
			namespaces = type.split(".");
			type = namespaces.shift();
			namespaces.sort();
		}
		ontype = type.indexOf(":") < 0 && "on" + type;

		// Caller can pass in a jQuery.Event object, Object, or just an event type string
		event = event[ jQuery.expando ] ?
			event :
			new jQuery.Event( type, typeof event === "object" && event );

		// Trigger bitmask: & 1 for native handlers; & 2 for jQuery (always true)
		event.isTrigger = onlyHandlers ? 2 : 3;
		event.namespace = namespaces.join(".");
		event.namespace_re = event.namespace ?
			new RegExp( "(^|\\.)" + namespaces.join("\\.(?:.*\\.|)") + "(\\.|$)" ) :
			null;

		// Clean up the event in case it is being reused
		event.result = undefined;
		if ( !event.target ) {
			event.target = elem;
		}

		// Clone any incoming data and prepend the event, creating the handler arg list
		data = data == null ?
			[ event ] :
			jQuery.makeArray( data, [ event ] );

		// Allow special events to draw outside the lines
		special = jQuery.event.special[ type ] || {};
		if ( !onlyHandlers && special.trigger && special.trigger.apply( elem, data ) === false ) {
			return;
		}

		// Determine event propagation path in advance, per W3C events spec (#9951)
		// Bubble up to document, then to window; watch for a global ownerDocument var (#9724)
		if ( !onlyHandlers && !special.noBubble && !jQuery.isWindow( elem ) ) {

			bubbleType = special.delegateType || type;
			if ( !rfocusMorph.test( bubbleType + type ) ) {
				cur = cur.parentNode;
			}
			for ( ; cur; cur = cur.parentNode ) {
				eventPath.push( cur );
				tmp = cur;
			}

			// Only add window if we got to document (e.g., not plain obj or detached DOM)
			if ( tmp === (elem.ownerDocument || document) ) {
				eventPath.push( tmp.defaultView || tmp.parentWindow || window );
			}
		}

		// Fire handlers on the event path
		i = 0;
		while ( (cur = eventPath[i++]) && !event.isPropagationStopped() ) {

			event.type = i > 1 ?
				bubbleType :
				special.bindType || type;

			// jQuery handler
			handle = ( jQuery._data( cur, "events" ) || {} )[ event.type ] && jQuery._data( cur, "handle" );
			if ( handle ) {
				handle.apply( cur, data );
			}

			// Native handler
			handle = ontype && cur[ ontype ];
			if ( handle && jQuery.acceptData( cur ) && handle.apply && handle.apply( cur, data ) === false ) {
				event.preventDefault();
			}
		}
		event.type = type;

		// If nobody prevented the default action, do it now
		if ( !onlyHandlers && !event.isDefaultPrevented() ) {

			if ( (!special._default || special._default.apply( eventPath.pop(), data ) === false) &&
				jQuery.acceptData( elem ) ) {

				// Call a native DOM method on the target with the same name name as the event.
				// Can't use an .isFunction() check here because IE6/7 fails that test.
				// Don't do default actions on window, that's where global variables be (#6170)
				if ( ontype && elem[ type ] && !jQuery.isWindow( elem ) ) {

					// Don't re-trigger an onFOO event when we call its FOO() method
					tmp = elem[ ontype ];

					if ( tmp ) {
						elem[ ontype ] = null;
					}

					// Prevent re-triggering of the same event, since we already bubbled it above
					jQuery.event.triggered = type;
					try {
						elem[ type ]();
					} catch ( e ) {
						// IE<9 dies on focus/blur to hidden element (#1486,#12518)
						// only reproducible on winXP IE8 native, not IE9 in IE8 mode
					}
					jQuery.event.triggered = undefined;

					if ( tmp ) {
						elem[ ontype ] = tmp;
					}
				}
			}
		}

		return event.result;
	},

	dispatch: function( event ) {

		// Make a writable jQuery.Event from the native event object
		event = jQuery.event.fix( event );

		var i, ret, handleObj, matched, j,
			handlerQueue = [],
			args = core_slice.call( arguments ),
			handlers = ( jQuery._data( this, "events" ) || {} )[ event.type ] || [],
			special = jQuery.event.special[ event.type ] || {};

		// Use the fix-ed jQuery.Event rather than the (read-only) native event
		args[0] = event;
		event.delegateTarget = this;

		// Call the preDispatch hook for the mapped type, and let it bail if desired
		if ( special.preDispatch && special.preDispatch.call( this, event ) === false ) {
			return;
		}

		// Determine handlers
		handlerQueue = jQuery.event.handlers.call( this, event, handlers );

		// Run delegates first; they may want to stop propagation beneath us
		i = 0;
		while ( (matched = handlerQueue[ i++ ]) && !event.isPropagationStopped() ) {
			event.currentTarget = matched.elem;

			j = 0;
			while ( (handleObj = matched.handlers[ j++ ]) && !event.isImmediatePropagationStopped() ) {

				// Triggered event must either 1) have no namespace, or
				// 2) have namespace(s) a subset or equal to those in the bound event (both can have no namespace).
				if ( !event.namespace_re || event.namespace_re.test( handleObj.namespace ) ) {

					event.handleObj = handleObj;
					event.data = handleObj.data;

					ret = ( (jQuery.event.special[ handleObj.origType ] || {}).handle || handleObj.handler )
							.apply( matched.elem, args );

					if ( ret !== undefined ) {
						if ( (event.result = ret) === false ) {
							event.preventDefault();
							event.stopPropagation();
						}
					}
				}
			}
		}

		// Call the postDispatch hook for the mapped type
		if ( special.postDispatch ) {
			special.postDispatch.call( this, event );
		}

		return event.result;
	},

	handlers: function( event, handlers ) {
		var sel, handleObj, matches, i,
			handlerQueue = [],
			delegateCount = handlers.delegateCount,
			cur = event.target;

		// Find delegate handlers
		// Black-hole SVG <use> instance trees (#13180)
		// Avoid non-left-click bubbling in Firefox (#3861)
		if ( delegateCount && cur.nodeType && (!event.button || event.type !== "click") ) {

			/* jshint eqeqeq: false */
			for ( ; cur != this; cur = cur.parentNode || this ) {
				/* jshint eqeqeq: true */

				// Don't check non-elements (#13208)
				// Don't process clicks on disabled elements (#6911, #8165, #11382, #11764)
				if ( cur.nodeType === 1 && (cur.disabled !== true || event.type !== "click") ) {
					matches = [];
					for ( i = 0; i < delegateCount; i++ ) {
						handleObj = handlers[ i ];

						// Don't conflict with Object.prototype properties (#13203)
						sel = handleObj.selector + " ";

						if ( matches[ sel ] === undefined ) {
							matches[ sel ] = handleObj.needsContext ?
								jQuery( sel, this ).index( cur ) >= 0 :
								jQuery.find( sel, this, null, [ cur ] ).length;
						}
						if ( matches[ sel ] ) {
							matches.push( handleObj );
						}
					}
					if ( matches.length ) {
						handlerQueue.push({ elem: cur, handlers: matches });
					}
				}
			}
		}

		// Add the remaining (directly-bound) handlers
		if ( delegateCount < handlers.length ) {
			handlerQueue.push({ elem: this, handlers: handlers.slice( delegateCount ) });
		}

		return handlerQueue;
	},

	fix: function( event ) {
		if ( event[ jQuery.expando ] ) {
			return event;
		}

		// Create a writable copy of the event object and normalize some properties
		var i, prop, copy,
			type = event.type,
			originalEvent = event,
			fixHook = this.fixHooks[ type ];

		if ( !fixHook ) {
			this.fixHooks[ type ] = fixHook =
				rmouseEvent.test( type ) ? this.mouseHooks :
				rkeyEvent.test( type ) ? this.keyHooks :
				{};
		}
		copy = fixHook.props ? this.props.concat( fixHook.props ) : this.props;

		event = new jQuery.Event( originalEvent );

		i = copy.length;
		while ( i-- ) {
			prop = copy[ i ];
			event[ prop ] = originalEvent[ prop ];
		}

		// Support: IE<9
		// Fix target property (#1925)
		if ( !event.target ) {
			event.target = originalEvent.srcElement || document;
		}

		// Support: Chrome 23+, Safari?
		// Target should not be a text node (#504, #13143)
		if ( event.target.nodeType === 3 ) {
			event.target = event.target.parentNode;
		}

		// Support: IE<9
		// For mouse/key events, metaKey==false if it's undefined (#3368, #11328)
		event.metaKey = !!event.metaKey;

		return fixHook.filter ? fixHook.filter( event, originalEvent ) : event;
	},

	// Includes some event props shared by KeyEvent and MouseEvent
	props: "altKey bubbles cancelable ctrlKey currentTarget eventPhase metaKey relatedTarget shiftKey target timeStamp view which".split(" "),

	fixHooks: {},

	keyHooks: {
		props: "char charCode key keyCode".split(" "),
		filter: function( event, original ) {

			// Add which for key events
			if ( event.which == null ) {
				event.which = original.charCode != null ? original.charCode : original.keyCode;
			}

			return event;
		}
	},

	mouseHooks: {
		props: "button buttons clientX clientY fromElement offsetX offsetY pageX pageY screenX screenY toElement".split(" "),
		filter: function( event, original ) {
			var body, eventDoc, doc,
				button = original.button,
				fromElement = original.fromElement;

			// Calculate pageX/Y if missing and clientX/Y available
			if ( event.pageX == null && original.clientX != null ) {
				eventDoc = event.target.ownerDocument || document;
				doc = eventDoc.documentElement;
				body = eventDoc.body;

				event.pageX = original.clientX + ( doc && doc.scrollLeft || body && body.scrollLeft || 0 ) - ( doc && doc.clientLeft || body && body.clientLeft || 0 );
				event.pageY = original.clientY + ( doc && doc.scrollTop  || body && body.scrollTop  || 0 ) - ( doc && doc.clientTop  || body && body.clientTop  || 0 );
			}

			// Add relatedTarget, if necessary
			if ( !event.relatedTarget && fromElement ) {
				event.relatedTarget = fromElement === event.target ? original.toElement : fromElement;
			}

			// Add which for click: 1 === left; 2 === middle; 3 === right
			// Note: button is not normalized, so don't use it
			if ( !event.which && button !== undefined ) {
				event.which = ( button & 1 ? 1 : ( button & 2 ? 3 : ( button & 4 ? 2 : 0 ) ) );
			}

			return event;
		}
	},

	special: {
		load: {
			// Prevent triggered image.load events from bubbling to window.load
			noBubble: true
		},
		focus: {
			// Fire native event if possible so blur/focus sequence is correct
			trigger: function() {
				if ( this !== safeActiveElement() && this.focus ) {
					try {
						this.focus();
						return false;
					} catch ( e ) {
						// Support: IE<9
						// If we error on focus to hidden element (#1486, #12518),
						// let .trigger() run the handlers
					}
				}
			},
			delegateType: "focusin"
		},
		blur: {
			trigger: function() {
				if ( this === safeActiveElement() && this.blur ) {
					this.blur();
					return false;
				}
			},
			delegateType: "focusout"
		},
		click: {
			// For checkbox, fire native event so checked state will be right
			trigger: function() {
				if ( jQuery.nodeName( this, "input" ) && this.type === "checkbox" && this.click ) {
					this.click();
					return false;
				}
			},

			// For cross-browser consistency, don't fire native .click() on links
			_default: function( event ) {
				return jQuery.nodeName( event.target, "a" );
			}
		},

		beforeunload: {
			postDispatch: function( event ) {

				// Even when returnValue equals to undefined Firefox will still show alert
				if ( event.result !== undefined ) {
					event.originalEvent.returnValue = event.result;
				}
			}
		}
	},

	simulate: function( type, elem, event, bubble ) {
		// Piggyback on a donor event to simulate a different one.
		// Fake originalEvent to avoid donor's stopPropagation, but if the
		// simulated event prevents default then we do the same on the donor.
		var e = jQuery.extend(
			new jQuery.Event(),
			event,
			{
				type: type,
				isSimulated: true,
				originalEvent: {}
			}
		);
		if ( bubble ) {
			jQuery.event.trigger( e, null, elem );
		} else {
			jQuery.event.dispatch.call( elem, e );
		}
		if ( e.isDefaultPrevented() ) {
			event.preventDefault();
		}
	}
};

jQuery.removeEvent = document.removeEventListener ?
	function( elem, type, handle ) {
		if ( elem.removeEventListener ) {
			elem.removeEventListener( type, handle, false );
		}
	} :
	function( elem, type, handle ) {
		var name = "on" + type;

		if ( elem.detachEvent ) {

			// #8545, #7054, preventing memory leaks for custom events in IE6-8
			// detachEvent needed property on element, by name of that event, to properly expose it to GC
			if ( typeof elem[ name ] === core_strundefined ) {
				elem[ name ] = null;
			}

			elem.detachEvent( name, handle );
		}
	};

jQuery.Event = function( src, props ) {
	// Allow instantiation without the 'new' keyword
	if ( !(this instanceof jQuery.Event) ) {
		return new jQuery.Event( src, props );
	}

	// Event object
	if ( src && src.type ) {
		this.originalEvent = src;
		this.type = src.type;

		// Events bubbling up the document may have been marked as prevented
		// by a handler lower down the tree; reflect the correct value.
		this.isDefaultPrevented = ( src.defaultPrevented || src.returnValue === false ||
			src.getPreventDefault && src.getPreventDefault() ) ? returnTrue : returnFalse;

	// Event type
	} else {
		this.type = src;
	}

	// Put explicitly provided properties onto the event object
	if ( props ) {
		jQuery.extend( this, props );
	}

	// Create a timestamp if incoming event doesn't have one
	this.timeStamp = src && src.timeStamp || jQuery.now();

	// Mark it as fixed
	this[ jQuery.expando ] = true;
};

// jQuery.Event is based on DOM3 Events as specified by the ECMAScript Language Binding
// http://www.w3.org/TR/2003/WD-DOM-Level-3-Events-20030331/ecma-script-binding.html
jQuery.Event.prototype = {
	isDefaultPrevented: returnFalse,
	isPropagationStopped: returnFalse,
	isImmediatePropagationStopped: returnFalse,

	preventDefault: function() {
		var e = this.originalEvent;

		this.isDefaultPrevented = returnTrue;
		if ( !e ) {
			return;
		}

		// If preventDefault exists, run it on the original event
		if ( e.preventDefault ) {
			e.preventDefault();

		// Support: IE
		// Otherwise set the returnValue property of the original event to false
		} else {
			e.returnValue = false;
		}
	},
	stopPropagation: function() {
		var e = this.originalEvent;

		this.isPropagationStopped = returnTrue;
		if ( !e ) {
			return;
		}
		// If stopPropagation exists, run it on the original event
		if ( e.stopPropagation ) {
			e.stopPropagation();
		}

		// Support: IE
		// Set the cancelBubble property of the original event to true
		e.cancelBubble = true;
	},
	stopImmediatePropagation: function() {
		this.isImmediatePropagationStopped = returnTrue;
		this.stopPropagation();
	}
};

// Create mouseenter/leave events using mouseover/out and event-time checks
jQuery.each({
	mouseenter: "mouseover",
	mouseleave: "mouseout"
}, function( orig, fix ) {
	jQuery.event.special[ orig ] = {
		delegateType: fix,
		bindType: fix,

		handle: function( event ) {
			var ret,
				target = this,
				related = event.relatedTarget,
				handleObj = event.handleObj;

			// For mousenter/leave call the handler if related is outside the target.
			// NB: No relatedTarget if the mouse left/entered the browser window
			if ( !related || (related !== target && !jQuery.contains( target, related )) ) {
				event.type = handleObj.origType;
				ret = handleObj.handler.apply( this, arguments );
				event.type = fix;
			}
			return ret;
		}
	};
});

// IE submit delegation
if ( !jQuery.support.submitBubbles ) {

	jQuery.event.special.submit = {
		setup: function() {
			// Only need this for delegated form submit events
			if ( jQuery.nodeName( this, "form" ) ) {
				return false;
			}

			// Lazy-add a submit handler when a descendant form may potentially be submitted
			jQuery.event.add( this, "click._submit keypress._submit", function( e ) {
				// Node name check avoids a VML-related crash in IE (#9807)
				var elem = e.target,
					form = jQuery.nodeName( elem, "input" ) || jQuery.nodeName( elem, "button" ) ? elem.form : undefined;
				if ( form && !jQuery._data( form, "submitBubbles" ) ) {
					jQuery.event.add( form, "submit._submit", function( event ) {
						event._submit_bubble = true;
					});
					jQuery._data( form, "submitBubbles", true );
				}
			});
			// return undefined since we don't need an event listener
		},

		postDispatch: function( event ) {
			// If form was submitted by the user, bubble the event up the tree
			if ( event._submit_bubble ) {
				delete event._submit_bubble;
				if ( this.parentNode && !event.isTrigger ) {
					jQuery.event.simulate( "submit", this.parentNode, event, true );
				}
			}
		},

		teardown: function() {
			// Only need this for delegated form submit events
			if ( jQuery.nodeName( this, "form" ) ) {
				return false;
			}

			// Remove delegated handlers; cleanData eventually reaps submit handlers attached above
			jQuery.event.remove( this, "._submit" );
		}
	};
}

// IE change delegation and checkbox/radio fix
if ( !jQuery.support.changeBubbles ) {

	jQuery.event.special.change = {

		setup: function() {

			if ( rformElems.test( this.nodeName ) ) {
				// IE doesn't fire change on a check/radio until blur; trigger it on click
				// after a propertychange. Eat the blur-change in special.change.handle.
				// This still fires onchange a second time for check/radio after blur.
				if ( this.type === "checkbox" || this.type === "radio" ) {
					jQuery.event.add( this, "propertychange._change", function( event ) {
						if ( event.originalEvent.propertyName === "checked" ) {
							this._just_changed = true;
						}
					});
					jQuery.event.add( this, "click._change", function( event ) {
						if ( this._just_changed && !event.isTrigger ) {
							this._just_changed = false;
						}
						// Allow triggered, simulated change events (#11500)
						jQuery.event.simulate( "change", this, event, true );
					});
				}
				return false;
			}
			// Delegated event; lazy-add a change handler on descendant inputs
			jQuery.event.add( this, "beforeactivate._change", function( e ) {
				var elem = e.target;

				if ( rformElems.test( elem.nodeName ) && !jQuery._data( elem, "changeBubbles" ) ) {
					jQuery.event.add( elem, "change._change", function( event ) {
						if ( this.parentNode && !event.isSimulated && !event.isTrigger ) {
							jQuery.event.simulate( "change", this.parentNode, event, true );
						}
					});
					jQuery._data( elem, "changeBubbles", true );
				}
			});
		},

		handle: function( event ) {
			var elem = event.target;

			// Swallow native change events from checkbox/radio, we already triggered them above
			if ( this !== elem || event.isSimulated || event.isTrigger || (elem.type !== "radio" && elem.type !== "checkbox") ) {
				return event.handleObj.handler.apply( this, arguments );
			}
		},

		teardown: function() {
			jQuery.event.remove( this, "._change" );

			return !rformElems.test( this.nodeName );
		}
	};
}

// Create "bubbling" focus and blur events
if ( !jQuery.support.focusinBubbles ) {
	jQuery.each({ focus: "focusin", blur: "focusout" }, function( orig, fix ) {

		// Attach a single capturing handler while someone wants focusin/focusout
		var attaches = 0,
			handler = function( event ) {
				jQuery.event.simulate( fix, event.target, jQuery.event.fix( event ), true );
			};

		jQuery.event.special[ fix ] = {
			setup: function() {
				if ( attaches++ === 0 ) {
					document.addEventListener( orig, handler, true );
				}
			},
			teardown: function() {
				if ( --attaches === 0 ) {
					document.removeEventListener( orig, handler, true );
				}
			}
		};
	});
}

jQuery.fn.extend({

	on: function( types, selector, data, fn, /*INTERNAL*/ one ) {
		var type, origFn;

		// Types can be a map of types/handlers
		if ( typeof types === "object" ) {
			// ( types-Object, selector, data )
			if ( typeof selector !== "string" ) {
				// ( types-Object, data )
				data = data || selector;
				selector = undefined;
			}
			for ( type in types ) {
				this.on( type, selector, data, types[ type ], one );
			}
			return this;
		}

		if ( data == null && fn == null ) {
			// ( types, fn )
			fn = selector;
			data = selector = undefined;
		} else if ( fn == null ) {
			if ( typeof selector === "string" ) {
				// ( types, selector, fn )
				fn = data;
				data = undefined;
			} else {
				// ( types, data, fn )
				fn = data;
				data = selector;
				selector = undefined;
			}
		}
		if ( fn === false ) {
			fn = returnFalse;
		} else if ( !fn ) {
			return this;
		}

		if ( one === 1 ) {
			origFn = fn;
			fn = function( event ) {
				// Can use an empty set, since event contains the info
				jQuery().off( event );
				return origFn.apply( this, arguments );
			};
			// Use same guid so caller can remove using origFn
			fn.guid = origFn.guid || ( origFn.guid = jQuery.guid++ );
		}
		return this.each( function() {
			jQuery.event.add( this, types, fn, data, selector );
		});
	},
	one: function( types, selector, data, fn ) {
		return this.on( types, selector, data, fn, 1 );
	},
	off: function( types, selector, fn ) {
		var handleObj, type;
		if ( types && types.preventDefault && types.handleObj ) {
			// ( event )  dispatched jQuery.Event
			handleObj = types.handleObj;
			jQuery( types.delegateTarget ).off(
				handleObj.namespace ? handleObj.origType + "." + handleObj.namespace : handleObj.origType,
				handleObj.selector,
				handleObj.handler
			);
			return this;
		}
		if ( typeof types === "object" ) {
			// ( types-object [, selector] )
			for ( type in types ) {
				this.off( type, selector, types[ type ] );
			}
			return this;
		}
		if ( selector === false || typeof selector === "function" ) {
			// ( types [, fn] )
			fn = selector;
			selector = undefined;
		}
		if ( fn === false ) {
			fn = returnFalse;
		}
		return this.each(function() {
			jQuery.event.remove( this, types, fn, selector );
		});
	},

	trigger: function( type, data ) {
		return this.each(function() {
			jQuery.event.trigger( type, data, this );
		});
	},
	triggerHandler: function( type, data ) {
		var elem = this[0];
		if ( elem ) {
			return jQuery.event.trigger( type, data, elem, true );
		}
	}
});
var isSimple = /^.[^:#\[\.,]*$/,
	rparentsprev = /^(?:parents|prev(?:Until|All))/,
	rneedsContext = jQuery.expr.match.needsContext,
	// methods guaranteed to produce a unique set when starting from a unique set
	guaranteedUnique = {
		children: true,
		contents: true,
		next: true,
		prev: true
	};

jQuery.fn.extend({
	find: function( selector ) {
		var i,
			ret = [],
			self = this,
			len = self.length;

		if ( typeof selector !== "string" ) {
			return this.pushStack( jQuery( selector ).filter(function() {
				for ( i = 0; i < len; i++ ) {
					if ( jQuery.contains( self[ i ], this ) ) {
						return true;
					}
				}
			}) );
		}

		for ( i = 0; i < len; i++ ) {
			jQuery.find( selector, self[ i ], ret );
		}

		// Needed because $( selector, context ) becomes $( context ).find( selector )
		ret = this.pushStack( len > 1 ? jQuery.unique( ret ) : ret );
		ret.selector = this.selector ? this.selector + " " + selector : selector;
		return ret;
	},

	has: function( target ) {
		var i,
			targets = jQuery( target, this ),
			len = targets.length;

		return this.filter(function() {
			for ( i = 0; i < len; i++ ) {
				if ( jQuery.contains( this, targets[i] ) ) {
					return true;
				}
			}
		});
	},

	not: function( selector ) {
		return this.pushStack( winnow(this, selector || [], true) );
	},

	filter: function( selector ) {
		return this.pushStack( winnow(this, selector || [], false) );
	},

	is: function( selector ) {
		return !!winnow(
			this,

			// If this is a positional/relative selector, check membership in the returned set
			// so $("p:first").is("p:last") won't return true for a doc with two "p".
			typeof selector === "string" && rneedsContext.test( selector ) ?
				jQuery( selector ) :
				selector || [],
			false
		).length;
	},

	closest: function( selectors, context ) {
		var cur,
			i = 0,
			l = this.length,
			ret = [],
			pos = rneedsContext.test( selectors ) || typeof selectors !== "string" ?
				jQuery( selectors, context || this.context ) :
				0;

		for ( ; i < l; i++ ) {
			for ( cur = this[i]; cur && cur !== context; cur = cur.parentNode ) {
				// Always skip document fragments
				if ( cur.nodeType < 11 && (pos ?
					pos.index(cur) > -1 :

					// Don't pass non-elements to Sizzle
					cur.nodeType === 1 &&
						jQuery.find.matchesSelector(cur, selectors)) ) {

					cur = ret.push( cur );
					break;
				}
			}
		}

		return this.pushStack( ret.length > 1 ? jQuery.unique( ret ) : ret );
	},

	// Determine the position of an element within
	// the matched set of elements
	index: function( elem ) {

		// No argument, return index in parent
		if ( !elem ) {
			return ( this[0] && this[0].parentNode ) ? this.first().prevAll().length : -1;
		}

		// index in selector
		if ( typeof elem === "string" ) {
			return jQuery.inArray( this[0], jQuery( elem ) );
		}

		// Locate the position of the desired element
		return jQuery.inArray(
			// If it receives a jQuery object, the first element is used
			elem.jquery ? elem[0] : elem, this );
	},

	add: function( selector, context ) {
		var set = typeof selector === "string" ?
				jQuery( selector, context ) :
				jQuery.makeArray( selector && selector.nodeType ? [ selector ] : selector ),
			all = jQuery.merge( this.get(), set );

		return this.pushStack( jQuery.unique(all) );
	},

	addBack: function( selector ) {
		return this.add( selector == null ?
			this.prevObject : this.prevObject.filter(selector)
		);
	}
});

function sibling( cur, dir ) {
	do {
		cur = cur[ dir ];
	} while ( cur && cur.nodeType !== 1 );

	return cur;
}

jQuery.each({
	parent: function( elem ) {
		var parent = elem.parentNode;
		return parent && parent.nodeType !== 11 ? parent : null;
	},
	parents: function( elem ) {
		return jQuery.dir( elem, "parentNode" );
	},
	parentsUntil: function( elem, i, until ) {
		return jQuery.dir( elem, "parentNode", until );
	},
	next: function( elem ) {
		return sibling( elem, "nextSibling" );
	},
	prev: function( elem ) {
		return sibling( elem, "previousSibling" );
	},
	nextAll: function( elem ) {
		return jQuery.dir( elem, "nextSibling" );
	},
	prevAll: function( elem ) {
		return jQuery.dir( elem, "previousSibling" );
	},
	nextUntil: function( elem, i, until ) {
		return jQuery.dir( elem, "nextSibling", until );
	},
	prevUntil: function( elem, i, until ) {
		return jQuery.dir( elem, "previousSibling", until );
	},
	siblings: function( elem ) {
		return jQuery.sibling( ( elem.parentNode || {} ).firstChild, elem );
	},
	children: function( elem ) {
		return jQuery.sibling( elem.firstChild );
	},
	contents: function( elem ) {
		return jQuery.nodeName( elem, "iframe" ) ?
			elem.contentDocument || elem.contentWindow.document :
			jQuery.merge( [], elem.childNodes );
	}
}, function( name, fn ) {
	jQuery.fn[ name ] = function( until, selector ) {
		var ret = jQuery.map( this, fn, until );

		if ( name.slice( -5 ) !== "Until" ) {
			selector = until;
		}

		if ( selector && typeof selector === "string" ) {
			ret = jQuery.filter( selector, ret );
		}

		if ( this.length > 1 ) {
			// Remove duplicates
			if ( !guaranteedUnique[ name ] ) {
				ret = jQuery.unique( ret );
			}

			// Reverse order for parents* and prev-derivatives
			if ( rparentsprev.test( name ) ) {
				ret = ret.reverse();
			}
		}

		return this.pushStack( ret );
	};
});

jQuery.extend({
	filter: function( expr, elems, not ) {
		var elem = elems[ 0 ];

		if ( not ) {
			expr = ":not(" + expr + ")";
		}

		return elems.length === 1 && elem.nodeType === 1 ?
			jQuery.find.matchesSelector( elem, expr ) ? [ elem ] : [] :
			jQuery.find.matches( expr, jQuery.grep( elems, function( elem ) {
				return elem.nodeType === 1;
			}));
	},

	dir: function( elem, dir, until ) {
		var matched = [],
			cur = elem[ dir ];

		while ( cur && cur.nodeType !== 9 && (until === undefined || cur.nodeType !== 1 || !jQuery( cur ).is( until )) ) {
			if ( cur.nodeType === 1 ) {
				matched.push( cur );
			}
			cur = cur[dir];
		}
		return matched;
	},

	sibling: function( n, elem ) {
		var r = [];

		for ( ; n; n = n.nextSibling ) {
			if ( n.nodeType === 1 && n !== elem ) {
				r.push( n );
			}
		}

		return r;
	}
});

// Implement the identical functionality for filter and not
function winnow( elements, qualifier, not ) {
	if ( jQuery.isFunction( qualifier ) ) {
		return jQuery.grep( elements, function( elem, i ) {
			/* jshint -W018 */
			return !!qualifier.call( elem, i, elem ) !== not;
		});

	}

	if ( qualifier.nodeType ) {
		return jQuery.grep( elements, function( elem ) {
			return ( elem === qualifier ) !== not;
		});

	}

	if ( typeof qualifier === "string" ) {
		if ( isSimple.test( qualifier ) ) {
			return jQuery.filter( qualifier, elements, not );
		}

		qualifier = jQuery.filter( qualifier, elements );
	}

	return jQuery.grep( elements, function( elem ) {
		return ( jQuery.inArray( elem, qualifier ) >= 0 ) !== not;
	});
}
function createSafeFragment( document ) {
	var list = nodeNames.split( "|" ),
		safeFrag = document.createDocumentFragment();

	if ( safeFrag.createElement ) {
		while ( list.length ) {
			safeFrag.createElement(
				list.pop()
			);
		}
	}
	return safeFrag;
}

var nodeNames = "abbr|article|aside|audio|bdi|canvas|data|datalist|details|figcaption|figure|footer|" +
		"header|hgroup|mark|meter|nav|output|progress|section|summary|time|video",
	rinlinejQuery = / jQuery\d+="(?:null|\d+)"/g,
	rnoshimcache = new RegExp("<(?:" + nodeNames + ")[\\s/>]", "i"),
	rleadingWhitespace = /^\s+/,
	rxhtmlTag = /<(?!area|br|col|embed|hr|img|input|link|meta|param)(([\w:]+)[^>]*)\/>/gi,
	rtagName = /<([\w:]+)/,
	rtbody = /<tbody/i,
	rhtml = /<|&#?\w+;/,
	rnoInnerhtml = /<(?:script|style|link)/i,
	manipulation_rcheckableType = /^(?:checkbox|radio)$/i,
	// checked="checked" or checked
	rchecked = /checked\s*(?:[^=]|=\s*.checked.)/i,
	rscriptType = /^$|\/(?:java|ecma)script/i,
	rscriptTypeMasked = /^true\/(.*)/,
	rcleanScript = /^\s*<!(?:\[CDATA\[|--)|(?:\]\]|--)>\s*$/g,

	// We have to close these tags to support XHTML (#13200)
	wrapMap = {
		option: [ 1, "<select multiple='multiple'>", "</select>" ],
		legend: [ 1, "<fieldset>", "</fieldset>" ],
		area: [ 1, "<map>", "</map>" ],
		param: [ 1, "<object>", "</object>" ],
		thead: [ 1, "<table>", "</table>" ],
		tr: [ 2, "<table><tbody>", "</tbody></table>" ],
		col: [ 2, "<table><tbody></tbody><colgroup>", "</colgroup></table>" ],
		td: [ 3, "<table><tbody><tr>", "</tr></tbody></table>" ],

		// IE6-8 can't serialize link, script, style, or any html5 (NoScope) tags,
		// unless wrapped in a div with non-breaking characters in front of it.
		_default: jQuery.support.htmlSerialize ? [ 0, "", "" ] : [ 1, "X<div>", "</div>"  ]
	},
	safeFragment = createSafeFragment( document ),
	fragmentDiv = safeFragment.appendChild( document.createElement("div") );

wrapMap.optgroup = wrapMap.option;
wrapMap.tbody = wrapMap.tfoot = wrapMap.colgroup = wrapMap.caption = wrapMap.thead;
wrapMap.th = wrapMap.td;

jQuery.fn.extend({
	text: function( value ) {
		return jQuery.access( this, function( value ) {
			return value === undefined ?
				jQuery.text( this ) :
				this.empty().append( ( this[0] && this[0].ownerDocument || document ).createTextNode( value ) );
		}, null, value, arguments.length );
	},

	append: function() {
		return this.domManip( arguments, function( elem ) {
			if ( this.nodeType === 1 || this.nodeType === 11 || this.nodeType === 9 ) {
				var target = manipulationTarget( this, elem );
				target.appendChild( elem );
			}
		});
	},

	prepend: function() {
		return this.domManip( arguments, function( elem ) {
			if ( this.nodeType === 1 || this.nodeType === 11 || this.nodeType === 9 ) {
				var target = manipulationTarget( this, elem );
				target.insertBefore( elem, target.firstChild );
			}
		});
	},

	before: function() {
		return this.domManip( arguments, function( elem ) {
			if ( this.parentNode ) {
				this.parentNode.insertBefore( elem, this );
			}
		});
	},

	after: function() {
		return this.domManip( arguments, function( elem ) {
			if ( this.parentNode ) {
				this.parentNode.insertBefore( elem, this.nextSibling );
			}
		});
	},

	// keepData is for internal use only--do not document
	remove: function( selector, keepData ) {
		var elem,
			elems = selector ? jQuery.filter( selector, this ) : this,
			i = 0;

		for ( ; (elem = elems[i]) != null; i++ ) {

			if ( !keepData && elem.nodeType === 1 ) {
				jQuery.cleanData( getAll( elem ) );
			}

			if ( elem.parentNode ) {
				if ( keepData && jQuery.contains( elem.ownerDocument, elem ) ) {
					setGlobalEval( getAll( elem, "script" ) );
				}
				elem.parentNode.removeChild( elem );
			}
		}

		return this;
	},

	empty: function() {
		var elem,
			i = 0;

		for ( ; (elem = this[i]) != null; i++ ) {
			// Remove element nodes and prevent memory leaks
			if ( elem.nodeType === 1 ) {
				jQuery.cleanData( getAll( elem, false ) );
			}

			// Remove any remaining nodes
			while ( elem.firstChild ) {
				elem.removeChild( elem.firstChild );
			}

			// If this is a select, ensure that it displays empty (#12336)
			// Support: IE<9
			if ( elem.options && jQuery.nodeName( elem, "select" ) ) {
				elem.options.length = 0;
			}
		}

		return this;
	},

	clone: function( dataAndEvents, deepDataAndEvents ) {
		dataAndEvents = dataAndEvents == null ? false : dataAndEvents;
		deepDataAndEvents = deepDataAndEvents == null ? dataAndEvents : deepDataAndEvents;

		return this.map( function () {
			return jQuery.clone( this, dataAndEvents, deepDataAndEvents );
		});
	},

	html: function( value ) {
		return jQuery.access( this, function( value ) {
			var elem = this[0] || {},
				i = 0,
				l = this.length;

			if ( value === undefined ) {
				return elem.nodeType === 1 ?
					elem.innerHTML.replace( rinlinejQuery, "" ) :
					undefined;
			}

			// See if we can take a shortcut and just use innerHTML
			if ( typeof value === "string" && !rnoInnerhtml.test( value ) &&
				( jQuery.support.htmlSerialize || !rnoshimcache.test( value )  ) &&
				( jQuery.support.leadingWhitespace || !rleadingWhitespace.test( value ) ) &&
				!wrapMap[ ( rtagName.exec( value ) || ["", ""] )[1].toLowerCase() ] ) {

				value = value.replace( rxhtmlTag, "<$1></$2>" );

				try {
					for (; i < l; i++ ) {
						// Remove element nodes and prevent memory leaks
						elem = this[i] || {};
						if ( elem.nodeType === 1 ) {
							jQuery.cleanData( getAll( elem, false ) );
							elem.innerHTML = value;
						}
					}

					elem = 0;

				// If using innerHTML throws an exception, use the fallback method
				} catch(e) {}
			}

			if ( elem ) {
				this.empty().append( value );
			}
		}, null, value, arguments.length );
	},

	replaceWith: function() {
		var
			// Snapshot the DOM in case .domManip sweeps something relevant into its fragment
			args = jQuery.map( this, function( elem ) {
				return [ elem.nextSibling, elem.parentNode ];
			}),
			i = 0;

		// Make the changes, replacing each context element with the new content
		this.domManip( arguments, function( elem ) {
			var next = args[ i++ ],
				parent = args[ i++ ];

			if ( parent ) {
				// Don't use the snapshot next if it has moved (#13810)
				if ( next && next.parentNode !== parent ) {
					next = this.nextSibling;
				}
				jQuery( this ).remove();
				parent.insertBefore( elem, next );
			}
		// Allow new content to include elements from the context set
		}, true );

		// Force removal if there was no new content (e.g., from empty arguments)
		return i ? this : this.remove();
	},

	detach: function( selector ) {
		return this.remove( selector, true );
	},

	domManip: function( args, callback, allowIntersection ) {

		// Flatten any nested arrays
		args = core_concat.apply( [], args );

		var first, node, hasScripts,
			scripts, doc, fragment,
			i = 0,
			l = this.length,
			set = this,
			iNoClone = l - 1,
			value = args[0],
			isFunction = jQuery.isFunction( value );

		// We can't cloneNode fragments that contain checked, in WebKit
		if ( isFunction || !( l <= 1 || typeof value !== "string" || jQuery.support.checkClone || !rchecked.test( value ) ) ) {
			return this.each(function( index ) {
				var self = set.eq( index );
				if ( isFunction ) {
					args[0] = value.call( this, index, self.html() );
				}
				self.domManip( args, callback, allowIntersection );
			});
		}

		if ( l ) {
			fragment = jQuery.buildFragment( args, this[ 0 ].ownerDocument, false, !allowIntersection && this );
			first = fragment.firstChild;

			if ( fragment.childNodes.length === 1 ) {
				fragment = first;
			}

			if ( first ) {
				scripts = jQuery.map( getAll( fragment, "script" ), disableScript );
				hasScripts = scripts.length;

				// Use the original fragment for the last item instead of the first because it can end up
				// being emptied incorrectly in certain situations (#8070).
				for ( ; i < l; i++ ) {
					node = fragment;

					if ( i !== iNoClone ) {
						node = jQuery.clone( node, true, true );

						// Keep references to cloned scripts for later restoration
						if ( hasScripts ) {
							jQuery.merge( scripts, getAll( node, "script" ) );
						}
					}

					callback.call( this[i], node, i );
				}

				if ( hasScripts ) {
					doc = scripts[ scripts.length - 1 ].ownerDocument;

					// Reenable scripts
					jQuery.map( scripts, restoreScript );

					// Evaluate executable scripts on first document insertion
					for ( i = 0; i < hasScripts; i++ ) {
						node = scripts[ i ];
						if ( rscriptType.test( node.type || "" ) &&
							!jQuery._data( node, "globalEval" ) && jQuery.contains( doc, node ) ) {

							if ( node.src ) {
								// Hope ajax is available...
								jQuery._evalUrl( node.src );
							} else {
								jQuery.globalEval( ( node.text || node.textContent || node.innerHTML || "" ).replace( rcleanScript, "" ) );
							}
						}
					}
				}

				// Fix #11809: Avoid leaking memory
				fragment = first = null;
			}
		}

		return this;
	}
});

// Support: IE<8
// Manipulating tables requires a tbody
function manipulationTarget( elem, content ) {
	return jQuery.nodeName( elem, "table" ) &&
		jQuery.nodeName( content.nodeType === 1 ? content : content.firstChild, "tr" ) ?

		elem.getElementsByTagName("tbody")[0] ||
			elem.appendChild( elem.ownerDocument.createElement("tbody") ) :
		elem;
}

// Replace/restore the type attribute of script elements for safe DOM manipulation
function disableScript( elem ) {
	elem.type = (jQuery.find.attr( elem, "type" ) !== null) + "/" + elem.type;
	return elem;
}
function restoreScript( elem ) {
	var match = rscriptTypeMasked.exec( elem.type );
	if ( match ) {
		elem.type = match[1];
	} else {
		elem.removeAttribute("type");
	}
	return elem;
}

// Mark scripts as having already been evaluated
function setGlobalEval( elems, refElements ) {
	var elem,
		i = 0;
	for ( ; (elem = elems[i]) != null; i++ ) {
		jQuery._data( elem, "globalEval", !refElements || jQuery._data( refElements[i], "globalEval" ) );
	}
}

function cloneCopyEvent( src, dest ) {

	if ( dest.nodeType !== 1 || !jQuery.hasData( src ) ) {
		return;
	}

	var type, i, l,
		oldData = jQuery._data( src ),
		curData = jQuery._data( dest, oldData ),
		events = oldData.events;

	if ( events ) {
		delete curData.handle;
		curData.events = {};

		for ( type in events ) {
			for ( i = 0, l = events[ type ].length; i < l; i++ ) {
				jQuery.event.add( dest, type, events[ type ][ i ] );
			}
		}
	}

	// make the cloned public data object a copy from the original
	if ( curData.data ) {
		curData.data = jQuery.extend( {}, curData.data );
	}
}

function fixCloneNodeIssues( src, dest ) {
	var nodeName, e, data;

	// We do not need to do anything for non-Elements
	if ( dest.nodeType !== 1 ) {
		return;
	}

	nodeName = dest.nodeName.toLowerCase();

	// IE6-8 copies events bound via attachEvent when using cloneNode.
	if ( !jQuery.support.noCloneEvent && dest[ jQuery.expando ] ) {
		data = jQuery._data( dest );

		for ( e in data.events ) {
			jQuery.removeEvent( dest, e, data.handle );
		}

		// Event data gets referenced instead of copied if the expando gets copied too
		dest.removeAttribute( jQuery.expando );
	}

	// IE blanks contents when cloning scripts, and tries to evaluate newly-set text
	if ( nodeName === "script" && dest.text !== src.text ) {
		disableScript( dest ).text = src.text;
		restoreScript( dest );

	// IE6-10 improperly clones children of object elements using classid.
	// IE10 throws NoModificationAllowedError if parent is null, #12132.
	} else if ( nodeName === "object" ) {
		if ( dest.parentNode ) {
			dest.outerHTML = src.outerHTML;
		}

		// This path appears unavoidable for IE9. When cloning an object
		// element in IE9, the outerHTML strategy above is not sufficient.
		// If the src has innerHTML and the destination does not,
		// copy the src.innerHTML into the dest.innerHTML. #10324
		if ( jQuery.support.html5Clone && ( src.innerHTML && !jQuery.trim(dest.innerHTML) ) ) {
			dest.innerHTML = src.innerHTML;
		}

	} else if ( nodeName === "input" && manipulation_rcheckableType.test( src.type ) ) {
		// IE6-8 fails to persist the checked state of a cloned checkbox
		// or radio button. Worse, IE6-7 fail to give the cloned element
		// a checked appearance if the defaultChecked value isn't also set

		dest.defaultChecked = dest.checked = src.checked;

		// IE6-7 get confused and end up setting the value of a cloned
		// checkbox/radio button to an empty string instead of "on"
		if ( dest.value !== src.value ) {
			dest.value = src.value;
		}

	// IE6-8 fails to return the selected option to the default selected
	// state when cloning options
	} else if ( nodeName === "option" ) {
		dest.defaultSelected = dest.selected = src.defaultSelected;

	// IE6-8 fails to set the defaultValue to the correct value when
	// cloning other types of input fields
	} else if ( nodeName === "input" || nodeName === "textarea" ) {
		dest.defaultValue = src.defaultValue;
	}
}

jQuery.each({
	appendTo: "append",
	prependTo: "prepend",
	insertBefore: "before",
	insertAfter: "after",
	replaceAll: "replaceWith"
}, function( name, original ) {
	jQuery.fn[ name ] = function( selector ) {
		var elems,
			i = 0,
			ret = [],
			insert = jQuery( selector ),
			last = insert.length - 1;

		for ( ; i <= last; i++ ) {
			elems = i === last ? this : this.clone(true);
			jQuery( insert[i] )[ original ]( elems );

			// Modern browsers can apply jQuery collections as arrays, but oldIE needs a .get()
			core_push.apply( ret, elems.get() );
		}

		return this.pushStack( ret );
	};
});

function getAll( context, tag ) {
	var elems, elem,
		i = 0,
		found = typeof context.getElementsByTagName !== core_strundefined ? context.getElementsByTagName( tag || "*" ) :
			typeof context.querySelectorAll !== core_strundefined ? context.querySelectorAll( tag || "*" ) :
			undefined;

	if ( !found ) {
		for ( found = [], elems = context.childNodes || context; (elem = elems[i]) != null; i++ ) {
			if ( !tag || jQuery.nodeName( elem, tag ) ) {
				found.push( elem );
			} else {
				jQuery.merge( found, getAll( elem, tag ) );
			}
		}
	}

	return tag === undefined || tag && jQuery.nodeName( context, tag ) ?
		jQuery.merge( [ context ], found ) :
		found;
}

// Used in buildFragment, fixes the defaultChecked property
function fixDefaultChecked( elem ) {
	if ( manipulation_rcheckableType.test( elem.type ) ) {
		elem.defaultChecked = elem.checked;
	}
}

jQuery.extend({
	clone: function( elem, dataAndEvents, deepDataAndEvents ) {
		var destElements, node, clone, i, srcElements,
			inPage = jQuery.contains( elem.ownerDocument, elem );

		if ( jQuery.support.html5Clone || jQuery.isXMLDoc(elem) || !rnoshimcache.test( "<" + elem.nodeName + ">" ) ) {
			clone = elem.cloneNode( true );

		// IE<=8 does not properly clone detached, unknown element nodes
		} else {
			fragmentDiv.innerHTML = elem.outerHTML;
			fragmentDiv.removeChild( clone = fragmentDiv.firstChild );
		}

		if ( (!jQuery.support.noCloneEvent || !jQuery.support.noCloneChecked) &&
				(elem.nodeType === 1 || elem.nodeType === 11) && !jQuery.isXMLDoc(elem) ) {

			// We eschew Sizzle here for performance reasons: http://jsperf.com/getall-vs-sizzle/2
			destElements = getAll( clone );
			srcElements = getAll( elem );

			// Fix all IE cloning issues
			for ( i = 0; (node = srcElements[i]) != null; ++i ) {
				// Ensure that the destination node is not null; Fixes #9587
				if ( destElements[i] ) {
					fixCloneNodeIssues( node, destElements[i] );
				}
			}
		}

		// Copy the events from the original to the clone
		if ( dataAndEvents ) {
			if ( deepDataAndEvents ) {
				srcElements = srcElements || getAll( elem );
				destElements = destElements || getAll( clone );

				for ( i = 0; (node = srcElements[i]) != null; i++ ) {
					cloneCopyEvent( node, destElements[i] );
				}
			} else {
				cloneCopyEvent( elem, clone );
			}
		}

		// Preserve script evaluation history
		destElements = getAll( clone, "script" );
		if ( destElements.length > 0 ) {
			setGlobalEval( destElements, !inPage && getAll( elem, "script" ) );
		}

		destElements = srcElements = node = null;

		// Return the cloned set
		return clone;
	},

	buildFragment: function( elems, context, scripts, selection ) {
		var j, elem, contains,
			tmp, tag, tbody, wrap,
			l = elems.length,

			// Ensure a safe fragment
			safe = createSafeFragment( context ),

			nodes = [],
			i = 0;

		for ( ; i < l; i++ ) {
			elem = elems[ i ];

			if ( elem || elem === 0 ) {

				// Add nodes directly
				if ( jQuery.type( elem ) === "object" ) {
					jQuery.merge( nodes, elem.nodeType ? [ elem ] : elem );

				// Convert non-html into a text node
				} else if ( !rhtml.test( elem ) ) {
					nodes.push( context.createTextNode( elem ) );

				// Convert html into DOM nodes
				} else {
					tmp = tmp || safe.appendChild( context.createElement("div") );

					// Deserialize a standard representation
					tag = ( rtagName.exec( elem ) || ["", ""] )[1].toLowerCase();
					wrap = wrapMap[ tag ] || wrapMap._default;

					tmp.innerHTML = wrap[1] + elem.replace( rxhtmlTag, "<$1></$2>" ) + wrap[2];

					// Descend through wrappers to the right content
					j = wrap[0];
					while ( j-- ) {
						tmp = tmp.lastChild;
					}

					// Manually add leading whitespace removed by IE
					if ( !jQuery.support.leadingWhitespace && rleadingWhitespace.test( elem ) ) {
						nodes.push( context.createTextNode( rleadingWhitespace.exec( elem )[0] ) );
					}

					// Remove IE's autoinserted <tbody> from table fragments
					if ( !jQuery.support.tbody ) {

						// String was a <table>, *may* have spurious <tbody>
						elem = tag === "table" && !rtbody.test( elem ) ?
							tmp.firstChild :

							// String was a bare <thead> or <tfoot>
							wrap[1] === "<table>" && !rtbody.test( elem ) ?
								tmp :
								0;

						j = elem && elem.childNodes.length;
						while ( j-- ) {
							if ( jQuery.nodeName( (tbody = elem.childNodes[j]), "tbody" ) && !tbody.childNodes.length ) {
								elem.removeChild( tbody );
							}
						}
					}

					jQuery.merge( nodes, tmp.childNodes );

					// Fix #12392 for WebKit and IE > 9
					tmp.textContent = "";

					// Fix #12392 for oldIE
					while ( tmp.firstChild ) {
						tmp.removeChild( tmp.firstChild );
					}

					// Remember the top-level container for proper cleanup
					tmp = safe.lastChild;
				}
			}
		}

		// Fix #11356: Clear elements from fragment
		if ( tmp ) {
			safe.removeChild( tmp );
		}

		// Reset defaultChecked for any radios and checkboxes
		// about to be appended to the DOM in IE 6/7 (#8060)
		if ( !jQuery.support.appendChecked ) {
			jQuery.grep( getAll( nodes, "input" ), fixDefaultChecked );
		}

		i = 0;
		while ( (elem = nodes[ i++ ]) ) {

			// #4087 - If origin and destination elements are the same, and this is
			// that element, do not do anything
			if ( selection && jQuery.inArray( elem, selection ) !== -1 ) {
				continue;
			}

			contains = jQuery.contains( elem.ownerDocument, elem );

			// Append to fragment
			tmp = getAll( safe.appendChild( elem ), "script" );

			// Preserve script evaluation history
			if ( contains ) {
				setGlobalEval( tmp );
			}

			// Capture executables
			if ( scripts ) {
				j = 0;
				while ( (elem = tmp[ j++ ]) ) {
					if ( rscriptType.test( elem.type || "" ) ) {
						scripts.push( elem );
					}
				}
			}
		}

		tmp = null;

		return safe;
	},

	cleanData: function( elems, /* internal */ acceptData ) {
		var elem, type, id, data,
			i = 0,
			internalKey = jQuery.expando,
			cache = jQuery.cache,
			deleteExpando = jQuery.support.deleteExpando,
			special = jQuery.event.special;

		for ( ; (elem = elems[i]) != null; i++ ) {

			if ( acceptData || jQuery.acceptData( elem ) ) {

				id = elem[ internalKey ];
				data = id && cache[ id ];

				if ( data ) {
					if ( data.events ) {
						for ( type in data.events ) {
							if ( special[ type ] ) {
								jQuery.event.remove( elem, type );

							// This is a shortcut to avoid jQuery.event.remove's overhead
							} else {
								jQuery.removeEvent( elem, type, data.handle );
							}
						}
					}

					// Remove cache only if it was not already removed by jQuery.event.remove
					if ( cache[ id ] ) {

						delete cache[ id ];

						// IE does not allow us to delete expando properties from nodes,
						// nor does it have a removeAttribute function on Document nodes;
						// we must handle all of these cases
						if ( deleteExpando ) {
							delete elem[ internalKey ];

						} else if ( typeof elem.removeAttribute !== core_strundefined ) {
							elem.removeAttribute( internalKey );

						} else {
							elem[ internalKey ] = null;
						}

						core_deletedIds.push( id );
					}
				}
			}
		}
	},

	_evalUrl: function( url ) {
		return jQuery.ajax({
			url: url,
			type: "GET",
			dataType: "script",
			async: false,
			global: false,
			"throws": true
		});
	}
});
jQuery.fn.extend({
	wrapAll: function( html ) {
		if ( jQuery.isFunction( html ) ) {
			return this.each(function(i) {
				jQuery(this).wrapAll( html.call(this, i) );
			});
		}

		if ( this[0] ) {
			// The elements to wrap the target around
			var wrap = jQuery( html, this[0].ownerDocument ).eq(0).clone(true);

			if ( this[0].parentNode ) {
				wrap.insertBefore( this[0] );
			}

			wrap.map(function() {
				var elem = this;

				while ( elem.firstChild && elem.firstChild.nodeType === 1 ) {
					elem = elem.firstChild;
				}

				return elem;
			}).append( this );
		}

		return this;
	},

	wrapInner: function( html ) {
		if ( jQuery.isFunction( html ) ) {
			return this.each(function(i) {
				jQuery(this).wrapInner( html.call(this, i) );
			});
		}

		return this.each(function() {
			var self = jQuery( this ),
				contents = self.contents();

			if ( contents.length ) {
				contents.wrapAll( html );

			} else {
				self.append( html );
			}
		});
	},

	wrap: function( html ) {
		var isFunction = jQuery.isFunction( html );

		return this.each(function(i) {
			jQuery( this ).wrapAll( isFunction ? html.call(this, i) : html );
		});
	},

	unwrap: function() {
		return this.parent().each(function() {
			if ( !jQuery.nodeName( this, "body" ) ) {
				jQuery( this ).replaceWith( this.childNodes );
			}
		}).end();
	}
});
var iframe, getStyles, curCSS,
	ralpha = /alpha\([^)]*\)/i,
	ropacity = /opacity\s*=\s*([^)]*)/,
	rposition = /^(top|right|bottom|left)$/,
	// swappable if display is none or starts with table except "table", "table-cell", or "table-caption"
	// see here for display values: https://developer.mozilla.org/en-US/docs/CSS/display
	rdisplayswap = /^(none|table(?!-c[ea]).+)/,
	rmargin = /^margin/,
	rnumsplit = new RegExp( "^(" + core_pnum + ")(.*)$", "i" ),
	rnumnonpx = new RegExp( "^(" + core_pnum + ")(?!px)[a-z%]+$", "i" ),
	rrelNum = new RegExp( "^([+-])=(" + core_pnum + ")", "i" ),
	elemdisplay = { BODY: "block" },

	cssShow = { position: "absolute", visibility: "hidden", display: "block" },
	cssNormalTransform = {
		letterSpacing: 0,
		fontWeight: 400
	},

	cssExpand = [ "Top", "Right", "Bottom", "Left" ],
	cssPrefixes = [ "Webkit", "O", "Moz", "ms" ];

// return a css property mapped to a potentially vendor prefixed property
function vendorPropName( style, name ) {

	// shortcut for names that are not vendor prefixed
	if ( name in style ) {
		return name;
	}

	// check for vendor prefixed names
	var capName = name.charAt(0).toUpperCase() + name.slice(1),
		origName = name,
		i = cssPrefixes.length;

	while ( i-- ) {
		name = cssPrefixes[ i ] + capName;
		if ( name in style ) {
			return name;
		}
	}

	return origName;
}

function isHidden( elem, el ) {
	// isHidden might be called from jQuery#filter function;
	// in that case, element will be second argument
	elem = el || elem;
	return jQuery.css( elem, "display" ) === "none" || !jQuery.contains( elem.ownerDocument, elem );
}

function showHide( elements, show ) {
	var display, elem, hidden,
		values = [],
		index = 0,
		length = elements.length;

	for ( ; index < length; index++ ) {
		elem = elements[ index ];
		if ( !elem.style ) {
			continue;
		}

		values[ index ] = jQuery._data( elem, "olddisplay" );
		display = elem.style.display;
		if ( show ) {
			// Reset the inline display of this element to learn if it is
			// being hidden by cascaded rules or not
			if ( !values[ index ] && display === "none" ) {
				elem.style.display = "";
			}

			// Set elements which have been overridden with display: none
			// in a stylesheet to whatever the default browser style is
			// for such an element
			if ( elem.style.display === "" && isHidden( elem ) ) {
				values[ index ] = jQuery._data( elem, "olddisplay", css_defaultDisplay(elem.nodeName) );
			}
		} else {

			if ( !values[ index ] ) {
				hidden = isHidden( elem );

				if ( display && display !== "none" || !hidden ) {
					jQuery._data( elem, "olddisplay", hidden ? display : jQuery.css( elem, "display" ) );
				}
			}
		}
	}

	// Set the display of most of the elements in a second loop
	// to avoid the constant reflow
	for ( index = 0; index < length; index++ ) {
		elem = elements[ index ];
		if ( !elem.style ) {
			continue;
		}
		if ( !show || elem.style.display === "none" || elem.style.display === "" ) {
			elem.style.display = show ? values[ index ] || "" : "none";
		}
	}

	return elements;
}

jQuery.fn.extend({
	css: function( name, value ) {
		return jQuery.access( this, function( elem, name, value ) {
			var len, styles,
				map = {},
				i = 0;

			if ( jQuery.isArray( name ) ) {
				styles = getStyles( elem );
				len = name.length;

				for ( ; i < len; i++ ) {
					map[ name[ i ] ] = jQuery.css( elem, name[ i ], false, styles );
				}

				return map;
			}

			return value !== undefined ?
				jQuery.style( elem, name, value ) :
				jQuery.css( elem, name );
		}, name, value, arguments.length > 1 );
	},
	show: function() {
		return showHide( this, true );
	},
	hide: function() {
		return showHide( this );
	},
	toggle: function( state ) {
		if ( typeof state === "boolean" ) {
			return state ? this.show() : this.hide();
		}

		return this.each(function() {
			if ( isHidden( this ) ) {
				jQuery( this ).show();
			} else {
				jQuery( this ).hide();
			}
		});
	}
});

jQuery.extend({
	// Add in style property hooks for overriding the default
	// behavior of getting and setting a style property
	cssHooks: {
		opacity: {
			get: function( elem, computed ) {
				if ( computed ) {
					// We should always get a number back from opacity
					var ret = curCSS( elem, "opacity" );
					return ret === "" ? "1" : ret;
				}
			}
		}
	},

	// Don't automatically add "px" to these possibly-unitless properties
	cssNumber: {
		"columnCount": true,
		"fillOpacity": true,
		"fontWeight": true,
		"lineHeight": true,
		"opacity": true,
		"order": true,
		"orphans": true,
		"widows": true,
		"zIndex": true,
		"zoom": true
	},

	// Add in properties whose names you wish to fix before
	// setting or getting the value
	cssProps: {
		// normalize float css property
		"float": jQuery.support.cssFloat ? "cssFloat" : "styleFloat"
	},

	// Get and set the style property on a DOM Node
	style: function( elem, name, value, extra ) {
		// Don't set styles on text and comment nodes
		if ( !elem || elem.nodeType === 3 || elem.nodeType === 8 || !elem.style ) {
			return;
		}

		// Make sure that we're working with the right name
		var ret, type, hooks,
			origName = jQuery.camelCase( name ),
			style = elem.style;

		name = jQuery.cssProps[ origName ] || ( jQuery.cssProps[ origName ] = vendorPropName( style, origName ) );

		// gets hook for the prefixed version
		// followed by the unprefixed version
		hooks = jQuery.cssHooks[ name ] || jQuery.cssHooks[ origName ];

		// Check if we're setting a value
		if ( value !== undefined ) {
			type = typeof value;

			// convert relative number strings (+= or -=) to relative numbers. #7345
			if ( type === "string" && (ret = rrelNum.exec( value )) ) {
				value = ( ret[1] + 1 ) * ret[2] + parseFloat( jQuery.css( elem, name ) );
				// Fixes bug #9237
				type = "number";
			}

			// Make sure that NaN and null values aren't set. See: #7116
			if ( value == null || type === "number" && isNaN( value ) ) {
				return;
			}

			// If a number was passed in, add 'px' to the (except for certain CSS properties)
			if ( type === "number" && !jQuery.cssNumber[ origName ] ) {
				value += "px";
			}

			// Fixes #8908, it can be done more correctly by specifing setters in cssHooks,
			// but it would mean to define eight (for every problematic property) identical functions
			if ( !jQuery.support.clearCloneStyle && value === "" && name.indexOf("background") === 0 ) {
				style[ name ] = "inherit";
			}

			// If a hook was provided, use that value, otherwise just set the specified value
			if ( !hooks || !("set" in hooks) || (value = hooks.set( elem, value, extra )) !== undefined ) {

				// Wrapped to prevent IE from throwing errors when 'invalid' values are provided
				// Fixes bug #5509
				try {
					style[ name ] = value;
				} catch(e) {}
			}

		} else {
			// If a hook was provided get the non-computed value from there
			if ( hooks && "get" in hooks && (ret = hooks.get( elem, false, extra )) !== undefined ) {
				return ret;
			}

			// Otherwise just get the value from the style object
			return style[ name ];
		}
	},

	css: function( elem, name, extra, styles ) {
		var num, val, hooks,
			origName = jQuery.camelCase( name );

		// Make sure that we're working with the right name
		name = jQuery.cssProps[ origName ] || ( jQuery.cssProps[ origName ] = vendorPropName( elem.style, origName ) );

		// gets hook for the prefixed version
		// followed by the unprefixed version
		hooks = jQuery.cssHooks[ name ] || jQuery.cssHooks[ origName ];

		// If a hook was provided get the computed value from there
		if ( hooks && "get" in hooks ) {
			val = hooks.get( elem, true, extra );
		}

		// Otherwise, if a way to get the computed value exists, use that
		if ( val === undefined ) {
			val = curCSS( elem, name, styles );
		}

		//convert "normal" to computed value
		if ( val === "normal" && name in cssNormalTransform ) {
			val = cssNormalTransform[ name ];
		}

		// Return, converting to number if forced or a qualifier was provided and val looks numeric
		if ( extra === "" || extra ) {
			num = parseFloat( val );
			return extra === true || jQuery.isNumeric( num ) ? num || 0 : val;
		}
		return val;
	}
});

// NOTE: we've included the "window" in window.getComputedStyle
// because jsdom on node.js will break without it.
if ( window.getComputedStyle ) {
	getStyles = function( elem ) {
		return window.getComputedStyle( elem, null );
	};

	curCSS = function( elem, name, _computed ) {
		var width, minWidth, maxWidth,
			computed = _computed || getStyles( elem ),

			// getPropertyValue is only needed for .css('filter') in IE9, see #12537
			ret = computed ? computed.getPropertyValue( name ) || computed[ name ] : undefined,
			style = elem.style;

		if ( computed ) {

			if ( ret === "" && !jQuery.contains( elem.ownerDocument, elem ) ) {
				ret = jQuery.style( elem, name );
			}

			// A tribute to the "awesome hack by Dean Edwards"
			// Chrome < 17 and Safari 5.0 uses "computed value" instead of "used value" for margin-right
			// Safari 5.1.7 (at least) returns percentage for a larger set of values, but width seems to be reliably pixels
			// this is against the CSSOM draft spec: http://dev.w3.org/csswg/cssom/#resolved-values
			if ( rnumnonpx.test( ret ) && rmargin.test( name ) ) {

				// Remember the original values
				width = style.width;
				minWidth = style.minWidth;
				maxWidth = style.maxWidth;

				// Put in the new values to get a computed value out
				style.minWidth = style.maxWidth = style.width = ret;
				ret = computed.width;

				// Revert the changed values
				style.width = width;
				style.minWidth = minWidth;
				style.maxWidth = maxWidth;
			}
		}

		return ret;
	};
} else if ( document.documentElement.currentStyle ) {
	getStyles = function( elem ) {
		return elem.currentStyle;
	};

	curCSS = function( elem, name, _computed ) {
		var left, rs, rsLeft,
			computed = _computed || getStyles( elem ),
			ret = computed ? computed[ name ] : undefined,
			style = elem.style;

		// Avoid setting ret to empty string here
		// so we don't default to auto
		if ( ret == null && style && style[ name ] ) {
			ret = style[ name ];
		}

		// From the awesome hack by Dean Edwards
		// http://erik.eae.net/archives/2007/07/27/18.54.15/#comment-102291

		// If we're not dealing with a regular pixel number
		// but a number that has a weird ending, we need to convert it to pixels
		// but not position css attributes, as those are proportional to the parent element instead
		// and we can't measure the parent instead because it might trigger a "stacking dolls" problem
		if ( rnumnonpx.test( ret ) && !rposition.test( name ) ) {

			// Remember the original values
			left = style.left;
			rs = elem.runtimeStyle;
			rsLeft = rs && rs.left;

			// Put in the new values to get a computed value out
			if ( rsLeft ) {
				rs.left = elem.currentStyle.left;
			}
			style.left = name === "fontSize" ? "1em" : ret;
			ret = style.pixelLeft + "px";

			// Revert the changed values
			style.left = left;
			if ( rsLeft ) {
				rs.left = rsLeft;
			}
		}

		return ret === "" ? "auto" : ret;
	};
}

function setPositiveNumber( elem, value, subtract ) {
	var matches = rnumsplit.exec( value );
	return matches ?
		// Guard against undefined "subtract", e.g., when used as in cssHooks
		Math.max( 0, matches[ 1 ] - ( subtract || 0 ) ) + ( matches[ 2 ] || "px" ) :
		value;
}

function augmentWidthOrHeight( elem, name, extra, isBorderBox, styles ) {
	var i = extra === ( isBorderBox ? "border" : "content" ) ?
		// If we already have the right measurement, avoid augmentation
		4 :
		// Otherwise initialize for horizontal or vertical properties
		name === "width" ? 1 : 0,

		val = 0;

	for ( ; i < 4; i += 2 ) {
		// both box models exclude margin, so add it if we want it
		if ( extra === "margin" ) {
			val += jQuery.css( elem, extra + cssExpand[ i ], true, styles );
		}

		if ( isBorderBox ) {
			// border-box includes padding, so remove it if we want content
			if ( extra === "content" ) {
				val -= jQuery.css( elem, "padding" + cssExpand[ i ], true, styles );
			}

			// at this point, extra isn't border nor margin, so remove border
			if ( extra !== "margin" ) {
				val -= jQuery.css( elem, "border" + cssExpand[ i ] + "Width", true, styles );
			}
		} else {
			// at this point, extra isn't content, so add padding
			val += jQuery.css( elem, "padding" + cssExpand[ i ], true, styles );

			// at this point, extra isn't content nor padding, so add border
			if ( extra !== "padding" ) {
				val += jQuery.css( elem, "border" + cssExpand[ i ] + "Width", true, styles );
			}
		}
	}

	return val;
}

function getWidthOrHeight( elem, name, extra ) {

	// Start with offset property, which is equivalent to the border-box value
	var valueIsBorderBox = true,
		val = name === "width" ? elem.offsetWidth : elem.offsetHeight,
		styles = getStyles( elem ),
		isBorderBox = jQuery.support.boxSizing && jQuery.css( elem, "boxSizing", false, styles ) === "border-box";

	// some non-html elements return undefined for offsetWidth, so check for null/undefined
	// svg - https://bugzilla.mozilla.org/show_bug.cgi?id=649285
	// MathML - https://bugzilla.mozilla.org/show_bug.cgi?id=491668
	if ( val <= 0 || val == null ) {
		// Fall back to computed then uncomputed css if necessary
		val = curCSS( elem, name, styles );
		if ( val < 0 || val == null ) {
			val = elem.style[ name ];
		}

		// Computed unit is not pixels. Stop here and return.
		if ( rnumnonpx.test(val) ) {
			return val;
		}

		// we need the check for style in case a browser which returns unreliable values
		// for getComputedStyle silently falls back to the reliable elem.style
		valueIsBorderBox = isBorderBox && ( jQuery.support.boxSizingReliable || val === elem.style[ name ] );

		// Normalize "", auto, and prepare for extra
		val = parseFloat( val ) || 0;
	}

	// use the active box-sizing model to add/subtract irrelevant styles
	return ( val +
		augmentWidthOrHeight(
			elem,
			name,
			extra || ( isBorderBox ? "border" : "content" ),
			valueIsBorderBox,
			styles
		)
	) + "px";
}

// Try to determine the default display value of an element
function css_defaultDisplay( nodeName ) {
	var doc = document,
		display = elemdisplay[ nodeName ];

	if ( !display ) {
		display = actualDisplay( nodeName, doc );

		// If the simple way fails, read from inside an iframe
		if ( display === "none" || !display ) {
			// Use the already-created iframe if possible
			iframe = ( iframe ||
				jQuery("<iframe frameborder='0' width='0' height='0'/>")
				.css( "cssText", "display:block !important" )
			).appendTo( doc.documentElement );

			// Always write a new HTML skeleton so Webkit and Firefox don't choke on reuse
			doc = ( iframe[0].contentWindow || iframe[0].contentDocument ).document;
			doc.write("<!doctype html><html><body>");
			doc.close();

			display = actualDisplay( nodeName, doc );
			iframe.detach();
		}

		// Store the correct default display
		elemdisplay[ nodeName ] = display;
	}

	return display;
}

// Called ONLY from within css_defaultDisplay
function actualDisplay( name, doc ) {
	var elem = jQuery( doc.createElement( name ) ).appendTo( doc.body ),
		display = jQuery.css( elem[0], "display" );
	elem.remove();
	return display;
}

jQuery.each([ "height", "width" ], function( i, name ) {
	jQuery.cssHooks[ name ] = {
		get: function( elem, computed, extra ) {
			if ( computed ) {
				// certain elements can have dimension info if we invisibly show them
				// however, it must have a current display style that would benefit from this
				return elem.offsetWidth === 0 && rdisplayswap.test( jQuery.css( elem, "display" ) ) ?
					jQuery.swap( elem, cssShow, function() {
						return getWidthOrHeight( elem, name, extra );
					}) :
					getWidthOrHeight( elem, name, extra );
			}
		},

		set: function( elem, value, extra ) {
			var styles = extra && getStyles( elem );
			return setPositiveNumber( elem, value, extra ?
				augmentWidthOrHeight(
					elem,
					name,
					extra,
					jQuery.support.boxSizing && jQuery.css( elem, "boxSizing", false, styles ) === "border-box",
					styles
				) : 0
			);
		}
	};
});

if ( !jQuery.support.opacity ) {
	jQuery.cssHooks.opacity = {
		get: function( elem, computed ) {
			// IE uses filters for opacity
			return ropacity.test( (computed && elem.currentStyle ? elem.currentStyle.filter : elem.style.filter) || "" ) ?
				( 0.01 * parseFloat( RegExp.$1 ) ) + "" :
				computed ? "1" : "";
		},

		set: function( elem, value ) {
			var style = elem.style,
				currentStyle = elem.currentStyle,
				opacity = jQuery.isNumeric( value ) ? "alpha(opacity=" + value * 100 + ")" : "",
				filter = currentStyle && currentStyle.filter || style.filter || "";

			// IE has trouble with opacity if it does not have layout
			// Force it by setting the zoom level
			style.zoom = 1;

			// if setting opacity to 1, and no other filters exist - attempt to remove filter attribute #6652
			// if value === "", then remove inline opacity #12685
			if ( ( value >= 1 || value === "" ) &&
					jQuery.trim( filter.replace( ralpha, "" ) ) === "" &&
					style.removeAttribute ) {

				// Setting style.filter to null, "" & " " still leave "filter:" in the cssText
				// if "filter:" is present at all, clearType is disabled, we want to avoid this
				// style.removeAttribute is IE Only, but so apparently is this code path...
				style.removeAttribute( "filter" );

				// if there is no filter style applied in a css rule or unset inline opacity, we are done
				if ( value === "" || currentStyle && !currentStyle.filter ) {
					return;
				}
			}

			// otherwise, set new filter values
			style.filter = ralpha.test( filter ) ?
				filter.replace( ralpha, opacity ) :
				filter + " " + opacity;
		}
	};
}

// These hooks cannot be added until DOM ready because the support test
// for it is not run until after DOM ready
jQuery(function() {
	if ( !jQuery.support.reliableMarginRight ) {
		jQuery.cssHooks.marginRight = {
			get: function( elem, computed ) {
				if ( computed ) {
					// WebKit Bug 13343 - getComputedStyle returns wrong value for margin-right
					// Work around by temporarily setting element display to inline-block
					return jQuery.swap( elem, { "display": "inline-block" },
						curCSS, [ elem, "marginRight" ] );
				}
			}
		};
	}

	// Webkit bug: https://bugs.webkit.org/show_bug.cgi?id=29084
	// getComputedStyle returns percent when specified for top/left/bottom/right
	// rather than make the css module depend on the offset module, we just check for it here
	if ( !jQuery.support.pixelPosition && jQuery.fn.position ) {
		jQuery.each( [ "top", "left" ], function( i, prop ) {
			jQuery.cssHooks[ prop ] = {
				get: function( elem, computed ) {
					if ( computed ) {
						computed = curCSS( elem, prop );
						// if curCSS returns percentage, fallback to offset
						return rnumnonpx.test( computed ) ?
							jQuery( elem ).position()[ prop ] + "px" :
							computed;
					}
				}
			};
		});
	}

});

if ( jQuery.expr && jQuery.expr.filters ) {
	jQuery.expr.filters.hidden = function( elem ) {
		// Support: Opera <= 12.12
		// Opera reports offsetWidths and offsetHeights less than zero on some elements
		return elem.offsetWidth <= 0 && elem.offsetHeight <= 0 ||
			(!jQuery.support.reliableHiddenOffsets && ((elem.style && elem.style.display) || jQuery.css( elem, "display" )) === "none");
	};

	jQuery.expr.filters.visible = function( elem ) {
		return !jQuery.expr.filters.hidden( elem );
	};
}

// These hooks are used by animate to expand properties
jQuery.each({
	margin: "",
	padding: "",
	border: "Width"
}, function( prefix, suffix ) {
	jQuery.cssHooks[ prefix + suffix ] = {
		expand: function( value ) {
			var i = 0,
				expanded = {},

				// assumes a single number if not a string
				parts = typeof value === "string" ? value.split(" ") : [ value ];

			for ( ; i < 4; i++ ) {
				expanded[ prefix + cssExpand[ i ] + suffix ] =
					parts[ i ] || parts[ i - 2 ] || parts[ 0 ];
			}

			return expanded;
		}
	};

	if ( !rmargin.test( prefix ) ) {
		jQuery.cssHooks[ prefix + suffix ].set = setPositiveNumber;
	}
});
var r20 = /%20/g,
	rbracket = /\[\]$/,
	rCRLF = /\r?\n/g,
	rsubmitterTypes = /^(?:submit|button|image|reset|file)$/i,
	rsubmittable = /^(?:input|select|textarea|keygen)/i;

jQuery.fn.extend({
	serialize: function() {
		return jQuery.param( this.serializeArray() );
	},
	serializeArray: function() {
		return this.map(function(){
			// Can add propHook for "elements" to filter or add form elements
			var elements = jQuery.prop( this, "elements" );
			return elements ? jQuery.makeArray( elements ) : this;
		})
		.filter(function(){
			var type = this.type;
			// Use .is(":disabled") so that fieldset[disabled] works
			return this.name && !jQuery( this ).is( ":disabled" ) &&
				rsubmittable.test( this.nodeName ) && !rsubmitterTypes.test( type ) &&
				( this.checked || !manipulation_rcheckableType.test( type ) );
		})
		.map(function( i, elem ){
			var val = jQuery( this ).val();

			return val == null ?
				null :
				jQuery.isArray( val ) ?
					jQuery.map( val, function( val ){
						return { name: elem.name, value: val.replace( rCRLF, "\r\n" ) };
					}) :
					{ name: elem.name, value: val.replace( rCRLF, "\r\n" ) };
		}).get();
	}
});

//Serialize an array of form elements or a set of
//key/values into a query string
jQuery.param = function( a, traditional ) {
	var prefix,
		s = [],
		add = function( key, value ) {
			// If value is a function, invoke it and return its value
			value = jQuery.isFunction( value ) ? value() : ( value == null ? "" : value );
			s[ s.length ] = encodeURIComponent( key ) + "=" + encodeURIComponent( value );
		};

	// Set traditional to true for jQuery <= 1.3.2 behavior.
	if ( traditional === undefined ) {
		traditional = jQuery.ajaxSettings && jQuery.ajaxSettings.traditional;
	}

	// If an array was passed in, assume that it is an array of form elements.
	if ( jQuery.isArray( a ) || ( a.jquery && !jQuery.isPlainObject( a ) ) ) {
		// Serialize the form elements
		jQuery.each( a, function() {
			add( this.name, this.value );
		});

	} else {
		// If traditional, encode the "old" way (the way 1.3.2 or older
		// did it), otherwise encode params recursively.
		for ( prefix in a ) {
			buildParams( prefix, a[ prefix ], traditional, add );
		}
	}

	// Return the resulting serialization
	return s.join( "&" ).replace( r20, "+" );
};

function buildParams( prefix, obj, traditional, add ) {
	var name;

	if ( jQuery.isArray( obj ) ) {
		// Serialize array item.
		jQuery.each( obj, function( i, v ) {
			if ( traditional || rbracket.test( prefix ) ) {
				// Treat each array item as a scalar.
				add( prefix, v );

			} else {
				// Item is non-scalar (array or object), encode its numeric index.
				buildParams( prefix + "[" + ( typeof v === "object" ? i : "" ) + "]", v, traditional, add );
			}
		});

	} else if ( !traditional && jQuery.type( obj ) === "object" ) {
		// Serialize object item.
		for ( name in obj ) {
			buildParams( prefix + "[" + name + "]", obj[ name ], traditional, add );
		}

	} else {
		// Serialize scalar item.
		add( prefix, obj );
	}
}
jQuery.each( ("blur focus focusin focusout load resize scroll unload click dblclick " +
	"mousedown mouseup mousemove mouseover mouseout mouseenter mouseleave " +
	"change select submit keydown keypress keyup error contextmenu").split(" "), function( i, name ) {

	// Handle event binding
	jQuery.fn[ name ] = function( data, fn ) {
		return arguments.length > 0 ?
			this.on( name, null, data, fn ) :
			this.trigger( name );
	};
});

jQuery.fn.extend({
	hover: function( fnOver, fnOut ) {
		return this.mouseenter( fnOver ).mouseleave( fnOut || fnOver );
	},

	bind: function( types, data, fn ) {
		return this.on( types, null, data, fn );
	},
	unbind: function( types, fn ) {
		return this.off( types, null, fn );
	},

	delegate: function( selector, types, data, fn ) {
		return this.on( types, selector, data, fn );
	},
	undelegate: function( selector, types, fn ) {
		// ( namespace ) or ( selector, types [, fn] )
		return arguments.length === 1 ? this.off( selector, "**" ) : this.off( types, selector || "**", fn );
	}
});
var
	// Document location
	ajaxLocParts,
	ajaxLocation,
	ajax_nonce = jQuery.now(),

	ajax_rquery = /\?/,
	rhash = /#.*$/,
	rts = /([?&])_=[^&]*/,
	rheaders = /^(.*?):[ \t]*([^\r\n]*)\r?$/mg, // IE leaves an \r character at EOL
	// #7653, #8125, #8152: local protocol detection
	rlocalProtocol = /^(?:about|app|app-storage|.+-extension|file|res|widget):$/,
	rnoContent = /^(?:GET|HEAD)$/,
	rprotocol = /^\/\//,
	rurl = /^([\w.+-]+:)(?:\/\/([^\/?#:]*)(?::(\d+)|)|)/,

	// Keep a copy of the old load method
	_load = jQuery.fn.load,

	/* Prefilters
	 * 1) They are useful to introduce custom dataTypes (see ajax/jsonp.js for an example)
	 * 2) These are called:
	 *    - BEFORE asking for a transport
	 *    - AFTER param serialization (s.data is a string if s.processData is true)
	 * 3) key is the dataType
	 * 4) the catchall symbol "*" can be used
	 * 5) execution will start with transport dataType and THEN continue down to "*" if needed
	 */
	prefilters = {},

	/* Transports bindings
	 * 1) key is the dataType
	 * 2) the catchall symbol "*" can be used
	 * 3) selection will start with transport dataType and THEN go to "*" if needed
	 */
	transports = {},

	// Avoid comment-prolog char sequence (#10098); must appease lint and evade compression
	allTypes = "*/".concat("*");

// #8138, IE may throw an exception when accessing
// a field from window.location if document.domain has been set
try {
	ajaxLocation = location.href;
} catch( e ) {
	// Use the href attribute of an A element
	// since IE will modify it given document.location
	ajaxLocation = document.createElement( "a" );
	ajaxLocation.href = "";
	ajaxLocation = ajaxLocation.href;
}

// Segment location into parts
ajaxLocParts = rurl.exec( ajaxLocation.toLowerCase() ) || [];

// Base "constructor" for jQuery.ajaxPrefilter and jQuery.ajaxTransport
function addToPrefiltersOrTransports( structure ) {

	// dataTypeExpression is optional and defaults to "*"
	return function( dataTypeExpression, func ) {

		if ( typeof dataTypeExpression !== "string" ) {
			func = dataTypeExpression;
			dataTypeExpression = "*";
		}

		var dataType,
			i = 0,
			dataTypes = dataTypeExpression.toLowerCase().match( core_rnotwhite ) || [];

		if ( jQuery.isFunction( func ) ) {
			// For each dataType in the dataTypeExpression
			while ( (dataType = dataTypes[i++]) ) {
				// Prepend if requested
				if ( dataType[0] === "+" ) {
					dataType = dataType.slice( 1 ) || "*";
					(structure[ dataType ] = structure[ dataType ] || []).unshift( func );

				// Otherwise append
				} else {
					(structure[ dataType ] = structure[ dataType ] || []).push( func );
				}
			}
		}
	};
}

// Base inspection function for prefilters and transports
function inspectPrefiltersOrTransports( structure, options, originalOptions, jqXHR ) {

	var inspected = {},
		seekingTransport = ( structure === transports );

	function inspect( dataType ) {
		var selected;
		inspected[ dataType ] = true;
		jQuery.each( structure[ dataType ] || [], function( _, prefilterOrFactory ) {
			var dataTypeOrTransport = prefilterOrFactory( options, originalOptions, jqXHR );
			if( typeof dataTypeOrTransport === "string" && !seekingTransport && !inspected[ dataTypeOrTransport ] ) {
				options.dataTypes.unshift( dataTypeOrTransport );
				inspect( dataTypeOrTransport );
				return false;
			} else if ( seekingTransport ) {
				return !( selected = dataTypeOrTransport );
			}
		});
		return selected;
	}

	return inspect( options.dataTypes[ 0 ] ) || !inspected[ "*" ] && inspect( "*" );
}

// A special extend for ajax options
// that takes "flat" options (not to be deep extended)
// Fixes #9887
function ajaxExtend( target, src ) {
	var deep, key,
		flatOptions = jQuery.ajaxSettings.flatOptions || {};

	for ( key in src ) {
		if ( src[ key ] !== undefined ) {
			( flatOptions[ key ] ? target : ( deep || (deep = {}) ) )[ key ] = src[ key ];
		}
	}
	if ( deep ) {
		jQuery.extend( true, target, deep );
	}

	return target;
}

jQuery.fn.load = function( url, params, callback ) {
	if ( typeof url !== "string" && _load ) {
		return _load.apply( this, arguments );
	}

	var selector, response, type,
		self = this,
		off = url.indexOf(" ");

	if ( off >= 0 ) {
		selector = url.slice( off, url.length );
		url = url.slice( 0, off );
	}

	// If it's a function
	if ( jQuery.isFunction( params ) ) {

		// We assume that it's the callback
		callback = params;
		params = undefined;

	// Otherwise, build a param string
	} else if ( params && typeof params === "object" ) {
		type = "POST";
	}

	// If we have elements to modify, make the request
	if ( self.length > 0 ) {
		jQuery.ajax({
			url: url,

			// if "type" variable is undefined, then "GET" method will be used
			type: type,
			dataType: "html",
			data: params
		}).done(function( responseText ) {

			// Save response for use in complete callback
			response = arguments;

			self.html( selector ?

				// If a selector was specified, locate the right elements in a dummy div
				// Exclude scripts to avoid IE 'Permission Denied' errors
				jQuery("<div>").append( jQuery.parseHTML( responseText ) ).find( selector ) :

				// Otherwise use the full result
				responseText );

		}).complete( callback && function( jqXHR, status ) {
			self.each( callback, response || [ jqXHR.responseText, status, jqXHR ] );
		});
	}

	return this;
};

// Attach a bunch of functions for handling common AJAX events
jQuery.each( [ "ajaxStart", "ajaxStop", "ajaxComplete", "ajaxError", "ajaxSuccess", "ajaxSend" ], function( i, type ){
	jQuery.fn[ type ] = function( fn ){
		return this.on( type, fn );
	};
});

jQuery.extend({

	// Counter for holding the number of active queries
	active: 0,

	// Last-Modified header cache for next request
	lastModified: {},
	etag: {},

	ajaxSettings: {
		url: ajaxLocation,
		type: "GET",
		isLocal: rlocalProtocol.test( ajaxLocParts[ 1 ] ),
		global: true,
		processData: true,
		async: true,
		contentType: "application/x-www-form-urlencoded; charset=UTF-8",
		/*
		timeout: 0,
		data: null,
		dataType: null,
		username: null,
		password: null,
		cache: null,
		throws: false,
		traditional: false,
		headers: {},
		*/

		accepts: {
			"*": allTypes,
			text: "text/plain",
			html: "text/html",
			xml: "application/xml, text/xml",
			json: "application/json, text/javascript"
		},

		contents: {
			xml: /xml/,
			html: /html/,
			json: /json/
		},

		responseFields: {
			xml: "responseXML",
			text: "responseText",
			json: "responseJSON"
		},

		// Data converters
		// Keys separate source (or catchall "*") and destination types with a single space
		converters: {

			// Convert anything to text
			"* text": String,

			// Text to html (true = no transformation)
			"text html": true,

			// Evaluate text as a json expression
			"text json": jQuery.parseJSON,

			// Parse text as xml
			"text xml": jQuery.parseXML
		},

		// For options that shouldn't be deep extended:
		// you can add your own custom options here if
		// and when you create one that shouldn't be
		// deep extended (see ajaxExtend)
		flatOptions: {
			url: true,
			context: true
		}
	},

	// Creates a full fledged settings object into target
	// with both ajaxSettings and settings fields.
	// If target is omitted, writes into ajaxSettings.
	ajaxSetup: function( target, settings ) {
		return settings ?

			// Building a settings object
			ajaxExtend( ajaxExtend( target, jQuery.ajaxSettings ), settings ) :

			// Extending ajaxSettings
			ajaxExtend( jQuery.ajaxSettings, target );
	},

	ajaxPrefilter: addToPrefiltersOrTransports( prefilters ),
	ajaxTransport: addToPrefiltersOrTransports( transports ),

	// Main method
	ajax: function( url, options ) {

		// If url is an object, simulate pre-1.5 signature
		if ( typeof url === "object" ) {
			options = url;
			url = undefined;
		}

		// Force options to be an object
		options = options || {};

		var // Cross-domain detection vars
			parts,
			// Loop variable
			i,
			// URL without anti-cache param
			cacheURL,
			// Response headers as string
			responseHeadersString,
			// timeout handle
			timeoutTimer,

			// To know if global events are to be dispatched
			fireGlobals,

			transport,
			// Response headers
			responseHeaders,
			// Create the final options object
			s = jQuery.ajaxSetup( {}, options ),
			// Callbacks context
			callbackContext = s.context || s,
			// Context for global events is callbackContext if it is a DOM node or jQuery collection
			globalEventContext = s.context && ( callbackContext.nodeType || callbackContext.jquery ) ?
				jQuery( callbackContext ) :
				jQuery.event,
			// Deferreds
			deferred = jQuery.Deferred(),
			completeDeferred = jQuery.Callbacks("once memory"),
			// Status-dependent callbacks
			statusCode = s.statusCode || {},
			// Headers (they are sent all at once)
			requestHeaders = {},
			requestHeadersNames = {},
			// The jqXHR state
			state = 0,
			// Default abort message
			strAbort = "canceled",
			// Fake xhr
			jqXHR = {
				readyState: 0,

				// Builds headers hashtable if needed
				getResponseHeader: function( key ) {
					var match;
					if ( state === 2 ) {
						if ( !responseHeaders ) {
							responseHeaders = {};
							while ( (match = rheaders.exec( responseHeadersString )) ) {
								responseHeaders[ match[1].toLowerCase() ] = match[ 2 ];
							}
						}
						match = responseHeaders[ key.toLowerCase() ];
					}
					return match == null ? null : match;
				},

				// Raw string
				getAllResponseHeaders: function() {
					return state === 2 ? responseHeadersString : null;
				},

				// Caches the header
				setRequestHeader: function( name, value ) {
					var lname = name.toLowerCase();
					if ( !state ) {
						name = requestHeadersNames[ lname ] = requestHeadersNames[ lname ] || name;
						requestHeaders[ name ] = value;
					}
					return this;
				},

				// Overrides response content-type header
				overrideMimeType: function( type ) {
					if ( !state ) {
						s.mimeType = type;
					}
					return this;
				},

				// Status-dependent callbacks
				statusCode: function( map ) {
					var code;
					if ( map ) {
						if ( state < 2 ) {
							for ( code in map ) {
								// Lazy-add the new callback in a way that preserves old ones
								statusCode[ code ] = [ statusCode[ code ], map[ code ] ];
							}
						} else {
							// Execute the appropriate callbacks
							jqXHR.always( map[ jqXHR.status ] );
						}
					}
					return this;
				},

				// Cancel the request
				abort: function( statusText ) {
					var finalText = statusText || strAbort;
					if ( transport ) {
						transport.abort( finalText );
					}
					done( 0, finalText );
					return this;
				}
			};

		// Attach deferreds
		deferred.promise( jqXHR ).complete = completeDeferred.add;
		jqXHR.success = jqXHR.done;
		jqXHR.error = jqXHR.fail;

		// Remove hash character (#7531: and string promotion)
		// Add protocol if not provided (#5866: IE7 issue with protocol-less urls)
		// Handle falsy url in the settings object (#10093: consistency with old signature)
		// We also use the url parameter if available
		s.url = ( ( url || s.url || ajaxLocation ) + "" ).replace( rhash, "" ).replace( rprotocol, ajaxLocParts[ 1 ] + "//" );

		// Alias method option to type as per ticket #12004
		s.type = options.method || options.type || s.method || s.type;

		// Extract dataTypes list
		s.dataTypes = jQuery.trim( s.dataType || "*" ).toLowerCase().match( core_rnotwhite ) || [""];

		// A cross-domain request is in order when we have a protocol:host:port mismatch
		if ( s.crossDomain == null ) {
			parts = rurl.exec( s.url.toLowerCase() );
			s.crossDomain = !!( parts &&
				( parts[ 1 ] !== ajaxLocParts[ 1 ] || parts[ 2 ] !== ajaxLocParts[ 2 ] ||
					( parts[ 3 ] || ( parts[ 1 ] === "http:" ? "80" : "443" ) ) !==
						( ajaxLocParts[ 3 ] || ( ajaxLocParts[ 1 ] === "http:" ? "80" : "443" ) ) )
			);
		}

		// Convert data if not already a string
		if ( s.data && s.processData && typeof s.data !== "string" ) {
			s.data = jQuery.param( s.data, s.traditional );
		}

		// Apply prefilters
		inspectPrefiltersOrTransports( prefilters, s, options, jqXHR );

		// If request was aborted inside a prefilter, stop there
		if ( state === 2 ) {
			return jqXHR;
		}

		// We can fire global events as of now if asked to
		fireGlobals = s.global;

		// Watch for a new set of requests
		if ( fireGlobals && jQuery.active++ === 0 ) {
			jQuery.event.trigger("ajaxStart");
		}

		// Uppercase the type
		s.type = s.type.toUpperCase();

		// Determine if request has content
		s.hasContent = !rnoContent.test( s.type );

		// Save the URL in case we're toying with the If-Modified-Since
		// and/or If-None-Match header later on
		cacheURL = s.url;

		// More options handling for requests with no content
		if ( !s.hasContent ) {

			// If data is available, append data to url
			if ( s.data ) {
				cacheURL = ( s.url += ( ajax_rquery.test( cacheURL ) ? "&" : "?" ) + s.data );
				// #9682: remove data so that it's not used in an eventual retry
				delete s.data;
			}

			// Add anti-cache in url if needed
			if ( s.cache === false ) {
				s.url = rts.test( cacheURL ) ?

					// If there is already a '_' parameter, set its value
					cacheURL.replace( rts, "$1_=" + ajax_nonce++ ) :

					// Otherwise add one to the end
					cacheURL + ( ajax_rquery.test( cacheURL ) ? "&" : "?" ) + "_=" + ajax_nonce++;
			}
		}

		// Set the If-Modified-Since and/or If-None-Match header, if in ifModified mode.
		if ( s.ifModified ) {
			if ( jQuery.lastModified[ cacheURL ] ) {
				jqXHR.setRequestHeader( "If-Modified-Since", jQuery.lastModified[ cacheURL ] );
			}
			if ( jQuery.etag[ cacheURL ] ) {
				jqXHR.setRequestHeader( "If-None-Match", jQuery.etag[ cacheURL ] );
			}
		}

		// Set the correct header, if data is being sent
		if ( s.data && s.hasContent && s.contentType !== false || options.contentType ) {
			jqXHR.setRequestHeader( "Content-Type", s.contentType );
		}

		// Set the Accepts header for the server, depending on the dataType
		jqXHR.setRequestHeader(
			"Accept",
			s.dataTypes[ 0 ] && s.accepts[ s.dataTypes[0] ] ?
				s.accepts[ s.dataTypes[0] ] + ( s.dataTypes[ 0 ] !== "*" ? ", " + allTypes + "; q=0.01" : "" ) :
				s.accepts[ "*" ]
		);

		// Check for headers option
		for ( i in s.headers ) {
			jqXHR.setRequestHeader( i, s.headers[ i ] );
		}

		// Allow custom headers/mimetypes and early abort
		if ( s.beforeSend && ( s.beforeSend.call( callbackContext, jqXHR, s ) === false || state === 2 ) ) {
			// Abort if not done already and return
			return jqXHR.abort();
		}

		// aborting is no longer a cancellation
		strAbort = "abort";

		// Install callbacks on deferreds
		for ( i in { success: 1, error: 1, complete: 1 } ) {
			jqXHR[ i ]( s[ i ] );
		}

		// Get transport
		transport = inspectPrefiltersOrTransports( transports, s, options, jqXHR );

		// If no transport, we auto-abort
		if ( !transport ) {
			done( -1, "No Transport" );
		} else {
			jqXHR.readyState = 1;

			// Send global event
			if ( fireGlobals ) {
				globalEventContext.trigger( "ajaxSend", [ jqXHR, s ] );
			}
			// Timeout
			if ( s.async && s.timeout > 0 ) {
				timeoutTimer = setTimeout(function() {
					jqXHR.abort("timeout");
				}, s.timeout );
			}

			try {
				state = 1;
				transport.send( requestHeaders, done );
			} catch ( e ) {
				// Propagate exception as error if not done
				if ( state < 2 ) {
					done( -1, e );
				// Simply rethrow otherwise
				} else {
					throw e;
				}
			}
		}

		// Callback for when everything is done
		function done( status, nativeStatusText, responses, headers ) {
			var isSuccess, success, error, response, modified,
				statusText = nativeStatusText;

			// Called once
			if ( state === 2 ) {
				return;
			}

			// State is "done" now
			state = 2;

			// Clear timeout if it exists
			if ( timeoutTimer ) {
				clearTimeout( timeoutTimer );
			}

			// Dereference transport for early garbage collection
			// (no matter how long the jqXHR object will be used)
			transport = undefined;

			// Cache response headers
			responseHeadersString = headers || "";

			// Set readyState
			jqXHR.readyState = status > 0 ? 4 : 0;

			// Determine if successful
			isSuccess = status >= 200 && status < 300 || status === 304;

			// Get response data
			if ( responses ) {
				response = ajaxHandleResponses( s, jqXHR, responses );
			}

			// Convert no matter what (that way responseXXX fields are always set)
			response = ajaxConvert( s, response, jqXHR, isSuccess );

			// If successful, handle type chaining
			if ( isSuccess ) {

				// Set the If-Modified-Since and/or If-None-Match header, if in ifModified mode.
				if ( s.ifModified ) {
					modified = jqXHR.getResponseHeader("Last-Modified");
					if ( modified ) {
						jQuery.lastModified[ cacheURL ] = modified;
					}
					modified = jqXHR.getResponseHeader("etag");
					if ( modified ) {
						jQuery.etag[ cacheURL ] = modified;
					}
				}

				// if no content
				if ( status === 204 || s.type === "HEAD" ) {
					statusText = "nocontent";

				// if not modified
				} else if ( status === 304 ) {
					statusText = "notmodified";

				// If we have data, let's convert it
				} else {
					statusText = response.state;
					success = response.data;
					error = response.error;
					isSuccess = !error;
				}
			} else {
				// We extract error from statusText
				// then normalize statusText and status for non-aborts
				error = statusText;
				if ( status || !statusText ) {
					statusText = "error";
					if ( status < 0 ) {
						status = 0;
					}
				}
			}

			// Set data for the fake xhr object
			jqXHR.status = status;
			jqXHR.statusText = ( nativeStatusText || statusText ) + "";

			// Success/Error
			if ( isSuccess ) {
				deferred.resolveWith( callbackContext, [ success, statusText, jqXHR ] );
			} else {
				deferred.rejectWith( callbackContext, [ jqXHR, statusText, error ] );
			}

			// Status-dependent callbacks
			jqXHR.statusCode( statusCode );
			statusCode = undefined;

			if ( fireGlobals ) {
				globalEventContext.trigger( isSuccess ? "ajaxSuccess" : "ajaxError",
					[ jqXHR, s, isSuccess ? success : error ] );
			}

			// Complete
			completeDeferred.fireWith( callbackContext, [ jqXHR, statusText ] );

			if ( fireGlobals ) {
				globalEventContext.trigger( "ajaxComplete", [ jqXHR, s ] );
				// Handle the global AJAX counter
				if ( !( --jQuery.active ) ) {
					jQuery.event.trigger("ajaxStop");
				}
			}
		}

		return jqXHR;
	},

	getJSON: function( url, data, callback ) {
		return jQuery.get( url, data, callback, "json" );
	},

	getScript: function( url, callback ) {
		return jQuery.get( url, undefined, callback, "script" );
	}
});

jQuery.each( [ "get", "post" ], function( i, method ) {
	jQuery[ method ] = function( url, data, callback, type ) {
		// shift arguments if data argument was omitted
		if ( jQuery.isFunction( data ) ) {
			type = type || callback;
			callback = data;
			data = undefined;
		}

		return jQuery.ajax({
			url: url,
			type: method,
			dataType: type,
			data: data,
			success: callback
		});
	};
});

/* Handles responses to an ajax request:
 * - finds the right dataType (mediates between content-type and expected dataType)
 * - returns the corresponding response
 */
function ajaxHandleResponses( s, jqXHR, responses ) {
	var firstDataType, ct, finalDataType, type,
		contents = s.contents,
		dataTypes = s.dataTypes;

	// Remove auto dataType and get content-type in the process
	while( dataTypes[ 0 ] === "*" ) {
		dataTypes.shift();
		if ( ct === undefined ) {
			ct = s.mimeType || jqXHR.getResponseHeader("Content-Type");
		}
	}

	// Check if we're dealing with a known content-type
	if ( ct ) {
		for ( type in contents ) {
			if ( contents[ type ] && contents[ type ].test( ct ) ) {
				dataTypes.unshift( type );
				break;
			}
		}
	}

	// Check to see if we have a response for the expected dataType
	if ( dataTypes[ 0 ] in responses ) {
		finalDataType = dataTypes[ 0 ];
	} else {
		// Try convertible dataTypes
		for ( type in responses ) {
			if ( !dataTypes[ 0 ] || s.converters[ type + " " + dataTypes[0] ] ) {
				finalDataType = type;
				break;
			}
			if ( !firstDataType ) {
				firstDataType = type;
			}
		}
		// Or just use first one
		finalDataType = finalDataType || firstDataType;
	}

	// If we found a dataType
	// We add the dataType to the list if needed
	// and return the corresponding response
	if ( finalDataType ) {
		if ( finalDataType !== dataTypes[ 0 ] ) {
			dataTypes.unshift( finalDataType );
		}
		return responses[ finalDataType ];
	}
}

/* Chain conversions given the request and the original response
 * Also sets the responseXXX fields on the jqXHR instance
 */
function ajaxConvert( s, response, jqXHR, isSuccess ) {
	var conv2, current, conv, tmp, prev,
		converters = {},
		// Work with a copy of dataTypes in case we need to modify it for conversion
		dataTypes = s.dataTypes.slice();

	// Create converters map with lowercased keys
	if ( dataTypes[ 1 ] ) {
		for ( conv in s.converters ) {
			converters[ conv.toLowerCase() ] = s.converters[ conv ];
		}
	}

	current = dataTypes.shift();

	// Convert to each sequential dataType
	while ( current ) {

		if ( s.responseFields[ current ] ) {
			jqXHR[ s.responseFields[ current ] ] = response;
		}

		// Apply the dataFilter if provided
		if ( !prev && isSuccess && s.dataFilter ) {
			response = s.dataFilter( response, s.dataType );
		}

		prev = current;
		current = dataTypes.shift();

		if ( current ) {

			// There's only work to do if current dataType is non-auto
			if ( current === "*" ) {

				current = prev;

			// Convert response if prev dataType is non-auto and differs from current
			} else if ( prev !== "*" && prev !== current ) {

				// Seek a direct converter
				conv = converters[ prev + " " + current ] || converters[ "* " + current ];

				// If none found, seek a pair
				if ( !conv ) {
					for ( conv2 in converters ) {

						// If conv2 outputs current
						tmp = conv2.split( " " );
						if ( tmp[ 1 ] === current ) {

							// If prev can be converted to accepted input
							conv = converters[ prev + " " + tmp[ 0 ] ] ||
								converters[ "* " + tmp[ 0 ] ];
							if ( conv ) {
								// Condense equivalence converters
								if ( conv === true ) {
									conv = converters[ conv2 ];

								// Otherwise, insert the intermediate dataType
								} else if ( converters[ conv2 ] !== true ) {
									current = tmp[ 0 ];
									dataTypes.unshift( tmp[ 1 ] );
								}
								break;
							}
						}
					}
				}

				// Apply converter (if not an equivalence)
				if ( conv !== true ) {

					// Unless errors are allowed to bubble, catch and return them
					if ( conv && s[ "throws" ] ) {
						response = conv( response );
					} else {
						try {
							response = conv( response );
						} catch ( e ) {
							return { state: "parsererror", error: conv ? e : "No conversion from " + prev + " to " + current };
						}
					}
				}
			}
		}
	}

	return { state: "success", data: response };
}
// Install script dataType
jQuery.ajaxSetup({
	accepts: {
		script: "text/javascript, application/javascript, application/ecmascript, application/x-ecmascript"
	},
	contents: {
		script: /(?:java|ecma)script/
	},
	converters: {
		"text script": function( text ) {
			jQuery.globalEval( text );
			return text;
		}
	}
});

// Handle cache's special case and global
jQuery.ajaxPrefilter( "script", function( s ) {
	if ( s.cache === undefined ) {
		s.cache = false;
	}
	if ( s.crossDomain ) {
		s.type = "GET";
		s.global = false;
	}
});

// Bind script tag hack transport
jQuery.ajaxTransport( "script", function(s) {

	// This transport only deals with cross domain requests
	if ( s.crossDomain ) {

		var script,
			head = document.head || jQuery("head")[0] || document.documentElement;

		return {

			send: function( _, callback ) {

				script = document.createElement("script");

				script.async = true;

				if ( s.scriptCharset ) {
					script.charset = s.scriptCharset;
				}

				script.src = s.url;

				// Attach handlers for all browsers
				script.onload = script.onreadystatechange = function( _, isAbort ) {

					if ( isAbort || !script.readyState || /loaded|complete/.test( script.readyState ) ) {

						// Handle memory leak in IE
						script.onload = script.onreadystatechange = null;

						// Remove the script
						if ( script.parentNode ) {
							script.parentNode.removeChild( script );
						}

						// Dereference the script
						script = null;

						// Callback if not abort
						if ( !isAbort ) {
							callback( 200, "success" );
						}
					}
				};

				// Circumvent IE6 bugs with base elements (#2709 and #4378) by prepending
				// Use native DOM manipulation to avoid our domManip AJAX trickery
				head.insertBefore( script, head.firstChild );
			},

			abort: function() {
				if ( script ) {
					script.onload( undefined, true );
				}
			}
		};
	}
});
var oldCallbacks = [],
	rjsonp = /(=)\?(?=&|$)|\?\?/;

// Default jsonp settings
jQuery.ajaxSetup({
	jsonp: "callback",
	jsonpCallback: function() {
		var callback = oldCallbacks.pop() || ( jQuery.expando + "_" + ( ajax_nonce++ ) );
		this[ callback ] = true;
		return callback;
	}
});

// Detect, normalize options and install callbacks for jsonp requests
jQuery.ajaxPrefilter( "json jsonp", function( s, originalSettings, jqXHR ) {

	var callbackName, overwritten, responseContainer,
		jsonProp = s.jsonp !== false && ( rjsonp.test( s.url ) ?
			"url" :
			typeof s.data === "string" && !( s.contentType || "" ).indexOf("application/x-www-form-urlencoded") && rjsonp.test( s.data ) && "data"
		);

	// Handle iff the expected data type is "jsonp" or we have a parameter to set
	if ( jsonProp || s.dataTypes[ 0 ] === "jsonp" ) {

		// Get callback name, remembering preexisting value associated with it
		callbackName = s.jsonpCallback = jQuery.isFunction( s.jsonpCallback ) ?
			s.jsonpCallback() :
			s.jsonpCallback;

		// Insert callback into url or form data
		if ( jsonProp ) {
			s[ jsonProp ] = s[ jsonProp ].replace( rjsonp, "$1" + callbackName );
		} else if ( s.jsonp !== false ) {
			s.url += ( ajax_rquery.test( s.url ) ? "&" : "?" ) + s.jsonp + "=" + callbackName;
		}

		// Use data converter to retrieve json after script execution
		s.converters["script json"] = function() {
			if ( !responseContainer ) {
				jQuery.error( callbackName + " was not called" );
			}
			return responseContainer[ 0 ];
		};

		// force json dataType
		s.dataTypes[ 0 ] = "json";

		// Install callback
		overwritten = window[ callbackName ];
		window[ callbackName ] = function() {
			responseContainer = arguments;
		};

		// Clean-up function (fires after converters)
		jqXHR.always(function() {
			// Restore preexisting value
			window[ callbackName ] = overwritten;

			// Save back as free
			if ( s[ callbackName ] ) {
				// make sure that re-using the options doesn't screw things around
				s.jsonpCallback = originalSettings.jsonpCallback;

				// save the callback name for future use
				oldCallbacks.push( callbackName );
			}

			// Call if it was a function and we have a response
			if ( responseContainer && jQuery.isFunction( overwritten ) ) {
				overwritten( responseContainer[ 0 ] );
			}

			responseContainer = overwritten = undefined;
		});

		// Delegate to script
		return "script";
	}
});
var xhrCallbacks, xhrSupported,
	xhrId = 0,
	// #5280: Internet Explorer will keep connections alive if we don't abort on unload
	xhrOnUnloadAbort = window.ActiveXObject && function() {
		// Abort all pending requests
		var key;
		for ( key in xhrCallbacks ) {
			xhrCallbacks[ key ]( undefined, true );
		}
	};

// Functions to create xhrs
function createStandardXHR() {
	try {
		return new window.XMLHttpRequest();
	} catch( e ) {}
}

function createActiveXHR() {
	try {
		return new window.ActiveXObject("Microsoft.XMLHTTP");
	} catch( e ) {}
}

// Create the request object
// (This is still attached to ajaxSettings for backward compatibility)
jQuery.ajaxSettings.xhr = window.ActiveXObject ?
	/* Microsoft failed to properly
	 * implement the XMLHttpRequest in IE7 (can't request local files),
	 * so we use the ActiveXObject when it is available
	 * Additionally XMLHttpRequest can be disabled in IE7/IE8 so
	 * we need a fallback.
	 */
	function() {
		return !this.isLocal && createStandardXHR() || createActiveXHR();
	} :
	// For all other browsers, use the standard XMLHttpRequest object
	createStandardXHR;

// Determine support properties
xhrSupported = jQuery.ajaxSettings.xhr();
jQuery.support.cors = !!xhrSupported && ( "withCredentials" in xhrSupported );
xhrSupported = jQuery.support.ajax = !!xhrSupported;

// Create transport if the browser can provide an xhr
if ( xhrSupported ) {

	jQuery.ajaxTransport(function( s ) {
		// Cross domain only allowed if supported through XMLHttpRequest
		if ( !s.crossDomain || jQuery.support.cors ) {

			var callback;

			return {
				send: function( headers, complete ) {

					// Get a new xhr
					var handle, i,
						xhr = s.xhr();

					// Open the socket
					// Passing null username, generates a login popup on Opera (#2865)
					if ( s.username ) {
						xhr.open( s.type, s.url, s.async, s.username, s.password );
					} else {
						xhr.open( s.type, s.url, s.async );
					}

					// Apply custom fields if provided
					if ( s.xhrFields ) {
						for ( i in s.xhrFields ) {
							xhr[ i ] = s.xhrFields[ i ];
						}
					}

					// Override mime type if needed
					if ( s.mimeType && xhr.overrideMimeType ) {
						xhr.overrideMimeType( s.mimeType );
					}

					// X-Requested-With header
					// For cross-domain requests, seeing as conditions for a preflight are
					// akin to a jigsaw puzzle, we simply never set it to be sure.
					// (it can always be set on a per-request basis or even using ajaxSetup)
					// For same-domain requests, won't change header if already provided.
					if ( !s.crossDomain && !headers["X-Requested-With"] ) {
						headers["X-Requested-With"] = "XMLHttpRequest";
					}

					// Need an extra try/catch for cross domain requests in Firefox 3
					try {
						for ( i in headers ) {
							xhr.setRequestHeader( i, headers[ i ] );
						}
					} catch( err ) {}

					// Do send the request
					// This may raise an exception which is actually
					// handled in jQuery.ajax (so no try/catch here)
					xhr.send( ( s.hasContent && s.data ) || null );

					// Listener
					callback = function( _, isAbort ) {
						var status, responseHeaders, statusText, responses;

						// Firefox throws exceptions when accessing properties
						// of an xhr when a network error occurred
						// http://helpful.knobs-dials.com/index.php/Component_returned_failure_code:_0x80040111_(NS_ERROR_NOT_AVAILABLE)
						try {

							// Was never called and is aborted or complete
							if ( callback && ( isAbort || xhr.readyState === 4 ) ) {

								// Only called once
								callback = undefined;

								// Do not keep as active anymore
								if ( handle ) {
									xhr.onreadystatechange = jQuery.noop;
									if ( xhrOnUnloadAbort ) {
										delete xhrCallbacks[ handle ];
									}
								}

								// If it's an abort
								if ( isAbort ) {
									// Abort it manually if needed
									if ( xhr.readyState !== 4 ) {
										xhr.abort();
									}
								} else {
									responses = {};
									status = xhr.status;
									responseHeaders = xhr.getAllResponseHeaders();

									// When requesting binary data, IE6-9 will throw an exception
									// on any attempt to access responseText (#11426)
									if ( typeof xhr.responseText === "string" ) {
										responses.text = xhr.responseText;
									}

									// Firefox throws an exception when accessing
									// statusText for faulty cross-domain requests
									try {
										statusText = xhr.statusText;
									} catch( e ) {
										// We normalize with Webkit giving an empty statusText
										statusText = "";
									}

									// Filter status for non standard behaviors

									// If the request is local and we have data: assume a success
									// (success with no data won't get notified, that's the best we
									// can do given current implementations)
									if ( !status && s.isLocal && !s.crossDomain ) {
										status = responses.text ? 200 : 404;
									// IE - #1450: sometimes returns 1223 when it should be 204
									} else if ( status === 1223 ) {
										status = 204;
									}
								}
							}
						} catch( firefoxAccessException ) {
							if ( !isAbort ) {
								complete( -1, firefoxAccessException );
							}
						}

						// Call complete if needed
						if ( responses ) {
							complete( status, statusText, responses, responseHeaders );
						}
					};

					if ( !s.async ) {
						// if we're in sync mode we fire the callback
						callback();
					} else if ( xhr.readyState === 4 ) {
						// (IE6 & IE7) if it's in cache and has been
						// retrieved directly we need to fire the callback
						setTimeout( callback );
					} else {
						handle = ++xhrId;
						if ( xhrOnUnloadAbort ) {
							// Create the active xhrs callbacks list if needed
							// and attach the unload handler
							if ( !xhrCallbacks ) {
								xhrCallbacks = {};
								jQuery( window ).unload( xhrOnUnloadAbort );
							}
							// Add to list of active xhrs callbacks
							xhrCallbacks[ handle ] = callback;
						}
						xhr.onreadystatechange = callback;
					}
				},

				abort: function() {
					if ( callback ) {
						callback( undefined, true );
					}
				}
			};
		}
	});
}
var fxNow, timerId,
	rfxtypes = /^(?:toggle|show|hide)$/,
	rfxnum = new RegExp( "^(?:([+-])=|)(" + core_pnum + ")([a-z%]*)$", "i" ),
	rrun = /queueHooks$/,
	animationPrefilters = [ defaultPrefilter ],
	tweeners = {
		"*": [function( prop, value ) {
			var tween = this.createTween( prop, value ),
				target = tween.cur(),
				parts = rfxnum.exec( value ),
				unit = parts && parts[ 3 ] || ( jQuery.cssNumber[ prop ] ? "" : "px" ),

				// Starting value computation is required for potential unit mismatches
				start = ( jQuery.cssNumber[ prop ] || unit !== "px" && +target ) &&
					rfxnum.exec( jQuery.css( tween.elem, prop ) ),
				scale = 1,
				maxIterations = 20;

			if ( start && start[ 3 ] !== unit ) {
				// Trust units reported by jQuery.css
				unit = unit || start[ 3 ];

				// Make sure we update the tween properties later on
				parts = parts || [];

				// Iteratively approximate from a nonzero starting point
				start = +target || 1;

				do {
					// If previous iteration zeroed out, double until we get *something*
					// Use a string for doubling factor so we don't accidentally see scale as unchanged below
					scale = scale || ".5";

					// Adjust and apply
					start = start / scale;
					jQuery.style( tween.elem, prop, start + unit );

				// Update scale, tolerating zero or NaN from tween.cur()
				// And breaking the loop if scale is unchanged or perfect, or if we've just had enough
				} while ( scale !== (scale = tween.cur() / target) && scale !== 1 && --maxIterations );
			}

			// Update tween properties
			if ( parts ) {
				start = tween.start = +start || +target || 0;
				tween.unit = unit;
				// If a +=/-= token was provided, we're doing a relative animation
				tween.end = parts[ 1 ] ?
					start + ( parts[ 1 ] + 1 ) * parts[ 2 ] :
					+parts[ 2 ];
			}

			return tween;
		}]
	};

// Animations created synchronously will run synchronously
function createFxNow() {
	setTimeout(function() {
		fxNow = undefined;
	});
	return ( fxNow = jQuery.now() );
}

function createTween( value, prop, animation ) {
	var tween,
		collection = ( tweeners[ prop ] || [] ).concat( tweeners[ "*" ] ),
		index = 0,
		length = collection.length;
	for ( ; index < length; index++ ) {
		if ( (tween = collection[ index ].call( animation, prop, value )) ) {

			// we're done with this property
			return tween;
		}
	}
}

function Animation( elem, properties, options ) {
	var result,
		stopped,
		index = 0,
		length = animationPrefilters.length,
		deferred = jQuery.Deferred().always( function() {
			// don't match elem in the :animated selector
			delete tick.elem;
		}),
		tick = function() {
			if ( stopped ) {
				return false;
			}
			var currentTime = fxNow || createFxNow(),
				remaining = Math.max( 0, animation.startTime + animation.duration - currentTime ),
				// archaic crash bug won't allow us to use 1 - ( 0.5 || 0 ) (#12497)
				temp = remaining / animation.duration || 0,
				percent = 1 - temp,
				index = 0,
				length = animation.tweens.length;

			for ( ; index < length ; index++ ) {
				animation.tweens[ index ].run( percent );
			}

			deferred.notifyWith( elem, [ animation, percent, remaining ]);

			if ( percent < 1 && length ) {
				return remaining;
			} else {
				deferred.resolveWith( elem, [ animation ] );
				return false;
			}
		},
		animation = deferred.promise({
			elem: elem,
			props: jQuery.extend( {}, properties ),
			opts: jQuery.extend( true, { specialEasing: {} }, options ),
			originalProperties: properties,
			originalOptions: options,
			startTime: fxNow || createFxNow(),
			duration: options.duration,
			tweens: [],
			createTween: function( prop, end ) {
				var tween = jQuery.Tween( elem, animation.opts, prop, end,
						animation.opts.specialEasing[ prop ] || animation.opts.easing );
				animation.tweens.push( tween );
				return tween;
			},
			stop: function( gotoEnd ) {
				var index = 0,
					// if we are going to the end, we want to run all the tweens
					// otherwise we skip this part
					length = gotoEnd ? animation.tweens.length : 0;
				if ( stopped ) {
					return this;
				}
				stopped = true;
				for ( ; index < length ; index++ ) {
					animation.tweens[ index ].run( 1 );
				}

				// resolve when we played the last frame
				// otherwise, reject
				if ( gotoEnd ) {
					deferred.resolveWith( elem, [ animation, gotoEnd ] );
				} else {
					deferred.rejectWith( elem, [ animation, gotoEnd ] );
				}
				return this;
			}
		}),
		props = animation.props;

	propFilter( props, animation.opts.specialEasing );

	for ( ; index < length ; index++ ) {
		result = animationPrefilters[ index ].call( animation, elem, props, animation.opts );
		if ( result ) {
			return result;
		}
	}

	jQuery.map( props, createTween, animation );

	if ( jQuery.isFunction( animation.opts.start ) ) {
		animation.opts.start.call( elem, animation );
	}

	jQuery.fx.timer(
		jQuery.extend( tick, {
			elem: elem,
			anim: animation,
			queue: animation.opts.queue
		})
	);

	// attach callbacks from options
	return animation.progress( animation.opts.progress )
		.done( animation.opts.done, animation.opts.complete )
		.fail( animation.opts.fail )
		.always( animation.opts.always );
}

function propFilter( props, specialEasing ) {
	var index, name, easing, value, hooks;

	// camelCase, specialEasing and expand cssHook pass
	for ( index in props ) {
		name = jQuery.camelCase( index );
		easing = specialEasing[ name ];
		value = props[ index ];
		if ( jQuery.isArray( value ) ) {
			easing = value[ 1 ];
			value = props[ index ] = value[ 0 ];
		}

		if ( index !== name ) {
			props[ name ] = value;
			delete props[ index ];
		}

		hooks = jQuery.cssHooks[ name ];
		if ( hooks && "expand" in hooks ) {
			value = hooks.expand( value );
			delete props[ name ];

			// not quite $.extend, this wont overwrite keys already present.
			// also - reusing 'index' from above because we have the correct "name"
			for ( index in value ) {
				if ( !( index in props ) ) {
					props[ index ] = value[ index ];
					specialEasing[ index ] = easing;
				}
			}
		} else {
			specialEasing[ name ] = easing;
		}
	}
}

jQuery.Animation = jQuery.extend( Animation, {

	tweener: function( props, callback ) {
		if ( jQuery.isFunction( props ) ) {
			callback = props;
			props = [ "*" ];
		} else {
			props = props.split(" ");
		}

		var prop,
			index = 0,
			length = props.length;

		for ( ; index < length ; index++ ) {
			prop = props[ index ];
			tweeners[ prop ] = tweeners[ prop ] || [];
			tweeners[ prop ].unshift( callback );
		}
	},

	prefilter: function( callback, prepend ) {
		if ( prepend ) {
			animationPrefilters.unshift( callback );
		} else {
			animationPrefilters.push( callback );
		}
	}
});

function defaultPrefilter( elem, props, opts ) {
	/* jshint validthis: true */
	var prop, value, toggle, tween, hooks, oldfire,
		anim = this,
		orig = {},
		style = elem.style,
		hidden = elem.nodeType && isHidden( elem ),
		dataShow = jQuery._data( elem, "fxshow" );

	// handle queue: false promises
	if ( !opts.queue ) {
		hooks = jQuery._queueHooks( elem, "fx" );
		if ( hooks.unqueued == null ) {
			hooks.unqueued = 0;
			oldfire = hooks.empty.fire;
			hooks.empty.fire = function() {
				if ( !hooks.unqueued ) {
					oldfire();
				}
			};
		}
		hooks.unqueued++;

		anim.always(function() {
			// doing this makes sure that the complete handler will be called
			// before this completes
			anim.always(function() {
				hooks.unqueued--;
				if ( !jQuery.queue( elem, "fx" ).length ) {
					hooks.empty.fire();
				}
			});
		});
	}

	// height/width overflow pass
	if ( elem.nodeType === 1 && ( "height" in props || "width" in props ) ) {
		// Make sure that nothing sneaks out
		// Record all 3 overflow attributes because IE does not
		// change the overflow attribute when overflowX and
		// overflowY are set to the same value
		opts.overflow = [ style.overflow, style.overflowX, style.overflowY ];

		// Set display property to inline-block for height/width
		// animations on inline elements that are having width/height animated
		if ( jQuery.css( elem, "display" ) === "inline" &&
				jQuery.css( elem, "float" ) === "none" ) {

			// inline-level elements accept inline-block;
			// block-level elements need to be inline with layout
			if ( !jQuery.support.inlineBlockNeedsLayout || css_defaultDisplay( elem.nodeName ) === "inline" ) {
				style.display = "inline-block";

			} else {
				style.zoom = 1;
			}
		}
	}

	if ( opts.overflow ) {
		style.overflow = "hidden";
		if ( !jQuery.support.shrinkWrapBlocks ) {
			anim.always(function() {
				style.overflow = opts.overflow[ 0 ];
				style.overflowX = opts.overflow[ 1 ];
				style.overflowY = opts.overflow[ 2 ];
			});
		}
	}


	// show/hide pass
	for ( prop in props ) {
		value = props[ prop ];
		if ( rfxtypes.exec( value ) ) {
			delete props[ prop ];
			toggle = toggle || value === "toggle";
			if ( value === ( hidden ? "hide" : "show" ) ) {
				continue;
			}
			orig[ prop ] = dataShow && dataShow[ prop ] || jQuery.style( elem, prop );
		}
	}

	if ( !jQuery.isEmptyObject( orig ) ) {
		if ( dataShow ) {
			if ( "hidden" in dataShow ) {
				hidden = dataShow.hidden;
			}
		} else {
			dataShow = jQuery._data( elem, "fxshow", {} );
		}

		// store state if its toggle - enables .stop().toggle() to "reverse"
		if ( toggle ) {
			dataShow.hidden = !hidden;
		}
		if ( hidden ) {
			jQuery( elem ).show();
		} else {
			anim.done(function() {
				jQuery( elem ).hide();
			});
		}
		anim.done(function() {
			var prop;
			jQuery._removeData( elem, "fxshow" );
			for ( prop in orig ) {
				jQuery.style( elem, prop, orig[ prop ] );
			}
		});
		for ( prop in orig ) {
			tween = createTween( hidden ? dataShow[ prop ] : 0, prop, anim );

			if ( !( prop in dataShow ) ) {
				dataShow[ prop ] = tween.start;
				if ( hidden ) {
					tween.end = tween.start;
					tween.start = prop === "width" || prop === "height" ? 1 : 0;
				}
			}
		}
	}
}

function Tween( elem, options, prop, end, easing ) {
	return new Tween.prototype.init( elem, options, prop, end, easing );
}
jQuery.Tween = Tween;

Tween.prototype = {
	constructor: Tween,
	init: function( elem, options, prop, end, easing, unit ) {
		this.elem = elem;
		this.prop = prop;
		this.easing = easing || "swing";
		this.options = options;
		this.start = this.now = this.cur();
		this.end = end;
		this.unit = unit || ( jQuery.cssNumber[ prop ] ? "" : "px" );
	},
	cur: function() {
		var hooks = Tween.propHooks[ this.prop ];

		return hooks && hooks.get ?
			hooks.get( this ) :
			Tween.propHooks._default.get( this );
	},
	run: function( percent ) {
		var eased,
			hooks = Tween.propHooks[ this.prop ];

		if ( this.options.duration ) {
			this.pos = eased = jQuery.easing[ this.easing ](
				percent, this.options.duration * percent, 0, 1, this.options.duration
			);
		} else {
			this.pos = eased = percent;
		}
		this.now = ( this.end - this.start ) * eased + this.start;

		if ( this.options.step ) {
			this.options.step.call( this.elem, this.now, this );
		}

		if ( hooks && hooks.set ) {
			hooks.set( this );
		} else {
			Tween.propHooks._default.set( this );
		}
		return this;
	}
};

Tween.prototype.init.prototype = Tween.prototype;

Tween.propHooks = {
	_default: {
		get: function( tween ) {
			var result;

			if ( tween.elem[ tween.prop ] != null &&
				(!tween.elem.style || tween.elem.style[ tween.prop ] == null) ) {
				return tween.elem[ tween.prop ];
			}

			// passing an empty string as a 3rd parameter to .css will automatically
			// attempt a parseFloat and fallback to a string if the parse fails
			// so, simple values such as "10px" are parsed to Float.
			// complex values such as "rotate(1rad)" are returned as is.
			result = jQuery.css( tween.elem, tween.prop, "" );
			// Empty strings, null, undefined and "auto" are converted to 0.
			return !result || result === "auto" ? 0 : result;
		},
		set: function( tween ) {
			// use step hook for back compat - use cssHook if its there - use .style if its
			// available and use plain properties where available
			if ( jQuery.fx.step[ tween.prop ] ) {
				jQuery.fx.step[ tween.prop ]( tween );
			} else if ( tween.elem.style && ( tween.elem.style[ jQuery.cssProps[ tween.prop ] ] != null || jQuery.cssHooks[ tween.prop ] ) ) {
				jQuery.style( tween.elem, tween.prop, tween.now + tween.unit );
			} else {
				tween.elem[ tween.prop ] = tween.now;
			}
		}
	}
};

// Support: IE <=9
// Panic based approach to setting things on disconnected nodes

Tween.propHooks.scrollTop = Tween.propHooks.scrollLeft = {
	set: function( tween ) {
		if ( tween.elem.nodeType && tween.elem.parentNode ) {
			tween.elem[ tween.prop ] = tween.now;
		}
	}
};

jQuery.each([ "toggle", "show", "hide" ], function( i, name ) {
	var cssFn = jQuery.fn[ name ];
	jQuery.fn[ name ] = function( speed, easing, callback ) {
		return speed == null || typeof speed === "boolean" ?
			cssFn.apply( this, arguments ) :
			this.animate( genFx( name, true ), speed, easing, callback );
	};
});

jQuery.fn.extend({
	fadeTo: function( speed, to, easing, callback ) {

		// show any hidden elements after setting opacity to 0
		return this.filter( isHidden ).css( "opacity", 0 ).show()

			// animate to the value specified
			.end().animate({ opacity: to }, speed, easing, callback );
	},
	animate: function( prop, speed, easing, callback ) {
		var empty = jQuery.isEmptyObject( prop ),
			optall = jQuery.speed( speed, easing, callback ),
			doAnimation = function() {
				// Operate on a copy of prop so per-property easing won't be lost
				var anim = Animation( this, jQuery.extend( {}, prop ), optall );

				// Empty animations, or finishing resolves immediately
				if ( empty || jQuery._data( this, "finish" ) ) {
					anim.stop( true );
				}
			};
			doAnimation.finish = doAnimation;

		return empty || optall.queue === false ?
			this.each( doAnimation ) :
			this.queue( optall.queue, doAnimation );
	},
	stop: function( type, clearQueue, gotoEnd ) {
		var stopQueue = function( hooks ) {
			var stop = hooks.stop;
			delete hooks.stop;
			stop( gotoEnd );
		};

		if ( typeof type !== "string" ) {
			gotoEnd = clearQueue;
			clearQueue = type;
			type = undefined;
		}
		if ( clearQueue && type !== false ) {
			this.queue( type || "fx", [] );
		}

		return this.each(function() {
			var dequeue = true,
				index = type != null && type + "queueHooks",
				timers = jQuery.timers,
				data = jQuery._data( this );

			if ( index ) {
				if ( data[ index ] && data[ index ].stop ) {
					stopQueue( data[ index ] );
				}
			} else {
				for ( index in data ) {
					if ( data[ index ] && data[ index ].stop && rrun.test( index ) ) {
						stopQueue( data[ index ] );
					}
				}
			}

			for ( index = timers.length; index--; ) {
				if ( timers[ index ].elem === this && (type == null || timers[ index ].queue === type) ) {
					timers[ index ].anim.stop( gotoEnd );
					dequeue = false;
					timers.splice( index, 1 );
				}
			}

			// start the next in the queue if the last step wasn't forced
			// timers currently will call their complete callbacks, which will dequeue
			// but only if they were gotoEnd
			if ( dequeue || !gotoEnd ) {
				jQuery.dequeue( this, type );
			}
		});
	},
	finish: function( type ) {
		if ( type !== false ) {
			type = type || "fx";
		}
		return this.each(function() {
			var index,
				data = jQuery._data( this ),
				queue = data[ type + "queue" ],
				hooks = data[ type + "queueHooks" ],
				timers = jQuery.timers,
				length = queue ? queue.length : 0;

			// enable finishing flag on private data
			data.finish = true;

			// empty the queue first
			jQuery.queue( this, type, [] );

			if ( hooks && hooks.stop ) {
				hooks.stop.call( this, true );
			}

			// look for any active animations, and finish them
			for ( index = timers.length; index--; ) {
				if ( timers[ index ].elem === this && timers[ index ].queue === type ) {
					timers[ index ].anim.stop( true );
					timers.splice( index, 1 );
				}
			}

			// look for any animations in the old queue and finish them
			for ( index = 0; index < length; index++ ) {
				if ( queue[ index ] && queue[ index ].finish ) {
					queue[ index ].finish.call( this );
				}
			}

			// turn off finishing flag
			delete data.finish;
		});
	}
});

// Generate parameters to create a standard animation
function genFx( type, includeWidth ) {
	var which,
		attrs = { height: type },
		i = 0;

	// if we include width, step value is 1 to do all cssExpand values,
	// if we don't include width, step value is 2 to skip over Left and Right
	includeWidth = includeWidth? 1 : 0;
	for( ; i < 4 ; i += 2 - includeWidth ) {
		which = cssExpand[ i ];
		attrs[ "margin" + which ] = attrs[ "padding" + which ] = type;
	}

	if ( includeWidth ) {
		attrs.opacity = attrs.width = type;
	}

	return attrs;
}

// Generate shortcuts for custom animations
jQuery.each({
	slideDown: genFx("show"),
	slideUp: genFx("hide"),
	slideToggle: genFx("toggle"),
	fadeIn: { opacity: "show" },
	fadeOut: { opacity: "hide" },
	fadeToggle: { opacity: "toggle" }
}, function( name, props ) {
	jQuery.fn[ name ] = function( speed, easing, callback ) {
		return this.animate( props, speed, easing, callback );
	};
});

jQuery.speed = function( speed, easing, fn ) {
	var opt = speed && typeof speed === "object" ? jQuery.extend( {}, speed ) : {
		complete: fn || !fn && easing ||
			jQuery.isFunction( speed ) && speed,
		duration: speed,
		easing: fn && easing || easing && !jQuery.isFunction( easing ) && easing
	};

	opt.duration = jQuery.fx.off ? 0 : typeof opt.duration === "number" ? opt.duration :
		opt.duration in jQuery.fx.speeds ? jQuery.fx.speeds[ opt.duration ] : jQuery.fx.speeds._default;

	// normalize opt.queue - true/undefined/null -> "fx"
	if ( opt.queue == null || opt.queue === true ) {
		opt.queue = "fx";
	}

	// Queueing
	opt.old = opt.complete;

	opt.complete = function() {
		if ( jQuery.isFunction( opt.old ) ) {
			opt.old.call( this );
		}

		if ( opt.queue ) {
			jQuery.dequeue( this, opt.queue );
		}
	};

	return opt;
};

jQuery.easing = {
	linear: function( p ) {
		return p;
	},
	swing: function( p ) {
		return 0.5 - Math.cos( p*Math.PI ) / 2;
	}
};

jQuery.timers = [];
jQuery.fx = Tween.prototype.init;
jQuery.fx.tick = function() {
	var timer,
		timers = jQuery.timers,
		i = 0;

	fxNow = jQuery.now();

	for ( ; i < timers.length; i++ ) {
		timer = timers[ i ];
		// Checks the timer has not already been removed
		if ( !timer() && timers[ i ] === timer ) {
			timers.splice( i--, 1 );
		}
	}

	if ( !timers.length ) {
		jQuery.fx.stop();
	}
	fxNow = undefined;
};

jQuery.fx.timer = function( timer ) {
	if ( timer() && jQuery.timers.push( timer ) ) {
		jQuery.fx.start();
	}
};

jQuery.fx.interval = 13;

jQuery.fx.start = function() {
	if ( !timerId ) {
		timerId = setInterval( jQuery.fx.tick, jQuery.fx.interval );
	}
};

jQuery.fx.stop = function() {
	clearInterval( timerId );
	timerId = null;
};

jQuery.fx.speeds = {
	slow: 600,
	fast: 200,
	// Default speed
	_default: 400
};

// Back Compat <1.8 extension point
jQuery.fx.step = {};

if ( jQuery.expr && jQuery.expr.filters ) {
	jQuery.expr.filters.animated = function( elem ) {
		return jQuery.grep(jQuery.timers, function( fn ) {
			return elem === fn.elem;
		}).length;
	};
}
jQuery.fn.offset = function( options ) {
	if ( arguments.length ) {
		return options === undefined ?
			this :
			this.each(function( i ) {
				jQuery.offset.setOffset( this, options, i );
			});
	}

	var docElem, win,
		box = { top: 0, left: 0 },
		elem = this[ 0 ],
		doc = elem && elem.ownerDocument;

	if ( !doc ) {
		return;
	}

	docElem = doc.documentElement;

	// Make sure it's not a disconnected DOM node
	if ( !jQuery.contains( docElem, elem ) ) {
		return box;
	}

	// If we don't have gBCR, just use 0,0 rather than error
	// BlackBerry 5, iOS 3 (original iPhone)
	if ( typeof elem.getBoundingClientRect !== core_strundefined ) {
		box = elem.getBoundingClientRect();
	}
	win = getWindow( doc );
	return {
		top: box.top  + ( win.pageYOffset || docElem.scrollTop )  - ( docElem.clientTop  || 0 ),
		left: box.left + ( win.pageXOffset || docElem.scrollLeft ) - ( docElem.clientLeft || 0 )
	};
};

jQuery.offset = {

	setOffset: function( elem, options, i ) {
		var position = jQuery.css( elem, "position" );

		// set position first, in-case top/left are set even on static elem
		if ( position === "static" ) {
			elem.style.position = "relative";
		}

		var curElem = jQuery( elem ),
			curOffset = curElem.offset(),
			curCSSTop = jQuery.css( elem, "top" ),
			curCSSLeft = jQuery.css( elem, "left" ),
			calculatePosition = ( position === "absolute" || position === "fixed" ) && jQuery.inArray("auto", [curCSSTop, curCSSLeft]) > -1,
			props = {}, curPosition = {}, curTop, curLeft;

		// need to be able to calculate position if either top or left is auto and position is either absolute or fixed
		if ( calculatePosition ) {
			curPosition = curElem.position();
			curTop = curPosition.top;
			curLeft = curPosition.left;
		} else {
			curTop = parseFloat( curCSSTop ) || 0;
			curLeft = parseFloat( curCSSLeft ) || 0;
		}

		if ( jQuery.isFunction( options ) ) {
			options = options.call( elem, i, curOffset );
		}

		if ( options.top != null ) {
			props.top = ( options.top - curOffset.top ) + curTop;
		}
		if ( options.left != null ) {
			props.left = ( options.left - curOffset.left ) + curLeft;
		}

		if ( "using" in options ) {
			options.using.call( elem, props );
		} else {
			curElem.css( props );
		}
	}
};


jQuery.fn.extend({

	position: function() {
		if ( !this[ 0 ] ) {
			return;
		}

		var offsetParent, offset,
			parentOffset = { top: 0, left: 0 },
			elem = this[ 0 ];

		// fixed elements are offset from window (parentOffset = {top:0, left: 0}, because it is it's only offset parent
		if ( jQuery.css( elem, "position" ) === "fixed" ) {
			// we assume that getBoundingClientRect is available when computed position is fixed
			offset = elem.getBoundingClientRect();
		} else {
			// Get *real* offsetParent
			offsetParent = this.offsetParent();

			// Get correct offsets
			offset = this.offset();
			if ( !jQuery.nodeName( offsetParent[ 0 ], "html" ) ) {
				parentOffset = offsetParent.offset();
			}

			// Add offsetParent borders
			parentOffset.top  += jQuery.css( offsetParent[ 0 ], "borderTopWidth", true );
			parentOffset.left += jQuery.css( offsetParent[ 0 ], "borderLeftWidth", true );
		}

		// Subtract parent offsets and element margins
		// note: when an element has margin: auto the offsetLeft and marginLeft
		// are the same in Safari causing offset.left to incorrectly be 0
		return {
			top:  offset.top  - parentOffset.top - jQuery.css( elem, "marginTop", true ),
			left: offset.left - parentOffset.left - jQuery.css( elem, "marginLeft", true)
		};
	},

	offsetParent: function() {
		return this.map(function() {
			var offsetParent = this.offsetParent || docElem;
			while ( offsetParent && ( !jQuery.nodeName( offsetParent, "html" ) && jQuery.css( offsetParent, "position") === "static" ) ) {
				offsetParent = offsetParent.offsetParent;
			}
			return offsetParent || docElem;
		});
	}
});


// Create scrollLeft and scrollTop methods
jQuery.each( {scrollLeft: "pageXOffset", scrollTop: "pageYOffset"}, function( method, prop ) {
	var top = /Y/.test( prop );

	jQuery.fn[ method ] = function( val ) {
		return jQuery.access( this, function( elem, method, val ) {
			var win = getWindow( elem );

			if ( val === undefined ) {
				return win ? (prop in win) ? win[ prop ] :
					win.document.documentElement[ method ] :
					elem[ method ];
			}

			if ( win ) {
				win.scrollTo(
					!top ? val : jQuery( win ).scrollLeft(),
					top ? val : jQuery( win ).scrollTop()
				);

			} else {
				elem[ method ] = val;
			}
		}, method, val, arguments.length, null );
	};
});

function getWindow( elem ) {
	return jQuery.isWindow( elem ) ?
		elem :
		elem.nodeType === 9 ?
			elem.defaultView || elem.parentWindow :
			false;
}
// Create innerHeight, innerWidth, height, width, outerHeight and outerWidth methods
jQuery.each( { Height: "height", Width: "width" }, function( name, type ) {
	jQuery.each( { padding: "inner" + name, content: type, "": "outer" + name }, function( defaultExtra, funcName ) {
		// margin is only for outerHeight, outerWidth
		jQuery.fn[ funcName ] = function( margin, value ) {
			var chainable = arguments.length && ( defaultExtra || typeof margin !== "boolean" ),
				extra = defaultExtra || ( margin === true || value === true ? "margin" : "border" );

			return jQuery.access( this, function( elem, type, value ) {
				var doc;

				if ( jQuery.isWindow( elem ) ) {
					// As of 5/8/2012 this will yield incorrect results for Mobile Safari, but there
					// isn't a whole lot we can do. See pull request at this URL for discussion:
					// https://github.com/jquery/jquery/pull/764
					return elem.document.documentElement[ "client" + name ];
				}

				// Get document width or height
				if ( elem.nodeType === 9 ) {
					doc = elem.documentElement;

					// Either scroll[Width/Height] or offset[Width/Height] or client[Width/Height], whichever is greatest
					// unfortunately, this causes bug #3838 in IE6/8 only, but there is currently no good, small way to fix it.
					return Math.max(
						elem.body[ "scroll" + name ], doc[ "scroll" + name ],
						elem.body[ "offset" + name ], doc[ "offset" + name ],
						doc[ "client" + name ]
					);
				}

				return value === undefined ?
					// Get width or height on the element, requesting but not forcing parseFloat
					jQuery.css( elem, type, extra ) :

					// Set width or height on the element
					jQuery.style( elem, type, value, extra );
			}, type, chainable ? margin : undefined, chainable, null );
		};
	});
});
// Limit scope pollution from any deprecated API
// (function() {

// The number of elements contained in the matched element set
jQuery.fn.size = function() {
	return this.length;
};

jQuery.fn.andSelf = jQuery.fn.addBack;

// })();
lib.jQuery = jQuery;

})( window, ChemDoodle.lib );
/*! Copyright (c) 2013 Brandon Aaron (http://brandon.aaron.sh)
 * Licensed under the MIT License (LICENSE.txt).
 *
 * Version: 3.1.8
 *
 * Requires: jQuery 1.2.2+
 */

(function (factory) {
    if ( typeof define === 'function' && define.amd ) {
        // AMD. Register as an anonymous module.
        define(['jquery'], factory);
    } else if (typeof exports === 'object') {
        // Node/CommonJS style for Browserify
        module.exports = factory;
    } else {
        // Browser globals
        factory(ChemDoodle.lib.jQuery);
    }
}(function ($) {

    var toFix  = ['wheel', 'mousewheel', 'DOMMouseScroll', 'MozMousePixelScroll'],
        toBind = ( 'onwheel' in document || document.documentMode >= 9 ) ?
                    ['wheel'] : ['mousewheel', 'DomMouseScroll', 'MozMousePixelScroll'],
        slice  = Array.prototype.slice,
        oldMode, nullLowestDeltaTimeout, lowestDelta;

    if ( $.event.fixHooks ) {
        for ( var i = toFix.length; i; ) {
            $.event.fixHooks[ toFix[--i] ] = $.event.mouseHooks;
        }
    }

    var special = $.event.special.mousewheel = {
        version: '3.1.8',

        setup: function() {
            if ( this.addEventListener ) {
                for ( var i = toBind.length; i; ) {
                    this.addEventListener( toBind[--i], handler, false );
                }
            } else {
                this.onmousewheel = handler;
            }
            // Store the line height and page height for this particular element
            $.data(this, 'mousewheel-line-height', special.getLineHeight(this));
            $.data(this, 'mousewheel-page-height', special.getPageHeight(this));
        },

        teardown: function() {
            if ( this.removeEventListener ) {
                for ( var i = toBind.length; i; ) {
                    this.removeEventListener( toBind[--i], handler, false );
                }
            } else {
                this.onmousewheel = null;
            }
        },

        getLineHeight: function(elem) {
            return parseInt($(elem)['offsetParent' in $.fn ? 'offsetParent' : 'parent']().css('fontSize'), 10);
        },

        getPageHeight: function(elem) {
            return $(elem).height();
        }
    };

    $.fn.extend({
        mousewheel: function(fn) {
            return fn ? this.bind('mousewheel', fn) : this.trigger('mousewheel');
        },

        unmousewheel: function(fn) {
            return this.unbind('mousewheel', fn);
        }
    });


    function handler(event) {
        var orgEvent   = event || window.event,
            args       = slice.call(arguments, 1),
            delta      = 0,
            deltaX     = 0,
            deltaY     = 0,
            absDelta   = 0;
        event = $.event.fix(orgEvent);
        event.type = 'mousewheel';

        // Old school scrollwheel delta
        if ( 'detail'      in orgEvent ) { deltaY = orgEvent.detail * -1;      }
        if ( 'wheelDelta'  in orgEvent ) { deltaY = orgEvent.wheelDelta;       }
        if ( 'wheelDeltaY' in orgEvent ) { deltaY = orgEvent.wheelDeltaY;      }
        if ( 'wheelDeltaX' in orgEvent ) { deltaX = orgEvent.wheelDeltaX * -1; }

        // Firefox < 17 horizontal scrolling related to DOMMouseScroll event
        if ( 'axis' in orgEvent && orgEvent.axis === orgEvent.HORIZONTAL_AXIS ) {
            deltaX = deltaY * -1;
            deltaY = 0;
        }

        // Set delta to be deltaY or deltaX if deltaY is 0 for backwards compatabilitiy
        delta = deltaY === 0 ? deltaX : deltaY;

        // New school wheel delta (wheel event)
        if ( 'deltaY' in orgEvent ) {
            deltaY = orgEvent.deltaY * -1;
            delta  = deltaY;
        }
        if ( 'deltaX' in orgEvent ) {
            deltaX = orgEvent.deltaX;
            if ( deltaY === 0 ) { delta  = deltaX * -1; }
        }

        // No change actually happened, no reason to go any further
        if ( deltaY === 0 && deltaX === 0 ) { return; }

        // Need to convert lines and pages to pixels if we aren't already in pixels
        // There are three delta modes:
        //   * deltaMode 0 is by pixels, nothing to do
        //   * deltaMode 1 is by lines
        //   * deltaMode 2 is by pages
        if ( orgEvent.deltaMode === 1 ) {
            var lineHeight = $.data(this, 'mousewheel-line-height');
            delta  *= lineHeight;
            deltaY *= lineHeight;
            deltaX *= lineHeight;
        } else if ( orgEvent.deltaMode === 2 ) {
            var pageHeight = $.data(this, 'mousewheel-page-height');
            delta  *= pageHeight;
            deltaY *= pageHeight;
            deltaX *= pageHeight;
        }

        // Store lowest absolute delta to normalize the delta values
        absDelta = Math.max( Math.abs(deltaY), Math.abs(deltaX) );

        if ( !lowestDelta || absDelta < lowestDelta ) {
            lowestDelta = absDelta;

            // Assuming that if the lowestDelta is 120, then that the browser
            // is treating this as an older mouse wheel event.
            // We'll divide it by 40 to try and get a more usable deltaFactor.
            if ( lowestDelta === 120 ) {
                oldMode = true;
                lowestDelta /= 40;
            }
        }

        // When in oldMode the delta is based on 120.
        // Dividing by 40 to try and get a more usable deltaFactor.
        if ( oldMode ) {
            // Divide all the things by 40!
            delta  /= 40;
            deltaX /= 40;
            deltaY /= 40;
        }

        // Get a whole, normalized value for the deltas
        delta  = Math[ delta  >= 1 ? 'floor' : 'ceil' ](delta  / lowestDelta);
        deltaX = Math[ deltaX >= 1 ? 'floor' : 'ceil' ](deltaX / lowestDelta);
        deltaY = Math[ deltaY >= 1 ? 'floor' : 'ceil' ](deltaY / lowestDelta);

        // Add information to the event object
        event.deltaX = deltaX;
        event.deltaY = deltaY;
        event.deltaFactor = lowestDelta;
        // Go ahead and set deltaMode to 0 since we converted to pixels
        // Although this is a little odd since we overwrite the deltaX/Y
        // properties with normalized deltas.
        event.deltaMode = 0;

        // Add event and delta to the front of the arguments
        args.unshift(event, delta, deltaX, deltaY);

        // Clearout lowestDelta after sometime to better
        // handle multiple device types that give different
        // a different lowestDelta
        // Ex: trackpad = 3 and mouse wheel = 120
        if (nullLowestDeltaTimeout) { clearTimeout(nullLowestDeltaTimeout); }
        nullLowestDeltaTimeout = setTimeout(nullLowestDelta, 200);

        return ($.event.dispatch || $.event.handle).apply(this, args);
    }

    function nullLowestDelta() {
        lowestDelta = null;
        oldMode = null;
    }

}));
/**
 * @fileoverview gl-matrix - High performance matrix and vector operations for WebGL
 * @author Brandon Jones
 * @author Colin MacKenzie IV
 * @version 1.3.7
 */

/*
 * Copyright (c) 2012 Brandon Jones, Colin MacKenzie IV
 *
 * This software is provided 'as-is', without any express or implied
 * warranty. In no event will the authors be held liable for any damages
 * arising from the use of this software.
 *
 * Permission is granted to anyone to use this software for any purpose,
 * including commercial applications, and to alter it and redistribute it
 * freely, subject to the following restrictions:
 *
 *    1. The origin of this software must not be misrepresented; you must not
 *    claim that you wrote the original software. If you use this software
 *    in a product, an acknowledgment in the product documentation would be
 *    appreciated but is not required.
 *
 *    2. Altered source versions must be plainly marked as such, and must not
 *    be misrepresented as being the original software.
 *
 *    3. This notice may not be removed or altered from any source
 *    distribution.
 */

// Updated to use a modification of the "returnExportsGlobal" pattern from https://github.com/umdjs/umd

(function (root, factory) {
    if (typeof exports === 'object') {
        // Node. Does not work with strict CommonJS, but
        // only CommonJS-like enviroments that support module.exports,
        // like Node.
        module.exports = factory(global);
    } else if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define([], function () {
            return factory(root);
        });
    } else {
        // Browser globals
        factory(root);
    }
}(ChemDoodle.lib, function (root) {
    "use strict";

    // Tweak to your liking
    var FLOAT_EPSILON = 0.000001;

    var glMath = {};
    (function() {
        if (typeof(Float32Array) != 'undefined') {
            var y = new Float32Array(1);
            var i = new Int32Array(y.buffer);

            /**
             * Fast way to calculate the inverse square root,
             * see http://jsperf.com/inverse-square-root/5
             *
             * If typed arrays are not available, a slower
             * implementation will be used.
             *
             * @param {Number} number the number
             * @returns {Number} Inverse square root
             */
            glMath.invsqrt = function(number) {
              var x2 = number * 0.5;
              y[0] = number;
              var threehalfs = 1.5;

              i[0] = 0x5f3759df - (i[0] >> 1);

              var number2 = y[0];

              return number2 * (threehalfs - (x2 * number2 * number2));
            };
        } else {
            glMath.invsqrt = function(number) { return 1.0 / Math.sqrt(number); };
        }
    })();

    /**
     * @class System-specific optimal array type
     * @name MatrixArray
     */
    var MatrixArray = null;
    
    // explicitly sets and returns the type of array to use within glMatrix
    function setMatrixArrayType(type) {
        MatrixArray = type;
        return MatrixArray;
    }

    // auto-detects and returns the best type of array to use within glMatrix, falling
    // back to Array if typed arrays are unsupported
    function determineMatrixArrayType() {
        MatrixArray = (typeof Float32Array !== 'undefined') ? Float32Array : Array;
        return MatrixArray;
    }
    
    determineMatrixArrayType();

    /**
     * @class 3 Dimensional Vector
     * @name vec3
     */
    var vec3 = {};
     
    /**
     * Creates a new instance of a vec3 using the default array type
     * Any javascript array-like objects containing at least 3 numeric elements can serve as a vec3
     *
     * @param {vec3} [vec] vec3 containing values to initialize with
     *
     * @returns {vec3} New vec3
     */
    vec3.create = function (vec) {
        var dest = new MatrixArray(3);

        if (vec) {
            dest[0] = vec[0];
            dest[1] = vec[1];
            dest[2] = vec[2];
        } else {
            dest[0] = dest[1] = dest[2] = 0;
        }

        return dest;
    };

    /**
     * Creates a new instance of a vec3, initializing it with the given arguments
     *
     * @param {number} x X value
     * @param {number} y Y value
     * @param {number} z Z value

     * @returns {vec3} New vec3
     */
    vec3.createFrom = function (x, y, z) {
        var dest = new MatrixArray(3);

        dest[0] = x;
        dest[1] = y;
        dest[2] = z;

        return dest;
    };

    /**
     * Copies the values of one vec3 to another
     *
     * @param {vec3} vec vec3 containing values to copy
     * @param {vec3} dest vec3 receiving copied values
     *
     * @returns {vec3} dest
     */
    vec3.set = function (vec, dest) {
        dest[0] = vec[0];
        dest[1] = vec[1];
        dest[2] = vec[2];

        return dest;
    };

    /**
     * Compares two vectors for equality within a certain margin of error
     *
     * @param {vec3} a First vector
     * @param {vec3} b Second vector
     *
     * @returns {Boolean} True if a is equivalent to b
     */
    vec3.equal = function (a, b) {
        return a === b || (
            Math.abs(a[0] - b[0]) < FLOAT_EPSILON &&
            Math.abs(a[1] - b[1]) < FLOAT_EPSILON &&
            Math.abs(a[2] - b[2]) < FLOAT_EPSILON
        );
    };

    /**
     * Performs a vector addition
     *
     * @param {vec3} vec First operand
     * @param {vec3} vec2 Second operand
     * @param {vec3} [dest] vec3 receiving operation result. If not specified result is written to vec
     *
     * @returns {vec3} dest if specified, vec otherwise
     */
    vec3.add = function (vec, vec2, dest) {
        if (!dest || vec === dest) {
            vec[0] += vec2[0];
            vec[1] += vec2[1];
            vec[2] += vec2[2];
            return vec;
        }

        dest[0] = vec[0] + vec2[0];
        dest[1] = vec[1] + vec2[1];
        dest[2] = vec[2] + vec2[2];
        return dest;
    };

    /**
     * Performs a vector subtraction
     *
     * @param {vec3} vec First operand
     * @param {vec3} vec2 Second operand
     * @param {vec3} [dest] vec3 receiving operation result. If not specified result is written to vec
     *
     * @returns {vec3} dest if specified, vec otherwise
     */
    vec3.subtract = function (vec, vec2, dest) {
        if (!dest || vec === dest) {
            vec[0] -= vec2[0];
            vec[1] -= vec2[1];
            vec[2] -= vec2[2];
            return vec;
        }

        dest[0] = vec[0] - vec2[0];
        dest[1] = vec[1] - vec2[1];
        dest[2] = vec[2] - vec2[2];
        return dest;
    };

    /**
     * Performs a vector multiplication
     *
     * @param {vec3} vec First operand
     * @param {vec3} vec2 Second operand
     * @param {vec3} [dest] vec3 receiving operation result. If not specified result is written to vec
     *
     * @returns {vec3} dest if specified, vec otherwise
     */
    vec3.multiply = function (vec, vec2, dest) {
        if (!dest || vec === dest) {
            vec[0] *= vec2[0];
            vec[1] *= vec2[1];
            vec[2] *= vec2[2];
            return vec;
        }

        dest[0] = vec[0] * vec2[0];
        dest[1] = vec[1] * vec2[1];
        dest[2] = vec[2] * vec2[2];
        return dest;
    };

    /**
     * Negates the components of a vec3
     *
     * @param {vec3} vec vec3 to negate
     * @param {vec3} [dest] vec3 receiving operation result. If not specified result is written to vec
     *
     * @returns {vec3} dest if specified, vec otherwise
     */
    vec3.negate = function (vec, dest) {
        if (!dest) { dest = vec; }

        dest[0] = -vec[0];
        dest[1] = -vec[1];
        dest[2] = -vec[2];
        return dest;
    };

    /**
     * Multiplies the components of a vec3 by a scalar value
     *
     * @param {vec3} vec vec3 to scale
     * @param {number} val Value to scale by
     * @param {vec3} [dest] vec3 receiving operation result. If not specified result is written to vec
     *
     * @returns {vec3} dest if specified, vec otherwise
     */
    vec3.scale = function (vec, val, dest) {
        if (!dest || vec === dest) {
            vec[0] *= val;
            vec[1] *= val;
            vec[2] *= val;
            return vec;
        }

        dest[0] = vec[0] * val;
        dest[1] = vec[1] * val;
        dest[2] = vec[2] * val;
        return dest;
    };

    /**
     * Generates a unit vector of the same direction as the provided vec3
     * If vector length is 0, returns [0, 0, 0]
     *
     * @param {vec3} vec vec3 to normalize
     * @param {vec3} [dest] vec3 receiving operation result. If not specified result is written to vec
     *
     * @returns {vec3} dest if specified, vec otherwise
     */
    vec3.normalize = function (vec, dest) {
        if (!dest) { dest = vec; }

        var x = vec[0], y = vec[1], z = vec[2],
            len = Math.sqrt(x * x + y * y + z * z);

        if (!len) {
            dest[0] = 0;
            dest[1] = 0;
            dest[2] = 0;
            return dest;
        } else if (len === 1) {
            dest[0] = x;
            dest[1] = y;
            dest[2] = z;
            return dest;
        }

        len = 1 / len;
        dest[0] = x * len;
        dest[1] = y * len;
        dest[2] = z * len;
        return dest;
    };

    /**
     * Generates the cross product of two vec3s
     *
     * @param {vec3} vec First operand
     * @param {vec3} vec2 Second operand
     * @param {vec3} [dest] vec3 receiving operation result. If not specified result is written to vec
     *
     * @returns {vec3} dest if specified, vec otherwise
     */
    vec3.cross = function (vec, vec2, dest) {
        if (!dest) { dest = vec; }

        var x = vec[0], y = vec[1], z = vec[2],
            x2 = vec2[0], y2 = vec2[1], z2 = vec2[2];

        dest[0] = y * z2 - z * y2;
        dest[1] = z * x2 - x * z2;
        dest[2] = x * y2 - y * x2;
        return dest;
    };

    /**
     * Caclulates the length of a vec3
     *
     * @param {vec3} vec vec3 to calculate length of
     *
     * @returns {number} Length of vec
     */
    vec3.length = function (vec) {
        var x = vec[0], y = vec[1], z = vec[2];
        return Math.sqrt(x * x + y * y + z * z);
    };

    /**
     * Caclulates the squared length of a vec3
     *
     * @param {vec3} vec vec3 to calculate squared length of
     *
     * @returns {number} Squared Length of vec
     */
    vec3.squaredLength = function (vec) {
        var x = vec[0], y = vec[1], z = vec[2];
        return x * x + y * y + z * z;
    };

    /**
     * Caclulates the dot product of two vec3s
     *
     * @param {vec3} vec First operand
     * @param {vec3} vec2 Second operand
     *
     * @returns {number} Dot product of vec and vec2
     */
    vec3.dot = function (vec, vec2) {
        return vec[0] * vec2[0] + vec[1] * vec2[1] + vec[2] * vec2[2];
    };

    /**
     * Generates a unit vector pointing from one vector to another
     *
     * @param {vec3} vec Origin vec3
     * @param {vec3} vec2 vec3 to point to
     * @param {vec3} [dest] vec3 receiving operation result. If not specified result is written to vec
     *
     * @returns {vec3} dest if specified, vec otherwise
     */
    vec3.direction = function (vec, vec2, dest) {
        if (!dest) { dest = vec; }

        var x = vec[0] - vec2[0],
            y = vec[1] - vec2[1],
            z = vec[2] - vec2[2],
            len = Math.sqrt(x * x + y * y + z * z);

        if (!len) {
            dest[0] = 0;
            dest[1] = 0;
            dest[2] = 0;
            return dest;
        }

        len = 1 / len;
        dest[0] = x * len;
        dest[1] = y * len;
        dest[2] = z * len;
        return dest;
    };

    /**
     * Performs a linear interpolation between two vec3
     *
     * @param {vec3} vec First vector
     * @param {vec3} vec2 Second vector
     * @param {number} lerp Interpolation amount between the two inputs
     * @param {vec3} [dest] vec3 receiving operation result. If not specified result is written to vec
     *
     * @returns {vec3} dest if specified, vec otherwise
     */
    vec3.lerp = function (vec, vec2, lerp, dest) {
        if (!dest) { dest = vec; }

        dest[0] = vec[0] + lerp * (vec2[0] - vec[0]);
        dest[1] = vec[1] + lerp * (vec2[1] - vec[1]);
        dest[2] = vec[2] + lerp * (vec2[2] - vec[2]);

        return dest;
    };

    /**
     * Calculates the euclidian distance between two vec3
     *
     * Params:
     * @param {vec3} vec First vector
     * @param {vec3} vec2 Second vector
     *
     * @returns {number} Distance between vec and vec2
     */
    vec3.dist = function (vec, vec2) {
        var x = vec2[0] - vec[0],
            y = vec2[1] - vec[1],
            z = vec2[2] - vec[2];
            
        return Math.sqrt(x*x + y*y + z*z);
    };

    // Pre-allocated to prevent unecessary garbage collection
    var unprojectMat = null;
    var unprojectVec = new MatrixArray(4);
    /**
     * Projects the specified vec3 from screen space into object space
     * Based on the <a href="http://webcvs.freedesktop.org/mesa/Mesa/src/glu/mesa/project.c?revision=1.4&view=markup">Mesa gluUnProject implementation</a>
     *
     * @param {vec3} vec Screen-space vector to project
     * @param {mat4} view View matrix
     * @param {mat4} proj Projection matrix
     * @param {vec4} viewport Viewport as given to gl.viewport [x, y, width, height]
     * @param {vec3} [dest] vec3 receiving unprojected result. If not specified result is written to vec
     *
     * @returns {vec3} dest if specified, vec otherwise
     */
    vec3.unproject = function (vec, view, proj, viewport, dest) {
        if (!dest) { dest = vec; }

        if(!unprojectMat) {
            unprojectMat = mat4.create();
        }

        var m = unprojectMat;
        var v = unprojectVec;
        
        v[0] = (vec[0] - viewport[0]) * 2.0 / viewport[2] - 1.0;
        v[1] = (vec[1] - viewport[1]) * 2.0 / viewport[3] - 1.0;
        v[2] = 2.0 * vec[2] - 1.0;
        v[3] = 1.0;
        
        mat4.multiply(proj, view, m);
        if(!mat4.inverse(m)) { return null; }
        
        mat4.multiplyVec4(m, v);
        if(v[3] === 0.0) { return null; }

        dest[0] = v[0] / v[3];
        dest[1] = v[1] / v[3];
        dest[2] = v[2] / v[3];
        
        return dest;
    };

    var xUnitVec3 = vec3.createFrom(1,0,0);
    var yUnitVec3 = vec3.createFrom(0,1,0);
    var zUnitVec3 = vec3.createFrom(0,0,1);

    var tmpvec3 = vec3.create();
    /**
     * Generates a quaternion of rotation between two given normalized vectors
     *
     * @param {vec3} a Normalized source vector
     * @param {vec3} b Normalized target vector
     * @param {quat4} [dest] quat4 receiving operation result.
     *
     * @returns {quat4} dest if specified, a new quat4 otherwise
     */
    vec3.rotationTo = function (a, b, dest) {
        if (!dest) { dest = quat4.create(); }
        
        var d = vec3.dot(a, b);
        var axis = tmpvec3;
        if (d >= 1.0) {
            quat4.set(identityQuat4, dest);
        } else if (d < (0.000001 - 1.0)) {
            vec3.cross(xUnitVec3, a, axis);
            if (vec3.length(axis) < 0.000001)
                vec3.cross(yUnitVec3, a, axis);
            if (vec3.length(axis) < 0.000001)
                vec3.cross(zUnitVec3, a, axis);
            vec3.normalize(axis);
            quat4.fromAngleAxis(Math.PI, axis, dest);
        } else {
            var s = Math.sqrt((1.0 + d) * 2.0);
            var sInv = 1.0 / s;
            vec3.cross(a, b, axis);
            dest[0] = axis[0] * sInv;
            dest[1] = axis[1] * sInv;
            dest[2] = axis[2] * sInv;
            dest[3] = s * 0.5;
            quat4.normalize(dest);
        }
        if (dest[3] > 1.0) dest[3] = 1.0;
        else if (dest[3] < -1.0) dest[3] = -1.0;
        return dest;
    };

    /**
     * Returns a string representation of a vector
     *
     * @param {vec3} vec Vector to represent as a string
     *
     * @returns {string} String representation of vec
     */
    vec3.str = function (vec) {
        return '[' + vec[0] + ', ' + vec[1] + ', ' + vec[2] + ']';
    };

    /**
     * @class 3x3 Matrix
     * @name mat3
     */
    var mat3 = {};

    /**
     * Creates a new instance of a mat3 using the default array type
     * Any javascript array-like object containing at least 9 numeric elements can serve as a mat3
     *
     * @param {mat3} [mat] mat3 containing values to initialize with
     *
     * @returns {mat3} New mat3
     */
    mat3.create = function (mat) {
        var dest = new MatrixArray(9);

        if (mat) {
            dest[0] = mat[0];
            dest[1] = mat[1];
            dest[2] = mat[2];
            dest[3] = mat[3];
            dest[4] = mat[4];
            dest[5] = mat[5];
            dest[6] = mat[6];
            dest[7] = mat[7];
            dest[8] = mat[8];
        } else {
            dest[0] = dest[1] =
            dest[2] = dest[3] =
            dest[4] = dest[5] =
            dest[6] = dest[7] =
            dest[8] = 0;
        }

        return dest;
    };

    /**
     * Creates a new instance of a mat3, initializing it with the given arguments
     *
     * @param {number} m00
     * @param {number} m01
     * @param {number} m02
     * @param {number} m10
     * @param {number} m11
     * @param {number} m12
     * @param {number} m20
     * @param {number} m21
     * @param {number} m22

     * @returns {mat3} New mat3
     */
    mat3.createFrom = function (m00, m01, m02, m10, m11, m12, m20, m21, m22) {
        var dest = new MatrixArray(9);

        dest[0] = m00;
        dest[1] = m01;
        dest[2] = m02;
        dest[3] = m10;
        dest[4] = m11;
        dest[5] = m12;
        dest[6] = m20;
        dest[7] = m21;
        dest[8] = m22;

        return dest;
    };

    /**
     * Calculates the determinant of a mat3
     *
     * @param {mat3} mat mat3 to calculate determinant of
     *
     * @returns {Number} determinant of mat
     */
    mat3.determinant = function (mat) {
        var a00 = mat[0], a01 = mat[1], a02 = mat[2],
            a10 = mat[3], a11 = mat[4], a12 = mat[5],
            a20 = mat[6], a21 = mat[7], a22 = mat[8];

        return a00 * (a22 * a11 - a12 * a21) + a01 * (-a22 * a10 + a12 * a20) + a02 * (a21 * a10 - a11 * a20);
    };

    /**
     * Calculates the inverse matrix of a mat3
     *
     * @param {mat3} mat mat3 to calculate inverse of
     * @param {mat3} [dest] mat3 receiving inverse matrix. If not specified result is written to mat
     *
     * @param {mat3} dest is specified, mat otherwise, null if matrix cannot be inverted
     */
    mat3.inverse = function (mat, dest) {
        var a00 = mat[0], a01 = mat[1], a02 = mat[2],
            a10 = mat[3], a11 = mat[4], a12 = mat[5],
            a20 = mat[6], a21 = mat[7], a22 = mat[8],

            b01 = a22 * a11 - a12 * a21,
            b11 = -a22 * a10 + a12 * a20,
            b21 = a21 * a10 - a11 * a20,

            d = a00 * b01 + a01 * b11 + a02 * b21,
            id;

        if (!d) { return null; }
        id = 1 / d;

        if (!dest) { dest = mat3.create(); }

        dest[0] = b01 * id;
        dest[1] = (-a22 * a01 + a02 * a21) * id;
        dest[2] = (a12 * a01 - a02 * a11) * id;
        dest[3] = b11 * id;
        dest[4] = (a22 * a00 - a02 * a20) * id;
        dest[5] = (-a12 * a00 + a02 * a10) * id;
        dest[6] = b21 * id;
        dest[7] = (-a21 * a00 + a01 * a20) * id;
        dest[8] = (a11 * a00 - a01 * a10) * id;
        return dest;
    };
    
    /**
     * Performs a matrix multiplication
     *
     * @param {mat3} mat First operand
     * @param {mat3} mat2 Second operand
     * @param {mat3} [dest] mat3 receiving operation result. If not specified result is written to mat
     *
     * @returns {mat3} dest if specified, mat otherwise
     */
    mat3.multiply = function (mat, mat2, dest) {
        if (!dest) { dest = mat; }
        

        // Cache the matrix values (makes for huge speed increases!)
        var a00 = mat[0], a01 = mat[1], a02 = mat[2],
            a10 = mat[3], a11 = mat[4], a12 = mat[5],
            a20 = mat[6], a21 = mat[7], a22 = mat[8],

            b00 = mat2[0], b01 = mat2[1], b02 = mat2[2],
            b10 = mat2[3], b11 = mat2[4], b12 = mat2[5],
            b20 = mat2[6], b21 = mat2[7], b22 = mat2[8];

        dest[0] = b00 * a00 + b01 * a10 + b02 * a20;
        dest[1] = b00 * a01 + b01 * a11 + b02 * a21;
        dest[2] = b00 * a02 + b01 * a12 + b02 * a22;

        dest[3] = b10 * a00 + b11 * a10 + b12 * a20;
        dest[4] = b10 * a01 + b11 * a11 + b12 * a21;
        dest[5] = b10 * a02 + b11 * a12 + b12 * a22;

        dest[6] = b20 * a00 + b21 * a10 + b22 * a20;
        dest[7] = b20 * a01 + b21 * a11 + b22 * a21;
        dest[8] = b20 * a02 + b21 * a12 + b22 * a22;

        return dest;
    };

    /**
     * Transforms the vec2 according to the given mat3.
     *
     * @param {mat3} matrix mat3 to multiply against
     * @param {vec2} vec    the vector to multiply
     * @param {vec2} [dest] an optional receiving vector. If not given, vec is used.
     *
     * @returns {vec2} The multiplication result
     **/
    mat3.multiplyVec2 = function(matrix, vec, dest) {
      if (!dest) dest = vec;
      var x = vec[0], y = vec[1];
      dest[0] = x * matrix[0] + y * matrix[3] + matrix[6];
      dest[1] = x * matrix[1] + y * matrix[4] + matrix[7];
      return dest;
    };

    /**
     * Transforms the vec3 according to the given mat3
     *
     * @param {mat3} matrix mat3 to multiply against
     * @param {vec3} vec    the vector to multiply
     * @param {vec3} [dest] an optional receiving vector. If not given, vec is used.
     *
     * @returns {vec3} The multiplication result
     **/
    mat3.multiplyVec3 = function(matrix, vec, dest) {
      if (!dest) dest = vec;
      var x = vec[0], y = vec[1], z = vec[2];
      dest[0] = x * matrix[0] + y * matrix[3] + z * matrix[6];
      dest[1] = x * matrix[1] + y * matrix[4] + z * matrix[7];
      dest[2] = x * matrix[2] + y * matrix[5] + z * matrix[8];
      
      return dest;
    };

    /**
     * Copies the values of one mat3 to another
     *
     * @param {mat3} mat mat3 containing values to copy
     * @param {mat3} dest mat3 receiving copied values
     *
     * @returns {mat3} dest
     */
    mat3.set = function (mat, dest) {
        dest[0] = mat[0];
        dest[1] = mat[1];
        dest[2] = mat[2];
        dest[3] = mat[3];
        dest[4] = mat[4];
        dest[5] = mat[5];
        dest[6] = mat[6];
        dest[7] = mat[7];
        dest[8] = mat[8];
        return dest;
    };

    /**
     * Compares two matrices for equality within a certain margin of error
     *
     * @param {mat3} a First matrix
     * @param {mat3} b Second matrix
     *
     * @returns {Boolean} True if a is equivalent to b
     */
    mat3.equal = function (a, b) {
        return a === b || (
            Math.abs(a[0] - b[0]) < FLOAT_EPSILON &&
            Math.abs(a[1] - b[1]) < FLOAT_EPSILON &&
            Math.abs(a[2] - b[2]) < FLOAT_EPSILON &&
            Math.abs(a[3] - b[3]) < FLOAT_EPSILON &&
            Math.abs(a[4] - b[4]) < FLOAT_EPSILON &&
            Math.abs(a[5] - b[5]) < FLOAT_EPSILON &&
            Math.abs(a[6] - b[6]) < FLOAT_EPSILON &&
            Math.abs(a[7] - b[7]) < FLOAT_EPSILON &&
            Math.abs(a[8] - b[8]) < FLOAT_EPSILON
        );
    };

    /**
     * Sets a mat3 to an identity matrix
     *
     * @param {mat3} dest mat3 to set
     *
     * @returns dest if specified, otherwise a new mat3
     */
    mat3.identity = function (dest) {
        if (!dest) { dest = mat3.create(); }
        dest[0] = 1;
        dest[1] = 0;
        dest[2] = 0;
        dest[3] = 0;
        dest[4] = 1;
        dest[5] = 0;
        dest[6] = 0;
        dest[7] = 0;
        dest[8] = 1;
        return dest;
    };

    /**
     * Transposes a mat3 (flips the values over the diagonal)
     *
     * Params:
     * @param {mat3} mat mat3 to transpose
     * @param {mat3} [dest] mat3 receiving transposed values. If not specified result is written to mat
     *
     * @returns {mat3} dest is specified, mat otherwise
     */
    mat3.transpose = function (mat, dest) {
        // If we are transposing ourselves we can skip a few steps but have to cache some values
        if (!dest || mat === dest) {
            var a01 = mat[1], a02 = mat[2],
                a12 = mat[5];

            mat[1] = mat[3];
            mat[2] = mat[6];
            mat[3] = a01;
            mat[5] = mat[7];
            mat[6] = a02;
            mat[7] = a12;
            return mat;
        }

        dest[0] = mat[0];
        dest[1] = mat[3];
        dest[2] = mat[6];
        dest[3] = mat[1];
        dest[4] = mat[4];
        dest[5] = mat[7];
        dest[6] = mat[2];
        dest[7] = mat[5];
        dest[8] = mat[8];
        return dest;
    };

    /**
     * Copies the elements of a mat3 into the upper 3x3 elements of a mat4
     *
     * @param {mat3} mat mat3 containing values to copy
     * @param {mat4} [dest] mat4 receiving copied values
     *
     * @returns {mat4} dest if specified, a new mat4 otherwise
     */
    mat3.toMat4 = function (mat, dest) {
        if (!dest) { dest = mat4.create(); }

        dest[15] = 1;
        dest[14] = 0;
        dest[13] = 0;
        dest[12] = 0;

        dest[11] = 0;
        dest[10] = mat[8];
        dest[9] = mat[7];
        dest[8] = mat[6];

        dest[7] = 0;
        dest[6] = mat[5];
        dest[5] = mat[4];
        dest[4] = mat[3];

        dest[3] = 0;
        dest[2] = mat[2];
        dest[1] = mat[1];
        dest[0] = mat[0];

        return dest;
    };

    /**
     * Returns a string representation of a mat3
     *
     * @param {mat3} mat mat3 to represent as a string
     *
     * @param {string} String representation of mat
     */
    mat3.str = function (mat) {
        return '[' + mat[0] + ', ' + mat[1] + ', ' + mat[2] +
            ', ' + mat[3] + ', ' + mat[4] + ', ' + mat[5] +
            ', ' + mat[6] + ', ' + mat[7] + ', ' + mat[8] + ']';
    };

    /**
     * @class 4x4 Matrix
     * @name mat4
     */
    var mat4 = {};

    /**
     * Creates a new instance of a mat4 using the default array type
     * Any javascript array-like object containing at least 16 numeric elements can serve as a mat4
     *
     * @param {mat4} [mat] mat4 containing values to initialize with
     *
     * @returns {mat4} New mat4
     */
    mat4.create = function (mat) {
        var dest = new MatrixArray(16);

        if (mat) {
            dest[0] = mat[0];
            dest[1] = mat[1];
            dest[2] = mat[2];
            dest[3] = mat[3];
            dest[4] = mat[4];
            dest[5] = mat[5];
            dest[6] = mat[6];
            dest[7] = mat[7];
            dest[8] = mat[8];
            dest[9] = mat[9];
            dest[10] = mat[10];
            dest[11] = mat[11];
            dest[12] = mat[12];
            dest[13] = mat[13];
            dest[14] = mat[14];
            dest[15] = mat[15];
        }

        return dest;
    };

    /**
     * Creates a new instance of a mat4, initializing it with the given arguments
     *
     * @param {number} m00
     * @param {number} m01
     * @param {number} m02
     * @param {number} m03
     * @param {number} m10
     * @param {number} m11
     * @param {number} m12
     * @param {number} m13
     * @param {number} m20
     * @param {number} m21
     * @param {number} m22
     * @param {number} m23
     * @param {number} m30
     * @param {number} m31
     * @param {number} m32
     * @param {number} m33

     * @returns {mat4} New mat4
     */
    mat4.createFrom = function (m00, m01, m02, m03, m10, m11, m12, m13, m20, m21, m22, m23, m30, m31, m32, m33) {
        var dest = new MatrixArray(16);

        dest[0] = m00;
        dest[1] = m01;
        dest[2] = m02;
        dest[3] = m03;
        dest[4] = m10;
        dest[5] = m11;
        dest[6] = m12;
        dest[7] = m13;
        dest[8] = m20;
        dest[9] = m21;
        dest[10] = m22;
        dest[11] = m23;
        dest[12] = m30;
        dest[13] = m31;
        dest[14] = m32;
        dest[15] = m33;

        return dest;
    };

    /**
     * Copies the values of one mat4 to another
     *
     * @param {mat4} mat mat4 containing values to copy
     * @param {mat4} dest mat4 receiving copied values
     *
     * @returns {mat4} dest
     */
    mat4.set = function (mat, dest) {
        dest[0] = mat[0];
        dest[1] = mat[1];
        dest[2] = mat[2];
        dest[3] = mat[3];
        dest[4] = mat[4];
        dest[5] = mat[5];
        dest[6] = mat[6];
        dest[7] = mat[7];
        dest[8] = mat[8];
        dest[9] = mat[9];
        dest[10] = mat[10];
        dest[11] = mat[11];
        dest[12] = mat[12];
        dest[13] = mat[13];
        dest[14] = mat[14];
        dest[15] = mat[15];
        return dest;
    };

    /**
     * Compares two matrices for equality within a certain margin of error
     *
     * @param {mat4} a First matrix
     * @param {mat4} b Second matrix
     *
     * @returns {Boolean} True if a is equivalent to b
     */
    mat4.equal = function (a, b) {
        return a === b || (
            Math.abs(a[0] - b[0]) < FLOAT_EPSILON &&
            Math.abs(a[1] - b[1]) < FLOAT_EPSILON &&
            Math.abs(a[2] - b[2]) < FLOAT_EPSILON &&
            Math.abs(a[3] - b[3]) < FLOAT_EPSILON &&
            Math.abs(a[4] - b[4]) < FLOAT_EPSILON &&
            Math.abs(a[5] - b[5]) < FLOAT_EPSILON &&
            Math.abs(a[6] - b[6]) < FLOAT_EPSILON &&
            Math.abs(a[7] - b[7]) < FLOAT_EPSILON &&
            Math.abs(a[8] - b[8]) < FLOAT_EPSILON &&
            Math.abs(a[9] - b[9]) < FLOAT_EPSILON &&
            Math.abs(a[10] - b[10]) < FLOAT_EPSILON &&
            Math.abs(a[11] - b[11]) < FLOAT_EPSILON &&
            Math.abs(a[12] - b[12]) < FLOAT_EPSILON &&
            Math.abs(a[13] - b[13]) < FLOAT_EPSILON &&
            Math.abs(a[14] - b[14]) < FLOAT_EPSILON &&
            Math.abs(a[15] - b[15]) < FLOAT_EPSILON
        );
    };

    /**
     * Sets a mat4 to an identity matrix
     *
     * @param {mat4} dest mat4 to set
     *
     * @returns {mat4} dest
     */
    mat4.identity = function (dest) {
        if (!dest) { dest = mat4.create(); }
        dest[0] = 1;
        dest[1] = 0;
        dest[2] = 0;
        dest[3] = 0;
        dest[4] = 0;
        dest[5] = 1;
        dest[6] = 0;
        dest[7] = 0;
        dest[8] = 0;
        dest[9] = 0;
        dest[10] = 1;
        dest[11] = 0;
        dest[12] = 0;
        dest[13] = 0;
        dest[14] = 0;
        dest[15] = 1;
        return dest;
    };

    /**
     * Transposes a mat4 (flips the values over the diagonal)
     *
     * @param {mat4} mat mat4 to transpose
     * @param {mat4} [dest] mat4 receiving transposed values. If not specified result is written to mat
     *
     * @param {mat4} dest is specified, mat otherwise
     */
    mat4.transpose = function (mat, dest) {
        // If we are transposing ourselves we can skip a few steps but have to cache some values
        if (!dest || mat === dest) {
            var a01 = mat[1], a02 = mat[2], a03 = mat[3],
                a12 = mat[6], a13 = mat[7],
                a23 = mat[11];

            mat[1] = mat[4];
            mat[2] = mat[8];
            mat[3] = mat[12];
            mat[4] = a01;
            mat[6] = mat[9];
            mat[7] = mat[13];
            mat[8] = a02;
            mat[9] = a12;
            mat[11] = mat[14];
            mat[12] = a03;
            mat[13] = a13;
            mat[14] = a23;
            return mat;
        }

        dest[0] = mat[0];
        dest[1] = mat[4];
        dest[2] = mat[8];
        dest[3] = mat[12];
        dest[4] = mat[1];
        dest[5] = mat[5];
        dest[6] = mat[9];
        dest[7] = mat[13];
        dest[8] = mat[2];
        dest[9] = mat[6];
        dest[10] = mat[10];
        dest[11] = mat[14];
        dest[12] = mat[3];
        dest[13] = mat[7];
        dest[14] = mat[11];
        dest[15] = mat[15];
        return dest;
    };

    /**
     * Calculates the determinant of a mat4
     *
     * @param {mat4} mat mat4 to calculate determinant of
     *
     * @returns {number} determinant of mat
     */
    mat4.determinant = function (mat) {
        // Cache the matrix values (makes for huge speed increases!)
        var a00 = mat[0], a01 = mat[1], a02 = mat[2], a03 = mat[3],
            a10 = mat[4], a11 = mat[5], a12 = mat[6], a13 = mat[7],
            a20 = mat[8], a21 = mat[9], a22 = mat[10], a23 = mat[11],
            a30 = mat[12], a31 = mat[13], a32 = mat[14], a33 = mat[15];

        return (a30 * a21 * a12 * a03 - a20 * a31 * a12 * a03 - a30 * a11 * a22 * a03 + a10 * a31 * a22 * a03 +
                a20 * a11 * a32 * a03 - a10 * a21 * a32 * a03 - a30 * a21 * a02 * a13 + a20 * a31 * a02 * a13 +
                a30 * a01 * a22 * a13 - a00 * a31 * a22 * a13 - a20 * a01 * a32 * a13 + a00 * a21 * a32 * a13 +
                a30 * a11 * a02 * a23 - a10 * a31 * a02 * a23 - a30 * a01 * a12 * a23 + a00 * a31 * a12 * a23 +
                a10 * a01 * a32 * a23 - a00 * a11 * a32 * a23 - a20 * a11 * a02 * a33 + a10 * a21 * a02 * a33 +
                a20 * a01 * a12 * a33 - a00 * a21 * a12 * a33 - a10 * a01 * a22 * a33 + a00 * a11 * a22 * a33);
    };

    /**
     * Calculates the inverse matrix of a mat4
     *
     * @param {mat4} mat mat4 to calculate inverse of
     * @param {mat4} [dest] mat4 receiving inverse matrix. If not specified result is written to mat
     *
     * @param {mat4} dest is specified, mat otherwise, null if matrix cannot be inverted
     */
    mat4.inverse = function (mat, dest) {
        if (!dest) { dest = mat; }

        // Cache the matrix values (makes for huge speed increases!)
        var a00 = mat[0], a01 = mat[1], a02 = mat[2], a03 = mat[3],
            a10 = mat[4], a11 = mat[5], a12 = mat[6], a13 = mat[7],
            a20 = mat[8], a21 = mat[9], a22 = mat[10], a23 = mat[11],
            a30 = mat[12], a31 = mat[13], a32 = mat[14], a33 = mat[15],

            b00 = a00 * a11 - a01 * a10,
            b01 = a00 * a12 - a02 * a10,
            b02 = a00 * a13 - a03 * a10,
            b03 = a01 * a12 - a02 * a11,
            b04 = a01 * a13 - a03 * a11,
            b05 = a02 * a13 - a03 * a12,
            b06 = a20 * a31 - a21 * a30,
            b07 = a20 * a32 - a22 * a30,
            b08 = a20 * a33 - a23 * a30,
            b09 = a21 * a32 - a22 * a31,
            b10 = a21 * a33 - a23 * a31,
            b11 = a22 * a33 - a23 * a32,

            d = (b00 * b11 - b01 * b10 + b02 * b09 + b03 * b08 - b04 * b07 + b05 * b06),
            invDet;

            // Calculate the determinant
            if (!d) { return null; }
            invDet = 1 / d;

        dest[0] = (a11 * b11 - a12 * b10 + a13 * b09) * invDet;
        dest[1] = (-a01 * b11 + a02 * b10 - a03 * b09) * invDet;
        dest[2] = (a31 * b05 - a32 * b04 + a33 * b03) * invDet;
        dest[3] = (-a21 * b05 + a22 * b04 - a23 * b03) * invDet;
        dest[4] = (-a10 * b11 + a12 * b08 - a13 * b07) * invDet;
        dest[5] = (a00 * b11 - a02 * b08 + a03 * b07) * invDet;
        dest[6] = (-a30 * b05 + a32 * b02 - a33 * b01) * invDet;
        dest[7] = (a20 * b05 - a22 * b02 + a23 * b01) * invDet;
        dest[8] = (a10 * b10 - a11 * b08 + a13 * b06) * invDet;
        dest[9] = (-a00 * b10 + a01 * b08 - a03 * b06) * invDet;
        dest[10] = (a30 * b04 - a31 * b02 + a33 * b00) * invDet;
        dest[11] = (-a20 * b04 + a21 * b02 - a23 * b00) * invDet;
        dest[12] = (-a10 * b09 + a11 * b07 - a12 * b06) * invDet;
        dest[13] = (a00 * b09 - a01 * b07 + a02 * b06) * invDet;
        dest[14] = (-a30 * b03 + a31 * b01 - a32 * b00) * invDet;
        dest[15] = (a20 * b03 - a21 * b01 + a22 * b00) * invDet;

        return dest;
    };

    /**
     * Copies the upper 3x3 elements of a mat4 into another mat4
     *
     * @param {mat4} mat mat4 containing values to copy
     * @param {mat4} [dest] mat4 receiving copied values
     *
     * @returns {mat4} dest is specified, a new mat4 otherwise
     */
    mat4.toRotationMat = function (mat, dest) {
        if (!dest) { dest = mat4.create(); }

        dest[0] = mat[0];
        dest[1] = mat[1];
        dest[2] = mat[2];
        dest[3] = mat[3];
        dest[4] = mat[4];
        dest[5] = mat[5];
        dest[6] = mat[6];
        dest[7] = mat[7];
        dest[8] = mat[8];
        dest[9] = mat[9];
        dest[10] = mat[10];
        dest[11] = mat[11];
        dest[12] = 0;
        dest[13] = 0;
        dest[14] = 0;
        dest[15] = 1;

        return dest;
    };

    /**
     * Copies the upper 3x3 elements of a mat4 into a mat3
     *
     * @param {mat4} mat mat4 containing values to copy
     * @param {mat3} [dest] mat3 receiving copied values
     *
     * @returns {mat3} dest is specified, a new mat3 otherwise
     */
    mat4.toMat3 = function (mat, dest) {
        if (!dest) { dest = mat3.create(); }

        dest[0] = mat[0];
        dest[1] = mat[1];
        dest[2] = mat[2];
        dest[3] = mat[4];
        dest[4] = mat[5];
        dest[5] = mat[6];
        dest[6] = mat[8];
        dest[7] = mat[9];
        dest[8] = mat[10];

        return dest;
    };

    /**
     * Calculates the inverse of the upper 3x3 elements of a mat4 and copies the result into a mat3
     * The resulting matrix is useful for calculating transformed normals
     *
     * Params:
     * @param {mat4} mat mat4 containing values to invert and copy
     * @param {mat3} [dest] mat3 receiving values
     *
     * @returns {mat3} dest is specified, a new mat3 otherwise, null if the matrix cannot be inverted
     */
    mat4.toInverseMat3 = function (mat, dest) {
        // Cache the matrix values (makes for huge speed increases!)
        var a00 = mat[0], a01 = mat[1], a02 = mat[2],
            a10 = mat[4], a11 = mat[5], a12 = mat[6],
            a20 = mat[8], a21 = mat[9], a22 = mat[10],

            b01 = a22 * a11 - a12 * a21,
            b11 = -a22 * a10 + a12 * a20,
            b21 = a21 * a10 - a11 * a20,

            d = a00 * b01 + a01 * b11 + a02 * b21,
            id;

        if (!d) { return null; }
        id = 1 / d;

        if (!dest) { dest = mat3.create(); }

        dest[0] = b01 * id;
        dest[1] = (-a22 * a01 + a02 * a21) * id;
        dest[2] = (a12 * a01 - a02 * a11) * id;
        dest[3] = b11 * id;
        dest[4] = (a22 * a00 - a02 * a20) * id;
        dest[5] = (-a12 * a00 + a02 * a10) * id;
        dest[6] = b21 * id;
        dest[7] = (-a21 * a00 + a01 * a20) * id;
        dest[8] = (a11 * a00 - a01 * a10) * id;

        return dest;
    };

    /**
     * Performs a matrix multiplication
     *
     * @param {mat4} mat First operand
     * @param {mat4} mat2 Second operand
     * @param {mat4} [dest] mat4 receiving operation result. If not specified result is written to mat
     *
     * @returns {mat4} dest if specified, mat otherwise
     */
    mat4.multiply = function (mat, mat2, dest) {
        if (!dest) { dest = mat; }

        // Cache the matrix values (makes for huge speed increases!)
        var a00 = mat[ 0], a01 = mat[ 1], a02 = mat[ 2], a03 = mat[3];
        var a10 = mat[ 4], a11 = mat[ 5], a12 = mat[ 6], a13 = mat[7];
        var a20 = mat[ 8], a21 = mat[ 9], a22 = mat[10], a23 = mat[11];
        var a30 = mat[12], a31 = mat[13], a32 = mat[14], a33 = mat[15];

        // Cache only the current line of the second matrix
        var b0  = mat2[0], b1 = mat2[1], b2 = mat2[2], b3 = mat2[3];  
        dest[0] = b0*a00 + b1*a10 + b2*a20 + b3*a30;
        dest[1] = b0*a01 + b1*a11 + b2*a21 + b3*a31;
        dest[2] = b0*a02 + b1*a12 + b2*a22 + b3*a32;
        dest[3] = b0*a03 + b1*a13 + b2*a23 + b3*a33;

        b0 = mat2[4];
        b1 = mat2[5];
        b2 = mat2[6];
        b3 = mat2[7];
        dest[4] = b0*a00 + b1*a10 + b2*a20 + b3*a30;
        dest[5] = b0*a01 + b1*a11 + b2*a21 + b3*a31;
        dest[6] = b0*a02 + b1*a12 + b2*a22 + b3*a32;
        dest[7] = b0*a03 + b1*a13 + b2*a23 + b3*a33;

        b0 = mat2[8];
        b1 = mat2[9];
        b2 = mat2[10];
        b3 = mat2[11];
        dest[8] = b0*a00 + b1*a10 + b2*a20 + b3*a30;
        dest[9] = b0*a01 + b1*a11 + b2*a21 + b3*a31;
        dest[10] = b0*a02 + b1*a12 + b2*a22 + b3*a32;
        dest[11] = b0*a03 + b1*a13 + b2*a23 + b3*a33;

        b0 = mat2[12];
        b1 = mat2[13];
        b2 = mat2[14];
        b3 = mat2[15];
        dest[12] = b0*a00 + b1*a10 + b2*a20 + b3*a30;
        dest[13] = b0*a01 + b1*a11 + b2*a21 + b3*a31;
        dest[14] = b0*a02 + b1*a12 + b2*a22 + b3*a32;
        dest[15] = b0*a03 + b1*a13 + b2*a23 + b3*a33;

        return dest;
    };

    /**
     * Transforms a vec3 with the given matrix
     * 4th vector component is implicitly '1'
     *
     * @param {mat4} mat mat4 to transform the vector with
     * @param {vec3} vec vec3 to transform
     * @param {vec3} [dest] vec3 receiving operation result. If not specified result is written to vec
     *
     * @returns {vec3} dest if specified, vec otherwise
     */
    mat4.multiplyVec3 = function (mat, vec, dest) {
        if (!dest) { dest = vec; }

        var x = vec[0], y = vec[1], z = vec[2];

        dest[0] = mat[0] * x + mat[4] * y + mat[8] * z + mat[12];
        dest[1] = mat[1] * x + mat[5] * y + mat[9] * z + mat[13];
        dest[2] = mat[2] * x + mat[6] * y + mat[10] * z + mat[14];

        return dest;
    };

    /**
     * Transforms a vec4 with the given matrix
     *
     * @param {mat4} mat mat4 to transform the vector with
     * @param {vec4} vec vec4 to transform
     * @param {vec4} [dest] vec4 receiving operation result. If not specified result is written to vec
     *
     * @returns {vec4} dest if specified, vec otherwise
     */
    mat4.multiplyVec4 = function (mat, vec, dest) {
        if (!dest) { dest = vec; }

        var x = vec[0], y = vec[1], z = vec[2], w = vec[3];

        dest[0] = mat[0] * x + mat[4] * y + mat[8] * z + mat[12] * w;
        dest[1] = mat[1] * x + mat[5] * y + mat[9] * z + mat[13] * w;
        dest[2] = mat[2] * x + mat[6] * y + mat[10] * z + mat[14] * w;
        dest[3] = mat[3] * x + mat[7] * y + mat[11] * z + mat[15] * w;

        return dest;
    };

    /**
     * Translates a matrix by the given vector
     *
     * @param {mat4} mat mat4 to translate
     * @param {vec3} vec vec3 specifying the translation
     * @param {mat4} [dest] mat4 receiving operation result. If not specified result is written to mat
     *
     * @returns {mat4} dest if specified, mat otherwise
     */
    mat4.translate = function (mat, vec, dest) {
        var x = vec[0], y = vec[1], z = vec[2],
            a00, a01, a02, a03,
            a10, a11, a12, a13,
            a20, a21, a22, a23;

        if (!dest || mat === dest) {
            mat[12] = mat[0] * x + mat[4] * y + mat[8] * z + mat[12];
            mat[13] = mat[1] * x + mat[5] * y + mat[9] * z + mat[13];
            mat[14] = mat[2] * x + mat[6] * y + mat[10] * z + mat[14];
            mat[15] = mat[3] * x + mat[7] * y + mat[11] * z + mat[15];
            return mat;
        }

        a00 = mat[0]; a01 = mat[1]; a02 = mat[2]; a03 = mat[3];
        a10 = mat[4]; a11 = mat[5]; a12 = mat[6]; a13 = mat[7];
        a20 = mat[8]; a21 = mat[9]; a22 = mat[10]; a23 = mat[11];

        dest[0] = a00; dest[1] = a01; dest[2] = a02; dest[3] = a03;
        dest[4] = a10; dest[5] = a11; dest[6] = a12; dest[7] = a13;
        dest[8] = a20; dest[9] = a21; dest[10] = a22; dest[11] = a23;

        dest[12] = a00 * x + a10 * y + a20 * z + mat[12];
        dest[13] = a01 * x + a11 * y + a21 * z + mat[13];
        dest[14] = a02 * x + a12 * y + a22 * z + mat[14];
        dest[15] = a03 * x + a13 * y + a23 * z + mat[15];
        return dest;
    };

    /**
     * Scales a matrix by the given vector
     *
     * @param {mat4} mat mat4 to scale
     * @param {vec3} vec vec3 specifying the scale for each axis
     * @param {mat4} [dest] mat4 receiving operation result. If not specified result is written to mat
     *
     * @param {mat4} dest if specified, mat otherwise
     */
    mat4.scale = function (mat, vec, dest) {
        var x = vec[0], y = vec[1], z = vec[2];

        if (!dest || mat === dest) {
            mat[0] *= x;
            mat[1] *= x;
            mat[2] *= x;
            mat[3] *= x;
            mat[4] *= y;
            mat[5] *= y;
            mat[6] *= y;
            mat[7] *= y;
            mat[8] *= z;
            mat[9] *= z;
            mat[10] *= z;
            mat[11] *= z;
            return mat;
        }

        dest[0] = mat[0] * x;
        dest[1] = mat[1] * x;
        dest[2] = mat[2] * x;
        dest[3] = mat[3] * x;
        dest[4] = mat[4] * y;
        dest[5] = mat[5] * y;
        dest[6] = mat[6] * y;
        dest[7] = mat[7] * y;
        dest[8] = mat[8] * z;
        dest[9] = mat[9] * z;
        dest[10] = mat[10] * z;
        dest[11] = mat[11] * z;
        dest[12] = mat[12];
        dest[13] = mat[13];
        dest[14] = mat[14];
        dest[15] = mat[15];
        return dest;
    };

    /**
     * Rotates a matrix by the given angle around the specified axis
     * If rotating around a primary axis (X,Y,Z) one of the specialized rotation functions should be used instead for performance
     *
     * @param {mat4} mat mat4 to rotate
     * @param {number} angle Angle (in radians) to rotate
     * @param {vec3} axis vec3 representing the axis to rotate around
     * @param {mat4} [dest] mat4 receiving operation result. If not specified result is written to mat
     *
     * @returns {mat4} dest if specified, mat otherwise
     */
    mat4.rotate = function (mat, angle, axis, dest) {
        var x = axis[0], y = axis[1], z = axis[2],
            len = Math.sqrt(x * x + y * y + z * z),
            s, c, t,
            a00, a01, a02, a03,
            a10, a11, a12, a13,
            a20, a21, a22, a23,
            b00, b01, b02,
            b10, b11, b12,
            b20, b21, b22;

        if (!len) { return null; }
        if (len !== 1) {
            len = 1 / len;
            x *= len;
            y *= len;
            z *= len;
        }

        s = Math.sin(angle);
        c = Math.cos(angle);
        t = 1 - c;

        a00 = mat[0]; a01 = mat[1]; a02 = mat[2]; a03 = mat[3];
        a10 = mat[4]; a11 = mat[5]; a12 = mat[6]; a13 = mat[7];
        a20 = mat[8]; a21 = mat[9]; a22 = mat[10]; a23 = mat[11];

        // Construct the elements of the rotation matrix
        b00 = x * x * t + c; b01 = y * x * t + z * s; b02 = z * x * t - y * s;
        b10 = x * y * t - z * s; b11 = y * y * t + c; b12 = z * y * t + x * s;
        b20 = x * z * t + y * s; b21 = y * z * t - x * s; b22 = z * z * t + c;

        if (!dest) {
            dest = mat;
        } else if (mat !== dest) { // If the source and destination differ, copy the unchanged last row
            dest[12] = mat[12];
            dest[13] = mat[13];
            dest[14] = mat[14];
            dest[15] = mat[15];
        }

        // Perform rotation-specific matrix multiplication
        dest[0] = a00 * b00 + a10 * b01 + a20 * b02;
        dest[1] = a01 * b00 + a11 * b01 + a21 * b02;
        dest[2] = a02 * b00 + a12 * b01 + a22 * b02;
        dest[3] = a03 * b00 + a13 * b01 + a23 * b02;

        dest[4] = a00 * b10 + a10 * b11 + a20 * b12;
        dest[5] = a01 * b10 + a11 * b11 + a21 * b12;
        dest[6] = a02 * b10 + a12 * b11 + a22 * b12;
        dest[7] = a03 * b10 + a13 * b11 + a23 * b12;

        dest[8] = a00 * b20 + a10 * b21 + a20 * b22;
        dest[9] = a01 * b20 + a11 * b21 + a21 * b22;
        dest[10] = a02 * b20 + a12 * b21 + a22 * b22;
        dest[11] = a03 * b20 + a13 * b21 + a23 * b22;
        return dest;
    };

    /**
     * Rotates a matrix by the given angle around the X axis
     *
     * @param {mat4} mat mat4 to rotate
     * @param {number} angle Angle (in radians) to rotate
     * @param {mat4} [dest] mat4 receiving operation result. If not specified result is written to mat
     *
     * @returns {mat4} dest if specified, mat otherwise
     */
    mat4.rotateX = function (mat, angle, dest) {
        var s = Math.sin(angle),
            c = Math.cos(angle),
            a10 = mat[4],
            a11 = mat[5],
            a12 = mat[6],
            a13 = mat[7],
            a20 = mat[8],
            a21 = mat[9],
            a22 = mat[10],
            a23 = mat[11];

        if (!dest) {
            dest = mat;
        } else if (mat !== dest) { // If the source and destination differ, copy the unchanged rows
            dest[0] = mat[0];
            dest[1] = mat[1];
            dest[2] = mat[2];
            dest[3] = mat[3];

            dest[12] = mat[12];
            dest[13] = mat[13];
            dest[14] = mat[14];
            dest[15] = mat[15];
        }

        // Perform axis-specific matrix multiplication
        dest[4] = a10 * c + a20 * s;
        dest[5] = a11 * c + a21 * s;
        dest[6] = a12 * c + a22 * s;
        dest[7] = a13 * c + a23 * s;

        dest[8] = a10 * -s + a20 * c;
        dest[9] = a11 * -s + a21 * c;
        dest[10] = a12 * -s + a22 * c;
        dest[11] = a13 * -s + a23 * c;
        return dest;
    };

    /**
     * Rotates a matrix by the given angle around the Y axis
     *
     * @param {mat4} mat mat4 to rotate
     * @param {number} angle Angle (in radians) to rotate
     * @param {mat4} [dest] mat4 receiving operation result. If not specified result is written to mat
     *
     * @returns {mat4} dest if specified, mat otherwise
     */
    mat4.rotateY = function (mat, angle, dest) {
        var s = Math.sin(angle),
            c = Math.cos(angle),
            a00 = mat[0],
            a01 = mat[1],
            a02 = mat[2],
            a03 = mat[3],
            a20 = mat[8],
            a21 = mat[9],
            a22 = mat[10],
            a23 = mat[11];

        if (!dest) {
            dest = mat;
        } else if (mat !== dest) { // If the source and destination differ, copy the unchanged rows
            dest[4] = mat[4];
            dest[5] = mat[5];
            dest[6] = mat[6];
            dest[7] = mat[7];

            dest[12] = mat[12];
            dest[13] = mat[13];
            dest[14] = mat[14];
            dest[15] = mat[15];
        }

        // Perform axis-specific matrix multiplication
        dest[0] = a00 * c + a20 * -s;
        dest[1] = a01 * c + a21 * -s;
        dest[2] = a02 * c + a22 * -s;
        dest[3] = a03 * c + a23 * -s;

        dest[8] = a00 * s + a20 * c;
        dest[9] = a01 * s + a21 * c;
        dest[10] = a02 * s + a22 * c;
        dest[11] = a03 * s + a23 * c;
        return dest;
    };

    /**
     * Rotates a matrix by the given angle around the Z axis
     *
     * @param {mat4} mat mat4 to rotate
     * @param {number} angle Angle (in radians) to rotate
     * @param {mat4} [dest] mat4 receiving operation result. If not specified result is written to mat
     *
     * @returns {mat4} dest if specified, mat otherwise
     */
    mat4.rotateZ = function (mat, angle, dest) {
        var s = Math.sin(angle),
            c = Math.cos(angle),
            a00 = mat[0],
            a01 = mat[1],
            a02 = mat[2],
            a03 = mat[3],
            a10 = mat[4],
            a11 = mat[5],
            a12 = mat[6],
            a13 = mat[7];

        if (!dest) {
            dest = mat;
        } else if (mat !== dest) { // If the source and destination differ, copy the unchanged last row
            dest[8] = mat[8];
            dest[9] = mat[9];
            dest[10] = mat[10];
            dest[11] = mat[11];

            dest[12] = mat[12];
            dest[13] = mat[13];
            dest[14] = mat[14];
            dest[15] = mat[15];
        }

        // Perform axis-specific matrix multiplication
        dest[0] = a00 * c + a10 * s;
        dest[1] = a01 * c + a11 * s;
        dest[2] = a02 * c + a12 * s;
        dest[3] = a03 * c + a13 * s;

        dest[4] = a00 * -s + a10 * c;
        dest[5] = a01 * -s + a11 * c;
        dest[6] = a02 * -s + a12 * c;
        dest[7] = a03 * -s + a13 * c;

        return dest;
    };

    /**
     * Generates a frustum matrix with the given bounds
     *
     * @param {number} left Left bound of the frustum
     * @param {number} right Right bound of the frustum
     * @param {number} bottom Bottom bound of the frustum
     * @param {number} top Top bound of the frustum
     * @param {number} near Near bound of the frustum
     * @param {number} far Far bound of the frustum
     * @param {mat4} [dest] mat4 frustum matrix will be written into
     *
     * @returns {mat4} dest if specified, a new mat4 otherwise
     */
    mat4.frustum = function (left, right, bottom, top, near, far, dest) {
        if (!dest) { dest = mat4.create(); }
        var rl = (right - left),
            tb = (top - bottom),
            fn = (far - near);
        dest[0] = (near * 2) / rl;
        dest[1] = 0;
        dest[2] = 0;
        dest[3] = 0;
        dest[4] = 0;
        dest[5] = (near * 2) / tb;
        dest[6] = 0;
        dest[7] = 0;
        dest[8] = (right + left) / rl;
        dest[9] = (top + bottom) / tb;
        dest[10] = -(far + near) / fn;
        dest[11] = -1;
        dest[12] = 0;
        dest[13] = 0;
        dest[14] = -(far * near * 2) / fn;
        dest[15] = 0;
        return dest;
    };

    /**
     * Generates a perspective projection matrix with the given bounds
     *
     * @param {number} fovy Vertical field of view
     * @param {number} aspect Aspect ratio. typically viewport width/height
     * @param {number} near Near bound of the frustum
     * @param {number} far Far bound of the frustum
     * @param {mat4} [dest] mat4 frustum matrix will be written into
     *
     * @returns {mat4} dest if specified, a new mat4 otherwise
     */
    mat4.perspective = function (fovy, aspect, near, far, dest) {
        var top = near * Math.tan(fovy * Math.PI / 360.0),
            right = top * aspect;
        return mat4.frustum(-right, right, -top, top, near, far, dest);
    };

    /**
     * Generates a orthogonal projection matrix with the given bounds
     *
     * @param {number} left Left bound of the frustum
     * @param {number} right Right bound of the frustum
     * @param {number} bottom Bottom bound of the frustum
     * @param {number} top Top bound of the frustum
     * @param {number} near Near bound of the frustum
     * @param {number} far Far bound of the frustum
     * @param {mat4} [dest] mat4 frustum matrix will be written into
     *
     * @returns {mat4} dest if specified, a new mat4 otherwise
     */
    mat4.ortho = function (left, right, bottom, top, near, far, dest) {
        if (!dest) { dest = mat4.create(); }
        var rl = (right - left),
            tb = (top - bottom),
            fn = (far - near);
        dest[0] = 2 / rl;
        dest[1] = 0;
        dest[2] = 0;
        dest[3] = 0;
        dest[4] = 0;
        dest[5] = 2 / tb;
        dest[6] = 0;
        dest[7] = 0;
        dest[8] = 0;
        dest[9] = 0;
        dest[10] = -2 / fn;
        dest[11] = 0;
        dest[12] = -(left + right) / rl;
        dest[13] = -(top + bottom) / tb;
        dest[14] = -(far + near) / fn;
        dest[15] = 1;
        return dest;
    };

    /**
     * Generates a look-at matrix with the given eye position, focal point, and up axis
     *
     * @param {vec3} eye Position of the viewer
     * @param {vec3} center Point the viewer is looking at
     * @param {vec3} up vec3 pointing "up"
     * @param {mat4} [dest] mat4 frustum matrix will be written into
     *
     * @returns {mat4} dest if specified, a new mat4 otherwise
     */
    mat4.lookAt = function (eye, center, up, dest) {
        if (!dest) { dest = mat4.create(); }

        var x0, x1, x2, y0, y1, y2, z0, z1, z2, len,
            eyex = eye[0],
            eyey = eye[1],
            eyez = eye[2],
            upx = up[0],
            upy = up[1],
            upz = up[2],
            centerx = center[0],
            centery = center[1],
            centerz = center[2];

        if (eyex === centerx && eyey === centery && eyez === centerz) {
            return mat4.identity(dest);
        }

        //vec3.direction(eye, center, z);
        z0 = eyex - centerx;
        z1 = eyey - centery;
        z2 = eyez - centerz;

        // normalize (no check needed for 0 because of early return)
        len = 1 / Math.sqrt(z0 * z0 + z1 * z1 + z2 * z2);
        z0 *= len;
        z1 *= len;
        z2 *= len;

        //vec3.normalize(vec3.cross(up, z, x));
        x0 = upy * z2 - upz * z1;
        x1 = upz * z0 - upx * z2;
        x2 = upx * z1 - upy * z0;
        len = Math.sqrt(x0 * x0 + x1 * x1 + x2 * x2);
        if (!len) {
            x0 = 0;
            x1 = 0;
            x2 = 0;
        } else {
            len = 1 / len;
            x0 *= len;
            x1 *= len;
            x2 *= len;
        }

        //vec3.normalize(vec3.cross(z, x, y));
        y0 = z1 * x2 - z2 * x1;
        y1 = z2 * x0 - z0 * x2;
        y2 = z0 * x1 - z1 * x0;

        len = Math.sqrt(y0 * y0 + y1 * y1 + y2 * y2);
        if (!len) {
            y0 = 0;
            y1 = 0;
            y2 = 0;
        } else {
            len = 1 / len;
            y0 *= len;
            y1 *= len;
            y2 *= len;
        }

        dest[0] = x0;
        dest[1] = y0;
        dest[2] = z0;
        dest[3] = 0;
        dest[4] = x1;
        dest[5] = y1;
        dest[6] = z1;
        dest[7] = 0;
        dest[8] = x2;
        dest[9] = y2;
        dest[10] = z2;
        dest[11] = 0;
        dest[12] = -(x0 * eyex + x1 * eyey + x2 * eyez);
        dest[13] = -(y0 * eyex + y1 * eyey + y2 * eyez);
        dest[14] = -(z0 * eyex + z1 * eyey + z2 * eyez);
        dest[15] = 1;

        return dest;
    };

    /**
     * Creates a matrix from a quaternion rotation and vector translation
     * This is equivalent to (but much faster than):
     *
     *     mat4.identity(dest);
     *     mat4.translate(dest, vec);
     *     var quatMat = mat4.create();
     *     quat4.toMat4(quat, quatMat);
     *     mat4.multiply(dest, quatMat);
     *
     * @param {quat4} quat Rotation quaternion
     * @param {vec3} vec Translation vector
     * @param {mat4} [dest] mat4 receiving operation result. If not specified result is written to a new mat4
     *
     * @returns {mat4} dest if specified, a new mat4 otherwise
     */
    mat4.fromRotationTranslation = function (quat, vec, dest) {
        if (!dest) { dest = mat4.create(); }

        // Quaternion math
        var x = quat[0], y = quat[1], z = quat[2], w = quat[3],
            x2 = x + x,
            y2 = y + y,
            z2 = z + z,

            xx = x * x2,
            xy = x * y2,
            xz = x * z2,
            yy = y * y2,
            yz = y * z2,
            zz = z * z2,
            wx = w * x2,
            wy = w * y2,
            wz = w * z2;

        dest[0] = 1 - (yy + zz);
        dest[1] = xy + wz;
        dest[2] = xz - wy;
        dest[3] = 0;
        dest[4] = xy - wz;
        dest[5] = 1 - (xx + zz);
        dest[6] = yz + wx;
        dest[7] = 0;
        dest[8] = xz + wy;
        dest[9] = yz - wx;
        dest[10] = 1 - (xx + yy);
        dest[11] = 0;
        dest[12] = vec[0];
        dest[13] = vec[1];
        dest[14] = vec[2];
        dest[15] = 1;
        
        return dest;
    };

    /**
     * Returns a string representation of a mat4
     *
     * @param {mat4} mat mat4 to represent as a string
     *
     * @returns {string} String representation of mat
     */
    mat4.str = function (mat) {
        return '[' + mat[0] + ', ' + mat[1] + ', ' + mat[2] + ', ' + mat[3] +
            ', ' + mat[4] + ', ' + mat[5] + ', ' + mat[6] + ', ' + mat[7] +
            ', ' + mat[8] + ', ' + mat[9] + ', ' + mat[10] + ', ' + mat[11] +
            ', ' + mat[12] + ', ' + mat[13] + ', ' + mat[14] + ', ' + mat[15] + ']';
    };

    /**
     * @class Quaternion
     * @name quat4
     */
    var quat4 = {};

    /**
     * Creates a new instance of a quat4 using the default array type
     * Any javascript array containing at least 4 numeric elements can serve as a quat4
     *
     * @param {quat4} [quat] quat4 containing values to initialize with
     *
     * @returns {quat4} New quat4
     */
    quat4.create = function (quat) {
        var dest = new MatrixArray(4);

        if (quat) {
            dest[0] = quat[0];
            dest[1] = quat[1];
            dest[2] = quat[2];
            dest[3] = quat[3];
        } else {
            dest[0] = dest[1] = dest[2] = dest[3] = 0;
        }

        return dest;
    };

    /**
     * Creates a new instance of a quat4, initializing it with the given arguments
     *
     * @param {number} x X value
     * @param {number} y Y value
     * @param {number} z Z value
     * @param {number} w W value

     * @returns {quat4} New quat4
     */
    quat4.createFrom = function (x, y, z, w) {
        var dest = new MatrixArray(4);

        dest[0] = x;
        dest[1] = y;
        dest[2] = z;
        dest[3] = w;

        return dest;
    };

    /**
     * Copies the values of one quat4 to another
     *
     * @param {quat4} quat quat4 containing values to copy
     * @param {quat4} dest quat4 receiving copied values
     *
     * @returns {quat4} dest
     */
    quat4.set = function (quat, dest) {
        dest[0] = quat[0];
        dest[1] = quat[1];
        dest[2] = quat[2];
        dest[3] = quat[3];

        return dest;
    };

    /**
     * Compares two quaternions for equality within a certain margin of error
     *
     * @param {quat4} a First vector
     * @param {quat4} b Second vector
     *
     * @returns {Boolean} True if a is equivalent to b
     */
    quat4.equal = function (a, b) {
        return a === b || (
            Math.abs(a[0] - b[0]) < FLOAT_EPSILON &&
            Math.abs(a[1] - b[1]) < FLOAT_EPSILON &&
            Math.abs(a[2] - b[2]) < FLOAT_EPSILON &&
            Math.abs(a[3] - b[3]) < FLOAT_EPSILON
        );
    };

    /**
     * Creates a new identity Quat4
     *
     * @param {quat4} [dest] quat4 receiving copied values
     *
     * @returns {quat4} dest is specified, new quat4 otherwise
     */
    quat4.identity = function (dest) {
        if (!dest) { dest = quat4.create(); }
        dest[0] = 0;
        dest[1] = 0;
        dest[2] = 0;
        dest[3] = 1;
        return dest;
    };

    var identityQuat4 = quat4.identity();

    /**
     * Calculates the W component of a quat4 from the X, Y, and Z components.
     * Assumes that quaternion is 1 unit in length.
     * Any existing W component will be ignored.
     *
     * @param {quat4} quat quat4 to calculate W component of
     * @param {quat4} [dest] quat4 receiving calculated values. If not specified result is written to quat
     *
     * @returns {quat4} dest if specified, quat otherwise
     */
    quat4.calculateW = function (quat, dest) {
        var x = quat[0], y = quat[1], z = quat[2];

        if (!dest || quat === dest) {
            quat[3] = -Math.sqrt(Math.abs(1.0 - x * x - y * y - z * z));
            return quat;
        }
        dest[0] = x;
        dest[1] = y;
        dest[2] = z;
        dest[3] = -Math.sqrt(Math.abs(1.0 - x * x - y * y - z * z));
        return dest;
    };

    /**
     * Calculates the dot product of two quaternions
     *
     * @param {quat4} quat First operand
     * @param {quat4} quat2 Second operand
     *
     * @return {number} Dot product of quat and quat2
     */
    quat4.dot = function(quat, quat2){
        return quat[0]*quat2[0] + quat[1]*quat2[1] + quat[2]*quat2[2] + quat[3]*quat2[3];
    };

    /**
     * Calculates the inverse of a quat4
     *
     * @param {quat4} quat quat4 to calculate inverse of
     * @param {quat4} [dest] quat4 receiving inverse values. If not specified result is written to quat
     *
     * @returns {quat4} dest if specified, quat otherwise
     */
    quat4.inverse = function(quat, dest) {
        var q0 = quat[0], q1 = quat[1], q2 = quat[2], q3 = quat[3],
            dot = q0*q0 + q1*q1 + q2*q2 + q3*q3,
            invDot = dot ? 1.0/dot : 0;
        
        // TODO: Would be faster to return [0,0,0,0] immediately if dot == 0
        
        if(!dest || quat === dest) {
            quat[0] *= -invDot;
            quat[1] *= -invDot;
            quat[2] *= -invDot;
            quat[3] *= invDot;
            return quat;
        }
        dest[0] = -quat[0]*invDot;
        dest[1] = -quat[1]*invDot;
        dest[2] = -quat[2]*invDot;
        dest[3] = quat[3]*invDot;
        return dest;
    };


    /**
     * Calculates the conjugate of a quat4
     * If the quaternion is normalized, this function is faster than quat4.inverse and produces the same result.
     *
     * @param {quat4} quat quat4 to calculate conjugate of
     * @param {quat4} [dest] quat4 receiving conjugate values. If not specified result is written to quat
     *
     * @returns {quat4} dest if specified, quat otherwise
     */
    quat4.conjugate = function (quat, dest) {
        if (!dest || quat === dest) {
            quat[0] *= -1;
            quat[1] *= -1;
            quat[2] *= -1;
            return quat;
        }
        dest[0] = -quat[0];
        dest[1] = -quat[1];
        dest[2] = -quat[2];
        dest[3] = quat[3];
        return dest;
    };

    /**
     * Calculates the length of a quat4
     *
     * Params:
     * @param {quat4} quat quat4 to calculate length of
     *
     * @returns Length of quat
     */
    quat4.length = function (quat) {
        var x = quat[0], y = quat[1], z = quat[2], w = quat[3];
        return Math.sqrt(x * x + y * y + z * z + w * w);
    };

    /**
     * Generates a unit quaternion of the same direction as the provided quat4
     * If quaternion length is 0, returns [0, 0, 0, 0]
     *
     * @param {quat4} quat quat4 to normalize
     * @param {quat4} [dest] quat4 receiving operation result. If not specified result is written to quat
     *
     * @returns {quat4} dest if specified, quat otherwise
     */
    quat4.normalize = function (quat, dest) {
        if (!dest) { dest = quat; }

        var x = quat[0], y = quat[1], z = quat[2], w = quat[3],
            len = Math.sqrt(x * x + y * y + z * z + w * w);
        if (len === 0) {
            dest[0] = 0;
            dest[1] = 0;
            dest[2] = 0;
            dest[3] = 0;
            return dest;
        }
        len = 1 / len;
        dest[0] = x * len;
        dest[1] = y * len;
        dest[2] = z * len;
        dest[3] = w * len;

        return dest;
    };

    /**
     * Performs quaternion addition
     *
     * @param {quat4} quat First operand
     * @param {quat4} quat2 Second operand
     * @param {quat4} [dest] quat4 receiving operation result. If not specified result is written to quat
     *
     * @returns {quat4} dest if specified, quat otherwise
     */
    quat4.add = function (quat, quat2, dest) {
        if(!dest || quat === dest) {
            quat[0] += quat2[0];
            quat[1] += quat2[1];
            quat[2] += quat2[2];
            quat[3] += quat2[3];
            return quat;
        }
        dest[0] = quat[0]+quat2[0];
        dest[1] = quat[1]+quat2[1];
        dest[2] = quat[2]+quat2[2];
        dest[3] = quat[3]+quat2[3];
        return dest;
    };

    /**
     * Performs a quaternion multiplication
     *
     * @param {quat4} quat First operand
     * @param {quat4} quat2 Second operand
     * @param {quat4} [dest] quat4 receiving operation result. If not specified result is written to quat
     *
     * @returns {quat4} dest if specified, quat otherwise
     */
    quat4.multiply = function (quat, quat2, dest) {
        if (!dest) { dest = quat; }

        var qax = quat[0], qay = quat[1], qaz = quat[2], qaw = quat[3],
            qbx = quat2[0], qby = quat2[1], qbz = quat2[2], qbw = quat2[3];

        dest[0] = qax * qbw + qaw * qbx + qay * qbz - qaz * qby;
        dest[1] = qay * qbw + qaw * qby + qaz * qbx - qax * qbz;
        dest[2] = qaz * qbw + qaw * qbz + qax * qby - qay * qbx;
        dest[3] = qaw * qbw - qax * qbx - qay * qby - qaz * qbz;

        return dest;
    };

    /**
     * Transforms a vec3 with the given quaternion
     *
     * @param {quat4} quat quat4 to transform the vector with
     * @param {vec3} vec vec3 to transform
     * @param {vec3} [dest] vec3 receiving operation result. If not specified result is written to vec
     *
     * @returns dest if specified, vec otherwise
     */
    quat4.multiplyVec3 = function (quat, vec, dest) {
        if (!dest) { dest = vec; }

        var x = vec[0], y = vec[1], z = vec[2],
            qx = quat[0], qy = quat[1], qz = quat[2], qw = quat[3],

            // calculate quat * vec
            ix = qw * x + qy * z - qz * y,
            iy = qw * y + qz * x - qx * z,
            iz = qw * z + qx * y - qy * x,
            iw = -qx * x - qy * y - qz * z;

        // calculate result * inverse quat
        dest[0] = ix * qw + iw * -qx + iy * -qz - iz * -qy;
        dest[1] = iy * qw + iw * -qy + iz * -qx - ix * -qz;
        dest[2] = iz * qw + iw * -qz + ix * -qy - iy * -qx;

        return dest;
    };

    /**
     * Multiplies the components of a quaternion by a scalar value
     *
     * @param {quat4} quat to scale
     * @param {number} val Value to scale by
     * @param {quat4} [dest] quat4 receiving operation result. If not specified result is written to quat
     *
     * @returns {quat4} dest if specified, quat otherwise
     */
    quat4.scale = function (quat, val, dest) {
        if(!dest || quat === dest) {
            quat[0] *= val;
            quat[1] *= val;
            quat[2] *= val;
            quat[3] *= val;
            return quat;
        }
        dest[0] = quat[0]*val;
        dest[1] = quat[1]*val;
        dest[2] = quat[2]*val;
        dest[3] = quat[3]*val;
        return dest;
    };

    /**
     * Calculates a 3x3 matrix from the given quat4
     *
     * @param {quat4} quat quat4 to create matrix from
     * @param {mat3} [dest] mat3 receiving operation result
     *
     * @returns {mat3} dest if specified, a new mat3 otherwise
     */
    quat4.toMat3 = function (quat, dest) {
        if (!dest) { dest = mat3.create(); }

        var x = quat[0], y = quat[1], z = quat[2], w = quat[3],
            x2 = x + x,
            y2 = y + y,
            z2 = z + z,

            xx = x * x2,
            xy = x * y2,
            xz = x * z2,
            yy = y * y2,
            yz = y * z2,
            zz = z * z2,
            wx = w * x2,
            wy = w * y2,
            wz = w * z2;

        dest[0] = 1 - (yy + zz);
        dest[1] = xy + wz;
        dest[2] = xz - wy;

        dest[3] = xy - wz;
        dest[4] = 1 - (xx + zz);
        dest[5] = yz + wx;

        dest[6] = xz + wy;
        dest[7] = yz - wx;
        dest[8] = 1 - (xx + yy);

        return dest;
    };

    /**
     * Calculates a 4x4 matrix from the given quat4
     *
     * @param {quat4} quat quat4 to create matrix from
     * @param {mat4} [dest] mat4 receiving operation result
     *
     * @returns {mat4} dest if specified, a new mat4 otherwise
     */
    quat4.toMat4 = function (quat, dest) {
        if (!dest) { dest = mat4.create(); }

        var x = quat[0], y = quat[1], z = quat[2], w = quat[3],
            x2 = x + x,
            y2 = y + y,
            z2 = z + z,

            xx = x * x2,
            xy = x * y2,
            xz = x * z2,
            yy = y * y2,
            yz = y * z2,
            zz = z * z2,
            wx = w * x2,
            wy = w * y2,
            wz = w * z2;

        dest[0] = 1 - (yy + zz);
        dest[1] = xy + wz;
        dest[2] = xz - wy;
        dest[3] = 0;

        dest[4] = xy - wz;
        dest[5] = 1 - (xx + zz);
        dest[6] = yz + wx;
        dest[7] = 0;

        dest[8] = xz + wy;
        dest[9] = yz - wx;
        dest[10] = 1 - (xx + yy);
        dest[11] = 0;

        dest[12] = 0;
        dest[13] = 0;
        dest[14] = 0;
        dest[15] = 1;

        return dest;
    };

    /**
     * Performs a spherical linear interpolation between two quat4
     *
     * @param {quat4} quat First quaternion
     * @param {quat4} quat2 Second quaternion
     * @param {number} slerp Interpolation amount between the two inputs
     * @param {quat4} [dest] quat4 receiving operation result. If not specified result is written to quat
     *
     * @returns {quat4} dest if specified, quat otherwise
     */
    quat4.slerp = function (quat, quat2, slerp, dest) {
        if (!dest) { dest = quat; }

        var cosHalfTheta = quat[0] * quat2[0] + quat[1] * quat2[1] + quat[2] * quat2[2] + quat[3] * quat2[3],
            halfTheta,
            sinHalfTheta,
            ratioA,
            ratioB;

        if (Math.abs(cosHalfTheta) >= 1.0) {
            if (dest !== quat) {
                dest[0] = quat[0];
                dest[1] = quat[1];
                dest[2] = quat[2];
                dest[3] = quat[3];
            }
            return dest;
        }

        halfTheta = Math.acos(cosHalfTheta);
        sinHalfTheta = Math.sqrt(1.0 - cosHalfTheta * cosHalfTheta);

        if (Math.abs(sinHalfTheta) < 0.001) {
            dest[0] = (quat[0] * 0.5 + quat2[0] * 0.5);
            dest[1] = (quat[1] * 0.5 + quat2[1] * 0.5);
            dest[2] = (quat[2] * 0.5 + quat2[2] * 0.5);
            dest[3] = (quat[3] * 0.5 + quat2[3] * 0.5);
            return dest;
        }

        ratioA = Math.sin((1 - slerp) * halfTheta) / sinHalfTheta;
        ratioB = Math.sin(slerp * halfTheta) / sinHalfTheta;

        dest[0] = (quat[0] * ratioA + quat2[0] * ratioB);
        dest[1] = (quat[1] * ratioA + quat2[1] * ratioB);
        dest[2] = (quat[2] * ratioA + quat2[2] * ratioB);
        dest[3] = (quat[3] * ratioA + quat2[3] * ratioB);

        return dest;
    };

    /**
     * Creates a quaternion from the given 3x3 rotation matrix.
     * If dest is omitted, a new quaternion will be created.
     *
     * @param {mat3}  mat    the rotation matrix
     * @param {quat4} [dest] an optional receiving quaternion
     *
     * @returns {quat4} the quaternion constructed from the rotation matrix
     *
     */
    quat4.fromRotationMatrix = function(mat, dest) {
        if (!dest) dest = quat4.create();
        
        // Algorithm in Ken Shoemake's article in 1987 SIGGRAPH course notes
        // article "Quaternion Calculus and Fast Animation".

        var fTrace = mat[0] + mat[4] + mat[8];
        var fRoot;

        if ( fTrace > 0.0 ) {
            // |w| > 1/2, may as well choose w > 1/2
            fRoot = Math.sqrt(fTrace + 1.0);  // 2w
            dest[3] = 0.5 * fRoot;
            fRoot = 0.5/fRoot;  // 1/(4w)
            dest[0] = (mat[7]-mat[5])*fRoot;
            dest[1] = (mat[2]-mat[6])*fRoot;
            dest[2] = (mat[3]-mat[1])*fRoot;
        } else {
            // |w| <= 1/2
            var s_iNext = quat4.fromRotationMatrix.s_iNext = quat4.fromRotationMatrix.s_iNext || [1,2,0];
            var i = 0;
            if ( mat[4] > mat[0] )
              i = 1;
            if ( mat[8] > mat[i*3+i] )
              i = 2;
            var j = s_iNext[i];
            var k = s_iNext[j];
            
            fRoot = Math.sqrt(mat[i*3+i]-mat[j*3+j]-mat[k*3+k] + 1.0);
            dest[i] = 0.5 * fRoot;
            fRoot = 0.5 / fRoot;
            dest[3] = (mat[k*3+j] - mat[j*3+k]) * fRoot;
            dest[j] = (mat[j*3+i] + mat[i*3+j]) * fRoot;
            dest[k] = (mat[k*3+i] + mat[i*3+k]) * fRoot;
        }
        
        return dest;
    };

    /**
     * Alias. See the description for quat4.fromRotationMatrix().
     */
    mat3.toQuat4 = quat4.fromRotationMatrix;

    (function() {
        var mat = mat3.create();
        
        /**
         * Creates a quaternion from the 3 given vectors. They must be perpendicular
         * to one another and represent the X, Y and Z axes.
         *
         * If dest is omitted, a new quat4 will be created.
         *
         * Example: The default OpenGL orientation has a view vector [0, 0, -1],
         * right vector [1, 0, 0], and up vector [0, 1, 0]. A quaternion representing
         * this orientation could be constructed with:
         *
         *   quat = quat4.fromAxes([0, 0, -1], [1, 0, 0], [0, 1, 0], quat4.create());
         *
         * @param {vec3}  view   the view vector, or direction the object is pointing in
         * @param {vec3}  right  the right vector, or direction to the "right" of the object
         * @param {vec3}  up     the up vector, or direction towards the object's "up"
         * @param {quat4} [dest] an optional receiving quat4
         *
         * @returns {quat4} dest
         **/
        quat4.fromAxes = function(view, right, up, dest) {
            mat[0] = right[0];
            mat[3] = right[1];
            mat[6] = right[2];

            mat[1] = up[0];
            mat[4] = up[1];
            mat[7] = up[2];

            mat[2] = view[0];
            mat[5] = view[1];
            mat[8] = view[2];

            return quat4.fromRotationMatrix(mat, dest);
        };
    })();

    /**
     * Sets a quat4 to the Identity and returns it.
     *
     * @param {quat4} [dest] quat4 to set. If omitted, a
     * new quat4 will be created.
     *
     * @returns {quat4} dest
     */
    quat4.identity = function(dest) {
        if (!dest) dest = quat4.create();
        dest[0] = 0;
        dest[1] = 0;
        dest[2] = 0;
        dest[3] = 1;
        return dest;
    };

    /**
     * Sets a quat4 from the given angle and rotation axis,
     * then returns it. If dest is not given, a new quat4 is created.
     *
     * @param {Number} angle  the angle in radians
     * @param {vec3}   axis   the axis around which to rotate
     * @param {quat4}  [dest] the optional quat4 to store the result
     *
     * @returns {quat4} dest
     **/
    quat4.fromAngleAxis = function(angle, axis, dest) {
        // The quaternion representing the rotation is
        //   q = cos(A/2)+sin(A/2)*(x*i+y*j+z*k)
        if (!dest) dest = quat4.create();
        
        var half = angle * 0.5;
        var s = Math.sin(half);
        dest[3] = Math.cos(half);
        dest[0] = s * axis[0];
        dest[1] = s * axis[1];
        dest[2] = s * axis[2];
        
        return dest;
    };

    /**
     * Stores the angle and axis in a vec4, where the XYZ components represent
     * the axis and the W (4th) component is the angle in radians.
     *
     * If dest is not given, src will be modified in place and returned, after
     * which it should not be considered not a quaternion (just an axis and angle).
     *
     * @param {quat4} quat   the quaternion whose angle and axis to store
     * @param {vec4}  [dest] the optional vec4 to receive the data
     *
     * @returns {vec4} dest
     */
    quat4.toAngleAxis = function(src, dest) {
        if (!dest) dest = src;
        // The quaternion representing the rotation is
        //   q = cos(A/2)+sin(A/2)*(x*i+y*j+z*k)

        var sqrlen = src[0]*src[0]+src[1]*src[1]+src[2]*src[2];
        if (sqrlen > 0)
        {
            dest[3] = 2 * Math.acos(src[3]);
            var invlen = glMath.invsqrt(sqrlen);
            dest[0] = src[0]*invlen;
            dest[1] = src[1]*invlen;
            dest[2] = src[2]*invlen;
        } else {
            // angle is 0 (mod 2*pi), so any axis will do
            dest[3] = 0;
            dest[0] = 1;
            dest[1] = 0;
            dest[2] = 0;
        }
        
        return dest;
    };

    /**
     * Returns a string representation of a quaternion
     *
     * @param {quat4} quat quat4 to represent as a string
     *
     * @returns {string} String representation of quat
     */
    quat4.str = function (quat) {
        return '[' + quat[0] + ', ' + quat[1] + ', ' + quat[2] + ', ' + quat[3] + ']';
    };
    
    /**
     * @class 2 Dimensional Vector
     * @name vec2
     */
    var vec2 = {};
     
    /**
     * Creates a new vec2, initializing it from vec if vec
     * is given.
     *
     * @param {vec2} [vec] the vector's initial contents
     * @returns {vec2} a new 2D vector
     */
    vec2.create = function(vec) {
        var dest = new MatrixArray(2);

        if (vec) {
            dest[0] = vec[0];
            dest[1] = vec[1];
        } else {
            dest[0] = 0;
            dest[1] = 0;
        }
        return dest;
    };

    /**
     * Creates a new instance of a vec2, initializing it with the given arguments
     *
     * @param {number} x X value
     * @param {number} y Y value

     * @returns {vec2} New vec2
     */
    vec2.createFrom = function (x, y) {
        var dest = new MatrixArray(2);

        dest[0] = x;
        dest[1] = y;

        return dest;
    };
    
    /**
     * Adds the vec2's together. If dest is given, the result
     * is stored there. Otherwise, the result is stored in vecB.
     *
     * @param {vec2} vecA the first operand
     * @param {vec2} vecB the second operand
     * @param {vec2} [dest] the optional receiving vector
     * @returns {vec2} dest
     */
    vec2.add = function(vecA, vecB, dest) {
        if (!dest) dest = vecB;
        dest[0] = vecA[0] + vecB[0];
        dest[1] = vecA[1] + vecB[1];
        return dest;
    };
    
    /**
     * Subtracts vecB from vecA. If dest is given, the result
     * is stored there. Otherwise, the result is stored in vecB.
     *
     * @param {vec2} vecA the first operand
     * @param {vec2} vecB the second operand
     * @param {vec2} [dest] the optional receiving vector
     * @returns {vec2} dest
     */
    vec2.subtract = function(vecA, vecB, dest) {
        if (!dest) dest = vecB;
        dest[0] = vecA[0] - vecB[0];
        dest[1] = vecA[1] - vecB[1];
        return dest;
    };
    
    /**
     * Multiplies vecA with vecB. If dest is given, the result
     * is stored there. Otherwise, the result is stored in vecB.
     *
     * @param {vec2} vecA the first operand
     * @param {vec2} vecB the second operand
     * @param {vec2} [dest] the optional receiving vector
     * @returns {vec2} dest
     */
    vec2.multiply = function(vecA, vecB, dest) {
        if (!dest) dest = vecB;
        dest[0] = vecA[0] * vecB[0];
        dest[1] = vecA[1] * vecB[1];
        return dest;
    };
    
    /**
     * Divides vecA by vecB. If dest is given, the result
     * is stored there. Otherwise, the result is stored in vecB.
     *
     * @param {vec2} vecA the first operand
     * @param {vec2} vecB the second operand
     * @param {vec2} [dest] the optional receiving vector
     * @returns {vec2} dest
     */
    vec2.divide = function(vecA, vecB, dest) {
        if (!dest) dest = vecB;
        dest[0] = vecA[0] / vecB[0];
        dest[1] = vecA[1] / vecB[1];
        return dest;
    };
    
    /**
     * Scales vecA by some scalar number. If dest is given, the result
     * is stored there. Otherwise, the result is stored in vecA.
     *
     * This is the same as multiplying each component of vecA
     * by the given scalar.
     *
     * @param {vec2}   vecA the vector to be scaled
     * @param {Number} scalar the amount to scale the vector by
     * @param {vec2}   [dest] the optional receiving vector
     * @returns {vec2} dest
     */
    vec2.scale = function(vecA, scalar, dest) {
        if (!dest) dest = vecA;
        dest[0] = vecA[0] * scalar;
        dest[1] = vecA[1] * scalar;
        return dest;
    };

    /**
     * Calculates the euclidian distance between two vec2
     *
     * Params:
     * @param {vec2} vecA First vector
     * @param {vec2} vecB Second vector
     *
     * @returns {number} Distance between vecA and vecB
     */
    vec2.dist = function (vecA, vecB) {
        var x = vecB[0] - vecA[0],
            y = vecB[1] - vecA[1];
        return Math.sqrt(x*x + y*y);
    };

    /**
     * Copies the values of one vec2 to another
     *
     * @param {vec2} vec vec2 containing values to copy
     * @param {vec2} dest vec2 receiving copied values
     *
     * @returns {vec2} dest
     */
    vec2.set = function (vec, dest) {
        dest[0] = vec[0];
        dest[1] = vec[1];
        return dest;
    };

    /**
     * Compares two vectors for equality within a certain margin of error
     *
     * @param {vec2} a First vector
     * @param {vec2} b Second vector
     *
     * @returns {Boolean} True if a is equivalent to b
     */
    vec2.equal = function (a, b) {
        return a === b || (
            Math.abs(a[0] - b[0]) < FLOAT_EPSILON &&
            Math.abs(a[1] - b[1]) < FLOAT_EPSILON
        );
    };

    /**
     * Negates the components of a vec2
     *
     * @param {vec2} vec vec2 to negate
     * @param {vec2} [dest] vec2 receiving operation result. If not specified result is written to vec
     *
     * @returns {vec2} dest if specified, vec otherwise
     */
    vec2.negate = function (vec, dest) {
        if (!dest) { dest = vec; }
        dest[0] = -vec[0];
        dest[1] = -vec[1];
        return dest;
    };

    /**
     * Normlize a vec2
     *
     * @param {vec2} vec vec2 to normalize
     * @param {vec2} [dest] vec2 receiving operation result. If not specified result is written to vec
     *
     * @returns {vec2} dest if specified, vec otherwise
     */
    vec2.normalize = function (vec, dest) {
        if (!dest) { dest = vec; }
        var mag = vec[0] * vec[0] + vec[1] * vec[1];
        if (mag > 0) {
            mag = Math.sqrt(mag);
            dest[0] = vec[0] / mag;
            dest[1] = vec[1] / mag;
        } else {
            dest[0] = dest[1] = 0;
        }
        return dest;
    };

    /**
     * Computes the cross product of two vec2's. Note that the cross product must by definition
     * produce a 3D vector. If a dest vector is given, it will contain the resultant 3D vector.
     * Otherwise, a scalar number will be returned, representing the vector's Z coordinate, since
     * its X and Y must always equal 0.
     *
     * Examples:
     *    var crossResult = vec3.create();
     *    vec2.cross([1, 2], [3, 4], crossResult);
     *    //=> [0, 0, -2]
     *
     *    vec2.cross([1, 2], [3, 4]);
     *    //=> -2
     *
     * See http://stackoverflow.com/questions/243945/calculating-a-2d-vectors-cross-product
     * for some interesting facts.
     *
     * @param {vec2} vecA left operand
     * @param {vec2} vecB right operand
     * @param {vec2} [dest] optional vec2 receiving result. If not specified a scalar is returned
     *
     */
    vec2.cross = function (vecA, vecB, dest) {
        var z = vecA[0] * vecB[1] - vecA[1] * vecB[0];
        if (!dest) return z;
        dest[0] = dest[1] = 0;
        dest[2] = z;
        return dest;
    };
    
    /**
     * Caclulates the length of a vec2
     *
     * @param {vec2} vec vec2 to calculate length of
     *
     * @returns {Number} Length of vec
     */
    vec2.length = function (vec) {
      var x = vec[0], y = vec[1];
      return Math.sqrt(x * x + y * y);
    };

    /**
     * Caclulates the squared length of a vec2
     *
     * @param {vec2} vec vec2 to calculate squared length of
     *
     * @returns {Number} Squared Length of vec
     */
    vec2.squaredLength = function (vec) {
      var x = vec[0], y = vec[1];
      return x * x + y * y;
    };

    /**
     * Caclulates the dot product of two vec2s
     *
     * @param {vec2} vecA First operand
     * @param {vec2} vecB Second operand
     *
     * @returns {Number} Dot product of vecA and vecB
     */
    vec2.dot = function (vecA, vecB) {
        return vecA[0] * vecB[0] + vecA[1] * vecB[1];
    };
    
    /**
     * Generates a 2D unit vector pointing from one vector to another
     *
     * @param {vec2} vecA Origin vec2
     * @param {vec2} vecB vec2 to point to
     * @param {vec2} [dest] vec2 receiving operation result. If not specified result is written to vecA
     *
     * @returns {vec2} dest if specified, vecA otherwise
     */
    vec2.direction = function (vecA, vecB, dest) {
        if (!dest) { dest = vecA; }

        var x = vecA[0] - vecB[0],
            y = vecA[1] - vecB[1],
            len = x * x + y * y;

        if (!len) {
            dest[0] = 0;
            dest[1] = 0;
            dest[2] = 0;
            return dest;
        }

        len = 1 / Math.sqrt(len);
        dest[0] = x * len;
        dest[1] = y * len;
        return dest;
    };

    /**
     * Performs a linear interpolation between two vec2
     *
     * @param {vec2} vecA First vector
     * @param {vec2} vecB Second vector
     * @param {Number} lerp Interpolation amount between the two inputs
     * @param {vec2} [dest] vec2 receiving operation result. If not specified result is written to vecA
     *
     * @returns {vec2} dest if specified, vecA otherwise
     */
    vec2.lerp = function (vecA, vecB, lerp, dest) {
        if (!dest) { dest = vecA; }
        dest[0] = vecA[0] + lerp * (vecB[0] - vecA[0]);
        dest[1] = vecA[1] + lerp * (vecB[1] - vecA[1]);
        return dest;
    };

    /**
     * Returns a string representation of a vector
     *
     * @param {vec2} vec Vector to represent as a string
     *
     * @returns {String} String representation of vec
     */
    vec2.str = function (vec) {
        return '[' + vec[0] + ', ' + vec[1] + ']';
    };
    
    /**
     * @class 2x2 Matrix
     * @name mat2
     */
    var mat2 = {};
    
    /**
     * Creates a new 2x2 matrix. If src is given, the new matrix
     * is initialized to those values.
     *
     * @param {mat2} [src] the seed values for the new matrix, if any
     * @returns {mat2} a new matrix
     */
    mat2.create = function(src) {
        var dest = new MatrixArray(4);
        
        if (src) {
            dest[0] = src[0];
            dest[1] = src[1];
            dest[2] = src[2];
            dest[3] = src[3];
        } else {
            dest[0] = dest[1] = dest[2] = dest[3] = 0;
        }
        return dest;
    };

    /**
     * Creates a new instance of a mat2, initializing it with the given arguments
     *
     * @param {number} m00
     * @param {number} m01
     * @param {number} m10
     * @param {number} m11

     * @returns {mat2} New mat2
     */
    mat2.createFrom = function (m00, m01, m10, m11) {
        var dest = new MatrixArray(4);

        dest[0] = m00;
        dest[1] = m01;
        dest[2] = m10;
        dest[3] = m11;

        return dest;
    };
    
    /**
     * Copies the values of one mat2 to another
     *
     * @param {mat2} mat mat2 containing values to copy
     * @param {mat2} dest mat2 receiving copied values
     *
     * @returns {mat2} dest
     */
    mat2.set = function (mat, dest) {
        dest[0] = mat[0];
        dest[1] = mat[1];
        dest[2] = mat[2];
        dest[3] = mat[3];
        return dest;
    };

    /**
     * Compares two matrices for equality within a certain margin of error
     *
     * @param {mat2} a First matrix
     * @param {mat2} b Second matrix
     *
     * @returns {Boolean} True if a is equivalent to b
     */
    mat2.equal = function (a, b) {
        return a === b || (
            Math.abs(a[0] - b[0]) < FLOAT_EPSILON &&
            Math.abs(a[1] - b[1]) < FLOAT_EPSILON &&
            Math.abs(a[2] - b[2]) < FLOAT_EPSILON &&
            Math.abs(a[3] - b[3]) < FLOAT_EPSILON
        );
    };

    /**
     * Sets a mat2 to an identity matrix
     *
     * @param {mat2} [dest] mat2 to set. If omitted a new one will be created.
     *
     * @returns {mat2} dest
     */
    mat2.identity = function (dest) {
        if (!dest) { dest = mat2.create(); }
        dest[0] = 1;
        dest[1] = 0;
        dest[2] = 0;
        dest[3] = 1;
        return dest;
    };

    /**
     * Transposes a mat2 (flips the values over the diagonal)
     *
     * @param {mat2} mat mat2 to transpose
     * @param {mat2} [dest] mat2 receiving transposed values. If not specified result is written to mat
     *
     * @param {mat2} dest if specified, mat otherwise
     */
    mat2.transpose = function (mat, dest) {
        // If we are transposing ourselves we can skip a few steps but have to cache some values
        if (!dest || mat === dest) {
            var a00 = mat[1];
            mat[1] = mat[2];
            mat[2] = a00;
            return mat;
        }
        
        dest[0] = mat[0];
        dest[1] = mat[2];
        dest[2] = mat[1];
        dest[3] = mat[3];
        return dest;
    };

    /**
     * Calculates the determinant of a mat2
     *
     * @param {mat2} mat mat2 to calculate determinant of
     *
     * @returns {Number} determinant of mat
     */
    mat2.determinant = function (mat) {
      return mat[0] * mat[3] - mat[2] * mat[1];
    };
    
    /**
     * Calculates the inverse matrix of a mat2
     *
     * @param {mat2} mat mat2 to calculate inverse of
     * @param {mat2} [dest] mat2 receiving inverse matrix. If not specified result is written to mat
     *
     * @param {mat2} dest is specified, mat otherwise, null if matrix cannot be inverted
     */
    mat2.inverse = function (mat, dest) {
        if (!dest) { dest = mat; }
        var a0 = mat[0], a1 = mat[1], a2 = mat[2], a3 = mat[3];
        var det = a0 * a3 - a2 * a1;
        if (!det) return null;
        
        det = 1.0 / det;
        dest[0] =  a3 * det;
        dest[1] = -a1 * det;
        dest[2] = -a2 * det;
        dest[3] =  a0 * det;
        return dest;
    };
    
    /**
     * Performs a matrix multiplication
     *
     * @param {mat2} matA First operand
     * @param {mat2} matB Second operand
     * @param {mat2} [dest] mat2 receiving operation result. If not specified result is written to matA
     *
     * @returns {mat2} dest if specified, matA otherwise
     */
    mat2.multiply = function (matA, matB, dest) {
        if (!dest) { dest = matA; }
        var a11 = matA[0],
            a12 = matA[1],
            a21 = matA[2],
            a22 = matA[3];
        dest[0] = a11 * matB[0] + a12 * matB[2];
        dest[1] = a11 * matB[1] + a12 * matB[3];
        dest[2] = a21 * matB[0] + a22 * matB[2];
        dest[3] = a21 * matB[1] + a22 * matB[3];
        return dest;
    };

    /**
     * Rotates a 2x2 matrix by an angle
     *
     * @param {mat2}   mat   The matrix to rotate
     * @param {Number} angle The angle in radians
     * @param {mat2} [dest]  Optional mat2 receiving the result. If omitted mat will be used.
     *
     * @returns {mat2} dest if specified, mat otherwise
     */
    mat2.rotate = function (mat, angle, dest) {
        if (!dest) { dest = mat; }
        var a11 = mat[0],
            a12 = mat[1],
            a21 = mat[2],
            a22 = mat[3],
            s = Math.sin(angle),
            c = Math.cos(angle);
        dest[0] = a11 *  c + a12 * s;
        dest[1] = a11 * -s + a12 * c;
        dest[2] = a21 *  c + a22 * s;
        dest[3] = a21 * -s + a22 * c;
        return dest;
    };

    /**
     * Multiplies the vec2 by the given 2x2 matrix
     *
     * @param {mat2} matrix the 2x2 matrix to multiply against
     * @param {vec2} vec    the vector to multiply
     * @param {vec2} [dest] an optional receiving vector. If not given, vec is used.
     *
     * @returns {vec2} The multiplication result
     **/
    mat2.multiplyVec2 = function(matrix, vec, dest) {
      if (!dest) dest = vec;
      var x = vec[0], y = vec[1];
      dest[0] = x * matrix[0] + y * matrix[1];
      dest[1] = x * matrix[2] + y * matrix[3];
      return dest;
    };
    
    /**
     * Scales the mat2 by the dimensions in the given vec2
     *
     * @param {mat2} matrix the 2x2 matrix to scale
     * @param {vec2} vec    the vector containing the dimensions to scale by
     * @param {vec2} [dest] an optional receiving mat2. If not given, matrix is used.
     *
     * @returns {mat2} dest if specified, matrix otherwise
     **/
    mat2.scale = function(matrix, vec, dest) {
      if (!dest) { dest = matrix; }
      var a11 = matrix[0],
          a12 = matrix[1],
          a21 = matrix[2],
          a22 = matrix[3],
          b11 = vec[0],
          b22 = vec[1];
      dest[0] = a11 * b11;
      dest[1] = a12 * b22;
      dest[2] = a21 * b11;
      dest[3] = a22 * b22;
      return dest;
    };

    /**
     * Returns a string representation of a mat2
     *
     * @param {mat2} mat mat2 to represent as a string
     *
     * @param {String} String representation of mat
     */
    mat2.str = function (mat) {
        return '[' + mat[0] + ', ' + mat[1] + ', ' + mat[2] + ', ' + mat[3] + ']';
    };
    
    /**
     * @class 4 Dimensional Vector
     * @name vec4
     */
    var vec4 = {};
     
    /**
     * Creates a new vec4, initializing it from vec if vec
     * is given.
     *
     * @param {vec4} [vec] the vector's initial contents
     * @returns {vec4} a new 2D vector
     */
    vec4.create = function(vec) {
        var dest = new MatrixArray(4);
        
        if (vec) {
            dest[0] = vec[0];
            dest[1] = vec[1];
            dest[2] = vec[2];
            dest[3] = vec[3];
        } else {
            dest[0] = 0;
            dest[1] = 0;
            dest[2] = 0;
            dest[3] = 0;
        }
        return dest;
    };

    /**
     * Creates a new instance of a vec4, initializing it with the given arguments
     *
     * @param {number} x X value
     * @param {number} y Y value
     * @param {number} z Z value
     * @param {number} w W value

     * @returns {vec4} New vec4
     */
    vec4.createFrom = function (x, y, z, w) {
        var dest = new MatrixArray(4);

        dest[0] = x;
        dest[1] = y;
        dest[2] = z;
        dest[3] = w;

        return dest;
    };
    
    /**
     * Adds the vec4's together. If dest is given, the result
     * is stored there. Otherwise, the result is stored in vecB.
     *
     * @param {vec4} vecA the first operand
     * @param {vec4} vecB the second operand
     * @param {vec4} [dest] the optional receiving vector
     * @returns {vec4} dest
     */
    vec4.add = function(vecA, vecB, dest) {
      if (!dest) dest = vecB;
      dest[0] = vecA[0] + vecB[0];
      dest[1] = vecA[1] + vecB[1];
      dest[2] = vecA[2] + vecB[2];
      dest[3] = vecA[3] + vecB[3];
      return dest;
    };
    
    /**
     * Subtracts vecB from vecA. If dest is given, the result
     * is stored there. Otherwise, the result is stored in vecB.
     *
     * @param {vec4} vecA the first operand
     * @param {vec4} vecB the second operand
     * @param {vec4} [dest] the optional receiving vector
     * @returns {vec4} dest
     */
    vec4.subtract = function(vecA, vecB, dest) {
      if (!dest) dest = vecB;
      dest[0] = vecA[0] - vecB[0];
      dest[1] = vecA[1] - vecB[1];
      dest[2] = vecA[2] - vecB[2];
      dest[3] = vecA[3] - vecB[3];
      return dest;
    };
    
    /**
     * Multiplies vecA with vecB. If dest is given, the result
     * is stored there. Otherwise, the result is stored in vecB.
     *
     * @param {vec4} vecA the first operand
     * @param {vec4} vecB the second operand
     * @param {vec4} [dest] the optional receiving vector
     * @returns {vec4} dest
     */
    vec4.multiply = function(vecA, vecB, dest) {
      if (!dest) dest = vecB;
      dest[0] = vecA[0] * vecB[0];
      dest[1] = vecA[1] * vecB[1];
      dest[2] = vecA[2] * vecB[2];
      dest[3] = vecA[3] * vecB[3];
      return dest;
    };
    
    /**
     * Divides vecA by vecB. If dest is given, the result
     * is stored there. Otherwise, the result is stored in vecB.
     *
     * @param {vec4} vecA the first operand
     * @param {vec4} vecB the second operand
     * @param {vec4} [dest] the optional receiving vector
     * @returns {vec4} dest
     */
    vec4.divide = function(vecA, vecB, dest) {
      if (!dest) dest = vecB;
      dest[0] = vecA[0] / vecB[0];
      dest[1] = vecA[1] / vecB[1];
      dest[2] = vecA[2] / vecB[2];
      dest[3] = vecA[3] / vecB[3];
      return dest;
    };
    
    /**
     * Scales vecA by some scalar number. If dest is given, the result
     * is stored there. Otherwise, the result is stored in vecA.
     *
     * This is the same as multiplying each component of vecA
     * by the given scalar.
     *
     * @param {vec4}   vecA the vector to be scaled
     * @param {Number} scalar the amount to scale the vector by
     * @param {vec4}   [dest] the optional receiving vector
     * @returns {vec4} dest
     */
    vec4.scale = function(vecA, scalar, dest) {
      if (!dest) dest = vecA;
      dest[0] = vecA[0] * scalar;
      dest[1] = vecA[1] * scalar;
      dest[2] = vecA[2] * scalar;
      dest[3] = vecA[3] * scalar;
      return dest;
    };

    /**
     * Copies the values of one vec4 to another
     *
     * @param {vec4} vec vec4 containing values to copy
     * @param {vec4} dest vec4 receiving copied values
     *
     * @returns {vec4} dest
     */
    vec4.set = function (vec, dest) {
        dest[0] = vec[0];
        dest[1] = vec[1];
        dest[2] = vec[2];
        dest[3] = vec[3];
        return dest;
    };

    /**
     * Compares two vectors for equality within a certain margin of error
     *
     * @param {vec4} a First vector
     * @param {vec4} b Second vector
     *
     * @returns {Boolean} True if a is equivalent to b
     */
    vec4.equal = function (a, b) {
        return a === b || (
            Math.abs(a[0] - b[0]) < FLOAT_EPSILON &&
            Math.abs(a[1] - b[1]) < FLOAT_EPSILON &&
            Math.abs(a[2] - b[2]) < FLOAT_EPSILON &&
            Math.abs(a[3] - b[3]) < FLOAT_EPSILON
        );
    };

    /**
     * Negates the components of a vec4
     *
     * @param {vec4} vec vec4 to negate
     * @param {vec4} [dest] vec4 receiving operation result. If not specified result is written to vec
     *
     * @returns {vec4} dest if specified, vec otherwise
     */
    vec4.negate = function (vec, dest) {
        if (!dest) { dest = vec; }
        dest[0] = -vec[0];
        dest[1] = -vec[1];
        dest[2] = -vec[2];
        dest[3] = -vec[3];
        return dest;
    };

    /**
     * Caclulates the length of a vec2
     *
     * @param {vec2} vec vec2 to calculate length of
     *
     * @returns {Number} Length of vec
     */
    vec4.length = function (vec) {
      var x = vec[0], y = vec[1], z = vec[2], w = vec[3];
      return Math.sqrt(x * x + y * y + z * z + w * w);
    };

    /**
     * Caclulates the squared length of a vec4
     *
     * @param {vec4} vec vec4 to calculate squared length of
     *
     * @returns {Number} Squared Length of vec
     */
    vec4.squaredLength = function (vec) {
      var x = vec[0], y = vec[1], z = vec[2], w = vec[3];
      return x * x + y * y + z * z + w * w;
    };

    /**
     * Performs a linear interpolation between two vec4
     *
     * @param {vec4} vecA First vector
     * @param {vec4} vecB Second vector
     * @param {Number} lerp Interpolation amount between the two inputs
     * @param {vec4} [dest] vec4 receiving operation result. If not specified result is written to vecA
     *
     * @returns {vec4} dest if specified, vecA otherwise
     */
    vec4.lerp = function (vecA, vecB, lerp, dest) {
        if (!dest) { dest = vecA; }
        dest[0] = vecA[0] + lerp * (vecB[0] - vecA[0]);
        dest[1] = vecA[1] + lerp * (vecB[1] - vecA[1]);
        dest[2] = vecA[2] + lerp * (vecB[2] - vecA[2]);
        dest[3] = vecA[3] + lerp * (vecB[3] - vecA[3]);
        return dest;
    };

    /**
     * Returns a string representation of a vector
     *
     * @param {vec4} vec Vector to represent as a string
     *
     * @returns {String} String representation of vec
     */
    vec4.str = function (vec) {
        return '[' + vec[0] + ', ' + vec[1] + ', ' + vec[2] + ', ' + vec[3] + ']';
    };

    /*
     * Exports
     */

    if(root) {
        root.glMatrixArrayType = MatrixArray;
        root.MatrixArray = MatrixArray;
        root.setMatrixArrayType = setMatrixArrayType;
        root.determineMatrixArrayType = determineMatrixArrayType;
        root.glMath = glMath;
        root.vec2 = vec2;
        root.vec3 = vec3;
        root.vec4 = vec4;
        root.mat2 = mat2;
        root.mat3 = mat3;
        root.mat4 = mat4;
        root.quat4 = quat4;
    }

    return {
        glMatrixArrayType: MatrixArray,
        MatrixArray: MatrixArray,
        setMatrixArrayType: setMatrixArrayType,
        determineMatrixArrayType: determineMatrixArrayType,
        glMath: glMath,
        vec2: vec2,
        vec3: vec3,
        vec4: vec4,
        mat2: mat2,
        mat3: mat3,
        mat4: mat4,
        quat4: quat4
    };
}));
//
//  Copyright 2006-2010 iChemLabs, LLC.  All rights reserved.
//

ChemDoodle.animations = (function(window) {
	'use strict';
	var ext = {};

	// Drop in replace functions for setTimeout() & setInterval() that 
	// make use of requestAnimationFrame() for performance where available
	// http://www.joelambert.co.uk
	 
	// Copyright 2011, Joe Lambert.
	// Free to use under the MIT license.
	// http://www.opensource.org/licenses/mit-license.php
	
	// requestAnimationFrame() shim by Paul Irish
	// http://paulirish.com/2011/requestanimationframe-for-smart-animating/
	window.requestAnimFrame = (function() {
		return  window.requestAnimationFrame       || 
				window.webkitRequestAnimationFrame || 
				window.mozRequestAnimationFrame    || 
				window.oRequestAnimationFrame      || 
				window.msRequestAnimationFrame     || 
				function(/* function */ callback, /* DOMElement */ element){
					window.setTimeout(callback, 1000 / 60);
				};
	})();
	
	/**
	 * Behaves the same as setInterval except uses requestAnimationFrame() where possible for better performance
	 * @param {function} fn The callback function
	 * @param {int} delay The delay in milliseconds
	 */
	ext.requestInterval = function(fn, delay) {
		if( !window.requestAnimationFrame       && 
			!window.webkitRequestAnimationFrame && 
			!(window.mozRequestAnimationFrame && window.mozCancelRequestAnimationFrame) && // Firefox 5 ships without cancel support
			!window.oRequestAnimationFrame      && 
			!window.msRequestAnimationFrame)
				return window.setInterval(fn, delay);
				
		var start = new Date().getTime(),
			handle = new Object();
			
		function loop() {
			var current = new Date().getTime(),
				delta = current - start;
				
			if(delta >= delay) {
				fn.call();
				start = new Date().getTime();
			}
	 
			handle.value = window.requestAnimFrame(loop);
		};
		
		handle.value = window.requestAnimFrame(loop);
		return handle;
	};
	 
	/**
	 * Behaves the same as clearInterval except uses cancelRequestAnimationFrame() where possible for better performance
	 * @param {int|object} fn The callback function
	 */
	ext.clearRequestInterval = function(handle) {
	    window.cancelAnimationFrame ? window.cancelAnimationFrame(handle.value) :
	    window.webkitCancelAnimationFrame ? window.webkitCancelAnimationFrame(handle.value) :
	    window.webkitCancelRequestAnimationFrame ? window.webkitCancelRequestAnimationFrame(handle.value) : /* Support for legacy API */
	    window.mozCancelRequestAnimationFrame ? window.mozCancelRequestAnimationFrame(handle.value) :
	    window.oCancelRequestAnimationFrame	? window.oCancelRequestAnimationFrame(handle.value) :
	    window.msCancelRequestAnimationFrame ? window.msCancelRequestAnimationFrame(handle.value) :
	    clearInterval(handle);
	};
	
	/**
	 * Behaves the same as setTimeout except uses requestAnimationFrame() where possible for better performance
	 * @param {function} fn The callback function
	 * @param {int} delay The delay in milliseconds
	 */
	 
	ext.requestTimeout = function(fn, delay) {
		if( !window.requestAnimationFrame      	&& 
			!window.webkitRequestAnimationFrame && 
			!(window.mozRequestAnimationFrame && window.mozCancelRequestAnimationFrame) && // Firefox 5 ships without cancel support
			!window.oRequestAnimationFrame      && 
			!window.msRequestAnimationFrame)
				return window.setTimeout(fn, delay);
				
		var start = new Date().getTime(),
			handle = new Object();
			
		function loop(){
			var current = new Date().getTime(),
				delta = current - start;
				
			delta >= delay ? fn.call() : handle.value = window.requestAnimFrame(loop);
		};
		
		handle.value = window.requestAnimFrame(loop);
		return handle;
	};
	 
	/**
	 * Behaves the same as clearTimeout except uses cancelRequestAnimationFrame() where possible for better performance
	 * @param {int|object} fn The callback function
	 */
	ext.clearRequestTimeout = function(handle) {
	    window.cancelAnimationFrame ? window.cancelAnimationFrame(handle.value) :
	    window.webkitCancelAnimationFrame ? window.webkitCancelAnimationFrame(handle.value) :
	    window.webkitCancelRequestAnimationFrame ? window.webkitCancelRequestAnimationFrame(handle.value) : /* Support for legacy API */
	    window.mozCancelRequestAnimationFrame ? window.mozCancelRequestAnimationFrame(handle.value) :
	    window.oCancelRequestAnimationFrame	? window.oCancelRequestAnimationFrame(handle.value) :
	    window.msCancelRequestAnimationFrame ? window.msCancelRequestAnimationFrame(handle.value) :
	    clearTimeout(handle);
	};

	return ext;

})(window);
//
//  Copyright 2006-2010 iChemLabs, LLC.  All rights reserved.
//

ChemDoodle.extensions = (function(structures, v3, m) {
	'use strict';
	var ext = {};

	ext.stringStartsWith = function(str, match) {
		return str.slice(0, match.length) === match;
	};

	ext.vec3AngleFrom = function(v1, v2) {
		var length1 = v3.length(v1);
		var length2 = v3.length(v2);
		var dot = v3.dot(v1, v2);
		var cosine = dot / length1 / length2;
		return m.acos(cosine);
	};

	ext.contextHashTo = function(ctx, xs, ys, xt, yt, width, spacing) {
		var travelled = 0;
		var dist = new structures.Point(xs, ys).distance(new structures.Point(xt, yt));
		var space = false;
		var lastX = xs;
		var lastY = ys;
		var difX = xt - xs;
		var difY = yt - ys;
		while (travelled < dist) {
			if (space) {
				if (travelled + spacing > dist) {
					ctx.moveTo(xt, yt);
					break;
				} else {
					var percent = spacing / dist;
					lastX += percent * difX;
					lastY += percent * difY;
					ctx.moveTo(lastX, lastY);
					travelled += spacing;
				}
			} else {
				if (travelled + width > dist) {
					ctx.lineTo(xt, yt);
					break;
				} else {
					var percent = width / dist;
					lastX += percent * difX;
					lastY += percent * difY;
					ctx.lineTo(lastX, lastY);
					travelled += width;
				}
			}
			space = !space;
		}
	};

	ext.contextRoundRect = function(ctx, x, y, width, height, radius) {
		ctx.beginPath();
		ctx.moveTo(x + radius, y);
		ctx.lineTo(x + width - radius, y);
		ctx.quadraticCurveTo(x + width, y, x + width, y + radius);
		ctx.lineTo(x + width, y + height - radius);
		ctx.quadraticCurveTo(x + width, y + height, x + width - radius, y + height);
		ctx.lineTo(x + radius, y + height);
		ctx.quadraticCurveTo(x, y + height, x, y + height - radius);
		ctx.lineTo(x, y + radius);
		ctx.quadraticCurveTo(x, y, x + radius, y);
		ctx.closePath();
	};

	ext.contextEllipse = function(ctx, x, y, w, h) {
		var kappa = .5522848;
		var ox = (w / 2) * kappa;
		var oy = (h / 2) * kappa;
		var xe = x + w;
		var ye = y + h;
		var xm = x + w / 2;
		var ym = y + h / 2;

		ctx.beginPath();
		ctx.moveTo(x, ym);
		ctx.bezierCurveTo(x, ym - oy, xm - ox, y, xm, y);
		ctx.bezierCurveTo(xm + ox, y, xe, ym - oy, xe, ym);
		ctx.bezierCurveTo(xe, ym + oy, xm + ox, ye, xm, ye);
		ctx.bezierCurveTo(xm - ox, ye, x, ym + oy, x, ym);
		ctx.closePath();
	};

	ext.getFontString = function(size, families, bold, italic) {
		var sb = [];
		if (bold) {
			sb.push('bold ');
		}
		if (italic) {
			sb.push('italic ');
		}
		sb.push(size + 'px ');
		for ( var i = 0, ii = families.length; i < ii; i++) {
			var use = families[i];
			if (use.indexOf(' ') !== -1) {
				use = '"' + use + '"';
			}
			sb.push((i !== 0 ? ',' : '') + use);
		}
		return sb.join('');
	};

	return ext;

})(ChemDoodle.structures, ChemDoodle.lib.vec3, Math);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//
ChemDoodle.math = (function(c, extensions, structures, q, m) {
	'use strict';
	var pack = {};
	var namedColors = {
		'aliceblue' : '#f0f8ff',
		'antiquewhite' : '#faebd7',
		'aqua' : '#00ffff',
		'aquamarine' : '#7fffd4',
		'azure' : '#f0ffff',
		'beige' : '#f5f5dc',
		'bisque' : '#ffe4c4',
		'black' : '#000000',
		'blanchedalmond' : '#ffebcd',
		'blue' : '#0000ff',
		'blueviolet' : '#8a2be2',
		'brown' : '#a52a2a',
		'burlywood' : '#deb887',
		'cadetblue' : '#5f9ea0',
		'chartreuse' : '#7fff00',
		'chocolate' : '#d2691e',
		'coral' : '#ff7f50',
		'cornflowerblue' : '#6495ed',
		'cornsilk' : '#fff8dc',
		'crimson' : '#dc143c',
		'cyan' : '#00ffff',
		'darkblue' : '#00008b',
		'darkcyan' : '#008b8b',
		'darkgoldenrod' : '#b8860b',
		'darkgray' : '#a9a9a9',
		'darkgreen' : '#006400',
		'darkkhaki' : '#bdb76b',
		'darkmagenta' : '#8b008b',
		'darkolivegreen' : '#556b2f',
		'darkorange' : '#ff8c00',
		'darkorchid' : '#9932cc',
		'darkred' : '#8b0000',
		'darksalmon' : '#e9967a',
		'darkseagreen' : '#8fbc8f',
		'darkslateblue' : '#483d8b',
		'darkslategray' : '#2f4f4f',
		'darkturquoise' : '#00ced1',
		'darkviolet' : '#9400d3',
		'deeppink' : '#ff1493',
		'deepskyblue' : '#00bfff',
		'dimgray' : '#696969',
		'dodgerblue' : '#1e90ff',
		'firebrick' : '#b22222',
		'floralwhite' : '#fffaf0',
		'forestgreen' : '#228b22',
		'fuchsia' : '#ff00ff',
		'gainsboro' : '#dcdcdc',
		'ghostwhite' : '#f8f8ff',
		'gold' : '#ffd700',
		'goldenrod' : '#daa520',
		'gray' : '#808080',
		'green' : '#008000',
		'greenyellow' : '#adff2f',
		'honeydew' : '#f0fff0',
		'hotpink' : '#ff69b4',
		'indianred ' : '#cd5c5c',
		'indigo ' : '#4b0082',
		'ivory' : '#fffff0',
		'khaki' : '#f0e68c',
		'lavender' : '#e6e6fa',
		'lavenderblush' : '#fff0f5',
		'lawngreen' : '#7cfc00',
		'lemonchiffon' : '#fffacd',
		'lightblue' : '#add8e6',
		'lightcoral' : '#f08080',
		'lightcyan' : '#e0ffff',
		'lightgoldenrodyellow' : '#fafad2',
		'lightgrey' : '#d3d3d3',
		'lightgreen' : '#90ee90',
		'lightpink' : '#ffb6c1',
		'lightsalmon' : '#ffa07a',
		'lightseagreen' : '#20b2aa',
		'lightskyblue' : '#87cefa',
		'lightslategray' : '#778899',
		'lightsteelblue' : '#b0c4de',
		'lightyellow' : '#ffffe0',
		'lime' : '#00ff00',
		'limegreen' : '#32cd32',
		'linen' : '#faf0e6',
		'magenta' : '#ff00ff',
		'maroon' : '#800000',
		'mediumaquamarine' : '#66cdaa',
		'mediumblue' : '#0000cd',
		'mediumorchid' : '#ba55d3',
		'mediumpurple' : '#9370d8',
		'mediumseagreen' : '#3cb371',
		'mediumslateblue' : '#7b68ee',
		'mediumspringgreen' : '#00fa9a',
		'mediumturquoise' : '#48d1cc',
		'mediumvioletred' : '#c71585',
		'midnightblue' : '#191970',
		'mintcream' : '#f5fffa',
		'mistyrose' : '#ffe4e1',
		'moccasin' : '#ffe4b5',
		'navajowhite' : '#ffdead',
		'navy' : '#000080',
		'oldlace' : '#fdf5e6',
		'olive' : '#808000',
		'olivedrab' : '#6b8e23',
		'orange' : '#ffa500',
		'orangered' : '#ff4500',
		'orchid' : '#da70d6',
		'palegoldenrod' : '#eee8aa',
		'palegreen' : '#98fb98',
		'paleturquoise' : '#afeeee',
		'palevioletred' : '#d87093',
		'papayawhip' : '#ffefd5',
		'peachpuff' : '#ffdab9',
		'peru' : '#cd853f',
		'pink' : '#ffc0cb',
		'plum' : '#dda0dd',
		'powderblue' : '#b0e0e6',
		'purple' : '#800080',
		'red' : '#ff0000',
		'rosybrown' : '#bc8f8f',
		'royalblue' : '#4169e1',
		'saddlebrown' : '#8b4513',
		'salmon' : '#fa8072',
		'sandybrown' : '#f4a460',
		'seagreen' : '#2e8b57',
		'seashell' : '#fff5ee',
		'sienna' : '#a0522d',
		'silver' : '#c0c0c0',
		'skyblue' : '#87ceeb',
		'slateblue' : '#6a5acd',
		'slategray' : '#708090',
		'snow' : '#fffafa',
		'springgreen' : '#00ff7f',
		'steelblue' : '#4682b4',
		'tan' : '#d2b48c',
		'teal' : '#008080',
		'thistle' : '#d8bfd8',
		'tomato' : '#ff6347',
		'turquoise' : '#40e0d0',
		'violet' : '#ee82ee',
		'wheat' : '#f5deb3',
		'white' : '#ffffff',
		'whitesmoke' : '#f5f5f5',
		'yellow' : '#ffff00',
		'yellowgreen' : '#9acd32'
	};
	pack.angleBetweenLargest = function(angles) {
		if (angles.length === 0) {
			return {
				angle : 0,
				largest : m.PI * 2
			};
		}
		if (angles.length === 1) {
			return {
				angle : angles[0] + m.PI,
				largest : m.PI * 2
			};
		}
		var largest = 0;
		var angle = 0;
		for ( var i = 0, ii = angles.length - 1; i < ii; i++) {
			var dif = angles[i + 1] - angles[i];
			if (dif > largest) {
				largest = dif;
				angle = (angles[i + 1] + angles[i]) / 2;
			}
		}
		var last = angles[0] + m.PI * 2 - angles[angles.length - 1];
		if (last > largest) {
			angle = angles[0] - last / 2;
			largest = last;
			if (angle < 0) {
				angle += m.PI * 2;
			}
		}
		return {
			angle : angle,
			largest : largest
		};
	};
	pack.isBetween = function(x, left, right) {
		if (left > right) {
			var tmp = left;
			left = right;
			right = tmp;
		}
		return x >= left && x <= right;
	};
	// be careful not to remove this, as this will cause corruption issues
	// contact iChemLabs for instructions to remove this
	q(document).ready(function() {
		if(c && c.iChemLabs && c.iChemLabs.checkForUpdates){
			c.iChemLabs.checkForUpdates({});
		}
	});
	pack.getRGB = function(color, multiplier) {
		var err = [ 0, 0, 0 ];
		if (namedColors[color.toLowerCase()]) {
			color = namedColors[color.toLowerCase()];
		}
		if (color.charAt(0) === '#') {
			if (color.length === 4) {
				color = '#' + color.charAt(1) + color.charAt(1) + color.charAt(2) + color.charAt(2) + color.charAt(3) + color.charAt(3);
			}
			return [ parseInt(color.substring(1, 3), 16) / 255.0 * multiplier, parseInt(color.substring(3, 5), 16) / 255.0 * multiplier, parseInt(color.substring(5, 7), 16) / 255.0 * multiplier ];
		} else if (extensions.stringStartsWith(color, 'rgb')) {
			var cs = color.replace(/rgb\(|\)/g, '').split(',');
			if (cs.length !== 3) {
				return err;
			}
			return [ parseInt(cs[0]) / 255.0 * multiplier, parseInt(cs[1]) / 255.0 * multiplier, parseInt(cs[2]) / 255.0 * multiplier ];
		}
		return err;
	};
	pack.idx2color = function(value) {
		var hex = value.toString(16);
		// add '0' padding
		for ( var i = 0, ii = 6 - hex.length; i < ii; i++) {
			hex = "0" + hex;
		}
		return "#" + hex;
	};
	pack.distanceFromPointToLineInclusive = function(p, l1, l2) {
		var length = l1.distance(l2);
		var angle = l1.angle(l2);
		var angleDif = m.PI / 2 - angle;
		var newAngleP = l1.angle(p) + angleDif;
		var pDist = l1.distance(p);
		var pcopRot = new structures.Point(pDist * m.cos(newAngleP), -pDist * m.sin(newAngleP));
		if (pack.isBetween(-pcopRot.y, 0, length)) {
			return m.abs(pcopRot.x);
		}
		return -1;
	};
	pack.calculateDistanceInterior = function(to, from, r) {
		if (this.isBetween(from.x, r.x, r.x + r.w) && this.isBetween(from.y, r.y, r.y + r.w)) {
			return to.distance(from);
		}
		// calculates the distance that a line needs to remove from itself to be
		// outside that rectangle
		var lines = [];
		// top
		lines.push({
			x1 : r.x,
			y1 : r.y,
			x2 : r.x + r.w,
			y2 : r.y
		});
		// bottom
		lines.push({
			x1 : r.x,
			y1 : r.y + r.h,
			x2 : r.x + r.w,
			y2 : r.y + r.h
		});
		// left
		lines.push({
			x1 : r.x,
			y1 : r.y,
			x2 : r.x,
			y2 : r.y + r.h
		});
		// right
		lines.push({
			x1 : r.x + r.w,
			y1 : r.y,
			x2 : r.x + r.w,
			y2 : r.y + r.h
		});
		var intersections = [];
		for ( var i = 0; i < 4; i++) {
			var l = lines[i];
			var p = this.intersectLines(from.x, from.y, to.x, to.y, l.x1, l.y1, l.x2, l.y2);
			if (p) {
				intersections.push(p);
			}
		}
		if (intersections.length === 0) {
			return 0;
		}
		var max = 0;
		for ( var i = 0, ii = intersections.length; i < ii; i++) {
			var p = intersections[i];
			var dx = to.x - p.x;
			var dy = to.y - p.y;
			max = m.max(max, m.sqrt(dx * dx + dy * dy));
		}
		return max;
	};
	pack.intersectLines = function(ax, ay, bx, by, cx, cy, dx, dy) {
		// calculate the direction vectors
		bx -= ax;
		by -= ay;
		dx -= cx;
		dy -= cy;
		// are they parallel?
		var denominator = by * dx - bx * dy;
		if (denominator === 0) {
			return false;
		}
		// calculate point of intersection
		var r = (dy * (ax - cx) - dx * (ay - cy)) / denominator;
		var s = (by * (ax - cx) - bx * (ay - cy)) / denominator;
		if ((s >= 0) && (s <= 1) && (r >= 0) && (r <= 1)) {
			return {
				x : (ax + r * bx),
				y : (ay + r * by)
			};
		} else {
			return false;
		}
	};
	pack.hsl2rgb = function(h, s, l) {
		var hue2rgb = function(p, q, t) {
			if (t < 0) {
				t += 1;
			} else if (t > 1) {
				t -= 1;
			}
			if (t < 1 / 6) {
				return p + (q - p) * 6 * t;
			} else if (t < 1 / 2) {
				return q;
			} else if (t < 2 / 3) {
				return p + (q - p) * (2 / 3 - t) * 6;
			}
			return p;
		};
		var r, g, b;
		if (s === 0) {
			r = g = b = l; // achromatic
		} else {
			var q = l < 0.5 ? l * (1 + s) : l + s - l * s;
			var p = 2 * l - q;
			r = hue2rgb(p, q, h + 1 / 3);
			g = hue2rgb(p, q, h);
			b = hue2rgb(p, q, h - 1 / 3);
		}
		return [ r * 255, g * 255, b * 255 ];
	};
	pack.isPointInPoly = function(poly, pt) {
		for ( var c = false, i = -1, l = poly.length, j = l - 1; ++i < l; j = i) {
			((poly[i].y <= pt.y && pt.y < poly[j].y) || (poly[j].y <= pt.y && pt.y < poly[i].y)) && (pt.x < (poly[j].x - poly[i].x) * (pt.y - poly[i].y) / (poly[j].y - poly[i].y) + poly[i].x) && (c = !c);
		}
		return c;
	};
	pack.clamp = function(value, min, max) {
		return value < min ? min : value > max ? max : value;
	};
	pack.rainbowAt = function(i, ii, colors) {
		// The rainbow colors length must be more than one color
		if (colors.length < 1) {
			colors.push('#000000', '#FFFFFF');
		} else if (colors.length < 2) {
			colors.push('#FFFFFF');
		}
		var step = ii / (colors.length - 1);
		var j = m.floor(i / step);
		var t = (i - j * step) / step;
		var startColor = pack.getRGB(colors[j], 1);
		var endColor = pack.getRGB(colors[j + 1], 1);
		var lerpColor = [ (startColor[0] + (endColor[0] - startColor[0]) * t) * 255, (startColor[1] + (endColor[1] - startColor[1]) * t) * 255, (startColor[2] + (endColor[2] - startColor[2]) * t) * 255 ];
		return 'rgb(' + lerpColor.join(',') + ')';
	};
	pack.angleBounds = function(angle, convertToDegrees, limitToPi) {
		var full = m.PI*2;
		while(angle<0){
			angle+=full;
		}
		while(angle>full){
			angle-=full;
		}
		if(limitToPi && angle>m.PI){
			angle = 2*m.PI-angle;
		}
		if(convertToDegrees){
			angle = 180*angle/m.PI;
		}
		return angle;
	};
	return pack;
})(ChemDoodle, ChemDoodle.extensions, ChemDoodle.structures, ChemDoodle.lib.jQuery, Math);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(math, m) {
	'use strict';
	math.Bounds = function() {
	};
	var _ = math.Bounds.prototype;
	_.minX = _.minY = _.minZ = Infinity;
	_.maxX = _.maxY = _.maxZ = -Infinity;
	_.expand = function(x1, y1, x2, y2) {
		if (x1 instanceof math.Bounds) {
			// only need to compare min and max since bounds already has
			// them ordered
			this.minX = m.min(this.minX, x1.minX);
			this.minY = m.min(this.minY, x1.minY);
			this.maxX = m.max(this.maxX, x1.maxX);
			this.maxY = m.max(this.maxY, x1.maxY);
			if(x1.maxZ!==Infinity){
				this.minZ = m.min(this.minZ, x1.minZ);
				this.maxZ = m.max(this.maxZ, x1.maxZ);
			}
		} else {
			this.minX = m.min(this.minX, x1);
			this.maxX = m.max(this.maxX, x1);
			this.minY = m.min(this.minY, y1);
			this.maxY = m.max(this.maxY, y1);
			// these two values could be 0, so check if undefined
			if (x2 !== undefined && y2 !== undefined) {
				this.minX = m.min(this.minX, x2);
				this.maxX = m.max(this.maxX, x2);
				this.minY = m.min(this.minY, y2);
				this.maxY = m.max(this.maxY, y2);
			}
		}
	};
	_.expand3D = function(x1, y1, z1, x2, y2, z2) {
		this.minX = m.min(this.minX, x1);
		this.maxX = m.max(this.maxX, x1);
		this.minY = m.min(this.minY, y1);
		this.maxY = m.max(this.maxY, y1);
		this.minZ = m.min(this.minZ, z1);
		this.maxZ = m.max(this.maxZ, z1);
		// these two values could be 0, so check if undefined
		if (x2 !== undefined && y2 !== undefined && z2 !== undefined) {
			this.minX = m.min(this.minX, x2);
			this.maxX = m.max(this.maxX, x2);
			this.minY = m.min(this.minY, y2);
			this.maxY = m.max(this.maxY, y2);
			this.minZ = m.min(this.minZ, z2);
			this.maxZ = m.max(this.maxZ, z2);
		}
	};

})(ChemDoodle.math, Math);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

/**
 * jsBezier-0.5
 * 
 * Copyright (c) 2010 - 2011 Simon Porritt (simon.porritt@gmail.com)
 * 
 * licensed under the MIT license.
 * 
 * a set of Bezier curve functions that deal with Beziers, used by jsPlumb, and
 * perhaps useful for other people. These functions work with Bezier curves of
 * arbitrary degree.
 *  - functions are all in the 'jsBezier' namespace.
 *  - all input points should be in the format {x:.., y:..}. all output points
 * are in this format too.
 *  - all input curves should be in the format [ {x:.., y:..}, {x:.., y:..},
 * {x:.., y:..}, {x:.., y:..} ]
 *  - 'location' as used as an input here refers to a decimal in the range 0-1
 * inclusive, which indicates a point some proportion along the length of the
 * curve. location as output has the same format and meaning.
 * 
 * 
 * Function List: --------------
 * 
 * distanceFromCurve(point, curve)
 * 
 * Calculates the distance that the given point lies from the given Bezier. Note
 * that it is computed relative to the center of the Bezier, so if you have
 * stroked the curve with a wide pen you may wish to take that into account! The
 * distance returned is relative to the values of the curve and the point - it
 * will most likely be pixels.
 * 
 * gradientAtPoint(curve, location)
 * 
 * Calculates the gradient to the curve at the given location, as a decimal
 * between 0 and 1 inclusive.
 * 
 * gradientAtPointAlongCurveFrom (curve, location)
 * 
 * Calculates the gradient at the point on the given curve that is 'distance'
 * units from location.
 * 
 * nearestPointOnCurve(point, curve)
 * 
 * Calculates the nearest point to the given point on the given curve. The
 * return value of this is a JS object literal, containing both the point's
 * coordinates and also the 'location' of the point (see above), for example: {
 * point:{x:551,y:150}, location:0.263365 }.
 * 
 * pointOnCurve(curve, location)
 * 
 * Calculates the coordinates of the point on the given Bezier curve at the
 * given location.
 * 
 * pointAlongCurveFrom(curve, location, distance)
 * 
 * Calculates the coordinates of the point on the given curve that is 'distance'
 * units from location. 'distance' should be in the same coordinate space as
 * that used to construct the Bezier curve. For an HTML Canvas usage, for
 * example, distance would be a measure of pixels.
 * 
 * locationAlongCurveFrom(curve, location, distance)
 * 
 * Calculates the location on the given curve that is 'distance' units from
 * location. 'distance' should be in the same coordinate space as that used to
 * construct the Bezier curve. For an HTML Canvas usage, for example, distance
 * would be a measure of pixels.
 * 
 * perpendicularToCurveAt(curve, location, length, distance)
 * 
 * Calculates the perpendicular to the given curve at the given location. length
 * is the length of the line you wish for (it will be centered on the point at
 * 'location'). distance is optional, and allows you to specify a point along
 * the path from the given location as the center of the perpendicular returned.
 * The return value of this is an array of two points: [ {x:...,y:...},
 * {x:...,y:...} ].
 * 
 * 
 */

(function(math) {
	'use strict';
	function sgn(x) {
		return x == 0 ? 0 : x > 0 ? 1 : -1;
	}

	var Vectors = {
		subtract : function(v1, v2) {
			return {
				x : v1.x - v2.x,
				y : v1.y - v2.y
			};
		},
		dotProduct : function(v1, v2) {
			return (v1.x * v2.x) + (v1.y * v2.y);
		},
		square : function(v) {
			return Math.sqrt((v.x * v.x) + (v.y * v.y));
		},
		scale : function(v, s) {
			return {
				x : v.x * s,
				y : v.y * s
			};
		}
	},

	maxRecursion = 64, flatnessTolerance = Math.pow(2.0, -maxRecursion - 1);

	/**
	 * Calculates the distance that the point lies from the curve.
	 * 
	 * @param point
	 *            a point in the form {x:567, y:3342}
	 * @param curve
	 *            a Bezier curve in the form [{x:..., y:...}, {x:..., y:...},
	 *            {x:..., y:...}, {x:..., y:...}]. note that this is currently
	 *            hardcoded to assume cubiz beziers, but would be better off
	 *            supporting any degree.
	 * @return a JS object literal containing location and distance, for
	 *         example: {location:0.35, distance:10}. Location is analogous to
	 *         the location argument you pass to the pointOnPath function: it is
	 *         a ratio of distance travelled along the curve. Distance is the
	 *         distance in pixels from the point to the curve.
	 */
	var _distanceFromCurve = function(point, curve) {
		var candidates = [], w = _convertToBezier(point, curve), degree = curve.length - 1, higherDegree = (2 * degree) - 1, numSolutions = _findRoots(w, higherDegree, candidates, 0), v = Vectors.subtract(point, curve[0]), dist = Vectors.square(v), t = 0.0;

		for ( var i = 0; i < numSolutions; i++) {
			v = Vectors.subtract(point, _bezier(curve, degree, candidates[i], null, null));
			var newDist = Vectors.square(v);
			if (newDist < dist) {
				dist = newDist;
				t = candidates[i];
			}
		}
		v = Vectors.subtract(point, curve[degree]);
		newDist = Vectors.square(v);
		if (newDist < dist) {
			dist = newDist;
			t = 1.0;
		}
		return {
			location : t,
			distance : dist
		};
	};
	/**
	 * finds the nearest point on the curve to the given point.
	 */
	var _nearestPointOnCurve = function(point, curve) {
		var td = _distanceFromCurve(point, curve);
		return {
			point : _bezier(curve, curve.length - 1, td.location, null, null),
			location : td.location
		};
	};
	var _convertToBezier = function(point, curve) {
		var degree = curve.length - 1, higherDegree = (2 * degree) - 1, c = [], d = [], cdTable = [], w = [], z = [ [ 1.0, 0.6, 0.3, 0.1 ], [ 0.4, 0.6, 0.6, 0.4 ], [ 0.1, 0.3, 0.6, 1.0 ] ];

		for ( var i = 0; i <= degree; i++)
			c[i] = Vectors.subtract(curve[i], point);
		for ( var i = 0; i <= degree - 1; i++) {
			d[i] = Vectors.subtract(curve[i + 1], curve[i]);
			d[i] = Vectors.scale(d[i], 3.0);
		}
		for ( var row = 0; row <= degree - 1; row++) {
			for ( var column = 0; column <= degree; column++) {
				if (!cdTable[row])
					cdTable[row] = [];
				cdTable[row][column] = Vectors.dotProduct(d[row], c[column]);
			}
		}
		for (i = 0; i <= higherDegree; i++) {
			if (!w[i])
				w[i] = [];
			w[i].y = 0.0;
			w[i].x = parseFloat(i) / higherDegree;
		}
		var n = degree, m = degree - 1;
		for ( var k = 0; k <= n + m; k++) {
			var lb = Math.max(0, k - m), ub = Math.min(k, n);
			for (i = lb; i <= ub; i++) {
				var j = k - i;
				w[i + j].y += cdTable[j][i] * z[j][i];
			}
		}
		return w;
	};
	/**
	 * counts how many roots there are.
	 */
	var _findRoots = function(w, degree, t, depth) {
		var left = [], right = [], left_count, right_count, left_t = [], right_t = [];

		switch (_getCrossingCount(w, degree)) {
		case 0: {
			return 0;
		}
		case 1: {
			if (depth >= maxRecursion) {
				t[0] = (w[0].x + w[degree].x) / 2.0;
				return 1;
			}
			if (_isFlatEnough(w, degree)) {
				t[0] = _computeXIntercept(w, degree);
				return 1;
			}
			break;
		}
		}
		_bezier(w, degree, 0.5, left, right);
		left_count = _findRoots(left, degree, left_t, depth + 1);
		right_count = _findRoots(right, degree, right_t, depth + 1);
		for ( var i = 0; i < left_count; i++)
			t[i] = left_t[i];
		for ( var i = 0; i < right_count; i++)
			t[i + left_count] = right_t[i];
		return (left_count + right_count);
	};
	var _getCrossingCount = function(curve, degree) {
		var n_crossings = 0, sign, old_sign;
		sign = old_sign = sgn(curve[0].y);
		for ( var i = 1; i <= degree; i++) {
			sign = sgn(curve[i].y);
			if (sign != old_sign)
				n_crossings++;
			old_sign = sign;
		}
		return n_crossings;
	};
	var _isFlatEnough = function(curve, degree) {
		var error, intercept_1, intercept_2, left_intercept, right_intercept, a, b, c, det, dInv, a1, b1, c1, a2, b2, c2;
		a = curve[0].y - curve[degree].y;
		b = curve[degree].x - curve[0].x;
		c = curve[0].x * curve[degree].y - curve[degree].x * curve[0].y;

		var max_distance_above = 0.0, max_distance_below = 0.0;

		for ( var i = 1; i < degree; i++) {
			var value = a * curve[i].x + b * curve[i].y + c;
			if (value > max_distance_above)
				max_distance_above = value;
			else if (value < max_distance_below)
				max_distance_below = value;
		}

		a1 = 0.0;
		b1 = 1.0;
		c1 = 0.0;
		a2 = a;
		b2 = b;
		c2 = c - max_distance_above;
		det = a1 * b2 - a2 * b1;
		dInv = 1.0 / det;
		intercept_1 = (b1 * c2 - b2 * c1) * dInv;
		a2 = a;
		b2 = b;
		c2 = c - max_distance_below;
		det = a1 * b2 - a2 * b1;
		dInv = 1.0 / det;
		intercept_2 = (b1 * c2 - b2 * c1) * dInv;
		left_intercept = Math.min(intercept_1, intercept_2);
		right_intercept = Math.max(intercept_1, intercept_2);
		error = right_intercept - left_intercept;
		return (error < flatnessTolerance) ? 1 : 0;
	};
	var _computeXIntercept = function(curve, degree) {
		var XLK = 1.0, YLK = 0.0, XNM = curve[degree].x - curve[0].x, YNM = curve[degree].y - curve[0].y, XMK = curve[0].x - 0.0, YMK = curve[0].y - 0.0, det = XNM * YLK - YNM * XLK, detInv = 1.0 / det, S = (XNM * YMK - YNM * XMK) * detInv;
		return 0.0 + XLK * S;
	};
	var _bezier = function(curve, degree, t, left, right) {
		var temp = [ [] ];
		for ( var j = 0; j <= degree; j++)
			temp[0][j] = curve[j];
		for ( var i = 1; i <= degree; i++) {
			for ( var j = 0; j <= degree - i; j++) {
				if (!temp[i])
					temp[i] = [];
				if (!temp[i][j])
					temp[i][j] = {};
				temp[i][j].x = (1.0 - t) * temp[i - 1][j].x + t * temp[i - 1][j + 1].x;
				temp[i][j].y = (1.0 - t) * temp[i - 1][j].y + t * temp[i - 1][j + 1].y;
			}
		}
		if (left != null)
			for (j = 0; j <= degree; j++)
				left[j] = temp[j][0];
		if (right != null)
			for (j = 0; j <= degree; j++)
				right[j] = temp[degree - j][j];

		return (temp[degree][0]);
	};

	var _curveFunctionCache = {};
	var _getCurveFunctions = function(order) {
		var fns = _curveFunctionCache[order];
		if (!fns) {
			fns = [];
			var f_term = function() {
				return function(t) {
					return Math.pow(t, order);
				};
			}, l_term = function() {
				return function(t) {
					return Math.pow((1 - t), order);
				};
			}, c_term = function(c) {
				return function(t) {
					return c;
				};
			}, t_term = function() {
				return function(t) {
					return t;
				};
			}, one_minus_t_term = function() {
				return function(t) {
					return 1 - t;
				};
			}, _termFunc = function(terms) {
				return function(t) {
					var p = 1;
					for ( var i = 0; i < terms.length; i++)
						p = p * terms[i](t);
					return p;
				};
			};

			fns.push(new f_term()); // first is t to the power of the curve
									// order
			for ( var i = 1; i < order; i++) {
				var terms = [ new c_term(order) ];
				for ( var j = 0; j < (order - i); j++)
					terms.push(new t_term());
				for ( var j = 0; j < i; j++)
					terms.push(new one_minus_t_term());
				fns.push(new _termFunc(terms));
			}
			fns.push(new l_term()); // last is (1-t) to the power of the curve
									// order

			_curveFunctionCache[order] = fns;
		}

		return fns;
	};

	/**
	 * calculates a point on the curve, for a Bezier of arbitrary order.
	 * 
	 * @param curve
	 *            an array of control points, eg [{x:10,y:20}, {x:50,y:50},
	 *            {x:100,y:100}, {x:120,y:100}]. For a cubic bezier this should
	 *            have four points.
	 * @param location
	 *            a decimal indicating the distance along the curve the point
	 *            should be located at. this is the distance along the curve as
	 *            it travels, taking the way it bends into account. should be a
	 *            number from 0 to 1, inclusive.
	 */
	var _pointOnPath = function(curve, location) {
		var cc = _getCurveFunctions(curve.length - 1), _x = 0, _y = 0;
		for ( var i = 0; i < curve.length; i++) {
			_x = _x + (curve[i].x * cc[i](location));
			_y = _y + (curve[i].y * cc[i](location));
		}

		return {
			x : _x,
			y : _y
		};
	};

	var _dist = function(p1, p2) {
		return Math.sqrt(Math.pow(p1.x - p2.x, 2) + Math.pow(p1.y - p2.y, 2));
	};

	/**
	 * finds the point that is 'distance' along the path from 'location'. this
	 * method returns both the x,y location of the point and also its 'location'
	 * (proportion of travel along the path); the method below -
	 * _pointAlongPathFrom - calls this method and just returns the point.
	 */
	var _pointAlongPath = function(curve, location, distance) {
		var prev = _pointOnPath(curve, location), tally = 0, curLoc = location, direction = distance > 0 ? 1 : -1, cur = null;

		while (tally < Math.abs(distance)) {
			curLoc += (0.005 * direction);
			cur = _pointOnPath(curve, curLoc);
			tally += _dist(cur, prev);
			prev = cur;
		}
		return {
			point : cur,
			location : curLoc
		};
	};

	var _length = function(curve) {
		var prev = _pointOnPath(curve, 0), tally = 0, curLoc = 0, direction = 1, cur = null;

		while (curLoc < 1) {
			curLoc += (0.005 * direction);
			cur = _pointOnPath(curve, curLoc);
			tally += _dist(cur, prev);
			prev = cur;
		}
		return tally;
	};

	/**
	 * finds the point that is 'distance' along the path from 'location'.
	 */
	var _pointAlongPathFrom = function(curve, location, distance) {
		return _pointAlongPath(curve, location, distance).point;
	};

	/**
	 * finds the location that is 'distance' along the path from 'location'.
	 */
	var _locationAlongPathFrom = function(curve, location, distance) {
		return _pointAlongPath(curve, location, distance).location;
	};

	/**
	 * returns the gradient of the curve at the given location, which is a
	 * decimal between 0 and 1 inclusive.
	 * 
	 * thanks // http://bimixual.org/AnimationLibrary/beziertangents.html
	 */
	var _gradientAtPoint = function(curve, location) {
		var p1 = _pointOnPath(curve, location), p2 = _pointOnPath(curve.slice(0, curve.length - 1), location), dy = p2.y - p1.y, dx = p2.x - p1.x;
		return dy == 0 ? Infinity : Math.atan(dy / dx);
	};

	/**
	 * returns the gradient of the curve at the point which is 'distance' from
	 * the given location. if this point is greater than location 1, the
	 * gradient at location 1 is returned. if this point is less than location
	 * 0, the gradient at location 0 is returned.
	 */
	var _gradientAtPointAlongPathFrom = function(curve, location, distance) {
		var p = _pointAlongPath(curve, location, distance);
		if (p.location > 1)
			p.location = 1;
		if (p.location < 0)
			p.location = 0;
		return _gradientAtPoint(curve, p.location);
	};

	/**
	 * calculates a line that is 'length' pixels long, perpendicular to, and
	 * centered on, the path at 'distance' pixels from the given location. if
	 * distance is not supplied, the perpendicular for the given location is
	 * computed (ie. we set distance to zero).
	 */
	var _perpendicularToPathAt = function(curve, location, length, distance) {
		distance = distance == null ? 0 : distance;
		var p = _pointAlongPath(curve, location, distance), m = _gradientAtPoint(curve, p.location), _theta2 = Math.atan(-1 / m), y = length / 2 * Math.sin(_theta2), x = length / 2 * Math.cos(_theta2);
		return [ {
			x : p.point.x + x,
			y : p.point.y + y
		}, {
			x : p.point.x - x,
			y : p.point.y - y
		} ];
	};

	ChemDoodle.math.jsBezier = {
		distanceFromCurve : _distanceFromCurve,
		gradientAtPoint : _gradientAtPoint,
		gradientAtPointAlongCurveFrom : _gradientAtPointAlongPathFrom,
		nearestPointOnCurve : _nearestPointOnCurve,
		pointOnCurve : _pointOnPath,
		pointAlongCurveFrom : _pointAlongPathFrom,
		perpendicularToCurveAt : _perpendicularToPathAt,
		locationAlongCurveFrom : _locationAlongPathFrom,
		getLength : _length
	};
})(ChemDoodle.math);
//
//  Copyright 2006-2010 iChemLabs, LLC.  All rights reserved.
//

ChemDoodle.featureDetection = (function(iChemLabs, q, document, window) {
	'use strict';
	var features = {};

	features.supports_canvas = function() {
		return !!document.createElement('canvas').getContext;
	};

	features.supports_canvas_text = function() {
		if (!features.supports_canvas()) {
			return false;
		}
		var dummy_canvas = document.createElement('canvas');
		var context = dummy_canvas.getContext('2d');
		return typeof context.fillText === 'function';
	};

	features.supports_webgl = function() {
		var dummy_canvas = document.createElement('canvas');
		try {
			if (dummy_canvas.getContext('webgl')) {
				return true;
			}
			if (dummy_canvas.getContext('experimental-webgl')) {
				return true;
			}
		} catch (b) {
		}
		return false;
	};

	features.supports_xhr2 = function() {
		return q.support.cors;
	};

	features.supports_touch = function() {
		// check the mobile os so we don't interfere with hybrid pcs
		return 'ontouchstart' in window && navigator.userAgent.match(/iPhone|iPad|iPod|Android|BlackBerry|BB10/i);
	};

	features.supports_gesture = function() {
		return 'ongesturestart' in window;
	};

	return features;

})(ChemDoodle.iChemLabs, ChemDoodle.lib.jQuery, document, window);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

// all symbols
ChemDoodle.SYMBOLS = [ 'H', 'He', 'Li', 'Be', 'B', 'C', 'N', 'O', 'F', 'Ne', 'Na', 'Mg', 'Al', 'Si', 'P', 'S', 'Cl', 'Ar', 'K', 'Ca', 'Sc', 'Ti', 'V', 'Cr', 'Mn', 'Fe', 'Co', 'Ni', 'Cu', 'Zn', 'Ga', 'Ge', 'As', 'Se', 'Br', 'Kr', 'Rb', 'Sr', 'Y', 'Zr', 'Nb', 'Mo', 'Tc', 'Ru', 'Rh', 'Pd', 'Ag', 'Cd', 'In', 'Sn', 'Sb', 'Te', 'I', 'Xe', 'Cs', 'Ba', 'La', 'Ce', 'Pr', 'Nd', 'Pm', 'Sm', 'Eu', 'Gd', 'Tb', 'Dy', 'Ho', 'Er', 'Tm', 'Yb', 'Lu', 'Hf', 'Ta', 'W', 'Re', 'Os', 'Ir', 'Pt', 'Au', 'Hg', 'Tl',
		'Pb', 'Bi', 'Po', 'At', 'Rn', 'Fr', 'Ra', 'Ac', 'Th', 'Pa', 'U', 'Np', 'Pu', 'Am', 'Cm', 'Bk', 'Cf', 'Es', 'Fm', 'Md', 'No', 'Lr', 'Rf', 'Db', 'Sg', 'Bh', 'Hs', 'Mt', 'Ds', 'Rg', 'Cn', 'Uut', 'Uuq', 'Uup', 'Uuh', 'Uus', 'Uuo' ];

ChemDoodle.ELEMENT = (function(SYMBOLS) {
	'use strict';
	var E = [];

	function Element(symbol, name, atomicNumber, addH, color, covalentRadius, vdWRadius, valency, mass) {
		this.symbol = symbol;
		this.name = name;
		this.atomicNumber = atomicNumber;
		this.addH = addH;
		this.jmolColor = this.pymolColor = color;
		this.covalentRadius = covalentRadius;
		this.vdWRadius = vdWRadius;
		this.valency = valency;
		this.mass = mass;
	}

	E.H = new Element('H', 'Hydrogen', 1, false, '#FFFFFF', 0.31, 1.2, 1, 1);
	E.He = new Element('He', 'Helium', 2, false, '#D9FFFF', 0.28, 1.4, 0, 4);
	E.Li = new Element('Li', 'Lithium', 3, false, '#CC80FF', 1.28, 1.82, 1, 7);
	E.Be = new Element('Be', 'Beryllium', 4, false, '#C2FF00', 0.96, 0, 2, 9);
	E.B = new Element('B', 'Boron', 5, true, '#FFB5B5', 0.84, 0, 3, 11);
	E.C = new Element('C', 'Carbon', 6, true, '#909090', 0.76, 1.7, 4, 12);
	E.N = new Element('N', 'Nitrogen', 7, true, '#3050F8', 0.71, 1.55, 3, 14);
	E.O = new Element('O', 'Oxygen', 8, true, '#FF0D0D', 0.66, 1.52, 2, 16);
	E.F = new Element('F', 'Fluorine', 9, true, '#90E050', 0.57, 1.47, 1, 19);
	E.Ne = new Element('Ne', 'Neon', 10, false, '#B3E3F5', 0.58, 1.54, 0, 20);
	E.Na = new Element('Na', 'Sodium', 11, false, '#AB5CF2', 1.66, 2.27, 1, 23);
	E.Mg = new Element('Mg', 'Magnesium', 12, false, '#8AFF00', 1.41, 1.73, 0, 24);
	E.Al = new Element('Al', 'Aluminum', 13, false, '#BFA6A6', 1.21, 0, 0, 27);
	E.Si = new Element('Si', 'Silicon', 14, true, '#F0C8A0', 1.11, 2.1, 4, 28);
	E.P = new Element('P', 'Phosphorus', 15, true, '#FF8000', 1.07, 1.8, 3, 31);
	E.S = new Element('S', 'Sulfur', 16, true, '#FFFF30', 1.05, 1.8, 2, 32);
	E.Cl = new Element('Cl', 'Chlorine', 17, true, '#1FF01F', 1.02, 1.75, 1, 35);
	E.Ar = new Element('Ar', 'Argon', 18, false, '#80D1E3', 1.06, 1.88, 0, 40);
	E.K = new Element('K', 'Potassium', 19, false, '#8F40D4', 2.03, 2.75, 0, 39);
	E.Ca = new Element('Ca', 'Calcium', 20, false, '#3DFF00', 1.76, 0, 0, 40);
	E.Sc = new Element('Sc', 'Scandium', 21, false, '#E6E6E6', 1.7, 0, 0, 45);
	E.Ti = new Element('Ti', 'Titanium', 22, false, '#BFC2C7', 1.6, 0, 1, 48);
	E.V = new Element('V', 'Vanadium', 23, false, '#A6A6AB', 1.53, 0, 1, 51);
	E.Cr = new Element('Cr', 'Chromium', 24, false, '#8A99C7', 1.39, 0, 2, 52);
	E.Mn = new Element('Mn', 'Manganese', 25, false, '#9C7AC7', 1.39, 0, 3, 55);
	E.Fe = new Element('Fe', 'Iron', 26, false, '#E06633', 1.32, 0, 2, 56);
	E.Co = new Element('Co', 'Cobalt', 27, false, '#F090A0', 1.26, 0, 1, 59);
	E.Ni = new Element('Ni', 'Nickel', 28, false, '#50D050', 1.24, 1.63, 1, 58);
	E.Cu = new Element('Cu', 'Copper', 29, false, '#C88033', 1.32, 1.4, 0, 63);
	E.Zn = new Element('Zn', 'Zinc', 30, false, '#7D80B0', 1.22, 1.39, 0, 64);
	E.Ga = new Element('Ga', 'Gallium', 31, false, '#C28F8F', 1.22, 1.87, 0, 69);
	E.Ge = new Element('Ge', 'Germanium', 32, false, '#668F8F', 1.2, 0, 4, 74);
	E.As = new Element('As', 'Arsenic', 33, true, '#BD80E3', 1.19, 1.85, 3, 75);
	E.Se = new Element('Se', 'Selenium', 34, true, '#FFA100', 1.2, 1.9, 2, 80);
	E.Br = new Element('Br', 'Bromine', 35, true, '#A62929', 1.2, 1.85, 1, 79);
	E.Kr = new Element('Kr', 'Krypton', 36, false, '#5CB8D1', 1.16, 2.02, 0, 84);
	E.Rb = new Element('Rb', 'Rubidium', 37, false, '#702EB0', 2.2, 0, 0, 85);
	E.Sr = new Element('Sr', 'Strontium', 38, false, '#00FF00', 1.95, 0, 0, 88);
	E.Y = new Element('Y', 'Yttrium', 39, false, '#94FFFF', 1.9, 0, 0, 89);
	E.Zr = new Element('Zr', 'Zirconium', 40, false, '#94E0E0', 1.75, 0, 0, 90);
	E.Nb = new Element('Nb', 'Niobium', 41, false, '#73C2C9', 1.64, 0, 1, 93);
	E.Mo = new Element('Mo', 'Molybdenum', 42, false, '#54B5B5', 1.54, 0, 2, 98);
	E.Tc = new Element('Tc', 'Technetium', 43, false, '#3B9E9E', 1.47, 0, 3, 0);
	E.Ru = new Element('Ru', 'Ruthenium', 44, false, '#248F8F', 1.46, 0, 2, 102);
	E.Rh = new Element('Rh', 'Rhodium', 45, false, '#0A7D8C', 1.42, 0, 1, 103);
	E.Pd = new Element('Pd', 'Palladium', 46, false, '#006985', 1.39, 1.63, 0, 106);
	E.Ag = new Element('Ag', 'Silver', 47, false, '#C0C0C0', 1.45, 1.72, 0, 107);
	E.Cd = new Element('Cd', 'Cadmium', 48, false, '#FFD98F', 1.44, 1.58, 0, 114);
	E.In = new Element('In', 'Indium', 49, false, '#A67573', 1.42, 1.93, 0, 115);
	E.Sn = new Element('Sn', 'Tin', 50, false, '#668080', 1.39, 2.17, 4, 120);
	E.Sb = new Element('Sb', 'Antimony', 51, false, '#9E63B5', 1.39, 0, 3, 121);
	E.Te = new Element('Te', 'Tellurium', 52, true, '#D47A00', 1.38, 2.06, 2, 130);
	E.I = new Element('I', 'Iodine', 53, true, '#940094', 1.39, 1.98, 1, 127);
	E.Xe = new Element('Xe', 'Xenon', 54, false, '#429EB0', 1.4, 2.16, 0, 132);
	E.Cs = new Element('Cs', 'Cesium', 55, false, '#57178F', 2.44, 0, 0, 133);
	E.Ba = new Element('Ba', 'Barium', 56, false, '#00C900', 2.15, 0, 0, 138);
	E.La = new Element('La', 'Lanthanum', 57, false, '#70D4FF', 2.07, 0, 0, 139);
	E.Ce = new Element('Ce', 'Cerium', 58, false, '#FFFFC7', 2.04, 0, 0, 140);
	E.Pr = new Element('Pr', 'Praseodymium', 59, false, '#D9FFC7', 2.03, 0, 0, 141);
	E.Nd = new Element('Nd', 'Neodymium', 60, false, '#C7FFC7', 2.01, 0, 0, 142);
	E.Pm = new Element('Pm', 'Promethium', 61, false, '#A3FFC7', 1.99, 0, 0, 0);
	E.Sm = new Element('Sm', 'Samarium', 62, false, '#8FFFC7', 1.98, 0, 0, 152);
	E.Eu = new Element('Eu', 'Europium', 63, false, '#61FFC7', 1.98, 0, 0, 153);
	E.Gd = new Element('Gd', 'Gadolinium', 64, false, '#45FFC7', 1.96, 0, 0, 158);
	E.Tb = new Element('Tb', 'Terbium', 65, false, '#30FFC7', 1.94, 0, 0, 159);
	E.Dy = new Element('Dy', 'Dysprosium', 66, false, '#1FFFC7', 1.92, 0, 0, 164);
	E.Ho = new Element('Ho', 'Holmium', 67, false, '#00FF9C', 1.92, 0, 0, 165);
	E.Er = new Element('Er', 'Erbium', 68, false, '#00E675', 1.89, 0, 0, 166);
	E.Tm = new Element('Tm', 'Thulium', 69, false, '#00D452', 1.9, 0, 0, 169);
	E.Yb = new Element('Yb', 'Ytterbium', 70, false, '#00BF38', 1.87, 0, 0, 174);
	E.Lu = new Element('Lu', 'Lutetium', 71, false, '#00AB24', 1.87, 0, 0, 175);
	E.Hf = new Element('Hf', 'Hafnium', 72, false, '#4DC2FF', 1.75, 0, 0, 180);
	E.Ta = new Element('Ta', 'Tantalum', 73, false, '#4DA6FF', 1.7, 0, 1, 181);
	E.W = new Element('W', 'Tungsten', 74, false, '#2194D6', 1.62, 0, 2, 184);
	E.Re = new Element('Re', 'Rhenium', 75, false, '#267DAB', 1.51, 0, 3, 187);
	E.Os = new Element('Os', 'Osmium', 76, false, '#266696', 1.44, 0, 2, 192);
	E.Ir = new Element('Ir', 'Iridium', 77, false, '#175487', 1.41, 0, 3, 193);
	E.Pt = new Element('Pt', 'Platinum', 78, false, '#D0D0E0', 1.36, 1.75, 0, 195);
	E.Au = new Element('Au', 'Gold', 79, false, '#FFD123', 1.36, 1.66, 1, 197);
	E.Hg = new Element('Hg', 'Mercury', 80, false, '#B8B8D0', 1.32, 1.55, 0, 202);
	E.Tl = new Element('Tl', 'Thallium', 81, false, '#A6544D', 1.45, 1.96, 0, 205);
	E.Pb = new Element('Pb', 'Lead', 82, false, '#575961', 1.46, 2.02, 4, 208);
	E.Bi = new Element('Bi', 'Bismuth', 83, false, '#9E4FB5', 1.48, 0, 3, 209);
	E.Po = new Element('Po', 'Polonium', 84, false, '#AB5C00', 1.4, 0, 2, 0);
	E.At = new Element('At', 'Astatine', 85, true, '#754F45', 1.5, 0, 1, 0);
	E.Rn = new Element('Rn', 'Radon', 86, false, '#428296', 1.5, 0, 0, 0);
	E.Fr = new Element('Fr', 'Francium', 87, false, '#420066', 2.6, 0, 0, 0);
	E.Ra = new Element('Ra', 'Radium', 88, false, '#007D00', 2.21, 0, 0, 0);
	E.Ac = new Element('Ac', 'Actinium', 89, false, '#70ABFA', 2.15, 0, 0, 0);
	E.Th = new Element('Th', 'Thorium', 90, false, '#00BAFF', 2.06, 0, 0, 232);
	E.Pa = new Element('Pa', 'Protactinium', 91, false, '#00A1FF', 2, 0, 0, 231);
	E.U = new Element('U', 'Uranium', 92, false, '#008FFF', 1.96, 1.86, 0, 238);
	E.Np = new Element('Np', 'Neptunium', 93, false, '#0080FF', 1.9, 0, 0, 0);
	E.Pu = new Element('Pu', 'Plutonium', 94, false, '#006BFF', 1.87, 0, 0, 0);
	E.Am = new Element('Am', 'Americium', 95, false, '#545CF2', 1.8, 0, 0, 0);
	E.Cm = new Element('Cm', 'Curium', 96, false, '#785CE3', 1.69, 0, 0, 0);
	E.Bk = new Element('Bk', 'Berkelium', 97, false, '#8A4FE3', 0, 0, 0, 0);
	E.Cf = new Element('Cf', 'Californium', 98, false, '#A136D4', 0, 0, 0, 0);
	E.Es = new Element('Es', 'Einsteinium', 99, false, '#B31FD4', 0, 0, 0, 0);
	E.Fm = new Element('Fm', 'Fermium', 100, false, '#B31FBA', 0, 0, 0, 0);
	E.Md = new Element('Md', 'Mendelevium', 101, false, '#B30DA6', 0, 0, 0, 0);
	E.No = new Element('No', 'Nobelium', 102, false, '#BD0D87', 0, 0, 0, 0);
	E.Lr = new Element('Lr', 'Lawrencium', 103, false, '#C70066', 0, 0, 0, 0);
	E.Rf = new Element('Rf', 'Rutherfordium', 104, false, '#CC0059', 0, 0, 0, 0);
	E.Db = new Element('Db', 'Dubnium', 105, false, '#D1004F', 0, 0, 0, 0);
	E.Sg = new Element('Sg', 'Seaborgium', 106, false, '#D90045', 0, 0, 0, 0);
	E.Bh = new Element('Bh', 'Bohrium', 107, false, '#E00038', 0, 0, 0, 0);
	E.Hs = new Element('Hs', 'Hassium', 108, false, '#E6002E', 0, 0, 0, 0);
	E.Mt = new Element('Mt', 'Meitnerium', 109, false, '#EB0026', 0, 0, 0, 0);
	E.Ds = new Element('Ds', 'Darmstadtium', 110, false, '#000000', 0, 0, 0, 0);
	E.Rg = new Element('Rg', 'Roentgenium', 111, false, '#000000', 0, 0, 0, 0);
	E.Cn = new Element('Cn', 'Copernicium', 112, false, '#000000', 0, 0, 0, 0);
	E.Uut = new Element('Uut', 'Ununtrium', 113, false, '#000000', 0, 0, 0, 0);
	E.Uuq = new Element('Uuq', 'Ununquadium', 114, false, '#000000', 0, 0, 0, 0);
	E.Uup = new Element('Uup', 'Ununpentium', 115, false, '#000000', 0, 0, 0, 0);
	E.Uuh = new Element('Uuh', 'Ununhexium', 116, false, '#000000', 0, 0, 0, 0);
	E.Uus = new Element('Uus', 'Ununseptium', 117, false, '#000000', 0, 0, 0, 0);
	E.Uuo = new Element('Uuo', 'Ununoctium', 118, false, '#000000', 0, 0, 0, 0);

	E.H.pymolColor = '#E6E6E6';
	E.C.pymolColor = '#33FF33';
	E.N.pymolColor = '#3333FF';
	E.O.pymolColor = '#FF4D4D';
	E.F.pymolColor = '#B3FFFF';
	E.S.pymolColor = '#E6C640';

	return E;

})(ChemDoodle.SYMBOLS);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//
ChemDoodle.RESIDUE = (function() {
	'use strict';
	var R = [];

	function Residue(symbol, name, polar, aminoColor, shapelyColor, acidity) {
		this.symbol = symbol;
		this.name = name;
		this.polar = polar;
		this.aminoColor = aminoColor;
		this.shapelyColor = shapelyColor;
		this.acidity = acidity;
	}

	R.Ala = new Residue('Ala', 'Alanine', false, '#C8C8C8', '#8CFF8C', 0);
	R.Arg = new Residue('Arg', 'Arginine', true, '#145AFF', '#00007C', 1);
	R.Asn = new Residue('Asn', 'Asparagine', true, '#00DCDC', '#FF7C70', 0);
	R.Asp = new Residue('Asp', 'Aspartic Acid', true, '#E60A0A', '#A00042', -1);
	R.Cys = new Residue('Cys', 'Cysteine', true, '#E6E600', '#FFFF70', 0);
	R.Gln = new Residue('Gln', 'Glutamine', true, '#00DCDC', '#FF4C4C', 0);
	R.Glu = new Residue('Glu', 'Glutamic Acid', true, '#E60A0A', '#660000', -1);
	R.Gly = new Residue('Gly', 'Glycine', false, '#EBEBEB', '#FFFFFF', 0);
	R.His = new Residue('His', 'Histidine', true, '#8282D2', '#7070FF', 1);
	R.Ile = new Residue('Ile', 'Isoleucine', false, '#0F820F', '#004C00', 0);
	R.Leu = new Residue('Leu', 'Leucine', false, '#0F820F', '#455E45', 0);
	R.Lys = new Residue('Lys', 'Lysine', true, '#145AFF', '#4747B8', 1);
	R.Met = new Residue('Met', 'Methionine', false, '#E6E600', '#B8A042', 0);
	R.Phe = new Residue('Phe', 'Phenylalanine', false, '#3232AA', '#534C52', 0);
	R.Pro = new Residue('Pro', 'Proline', false, '#DC9682', '#525252', 0);
	R.Ser = new Residue('Ser', 'Serine', true, '#FA9600', '#FF7042', 0);
	R.Thr = new Residue('Thr', 'Threonine', true, '#FA9600', '#B84C00', 0);
	R.Trp = new Residue('Trp', 'Tryptophan', true, '#B45AB4', '#4F4600', 0);
	R.Tyr = new Residue('Tyr', 'Tyrosine', true, '#3232AA', '#8C704C', 0);
	R.Val = new Residue('Val', 'Valine', false, '#0F820F', '#FF8CFF', 0);
	R.Asx = new Residue('Asx', 'Asparagine/Aspartic Acid', true, '#FF69B4', '#FF00FF', 0);
	R.Glx = new Residue('Glx', 'Glutamine/Glutamic Acid', true, '#FF69B4', '#FF00FF', 0);
	R['*'] = new Residue('*', 'Other', false, '#BEA06E', '#FF00FF', 0);
	R.A = new Residue('A', 'Adenine', false, '#BEA06E', '#A0A0FF', 0);
	R.G = new Residue('G', 'Guanine', false, '#BEA06E', '#FF7070', 0);
	R.I = new Residue('I', '', false, '#BEA06E', '#80FFFF', 0);
	R.C = new Residue('C', 'Cytosine', false, '#BEA06E', '#FF8C4B', 0);
	R.T = new Residue('T', 'Thymine', false, '#BEA06E', '#A0FFA0', 0);
	R.U = new Residue('U', 'Uracil', false, '#BEA06E', '#FF8080', 0);

	return R;

})();
//
//  Copyright 2006-2010 iChemLabs, LLC.  All rights reserved.
//

(function(structures) {
	'use strict';
	/*
	 * Creates a new Queue. A Queue is a first-in-first-out (FIFO) data
	 * structure. Functions of the Queue object allow elements to be
	 * enthis.queued and dethis.queued, the first element to be obtained without
	 * dequeuing, and for the current size of the Queue and empty/non-empty
	 * status to be obtained.
	 */
	structures.Queue = function() {
		// the list of elements, initialised to the empty array
		this.queue = [];
	};
	var _ = structures.Queue.prototype;

	// the amount of space at the front of the this.queue, initialised to zero
	_.queueSpace = 0;

	/*
	 * Returns the size of this Queue. The size of a Queue is equal to the
	 * number of elements that have been enthis.queued minus the number of
	 * elements that have been dethis.queued.
	 */
	_.getSize = function() {

		// return the number of elements in the this.queue
		return this.queue.length - this.queueSpace;

	};

	/*
	 * Returns true if this Queue is empty, and false otherwise. A Queue is
	 * empty if the number of elements that have been enthis.queued equals the
	 * number of elements that have been dethis.queued.
	 */
	_.isEmpty = function() {

		// return true if the this.queue is empty, and false otherwise
		return this.queue.length === 0;

	};

	/*
	 * Enthis.queues the specified element in this Queue. The parameter is:
	 * 
	 * element - the element to enthis.queue
	 */
	_.enqueue = function(element) {
		this.queue.push(element);
	};

	/*
	 * Dethis.queues an element from this Queue. The oldest element in this
	 * Queue is removed and returned. If this Queue is empty then undefined is
	 * returned.
	 */
	_.dequeue = function() {

		// initialise the element to return to be undefined
		var element;

		// check whether the this.queue is empty
		if (this.queue.length) {

			// fetch the oldest element in the this.queue
			element = this.queue[this.queueSpace];

			// update the amount of space and check whether a shift should
			// occur
			if (++this.queueSpace * 2 >= this.queue.length) {

				// set the this.queue equal to the non-empty portion of the
				// this.queue
				this.queue = this.queue.slice(this.queueSpace);

				// reset the amount of space at the front of the this.queue
				this.queueSpace = 0;

			}

		}

		// return the removed element
		return element;

	};

	/*
	 * Returns the oldest element in this Queue. If this Queue is empty then
	 * undefined is returned. This function returns the same value as the
	 * dethis.queue function, but does not remove the returned element from this
	 * Queue.
	 */
	_.getOldestElement = function() {

		// initialise the element to return to be undefined
		var element;

		// if the this.queue is not element then fetch the oldest element in the
		// this.queue
		if (this.queue.length) {
			element = this.queue[this.queueSpace];
		}

		// return the oldest element
		return element;
	};

})(ChemDoodle.structures);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(structures, m) {
	'use strict';
	structures.Point = function(x, y) {
		this.x = x ? x : 0;
		this.y = y ? y : 0;
	};
	var _ = structures.Point.prototype;
	_.sub = function(p) {
		this.x -= p.x;
		this.y -= p.y;
	};
	_.add = function(p) {
		this.x += p.x;
		this.y += p.y;
	};
	_.distance = function(p) {
		var dx = p.x - this.x;
		var dy = p.y - this.y;
		return m.sqrt(dx * dx + dy * dy);
	};
	_.angleForStupidCanvasArcs = function(p) {
		var dx = p.x - this.x;
		var dy = p.y - this.y;
		var angle = 0;
		// Calculate angle
		if (dx === 0) {
			if (dy === 0) {
				angle = 0;
			} else if (dy > 0) {
				angle = m.PI / 2;
			} else {
				angle = 3 * m.PI / 2;
			}
		} else if (dy === 0) {
			if (dx > 0) {
				angle = 0;
			} else {
				angle = m.PI;
			}
		} else {
			if (dx < 0) {
				angle = m.atan(dy / dx) + m.PI;
			} else if (dy < 0) {
				angle = m.atan(dy / dx) + 2 * m.PI;
			} else {
				angle = m.atan(dy / dx);
			}
		}
		while (angle < 0) {
			angle += m.PI * 2;
		}
		angle = angle % (m.PI * 2);
		return angle;
	};
	_.angle = function(p) {
		// y is upside down to account for inverted canvas
		var dx = p.x - this.x;
		var dy = this.y - p.y;
		var angle = 0;
		// Calculate angle
		if (dx === 0) {
			if (dy === 0) {
				angle = 0;
			} else if (dy > 0) {
				angle = m.PI / 2;
			} else {
				angle = 3 * m.PI / 2;
			}
		} else if (dy === 0) {
			if (dx > 0) {
				angle = 0;
			} else {
				angle = m.PI;
			}
		} else {
			if (dx < 0) {
				angle = m.atan(dy / dx) + m.PI;
			} else if (dy < 0) {
				angle = m.atan(dy / dx) + 2 * m.PI;
			} else {
				angle = m.atan(dy / dx);
			}
		}
		while (angle < 0) {
			angle += m.PI * 2;
		}
		angle = angle % (m.PI * 2);
		return angle;
	};

})(ChemDoodle.structures, Math);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(extensions, structures, m) {
	'use strict';
	
	var COMMA_SPACE_REGEX = /[ ,]+/;
	var COMMA_DASH_REGEX = /\-+/;
	var FONTS = [ 'Helvetica', 'Arial', 'Dialog' ];
	
	structures.Query = function(type) {
		this.type = type;
		// atom properties
		this.elements = {v:[],not:false};
		this.charge = undefined;
		this.chirality = undefined;
		this.connectivity = undefined;
		this.connectivityNoH = undefined;
		this.hydrogens = undefined;
		this.saturation = undefined;
		// bond properties
		this.orders = {v:[],not:false};
		this.stereo = undefined;
		// generic properties
		this.aromatic = undefined;
		this.ringCount = undefined;
		// cache the string value
		this.cache = undefined;
	};
	structures.Query.TYPE_ATOM = 0;
	structures.Query.TYPE_BOND = 1;
	var _ = structures.Query.prototype;
	_.parseRange = function(range){
		var points = [];
		var splits = range.split(COMMA_SPACE_REGEX);
		for(var i = 0, ii = splits.length; i<ii; i++){
			var t = splits[i];
			var neg = false;
			var neg2 = false;
			if(t.charAt(0)==='-'){
				neg = true;
				t = t.substring(1);
			}
			if (t.indexOf('--')!=-1) {
				neg2 = true;
			}
			if (t.indexOf('-')!=-1) {
				var parts = t.split(COMMA_DASH_REGEX);
				var p = {x:parseInt(parts[0]) * (neg ? -1 : 1),y:parseInt(parts[1]) * (neg2 ? -1 : 1)};
				if (p.y < p.x) {
					var tmp = p.y;
					p.y = p.x;
					p.x = tmp;
				}
				points.push(p);
			} else {
				points.push({x:parseInt(t) * (neg ? -1 : 1)});
			}
		}
		return points;
	};
	_.draw = function(ctx, specs, pos) {
		if(!this.cache){
			this.cache = this.toString();
		}
		var top = this.cache;
		var bottom = undefined;
		var split = top.indexOf('(');
		if(split!=-1){
			top = this.cache.substring(0, split);
			bottom = this.cache.substring(split, this.cache.length);
		}
		ctx.textAlign = 'center';
		ctx.textBaseline = 'middle';
		ctx.font = extensions.getFontString(12, FONTS, true, false);
		var tw = ctx.measureText(top).width;
		ctx.fillStyle = specs.backgroundColor;
		ctx.fillRect(pos.x-tw/2, pos.y-6, tw, 12);
		ctx.fillStyle = 'black';
		ctx.fillText(top, pos.x, pos.y);
		if(bottom){
			ctx.font = extensions.getFontString(10, FONTS, false, true);
			tw = ctx.measureText(bottom).width;
			ctx.fillStyle = specs.backgroundColor;
			ctx.fillRect(pos.x-tw/2, pos.y+6, tw, 11);
			ctx.fillStyle = 'black';
			ctx.fillText(bottom, pos.x, pos.y+11);
		}
	};
	_.outputRange = function(array){
		var comma = false;
		var sb = [];
		for(var i = 0, ii = array.length; i<ii; i++){
			if(comma){
				sb.push(',');
			}
			comma = true;
			var p = array[i];
			if(p.y){
				sb.push(p.x);
				sb.push('-');
				sb.push(p.y);
			}else{
				sb.push(p.x);
			}
		}
		return sb.join('');
	};
	_.toString = function() {
		var sb = [];
		var attributes = [];
		if(this.type===structures.Query.TYPE_ATOM){
			if(!this.elements || this.elements.v.length===0){
				sb.push('[a]');
			}else{
				if(this.elements.not){
					sb.push('!');
				}
				sb.push('[');
				sb.push(this.elements.v.join(','));
				sb.push(']');
			}
			if(this.chirality){
				attributes.push((this.chirality.not?'!':'')+'@='+this.chirality.v);
			}
			if(this.aromatic){
				attributes.push((this.aromatic.not?'!':'')+'A');
			}
			if(this.charge){
				attributes.push((this.charge.not?'!':'')+'C='+this.outputRange(this.charge.v));
			}
			if(this.hydrogens){
				attributes.push((this.hydrogens.not?'!':'')+'H='+this.outputRange(this.hydrogens.v));
			}
			if(this.ringCount){
				attributes.push((this.ringCount.not?'!':'')+'R='+this.outputRange(this.ringCount.v));
			}
			if(this.saturation){
				attributes.push((this.saturation.not?'!':'')+'S');
			}
			if(this.connectivity){
				attributes.push((this.connectivity.not?'!':'')+'X='+this.outputRange(this.connectivity.v));
			}
			if(this.connectivityNoH){
				attributes.push((this.connectivityNoH.not?'!':'')+'x='+this.outputRange(this.connectivityNoH.v));
			}
		}else if(this.type===structures.Query.TYPE_BOND){
			if(!this.orders || this.orders.v.length===0){
				sb.push('[a]');
			}else{
				if(this.orders.not){
					sb.push('!');
				}
				sb.push('[');
				sb.push(this.orders.v.join(','));
				sb.push(']');
			}
			if(this.stereo){
				attributes.push((this.stereo.not?'!':'')+'@='+this.stereo.v);
			}
			if(this.aromatic){
				attributes.push((this.aromatic.not?'!':'')+'A');
			}
			if(this.ringCount){
				attributes.push((this.ringCount.not?'!':'')+'R='+this.outputRange(this.ringCount.v));
			}
		}
		if(attributes.length>0){
			sb.push('(');
			sb.push(attributes.join(','));
			sb.push(')');
		}
		return sb.join('');
	};

})(ChemDoodle.extensions, ChemDoodle.structures, Math);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(ELEMENT, extensions, math, structures, m, m4) {
	'use strict';
	structures.Atom = function(label, x, y, z) {
		this.label = label ? label.replace(/\s/g, '') : 'C';
		if (!ELEMENT[this.label]) {
			this.label = 'C';
		}
		this.x = x ? x : 0;
		this.y = y ? y : 0;
		this.z = z ? z : 0;
	};
	var _ = structures.Atom.prototype = new structures.Point(0, 0);
	_.charge = 0;
	_.numLonePair = 0;
	_.numRadical = 0;
	_.mass = -1;
	_.coordinationNumber = 0;
	_.bondNumber = 0;
	_.angleOfLeastInterference = 0;
	_.isHidden = false;
	_.altLabel = undefined;
	_.isLone = false;
	_.isHover = false;
	_.isSelected = false;
	_.add3D = function(p) {
		this.x += p.x;
		this.y += p.y;
		this.z += p.z;
	};
	_.sub3D = function(p) {
		this.x -= p.x;
		this.y -= p.y;
		this.z -= p.z;
	};
	_.distance3D = function(p) {
		var dx = p.x - this.x;
		var dy = p.y - this.y;
		var dz = p.z - this.z;
		return m.sqrt(dx * dx + dy * dy + dz * dz);
	};
	_.draw = function(ctx, specs) {
		if (this.isLassoed) {
			var grd = ctx.createRadialGradient(this.x - 1, this.y - 1, 0, this.x, this.y, 7);
			grd.addColorStop(0, 'rgba(212, 99, 0, 0)');
			grd.addColorStop(0.7, 'rgba(212, 99, 0, 0.8)');
			ctx.fillStyle = grd;
			ctx.beginPath();
			ctx.arc(this.x, this.y, 5, 0, m.PI * 2, false);
			ctx.fill();
		}
		if(this.query){
			return;
		}
		this.textBounds = [];
		if (this.specs) {
			specs = this.specs;
		}
		var font = extensions.getFontString(specs.atoms_font_size_2D, specs.atoms_font_families_2D, specs.atoms_font_bold_2D, specs.atoms_font_italic_2D);
		ctx.font = font;
		ctx.fillStyle = this.getElementColor(specs.atoms_useJMOLColors, specs.atoms_usePYMOLColors, specs.atoms_color, 2);
		if(this.label==='H' && specs.atoms_HBlack_2D){
			ctx.fillStyle = 'black';
		}
		var hAngle;
		if (this.isLone && !specs.atoms_displayAllCarbonLabels_2D || specs.atoms_circles_2D) {
			ctx.beginPath();
			ctx.arc(this.x, this.y, specs.atoms_circleDiameter_2D / 2, 0, m.PI * 2, false);
			ctx.fill();
			if (specs.atoms_circleBorderWidth_2D > 0) {
				ctx.lineWidth = specs.atoms_circleBorderWidth_2D;
				ctx.strokeStyle = 'black';
				ctx.stroke();
			}
		} else if (this.isLabelVisible(specs)) {
			ctx.textAlign = 'center';
			ctx.textBaseline = 'middle';
			// keep check to undefined here as dev may set altLabel to empty
			// string
			if (this.altLabel !== undefined) {
				// altLabel can be 0, so check if undefined
				ctx.fillText(this.altLabel, this.x, this.y);
				var symbolWidth = ctx.measureText(this.altLabel).width;
				this.textBounds.push({
					x : this.x - symbolWidth / 2,
					y : this.y - specs.atoms_font_size_2D / 2 + 1,
					w : symbolWidth,
					h : specs.atoms_font_size_2D - 2
				});
			} else {
				ctx.fillText(this.label, this.x, this.y);
				var symbolWidth = ctx.measureText(this.label).width;
				this.textBounds.push({
					x : this.x - symbolWidth / 2,
					y : this.y - specs.atoms_font_size_2D / 2 + 1,
					w : symbolWidth,
					h : specs.atoms_font_size_2D - 2
				});
				// mass
				var massWidth = 0;
				if (this.mass !== -1) {
					var fontSave = ctx.font;
					ctx.font = extensions.getFontString(specs.atoms_font_size_2D * .7, specs.atoms_font_families_2D, specs.atoms_font_bold_2D, specs.atoms_font_italic_2D);
					massWidth = ctx.measureText(this.mass).width;
					ctx.fillText(this.mass, this.x - massWidth - .5, this.y - specs.atoms_font_size_2D / 2 + 1);
					this.textBounds.push({
						x : this.x - symbolWidth / 2 - massWidth - .5,
						y : this.y - (specs.atoms_font_size_2D * 1.7) / 2 + 1,
						w : massWidth,
						h : specs.atoms_font_size_2D / 2 - 1
					});
					ctx.font = fontSave;
				}
				// implicit hydrogens
				var chargeOffset = symbolWidth / 2;
				var numHs = this.getImplicitHydrogenCount();
				if (specs.atoms_implicitHydrogens_2D && numHs > 0) {
					hAngle = 0;
					var hWidth = ctx.measureText('H').width;
					var moveCharge = true;
					if (numHs > 1) {
						var xoffset = symbolWidth / 2 + hWidth / 2;
						var yoffset = 0;
						var subFont = extensions.getFontString(specs.atoms_font_size_2D * .8, specs.atoms_font_families_2D, specs.atoms_font_bold_2D, specs.atoms_font_italic_2D);
						ctx.font = subFont;
						var numWidth = ctx.measureText(numHs).width;
						if (this.bondNumber === 1) {
							if (this.angleOfLeastInterference > m.PI / 2 && this.angleOfLeastInterference < 3 * m.PI / 2) {
								xoffset = -symbolWidth / 2 - numWidth - hWidth / 2 - massWidth / 2;
								moveCharge = false;
								hAngle = m.PI;
							}
						} else {
							if (this.angleOfLeastInterference <= m.PI / 4) {
								// default
							} else if (this.angleOfLeastInterference < 3 * m.PI / 4) {
								xoffset = 0;
								yoffset = -specs.atoms_font_size_2D * .9;
								if (this.charge !== 0) {
									yoffset -= specs.atoms_font_size_2D * .3;
								}
								moveCharge = false;
								hAngle = m.PI / 2;
							} else if (this.angleOfLeastInterference <= 5 * m.PI / 4) {
								xoffset = -symbolWidth / 2 - numWidth - hWidth / 2 - massWidth / 2;
								moveCharge = false;
								hAngle = m.PI;
							} else if (this.angleOfLeastInterference < 7 * m.PI / 4) {
								xoffset = 0;
								yoffset = specs.atoms_font_size_2D * .9;
								moveCharge = false;
								hAngle = 3 * m.PI / 2;
							}
						}
						ctx.font = font;
						ctx.fillText('H', this.x + xoffset, this.y + yoffset);
						ctx.font = subFont;
						ctx.fillText(numHs, this.x + xoffset + hWidth / 2 + numWidth / 2, this.y + yoffset + specs.atoms_font_size_2D * .3);
						this.textBounds.push({
							x : this.x + xoffset - hWidth / 2,
							y : this.y + yoffset - specs.atoms_font_size_2D / 2 + 1,
							w : hWidth,
							h : specs.atoms_font_size_2D - 2
						});
						this.textBounds.push({
							x : this.x + xoffset + hWidth / 2,
							y : this.y + yoffset + specs.atoms_font_size_2D * .3 - specs.atoms_font_size_2D / 2 + 1,
							w : numWidth,
							h : specs.atoms_font_size_2D * .8 - 2
						});
					} else {
						var xoffset = symbolWidth / 2 + hWidth / 2;
						var yoffset = 0;
						if (this.bondNumber === 1) {
							if (this.angleOfLeastInterference > m.PI / 2 && this.angleOfLeastInterference < 3 * m.PI / 2) {
								xoffset = -symbolWidth / 2 - hWidth / 2 - massWidth / 2;
								moveCharge = false;
								hAngle = m.PI;
							}
						} else {
							if (this.angleOfLeastInterference <= m.PI / 4) {
								// default
							} else if (this.angleOfLeastInterference < 3 * m.PI / 4) {
								xoffset = 0;
								yoffset = -specs.atoms_font_size_2D * .9;
								moveCharge = false;
								hAngle = m.PI / 2;
							} else if (this.angleOfLeastInterference <= 5 * m.PI / 4) {
								xoffset = -symbolWidth / 2 - hWidth / 2 - massWidth / 2;
								moveCharge = false;
								hAngle = m.PI;
							} else if (this.angleOfLeastInterference < 7 * m.PI / 4) {
								xoffset = 0;
								yoffset = specs.atoms_font_size_2D * .9;
								moveCharge = false;
								hAngle = 3 * m.PI / 2;
							}
						}
						ctx.fillText('H', this.x + xoffset, this.y + yoffset);
						this.textBounds.push({
							x : this.x + xoffset - hWidth / 2,
							y : this.y + yoffset - specs.atoms_font_size_2D / 2 + 1,
							w : hWidth,
							h : specs.atoms_font_size_2D - 2
						});
					}
					if (moveCharge) {
						chargeOffset += hWidth;
					}
					// adjust the angles metadata to account for hydrogen
					// placement
					/*
					 * this.angles.push(hAngle); var angleData =
					 * math.angleBetweenLargest(this.angles);
					 * this.angleOfLeastInterference = angleData.angle % (m.PI *
					 * 2); this.largestAngle = angleData.largest;
					 */
				}
				// charge
				if (this.charge !== 0) {
					var s = this.charge.toFixed(0);
					if (s === '1') {
						s = '+';
					} else if (s === '-1') {
						s = '\u2013';
					} else if (extensions.stringStartsWith(s, '-')) {
						s = s.substring(1) + '\u2013';
					} else {
						s += '+';
					}
					var chargeWidth = ctx.measureText(s).width;
					chargeOffset += chargeWidth / 2;
					ctx.textAlign = 'center';
					ctx.textBaseline = 'middle';
					ctx.font = extensions.getFontString(m.floor(specs.atoms_font_size_2D * .8), specs.atoms_font_families_2D, specs.atoms_font_bold_2D, specs.atoms_font_italic_2D);
					ctx.fillText(s, this.x + chargeOffset - 1, this.y - specs.atoms_font_size_2D / 2 + 1);
					this.textBounds.push({
						x : this.x + chargeOffset - chargeWidth / 2 - 1,
						y : this.y - (specs.atoms_font_size_2D * 1.8) / 2 + 5,
						w : chargeWidth,
						h : specs.atoms_font_size_2D / 2 - 1
					});
				}
			}
		}
		if (this.numLonePair > 0 || this.numRadical > 0) {
			ctx.fillStyle = 'black';
			var as = this.angles.slice(0);
			var ali = this.angleOfLeastInterference;
			var la = this.largestAngle;
			if (hAngle !== undefined) {
				// have to check for undefined here as this number can be 0
				as.push(hAngle);
				as.sort();
				var angleData = math.angleBetweenLargest(as);
				ali = angleData.angle % (m.PI * 2);
				la = angleData.largest;
			}
			var things = [];
			for ( var i = 0; i < this.numLonePair; i++) {
				things.push({
					t : 2
				});
			}
			for ( var i = 0; i < this.numRadical; i++) {
				things.push({
					t : 1
				});
			}
			if (hAngle === undefined && m.abs(la - 2 * m.PI / as.length) < m.PI / 60) {
				var mid = m.ceil(things.length / as.length);
				for ( var i = 0, ii = things.length; i < ii; i += mid, ali += la) {
					this.drawElectrons(ctx, specs, things.slice(i, m.min(things.length, i + mid)), ali, la, hAngle);
				}
			} else {
				this.drawElectrons(ctx, specs, things, ali, la, hAngle);
			}
		}
		// for debugging atom label dimensions
		// ctx.strokeStyle = 'red'; for(var i = 0, ii =
		// this.textBounds.length;i<ii; i++){ var r = this.textBounds[i];
		// ctx.beginPath();ctx.rect(r.x, r.y, r.w, r.h); ctx.stroke(); }

	};
	_.drawElectrons = function(ctx, specs, things, angle, largest, hAngle) {
		var segment = largest / (things.length + (this.bonds.length === 0 && hAngle === undefined ? 0 : 1));
		var angleStart = angle - largest / 2 + segment;
		for ( var i = 0; i < things.length; i++) {
			var t = things[i];
			var angle = angleStart + i * segment;
			var p1x = this.x + Math.cos(angle) * specs.atoms_lonePairDistance_2D;
			var p1y = this.y - Math.sin(angle) * specs.atoms_lonePairDistance_2D;
			if (t.t === 2) {
				var perp = angle + Math.PI / 2;
				var difx = Math.cos(perp) * specs.atoms_lonePairSpread_2D / 2;
				var dify = -Math.sin(perp) * specs.atoms_lonePairSpread_2D / 2;
				ctx.beginPath();
				ctx.arc(p1x + difx, p1y + dify, specs.atoms_lonePairDiameter_2D, 0, m.PI * 2, false);
				ctx.fill();
				ctx.beginPath();
				ctx.arc(p1x - difx, p1y - dify, specs.atoms_lonePairDiameter_2D, 0, m.PI * 2, false);
				ctx.fill();
			} else if (t.t === 1) {
				ctx.beginPath();
				ctx.arc(p1x, p1y, specs.atoms_lonePairDiameter_2D, 0, m.PI * 2, false);
				ctx.fill();
			}
		}
	};
	_.drawDecorations = function(ctx) {
		if (this.isHover || this.isSelected) {
			ctx.strokeStyle = this.isHover ? '#885110' : '#0060B2';
			ctx.lineWidth = 1.2;
			ctx.beginPath();
			var radius = this.isHover ? 7 : 15;
			ctx.arc(this.x, this.y, radius, 0, m.PI * 2, false);
			ctx.stroke();
		}
		if (this.isOverlap) {
			ctx.strokeStyle = '#C10000';
			ctx.lineWidth = 1.2;
			ctx.beginPath();
			ctx.arc(this.x, this.y, 7, 0, m.PI * 2, false);
			ctx.stroke();
		}
	};
	_.render = function(gl, specs, noColor) {
		if (this.specs) {
			specs = this.specs;
		}
		var transform = m4.translate(gl.modelViewMatrix, [ this.x, this.y, this.z ], []);
		var radius = specs.atoms_useVDWDiameters_3D ? ELEMENT[this.label].vdWRadius * specs.atoms_vdwMultiplier_3D : specs.atoms_sphereDiameter_3D / 2;
		if (radius === 0) {
			radius = 1;
		}
		m4.scale(transform, [ radius, radius, radius ]);

		// colors
		if (!noColor) {
			var color = specs.atoms_color;
			if (specs.atoms_useJMOLColors) {
				color = ELEMENT[this.label].jmolColor;
			} else if (specs.atoms_usePYMOLColors) {
				color = ELEMENT[this.label].pymolColor;
			}
			gl.material.setDiffuseColor(color);
		}

		// render
		gl.setMatrixUniforms(transform);
		var buffer = this.renderAsStar ? gl.starBuffer : gl.sphereBuffer;
		gl.drawElements(gl.TRIANGLES, buffer.vertexIndexBuffer.numItems, gl.UNSIGNED_SHORT, 0);
	};
	_.renderHighlight = function(gl, specs) {
		if (this.isSelected || this.isHover) {
			if (this.specs) {
				specs = this.specs;
			}
			var transform = m4.translate(gl.modelViewMatrix, [ this.x, this.y, this.z ], []);
			var radius = specs.atoms_useVDWDiameters_3D ? ELEMENT[this.label].vdWRadius * specs.atoms_vdwMultiplier_3D : specs.atoms_sphereDiameter_3D / 2;
			if (radius === 0) {
				radius = 1;
			}
			radius *= 1.3;
			m4.scale(transform, [ radius, radius, radius ]);

			gl.setMatrixUniforms(transform);
			gl.material.setDiffuseColor(this.isHover ? '#885110' : '#0060B2');
			var buffer = this.renderAsStar ? gl.starBuffer : gl.sphereBuffer;
			gl.drawElements(gl.TRIANGLES, buffer.vertexIndexBuffer.numItems, gl.UNSIGNED_SHORT, 0);
		}
	};
	_.isLabelVisible = function(specs) {
		if (specs.atoms_displayAllCarbonLabels_2D) {
			// show all carbons
			return true;
		}
		if (this.label !== 'C') {
			// not a carbon
			return true;
		}
		if (this.altLabel) {
			// there is an alternative label
			return true;
		}
		if (this.mass !== -1 || this.charge !== 0) {
			// an isotope or charge designation, so label must be shown
			return true;
		}
		if (specs.atoms_showAttributedCarbons_2D && (this.numRadical !== 0 || this.numLonePair !== 0)) {
			// there are attributes and we want to show the associated label
			return true;
		}
		if (this.isHidden && specs.atoms_showHiddenCarbons_2D) {
			// if it is hidden and we want to show them
			return true;
		}
		if (specs.atoms_displayTerminalCarbonLabels_2D && this.bondNumber === 1) {
			// if it is terminal and we want to show them
			return true;
		}
		return false;
	};
	_.getImplicitHydrogenCount = function() {
		if (this.label === 'H' || !ELEMENT[this.label] || !ELEMENT[this.label].addH) {
			return 0;
		}
		var valence = ELEMENT[this.label].valency;
		var dif = valence - this.coordinationNumber;
		if (this.numRadical > 0) {
			dif = m.max(0, dif - this.numRadical);
		}
		if (this.charge > 0) {
			var vdif = 4 - valence;
			if (this.charge <= vdif) {
				dif += this.charge;
			} else {
				dif = 4 - this.coordinationNumber - this.charge + vdif;
			}
		} else {
			dif += this.charge;
		}
		return dif < 0 ? 0 : m.floor(dif);
	};
	_.getBounds = function() {
		var bounds = new math.Bounds();
		bounds.expand(this.x, this.y);
		if (this.textBounds) {
			for ( var i = 0, ii = this.textBounds.length; i < ii; i++) {
				var tb = this.textBounds[i];
				bounds.expand(tb.x, tb.y, tb.x + tb.w, tb.y + tb.h);
			}
		}
		return bounds;
	};
	_.getBounds3D = function() {
		var bounds = new math.Bounds();
		bounds.expand3D(this.x, this.y, this.z);
		return bounds;
	};
	/**
	 * Get Color by atom element.
	 * 
	 * @param {boolean}
	 *            useJMOLColors
	 * @param {boolean}
	 *            usePYMOLColors
	 * @param {string}
	 *            color The default color
	 * @param {number}
	 *            dim The render dimension
	 * @return {string} The atom element color
	 */
	_.getElementColor = function(useJMOLColors, usePYMOLColors, color, dim) {
		if (useJMOLColors) {
			color = ELEMENT[this.label].jmolColor;
		} else if (usePYMOLColors) {
			color = ELEMENT[this.label].pymolColor;
		}
		return color;
	};

})(ChemDoodle.ELEMENT, ChemDoodle.extensions, ChemDoodle.math, ChemDoodle.structures, Math, ChemDoodle.lib.mat4);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(ELEMENT, extensions, structures, math, m, m4, v3) {
	'use strict';
	structures.Bond = function(a1, a2, bondOrder) {
		this.a1 = a1;
		this.a2 = a2;
		// bondOrder can be 0, so need to check against undefined
		this.bondOrder = bondOrder !== undefined ? bondOrder : 1;
	};
	structures.Bond.STEREO_NONE = 'none';
	structures.Bond.STEREO_PROTRUDING = 'protruding';
	structures.Bond.STEREO_RECESSED = 'recessed';
	structures.Bond.STEREO_AMBIGUOUS = 'ambiguous';
	var _ = structures.Bond.prototype;
	_.stereo = structures.Bond.STEREO_NONE;
	_.isHover = false;
	_.ring = undefined;
	_.getCenter = function() {
		return new structures.Point((this.a1.x + this.a2.x) / 2, (this.a1.y + this.a2.y) / 2);
	};
	_.getLength = function() {
		return this.a1.distance(this.a2);
	};
	_.getLength3D = function() {
		return this.a1.distance3D(this.a2);
	};
	_.contains = function(a) {
		return a === this.a1 || a === this.a2;
	};
	_.getNeighbor = function(a) {
		if (a === this.a1) {
			return this.a2;
		} else if (a === this.a2) {
			return this.a1;
		}
		return undefined;
	};
	_.draw = function(ctx, specs) {
		if (this.a1.x === this.a2.x && this.a1.y === this.a2.y) {
			// return, as there is nothing to render, will only cause fill
			// overflows
			return;
		}
		if (this.specs) {
			specs = this.specs;
		}
		var x1 = this.a1.x;
		var x2 = this.a2.x;
		var y1 = this.a1.y;
		var y2 = this.a2.y;
		var dist = this.a1.distance(this.a2);
		var difX = x2 - x1;
		var difY = y2 - y1;
		if (this.a1.isLassoed && this.a2.isLassoed) {
			var grd = ctx.createLinearGradient(x1, y1, x2, y2);
			grd.addColorStop(0, 'rgba(212, 99, 0, 0)');
			grd.addColorStop(0.5, 'rgba(212, 99, 0, 0.8)');
			grd.addColorStop(1, 'rgba(212, 99, 0, 0)');
			var useDist = 2.5;
			var perpendicular = this.a1.angle(this.a2) + m.PI / 2;
			var mcosp = m.cos(perpendicular);
			var msinp = m.sin(perpendicular);
			var cx1 = x1 - mcosp * useDist;
			var cy1 = y1 + msinp * useDist;
			var cx2 = x1 + mcosp * useDist;
			var cy2 = y1 - msinp * useDist;
			var cx3 = x2 + mcosp * useDist;
			var cy3 = y2 - msinp * useDist;
			var cx4 = x2 - mcosp * useDist;
			var cy4 = y2 + msinp * useDist;
			ctx.fillStyle = grd;
			ctx.beginPath();
			ctx.moveTo(cx1, cy1);
			ctx.lineTo(cx2, cy2);
			ctx.lineTo(cx3, cy3);
			ctx.lineTo(cx4, cy4);
			ctx.closePath();
			ctx.fill();
		}
		if (specs.atoms_display && !specs.atoms_circles_2D && this.a1.isLabelVisible(specs) && this.a1.textBounds) {
			var distShrink = 0;
			for ( var i = 0, ii = this.a1.textBounds.length; i < ii; i++) {
				distShrink = Math.max(distShrink, math.calculateDistanceInterior(this.a1, this.a2, this.a1.textBounds[i]));
			}
			distShrink += specs.bonds_atomLabelBuffer_2D;
			var perc = distShrink / dist;
			x1 += difX * perc;
			y1 += difY * perc;
		}
		if (specs.atoms_display && !specs.atoms_circles_2D && this.a2.isLabelVisible(specs) && this.a2.textBounds) {
			var distShrink = 0;
			for ( var i = 0, ii = this.a2.textBounds.length; i < ii; i++) {
				distShrink = Math.max(distShrink, math.calculateDistanceInterior(this.a2, this.a1, this.a2.textBounds[i]));
			}
			distShrink += specs.bonds_atomLabelBuffer_2D;
			var perc = distShrink / dist;
			x2 -= difX * perc;
			y2 -= difY * perc;
		}
		if (specs.bonds_clearOverlaps_2D) {
			var xs = x1 + difX * .15;
			var ys = y1 + difY * .15;
			var xf = x2 - difX * .15;
			var yf = y2 - difY * .15;
			ctx.strokeStyle = specs.backgroundColor;
			ctx.lineWidth = specs.bonds_width_2D + specs.bonds_overlapClearWidth_2D * 2;
			ctx.lineCap = 'round';
			ctx.beginPath();
			ctx.moveTo(xs, ys);
			ctx.lineTo(xf, yf);
			ctx.closePath();
			ctx.stroke();
		}
		ctx.strokeStyle = specs.bonds_color;
		ctx.fillStyle = specs.bonds_color;
		ctx.lineWidth = specs.bonds_width_2D;
		ctx.lineCap = specs.bonds_ends_2D;
		if (specs.bonds_useJMOLColors || specs.bonds_usePYMOLColors) {
			var linearGradient = ctx.createLinearGradient(x1, y1, x2, y2);
			var color1 = this.a1.getElementColor(specs.bonds_useJMOLColors, specs.bonds_usePYMOLColors, specs.atoms_color, 2);
			var color2 = this.a2.getElementColor(specs.bonds_useJMOLColors, specs.bonds_usePYMOLColors, specs.atoms_color, 2);
			linearGradient.addColorStop(0, color1);
			if (!specs.bonds_colorGradient) {
				linearGradient.addColorStop(0.5, color1);
				linearGradient.addColorStop(0.51, color2);
			}
			linearGradient.addColorStop(1, color2);
			ctx.strokeStyle = linearGradient;
			ctx.fillStyle = linearGradient;
		}
		if (specs.bonds_lewisStyle_2D && this.bondOrder % 1 === 0) {
			this.drawLewisStyle(ctx, specs, x1, y1, x2, y2);
		} else {
			switch (this.query?1:this.bondOrder) {
			case 0:
				var dx = x2 - x1;
				var dy = y2 - y1;
				var innerDist = m.sqrt(dx * dx + dy * dy);
				var num = m.floor(innerDist / specs.bonds_dotSize_2D);
				var remainder = (innerDist - (num - 1) * specs.bonds_dotSize_2D) / 2;
				if (num % 2 === 1) {
					remainder += specs.bonds_dotSize_2D / 4;
				} else {
					remainder -= specs.bonds_dotSize_2D / 4;
					num += 2;
				}
				num /= 2;
				var angle = this.a1.angle(this.a2);
				var xs = x1 + remainder * Math.cos(angle);
				var ys = y1 - remainder * Math.sin(angle);
				ctx.beginPath();
				for ( var i = 0; i < num; i++) {
					ctx.arc(xs, ys, specs.bonds_dotSize_2D / 2, 0, m.PI * 2, false);
					xs += 2 * specs.bonds_dotSize_2D * Math.cos(angle);
					ys -= 2 * specs.bonds_dotSize_2D * Math.sin(angle);
				}
				ctx.fill();
				break;
			case 0.5:
				ctx.beginPath();
				ctx.moveTo(x1, y1);
				extensions.contextHashTo(ctx, x1, y1, x2, y2, specs.bonds_hashSpacing_2D, specs.bonds_hashSpacing_2D);
				ctx.stroke();
				break;
			case 1:
				if (!this.query && (this.stereo === structures.Bond.STEREO_PROTRUDING || this.stereo === structures.Bond.STEREO_RECESSED)) {
					var thinSpread = specs.bonds_width_2D / 2;
					var useDist = this.a1.distance(this.a2) * specs.bonds_wedgeThickness_2D / 2;
					var perpendicular = this.a1.angle(this.a2) + m.PI / 2;
					var mcosp = m.cos(perpendicular);
					var msinp = m.sin(perpendicular);
					var cx1 = x1 - mcosp * thinSpread;
					var cy1 = y1 + msinp * thinSpread;
					var cx2 = x1 + mcosp * thinSpread;
					var cy2 = y1 - msinp * thinSpread;
					var cx3 = x2 + mcosp * useDist;
					var cy3 = y2 - msinp * useDist;
					var cx4 = x2 - mcosp * useDist;
					var cy4 = y2 + msinp * useDist;
					ctx.beginPath();
					ctx.moveTo(cx1, cy1);
					ctx.lineTo(cx2, cy2);
					ctx.lineTo(cx3, cy3);
					ctx.lineTo(cx4, cy4);
					ctx.closePath();
					if (this.stereo === structures.Bond.STEREO_PROTRUDING) {
						ctx.fill();
					} else {
						ctx.save();
						ctx.clip();
						ctx.lineWidth = useDist * 2;
						ctx.lineCap = 'butt';
						ctx.beginPath();
						ctx.moveTo(x1, y1);
						extensions.contextHashTo(ctx, x1, y1, x2, y2, specs.bonds_hashWidth_2D, specs.bonds_hashSpacing_2D);
						ctx.stroke();
						ctx.restore();
					}
				} else if (!this.query && this.stereo === structures.Bond.STEREO_AMBIGUOUS) {
					ctx.beginPath();
					ctx.moveTo(x1, y1);
					var curves = m.floor(m.sqrt(difX * difX + difY * difY) / specs.bonds_wavyLength_2D);
					var x = x1;
					var y = y1;
					var perpendicular = this.a1.angle(this.a2) + m.PI / 2;
					var mcosp = m.cos(perpendicular);
					var msinp = m.sin(perpendicular);

					var curveX = difX / curves;
					var curveY = difY / curves;
					var cpx1, cpx2, cpy1, cpy2;
					for ( var i = 0, ii = curves; i < ii; i++) {
						x += curveX;
						y += curveY;
						cpx1 = specs.bonds_wavyLength_2D * mcosp + x - curveX * 0.5;
						cpy1 = specs.bonds_wavyLength_2D * -msinp + y - curveY * 0.5;
						cpx2 = specs.bonds_wavyLength_2D * -mcosp + x - curveX * 0.5;
						cpy2 = specs.bonds_wavyLength_2D * msinp + y - curveY * 0.5;
						if (i % 2 === 0) {
							ctx.quadraticCurveTo(cpx1, cpy1, x, y);
						} else {
							ctx.quadraticCurveTo(cpx2, cpy2, x, y);
						}
					}
					ctx.stroke();
					break;
				} else {
					ctx.beginPath();
					ctx.moveTo(x1, y1);
					ctx.lineTo(x2, y2);
					ctx.stroke();
					if(this.query){
						this.query.draw(ctx, specs, this.getCenter());
					}
				}
				break;
			case 1.5:
			case 2:
				if (this.stereo === structures.Bond.STEREO_AMBIGUOUS) {
					var useDist = this.a1.distance(this.a2) * specs.bonds_saturationWidth_2D / 2;
					var perpendicular = this.a1.angle(this.a2) + m.PI / 2;
					var mcosp = m.cos(perpendicular);
					var msinp = m.sin(perpendicular);
					var cx1 = x1 - mcosp * useDist;
					var cy1 = y1 + msinp * useDist;
					var cx2 = x1 + mcosp * useDist;
					var cy2 = y1 - msinp * useDist;
					var cx3 = x2 + mcosp * useDist;
					var cy3 = y2 - msinp * useDist;
					var cx4 = x2 - mcosp * useDist;
					var cy4 = y2 + msinp * useDist;
					ctx.beginPath();
					ctx.moveTo(cx1, cy1);
					ctx.lineTo(cx3, cy3);
					ctx.moveTo(cx2, cy2);
					ctx.lineTo(cx4, cy4);
					ctx.stroke();
				} else if (!specs.bonds_symmetrical_2D && (this.ring || this.a1.label === 'C' && this.a2.label === 'C')) {
					ctx.beginPath();
					ctx.moveTo(x1, y1);
					ctx.lineTo(x2, y2);
					var clip = 0;
					var dist = this.a1.distance(this.a2);
					var angle = this.a1.angle(this.a2);
					var perpendicular = angle + m.PI / 2;
					var useDist = dist * specs.bonds_saturationWidth_2D;
					var clipAngle = specs.bonds_saturationAngle_2D;
					if (clipAngle < m.PI / 2) {
						clip = -(useDist / m.tan(clipAngle));
					}
					if (m.abs(clip) < dist / 2) {
						var xuse1 = x1 - m.cos(angle) * clip;
						var xuse2 = x2 + m.cos(angle) * clip;
						var yuse1 = y1 + m.sin(angle) * clip;
						var yuse2 = y2 - m.sin(angle) * clip;
						var mcosp = m.cos(perpendicular);
						var msinp = m.sin(perpendicular);
						var cx1 = xuse1 - mcosp * useDist;
						var cy1 = yuse1 + msinp * useDist;
						var cx2 = xuse1 + mcosp * useDist;
						var cy2 = yuse1 - msinp * useDist;
						var cx3 = xuse2 - mcosp * useDist;
						var cy3 = yuse2 + msinp * useDist;
						var cx4 = xuse2 + mcosp * useDist;
						var cy4 = yuse2 - msinp * useDist;
						var flip = !this.ring || (this.ring.center.angle(this.a1) > this.ring.center.angle(this.a2) && !(this.ring.center.angle(this.a1) - this.ring.center.angle(this.a2) > m.PI) || (this.ring.center.angle(this.a1) - this.ring.center.angle(this.a2) < -m.PI));
						if (flip) {
							ctx.moveTo(cx1, cy1);
							if (this.bondOrder === 2) {
								ctx.lineTo(cx3, cy3);
							} else {
								extensions.contextHashTo(ctx, cx1, cy1, cx3, cy3, specs.bonds_hashSpacing_2D, specs.bonds_hashSpacing_2D);
							}
						} else {
							ctx.moveTo(cx2, cy2);
							if (this.bondOrder === 2) {
								ctx.lineTo(cx4, cy4);
							} else {
								extensions.contextHashTo(ctx, cx2, cy2, cx4, cy4, specs.bonds_hashSpacing_2D, specs.bonds_hashSpacing_2D);
							}
						}
						ctx.stroke();
					}
				} else {
					var useDist = this.a1.distance(this.a2) * specs.bonds_saturationWidth_2D / 2;
					var perpendicular = this.a1.angle(this.a2) + m.PI / 2;
					var mcosp = m.cos(perpendicular);
					var msinp = m.sin(perpendicular);
					var cx1 = x1 - mcosp * useDist;
					var cy1 = y1 + msinp * useDist;
					var cx2 = x1 + mcosp * useDist;
					var cy2 = y1 - msinp * useDist;
					var cx3 = x2 + mcosp * useDist;
					var cy3 = y2 - msinp * useDist;
					var cx4 = x2 - mcosp * useDist;
					var cy4 = y2 + msinp * useDist;
					ctx.beginPath();
					ctx.moveTo(cx1, cy1);
					ctx.lineTo(cx4, cy4);
					ctx.moveTo(cx2, cy2);
					if (this.bondOrder === 2) {
						ctx.lineTo(cx3, cy3);
					} else {
						extensions.contextHashTo(ctx, cx2, cy2, cx3, cy3, specs.bonds_hashSpacing_2D, specs.bonds_hashSpacing_2D);
					}
					ctx.stroke();
				}
				break;
			case 3:
				var useDist = this.a1.distance(this.a2) * specs.bonds_saturationWidth_2D;
				var perpendicular = this.a1.angle(this.a2) + m.PI / 2;
				var mcosp = m.cos(perpendicular);
				var msinp = m.sin(perpendicular);
				var cx1 = x1 - mcosp * useDist;
				var cy1 = y1 + msinp * useDist;
				var cx2 = x1 + mcosp * useDist;
				var cy2 = y1 - msinp * useDist;
				var cx3 = x2 + mcosp * useDist;
				var cy3 = y2 - msinp * useDist;
				var cx4 = x2 - mcosp * useDist;
				var cy4 = y2 + msinp * useDist;
				ctx.beginPath();
				ctx.moveTo(cx1, cy1);
				ctx.lineTo(cx4, cy4);
				ctx.moveTo(cx2, cy2);
				ctx.lineTo(cx3, cy3);
				ctx.moveTo(x1, y1);
				ctx.lineTo(x2, y2);
				ctx.stroke();
				break;
			}
		}
	};
	_.drawDecorations = function(ctx) {
		if (this.isHover || this.isSelected) {
			var pi2 = 2 * m.PI;
			var angle = (this.a1.angleForStupidCanvasArcs(this.a2) + m.PI / 2) % pi2;
			ctx.strokeStyle = this.isHover ? '#885110' : '#0060B2';
			ctx.lineWidth = 1.2;
			ctx.beginPath();
			var angleTo = (angle + m.PI) % pi2;
			angleTo = angleTo % (m.PI * 2);
			ctx.arc(this.a1.x, this.a1.y, 7, angle, angleTo, false);
			ctx.stroke();
			ctx.beginPath();
			angle += m.PI;
			angleTo = (angle + m.PI) % pi2;
			ctx.arc(this.a2.x, this.a2.y, 7, angle, angleTo, false);
			ctx.stroke();
		}
	};
	_.drawLewisStyle = function(ctx, specs, x1, y1, x2, y2) {
		var angle = this.a1.angle(this.a2);
		var perp = angle + m.PI/2;
		var difx = x2 - x1;
		var dify = y2 - y1;
		var increment = m.sqrt(difx * difx + dify * dify) / (this.bondOrder + 1);
		var xi = increment * m.cos(angle);
		var yi = -increment * m.sin(angle);
		var x = x1 + xi;
		var y = y1 + yi;
		for ( var i = 0; i < this.bondOrder; i++) {
			var sep = specs.atoms_lonePairSpread_2D / 2;
			var cx1 = x - m.cos(perp) * sep;
			var cy1 = y + m.sin(perp) * sep;
			var cx2 = x + m.cos(perp) * sep;
			var cy2 = y - m.sin(perp) * sep;
			ctx.beginPath();
			ctx.arc(cx1 - specs.atoms_lonePairDiameter_2D / 2, cy1 - specs.atoms_lonePairDiameter_2D / 2, specs.atoms_lonePairDiameter_2D, 0, m.PI * 2, false);
			ctx.fill();
			ctx.beginPath();
			ctx.arc(cx2 - specs.atoms_lonePairDiameter_2D / 2, cy2 - specs.atoms_lonePairDiameter_2D / 2, specs.atoms_lonePairDiameter_2D, 0, m.PI * 2, false);
			ctx.fill();
			x += xi;
			y += yi;
		}
	};
	/**
	 * 
	 * @param {WegGLRenderingContext}
	 *            gl
	 * @param {structures.VisualSpecifications}
	 *            specs
	 * @param {boolean}
	 *            asSegments Using cylinder/solid line or segmented pills/dashed
	 *            line
	 * @return {void}
	 */
	_.render = function(gl, specs, asSegments) {
		if (this.specs) {
			specs = this.specs;
		}
		// this is the elongation vector for the cylinder
		var height = this.a1.distance3D(this.a2);
		if (height === 0) {
			// if there is no height, then no point in rendering this bond,
			// just return
			return;
		}

		// scale factor for cylinder/pill radius.
		// when scale pill, the cap will affected too.
		var radiusScale = specs.bonds_cylinderDiameter_3D / 2;

		// atom1 color and atom2 color
		var a1Color = specs.bonds_color;
		var a2Color;

		// transform to the atom as well as the opposite atom (for Jmol and
		// PyMOL
		// color splits)
		var transform = m4.translate(gl.modelViewMatrix, [ this.a1.x, this.a1.y, this.a1.z ], []);
		var transformOpposite;

		// vector from atom1 to atom2
		var a2b = [ this.a2.x - this.a1.x, this.a2.y - this.a1.y, this.a2.z - this.a1.z ];

		// calculate the rotation
		var y = [ 0, 1, 0 ];
		var ang = 0;
		var axis;
		if (this.a1.x === this.a2.x && this.a1.z === this.a2.z) {
			axis = [ 0, 0, 1 ];
			if (this.a2.y < this.a1.y) {
				ang = m.PI;
			}
		} else {
			ang = extensions.vec3AngleFrom(y, a2b);
			axis = v3.cross(y, a2b, []);
		}

		var useJMOLColors = specs.bonds_useJMOLColors;
		var usePYMOLColors = specs.bonds_usePYMOLColors;

		// the specs will use JMol or PyMol color are
		// - Line
		// - Stick
		// - Wireframe
		if (useJMOLColors || usePYMOLColors) {

			a1Color = this.a1.getElementColor(useJMOLColors, usePYMOLColors, a1Color);
			a2Color = this.a2.getElementColor(useJMOLColors, usePYMOLColors, specs.bonds_color);

			// the transformOpposite will use for split color.
			// just make it splited if the color different.
			if (a1Color != a2Color) {
				transformOpposite = m4.translate(gl.modelViewMatrix, [ this.a2.x, this.a2.y, this.a2.z ], []);
			}
		}

		// calculate the translations for unsaturated bonds.
		// represenattio use saturatedCross are
		// - Line
		// - Wireframe
		// - Ball and Stick
		// just Stick will set bonds_showBondOrders_3D to false
		var others = [ 0 ];
		var saturatedCross;

		if (asSegments) { // block for draw bond as segmented line/pill

			if (specs.bonds_showBondOrders_3D && this.bondOrder > 1) {

				// The "0.5" part set here,
				// the other part (1) will render as cylinder
				others = [/*-specs.bonds_cylinderDiameter_3D, */specs.bonds_cylinderDiameter_3D ];

				var z = [ 0, 0, 1 ];
				var inverse = m4.inverse(gl.rotationMatrix, []);
				m4.multiplyVec3(inverse, z);
				saturatedCross = v3.cross(a2b, z, []);
				v3.normalize(saturatedCross);
			}

			var segmentScale = 1;

			var spaceBetweenPill = specs.bonds_pillSpacing_3D;

			var pillHeight = specs.bonds_pillHeight_3D;

			if (this.bondOrder == 0) {

				if (specs.bonds_renderAsLines_3D) {
					pillHeight = spaceBetweenPill;
				} else {
					pillHeight = specs.bonds_pillDiameter_3D;

					// Detect Ball and Stick representation
					if (pillHeight < specs.bonds_cylinderDiameter_3D) {
						pillHeight /= 2;
					}

					segmentScale = pillHeight / 2;
					height /= segmentScale;
					spaceBetweenPill /= segmentScale / 2;
				}

			}

			// total space need for one pill, iclude the space.
			var totalSpaceForPill = pillHeight + spaceBetweenPill;

			// segmented pills for one bond.
			var totalPillsPerBond = height / totalSpaceForPill;

			// segmented one unit pill for one bond
			var pillsPerBond = m.floor(totalPillsPerBond);

			var extraSegmentedSpace = height - totalSpaceForPill * pillsPerBond;

			var paddingSpace = (spaceBetweenPill + specs.bonds_pillDiameter_3D + extraSegmentedSpace) / 2;

			// pillSegmentsLength will change if both atom1 and atom2 color used
			// for rendering
			var pillSegmentsLength = pillsPerBond;

			if (transformOpposite) {
				// floor will effected for odd pills, because one pill at the
				// center
				// will replace with splited pills
				pillSegmentsLength = m.floor(pillsPerBond / 2);
			}

			// render bonds
			for ( var i = 0, ii = others.length; i < ii; i++) {
				var transformUse = m4.set(transform, []);

				if (others[i] !== 0) {
					m4.translate(transformUse, v3.scale(saturatedCross, others[i], []));
				}
				if (ang !== 0) {
					m4.rotate(transformUse, ang, axis);
				}

				if (segmentScale != 1) {
					m4.scale(transformUse, [ segmentScale, segmentScale, segmentScale ]);
				}

				// colors
				if (a1Color)
					gl.material.setDiffuseColor(a1Color);

				m4.translate(transformUse, [ 0, paddingSpace, 0 ]);

				for ( var j = 0; j < pillSegmentsLength; j++) {

					if (specs.bonds_renderAsLines_3D) {
						if (this.bondOrder == 0) {
							gl.setMatrixUniforms(transformUse);
							gl.drawArrays(gl.POINTS, 0, 1);
						} else {
							m4.scale(transformUse, [ 1, pillHeight, 1 ]);

							gl.setMatrixUniforms(transformUse);
							gl.drawArrays(gl.LINES, 0, gl.lineBuffer.vertexPositionBuffer.numItems);

							m4.scale(transformUse, [ 1, 1 / pillHeight, 1 ]);
						}
					} else {
						gl.setMatrixUniforms(transformUse);
						if (this.bondOrder == 0) {
							gl.drawElements(gl.TRIANGLES, gl.sphereBuffer.vertexIndexBuffer.numItems, gl.UNSIGNED_SHORT, 0);
						} else {
							gl.drawElements(gl.TRIANGLES, gl.pillBuffer.vertexIndexBuffer.numItems, gl.UNSIGNED_SHORT, 0);
						}
					}

					m4.translate(transformUse, [ 0, totalSpaceForPill, 0 ]);
				}

				// if rendering segmented pill use atom1 and atom2 color
				if (transformOpposite) {
					// parameter for calculate splited pills
					var scaleY, halfOneMinScaleY;

					if (specs.bonds_renderAsLines_3D) {
						scaleY = pillHeight;
						// if(this.bondOrder != 0) {
						// scaleY -= spaceBetweenPill;
						// }
						scaleY /= 2;
						halfOneMinScaleY = 0;
					} else {
						scaleY = 2 / 3;
						halfOneMinScaleY = (1 - scaleY) / 2;
					}

					// if count of pills per bound is odd,
					// then draw the splited pills of atom1
					if (pillsPerBond % 2 != 0) {

						m4.scale(transformUse, [ 1, scaleY, 1 ]);

						gl.setMatrixUniforms(transformUse);

						if (specs.bonds_renderAsLines_3D) {

							if (this.bondOrder == 0) {
								gl.drawArrays(gl.POINTS, 0, 1);
							} else {
								gl.drawArrays(gl.LINES, 0, gl.lineBuffer.vertexPositionBuffer.numItems);
							}

						} else {

							if (this.bondOrder == 0) {
								gl.drawElements(gl.TRIANGLES, gl.sphereBuffer.vertexIndexBuffer.numItems, gl.UNSIGNED_SHORT, 0);
							} else {
								gl.drawElements(gl.TRIANGLES, gl.pillBuffer.vertexIndexBuffer.numItems, gl.UNSIGNED_SHORT, 0);
							}

						}

						m4.translate(transformUse, [ 0, totalSpaceForPill * (1 + halfOneMinScaleY), 0 ]);

						m4.scale(transformUse, [ 1, 1 / scaleY, 1 ]);
					}

					// prepare to render the atom2

					m4.set(transformOpposite, transformUse);
					if (others[i] !== 0) {
						m4.translate(transformUse, v3.scale(saturatedCross, others[i], []));
					}
					// don't check for 0 here as that means it should be rotated
					// by PI, but PI will be negated
					m4.rotate(transformUse, ang + m.PI, axis);

					if (segmentScale != 1) {
						m4.scale(transformUse, [ segmentScale, segmentScale, segmentScale ]);
					}

					// colors
					if (a2Color){
						gl.material.setDiffuseColor(a2Color);
					}

					m4.translate(transformUse, [ 0, paddingSpace, 0 ]);

					// draw the remain pills which use the atom2 color
					for ( var j = 0; j < pillSegmentsLength; j++) {

						if (specs.bonds_renderAsLines_3D) {
							if (this.bondOrder == 0) {
								gl.setMatrixUniforms(transformUse);
								gl.drawArrays(gl.POINTS, 0, 1);
							} else {
								m4.scale(transformUse, [ 1, pillHeight, 1 ]);

								gl.setMatrixUniforms(transformUse);
								gl.drawArrays(gl.LINES, 0, gl.lineBuffer.vertexPositionBuffer.numItems);

								m4.scale(transformUse, [ 1, 1 / pillHeight, 1 ]);
							}
						} else {
							gl.setMatrixUniforms(transformUse);
							if (this.bondOrder == 0) {
								gl.drawElements(gl.TRIANGLES, gl.sphereBuffer.vertexIndexBuffer.numItems, gl.UNSIGNED_SHORT, 0);
							} else {
								gl.drawElements(gl.TRIANGLES, gl.pillBuffer.vertexIndexBuffer.numItems, gl.UNSIGNED_SHORT, 0);
							}
						}

						m4.translate(transformUse, [ 0, totalSpaceForPill, 0 ]);
					}

					// draw the splited center pills of atom2
					if (pillsPerBond % 2 != 0) {

						m4.scale(transformUse, [ 1, scaleY, 1 ]);

						gl.setMatrixUniforms(transformUse);

						if (specs.bonds_renderAsLines_3D) {

							if (this.bondOrder == 0) {
								gl.drawArrays(gl.POINTS, 0, 1);
							} else {
								gl.drawArrays(gl.LINES, 0, gl.lineBuffer.vertexPositionBuffer.numItems);
							}

						} else {

							if (this.bondOrder == 0) {
								gl.drawElements(gl.TRIANGLES, gl.sphereBuffer.vertexIndexBuffer.numItems, gl.UNSIGNED_SHORT, 0);
							} else {
								gl.drawElements(gl.TRIANGLES, gl.pillBuffer.vertexIndexBuffer.numItems, gl.UNSIGNED_SHORT, 0);
							}

						}

						m4.translate(transformUse, [ 0, totalSpaceForPill * (1 + halfOneMinScaleY), 0 ]);

						m4.scale(transformUse, [ 1, 1 / scaleY, 1 ]);
					}
				}
			}
		} else {
			// calculate the translations for unsaturated bonds.
			// represenation that use saturatedCross are
			// - Line
			// - Wireframe
			// - Ball and Stick
			// just Stick will set bonds_showBondOrders_3D to false
			if (specs.bonds_showBondOrders_3D) {

				switch (this.bondOrder) {
				// the 0 and 0.5 bond order will draw as segmented pill.
				// so we not set that here.
				// case 0:
				// case 0.5: break;

				case 1.5:
					// The "1" part set here,
					// the other part (0.5) will render as segmented pill
					others = [ -specs.bonds_cylinderDiameter_3D /*
																 * ,
																 * specs.bonds_cylinderDiameter_3D
																 */];
					break;
				case 2:
					others = [ -specs.bonds_cylinderDiameter_3D, specs.bonds_cylinderDiameter_3D ];
					break;
				case 3:
					others = [ -1.2 * specs.bonds_cylinderDiameter_3D, 0, 1.2 * specs.bonds_cylinderDiameter_3D ];
					break;
				}

				// saturatedCross just need for need for bondorder greather than
				// 1
				if (this.bondOrder > 1) {
					var z = [ 0, 0, 1 ];
					var inverse = m4.inverse(gl.rotationMatrix, []);
					m4.multiplyVec3(inverse, z);
					saturatedCross = v3.cross(a2b, z, []);
					v3.normalize(saturatedCross);
				}
			}
			// for Stick representation, we just change the cylinder radius
			else {

				switch (this.bondOrder) {
				case 0:
					radiusScale *= 0.25;
					break;
				case 0.5:
				case 1.5:
					radiusScale *= 0.5;
					break;
				}
			}

			// if transformOpposite is set, the it mean the color must be
			// splited.
			// so the heigh of cylinder will be half.
			// one half for atom1 color the other for atom2 color
			if (transformOpposite) {
				height /= 2;
			}

			// Radius of cylinder already defined when initialize cylinder mesh,
			// so at this rate, the scale just needed for Y to strech
			// cylinder to bond length (height) and X and Z for radius.
			var scaleVector = [ radiusScale, height, radiusScale ];

			// render bonds
			for ( var i = 0, ii = others.length; i < ii; i++) {
				var transformUse = m4.set(transform, []);
				if (others[i] !== 0) {
					m4.translate(transformUse, v3.scale(saturatedCross, others[i], []));
				}
				if (ang !== 0) {
					m4.rotate(transformUse, ang, axis);
				}
				m4.scale(transformUse, scaleVector);

				// colors
				if (a1Color)
					gl.material.setDiffuseColor(a1Color);

				// render
				gl.setMatrixUniforms(transformUse);
				if (specs.bonds_renderAsLines_3D) {
					gl.drawArrays(gl.LINES, 0, gl.lineBuffer.vertexPositionBuffer.numItems);
				} else {
					gl.drawArrays(gl.TRIANGLE_STRIP, 0, gl.cylinderBuffer.vertexPositionBuffer.numItems);
				}

				// if transformOpposite is set, then a2Color also shoudl be
				// seted as well.
				if (transformOpposite) {

					m4.set(transformOpposite, transformUse);
					if (others[i] !== 0) {
						m4.translate(transformUse, v3.scale(saturatedCross, others[i], []));
					}
					// don't check for 0 here as that means it should be rotated
					// by PI, but PI will be negated
					m4.rotate(transformUse, ang + m.PI, axis);
					m4.scale(transformUse, scaleVector);

					// colors
					if (a2Color)
						gl.material.setDiffuseColor(a2Color);

					// render
					gl.setMatrixUniforms(transformUse);
					if (specs.bonds_renderAsLines_3D) {
						gl.drawArrays(gl.LINES, 0, gl.lineBuffer.vertexPositionBuffer.numItems);
					} else {
						gl.drawArrays(gl.TRIANGLE_STRIP, 0, gl.cylinderBuffer.vertexPositionBuffer.numItems);
					}
				}

			}
		}
	};
	_.renderHighlight = function(gl, specs) {
		if (this.isSelected || this.isHover) {
			if (this.specs) {
				specs = this.specs;
			}
			if (this.specs) {
				specs = this.specs;
			}
			// this is the elongation vector for the cylinder
			var height = this.a1.distance3D(this.a2);
			if (height === 0) {
				// if there is no height, then no point in rendering this bond,
				// just return
				return;
			}

			// scale factor for cylinder/pill radius.
			// when scale pill, the cap will affected too.
			var radiusScale = specs.bonds_cylinderDiameter_3D / 1.2;
			var transform = m4.translate(gl.modelViewMatrix, [ this.a1.x, this.a1.y, this.a1.z ], []);

			// vector from atom1 to atom2
			var a2b = [ this.a2.x - this.a1.x, this.a2.y - this.a1.y, this.a2.z - this.a1.z ];

			// calculate the rotation
			var y = [ 0, 1, 0 ];
			var ang = 0;
			var axis;
			if (this.a1.x === this.a2.x && this.a1.z === this.a2.z) {
				axis = [ 0, 0, 1 ];
				if (this.a2.y < this.a1.y) {
					ang = m.PI;
				}
			} else {
				ang = extensions.vec3AngleFrom(y, a2b);
				axis = v3.cross(y, a2b, []);
			}
			var scaleVector = [ radiusScale, height, radiusScale ];
			
			if (ang !== 0) {
				m4.rotate(transform, ang, axis);
			}
			m4.scale(transform, scaleVector);
			gl.setMatrixUniforms(transform);
			gl.material.setDiffuseColor(this.isHover ? '#885110' : '#0060B2');
			gl.drawArrays(gl.TRIANGLE_STRIP, 0, gl.cylinderBuffer.vertexPositionBuffer.numItems);
		}
	};
	/**
	 * 
	 * @param {WegGLRenderingContext}
	 *            gl
	 * @param {structures.VisualSpecifications}
	 *            specs
	 * @return {void}
	 */
	_.renderPicker = function(gl, specs) {

		// gl.cylinderBuffer.bindBuffers(gl);
		// gl.material.setDiffuseColor(
		// this.bondOrder == 0 ? '#FF0000' : // merah
		// this.bondOrder == 0.5 ? '#FFFF00' : // kuning
		// this.bondOrder == 1 ? '#FF00FF' : // ungu
		// this.bondOrder == 1.5 ? '#00FF00' : // hijau
		// this.bondOrder == 2 ? '#00FFFF' : // cyan
		// this.bondOrder == 3 ? '#0000FF' : // biru
		// '#FFFFFF');
		// gl.material.setAlpha(1);

		if (this.specs) {
			specs = this.specs;
		}
		// this is the elongation vector for the cylinder
		var height = this.a1.distance3D(this.a2);
		if (height === 0) {
			// if there is no height, then no point in rendering this bond,
			// just return
			return;
		}

		// scale factor for cylinder/pill radius.
		// when scale pill, the cap will affected too.
		var radiusScale = specs.bonds_cylinderDiameter_3D / 2;

		// transform to the atom as well as the opposite atom (for Jmol and
		// PyMOL
		// color splits)
		var transform = m4.translate(gl.modelViewMatrix, [ this.a1.x, this.a1.y, this.a1.z ], []);

		// vector from atom1 to atom2
		var a2b = [ this.a2.x - this.a1.x, this.a2.y - this.a1.y, this.a2.z - this.a1.z ];

		// calculate the rotation
		var y = [ 0, 1, 0 ];
		var ang = 0;
		var axis;
		if (this.a1.x === this.a2.x && this.a1.z === this.a2.z) {
			axis = [ 0, 0, 1 ];
			if (this.a2.y < this.a1.y) {
				ang = m.PI;
			}
		} else {
			ang = extensions.vec3AngleFrom(y, a2b);
			axis = v3.cross(y, a2b, []);
		}

		// calculate the translations for unsaturated bonds.
		// represenattio use saturatedCross are
		// - Line
		// - WIreframe
		// - Ball and Stick
		// just Stick will set bonds_showBondOrders_3D to false
		var others = [ 0 ];
		var saturatedCross;

		if (specs.bonds_showBondOrders_3D) {

			if (specs.bonds_renderAsLines_3D) {

				switch (this.bondOrder) {

				case 1.5:
				case 2:
					others = [ -specs.bonds_cylinderDiameter_3D, specs.bonds_cylinderDiameter_3D ];
					break;
				case 3:
					others = [ -1.2 * specs.bonds_cylinderDiameter_3D, 0, 1.2 * specs.bonds_cylinderDiameter_3D ];
					break;
				}

				// saturatedCross just need for need for bondorder greather than
				// 1
				if (this.bondOrder > 1) {
					var z = [ 0, 0, 1 ];
					var inverse = m4.inverse(gl.rotationMatrix, []);
					m4.multiplyVec3(inverse, z);
					saturatedCross = v3.cross(a2b, z, []);
					v3.normalize(saturatedCross);
				}

			} else {

				switch (this.bondOrder) {
				case 1.5:
				case 2:
					radiusScale *= 3;
					break;
				case 3:
					radiusScale *= 3.4;
					break;
				}

			}

		} else {
			// this is for Stick repersentation because Stick not have
			// bonds_showBondOrders_3D

			switch (this.bondOrder) {

			case 0:
				radiusScale *= 0.25;
				break;
			case 0.5:
			case 1.5:
				radiusScale *= 0.5;
				break;
			}

		}

		// Radius of cylinder already defined when initialize cylinder mesh,
		// so at this rate, the scale just needed for Y to strech
		// cylinder to bond length (height) and X and Z for radius.
		var scaleVector = [ radiusScale, height, radiusScale ];

		// render bonds
		for ( var i = 0, ii = others.length; i < ii; i++) {
			var transformUse = m4.set(transform, []);
			if (others[i] !== 0) {
				m4.translate(transformUse, v3.scale(saturatedCross, others[i], []));
			}
			if (ang !== 0) {
				m4.rotate(transformUse, ang, axis);
			}
			m4.scale(transformUse, scaleVector);

			// render
			gl.setMatrixUniforms(transformUse);
			if (specs.bonds_renderAsLines_3D) {
				gl.drawArrays(gl.LINES, 0, gl.lineBuffer.vertexPositionBuffer.numItems);
			} else {
				gl.drawArrays(gl.TRIANGLE_STRIP, 0, gl.cylinderBuffer.vertexPositionBuffer.numItems);
			}

		}
	};

})(ChemDoodle.ELEMENT, ChemDoodle.extensions, ChemDoodle.structures, ChemDoodle.math, Math, ChemDoodle.lib.mat4, ChemDoodle.lib.vec3);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(structures, m) {
	'use strict';
	structures.Ring = function() {
		this.atoms = [];
		this.bonds = [];
	};
	var _ = structures.Ring.prototype;
	_.center = undefined;
	_.setupBonds = function() {
		for ( var i = 0, ii = this.bonds.length; i < ii; i++) {
			this.bonds[i].ring = this;
		}
		this.center = this.getCenter();
	};
	_.getCenter = function() {
		var minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;
		for ( var i = 0, ii = this.atoms.length; i < ii; i++) {
			minX = m.min(this.atoms[i].x, minX);
			minY = m.min(this.atoms[i].y, minY);
			maxX = m.max(this.atoms[i].x, maxX);
			maxY = m.max(this.atoms[i].y, maxY);
		}
		return new structures.Point((maxX + minX) / 2, (maxY + minY) / 2);
	};

})(ChemDoodle.structures, Math);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(c, math, structures, RESIDUE, m) {
	'use strict';
	structures.Molecule = function() {
		this.atoms = [];
		this.bonds = [];
		this.rings = [];
	};
	var _ = structures.Molecule.prototype;
	// this can be an extensive algorithm for large molecules, you may want
	// to turn this off
	_.findRings = true;
	_.draw = function(ctx, specs) {
		if (this.specs) {
			specs = this.specs;
		}
		// draw
		// need this weird render of atoms before and after, just in case
		// circles are rendered, as those should be on top
		if (specs.atoms_display && !specs.atoms_circles_2D) {
			for ( var i = 0, ii = this.atoms.length; i < ii; i++) {
				this.atoms[i].draw(ctx, specs);
			}
		}
		if (specs.bonds_display) {
			for ( var i = 0, ii = this.bonds.length; i < ii; i++) {
				this.bonds[i].draw(ctx, specs);
			}
		}
		if (specs.atoms_display) {
			for ( var i = 0, ii = this.atoms.length; i < ii; i++) {
				var a = this.atoms[i];
				if(specs.atoms_circles_2D){
					a.draw(ctx, specs);
				}
				if(a.query){
					a.query.draw(ctx, specs, a);
				}
			}
		}
	};
	_.render = function(gl, specs) {
		// uncomment this to render the picking frame
		// return this.renderPickFrame(gl, specs, []);
		if (this.specs) {
			specs = this.specs;
		}
		// check explicitly if it is undefined here, since hetatm is a
		// boolean that can be true or false, as long as it is set, it is
		// macro
		var isMacro = this.atoms.length > 0 && this.atoms[0].hetatm !== undefined;
		if (isMacro) {
			if (specs.macro_displayBonds) {
				if (this.bonds.length > 0) {
					if (specs.bonds_renderAsLines_3D && !this.residueSpecs || this.residueSpecs && this.residueSpecs.bonds_renderAsLines_3D) {
						gl.lineWidth(this.residueSpecs ? this.residueSpecs.bonds_width_2D : specs.bonds_width_2D);
						gl.lineBuffer.bindBuffers(gl);
					} else {
						gl.cylinderBuffer.bindBuffers(gl);
					}
					// colors
					gl.material.setTempColors(specs.bonds_materialAmbientColor_3D, undefined, specs.bonds_materialSpecularColor_3D, specs.bonds_materialShininess_3D);
				}
				for ( var i = 0, ii = this.bonds.length; i < ii; i++) {
					var b = this.bonds[i];
					// closestDistance may be 0, so check if undefined
					if (!b.a1.hetatm && (specs.macro_atomToLigandDistance === -1 || (b.a1.closestDistance !== undefined && specs.macro_atomToLigandDistance >= b.a1.closestDistance && specs.macro_atomToLigandDistance >= b.a2.closestDistance))) {
						b.render(gl, this.residueSpecs ? this.residueSpecs : specs);
					}
				}
			}
			if (specs.macro_displayAtoms) {
				if (this.atoms.length > 0) {
					gl.sphereBuffer.bindBuffers(gl);
					// colors
					gl.material.setTempColors(specs.atoms_materialAmbientColor_3D, undefined, specs.atoms_materialSpecularColor_3D, specs.atoms_materialShininess_3D);
				}
				for ( var i = 0, ii = this.atoms.length; i < ii; i++) {
					var a = this.atoms[i];
					// closestDistance may be 0, so check if undefined
					if (!a.hetatm && (specs.macro_atomToLigandDistance === -1 || (a.closestDistance !== undefined && specs.macro_atomToLigandDistance >= a.closestDistance))) {
						a.render(gl, this.residueSpecs ? this.residueSpecs : specs);
					}
				}
			}
		}
		if (specs.bonds_display) {
			// Array for Half Bonds. It is needed because Half Bonds use the
			// pill buffer.
			var asPills = [];
			// Array for 0 bond order.
			var asSpheres = [];
			if (this.bonds.length > 0) {
				if (specs.bonds_renderAsLines_3D) {
					gl.lineWidth(specs.bonds_width_2D);
					gl.lineBuffer.bindBuffers(gl);
				} else {
					gl.cylinderBuffer.bindBuffers(gl);
				}
				// colors
				gl.material.setTempColors(specs.bonds_materialAmbientColor_3D, undefined, specs.bonds_materialSpecularColor_3D, specs.bonds_materialShininess_3D);
			}
			for ( var i = 0, ii = this.bonds.length; i < ii; i++) {
				var b = this.bonds[i];
				if (!isMacro || b.a1.hetatm) {
					// Check if render as segmented pill will used.
					if (specs.bonds_showBondOrders_3D) {
						if (b.bondOrder == 0) {
							// 0 bond order
							asSpheres.push(b);
						} else if (b.bondOrder == 0.5) {
							// 0.5 bond order
							asPills.push(b);
						} else {
							if (b.bondOrder == 1.5) {
								// For 1.5 bond order, the "1" part will render
								// as cylinder, and the "0.5" part will render
								// as segmented pills
								asPills.push(b);
							}
							b.render(gl, specs);
						}
					} else {
						// this will render the Stick representation
						b.render(gl, specs);
					}

				}
			}
			// Render the Half Bond
			if (asPills.length > 0) {
				// if bonds_renderAsLines_3D is true, then lineBuffer will
				// binded.
				// so in here we just need to check if we need to change
				// the binding buffer to pillBuffer or not.
				if (!specs.bonds_renderAsLines_3D) {
					gl.pillBuffer.bindBuffers(gl);
				}
				for ( var i = 0, ii = asPills.length; i < ii; i++) {
					asPills[i].render(gl, specs, true);
				}
			}
			// Render zero bond order
			if (asSpheres.length > 0) {
				// if bonds_renderAsLines_3D is true, then lineBuffer will
				// binded.
				// so in here we just need to check if we need to change
				// the binding buffer to pillBuffer or not.
				if (!specs.bonds_renderAsLines_3D) {
					gl.sphereBuffer.bindBuffers(gl);
				}
				for ( var i = 0, ii = asSpheres.length; i < ii; i++) {
					asSpheres[i].render(gl, specs, true);
				}
			}
		}
		if (specs.atoms_display) {
			for ( var i = 0, ii = this.atoms.length; i < ii; i++) {
				var a = this.atoms[i];
				a.bondNumber = 0;
				a.renderAsStar = false;
			}
			for ( var i = 0, ii = this.bonds.length; i < ii; i++) {
				var b = this.bonds[i];
				b.a1.bondNumber++;
				b.a2.bondNumber++;
			}
			if (this.atoms.length > 0) {
				gl.sphereBuffer.bindBuffers(gl);
				// colors
				gl.material.setTempColors(specs.atoms_materialAmbientColor_3D, undefined, specs.atoms_materialSpecularColor_3D, specs.atoms_materialShininess_3D);
			}
			var asStars = [];
			for ( var i = 0, ii = this.atoms.length; i < ii; i++) {
				var a = this.atoms[i];
				if (!isMacro || (a.hetatm && (specs.macro_showWater || !a.isWater))) {
					if (specs.atoms_nonBondedAsStars_3D && a.bondNumber === 0) {
						a.renderAsStar = true;
						asStars.push(a);
					} else {
						a.render(gl, specs);
					}
				}
			}
			if (asStars.length > 0) {
				gl.starBuffer.bindBuffers(gl);
				for ( var i = 0, ii = asStars.length; i < ii; i++) {
					asStars[i].render(gl, specs);
				}
			}
		}
		if (this.chains) {
			// set up the model view matrix, since it won't be modified
			// for macromolecules
			gl.setMatrixUniforms(gl.modelViewMatrix);
			// render chains
			if (specs.proteins_displayRibbon) {
				// proteins
				// colors
				gl.material.setTempColors(specs.proteins_materialAmbientColor_3D, undefined, specs.proteins_materialSpecularColor_3D, specs.proteins_materialShininess_3D);
				var uses = specs.proteins_ribbonCartoonize ? this.cartoons : this.ribbons;
				for ( var j = 0, jj = uses.length; j < jj; j++) {
					var use = uses[j];
					if (specs.proteins_residueColor !== 'none') {
						use.front.bindBuffers(gl);
						var rainbow = (specs.proteins_residueColor === 'rainbow');
						for ( var i = 0, ii = use.front.segments.length; i < ii; i++) {
							if (rainbow) {
								gl.material.setDiffuseColor(math.rainbowAt(i, ii, specs.macro_rainbowColors));
							}
							use.front.segments[i].render(gl, specs);
						}
						use.back.bindBuffers(gl);
						for ( var i = 0, ii = use.back.segments.length; i < ii; i++) {
							if (rainbow) {
								gl.material.setDiffuseColor(math.rainbowAt(i, ii, specs.macro_rainbowColors));
							}
							use.back.segments[i].render(gl, specs);
						}
					} else {
						use.front.render(gl, specs);
						use.back.render(gl, specs);
					}
				}
			}

			if(specs.proteins_displayPipePlank) {
				for ( var j = 0, jj = this.pipePlanks.length; j < jj; j++) {
					this.pipePlanks[j].render(gl, specs);
				}
			}

			if (specs.proteins_displayBackbone) {
				if (!this.alphaCarbonTrace) {
					// cache the alpha carbon trace
					this.alphaCarbonTrace = {
						nodes : [],
						edges : []
					};
					for ( var j = 0, jj = this.chains.length; j < jj; j++) {
						var rs = this.chains[j];
						var isNucleotide = rs.length > 2 && RESIDUE[rs[2].name] && RESIDUE[rs[2].name].aminoColor === '#BEA06E';
						if (!isNucleotide && rs.length > 0) {
							for ( var i = 0, ii = rs.length - 2; i < ii; i++) {
								var n = rs[i].cp1;
								n.chainColor = rs.chainColor;
								this.alphaCarbonTrace.nodes.push(n);
								var b = new structures.Bond(rs[i].cp1, rs[i + 1].cp1);
								b.residueName = rs[i].name;
								b.chainColor = rs.chainColor;
								this.alphaCarbonTrace.edges.push(b);
								if (i === rs.length - 3) {
									n = rs[i + 1].cp1;
									n.chainColor = rs.chainColor;
									this.alphaCarbonTrace.nodes.push(n);
								}
							}
						}
					}
				}
				if (this.alphaCarbonTrace.nodes.length > 0) {
					var traceSpecs = new structures.VisualSpecifications();
					traceSpecs.atoms_display = true;
					traceSpecs.bonds_display = true;
					traceSpecs.atoms_sphereDiameter_3D = specs.proteins_backboneThickness;
					traceSpecs.bonds_cylinderDiameter_3D = specs.proteins_backboneThickness;
					traceSpecs.bonds_useJMOLColors = false;
					traceSpecs.atoms_color = specs.proteins_backboneColor;
					traceSpecs.bonds_color = specs.proteins_backboneColor;
					traceSpecs.atoms_useVDWDiameters_3D = false;
					// colors
					gl.material.setTempColors(specs.proteins_materialAmbientColor_3D, undefined, specs.proteins_materialSpecularColor_3D, specs.proteins_materialShininess_3D);
					gl.material.setDiffuseColor(specs.proteins_backboneColor);
					for ( var i = 0, ii = this.alphaCarbonTrace.nodes.length; i < ii; i++) {
						var n = this.alphaCarbonTrace.nodes[i];
						if (specs.macro_colorByChain) {
							traceSpecs.atoms_color = n.chainColor;
						}
						gl.sphereBuffer.bindBuffers(gl);
						n.render(gl, traceSpecs);
					}
					for ( var i = 0, ii = this.alphaCarbonTrace.edges.length; i < ii; i++) {
						var e = this.alphaCarbonTrace.edges[i];
						var color;
						var r = RESIDUE[e.residueName] ? RESIDUE[e.residueName] : RESIDUE['*'];
						if (specs.macro_colorByChain) {
							color = e.chainColor;
						} else if (specs.proteins_residueColor === 'shapely') {
							color = r.shapelyColor;
						} else if (specs.proteins_residueColor === 'amino') {
							color = r.aminoColor;
						} else if (specs.proteins_residueColor === 'polarity') {
							if (r.polar) {
								color = '#C10000';
							} else {
								color = '#FFFFFF';
							}
						} else if (specs.proteins_residueColor === 'acidity') {
							if(r.acidity === 1){
								color = '#0000FF';
							}else if(r.acidity === -1){
								color = '#FF0000';
							}else if (r.polar) {
								color = '#FFFFFF';
							} else {
								color = '#773300';
							}
						} else if (specs.proteins_residueColor === 'rainbow') {
							color = math.rainbowAt(i, ii, specs.macro_rainbowColors);
						}
						if (color) {
							traceSpecs.bonds_color = color;
						}
						gl.cylinderBuffer.bindBuffers(gl);
						e.render(gl, traceSpecs);
					}
				}
			}
			if (specs.nucleics_display) {
				// nucleic acids
				// colors
				gl.material.setTempColors(specs.nucleics_materialAmbientColor_3D, undefined, specs.nucleics_materialSpecularColor_3D, specs.nucleics_materialShininess_3D);
				for ( var j = 0, jj = this.tubes.length; j < jj; j++) {
					gl.setMatrixUniforms(gl.modelViewMatrix);
					var use = this.tubes[j];
					use.render(gl, specs);
				}
			}
		}
		if (specs.atoms_display) {
			var highlight = false;
			for ( var i = 0, ii = this.atoms.length; i < ii; i++) {
				var a = this.atoms[i];
				if(a.isHover || a.isSelected){
					highlight = true;
					break;
				}
			}
			if(!highlight){
				for ( var i = 0, ii = this.bonds.length; i < ii; i++) {
					var b = this.bonds[i];
					if(b.isHover || b.isSelected){
						highlight = true;
						break;
					}
				}
			}
			if(highlight){
				gl.sphereBuffer.bindBuffers(gl);
				// colors
				gl.blendFunc(gl.SRC_ALPHA, gl.ONE);
				gl.material.setTempColors(specs.atoms_materialAmbientColor_3D, undefined, '#000000', 0);
				gl.enable(gl.BLEND);
				gl.depthMask(false);
				gl.material.setAlpha(.4);
				gl.sphereBuffer.bindBuffers(gl);
				for ( var i = 0, ii = this.atoms.length; i < ii; i++) {
					var a = this.atoms[i];
					if(a.isHover || a.isSelected){
						a.renderHighlight(gl, specs);
					}
				}
				gl.cylinderBuffer.bindBuffers(gl);
				for ( var i = 0, ii = this.bonds.length; i < ii; i++) {
					var b = this.bonds[i];
					if(b.isHover || b.isSelected){
						b.renderHighlight(gl, specs);
					}
				}
				gl.depthMask(true);
				gl.disable(gl.BLEND);
				gl.blendFuncSeparate(gl.SRC_ALPHA, gl.ONE_MINUS_SRC_ALPHA, gl.ONE, gl.ONE_MINUS_SRC_ALPHA);			
			}
		}
		if (this.surface && specs.surfaces_display) {
			gl.setMatrixUniforms(gl.modelViewMatrix);
			// gl.blendFunc(gl.SRC_ALPHA, gl.ONE);
			// gl.enable(gl.BLEND);
			// gl.disable(gl.DEPTH_TEST);
			this.surface.bindBuffers(gl);
			gl.material.setTempColors(specs.surfaces_materialAmbientColor_3D, specs.surfaces_color, specs.surfaces_materialSpecularColor_3D, specs.surfaces_materialShininess_3D);
			// gl.material.setAlpha(.2);
			if (specs.surfaces_style === 'Dot') {
				gl.drawArrays(gl.POINTS, 0, this.surface.vertexPositionBuffer.numItems);
				// } else if (specs.surfaces_style === 'Mesh') {
				// gl.drawElements(gl.LINES,
				// this.surface.vertexIndexBuffer.numItems,
				// gl.UNSIGNED_SHORT, 0);
			} else {
				gl.drawElements(gl.TRIANGLES, this.surface.vertexIndexBuffer.numItems, gl.UNSIGNED_SHORT, 0);
			}
			// gl.disable(gl.BLEND);
			// gl.enable(gl.DEPTH_TEST);
		}
	};
	_.renderPickFrame = function(gl, specs, objects, includeAtoms, includeBonds) {
		if (this.specs) {
			specs = this.specs;
		}
		var isMacro = this.atoms.length > 0 && this.atoms[0].hetatm !== undefined;
		if (includeBonds && specs.bonds_display) {
			if (this.bonds.length > 0) {
				if (specs.bonds_renderAsLines_3D) {
					gl.lineWidth(specs.bonds_width_2D);
					gl.lineBuffer.bindBuffers(gl);
				} else {
					gl.cylinderBuffer.bindBuffers(gl);
				}
			}
			for ( var i = 0, ii = this.bonds.length; i < ii; i++) {
				var b = this.bonds[i];
				if (!isMacro || b.a1.hetatm) {
					gl.material.setDiffuseColor(math.idx2color(objects.length));
					b.renderPicker(gl, specs);
					objects.push(b);
				}
			}
		}
		if (includeAtoms && specs.atoms_display) {
			for ( var i = 0, ii = this.atoms.length; i < ii; i++) {
				var a = this.atoms[i];
				a.bondNumber = 0;
				a.renderAsStar = false;
			}
			for ( var i = 0, ii = this.bonds.length; i < ii; i++) {
				var b = this.bonds[i];
				b.a1.bondNumber++;
				b.a2.bondNumber++;
			}
			if (this.atoms.length > 0) {
				gl.sphereBuffer.bindBuffers(gl);
			}
			var asStars = [];
			for ( var i = 0, ii = this.atoms.length; i < ii; i++) {
				var a = this.atoms[i];
				if (!isMacro || (a.hetatm && (specs.macro_showWater || !a.isWater))) {
					if (specs.atoms_nonBondedAsStars_3D && a.bondNumber === 0) {
						a.renderAsStar = true;
						asStars.push(a);
					} else {
						gl.material.setDiffuseColor(math.idx2color(objects.length));
						a.render(gl, specs, true);
						objects.push(a);
					}
				}
			}
			if (asStars.length > 0) {
				gl.starBuffer.bindBuffers(gl);
				for ( var i = 0, ii = asStars.length; i < ii; i++) {
					var a = asStars[i];
					gl.material.setDiffuseColor(math.idx2color(objects.length));
					a.render(gl, specs, true);
					objects.push(a);
				}
			}
		}
	};
	_.getCenter3D = function() {
		if (this.atoms.length === 1) {
			return new structures.Atom('C', this.atoms[0].x, this.atoms[0].y, this.atoms[0].z);
		}
		var minX = Infinity, minY = Infinity, minZ = Infinity;
		var maxX = -Infinity, maxY = -Infinity, maxZ = -Infinity;
		if (this.chains) {
			// residues
			for ( var i = 0, ii = this.chains.length; i < ii; i++) {
				var chain = this.chains[i];
				for ( var j = 0, jj = chain.length; j < jj; j++) {
					var residue = chain[j];
					minX = m.min(residue.cp1.x, residue.cp2.x, minX);
					minY = m.min(residue.cp1.y, residue.cp2.y, minY);
					minZ = m.min(residue.cp1.z, residue.cp2.z, minZ);
					maxX = m.max(residue.cp1.x, residue.cp2.x, maxX);
					maxY = m.max(residue.cp1.y, residue.cp2.y, maxY);
					maxZ = m.max(residue.cp1.z, residue.cp2.z, maxZ);
				}
			}
		}
		for ( var i = 0, ii = this.atoms.length; i < ii; i++) {
			minX = m.min(this.atoms[i].x, minX);
			minY = m.min(this.atoms[i].y, minY);
			minZ = m.min(this.atoms[i].z, minZ);
			maxX = m.max(this.atoms[i].x, maxX);
			maxY = m.max(this.atoms[i].y, maxY);
			maxZ = m.max(this.atoms[i].z, maxZ);
		}
		return new structures.Atom('C', (maxX + minX) / 2, (maxY + minY) / 2, (maxZ + minZ) / 2);
	};
	_.getCenter = function() {
		if (this.atoms.length === 1) {
			return new structures.Point(this.atoms[0].x, this.atoms[0].y);
		}
		var minX = Infinity, minY = Infinity;
		var maxX = -Infinity, maxY = -Infinity;
		for ( var i = 0, ii = this.atoms.length; i < ii; i++) {
			minX = m.min(this.atoms[i].x, minX);
			minY = m.min(this.atoms[i].y, minY);
			maxX = m.max(this.atoms[i].x, maxX);
			maxY = m.max(this.atoms[i].y, maxY);
		}
		return new structures.Point((maxX + minX) / 2, (maxY + minY) / 2);
	};
	_.getDimension = function() {
		if (this.atoms.length === 1) {
			return new structures.Point(0, 0);
		}
		var minX = Infinity, minY = Infinity;
		var maxX = -Infinity, maxY = -Infinity;
		if (this.chains) {
			for ( var i = 0, ii = this.chains.length; i < ii; i++) {
				var chain = this.chains[i];
				for ( var j = 0, jj = chain.length; j < jj; j++) {
					var residue = chain[j];
					minX = m.min(residue.cp1.x, residue.cp2.x, minX);
					minY = m.min(residue.cp1.y, residue.cp2.y, minY);
					maxX = m.max(residue.cp1.x, residue.cp2.x, maxX);
					maxY = m.max(residue.cp1.y, residue.cp2.y, maxY);
				}
			}
			minX -= 30;
			minY -= 30;
			maxX += 30;
			maxY += 30;
		}
		for ( var i = 0, ii = this.atoms.length; i < ii; i++) {
			minX = m.min(this.atoms[i].x, minX);
			minY = m.min(this.atoms[i].y, minY);
			maxX = m.max(this.atoms[i].x, maxX);
			maxY = m.max(this.atoms[i].y, maxY);
		}
		return new structures.Point(maxX - minX, maxY - minY);
	};
	_.check = function(force) {
		// using force improves efficiency, so changes will not be checked
		// until a render occurs
		// you can force a check by sending true to this function after
		// calling check with a false
		if (force && this.doChecks) {
			// only check if the number of bonds has changed
			if (this.findRings) {
				if (this.bonds.length - this.atoms.length !== this.fjNumCache) {
					// find rings
					this.rings = new c.informatics.SSSRFinder(this).rings;
					for ( var i = 0, ii = this.bonds.length; i < ii; i++) {
						this.bonds[i].ring = undefined;
					}
					for ( var i = 0, ii = this.rings.length; i < ii; i++) {
						this.rings[i].setupBonds();
					}
				} else {
					// update rings if any
					for ( var i = 0, ii = this.rings.length; i < ii; i++) {
						var r = this.rings[i];
						r.center = r.getCenter();
					}
				}
			}
			// find lones
			for ( var i = 0, ii = this.atoms.length; i < ii; i++) {
				this.atoms[i].isLone = false;
				if (this.atoms[i].label === 'C') {
					var counter = 0;
					for ( var j = 0, jj = this.bonds.length; j < jj; j++) {
						if (this.bonds[j].a1 === this.atoms[i] || this.bonds[j].a2 === this.atoms[i]) {
							counter++;
						}
					}
					if (counter === 0) {
						this.atoms[i].isLone = true;
					}
				}
			}
			// sort
			var sort = false;
			for ( var i = 0, ii = this.atoms.length; i < ii; i++) {
				if (this.atoms[i].z !== 0) {
					sort = true;
				}
			}
			if (sort) {
				this.sortAtomsByZ();
				this.sortBondsByZ();
			}
			// setup metadata
			this.setupMetaData();
			this.atomNumCache = this.atoms.length;
			this.bondNumCache = this.bonds.length;
			// fj number cache doesnt care if there are separate molecules,
			// as the change will signal a need to check for rings; the
			// accuracy doesn't matter
			this.fjNumCache = this.bonds.length - this.atoms.length;
		}
		this.doChecks = !force;
	};
	_.getAngles = function(a) {
		var angles = [];
		for ( var i = 0, ii = this.bonds.length; i < ii; i++) {
			if (this.bonds[i].contains(a)) {
				angles.push(a.angle(this.bonds[i].getNeighbor(a)));
			}
		}
		angles.sort();
		return angles;
	};
	_.getCoordinationNumber = function(bs) {
		var coordinationNumber = 0;
		for ( var i = 0, ii = bs.length; i < ii; i++) {
			coordinationNumber += bs[i].bondOrder;
		}
		return coordinationNumber;
	};
	_.getBonds = function(a) {
		var bonds = [];
		for ( var i = 0, ii = this.bonds.length; i < ii; i++) {
			if (this.bonds[i].contains(a)) {
				bonds.push(this.bonds[i]);
			}
		}
		return bonds;
	};
	_.sortAtomsByZ = function() {
		for ( var i = 1, ii = this.atoms.length; i < ii; i++) {
			var index = i;
			while (index > 0 && this.atoms[index].z < this.atoms[index - 1].z) {
				var hold = this.atoms[index];
				this.atoms[index] = this.atoms[index - 1];
				this.atoms[index - 1] = hold;
				index--;
			}
		}
	};
	_.sortBondsByZ = function() {
		for ( var i = 1, ii = this.bonds.length; i < ii; i++) {
			var index = i;
			while (index > 0 && (this.bonds[index].a1.z + this.bonds[index].a2.z) < (this.bonds[index - 1].a1.z + this.bonds[index - 1].a2.z)) {
				var hold = this.bonds[index];
				this.bonds[index] = this.bonds[index - 1];
				this.bonds[index - 1] = hold;
				index--;
			}
		}
	};
	_.setupMetaData = function() {
		var center = this.getCenter();
		for ( var i = 0, ii = this.atoms.length; i < ii; i++) {
			var a = this.atoms[i];
			a.bonds = this.getBonds(a);
			a.angles = this.getAngles(a);
			a.isHidden = a.bonds.length === 2 && m.abs(m.abs(a.angles[1] - a.angles[0]) - m.PI) < m.PI / 30 && a.bonds[0].bondOrder === a.bonds[1].bondOrder;
			var angleData = math.angleBetweenLargest(a.angles);
			a.angleOfLeastInterference = angleData.angle % (m.PI * 2);
			a.largestAngle = angleData.largest;
			a.coordinationNumber = this.getCoordinationNumber(a.bonds);
			a.bondNumber = a.bonds.length;
			a.molCenter = center;
		}
		for ( var i = 0, ii = this.bonds.length; i < ii; i++) {
			var b = this.bonds[i];
			b.molCenter = center;
		}
	};
	_.scaleToAverageBondLength = function(length) {
		var avBondLength = this.getAverageBondLength();
		if (avBondLength !== 0) {
			var scale = length / avBondLength;
			for ( var i = 0, ii = this.atoms.length; i < ii; i++) {
				this.atoms[i].x *= scale;
				this.atoms[i].y *= scale;
			}
		}
	};
	_.getAverageBondLength = function() {
		if (this.bonds.length === 0) {
			return 0;
		}
		var tot = 0;
		for ( var i = 0, ii = this.bonds.length; i < ii; i++) {
			tot += this.bonds[i].getLength();
		}
		tot /= this.bonds.length;
		return tot;
	};
	_.getBounds = function() {
		var bounds = new math.Bounds();
		for ( var i = 0, ii = this.atoms.length; i < ii; i++) {
			bounds.expand(this.atoms[i].getBounds());
		}
		if (this.chains) {
			for ( var i = 0, ii = this.chains.length; i < ii; i++) {
				var chain = this.chains[i];
				for ( var j = 0, jj = chain.length; j < jj; j++) {
					var residue = chain[j];
					bounds.expand(residue.cp1.x, residue.cp1.y);
					bounds.expand(residue.cp2.x, residue.cp2.y);
				}
			}
			bounds.minX -= 30;
			bounds.minY -= 30;
			bounds.maxX += 30;
			bounds.maxY += 30;
		}
		return bounds;
	};
	_.getBounds3D = function() {
		var bounds = new math.Bounds();
		for ( var i = 0, ii = this.atoms.length; i < ii; i++) {
			bounds.expand(this.atoms[i].getBounds3D());
		}
		if (this.chains) {
			for ( var i = 0, ii = this.chains.length; i < ii; i++) {
				var chain = this.chains[i];
				for ( var j = 0, jj = chain.length; j < jj; j++) {
					var residue = chain[j];
					bounds.expand3D(residue.cp1.x, residue.cp1.y, residue.cp1.z);
					bounds.expand3D(residue.cp2.x, residue.cp2.y, residue.cp2.z);
				}
			}
		}
		return bounds;
	};

})(ChemDoodle, ChemDoodle.math, ChemDoodle.structures, ChemDoodle.RESIDUE, Math);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(structures, m, m4, v3) {
	'use strict';
	var SB;
	var lastVerticalResolution = -1;

	function setupMatrices(verticalResolution) {
		var n2 = verticalResolution * verticalResolution;
		var n3 = verticalResolution * verticalResolution * verticalResolution;
		var S = [ 6 / n3, 0, 0, 0, 6 / n3, 2 / n2, 0, 0, 1 / n3, 1 / n2, 1 / verticalResolution, 0, 0, 0, 0, 1 ];
		var Bm = [ -1 / 6, 1 / 2, -1 / 2, 1 / 6, 1 / 2, -1, 1 / 2, 0, -1 / 2, 0, 1 / 2, 0, 1 / 6, 2 / 3, 1 / 6, 0 ];
		SB = m4.multiply(Bm, S, []);
		lastVerticalResolution = verticalResolution;
	}

	structures.Residue = function(resSeq) {
		// number of vertical slashes per segment
		this.resSeq = resSeq;
	};
	var _ = structures.Residue.prototype;
	_.setup = function(nextAlpha, horizontalResolution) {
		this.horizontalResolution = horizontalResolution;
		// define plane
		var A = [ nextAlpha.x - this.cp1.x, nextAlpha.y - this.cp1.y, nextAlpha.z - this.cp1.z ];
		var B = [ this.cp2.x - this.cp1.x, this.cp2.y - this.cp1.y, this.cp2.z - this.cp1.z ];
		var C = v3.cross(A, B, []);
		this.D = v3.cross(C, A, []);
		v3.normalize(C);
		v3.normalize(this.D);
		// generate guide coordinates
		// guides for the narrow parts of the ribbons
		this.guidePointsSmall = [];
		// guides for the wide parts of the ribbons
		this.guidePointsLarge = [];
		// guides for the ribbon part of helix as cylinder model
		var P = [ (nextAlpha.x + this.cp1.x) / 2, (nextAlpha.y + this.cp1.y) / 2, (nextAlpha.z + this.cp1.z) / 2 ];
		if (this.helix) {
			// expand helices
			v3.scale(C, 1.5);
			v3.add(P, C);
		}
		this.guidePointsSmall[0] = new structures.Atom('', P[0] - this.D[0] / 2, P[1] - this.D[1] / 2, P[2] - this.D[2] / 2);
		for ( var i = 1; i < horizontalResolution; i++) {
			this.guidePointsSmall[i] = new structures.Atom('', this.guidePointsSmall[0].x + this.D[0] * i / horizontalResolution, this.guidePointsSmall[0].y + this.D[1] * i / horizontalResolution, this.guidePointsSmall[0].z + this.D[2] * i / horizontalResolution);
		}
		v3.scale(this.D, 4);
		this.guidePointsLarge[0] = new structures.Atom('', P[0] - this.D[0] / 2, P[1] - this.D[1] / 2, P[2] - this.D[2] / 2);
		for ( var i = 1; i < horizontalResolution; i++) {
			this.guidePointsLarge[i] = new structures.Atom('', this.guidePointsLarge[0].x + this.D[0] * i / horizontalResolution, this.guidePointsLarge[0].y + this.D[1] * i / horizontalResolution, this.guidePointsLarge[0].z + this.D[2] * i / horizontalResolution);
		}
	};
	_.getGuidePointSet = function(type) {
		if (type === 0) {
			return this.helix || this.sheet ? this.guidePointsLarge : this.guidePointsSmall;
		} else if (type === 1) {
			return this.guidePointsSmall;
		} else if (type === 2) {
			return this.guidePointsLarge;
		}
	};
	_.computeLineSegments = function(b2, b1, a1, doCartoon, verticalResolution) {
		this.setVerticalResolution(verticalResolution);
		this.split = a1.helix !== this.helix || a1.sheet !== this.sheet;
		this.lineSegments = this.innerCompute(0, b2, b1, a1, false, verticalResolution);
		if (doCartoon) {
			this.lineSegmentsCartoon = this.innerCompute(this.helix || this.sheet ? 2 : 1, b2, b1, a1, true, verticalResolution);
		}
	};
	_.innerCompute = function(set, b2, b1, a1, useArrows, verticalResolution) {
		var segments = [];
		var use = this.getGuidePointSet(set);
		var useb2 = b2.getGuidePointSet(set);
		var useb1 = b1.getGuidePointSet(set);
		var usea1 = a1.getGuidePointSet(set);
		for ( var l = 0, ll = use.length; l < ll; l++) {
			var G = [ useb2[l].x, useb2[l].y, useb2[l].z, 1, useb1[l].x, useb1[l].y, useb1[l].z, 1, use[l].x, use[l].y, use[l].z, 1, usea1[l].x, usea1[l].y, usea1[l].z, 1 ];
			var M = m4.multiply(G, SB, []);
			var strand = [];
			for ( var k = 0; k < verticalResolution; k++) {
				for ( var i = 3; i > 0; i--) {
					for ( var j = 0; j < 4; j++) {
						M[i * 4 + j] += M[(i - 1) * 4 + j];
					}
				}
				strand[k] = new structures.Atom('', M[12] / M[15], M[13] / M[15], M[14] / M[15]);
			}
			segments[l] = strand;
		}
		if (useArrows && this.arrow) {
			for ( var i = 0, ii = verticalResolution; i < ii; i++) {
				var mult = 1.5 - 1.3 * i / verticalResolution;
				var mid = m.floor(this.horizontalResolution / 2);
				var center = segments[mid];
				for ( var j = 0, jj = segments.length; j < jj; j++) {
					if (j !== mid) {
						var o = center[i];
						var f = segments[j][i];
						var vec = [ f.x - o.x, f.y - o.y, f.z - o.z ];
						v3.scale(vec, mult);
						f.x = o.x + vec[0];
						f.y = o.y + vec[1];
						f.z = o.z + vec[2];
					}
				}
			}
		}
		return segments;
	};
	_.setVerticalResolution = function(verticalResolution) {
		if (verticalResolution !== lastVerticalResolution) {
			setupMatrices(verticalResolution);
		}
	};

})(ChemDoodle.structures, Math, ChemDoodle.lib.mat4, ChemDoodle.lib.vec3);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(extensions, structures, math, q, m) {
	'use strict';
	structures.Spectrum = function() {
		this.data = [];
		this.metadata = [];
		this.dataDisplay = [];
		this.memory = {
			offsetTop : 0,
			offsetLeft : 0,
			offsetBottom : 0,
			flipXAxis : false,
			scale : 1,
			width : 0,
			height : 0
		};
	};
	var _ = structures.Spectrum.prototype;
	_.title = undefined;
	_.xUnit = undefined;
	_.yUnit = undefined;
	_.continuous = true;
	_.integrationSensitivity = 0.01;
	_.draw = function(ctx, specs, width, height) {
		if (this.specs) {
			specs = this.specs;
		}
		var offsetTop = 5;
		var offsetLeft = 0;
		var offsetBottom = 0;
		// draw decorations
		ctx.fillStyle = specs.text_color;
		ctx.textAlign = 'center';
		ctx.textBaseline = 'alphabetic';
		ctx.font = extensions.getFontString(specs.text_font_size, specs.text_font_families);
		if (this.xUnit) {
			offsetBottom += specs.text_font_size;
			ctx.fillText(this.xUnit, width / 2, height - 2);
		}
		if (this.yUnit && specs.plots_showYAxis) {
			offsetLeft += specs.text_font_size;
			ctx.save();
			ctx.translate(specs.text_font_size, height / 2);
			ctx.rotate(-m.PI / 2);
			ctx.fillText(this.yUnit, 0, 0);
			ctx.restore();
		}
		if (this.title) {
			offsetTop += specs.text_font_size;
			ctx.fillText(this.title, width / 2, specs.text_font_size);
		}
		// draw ticks
		ctx.lineCap = 'square';
		offsetBottom += 5 + specs.text_font_size;
		if (specs.plots_showYAxis) {
			offsetLeft += 5 + ctx.measureText('1000').width;
		}
		if (specs.plots_showGrid) {
			ctx.strokeStyle = specs.plots_gridColor;
			ctx.lineWidth = specs.plots_gridLineWidth;
			ctx.strokeRect(offsetLeft, offsetTop, width - offsetLeft, height - offsetBottom - offsetTop);
		}
		ctx.textAlign = 'center';
		ctx.textBaseline = 'top';
		var span = this.maxX - this.minX;
		var t = span / 100;
		var major = .001;
		while (major < t || span / major > 25) {
			major *= 10;
		}
		var counter = 0;
		var overlapX = specs.plots_flipXAxis ? width : 0;
		for ( var i = m.round(this.minX / major) * major; i <= this.maxX; i += major / 2) {
			var x = this.getTransformedX(i, specs, width, offsetLeft);
			if (x > offsetLeft) {
				ctx.strokeStyle = 'black';
				ctx.lineWidth = 1;
				if (counter % 2 === 0) {
					ctx.beginPath();
					ctx.moveTo(x, height - offsetBottom);
					ctx.lineTo(x, height - offsetBottom + 2);
					ctx.stroke();
					var s = i.toFixed(5);
					while (s.charAt(s.length - 1) === '0') {
						s = s.substring(0, s.length - 1);
					}
					if (s.charAt(s.length - 1) === '.') {
						s = s.substring(0, s.length - 1);
					}
					// do this to avoid label overlap
					var numWidth = ctx.measureText(s).width;
					if (specs.plots_flipXAxis) {
						numWidth *= -1;
					}
					var ls = x - numWidth / 2;
					if (specs.plots_flipXAxis ? ls < overlapX : ls > overlapX) {
						ctx.fillText(s, x, height - offsetBottom + 2);
						overlapX = x + numWidth / 2;
					}
					if (specs.plots_showGrid) {
						ctx.strokeStyle = specs.plots_gridColor;
						ctx.lineWidth = specs.plots_gridLineWidth;
						ctx.beginPath();
						ctx.moveTo(x, height - offsetBottom);
						ctx.lineTo(x, offsetTop);
						ctx.stroke();
					}
				} else {
					ctx.beginPath();
					ctx.moveTo(x, height - offsetBottom);
					ctx.lineTo(x, height - offsetBottom + 2);
					ctx.stroke();
				}
			}
			counter++;
		}
		if (specs.plots_showYAxis || specs.plots_showGrid) {
			var spany = 1 / specs.scale;
			ctx.textAlign = 'right';
			ctx.textBaseline = 'middle';
			for ( var i = 0; i <= 10; i++) {
				var yval = spany / 10 * i;
				var y = offsetTop + (height - offsetBottom - offsetTop) * (1 - yval * specs.scale);
				if (specs.plots_showGrid) {
					ctx.strokeStyle = specs.plots_gridColor;
					ctx.lineWidth = specs.plots_gridLineWidth;
					ctx.beginPath();
					ctx.moveTo(offsetLeft, y);
					ctx.lineTo(width, y);
					ctx.stroke();
				}
				if (specs.plots_showYAxis) {
					ctx.strokeStyle = 'black';
					ctx.lineWidth = 1;
					ctx.beginPath();
					ctx.moveTo(offsetLeft, y);
					ctx.lineTo(offsetLeft - 3, y);
					ctx.stroke();
					var val = yval * 100;
					var cutoff = m.max(0, 3 - m.floor(val).toString().length);
					var s = val.toFixed(cutoff);
					if (cutoff > 0) {
						while (s.charAt(s.length - 1) === '0') {
							s = s.substring(0, s.length - 1);
						}
					}
					if (s.charAt(s.length - 1) === '.') {
						s = s.substring(0, s.length - 1);
					}
					ctx.fillText(s, offsetLeft - 3, y);
				}
			}
		}
		// draw axes
		ctx.strokeStyle = 'black';
		ctx.lineWidth = 1;
		ctx.beginPath();
		// draw x axis
		ctx.moveTo(width, height - offsetBottom);
		ctx.lineTo(offsetLeft, height - offsetBottom);
		// draw y axis
		if (specs.plots_showYAxis) {
			ctx.lineTo(offsetLeft, offsetTop);
		}
		ctx.stroke();
		// draw metadata
		if (this.dataDisplay.length > 0) {
			ctx.textAlign = 'left';
			ctx.textBaseline = 'top';
			var mcount = 0;
			for ( var i = 0, ii = this.dataDisplay.length; i < ii; i++) {
				if (this.dataDisplay[i].value) {
					ctx.fillText([ this.dataDisplay[i].display, ': ', this.dataDisplay[i].value ].join(''), offsetLeft + 10, offsetTop + 10 + mcount * (specs.text_font_size + 5));
					mcount++;
				} else if (this.dataDisplay[i].tag) {
					for ( var j = 0, jj = this.metadata.length; j < jj; j++) {
						if (extensions.stringStartsWith(this.metadata[j], this.dataDisplay[i].tag)) {
							var draw = this.metadata[j];
							if (this.dataDisplay[i].display) {
								var index = this.metadata[j].indexOf('=');
								draw = [ this.dataDisplay[i].display, ': ', index > -1 ? this.metadata[j].substring(index + 2) : this.metadata[j] ].join('');
							}
							ctx.fillText(draw, offsetLeft + 10, offsetTop + 10 + mcount * (specs.text_font_size + 5));
							mcount++;
							break;
						}
					}
				}
			}
		}
		this.drawPlot(ctx, specs, width, height, offsetTop, offsetLeft, offsetBottom);
		this.memory.offsetTop = offsetTop;
		this.memory.offsetLeft = offsetLeft;
		this.memory.offsetBottom = offsetBottom;
		this.memory.flipXAxis = specs.plots_flipXAxis;
		this.memory.scale = specs.scale;
		this.memory.width = width;
		this.memory.height = height;
	};
	_.drawPlot = function(ctx, specs, width, height, offsetTop, offsetLeft, offsetBottom) {
		if (this.specs) {
			specs = this.specs;
		}
		ctx.strokeStyle = specs.plots_color;
		ctx.lineWidth = specs.plots_width;
		var integration = [];
		// clip the spectrum display bounds here to not draw over the axes
		// we do this because we want to continue drawing segments to their natural ends to be accurate, but don't want to see them past the display area
		ctx.save();
		ctx.rect(offsetLeft, offsetTop, width-offsetLeft, height-offsetBottom-offsetTop);
		ctx.clip();
		ctx.beginPath();
		if (this.continuous) {
			var started = false;
			var counter = 0;
			var stop = false;
			for ( var i = 0, ii = this.data.length; i < ii; i++) {
				var x = this.getTransformedX(this.data[i].x, specs, width, offsetLeft);
				var xnext;
				if (i < ii && !started && this.data[i+1]) {
					// see if you should render this first segment
					xnext = this.getTransformedX(this.data[i + 1].x, specs, width, offsetLeft);
				}
				// check xnext against undefined as it can be 0/1
				if (x >= offsetLeft && x < width || xnext !== undefined && xnext >= offsetLeft && xnext < width) {
					var y = this.getTransformedY(this.data[i].y, specs, height, offsetBottom, offsetTop);
					if (specs.plots_showIntegration && m.abs(this.data[i].y) > this.integrationSensitivity) {
						integration.push(new structures.Point(this.data[i].x, this.data[i].y));
					}
					if (!started) {
						ctx.moveTo(x, y);
						started = true;
					}
					ctx.lineTo(x, y);
					counter++;
					if (counter % 1000 === 0) {
						// segment the path to avoid crashing safari on mac os x
						ctx.stroke();
						ctx.beginPath();
						ctx.moveTo(x, y);
					}
					if (stop) {
						break;
					}
				} else if (started) {
					// render one more segment
					stop = true;
				}
			}
		} else {
			for ( var i = 0, ii = this.data.length; i < ii; i++) {
				var x = this.getTransformedX(this.data[i].x, specs, width, offsetLeft);
				if (x >= offsetLeft && x < width) {
					ctx.moveTo(x, height - offsetBottom);
					ctx.lineTo(x, this.getTransformedY(this.data[i].y, specs, height, offsetBottom, offsetTop));
				}
			}
		}
		ctx.stroke();
		if (specs.plots_showIntegration && integration.length > 1) {
			ctx.strokeStyle = specs.plots_integrationColor;
			ctx.lineWidth = specs.plots_integrationLineWidth;
			ctx.beginPath();
			var ascending = integration[1].x > integration[0].x;
			var max;
			if (this.flipXAxis && !ascending || !this.flipXAxis && ascending) {
				for ( var i = integration.length - 2; i >= 0; i--) {
					integration[i].y = integration[i].y + integration[i + 1].y;
				}
				max = integration[0].y;
			} else {
				for ( var i = 1, ii = integration.length; i < ii; i++) {
					integration[i].y = integration[i].y + integration[i - 1].y;
				}
				max = integration[integration.length - 1].y;
			}
			for ( var i = 0, ii = integration.length; i < ii; i++) {
				var x = this.getTransformedX(integration[i].x, specs, width, offsetLeft);
				var y = this.getTransformedY(integration[i].y / specs.scale / max, specs, height, offsetBottom, offsetTop);
				if (i === 0) {
					ctx.moveTo(x, y);
				} else {
					ctx.lineTo(x, y);
				}
			}
			ctx.stroke();
		}
		ctx.restore();
	};
	_.getTransformedY = function(y, specs, height, offsetBottom, offsetTop) {
		return offsetTop + (height - offsetBottom - offsetTop) * (1 - y * specs.scale);
	};
	_.getInverseTransformedY = function(y) {
		// can only be called after a render when memory is set, this
		// function doesn't make sense without a render first anyway
		return (1 - (y - this.memory.offsetTop) / (this.memory.height - this.memory.offsetBottom - this.memory.offsetTop)) / this.memory.scale * 100;
	};
	_.getTransformedX = function(x, specs, width, offsetLeft) {
		var returning = offsetLeft + (x - this.minX) / (this.maxX - this.minX) * (width - offsetLeft);
		if (specs.plots_flipXAxis) {
			returning = width + offsetLeft - returning;
		}
		return returning;
	};
	_.getInverseTransformedX = function(x) {
		// can only be called after a render when memory is set, this
		// function doesn't make sense without a render first anyway
		if (this.memory.flipXAxis) {
			x = this.memory.width + this.memory.offsetLeft - x;
		}
		return (x - this.memory.offsetLeft) * (this.maxX - this.minX) / (this.memory.width - this.memory.offsetLeft) + this.minX;
	};
	_.setup = function() {
		var xmin = Number.MAX_VALUE;
		var xmax = Number.MIN_VALUE;
		var ymax = Number.MIN_VALUE;
		for ( var i = 0, ii = this.data.length; i < ii; i++) {
			xmin = m.min(xmin, this.data[i].x);
			xmax = m.max(xmax, this.data[i].x);
			ymax = m.max(ymax, this.data[i].y);
		}
		if (this.continuous) {
			this.minX = xmin;
			this.maxX = xmax;
		} else {
			this.minX = xmin - 1;
			this.maxX = xmax + 1;
		}
		for ( var i = 0, ii = this.data.length; i < ii; i++) {
			this.data[i].y /= ymax;
		}
	};
	_.zoom = function(pixel1, pixel2, width, rescaleY) {
		var p1 = this.getInverseTransformedX(pixel1);
		var p2 = this.getInverseTransformedX(pixel2);
		this.minX = m.min(p1, p2);
		this.maxX = m.max(p1, p2);
		if (rescaleY) {
			var ymax = Number.MIN_VALUE;
			for ( var i = 0, ii = this.data.length; i < ii; i++) {
				if (math.isBetween(this.data[i].x, this.minX, this.maxX)) {
					ymax = m.max(ymax, this.data[i].y);
				}
			}
			return 1 / ymax;
		}
	};
	_.translate = function(dif, width) {
		var dist = dif / (width - this.memory.offsetLeft) * (this.maxX - this.minX) * (this.memory.flipXAxis ? 1 : -1);
		this.minX += dist;
		this.maxX += dist;
	};
	_.alertMetadata = function() {
		alert(this.metadata.join('\n'));
	};
	_.getInternalCoordinates = function(x, y) {
		return new ChemDoodle.structures.Point(this.getInverseTransformedX(x), this.getInverseTransformedY(y));
	};
	_.getClosestPlotInternalCoordinates = function(x) {
		var xtl = this.getInverseTransformedX(x - 1);
		var xtr = this.getInverseTransformedX(x + 1);
		if (xtl > xtr) {
			var temp = xtl;
			xtl = xtr;
			xtr = temp;
		}
		var highest = -1;
		var max = -Infinity;
		var inRange = false;
		for ( var i = 0, ii = this.data.length; i < ii; i++) {
			var p = this.data[i];
			if (math.isBetween(p.x, xtl, xtr)) {
				if (p.y > max) {
					inRange = true;
					max = p.y;
					highest = i;
				}
			} else if (inRange) {
				break;
			}
		}
		if (highest === -1) {
			return undefined;
		}
		var p = this.data[highest];
		return new ChemDoodle.structures.Point(p.x, p.y * 100);
	};
	_.getClosestPeakInternalCoordinates = function(x) {
		var xt = this.getInverseTransformedX(x);
		var closest = 0;
		var dif = Infinity;
		for ( var i = 0, ii = this.data.length; i < ii; i++) {
			var sub = m.abs(this.data[i].x - xt);
			if (sub <= dif) {
				dif = sub;
				closest = i;
			} else {
				break;
			}
		}
		var highestLeft = closest, highestRight = closest;
		var maxLeft = this.data[closest].y, maxRight = this.data[closest].y;
		for ( var i = closest + 1, ii = this.data.length; i < ii; i++) {
			if (this.data[i].y + .05 > maxRight) {
				maxRight = this.data[i].y;
				highestRight = i;
			} else {
				break;
			}
		}
		for ( var i = closest - 1; i >= 0; i--) {
			if (this.data[i].y + .05 > maxLeft) {
				maxLeft = this.data[i].y;
				highestLeft = i;
			} else {
				break;
			}
		}
		var p = this.data[highestLeft - closest > highestRight - closest ? highestRight : highestLeft];
		return new ChemDoodle.structures.Point(p.x, p.y * 100);
	};

})(ChemDoodle.extensions, ChemDoodle.structures, ChemDoodle.math, ChemDoodle.lib.jQuery, Math);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(math, d2, m) {
	'use strict';
	d2._Shape = function() {
	};
	var _ = d2._Shape.prototype;
	_.drawDecorations = function(ctx, specs) {
		if (this.isHover) {
			var ps = this.getPoints();
			for ( var i = 0, ii = ps.length; i < ii; i++) {
				var p = ps[i];
				this.drawAnchor(ctx, specs, p, p === this.hoverPoint);
			}
		}
	};
	_.getBounds = function() {
		var bounds = new math.Bounds();
		var ps = this.getPoints();
		for ( var i = 0, ii = ps.length; i < ii; i++) {
			var p = ps[i];
			bounds.expand(p.x, p.y);
		}
		return bounds;
	};
	_.drawAnchor = function(ctx, specs, p, hovered) {
		ctx.save();
		ctx.translate(p.x, p.y);
		ctx.rotate(m.PI / 4);
		ctx.scale(1 / specs.scale, 1 / specs.scale);
		var boxRadius = 4;
		var innerRadius = boxRadius / 2;

		ctx.beginPath();
		ctx.moveTo(-boxRadius, -boxRadius);
		ctx.lineTo(boxRadius, -boxRadius);
		ctx.lineTo(boxRadius, boxRadius);
		ctx.lineTo(-boxRadius, boxRadius);
		ctx.closePath();
		if (hovered) {
			ctx.fillStyle = '#885110';
		} else {
			ctx.fillStyle = 'white';
		}
		ctx.fill();

		ctx.beginPath();
		ctx.moveTo(-boxRadius, -innerRadius);
		ctx.lineTo(-boxRadius, -boxRadius);
		ctx.lineTo(-innerRadius, -boxRadius);
		ctx.moveTo(innerRadius, -boxRadius);
		ctx.lineTo(boxRadius, -boxRadius);
		ctx.lineTo(boxRadius, -innerRadius);
		ctx.moveTo(boxRadius, innerRadius);
		ctx.lineTo(boxRadius, boxRadius);
		ctx.lineTo(innerRadius, boxRadius);
		ctx.moveTo(-innerRadius, boxRadius);
		ctx.lineTo(-boxRadius, boxRadius);
		ctx.lineTo(-boxRadius, innerRadius);
		ctx.moveTo(-boxRadius, -innerRadius);

		ctx.strokeStyle = 'rgba(0,0,0,.2)';
		ctx.lineWidth = 5;
		ctx.stroke();
		ctx.strokeStyle = 'blue';
		ctx.lineWidth = 1;
		ctx.stroke();
		ctx.restore();
	};

})(ChemDoodle.math, ChemDoodle.structures.d2, Math);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(extensions, math, structures, d2, m) {
	'use strict';
	d2.Bracket = function(p1, p2) {
		this.p1 = p1 ? p1 : new structures.Point();
		this.p2 = p2 ? p2 : new structures.Point();
	};
	var _ = d2.Bracket.prototype = new d2._Shape();
	_.charge = 0;
	_.mult = 0;
	_.repeat = 0;
	_.draw = function(ctx, specs) {
		var minX = m.min(this.p1.x, this.p2.x);
		var maxX = m.max(this.p1.x, this.p2.x);
		var minY = m.min(this.p1.y, this.p2.y);
		var maxY = m.max(this.p1.y, this.p2.y);
		var h = maxY - minY;
		var lip = h / 10;
		ctx.beginPath();
		ctx.moveTo(minX + lip, minY);
		ctx.lineTo(minX, minY);
		ctx.lineTo(minX, maxY);
		ctx.lineTo(minX + lip, maxY);
		ctx.moveTo(maxX - lip, maxY);
		ctx.lineTo(maxX, maxY);
		ctx.lineTo(maxX, minY);
		ctx.lineTo(maxX - lip, minY);
		if (this.isLassoed) {
			var grd = ctx.createLinearGradient(this.p1.x, this.p1.y, this.p2.x, this.p2.y);
			grd.addColorStop(0, 'rgba(212, 99, 0, 0)');
			grd.addColorStop(0.5, 'rgba(212, 99, 0, 0.8)');
			grd.addColorStop(1, 'rgba(212, 99, 0, 0)');
			ctx.lineWidth = specs.shapes_lineWidth + 5;
			ctx.strokeStyle = grd;
			ctx.lineJoin = 'miter';
			ctx.lineCap = 'square';
			ctx.stroke();
		}
		ctx.strokeStyle = specs.shapes_color;
		ctx.lineWidth = specs.shapes_lineWidth;
		ctx.lineJoin = 'miter';
		ctx.lineCap = 'butt';
		ctx.stroke();
		if (this.charge !== 0) {
			ctx.fillStyle = specs.text_color;
			ctx.textAlign = 'left';
			ctx.textBaseline = 'alphabetic';
			ctx.font = extensions.getFontString(specs.text_font_size, specs.text_font_families);
			var s = this.charge.toFixed(0);
			if (s === '1') {
				s = '+';
			} else if (s === '-1') {
				s = '\u2013';
			} else if (extensions.stringStartsWith(s, '-')) {
				s = s.substring(1) + '\u2013';
			} else {
				s += '+';
			}
			ctx.fillText(s, maxX + 5, minY + 5);
		}
		if (this.mult !== 0) {
			ctx.fillStyle = specs.text_color;
			ctx.textAlign = 'right';
			ctx.textBaseline = 'middle';
			ctx.font = extensions.getFontString(specs.text_font_size, specs.text_font_families);
			ctx.fillText(this.mult.toFixed(0), minX - 5, minY + h / 2);
		}
		if (this.repeat !== 0) {
			ctx.fillStyle = specs.text_color;
			ctx.textAlign = 'left';
			ctx.textBaseline = 'top';
			ctx.font = extensions.getFontString(specs.text_font_size, specs.text_font_families);
			var s = this.repeat.toFixed(0);
			ctx.fillText(s, maxX + 5, maxY - 5);
		}
	};
	_.getPoints = function() {
		return [ this.p1, this.p2 ];
	};
	_.isOver = function(p, barrier) {
		return math.isBetween(p.x, this.p1.x, this.p2.x) && math.isBetween(p.y, this.p1.y, this.p2.y);
	};

})(ChemDoodle.extensions, ChemDoodle.math, ChemDoodle.structures, ChemDoodle.structures.d2, Math);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(math, structures, d2, m) {
	'use strict';
	d2.Line = function(p1, p2) {
		this.p1 = p1 ? p1 : new structures.Point();
		this.p2 = p2 ? p2 : new structures.Point();
	};
	d2.Line.ARROW_SYNTHETIC = 'synthetic';
	d2.Line.ARROW_RETROSYNTHETIC = 'retrosynthetic';
	d2.Line.ARROW_RESONANCE = 'resonance';
	d2.Line.ARROW_EQUILIBRIUM = 'equilibrium';
	var _ = d2.Line.prototype = new d2._Shape();
	_.arrowType = undefined;
	_.topText = undefined;
	_.bottomText = undefined;
	_.draw = function(ctx, specs) {
		if (this.isLassoed) {
			var grd = ctx.createLinearGradient(this.p1.x, this.p1.y, this.p2.x, this.p2.y);
			grd.addColorStop(0, 'rgba(212, 99, 0, 0)');
			grd.addColorStop(0.5, 'rgba(212, 99, 0, 0.8)');
			grd.addColorStop(1, 'rgba(212, 99, 0, 0)');
			var useDist = 2.5;
			var perpendicular = this.p1.angle(this.p2) + m.PI / 2;
			var mcosp = m.cos(perpendicular);
			var msinp = m.sin(perpendicular);
			var cx1 = this.p1.x - mcosp * useDist;
			var cy1 = this.p1.y + msinp * useDist;
			var cx2 = this.p1.x + mcosp * useDist;
			var cy2 = this.p1.y - msinp * useDist;
			var cx3 = this.p2.x + mcosp * useDist;
			var cy3 = this.p2.y - msinp * useDist;
			var cx4 = this.p2.x - mcosp * useDist;
			var cy4 = this.p2.y + msinp * useDist;
			ctx.fillStyle = grd;
			ctx.beginPath();
			ctx.moveTo(cx1, cy1);
			ctx.lineTo(cx2, cy2);
			ctx.lineTo(cx3, cy3);
			ctx.lineTo(cx4, cy4);
			ctx.closePath();
			ctx.fill();
		}
		ctx.strokeStyle = specs.shapes_color;
		ctx.fillStyle = specs.shapes_color;
		ctx.lineWidth = specs.shapes_lineWidth;
		ctx.lineJoin = 'miter';
		ctx.lineCap = 'butt';
		if (this.p1.x !== this.p2.x || this.p1.y !== this.p2.y) {
			// only render if the points are different, otherwise this will
			// cause fill overflows
			if (this.arrowType === d2.Line.ARROW_RETROSYNTHETIC) {
				var r2 = m.sqrt(2) * 2;
				var useDist = specs.shapes_arrowLength_2D / r2;
				var angle = this.p1.angle(this.p2);
				var perpendicular = angle + m.PI / 2;
				var retract = specs.shapes_arrowLength_2D / r2;
				var mcosa = m.cos(angle);
				var msina = m.sin(angle);
				var mcosp = m.cos(perpendicular);
				var msinp = m.sin(perpendicular);
				var cx1 = this.p1.x - mcosp * useDist;
				var cy1 = this.p1.y + msinp * useDist;
				var cx2 = this.p1.x + mcosp * useDist;
				var cy2 = this.p1.y - msinp * useDist;
				var cx3 = this.p2.x + mcosp * useDist - mcosa * retract;
				var cy3 = this.p2.y - msinp * useDist + msina * retract;
				var cx4 = this.p2.x - mcosp * useDist - mcosa * retract;
				var cy4 = this.p2.y + msinp * useDist + msina * retract;
				var ax1 = this.p2.x + mcosp * useDist * 2 - mcosa * retract * 2;
				var ay1 = this.p2.y - msinp * useDist * 2 + msina * retract * 2;
				var ax2 = this.p2.x - mcosp * useDist * 2 - mcosa * retract * 2;
				var ay2 = this.p2.y + msinp * useDist * 2 + msina * retract * 2;
				ctx.beginPath();
				ctx.moveTo(cx2, cy2);
				ctx.lineTo(cx3, cy3);
				ctx.moveTo(ax1, ay1);
				ctx.lineTo(this.p2.x, this.p2.y);
				ctx.lineTo(ax2, ay2);
				ctx.moveTo(cx4, cy4);
				ctx.lineTo(cx1, cy1);
				ctx.stroke();
			} else if (this.arrowType === d2.Line.ARROW_EQUILIBRIUM) {
				var r2 = m.sqrt(2) * 2;
				var useDist = specs.shapes_arrowLength_2D / r2 / 2;
				var angle = this.p1.angle(this.p2);
				var perpendicular = angle + m.PI / 2;
				var retract = specs.shapes_arrowLength_2D * 2 / m.sqrt(3);
				var mcosa = m.cos(angle);
				var msina = m.sin(angle);
				var mcosp = m.cos(perpendicular);
				var msinp = m.sin(perpendicular);
				var cx1 = this.p1.x - mcosp * useDist;
				var cy1 = this.p1.y + msinp * useDist;
				var cx2 = this.p1.x + mcosp * useDist;
				var cy2 = this.p1.y - msinp * useDist;
				var cx3 = this.p2.x + mcosp * useDist;
				var cy3 = this.p2.y - msinp * useDist;
				var cx4 = this.p2.x - mcosp * useDist;
				var cy4 = this.p2.y + msinp * useDist;
				ctx.beginPath();
				ctx.moveTo(cx2, cy2);
				ctx.lineTo(cx3, cy3);
				ctx.moveTo(cx4, cy4);
				ctx.lineTo(cx1, cy1);
				ctx.stroke();
				// right arrow
				var rx1 = cx3 - mcosa * retract * .8;
				var ry1 = cy3 + msina * retract * .8;
				var ax1 = cx3 + mcosp * specs.shapes_arrowLength_2D / 3 - mcosa * retract;
				var ay1 = cy3 - msinp * specs.shapes_arrowLength_2D / 3 + msina * retract;
				ctx.beginPath();
				ctx.moveTo(cx3, cy3);
				ctx.lineTo(ax1, ay1);
				ctx.lineTo(rx1, ry1);
				ctx.closePath();
				ctx.fill();
				ctx.stroke();
				// left arrow
				rx1 = cx1 + mcosa * retract * .8;
				ry1 = cy1 - msina * retract * .8;
				ax1 = cx1 - mcosp * specs.shapes_arrowLength_2D / 3 + mcosa * retract;
				ay1 = cy1 + msinp * specs.shapes_arrowLength_2D / 3 - msina * retract;
				ctx.beginPath();
				ctx.moveTo(cx1, cy1);
				ctx.lineTo(ax1, ay1);
				ctx.lineTo(rx1, ry1);
				ctx.closePath();
				ctx.fill();
				ctx.stroke();
			} else if (this.arrowType === d2.Line.ARROW_SYNTHETIC) {
				var angle = this.p1.angle(this.p2);
				var perpendicular = angle + m.PI / 2;
				var retract = specs.shapes_arrowLength_2D * 2 / m.sqrt(3);
				var mcosa = m.cos(angle);
				var msina = m.sin(angle);
				var mcosp = m.cos(perpendicular);
				var msinp = m.sin(perpendicular);
				ctx.beginPath();
				ctx.moveTo(this.p1.x, this.p1.y);
				ctx.lineTo(this.p2.x - mcosa * retract / 2, this.p2.y + msina * retract / 2);
				ctx.stroke();
				var rx1 = this.p2.x - mcosa * retract * .8;
				var ry1 = this.p2.y + msina * retract * .8;
				var ax1 = this.p2.x + mcosp * specs.shapes_arrowLength_2D / 3 - mcosa * retract;
				var ay1 = this.p2.y - msinp * specs.shapes_arrowLength_2D / 3 + msina * retract;
				var ax2 = this.p2.x - mcosp * specs.shapes_arrowLength_2D / 3 - mcosa * retract;
				var ay2 = this.p2.y + msinp * specs.shapes_arrowLength_2D / 3 + msina * retract;
				ctx.beginPath();
				ctx.moveTo(this.p2.x, this.p2.y);
				ctx.lineTo(ax2, ay2);
				ctx.lineTo(rx1, ry1);
				ctx.lineTo(ax1, ay1);
				ctx.closePath();
				ctx.fill();
				ctx.stroke();
			} else if (this.arrowType === d2.Line.ARROW_RESONANCE) {
				var angle = this.p1.angle(this.p2);
				var perpendicular = angle + m.PI / 2;
				var retract = specs.shapes_arrowLength_2D * 2 / m.sqrt(3);
				var mcosa = m.cos(angle);
				var msina = m.sin(angle);
				var mcosp = m.cos(perpendicular);
				var msinp = m.sin(perpendicular);
				ctx.beginPath();
				ctx.moveTo(this.p1.x + mcosa * retract / 2, this.p1.y - msina * retract / 2);
				ctx.lineTo(this.p2.x - mcosa * retract / 2, this.p2.y + msina * retract / 2);
				ctx.stroke();
				// right arrow
				var rx1 = this.p2.x - mcosa * retract * .8;
				var ry1 = this.p2.y + msina * retract * .8;
				var ax1 = this.p2.x + mcosp * specs.shapes_arrowLength_2D / 3 - mcosa * retract;
				var ay1 = this.p2.y - msinp * specs.shapes_arrowLength_2D / 3 + msina * retract;
				var ax2 = this.p2.x - mcosp * specs.shapes_arrowLength_2D / 3 - mcosa * retract;
				var ay2 = this.p2.y + msinp * specs.shapes_arrowLength_2D / 3 + msina * retract;
				ctx.beginPath();
				ctx.moveTo(this.p2.x, this.p2.y);
				ctx.lineTo(ax2, ay2);
				ctx.lineTo(rx1, ry1);
				ctx.lineTo(ax1, ay1);
				ctx.closePath();
				ctx.fill();
				ctx.stroke();
				// left arrow
				rx1 = this.p1.x + mcosa * retract * .8;
				ry1 = this.p1.y - msina * retract * .8;
				ax1 = this.p1.x - mcosp * specs.shapes_arrowLength_2D / 3 + mcosa * retract;
				ay1 = this.p1.y + msinp * specs.shapes_arrowLength_2D / 3 - msina * retract;
				ax2 = this.p1.x + mcosp * specs.shapes_arrowLength_2D / 3 + mcosa * retract;
				ay2 = this.p1.y - msinp * specs.shapes_arrowLength_2D / 3 - msina * retract;
				ctx.beginPath();
				ctx.moveTo(this.p1.x, this.p1.y);
				ctx.lineTo(ax2, ay2);
				ctx.lineTo(rx1, ry1);
				ctx.lineTo(ax1, ay1);
				ctx.closePath();
				ctx.fill();
				ctx.stroke();
			} else {
				ctx.beginPath();
				ctx.moveTo(this.p1.x, this.p1.y);
				ctx.lineTo(this.p2.x, this.p2.y);
				ctx.stroke();
			}
			if(this.topText){
				ctx.textAlign = 'center';
				ctx.textBaseline = 'bottom';
				ctx.fillText(this.topText, (this.p1.x+this.p2.x)/2, this.p1.y-5);
			}
			if(this.bottomText){
				ctx.textAlign = 'center';
				ctx.textBaseline = 'top';
				ctx.fillText(this.bottomText, (this.p1.x+this.p2.x)/2, this.p1.y+5);
			}
		}
	};
	_.getPoints = function() {
		return [ this.p1, this.p2 ];
	};
	_.isOver = function(p, barrier) {
		var dist = math.distanceFromPointToLineInclusive(p, this.p1, this.p2);
		return dist !== -1 && dist < barrier;
	};

})(ChemDoodle.math, ChemDoodle.structures, ChemDoodle.structures.d2, Math);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(math, jsb, structures, d2, m) {
	'use strict';
	var getPossibleAngles = function(o) {
		var as = [];
		if (o instanceof structures.Atom) {
			if (o.bondNumber === 0) {
				as.push(m.PI);
			} else if (o.angles) {
				if (o.angles.length === 1) {
					as.push(o.angles[0] + m.PI);
				} else {
					for ( var i = 1, ii = o.angles.length; i < ii; i++) {
						as.push(o.angles[i - 1] + (o.angles[i] - o.angles[i - 1]) / 2);
					}
					var firstIncreased = o.angles[0] + m.PI * 2;
					var last = o.angles[o.angles.length - 1];
					as.push(last + (firstIncreased - last) / 2);
				}
				if (o.largestAngle > m.PI) {
					// always use angle of least interfearence if it is greater
					// than 120
					as = [ o.angleOfLeastInterference ];
				}
				if (o.bonds) {
					// point up towards a carbonyl
					for ( var i = 0, ii = o.bonds.length; i < ii; i++) {
						var b = o.bonds[i];
						if (b.bondOrder === 2) {
							var n = b.getNeighbor(o);
							if (n.label === 'O') {
								as = [ n.angle(o) ];
								break;
							}
						}
					}
				}
			}
		} else {
			var angle = o.a1.angle(o.a2);
			as.push(angle + m.PI / 2);
			as.push(angle + 3 * m.PI / 2);
		}
		for ( var i = 0, ii = as.length; i < ii; i++) {
			while (as[i] > m.PI * 2) {
				as[i] -= m.PI * 2;
			}
			while (as[i] < 0) {
				as[i] += m.PI * 2;
			}
		}
		return as;
	};
	var getPullBack = function(o, specs) {
		var pullback = 3;
		if (o instanceof structures.Atom) {
			if (o.isLabelVisible(specs)) {
				pullback = 8;
			}
			if (o.charge !== 0 || o.numRadical !== 0 || o.numLonePair !== 0) {
				pullback = 13;
			}
		} else if (o instanceof structures.Point) {
			// this is the midpoint of a bond forming pusher
			pullback = 0;
		} else {
			if (o.bondOrder > 1) {
				pullback = 5;
			}
		}
		return pullback;
	};
	var drawPusher = function(ctx, specs, o1, o2, p1, c1, c2, p2, numElectron, caches) {
		var angle1 = c1.angle(p1);
		var angle2 = c2.angle(p2);
		var mcosa = m.cos(angle1);
		var msina = m.sin(angle1);
		// pull back from start
		var pullBack = getPullBack(o1, specs);
		p1.x -= mcosa * pullBack;
		p1.y += msina * pullBack;
		// arrow
		var perpendicular = angle2 + m.PI / 2;
		var retract = specs.shapes_arrowLength_2D * 2 / m.sqrt(3);
		var mcosa = m.cos(angle2);
		var msina = m.sin(angle2);
		var mcosp = m.cos(perpendicular);
		var msinp = m.sin(perpendicular);
		p2.x -= mcosa * 5;
		p2.y += msina * 5;
		var nap = new structures.Point(p2.x, p2.y);
		// pull back from end
		pullBack = getPullBack(o2, specs) / 3;
		nap.x -= mcosa * pullBack;
		nap.y += msina * pullBack;
		p2.x -= mcosa * (retract * 0.8 + pullBack);
		p2.y += msina * (retract * 0.8 + pullBack);
		var rx1 = nap.x - mcosa * retract * 0.8;
		var ry1 = nap.y + msina * retract * 0.8;
		var a1 = new structures.Point(nap.x + mcosp * specs.shapes_arrowLength_2D / 3 - mcosa * retract, nap.y - msinp * specs.shapes_arrowLength_2D / 3 + msina * retract);
		var a2 = new structures.Point(nap.x - mcosp * specs.shapes_arrowLength_2D / 3 - mcosa * retract, nap.y + msinp * specs.shapes_arrowLength_2D / 3 + msina * retract);
		var include1 = true, include2 = true;
		if (numElectron === 1) {
			if (a1.distance(c1) > a2.distance(c1)) {
				include2 = false;
			} else {
				include1 = false;
			}
		}
		ctx.beginPath();
		ctx.moveTo(nap.x, nap.y);
		if (include2) {
			ctx.lineTo(a2.x, a2.y);
		}
		ctx.lineTo(rx1, ry1);
		if (include1) {
			ctx.lineTo(a1.x, a1.y);
		}
		ctx.closePath();
		ctx.fill();
		ctx.stroke();
		// bezier
		ctx.beginPath();
		ctx.moveTo(p1.x, p1.y);
		ctx.bezierCurveTo(c1.x, c1.y, c2.x, c2.y, p2.x, p2.y);
		ctx.stroke();
		caches.push([ p1, c1, c2, p2 ]);
	};

	d2.Pusher = function(o1, o2, numElectron) {
		this.o1 = o1;
		this.o2 = o2;
		this.numElectron = numElectron ? numElectron : 1;
	};
	var _ = d2.Pusher.prototype = new d2._Shape();
	_.drawDecorations = function(ctx, specs) {
		if (this.isHover) {
			var p1 = this.o1 instanceof structures.Atom ? new structures.Point(this.o1.x, this.o1.y) : this.o1.getCenter();
			var p2 = this.o2 instanceof structures.Atom ? new structures.Point(this.o2.x, this.o2.y) : this.o2.getCenter();
			var ps = [ p1, p2 ];
			for ( var i = 0, ii = ps.length; i < ii; i++) {
				var p = ps[i];
				this.drawAnchor(ctx, specs, p, p === this.hoverPoint);
			}
		}
	};
	_.draw = function(ctx, specs) {
		if (this.o1 && this.o2) {
			ctx.strokeStyle = specs.shapes_color;
			ctx.fillStyle = specs.shapes_color;
			ctx.lineWidth = specs.shapes_lineWidth;
			ctx.lineJoin = 'miter';
			ctx.lineCap = 'butt';
			var p1 = this.o1 instanceof structures.Atom ? new structures.Point(this.o1.x, this.o1.y) : this.o1.getCenter();
			var p2 = this.o2 instanceof structures.Atom ? new structures.Point(this.o2.x, this.o2.y) : this.o2.getCenter();
			var controlDist = 35;
			var as1 = getPossibleAngles(this.o1);
			var as2 = getPossibleAngles(this.o2);
			var c1, c2;
			var minDif = Infinity;
			for ( var i = 0, ii = as1.length; i < ii; i++) {
				for ( var j = 0, jj = as2.length; j < jj; j++) {
					var c1c = new structures.Point(p1.x + controlDist * m.cos(as1[i]), p1.y - controlDist * m.sin(as1[i]));
					var c2c = new structures.Point(p2.x + controlDist * m.cos(as2[j]), p2.y - controlDist * m.sin(as2[j]));
					var dif = c1c.distance(c2c);
					if (dif < minDif) {
						minDif = dif;
						c1 = c1c;
						c2 = c2c;
					}
				}
			}
			this.caches = [];
			if (this.numElectron === -1) {
				var dist = p1.distance(p2)/2;
				var angle = p1.angle(p2);
				var perp = angle+m.PI/2;
				var mcosa = m.cos(angle);
				var msina = m.sin(angle);
				var m1 = new structures.Point(p1.x+(dist-1)*mcosa, p1.y-(dist-1)*msina);
				var cm1 = new structures.Point(m1.x+m.cos(perp+m.PI/6)*controlDist, m1.y - m.sin(perp+m.PI/6)*controlDist);
				var m2 = new structures.Point(p1.x+(dist+1)*mcosa, p1.y-(dist+1)*msina);
				var cm2 = new structures.Point(m2.x+m.cos(perp-m.PI/6)*controlDist, m2.y - m.sin(perp-m.PI/6)*controlDist);
				drawPusher(ctx, specs, this.o1, m1, p1, c1, cm1, m1, 1, this.caches);
				drawPusher(ctx, specs, this.o2, m2, p2, c2, cm2, m2, 1, this.caches);
			} else {
				if (math.intersectLines(p1.x, p1.y, c1.x, c1.y, p2.x, p2.y, c2.x, c2.y)) {
					var tmp = c1;
					c1 = c2;
					c2 = tmp;
				}
				// try to clean up problems, like loops
				var angle1 = c1.angle(p1);
				var angle2 = c2.angle(p2);
				var angleDif = (m.max(angle1, angle2) - m.min(angle1, angle2));
				if (m.abs(angleDif - m.PI) < .001 && this.o1.molCenter === this.o2.molCenter) {
					// in the case where the control tangents are parallel
					angle1 += m.PI / 2;
					angle2 -= m.PI / 2;
					c1.x = p1.x + controlDist * m.cos(angle1 + m.PI);
					c1.y = p1.y - controlDist * m.sin(angle1 + m.PI);
					c2.x = p2.x + controlDist * m.cos(angle2 + m.PI);
					c2.y = p2.y - controlDist * m.sin(angle2 + m.PI);
				}
				drawPusher(ctx, specs, this.o1, this.o2, p1, c1, c2, p2, this.numElectron, this.caches);
			}
		}
	};
	_.getPoints = function() {
		return [];
	};
	_.isOver = function(p, barrier) {
		for ( var i = 0, ii = this.caches.length; i < ii; i++) {
			var r = jsb.distanceFromCurve(p, this.caches[i]);
			if (r.distance < barrier) {
				return true;
			}
		}
		return false;
	};

})(ChemDoodle.math, ChemDoodle.math.jsBezier, ChemDoodle.structures, ChemDoodle.structures.d2, Math);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(d3, m) {
	'use strict';
	d3._Mesh = function() {
	};
	var _ = d3._Mesh.prototype;
	_.storeData = function(positionData, normalData, indexData) {
		this.positionData = positionData;
		this.normalData = normalData;
		this.indexData = indexData;
	};
	_.setupBuffers = function(gl) {
		this.vertexPositionBuffer = gl.createBuffer();
		gl.bindBuffer(gl.ARRAY_BUFFER, this.vertexPositionBuffer);
		gl.bufferData(gl.ARRAY_BUFFER, new Float32Array(this.positionData), gl.STATIC_DRAW);
		this.vertexPositionBuffer.itemSize = 3;
		this.vertexPositionBuffer.numItems = this.positionData.length / 3;

		this.vertexNormalBuffer = gl.createBuffer();
		gl.bindBuffer(gl.ARRAY_BUFFER, this.vertexNormalBuffer);
		gl.bufferData(gl.ARRAY_BUFFER, new Float32Array(this.normalData), gl.STATIC_DRAW);
		this.vertexNormalBuffer.itemSize = 3;
		this.vertexNormalBuffer.numItems = this.normalData.length / 3;

		if (this.indexData) {
			this.vertexIndexBuffer = gl.createBuffer();
			gl.bindBuffer(gl.ELEMENT_ARRAY_BUFFER, this.vertexIndexBuffer);
			gl.bufferData(gl.ELEMENT_ARRAY_BUFFER, new Uint16Array(this.indexData), gl.STATIC_DRAW);
			this.vertexIndexBuffer.itemSize = 1;
			this.vertexIndexBuffer.numItems = this.indexData.length;
		}

		if (this.partitions) {
			for ( var i = 0, ii = this.partitions.length; i < ii; i++) {
				var p = this.partitions[i];
				var buffers = this.generateBuffers(gl, p.positionData, p.normalData, p.indexData);
				p.vertexPositionBuffer = buffers[0];
				p.vertexNormalBuffer = buffers[1];
				p.vertexIndexBuffer = buffers[2];
			}
		}
	};
	_.generateBuffers = function(gl, positionData, normalData, indexData) {
		var vertexPositionBuffer = gl.createBuffer();
		gl.bindBuffer(gl.ARRAY_BUFFER, vertexPositionBuffer);
		gl.bufferData(gl.ARRAY_BUFFER, new Float32Array(positionData), gl.STATIC_DRAW);
		vertexPositionBuffer.itemSize = 3;
		vertexPositionBuffer.numItems = positionData.length / 3;

		var vertexNormalBuffer = gl.createBuffer();
		gl.bindBuffer(gl.ARRAY_BUFFER, vertexNormalBuffer);
		gl.bufferData(gl.ARRAY_BUFFER, new Float32Array(normalData), gl.STATIC_DRAW);
		vertexNormalBuffer.itemSize = 3;
		vertexNormalBuffer.numItems = normalData.length / 3;

		var vertexIndexBuffer;
		if (indexData) {
			vertexIndexBuffer = gl.createBuffer();
			gl.bindBuffer(gl.ELEMENT_ARRAY_BUFFER, vertexIndexBuffer);
			gl.bufferData(gl.ELEMENT_ARRAY_BUFFER, new Uint16Array(indexData), gl.STATIC_DRAW);
			vertexIndexBuffer.itemSize = 1;
			vertexIndexBuffer.numItems = indexData.length;
		}

		return [ vertexPositionBuffer, vertexNormalBuffer, vertexIndexBuffer ];
	};
	_.bindBuffers = function(gl) {
		if (!this.vertexPositionBuffer) {
			this.setupBuffers(gl);
		}
		// positions
		gl.bindBuffer(gl.ARRAY_BUFFER, this.vertexPositionBuffer);
		gl.vertexAttribPointer(gl.shader.vertexPositionAttribute, this.vertexPositionBuffer.itemSize, gl.FLOAT, false, 0, 0);
		// normals
		gl.bindBuffer(gl.ARRAY_BUFFER, this.vertexNormalBuffer);
		gl.vertexAttribPointer(gl.shader.vertexNormalAttribute, this.vertexNormalBuffer.itemSize, gl.FLOAT, false, 0, 0);
		if (this.vertexIndexBuffer) {
			// indexes
			gl.bindBuffer(gl.ELEMENT_ARRAY_BUFFER, this.vertexIndexBuffer);
		}
	};

})(ChemDoodle.structures.d3, Math);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(d3) {
	'use strict';
	d3._Measurement = function() {
	};
	var _ = d3._Measurement.prototype = new d3._Mesh();
	_.render = function(gl, specs) {
		gl.setMatrixUniforms(gl.modelViewMatrix);
		// setting the vertex position buffer to undefined resets the buffers, so this shape can be dynamically updated with the molecule
		if(specs.measurement_update_3D){
			this.vertexPositionBuffer = undefined;
			this.text = undefined;
		}
		if(!this.vertexPositionBuffer){
			this.calculateData(specs);
		}
		this.bindBuffers(gl);
		// colors
		gl.material.setDiffuseColor(specs.shapes_color);
		gl.lineWidth(specs.shapes_lineWidth);
		// render
		gl.drawElements(gl.LINES, this.vertexIndexBuffer.numItems, gl.UNSIGNED_SHORT, 0);
		
		if(specs.measurement_displayText_3D) {
			// render the text
			if(!this.text){
				this.text = this.getText(specs);
			}
			
			var vertexData = {
				position : [],
				texCoord : [],
				translation : []
			};

			gl.textImage.pushVertexData(this.text.value, this.text.pos, 1, vertexData);
			gl.textMesh.storeData(gl, vertexData.position, vertexData.texCoord, vertexData.translation);
			
			// enable blend and depth mask set to false
			gl.enable(gl.BLEND);
			gl.depthMask(false);

			// enable vertex for draw text
			gl.enableVertexAttribArray(gl.shader.vertexTexCoordAttribute);

			gl.textImage.useTexture(gl);
			gl.textMesh.render(gl);

			// disable vertex for draw text
			gl.disableVertexAttribArray(gl.shader.vertexTexCoordAttribute);

			// disable blend and depth mask set to true
			gl.disable(gl.BLEND);
			gl.depthMask(true);
		}
		
	};

})(ChemDoodle.structures.d3);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(ELEMENT, extensions, d3, math, m, m4, v3) {
	'use strict';
	d3.Angle = function(a1, a2, a3) {
		this.a1 = a1;
		this.a2 = a2;
		this.a3 = a3;
	};
	var _ = d3.Angle.prototype = new d3._Measurement();
	_.calculateData = function(specs) {
		var positionData = [];
		var normalData = [];
		var indexData = [];
		var dist1 = this.a2.distance3D(this.a1);
		var dist2 = this.a2.distance3D(this.a3);
		this.distUse = m.min(dist1, dist2) / 2;
		// data for the angle
		this.vec1 = v3.normalize([ this.a1.x - this.a2.x, this.a1.y - this.a2.y, this.a1.z - this.a2.z ]);
		this.vec2 = v3.normalize([ this.a3.x - this.a2.x, this.a3.y - this.a2.y, this.a3.z - this.a2.z ]);
		this.angle = extensions.vec3AngleFrom(this.vec1, this.vec2);

		var axis = v3.normalize(v3.cross(this.vec1, this.vec2, []));
		var vec3 = v3.normalize(v3.cross(axis, this.vec1, []));

		var bands = specs.measurement_angleBands_3D;
		for ( var i = 0; i <= bands; ++i) {
			var theta = this.angle * i / bands;
			var vecCos = v3.scale(this.vec1, m.cos(theta), []);
			var vecSin = v3.scale(vec3, m.sin(theta), []);
			var norm = v3.scale(v3.normalize(v3.add(vecCos, vecSin, [])), this.distUse);

			positionData.push(this.a2.x + norm[0], this.a2.y + norm[1], this.a2.z + norm[2]);
			normalData.push(0, 0, 0);
			if (i < bands) {
				indexData.push(i, i + 1);
			}
		}

		this.storeData(positionData, normalData, indexData);
	};
	_.getText = function(specs) {
		var vecCenter = v3.scale(v3.normalize(v3.add(this.vec1, this.vec2, [])), this.distUse + 0.3);
		return {
			pos : [ this.a2.x + vecCenter[0], this.a2.y + vecCenter[1], this.a2.z + vecCenter[2] ],
			value : [ math.angleBounds(this.angle, true).toFixed(2), ' \u00b0' ].join('')
		};
	};

})(ChemDoodle.ELEMENT, ChemDoodle.extensions, ChemDoodle.structures.d3, ChemDoodle.math, Math, ChemDoodle.lib.mat4, ChemDoodle.lib.vec3);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(d3, m) {
	'use strict';
	d3.Arrow = function(radius, longitudeBands) {
		var positionData = [];
		var normalData = [];

		for ( var longNumber = 0; longNumber <= longitudeBands; longNumber++) {
			var theta = longNumber * 2 * m.PI / longitudeBands;
			var sinTheta = m.sin(theta);
			var cosTheta = m.cos(theta);

			var x = cosTheta;
			var y = sinTheta;

			normalData.push(
			// base cylinder
			0, 0, -1, 0, 0, -1,
			// cylinder
			x, y, 0, x, y, 0,
			// base cone
			0, 0, -1, 0, 0, -1,
			// cone
			x, y, 1, x, y, 1);

			positionData.push(
			// base cylinder
			0, 0, 0, radius * x, radius * y, 0,
			// cylinder
			radius * x, radius * y, 0, radius * x, radius * y, 2,
			// base cone
			radius * x, radius * y, 2, radius * x * 2, radius * y * 2, 2,
			// cone
			radius * x * 2, radius * y * 2, 2, 0, 0, 3);
		}

		var indexData = [];
		for ( var i = 0; i < longitudeBands; i++) {
			var offset = i * 8;
			for ( var j = 0, jj = 7; j < jj; j++) {
				var first = j + offset;
				var second = first + 1;
				var third = first + jj + 2;
				var forth = third - 1;
				indexData.push(first, third, second, third, first, forth);
			}
		}

		this.storeData(positionData, normalData, indexData);
	};
	d3.Arrow.prototype = new d3._Mesh();

})(ChemDoodle.structures.d3, Math);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(d3, m) {
	'use strict';
	d3.Box = function(width, height, depth) {
		width /= 2;
		depth /= 2;

		var positionData = [];
		var normalData = [];

		// top
		positionData.push(width, height, -depth);
		positionData.push(width, height, -depth);
		positionData.push(-width, height, -depth);
		positionData.push(width, height, depth);
		positionData.push(-width, height, depth);
		positionData.push(-width, height, depth);
		for(var i = 6; i--; normalData.push(0 , 1, 0));

		// front
		positionData.push(-width, height, depth);
		positionData.push(-width, height, depth);
		positionData.push(-width, 0, depth);
		positionData.push(width, height, depth);
		positionData.push(width, 0, depth);
		positionData.push(width, 0, depth);
		for(var i = 6; i--; normalData.push(0 , 0, 1));

		// right
		positionData.push(width, height, depth);
		positionData.push(width, height, depth);
		positionData.push(width, 0, depth);
		positionData.push(width, height, -depth);
		positionData.push(width, 0, -depth);
		positionData.push(width, 0, -depth);
		for(var i = 6; i--; normalData.push(1 , 0, 0));

		// back
		positionData.push(width, height, -depth);
		positionData.push(width, height, -depth);
		positionData.push(width, 0, -depth);
		positionData.push(-width, height, -depth);
		positionData.push(-width, 0, -depth);
		positionData.push(-width, 0, -depth);
		for(var i = 6; i--; normalData.push(0 , 0, -1));

		// left
		positionData.push(-width, height, -depth);
		positionData.push(-width, height, -depth);
		positionData.push(-width, 0, -depth);
		positionData.push(-width, height, depth);
		positionData.push(-width, 0, depth);
		positionData.push(-width, 0, depth);
		for(var i = 6; i--; normalData.push(-1 , 0, 0));

		// bottom
		positionData.push(-width, 0, depth);
		positionData.push(-width, 0, depth);
		positionData.push(-width, 0, -depth);
		positionData.push(width, 0, depth);
		positionData.push(width, 0, -depth);
		positionData.push(width, 0, -depth);
		for(var i = 6; i--; normalData.push(0 , -1, 0));

		this.storeData(positionData, normalData);
	};
	d3.Box.prototype = new d3._Mesh();

})(ChemDoodle.structures.d3, Math);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(d3, m, m4) {
	'use strict';
	d3.LineArrow = function() {
		var d = 2.8;
		var w = 0.1;

		this.storeData([
				0, 0, -3, w, 0, -d,
				0, 0, -3, -w, 0, -d,

				0, 0, -3, 0, 0, 3,

				0, 0, 3, w, 0, d,
				0, 0, 3, -w, 0, d
			],
			[
				0, 0, 0, 0, 0, 0,
				0, 0, 0, 0, 0, 0,
				0, 0, 0, 0, 0, 0,
				0, 0, 0, 0, 0, 0,
				0, 0, 0, 0, 0, 0
			]);
	};
	d3.LineArrow.prototype = new d3._Mesh();
	
	d3.Compass = function(gl, specs) {

		// setup text X Y Z
		this.textImage = new d3.TextImage();
		this.textImage.init(gl);
		this.textImage.updateFont(gl, specs.text_font_size, specs.text_font_families, specs.text_font_bold, specs.text_font_italic, specs.text_font_stroke_3D);

		this.textMesh = new d3.TextMesh();
		this.textMesh.init(gl);

		var screenRatioHeight = specs.compass_size_3D / gl.canvas.clientHeight;

		var height = 3 / screenRatioHeight;
		var tanTheta = m.tan(specs.projectionPerspectiveVerticalFieldOfView_3D / 360 * m.PI);
		var depth = height / tanTheta;
		var near = m.max(depth - height, 0.1);
		var far = depth + height;

		var aspec = gl.canvas.clientWidth / gl.canvas.clientHeight;

		var fnProjection, z;

		if (specs.projectionPerspective_3D) {
			z = near;
			fnProjection = m4.frustum;
		} else {
			z = depth;
			fnProjection = m4.ortho;
		}

		var nearRatio = z / gl.canvas.clientHeight * 2 * tanTheta;
		var top = tanTheta * z;
		var bottom = -top;
		var left = aspec * bottom;
		var right = aspec * top;

		if(specs.compass_type_3D === 0) {
			var deltaX = -(gl.canvas.clientWidth - specs.compass_size_3D) / 2 + this.textImage.charHeight;
			var deltaY = -(gl.canvas.clientHeight - specs.compass_size_3D) / 2 + this.textImage.charHeight;

			var x = deltaX * nearRatio;
			var y = deltaY * nearRatio;

			left -= x;
			right -= x;
			bottom -= y;
			top -= y;
		}


		this.projectionMatrix = fnProjection(left, right, bottom, top, near, far);
		this.translationMatrix = m4.translate(m4.identity([]), [ 0, 0, -depth ]);

		// vertex data for X Y Z text label
		var vertexData = {
			position : [],
			texCoord : [],
			translation : []
		};

		// it need to auto calculated somehow
		var textPos = 3.5;

		this.textImage.pushVertexData('X', [ textPos, 0, 0 ], 0, vertexData);
		this.textImage.pushVertexData('Y', [ 0, textPos, 0 ], 0, vertexData);
		this.textImage.pushVertexData('Z', [ 0, 0, textPos ], 0, vertexData);

		this.textMesh.storeData(gl, vertexData.position, vertexData.texCoord, vertexData.translation);
	};

	var _ = d3.Compass.prototype;
	_.renderArrow = function(gl, type, color, mvMatrix) {
		gl.material.setDiffuseColor(color);
		gl.setMatrixUniforms(mvMatrix);
		if(type === 1) {
			gl.drawArrays(gl.LINES, 0, gl.lineArrowBuffer.vertexPositionBuffer.numItems);
		} else {
			gl.drawElements(gl.TRIANGLES, gl.arrowBuffer.vertexIndexBuffer.numItems, gl.UNSIGNED_SHORT, 0);
		}
	};
	_.render = function(gl, specs) {

		specs.compass_type_3D === 1? gl.lineArrowBuffer.bindBuffers(gl) : gl.arrowBuffer.bindBuffers(gl);

		gl.material.setTempColors(specs.bonds_materialAmbientColor_3D, undefined, specs.bonds_materialSpecularColor_3D, specs.bonds_materialShininess_3D);

		var modelMatrix = m4.multiply(this.translationMatrix, gl.rotationMatrix, []);
		var angle = m.PI / 2;

		// disable fogging for the compass
		gl.fogging.setMode(0);

		// x - axis
		this.renderArrow(gl, specs.compass_type_3D, specs.compass_axisXColor_3D, m4.rotateY(modelMatrix, angle, []));

		// y - axis
		this.renderArrow(gl, specs.compass_type_3D, specs.compass_axisYColor_3D, m4.rotateX(modelMatrix, -angle, []));

		// z - axis
		this.renderArrow(gl, specs.compass_type_3D, specs.compass_axisZColor_3D, modelMatrix);
	};
	_.renderAxis = function(gl) {
		var modelMatrix = m4.multiply(this.translationMatrix, gl.rotationMatrix, []);
		gl.setMatrixUniforms(modelMatrix);

		this.textImage.useTexture(gl);
		this.textMesh.render(gl);
	};

})(ChemDoodle.structures.d3, Math, ChemDoodle.lib.mat4);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(d3, m) {
	'use strict';
	d3.Cylinder = function(radius, height, bands, closed) {
		var positionData = [];
		var normalData = [];

		if (closed) {
			for (var i = 0; i <= bands; i++) {
				var theta = i % bands * 2 * m.PI / bands;
				var cosTheta = m.cos(theta);
				var sinTheta = m.sin(theta);

				normalData.push(0, -1, 0);
				positionData.push(0, 0, 0);
				normalData.push(0, -1, 0);
				positionData.push(radius * cosTheta, 0, radius * sinTheta);

			}

			for (var i = 0; i <= bands; i++) {
				var theta = i % bands * 2 * m.PI / bands;
				var cosTheta = m.cos(theta);
				var sinTheta = m.sin(theta);

				normalData.push(cosTheta, 0, sinTheta);
				positionData.push(radius * cosTheta, 0, radius * sinTheta);

				normalData.push(cosTheta, 0, sinTheta);
				positionData.push(radius * cosTheta, height, radius * sinTheta);
			}

			for (var i = 0; i <= bands; i++) {
				var theta = i % bands * 2 * m.PI / bands;
				var cosTheta = m.cos(theta);
				var sinTheta = m.sin(theta);

				normalData.push(0, 1, 0);
				positionData.push(radius * cosTheta, height, radius * sinTheta);

				normalData.push(0, 1, 0);
				positionData.push(0, height, 0);
			}
		} else {
			for (var i = 0; i < bands; i++) {
				var theta = i * 2 * m.PI / bands;
				var cosTheta = m.cos(theta);
				var sinTheta = m.sin(theta);
				normalData.push(cosTheta, 0, sinTheta);
				positionData.push(radius * cosTheta, 0, radius * sinTheta);
				normalData.push(cosTheta, 0, sinTheta);
				positionData.push(radius * cosTheta, height, radius * sinTheta);
			}
			normalData.push(1, 0, 0);
			positionData.push(radius, 0, 0);
			normalData.push(1, 0, 0);
			positionData.push(radius, height, 0);
		}

		this.storeData(positionData, normalData);
	};
	d3.Cylinder.prototype = new d3._Mesh();

})(ChemDoodle.structures.d3, Math);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(ELEMENT, d3, m, v3) {
	'use strict';
	d3.Distance = function(a1, a2, node, offset) {
		this.a1 = a1;
		this.a2 = a2;
		this.node = node;
		this.offset = offset ? offset : 0;
	};
	var _ = d3.Distance.prototype = new d3._Measurement();
	_.calculateData = function(specs) {
		var positionData = [ this.a1.x, this.a1.y, this.a1.z, this.a2.x, this.a2.y, this.a2.z ];
		if (this.node) {
			var r1 = specs.atoms_useVDWDiameters_3D ? ELEMENT[this.a1.label].vdWRadius * specs.atoms_vdwMultiplier_3D : specs.atoms_sphereDiameter_3D / 2;
			var r2 = specs.atoms_useVDWDiameters_3D ? ELEMENT[this.a2.label].vdWRadius * specs.atoms_vdwMultiplier_3D : specs.atoms_sphereDiameter_3D / 2;
			this.move = this.offset + m.max(r1, r2);
			this.displacement = [ (this.a1.x + this.a2.x) / 2 - this.node.x, (this.a1.y + this.a2.y) / 2 - this.node.y, (this.a1.z + this.a2.z) / 2 - this.node.z ];
			v3.normalize(this.displacement);
			var change = v3.scale(this.displacement, this.move, []);
			positionData[0] += change[0];
			positionData[1] += change[1];
			positionData[2] += change[2];
			positionData[3] += change[0];
			positionData[4] += change[1];
			positionData[5] += change[2];
		}
		var normalData = [ 0, 0, 0, 0, 0, 0 ];
		var indexData = [ 0, 1 ];
		this.storeData(positionData, normalData, indexData);
	};
	_.getText = function(specs) {
		var dist = this.a1.distance3D(this.a2);
		var center = [ (this.a1.x + this.a2.x) / 2, (this.a1.y + this.a2.y) / 2, (this.a1.z + this.a2.z) / 2 ];
		if (this.node) {
			var change = v3.scale(this.displacement, this.move+.1, []);
			center[0] += change[0];
			center[1] += change[1];
			center[2] += change[2];
		}
		return {
			pos : center,
			value : [ dist.toFixed(2), ' \u212b' ].join('')
		};
	};

})(ChemDoodle.ELEMENT, ChemDoodle.structures.d3, Math, ChemDoodle.lib.vec3);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(math, d3, v3) {
	'use strict';

	d3.Fog = function(gl) {
		this.gl = gl;
		var prefix = 'u_fog_';
		this.mUL = gl.getUniformLocation(gl.program, prefix + 'mode');
		this.cUL = gl.getUniformLocation(gl.program, prefix + 'color');
		this.sUL = gl.getUniformLocation(gl.program, prefix + 'start');
		this.eUL = gl.getUniformLocation(gl.program, prefix + 'end');
		this.dUL = gl.getUniformLocation(gl.program, prefix + 'density');
	};

	var _ = d3.Fog.prototype;
	_.setTempParameter = function(color, fogStart, fogEnd, density) {
		if (!this.cCache || this.cCache !== color) {
			this.cCache = color;
			var cs = math.getRGB(color, 1);
			this.gl.uniform3f(this.cUL, cs[0], cs[1], cs[2]);
		}
		if (!this.sCache || this.sCache !== fogStart) {
			this.sCache = fogStart;
			this.gl.uniform1f(this.sUL, fogStart);
		}
		if (!this.eCache || this.eCache !== fogEnd) {
			this.eCache = fogEnd;
			this.gl.uniform1f(this.eUL, fogEnd);
		}
		if (!this.dCache || this.dCache !== density) {
			this.dCache = density;
			this.gl.uniform1f(this.dUL, density);
		}
	};
	_.setMode = function(mode) {
		if (!this.mCache || this.mCache !== mode) {
			this.mCache = mode;
			this.gl.uniform1i(this.mUL, mode);
		}
	};
})(ChemDoodle.math, ChemDoodle.structures.d3, ChemDoodle.lib.vec3);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(ELEMENT, d3) {

	d3.Label = function() {
		this.textImage = new d3.TextImage();
	};
	var _ = d3.Label.prototype;
	_.init = function(gl, specs) {
		this.textImage.init(gl);
		this.textImage.updateFont(gl, specs.atoms_font_size_2D, specs.atoms_font_families_2D, specs.atoms_font_bold_2D, specs.atoms_font_italic_2D, specs.text_font_stroke_3D);
	};
	_.updateVerticesBuffer = function(gl, molecules, specs) {
		for ( var i = 0, ii = molecules.length; i < ii; i++) {
			var molecule = molecules[i];
			var moleculeLabel = molecule.labelMesh;
			var atoms = molecule.atoms;
			var textImage = this.textImage;
			var vertexData = {
				position : [],
				texCoord : [],
				translation : []
			};

			var isMacro = atoms.length > 0 && atoms[0].hetatm != undefined;

			for ( var j = 0, jj = atoms.length; j < jj; j++) {
				var atom = atoms[j];
				
				var atomLabel = atom.label;
				var zDepth = 0.05;

				// Sphere or Ball and Stick
				if (specs.atoms_useVDWDiameters_3D) {
					var add = ELEMENT[atomLabel].vdWRadius * specs.atoms_vdwMultiplier_3D;
					if (add === 0) {
						add = 1;
					}
					zDepth += add;
				}
				// if Stick or Wireframe
				else if (specs.atoms_sphereDiameter_3D) {
					zDepth += specs.atoms_sphereDiameter_3D / 2 * 1.5;
				}

				if (isMacro) {
					if (!atom.hetatm) {
						if (!specs.macro_displayAtoms) {
							continue;
						}
					} else if (atom.isWater) {
						if (!specs.macro_showWaters) {
							continue;
						}
					}
				}
				
				textImage.pushVertexData(atom.altLabel ? atom.altLabel : atom.label, [ atom.x, atom.y, atom.z ], zDepth, vertexData);

			}

			var chains = molecule.chains;

			if (chains && (specs.proteins_displayRibbon || specs.proteins_displayBackbone)) {

				for ( var j = 0, jj = chains.length; j < jj; j++) {
					var chain = chains[j];

					for ( var k = 0, kk = chain.length; k < kk; k++) {
						var residue = chain[k];

						if (residue.name) {
							var atom = residue.cp1;
							textImage.pushVertexData(residue.name, [ atom.x, atom.y, atom.z ], 2, vertexData);
						}
					}
				}

			}

			moleculeLabel.storeData(gl, vertexData.position, vertexData.texCoord, vertexData.translation, vertexData.zDepth);

		}
	};
	_.render = function(gl, specs, molecules) {
		// use projection for shader text.
		gl.setMatrixUniforms(gl.modelViewMatrix);

		this.textImage.useTexture(gl);
		for ( var i = 0, ii = molecules.length; i < ii; i++) {
			if (molecules[i].labelMesh) {
				molecules[i].labelMesh.render(gl);
			}
		}
	};

})(ChemDoodle.ELEMENT, ChemDoodle.structures.d3);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(d3, m) {
	'use strict';
	d3.Sphere = function(radius, latitudeBands, longitudeBands) {
		var positionData = [];
		var normalData = [];
		for ( var latNumber = 0; latNumber <= latitudeBands; latNumber++) {
			var theta = latNumber * m.PI / latitudeBands;
			var sinTheta = m.sin(theta);
			var cosTheta = m.cos(theta);

			for ( var longNumber = 0; longNumber <= longitudeBands; longNumber++) {
				var phi = longNumber * 2 * m.PI / longitudeBands;
				var sinPhi = m.sin(phi);
				var cosPhi = m.cos(phi);

				var x = cosPhi * sinTheta;
				var y = cosTheta;
				var z = sinPhi * sinTheta;

				normalData.push(x, y, z);
				positionData.push(radius * x, radius * y, radius * z);
			}
		}

		var indexData = [];
		longitudeBands += 1;
		for ( var latNumber = 0; latNumber < latitudeBands; latNumber++) {
			for ( var longNumber = 0; longNumber < longitudeBands; longNumber++) {
				var first = (latNumber * longitudeBands) + (longNumber % longitudeBands);
				var second = first + longitudeBands;
				indexData.push(first, first + 1, second);
				if (longNumber < longitudeBands - 1) {
					indexData.push(second, first + 1, second + 1);
				}
			}
		}

		this.storeData(positionData, normalData, indexData);
	};
	d3.Sphere.prototype = new d3._Mesh();

})(ChemDoodle.structures.d3, Math);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(RESIDUE, d3, m, v3) {
	'use strict';
	var loadPartition = function(gl, p) {
		// positions
		gl.bindBuffer(gl.ARRAY_BUFFER, p.vertexPositionBuffer);
		gl.vertexAttribPointer(gl.shader.vertexPositionAttribute, p.vertexPositionBuffer.itemSize, gl.FLOAT, false, 0, 0);
		// normals
		gl.bindBuffer(gl.ARRAY_BUFFER, p.vertexNormalBuffer);
		gl.vertexAttribPointer(gl.shader.vertexNormalAttribute, p.vertexNormalBuffer.itemSize, gl.FLOAT, false, 0, 0);
		// indexes
		gl.bindBuffer(gl.ELEMENT_ARRAY_BUFFER, p.vertexIndexBuffer);
	};

	function SubRibbon(entire, name, indexes, pi) {
		this.entire = entire;
		this.name = name;
		this.indexes = indexes;
		this.pi = pi;
	}
	var _2 = SubRibbon.prototype;
	// NOTE: To use rainbow coloring for chains, it needs coloring each residue with total residue count
	// and current index residue in chain parameters.
	_2.getColor = function(specs) {
		if (specs.macro_colorByChain) {
			return this.entire.chainColor;
		} else if (this.name) {
			return this.getResidueColor(RESIDUE[this.name] ? this.name : '*', specs);
		} else if (this.helix) {
			return this.entire.front ? specs.proteins_ribbonCartoonHelixPrimaryColor : specs.proteins_ribbonCartoonHelixSecondaryColor;
		} else if (this.sheet) {
			return specs.proteins_ribbonCartoonSheetColor;
		} else {
			return this.entire.front ? specs.proteins_primaryColor : specs.proteins_secondaryColor;
		}
	};
	_2.getResidueColor = function(name, specs) {
		var r = RESIDUE[name];
		if (specs.proteins_residueColor === 'shapely') {
			return r.shapelyColor;
		} else if (specs.proteins_residueColor === 'amino') {
			return r.aminoColor;
		} else if (specs.proteins_residueColor === 'polarity') {
			if (r.polar) {
				return '#C10000';
			} else {
				return '#FFFFFF';
			}
		} else if (specs.proteins_residueColor === 'acidity') {
			if(r.acidity === 1){
				return '#0000FF';
			}else if(r.acidity === -1){
				return '#FF0000';
			}else if (r.polar) {
				return '#FFFFFF';
			} else {
				return '#773300';
			}
		}
		return '#FFFFFF';
	};
	_2.render = function(gl, specs, noColor) {
		if (this.entire.partitions && this.pi !== this.entire.partitions.lastRender) {
			loadPartition(gl, this.entire.partitions[this.pi]);
			this.entire.partitions.lastRender = this.pi;
		}
		if (!this.vertexIndexBuffer) {
			this.vertexIndexBuffer = gl.createBuffer();
			gl.bindBuffer(gl.ELEMENT_ARRAY_BUFFER, this.vertexIndexBuffer);
			gl.bufferData(gl.ELEMENT_ARRAY_BUFFER, new Uint16Array(this.indexes), gl.STATIC_DRAW);
			this.vertexIndexBuffer.itemSize = 1;
			this.vertexIndexBuffer.numItems = this.indexes.length;
		}
		// indexes
		gl.bindBuffer(gl.ELEMENT_ARRAY_BUFFER, this.vertexIndexBuffer);
		// colors
		if (!noColor && specs.proteins_residueColor !== 'rainbow') {
			gl.material.setDiffuseColor(this.getColor(specs));
		}
		// render
		gl.drawElements(gl.TRIANGLES, this.vertexIndexBuffer.numItems, gl.UNSIGNED_SHORT, 0);
	};

	d3.Ribbon = function(chain, offset, cartoon) {
		// ribbon meshes build front to back, not side to side, so keep this in
		// mind
		var lineSegmentNum = chain[0].lineSegments.length;
		var lineSegmentLength = chain[0].lineSegments[0].length;
		this.partitions = [];
		this.partitions.lastRender = 0;
		var currentPartition;
		this.front = offset > 0;
		// calculate vertex and normal points
		for ( var i = 0, ii = chain.length; i < ii; i++) {
			if (!currentPartition || currentPartition.positionData.length > 65000) {
				if (this.partitions.length > 0) {
					i--;
				}
				currentPartition = {
					count : 0,
					positionData : [],
					normalData : []
				};
				this.partitions.push(currentPartition);
			}
			var residue = chain[i];
			currentPartition.count++;
			for ( var j = 0; j < lineSegmentNum; j++) {
				var lineSegment = cartoon ? residue.lineSegmentsCartoon[j] : residue.lineSegments[j];
				var doSide1 = j === 0;
				var doSide2 = false;
				for ( var k = 0; k < lineSegmentLength; k++) {
					var a = lineSegment[k];
					// normals
					var abovei = i;
					var abovek = k + 1;
					if (i === chain.length - 1 && k === lineSegmentLength - 1) {
						abovek--;
					} else if (k === lineSegmentLength - 1) {
						abovei++;
						abovek = 0;
					}
					var above = cartoon ? chain[abovei].lineSegmentsCartoon[j][abovek] : chain[abovei].lineSegments[j][abovek];
					var negate = false;
					var nextj = j + 1;
					if (j === lineSegmentNum - 1) {
						nextj -= 2;
						negate = true;
					}
					var side = cartoon ? residue.lineSegmentsCartoon[nextj][k] : residue.lineSegments[nextj][k];
					var toAbove = [ above.x - a.x, above.y - a.y, above.z - a.z ];
					var toSide = [ side.x - a.x, side.y - a.y, side.z - a.z ];
					var normal = v3.cross(toAbove, toSide, []);
					// positions
					if (k === 0) {
						// tip
						v3.normalize(toAbove);
						v3.scale(toAbove, -1);
						currentPartition.normalData.push(toAbove[0], toAbove[1], toAbove[2]);
						currentPartition.positionData.push(a.x, a.y, a.z);
					}
					if (doSide1 || doSide2) {
						// sides
						v3.normalize(toSide);
						v3.scale(toSide, -1);
						currentPartition.normalData.push(toSide[0], toSide[1], toSide[2]);
						currentPartition.positionData.push(a.x, a.y, a.z);
						if (doSide1 && k === lineSegmentLength - 1) {
							doSide1 = false;
							k = -1;
						}
					} else {
						// center strips
						v3.normalize(normal);
						if (negate && !this.front || !negate && this.front) {
							v3.scale(normal, -1);
						}
						currentPartition.normalData.push(normal[0], normal[1], normal[2]);
						v3.scale(normal, m.abs(offset));
						currentPartition.positionData.push(a.x + normal[0], a.y + normal[1], a.z + normal[2]);
						if (j === lineSegmentNum - 1 && k === lineSegmentLength - 1) {
							doSide2 = true;
							k = -1;
						}
					}
					if (k === -1 || k === lineSegmentLength - 1) {
						// end
						v3.normalize(toAbove);
						currentPartition.normalData.push(toAbove[0], toAbove[1], toAbove[2]);
						currentPartition.positionData.push(a.x, a.y, a.z);
					}
				}
			}
		}
		
		// build mesh connectivity
		// add 2 to lineSegmentNum and lineSegmentLength to account for sides
		// and ends
		lineSegmentNum += 2;
		lineSegmentLength += 2;
		this.segments = [];
		this.partitionSegments = [];
		for ( var n = 0, nn = this.partitions.length; n < nn; n++) {
			var currentPartition = this.partitions[n];
			var partitionSegmentIndexData = [];
			for ( var i = 0, ii = currentPartition.count - 1; i < ii; i++) {
				var chainIndex = i;
				for ( var j = 0; j < n; j++) {
					chainIndex += this.partitions[j].count - 1;
				}
				var c = chain[chainIndex];
				var residueIndexStart = i * lineSegmentNum * lineSegmentLength;
				var individualIndexData = [];
				for ( var j = 0, jj = lineSegmentNum - 1; j < jj; j++) {
					var segmentIndexStart = residueIndexStart + j * lineSegmentLength;
					for ( var k = 0; k < lineSegmentLength; k++) {
						var nextRes = 1;
						if (i === ii) {
							nextRes = 0;
						} else if (k === lineSegmentLength - 1) {
							nextRes = lineSegmentNum * lineSegmentLength - k;
						}
						var add = [ segmentIndexStart + k, segmentIndexStart + lineSegmentLength + k, segmentIndexStart + lineSegmentLength + k + nextRes, segmentIndexStart + k, segmentIndexStart + k + nextRes, segmentIndexStart + lineSegmentLength + k + nextRes ];
						if (k !== lineSegmentLength - 1) {
							if (this.front) {
								individualIndexData.push(add[0], add[1], add[2], add[3], add[5], add[4]);
							} else {
								individualIndexData.push(add[0], add[2], add[1], add[3], add[4], add[5]);
							}
						}
						if (k === lineSegmentLength - 2 && i < ii) {
							// jump the gap, the other mesh points will be
							// covered,
							// so no need to explicitly skip them
							var jump = lineSegmentNum * lineSegmentLength - k;
							add[2] += jump;
							add[4] += jump;
							add[5] += jump;
						}
						if (this.front) {
							partitionSegmentIndexData.push(add[0], add[1], add[2], add[3], add[5], add[4]);
						} else {
							partitionSegmentIndexData.push(add[0], add[2], add[1], add[3], add[4], add[5]);
						}
					}
				}

				if (cartoon && c.split) {
					var sr = new SubRibbon(this, undefined, partitionSegmentIndexData, n);
					sr.helix = c.helix;
					sr.sheet = c.sheet;
					this.partitionSegments.push(sr);
					partitionSegmentIndexData = [];
				}

				this.segments.push(new SubRibbon(this, c.name, individualIndexData, n));
			}

			var sr = new SubRibbon(this, undefined, partitionSegmentIndexData, n);
			sr.helix = c.helix;
			sr.sheet = c.sheet;
			this.partitionSegments.push(sr);
		}
		this.storeData(this.partitions[0].positionData, this.partitions[0].normalData);
		if (this.partitions.length === 1) {
			// clear partitions to reduce overhead
			this.partitions = undefined;
		}
	};
	var _ = d3.Ribbon.prototype = new d3._Mesh();
	_.render = function(gl, specs) {
		this.bindBuffers(gl);
		// colors
		var color = specs.macro_colorByChain ? this.chainColor : undefined;
		if (!color) {
			color = this.front ? specs.proteins_primaryColor : specs.proteins_secondaryColor;
		}
		gl.material.setDiffuseColor(color);
			
		for ( var i = 0, ii = this.partitionSegments.length; i < ii; i++) {
			this.partitionSegments[i].render(gl, specs, !specs.proteins_ribbonCartoonize);
		}
	};

})(ChemDoodle.RESIDUE, ChemDoodle.structures.d3, Math, ChemDoodle.lib.vec3);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(math, d3, v3) {
	'use strict';
	d3.Light = function(diffuseColor, specularColor, direction) {
		this.diffuseRGB = math.getRGB(diffuseColor, 1);
		this.specularRGB = math.getRGB(specularColor, 1);
		this.direction = direction;
	};
	var _ = d3.Light.prototype;
	_.lightScene = function(gl) {
		var prefix = 'u_light_';
		gl.uniform3f(gl.getUniformLocation(gl.program, prefix + 'diffuse_color'), this.diffuseRGB[0], this.diffuseRGB[1], this.diffuseRGB[2]);
		gl.uniform3f(gl.getUniformLocation(gl.program, prefix + 'specular_color'), this.specularRGB[0], this.specularRGB[1], this.specularRGB[2]);

		var lightingDirection = v3.create(this.direction);
		v3.normalize(lightingDirection);
		v3.negate(lightingDirection);
		gl.uniform3f(gl.getUniformLocation(gl.program, prefix + 'direction'), lightingDirection[0], lightingDirection[1], lightingDirection[2]);

		// compute the half vector
		var eyeVector = [ 0, 0, 0 ];
		var halfVector = [ eyeVector[0] + lightingDirection[0], eyeVector[1] + lightingDirection[1], eyeVector[2] + lightingDirection[2] ];
		var length = v3.length(halfVector);
		if (length === 0)
			halfVector = [ 0, 0, 1 ];
		else {
			v3.scale(halfVector, 1 / length);
		}
		gl.uniform3f(gl.getUniformLocation(gl.program, prefix + 'half_vector'), halfVector[0], halfVector[1], halfVector[2]);
	};

})(ChemDoodle.math, ChemDoodle.structures.d3, ChemDoodle.lib.vec3);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(d3) {
	'use strict';
	d3.Line = function() {
		this.storeData([ 0, 0, 0, 0, 1, 0 ], [ 0, 0, 0, 0, 0, 0 ]);
	};
	d3.Line.prototype = new d3._Mesh();

})(ChemDoodle.structures.d3);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(math, d3) {
	'use strict';
	d3.Material = function(gl) {
		this.gl = gl;
		var prefix = 'u_material_';
		this.aUL = gl.getUniformLocation(gl.program, prefix + 'ambient_color');
		this.dUL = gl.getUniformLocation(gl.program, prefix + 'diffuse_color');
		this.sUL = gl.getUniformLocation(gl.program, prefix + 'specular_color');
		this.snUL = gl.getUniformLocation(gl.program, prefix + 'shininess');
		this.alUL = gl.getUniformLocation(gl.program, prefix + 'alpha');
	};
	var _ = d3.Material.prototype;
	_.setTempColors = function(ambientColor, diffuseColor, specularColor, shininess) {
		if (!this.aCache || this.aCache !== ambientColor) {
			this.aCache = ambientColor;
			var cs = math.getRGB(ambientColor, 1);
			this.gl.uniform3f(this.aUL, cs[0], cs[1], cs[2]);
		}
		if (diffuseColor && (!this.dCache || this.dCache !== diffuseColor)) {
			this.dCache = diffuseColor;
			var cs = math.getRGB(diffuseColor, 1);
			this.gl.uniform3f(this.dUL, cs[0], cs[1], cs[2]);
		}
		if (!this.sCache || this.sCache !== specularColor) {
			this.sCache = specularColor;
			var cs = math.getRGB(specularColor, 1);
			this.gl.uniform3f(this.sUL, cs[0], cs[1], cs[2]);
		}
		if (!this.snCache || this.snCache !== shininess) {
			this.snCache = shininess;
			this.gl.uniform1f(this.snUL, shininess);
		}
		this.alCache = 1;
		this.gl.uniform1f(this.alUL, 1);
	};
	_.setDiffuseColor = function(diffuseColor) {
		if (!this.dCache || this.dCache !== diffuseColor) {
			this.dCache = diffuseColor;
			var cs = math.getRGB(diffuseColor, 1);
			this.gl.uniform3f(this.dUL, cs[0], cs[1], cs[2]);
		}
	};
	_.setAlpha = function(alpha) {
		if (!this.alCache || this.alCache !== alpha) {
			this.alCache = alpha;
			this.gl.uniform1f(this.alUL, alpha);
		}
	};

})(ChemDoodle.math, ChemDoodle.structures.d3);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(structures, d3, ELEMENT, m) {
	'use strict';
	d3.MolecularSurface = function(molecule, latitudeBands, longitudeBands, probeRadius, atomRadius) {
		var positionData = [];
		var normalData = [];
		var indexData = [];

		// determine a generic set of normals to define a single atom surface
		var genericSurface = [];
		for ( var latNumber = 0; latNumber <= latitudeBands; latNumber++) {
			var theta = latNumber * m.PI / latitudeBands;
			var sinTheta = m.sin(theta);
			var cosTheta = m.cos(theta);
			for ( var longNumber = 0; longNumber <= longitudeBands; longNumber++) {
				var phi = longNumber * 2 * m.PI / longitudeBands;
				genericSurface.push(m.cos(phi) * sinTheta, cosTheta, m.sin(phi) * sinTheta);
			}
		}

		// add surfaces for each atom, to be post processed
		var atomSurfaces = [];
		for ( var i = 0, ii = molecule.atoms.length; i < ii; i++) {
			var atomSurface = [];
			var atom = molecule.atoms[i];

			// cache the atoms within distance, so that we don't need to waste
			// calculations later
			var radius = ELEMENT[atom.label][atomRadius] + probeRadius;
			var checks = [];
			for ( var j = 0, jj = molecule.atoms.length; j < jj; j++) {
				if (j !== i) {
					var check = molecule.atoms[j];
					check.index = j;
					if (atom.distance3D(check) < radius + ELEMENT[check.label][atomRadius] + probeRadius) {
						checks.push(check);
					}
				}
			}

			for ( var j = 0, jj = genericSurface.length; j < jj; j += 3) {
				var p = new structures.Atom('C', atom.x + radius * genericSurface[j], atom.y + radius * genericSurface[j + 1], atom.z + radius * genericSurface[j + 2]);
				for ( var k = 0, kk = checks.length; k < kk; k++) {
					var check = checks[k];
					if (p.distance3D(check) < ELEMENT[check.label][atomRadius] + probeRadius) {
						p.contained = true;
						break;
					}
				}
				atomSurface.push(p);
			}

			atomSurfaces.push(atomSurface);
		}

		// set up the mesh vectors
		var genericIndexes = [];
		longitudeBands++;
		for ( var latNumber = 0; latNumber < latitudeBands; latNumber++) {
			for ( var longNumber = 0; longNumber < longitudeBands; longNumber++) {
				var first = (latNumber * longitudeBands) + (longNumber % longitudeBands);
				var second = first + longitudeBands;
				genericIndexes.push(first);
				genericIndexes.push(second);
				genericIndexes.push(first + 1);
				if (longNumber < longitudeBands - 1) {
					genericIndexes.push(second);
					genericIndexes.push(second + 1);
					genericIndexes.push(first + 1);
				}
			}
		}

		var indexCounter = 0;
		// connect discrete sphere parts
		for ( var i = 0, ii = atomSurfaces.length; i < ii; i++) {
			var atomSurface = atomSurfaces[i];
			for ( var j = 0, jj = atomSurface.length; j < jj; j++) {
				var p = atomSurface[j];
				if (!p.contained) {
					p.index = indexCounter;
					indexCounter++;
					positionData.push(p.x, p.y, p.z);
					normalData.push(genericSurface[j * 3], genericSurface[j * 3 + 1], genericSurface[j * 3 + 2]);
				}
			}
			for ( var j = 0, jj = genericIndexes.length; j < jj; j += 3) {
				var first = atomSurface[genericIndexes[j]];
				var second = atomSurface[genericIndexes[j + 1]];
				var third = atomSurface[genericIndexes[j + 2]];
				if (!first.contained && !second.contained && !third.contained) {
					indexData.push(first.index, second.index, third.index);
				}
			}
		}
		// sow together spheres
		function findClosestPoint(pNotContained, checks, exclude1, exclude2) {
			var index = pNotContained.index;
			if (pNotContained.contained) {
				index = -1;
				var dist = Infinity;
				for ( var k = 0, kk = checks.length; k < kk; k++) {
					var check = checks[k];
					for ( var l = 0, ll = check.length; l < ll; l++) {
						var p = check[l];
						if (!p.contained && p.index !== exclude1 && p.index !== exclude2) {
							var distCheck = p.distance3D(pNotContained);
							if (distCheck < dist) {
								index = p.index;
								dist = distCheck;
							}
						}
					}
				}
			}
			return index;
		}
		var seams = [];
		for ( var i = 0, ii = atomSurfaces.length; i < ii; i++) {
			var atomSurface = atomSurfaces[i];
			for ( var j = 0, jj = genericIndexes.length; j < jj; j += 3) {
				var first = atomSurface[genericIndexes[j]];
				var second = atomSurface[genericIndexes[j + 1]];
				var third = atomSurface[genericIndexes[j + 2]];
				var checks = [];
				for ( var k = 0, kk = atomSurfaces.length; k < kk; k++) {
					if (k !== i) {
						checks.push(atomSurfaces[k]);
					}
				}
				if (!(first.contained && second.contained && third.contained) && (first.contained || second.contained || third.contained)) {
					var fi = findClosestPoint(first, checks, -1, -1);
					var si = findClosestPoint(second, checks, fi, -1);
					var ti = findClosestPoint(third, checks, fi, si);
					if (fi !== -1 && si !== -1 && ti !== -1) {
						var already = false;
						for ( var k = 0, kk = seams.length; k < kk; k += 3) {
							var already1 = seams[k];
							var already2 = seams[k + 1];
							var already3 = seams[k + 2];
							var f1 = fi === already1 || fi === already2 || fi === already3;
							var f2 = si === already1 || si === already2 || si === already3;
							var f3 = ti === already1 || ti === already2 || ti === already3;
							if (f1 && f2 && f3) {
								already = true;
								break;
							}
						}
						if (!already) {
							seams.push(fi, si, ti);
						}
					}
				}
			}
		}
		indexData = indexData.concat(seams);

		this.storeData(positionData, normalData, indexData);
	};
	d3.MolecularSurface.prototype = new d3._Mesh();

})(ChemDoodle.structures, ChemDoodle.structures.d3, ChemDoodle.ELEMENT, Math);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(d3, math, document) {
	'use strict';
	d3.Picker = function() {
	};
	var _ = d3.Picker.prototype;

	_.init = function(gl) {
		// setup for picking system
		this.framebuffer = gl.createFramebuffer();

		// set pick texture
		var texture2D = gl.createTexture();
		var renderbuffer = gl.createRenderbuffer();

		gl.bindTexture(gl.TEXTURE_2D, texture2D);
		gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MAG_FILTER, gl.NEAREST);
		gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MIN_FILTER, gl.NEAREST);
		gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_S, gl.CLAMP_TO_EDGE);
		gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_T, gl.CLAMP_TO_EDGE);

		gl.bindRenderbuffer(gl.RENDERBUFFER, renderbuffer);

		// set framebuffer and bind the texture and renderbuffer
		gl.bindFramebuffer(gl.FRAMEBUFFER, this.framebuffer);
		gl.framebufferTexture2D(gl.FRAMEBUFFER, gl.COLOR_ATTACHMENT0, gl.TEXTURE_2D, texture2D, 0);
		gl.framebufferRenderbuffer(gl.FRAMEBUFFER, gl.DEPTH_ATTACHMENT, gl.RENDERBUFFER, renderbuffer);

		gl.bindTexture(gl.TEXTURE_2D, null);
		gl.bindRenderbuffer(gl.RENDERBUFFER, null);
		gl.bindFramebuffer(gl.FRAMEBUFFER, null);
	};

	_.setDimension = function(gl, width, height) {
		gl.bindFramebuffer(gl.FRAMEBUFFER, this.framebuffer);

		// get binded depth attachment renderbuffer
		var renderbuffer = gl.getFramebufferAttachmentParameter(gl.FRAMEBUFFER, gl.DEPTH_ATTACHMENT, gl.FRAMEBUFFER_ATTACHMENT_OBJECT_NAME);
		if (gl.isRenderbuffer(renderbuffer)) {
			// set renderbuffer dimension
			gl.bindRenderbuffer(gl.RENDERBUFFER, renderbuffer);
			gl.renderbufferStorage(gl.RENDERBUFFER, gl.DEPTH_COMPONENT16, width, height);
			gl.bindRenderbuffer(gl.RENDERBUFFER, null);
		}

		// get binded color attachment texture 2d
		var texture2D = gl.getFramebufferAttachmentParameter(gl.FRAMEBUFFER, gl.COLOR_ATTACHMENT0, gl.FRAMEBUFFER_ATTACHMENT_OBJECT_NAME);
		if (gl.isTexture(texture2D)) {
			// set texture dimension
			gl.bindTexture(gl.TEXTURE_2D, texture2D);
			gl.texImage2D(gl.TEXTURE_2D, 0, gl.RGBA, width, height, 0, gl.RGBA, gl.UNSIGNED_BYTE, null);
			gl.bindTexture(gl.TEXTURE_2D, null);
		}

		gl.bindFramebuffer(gl.FRAMEBUFFER, null);
	};

	_.pick = function(gl, molecules, specs, x, y, includeAtoms, includeBonds) {
		var object = undefined;

		// current clear color
		var cs = gl.getParameter(gl.COLOR_CLEAR_VALUE);

		gl.bindFramebuffer(gl.FRAMEBUFFER, this.framebuffer);

		gl.clearColor(1.0, 1.0, 1.0, 0.0);
		gl.clear(gl.COLOR_BUFFER_BIT | gl.DEPTH_BUFFER_BIT);

		// disable foging effect
		gl.fogging.setMode(0);

		// not need the normal for diffuse light, we need flat color
		gl.disableVertexAttribArray(gl.shader.vertexNormalAttribute);

		var objects = [];

		gl.material.setAlpha(255);
		for ( var i = 0, ii = molecules.length; i < ii; i++) {
			molecules[i].renderPickFrame(gl, specs, objects, includeAtoms, includeBonds);
		}

		// flush as this is seen in documentation
		gl.flush();

		var rgba = new Uint8Array(4);
		gl.readPixels(x - 2, y + 2, 1, 1, gl.RGBA, gl.UNSIGNED_BYTE, rgba);

		var idxMolecule = rgba[3];
		if (idxMolecule > 0) {
			var idxAtom = rgba[2] | (rgba[1] << 8) | (rgba[0] << 16);
			object = objects[idxAtom];
		}

		// release a little bit memory
		objects = undefined;

		// reenable the normal attribute
		gl.enableVertexAttribArray(gl.shader.vertexNormalAttribute);

		// enable fogging
		gl.fogging.setMode(specs.fog_mode_3D);

		gl.bindFramebuffer(gl.FRAMEBUFFER, null);

		// set back the clear color
		gl.clearColor(cs[0], cs[1], cs[2], cs[3]);

		return object;
	};

})(ChemDoodle.structures.d3, ChemDoodle.math, document);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(d3, m) {
	'use strict';

	/**
	 * @constructor d3.Pill
	 * @inherit {d3._Mesh}
	 * @param {number}
	 *            height Height of pill including the rounded cap
	 * @param {number}
	 *            radius Radius of pill
	 * @param {number}
	 *            latitudeBands Total bands of latitute division on one pill's
	 *            cap
	 * @param {number}
	 *            longitudeBands Total bands of longitude division on one pill's
	 *            cap and cylinder part
	 */
	d3.Pill = function(radius, height, latitudeBands, longitudeBands) {

		var capHeightScale = 1;
		var capDiameter = 2 * radius;

		height -= capDiameter;

		if (height < 0) {
			capHeightScale = 0;
			height += capDiameter;
		} else if (height < capDiameter) {
			capHeightScale = height / capDiameter;
			height = capDiameter;
		}

		// update latitude and logintude band for two caps.
		// latitudeBands *= 2;
		// longitudeBands *= 2;

		var positionData = [];
		var normalData = [];
		for ( var latNumber = 0; latNumber <= latitudeBands; latNumber++) {
			var theta = latNumber * m.PI / latitudeBands;
			var sinTheta = m.sin(theta);
			var cosTheta = m.cos(theta) * capHeightScale;

			for ( var longNumber = 0; longNumber <= longitudeBands; longNumber++) {
				var phi = longNumber * 2 * m.PI / longitudeBands;
				var sinPhi = m.sin(phi);
				var cosPhi = m.cos(phi);

				var x = cosPhi * sinTheta;
				var y = cosTheta;
				var z = sinPhi * sinTheta;

				normalData.push(x, y, z);
				positionData.push(radius * x, radius * y + (latNumber < latitudeBands / 2 ? height : 0), radius * z);
			}
		}

		var indexData = [];
		longitudeBands += 1;
		for ( var latNumber = 0; latNumber < latitudeBands; latNumber++) {
			for ( var longNumber = 0; longNumber < longitudeBands; longNumber++) {
				var first = (latNumber * longitudeBands) + (longNumber % longitudeBands);
				var second = first + longitudeBands;
				indexData.push(first, first + 1, second);
				if (longNumber < longitudeBands - 1) {
					indexData.push(second, first + 1, second + 1);
				}
			}
		}

		this.storeData(positionData, normalData, indexData);
	};
	d3.Pill.prototype = new d3._Mesh();

})(ChemDoodle.structures.d3, Math);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(extensions, RESIDUE, structures, d3, m, m4, v3, math) {
	'use strict';
	
	function createDummyResidue(x, y, z) {
		var dummyRes = new structures.Residue(-1);
		dummyRes.cp1 = dummyRes.cp2 = new structures.Atom('', x, y, z);
		return dummyRes;
	}
	
	function Pipe(a1, a2) {
		this.a1 = a1;
		this.a2 = a2;
	};
	var _ = Pipe.prototype;
	_.render = function(gl, specs) {
		var p1 = this.a1;
		var p2 = this.a2;
		var height = 1.001 * p1.distance3D(p2);
		var radiusScale = specs.proteins_cylinderHelixDiameter / 2;
		var scaleVector = [ radiusScale, height, radiusScale ];
		var transform = m4.translate(gl.modelViewMatrix, [ p1.x, p1.y, p1.z ], []);
		var y = [ 0, 1, 0 ];
		var ang = 0;
		var axis;
		if (p1.x === p2.x && p1.z === p2.z) {
			axis = [ 0, 0, 1 ];
			if (p2.y < p1.y) {
				ang = m.PI;
			}
		} else {
			var a2b = [ p2.x - p1.x, p2.y - p1.y, p2.z - p1.z ];
			ang = extensions.vec3AngleFrom(y, a2b);
			axis = v3.cross(y, a2b, []);
		}

		if (ang !== 0) {
			m4.rotate(transform, ang, axis);
		}
		m4.scale(transform, scaleVector);
		gl.setMatrixUniforms(transform);
		gl.drawArrays(gl.TRIANGLE_STRIP, 0, gl.cylinderClosedBuffer.vertexPositionBuffer.numItems);
	};

	function Plank(a1, a2, vx) {
		this.a1 = a1;
		this.a2 = a2;
		this.vx = vx;
	};
	var _ = Plank.prototype;
	_.render = function(gl, specs) {
		if (this.specs) {
			specs = this.specs;
		}
		// this is the elongation vector for the cylinder
		var height = 1.001 * this.a1.distance3D(this.a2);

		var diry = [ this.a2.x - this.a1.x, this.a2.y - this.a1.y, this.a2.z - this.a1.z ];
		var dirz = v3.cross(diry, this.vx, []);
		var dirx = v3.cross(dirz, diry, []);

		v3.normalize(dirx);
		v3.normalize(diry);
		v3.normalize(dirz);

		var modelMatrix = [
			dirx[0], dirx[1], dirx[2], 0,
			diry[0], diry[1], diry[2], 0,
			dirz[0], dirz[1], dirz[2], 0,
			this.a1.x, this.a1.y, this.a1.z, 1
		];

		var scaleVector = [ specs.proteins_plankSheetWidth, height, specs.proteins_tubeThickness];
		var transform = m4.multiply(gl.modelViewMatrix, modelMatrix, []);
		m4.scale(transform, scaleVector);
		gl.setMatrixUniforms(transform);
		gl.drawArrays(gl.TRIANGLE_STRIP, 0, gl.boxBuffer.vertexPositionBuffer.numItems);
	};

	d3.PipePlank = function(rs, specs) {
		this.tubes = [];
		this.helixCylinders = [];
		this.sheetPlanks = [];
		this.chainColor = rs.chainColor;

		var chainNoSS = [];
		var noSSResidues = [];
		var helixResidues = [];
		var sheetResidues = [];

		// the first residue just a dummy residue.
		// so at beginning, the secondary structure of second residue must be check
		if(rs.length > 1) {
			var r0 = rs[0];
			var r1 = rs[1];
			if (r1.helix) {
				helixResidues.push(r0);
			} else if(r1.sheet) {
				sheetResidues.push(r0);
			} else {
				noSSResidues.push(r0);
			}
		}

		// iterate residues
		for ( var i = 1, ii = rs.length - 1; i <= ii; i++) {
			var residue = rs[i];
			if(residue.helix) {
				helixResidues.push(residue);

				if(residue.arrow) {
					var startPoint = v3.create();
					var endPoint = v3.create();

					if (helixResidues.length == 2) {
						// PDB like 2PEC have helix which is just have 2 residues in it.
						startPoint = [helixResidues[0].cp1.x, helixResidues[0].cp1.y, helixResidues[0].cp1.z];
						endPoint = [helixResidues[1].cp1.x, helixResidues[1].cp1.y, helixResidues[1].cp1.z];
					} else {
						
						// To get helix axis, we need at least 4 residues.
						// if residues lenght is 3, then one residue need to be added.
						// The added residue is residue before helix.
						if(helixResidues.length == 3) {
							helixResidues.unshift(rs[m.max(i - 3, 0)]);
						}

						var Ps = [];
						var Vs = [];

						for (var h = 1, hh = helixResidues.length - 1; h < hh; h++) {
							var cai = [helixResidues[h].cp1.x, helixResidues[h].cp1.y, helixResidues[h].cp1.z];
							var A = [helixResidues[h-1].cp1.x, helixResidues[h-1].cp1.y, helixResidues[h-1].cp1.z];
							var B = [helixResidues[h+1].cp1.x, helixResidues[h+1].cp1.y, helixResidues[h+1].cp1.z];

							v3.subtract(A, cai);
							v3.subtract(B, cai);

							var Al = v3.scale(A, v3.length(B), []);
							var Bl = v3.scale(B, v3.length(A), []);

							var V = v3.normalize(v3.add(Al, Bl, []));

							Ps.push(cai);
							Vs.push(V);
						}

						var axes = [];
						for (var h = 0, hh = Ps.length - 1; h < hh; h++) {
							var P1 = Ps[h];
							var V1 = Vs[h];
							var P2 = Ps[h+1];
							var V2 = Vs[h+1];

							var H = v3.normalize(v3.cross(V1, V2, []));

							var P2subP1 = v3.subtract(P2, P1, []);
							var d = v3.dot(P2subP1, H);

							var dH = v3.scale(H, d, []);

							var dHl = v3.length(dH);
							var P2subP1l = v3.length(P2subP1);

							var r = -(dHl * dHl - P2subP1l * P2subP1l) / (2 * v3.dot(v3.subtract(P1, P2, []), V2));

							var H1 = v3.add(P1, v3.scale(V1, r, []), []);
							var H2 = v3.add(P2, v3.scale(V2, r, []), []);

							axes.push([H1, H2]);
						}

						var firstPoint = axes[0][0];
						var secondPoint = axes[0][1];
						var secondToFirst = v3.subtract(firstPoint, secondPoint, []);
						v3.add(firstPoint, secondToFirst, startPoint);

						var firstPoint = axes[axes.length-1][1];
						var secondPoint = axes[axes.length-1][0];
						var secondToFirst = v3.subtract(firstPoint, secondPoint, []);
						v3.add(firstPoint, secondToFirst, endPoint);

					}

					var startAtom = new structures.Atom('', startPoint[0], startPoint[1], startPoint[2]);
					var endAtom = new structures.Atom('', endPoint[0], endPoint[1], endPoint[2]);

					this.helixCylinders.push(new Pipe(startAtom, endAtom));

					helixResidues = [];

					// get vector direction from Pipe end to start
					var helixDir = v3.subtract(startPoint, endPoint, []);
					v3.normalize(helixDir);
					v3.scale(helixDir, .5);

					if (noSSResidues.length > 0) {

						var additionCp = v3.add(startPoint, helixDir, []);
						var prevResCp = noSSResidues[noSSResidues.length - 1].cp1;
						var helixDirToPrevRes = v3.subtract([prevResCp.x, prevResCp.y, prevResCp.z], additionCp, []);
						v3.normalize(helixDirToPrevRes);
						v3.scale(helixDirToPrevRes, .5);
						v3.add(additionCp, helixDirToPrevRes);
						var dummyRes = new structures.Residue(-1);
						dummyRes.cp1 = dummyRes.cp2 = new structures.Atom('', additionCp[0], additionCp[1], additionCp[2]);
						noSSResidues.push(dummyRes);

						// force the non secondary structure spline to end on helix start point.
						var dummyRes = createDummyResidue(startPoint[0], startPoint[1], startPoint[2]);
						noSSResidues.push(dummyRes);

						chainNoSS.push(noSSResidues);
					}

					noSSResidues = [];

					// check for next residue
					if (i < ii) {
						// force the non secondary structure spline to start on helix end point.
						var dummyRes = createDummyResidue(endPoint[0], endPoint[1], endPoint[2]);
						noSSResidues.push(dummyRes);

						var rm = rs[i + 1];
						if (rm.sheet) {
							noSSResidues.push(residue);
							noSSResidues.push(residue);
							chainNoSS.push(noSSResidues);
							noSSResidues = [];

							sheetResidues.push(residue);
						} else {
							// force the non secondary structure spline to start on helix end point.
							v3.scale(helixDir, -1);
							var additionCp = v3.add(endPoint, helixDir, []);
							var nextResCp = rm.cp1;
							var helixDirToNextRes = v3.subtract([nextResCp.x, nextResCp.y, nextResCp.z], additionCp, []);
							v3.normalize(helixDirToNextRes);
							v3.scale(helixDirToNextRes, .5);
							v3.add(additionCp, helixDirToNextRes);
							var dummyRes = createDummyResidue(additionCp[0], additionCp[1], additionCp[2]);
							noSSResidues.push(dummyRes);
						}
					}
				}

			} else if(residue.sheet) {

				sheetResidues.push(residue);
				if(residue.arrow) {

					var p1 = [0, 0, 0];
					var p2 = [0, 0, 0];
					for(var h = 0, hh = sheetResidues.length; h < hh; h++) {
						var guidePoints = sheetResidues[h].guidePointsLarge;
						var gp1 = guidePoints[0];
						var gp2 = guidePoints[guidePoints.length - 1];

						v3.add(p1, [gp1.x, gp1.y, gp1.z]);
						v3.add(p2, [gp2.x, gp2.y, gp2.z]);
					}

					v3.scale(p1, 1 / hh);
					v3.scale(p2, 1 / hh);

					var dirx = v3.subtract(p1, p2);

					var firstRs = sheetResidues[0];
					var lastRs = sheetResidues[sheetResidues.length - 1];

					var firstGuidePoints = firstRs.guidePointsSmall[0];
					var lastGuidePoints = lastRs.guidePointsSmall[0];

					this.sheetPlanks.push(new Plank(firstGuidePoints, lastGuidePoints, dirx));

					sheetResidues = [];

					if (i < ii) {
						var rm = rs[i + 1];

						if (rm.sheet) {
							sheetResidues.push(residue);
						} else {
							var dummyRes = createDummyResidue(lastGuidePoints.x, lastGuidePoints.y, lastGuidePoints.z);
							noSSResidues.push(dummyRes);
						}
					}
				}

			} else {
				noSSResidues.push(residue);

				if (i < ii) {
					var rm = rs[i + 1];
					if (rm.sheet) {
						var guidePoints = residue.guidePointsSmall[0];
						var dummyRes = createDummyResidue(guidePoints.x, guidePoints.y, guidePoints.z);

						noSSResidues.push(dummyRes);

						chainNoSS.push(noSSResidues);
						noSSResidues = [];

						sheetResidues.push(residue);
					}
				}
			}
		}

		if(noSSResidues.length > 1) {
			if(noSSResidues.length == 2) {
				noSSResidues.push(noSSResidues[noSSResidues.length - 1]);
			}
			chainNoSS.push(noSSResidues);
		}
		noSSResidues = [];

		var chainSegments = [];
		for ( var n = 0, nn = chainNoSS.length; n < nn; n++) {
			var nhs = chainNoSS[n];
			var lineSegmentsList = [];

			for ( var i = 0, ii = nhs.length - 1; i <= ii; i++) {
				lineSegmentsList.push(nhs[i].cp1);
			}
			chainSegments.push(lineSegmentsList);
		}

		for (var i = 0, ii = chainSegments.length; i < ii; i++) {
			var t = new d3.CatmullTube(chainSegments[i], specs.proteins_tubeThickness, specs.proteins_tubeResolution_3D, specs.proteins_horizontalResolution);
			t.chainColor = rs.chainColor;
			this.tubes.push(t);
		}
	};
	var _ = d3.PipePlank.prototype = new d3._Mesh();

	_.render = function(gl, specs) {
		gl.material.setTempColors(specs.proteins_materialAmbientColor_3D, undefined, specs.proteins_materialSpecularColor_3D, specs.proteins_materialShininess_3D);
		
		// colors
		gl.material.setDiffuseColor(specs.macro_colorByChain ? this.chainColor : specs.proteins_tubeColor);
		for ( var j = 0, jj = this.tubes.length; j < jj; j++) {
			gl.setMatrixUniforms(gl.modelViewMatrix);
			this.tubes[j].render(gl, specs);
		}

		if(!specs.macro_colorByChain) {
			gl.material.setDiffuseColor(specs.proteins_ribbonCartoonHelixSecondaryColor);
		}

		gl.cylinderClosedBuffer.bindBuffers(gl);
		for (var j = 0, jj = this.helixCylinders.length; j < jj; j++) {
			this.helixCylinders[j].render(gl, specs);
		}

		if(!specs.macro_colorByChain) {
			gl.material.setDiffuseColor(specs.proteins_ribbonCartoonSheetColor);
		}

		gl.boxBuffer.bindBuffers(gl);
		for (var j = 0, jj = this.sheetPlanks.length; j < jj; j++) {
			this.sheetPlanks[j].render(gl, specs);
		}

	};

})(ChemDoodle.extensions, ChemDoodle.RESIDUE, ChemDoodle.structures, ChemDoodle.structures.d3, Math, ChemDoodle.lib.mat4, ChemDoodle.lib.vec3, ChemDoodle.math);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(d3, document) {
	'use strict';
	d3.Shader = function() {
	};
	var _ = d3.Shader.prototype;
	_.init = function(gl) {
		var vertexShader = this.getShader(gl, 'vertex-shader');
		if (!vertexShader) {
			vertexShader = this.loadDefaultVertexShader(gl);
		}
		var fragmentShader = this.getShader(gl, 'fragment-shader');
		if (!fragmentShader) {
			fragmentShader = this.loadDefaultFragmentShader(gl);
		}

		gl.attachShader(gl.program, vertexShader);
		gl.attachShader(gl.program, fragmentShader);
		
		// the vertex position location must be explicit set to '0',
		// to prevent vertex normal become location '0'.
		// It's needed because later normal must be disabled for
		// rendering on picking framebuffer
		this.vertexPositionAttribute = 0;
		gl.bindAttribLocation(gl.program, this.vertexPositionAttribute, 'a_vertex_position');

		gl.linkProgram(gl.program);

		if (!gl.getProgramParameter(gl.program, gl.LINK_STATUS)) {
			alert('Could not initialize shaders: ' + gl.getProgramInfoLog(gl.program));
		}

		gl.useProgram(gl.program);

		gl.enableVertexAttribArray(this.vertexPositionAttribute);

		this.vertexTexCoordAttribute = gl.getAttribLocation(gl.program, 'a_vertex_texcoord');
		this.vertexNormalAttribute = gl.getAttribLocation(gl.program, 'a_vertex_normal');
		gl.enableVertexAttribArray(this.vertexNormalAttribute);
		
		// assign uniform properties
		this.dimensionUniform = gl.getUniformLocation(gl.program, 'u_dimension');
	};
	_.getShader = function(gl, id) {
		var shaderScript = document.getElementById(id);
		if (!shaderScript) {
			return undefined;
		}
		var sb = [];
		var k = shaderScript.firstChild;
		while (k) {
			if (k.nodeType === 3) {
				sb.push(k.textContent);
			}
			k = k.nextSibling;
		}
		var shader;
		if (shaderScript.type === 'x-shader/x-fragment') {
			shader = gl.createShader(gl.FRAGMENT_SHADER);
		} else if (shaderScript.type === 'x-shader/x-vertex') {
			shader = gl.createShader(gl.VERTEX_SHADER);
		} else {
			return undefined;
		}
		gl.shaderSource(shader, sb.join(''));
		gl.compileShader(shader);
		if (!gl.getShaderParameter(shader, gl.COMPILE_STATUS)) {
			alert(shaderScript.type + ' ' + gl.getShaderInfoLog(shader));
			return undefined;
		}
		return shader;
	};
	_.loadDefaultVertexShader = function(gl) {
		var sb = [
		'precision mediump float;',
		
		// attributes set when rendering objects
		'attribute vec3 a_vertex_position;',
		'attribute vec3 a_vertex_normal;',
		'attribute vec2 a_vertex_texcoord;',
		// scene uniforms
		'uniform vec3 u_light_diffuse_color;',
		'uniform vec3 u_material_ambient_color;',
		'uniform vec3 u_material_diffuse_color;',
		// matrices set by gl.setMatrixUniforms
		'uniform mat4 u_model_view_matrix;',
		'uniform mat4 u_projection_matrix;',
		'uniform mat3 u_normal_matrix;',
		'uniform vec2 u_dimension;',
		// sent to the fragment shader
		'varying vec2 v_texcoord;',
		'varying vec3 v_diffuse;',
		'varying vec4 v_ambient;',
		'varying vec3 v_normal;',

		'void main() {',
			'v_texcoord = a_vertex_texcoord;',
			'if(length(a_vertex_texcoord) != 0.) {',

				'gl_Position = u_model_view_matrix * vec4(a_vertex_position, 1.);',

				'vec4 depth_pos = vec4(gl_Position);',

				'depth_pos.z += a_vertex_normal.z;',

				'gl_Position = u_projection_matrix * gl_Position;',

				'depth_pos = u_projection_matrix * depth_pos;',

				'gl_Position /= gl_Position.w;',

				'gl_Position.xy += a_vertex_normal.xy / u_dimension * 2.;',

				'gl_Position.z = depth_pos.z / depth_pos.w;',

			'} else {',

				'v_normal = length(a_vertex_normal)==0. ? a_vertex_normal : normalize(u_normal_matrix * a_vertex_normal);',
				
				'v_ambient = vec4(u_material_ambient_color, 1.);',
				'v_diffuse = u_material_diffuse_color * u_light_diffuse_color;',
				
				'gl_Position = u_projection_matrix * u_model_view_matrix * vec4(a_vertex_position, 1.);',
				// just to make sure the w is 1
				'gl_Position /= gl_Position.w;',
				
			'}',

		'}'].join('');
		
		var shader = gl.createShader(gl.VERTEX_SHADER);
		gl.shaderSource(shader, sb);
		gl.compileShader(shader);
		if (!gl.getShaderParameter(shader, gl.COMPILE_STATUS)) {
			alert('Vertex shader failed to compile: ' + gl.getShaderInfoLog(shader));
			return undefined;
		}
		return shader;
	};
	_.loadDefaultFragmentShader = function(gl) {
		var sb = [
		// set float precision
		'precision mediump float;\n',
					
		// scene uniforms
		'uniform vec3 u_light_specular_color;',
		'uniform vec3 u_light_direction;',
		'uniform vec3 u_light_half_vector;',

		'uniform vec3 u_material_specular_color;',
		'uniform float u_material_shininess;',
		'uniform float u_material_alpha;',

		'uniform int u_fog_mode;',
		'uniform vec3 u_fog_color;',
		'uniform float u_fog_density;',
		'uniform float u_fog_start;',
		'uniform float u_fog_end;',

		// texture for draw text
		'uniform sampler2D u_image;',
					
		// from the vertex shader
		'varying vec2 v_texcoord;',
		'varying vec3 v_diffuse;',
		'varying vec4 v_ambient;',
		'varying vec3 v_normal;',
		'void main(void)',
		'{',
			'if(length(v_texcoord)!=0.) {',
				'gl_FragColor = texture2D(u_image, v_texcoord);',
				'return;',
			'}',

			'if(length(v_normal)==0.){',
				'gl_FragColor = vec4(v_diffuse, u_material_alpha);',
			'}else{',
				'float nDotL = max(dot(v_normal, u_light_direction), 0.);',
				'vec4 color = vec4(v_diffuse*nDotL, 1.);',
				'float nDotHV = max(dot(v_normal, u_light_half_vector), 0.);',
				'vec3 specular = u_material_specular_color * u_light_specular_color;',
				'color+=vec4(specular * pow(nDotHV, u_material_shininess), 1.);',

				// set the color
				'gl_FragColor = color+v_ambient;',
				'gl_FragColor.a*=u_material_alpha;',
			'}',

			'float fogCoord = 1.-clamp((u_fog_end - gl_FragCoord.z/gl_FragCoord.w) / (u_fog_end - u_fog_start), 0., 1.);',
			'float fogFactor = 1.;',

			// linear equation
			'if(u_fog_mode == 1){',
				'fogFactor = 1.-fogCoord;',
			'}',
			// exp equation
			'else if(u_fog_mode == 2) {',
				'fogFactor = clamp(exp(-u_fog_density*fogCoord), 0., 1.);',
			'}',
			// exp2 equation
			'else if(u_fog_mode == 3) {',
				'fogFactor = clamp(exp(-pow(u_fog_density*fogCoord, 2.)), 0., 1.);',
			'}',
			'gl_FragColor = mix(vec4(u_fog_color, 1.), gl_FragColor, fogFactor);',

			// for debugging
			// 'gl_FragColor = vec4(vec3(fogFactor), 1.);',
		'}'
		].join('');
		
		var shader = gl.createShader(gl.FRAGMENT_SHADER);
		gl.shaderSource(shader, sb);
		gl.compileShader(shader);
		if (!gl.getShaderParameter(shader, gl.COMPILE_STATUS)) {
			alert('Fragment shader failed to compile: ' + gl.getShaderInfoLog(shader));
			return undefined;
		}
		return shader;
	};

})(ChemDoodle.structures.d3, document);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(structures, d3, v3) {
	'use strict';
	d3.Shape = function(points, thickness) {
		// points must be in the xy-plane, all z-coords must be 0, thickness
		// will be in the z-plane
		var numPoints = points.length;
		var positionData = [];
		var normalData = [];

		// calculate vertex and normal points
		var center = new structures.Point();
		for ( var i = 0, ii = numPoints; i < ii; i++) {
			var next = i + 1;
			if (i === ii - 1) {
				next = 0;
			}
			var z = [ 0, 0, 1 ];
			var currentPoint = points[i];
			var nextPoint = points[next];
			var v = [ nextPoint.x - currentPoint.x, nextPoint.y - currentPoint.y, 0 ];
			var normal = v3.cross(z, v);
			// first four are for the side normal
			// second four will do both the bottom and top triangle normals
			for ( var j = 0; j < 2; j++) {
				positionData.push(currentPoint.x, currentPoint.y, thickness / 2);
				positionData.push(currentPoint.x, currentPoint.y, -thickness / 2);
				positionData.push(nextPoint.x, nextPoint.y, thickness / 2);
				positionData.push(nextPoint.x, nextPoint.y, -thickness / 2);
			}
			// side normals
			for ( var j = 0; j < 4; j++) {
				normalData.push(normal[0], normal[1], normal[2]);
			}
			// top and bottom normals
			normalData.push(0, 0, 1);
			normalData.push(0, 0, -1);
			normalData.push(0, 0, 1);
			normalData.push(0, 0, -1);
			center.add(currentPoint);
		}
		// centers
		center.x /= numPoints;
		center.y /= numPoints;
		normalData.push(0, 0, 1);
		positionData.push(center.x, center.y, thickness / 2);
		normalData.push(0, 0, -1);
		positionData.push(center.x, center.y, -thickness / 2);

		// build mesh connectivity
		var indexData = [];
		var centerIndex = numPoints * 8;
		for ( var i = 0, ii = numPoints; i < ii; i++) {
			var start = i * 8;
			// sides
			indexData.push(start);
			indexData.push(start + 3);
			indexData.push(start + 1);
			indexData.push(start);
			indexData.push(start + 2);
			indexData.push(start + 3);
			// top and bottom
			indexData.push(start + 4);
			indexData.push(centerIndex);
			indexData.push(start + 6);
			indexData.push(start + 5);
			indexData.push(start + 7);
			indexData.push(centerIndex + 1);
		}

		this.storeData(positionData, normalData, indexData);
	};
	d3.Shape.prototype = new d3._Mesh();

})(ChemDoodle.structures, ChemDoodle.structures.d3, ChemDoodle.lib.vec3);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(d3, m, v3) {
	'use strict';
	d3.Star = function() {
		var ps = [ .8944, .4472, 0, .2764, .4472, .8506, .2764, .4472, -.8506, -.7236, .4472, .5257, -.7236, .4472, -.5257, -.3416, .4472, 0, -.1056, .4472, .3249, -.1056, .4472, -.3249, .2764, .4472, .2008, .2764, .4472, -.2008, -.8944, -.4472, 0, -.2764, -.4472, .8506, -.2764, -.4472, -.8506, .7236, -.4472, .5257, .7236, -.4472, -.5257, .3416, -.4472, 0, .1056, -.4472, .3249, .1056, -.4472, -.3249, -.2764, -.4472, .2008, -.2764, -.4472, -.2008, -.5527, .1058, 0, -.1708, .1058, .5527, -.1708,
				.1058, -.5527, .4471, .1058, .3249, .4471, .1058, -.3249, .5527, -.1058, 0, .1708, -.1058, .5527, .1708, -.1058, -.5527, -.4471, -.1058, .3249, -.4471, -.1058, -.3249, 0, 1, 0, 0, -1, 0 ];
		var is = [ 0, 9, 8, 2, 7, 9, 4, 5, 7, 3, 6, 5, 1, 8, 6, 0, 8, 23, 30, 6, 8, 3, 21, 6, 11, 26, 21, 13, 23, 26, 2, 9, 24, 30, 8, 9, 1, 23, 8, 13, 25, 23, 14, 24, 25, 4, 7, 22, 30, 9, 7, 0, 24, 9, 14, 27, 24, 12, 22, 27, 3, 5, 20, 30, 7, 5, 2, 22, 7, 12, 29, 22, 10, 20, 29, 1, 6, 21, 30, 5, 6, 4, 20, 5, 10, 28, 20, 11, 21, 28, 10, 19, 18, 12, 17, 19, 14, 15, 17, 13, 16, 15, 11, 18, 16, 31, 19, 17, 14, 17, 27, 2, 27, 22, 4, 22, 29, 10, 29, 19, 31, 18, 19, 12, 19, 29, 4, 29, 20, 3, 20, 28,
				11, 28, 18, 31, 16, 18, 10, 18, 28, 3, 28, 21, 1, 21, 26, 13, 26, 16, 31, 15, 16, 11, 16, 26, 1, 26, 23, 0, 23, 25, 14, 25, 15, 31, 17, 15, 13, 15, 25, 0, 25, 24, 2, 24, 27, 12, 27, 17 ];

		var positionData = [];
		var normalData = [];
		var indexData = [];
		for ( var i = 0, ii = is.length; i < ii; i += 3) {
			var j1 = is[i] * 3;
			var j2 = is[i + 1] * 3;
			var j3 = is[i + 2] * 3;

			var p1 = [ ps[j1], ps[j1 + 1], ps[j1 + 2] ];
			var p2 = [ ps[j2], ps[j2 + 1], ps[j2 + 2] ];
			var p3 = [ ps[j3], ps[j3 + 1], ps[j3 + 2] ];

			var toAbove = [ p1[0] - p2[0], p1[1] - p2[1], p1[2] - p2[2] ];
			var toSide = [ p3[0] - p2[0], p3[1] - p2[1], p3[2] - p2[2] ];
			var normal = v3.cross(toSide, toAbove, []);
			v3.normalize(normal);

			positionData.push(p1[0], p1[1], p1[2], p2[0], p2[1], p2[2], p3[0], p3[1], p3[2]);
			normalData.push(normal[0], normal[1], normal[2], normal[0], normal[1], normal[2], normal[0], normal[1], normal[2]);
			indexData.push(i, i + 1, i + 2);
		}

		this.storeData(positionData, normalData, indexData);
	};
	d3.Star.prototype = new d3._Mesh();

})(ChemDoodle.structures.d3, Math, ChemDoodle.lib.vec3);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//
(function(d3, extensions, document, window) {
	'use strict';
	var ratio = 1;
	if(window.devicePixelRatio){
		ratio = window.devicePixelRatio;
	}
	
	d3.TextImage = function() {
		this.ctx = document.createElement('canvas').getContext('2d');
		this.data = [];
		this.text = '';
		this.charHeight = 0;
	};
	var _ = d3.TextImage.prototype;
	_.init = function(gl) {
		// init texture
		this.textureImage = gl.createTexture();
		gl.bindTexture(gl.TEXTURE_2D, this.textureImage);
		gl.pixelStorei(gl.UNPACK_FLIP_Y_WEBGL, false);
		gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_S, gl.CLAMP_TO_EDGE);
		gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_T, gl.CLAMP_TO_EDGE);
		gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MIN_FILTER, gl.NEAREST);
		gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MAG_FILTER, gl.NEAREST);
		gl.bindTexture(gl.TEXTURE_2D, null);
		this.updateFont(gl, 12, [ 'Sans-serif' ], false, false, false);
	};
	_.charData = function(character) {
		var index = this.text.indexOf(character);
		return index >= 0 ? this.data[index] : null;
	};
	_.updateFont = function(gl, fontSize, fontFamilies, fontBold, fontItalic, fontStroke) {
		var ctx = this.ctx;
		var canvas = this.ctx.canvas;
		var data = [];
		var text = "";
		fontSize *= ratio;
		var contextFont = extensions.getFontString(fontSize, fontFamilies, fontBold, fontItalic);
		ctx.font = contextFont;
		ctx.save();
		var totalWidth = 0;
		var charHeight = fontSize * 1.5;
		for ( var i = 32, ii = 127; i < ii; i++) {
			// skip control characters
			// if(i <= 31 || i == 127) continue;
			var character = String.fromCharCode(i), width = ctx.measureText(character).width;
			data.push({
				text : character,
				width : width,
				height : charHeight
			});
			totalWidth += width * 2;
		}
		
		// add other characters
		var chars = '\u00b0\u212b\u00AE'.split('');
		for ( var i = 0, ii = chars.length; i < ii; i++) {
			var character = chars[i], width = ctx.measureText(character).width;
			data.push({
				text : character,
				width : width,
				height : charHeight
			});
			totalWidth += width * 2;
		}
		var areaImage = totalWidth * charHeight;
		var sqrtArea = Math.sqrt(areaImage);
		var totalRows = Math.ceil(sqrtArea / charHeight);
		var maxWidth = Math.ceil(totalWidth / (totalRows - 1));
		canvas.width = maxWidth;
		canvas.height = totalRows * charHeight;
		ctx.font = contextFont;
		ctx.textAlign = "left";
		ctx.textBaseline = "middle";
		ctx.strokeStyle = "#000";
		ctx.lineWidth = 1.4;
		ctx.fillStyle = "#fff";
		var offsetRow = 0;
		var posX = 0;
		for ( var i = 0, ii = data.length; i < ii; i++) {
			var charData = data[i];
			var charWidth = charData.width * 2;
			var charHeight = charData.height;
			var charText = charData.text;
			var willWidth = posX + charWidth;
			if (willWidth > maxWidth) {
				offsetRow++;
				posX = 0;
			}
			var posY = offsetRow * charHeight;
			if (fontStroke) {
				// stroke must draw before fill
				ctx.strokeText(charText, posX, posY + (charHeight / 2));
			}
			ctx.fillText(charText, posX, posY + (charHeight / 2));
			charData.x = posX;
			charData.y = posY;
			text += charText;
			posX += charWidth;
		}
		this.text = text;
		this.data = data;
		this.charHeight = charHeight;
		// also update the texture
		gl.bindTexture(gl.TEXTURE_2D, this.textureImage);
		gl.texImage2D(gl.TEXTURE_2D, 0, gl.RGBA, gl.RGBA, gl.UNSIGNED_BYTE, canvas);
		gl.bindTexture(gl.TEXTURE_2D, null);
	};
	_.pushVertexData = function(text, position, zDepth, data) {
		// characters of string text
		var textPiece = text.toString().split("");
		// height of texture image
		var heightImage = this.getHeight();
		var widthImage = this.getWidth();
		var x1 = -this.textWidth(text) / 2 / ratio;
		var y1 = -this.charHeight / 2 / ratio;
		// iterate each character
		for ( var j = 0, jj = textPiece.length; j < jj; j++) {
			var charData = this.charData(textPiece[j]);
			var width = charData.width;
			var left = charData.x / widthImage;
			var right = left + charData.width * 1.8 / widthImage;
			var top = charData.y / heightImage;
			var bottom = top + charData.height / heightImage;
			var x2 = x1 + width * 1.8 / ratio;
			var y2 = this.charHeight / 2 / ratio;
			data.position.push(
			// left top
			position[0], position[1], position[2],
			// right top
			position[0], position[1], position[2],
			// right bottom
			position[0], position[1], position[2],
			// left top
			position[0], position[1], position[2],
			// left bottom
			position[0], position[1], position[2],
			// right bottom
			position[0], position[1], position[2]);
			data.texCoord.push(
			// left top
			left, top,
			// right bottom
			right, bottom,
			// right top
			right, top,
			// left top
			left, top,
			// left bottom
			left, bottom,
			// right bottom
			right, bottom);
			data.translation.push(
			// left top
			x1, y2, zDepth,
			// right bottom
			x2, y1, zDepth,
			// right top
			x2, y2, zDepth,
			// left top
			x1, y2, zDepth,
			// left bottom
			x1, y1, zDepth,
			// right bottom
			x2, y1, zDepth);
			x1 = x2 + (width - width * 1.8) / ratio;
		}
	};
	_.getCanvas = function() {
		return this.ctx.canvas;
	};
	_.getHeight = function() {
		return this.getCanvas().height;
	};
	_.getWidth = function() {
		return this.getCanvas().width;
	};
	_.textWidth = function(text) {
		return this.ctx.measureText(text).width;
	};
	_.test = function() {
		document.body.appendChild(this.getCanvas());
	};
	_.useTexture = function(gl) {
		gl.bindTexture(gl.TEXTURE_2D, this.textureImage);
	};
})(ChemDoodle.structures.d3, ChemDoodle.extensions, document, window);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(d3, m) {
	'use strict';
	d3.TextMesh = function() {
	};
	var _ = d3.TextMesh.prototype;
	_.init = function(gl) {

		// set vertex buffer
		this.vertexPositionBuffer = gl.createBuffer();
		this.vertexTexCoordBuffer = gl.createBuffer();
		this.vertexTranslationBuffer = gl.createBuffer();

	};
	_.setVertexData = function(gl, vertexBuffer, bufferData, itemSize) {
		gl.bindBuffer(gl.ARRAY_BUFFER, vertexBuffer);
		gl.bufferData(gl.ARRAY_BUFFER, new Float32Array(bufferData), gl.STATIC_DRAW);
		vertexBuffer.itemSize = itemSize;
		vertexBuffer.numItems = bufferData.length / itemSize;
	};
	_.storeData = function(gl, vertexPositionData, vertexTexCoordData, vertexTranslationData) {
		this.setVertexData(gl, this.vertexPositionBuffer, vertexPositionData, 3);
		this.setVertexData(gl, this.vertexTexCoordBuffer, vertexTexCoordData, 2);
		this.setVertexData(gl, this.vertexTranslationBuffer, vertexTranslationData, 3);
	};
	_.bindBuffers = function(gl) {

		// positions
		gl.bindBuffer(gl.ARRAY_BUFFER, this.vertexPositionBuffer);
		gl.vertexAttribPointer(gl.shader.vertexPositionAttribute, this.vertexPositionBuffer.itemSize, gl.FLOAT, false, 0, 0);

		// texCoord
		gl.bindBuffer(gl.ARRAY_BUFFER, this.vertexTexCoordBuffer);
		gl.vertexAttribPointer(gl.shader.vertexTexCoordAttribute, this.vertexTexCoordBuffer.itemSize, gl.FLOAT, false, 0, 0);

		// translation and z depth
		gl.bindBuffer(gl.ARRAY_BUFFER, this.vertexTranslationBuffer);
		gl.vertexAttribPointer(gl.shader.vertexNormalAttribute, this.vertexTranslationBuffer.itemSize, gl.FLOAT, false, 0, 0);

	};
	_.render = function(gl) {
		var numItems = this.vertexPositionBuffer.numItems;

		if (!numItems) {
			// nothing to do here
			return;
		}

		this.bindBuffers(gl);
		gl.drawArrays(gl.TRIANGLES, 0, numItems);
	};

})(ChemDoodle.structures.d3, Math);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(ELEMENT, math, extensions, d3, m, m4, v3) {
	'use strict';
	d3.Torsion = function(a1, a2, a3, a4) {
		this.a1 = a1;
		this.a2 = a2;
		this.a3 = a3;
		this.a4 = a4;
	};
	var _ = d3.Torsion.prototype = new d3._Measurement();
	_.calculateData = function(specs) {
		var positionData = [];
		var normalData = [];
		var indexData = [];
		var dist1 = this.a2.distance3D(this.a1);
		var dist2 = this.a2.distance3D(this.a3);
		this.distUse = m.min(dist1, dist2) / 2;
		// data for the angle
		var b1 = [ this.a2.x - this.a1.x, this.a2.y - this.a1.y, this.a2.z - this.a1.z ];
		var b2 = [ this.a3.x - this.a2.x, this.a3.y - this.a2.y, this.a3.z - this.a2.z ];
		var b3 = [ this.a4.x - this.a3.x, this.a4.y - this.a3.y, this.a4.z - this.a3.z ];
		var cross12 = v3.cross(b1, b2, []);
		var cross23 = v3.cross(b2, b3, []);
		v3.scale(b1, v3.length(b2));
		this.torsion = m.atan2(v3.dot(b1, cross23), v3.dot(cross12, cross23));

		var vec1 = v3.normalize(v3.cross(cross12, b2, []));
		var vec3 = v3.normalize(v3.cross(b2, vec1, []));

		this.pos = v3.add([ this.a2.x, this.a2.y, this.a2.z ], v3.scale(v3.normalize(b2, []), this.distUse));

		var vec0 = [];

		var bands = specs.measurement_angleBands_3D;
		for ( var i = 0; i <= bands; ++i) {
			var theta = this.torsion * i / bands;
			var vecCos = v3.scale(vec1, m.cos(theta), []);
			var vecSin = v3.scale(vec3, m.sin(theta), []);
			var norm = v3.scale(v3.normalize(v3.add(vecCos, vecSin, [])), this.distUse);

			if (i == 0) {
				vec0 = norm;
			}

			positionData.push(this.pos[0] + norm[0], this.pos[1] + norm[1], this.pos[2] + norm[2]);
			normalData.push(0, 0, 0);
			if (i < bands) {
				indexData.push(i, i + 1);
			}
		}

		this.vecText = v3.normalize(v3.add(vec0, norm, []));
		
		var arrowLength = 0.25;
		var b2Norm = v3.normalize(b2, []);
		v3.scale(b2Norm, arrowLength / 4);

		var theta = this.torsion - m.asin(arrowLength / 2) * 2 * this.torsion / m.abs(this.torsion);
		var vecCos = v3.scale(vec1, m.cos(theta), []);
		var vecSin = v3.scale(vec3, m.sin(theta), []);
		var norm = v3.scale(v3.normalize(v3.add(vecCos, vecSin, [])), this.distUse);

		positionData.push(this.pos[0] + b2Norm[0] + norm[0], this.pos[1] + b2Norm[1] + norm[1], this.pos[2] + b2Norm[2] + norm[2]);
		normalData.push(0, 0, 0);

		positionData.push(this.pos[0] - b2Norm[0] + norm[0], this.pos[1] - b2Norm[1] + norm[1], this.pos[2] - b2Norm[2] + norm[2]);
		normalData.push(0, 0, 0);

		indexData.push(--i, i + 1, i, i + 2);

		this.storeData(positionData, normalData, indexData);
	};
	_.getText = function(specs) {
		v3.add(this.pos, v3.scale(this.vecText, this.distUse + 0.3, []));

		return {
			pos : this.pos,
			value : [ math.angleBounds(this.torsion, true, true).toFixed(2), ' \u00b0' ].join('')
		};
	};

})(ChemDoodle.ELEMENT, ChemDoodle.math, ChemDoodle.extensions, ChemDoodle.structures.d3, Math, ChemDoodle.lib.mat4, ChemDoodle.lib.vec3);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(extensions, RESIDUE, structures, d3, m, m4, v3, math) {
	'use strict';
	var loadPartition = function(gl, p) {
		// positions
		gl.bindBuffer(gl.ARRAY_BUFFER, p.vertexPositionBuffer);
		gl.vertexAttribPointer(gl.shader.vertexPositionAttribute, p.vertexPositionBuffer.itemSize, gl.FLOAT, false, 0, 0);
		// normals
		gl.bindBuffer(gl.ARRAY_BUFFER, p.vertexNormalBuffer);
		gl.vertexAttribPointer(gl.shader.vertexNormalAttribute, p.vertexNormalBuffer.itemSize, gl.FLOAT, false, 0, 0);
		// indexes
		gl.bindBuffer(gl.ELEMENT_ARRAY_BUFFER, p.vertexIndexBuffer);
	};

	var PointRotator = function(point, axis, angle) {
		var d = m.sqrt(axis[1] * axis[1] + axis[2] * axis[2]);
		var Rx = [ 1, 0, 0, 0, 0, axis[2] / d, -axis[1] / d, 0, 0, axis[1] / d, axis[2] / d, 0, 0, 0, 0, 1 ];
		var RxT = [ 1, 0, 0, 0, 0, axis[2] / d, axis[1] / d, 0, 0, -axis[1] / d, axis[2] / d, 0, 0, 0, 0, 1 ];
		var Ry = [ d, 0, -axis[0], 0, 0, 1, 0, 0, axis[0], 0, d, 0, 0, 0, 0, 1 ];
		var RyT = [ d, 0, axis[0], 0, 0, 1, 0, 0, -axis[0], 0, d, 0, 0, 0, 0, 1 ];
		var Rz = [ m.cos(angle), -m.sin(angle), 0, 0, m.sin(angle), m.cos(angle), 0, 0, 0, 0, 1, 0, 0, 0, 0, 1 ];
		var matrix = m4.multiply(Rx, m4.multiply(Ry, m4.multiply(Rz, m4.multiply(RyT, RxT, []))));
		this.rotate = function() {
			return m4.multiplyVec3(matrix, point);
		};
	};

	d3.Tube = function(chain, thickness, cylinderResolution) {
		var lineSegmentNum = chain[0].lineSegments[0].length;
		this.partitions = [];
		var currentPartition;
		this.ends = [];
		this.ends.push(chain[0].lineSegments[0][0]);
		this.ends.push(chain[chain.length - 1].lineSegments[0][0]);
		// calculate vertex and normal points
		var last = [ 1, 0, 0 ];
		for ( var i = 0, ii = chain.length; i < ii; i++) {
			if (!currentPartition || currentPartition.positionData.length > 65000) {
				if (this.partitions.length > 0) {
					i--;
				}
				currentPartition = {
					count : 0,
					positionData : [],
					normalData : [],
					indexData : []
				};
				this.partitions.push(currentPartition);
			}
			var residue = chain[i];
			currentPartition.count++;
			var min = Infinity;
			var p = new structures.Atom('', chain[i].cp1.x, chain[i].cp1.y, chain[i].cp1.z);
			for ( var j = 0; j < lineSegmentNum; j++) {
				var currentPoint = residue.lineSegments[0][j];
				var nextPoint;
				if (j === lineSegmentNum - 1) {
					if (i === chain.length - 1) {
						nextPoint = residue.lineSegments[0][j - 1];
					} else {
						nextPoint = chain[i + 1].lineSegments[0][0];
					}
				} else {
					nextPoint = residue.lineSegments[0][j + 1];
				}
				var axis = [ nextPoint.x - currentPoint.x, nextPoint.y - currentPoint.y, nextPoint.z - currentPoint.z ];
				v3.normalize(axis);
				if (i === chain.length - 1 && j === lineSegmentNum - 1) {
					v3.scale(axis, -1);
				}
				var startVector = v3.cross(axis, last, []);
				v3.normalize(startVector);
				v3.scale(startVector, thickness / 2);
				var rotator = new PointRotator(startVector, axis, 2 * Math.PI / cylinderResolution);
				for ( var k = 0, kk = cylinderResolution; k < kk; k++) {
					var use = rotator.rotate();
					if (k === m.floor(cylinderResolution / 4)) {
						last = [ use[0], use[1], use[2] ];
					}
					currentPartition.normalData.push(use[0], use[1], use[2]);
					currentPartition.positionData.push(currentPoint.x + use[0], currentPoint.y + use[1], currentPoint.z + use[2]);
				}
				// find closest point to attach stick to
				if (p) {
					var dist = currentPoint.distance3D(p);
					if (dist < min) {
						min = dist;
						chain[i].pPoint = currentPoint;
					}
				}
			}
		}

		// build mesh connectivity
		for ( var n = 0, nn = this.partitions.length; n < nn; n++) {
			var currentPartition = this.partitions[n];
			for ( var i = 0, ii = currentPartition.count - 1; i < ii; i++) {
				var indexStart = i * lineSegmentNum * cylinderResolution;
				for ( var j = 0, jj = lineSegmentNum; j < jj; j++) {
					var segmentIndexStart = indexStart + j * cylinderResolution;
					for ( var k = 0; k < cylinderResolution; k++) {
						var next = 1;
						var sk = segmentIndexStart + k;
						currentPartition.indexData.push(sk);
						currentPartition.indexData.push(sk + cylinderResolution);
						currentPartition.indexData.push(sk + cylinderResolution + next);
						currentPartition.indexData.push(sk);
						currentPartition.indexData.push(sk + cylinderResolution + next);
						currentPartition.indexData.push(sk + next);
					}
				}
			}
		}

		this.storeData(this.partitions[0].positionData, this.partitions[0].normalData, this.partitions[0].indexData);

		var ps = [ new structures.Point(2, 0) ];
		for ( var i = 0; i < 60; i++) {
			var ang = i / 60 * m.PI;
			ps.push(new structures.Point(2 * m.cos(ang), -2 * m.sin(ang)));
		}
		ps.push(new structures.Point(-2, 0), new structures.Point(-2, 4), new structures.Point(2, 4));
		var platform = new structures.d3.Shape(ps, 1);

		this.render = function(gl, specs) {
			// draw tube
			this.bindBuffers(gl);
			// colors
			gl.material.setDiffuseColor(specs.macro_colorByChain ? this.chainColor : specs.nucleics_tubeColor);
			// render
			gl.drawElements(gl.TRIANGLES, this.vertexIndexBuffer.numItems, gl.UNSIGNED_SHORT, 0);
			if (this.partitions) {
				for ( var i = 1, ii = this.partitions.length; i < ii; i++) {
					var p = this.partitions[i];
					loadPartition(gl, p);
					// render
					gl.drawElements(gl.TRIANGLES, p.vertexIndexBuffer.numItems, gl.UNSIGNED_SHORT, 0);
				}
			}

			// draw ends
			gl.sphereBuffer.bindBuffers(gl);
			for ( var i = 0; i < 2; i++) {
				var p = this.ends[i];
				var transform = m4.translate(gl.modelViewMatrix, [ p.x, p.y, p.z ], []);
				var radius = thickness / 2;
				m4.scale(transform, [ radius, radius, radius ]);
				// render
				gl.setMatrixUniforms(transform);
				gl.drawElements(gl.TRIANGLES, gl.sphereBuffer.vertexIndexBuffer.numItems, gl.UNSIGNED_SHORT, 0);
			}

			// draw nucleotide handles
			gl.cylinderBuffer.bindBuffers(gl);
			for ( var i = 0, ii = chain.length - 1; i < ii; i++) {
				var residue = chain[i];
				var p1 = residue.pPoint;
				var p2 = new structures.Atom('', residue.cp2.x, residue.cp2.y, residue.cp2.z);
				var height = 1.001 * p1.distance3D(p2);
				var scaleVector = [ thickness / 4, height, thickness / 4 ];
				var transform = m4.translate(gl.modelViewMatrix, [ p1.x, p1.y, p1.z ], []);
				var y = [ 0, 1, 0 ];
				var ang = 0;
				var axis;
				var a2b = [ p2.x - p1.x, p2.y - p1.y, p2.z - p1.z ];
				if (p1.x === p2.x && p1.z === p2.z) {
					axis = [ 0, 0, 1 ];
					if (p1.y < p1.y) {
						ang = m.PI;
					}
				} else {
					ang = extensions.vec3AngleFrom(y, a2b);
					axis = v3.cross(y, a2b, []);
				}
				if (ang !== 0) {
					m4.rotate(transform, ang, axis);
				}
				m4.scale(transform, scaleVector);
				gl.setMatrixUniforms(transform);
				gl.drawArrays(gl.TRIANGLE_STRIP, 0, gl.cylinderBuffer.vertexPositionBuffer.numItems);
			}

			// draw nucleotide platforms
			platform.bindBuffers(gl);
			// colors
			if (specs.nucleics_residueColor === 'none' && !specs.macro_colorByChain) {
				gl.material.setDiffuseColor(specs.nucleics_baseColor);
			}
			for ( var i = 0, ii = chain.length - 1; i < ii; i++) {
				var residue = chain[i];
				var p2 = residue.cp2;
				var transform = m4.translate(gl.modelViewMatrix, [ p2.x, p2.y, p2.z ], []);
				// rotate to direction
				var y = [ 0, 1, 0 ];
				var ang = 0;
				var axis;
				var p3 = residue.cp3;
				if(p3){
					var a2b = [ p3.x - p2.x, p3.y - p2.y, p3.z - p2.z ];
					if (p2.x === p3.x && p2.z === p3.z) {
						axis = [ 0, 0, 1 ];
						if (p2.y < p2.y) {
							ang = m.PI;
						}
					} else {
						ang = extensions.vec3AngleFrom(y, a2b);
						axis = v3.cross(y, a2b, []);
					}
					if (ang !== 0) {
						m4.rotate(transform, ang, axis);
					}
					// rotate to orientation
					var x = [ 1, 0, 0 ];
					var rM = m4.rotate(m4.identity([]), ang, axis);
					m4.multiplyVec3(rM, x);
					var p4 = residue.cp4;
					var p5 = residue.cp5;
					if (!(p4.y === p5.y && p4.z === p5.z)) {
						var pivot = [ p5.x - p4.x, p5.y - p4.y, p5.z - p4.z ];
						var ang2 = extensions.vec3AngleFrom(x, pivot);
						if (v3.dot(a2b, v3.cross(x, pivot)) < 0) {
							ang2 *= -1;
						}
						m4.rotateY(transform, ang2);
					}
					// color
					if (!specs.macro_colorByChain) {
						if (specs.nucleics_residueColor === 'shapely') {
							if (RESIDUE[residue.name]) {
								gl.material.setDiffuseColor(RESIDUE[residue.name].shapelyColor);
							} else {
								gl.material.setDiffuseColor(RESIDUE['*'].shapelyColor);
							}
						} else if (specs.nucleics_residueColor === 'rainbow') {
							gl.material.setDiffuseColor(math.rainbowAt(i, ii, specs.macro_rainbowColors));
						}
					}
					// render
					gl.setMatrixUniforms(transform);
					gl.drawElements(gl.TRIANGLES, platform.vertexIndexBuffer.numItems, gl.UNSIGNED_SHORT, 0);
				}
			}

		};
	};
	d3.Tube.prototype = new d3._Mesh();

	d3.CatmullTube = function(chains, thickness, cylinderResolution, horizontalResolution) {
		var chain = [];
		chains.push(chains[chains.length - 1]);
		for ( var i = 0, ii = chains.length - 2; i <= ii; i++) {
			var p0 = chains[i == 0 ? 0 : i - 1];
			var p1 = chains[i + 0];
			var p2 = chains[i + 1];
			var p3 = chains[i == ii ? i + 1 : i + 2];

			var segments = [];

			for(var j = 0; j < horizontalResolution; j++) {

				var t = j / horizontalResolution;
				if(i == ii) {
					t = j / (horizontalResolution-1);
				}

				var x = 0.5 * ((2 * p1.x) +
                      (p2.x - p0.x) * t +
                      (2*p0.x - 5*p1.x + 4*p2.x - p3.x) * t * t +
                      (3*p1.x - p0.x - 3 * p2.x + p3.x) * t * t * t);
				var y = 0.5 * ((2 * p1.y) +
                      (p2.y - p0.y) * t +
                      (2*p0.y - 5*p1.y + 4*p2.y - p3.y) * t * t +
                      (3*p1.y -p0.y - 3 * p2.y + p3.y) * t * t * t);
				var z = 0.5 * ((2 * p1.z) +
                      (p2.z - p0.z) * t +
                      (2*p0.z - 5*p1.z + 4*p2.z - p3.z) * t * t +
                      (3*p1.z -p0.z - 3 * p2.z + p3.z) * t * t * t);

				var o = new structures.Atom('C', x, y, z);
				segments.push(o);
			}

			chain.push(segments);
		}

		var lineSegmentNum = chain[0].length;
		this.partitions = [];
		var currentPartition;
		this.ends = [];
		this.ends.push(chain[0][0]);
		this.ends.push(chain[chain.length - 1][0]);

		// calculate vertex and normal points
		var last = [ 1, 0, 0 ];
		for ( var i = 0, ii = chain.length; i < ii; i++) {
			if (!currentPartition || currentPartition.positionData.length > 65000) {
				if (this.partitions.length > 0) {
					i--;
				}
				currentPartition = {
					count : 0,
					positionData : [],
					normalData : [],
					indexData : []
				};
				this.partitions.push(currentPartition);
			}

			var residue = chain[i];

			currentPartition.count++;
			var min = Infinity;
			// var p = new structures.Atom('', chain[i].cp1.x, chain[i].cp1.y, chain[i].cp1.z);
			for ( var j = 0; j < lineSegmentNum; j++) {
				var currentPoint = residue[j];
				var nextPoint;
				if (j === lineSegmentNum - 1) {
					if (i === chain.length - 1) {
						nextPoint = residue[j - 1];
					} else {
						nextPoint = chain[i + 1][0];
					}
				} else {
					nextPoint = residue[j + 1];
				}

				var axis = [ nextPoint.x - currentPoint.x, nextPoint.y - currentPoint.y, nextPoint.z - currentPoint.z ];
				v3.normalize(axis);
				if (i === chain.length - 1 && j === lineSegmentNum - 1) {
					v3.scale(axis, -1);
				}
				var startVector = v3.cross(axis, last, []);
				v3.normalize(startVector);
				v3.scale(startVector, thickness / 2);
				var rotator = new PointRotator(startVector, axis, 2 * Math.PI / cylinderResolution);
				for ( var k = 0, kk = cylinderResolution; k < kk; k++) {
					var use = rotator.rotate();
					if (k === m.floor(cylinderResolution / 4)) {
						last = [ use[0], use[1], use[2] ];
					}
					currentPartition.normalData.push(use[0], use[1], use[2]);
					currentPartition.positionData.push(currentPoint.x + use[0], currentPoint.y + use[1], currentPoint.z + use[2]);
				}
			}
		}

		// build mesh connectivity
		for ( var n = 0, nn = this.partitions.length; n < nn; n++) {
			var currentPartition = this.partitions[n];
			for ( var i = 0, ii = currentPartition.count - 1; i < ii; i++) {
				var indexStart = i * lineSegmentNum * cylinderResolution;
				for ( var j = 0, jj = lineSegmentNum; j < jj; j++) {
					var segmentIndexStart = indexStart + j * cylinderResolution;
					for ( var k = 0; k <= cylinderResolution; k++) {
						var sk = segmentIndexStart + k % cylinderResolution;
						currentPartition.indexData.push(sk, sk + cylinderResolution);
					}
				}
			}
		}

		this.storeData(this.partitions[0].positionData, this.partitions[0].normalData, this.partitions[0].indexData);
	};
	var _ = d3.CatmullTube.prototype = new d3._Mesh();
	_.render = function(gl, specs) {
		// draw tube
		this.bindBuffers(gl);

		// render
		for ( var i = 0, ii = this.partitions.length; i < ii; i++) {
			var p = this.partitions[i];
			loadPartition(gl, p);
			// render
			gl.drawElements(gl.TRIANGLE_STRIP, p.vertexIndexBuffer.numItems, gl.UNSIGNED_SHORT, 0);
		}

		// draw ends
		gl.sphereBuffer.bindBuffers(gl);
		for ( var i = 0; i < 2; i++) {
			var p = this.ends[i];
			var transform = m4.translate(gl.modelViewMatrix, [ p.x, p.y, p.z ], []);
			var radius = specs.proteins_tubeThickness / 2;
			m4.scale(transform, [ radius, radius, radius ]);
			// render
			gl.setMatrixUniforms(transform);
			gl.drawElements(gl.TRIANGLES, gl.sphereBuffer.vertexIndexBuffer.numItems, gl.UNSIGNED_SHORT, 0);
		}
	};

})(ChemDoodle.extensions, ChemDoodle.RESIDUE, ChemDoodle.structures, ChemDoodle.structures.d3, Math, ChemDoodle.lib.mat4, ChemDoodle.lib.vec3, ChemDoodle.math);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(d3, v3) {
	'use strict';
	d3.UnitCell = function(unitCellVectors) {
		this.unitCell = unitCellVectors;
		var positionData = [];
		var normalData = [];
		// calculate vertex and normal points

		var pushSide = function(p1, p2, p3, p4) {
			positionData.push(p1[0], p1[1], p1[2]);
			positionData.push(p2[0], p2[1], p2[2]);
			positionData.push(p3[0], p3[1], p3[2]);
			positionData.push(p4[0], p4[1], p4[2]);
			// push 0s for normals so shader gives them full color
			for ( var i = 0; i < 4; i++) {
				normalData.push(0, 0, 0);
			}
		};
		pushSide(unitCellVectors.o, unitCellVectors.x, unitCellVectors.xy, unitCellVectors.y);
		pushSide(unitCellVectors.o, unitCellVectors.y, unitCellVectors.yz, unitCellVectors.z);
		pushSide(unitCellVectors.o, unitCellVectors.z, unitCellVectors.xz, unitCellVectors.x);
		pushSide(unitCellVectors.yz, unitCellVectors.y, unitCellVectors.xy, unitCellVectors.xyz);
		pushSide(unitCellVectors.xyz, unitCellVectors.xz, unitCellVectors.z, unitCellVectors.yz);
		pushSide(unitCellVectors.xy, unitCellVectors.x, unitCellVectors.xz, unitCellVectors.xyz);

		// build mesh connectivity
		var indexData = [];
		for ( var i = 0; i < 6; i++) {
			var start = i * 4;
			// sides
			indexData.push(start, start + 1, start + 1, start + 2, start + 2, start + 3, start + 3, start);
		}

		this.storeData(positionData, normalData, indexData);
	};
	var _ = d3.UnitCell.prototype = new d3._Mesh();
	_.render = function(gl, specs) {
		gl.setMatrixUniforms(gl.modelViewMatrix);
		this.bindBuffers(gl);
		// colors
		gl.material.setDiffuseColor(specs.shapes_color);
		gl.lineWidth(specs.shapes_lineWidth);
		// render
		gl.drawElements(gl.LINES, this.vertexIndexBuffer.numItems, gl.UNSIGNED_SHORT, 0);
	};

})(ChemDoodle.structures.d3, ChemDoodle.lib.vec3);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(structures, extensions, m) {
	'use strict';
	structures.Plate = function(lanes) {
		this.lanes = new Array(lanes);
		for (i = 0, ii = lanes; i < ii; i++) {
			this.lanes[i] = [];
		}
	};
	var _ = structures.Plate.prototype;
	_.sort = function() {
		for (i = 0, ii = this.lanes.length; i < ii; i++) {
			this.lanes[i].sort(function(a, b) {
				return a - b;
			});
		}
	};
	_.draw = function(ctx, specs) {
		// Front and origin
		var width = ctx.canvas.width;
		var height = ctx.canvas.height;
		this.origin = 9 * height / 10;
		this.front = height / 10;
		this.laneLength = this.origin - this.front;
		ctx.strokeStyle = '#000000';
		ctx.beginPath();
		ctx.moveTo(0, this.front);
		extensions.contextHashTo(ctx, 0, this.front, width, this.front, 3, 3);
		ctx.closePath();
		ctx.stroke();
		ctx.beginPath();
		ctx.moveTo(0, this.origin);
		ctx.lineTo(width, this.origin);
		ctx.closePath();
		ctx.stroke();
		// Lanes
		for (i = 0, ii = this.lanes.length; i < ii; i++) {
			var laneX = (i + 1) * width / (ii + 1);
			ctx.beginPath();
			ctx.moveTo(laneX, this.origin);
			ctx.lineTo(laneX, this.origin + 3);
			ctx.closePath();
			ctx.stroke();
			// Spots
			for (s = 0, ss = this.lanes[i].length; s < ss; s++) {
				var spotY = this.origin - (this.laneLength * this.lanes[i][s].rf);
				switch (this.lanes[i][s].type) {
				case 'compact':
					ctx.beginPath();
					ctx.arc(laneX, spotY, 3, 0, 2 * m.PI, false);
					ctx.closePath();
					break;
				case 'expanded':
					ctx.beginPath();
					ctx.arc(laneX, spotY, 7, 0, 2 * m.PI, false);
					ctx.closePath();
					break;
				case 'trailing':
					// trailing
					break;
				case 'widened':
					extensions.contextOval(ctx, laneX - 18, spotY - 10, 36, 10);
					break;
				case 'cresent':
					ctx.beginPath();
					ctx.arc(laneX, spotY, 9, 0, m.PI, true);
					ctx.closePath();
					break;
				}
				switch (this.lanes[i][s].style) {
				case 'solid':
					ctx.fillStyle = '#000000';
					ctx.fill();
					break;
				case 'transparent':
					ctx.stroke();
					break;
				case 'gradient':
					// gradient
					break;
				}
			}
		}
	};

	structures.Plate.Spot = function(type, rf, style) {
		this.type = type;
		this.rf = rf;
		this.style = style ? style : 'solid';
	};

})(ChemDoodle.structures, ChemDoodle.extensions, Math);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(c, structures, m) {
	'use strict';
	// default canvas properties
	c.default_backgroundColor = '#FFFFFF';
	c.default_scale = 1;
	c.default_rotateAngle = 0;
	c.default_bondLength_2D = 20;
	c.default_angstromsPerBondLength = 1.25;
	c.default_lightDirection_3D = [ -.1, -.1, -1 ];
	c.default_lightDiffuseColor_3D = '#FFFFFF';
	c.default_lightSpecularColor_3D = '#FFFFFF';
	c.default_projectionPerspective_3D = true;
	c.default_projectionPerspectiveVerticalFieldOfView_3D = 45;
	c.default_projectionOrthoWidth_3D = 40;
	c.default_projectionWidthHeightRatio_3D = undefined;
	c.default_projectionFrontCulling_3D = .1;
	c.default_projectionBackCulling_3D = 10000;
	c.default_cullBackFace_3D = true;
	c.default_fog_mode_3D = 0;
	c.default_fog_color_3D = '#000000';
	c.default_fog_start_3D = 0;
	c.default_fog_end_3D = 1;
	c.default_fog_density_3D = 1;

	// default atom properties
	c.default_atoms_display = true;
	c.default_atoms_color = '#000000';
	c.default_atoms_font_size_2D = 12;
	c.default_atoms_font_families_2D = [ 'Helvetica', 'Arial', 'Dialog' ];
	c.default_atoms_font_bold_2D = false;
	c.default_atoms_font_italic_2D = false;
	c.default_atoms_circles_2D = false;
	c.default_atoms_circleDiameter_2D = 10;
	c.default_atoms_circleBorderWidth_2D = 1;
	c.default_atoms_lonePairDistance_2D = 8;
	c.default_atoms_lonePairSpread_2D = 4;
	c.default_atoms_lonePairDiameter_2D = 1;
	c.default_atoms_useJMOLColors = false;
	c.default_atoms_usePYMOLColors = false;
	c.default_atoms_resolution_3D = 60;
	c.default_atoms_sphereDiameter_3D = .8;
	c.default_atoms_useVDWDiameters_3D = false;
	c.default_atoms_vdwMultiplier_3D = 1;
	c.default_atoms_materialAmbientColor_3D = '#000000';
	c.default_atoms_materialSpecularColor_3D = '#555555';
	c.default_atoms_materialShininess_3D = 32;
	c.default_atoms_implicitHydrogens_2D = true;
	c.default_atoms_displayTerminalCarbonLabels_2D = false;
	c.default_atoms_showHiddenCarbons_2D = true;
	c.default_atoms_showAttributedCarbons_2D = true;
	c.default_atoms_displayAllCarbonLabels_2D = false;
	c.default_atoms_nonBondedAsStars_3D = false;
	c.default_atoms_displayLabels_3D = false;
	c.default_atoms_HBlack_2D = true;

	// default bond properties
	c.default_bonds_display = true;
	c.default_bonds_color = '#000000';
	c.default_bonds_width_2D = 1;
	c.default_bonds_saturationWidth_2D = .2;
	c.default_bonds_ends_2D = 'round';
	c.default_bonds_useJMOLColors = false;
	c.default_bonds_usePYMOLColors = false;
	c.default_bonds_colorGradient = false;
	c.default_bonds_saturationAngle_2D = m.PI / 3;
	c.default_bonds_symmetrical_2D = false;
	c.default_bonds_clearOverlaps_2D = false;
	c.default_bonds_overlapClearWidth_2D = .5;
	c.default_bonds_atomLabelBuffer_2D = 1;
	c.default_bonds_wedgeThickness_2D = .22;
	c.default_bonds_hashWidth_2D = 1;
	c.default_bonds_hashSpacing_2D = 2.5;
	c.default_bonds_dotSize_2D = 2;
	c.default_bonds_lewisStyle_2D = false;
	c.default_bonds_showBondOrders_3D = false;
	c.default_bonds_resolution_3D = 60;
	c.default_bonds_renderAsLines_3D = false;
	c.default_bonds_cylinderDiameter_3D = .3;
	c.default_bonds_pillLatitudeResolution_3D = 10;
	c.default_bonds_pillLongitudeResolution_3D = 20;
	c.default_bonds_pillHeight_3D = .3;
	c.default_bonds_pillSpacing_3D = .1;
	c.default_bonds_pillDiameter_3D = .3;
	c.default_bonds_materialAmbientColor_3D = '#222222';
	c.default_bonds_materialSpecularColor_3D = '#555555';
	c.default_bonds_materialShininess_3D = 32;

	// default macromolecular properties
	c.default_proteins_displayRibbon = true;
	c.default_proteins_displayBackbone = false;
	c.default_proteins_backboneThickness = 1.5;
	c.default_proteins_backboneColor = '#CCCCCC';
	c.default_proteins_ribbonCartoonize = false;
	c.default_proteins_displayPipePlank = false;
	// shapely, amino, polarity, rainbow, acidity
	c.default_proteins_residueColor = 'none';
	c.default_proteins_primaryColor = '#FF0D0D';
	c.default_proteins_secondaryColor = '#FFFF30';
	c.default_proteins_ribbonCartoonHelixPrimaryColor = '#00E740';
	c.default_proteins_ribbonCartoonHelixSecondaryColor = '#9905FF';
	c.default_proteins_ribbonCartoonSheetColor = '#E8BB99';
	c.default_proteins_tubeColor = '#FF0D0D';
	c.default_proteins_tubeResolution_3D = 30;
	c.default_proteins_ribbonThickness = .2;
	c.default_proteins_tubeThickness = 0.5;
	c.default_proteins_plankSheetWidth = 3.5;
	c.default_proteins_cylinderHelixDiameter = 4;
	c.default_proteins_verticalResolution = 10;
	c.default_proteins_horizontalResolution = 9;
	c.default_proteins_materialAmbientColor_3D = '#222222';
	c.default_proteins_materialSpecularColor_3D = '#555555';
	c.default_proteins_materialShininess_3D = 32;
	c.default_nucleics_display = true;
	c.default_nucleics_tubeColor = '#CCCCCC';
	c.default_nucleics_baseColor = '#C10000';
	// shapely, rainbow
	c.default_nucleics_residueColor = 'none';
	c.default_nucleics_tubeThickness = 1.5;
	c.default_nucleics_tubeResolution_3D = 60;
	c.default_nucleics_verticalResolution = 10;
	c.default_nucleics_materialAmbientColor_3D = '#222222';
	c.default_nucleics_materialSpecularColor_3D = '#555555';
	c.default_nucleics_materialShininess_3D = 32;
	c.default_macro_displayAtoms = false;
	c.default_macro_displayBonds = false;
	c.default_macro_atomToLigandDistance = -1;
	c.default_macro_showWater = false;
	c.default_macro_colorByChain = false;
	c.default_macro_rainbowColors = ['#0000FF', '#00FFFF', '#00FF00', '#FFFF00', '#FF0000'];

	// default surface properties
	c.default_surfaces_display = true;
	c.default_surfaces_style = 'Dot';
	c.default_surfaces_color = '#E9B862';
	c.default_surfaces_materialAmbientColor_3D = '#000000';
	c.default_surfaces_materialSpecularColor_3D = '#000000';
	c.default_surfaces_materialShininess_3D = 32;

	// default spectrum properties
	c.default_plots_color = '#000000';
	c.default_plots_width = 1;
	c.default_plots_showIntegration = false;
	c.default_plots_integrationColor = '#c10000';
	c.default_plots_integrationLineWidth = 1;
	c.default_plots_showGrid = false;
	c.default_plots_gridColor = 'gray';
	c.default_plots_gridLineWidth = .5;
	c.default_plots_showYAxis = true;
	c.default_plots_flipXAxis = false;

	// default shape properties
	c.default_text_font_size = 12;
	c.default_text_font_families = [ 'Helvetica', 'Arial', 'Dialog' ];
	c.default_text_font_bold = true;
	c.default_text_font_italic = false;
	c.default_text_font_stroke_3D = true;
	c.default_text_color = '#000000';
	c.default_shapes_color = '#000000';
	c.default_shapes_lineWidth = 1;
	c.default_shapes_arrowLength_2D = 8;
	c.default_compass_display = false;
	c.default_compass_axisXColor_3D = '#FF0000';
	c.default_compass_axisYColor_3D = '#00FF00';
	c.default_compass_axisZColor_3D = '#0000FF';
	c.default_compass_size_3D = 50;
	c.default_compass_resolution_3D = 10;
	c.default_compass_displayText_3D = true;
	c.default_compass_type_3D = 0;
	c.default_measurement_update_3D = false;
	c.default_measurement_angleBands_3D = 10;
	c.default_measurement_displayText_3D = true;

	structures.VisualSpecifications = function() {
		// canvas properties
		this.backgroundColor = c.default_backgroundColor;
		this.scale = c.default_scale;
		this.rotateAngle = c.default_rotateAngle;
		this.bondLength_2D = c.default_bondLength_2D;
		this.angstromsPerBondLength = c.default_angstromsPerBondLength;
		this.lightDirection_3D = c.default_lightDirection_3D.slice(0);
		this.lightDiffuseColor_3D = c.default_lightDiffuseColor_3D;
		this.lightSpecularColor_3D = c.default_lightSpecularColor_3D;
		this.projectionPerspective_3D = c.default_projectionPerspective_3D;
		this.projectionPerspectiveVerticalFieldOfView_3D = c.default_projectionPerspectiveVerticalFieldOfView_3D;
		this.projectionOrthoWidth_3D = c.default_projectionOrthoWidth_3D;
		this.projectionWidthHeightRatio_3D = c.default_projectionWidthHeightRatio_3D;
		this.projectionFrontCulling_3D = c.default_projectionFrontCulling_3D;
		this.projectionBackCulling_3D = c.default_projectionBackCulling_3D;
		this.cullBackFace_3D = c.default_cullBackFace_3D;
		this.fog_mode_3D = c.default_fog_mode_3D;
		this.fog_color_3D = c.default_fog_color_3D;
		this.fog_start_3D = c.default_fog_start_3D;
		this.fog_end_3D = c.default_fog_end_3D;
		this.fog_density_3D = c.default_fog_density_3D;

		// atom properties
		this.atoms_display = c.default_atoms_display;
		this.atoms_color = c.default_atoms_color;
		this.atoms_font_size_2D = c.default_atoms_font_size_2D;
		this.atoms_font_families_2D = c.default_atoms_font_families_2D.slice(0);
		this.atoms_font_bold_2D = c.default_atoms_font_bold_2D;
		this.atoms_font_italic_2D = c.default_atoms_font_italic_2D;
		this.atoms_circles_2D = c.default_atoms_circles_2D;
		this.atoms_circleDiameter_2D = c.default_atoms_circleDiameter_2D;
		this.atoms_circleBorderWidth_2D = c.default_atoms_circleBorderWidth_2D;
		this.atoms_lonePairDistance_2D = c.default_atoms_lonePairDistance_2D;
		this.atoms_lonePairSpread_2D = c.default_atoms_lonePairSpread_2D;
		this.atoms_lonePairDiameter_2D = c.default_atoms_lonePairDiameter_2D;
		this.atoms_useJMOLColors = c.default_atoms_useJMOLColors;
		this.atoms_usePYMOLColors = c.default_atoms_usePYMOLColors;
		this.atoms_resolution_3D = c.default_atoms_resolution_3D;
		this.atoms_sphereDiameter_3D = c.default_atoms_sphereDiameter_3D;
		this.atoms_useVDWDiameters_3D = c.default_atoms_useVDWDiameters_3D;
		this.atoms_vdwMultiplier_3D = c.default_atoms_vdwMultiplier_3D;
		this.atoms_materialAmbientColor_3D = c.default_atoms_materialAmbientColor_3D;
		this.atoms_materialSpecularColor_3D = c.default_atoms_materialSpecularColor_3D;
		this.atoms_materialShininess_3D = c.default_atoms_materialShininess_3D;
		this.atoms_implicitHydrogens_2D = c.default_atoms_implicitHydrogens_2D;
		this.atoms_displayTerminalCarbonLabels_2D = c.default_atoms_displayTerminalCarbonLabels_2D;
		this.atoms_showHiddenCarbons_2D = c.default_atoms_showHiddenCarbons_2D;
		this.atoms_showAttributedCarbons_2D = c.default_atoms_showAttributedCarbons_2D;
		this.atoms_displayAllCarbonLabels_2D = c.default_atoms_displayAllCarbonLabels_2D;
		this.atoms_nonBondedAsStars_3D = c.default_atoms_nonBondedAsStars_3D;
		this.atoms_displayLabels_3D = c.default_atoms_displayLabels_3D;
		this.atoms_HBlack_2D = c.default_atoms_HBlack_2D;

		// bond properties
		this.bonds_display = c.default_bonds_display;
		this.bonds_color = c.default_bonds_color;
		this.bonds_width_2D = c.default_bonds_width_2D;
		this.bonds_saturationWidth_2D = c.default_bonds_saturationWidth_2D;
		this.bonds_ends_2D = c.default_bonds_ends_2D;
		this.bonds_useJMOLColors = c.default_bonds_useJMOLColors;
		this.bonds_usePYMOLColors = c.default_bonds_usePYMOLColors;
		this.bonds_colorGradient = c.default_bonds_colorGradient;
		this.bonds_saturationAngle_2D = c.default_bonds_saturationAngle_2D;
		this.bonds_symmetrical_2D = c.default_bonds_symmetrical_2D;
		this.bonds_clearOverlaps_2D = c.default_bonds_clearOverlaps_2D;
		this.bonds_overlapClearWidth_2D = c.default_bonds_overlapClearWidth_2D;
		this.bonds_atomLabelBuffer_2D = c.default_bonds_atomLabelBuffer_2D;
		this.bonds_wedgeThickness_2D = c.default_bonds_wedgeThickness_2D;
		this.bonds_hashWidth_2D = c.default_bonds_hashWidth_2D;
		this.bonds_hashSpacing_2D = c.default_bonds_hashSpacing_2D;
		this.bonds_dotSize_2D = c.default_bonds_dotSize_2D;
		this.bonds_lewisStyle_2D = c.default_bonds_lewisStyle_2D;
		this.bonds_showBondOrders_3D = c.default_bonds_showBondOrders_3D;
		this.bonds_resolution_3D = c.default_bonds_resolution_3D;
		this.bonds_renderAsLines_3D = c.default_bonds_renderAsLines_3D;
		this.bonds_cylinderDiameter_3D = c.default_bonds_cylinderDiameter_3D;
		this.bonds_pillHeight_3D = c.default_bonds_pillHeight_3D;
		this.bonds_pillLatitudeResolution_3D = c.default_bonds_pillLatitudeResolution_3D;
		this.bonds_pillLongitudeResolution_3D = c.default_bonds_pillLongitudeResolution_3D;
		this.bonds_pillSpacing_3D = c.default_bonds_pillSpacing_3D;
		this.bonds_pillDiameter_3D = c.default_bonds_pillDiameter_3D;
		this.bonds_materialAmbientColor_3D = c.default_bonds_materialAmbientColor_3D;
		this.bonds_materialSpecularColor_3D = c.default_bonds_materialSpecularColor_3D;
		this.bonds_materialShininess_3D = c.default_bonds_materialShininess_3D;

		// macromolecular properties
		this.proteins_displayRibbon = c.default_proteins_displayRibbon;
		this.proteins_displayBackbone = c.default_proteins_displayBackbone;
		this.proteins_backboneThickness = c.default_proteins_backboneThickness;
		this.proteins_backboneColor = c.default_proteins_backboneColor;
		this.proteins_ribbonCartoonize = c.default_proteins_ribbonCartoonize;
		this.proteins_residueColor = c.default_proteins_residueColor;
		this.proteins_primaryColor = c.default_proteins_primaryColor;
		this.proteins_secondaryColor = c.default_proteins_secondaryColor;
		this.proteins_ribbonCartoonHelixPrimaryColor = c.default_proteins_ribbonCartoonHelixPrimaryColor;
		this.proteins_ribbonCartoonHelixSecondaryColor = c.default_proteins_ribbonCartoonHelixSecondaryColor;
		this.proteins_tubeColor = c.default_proteins_tubeColor;
		this.proteins_tubeResolution_3D = c.default_proteins_tubeResolution_3D;
		this.proteins_displayPipePlank = c.default_proteins_displayPipePlank;
		this.proteins_ribbonCartoonSheetColor = c.default_proteins_ribbonCartoonSheetColor;
		this.proteins_ribbonThickness = c.default_proteins_ribbonThickness;
		this.proteins_tubeThickness = c.default_proteins_tubeThickness;
		this.proteins_plankSheetWidth = c.default_proteins_plankSheetWidth;
		this.proteins_cylinderHelixDiameter = c.default_proteins_cylinderHelixDiameter;
		this.proteins_verticalResolution = c.default_proteins_verticalResolution;
		this.proteins_horizontalResolution = c.default_proteins_horizontalResolution;
		this.proteins_materialAmbientColor_3D = c.default_proteins_materialAmbientColor_3D;
		this.proteins_materialSpecularColor_3D = c.default_proteins_materialSpecularColor_3D;
		this.proteins_materialShininess_3D = c.default_proteins_materialShininess_3D;
		this.macro_displayAtoms = c.default_macro_displayAtoms;
		this.macro_displayBonds = c.default_macro_displayBonds;
		this.macro_atomToLigandDistance = c.default_macro_atomToLigandDistance;
		this.nucleics_display = c.default_nucleics_display;
		this.nucleics_tubeColor = c.default_nucleics_tubeColor;
		this.nucleics_baseColor = c.default_nucleics_baseColor;
		this.nucleics_residueColor = c.default_nucleics_residueColor;
		this.nucleics_tubeThickness = c.default_nucleics_tubeThickness;
		this.nucleics_tubeResolution_3D = c.default_nucleics_tubeResolution_3D;
		this.nucleics_verticalResolution = c.default_nucleics_verticalResolution;
		this.nucleics_materialAmbientColor_3D = c.default_nucleics_materialAmbientColor_3D;
		this.nucleics_materialSpecularColor_3D = c.default_nucleics_materialSpecularColor_3D;
		this.nucleics_materialShininess_3D = c.default_nucleics_materialShininess_3D;
		this.macro_showWater = c.default_macro_showWater;
		this.macro_colorByChain = c.default_macro_colorByChain;
		this.macro_rainbowColors = c.default_macro_rainbowColors.slice(0);

		// surface properties
		this.surfaces_display = c.default_surfaces_display;
		this.surfaces_style = c.default_surfaces_style;
		this.surfaces_color = c.default_surfaces_color;
		this.surfaces_materialAmbientColor_3D = c.default_surfaces_materialAmbientColor_3D;
		this.surfaces_materialSpecularColor_3D = c.default_surfaces_materialSpecularColor_3D;
		this.surfaces_materialShininess_3D = c.default_surfaces_materialShininess_3D;

		// spectrum properties
		this.plots_color = c.default_plots_color;
		this.plots_width = c.default_plots_width;
		this.plots_showIntegration = c.default_plots_showIntegration;
		this.plots_integrationColor = c.default_plots_integrationColor;
		this.plots_integrationLineWidth = c.default_plots_integrationLineWidth;
		this.plots_showGrid = c.default_plots_showGrid;
		this.plots_gridColor = c.default_plots_gridColor;
		this.plots_gridLineWidth = c.default_plots_gridLineWidth;
		this.plots_showYAxis = c.default_plots_showYAxis;
		this.plots_flipXAxis = c.default_plots_flipXAxis;

		// shape properties
		this.text_font_size = c.default_text_font_size;
		this.text_font_families = c.default_text_font_families.slice(0);
		this.text_font_bold = c.default_text_font_bold;
		this.text_font_italic = c.default_text_font_italic;
		this.text_font_stroke_3D = c.default_text_font_stroke_3D;
		this.text_color = c.default_text_color;
		this.shapes_color = c.default_shapes_color;
		this.shapes_lineWidth = c.default_shapes_lineWidth;
		this.shapes_arrowLength_2D = c.default_shapes_arrowLength_2D;
		this.compass_display = c.default_compass_display;
		this.compass_axisXColor_3D = c.default_compass_axisXColor_3D;
		this.compass_axisYColor_3D = c.default_compass_axisYColor_3D;
		this.compass_axisZColor_3D = c.default_compass_axisZColor_3D;
		this.compass_size_3D = c.default_compass_size_3D;
		this.compass_resolution_3D = c.default_compass_resolution_3D;
		this.compass_displayText_3D = c.default_compass_displayText_3D;
		this.compass_type_3D = c.default_compass_type_3D;
		this.measurement_update_3D = c.default_measurement_update_3D;
		this.measurement_angleBands_3D = c.default_measurement_angleBands_3D;
		this.measurement_displayText_3D = c.default_measurement_displayText_3D;
	};
	var _ = structures.VisualSpecifications.prototype;
	_.set3DRepresentation = function(representation) {
		this.atoms_display = true;
		this.bonds_display = true;
		this.bonds_color = '#777777';
		this.atoms_useVDWDiameters_3D = true;
		this.atoms_useJMOLColors = true;
		this.bonds_useJMOLColors = true;
		this.bonds_showBondOrders_3D = true;
		this.bonds_renderAsLines_3D = false;
		if (representation === 'Ball and Stick') {
			this.atoms_vdwMultiplier_3D = .3;
			this.bonds_useJMOLColors = false;
			this.bonds_cylinderDiameter_3D = .3;
			this.bonds_materialAmbientColor_3D = c.default_atoms_materialAmbientColor_3D;
			this.bonds_pillDiameter_3D = .15;
		} else if (representation === 'van der Waals Spheres') {
			this.bonds_display = false;
			this.atoms_vdwMultiplier_3D = 1;
		} else if (representation === 'Stick') {
			this.atoms_useVDWDiameters_3D = false;
			this.bonds_showBondOrders_3D = false;
			this.bonds_cylinderDiameter_3D = this.atoms_sphereDiameter_3D = .8;
			this.bonds_materialAmbientColor_3D = this.atoms_materialAmbientColor_3D;
		} else if (representation === 'Wireframe') {
			this.atoms_useVDWDiameters_3D = false;
			this.bonds_cylinderDiameter_3D = this.bonds_pillDiameter_3D = .05;
			this.atoms_sphereDiameter_3D = .15;
			this.bonds_materialAmbientColor_3D = c.default_atoms_materialAmbientColor_3D;
		} else if (representation === 'Line') {
			this.atoms_display = false;
			this.bonds_renderAsLines_3D = true;
			this.bonds_width_2D = 1;
			this.bonds_cylinderDiameter_3D = .05;
		} else {
			alert('"' + representation + '" is not recognized. Use one of the following strings:\n\n' + '1. Ball and Stick\n' + '2. van der Waals Spheres\n' + '3. Stick\n' + '4. Wireframe\n' + '5. Line\n');
		}
	};

})(ChemDoodle, ChemDoodle.structures, Math);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//
(function(c, ELEMENT, informatics, structures) {
	'use strict';
	informatics.getPointsPerAngstrom = function() {
		return c.default_bondLength_2D / c.default_angstromsPerBondLength;
	};

	informatics.BondDeducer = function() {
	};
	var _ = informatics.BondDeducer.prototype;
	_.margin = 1.1;
	_.deduceCovalentBonds = function(molecule, customPointsPerAngstrom) {
		var pointsPerAngstrom = informatics.getPointsPerAngstrom();
		if (customPointsPerAngstrom) {
			pointsPerAngstrom = customPointsPerAngstrom;
		}
		for ( var i = 0, ii = molecule.atoms.length; i < ii; i++) {
			for ( var j = i + 1; j < ii; j++) {
				var first = molecule.atoms[i];
				var second = molecule.atoms[j];
				if (first.distance3D(second) < (ELEMENT[first.label].covalentRadius + ELEMENT[second.label].covalentRadius) * pointsPerAngstrom * this.margin) {
					molecule.bonds.push(new structures.Bond(first, second, 1));
				}
			}
		}
	};

})(ChemDoodle, ChemDoodle.ELEMENT, ChemDoodle.informatics, ChemDoodle.structures);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//
(function(informatics, structures) {
	'use strict';
	informatics.HydrogenDeducer = function() {
	};
	var _ = informatics.HydrogenDeducer.prototype;
	_.removeHydrogens = function(molecule, removeStereo) {
		var atoms = [];
		var bonds = [];
		for ( var i = 0, ii = molecule.bonds.length; i < ii; i++) {
			var b = molecule.bonds[i];
			var save = b.a1.label !== 'H' && b.a2.label !== 'H';
			if(!save && (!removeStereo && b.stereo !== structures.Bond.STEREO_NONE)){
				save = true;
			}
			if (save) {
				b.a1.tag = true;
				bonds.push(b);
			}else{
				if(b.a1.label === 'H'){
					b.a1.remove = true;
				}
				if(b.a2.label === 'H'){
					b.a2.remove = true;
				}
			}
		}
		for ( var i = 0, ii = molecule.atoms.length; i < ii; i++) {
			var a = molecule.atoms[i];
			if (a.remove) {
				a.remove = undefined;
			}else{
				atoms.push(a);
			}
		}
		molecule.atoms = atoms;
		molecule.bonds = bonds;
	};

})(ChemDoodle.informatics, ChemDoodle.structures);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//
(function(c, informatics, d3) {
	'use strict';
	informatics.MolecularSurfaceGenerator = function() {
	};
	var _ = informatics.MolecularSurfaceGenerator.prototype;
	_.generateSurface = function(molecule, latitudeBands, longitudeBands, probeRadius, atomRadius) {
		return new d3.MolecularSurface(molecule, latitudeBands, longitudeBands, probeRadius, atomRadius);
	};

})(ChemDoodle, ChemDoodle.informatics, ChemDoodle.structures.d3);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//
(function(informatics, structures) {
	'use strict';
	informatics.Splitter = function() {
	};
	var _ = informatics.Splitter.prototype;
	_.split = function(molecule) {
		var mols = [];
		for ( var i = 0, ii = molecule.atoms.length; i < ii; i++) {
			molecule.atoms[i].visited = false;
		}
		for ( var i = 0, ii = molecule.bonds.length; i < ii; i++) {
			molecule.bonds[i].visited = false;
		}
		for ( var i = 0, ii = molecule.atoms.length; i < ii; i++) {
			var a = molecule.atoms[i];
			if (!a.visited) {
				var newMol = new structures.Molecule();
				newMol.atoms.push(a);
				a.visited = true;
				var q = new structures.Queue();
				q.enqueue(a);
				while (!q.isEmpty()) {
					var atom = q.dequeue();
					for ( var j = 0, jj = molecule.bonds.length; j < jj; j++) {
						var b = molecule.bonds[j];
						if (b.contains(atom) && !b.visited) {
							b.visited = true;
							newMol.bonds.push(b);
							var neigh = b.getNeighbor(atom);
							if (!neigh.visited) {
								neigh.visited = true;
								newMol.atoms.push(neigh);
								q.enqueue(neigh);
							}
						}
					}
				}
				mols.push(newMol);
			}
		}
		return mols;
	};

})(ChemDoodle.informatics, ChemDoodle.structures);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//
(function(informatics, io, structures) {
	'use strict';
	informatics.StructureBuilder = function() {
	};
	var _ = informatics.StructureBuilder.prototype;
	_.copy = function(molecule) {
		var json = new io.JSONInterpreter();
		return json.molFrom(json.molTo(molecule));
	};

})(ChemDoodle.informatics, ChemDoodle.io, ChemDoodle.structures);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//
(function(informatics) {
	'use strict';
	informatics._Counter = function() {
	};
	var _ = informatics._Counter.prototype;
	_.value = 0;
	_.molecule = undefined;
	_.setMolecule = function(molecule) {
		this.value = 0;
		this.molecule = molecule;
		if (this.innerCalculate) {
			this.innerCalculate();
		}
	};
})(ChemDoodle.informatics);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//
(function(informatics) {
	'use strict';
	informatics.FrerejacqueNumberCounter = function(molecule) {
		this.setMolecule(molecule);
	};
	var _ = informatics.FrerejacqueNumberCounter.prototype = new informatics._Counter();
	_.innerCalculate = function() {
		this.value = this.molecule.bonds.length - this.molecule.atoms.length + new informatics.NumberOfMoleculesCounter(this.molecule).value;
	};
})(ChemDoodle.informatics);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//
(function(structures, informatics) {
	'use strict';
	informatics.NumberOfMoleculesCounter = function(molecule) {
		this.setMolecule(molecule);
	};
	var _ = informatics.NumberOfMoleculesCounter.prototype = new informatics._Counter();
	_.innerCalculate = function() {
		for ( var i = 0, ii = this.molecule.atoms.length; i < ii; i++) {
			this.molecule.atoms[i].visited = false;
		}
		for ( var i = 0, ii = this.molecule.atoms.length; i < ii; i++) {
			if (!this.molecule.atoms[i].visited) {
				this.value++;
				var q = new structures.Queue();
				this.molecule.atoms[i].visited = true;
				q.enqueue(this.molecule.atoms[i]);
				while (!q.isEmpty()) {
					var atom = q.dequeue();
					for ( var j = 0, jj = this.molecule.bonds.length; j < jj; j++) {
						var b = this.molecule.bonds[j];
						if (b.contains(atom)) {
							var neigh = b.getNeighbor(atom);
							if (!neigh.visited) {
								neigh.visited = true;
								q.enqueue(neigh);
							}
						}
					}
				}
			}
		}
	};
})(ChemDoodle.structures, ChemDoodle.informatics);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(informatics) {
	'use strict';
	informatics._RingFinder = function() {
	};
	var _ = informatics._RingFinder.prototype;
	_.atoms = undefined;
	_.bonds = undefined;
	_.rings = undefined;
	_.reduce = function(molecule) {
		for ( var i = 0, ii = molecule.atoms.length; i < ii; i++) {
			molecule.atoms[i].visited = false;
		}
		for ( var i = 0, ii = molecule.bonds.length; i < ii; i++) {
			molecule.bonds[i].visited = false;
		}
		var cont = true;
		while (cont) {
			cont = false;
			for ( var i = 0, ii = molecule.atoms.length; i < ii; i++) {
				var count = 0;
				var bond;
				for ( var j = 0, jj = molecule.bonds.length; j < jj; j++) {
					if (molecule.bonds[j].contains(molecule.atoms[i]) && !molecule.bonds[j].visited) {
						count++;
						if (count === 2) {
							break;
						}
						bond = molecule.bonds[j];
					}
				}
				if (count === 1) {
					cont = true;
					bond.visited = true;
					molecule.atoms[i].visited = true;
				}
			}
		}
		for ( var i = 0, ii = molecule.atoms.length; i < ii; i++) {
			if (!molecule.atoms[i].visited) {
				this.atoms.push(molecule.atoms[i]);
			}
		}
		for ( var i = 0, ii = molecule.bonds.length; i < ii; i++) {
			if (!molecule.bonds[i].visited) {
				this.bonds.push(molecule.bonds[i]);
			}
		}
		if (this.bonds.length === 0 && this.atoms.length !== 0) {
			this.atoms = [];
		}
	};
	_.setMolecule = function(molecule) {
		this.atoms = [];
		this.bonds = [];
		this.rings = [];
		this.reduce(molecule);
		if (this.atoms.length > 2 && this.innerGetRings) {
			this.innerGetRings();
		}
	};
	_.fuse = function() {
		for ( var i = 0, ii = this.rings.length; i < ii; i++) {
			for ( var j = 0, jj = this.bonds.length; j < jj; j++) {
				if (this.rings[i].atoms.indexOf(this.bonds[j].a1) !== -1 && this.rings[i].atoms.indexOf(this.bonds[j].a2) !== -1) {
					this.rings[i].bonds.push(this.bonds[j]);
				}
			}
		}
	};

})(ChemDoodle.informatics);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//
(function(informatics, structures) {
	'use strict';
	function Finger(a, from) {
		this.atoms = [];
		if (from) {
			for ( var i = 0, ii = from.atoms.length; i < ii; i++) {
				this.atoms[i] = from.atoms[i];
			}
		}
		this.atoms.push(a);
	}
	var _2 = Finger.prototype;
	_2.grow = function(bonds, blockers) {
		var last = this.atoms[this.atoms.length - 1];
		var neighs = [];
		for ( var i = 0, ii = bonds.length; i < ii; i++) {
			if (bonds[i].contains(last)) {
				var neigh = bonds[i].getNeighbor(last);
				if (blockers.indexOf(neigh) === -1) {
					neighs.push(neigh);
				}
			}
		}
		var returning = [];
		for ( var i = 0, ii = neighs.length; i < ii; i++) {
			returning.push(new Finger(neighs[i], this));
		}
		return returning;
	};
	_2.check = function(bonds, finger, a) {
		// check that they dont contain similar parts
		for ( var i = 0, ii = finger.atoms.length - 1; i < ii; i++) {
			if (this.atoms.indexOf(finger.atoms[i]) !== -1) {
				return undefined;
			}
		}
		var ring;
		// check if fingers meet at tips
		if (finger.atoms[finger.atoms.length - 1] === this.atoms[this.atoms.length - 1]) {
			ring = new structures.Ring();
			ring.atoms[0] = a;
			for ( var i = 0, ii = this.atoms.length; i < ii; i++) {
				ring.atoms.push(this.atoms[i]);
			}
			for ( var i = finger.atoms.length - 2; i >= 0; i--) {
				ring.atoms.push(finger.atoms[i]);
			}
		} else {
			// check if fingers meet at bond
			var endbonds = [];
			for ( var i = 0, ii = bonds.length; i < ii; i++) {
				if (bonds[i].contains(finger.atoms[finger.atoms.length - 1])) {
					endbonds.push(bonds[i]);
				}
			}
			for ( var i = 0, ii = endbonds.length; i < ii; i++) {
				if ((finger.atoms.length === 1 || !endbonds[i].contains(finger.atoms[finger.atoms.length - 2])) && endbonds[i].contains(this.atoms[this.atoms.length - 1])) {
					ring = new structures.Ring();
					ring.atoms[0] = a;
					for ( var j = 0, jj = this.atoms.length; j < jj; j++) {
						ring.atoms.push(this.atoms[j]);
					}
					for ( var j = finger.atoms.length - 1; j >= 0; j--) {
						ring.atoms.push(finger.atoms[j]);
					}
					break;
				}
			}
		}
		return ring;
	};

	informatics.EulerFacetRingFinder = function(molecule) {
		this.setMolecule(molecule);
	};
	var _ = informatics.EulerFacetRingFinder.prototype = new informatics._RingFinder();
	_.fingerBreak = 5;
	_.innerGetRings = function() {
		for ( var i = 0, ii = this.atoms.length; i < ii; i++) {
			var neigh = [];
			for ( var j = 0, jj = this.bonds.length; j < jj; j++) {
				if (this.bonds[j].contains(this.atoms[i])) {
					neigh.push(this.bonds[j].getNeighbor(this.atoms[i]));
				}
			}
			for ( var j = 0, jj = neigh.length; j < jj; j++) {
				// weird that i can't optimize this loop without breaking a test
				// case...
				for ( var k = j + 1; k < neigh.length; k++) {
					var fingers = [];
					fingers[0] = new Finger(neigh[j]);
					fingers[1] = new Finger(neigh[k]);
					var blockers = [];
					blockers[0] = this.atoms[i];
					for ( var l = 0, ll = neigh.length; l < ll; l++) {
						if (l !== j && l !== k) {
							blockers.push(neigh[l]);
						}
					}
					var found = [];
					// check for 3 membered ring
					var three = fingers[0].check(this.bonds, fingers[1], this.atoms[i]);
					if (three) {
						found[0] = three;
					}
					while (found.length === 0 && fingers.length > 0 && fingers[0].atoms.length < this.fingerBreak) {
						var newfingers = [];
						for ( var l = 0, ll = fingers.length; l < ll; l++) {
							var adding = fingers[l].grow(this.bonds, blockers);
							for ( var m = 0, mm = adding.length; m < mm; m++) {
								newfingers.push(adding[m]);
							}
						}
						fingers = newfingers;
						for ( var l = 0, ll = fingers.length; l < ll; l++) {
							for ( var m = l + 1; m < ll; m++) {
								var r = fingers[l].check(this.bonds, fingers[m], this.atoms[i]);
								if (r) {
									found.push(r);
								}
							}
						}
						if (found.length === 0) {
							var newBlockers = [];
							for ( var l = 0, ll = blockers.length; l < ll; l++) {
								for ( var m = 0, mm = this.bonds.length; m < mm; m++) {
									if (this.bonds[m].contains(blockers[l])) {
										var neigh = this.bonds[m].getNeighbor(blockers[l]);
										if (blockers.indexOf(neigh) === -1 && newBlockers.indexOf(neigh) === -1) {
											newBlockers.push(neigh);
										}
									}
								}
							}
							for ( var l = 0, ll = newBlockers.length; l < ll; l++) {
								blockers.push(newBlockers[l]);
							}
						}
					}
					if (found.length > 0) {
						// this undefined is required...weird, don't know why
						var use = undefined;
						for ( var l = 0, ll = found.length; l < ll; l++) {
							if (!use || use.atoms.length > found[l].atoms.length) {
								use = found[l];
							}
						}
						var already = false;
						for ( var l = 0, ll = this.rings.length; l < ll; l++) {
							var all = true;
							for ( var m = 0, mm = use.atoms.length; m < mm; m++) {
								if (this.rings[l].atoms.indexOf(use.atoms[m]) === -1) {
									all = false;
									break;
								}
							}
							if (all) {
								already = true;
								break;
							}
						}
						if (!already) {
							this.rings.push(use);
						}
					}
				}
			}
		}
		this.fuse();
	};

})(ChemDoodle.informatics, ChemDoodle.structures);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(informatics) {
	'use strict';
	informatics.SSSRFinder = function(molecule) {
		this.rings = [];
		if (molecule.atoms.length > 0) {
			var frerejacqueNumber = new informatics.FrerejacqueNumberCounter(molecule).value;
			var all = new informatics.EulerFacetRingFinder(molecule).rings;
			all.sort(function(a, b) {
				return a.atoms.length - b.atoms.length;
			});
			for ( var i = 0, ii = molecule.bonds.length; i < ii; i++) {
				molecule.bonds[i].visited = false;
			}
			for ( var i = 0, ii = all.length; i < ii; i++) {
				var use = false;
				for ( var j = 0, jj = all[i].bonds.length; j < jj; j++) {
					if (!all[i].bonds[j].visited) {
						use = true;
						break;
					}
				}
				if (use) {
					for ( var j = 0, jj = all[i].bonds.length; j < jj; j++) {
						all[i].bonds[j].visited = true;
					}
					this.rings.push(all[i]);
				}
				if (this.rings.length === frerejacqueNumber) {
					break;
				}
			}
		}
	};

})(ChemDoodle.informatics);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//
(function(io) {
	'use strict';
	io._Interpreter = function() {
	};
	var _ = io._Interpreter.prototype;
	_.fit = function(data, length, leftAlign) {
		var size = data.length;
		var padding = [];
		for ( var i = 0; i < length - size; i++) {
			padding.push(' ');
		}
		return leftAlign ? data + padding.join('') : padding.join('') + data;
	};

})(ChemDoodle.io);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(c, extensions, io, structures, d3, m, m4, v3) {
	'use strict';
	var whitespaceRegex = /\s+/g;
	var whitespaceAndParenthesisRegex = /\(|\)|\s+/g;
	var whitespaceAndQuoteRegex = /\'|\s+/g;
	var whitespaceAndQuoteAndCommaRegex = /,|\'|\s+/g;
	var leadingWhitespaceRegex = /^\s+/;
	var digitsRegex = /[0-9]/g;
	var digitsSymbolRegex = /[0-9]|\+|\-/g;

	var filter = function(s) {
		return s.length !== 0;
	};

	var hallTranslations = {
		'P' : [],
		'A' : [ [ 0, .5, .5 ] ],
		'B' : [ [ .5, 0, .5 ] ],
		'C' : [ [ .5, .5, 0 ] ],
		'I' : [ [ .5, .5, .5 ] ],
		'R' : [ [ 2 / 3, 1 / 3, 1 / 3 ], [ 1 / 3, 2 / 3, 2 / 3 ] ],
		'S' : [ [ 1 / 3, 1 / 3, 2 / 3 ], [ 2 / 3, 2 / 3, 1 / 3 ] ],
		'T' : [ [ 1 / 3, 2 / 3, 1 / 3 ], [ 2 / 3, 1 / 3, 2 / 3 ] ],
		'F' : [ [ 0, .5, .5 ], [ .5, 0, .5 ], [ .5, .5, 0 ] ]
	};

	var parseTransform = function(s) {
		var displacement = 0;
		var x = 0, y = 0, z = 0;
		var indexx = s.indexOf('x');
		var indexy = s.indexOf('y');
		var indexz = s.indexOf('z');
		if (indexx !== -1) {
			x++;
			if (indexx > 0 && s.charAt(indexx - 1) !== '+') {
				x *= -1;
			}
		}
		if (indexy !== -1) {
			y++;
			if (indexy > 0 && s.charAt(indexy - 1) !== '+') {
				y *= -1;
			}
		}
		if (indexz !== -1) {
			z++;
			if (indexz > 0 && s.charAt(indexz - 1) !== '+') {
				z *= -1;
			}
		}
		if (s.length > 2) {
			var op = '+';
			for ( var i = 0, ii = s.length; i < ii; i++) {
				var l = s.charAt(i);
				if ((l === '-' || l === '/') && (i === s.length - 1 || s.charAt(i + 1).match(digitsRegex))) {
					op = l;
				}
				if (l.match(digitsRegex)) {
					if (op === '+') {
						displacement += parseInt(l);
					} else if (op === '-') {
						displacement -= parseInt(l);
					} else if (op === '/') {
						displacement /= parseInt(l);
					}
				}
			}
		}
		return [ displacement, x, y, z ];
	};

	var generateABC2XYZ = function(a, b, c, alpha, beta, gamma) {
		var d = (m.cos(alpha) - m.cos(gamma) * m.cos(beta)) / m.sin(gamma);
		return [ a, 0, 0, 0, b * m.cos(gamma), b * m.sin(gamma), 0, 0, c * m.cos(beta), c * d, c * m.sqrt(1 - m.pow(m.cos(beta), 2) - d * d), 0, 0, 0, 0, 1 ];
	};

	io.CIFInterpreter = function() {
	};
	var _ = io.CIFInterpreter.prototype = new io._Interpreter();
	_.read = function(content, xSuper, ySuper, zSuper) {
		xSuper = xSuper ? xSuper : 1;
		ySuper = ySuper ? ySuper : 1;
		zSuper = zSuper ? zSuper : 1;
		var molecule = new structures.Molecule();
		if (!content) {
			return molecule;
		}
		var lines = content.split('\n');
		var aLength = 0, bLength = 0, cLength = 0, alphaAngle = 0, betaAngle = 0, gammaAngle = 0;
		var hallClass = 'P';
		var transformLoop;
		var atomLoop;
		var bondLoop;

		var line;
		var shift = true;
		while (lines.length > 0) {
			if (shift) {
				line = lines.shift();
			} else {
				shift = true;
			}
			if (line.length > 0) {
				if (extensions.stringStartsWith(line, '_cell_length_a')) {
					aLength = parseFloat(line.split(whitespaceAndParenthesisRegex)[1]);
				} else if (extensions.stringStartsWith(line, '_cell_length_b')) {
					bLength = parseFloat(line.split(whitespaceAndParenthesisRegex)[1]);
				} else if (extensions.stringStartsWith(line, '_cell_length_c')) {
					cLength = parseFloat(line.split(whitespaceAndParenthesisRegex)[1]);
				} else if (extensions.stringStartsWith(line, '_cell_angle_alpha')) {
					alphaAngle = m.PI * parseFloat(line.split(whitespaceAndParenthesisRegex)[1]) / 180;
				} else if (extensions.stringStartsWith(line, '_cell_angle_beta')) {
					betaAngle = m.PI * parseFloat(line.split(whitespaceAndParenthesisRegex)[1]) / 180;
				} else if (extensions.stringStartsWith(line, '_cell_angle_gamma')) {
					gammaAngle = m.PI * parseFloat(line.split(whitespaceAndParenthesisRegex)[1]) / 180;
				} else if (extensions.stringStartsWith(line, '_symmetry_space_group_name_H-M')) {
					hallClass = line.split(whitespaceAndQuoteRegex)[1];
				} else if (extensions.stringStartsWith(line, 'loop_')) {
					var loop = {
						fields : [],
						lines : []
					};
					var pushingLines = false;
					// keep undefined check here because the line may be an
					// empty string
					while ((line = lines.shift()) !== undefined && !extensions.stringStartsWith(line = line.replace(leadingWhitespaceRegex, ''), 'loop_') && line.length > 0) {
						// remove leading whitespace that may appear in
						// subloop lines ^
						if (extensions.stringStartsWith(line, '_')) {
							if (pushingLines) {
								break;
							}
							loop.fields = loop.fields.concat(line.split(whitespaceRegex).filter(filter));
						} else {
							pushingLines = true;
							loop.lines.push(line);
						}
					}
					if (lines.length !== 0 && (extensions.stringStartsWith(line, 'loop_') || extensions.stringStartsWith(line, '_'))) {
						shift = false;
					}
					if (loop.fields.indexOf('_symmetry_equiv_pos_as_xyz') !== -1 || loop.fields.indexOf('_space_group_symop_operation_xyz') !== -1) {
						transformLoop = loop;
					} else if (loop.fields.indexOf('_atom_site_label') !== -1) {
						atomLoop = loop;
					} else if (loop.fields.indexOf('_geom_bond_atom_site_label_1') !== -1) {
						bondLoop = loop;
					}
				}
			}
		}
		var abc2xyz = generateABC2XYZ(aLength, bLength, cLength, alphaAngle, betaAngle, gammaAngle);
		// internal atom coordinates
		if (atomLoop) {
			var labelIndex = -1, altLabelIndex = -1, xIndex = -1, yIndex = -1, zIndex = -1;
			for ( var i = 0, ii = atomLoop.fields.length; i < ii; i++) {
				var field = atomLoop.fields[i];
				if (field === '_atom_site_type_symbol') {
					labelIndex = i;
				} else if (field === '_atom_site_label') {
					altLabelIndex = i;
				} else if (field === '_atom_site_fract_x') {
					xIndex = i;
				} else if (field === '_atom_site_fract_y') {
					yIndex = i;
				} else if (field === '_atom_site_fract_z') {
					zIndex = i;
				}
			}
			for ( var i = 0, ii = atomLoop.lines.length; i < ii; i++) {
				line = atomLoop.lines[i];
				var tokens = line.split(whitespaceRegex).filter(filter);
				var a = new structures.Atom(tokens[labelIndex === -1 ? altLabelIndex : labelIndex].split(digitsSymbolRegex)[0], parseFloat(tokens[xIndex]), parseFloat(tokens[yIndex]), parseFloat(tokens[zIndex]));
				molecule.atoms.push(a);
				if (altLabelIndex !== -1) {
					a.cifId = tokens[altLabelIndex];
					a.cifPart = 0;
				}
			}
		}
		// transforms, unless bonds are specified
		if (transformLoop && !bondLoop) {
			// assume the index is 0, just incase a different identifier is
			// used
			var symIndex = 0;
			for ( var i = 0, ii = transformLoop.fields.length; i < ii; i++) {
				var field = transformLoop.fields[i];
				if (field === '_symmetry_equiv_pos_as_xyz' || field === '_space_group_symop_operation_xyz') {
					symIndex = i;
				}
			}
			var impliedTranslations = hallTranslations[hallClass];
			var add = [];
			for ( var i = 0, ii = transformLoop.lines.length; i < ii; i++) {
				var parts = transformLoop.lines[i].split(whitespaceAndQuoteAndCommaRegex).filter(filter);
				var multx = parseTransform(parts[symIndex]);
				var multy = parseTransform(parts[symIndex + 1]);
				var multz = parseTransform(parts[symIndex + 2]);
				for ( var j = 0, jj = molecule.atoms.length; j < jj; j++) {
					var a = molecule.atoms[j];
					var x = a.x * multx[1] + a.y * multx[2] + a.z * multx[3] + multx[0];
					var y = a.x * multy[1] + a.y * multy[2] + a.z * multy[3] + multy[0];
					var z = a.x * multz[1] + a.y * multz[2] + a.z * multz[3] + multz[0];
					var copy1 = new structures.Atom(a.label, x, y, z);
					add.push(copy1);
					// cifID could be 0, so check for undefined
					if (a.cifId !== undefined) {
						copy1.cifId = a.cifId;
						copy1.cifPart = i + 1;
					}
					if (impliedTranslations) {
						for ( var k = 0, kk = impliedTranslations.length; k < kk; k++) {
							var trans = impliedTranslations[k];
							var copy2 = new structures.Atom(a.label, x + trans[0], y + trans[1], z + trans[2]);
							add.push(copy2);
							// cifID could be 0, so check for undefined
							if (a.cifId !== undefined) {
								copy2.cifId = a.cifId;
								copy2.cifPart = i + 1;
							}
						}
					}
				}
			}
			// make sure all atoms are within the unit cell
			for ( var i = 0, ii = add.length; i < ii; i++) {
				var a = add[i];
				while (a.x >= 1) {
					a.x--;
				}
				while (a.x < 0) {
					a.x++;
				}
				while (a.y >= 1) {
					a.y--;
				}
				while (a.y < 0) {
					a.y++;
				}
				while (a.z >= 1) {
					a.z--;
				}
				while (a.z < 0) {
					a.z++;
				}
			}
			// remove overlaps
			var noOverlaps = [];
			for ( var i = 0, ii = add.length; i < ii; i++) {
				var overlap = false;
				var a = add[i];
				for ( var j = 0, jj = molecule.atoms.length; j < jj; j++) {
					if (molecule.atoms[j].distance3D(a) < .0001) {
						overlap = true;
						break;
					}
				}
				if (!overlap) {
					for ( var j = 0, jj = noOverlaps.length; j < jj; j++) {
						if (noOverlaps[j].distance3D(a) < .0001) {
							overlap = true;
							break;
						}
					}
					if (!overlap) {
						noOverlaps.push(a);
					}
				}
			}
			// concat arrays
			molecule.atoms = molecule.atoms.concat(noOverlaps);
		}
		// build super cell
		var extras = [];
		for ( var i = 0; i < xSuper; i++) {
			for ( var j = 0; j < ySuper; j++) {
				for ( var k = 0; k < zSuper; k++) {
					if (!(i === 0 && j === 0 && k === 0)) {
						for ( var l = 0, ll = molecule.atoms.length; l < ll; l++) {
							var a = molecule.atoms[l];
							var copy = new structures.Atom(a.label, a.x + i, a.y + j, a.z + k);
							extras.push(copy);
							// cifID could be 0, so check for undefined
							if (a.cifId !== undefined) {
								copy.cifId = a.cifId;
								copy.cifPart = a.cifPart + (transformLoop ? transformLoop.lines.length : 0) + i + j * 10 + k * 100;
							}
						}
					}
				}
			}
		}
		molecule.atoms = molecule.atoms.concat(extras);
		// convert to xyz
		for ( var i = 0, ii = molecule.atoms.length; i < ii; i++) {
			var a = molecule.atoms[i];
			var xyz = m4.multiplyVec3(abc2xyz, [ a.x, a.y, a.z ]);
			a.x = xyz[0];
			a.y = xyz[1];
			a.z = xyz[2];
		}
		// handle bonds
		if (bondLoop) {
			var atom1 = -1, atom2 = -1;
			for ( var i = 0, ii = bondLoop.fields.length; i < ii; i++) {
				var field = bondLoop.fields[i];
				if (field === '_geom_bond_atom_site_label_1') {
					atom1 = i;
				} else if (field === '_geom_bond_atom_site_label_2') {
					atom2 = i;
				}
			}
			for ( var k = 0, kk = bondLoop.lines.length; k < kk; k++) {
				var tokens = bondLoop.lines[k].split(whitespaceRegex).filter(filter);
				var id1 = tokens[atom1];
				var id2 = tokens[atom2];
				for ( var i = 0, ii = molecule.atoms.length; i < ii; i++) {
					for ( var j = i + 1; j < ii; j++) {
						var ai = molecule.atoms[i];
						var aj = molecule.atoms[j];
						if (ai.cifPart !== aj.cifPart) {
							break;
						}
						if (ai.cifId === id1 && aj.cifId === id2 || ai.cifId === id2 && aj.cifId === id1) {
							molecule.bonds.push(new structures.Bond(ai, aj));
						}
					}
				}
			}
		} else {
			new c.informatics.BondDeducer().deduceCovalentBonds(molecule, 1);
		}
		// generate unit cell
		var o = [ -xSuper / 2, -ySuper / 2, -zSuper / 2 ];
		var unitCellVectors = {
			o : m4.multiplyVec3(abc2xyz, o, []),
			x : m4.multiplyVec3(abc2xyz, [ o[0] + 1, o[1], o[2] ]),
			y : m4.multiplyVec3(abc2xyz, [ o[0], o[1] + 1, o[2] ]),
			z : m4.multiplyVec3(abc2xyz, [ o[0], o[1], o[2] + 1 ]),
			xy : m4.multiplyVec3(abc2xyz, [ o[0] + 1, o[1] + 1, o[2] ]),
			xz : m4.multiplyVec3(abc2xyz, [ o[0] + 1, o[1], o[2] + 1 ]),
			yz : m4.multiplyVec3(abc2xyz, [ o[0], o[1] + 1, o[2] + 1 ]),
			xyz : m4.multiplyVec3(abc2xyz, [ o[0] + 1, o[1] + 1, o[2] + 1 ])
		};
		return {molecule:molecule, unitCell: new d3.UnitCell(unitCellVectors)};
	};

	// shortcuts
	var interpreter = new io.CIFInterpreter();
	c.readCIF = function(content, xSuper, ySuper, zSuper) {
		return interpreter.read(content, xSuper, ySuper, zSuper);
	};

})(ChemDoodle, ChemDoodle.extensions, ChemDoodle.io, ChemDoodle.structures, ChemDoodle.structures.d3, Math, ChemDoodle.lib.mat4, ChemDoodle.lib.vec3);
//
// Copyright 2009-2015 iChemLabs, LLC. All rights reserved.
//
(function(c, io, structures, q) {
	'use strict';
	io.CMLInterpreter = function() {
	};
	var _ = io.CMLInterpreter.prototype = new io._Interpreter();
	_.read = function(content) {
		var molecules = [];
		var xml = q.parseXML(content);
		// Possible for multiple CML tags to exist
		var allCml = q(xml).find('cml');
		for (var i = 0, ii = allCml.length; i < ii; i++){
			var allMolecules = q(allCml[i]).find('molecule');
			for (var j = 0, jj = allMolecules.length; j < jj; j++) {
				var currentMolecule = molecules[j] = new structures.Molecule();
				var idmap = [];
				// Don't even bother with atomArrays, there's no point.
				var cmlAtoms = q(allMolecules[j]).find('atom');
				for (var k = 0, kk = cmlAtoms.length; k < kk; k++) {
					var currentCMLAtom = q(cmlAtoms[k]);
					var label = currentCMLAtom.attr('elementType');
					var x, y, z, currentAtom;
					if (currentCMLAtom.attr('x2') == undefined) {
						x = currentCMLAtom.attr('x3');
						y = currentCMLAtom.attr('y3');
						z = currentCMLAtom.attr('z3');
					} else {
						x = currentCMLAtom.attr('x2');
						y = currentCMLAtom.attr('y2');
						z = 0;
					}
					currentAtom = molecules[j].atoms[k] = new structures.Atom(label, x, y, z);
					idmap[k] = currentCMLAtom.attr('id');
					// charge
					if (currentCMLAtom.attr('formalCharge') != undefined) {
						currentAtom.charge = currentCMLAtom.attr('formalCharge');
					}

				}
				var cmlBonds = q(allMolecules[j]).find('bond');
				for (var k = 0, kk = cmlBonds.length; k < kk; k++) {
					var currentCMLBond = q(cmlBonds[k]);
					var atomRefs2 = currentCMLBond.attr('atomRefs2').split(' ');
					var a1, a2, order;
					a1 = currentMolecule.atoms[q.inArray(atomRefs2[0], idmap)];
					a2 = currentMolecule.atoms[q.inArray(atomRefs2[1], idmap)];
					switch(currentCMLBond.attr('order')) {
					case '2':
					case 'D':
						order = 2;
						break;
					case '3':
					case 'T':
						order = 3;
						break;
					case 'A':
						order = 1.5;
						break;
					default:
						order = 1;	 
					}
					var currentBond = molecules[j].bonds[k] = new structures.Bond(a1, a2, order);
					// check stereo... only support W or H
					switch (currentCMLBond.find('bondStereo').text()) {
					case 'W':
						currentBond.stereo = structures.Bond.STEREO_PROTRUDING;
						break;
					case 'H':
						currentBond.stereo = structures.Bond.STEREO_RECESSED;
						break;
					}
				}
			}
		}
		return molecules;
	};
	_.write = function(molecules) {
		var sb = [];
		sb.push('<?xml version="1.0" encoding="UTF-8"?>\n');
		sb.push('<cml convention="conventions:molecular" xmlns="http://www.xml-cml.org/schema" xmlns:conventions="http://www.xml-cml.org/convention/" xmlns:dc="http://purl.org/dc/elements/1.1/">\n');
		// TODO: Metadata
		for (var i = 0, ii = molecules.length; i < ii; i++) {
			sb.push('<molecule id="m'); 
			sb.push(i); 
			sb.push('">');
			sb.push('<atomArray>');
			for (var j = 0, jj = molecules[i].atoms.length; j < jj; j++) {
				var currentAtom = molecules[i].atoms[j];
				sb.push('<atom elementType="'); 
				sb.push(currentAtom.label); 
				sb.push('" id="a');
				sb.push(j); 
				sb.push('" ');
				// Always do 3D coordinates, unless there is a fancy reliable way to tell if the molecule is 2D.
				sb.push('x3="');
				sb.push(currentAtom.x);
				sb.push('" y3="');
				sb.push(currentAtom.y);
				sb.push('" z3="');
				sb.push(currentAtom.z);
				sb.push('" ');
				if (currentAtom.charge != 0) {
					sb.push('formalCharge="');
					sb.push(currentAtom.charge);
					sb.push('" ');
				}
				sb.push('/>');
			}
			sb.push('</atomArray>');
			sb.push('<bondArray>');
			for (var j = 0, jj = molecules[i].bonds.length; j < jj; j++) {
				var currentBond = molecules[i].bonds[j];
				sb.push('<bond atomRefs2="a');
				sb.push(molecules[i].atoms.indexOf(currentBond.a1));
				sb.push(' a');
				sb.push(molecules[i].atoms.indexOf(currentBond.a2));
				sb.push('" order="');
				switch(currentBond.bondOrder) {
				case 1.5:
					sb.push('A');
					break;
				case 1:
				case 2:
				case 3:
					sb.push(currentBond.bondOrder);
					break;
				case 0.5:
				default:
					sb.push('S');
				break;
				}
				sb.push('"/>');
			}
			sb.push('</bondArray>');
			sb.push('</molecule>');
		}
		sb.push('</cml>');
		return sb.join('');
	};

	// shortcuts
	var interpreter = new io.CMLInterpreter();
	c.readCML = function(content) {
		return interpreter.read(content);
	};
	c.writeCML = function(molecules) {
		return interpreter.write(molecules);
	};
	
})(ChemDoodle, ChemDoodle.io, ChemDoodle.structures, ChemDoodle.lib.jQuery);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(c, ELEMENT, io, structures) {
	'use strict';
	io.MOLInterpreter = function() {
	};
	var _ = io.MOLInterpreter.prototype = new io._Interpreter();
	_.read = function(content, multiplier) {
		if (!multiplier) {
			multiplier = c.default_bondLength_2D;
		}
		var molecule = new structures.Molecule();
		if (!content) {
			return molecule;
		}
		var currentTagTokens = content.split('\n');

		var counts = currentTagTokens[3];
		var numAtoms = parseInt(counts.substring(0, 3));
		var numBonds = parseInt(counts.substring(3, 6));

		for ( var i = 0; i < numAtoms; i++) {
			var line = currentTagTokens[4 + i];
			molecule.atoms[i] = new structures.Atom(line.substring(31, 34), parseFloat(line.substring(0, 10)) * multiplier, (multiplier === 1 ? 1 : -1) * parseFloat(line.substring(10, 20)) * multiplier, parseFloat(line.substring(20, 30)) * multiplier);
			var massDif = parseInt(line.substring(34, 36));
			if (massDif !== 0 && ELEMENT[molecule.atoms[i].label]) {
				molecule.atoms[i].mass = ELEMENT[molecule.atoms[i].label].mass + massDif;
			}
			switch (parseInt(line.substring(36, 39))) {
			case 1:
				molecule.atoms[i].charge = 3;
				break;
			case 2:
				molecule.atoms[i].charge = 2;
				break;
			case 3:
				molecule.atoms[i].charge = 1;
				break;
			case 5:
				molecule.atoms[i].charge = -1;
				break;
			case 6:
				molecule.atoms[i].charge = -2;
				break;
			case 7:
				molecule.atoms[i].charge = -3;
				break;
			}
		}
		for ( var i = 0; i < numBonds; i++) {
			var line = currentTagTokens[4 + numAtoms + i];
			var bondOrder = parseInt(line.substring(6, 9));
			var stereo = parseInt(line.substring(9, 12));
			if (bondOrder > 3) {
				switch (bondOrder) {
				case 4:
					bondOrder = 1.5;
					break;
				default:
					bondOrder = 1;
					break;
				}
			}
			var b = new structures.Bond(molecule.atoms[parseInt(line.substring(0, 3)) - 1], molecule.atoms[parseInt(line.substring(3, 6)) - 1], bondOrder);
			switch (stereo) {
			case 3:
				b.stereo = structures.Bond.STEREO_AMBIGUOUS;
				break;
			case 1:
				b.stereo = structures.Bond.STEREO_PROTRUDING;
				break;
			case 6:
				b.stereo = structures.Bond.STEREO_RECESSED;
				break;
			}
			molecule.bonds[i] = b;
		}
		return molecule;
	};
	_.write = function(molecule) {
		var sb = [];
		sb.push('Molecule from ChemDoodle Web Components\n\nhttp://www.ichemlabs.com\n');
		sb.push(this.fit(molecule.atoms.length.toString(), 3));
		sb.push(this.fit(molecule.bonds.length.toString(), 3));
		sb.push('  0  0  0  0            999 V2000\n');
		var p = molecule.getCenter();
		for ( var i = 0, ii = molecule.atoms.length; i < ii; i++) {
			var a = molecule.atoms[i];
			var mass = ' 0';
			if (a.mass !== -1 && ELEMENT[a.label]) {
				var dif = a.mass - ELEMENT[a.label].mass;
				if (dif < 5 && dif > -4) {
					mass = (dif > -1 ? ' ' : '') + dif;
				}
			}
			var charge = '  0';
			if (a.charge !== 0) {
				switch (a.charge) {
				case 3:
					charge = '  1';
					break;
				case 2:
					charge = '  2';
					break;
				case 1:
					charge = '  3';
					break;
				case -1:
					charge = '  5';
					break;
				case -2:
					charge = '  6';
					break;
				case -3:
					charge = '  7';
					break;
				}
			}
			sb.push(this.fit(((a.x - p.x) / c.default_bondLength_2D).toFixed(4), 10));
			sb.push(this.fit((-(a.y - p.y) / c.default_bondLength_2D).toFixed(4), 10));
			sb.push(this.fit((a.z / c.default_bondLength_2D).toFixed(4), 10));
			sb.push(' ');
			sb.push(this.fit(a.label, 3, true));
			sb.push(mass);
			sb.push(charge);
			sb.push('  0  0  0  0\n');
		}
		for ( var i = 0, ii = molecule.bonds.length; i < ii; i++) {
			var b = molecule.bonds[i];
			var stereo = 0;
			if (b.stereo === structures.Bond.STEREO_AMBIGUOUS) {
				stereo = 3;
			} else if (b.stereo === structures.Bond.STEREO_PROTRUDING) {
				stereo = 1;
			} else if (b.stereo === structures.Bond.STEREO_RECESSED) {
				stereo = 6;
			}
			sb.push(this.fit((molecule.atoms.indexOf(b.a1) + 1).toString(), 3));
			sb.push(this.fit((molecule.atoms.indexOf(b.a2) + 1).toString(), 3));
			var btype = b.bondOrder;
			if(btype==1.5){
				btype = 4;
			}else if(btype>3 || btype%1!=0){
				btype = 1;
			}
			sb.push(this.fit(btype, 3));
			sb.push('  ');
			sb.push(stereo);
			sb.push('     0  0\n');
		}
		sb.push('M  END');
		return sb.join('');
	};

	// shortcuts
	var interpreter = new io.MOLInterpreter();
	c.readMOL = function(content, multiplier) {
		return interpreter.read(content, multiplier);
	};
	c.writeMOL = function(mol) {
		return interpreter.write(mol);
	};

})(ChemDoodle, ChemDoodle.ELEMENT, ChemDoodle.io, ChemDoodle.structures);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(c, extensions, io, structures, ELEMENT, trim, m) {
	'use strict';
	function checkContained(residue, set, chainID, index, helix) {
		for ( var j = 0, jj = set.length; j < jj; j++) {
			var check = set[j];
			if (check.id === chainID && index >= check.start && index <= check.end) {
				if (helix) {
					residue.helix = true;
				} else {
					residue.sheet = true;
				}
				if (index === check.end) {
					residue.arrow = true;
				}
				return;
			}
		}
	}
	
	io.PDBInterpreter = function() {
	};
	var _ = io.PDBInterpreter.prototype = new io._Interpreter();
	_.calculateRibbonDistances = false;
	_.deduceResidueBonds = false;
	_.read = function(content, multiplier) {
		var molecule = new structures.Molecule();
		molecule.chains = [];
		if (!content) {
			return molecule;
		}
		var currentTagTokens = content.split('\n');
		if (!multiplier) {
			multiplier = 1;
		}
		var helices = [];
		var sheets = [];
		var lastC;
		var currentChain = [];
		var resatoms = [];
		var atomSerials = [];
		for ( var i = 0, ii = currentTagTokens.length; i < ii; i++) {
			var line = currentTagTokens[i];
			if (extensions.stringStartsWith(line, 'HELIX')) {
				helices.push({
					id : line.substring(19, 20),
					start : parseInt(line.substring(21, 25)),
					end : parseInt(line.substring(33, 37))
				});
			} else if (extensions.stringStartsWith(line, 'SHEET')) {
				sheets.push({
					id : line.substring(21, 22),
					start : parseInt(line.substring(22, 26)),
					end : parseInt(line.substring(33, 37))
				});
			} else if (extensions.stringStartsWith(line, 'ATOM')) {
				var altLoc = line.substring(16, 17);
				if (altLoc === ' ' || altLoc === 'A') {
					var label = trim(line.substring(76, 78));
					if (label.length === 0) {
						var s = trim(line.substring(12, 14));
						if (s === 'HD') {
							label = 'H';
						} else if (s.length > 0) {
							if (s.length > 1) {
								label = s.charAt(0) + s.substring(1).toLowerCase();
							} else {
								label = s;
							}
						}
					}
					var a = new structures.Atom(label, parseFloat(line.substring(30, 38)) * multiplier, parseFloat(line.substring(38, 46)) * multiplier, parseFloat(line.substring(46, 54)) * multiplier);
					a.hetatm = false;
					resatoms.push(a);
					// set up residue
					var resSeq = parseInt(line.substring(22, 26));
					if (currentChain.length === 0) {
						for ( var j = 0; j < 3; j++) {
							var dummyFront = new structures.Residue(-1);
							dummyFront.cp1 = a;
							dummyFront.cp2 = a;
							currentChain.push(dummyFront);
						}
					}
					if (resSeq !== Number.NaN && currentChain[currentChain.length - 1].resSeq !== resSeq) {
						var r = new structures.Residue(resSeq);
						r.name = trim(line.substring(17, 20));
						if (r.name.length === 3) {
							r.name = r.name.substring(0, 1) + r.name.substring(1).toLowerCase();
						} else {
							if (r.name.length === 2 && r.name.charAt(0) === 'D') {
								r.name = r.name.substring(1);
							}
						}
						currentChain.push(r);
						var chainID = line.substring(21, 22);
						checkContained(r, helices, chainID, resSeq, true);
						checkContained(r, sheets, chainID, resSeq, false);
					}
					// end residue setup
					var atomName = trim(line.substring(12, 16));
					var currentResidue = currentChain[currentChain.length - 1];
					if (atomName === 'CA' || atomName === 'P' || atomName === 'O5\'') {
						if (!currentResidue.cp1) {
							currentResidue.cp1 = a;
						}
					} else if (atomName === 'N3' && (currentResidue.name === 'C' || currentResidue.name === 'U' || currentResidue.name === 'T') || atomName === 'N1' && (currentResidue.name === 'A' || currentResidue.name === 'G')) {
						// control points for base platform direction
						currentResidue.cp3 = a;
					} else if (atomName === 'C2') {
						// control points for base platform orientation
						currentResidue.cp4 = a;
					} else if (atomName === 'C4' && (currentResidue.name === 'C' || currentResidue.name === 'U' || currentResidue.name === 'T') || atomName === 'C6' && (currentResidue.name === 'A' || currentResidue.name === 'G')) {
						// control points for base platform orientation
						currentResidue.cp5 = a;
					} else if (atomName === 'O' || atomName === 'C6' && (currentResidue.name === 'C' || currentResidue.name === 'U' || currentResidue.name === 'T') || atomName === 'N9') {
						if (!currentChain[currentChain.length - 1].cp2) {
							if (atomName === 'C6' || atomName === 'N9') {
								lastC = a;
							}
							currentResidue.cp2 = a;
						}
					} else if (atomName === 'C') {
						lastC = a;
					}
				}
			} else if (extensions.stringStartsWith(line, 'HETATM')) {
				var symbol = trim(line.substring(76, 78));
				if (symbol.length === 0) {
					// handle the case where an improperly formatted PDB
					// file states the element label in the atom name column
					symbol = trim(line.substring(12, 16));
				}
				if (symbol.length > 1) {
					symbol = symbol.substring(0, 1) + symbol.substring(1).toLowerCase();
				}
				var het = new structures.Atom(symbol, parseFloat(line.substring(30, 38)) * multiplier, parseFloat(line.substring(38, 46)) * multiplier, parseFloat(line.substring(46, 54)) * multiplier);
				het.hetatm = true;
				var residueName = trim(line.substring(17, 20));
				if (residueName === 'HOH') {
					het.isWater = true;
				}
				molecule.atoms.push(het);
				atomSerials[parseInt(trim(line.substring(6, 11)))] = het;
			} else if (extensions.stringStartsWith(line, 'CONECT')) {
				var oid = parseInt(trim(line.substring(6, 11)));
				if (atomSerials[oid]) {
					var origin = atomSerials[oid];
					for ( var k = 0; k < 4; k++) {
						var next = trim(line.substring(11 + k * 5, 16 + k * 5));
						if (next.length !== 0) {
							var nid = parseInt(next);
							if (atomSerials[nid]) {
								var a2 = atomSerials[nid];
								var found = false;
								for ( var j = 0, jj = molecule.bonds.length; j < jj; j++) {
									var b = molecule.bonds[j];
									if (b.a1 === origin && b.a2 === a2 || b.a1 === a2 && b.a2 === origin) {
										found = true;
										break;
									}
								}
								if (!found) {
									molecule.bonds.push(new structures.Bond(origin, a2));
								}
							}
						}
					}
				}
			} else if (extensions.stringStartsWith(line, 'TER')) {
				this.endChain(molecule, currentChain, lastC, resatoms);
				currentChain = [];
			} else if (extensions.stringStartsWith(line, 'ENDMDL')) {
				break;
			}
		}
		this.endChain(molecule, currentChain, lastC, resatoms);
		if (molecule.bonds.size === 0) {
			new c.informatics.BondDeducer().deduceCovalentBonds(molecule, multiplier);
		}
		if (this.deduceResidueBonds) {
			for ( var i = 0, ii = resatoms.length; i < ii; i++) {
				var max = m.min(ii, i + 20);
				for ( var j = i + 1; j < max; j++) {
					var first = resatoms[i];
					var second = resatoms[j];
					if (first.distance3D(second) < (ELEMENT[first.label].covalentRadius + ELEMENT[second.label].covalentRadius) * 1.1) {
						molecule.bonds.push(new structures.Bond(first, second, 1));
					}
				}
			}
		}
		molecule.atoms = molecule.atoms.concat(resatoms);
		if (this.calculateRibbonDistances) {
			this.calculateDistances(molecule, resatoms);
		}
		return molecule;
	};
	_.endChain = function(molecule, chain, lastC, resatoms) {
		if (chain.length > 0) {
			var last = chain[chain.length - 1];
			if (!last.cp1) {
				last.cp1 = resatoms[resatoms.length - 2];
			}
			if (!last.cp2) {
				last.cp2 = resatoms[resatoms.length - 1];
			}
			for ( var i = 0; i < 4; i++) {
				var dummyEnd = new structures.Residue(-1);
				dummyEnd.cp1 = lastC;
				dummyEnd.cp2 = chain[chain.length - 1].cp2;
				chain.push(dummyEnd);
			}
			molecule.chains.push(chain);
		}
	};
	_.calculateDistances = function(molecule, resatoms) {
		var hetatm = [];
		for ( var i = 0, ii = molecule.atoms.length; i < ii; i++) {
			var a = molecule.atoms[i];
			if (a.hetatm) {
				if (!a.isWater) {
					hetatm.push(a);
				}
			}
		}
		for ( var i = 0, ii = resatoms.length; i < ii; i++) {
			var a = resatoms[i];
			a.closestDistance = Number.POSITIVE_INFINITY;
			if (hetatm.length === 0) {
				a.closestDistance = 0;
			} else {
				for ( var j = 0, jj = hetatm.length; j < jj; j++) {
					a.closestDistance = Math.min(a.closestDistance, a.distance3D(hetatm[j]));
				}
			}
		}
	};

	// shortcuts
	var interpreter = new io.PDBInterpreter();
	c.readPDB = function(content, multiplier) {
		return interpreter.read(content, multiplier);
	};

})(ChemDoodle, ChemDoodle.extensions, ChemDoodle.io, ChemDoodle.structures, ChemDoodle.ELEMENT, ChemDoodle.lib.jQuery.trim, Math);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(c, extensions, io, structures, q) {
	'use strict';
	var SQZ_HASH = {
		'@' : 0,
		'A' : 1,
		'B' : 2,
		'C' : 3,
		'D' : 4,
		'E' : 5,
		'F' : 6,
		'G' : 7,
		'H' : 8,
		'I' : 9,
		'a' : -1,
		'b' : -2,
		'c' : -3,
		'd' : -4,
		'e' : -5,
		'f' : -6,
		'g' : -7,
		'h' : -8,
		'i' : -9
	}, DIF_HASH = {
		'%' : 0,
		'J' : 1,
		'K' : 2,
		'L' : 3,
		'M' : 4,
		'N' : 5,
		'O' : 6,
		'P' : 7,
		'Q' : 8,
		'R' : 9,
		'j' : -1,
		'k' : -2,
		'l' : -3,
		'm' : -4,
		'n' : -5,
		'o' : -6,
		'p' : -7,
		'q' : -8,
		'r' : -9
	}, DUP_HASH = {
		'S' : 1,
		'T' : 2,
		'U' : 3,
		'V' : 4,
		'W' : 5,
		'X' : 6,
		'Y' : 7,
		'Z' : 8,
		's' : 9
	};

	io.JCAMPInterpreter = function() {
	};
	var _ = io.JCAMPInterpreter.prototype = new io._Interpreter();
	_.convertHZ2PPM = false;
	_.read = function(content) {
		this.isBreak = function(c) {
			// some of these arrays may return zero, so check if undefined
			return SQZ_HASH[c] !== undefined || DIF_HASH[c] !== undefined || DUP_HASH[c] !== undefined || c === ' ' || c === '-' || c === '+';
		};
		this.getValue = function(decipher, lastDif) {
			var first = decipher.charAt(0);
			var rest = decipher.substring(1);
			// some of these arrays may return zero, so check if undefined
			if (SQZ_HASH[first] !== undefined) {
				return parseFloat(SQZ_HASH[first] + rest);
			} else if (DIF_HASH[first] !== undefined) {
				return parseFloat(DIF_HASH[first] + rest) + lastDif;
			}
			return parseFloat(rest);
		};
		var spectrum = new structures.Spectrum();
		if (content === undefined || content.length === 0) {
			return spectrum;
		}
		var lines = content.split('\n');
		var sb = [];
		var xLast, xFirst, yFirst, nPoints, xFactor = 1, yFactor = 1, observeFrequency = 1, deltaX = -1, shiftOffsetNum = -1, shiftOffsetVal = -1;
		var recordMeta = true, divideByFrequency = false;
		for ( var i = 0, ii = lines.length; i < ii; i++) {
			var use = lines[i].trim();
			var index = use.indexOf('$$');
			if (index !== -1) {
				use = use.substring(0, index);
			}
			if (sb.length === 0 || !extensions.stringStartsWith(lines[i], '##')) {
				var trimmed = use.trim();
				if (sb.length !== 0 && trimmed.length!==0) {
					sb.push('\n');
				}
				sb.push(trimmed);
			} else {
				var currentRecord = sb.join('');
				if (recordMeta && currentRecord.length < 100) {
					spectrum.metadata.push(currentRecord);
				}
				sb = [ use ];
				if (extensions.stringStartsWith(currentRecord, '##TITLE=')) {
					spectrum.title = currentRecord.substring(8).trim();
				} else if (extensions.stringStartsWith(currentRecord, '##XUNITS=')) {
					spectrum.xUnit = currentRecord.substring(9).trim();
					if (this.convertHZ2PPM && spectrum.xUnit.toUpperCase() === 'HZ') {
						spectrum.xUnit = 'PPM';
						divideByFrequency = true;
					}
				} else if (extensions.stringStartsWith(currentRecord, '##YUNITS=')) {
					spectrum.yUnit = currentRecord.substring(9).trim();
				} else if (extensions.stringStartsWith(currentRecord, '##XYPAIRS=')) {
					// spectrum.yUnit = currentRecord.substring(9).trim();
				} else if (extensions.stringStartsWith(currentRecord, '##FIRSTX=')) {
					xFirst = parseFloat(currentRecord.substring(9).trim());
				} else if (extensions.stringStartsWith(currentRecord, '##LASTX=')) {
					xLast = parseFloat(currentRecord.substring(8).trim());
				} else if (extensions.stringStartsWith(currentRecord, '##FIRSTY=')) {
					yFirst = parseFloat(currentRecord.substring(9).trim());
				} else if (extensions.stringStartsWith(currentRecord, '##NPOINTS=')) {
					nPoints = parseFloat(currentRecord.substring(10).trim());
				} else if (extensions.stringStartsWith(currentRecord, '##XFACTOR=')) {
					xFactor = parseFloat(currentRecord.substring(10).trim());
				} else if (extensions.stringStartsWith(currentRecord, '##YFACTOR=')) {
					yFactor = parseFloat(currentRecord.substring(10).trim());
				} else if (extensions.stringStartsWith(currentRecord, '##DELTAX=')) {
					deltaX = parseFloat(currentRecord.substring(9).trim());
				} else if (extensions.stringStartsWith(currentRecord, '##.OBSERVE FREQUENCY=')) {
					if (this.convertHZ2PPM) {
						observeFrequency = parseFloat(currentRecord.substring(21).trim());
					}
				} else if (extensions.stringStartsWith(currentRecord, '##.SHIFT REFERENCE=')) {
					if (this.convertHZ2PPM) {
						var parts = currentRecord.substring(19).split(',');
						shiftOffsetNum = parseInt(parts[2].trim());
						shiftOffsetVal = parseFloat(parts[3].trim());
					}
				} else if (extensions.stringStartsWith(currentRecord, '##XYDATA=')) {
					if (!divideByFrequency) {
						observeFrequency = 1;
					}
					recordMeta = false;
					var lastWasDif = false;
					var innerLines = currentRecord.split('\n');
					var abscissaSpacing = (xLast - xFirst) / (nPoints - 1);
					// use provided deltaX if determined to be compressed
					// and discontinuous
					if (deltaX !== -1) {
						for ( var j = 1, jj = innerLines.length; j < jj; j++) {
							if (innerLines[j].charAt(0) === '|') {
								abscissaSpacing = deltaX;
								break;
							}
						}
					}
					var lastX = xFirst - abscissaSpacing;
					var lastY = yFirst;
					var lastDif = 0;
					var lastOrdinate;
					for ( var j = 1, jj = innerLines.length; j < jj; j++) {
						var data = [];
						var read = innerLines[j].trim();
						var sb = [];
						var isCompressedDiscontinuous = false;
						for ( var k = 0, kk = read.length; k < kk; k++) {
							if (this.isBreak(read.charAt(k))) {
								if (sb.length > 0 && !(sb.length === 1 && sb[0] === ' ')) {
									data.push(sb.join(''));
								}
								sb = [ read.charAt(k) ];
							} else {
								if (read.charAt(k) === '|') {
									isCompressedDiscontinuous = true;
								} else {
									sb.push(read.charAt(k));
								}
							}
						}
						data.push(sb.join(''));
						lastX = parseFloat(data[0]) * xFactor - abscissaSpacing;
						for ( var k = 1, kk = data.length; k < kk; k++) {
							var decipher = data[k];
							// some of these arrays may return zero, so
							// check if undefined
							if (DUP_HASH[decipher.charAt(0)] !== undefined) {
								// be careful when reading this, to keep
								// spectra efficient, DUPS are actually
								// discarded, except the last y!
								var dup = parseInt(DUP_HASH[decipher.charAt(0)] + decipher.substring(1)) - 1;
								for ( var l = 0; l < dup; l++) {
									lastX += abscissaSpacing;
									lastDif = this.getValue(lastOrdinate, lastDif);
									lastY = lastDif * yFactor;
									count++;
									spectrum.data[spectrum.data.length - 1] = new structures.Point(lastX / observeFrequency, lastY);
								}
							} else {
								// some of these arrays may return zero, so
								// check if undefined
								if (!(SQZ_HASH[decipher.charAt(0)] !== undefined && lastWasDif)) {
									lastWasDif = DIF_HASH[decipher.charAt(0)] !== undefined;
									lastOrdinate = decipher;
									lastX += abscissaSpacing;
									lastDif = this.getValue(decipher, lastDif);
									lastY = lastDif * yFactor;
									count++;
									spectrum.data.push(new structures.Point(lastX / observeFrequency, lastY));
								} else {
									lastY = this.getValue(decipher, lastDif) * yFactor;
									if (isCompressedDiscontinuous) {
										lastX += abscissaSpacing;
										spectrum.data.push(new structures.Point(lastX / observeFrequency, lastY));
									}
								}
							}
						}
					}
					if (shiftOffsetNum !== -1) {
						var dif = shiftOffsetVal - spectrum.data[shiftOffsetNum - 1].x;
						for ( var i = 0, ii = spectrum.data.length; i < ii; i++) {
							spectrum.data[i].x += dif;
						}
					}
				} else if (extensions.stringStartsWith(currentRecord, '##PEAK TABLE=')) {
					recordMeta = false;
					spectrum.continuous = false;
					var innerLines = currentRecord.split('\n');
					var count = 0;
					var reg = /[\s,]+/;
					for ( var j = 1, jj = innerLines.length; j < jj; j++) {
						var items = innerLines[j].split(reg);
						count += items.length / 2;
						for ( var k = 0, kk = items.length; k + 1 < kk; k += 2) {
							spectrum.data.push(new structures.Point(parseFloat(items[k].trim()), parseFloat(items[k + 1].trim())));
						}
					}
				} else if (extensions.stringStartsWith(currentRecord, '##ATOMLIST=')) {
					spectrum.molecule = new structures.Molecule();
					var innerLines = currentRecord.split('\n');
					var reg = /[\s]+/;
					for ( var j = 1, jj = innerLines.length; j < jj; j++) {
						var items = innerLines[j].split(reg);
						spectrum.molecule.atoms.push(new structures.Atom(items[1]));
					}
				} else if (extensions.stringStartsWith(currentRecord, '##BONDLIST=')) {
					var innerLines = currentRecord.split('\n');
					var reg = /[\s]+/;
					for ( var j = 1, jj = innerLines.length; j < jj; j++) {
						var items = innerLines[j].split(reg);
						var order = 1;
						if(items[2]==='D'){
							order = 2;
						}else if(items[2]==='T'){
							order = 3;
						}
						spectrum.molecule.bonds.push(new structures.Bond(spectrum.molecule.atoms[parseInt(items[0])-1], spectrum.molecule.atoms[parseInt(items[1])-1], order));
					}
				} else if (spectrum.molecule && extensions.stringStartsWith(currentRecord, '##XY_RASTER=')) {
					var innerLines = currentRecord.split('\n');
					var reg = /[\s]+/;
					for ( var j = 1, jj = innerLines.length; j < jj; j++) {
						var items = innerLines[j].split(reg);
						var a = spectrum.molecule.atoms[parseInt(items[0])-1];
						a.x = parseInt(items[1]);
						a.y = parseInt(items[2]);
						if(items.length==4){
							a.z = parseInt(items[3]);
						}
					}
					spectrum.molecule.scaleToAverageBondLength(20);
				} else if (extensions.stringStartsWith(currentRecord, '##PEAK ASSIGNMENTS=')) {
					var innerLines = currentRecord.split('\n');
					var reg = /[\s,()<>]+/;
					spectrum.assignments = [];
					for ( var j = 1, jj = innerLines.length; j < jj; j++) {
						var items = innerLines[j].split(reg);
						var x = parseFloat(items[1]);
						var y = parseFloat(items[2]);
						var a = spectrum.molecule.atoms[parseInt(items[3])-1];
						var used = false;
						for(var k = 0, kk = spectrum.assignments.length; k<kk; k++){
							var assign = spectrum.assignments[k];
							if(assign.x === x){
								assign.as.push(a);
								a.assigned = assign;
								used = true;
								break;
							}
						}
						if(!used){
							var assign = {x:x, y:y, as:[a]};
							a.assigned = assign;
							spectrum.assignments.push(assign);
						}
					}
				}
			}
		}
		spectrum.setup();
		return spectrum;
	};
	_.makeStructureSpectrumSet = function(id, content) {
		this.convertHZ2PPM = true;
		var spectrum = this.read(content);
		var mcanvas = new c.ViewerCanvas(id+'_molecule', 200,200);
		mcanvas.specs.atoms_displayTerminalCarbonLabels_2D = true;
		mcanvas.specs.atoms_displayImplicitHydrogens_2D = true;
		mcanvas.mouseout = function(e){
			if(this.molecules.length!==0){
				for(var i = 0, ii = this.molecules[0].atoms.length; i<ii; i++){
					this.molecules[0].atoms[i].isHover = false;
				}
				spectrum.hovered = undefined;
				this.repaint();
				scanvas.repaint();
			}
		};
		mcanvas.touchend = mcanvas.mouseout;
		mcanvas.mousemove = function(e){
			if(this.molecules.length!==0){
				var closest=undefined;
				for(var i = 0, ii = this.molecules[0].atoms.length; i<ii; i++){
					var a = this.molecules[0].atoms[i];
					a.isHover = false;
					if(a.assigned && (closest===undefined || e.p.distance(a)<e.p.distance(closest))){
						closest = a;
					}
				}
				spectrum.hovered = undefined;
				if(e.p.distance(closest)<20){
					for(var i = 0, ii = closest.assigned.as.length; i<ii; i++){
						closest.assigned.as[i].isHover = true;
					}
					scanvas.spectrum.hovered = closest.assigned;
				}
				this.repaint();
				scanvas.repaint();
			}
		};
		mcanvas.touchmove = mcanvas.mousemove;
		mcanvas.drawChildExtras = function(ctx){
			if(this.molecules.length!==0){
				for(var i = 0, ii = this.molecules[0].atoms.length; i<ii; i++){
					this.molecules[0].atoms[i].drawDecorations(ctx);
				}
			}
		};
		var scanvas = new c.ObserverCanvas(id+'_spectrum', 400,200);
		scanvas.specs.plots_showYAxis = false;
		scanvas.specs.plots_flipXAxis = true;
		scanvas.mouseout = function(e){
			if(this.spectrum && this.spectrum.assignments){
				for(var i = 0, ii = mcanvas.molecules[0].atoms.length; i<ii; i++){
					mcanvas.molecules[0].atoms[i].isHover = false;
				}
				this.spectrum.hovered = undefined;
				mcanvas.repaint();
				this.repaint();
			}
		};
		scanvas.touchend = scanvas.mouseout;
		scanvas.mousemove = function(e){
			if(this.spectrum && this.spectrum.assignments){
				var closest=undefined;
				for(var i = 0, ii = mcanvas.molecules[0].atoms.length; i<ii; i++){
					mcanvas.molecules[0].atoms[i].isHover = false;
				}
				this.spectrum.hovered = undefined;
				for(var i = 0, ii = this.spectrum.assignments.length; i<ii; i++){
					var a = this.spectrum.assignments[i];
					if(closest===undefined || Math.abs(this.spectrum.getTransformedX(a.x, this.specs, this.spectrum.memory.width, this.spectrum.memory.offsetLeft)-e.p.x)<Math.abs(this.spectrum.getTransformedX(closest.x, this.specs, this.spectrum.memory.width, this.spectrum.memory.offsetLeft)-e.p.x)){
						closest = a;
					}
				}
				if(Math.abs(this.spectrum.getTransformedX(closest.x, this.specs, this.spectrum.memory.width, this.spectrum.memory.offsetLeft)-e.p.x)<20){
					for(var i = 0, ii = closest.as.length; i<ii; i++){
						closest.as[i].isHover = true;
					}
					this.spectrum.hovered = closest;
				}
				mcanvas.repaint();
				this.repaint();
			}
		};
		scanvas.touchmove = scanvas.mousemove;
		scanvas.drawChildExtras = function(ctx){
			if(this.spectrum && this.spectrum.hovered){
				var x = this.spectrum.getTransformedX(this.spectrum.hovered.x, scanvas.specs, this.spectrum.memory.width, this.spectrum.memory.offsetLeft);
				if (x >= this.spectrum.memory.offsetLeft && x < this.spectrum.memory.width) {
					ctx.save();
					ctx.strokeStyle='#885110';
					ctx.lineWidth = 3;
					ctx.beginPath();
					ctx.moveTo(x, this.spectrum.memory.height - this.spectrum.memory.offsetBottom);
					ctx.lineTo(x, this.spectrum.getTransformedY(this.spectrum.hovered.y, scanvas.specs, this.spectrum.memory.height, this.spectrum.memory.offsetBottom, this.spectrum.memory.offsetTop));
					ctx.stroke();
					ctx.restore();
				}
			}
		};
		if(spectrum){
			scanvas.loadSpectrum(spectrum);
			if(spectrum.molecule){
				mcanvas.loadMolecule(spectrum.molecule);
			}
		}
		return [mcanvas, scanvas];
	};

	// shortcuts
	var interpreter = new io.JCAMPInterpreter();
	interpreter.convertHZ2PPM = true;
	c.readJCAMP = function(content) {
		return interpreter.read(content);
	};
})(ChemDoodle, ChemDoodle.extensions, ChemDoodle.io, ChemDoodle.structures, ChemDoodle.lib.jQuery);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//
(function(c, io, structures, d2, d3, JSON) {
	'use strict';
	io.JSONInterpreter = function() {
	};
	var _ = io.JSONInterpreter.prototype;
	_.contentTo = function(mols, shapes) {
		var count1 = 0, count2 = 0;
		for ( var i = 0, ii = mols.length; i < ii; i++) {
			var mol = mols[i];
			for ( var j = 0, jj = mol.atoms.length; j < jj; j++) {
				mol.atoms[j].tmpid = 'a' + count1++;
			}
			for ( var j = 0, jj = mol.bonds.length; j < jj; j++) {
				mol.bonds[j].tmpid = 'b' + count2++;
			}
		}
		count1 = 0;
		for ( var i = 0, ii = shapes.length; i < ii; i++) {
			shapes[i].tmpid = 's' + count1++;
		}
		var dummy = {};
		if (mols && mols.length > 0) {
			dummy.m = [];
			for ( var i = 0, ii = mols.length; i < ii; i++) {
				dummy.m.push(this.molTo(mols[i]));
			}
		}
		if (shapes && shapes.length > 0) {
			dummy.s = [];
			for ( var i = 0, ii = shapes.length; i < ii; i++) {
				dummy.s.push(this.shapeTo(shapes[i]));
			}
		}
		for ( var i = 0, ii = mols.length; i < ii; i++) {
			var mol = mols[i];
			for ( var j = 0, jj = mol.atoms.length; j < jj; j++) {
				mol.atoms[j].tmpid = undefined;
			}
			for ( var j = 0, jj = mol.bonds.length; j < jj; j++) {
				mol.bonds[j].tmpid = undefined;
			}
		}
		for ( var i = 0, ii = shapes.length; i < ii; i++) {
			shapes[i].tmpid = undefined;
		}
		return dummy;
	};
	_.contentFrom = function(dummy) {
		var obj = {
			molecules : [],
			shapes : []
		};
		if (dummy.m) {
			for ( var i = 0, ii = dummy.m.length; i < ii; i++) {
				obj.molecules.push(this.molFrom(dummy.m[i]));
			}
		}
		if (dummy.s) {
			for ( var i = 0, ii = dummy.s.length; i < ii; i++) {
				obj.shapes.push(this.shapeFrom(dummy.s[i], obj.molecules));
			}
		}
		for ( var i = 0, ii = obj.molecules.length; i < ii; i++) {
			var mol = obj.molecules[i];
			for ( var j = 0, jj = mol.atoms.length; j < jj; j++) {
				mol.atoms[j].tmpid = undefined;
			}
			for ( var j = 0, jj = mol.bonds.length; j < jj; j++) {
				mol.bonds[j].tmpid = undefined;
			}
		}
		for ( var i = 0, ii = obj.shapes.length; i < ii; i++) {
			obj.shapes[i].tmpid = undefined;
		}
		return obj;
	};
	_.queryTo = function(query) {
		var q = {};
		var appendProperty = function(q, p, name, isRange){
			if(p){
				q[name] = {v:isRange?query.outputRange(p.v):p.v, n:p.not};
			}
		};
		if(query.type===structures.Query.TYPE_ATOM){
			appendProperty(q, query.elements, 'as');
			appendProperty(q, query.chirality, '@');
			appendProperty(q, query.aromatic, 'A');
			appendProperty(q, query.charge, 'C', true);
			appendProperty(q, query.hydrogens, 'H', true);
			appendProperty(q, query.ringCount, 'R', true);
			appendProperty(q, query.saturation, 'S');
			appendProperty(q, query.connectivity, 'X', true);
			appendProperty(q, query.connectivityNoH, 'x', true);
		}else{
			appendProperty(q, query.orders, 'bs');
			appendProperty(q, query.stereo, '@');
			appendProperty(q, query.aromatic, 'A');
			appendProperty(q, query.ringCount, 'R', true);
		}
		return q;
	};
	_.molTo = function(mol) {
		var dummy = {
			a : []
		};
		for ( var i = 0, ii = mol.atoms.length; i < ii; i++) {
			var a = mol.atoms[i];
			var da = {
				x : a.x,
				y : a.y
			};
			if (a.tmpid) {
				da.i = a.tmpid;
			}
			if (a.label !== 'C') {
				da.l = a.label;
			}
			if (a.z !== 0) {
				da.z = a.z;
			}
			if (a.charge !== 0) {
				da.c = a.charge;
			}
			if (a.mass !== -1) {
				da.m = a.mass;
			}
			if (a.numRadical !== 0) {
				da.r = a.numRadical;
			}
			if (a.numLonePair !== 0) {
				da.p = a.numLonePair;
			}
			if (a.query) {
				da.q = this.queryTo(a.query);
			}
			dummy.a.push(da);
		}
		if (mol.bonds.length > 0) {
			dummy.b = [];
			for ( var i = 0, ii = mol.bonds.length; i < ii; i++) {
				var b = mol.bonds[i];
				var db = {
					b : mol.atoms.indexOf(b.a1),
					e : mol.atoms.indexOf(b.a2)
				};
				if (b.tmpid) {
					db.i = b.tmpid;
				}
				if (b.bondOrder !== 1) {
					db.o = b.bondOrder;
				}
				if (b.stereo !== structures.Bond.STEREO_NONE) {
					db.s = b.stereo;
				}
				if (b.query) {
					db.q = this.queryTo(b.query);
				}
				dummy.b.push(db);
			}
		}
		return dummy;
	};
	_.queryFrom = function(json) {
		var query = new structures.Query(json.as?structures.Query.TYPE_ATOM:structures.Query.TYPE_BOND);
		var setupProperty = function(query, json, name, isRange){
			if(json){
				query[name] = {};
				query[name].v = isRange?query.parseRange(json.v):json.v;
				if(json.n){
					query[name].not = true;
				}
			}
		};
		if(query.type===structures.Query.TYPE_ATOM){
			setupProperty(query, json.as, 'elements');
			setupProperty(query, json['@'], 'chirality');
			setupProperty(query, json.A, 'aromatic');
			setupProperty(query, json.C, 'charge', true);
			setupProperty(query, json.H, 'hydrogens', true);
			setupProperty(query, json.R, 'ringCount', true);
			setupProperty(query, json.S, 'saturation');
			setupProperty(query, json.X, 'connectivity', true);
			setupProperty(query, json.x, 'connectivityNoH', true);
		}else{
			setupProperty(query, json.bs, 'orders');
			setupProperty(query, json['@'], 'stereo');
			setupProperty(query, json.A, 'aromatic');
			setupProperty(query, json.R, 'ringCount', true);
		}
		return query;
	};
	_.molFrom = function(json) {
		var molecule = new structures.Molecule();
		for ( var i = 0, ii = json.a.length; i < ii; i++) {
			var c = json.a[i];
			var a = new structures.Atom(c.l ? c.l : 'C', c.x, c.y);
			if (c.i) {
				a.tmpid = c.i;
			}
			if (c.z) {
				a.z = c.z;
			}
			if (c.c) {
				a.charge = c.c;
			}
			if (c.m) {
				a.mass = c.m;
			}
			if (c.r) {
				a.numRadical = c.r;
			}
			if (c.p) {
				a.numLonePair = c.p;
			}
			if(c.q){
				a.query = this.queryFrom(c.q);
			}
			// these are booleans or numbers, so check if undefined
			if (c.p_h !== undefined) {
				a.hetatm = c.p_h;
			}
			if (c.p_w !== undefined) {
				a.isWater = c.p_w;
			}
			if (c.p_d !== undefined) {
				a.closestDistance = c.p_d;
			}
			molecule.atoms.push(a);
		}
		if (json.b) {
			for ( var i = 0, ii = json.b.length; i < ii; i++) {
				var c = json.b[i];
				// order can be 0, so check against undefined
				var b = new structures.Bond(molecule.atoms[c.b], molecule.atoms[c.e], c.o === undefined ? 1 : c.o);
				if (c.i) {
					b.tmpid = c.i;
				}
				if (c.s) {
					b.stereo = c.s;
				}
				if(c.q){
					b.query = this.queryFrom(c.q);
				}
				molecule.bonds.push(b);
			}
		}
		return molecule;
	};
	_.shapeTo = function(shape) {
		var dummy = {};
		if (shape.tmpid) {
			dummy.i = shape.tmpid;
		}
		if (shape instanceof d2.Line) {
			dummy.t = 'Line';
			dummy.x1 = shape.p1.x;
			dummy.y1 = shape.p1.y;
			dummy.x2 = shape.p2.x;
			dummy.y2 = shape.p2.y;
			dummy.a = shape.arrowType;
		} else if (shape instanceof d2.Pusher) {
			dummy.t = 'Pusher';
			dummy.o1 = shape.o1.tmpid;
			dummy.o2 = shape.o2.tmpid;
			if (shape.numElectron !== 1) {
				dummy.e = shape.numElectron;
			}
		} else if (shape instanceof d2.Bracket) {
			dummy.t = 'Bracket';
			dummy.x1 = shape.p1.x;
			dummy.y1 = shape.p1.y;
			dummy.x2 = shape.p2.x;
			dummy.y2 = shape.p2.y;
			if (shape.charge !== 0) {
				dummy.c = shape.charge;
			}
			if (shape.mult !== 0) {
				dummy.m = shape.mult;
			}
			if (shape.repeat !== 0) {
				dummy.r = shape.repeat;
			}
		} else if (shape instanceof d3.Distance) {
			dummy.t = 'Distance';
			dummy.a1 = shape.a1.tmpid;
			dummy.a2 = shape.a2.tmpid;
			if (shape.node) {
				dummy.n = shape.node;
				dummy.o = shape.offset;
			}
		} else if (shape instanceof d3.Angle) {
			dummy.t = 'Angle';
			dummy.a1 = shape.a1.tmpid;
			dummy.a2 = shape.a2.tmpid;
			dummy.a3 = shape.a3.tmpid;
		} else if (shape instanceof d3.Torsion) {
			dummy.t = 'Torsion';
			dummy.a1 = shape.a1.tmpid;
			dummy.a2 = shape.a2.tmpid;
			dummy.a3 = shape.a3.tmpid;
			dummy.a4 = shape.a4.tmpid;
		} else if (shape instanceof d3.UnitCell) {
			dummy.t = 'UnitCell';
			for (var p in shape.unitCell) {
		        dummy[p] = shape.unitCell[p];
		    }
		}
		return dummy;
	};
	_.shapeFrom = function(dummy, mols) {
		var shape;
		if (dummy.t === 'Line') {
			shape = new d2.Line(new structures.Point(dummy.x1, dummy.y1), new structures.Point(dummy.x2, dummy.y2));
			shape.arrowType = dummy.a;
		} else if (dummy.t === 'Pusher') {
			var o1, o2;
			for ( var i = 0, ii = mols.length; i < ii; i++) {
				var mol = mols[i];
				for ( var j = 0, jj = mol.atoms.length; j < jj; j++) {
					var a = mol.atoms[j];
					if (a.tmpid === dummy.o1) {
						o1 = a;
					} else if (a.tmpid === dummy.o2) {
						o2 = a;
					}
				}
				for ( var j = 0, jj = mol.bonds.length; j < jj; j++) {
					var b = mol.bonds[j];
					if (b.tmpid === dummy.o1) {
						o1 = b;
					} else if (b.tmpid === dummy.o2) {
						o2 = b;
					}
				}
			}
			shape = new d2.Pusher(o1, o2);
			if (dummy.e) {
				shape.numElectron = dummy.e;
			}
		} else if (dummy.t === 'Bracket') {
			shape = new d2.Bracket(new structures.Point(dummy.x1, dummy.y1), new structures.Point(dummy.x2, dummy.y2));
			if (dummy.c !== undefined) {
				// have to check against undefined as it is an integer that can
				// be 0
				shape.charge = dummy.c;
			}
			if (dummy.m !== undefined) {
				// have to check against undefined as it is an integer that can
				// be 0
				shape.mult = dummy.m;
			}
			if (dummy.r !== undefined) {
				// have to check against undefined as it is an integer that can
				// be 0
				shape.repeat = dummy.r;
			}
		} else if (dummy.t === 'Distance') {
			var a1, a2;
			for ( var i = 0, ii = mols.length; i < ii; i++) {
				var mol = mols[i];
				for ( var j = 0, jj = mol.atoms.length; j < jj; j++) {
					var a = mol.atoms[j];
					if (a.tmpid === dummy.a1) {
						a1 = a;
					} else if (a.tmpid === dummy.a2) {
						a2 = a;
					}
				}
			}
			shape = new d3.Distance(a1, a2, dummy.n, dummy.o);
		} else if (dummy.t === 'Angle') {
			var a1, a2, a3;
			for ( var i = 0, ii = mols.length; i < ii; i++) {
				var mol = mols[i];
				for ( var j = 0, jj = mol.atoms.length; j < jj; j++) {
					var a = mol.atoms[j];
					if (a.tmpid === dummy.a1) {
						a1 = a;
					} else if (a.tmpid === dummy.a2) {
						a2 = a;
					} else if (a.tmpid === dummy.a3) {
						a3 = a;
					}
				}
			}
			shape = new d3.Angle(a1, a2, a3);
		} else if (dummy.t === 'Torsion') {
			var a1, a2, a3, a4;
			for ( var i = 0, ii = mols.length; i < ii; i++) {
				var mol = mols[i];
				for ( var j = 0, jj = mol.atoms.length; j < jj; j++) {
					var a = mol.atoms[j];
					if (a.tmpid === dummy.a1) {
						a1 = a;
					} else if (a.tmpid === dummy.a2) {
						a2 = a;
					} else if (a.tmpid === dummy.a3) {
						a3 = a;
					} else if (a.tmpid === dummy.a4) {
						a4 = a;
					}
				}
			}
			shape = new d3.Torsion(a1, a2, a3, a4);
		} else if (dummy.t === 'UnitCell') {
			var unitCellVectors = {};
			for (var p in dummy) {
				unitCellVectors[p] = dummy[p];
		    }
			shape = new d3.UnitCell(unitCellVectors);
		}
		return shape;
	};
	_.pdbFrom = function(content) {
		var mol = this.molFrom(content.mol);
		mol.findRings = false;
		// mark from JSON to note to algorithms that atoms in chain are not
		// same
		// objects as in atom array
		mol.fromJSON = true;
		mol.chains = this.chainsFrom(content.ribbons);
		return mol;
	};
	_.chainsFrom = function(content) {
		var chains = [];
		for ( var i = 0, ii = content.cs.length; i < ii; i++) {
			var chain = content.cs[i];
			var c = [];
			for ( var j = 0, jj = chain.length; j < jj; j++) {
				var convert = chain[j];
				var r = new structures.Residue();
				r.name = convert.n;
				r.cp1 = new structures.Atom('', convert.x1, convert.y1, convert.z1);
				r.cp2 = new structures.Atom('', convert.x2, convert.y2, convert.z2);
				if (convert.x3) {
					r.cp3 = new structures.Atom('', convert.x3, convert.y3, convert.z3);
					r.cp4 = new structures.Atom('', convert.x4, convert.y4, convert.z4);
					r.cp5 = new structures.Atom('', convert.x5, convert.y5, convert.z5);
				}
				r.helix = convert.h;
				r.sheet = convert.s;
				r.arrow = j > 0 && chain[j - 1].a;
				c.push(r);
			}
			chains.push(c);
		}
		return chains;
	};

	// shortcuts
	var interpreter = new io.JSONInterpreter();
	c.readJSON = function(string) {
		var obj;
		try {
			obj = JSON.parse(string);
		} catch (e) {
			// not json
			return undefined;
		}
		if (obj) {
			if (obj.m || obj.s) {
				return interpreter.contentFrom(obj);
			} else if (obj.a) {
				return obj = {
					molecules : [ interpreter.molFrom(obj) ],
					shapes : []
				};
			} else {
				return obj = {
					molecules : [],
					shapes : []
				};
			}
		}
		return undefined;
	};
	c.writeJSON = function(mols, shapes) {
		return JSON.stringify(interpreter.contentTo(mols, shapes));
	};

})(ChemDoodle, ChemDoodle.io, ChemDoodle.structures, ChemDoodle.structures.d2, ChemDoodle.structures.d3, JSON);
//
// Copyright 2009-2015 iChemLabs, LLC. All rights reserved.
//
(function(c, io, structures) {
	'use strict';
	io.RXNInterpreter = function() {
	};
	var _ = io.RXNInterpreter.prototype = new io._Interpreter();
	_.read = function(content, multiplier) {
		if (!multiplier) {
			multiplier = c.default_bondLength_2D;
		}
		var molecules = [];
		var line;
		if (!content) {
			molecules.push(new structures.Molecule());
			line = new structures.d2.Line(new structures.Point(-20, 0), new structures.Point(20, 0));
		} else {
			var contentTokens = content.split('$MOL\n');
			var headerTokens = contentTokens[0].split('\n');
			var counts = headerTokens[4];
			var numReactants = parseInt(counts.substring(0, 3));
			var numProducts = parseInt(counts.substring(3, 6));
			var currentMolecule = 1;
			var start = 0;
			for ( var i = 0, ii = numReactants + numProducts; i < ii; i++) {
				molecules[i] = c.readMOL(contentTokens[currentMolecule], multiplier);
				var b = molecules[i].getBounds();
				var width = b.maxX - b.minX;
				start -= width + 40;
				currentMolecule++;
			}
			for ( var i = 0, ii = numReactants; i < ii; i++) {
				var b = molecules[i].getBounds();
				var width = b.maxX - b.minX;
				var center = molecules[i].getCenter();
				for ( var j = 0, jj = molecules[i].atoms.length; j < jj; j++) {
					var a = molecules[i].atoms[j];
					a.x += start + (width / 2) - center.x;
					a.y -= center.y;
				}
				start += width + 40;
			}
			line = new structures.d2.Line(new structures.Point(start, 0), new structures.Point(start + 40, 0));
			start += 80;
			for ( var i = numReactants, ii = numReactants + numProducts; i < ii; i++) {
				var b = molecules[i].getBounds();
				var width = b.maxX - b.minX;
				var center = molecules[i].getCenter();
				for ( var j = 0; j < molecules[i].atoms.length; j++) {
					var a = molecules[i].atoms[j];
					a.x += start + (width / 2) - center.x;
					a.y -= center.y;
				}
				start += width + 40;
			}
		}
		line.arrowType = structures.d2.Line.ARROW_SYNTHETIC;
		return {
			'molecules' : molecules,
			'shapes' : [ line ]
		};
	};
	_.write = function(mols, shapes) {
		var molecules = [ [], [] ];
		var ps = undefined;
		if (!mols || !shapes) {
			return;
		}
		for (i = 0, ii = shapes.length; i < ii; i++) {
			if (shapes[i] instanceof structures.d2.Line) {
				ps = shapes[i].getPoints();
				break;
			}
		}
		if (!ps) {
			return '';
		}
		for ( var i = 0, ii = mols.length; i < ii; i++) {
			var center = mols[i].getCenter();
			if (center.x < ps[1].x) {
				molecules[0].push(mols[i]);
			} else {
				molecules[1].push(mols[i]);
			}
		}
		var sb = [];
		sb.push('$RXN\nReaction from ChemDoodle Web Components\n\nhttp://www.ichemlabs.com\n');
		sb.push(this.fit(molecules[0].length.toString(), 3));
		sb.push(this.fit(molecules[1].length.toString(), 3));
		sb.push('\n');
		for ( var i = 0; i < 2; i++) {
			for ( var j = 0, jj = molecules[i].length; j < jj; j++) {
				sb.push('$MOL\n');
				sb.push(c.writeMOL(molecules[i][j]));
				sb.push('\n');
			}
		}
		return sb.join('');
	};

	// shortcuts
	var interpreter = new io.RXNInterpreter();
	c.readRXN = function(content, multiplier) {
		return interpreter.read(content, multiplier);
	};
	c.writeRXN = function(mols, shapes) {
		return interpreter.write(mols, shapes);
	};

})(ChemDoodle, ChemDoodle.io, ChemDoodle.structures);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(c, ELEMENT, SYMBOLS, io, structures, trim) {
	'use strict';
	io.XYZInterpreter = function() {
	};
	var _ = io.XYZInterpreter.prototype = new io._Interpreter();
	_.deduceCovalentBonds = true;
	_.read = function(content) {
		var molecule = new structures.Molecule();
		if (!content) {
			return molecule;
		}
		var lines = content.split('\n');

		var numAtoms = parseInt(trim(lines[0]));

		for ( var i = 0; i < numAtoms; i++) {
			var line = lines[i + 2];
			var tokens = line.split(/\s+/g);
			molecule.atoms[i] = new structures.Atom(isNaN(tokens[0]) ? tokens[0] : SYMBOLS[parseInt(tokens[0]) - 1], parseFloat(tokens[1]), parseFloat(tokens[2]), parseFloat(tokens[3]));
		}
		if (this.deduceCovalentBonds) {
			new c.informatics.BondDeducer().deduceCovalentBonds(molecule, 1);
		}
		return molecule;
	};

	// shortcuts
	var interpreter = new io.XYZInterpreter();
	c.readXYZ = function(content) {
		return interpreter.read(content);
	};

})(ChemDoodle, ChemDoodle.ELEMENT, ChemDoodle.SYMBOLS, ChemDoodle.io, ChemDoodle.structures, ChemDoodle.lib.jQuery.trim);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

ChemDoodle.monitor = (function(featureDetection, q, document) {
	'use strict';
	var m = {};

	m.CANVAS_DRAGGING = undefined;
	m.CANVAS_OVER = undefined;
	m.ALT = false;
	m.SHIFT = false;
	m.META = false;

	if (!featureDetection.supports_touch()) {
		q(document).ready(function() {
			// handles dragging beyond the canvas bounds
			q(document).mousemove(function(e) {
				if (m.CANVAS_DRAGGING) {
					if (m.CANVAS_DRAGGING.drag) {
						m.CANVAS_DRAGGING.prehandleEvent(e);
						m.CANVAS_DRAGGING.drag(e);
					}
				}
			});
			q(document).mouseup(function(e) {
				if (m.CANVAS_DRAGGING && m.CANVAS_DRAGGING !== m.CANVAS_OVER) {
					if (m.CANVAS_DRAGGING.mouseup) {
						m.CANVAS_DRAGGING.prehandleEvent(e);
						m.CANVAS_DRAGGING.mouseup(e);
					}
				}
				m.CANVAS_DRAGGING = undefined;
			});
			// handles modifier keys from a single keyboard
			q(document).keydown(function(e) {
				m.SHIFT = e.shiftKey;
				m.ALT = e.altKey;
				m.META = e.metaKey || e.ctrlKey;
				var affecting = m.CANVAS_OVER;
				if (m.CANVAS_DRAGGING) {
					affecting = m.CANVAS_DRAGGING;
				}
				if (affecting) {
					if (affecting.keydown) {
						affecting.prehandleEvent(e);
						affecting.keydown(e);
					}
				}
			});
			q(document).keypress(function(e) {
				var affecting = m.CANVAS_OVER;
				if (m.CANVAS_DRAGGING) {
					affecting = m.CANVAS_DRAGGING;
				}
				if (affecting) {
					if (affecting.keypress) {
						affecting.prehandleEvent(e);
						affecting.keypress(e);
					}
				}
			});
			q(document).keyup(function(e) {
				m.SHIFT = e.shiftKey;
				m.ALT = e.altKey;
				m.META = e.metaKey || e.ctrlKey;
				var affecting = m.CANVAS_OVER;
				if (m.CANVAS_DRAGGING) {
					affecting = m.CANVAS_DRAGGING;
				}
				if (affecting) {
					if (affecting.keyup) {
						affecting.prehandleEvent(e);
						affecting.keyup(e);
					}
				}
			});
		});
	}

	return m;

})(ChemDoodle.featureDetection, ChemDoodle.lib.jQuery, document);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//
(function(c, featureDetection, math, monitor, structures, q, m, document, window, userAgent) {
	'use strict';
	c._Canvas = function() {
	};
	var _ = c._Canvas.prototype;
	_.molecules = undefined;
	_.shapes = undefined;
	_.emptyMessage = undefined;
	_.image = undefined;
	_.repaint = function() {
		if (this.test) {
			return;
		}
		var canvas = document.getElementById(this.id);
		if (canvas.getContext) {
			var ctx = canvas.getContext('2d');
			if (this.pixelRatio !== 1 && canvas.width === this.width) {
				canvas.width = this.width * this.pixelRatio;
				canvas.height = this.height * this.pixelRatio;
				ctx.scale(this.pixelRatio, this.pixelRatio);
			}
			if (!this.image) {
				if (this.specs.backgroundColor && this.bgCache !== canvas.style.backgroundColor) {
					canvas.style.backgroundColor = this.specs.backgroundColor;
					this.bgCache = canvas.style.backgroundColor;
				}
				// clearRect is correct, but doesn't work as expected on Android
				// ctx.clearRect(0, 0, this.width, this.height);
				ctx.fillStyle = this.specs.backgroundColor;
				ctx.fillRect(0, 0, this.width, this.height);
			} else {
				ctx.drawImage(this.image, 0, 0);
			}
			if (this.innerRepaint) {
				this.innerRepaint(ctx);
			} else {
				if (this.molecules.length !== 0 || this.shapes.length !== 0) {
					ctx.save();
					ctx.translate(this.width / 2, this.height / 2);
					ctx.rotate(this.specs.rotateAngle);
					ctx.scale(this.specs.scale, this.specs.scale);
					ctx.translate(-this.width / 2, -this.height / 2);
					for ( var i = 0, ii = this.molecules.length; i < ii; i++) {
						this.molecules[i].check(true);
						this.molecules[i].draw(ctx, this.specs);
					}
					for ( var i = 0, ii = this.shapes.length; i < ii; i++) {
						this.shapes[i].draw(ctx, this.specs);
					}
					ctx.restore();
				} else if (this.emptyMessage) {
					ctx.fillStyle = '#737683';
					ctx.textAlign = 'center';
					ctx.textBaseline = 'middle';
					ctx.font = '18px Helvetica, Verdana, Arial, Sans-serif';
					ctx.fillText(this.emptyMessage, this.width / 2, this.height / 2);
				}
			}
			if (this.drawChildExtras) {
				this.drawChildExtras(ctx);
			}
		}
	};
	_.resize = function(w, h) {
		var cap = q('#' + this.id);
		cap.attr({
			width : w,
			height : h
		});
		cap.css('width', w);
		cap.css('height', h);
		this.width = w;
		this.height = h;
		if (c._Canvas3D && this instanceof c._Canvas3D) {
			var wu = w;
			var hu = h;
			if (this.pixelRatio !== 1) {
				wu *= this.pixelRatio;
				hu *= this.pixelRatio;
				this.gl.canvas.width = wu;
				this.gl.canvas.height = hu;
			}
			this.gl.viewport(0, 0, wu, hu);
			this.afterLoadContent();
		} else if (this.molecules.length > 0) {
			this.center();
			for ( var i = 0, ii = this.molecules.length; i < ii; i++) {
				this.molecules[i].check();
			}
		}
		this.repaint();
	};
	_.setBackgroundImage = function(path) {
		this.image = new Image(); // Create new Image object
		var me = this;
		this.image.onload = function() {
			me.repaint();
		};
		this.image.src = path; // Set source path
	};
	_.loadMolecule = function(molecule) {
		this.clear();
		this.molecules.push(molecule);
		this.center();
		if (!(c._Canvas3D && this instanceof c._Canvas3D)) {
			molecule.check();
		}
		if (this.afterLoadContent) {
			this.afterLoadContent();
		}
		this.repaint();
	};
	_.loadContent = function(mols, shapes) {
		this.molecules = mols?mols:[];
		this.shapes = shapes?shapes:[];
		this.center();
		if (!(c._Canvas3D && this instanceof c._Canvas3D)) {
			for ( var i = 0, ii = this.molecules.length; i < ii; i++) {
				this.molecules[i].check();
			}
		}
		if (this.afterLoadContent) {
			this.afterLoadContent();
		}
		this.repaint();
	};
	_.addMolecule = function(molecule) {
		this.molecules.push(molecule);
		if (!(c._Canvas3D && this instanceof c._Canvas3D)) {
			molecule.check();
		}
		this.repaint();
	};
	_.removeMolecule = function(mol) {
		this.molecules = q.grep(this.molecules, function(value) {
			return value !== mol;
		});
		this.repaint();
	};
	_.getMolecule = function() {
		return this.molecules.length > 0 ? this.molecules[0] : undefined;
	};
	_.getMolecules = function() {
		return this.molecules;
	};
	_.addShape = function(shape) {
		this.shapes.push(shape);
		this.repaint();
	};
	_.removeShape = function(shape) {
		this.shapes = q.grep(this.shapes, function(value) {
			return value !== shape;
		});
		this.repaint();
	};
	_.getShapes = function() {
		return this.shapes;
	};
	_.clear = function() {
		this.molecules = [];
		this.shapes = [];
		this.specs.scale = 1;
		this.repaint();
	};
	_.center = function() {
		var bounds = this.getContentBounds();
		var center = new structures.Point((this.width - bounds.minX - bounds.maxX) / 2, (this.height - bounds.minY - bounds.maxY) / 2);
		for ( var i = 0, ii = this.molecules.length; i < ii; i++) {
			var mol = this.molecules[i];
			for ( var j = 0, jj = mol.atoms.length; j < jj; j++) {
				mol.atoms[j].add(center);
			}
		}
		for ( var i = 0, ii = this.shapes.length; i < ii; i++) {
			var sps = this.shapes[i].getPoints();
			for ( var j = 0, jj = sps.length; j < jj; j++) {
				sps[j].add(center);
			}
		}
		this.specs.scale = 1;
		var difX = bounds.maxX - bounds.minX;
		var difY = bounds.maxY - bounds.minY;
		if (difX > this.width || difY > this.height) {
			this.specs.scale = m.min(this.width / difX, this.height / difY) * .85;
		}
	};
	_.bondExists = function(a1, a2) {
		for ( var i = 0, ii = this.molecules.length; i < ii; i++) {
			var mol = this.molecules[i];
			for ( var j = 0, jj = mol.bonds.length; j < jj; j++) {
				var b = mol.bonds[j];
				if (b.contains(a1) && b.contains(a2)) {
					return true;
				}
			}
		}
		return false;
	};
	_.getBond = function(a1, a2) {
		for ( var i = 0, ii = this.molecules.length; i < ii; i++) {
			var mol = this.molecules[i];
			for ( var j = 0, jj = mol.bonds.length; j < jj; j++) {
				var b = mol.bonds[j];
				if (b.contains(a1) && b.contains(a2)) {
					return b;
				}
			}
		}
		return undefined;
	};
	_.getMoleculeByAtom = function(a) {
		for ( var i = 0, ii = this.molecules.length; i < ii; i++) {
			var mol = this.molecules[i];
			if (mol.atoms.indexOf(a) !== -1) {
				return mol;
			}
		}
		return undefined;
	};
	_.getAllAtoms = function() {
		var as = [];
		for ( var i = 0, ii = this.molecules.length; i < ii; i++) {
			as = as.concat(this.molecules[i].atoms);
		}
		return as;
	};
	_.getAllPoints = function() {
		var ps = [];
		for ( var i = 0, ii = this.molecules.length; i < ii; i++) {
			ps = ps.concat(this.molecules[i].atoms);
		}
		for ( var i = 0, ii = this.shapes.length; i < ii; i++) {
			ps = ps.concat(this.shapes[i].getPoints());
		}
		return ps;
	};
	_.getContentBounds = function() {
		var bounds = new math.Bounds();
		for ( var i = 0, ii = this.molecules.length; i < ii; i++) {
			bounds.expand(this.molecules[i].getBounds());
		}
		for ( var i = 0, ii = this.shapes.length; i < ii; i++) {
			bounds.expand(this.shapes[i].getBounds());
		}
		return bounds;
	};
	_.create = function(id, width, height) {
		this.id = id;
		this.width = width;
		this.height = height;
		this.molecules = [];
		this.shapes = [];
		if (document.getElementById(id)) {
			var canvas = q('#' + id);
			if (!width) {
				this.width = canvas.attr('width');
			} else {
				canvas.attr('width', width);
			}
			if (!height) {
				this.height = canvas.attr('height');
			} else {
				canvas.attr('height', height);
			}
			// If the canvas is pre-created, make sure that the class attribute
			// is specified.
			canvas.attr('class', 'ChemDoodleWebComponent');
		} else if (!c.featureDetection.supports_canvas_text() && userAgent.indexOf("MSIE") != -1) {
			// Install Google Chrome Frame
			document.writeln('<div style="border: 1px solid black;" width="' + width + '" height="' + height + '">Please install <a href="http://code.google.com/chrome/chromeframe/">Google Chrome Frame</a>, then restart Internet Explorer.</div>');
			return;
		} else {
			document.writeln('<canvas class="ChemDoodleWebComponent" id="' + id + '" width="' + width + '" height="' + height + '" alt="ChemDoodle Web Component">This browser does not support HTML5/Canvas.</canvas>');
		}
		var jqCapsule = q('#' + id);
		jqCapsule.css('width', this.width);
		jqCapsule.css('height', this.height);
		this.pixelRatio = window.devicePixelRatio ? window.devicePixelRatio : 1;
		this.specs = new structures.VisualSpecifications();
		// setup input events
		// make sure prehandle events are only in if statements if handled, so
		// as not to block browser events
		var me = this;
		if (featureDetection.supports_touch()) {
			// for iPhone OS and Android devices (and other mobile browsers that
			// support mobile events)
			jqCapsule.bind('touchstart', function(e) {
				var time = new Date().getTime();
				if (!featureDetection.supports_gesture() && e.originalEvent.touches.length === 2) {
					// on some platforms, like Android, there is no gesture
					// support, so we have to implement it
					var ts = e.originalEvent.touches;
					var p1 = new structures.Point(ts[0].pageX, ts[0].pageY);
					var p2 = new structures.Point(ts[1].pageX, ts[1].pageY);
					me.implementedGestureDist = p1.distance(p2);
					me.implementedGestureAngle = p1.angle(p2);
					if (me.gesturestart) {
						me.prehandleEvent(e);
						me.gesturestart(e);
					}
				}
				if (me.lastTouch && e.originalEvent.touches.length === 1 && (time - me.lastTouch) < 500) {
					if (me.dbltap) {
						me.prehandleEvent(e);
						me.dbltap(e);
					} else if (me.dblclick) {
						me.prehandleEvent(e);
						me.dblclick(e);
					} else if (me.touchstart) {
						me.prehandleEvent(e);
						me.touchstart(e);
					} else if (me.mousedown) {
						me.prehandleEvent(e);
						me.mousedown(e);
					}
				} else if (me.touchstart) {
					me.prehandleEvent(e);
					me.touchstart(e);
					if (this.hold) {
						clearTimeout(this.hold);
					}
					if (this.touchhold) {
						this.hold = setTimeout(function() {
							me.touchhold(e);
						}, 1000);
					}
				} else if (me.mousedown) {
					me.prehandleEvent(e);
					me.mousedown(e);
				}
				me.lastTouch = time;
			});
			jqCapsule.bind('touchmove', function(e) {
				if (this.hold) {
					clearTimeout(this.hold);
					this.hold = undefined;
				}
				if (!featureDetection.supports_gesture() && e.originalEvent.touches.length === 2) {
					// on some platforms, like Android, there is no gesture
					// support, so we have to implement it
					if (me.gesturechange) {
						var ts = e.originalEvent.touches;
						var p1 = new structures.Point(ts[0].pageX, ts[0].pageY);
						var p2 = new structures.Point(ts[1].pageX, ts[1].pageY);
						var newDist = p1.distance(p2);
						var newAngle = p1.angle(p2);
						e.originalEvent.scale = newDist / me.implementedGestureDist;
						e.originalEvent.rotation = 180 * (me.implementedGestureAngle - newAngle) / m.PI;
						me.prehandleEvent(e);
						me.gesturechange(e);
					}
				}
				if (e.originalEvent.touches.length > 1 && me.multitouchmove) {
					var numFingers = e.originalEvent.touches.length;
					me.prehandleEvent(e);
					var center = new structures.Point(-e.offset.left * numFingers, -e.offset.top * numFingers);
					for ( var i = 0; i < numFingers; i++) {
						center.x += e.originalEvent.changedTouches[i].pageX;
						center.y += e.originalEvent.changedTouches[i].pageY;
					}
					center.x /= numFingers;
					center.y /= numFingers;
					e.p = center;
					me.multitouchmove(e, numFingers);
				} else if (me.touchmove) {
					me.prehandleEvent(e);
					me.touchmove(e);
				} else if (me.drag) {
					me.prehandleEvent(e);
					me.drag(e);
				}
			});
			jqCapsule.bind('touchend', function(e) {
				if (this.hold) {
					clearTimeout(this.hold);
					this.hold = undefined;
				}
				if (!featureDetection.supports_gesture() && me.implementedGestureDist) {
					// on some platforms, like Android, there is no gesture
					// support, so we have to implement it
					me.implementedGestureDist = undefined;
					me.implementedGestureAngle = undefined;
					if (me.gestureend) {
						me.prehandleEvent(e);
						me.gestureend(e);
					}
				}
				if (me.touchend) {
					me.prehandleEvent(e);
					me.touchend(e);
				} else if (me.mouseup) {
					me.prehandleEvent(e);
					me.mouseup(e);
				}
				if ((new Date().getTime() - me.lastTouch) < 250) {
					if (me.tap) {
						me.prehandleEvent(e);
						me.tap(e);
					} else if (me.click) {
						me.prehandleEvent(e);
						me.click(e);
					}
				}
			});
			jqCapsule.bind('gesturestart', function(e) {
				if (me.gesturestart) {
					me.prehandleEvent(e);
					me.gesturestart(e);
				}
			});
			jqCapsule.bind('gesturechange', function(e) {
				if (me.gesturechange) {
					me.prehandleEvent(e);
					me.gesturechange(e);
				}
			});
			jqCapsule.bind('gestureend', function(e) {
				if (me.gestureend) {
					me.prehandleEvent(e);
					me.gestureend(e);
				}
			});
		} else {
			// normal events
			// some mobile browsers will simulate mouse events, so do not set
			// these
			// events if mobile, or it will interfere with the handling of touch
			// events
			jqCapsule.click(function(e) {
				switch (e.which) {
				case 1:
					// left mouse button pressed
					if (me.click) {
						me.prehandleEvent(e);
						me.click(e);
					}
					break;
				case 2:
					// middle mouse button pressed
					if (me.middleclick) {
						me.prehandleEvent(e);
						me.middleclick(e);
					}
					break;
				case 3:
					// right mouse button pressed
					if (me.rightclick) {
						me.prehandleEvent(e);
						me.rightclick(e);
					}
					break;
				}
			});
			jqCapsule.dblclick(function(e) {
				if (me.dblclick) {
					me.prehandleEvent(e);
					me.dblclick(e);
				}
			});
			jqCapsule.mousedown(function(e) {
				switch (e.which) {
				case 1:
					// left mouse button pressed
					monitor.CANVAS_DRAGGING = me;
					if (me.mousedown) {
						me.prehandleEvent(e);
						me.mousedown(e);
					}
					break;
				case 2:
					// middle mouse button pressed
					if (me.middlemousedown) {
						me.prehandleEvent(e);
						me.middlemousedown(e);
					}
					break;
				case 3:
					// right mouse button pressed
					if (me.rightmousedown) {
						me.prehandleEvent(e);
						me.rightmousedown(e);
					}
					break;
				}
			});
			jqCapsule.mousemove(function(e) {
				if (!monitor.CANVAS_DRAGGING && me.mousemove) {
					me.prehandleEvent(e);
					me.mousemove(e);
				}
			});
			jqCapsule.mouseout(function(e) {
				monitor.CANVAS_OVER = undefined;
				if (me.mouseout) {
					me.prehandleEvent(e);
					me.mouseout(e);
				}
			});
			jqCapsule.mouseover(function(e) {
				monitor.CANVAS_OVER = me;
				if (me.mouseover) {
					me.prehandleEvent(e);
					me.mouseover(e);
				}
			});
			jqCapsule.mouseup(function(e) {
				switch (e.which) {
				case 1:
					// left mouse button pressed
					if (me.mouseup) {
						me.prehandleEvent(e);
						me.mouseup(e);
					}
					break;
				case 2:
					// middle mouse button pressed
					if (me.middlemouseup) {
						me.prehandleEvent(e);
						me.middlemouseup(e);
					}
					break;
				case 3:
					// right mouse button pressed
					if (me.rightmouseup) {
						me.prehandleEvent(e);
						me.rightmouseup(e);
					}
					break;
				}
			});
			jqCapsule.mousewheel(function(e, delta) {
				if (me.mousewheel) {
					me.prehandleEvent(e);
					me.mousewheel(e, delta);
				}
			});
		}
		if (this.subCreate) {
			this.subCreate();
		}
	};
	_.prehandleEvent = function(e) {
		if (e.originalEvent.changedTouches) {
			e.pageX = e.originalEvent.changedTouches[0].pageX;
			e.pageY = e.originalEvent.changedTouches[0].pageY;
		}
		e.preventDefault();
		e.offset = q('#' + this.id).offset();
		e.p = new structures.Point(e.pageX - e.offset.left, e.pageY - e.offset.top);
	};
	
})(ChemDoodle, ChemDoodle.featureDetection, ChemDoodle.math, ChemDoodle.monitor, ChemDoodle.structures, ChemDoodle.lib.jQuery, Math, document, window, navigator.userAgent);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(c, animations) {
	'use strict';
	c._AnimatorCanvas = function(id, width, height) {
		if (id) {
			this.create(id, width, height);
		}
	};
	var _ = c._AnimatorCanvas.prototype = new c._Canvas();
	_.timeout = 33;
	_.startAnimation = function() {
		this.stopAnimation();
		this.lastTime = new Date().getTime();
		var me = this;
		if (this.nextFrame) {
			this.handle = animations.requestInterval(function() {
				// advance clock
				var timeNow = new Date().getTime();
				// update and repaint
				me.nextFrame(timeNow - me.lastTime);
				me.repaint();
				me.lastTime = timeNow;
			}, this.timeout);
		}
	};
	_.stopAnimation = function() {
		if (this.handle) {
			animations.clearRequestInterval(this.handle);
			this.handle = undefined;
		}
	};
	_.isRunning = function() {
		// must compare to undefined here to return a boolean
		return this.handle !== undefined;
	};

})(ChemDoodle, ChemDoodle.animations);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(c, document) {
	'use strict';
	c.FileCanvas = function(id, width, height, action) {
		if (id) {
			this.create(id, width, height);
		}
		var form = '<br><form name="FileForm" enctype="multipart/form-data" method="POST" action="' + action + '" target="HiddenFileFrame"><input type="file" name="f" /><input type="submit" name="submitbutton" value="Show File" /></form><iframe id="HFF-' + id + '" name="HiddenFileFrame" height="0" width="0" style="display:none;" onLoad="GetMolFromFrame(\'HFF-' + id + '\', ' + id + ')"></iframe>';
		document.writeln(form);
		this.emptyMessage = 'Click below to load file';
		this.repaint();
	};
	c.FileCanvas.prototype = new c._Canvas();

})(ChemDoodle, document);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(c) {
	'use strict';
	c.HyperlinkCanvas = function(id, width, height, urlOrFunction, color, size) {
		if (id) {
			this.create(id, width, height);
		}
		this.urlOrFunction = urlOrFunction;
		this.color = color ? color : 'blue';
		this.size = size ? size : 2;
	};
	var _ = c.HyperlinkCanvas.prototype = new c._Canvas();
	_.openInNewWindow = true;
	_.hoverImage = undefined;
	_.drawChildExtras = function(ctx) {
		if (this.e) {
			if (this.hoverImage) {
				ctx.drawImage(this.hoverImage, 0, 0);
			} else {
				ctx.strokeStyle = this.color;
				ctx.lineWidth = this.size * 2;
				ctx.strokeRect(0, 0, this.width, this.height);
			}
		}
	};
	_.setHoverImage = function(url) {
		this.hoverImage = new Image();
		this.hoverImage.src = url;
	};
	_.click = function(p) {
		this.e = undefined;
		this.repaint();
		if (this.urlOrFunction instanceof Function) {
			this.urlOrFunction();
		} else {
			if (this.openInNewWindow) {
				window.open(this.urlOrFunction);
			} else {
				location.href = this.urlOrFunction;
			}
		}
	};
	_.mouseout = function(e) {
		this.e = undefined;
		this.repaint();
	};
	_.mouseover = function(e) {
		this.e = e;
		this.repaint();
	};

})(ChemDoodle);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(c, iChemLabs, q, document) {
	'use strict';
	c.MolGrabberCanvas = function(id, width, height) {
		if (id) {
			this.create(id, width, height);
		}
		var sb = [];
		sb.push('<br><input type="text" id="');
		sb.push(id);
		sb.push('_query" size="32" value="" />');
		sb.push('<br><nobr>');
		sb.push('<select id="');
		sb.push(id);
		sb.push('_select">');
		sb.push('<option value="chemexper">ChemExper');
		sb.push('<option value="chemspider">ChemSpider');
		sb.push('<option value="pubchem" selected>PubChem');
		sb.push('</select>');
		sb.push('<button id="');
		sb.push(id);
		sb.push('_submit">Show Molecule</button>');
		sb.push('</nobr>');

		// Don't use document.writeln here, it breaks the whole page after
		// document is closed.
		document.getElementById(id);
		var canvas = q('#' + id);
		canvas.after(sb.join(''));

		var self = this;
		q('#' + id + '_submit').click(function() {
			self.search();
		});
		q('#' + id + '_query').keypress(function(e) {
			if (e.which === 13) {
				self.search();
			}
		});
		this.emptyMessage = 'Enter search term below';
		this.repaint();
	};
	var _ = c.MolGrabberCanvas.prototype = new c._Canvas();
	_.setSearchTerm = function(term) {
		q('#' + this.id + '_query').val(term);
		this.search();
	};
	_.search = function() {
		this.emptyMessage = 'Searching...';
		this.clear();
		var self = this;
		iChemLabs.getMoleculeFromDatabase(q('#' + this.id + '_query').val(), {
			database : q('#' + this.id + '_select').val()
		}, function(mol) {
			self.loadMolecule(mol);
		});
	};

})(ChemDoodle, ChemDoodle.iChemLabs, ChemDoodle.lib.jQuery, document);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(c, m, m4) {
	'use strict';
	// keep these declaration outside the loop to avoid overhead
	var matrix = [];
	var xAxis = [ 1, 0, 0 ];
	var yAxis = [ 0, 1, 0 ];
	var zAxis = [ 0, 0, 1 ];

	c.RotatorCanvas = function(id, width, height, rotate3D) {
		if (id) {
			this.create(id, width, height);
		}
		this.rotate3D = rotate3D;
	};
	var _ = c.RotatorCanvas.prototype = new c._AnimatorCanvas();
	var increment = m.PI / 15;
	_.xIncrement = increment;
	_.yIncrement = increment;
	_.zIncrement = increment;
	_.nextFrame = function(delta) {
		if (this.molecules.length === 0 && this.shapes.length === 0) {
			this.stopAnimation();
			return;
		}
		var change = delta / 1000;
		if (this.rotate3D) {
			m4.identity(matrix);
			m4.rotate(matrix, this.xIncrement * change, xAxis);
			m4.rotate(matrix, this.yIncrement * change, yAxis);
			m4.rotate(matrix, this.zIncrement * change, zAxis);
			for ( var i = 0, ii = this.molecules.length; i < ii; i++) {
				var m = this.molecules[i];
				for ( var j = 0, jj = m.atoms.length; j < jj; j++) {
					var a = m.atoms[j];
					var p = [ a.x - this.width / 2, a.y - this.height / 2, a.z ];
					m4.multiplyVec3(matrix, p);
					a.x = p[0] + this.width / 2;
					a.y = p[1] + this.height / 2;
					a.z = p[2];
				}
				for ( var j = 0, jj = m.rings.length; j < jj; j++) {
					m.rings[j].center = m.rings[j].getCenter();
				}
				if (this.specs.atoms_display && this.specs.atoms_circles_2D) {
					m.sortAtomsByZ();
				}
				if (this.specs.bonds_display && this.specs.bonds_clearOverlaps_2D) {
					m.sortBondsByZ();
				}
			}
			for ( var i = 0, ii = this.shapes.length; i < ii; i++) {
				var sps = this.shapes[i].getPoints();
				for ( var j = 0, jj = sps.length; j < jj; j++) {
					var a = sps[j];
					var p = [ a.x - this.width / 2, a.y - this.height / 2, 0 ];
					m4.multiplyVec3(matrix, p);
					a.x = p[0] + this.width / 2;
					a.y = p[1] + this.height / 2;
				}
			}
		} else {
			this.specs.rotateAngle += this.zIncrement * change;
		}
	};
	_.dblclick = function(e) {
		if (this.isRunning()) {
			this.stopAnimation();
		} else {
			this.startAnimation();
		}
	};

})(ChemDoodle, Math, ChemDoodle.lib.mat4);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(c, animations, math) {
	'use strict';
	c.SlideshowCanvas = function(id, width, height) {
		if (id) {
			this.create(id, width, height);
		}
	};
	var _ = c.SlideshowCanvas.prototype = new c._AnimatorCanvas();
	_.frames = [];
	_.curIndex = 0;
	_.timeout = 5000;
	_.alpha = 0;
	_.innerHandle = undefined;
	_.phase = 0;
	_.drawChildExtras = function(ctx) {
		var rgb = math.getRGB(this.specs.backgroundColor, 255);
		ctx.fillStyle = 'rgba(' + rgb[0] + ', ' + rgb[1] + ', ' + rgb[2] + ', ' + this.alpha + ')';
		ctx.fillRect(0, 0, this.width, this.height);
	};
	_.nextFrame = function(delta) {
		if (this.frames.length === 0) {
			this.stopAnimation();
			return;
		}
		this.phase = 0;
		var me = this;
		var count = 1;
		this.innerHandle = setInterval(function() {
			me.alpha = count / 15;
			me.repaint();
			if (count === 15) {
				me.breakInnerHandle();
			}
			count++;
		}, 33);
	};
	_.breakInnerHandle = function() {
		if (this.innerHandle) {
			clearInterval(this.innerHandle);
			this.innerHandle = undefined;
		}
		if (this.phase === 0) {
			this.curIndex++;
			if (this.curIndex > this.frames.length - 1) {
				this.curIndex = 0;
			}
			this.alpha = 1;
			var f = this.frames[this.curIndex];
			this.loadContent(f.mols, f.shapes);
			this.phase = 1;
			var me = this;
			var count = 1;
			this.innerHandle = setInterval(function() {
				me.alpha = (15 - count) / 15;
				me.repaint();
				if (count === 15) {
					me.breakInnerHandle();
				}
				count++;
			}, 33);
		} else if (this.phase === 1) {
			this.alpha = 0;
			this.repaint();
		}
	};
	_.addFrame = function(molecules, shapes) {
		if (this.frames.length === 0) {
			this.loadContent(molecules, shapes);
		}
		this.frames.push({
			mols : molecules,
			shapes : shapes
		});
	};

})(ChemDoodle, ChemDoodle.animations, ChemDoodle.math);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(c, monitor, structures, m, m4) {
	'use strict';
	c.TransformCanvas = function(id, width, height, rotate3D) {
		if (id) {
			this.create(id, width, height);
		}
		this.rotate3D = rotate3D;
	};
	var _ = c.TransformCanvas.prototype = new c._Canvas();
	_.lastPoint = undefined;
	_.rotationMultMod = 1.3;
	_.lastPinchScale = 1;
	_.lastGestureRotate = 0;
	_.mousedown = function(e) {
		this.lastPoint = e.p;
	};
	_.dblclick = function(e) {
		// center structure
		this.center();
		this.repaint();
	};
	_.drag = function(e) {
		if (!this.lastPoint.multi) {
			if (monitor.ALT) {
				var t = new structures.Point(e.p.x, e.p.y);
				t.sub(this.lastPoint);
				for ( var i = 0, ii = this.molecules.length; i < ii; i++) {
					var mol = this.molecules[i];
					for ( var j = 0, jj = mol.atoms.length; j < jj; j++) {
						mol.atoms[j].add(t);
					}
					mol.check();
				}
				for ( var i = 0, ii = this.shapes.length; i < ii; i++) {
					var sps = this.shapes[i].getPoints();
					for ( var j = 0, jj = sps.length; j < jj; j++) {
						sps[j].add(t);
					}
				}
				this.lastPoint = e.p;
				this.repaint();
			} else {
				if (this.rotate3D === true) {
					var diameter = m.max(this.width / 4, this.height / 4);
					var difx = e.p.x - this.lastPoint.x;
					var dify = e.p.y - this.lastPoint.y;
					var yIncrement = difx / diameter * this.rotationMultMod;
					var xIncrement = -dify / diameter * this.rotationMultMod;
					var matrix = [];
					m4.identity(matrix);
					m4.rotate(matrix, xIncrement, [ 1, 0, 0 ]);
					m4.rotate(matrix, yIncrement, [ 0, 1, 0 ]);
					for ( var i = 0, ii = this.molecules.length; i < ii; i++) {
						var mol = this.molecules[i];
						for ( var j = 0, jj = mol.atoms.length; j < jj; j++) {
							var a = mol.atoms[j];
							var p = [ a.x - this.width / 2, a.y - this.height / 2, a.z ];
							m4.multiplyVec3(matrix, p);
							a.x = p[0] + this.width / 2;
							a.y = p[1] + this.height / 2;
							a.z = p[2];
						}
						for ( var i = 0, ii = mol.rings.length; i < ii; i++) {
							mol.rings[i].center = mol.rings[i].getCenter();
						}
						this.lastPoint = e.p;
						if (this.specs.atoms_display && this.specs.atoms_circles_2D) {
							mol.sortAtomsByZ();
						}
						if (this.specs.bonds_display && this.specs.bonds_clearOverlaps_2D) {
							mol.sortBondsByZ();
						}
					}
					this.repaint();
				} else {
					var center = new structures.Point(this.width / 2, this.height / 2);
					var before = center.angle(this.lastPoint);
					var after = center.angle(e.p);
					this.specs.rotateAngle -= (after - before);
					this.lastPoint = e.p;
					this.repaint();
				}
			}
		}
	};
	_.mousewheel = function(e, delta) {
		this.specs.scale += delta / 50;
		if (this.specs.scale < .01) {
			this.specs.scale = .01;
		}
		this.repaint();
	};
	_.multitouchmove = function(e, numFingers) {
		if (numFingers === 2) {
			if (this.lastPoint.multi) {
				var t = new structures.Point(e.p.x, e.p.y);
				t.sub(this.lastPoint);
				for ( var i = 0, ii = this.molecules.length; i < ii; i++) {
					var m = this.molecules[i];
					for ( var j = 0, jj = m.atoms.length; j < jj; j++) {
						m.atoms[j].add(t);
					}
					m.check();
				}
				for ( var i = 0, ii = this.shapes.length; i < ii; i++) {
					var sps = this.shapes[i].getPoints();
					for ( var j = 0, jj = sps.length; j < jj; j++) {
						sps[j].add(t);
					}
				}
				this.lastPoint = e.p;
				this.lastPoint.multi = true;
				this.repaint();
			} else {
				this.lastPoint = e.p;
				this.lastPoint.multi = true;
			}
		}
	};
	_.gesturechange = function(e) {
		if (e.originalEvent.scale - this.lastPinchScale !== 0) {
			this.specs.scale *= e.originalEvent.scale / this.lastPinchScale;
			if (this.specs.scale < .01) {
				this.specs.scale = .01;
			}
			this.lastPinchScale = e.originalEvent.scale;
		}
		if (this.lastGestureRotate - e.originalEvent.rotation !== 0) {
			var rot = (this.lastGestureRotate - e.originalEvent.rotation) / 180 * m.PI;
			var center = new structures.Point(this.width / 2, this.height / 2);
			for ( var i = 0, ii = this.molecules.length; i < ii; i++) {
				var mol = this.molecules[i];
				for ( var j = 0, jj = mol.atoms.length; j < jj; j++) {
					var a = mol.atoms[j];
					var dist = center.distance(a);
					var angle = center.angle(a) + rot;
					a.x = center.x + dist * m.cos(angle);
					a.y = center.y - dist * m.sin(angle);
				}
				mol.check();
			}
			this.lastGestureRotate = e.originalEvent.rotation;
		}
		this.repaint();
	};
	_.gestureend = function(e) {
		this.lastPinchScale = 1;
		this.lastGestureRotate = 0;
	};

})(ChemDoodle, ChemDoodle.monitor, ChemDoodle.structures, Math, ChemDoodle.lib.mat4);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(c) {
	'use strict';
	c.ViewerCanvas = function(id, width, height) {
		if (id) {
			this.create(id, width, height);
		}
	};
	c.ViewerCanvas.prototype = new c._Canvas();

})(ChemDoodle);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(c, document) {
	'use strict';
	c._SpectrumCanvas = function(id, width, height) {
		if (id) {
			this.create(id, width, height);
		}
	};
	var _ = c._SpectrumCanvas.prototype = new c._Canvas();
	_.spectrum = undefined;
	_.emptyMessage = 'No Spectrum Loaded or Recognized';
	_.loadMolecule = undefined;
	_.getMolecule = undefined;
	_.innerRepaint = function(ctx) {
		if (this.spectrum && this.spectrum.data.length > 0) {
			this.spectrum.draw(ctx, this.specs, this.width, this.height);
		} else if (this.emptyMessage) {
			ctx.fillStyle = '#737683';
			ctx.textAlign = 'center';
			ctx.textBaseline = 'middle';
			ctx.font = '18px Helvetica, Verdana, Arial, Sans-serif';
			ctx.fillText(this.emptyMessage, this.width / 2, this.height / 2);
		}
	};
	_.loadSpectrum = function(spectrum) {
		this.spectrum = spectrum;
		this.repaint();
	};
	_.getSpectrum = function() {
		return this.spectrum;
	};
	_.getSpectrumCoordinates = function(x, y) {
		return spectrum.getInternalCoordinates(x, y, this.width, this.height);
	};

})(ChemDoodle, document);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(c) {
	'use strict';
	c.ObserverCanvas = function(id, width, height) {
		if (id) {
			this.create(id, width, height);
		}
	};
	c.ObserverCanvas.prototype = new c._SpectrumCanvas();

})(ChemDoodle);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(c) {
	'use strict';
	c.OverlayCanvas = function(id, width, height) {
		if (id) {
			this.create(id, width, height);
		}
	};
	var _ = c.OverlayCanvas.prototype = new c._SpectrumCanvas();
	_.overlaySpectra = [];
	_.superRepaint = _.innerRepaint;
	_.innerRepaint = function(ctx) {
		this.superRepaint(ctx);
		if (this.spectrum && this.spectrum.data.length > 0) {
			for ( var i = 0, ii = this.overlaySpectra.length; i < ii; i++) {
				var s = this.overlaySpectra[i];
				if (s && s.data.length > 0) {
					s.minX = this.spectrum.minX;
					s.maxX = this.spectrum.maxX;
					s.drawPlot(ctx, this.specs, this.width, this.height, this.spectrum.memory.offsetTop, this.spectrum.memory.offsetLeft, this.spectrum.memory.offsetBottom);
				}
			}
		}
	};
	_.addSpectrum = function(spectrum) {
		if (!this.spectrum) {
			this.spectrum = spectrum;
		} else {
			this.overlaySpectra.push(spectrum);
		}
	};

})(ChemDoodle);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(c, monitor, m) {
	'use strict';
	c.PerspectiveCanvas = function(id, width, height) {
		if (id) {
			this.create(id, width, height);
		}
	};
	var _ = c.PerspectiveCanvas.prototype = new c._SpectrumCanvas();
	_.dragRange = undefined;
	_.rescaleYAxisOnZoom = true;
	_.lastPinchScale = 1;
	_.mousedown = function(e) {
		this.dragRange = new c.structures.Point(e.p.x, e.p.x);
	};
	_.mouseup = function(e) {
		if (this.dragRange && this.dragRange.x !== this.dragRange.y) {
			if (!this.dragRange.multi) {
				var newScale = this.spectrum.zoom(this.dragRange.x, e.p.x, this.width, this.rescaleYAxisOnZoom);
				if (this.rescaleYAxisOnZoom) {
					this.specs.scale = newScale;
				}
			}
			this.dragRange = undefined;
			this.repaint();
		}
	};
	_.drag = function(e) {
		if (this.dragRange) {
			if (this.dragRange.multi) {
				this.dragRange = undefined;
			} else if (monitor.SHIFT) {
				this.spectrum.translate(e.p.x - this.dragRange.x, this.width);
				this.dragRange.x = e.p.x;
				this.dragRange.y = e.p.x;
			} else {
				this.dragRange.y = e.p.x;
			}
			this.repaint();
		}
	};
	_.drawChildExtras = function(ctx) {
		if (this.dragRange) {
			var xs = m.min(this.dragRange.x, this.dragRange.y);
			var xe = m.max(this.dragRange.x, this.dragRange.y);
			ctx.strokeStyle = 'gray';
			ctx.lineStyle = 1;
			ctx.beginPath();
			ctx.moveTo(xs, this.height / 2);
			for ( var i = xs; i <= xe; i++) {
				if (i % 10 < 5) {
					ctx.lineTo(i, m.round(this.height / 2));
				} else {
					ctx.moveTo(i, m.round(this.height / 2));
				}
			}
			ctx.stroke();
		}
	};
	_.mousewheel = function(e, delta) {
		this.specs.scale += delta / 10;
		if (this.specs.scale < .01) {
			this.specs.scale = .01;
		}
		this.repaint();
	};
	_.dblclick = function(e) {
		this.spectrum.setup();
		this.specs.scale = 1;
		this.repaint();
	};
	_.multitouchmove = function(e, numFingers) {
		if (numFingers === 2) {
			if (!this.dragRange || !this.dragRange.multi) {
				this.dragRange = new c.structures.Point(e.p.x, e.p.x);
				this.dragRange.multi = true;
			} else {
				this.spectrum.translate(e.p.x - this.dragRange.x, this.width);
				this.dragRange.x = e.p.x;
				this.dragRange.y = e.p.x;
				this.repaint();
			}
		}
	};
	_.gesturechange = function(e) {
		this.specs.scale *= e.originalEvent.scale / this.lastPinchScale;
		if (this.specs.scale < .01) {
			this.specs.scale = .01;
		}
		this.lastPinchScale = e.originalEvent.scale;
		this.repaint();
	};
	_.gestureend = function(e) {
		this.lastPinchScale = 1;
	};

})(ChemDoodle, ChemDoodle.monitor, Math);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(c, extensions, m) {
	'use strict';
	c.SeekerCanvas = function(id, width, height, seekType) {
		if (id) {
			this.create(id, width, height);
		}
		this.seekType = seekType;
	};
	var _ = c.SeekerCanvas.prototype = new c._SpectrumCanvas();
	_.superRepaint = _.innerRepaint;
	_.innerRepaint = function(ctx) {
		this.superRepaint(ctx);
		if (this.spectrum && this.spectrum.data.length > 0 && this.p) {
			// set up coords
			var renderP;
			var internalP;
			if (this.seekType === c.SeekerCanvas.SEEK_POINTER) {
				renderP = this.p;
				internalP = this.spectrum.getInternalCoordinates(renderP.x, renderP.y);
			} else if (this.seekType === c.SeekerCanvas.SEEK_PLOT || this.seekType === c.SeekerCanvas.SEEK_PEAK) {
				internalP = this.seekType === c.SeekerCanvas.SEEK_PLOT ? this.spectrum.getClosestPlotInternalCoordinates(this.p.x) : this.spectrum.getClosestPeakInternalCoordinates(this.p.x);
				if (!internalP) {
					return;
				}
				renderP = {
					x : this.spectrum.getTransformedX(internalP.x, this.specs, this.width, this.spectrum.memory.offsetLeft),
					y : this.spectrum.getTransformedY(internalP.y / 100, this.specs, this.height, this.spectrum.memory.offsetBottom, this.spectrum.memory.offsetTop)
				};
			}
			// draw point
			ctx.fillStyle = 'white';
			ctx.strokeStyle = this.specs.plots_color;
			ctx.lineWidth = this.specs.plots_width;
			ctx.beginPath();
			ctx.arc(renderP.x, renderP.y, 3, 0, m.PI * 2, false);
			ctx.fill();
			ctx.stroke();
			// draw internal coordinates
			ctx.font = extensions.getFontString(this.specs.text_font_size, this.specs.text_font_families);
			ctx.textAlign = 'left';
			ctx.textBaseline = 'bottom';
			var s = 'x:' + internalP.x.toFixed(3) + ', y:' + internalP.y.toFixed(3);
			var x = renderP.x + 3;
			var w = ctx.measureText(s).width;
			if (x + w > this.width - 2) {
				x -= 6 + w;
			}
			var y = renderP.y;
			if (y - this.specs.text_font_size - 2 < 0) {
				y += this.specs.text_font_size;
			}
			ctx.fillRect(x, y - this.specs.text_font_size, w, this.specs.text_font_size);
			ctx.fillStyle = 'black';
			ctx.fillText(s, x, y);
		}
	};
	_.mouseout = function(e) {
		this.p = undefined;
		this.repaint();
	};
	_.mousemove = function(e) {
		this.p = {
			x : e.p.x - 2,
			y : e.p.y - 3
		};
		this.repaint();
	};
	_.touchstart = function(e) {
		this.mousemove(e);
	};
	_.touchmove = function(e) {
		this.mousemove(e);
	};
	_.touchend = function(e) {
		this.mouseout(e);
	};
	c.SeekerCanvas.SEEK_POINTER = 'pointer';
	c.SeekerCanvas.SEEK_PLOT = 'plot';
	c.SeekerCanvas.SEEK_PEAK = 'peak';

})(ChemDoodle, ChemDoodle.extensions, Math);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//
(function(c, extensions, math, structures, d3, RESIDUE, m, document, m4, m3, v3, q, window) {
	'use strict';
	c._Canvas3D = function(id, width, height) {
		if (id) {
			this.create(id, width, height);
		}
	};
	var _ = c._Canvas3D.prototype = new c._Canvas();
	_.rotationMatrix = undefined;
	_.translationMatrix = undefined;
	_.lastPoint = undefined;
	_.emptyMessage = 'WebGL is Unavailable!';
	_.lastPinchScale = 1;
	_.lastGestureRotate = 0;
	_.afterLoadContent = function() {
		var bounds = new math.Bounds();
		for ( var i = 0, ii = this.molecules.length; i < ii; i++) {
			bounds.expand(this.molecules[i].getBounds3D());
		}
		// build fog parameter
		var maxDimension3D = v3.dist([ bounds.maxX, bounds.maxY, bounds.maxZ ], [ bounds.minX, bounds.minY, bounds.minZ ]) / 2 + 1.5;
		if(maxDimension3D===Infinity){
			// there is no content
			maxDimension3D = 10;
		}
		
		var fov = 45;
		var theta = fov / 360 * Math.PI;
		var tanTheta = Math.tan(theta) / 0.8;
		var top = maxDimension3D;
		this.depth = top / tanTheta;
		var near = m.max(this.depth - top, 0.1);
		var far = this.depth + top;
		var aspec = this.gl.canvas.clientWidth / this.gl.canvas.clientHeight;
		if (aspec < 1) {
			fov /= aspec;
		}
		this.specs.projectionOrthoWidth_3D = Math.tan(fov / 360 * Math.PI) * this.depth * 2 * aspec;
		this.specs.projectionPerspectiveVerticalFieldOfView_3D = fov;
		this.specs.projectionFrontCulling_3D = near;
		this.specs.projectionBackCulling_3D = far;
		this.specs.projectionWidthHeightRatio_3D = aspec;
		this.translationMatrix = m4.translate(m4.identity([]), [ 0, 0, -this.depth ]);
		this.maxDimension = m.max(bounds.maxX - bounds.minX, bounds.maxY - bounds.minY);
		// this.translationMatrix = m4.translate(m4.identity([]), [ 0, 0,
		// -this.maxDimension - 10 ]);
		this.setupScene();
	};
	_.setViewDistance = function(fov) {
		var minFov = 0.1;
		var maxFov = 179.9;
		this.specs.projectionPerspectiveVerticalFieldOfView_3D = math.clamp(this.specs.projectionPerspectiveVerticalFieldOfView_3D / fov, minFov, maxFov);
		this.specs.projectionOrthoWidth_3D = m.tan(this.specs.projectionPerspectiveVerticalFieldOfView_3D / 360 * Math.PI) * this.depth * 2 * this.specs.projectionWidthHeightRatio_3D;
		this.updateScene();
	};
	_.repaint = function() {
		if (this.gl) {
			// ready the bits for rendering
			this.gl.clear(this.gl.COLOR_BUFFER_BIT | this.gl.DEPTH_BUFFER_BIT);
			// set up the model view matrix to the specified transformations
			this.gl.modelViewMatrix = m4.multiply(this.translationMatrix, this.rotationMatrix, []);
			this.gl.rotationMatrix = this.rotationMatrix;
			// use default projection matrix to draw the molecule
			var pUniform = this.gl.getUniformLocation(this.gl.program, 'u_projection_matrix');
			this.gl.uniformMatrix4fv(pUniform, false, this.gl.projectionMatrix);
			this.gl.fogging.setMode(this.specs.fog_mode_3D);
			for ( var i = 0, ii = this.molecules.length; i < ii; i++) {
				this.molecules[i].render(this.gl, this.specs);
			}
			for ( var i = 0, ii = this.shapes.length; i < ii; i++) {
				this.shapes[i].render(this.gl, this.specs);
			}
			// compass use its own model view and projection matrix
			// so it need to use back the default matrix for other
			// rendering process (ex. render arbitrary text).
			if (this.specs.compass_display) {
				// use projection matrix to draw the compass
				this.gl.uniformMatrix4fv(pUniform, false, this.compass.projectionMatrix);
				this.compass.render(this.gl, this.specs);
				// use back the default model view matrix
				this.gl.setMatrixUniforms(this.gl.modelViewMatrix);
				// use back the default projection matrix
				this.gl.uniformMatrix4fv(pUniform, false, this.gl.projectionMatrix);
			}
			// enable blend and depth mask set to false
			this.gl.enable(this.gl.BLEND);
			this.gl.depthMask(false);
			// enable vertex for draw text
			this.gl.enableVertexAttribArray(this.gl.shader.vertexTexCoordAttribute);
			// draw label molecule
			if (this.specs.atoms_displayLabels_3D) {
				this.label3D.render(this.gl, this.specs, this.getMolecules());
			}
			// draw compass X Y Z text
			if (this.specs.compass_display && this.specs.compass_displayText_3D) {
				this.gl.uniformMatrix4fv(pUniform, false, this.compass.projectionMatrix);
				this.compass.renderAxis(this.gl);
				// use back the default model view matrix
				this.gl.setMatrixUniforms(this.gl.modelViewMatrix);
				// use back the default projection matrix
				this.gl.uniformMatrix4fv(pUniform, false, this.gl.projectionMatrix);
			}
				
			// disable vertex for draw text
			this.gl.disableVertexAttribArray(this.gl.shader.vertexTexCoordAttribute);
			// disable blend and depth mask set to true
			this.gl.disable(this.gl.BLEND);
			this.gl.depthMask(true);
			
			this.gl.disable(this.gl.DEPTH_TEST);
			if (this.drawChildExtras) {
				this.drawChildExtras(this.gl);
			}
			this.gl.enable(this.gl.DEPTH_TEST);
			// flush as this is seen in documentation
			this.gl.flush();
		}
	};
	_.pick = function(x, y, includeAtoms, includeBonds) {
		if (this.gl) {
			// set up the model view matrix to the specified transformations
			m4.multiply(this.translationMatrix, this.rotationMatrix, this.gl.modelViewMatrix);
			this.gl.rotationMatrix = this.rotationMatrix;
			// use default projection matrix to draw the molecule
			var pUniform = this.gl.getUniformLocation(this.gl.program, 'u_projection_matrix');
			this.gl.uniformMatrix4fv(pUniform, false, this.gl.projectionMatrix);
			// draw with pick framebuffer
			var xu = x;
			var yu = this.height - y;
			if (this.pixelRatio !== 1) {
				xu *= this.pixelRatio;
				yu *= this.pixelRatio;
			}
			return this.picker.pick(this.gl, this.molecules, this.specs, xu, yu, includeAtoms, includeBonds);
		}
		return undefined;
	};
	_.center = function() {
		var p = new structures.Atom();
		for ( var k = 0, kk = this.molecules.length; k < kk; k++) {
			var m = this.molecules[k];
			p.add3D(m.getCenter3D());
		}
		p.x /= this.molecules.length;
		p.y /= this.molecules.length;
		for ( var k = 0, kk = this.molecules.length; k < kk; k++) {
			var m = this.molecules[k];
			for ( var i = 0, ii = m.atoms.length; i < ii; i++) {
				m.atoms[i].sub3D(p);
			}
			if (m.chains && m.fromJSON) {
				for ( var i = 0, ii = m.chains.length; i < ii; i++) {
					var chain = m.chains[i];
					for ( var j = 0, jj = chain.length; j < jj; j++) {
						var residue = chain[j];
						residue.cp1.sub3D(p);
						residue.cp2.sub3D(p);
						if (residue.cp3) {
							residue.cp3.sub3D(p);
							residue.cp4.sub3D(p);
							residue.cp5.sub3D(p);
						}
					}
				}
			}
		}
	};
	_.subCreate = function() {
		// setup gl object
		try {
			var canvas = document.getElementById(this.id);
			this.gl = canvas.getContext('webgl');
			if (!this.gl) {
				this.gl = canvas.getContext('experimental-webgl');
			}
		} catch (e) {
		}
		if (this.gl) {
			if (this.pixelRatio !== 1 && this.gl.canvas.width === this.width) {
				this.gl.canvas.style.width = this.width + 'px';
				this.gl.canvas.style.height = this.height + 'px';
				this.gl.canvas.width = this.width * this.pixelRatio;
				this.gl.canvas.height = this.height * this.pixelRatio;
			}
			// setup matrices
			this.rotationMatrix = m4.identity([]);
			this.translationMatrix = m4.identity([]);
			// setup viewport
			this.gl.viewport(0, 0, this.gl.canvas.width, this.gl.canvas.height);
			this.gl.program = this.gl.createProgram();
			// this is the shader
			this.gl.shader = new d3.Shader();
			this.gl.shader.init(this.gl);
			// shader for text renderer
			this.gl.programLabel = this.gl.createProgram();
			this.setupScene();
		} else {
			this.displayMessage();
		}
	};
	c._Canvas.prototype.displayMessage = function() {
		var canvas = document.getElementById(this.id);
		if (canvas.getContext) {
			var ctx = canvas.getContext('2d');
			if (this.specs.backgroundColor) {
				ctx.fillStyle = this.specs.backgroundColor;
				ctx.fillRect(0, 0, this.width, this.height);
			}
			if (this.emptyMessage) {
				ctx.fillStyle = '#737683';
				ctx.textAlign = 'center';
				ctx.textBaseline = 'middle';
				ctx.font = '18px Helvetica, Verdana, Arial, Sans-serif';
				ctx.fillText(this.emptyMessage, this.width / 2, this.height / 2);
			}
		}
	};
	_.renderText = function(text, position) {
		if(this.gl) {
			var vertexData = {
				position : [],
				texCoord : [],
				translation : []
			};
			this.gl.textImage.pushVertexData(text, position, 0, vertexData);
			this.gl.textMesh.storeData(this.gl, vertexData.position, vertexData.texCoord, vertexData.translation);
			
			// enable blend and depth mask set to false
			this.gl.enable(this.gl.BLEND);
			this.gl.depthMask(false);
			// enable vertex for draw text
			this.gl.enableVertexAttribArray(this.gl.shader.vertexTexCoordAttribute);
			this.gl.textImage.useTexture(this.gl);
			this.gl.textMesh.render(this.gl);
			// disable vertex for draw text
			this.gl.disableVertexAttribArray(this.gl.shader.vertexTexCoordAttribute);
			// disable blend and depth mask set to true
			this.gl.disable(this.gl.BLEND);
			this.gl.depthMask(true);
		}
	};
	_.setupScene = function() {
		if (this.gl) {
			// clear the canvas
			// set background color for IE's sake, seems like an IE bug where half the repaints don't render a background
			var jqCapsule = q('#' + this.id);
			jqCapsule.css('background-color', this.specs.backgroundColor);
			var cs = math.getRGB(this.specs.backgroundColor, 1);
			this.gl.clearColor(cs[0], cs[1], cs[2], 1.0);
			this.gl.clearDepth(1.0);
			this.gl.enable(this.gl.DEPTH_TEST);
			this.gl.depthFunc(this.gl.LEQUAL);
			this.gl.blendFuncSeparate(this.gl.SRC_ALPHA, this.gl.ONE_MINUS_SRC_ALPHA, this.gl.ONE, this.gl.ONE_MINUS_SRC_ALPHA);
			this.specs.cullBackFace_3D ? this.gl.enable(this.gl.CULL_FACE) : this.gl.disable(this.gl.CULL_FACE);
			// here is the sphere buffer to be drawn, make it once, then scale
			// and translate to draw atoms
			this.gl.sphereBuffer = new d3.Sphere(1, this.specs.atoms_resolution_3D, this.specs.atoms_resolution_3D);
			this.gl.starBuffer = new d3.Star();
			this.gl.cylinderBuffer = new d3.Cylinder(1, 1, this.specs.bonds_resolution_3D);
			this.gl.cylinderClosedBuffer = new d3.Cylinder(1, 1, this.specs.bonds_resolution_3D, true);
			this.gl.boxBuffer = new d3.Box(1, 1, 1);
			this.gl.pillBuffer = new d3.Pill(this.specs.bonds_pillDiameter_3D / 2, this.specs.bonds_pillHeight_3D, this.specs.bonds_pillLatitudeResolution_3D, this.specs.bonds_pillLongitudeResolution_3D);
			this.gl.lineBuffer = new d3.Line();
			this.gl.lineArrowBuffer = new d3.LineArrow();
			this.gl.arrowBuffer = new d3.Arrow(0.3, this.specs.compass_resolution_3D);
			this.gl.textMesh = new d3.TextMesh();
			this.gl.textMesh.init(this.gl);
			// texture for rendering text
			this.gl.textImage = new d3.TextImage();
			this.gl.textImage.init(this.gl);
			this.gl.textImage.updateFont(this.gl, this.specs.text_font_size, this.specs.text_font_families, this.specs.text_font_bold, this.specs.text_font_italic, this.specs.text_font_stroke_3D);
			// add label
			this.label3D = new d3.Label();
			this.label3D.init(this.gl, this.specs);
			for ( var k = 0, kk = this.molecules.length; k < kk; k++) {
				var mol = this.molecules[k];
				if (!(mol.labelMesh instanceof d3.TextMesh)) {
					mol.labelMesh = new d3.TextMesh();
					mol.labelMesh.init(this.gl);
				}
				if (mol.chains) {
					mol.ribbons = [];
					mol.cartoons = [];
					mol.tubes = [];
					mol.pipePlanks = [];
					// set up ribbon diagram if available and not already setup
					for ( var j = 0, jj = mol.chains.length; j < jj; j++) {
						var rs = mol.chains[j];
						for ( var i = 0, ii = rs.length - 1; i < ii; i++) {
							rs[i].Test =i;
						}
						var isNucleotide = rs.length > 3 && RESIDUE[rs[3].name] && RESIDUE[rs[3].name].aminoColor === '#BEA06E';
						if (rs.length > 0 && !rs[0].lineSegments) {
							for ( var i = 0, ii = rs.length - 1; i < ii; i++) {
								rs[i].setup(rs[i + 1].cp1, isNucleotide ? 1 : this.specs.proteins_horizontalResolution);
							}
							if (!isNucleotide) {
								for ( var i = 1, ii = rs.length - 1; i < ii; i++) {
									// reverse guide points if carbonyl
									// orientation flips
									if (extensions.vec3AngleFrom(rs[i - 1].D, rs[i].D) > m.PI / 2) {
										rs[i].guidePointsSmall.reverse();
										rs[i].guidePointsLarge.reverse();
										v3.scale(rs[i].D, -1);
									}
								}
							}
							for ( var i = 2, ii = rs.length - 3; i < ii; i++) {
								// compute line segments
								rs[i].computeLineSegments(rs[i - 2], rs[i - 1], rs[i + 1], !isNucleotide, isNucleotide ? this.specs.nucleics_verticalResolution : this.specs.proteins_verticalResolution);
							}
							// remove unneeded dummies
							rs.pop();
							rs.pop();
							rs.pop();
							rs.shift();
							rs.shift();
						}
						// create the hsl color for the chain
						var rgb = math.hsl2rgb(jj === 1 ? .5 : j / jj, 1, .5);
						var chainColor = 'rgb(' + rgb[0] + ',' + rgb[1] + ',' + rgb[2] + ')';
						rs.chainColor = chainColor;
						if (isNucleotide) {
							var t = new d3.Tube(rs, this.specs.nucleics_tubeThickness, this.specs.nucleics_tubeResolution_3D);
							t.chainColor = chainColor;
							mol.tubes.push(t);
						} else {
							var t = new d3.PipePlank(rs, this.specs);
							mol.pipePlanks.push(t);
							var res = rs.shift();
							var r = {
								front : new d3.Ribbon(rs, this.specs.proteins_ribbonThickness, false),
								back : new d3.Ribbon(rs, -this.specs.proteins_ribbonThickness, false)
							};
							r.front.chainColor = chainColor;
							r.back.chainColor = chainColor;
							mol.ribbons.push(r);
							var d = {
								front : new d3.Ribbon(rs, this.specs.proteins_ribbonThickness, true),
								back : new d3.Ribbon(rs, -this.specs.proteins_ribbonThickness, true)
							};
							d.front.chainColor = chainColor;
							d.back.chainColor = chainColor;
							mol.cartoons.push(d);
							rs.unshift(res);
						}
					}
				}
			}
			this.label3D.updateVerticesBuffer(this.gl, this.getMolecules(), this.specs);
			// the molecules in frame of MovieCanvas3D must be handled
			if (this instanceof c.MovieCanvas3D && this.frames) {
				for ( var i = 0, ii = this.frames.length; i < ii; i++) {
					var f = this.frames[i];
					for ( var j = 0, jj = f.mols.length; j < jj; j++) {
						var mol = f.mols[j];
						if (!(mol.labelMesh instanceof structures.d3.TextMesh)) {
							mol.labelMesh = new structures.d3.TextMesh();
							mol.labelMesh.init(this.gl);
						}
					}
					this.label3D.updateVerticesBuffer(this.gl, f.mols, this.specs);
				}
			}
			// set up lighting
			this.gl.lighting = new d3.Light(this.specs.lightDiffuseColor_3D, this.specs.lightSpecularColor_3D, this.specs.lightDirection_3D);
			this.gl.lighting.lightScene(this.gl);
			// set up material
			this.gl.material = new d3.Material(this.gl);
			// set up fogging
			this.gl.fogging = new d3.Fog(this.gl);
			this.gl.fogging.setTempParameter(this.specs.fog_color_3D || this.specs.backgroundColor, this.specs.fog_start_3D, this.specs.fog_end_3D, this.specs.fog_density_3D);
			// set up compass
			this.compass = new d3.Compass(this.gl, this.specs);
			// projection matrix
			// arg1: vertical field of view (degrees)
			// arg2: width to height ratio
			// arg3: front culling
			// arg4: back culling
			var widthHeightRatio = this.width / this.height;
			if (this.specs.projectionWidthHeightRatio_3D) {
				widthHeightRatio = this.specs.projectionWidthHeightRatio_3D;
			}
			this.gl.projectionMatrix = this.specs.projectionPerspective_3D ? m4.perspective(this.specs.projectionPerspectiveVerticalFieldOfView_3D, widthHeightRatio, this.specs.projectionFrontCulling_3D, this.specs.projectionBackCulling_3D) : m4.ortho(-this.specs.projectionOrthoWidth_3D / 2, this.specs.projectionOrthoWidth_3D / 2, -this.specs.projectionOrthoWidth_3D / 2 / widthHeightRatio, this.specs.projectionOrthoWidth_3D / 2 / widthHeightRatio, this.specs.projectionFrontCulling_3D,
					this.specs.projectionBackCulling_3D);
			// push the projection matrix to the graphics card
			var pUniform = this.gl.getUniformLocation(this.gl.program, 'u_projection_matrix');
			this.gl.uniformMatrix4fv(pUniform, false, this.gl.projectionMatrix);
			// matrix setup functions
			var mvUL = this.gl.getUniformLocation(this.gl.program, 'u_model_view_matrix');
			var nUL = this.gl.getUniformLocation(this.gl.program, 'u_normal_matrix');
			this.gl.setMatrixUniforms = function(mvMatrix) {
				// push the model-view matrix to the graphics card
				this.uniformMatrix4fv(mvUL, false, mvMatrix);
				// create the normal matrix and push it to the graphics card
				var normalMatrix = m3.transpose(m4.toInverseMat3(mvMatrix, []));
				this.uniformMatrix3fv(nUL, false, normalMatrix);
			};
			var dimUL = this.gl.getUniformLocation(this.gl.program, 'u_dimension');
			this.gl.uniformMatrix4fv(pUniform, false, this.gl.projectionMatrix);
			this.gl.uniform2f(dimUL, this.gl.canvas.clientWidth, this.gl.canvas.clientHeight);
			// set framebuffer
			this.picker = new d3.Picker();
			this.picker.init(this.gl);
			this.picker.setDimension(this.gl, this.gl.canvas.width, this.gl.canvas.height);
		}
	};
	_.updateScene = function() {
		this.gl.fogging.setTempParameter(this.specs.fog_color_3D || this.specs.backgroundColor, this.specs.fog_start_3D, this.specs.fog_end_3D, this.specs.fog_density_3D);
		var widthHeightRatio = this.width / this.height;
		if (this.specs.projectionWidthHeightRatio_3D) {
			widthHeightRatio = this.specs.projectionWidthHeightRatio_3D;
		}
		this.gl.projectionMatrix = this.specs.projectionPerspective_3D ? m4.perspective(this.specs.projectionPerspectiveVerticalFieldOfView_3D, widthHeightRatio, this.specs.projectionFrontCulling_3D, this.specs.projectionBackCulling_3D) : m4.ortho(-this.specs.projectionOrthoWidth_3D / 2, this.specs.projectionOrthoWidth_3D / 2, -this.specs.projectionOrthoWidth_3D / 2 / widthHeightRatio, this.specs.projectionOrthoWidth_3D / 2 / widthHeightRatio, this.specs.projectionFrontCulling_3D,
				this.specs.projectionBackCulling_3D);
		this.repaint();
	};
	_.mousedown = function(e) {
		this.lastPoint = e.p;
	};
	_.mouseup = function(e) {
		this.lastPoint = undefined;
	};
	_.rightmousedown = function(e) {
		this.lastPoint = e.p;
	};
	_.drag = function(e) {
		if(this.lastPoint){
			if (c.monitor.ALT) {
				var t = new structures.Point(e.p.x, e.p.y);
				t.sub(this.lastPoint);
				var theta = this.specs.projectionPerspectiveVerticalFieldOfView_3D / 360 * m.PI;
				var tanTheta = m.tan(theta);
				var topScreen = this.height / 2;
				var nearScreen = topScreen / tanTheta;
				var nearRatio = this.depth / nearScreen;
				m4.translate(this.translationMatrix, [ t.x * nearRatio, -t.y * nearRatio, 0 ]);
				this.lastPoint = e.p;
				this.repaint();
			} else {
				var difx = e.p.x - this.lastPoint.x;
				var dify = e.p.y - this.lastPoint.y;
				var rotation = m4.rotate(m4.identity([]), difx * m.PI / 180.0, [ 0, 1, 0 ]);
				m4.rotate(rotation, dify * m.PI / 180.0, [ 1, 0, 0 ]);
				this.rotationMatrix = m4.multiply(rotation, this.rotationMatrix);
				this.lastPoint = e.p;
				this.repaint();
			}
		}
	};
	_.mousewheel = function(e, delta) {
		var minFov = 0.1;
		var maxFov = 179.9;
		var fov = this.specs.projectionPerspectiveVerticalFieldOfView_3D + delta;
		this.specs.projectionPerspectiveVerticalFieldOfView_3D = fov < minFov ? minFov : fov > maxFov ? maxFov : fov;
		this.specs.projectionOrthoWidth_3D = Math.tan(this.specs.projectionPerspectiveVerticalFieldOfView_3D / 360 * Math.PI) * this.depth * 2 * this.specs.projectionWidthHeightRatio_3D;
		this.updateScene();
	};
	_.multitouchmove = function(e, numFingers) {
		if (numFingers === 2) {
			if (this.lastPoint && this.lastPoint.multi) {
				var t = new structures.Point(e.p.x, e.p.y);
				t.sub(this.lastPoint);
				var theta = this.specs.projectionPerspectiveVerticalFieldOfView_3D / 360 * m.PI;
				var tanTheta = m.tan(theta);
				var topScreen = this.height / 2;
				var nearScreen = topScreen / tanTheta;
				var nearRatio = this.depth / nearScreen;
				m4.translate(this.translationMatrix, [ t.x * nearRatio, -t.y * nearRatio, 0 ]);
				this.lastPoint = e.p;
				this.repaint();
			} else {
				this.lastPoint = e.p;
				this.lastPoint.multi = true;
			}
		}
	};
	_.gesturechange = function(e) {
		if (e.originalEvent.scale - this.lastPinchScale !== 0) {
			var minFov = 0.1;
			var maxFov = 179.9;
			var dz = -(e.originalEvent.scale / this.lastPinchScale - 1) * 30;
			if(isNaN(dz)){
				// this seems to happen on Android when using multiple fingers
				return;
			}
			var fov = this.specs.projectionPerspectiveVerticalFieldOfView_3D + dz;
			this.specs.projectionPerspectiveVerticalFieldOfView_3D = fov < minFov ? minFov : fov > maxFov ? maxFov : fov;
			this.specs.projectionOrthoWidth_3D = Math.tan(this.specs.projectionPerspectiveVerticalFieldOfView_3D / 360 * Math.PI) * this.depth * 2 * this.specs.projectionWidthHeightRatio_3D;
			this.updateScene();
			this.lastPinchScale = e.originalEvent.scale;
		}
		this.repaint();
	};
	_.gestureend = function(e) {
		this.lastPinchScale = 1;
		this.lastGestureRotate = 0;
	};
})(ChemDoodle, ChemDoodle.extensions, ChemDoodle.math, ChemDoodle.structures, ChemDoodle.structures.d3, ChemDoodle.RESIDUE, Math, document, ChemDoodle.lib.mat4, ChemDoodle.lib.mat3, ChemDoodle.lib.vec3, ChemDoodle.lib.jQuery, window);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(c, iChemLabs, q, document) {
	'use strict';
	c.MolGrabberCanvas3D = function(id, width, height) {
		if (id) {
			this.create(id, width, height);
		}
		var sb = [];
		sb.push('<br><input type="text" id="');
		sb.push(id);
		sb.push('_query" size="32" value="" />');
		sb.push('<br><nobr>');
		sb.push('<select id="');
		sb.push(id);
		sb.push('_select">');
		// sb.push('<option value="chemexper">ChemExper');
		// sb.push('<option value="chemspider">ChemSpider');
		sb.push('<option value="pubchem" selected>PubChem');
		sb.push('</select>');
		sb.push('<button id="');
		sb.push(id);
		sb.push('_submit">Show Molecule</button>');
		sb.push('</nobr>');
		document.writeln(sb.join(''));
		var self = this;
		q('#' + id + '_submit').click(function() {
			self.search();
		});
		q('#' + id + '_query').keypress(function(e) {
			if (e.which === 13) {
				self.search();
			}
		});
	};
	var _ = c.MolGrabberCanvas3D.prototype = new c._Canvas3D();
	_.setSearchTerm = function(term) {
		q('#' + this.id + '_query').val(term);
		this.search();
	};
	_.search = function() {
		var self = this;
		iChemLabs.getMoleculeFromDatabase(q('#' + this.id + '_query').val(), {
			database : q('#' + this.id + '_select').val(),
			dimension : 3
		}, function(mol) {
			self.loadMolecule(mol);
		});
	};

})(ChemDoodle, ChemDoodle.iChemLabs, ChemDoodle.lib.jQuery, document);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//
(function(c, structures) {
	'use strict';
	c.MovieCanvas3D = function(id, width, height) {
		if (id) {
			this.create(id, width, height);
		}
		this.frames = [];
	};
	c.MovieCanvas3D.PLAY_ONCE = 0;
	c.MovieCanvas3D.PLAY_LOOP = 1;
	c.MovieCanvas3D.PLAY_SPRING = 2;
	var _ = c.MovieCanvas3D.prototype = new c._Canvas3D();
	_.timeout = 50;
	_.frameNumber = 0;
	_.playMode = 2;
	_.reverse = false;
	_.startAnimation = c._AnimatorCanvas.prototype.startAnimation;
	_.stopAnimation = c._AnimatorCanvas.prototype.stopAnimation;
	_.isRunning = c._AnimatorCanvas.prototype.isRunning;
	_.dblclick = c.RotatorCanvas.prototype.dblclick;
	_.nextFrame = function(delta) {
		var f = this.frames[this.frameNumber];
		this.molecules = f.mols;
		this.shapes = f.shapes;
		if (this.playMode === 2 && this.reverse) {
			this.frameNumber--;
			if (this.frameNumber < 0) {
				this.frameNumber = 1;
				this.reverse = false;
			}
		} else {
			this.frameNumber++;
			if (this.frameNumber >= this.frames.length) {
				if (this.playMode === 2) {
					this.frameNumber -= 2;
					this.reverse = true;
				} else {
					this.frameNumber = 0;
					if (this.playMode === 0) {
						this.stopAnimation();
					}
				}
			}
		}
	};
	_.center = function() {
		// override this function to center the entire movie
		var p = new structures.Atom();
		var first = this.frames[0];
		for ( var j = 0, jj = first.mols.length; j < jj; j++) {
			p.add3D(first.mols[j].getCenter3D());
		}
		p.x /= first.mols.length;
		p.y /= first.mols.length;
		var center = new structures.Atom();
		center.sub3D(p);
		for ( var i = 0, ii = this.frames.length; i < ii; i++) {
			var f = this.frames[i];
			for ( var j = 0, jj = f.mols.length; j < jj; j++) {
				var mol = f.mols[j];
				for ( var k = 0, kk = mol.atoms.length; k < kk; k++) {
					mol.atoms[k].add3D(center);
				}
			}
		}
	};
	_.addFrame = function(molecules, shapes) {
		this.frames.push({
			mols : molecules,
			shapes : shapes
		});
	};

})(ChemDoodle, ChemDoodle.structures);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(c, m, m4) {
	'use strict';
	// keep these declaration outside the loop to avoid overhead
	var matrix = [];
	var xAxis = [ 1, 0, 0 ];
	var yAxis = [ 0, 1, 0 ];
	var zAxis = [ 0, 0, 1 ];

	c.RotatorCanvas3D = function(id, width, height) {
		if (id) {
			this.create(id, width, height);
		}
	};
	var _ = c.RotatorCanvas3D.prototype = new c._Canvas3D();
	_.timeout = 33;
	var increment = m.PI / 15;
	_.xIncrement = increment;
	_.yIncrement = increment;
	_.zIncrement = increment;
	_.startAnimation = c._AnimatorCanvas.prototype.startAnimation;
	_.stopAnimation = c._AnimatorCanvas.prototype.stopAnimation;
	_.isRunning = c._AnimatorCanvas.prototype.isRunning;
	_.dblclick = c.RotatorCanvas.prototype.dblclick;
	_.mousedown = undefined;
	_.rightmousedown = undefined;
	_.drag = undefined;
	_.mousewheel = undefined;
	_.nextFrame = function(delta) {
		if (this.molecules.length === 0 && this.shapes.length === 0) {
			this.stopAnimation();
			return;
		}
		m4.identity(matrix);
		var change = delta / 1000;
		m4.rotate(matrix, this.xIncrement * change, xAxis);
		m4.rotate(matrix, this.yIncrement * change, yAxis);
		m4.rotate(matrix, this.zIncrement * change, zAxis);
		m4.multiply(this.rotationMatrix, matrix);
	};

})(ChemDoodle, Math, ChemDoodle.lib.mat4);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//
(function(c) {
	'use strict';
	c.TransformCanvas3D = function(id, width, height) {
		if (id) {
			this.create(id, width, height);
		}
	};
	c.TransformCanvas3D.prototype = new c._Canvas3D();

})(ChemDoodle);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//
(function(c) {
	'use strict';
	c.ViewerCanvas3D = function(id, width, height) {
		if (id) {
			this.create(id, width, height);
		}
	};
	var _ = c.ViewerCanvas3D.prototype = new c._Canvas3D();
	_.mousedown = undefined;
	_.rightmousedown = undefined;
	_.drag = undefined;
	_.mousewheel = undefined;

})(ChemDoodle);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(c, extensions, math, document) {
	'use strict';
	function PeriodicCell(element, x, y, dimension) {
		this.element = element;
		this.x = x;
		this.y = y;
		this.dimension = dimension;
		this.allowMultipleSelections = false;
	}

	c.PeriodicTableCanvas = function(id, cellDimension) {
		this.padding = 5;
		if (id) {
			this.create(id, cellDimension * 18 + this.padding * 2, cellDimension * 10 + this.padding * 2);
		}
		this.cellDimension = cellDimension ? cellDimension : 20;
		this.setupTable();
		this.repaint();
	};
	var _ = c.PeriodicTableCanvas.prototype = new c._Canvas();
	_.loadMolecule = undefined;
	_.getMolecule = undefined;
	_.getHoveredElement = function() {
		if (this.hovered) {
			return this.hovered.element;
		}
		return undefined;
	};
	_.innerRepaint = function(ctx) {
		for ( var i = 0, ii = this.cells.length; i < ii; i++) {
			this.drawCell(ctx, this.specs, this.cells[i]);
		}
		if (this.hovered) {
			this.drawCell(ctx, this.specs, this.hovered);
		}
		if (this.selected) {
			this.drawCell(ctx, this.specs, this.selected);
		}
	};
	_.setupTable = function() {
		this.cells = [];
		var x = this.padding;
		var y = this.padding;
		var count = 0;
		for ( var i = 0, ii = c.SYMBOLS.length; i < ii; i++) {
			if (count === 18) {
				count = 0;
				y += this.cellDimension;
				x = this.padding;
			}
			var e = c.ELEMENT[c.SYMBOLS[i]];
			if (e.atomicNumber === 2) {
				x += 16 * this.cellDimension;
				count += 16;
			} else if (e.atomicNumber === 5 || e.atomicNumber === 13) {
				x += 10 * this.cellDimension;
				count += 10;
			}
			if ((e.atomicNumber < 58 || e.atomicNumber > 71 && e.atomicNumber < 90 || e.atomicNumber > 103) && e.atomicNumber < 113) {
				this.cells.push(new PeriodicCell(e, x, y, this.cellDimension));
				x += this.cellDimension;
				count++;
			}
		}
		y += 2 * this.cellDimension;
		x = 3 * this.cellDimension + this.padding;
		for ( var i = 57; i < 104; i++) {
			var e = c.ELEMENT[c.SYMBOLS[i]];
			if (e.atomicNumber === 90) {
				y += this.cellDimension;
				x = 3 * this.cellDimension + this.padding;
			}
			if (e.atomicNumber >= 58 && e.atomicNumber <= 71 || e.atomicNumber >= 90 && e.atomicNumber <= 103) {
				this.cells.push(new PeriodicCell(e, x, y, this.cellDimension));
				x += this.cellDimension;
			}
		}
	};
	_.drawCell = function(ctx, specs, cell) {
		var radgrad = ctx.createRadialGradient(cell.x + cell.dimension / 3, cell.y + cell.dimension / 3, cell.dimension * 1.5, cell.x + cell.dimension / 3, cell.y + cell.dimension / 3, cell.dimension / 10);
		radgrad.addColorStop(0, '#000000');
		radgrad.addColorStop(.7, cell.element.jmolColor);
		radgrad.addColorStop(1, '#FFFFFF');
		ctx.fillStyle = radgrad;
		extensions.contextRoundRect(ctx, cell.x, cell.y, cell.dimension, cell.dimension, cell.dimension / 8);
		if (cell === this.hovered || cell === this.selected || cell.selected) {
			ctx.lineWidth = 2;
			ctx.strokeStyle = '#c10000';
			ctx.stroke();
			ctx.fillStyle = 'white';
		}
		ctx.fill();
		ctx.font = extensions.getFontString(specs.text_font_size, specs.text_font_families);
		ctx.fillStyle = specs.text_color;
		ctx.textAlign = 'center';
		ctx.textBaseline = 'middle';
		ctx.fillText(cell.element.symbol, cell.x + cell.dimension / 2, cell.y + cell.dimension / 2);
	};
	_.click = function(e) {
		if (this.hovered) {
			if(this.allowMultipleSelections){
				this.hovered.selected = !this.hovered.selected;
			}else{
				this.selected = this.hovered;
			}
			this.repaint();
		}
	};
	_.touchstart = function(e){
		// try to hover an element
		this.mousemove(e);
	};
	_.mousemove = function(e) {
		var x = e.p.x;
		var y = e.p.y;
		this.hovered = undefined;
		for ( var i = 0, ii = this.cells.length; i < ii; i++) {
			var c = this.cells[i];
			if (math.isBetween(x, c.x, c.x + c.dimension) && math.isBetween(y, c.y, c.y + c.dimension)) {
				this.hovered = c;
				break;
			}
		}
		this.repaint();
	};
	_.mouseout = function(e) {
		this.hovered = undefined;
		this.repaint();
	};

})(ChemDoodle, ChemDoodle.extensions, ChemDoodle.math, document);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(io, document, window) {
	'use strict';
	io.png = {};

	io.png.create = function(canvas) {
		// this will not work for WebGL canvases in some browsers
		// to fix that you need to set the "preserveDrawingBuffer" to true when
		// creating the WebGL context
		// note that this will cause performance issues on some platforms and is
		// therefore not done by default
		window.open(document.getElementById(canvas.id).toDataURL('image/png'));
	};

})(ChemDoodle.io, document, window);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(io, q) {
	'use strict';
	io.file = {};

	// this function will only work with files from the same origin it is being
	// called from, unless the receiving server supports XHR2
	io.file.content = function(url, callback) {
		q.get(url, '', callback);
	};

})(ChemDoodle.io, ChemDoodle.lib.jQuery);
//
//  Copyright 2009 iChemLabs, LLC.  All rights reserved.
//

(function(c, iChemLabs, io, structures, q, location) {
	'use strict';
	iChemLabs.SERVER_URL = 'https://ichemlabs.cloud.chemdoodle.com/icl_cdc_v070000/WebHQ';

	iChemLabs.inRelay = false;
	iChemLabs.asynchronous = true;

	iChemLabs.INFO = {
		userAgent : navigator.userAgent,
		v_cwc : c.getVersion(),
		v_jQuery : q.version,
		v_jQuery_ui : (q.ui ? q.ui.version : 'N/A')
	};

	var JSON_INTERPRETER = new io.JSONInterpreter();
	var queue = new structures.Queue();
	
	iChemLabs.useHTTPS = function(){
		iChemLabs.SERVER_URL = 'https'+iChemLabs.SERVER_URL.substr(4);
	};

	iChemLabs._contactServer = function(call, content, options, callback, errorback) {
		if (this.inRelay) {
			queue.enqueue({
				'call' : call,
				'content' : content,
				'options' : options,
				'callback' : callback,
				'errorback' : errorback
			});
		} else {
			iChemLabs.inRelay = true;
			q.ajax({
				dataType : 'text',
				type : 'POST',
				data : JSON.stringify({
					'call' : call,
					'content' : content,
					'options' : options,
					'info' : iChemLabs.INFO
				}),
				url : this.SERVER_URL,
				success : function(data) {
					var o = JSON.parse(data);
					if (o.message) {
						alert(o.message);
					}
					iChemLabs.inRelay = false;
					if (callback && o.content && !o.stop) {
						callback(o.content);
					}
					if (o.stop && errorback) {
						errorback();
					}
					if(!queue.isEmpty()){
						var next = queue.dequeue();
						iChemLabs._contactServer(next.call, next.content, next.options, next.callback, next.errorback);
					}
				},
				error : function(xhr, status, error) {
					if(call!='checkForUpdates'){
						alert('Call failed. Please try again. If you continue to see this message, please contact iChemLabs customer support.');
					}
					iChemLabs.inRelay = false;
					if (errorback) {
						errorback();
					}
					if(!queue.isEmpty()){
						var next = queue.dequeue();
						iChemLabs._contactServer(next.call, next.content, next.options, next.callback, next.errorback);
					}
				},
				xhrFields : {
					withCredentials : true
				},
				async : iChemLabs.asynchronous
			});
		}
	};

	// undocumented, this call is for clients that have licensed cloud for their
	// own servers
	iChemLabs.authenticate = function(credential, options, callback, errorback) {
		this._contactServer('authenticate', {
			'credential' : credential
		}, options, function(content) {
			callback(content);
		}, errorback);
	};

	iChemLabs.calculate = function(mol, options, callback, errorback) {
		this._contactServer('calculate', {
			'mol' : JSON_INTERPRETER.molTo(mol)
		}, options, function(content) {
			callback(content);
		}, errorback);
	};

	iChemLabs.createLewisDotStructure = function(mol, options, callback, errorback) {
		this._contactServer('createLewisDot', {
			'mol' : JSON_INTERPRETER.molTo(mol)
		}, options, function(content) {
			callback(JSON_INTERPRETER.molFrom(content.mol));
		}, errorback);
	};

	iChemLabs.generateImage = function(mol, options, callback, errorback) {
		this._contactServer('generateImage', {
			'mol' : JSON_INTERPRETER.molTo(mol)
		}, options, function(content) {
			callback(content.link);
		}, errorback);
	};

	iChemLabs.generateIUPACName = function(mol, options, callback, errorback) {
		this._contactServer('generateIUPACName', {
			'mol' : JSON_INTERPRETER.molTo(mol)
		}, options, function(content) {
			callback(content.iupac);
		}, errorback);
	};

	iChemLabs.getAd = function(callback, errorback) {
		this._contactServer('getAd', {}, {}, function(content) {
			callback(content.image_url, content.target_url);
		}, errorback);
	};

	iChemLabs.getMoleculeFromContent = function(input, options, callback, errorback) {
		this._contactServer('getMoleculeFromContent', {
			'content' : input
		}, options, function(content) {
			var z = false;
			for ( var i = 0, ii = content.mol.a.length; i < ii; i++) {
				if (content.mol.a[i].z !== 0) {
					z = true;
					break;
				}
			}
			if (z) {
				for ( var i = 0, ii = content.mol.a.length; i < ii; i++) {
					content.mol.a[i].x /= 20;
					content.mol.a[i].y /= 20;
					content.mol.a[i].z /= 20;
				}
			}
			callback(JSON_INTERPRETER.molFrom(content.mol));
		}, errorback);
	};

	iChemLabs.getMoleculeFromDatabase = function(query, options, callback, errorback) {
		this._contactServer('getMoleculeFromDatabase', {
			'query' : query
		}, options, function(content) {
			if (options.dimension === 3) {
				for ( var i = 0, ii = content.mol.a.length; i < ii; i++) {
					content.mol.a[i].x /= 20;
					content.mol.a[i].y /= -20;
					content.mol.a[i].z /= 20;
				}
			}
			callback(JSON_INTERPRETER.molFrom(content.mol));
		}, errorback);
	};

	iChemLabs.getOptimizedPDBStructure = function(id, options, callback, errorback) {
		this._contactServer('getOptimizedPDBStructure', {
			'id' : id
		}, options, function(content) {
			var mol;
			if (content.mol) {
				mol = JSON_INTERPRETER.molFrom(content.mol);
			} else {
				mol = new structures.Molecule();
			}
			mol.chains = JSON_INTERPRETER.chainsFrom(content.ribbons);
			mol.fromJSON = true;
			callback(mol);
		}, errorback);
	};

	iChemLabs.getZeoliteFromIZA = function(query, options, callback, errorback) {
		this._contactServer('getZeoliteFromIZA', {
			'query' : query
		}, options, function(content) {
			callback(ChemDoodle.readCIF(content.cif, options.xSuper, options.ySuper, options.zSuper));
		}, errorback);
	};

	iChemLabs.isGraphIsomorphism = function(arrow, target, options, callback, errorback) {
		this._contactServer('isGraphIsomorphism', {
			'arrow' : JSON_INTERPRETER.molTo(arrow),
			'target' : JSON_INTERPRETER.molTo(target)
		}, options, function(content) {
			callback(content.value);
		}, errorback);
	};

	iChemLabs.isSubgraphIsomorphism = function(arrow, target, options, callback, errorback) {
		this._contactServer('isSubgraphIsomorphism', {
			'arrow' : JSON_INTERPRETER.molTo(arrow),
			'target' : JSON_INTERPRETER.molTo(target)
		}, options, function(content) {
			callback(content.value);
		}, errorback);
	};

	iChemLabs.isSupergraphIsomorphism = function(arrow, target, options, callback, errorback) {
		this._contactServer('isSupergraphIsomorphism', {
			'arrow' : JSON_INTERPRETER.molTo(arrow),
			'target' : JSON_INTERPRETER.molTo(target)
		}, options, function(content) {
			callback(content.value);
		}, errorback);
	};

	iChemLabs.getSimilarityMeasure = function(first, second, options, callback, errorback) {
		this._contactServer('getSimilarityMeasure', {
			'first' : JSON_INTERPRETER.molTo(first),
			'second' : JSON_INTERPRETER.molTo(second)
		}, options, function(content) {
			callback(content.value);
		}, errorback);
	};

	iChemLabs.kekulize = function(mol, options, callback, errorback) {
		this._contactServer('kekulize', {
			'mol' : JSON_INTERPRETER.molTo(mol)
		}, options, function(content) {
			callback(JSON_INTERPRETER.molFrom(content.mol));
		}, errorback);
	};

	iChemLabs.optimize = function(mol, options, callback, errorback) {
		this._contactServer('optimize', {
			'mol' : JSON_INTERPRETER.molTo(mol)
		}, options, function(content) {
			var optimized = JSON_INTERPRETER.molFrom(content.mol);
			if (options.dimension === 2) {
				for ( var i = 0, ii = optimized.atoms.length; i < ii; i++) {
					mol.atoms[i].x = optimized.atoms[i].x;
					mol.atoms[i].y = optimized.atoms[i].y;
				}
				callback();
			} else if (options.dimension === 3) {
				for ( var i = 0, ii = optimized.atoms.length; i < ii; i++) {
					optimized.atoms[i].x /= 20;
					optimized.atoms[i].y /= -20;
					optimized.atoms[i].z /= 20;
				}
				callback(optimized);
			}
		}, errorback);
	};

	iChemLabs.readIUPACName = function(iupac, options, callback, errorback) {
		this._contactServer('readIUPACName', {
			'iupac' : iupac
		}, options, function(content) {
			callback(JSON_INTERPRETER.molFrom(content.mol));
		}, errorback);
	};

	iChemLabs.readSMILES = function(smiles, options, callback, errorback) {
		this._contactServer('readSMILES', {
			'smiles' : smiles
		}, options, function(content) {
			callback(JSON_INTERPRETER.molFrom(content.mol));
		}, errorback);
	};

	iChemLabs.saveFile = function(mol, options, callback, errorback) {
		this._contactServer('saveFile', {
			'mol' : JSON_INTERPRETER.molTo(mol)
		}, options, function(content) {
			callback(content.link);
		}, errorback);
	};

	iChemLabs.simulate13CNMR = function(mol, options, callback, errorback) {
		options.nucleus = 'C';
		options.isotope = 13;
		this._contactServer('simulateNMR', {
			'mol' : JSON_INTERPRETER.molTo(mol)
		}, options, function(content) {
			callback(c.readJCAMP(content.jcamp));
		}, errorback);
	};

	iChemLabs.simulate1HNMR = function(mol, options, callback, errorback) {
		options.nucleus = 'H';
		options.isotope = 1;
		this._contactServer('simulateNMR', {
			'mol' : JSON_INTERPRETER.molTo(mol)
		}, options, function(content) {
			callback(c.readJCAMP(content.jcamp));
		}, errorback);
	};

	iChemLabs.simulateMassParentPeak = function(mol, options, callback, errorback) {
		this._contactServer('simulateMassParentPeak', {
			'mol' : JSON_INTERPRETER.molTo(mol)
		}, options, function(content) {
			callback(c.readJCAMP(content.jcamp));
		}, errorback);
	};

	iChemLabs.writeSMILES = function(mol, options, callback, errorback) {
		this._contactServer('writeSMILES', {
			'mol' : JSON_INTERPRETER.molTo(mol)
		}, options, function(content) {
			callback(content.smiles);
		}, errorback);
	};

	iChemLabs.version = function(options, callback, errorback) {
		this._contactServer('version', {}, options, function(content) {
			callback(content.value);
		}, errorback);
	};

	iChemLabs.checkForUpdates = function(options) {
		this._contactServer('checkForUpdates', {
			'value' : location.href
		}, options, function(content) {}, function(){});
	};

})(ChemDoodle, ChemDoodle.iChemLabs, ChemDoodle.io, ChemDoodle.structures, ChemDoodle.lib.jQuery, location);
