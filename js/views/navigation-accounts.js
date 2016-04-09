/* global oc_defaults */

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

	var _ = require('underscore');
	var $ = require('jquery');
	var Marionette = require('marionette');
	var AccountView = require('views/account');
	var Radio = require('radio');

	/**
	 * @class NavigationAccountsView
	 */
	return Marionette.CollectionView.extend({
		collection: null,
		childView: AccountView,
		/**
		 * @returns {undefined}
		 */
		initialize: function() {
			this.listenTo(Radio.ui, 'folder:changed', this.onFolderChanged);
			this.listenTo(Radio.ui, 'title:update', this.updateTitle);
			this.listenTo(Radio.folder, 'setactive', this.setFolderActive);
		},
		/**
		 * @param {Account} model
		 * @param {boolean} unseen
		 * @returns {undefined}
		 */
		changeUnseen: function(model, unseen) {
			// TODO: currentFolderId and currentAccount should be an attribute of this view
			var activeFolder = require('state').currentFolder;
			if (unseen) {
				activeFolder.set('unseen', activeFolder.get('unseen') + 1);
			} else {
				if (activeFolder.get('unseen') > 0) {
					activeFolder.set('unseen', activeFolder.get('unseen') - 1);
				}
			}
			this.updateTitle();
		},
		/**
		 * @returns {undefined}
		 */
		updateTitle: function() {
			var activeEmail = '';
			if (require('state').currentAccount.get('accountId') !== -1) {
				var activeAccount = require('state').currentAccount;
				activeEmail = ' - ' + activeAccount.get('email');
			}
			var activeFolder = require('state').currentFolder;
			var unread = activeFolder.unseen || activeFolder.get('unseen');
			var name = activeFolder.name || activeFolder.get('name');
			if (unread > 0) {
				window.document.title = name + ' (' + unread + ')' +
					// jscs:disable requireCamelCaseOrUpperCaseIdentifiers
					activeEmail + ' - Mail - ' + oc_defaults.title;
				// jscs:enable requireCamelCaseOrUpperCaseIdentifiers
			} else {
				window.document.title = name + activeEmail +
					// jscs:disable requireCamelCaseOrUpperCaseIdentifiers
					' - Mail - ' + oc_defaults.title;
				// jscs:enable requireCamelCaseOrUpperCaseIdentifiers
			}
		},
		/**
		 * @param {Account} account
		 * @param {Folder} folder
		 * @returns {undefined}
		 */
		setFolderActive: function(account, folder) {
			Radio.ui.trigger('messagesview:filter:clear');

			if (_.isUndefined(account)) {
				return;
			}

			// disable all other folders for all accounts
			require('state').accounts.each(function(acnt) {
				var folders = acnt.get('folders');
				folders.each(function(folder) {
					folders.get(folder).set('active', false);
				});
			});

			folder.set('active', true);
		},
		/**
		 * @returns {undefined}
		 */
		onFolderChanged: function() {
			// Stop background message fetcher of previous folder
			require('background').messageFetcher.restart();
			// hide message detail view on mobile
			// TODO: find better place for this
			$('#mail-message').addClass('hidden-mobile');
		}
	});
});
