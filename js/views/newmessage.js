/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * ownCloud - Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

define(function(require) {
	'use strict';

	var Marionette = require('marionette');
	var Handlebars = require('handlebars');
	var NewMessageTemplate = require('text!templates/newmessage.html');

	return Marionette.LayoutView.extend({
		template: Handlebars.compile(NewMessageTemplate),
		events: {
			'click input': 'onClick'
		},
		initialize: function(options) {
			console.log(arguments);
			//this.listenTo(options.accounts, 'change', this.render);
		},
		onClick: function(e) {
			e.preventDefault();
			require('ui').openComposer();
		}
	});
});
