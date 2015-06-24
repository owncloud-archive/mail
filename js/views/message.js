/* global Backbone, Marionette, Mail, models */

var views = views || {};

$('.action.delete').tipsy({gravity:'e', live:true});
$('.tipsy-mailto').tipsy({gravity:'n', live:true});

views.DetailedMessage = Backbone.Marionette.ItemView.extend({
	template: "#mail-message-template"
});

views.Message = Backbone.Marionette.ItemView.extend({

	template: "#mail-messages-template",

	ui:{
		iconDelete : '.action.delete',
		star : '.star'
	},

	events: {
		"click .mail-message-header" : "openMessage",
		"click .action.delete" : "deleteMessage",
		"click .star" : "toggleMessageStar"
	},

	initialize: function (){
		//_.bindAll(this, 'setMessageFlag');
		//this.listenTo(this.model, 'change', this.render, this);

		// AFAIK, this changes nothing.
		this.model.bind('change', this.render, this);
	},

	onRender: function () {
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
		// Stop any event handlers below this element from being fired.
		event.stopPropagation();

		var thisModel = this.model;
		var messageId = this.model.id;
		var starred = this.model.attributes.flags.flagged;

		// Change star state in the interface for quick feedback.
		if(starred) {
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
				accountId: Mail.State.currentAccountId,
				folderId: Mail.State.currentFolderId,
				messageId: messageId
			}), {
				data: {
					starred: starred
				},
				type:'POST',
				success: function () {
					// Retrieve the starred/flag status from the backend and display the results.
					Mail.UI.messageView.loadMessages();
				},
				error: function() {
					Mail.UI.showError(t('mail', 'Message could not be starred. Please try again.'));
					// Change star state in the interface to it's previous state.
					thisModel.get('flags').set('flagged', starred);
				}
			});
	},

	openMessage: function(event) {
		event.stopPropagation();
		$('#mail-message').removeClass('hidden-mobile');
		Mail.UI.loadMessage(this.model.id, {
			force: true
		});

		var message = this.model;
		this.setMessageFlag(message, 'unseen');
	},

	/**
	 *
	 * @todo Combine toggleMessageStar.
	 * @todo Fully integrate object handlers.
	 * @todo add flag handlers for: flagged, answered, deleted, draft, forwarded, hasAttachments
	 * @param object $message Uses the object for obtaining the ID.
	 * @param string $flag unseen
	 * @param boolean $value Default is to toggle, otherwise use defined value.
	 */
	setMessageFlag: function(message, flag, value) {
		var messageId = message.id;
		var unseen;
		message = this.$el;

		// Reusable function that provides immediate feedback while AJAX is loading.
		var setUnseenCss = function (unseen) {
			if (unseen) {
				message.removeClass('unseen');
			} else {
				message.addClass('unseen');
			}
		};

		if (flag === 'unseen') {
			// If there is no manual selection/value, then toggle.
			if (_.isUndefined(value)) {
				unseen = !message.is('.unseen');
				setUnseenCss(unseen);
			} else {
				unseen = value;
				setUnseenCss(unseen);
			}
			$.ajax(
				OC.generateUrl('apps/mail/accounts/{accountId}/folders/{folderId}/messages/{messageId}/toggleUnseen',
				{	accountId: Mail.State.currentAccountId,
					folderId: Mail.State.currentFolderId,
					messageId: messageId
				}), {
					data: {
						unseen: unseen
					},
					type:'POST',
					success: function () {
						// Refresh the message list to display the updates.
						Mail.UI.messageView.loadMessages();
					},
					error: function() {
						Mail.UI.showError(t('mail', 'Could not communicate with the mail server. Please try again.'));
						// Change unseen state in the interface to it's previous state.
						setUnseenCss(unseen);
					}
				});
		} else {
			// @todo Add new flag methods here.
		}
	},

	deleteMessage: function(event) {
		event.stopPropagation();
		var thisModel = this.model;
		this.ui.iconDelete.removeClass('icon-delete').addClass('icon-loading');
		$('.tipsy').remove();

		this.$el.addClass('transparency').slideUp(function() {
			$('.tipsy').remove();
			var thisModelCollection = thisModel.collection;
			var index = thisModelCollection.indexOf(thisModel);
			var nextMessage = thisModelCollection.at(index-1);
			if (!nextMessage) {
				nextMessage = thisModelCollection.at(index+1);
			}
			thisModelCollection.remove(thisModel);
			if (Mail.State.currentMessageId === thisModel.id) {
				if (nextMessage) {
					Mail.UI.loadMessage(nextMessage.id);
				}
			}
			// manually trigger mouseover event for current mouse position
			// in order to create a tipsy for the next message if needed
			$(document.elementFromPoint(event.clientX, event.clientY)).trigger('mouseover');
		});

		// really delete the message
		$.ajax(
			OC.generateUrl('apps/mail/accounts/{accountId}/folders/{folderId}/messages/{messageId}',
				{
				accountId: Mail.State.currentAccountId,
				folderId: Mail.State.currentFolderId,
				messageId: thisModel.id
			}), {
				data: {},
				type:'DELETE',
				success: function () {
					// delete local storage draft
					var storage = $.localStorage;
					var draftId = 'draft' +
						'.' + Mail.State.currentAccountId.toString() +
						'.' + Mail.State.currentFolderId.toString() +
						'.' + thisModel.id;
					if (storage.isSet(draftId)) {
						storage.remove(draftId);
					}
				},
				error: function() {
					Mail.UI.showError(t('mail', 'Error while deleting message.'));
				}
			});
	}
});

