/**
 * ownCloud - Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2016
 */

define(function(require) {
	'use strict';

	var Marionette = require('marionette');
	var $ = require('jquery');
	var OC = require('OC');
	var Radio = require('radio');
	var MessageContentView = require('views/messagecontent');
	var LoadingView = require('views/loadingview');
	var NavigationView = require('views/navigation');
	var SetupView = require('views/setup');

	var ContentType = Object.freeze({
		LOADING: -1,
		MESSAGE_CONTENT: 0,
		SETUP: 1
	});

	var AppView = Marionette.LayoutView.extend({
		accountsView: null,
		activeContent: null,
		regions: {
			navigation: '#app-navigation',
			content: '#app-content',
			setup: '#setup'
		},
		initialize: function() {
			this.bindUIElements();

			// Global event handlers:
			this.listenTo(Radio.notification, 'favicon:change', this.changeFavicon);
			this.listenTo(Radio.ui, 'notification:show', this.showNotification);
			this.listenTo(Radio.ui, 'error:show', this.showError);
			this.listenTo(Radio.ui, 'setup:show', this.showSetup);
			this.listenTo(Radio.ui, 'messagecontent:show', this.showMessageContent);
			this.listenTo(Radio.ui, 'navigation:loading', this.showNavigationLoading);
			this.listenTo(Radio.ui, 'navigation:show', this.showNavigation);
			this.listenTo(Radio.ui, 'content:loading', this.showContentLoading);

			// Hide notification favicon when switching back from
			// another browser tab
			$(document).on('show', this.onDocumentShow);

			// Listens to key strokes, and executes a function based
			// on the key combinations.
			$(document).keyup(this.onKeyUp);

			window.addEventListener('resize', this.onWindowResize);
		},
		onDocumentShow: function(e) {
			e.preventDefault();
			Radio.notification.trigger('favicon:change', OC.filePath('mail', 'img', 'favicon.png'));
		},
		onKeyUp: function(e) {
			// Define which objects to check for the event properties.
			// (Window object provides fallback for IE8 and lower.)
			e = e || window.e;
			var key = e.keyCode || e.which;
			// If the client is currently viewing a message:
			if (require('state').currentMessageId) {
				if (key === 46) {
					// If delete key is pressed:
					// If not composing a reply
					// and message list is visible (not being in a settings dialog)
					// and if searchbox is not focused
					if (!$('.to, .cc, .message-body').is(':focus') &&
						$('#mail-messages').is(':visible') &&
						!$('#searchbox').is(':focus')) {
						// Mimic a client clicking the delete button for the currently active message.
						$('.mail-message-summary.active .icon-delete.action.delete').click();
					}
				}
			}
		},
		onWindowResize: function() {
			// Resize iframe
			var iframe = $('#mail-content iframe');
			iframe.height(iframe.contents().find('html').height() + 20);

			// resize width of attached images
			$('.mail-message-attachments .mail-attached-image').each(function() {
				$(this).css('max-width', $('.mail-message-body').width());
			});
		},
		render: function() {
			// This view doesn't need rendering
		},
		changeFavicon: function(src) {
			$('link[rel="shortcut icon"]').attr('href', src);
		},
		showNotification: function(message) {
			OC.Notification.showTemporary(message);
		},
		showError: function(message) {
			OC.Notification.showTemporary(message);
			$('#app-navigation').removeClass('icon-loading');
			$('#app-content').removeClass('icon-loading');
			$('#mail_message').removeClass('icon-loading');
		},
		showSetup: function() {
			if (this.activeContent !== ContentType.SETUP) {
				this.activeContent = ContentType.SETUP;

				this.content.show(new SetupView({
					displayName: $('#user-displayname').text(),
					email: $('#user-email').text()
				}));
			}
		},
		showMessageContent: function() {
			if (this.activeContent !== ContentType.MESSAGE_CONTENT) {
				this.activeContent = ContentType.MESSAGE_CONTENT;

				var messageContentView = new MessageContentView();
				var accountsView = this.navigation.currentView.accounts;
				accountsView.listenTo(messageContentView.messages, 'change:unseen',
					accountsView.changeUnseen);
				this.content.show(messageContentView);
			}
		},
		showNavigation: function() {
			this.navigation.show(new NavigationView());
		},
		showNavigationLoading: function() {
			this.navigation.show(new LoadingView());
		},
		showContentLoading: function() {
			if (this.activeContent !== ContentType.LOADING) {
				this.activeContent = ContentType.LOADING;
				this.content.show(new LoadingView());
			}
		}
	});

	return AppView;
});
