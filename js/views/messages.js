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

	var $ = require('jquery');
	var Backbone = require('backbone');
	var Handlebars = require('handlebars');
	var Radio = require('radio');
	var MessagesItemView = require('views/messagesitem');
	var MessageListTemplate = require('text!templates/message-list.html');
	var NoSearchResultMessageListView = require('views/nosearchresultmessagelistview');

	return Backbone.Marionette.CompositeView.extend({
		collection: null,
		$scrollContainer: undefined,
		childView: MessagesItemView,
		childViewContainer: '#mail-message-list',
		template: Handlebars.compile(MessageListTemplate),
		currentMessageId: null,
		loadingMore: false,
		filterCriteria: null,
		initialize: function() {
			var _this = this;
			Radio.ui.reply('messagesview:collection', function() {
				return _this.collection;
			});
			this.listenTo(Radio.ui, 'messagesview:messages:update', this.refresh);
			this.listenTo(Radio.ui, 'messagesview:messages:add', this.addMessages);
			this.listenTo(Radio.ui, 'messagesview:messageflag:set', this.setMessageFlag);
			this.listenTo(Radio.ui, 'messagesview:filter', this.filterCurrentMailbox);
			this.listenTo(Radio.ui, 'messagesview:filter:clear', this.clearFilter);
			this.listenTo(Radio.ui, 'messagesview:message:setactive', this.setActiveMessage);
			this.listenTo(Radio.message, 'messagesview:message:next', this.selectNextMessage);
			this.listenTo(Radio.message, 'messagesview:message:prev', this.selectPreviousMessage);
		},
		onShow: function() {
			this.$scrollContainer = this.$el.parent();
			this.$scrollContainer.scroll(_.bind(this.onScroll, this));
		},
		getEmptyView: function() {
			if (this.filterCriteria) {
				return NoSearchResultMessageListView;
			}
		},
		emptyViewOptions: function() {
			return {filterCriteria: this.filterCriteria};
		},
		setMessageFlag: function(messageId, flag, val) {
			var message = this.collection.get(messageId);
			if (message) {
				// TODO: globals are bad :-/
				var account = require('state').currentAccount;
				var folder = require('state').currentFolder;

				Radio.message.trigger('flag', account, folder, message, flag, val);
			}
		},
		setActiveMessage: function(messageId) {
			// Set active class for current message and remove it from old one

			var message = null;
			if (this.currentMessageId !== null) {
				message = this.collection.get(this.currentMessageId);
				if (message) {
					message.set('active', false);
				}
			}

			this.currentMessageId = messageId;

			if (messageId !== null) {
				message = this.collection.get(this.currentMessageId);
				if (message) {
					message.set('active', true);
				}
			}

			require('state').currentMessageId = messageId;
			Radio.ui.trigger('title:update');

		},
		selectNextMessage: function() {
			if (this.currentMessageId === null) {
				return;
			}

			var message = this.collection.get(this.currentMessageId);
			if (message === null) {
				return;
			}

			if (this.collection.indexOf(message) === (this.collection.length - 1)) {
				// Last message, nothing to do
				return;
			}

			var nextMessage = this.collection.at(this.collection.indexOf(message) + 1);
			if (nextMessage) {
				var account = require('state').currentAccount;
				var folder = require('state').currentFolder;
				Radio.message.trigger('load', account, folder, nextMessage, {
					force: true
				});
			}
		},
		selectPreviousMessage: function() {
			if (this.currentMessageId === null) {
				return;
			}

			var message = this.collection.get(this.currentMessageId);
			if (message === null) {
				return;
			}

			if (this.collection.indexOf(message) === 0) {
				// First message, nothing to do
				return;
			}

			var previousMessage = this.collection.at(this.collection.indexOf(message) - 1);
			if (previousMessage) {
				var account = require('state').currentAccount;
				var folder = require('state').currentFolder;
				Radio.message.trigger('load', account, folder, previousMessage, {
					force: true
				});
			}
		},
		loadNew: function() {
			if (!require('state').currentAccount) {
				return;
			}
			if (!require('state').currentFolder) {
				return;
			}

			this.loadMessages(true);
		},
		onScroll: function() {
			if (this.loadingMore === true) {
				// Ignore events until loading has finished
				return;
			}
			if ((this.$scrollContainer.scrollTop() + this.$scrollContainer.height()) > (this.$el.height() - 150)) {
				this.loadingMore = true;
				this.loadMessages(false);
			}
		},
		filterCurrentMailbox: function(query) {
			this.filterCriteria = {
				text: query
			};
			this.refresh();
		},
		clearFilter: function() {
			$('#searchbox').val('');
			this.filterCriteria = null;
		},
		loadMessages: function(reload) {
			reload = reload || false;
			var from = this.collection.size();
			if (reload) {
				from = 0;
			}
			// Add loading feedback
			$('#load-more-mail-messages').addClass('icon-loading');

			var _this = this;
			var loadingMessages = Radio.message.request('entities',
				require('state').currentAccount,
				require('state').currentFolder,
				{
					from: from,
					to: from + 20,
					filter: this.filterCriteria ? this.filterCriteria.text : null,
					force: true,
					// Replace cached message list on reload
					replace: reload
				});

			$.when(loadingMessages).done(function() {
				Radio.ui.trigger('messagesview:message:setactive', require('state').currentMessageId);
			});

			$.when(loadingMessages).fail(function() {
				Radio.ui.trigger('error:show', t('mail', 'Error while loading messages.'));
				// Set the old folder as being active
				var account = require('state').currentAccount;
				var folder = require('state').currentFolder;
				Radio.folder.trigger('setactive', account, folder);
			});

			$.when(loadingMessages).always(function() {
				// Remove loading feedback again
				$('#load-more-mail-messages').removeClass('icon-loading');
				_this.loadingMore = false;
			});
		},
		addMessages: function(message) {
			var _this = this;
			// TODO: merge?
			message.each(function(msg) {
				_this.collection.add(msg);
			});
		}
	});
});
