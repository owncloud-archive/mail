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

	var _ = require('underscore');
	var $ = require('jquery');
	var OC = require('OC');
	var Radio = require('radio');

	require('views/helper');

	Radio.ui.on('folder:show', loadFolder);

	var composerVisible = false;

	/**
	 * @param {Account} account
	 * @param {Folder} folder
	 * @param {boolean} noSelect
	 * @returns {undefined}
	 */
	function loadFolder(account, folder, noSelect) {
		Radio.ui.trigger('composer:leave');

		if (require('state').messagesLoading !== null) {
			require('state').messagesLoading.abort();
		}
		if (require('state').messageLoading !== null) {
			require('state').messageLoading.abort();
		}

		// Set folder active
		Radio.folder.trigger('setactive', account, folder);
		Radio.ui.trigger('content:loading');
		Radio.ui.trigger('messagesview:messages:reset');

		$('#load-new-mail-messages').hide();
		$('#load-more-mail-messages').hide();

		if (noSelect) {
			$('#emptycontent').show();
			require('state').currentAccount = account;
			require('state').currentFolder = folder;
			Radio.ui.trigger('messagesview:message:setactive', null);
			require('state').currentlyLoading = null;
		} else {
			require('communication').fetchMessageList(account, folder, {
				onSuccess: function(messages, cached) {
					Radio.ui.trigger('messagecontent:show');
					require('state').currentlyLoading = null;
					require('state').currentAccount = account;
					require('state').currentFolder = folder;
					Radio.ui.trigger('messagesview:message:setactive', null);

					// Fade out the message composer
					$('#mail_new_message').prop('disabled', false);

					if (messages.length > 0) {
						Radio.ui.trigger('messagesview:messages:add', messages);

						// Fetch first 10 messages in background
						_.each(messages.slice(0, 10), function(
							message) {
							require('background').messageFetcher.push(message.id);
						});

						var messageId = messages[0].id;
						Radio.message.trigger('load', account, folder, messageId);
						// Show 'Load More' button if there are
						// more messages than the pagination limit
						if (messages.length > 20) {
							$('#load-more-mail-messages')
								.fadeIn()
								.css('display', 'block');
						}
					} else {
						$('#emptycontent').show();
					}
					$('#load-new-mail-messages')
						.fadeIn()
						.css('display', 'block')
						.prop('disabled', false);

					if (cached) {
						// Trigger folder update
						// TODO: replace with horde sync once it's implemented
						Radio.ui.trigger('messagesview:messages:update');
					}
				},
				onError: function(error, textStatus) {
					if (textStatus !== 'abort') {
						// Set the old folder as being active
						var folder = require('state').currentFolder;
						Radio.folder.trigger('setactive', account, folder);
						Radio.ui.trigger('error:show', t('mail', 'Error while loading messages.'));
					}
				},
				cache: true
			});
		}
	}

	/**
	 * @param {number} messageId
	 * @param {number} attachmentId
	 * @returns {undefined}
	 */
	function saveAttachment(messageId, attachmentId) {
		OC.dialogs.filepicker(
			t('mail', 'Choose a folder to store the attachment in'),
			function(path) {
				// Loading feedback
				var saveToFilesBtnSelector = '.attachment-save-to-cloud';
				if (typeof attachmentId !== 'undefined') {
					saveToFilesBtnSelector = 'li[data-attachment-id="' +
						attachmentId + '"] ' + saveToFilesBtnSelector;
				}
				$(saveToFilesBtnSelector)
					.removeClass('icon-folder')
					.addClass('icon-loading-small')
					.prop('disabled', true);

				$.ajax(
					OC.generateUrl(
						'apps/mail/accounts/{accountId}/' +
						'folders/{folderId}/messages/{messageId}/' +
						'attachment/{attachmentId}',
						{
							accountId: require('state').currentAccount.get('accountId'),
							folderId: require('state').currentFolder.get('id'),
							messageId: messageId,
							attachmentId: attachmentId
						}), {
					data: {
						targetPath: path
					},
					type: 'POST',
					success: function() {
						if (typeof attachmentId === 'undefined') {
							Radio.ui.trigger('error:show', t('mail', 'Attachments saved to Files.'));
						} else {
							Radio.ui.trigger('error:show', t('mail', 'Attachment saved to Files.'));
						}
					},
					error: function() {
						if (typeof attachmentId === 'undefined') {
							Radio.ui.trigger('error:show', t('mail', 'Error while saving attachments to Files.'));
						} else {
							Radio.ui.trigger('error:show', t('mail', 'Error while saving attachment to Files.'));
						}
					},
					complete: function() {
						// Remove loading feedback again
						$('.attachment-save-to-cloud')
							.removeClass('icon-loading-small')
							.addClass('icon-folder')
							.prop('disabled', false);
					}
				});
			},
			false,
			'httpd/unix-directory',
			true
			);
	}

	/**
	 * @returns {undefined}
	 */
	function openForwardComposer() {
		var header = '\n\n\n\n-------- ' +
			t('mail', 'Forwarded message') +
			' --------\n';

		// TODO: find a better way to get the current message body
		var data = {
			subject: 'Fwd: ' + require('state').currentMessageSubject,
			body: header + require('state').currentMessageBody.replace(/<br \/>/g, '\n')
		};

		if (require('state').currentAccount.get('accountId') !== -1) {
			data.accountId = require('state').currentAccount.get('accountId');
		}

		Radio.ui.trigger('composer:show', data);
	}

	function isComposerVisible() {
		return composerVisible;
	}

	return {
		saveAttachment: saveAttachment,
		openForwardComposer: openForwardComposer,
		isComposerVisible: isComposerVisible
	};
});
