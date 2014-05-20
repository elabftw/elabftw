/*!
 * jQuery pub/sub plugin v1.0
 * Copyright (c) 2011 Tubal Martin tubalmartin@gmail.com
 * Licensed under GNU GPL3 http://www.gnu.org/licenses/gpl-3.0.txt
 *//*
 * Requires jQuery 1.1+
 *
 * Inspired by Dojo's, Google Closure's & Morgan Roderick's pubsub implementation:
 * - http://bugs.dojotoolkit.org/browser/dojo/trunk/_base/connect.js
 * - http://closure-library.googlecode.com/svn/docs/class_goog_pubsub_PubSub.html
 * - https://github.com/mroderick/PubSubJS/blob/master/pubsub.js
 */
;(function($, win, undefined) {

	if (!$) throw new Error("jQuery is missing, load it before any jQuery plugin!!");

		// Topics/subscriptions hash
	var topics = {},

		// Array of subscriptions pending removal once publishing is done.
		pendingUnsubscriptions = [],

		// Lock to prevent the removal of subscriptions during publishing.
		publishDepth = 0,

		// Events namespace
		NS = ".publisher",

		// Set best method to attach/dettach a handler to an event for the elements.
		// Compatible since jQuery 1.0+ and optimized for jQuery 1.7+
		BIND   = !!$.fn.on  ? "on"  : "bind",
		UNBIND = !!$.fn.off ? "off" : "unbind";


	/**
	 * @description Publishes some data on a named topic.
	 * @param {String} topic The topic to publish.
	 * @param {Object} [data] The data to publish.
	 * @param {Boolean} [sync] Whether publish topics in order of execution (sync = true) or asynchronously (default).
	 * @returns {jQuery} returns jQuery object.
	 * @example
	 * $( "#sidebarWidget" ).publish( "/some/topic", {key: value} );
	 */
	$.fn.publish = function(topic, data, sync) {
		var that = this,
			publish = function() {
				var Subscriptions = topics[topic], l, pu;

				if (!Subscriptions) return;

				// The length of the topics array must be fixed during the iteration, since
				// new subscriptions may be added during publishing
				l = Subscriptions.length;

				// We must lock unsubscriptions and remove them at the end
				publishDepth += 1;

				that.each(function(i, obj) {
					// Let's add a reference to the publisher object
					data["Publisher"] = obj;

					for (var i = 0; i < l; i++) {
						Subscriptions[i][0] && Subscriptions[i][1].call(Subscriptions[i][0], data);
					}
				});

				// Unlock unsubscriptions.
				publishDepth -= 1;

				if (pendingUnsubscriptions.length > 0 && publishDepth === 0) {
					while ((pu = pendingUnsubscriptions.pop())) {
						pu[0] && pu[0].unsubscribe(pu[1]);
					}
				}
			};

		data = data || {};

		if (sync === true) {
			publish();
		} else {
			win.setTimeout(publish, 0);
		}

		return this;
	};


	/**
	 * @description Publishes some data on a named topic synchronously (order of execution is guaranteed).
	 * @param {String} topic The topic to publish.
	 * @param {Object} [data] The data to publish.
	 * @returns {jQuery} returns jQuery object.
	 * @example
	 * $( "#sidebarWidget" ).publish( "/some/topic", {key: value} );
	 */
	$.fn.publishSync = function(topic, data) {
		return this.publish(topic, data, true);
	};


	/**
	 * @description Registers a callback on a named topic.
	 * @param {String} topic The topic to subscribe to.
	 * @param {Function} callback The handler. Anytime a previously subscribed topic is published,
	 * the callback will be called with the published data map as its argument.
	 * @returns {jQuery} returns jQuery object.
	 * @example
	 * $( "#header" ).subscribe( "/some/topic", function( data ) { // handle data } );
	 */
	$.fn.subscribe = function(topic, callback) {
		if (!topics[topic]) {
			if (typeof topic != "string") throw new Error("Invalid topic. Topics must be of type 'string'.");
			if (/\s/.test(topic)) throw new Error("Invalid topic. Topics cannot contain spaces.");
			topics[topic] = [];
		}

		this.each(function(i, obj) {
			topics[topic].push([ obj, callback ]);
		});

		return this;
	};


	/**
	 * @description Registers a single-use callback to a topic.
	 * @param {String} topic The topic to subscribe to.
	 * @param {Function} callback The handler. Anytime a previously subscribed topic is published,
	 * the callback will be called with the published data map as its argument.
	 * @returns {jQuery} returns jQuery object.
	 * @example
	 * $( "#header" ).subscribeOnce( "/some/topic", function( data ) { // handle data } );
	 */
	$.fn.subscribeOnce = function(topic, callback) {
		var that = this;
		return this.subscribe(topic, function(data) {
			that.unsubscribe(topic);
			callback.call(this, data);
		});
	};


	/**
	 * @description Unregisters a previously registered callback for a named topic.
	 * @param {String} topic The topic to unsubscribe from.
	 * @returns {jQuery} returns jQuery object.
	 * @example
	 * $( "#header" ).unsubscribe( "/some/topic" );
	 */
	$.fn.unsubscribe = function(topic) {
		if (!topics[topic]) {
			return this;
		}

		// Defer removal until publishing is complete.
		if (publishDepth > 0) {
			pendingUnsubscriptions.push([ this, topic ]);
			return this;
		}

		this.each(function(i, obj) {
			topics[topic] = $.grep(topics[topic], function(el, j) {
				return el[0] && obj !== el[0];
			});
		});

		if (topics[topic].length === 0) {
			delete topics[topic];
		}

		return this;
	};


	/**
	 * @description Publish some data on a named topic every time an event happens on an object.
	 * @param {String} events The event(s) that should trigger the publishing
	 * @param {String} topic The topic to publish.
	 * @param {Object} [data] The data to publish.
	 * @param {Boolean} [sync] Whether publish topics in order of execution (sync) or when possible (sync = false).
	 * @returns {jQuery} returns jQuery object.
	 * @example
	 * $( "#sidebarWidget" ).bindPublisher( "click mouseover", "/some/topic", {key: value} );
	 */
	$.fn.bindPublisher = function(events, topic, data, sync) {
		var that = this;
		events = events.split(" ").join(NS + " ") + NS;

		return this[arguments[4] || BIND](events, function(e) {
			that.publish(topic, data || {}, sync);
			// Prevent default action and bubbling
			return false;
		});
	};


	/**
	 * @description Publish, only once, some data on a named topic when an event happens on an object.
	 * @param {String} events The event(s) that should trigger the publishing
	 * @param {String} topic The topic to publish.
	 * @param {Object} [data] The data to publish.
	 * @param {Boolean} [sync] Whether publish topics in order of execution (sync) or when possible (sync = false).
	 * @returns {jQuery} returns jQuery object.
	 * @example
	 * $( "#sidebarWidget" ).bindPublisherOnce( "click mouseover", "/some/topic", {key: value} );
	 */
	$.fn.bindPublisherOnce = function(events, topic, data, sync) {
		return this.bindPublisher(events, topic, data, sync, "one");
	};


	/**
	 * @description Stop publishing some data on a named topic every time an specified event happens on an object.
	 * @param {String} event The event that triggered the publishing
	 * @returns {jQuery} returns jQuery object.
	 * @example
	 * To unbind just the click event from publishing a topic
	 * $( "#sidebarWidget" ).unbindPublisher( "click" );
	 * To unbind all events at once:
	 * $( "#sidebarWidget" ).unbindPublisher();
	 */
	$.fn.unbindPublisher = function(event) {
		return this[UNBIND](!!event ? event + NS : NS);
	};

}(window.jQuery, window));