/* global Notification */

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
	var _ = require('underscore');

	return {
		showNotification: function(title, body, tag, icon, accountId, folderId) {
			// notifications not supported -> go away
			if (typeof Notification === 'undefined') {
				return;
			}
			// browser is active -> go away
			var isWindowFocused = document.querySelector(':focus') !== null;
			if (isWindowFocused) {
				return;
			}
			var notification = new Notification(
				title,
				{
					body: body,
					tag: tag,
					icon: icon
				}
			);
			notification.onclick = function() {
				require('Mail').UI.loadFolder(accountId, folderId, false);
				window.focus();
			};
			setTimeout(function() {
				notification.close();
			}, 5000);
		},
		showMailNotification: function(email, folder) {
			if (Notification.permission === "granted" && folder.messages.length > 0) {
				var from = _.map(folder.messages, function(m) {
					return m.from;
				});
				from = _.uniq(from);
				if (from.length > 2) {
					from = from.slice(0, 2);
					from.push('…');
				} else {
					from = from.slice(0, 2);
				}
				// special layout if there is only 1 new message
				var body = '';
				if (folder.messages.length === 1) {
					var subject = _.map(folder.messages, function(m) {
						return m.subject;
					});
					body = t('mail',
						'{from}\n{subject}', {
							from: from.join(),
							subject: subject.join()
						});
				} else {
					body = n('mail',
						'%n new message in {folderName} \nfrom {from}',
						'%n new messages in {folderName} \nfrom {from}',
						folder.messages.length, {
							folderName: folder.name,
							from: from.join()
						});
				}
				// If it's okay let's create a notification
				var tag = 'not-' + folder.accountId + '-' + folder.name;
				var icon = OC.filePath('mail', 'img', 'mail-notification.png');
				require('Mail').BackGround.showNotification(email, body, tag, icon, folder.accountId, folder.id);
			}
		},
		checkForNotifications: function() {
			_.each(require('Mail').State.accounts, function(a) {
				var localAccount = require('Mail').State.folderView.collection.get(a.accountId);
				var folders = localAccount.get('folders');

				$.ajax(
					OC.generateUrl('apps/mail/accounts/{accountId}/folders/detectChanges', {accountId: a.accountId}), {
					data: JSON.stringify({folders: folders.toJSON()}),
					contentType: "application/json; charset=utf-8",
					dataType: "json",
					type: 'POST',
					success: function(jsondata) {
						_.each(jsondata, function(f) {
							// send notification
							if (f.newUnReadCounter > 0) {
								require('Mail').UI.changeFavicon(OC.filePath('mail', 'img', 'favicon-notification.png'));
								require('Mail').BackGround.showMailNotification(localAccount.get('email'), f);
							}

							// update folder status
							var localFolder = folders.get(f.id);
							localFolder.set('uidvalidity', f.uidvalidity);
							localFolder.set('uidnext', f.uidnext);
							localFolder.set('unseen', f.unseen);
							localFolder.set('total', f.total);

							// reload if current selected folder has changed
							if (require('Mail').State.currentAccountId === f.accountId &&
								require('Mail').State.currentFolderId === f.id) {
								require('Mail').UI.messageView.collection.add(f.messages);
							}

							require('Mail').State.folderView.updateTitle();
						});
					}

				}
				);
			});
		},
		/**
		 * Fetch message of the current account/folder in background
		 *
		 * Uses a queue where message IDs are stored and fetched periodically
		 * The message is only fetched if it's not already cached
		 */
		messageFetcher: (function() {
			var accountId = null;
			var folderId = null;
			var pollIntervall = 3 * 1000;
			var queue = [];
			var timer = null;

			function fetch() {
				if (queue.length > 0) {
					// Empty waiting queue
					var messages = queue;
					queue = [];

					_.each(messages, function(messageId) {
						if (!require('Mail').Cache.getMessage(accountId, folderId, messageId)) {
							require('Mail').Communication.fetchMessage(accountId, folderId, messageId, {
								backgroundMode: true,
								onSuccess: function(message) {
									// Add the fetched message to cache
									require('Mail').Cache.addMessage(accountId, folderId, message);
								}
							});
						}
					});
				}
			}

			return {
				start: function() {
					accountId = require('Mail').State.currentAccountId;
					folderId = require('Mail').State.currentFolderId;
					timer = setInterval(fetch, pollIntervall);
				},
				restart: function() {
					// Stop previous fetcher
					clearInterval(timer);

					// Clear waiting queue
					queue.length = 0;

					// Start again
					this.start();
				},
				push: function(message) {
					queue.push(message);
				}
			};
		}())
	};
});
