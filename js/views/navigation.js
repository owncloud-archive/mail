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

	var $ = require('jquery');
	var Marionette = require('marionette');
	var Handlebars = require('handlebars');
	var Radio = require('radio');
	var NewMessageView = require('views/newmessage');
	var NavigationAccountsView = require('views/navigation-accounts');
	var SettingsView = require('views/settings');
	var NavigationTemplate = require('text!templates/navigation.html');

	return Marionette.LayoutView.extend({
		template: Handlebars.compile(NavigationTemplate),
		regions: {
			newMessage: '#mail-new-message-fixed',
			accounts: '#app-navigation-accounts',
			settings: '#app-settings-content'
		},
		initialize: function(options) {
			this.listenTo(Radio.ui, 'navigation:show', this.show);
			this.listenTo(Radio.ui, 'navigation:hide', this.hide);
		},
		show: function() {
			this.$el.show();
			$('#app-navigation-toggle').css('background-image', '');
		},
		onShow: function() {
			var accounts = require('state').accounts;
			// setup new message view
			this.newMessage.show(new NewMessageView({
				accounts: accounts
			}));

			// setup folder view
			this.accounts.show(new NavigationAccountsView({
				collection: accounts
			}));

			// setup settings view
			this.settings.show(new SettingsView({
				accounts: accounts
			}));
		},
		hide: function() {
			// TODO: move if or rename function
			if (require('state').accounts.length === 0) {
				this.$el.hide();
				$('#app-navigation-toggle').css('background-image', 'none');
			}
		}
	});
});
