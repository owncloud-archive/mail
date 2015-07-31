/* global requirejs */

/**
 * ownCloud - Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015
 */

requirejs.config({
	baseUrl: '/apps/mail/js',
	paths: {
		/**
		 * Application
		 */
		Mail: 'mail',
		/**
		 * OC core js
		 */
		OC: '../../../core/js/js',
		/**
		 * Libraries
		 */
		domReady: 'node_modules/domready/ready.min',
		//jquery: '../../../core/vendor/jquery/jquery.min',
		storage: 'jquery.storageapi',
		underscore: '../../../core/vendor/underscore/underscore'
	},
	shim: {
		OC: {
			exports: 'OC'
		}
	}
});

require([
	'init',
	'notification'
]);