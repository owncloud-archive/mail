/**
 * ownCloud - require('app')
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015
 */

define(function(require) {
	'use strict';

	var Marionette = require('marionette'),
		OC = require('OC');

	return Marionette.ItemView.extend({
		template: "#mail-messages-template",
		ui: {
			iconDelete: '.action.delete',
			star: '.star'
		},
		events: {
			"click .action.delete": "deleteMessage",
			"click .mail-message-header": "openMessage",
			"click .star": "toggleMessageStar"
		},
		onRender: function() {
			// Get rid of that pesky wrapping-div.
			// Assumes 1 child element present in template.
			this.$el = this.$el.children();
			// Unwrap the element to prevent infinitely
			// nesting elements during re-render.
			this.$el.unwrap();
			this.setElement(this.$el);

			var displayName = this.model.get('from');
			// Don't show any placeholder if 'from' isn't set
			if (displayName) {
				_.each(this.$el.find('.avatar'), function(a) {
					$(a).height('32px');
					$(a).imageplaceholder(displayName, displayName);
				});
			}

			$('.action.delete').tipsy({gravity: 'e', live: true});
		},
		toggleMessageStar: function(event) {
			event.stopPropagation();

			var starred = this.model.get('flags').get('flagged');

			// directly change star state in the interface for quick feedback
			if (starred) {
				this.ui.star
					.removeClass('icon-starred')
					.addClass('icon-star');
			} else {
				this.ui.star
					.removeClass('icon-star')
					.addClass('icon-starred');
			}
			this.model.flagMessage(
				'flagged',
				!starred
				);
		},
		openMessage: function(event) {
			event.stopPropagation();
			$('#mail-message').removeClass('hidden-mobile');
			require('app').UI.loadMessage(this.model.id, {
				force: true
			});
		},
		deleteMessage: function(event) {
			event.stopPropagation();
			var thisModel = this.model;
			this.ui.iconDelete.removeClass('icon-delete').addClass('icon-loading');
			$('.tipsy').remove();

			thisModel.get('flags').set('unseen', false);

			this.$el.addClass('transparency').slideUp(function() {
				$('.tipsy').remove();
				var thisModelCollection = thisModel.collection;
				var index = thisModelCollection.indexOf(thisModel);
				var nextMessage = thisModelCollection.at(index - 1);
				if (!nextMessage) {
					nextMessage = thisModelCollection.at(index + 1);
				}
				thisModelCollection.remove(thisModel);
				if (require('app').State.currentMessageId === thisModel.id) {
					if (nextMessage) {
						require('app').UI.loadMessage(nextMessage.id);
					}
				}
				// manually trigger mouseover event for current mouse position
				// in order to create a tipsy for the next message if needed
				if (event.clientX) {
					$(document.elementFromPoint(event.clientX, event.clientY)).trigger('mouseover');
				}
			});

			// really delete the message
			$.ajax(
				OC.generateUrl('apps/mail/accounts/{accountId}/folders/{folderId}/messages/{messageId}',
					{
						accountId: require('app').State.currentAccountId,
						folderId: require('app').State.currentFolderId,
						messageId: thisModel.id
					}), {
				data: {},
				type: 'DELETE',
				success: function() {
					var app = require('app');
					app.Cache.removeMessage(app.State.currentAccountId, app.State.currentFolderId, thisModel.id);
				},
				error: function() {
					require('app').UI.showError(t('mail', 'Error while deleting message.'));
				}
			});
		}
	});
});
