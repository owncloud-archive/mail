/**
 * ownCloud - Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015
 */


define(['require', 'Mail'], function(require) {
	var accounts = null,
		currentFolderId = null,
		currentAccountId = null,
		currentMessageId = null,
		currentMessageSubject = null,
		currentMessageBody = '',
		messagesLoading = null,
		messageLoading = null;

	var state = {};

	Object.defineProperties(state, {
		accounts: {
			get: function() {
				return accounts;
			},
			set: function(acc) {
				accounts = acc;
			}
		},
		currentAccountId: {
			get: function() {
				return currentAccountId;
			},
			set: function(newId) {
				currentAccountId = newId;
			}
		},
		currentFolderId: {
			get: function() {
				return currentFolderId;
			},
			set: function(newId) {
				var oldId = currentFolderId;
				currentFolderId = newId;
				if (newId !== oldId) {
					require('Mail').UI.Events.onFolderChanged();
				}
			}
		},
		currentMessageId: {
			get: function() {
				return currentMessageId;
			},
			set: function(newId) {
				currentMessageId = newId;
			}
		},
		currentMessageSubject: {
			get: function() {
				return currentMessageSubject;
			},
			set: function(subject) {
				currentMessageSubject = subject;
			}
		},
		currentMessageBody: {
			get: function() {
				return currentMessageBody;
			},
			set: function(body) {
				currentMessageBody = body;
			}
		},
		messagesLoading: {
			get: function() {
				return messagesLoading;
			},
			set: function(loading) {
				messagesLoading = loading;
			}
		},
		messageLoading: {
			get: function() {
				return messageLoading;
			},
			set: function(loading) {
				messageLoading = loading;
			}
		}
	});

	return state;
});