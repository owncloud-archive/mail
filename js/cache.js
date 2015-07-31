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
	var _ = require('underscore');
	
	var MessageCache = {
		getFolderPath: function(accountId, folderId) {
			return ['messages', accountId.toString(), folderId.toString()].join('.');
		},
		getMessagePath: function(accountId, folderId, messageId) {
			return [this.getFolderPath(accountId, folderId), messageId.toString()].join('.');
		}
	};

	var FolderCache = {
		getFolderPath: function(accountId, folderId) {
			return ['folders', accountId.toString(), folderId.toString()].join('.');
		}
	};

	return {
		cleanUp: function(accounts) {
			var storage = $.localStorage;
			var activeAccounts = _.map(accounts, function(account) {
				return account.accountId;
			});
			_.each(storage.get('messages'), function(account, accountId) {
				var isActive = _.any(activeAccounts, function(a) {
					return a === parseInt(accountId);
				});
				if (!isActive) {
					// Account does not exist anymore -> remove it
					storage.remove('messages.' + accountId);
				}
			});
		},
		getFolderMessages: function(accountId, folderId) {
			var storage = $.localStorage;
			var path = MessageCache.getFolderPath(accountId, folderId);
			return storage.isSet(path) ? storage.get(path) : null;
		},
		getMessage: function(accountId, folderId, messageId) {
			var storage = $.localStorage;
			var path = MessageCache.getMessagePath(accountId, folderId, messageId);
			if (storage.isSet(path)) {
				var message = storage.get(path);
				// Update the timestamp
				this.addMessage(accountId, folderId, message);
				return message;
			} else {
				return null;
			}
		},
		addMessage: function(accountId, folderId, message) {
			var storage = $.localStorage;
			var path = MessageCache.getMessagePath(accountId, folderId, message.id);
			// Add timestamp for later cleanup
			message.timestamp = Date.now();

			// Save the message to local storage
			storage.set(path, message);

			// Remove old messages (keep 20 most recently loaded)
			var messages = $.map(this.getFolderMessages(accountId, folderId), function(value) {
				return [value];
			});
			messages.sort(function(m1, m2) {
				return m2.timestamp - m1.timestamp;
			});
			var oldMessages = messages.slice(20, messages.length);
			_.each(oldMessages, function(message) {
				storage.remove(MessageCache.getMessagePath(accountId, folderId, message.id));
			});
		},
		removeMessage: function(accountId, folderId, messageId) {
			var storage = $.localStorage;
			var message = this.getMessage(accountId, folderId, messageId);
			if (message) {
				// message exists in cache -> remove it
				storage.remove(MessageCache.getMessagePath(accountId, folderId, messageId));
				var messageList = this.getMessageList(accountId, folderId);
				if (messageList) {
					// message list is cached -> remove message from it
					var newList = _.filter(messageList, function(message) {
						return message.id !== messageId;
					});
					this.addMessageList(accountId, folderId, newList);
				}
			}
		},
		getMessageList: function(accountId, folderId) {
			var storage = $.localStorage;
			var path = FolderCache.getFolderPath(accountId, folderId);
			if (storage.isSet(path)) {
				return storage.get(path);
			} else {
				return null;
			}
		},
		addMessageList: function(accountId, folderId, messages) {
			var storage = $.localStorage;
			var path = FolderCache.getFolderPath(accountId, folderId);
			storage.set(path, messages);
		}
	};
});
