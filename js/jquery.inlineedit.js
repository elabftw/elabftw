/*
 * jQuery inlineEdit
 *
 * Copyright (c) 2009 Ca-Phun Ung <caphun at yelotofu dot com>
 * Licensed under the MIT (MIT-LICENSE.txt) license.
 *
 * http://github.com/caphun/jquery.inlineedit/
 *
 * Inline (in-place) editing.
 */

(function( factory ) {
    if ( typeof define === 'function' && define.amd ) {
        define( ['jquery'], factory );
    } else {
        factory( jQuery );
    }
}(function( $ ) {

// cached values
var namespace = '.inlineedit',
    placeholderClass = 'inlineEdit-placeholder',
    events = ['click', 'mouseenter','mouseleave'].join(namespace+' ');

// define inlineEdit method
$.fn.inlineEdit = function( options ) {

    this.each( function() {
        $.inlineEdit.getInstance( this, options ).initValue();
    });

    var cbBindings = function( event ) {
            bindings.apply( this, [event] );
        };

    if ($.fn.on) {
        $(this.context).on( events, this.selector, cbBindings );
    } else {
        // legacy support
        $(this).live( events, cbBindings );
    }

    function bindings( event ) {
        var widget = $.inlineEdit.getInstance( this, options ),
            editableElement = widget.element.find( widget.options.control ),
            mutated = !!editableElement.length;

        widget.element.removeClass( widget.options.hover );
        if ( editableElement[0] != event.target  && editableElement.has(event.target).length == 0 ) {
            switch ( event.type ) {
                case 'click':
                    widget[ mutated ? 'mutate' : 'init' ]();
                    break;

                case 'mouseover': // jquery 1.4.x
                case 'mouseout': // jquery 1.4.x
                case 'mouseenter':
                case 'mouseleave':
                    if ( !mutated ) {
                        widget.hoverClassChange( event );
                    }
                    break;
            }
        }
    }
}

// plugin constructor
$.inlineEdit = function( elem, options ) {

    // deep extend
    this.options = $.extend( true, {}, $.inlineEdit.defaults, options );

    // the original element
    this.element = $( elem );

}

// plugin instance
$.inlineEdit.getInstance = function( elem, options ) {
    return ( $.inlineEdit.initialised( elem ) ) 
    ? $( elem ).data( 'widget' + namespace )
    : new $.inlineEdit( elem, options );
}

// check if plugin initialised
$.inlineEdit.initialised = function( elem ) {
    var init = $( elem ).data( 'init' + namespace );
    return init !== undefined && init !== null ? true : false;
}

// plugin defaults
$.inlineEdit.defaults = {
    hover: 'ui-state-hover',
    editInProgress: 'edit-in-progress',
    value: '',
    save: '',
    cancel: '', 
    buttons: '<button class="save">save</button> <button class="cancel">cancel</button>',
    placeholder: 'Click to edit',
    control: 'input',
    cancelOnBlur: false,
    saveOnBlur: false,
    nl2br: true,
    debug: false
};

// plugin prototypes
$.inlineEdit.prototype = {

    // initialisation
    init: function() {

        // set initialise flag
        this.element.data( 'init' + namespace, true );
    
        // initialise value
        this.initValue();

        // mutate
        this.mutate();
    
        // save widget data
        this.element.data( 'widget' + namespace, this );

    },

    initValue: function() {

        this.value( $.trim( this.element.data('original-content') || this.element.html() ) || this.options.value );
    
        if ( !this.value() ) {
            this.element.html( $( this.placeholderHtml() ) );
        } else if ( this.options.value ) {
            this.element.html( this.options.value );
        }
    },
    
    mutate: function() {
        var self = this;
        //console.log('mutate', self.value());

        // save a copy of self before mutation (useful for cancel)
        self.prevValue( self.element.html() );

        return self
            .element
            .html( self.mutatedHtml( self.value() ) )
            .addClass( self.options.editInProgress )
            .find( '.save' )
                .bind( 'click', function( event ) {
                    self.save( self.element, event );
                    self.change( self.element, event );
                    return false;
                })
            .end()
            .find( '.cancel' )
                .bind( 'click', function( event ) {
                    self.cancel( self.element, event );
                    self.change( self.element, event );
                    return false;
                })
            .end()
            .find( self.options.control )
                .bind( 'blur', function( event ) {
                  if (self.options.cancelOnBlur === true) {
                    self.cancel( self.element, event );
                    self.change( self.element, event );
                  } else if (self.options.saveOnBlur == true){
                    self.save( self.element, event );
                    self.change( self.element, event );
                  }
                })
                .bind( 'keyup', function( event ) {
                    switch ( event.keyCode ) {
                        case 13: // save on ENTER
                            if (self.options.control !== 'textarea') {
                                self.save( self.element, event );
                                self.change( self.element, event );
                            }
                            break;
                        case 27: // cancel on ESC
                            self.cancel( self.element, event );
                            self.change( self.element, event );
                            break;
                    }
                })
                .focus()
            .end();
    },
    
    value: function( newValue ) {
        if ( arguments.length ) {
            var value = newValue == this.placeholderHtml() ? '' : newValue;
            this._debug('value:','to save', value);
            this.element.data( 'value' + namespace, value && this.encodeHtml( this.nl2br(value) ) );
            this._debug('value:','saved', this.element.data( 'value' + namespace ));
        }
        return this._decodeHtml( this.element.data( 'value' + namespace) );
    },

    prevValue: function( newValue ) {
        if ( arguments.length ) {
            var value = newValue === this.options.placeholder ? '' : newValue;
            this.element.data('prev_value' + namespace, value);
        }
        return this.element.data( 'prev_value' + namespace );
    },

    mutatedHtml: function( value ) {
        //console.log('mutatedHtml', value);
        return this.controls[ this.options.control ].call( this, value );
    },

    placeholderHtml: function() {
        return '<span class="'+ placeholderClass +'">'+ this.options.placeholder +'</span>';
    },

    buttonHtml: function( options ) {
        var o = $.extend({}, {
            before: ' ',
            buttons: this.options.buttons,
            after: ''
        }, options);
        
        return o.before + o.buttons + o.after;
    },

    save: function( elem, event ) {
        var $control = this.element.find( this.options.control ), 
            hash = {
                value: this.encodeHtml( $control.val() )
            };

        this._debug('save:',"Saving...");

        // save value back to control to avoid XSS
        $control.val(hash.value);
        
        //if ( ( $.isFunction( this.options.save ) && this.options.save.call( this.element[0], event, hash, this ) ) !== false || !this.options.save ) {
        if (this._callback('save', [event, hash, this])) {
            this.value( hash.value );
            this._debug( 'save:', 'Current stored value', this.value() );
        }
    },
    
    cancel: function( elem, event ) {
        var $control = this.element.find( this.options.control ), 
            hash = {
                value: this.encodeHtml( $control.val() )
            };

        this._debug('cancel:', "Cancelling...");

        //if ( ( $.isFunction( this.options.cancel ) && this.options.cancel.call( this.element[0], event, hash, this ) ) !== false || !this.options.cancel ) {
        if (this._callback('cancel', [event, hash, this])) {
            this.value( this.prevValue() ); // put back previous value
            this._debug( 'cancel:', 'Current stored value', this.value() );
        }
    },
    
    change: function( elem, event ) {
        var self = this;
        
        if ( this.timer ) {
            window.clearTimeout( this.timer );
        }

        this.timer = window.setTimeout( function() {
            self._debug( 'change:', 'Change', self.value() );
            self.element.html( self.value() || self.placeholderHtml() );
            self.element.removeClass( self.options.hover );
            self.element.removeClass( self.options.editInProgress );
            self._callback( 'change', [event, self] );
            self._debug( 'change:', 'Change complete' );
        }, 200 );

    },

    controls: {
        textarea: function( value ) {
            return '<textarea>'+ this.br2nl(value) +'</textarea>' + this.buttonHtml( { before: '<br />' } );
        },
        input: function( value ) {
            return '<input type="text" value="'+ value.replace(/(\u0022)+/g, '') +'"/>' + this.buttonHtml();
        }
    },

    hoverClassChange: function( event ) {
        $( this.element )[ /mouseover|mouseenter/.test( event.type ) ? 'addClass':'removeClass']( this.options.hover );
    },
    
    encodeHtml: function( s ) {
        var encoding = [
              {key: /</g, value: '<'},
              {key: />/g, value: '>'},
              {key: /"/g, value: '&quot;'}
            ],
            value = s;

        $.each(encoding, function(i,n) {
          value = value.replace(n.key, n.value);
        });

        return value;
    },

    br2nl: function( val ) {
        return this.options.nl2br ? val.replace( /<br\s?\/?>/g, "\n" ) : val;
    },

    nl2br: function( val ) {
        return this.options.nl2br ? val.replace( /\n/g, "<br />" ) : val;
    },

    _debug: function() {

        if (this.options && this.options.debug) {
            return window.console && console.log.call(console, arguments);
        }

    },

    _callback: function( fn, args ) {
        return ($.isFunction( this.options[fn] ) && this.options[fn].apply( this.element[0], args ) ) !== false || !this.options[fn];
    },

    _decodeHtml: function( encoded ) {
        var decoded = encoded.replace(/&quot;/g,'"');
        this._debug('_decodeHtml:', decoded);
        return decoded;
    }

};

}));