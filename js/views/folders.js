/* global Backbone, _, oc_defaults */

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
	var AccountView = require('views/account'),
		AccountList = require('models/accountlist');

	return Backbone.Marionette.CollectionView.extend({

	// The collection will be kept here
	collection: null,

	childView: AccountView,

	initialize: function() {
		this.collection = new AccountList();
	},

	getFolderById: function(accountId, folderId) {
		var activeAccount = accountId || require('Mail').State.currentAccountId;
		folderId = folderId || require('Mail').State.currentFolderId;
		activeAccount = this.collection.get(activeAccount);
		var activeFolder = activeAccount.get('folders').get(folderId);
		if (!_.isUndefined(activeFolder)) {
			return activeFolder;
		}

		// bad hack to navigate down the tree ...
		var delimiter = activeAccount.get('delimiter');
		folderId = atob(folderId);
		activeFolder = activeAccount;
		var parts = folderId.split(delimiter);
		var k = '';
		_.each(parts, function(p) {
			if (k.length > 0) {
				k += delimiter;
			}
			k += p;

			var folders = activeFolder.folders || activeFolder.get('folders');
			activeFolder = folders.filter(function(f) {
				return f.id === btoa(k);
			}).shift();
		});
		return activeFolder;
	},

	changeUnseen: function(model, unseen) {
		// TODO: currentFolderId and currentAccountId should be an attribute of this view
		var activeFolder = this.getFolderById();
		if (unseen) {
			activeFolder.set('unseen', activeFolder.get('unseen') + 1);
		} else {
			if (activeFolder.get('unseen') > 0) {
				activeFolder.set('unseen', activeFolder.get('unseen') - 1);
			}
		}
		this.updateTitle();
	},

	updateTitle: function() {
		var activeEmail = '';
		if (require('Mail').State.currentAccountId !== -1) {
			var activeAccount = require('Mail').State.currentAccountId;
			activeAccount = this.collection.get(activeAccount);
			activeEmail = ' - ' + activeAccount.get('email');
		}
		var activeFolder = this.getFolderById();
		var unread = activeFolder.unseen;
		var name = activeFolder.name || activeFolder.get('name');

		if (unread > 0) {
			window.document.title = name + ' (' + unread + ')' +
			activeEmail + ' - Mail - ' + oc_defaults.title;
		} else {
			window.document.title = name + activeEmail +
			' - Mail - ' + oc_defaults.title;
		}
	}

});
});
