/* global Backbone, Marionette, Mail, models, OC */

define(function(require) {
	return Backbone.Marionette.ItemView.extend({
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
			_.each(this.$el.find('.avatar'), function(a) {
				$(a).height('32px');
				$(a).imageplaceholder(displayName, displayName);
			});
		},
		toggleMessageStar: function(event) {
			event.stopPropagation();

			var messageId = this.model.id;
			var starred = this.model.get('flags').get('flagged');
			var thisModel = this.model;

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

			$.ajax(
				OC.generateUrl('apps/mail/accounts/{accountId}/folders/{folderId}/messages/{messageId}/toggleStar',
					{
						accountId: require('Mail').State.currentAccountId,
						folderId: require('Mail').State.currentFolderId,
						messageId: messageId
					}), {
				data: {
					starred: starred
				},
				type: 'POST',
				success: function() {
					thisModel.get('flags').set('flagged', !starred);
				},
				error: function() {
					require('Mail').UI.showError(t('mail', 'Message could not be starred. Please try again.'));
					thisModel.get('flags').set('flagged', starred);
				}
			});
		},
		openMessage: function(event) {
			event.stopPropagation();
			$('#mail-message').removeClass('hidden-mobile');
			require('Mail').UI.loadMessage(this.model.id, {
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
				if (require('Mail').State.currentMessageId === thisModel.id) {
					if (nextMessage) {
						require('Mail').UI.loadMessage(nextMessage.id);
					}
				}
				// manually trigger mouseover event for current mouse position
				// in order to create a tipsy for the next message if needed
				$(document.elementFromPoint(event.clientX, event.clientY)).trigger('mouseover');
			});

			// really delete the message
			$.ajax(OC.generateUrl('apps/mail/accounts/{accountId}/folders/{folderId}/messages/{messageId}',
				{
					accountId: require('Mail').State.currentAccountId,
					folderId: require('Mail').State.currentFolderId,
					messageId: thisModel.id
				}), {
				data: {},
				type: 'DELETE',
				success: function() {
					require('Mail').Cache.removeMessage(require('Mail').State.currentAccountId, require('Mail').State.currentFolderId, thisModel.id);
				},
				error: function() {
					require('Mail').UI.showError(t('mail', 'Error while deleting message.'));
				}
			});
		}

	});
});