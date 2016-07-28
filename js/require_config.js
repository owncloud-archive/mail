/* global requirejs */

/**
 * ownCloud - Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015, 2016
 */

(function() {
	'use strict';

	requirejs.config({
		baseUrl: './../../../apps/mail/js',
		paths: {
			/**
			 * Libraries
			 */
			backbone: 'vendor/backbone/backbone',
			'backbone.radio': 'vendor/backbone.radio/build/backbone.radio',
			davclient: 'vendor/davclient.js/lib/client',
			domready: 'vendor/domReady/domReady',
			'es6-promise': 'vendor/es6-promise/es6-promise.min',
			hammerjs: 'vendor/hammerjs/hammer',
			handlebars: 'vendor/handlebars/handlebars',
			ical: 'vendor/ical.js/build/ical.min',
			'jquery.hammerjs': 'vendor/jquery-hammerjs/jquery.hammer',
			marionette: 'vendor/backbone.marionette/lib/backbone.marionette',
			underscore: 'vendor/underscore/underscore',
			text: 'vendor/text/text',
			wptr: 'vendor/web-pull-to-refresh/lib/wptr.1.1'
		},
		shim: {
			davclient: {
				exports: 'dav'
			},
			ical: {
				exports: 'ICAL'
			},
			wptr: {
				exports: 'WebPullToRefresh',
				deps: [
					'hammerjs'
				]
			}
		}
	});

	require([
		'app',
		'notification'
	]);
})();
