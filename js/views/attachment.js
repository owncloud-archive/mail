/**
 * ownCloud - Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015
 */

define(function(require) {
	'use strict';

	var Marionette = require('marionette');

	return Marionette.ItemView.extend({
		tagName: 'li',
		template: '#mail-attachment-template',
		events: {
			'click .icon-delete': 'removeAttachment'
		},
		removeAttachment: function() {
			this.model.collection.remove(this.model);
		}

	});
});