views.NoSearchResultMessageListView = Marionette.ItemView.extend({
	initialize: function(options) {
		this.model.set('searchTerm', options.filterCriteria.text || "");
	},

	template: "#no-search-results-message-list-template",

	onRender: function() {
	}
});

views.Messages = Backbone.Marionette.CompositeView.extend({

	collection: null,

	childView: views.Message,

	childViewContainer: '#mail-message-list',

	currentMessageId: null,

	events: {
	},

	filterCriteria: null,

	template: "#message-list-template",

	initialize: function() {
		this.collection = new models.MessageList();
	},

	getEmptyView: function() {
		if (this.filterCriteria) {
			return views.NoSearchResultMessageListView;
		}
		return views.template;
	},

	emptyViewOptions: function () {
		return { filterCriteria: this.filterCriteria };
	},

	setActiveMessage: function(messageId) {
		// Set active class for current message and remove it from old one

		var message = null;
		if(this.currentMessageId !== null) {
			message = this.collection.get(this.currentMessageId);
			if (message) {
				message.set('active', false);
			}
		}

		this.currentMessageId = messageId;

		if(messageId !== null) {
			message = this.collection.get(this.currentMessageId);
			if (message) {
				message.set('active', true);
			}
		}

	},

	filterCurrentMailbox: function(query) {
		this.filterCriteria = {
			text: query
		};
		this.loadMessages();
	},

	clearFilter: function() {
		$('#searchbox').val('');
		this.filterCriteria = null;
	},

	/**
	 * Refreshes the message list of the current folder by default.
	 * @param boolean|string $addMore Add 20 more messages to the message list.
	 */
	loadMessages: function(addMore) {
		addMore = addMore || false;
		var element = $('#load-more-mail-messages');
		var outerHeight = element.outerHeight();
		var offset = element.offset();
		var scrollLength = $('#mail_messages').scrollTop();
		var messageListHeight = scrollLength + offset.top + outerHeight;
		var from = 0;
		var to;

		// If there are no more messages to load, stop here.
		if (addMore && element.hasClass('noNewMessages')) {
			return;
		}

		// Prepare to retrieve the next 20 older messages.
		if (addMore){
			from = this.collection.size();
			to = from + 20;
			// Show the loading element to notify of the message retrieval.
			if (!element.is(':visible')) {
				element
					.show(1, function() {
						// Scroll to the notification.
						$('#mail_messages').animate({ scrollTop: messageListHeight }, 1);
					});
			}
		} else  {
			// Refresh the message list to display any updates.
			from = 0;
			to = this.collection.size() - 1;
		}

		var url = OC.generateUrl(
			'apps/mail/accounts/{accountId}/folders/{folderId}/messages?from={from}&to={to}',
			{
				'accountId': Mail.State.currentAccountId,
				'folderId':Mail.State.currentFolderId,
				'from': from,
				'to': to
			});
		if (this.filterCriteria) {
			url = OC.generateUrl(
				'apps/mail/accounts/{accountId}/folders/{folderId}/messages?filter={query}&from={from}&to={to}',
				{
					'accountId': Mail.State.currentAccountId,
					'folderId':Mail.State.currentFolderId,
					'query': this.filterCriteria.text,
					'from': from,
					'to': to
				});
		}
		$.ajax(url, {
				data: {},
				type:'GET',
				success: function (jsondata) {
					if (addMore){
						// Add 20 more messages to the message list.
						Mail.UI.messageView.collection.add(jsondata);
						// Compare sizes to see if anything new is being retrieved.
						var newSize = Mail.UI.messageView.collection.size();
						var oldSize = from;
						// If there are no older messages, this CSS class stops future processing.
						if (newSize === oldSize) {
							element.addClass('noNewMessages');
						}
					} else {
						// Refresh the message list.
						Mail.UI.messageView.collection.set(jsondata);
						// Update the unseen counter for the current folder.
						Mail.State.folderView.changeUnseenCount(this);
						// Update the title.
						Mail.State.folderView.updateTitle();
					}

					$('#app-content').removeClass('icon-loading');
					// Set the active message to the message currently open.
					Mail.UI.setMessageActive(Mail.State.currentMessageId);
					// Hide and reset our loading/error element.
					if (element.is(':visible')) {
						element
							.hide()
							.addClass('icon-loading')
							.html('');
					}
				},
				error: function() {
					// Prepare our loading/error element to display the error notification.
					if (element.hasClass('icon-loading')) {
						element
							.removeClass('icon-loading')
							.html("<span>" + t('mail', 'Cannot communicate with mail server.') + "</span>")
							.show(1, function() {
								// Scroll to the notification.
								$('#mail_messages').animate({ scrollTop: messageListHeight }, 1);
							});
					}
					// Set the current folder as being active.
					Mail.UI.setFolderActive(Mail.State.currentAccountId, Mail.State.currentFolderId);
				},
				complete: function() {
					//
				}
			});
	}
});
