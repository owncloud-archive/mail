/* global Marionette, Handlebars, OC */

/**
 * ownCloud - Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015
 */

define(['require', 'Mail', 'views/composer', 'views/messages', 'views/folders',
	'views/helper'], function(require) {
	var _ = require('underscore'),
		//$ = require('jquery'),
		MessagesView = require('views/messages'),
		FoldersView = require('views/folders'),
		messageView = null,
		composer = null,
		composerVisible = false,
		UI = {};

	UI.renderSettings = function() {
		var accounts = _.filter(require('Mail').State.accounts, function(item) {
			return item.accountId !== -1;
		});
		var source = $("#mail-settings-template").html();
		var template = Handlebars.compile(source);
		var html = template(accounts);
		$('#app-settings-content').html(html);
	};

	UI.changeFavicon = function(src) {
		$('link[rel="shortcut icon"]').attr('href', src);
	};

	UI.loadAccounts = function() {
		require('Mail').Communication.get(OC.generateUrl('apps/mail/accounts'), {
			success: function(accounts) {
				require('Mail').State.accounts = accounts;
				UI.renderSettings();
				if (accounts.length === 0) {
					UI.addAccount();
				} else {
					var firstAccountId = accounts[0].accountId;
					_.each(accounts, function(a) {
						UI.loadFoldersForAccount(a.accountId, firstAccountId);
					});
				}
				require('Mail').Cache.cleanUp(accounts);
			},
			error: function() {
				UI.showError(t('mail', 'Error while loading the accounts.'));
			},
			ttl: 'no'
		});
	};

	UI.initializeInterface = function() {
		// Register UI events
		window.addEventListener('resize', UI.Events.onWindowResize);

		Marionette.TemplateCache.prototype.compileTemplate = function(rawTemplate) {
			return Handlebars.compile(rawTemplate);
		};
		Marionette.ItemView.prototype.modelEvents = {"change": "render"};
		Marionette.CompositeView.prototype.modelEvents = {"change": "render"};

		// ask to handle all mailto: links
		if (window.navigator.registerProtocolHandler) {
			var url = window.location.protocol + '//' +
				window.location.host +
				OC.generateUrl('apps/mail/compose?uri=%s');
			try {
				window.navigator
					.registerProtocolHandler("mailto", url, "ownCloud Mail");
			} catch (e) {
			}
		}

		// setup messages view
		UI.messageView = new MessagesView({
			el: $('#mail_messages')
		});
		UI.messageView.render();

		// setup folder view
		require('Mail').State.folderView = new FoldersView({
			el: $('#folders')
		});
		require('Mail').State.folderView.render();

		require('Mail').State.folderView.listenTo(UI.messageView, 'change:unseen',
			require('Mail').State.folderView.changeUnseen);

		// request permissions
		if (typeof Notification !== 'undefined') {
			Notification.requestPermission();
		}

		if (!_.isUndefined(OC.Plugins)) {
			OC.Plugins.register('OCA.Search', require('Mail').Search);
		}

		setInterval(require('Mail').BackGround.checkForNotifications, 5 * 60 * 1000);
		this.loadAccounts();
	};

	UI.loadFoldersForAccount = function(accountId, firstAccountId) {
		$('#mail_messages').removeClass('hidden').addClass('icon-loading');
		$('#mail-message').removeClass('hidden').addClass('icon-loading');
		$('#mail_new_message').removeClass('hidden');
		$('#folders').removeClass('hidden');
		$('#mail-setup').addClass('hidden');

		UI.clearMessages();
		$('#app-navigation').addClass('icon-loading');

		require('Mail').Communication.get(OC.generateUrl('apps/mail/accounts/{accountId}/folders', {accountId: accountId}), {
			success: function(jsondata) {
				$('#app-navigation').removeClass('icon-loading');
				require('Mail').State.folderView.collection.add(jsondata);

				if (jsondata.id === firstAccountId) {
					var folderId = jsondata.folders[0].id;

					UI.loadFolder(accountId, folderId, false);

					// Save current folder
					UI.setFolderActive(accountId, folderId);
					require('Mail').State.currentAccountId = accountId;
					require('Mail').State.currentFolderId = folderId;

					// Start fetching messages in background
					require('Mail').BackGround.messageFetcher.start();
				}
			},
			error: function() {
				UI.showError(t('mail', 'Error while loading the selected account.'));
			},
			ttl: 'no'
		});
	};

	UI.showError = function(message) {
		OC.Notification.showTemporary(message);
		$('#app-navigation')
			.removeClass('icon-loading');
		$('#app-content')
			.removeClass('icon-loading');
		$('#mail-message')
			.removeClass('icon-loading');
		$('#mail_message')
			.removeClass('icon-loading');
	};

	UI.clearMessages = function() {
		UI.messageView.collection.reset();
		$('#messages-loading').fadeIn();

		$('#mail-message')
			.html('')
			.addClass('icon-loading');
	};

	UI.hideMenu = function() {
		$('.message-composer').addClass('hidden');
		if (require('Mail').State.accounts.length === 0) {
			$('#app-navigation').hide();
			$('#app-navigation-toggle').css('background-image', 'none');
		}
	};

	UI.showMenu = function() {
		$('.message-composer').removeClass('hidden');
		$('#app-navigation').show();
		$('#app-navigation-toggle').css('background-image', '');
	};

	UI.addMessages = function(data) {
		UI.messageView.collection.add(data);
	};

	UI.loadFolder = function(accountId, folderId, noSelect) {
		UI.Events.onComposerLeave();

		if (require('Mail').State.messagesLoading !== null) {
			require('Mail').State.messagesLoading.abort();
		}
		if (require('Mail').State.messageLoading !== null) {
			require('Mail').State.messageLoading.abort();
		}

		// Set folder active
		UI.setFolderActive(accountId, folderId);
		UI.clearMessages();
		$('#mail_messages')
			.removeClass('hidden')
			.addClass('icon-loading')
			.removeClass('hidden');
		$('#mail_new_message')
			.removeClass('hidden')
			.fadeIn();
		$('#mail-message').removeClass('hidden');
		$('#folders').removeClass('hidden');
		$('#mail-setup').addClass('hidden');

		$('#load-new-mail-messages').hide();
		$('#load-more-mail-messages').hide();
		$('#emptycontent').hide();

		if (noSelect) {
			$('#emptycontent').show();
			$('#mail-message').removeClass('icon-loading');
			require('Mail').State.currentAccountId = accountId;
			require('Mail').State.currentFolderId = folderId;
			UI.setMessageActive(null);
			$('#mail_messages').removeClass('icon-loading');
			require('Mail').State.currentlyLoading = null;
		} else {
			require('Mail').Communication.fetchMessageList(accountId, folderId, {
				onSuccess: function(messages, cached) {
					require('Mail').State.currentlyLoading = null;
					require('Mail').State.currentAccountId = accountId;
					require('Mail').State.currentFolderId = folderId;
					UI.setMessageActive(null);
					$('#mail_messages').removeClass('icon-loading');

					// Fade out the message composer
					$('#mail_new_message').prop('disabled', false);

					if (messages.length > 0) {
						UI.addMessages(messages);

						// Fetch first 10 messages in background
						_.each(messages.slice(0, 10), function(message) {
							require('Mail').BackGround.messageFetcher.push(message.id);
						});

						var messageId = messages[0].id;
						UI.loadMessage(messageId);
						// Show 'Load More' button if there are
						// more messages than the pagination limit
						if (messages.length > 20) {
							$('#load-more-mail-messages')
								.fadeIn()
								.css('display', 'block');
						}
					} else {
						$('#emptycontent').show();
						$('#mail-message').removeClass('icon-loading');
					}
					$('#load-new-mail-messages')
						.fadeIn()
						.css('display', 'block')
						.prop('disabled', false);

					if (cached) {
						// Trigger folder update
						// TODO: replace with horde sync once it's implemented
						UI.messageView.loadNew();
					}

				},
				onError: function(error, textStatus) {
					if (textStatus !== 'abort') {
						// Set the old folder as being active
						UI.setFolderActive(require('Mail').State.currentAccountId, require('Mail').State.currentFolderId);
						UI.showError(t('mail', 'Error while loading messages.'));
					}
				},
				cache: true
			});
		}
	};

	UI.saveAttachment = function(messageId, attachmentId) {
		OC.dialogs.filepicker(
			t('mail', 'Choose a folder to store the attachment in'),
			function(path) {
				// Loading feedback
				var saveToFilesBtnSelector = '.attachment-save-to-cloud';
				if (typeof attachmentId !== "undefined") {
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
							accountId: require('Mail').State.currentAccountId,
							folderId: require('Mail').State.currentFolderId,
							messageId: messageId,
							attachmentId: attachmentId
						}), {
					data: {
						targetPath: path
					},
					type: 'POST',
					success: function() {
						if (typeof attachmentId === "undefined") {
							UI.showError(t('mail', 'Attachments saved to Files.'));
						} else {
							UI.showError(t('mail', 'Attachment saved to Files.'));
						}
					},
					error: function() {
						if (typeof attachmentId === "undefined") {
							UI.showError(t('mail', 'Error while saving attachments to Files.'));
						} else {
							UI.showError(t('mail', 'Error while saving attachment to Files.'));
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
	};

	UI.openComposer = function(data) {
		composerVisible = true;
		$('.tipsy').remove();
		$('#mail_new_message').prop('disabled', true);
		$('#mail-message').removeClass('hidden-mobile');

		// Abort message loads
		if (require('Mail').State.messageLoading !== null) {
			require('Mail').State.messageLoading.abort();
			$('iframe').parent().removeClass('icon-loading');
			$('#mail-message').removeClass('icon-loading');
			$('#mail_message').removeClass('icon-loading');
		}

		if (composer === null) {
			// setup composer view
			var Composer = require('views/composer');
			composer = new Composer({
				el: $('#mail-message'),
				onSubmit: require('Mail').Communication.sendMessage,
				onDraft: require('Mail').Communication.saveDraft,
				aliases: require('Mail').State.accounts
			});
		} else {
			composer.data = data;
			composer.hasData = false;
			composer.hasUnsavedChanges = false;
			composer.delegateEvents();
		}

		if (data && data.hasHtmlBody) {
			UI.showError(t('mail', 'Opening HTML drafts is not supported yet.'));
		}

		composer.render({
			data: data
		});

		// set 'from' dropdown to current account
		// TODO: fix selector conflicts
		if (require('Mail').State.currentAccountId !== -1) {
			$('.mail-account').val(require('Mail').State.currentAccountId);
		}

		// focus 'to' field automatically on clicking New message button
		var toInput = composer.el.find('input.to');
		toInput.focus();

		if (!_.isUndefined(data.currentTarget) && !_.isUndefined($(data.currentTarget).data().email)) {
			var to = '"' + $(data.currentTarget).data().label + '" <' + $(data.currentTarget).data().email + '>';
			toInput.val(to);
			composer.el.find('input.subject').focus();
		}

		UI.setMessageActive(null);
	};

	UI.openForwardComposer = function() {
		var header = '\n\n\n\n-------- ' +
			t('mail', 'Forwarded message') +
			" --------\n";

		// TODO: find a better way to get the current message body
		var data = {
			subject: 'Fwd: ' + require('Mail').State.currentMessageSubject,
			body: header + require('Mail').State.currentMessageBody.replace(/<br \/>/g, "\n")
		};

		if (require('Mail').State.currentAccountId !== -1) {
			data.accountId = require('Mail').State.currentAccountId;
		}

		this.openComposer(data);
	};

	UI.htmlToText = function(html) {
		var breakToken = '__break_token__';
		// Preserve line breaks
		html = html.replace(/<br>/g, breakToken);
		html = html.replace(/<br\/>/g, breakToken);

		// Add <br> break after each closing div, p, li to preserve visual
		// line breaks for replies
		html = html.replace(/<\/div>/g, '</div>' + breakToken);
		html = html.replace(/<\/p>/g, '</p>' + breakToken);
		html = html.replace(/<\/li>/g, '</li>' + breakToken);

		var tmp = $('<div>');
		tmp.html(html);
		var text = tmp.text();

		// Finally, replace tokens with line breaks
		text = text.replace(new RegExp(breakToken, 'g'), "\n");
		return text;
	};

	UI.loadMessage = function(messageId, options) {
		options = options || {};
		var defaultOptions = {
			force: false
		};
		_.defaults(options, defaultOptions);

		// Do not reload email when clicking same again
		if (require('Mail').State.currentMessageId === messageId) {
			return;
		}

		UI.Events.onComposerLeave();

		if (!options.force && composerVisible) {
			return;
		}
		// Abort previous loading requests
		if (require('Mail').State.messageLoading !== null) {
			require('Mail').State.messageLoading.abort();
		}

		// check if message is a draft
		var accountId = require('Mail').State.currentAccountId;
		var account = require('Mail').State.folderView.collection.findWhere({id: accountId});
		var draftsFolder = account.attributes.specialFolders.drafts;
		var draft = draftsFolder === require('Mail').State.currentFolderId;

		// close email first
		// Check if message is open
		if (require('Mail').State.currentMessageId !== null) {
			var lastMessageId = require('Mail').State.currentMessageId;
			UI.setMessageActive(null);
			if (lastMessageId === messageId) {
				return;
			}
		}

		var mailBody = $('#mail-message');
		mailBody.html('').addClass('icon-loading');

		// Set current Message as active
		UI.setMessageActive(messageId);
		require('Mail').State.currentMessageBody = '';

		// Fade out the message composer
		$('#mail_new_message').prop('disabled', false);

		var self = this;
		var loadMessageSuccess = function(message) {
			var reply = {
				replyToList: message.replyToList,
				replyCc: message.ReplyCc,
				replyCcList: message.replyCcList,
				body: ''
			};

			// Add body content to inline reply (text mails)
			if (!message.hasHtmlBody) {
				var date = new Date(message.dateIso);
				var minutes = date.getMinutes();
				var text = UI.htmlToText(message.body);

				reply.body = '\n\n\n\n' +
					message.from + ' – ' +
					$.datepicker.formatDate('D, d. MM yy ', date) +
					date.getHours() + ':' + (minutes < 10 ? '0' : '') + minutes + '\n> ' +
					text.replace(/\n/g, '\n> ');
			}

			// Save current messages's content for later use (forward)
			if (!message.hasHtmlBody) {
				require('Mail').State.currentMessageBody = message.body;
			}
			require('Mail').State.currentMessageSubject = message.subject;

			// Render the message body
			var source = $("#mail-message-template").html();
			var template = Handlebars.compile(source);
			var html = template(message);
			mailBody
				.html(html)
				.removeClass('icon-loading');
			adjustControlsWidth();

			// Temporarily disable new-message composer events
			if (composer) {
				composer.undelegateEvents();
			}

			// setup reply composer view
			var Composer = require('views/composer');
			var replyComposer = new Composer({
				el: $('#reply-composer'),
				type: 'reply',
				onSubmit: require('Mail').Communication.sendMessage,
				onDraft: require('Mail').Communication.saveDraft,
				accountId: require('Mail').State.currentAccountId,
				folderId: require('Mail').State.currentFolderId,
				messageId: messageId
			});
			replyComposer.render({
				data: reply
			});

			// Hide forward button until the message has finished loading
			if (message.hasHtmlBody) {
				$('#forward-button').hide();
			}

			UI.messageView.setMessageFlag(messageId, 'unseen', false);

			// HTML mail rendering
			$('iframe').load(function() {
				// Expand height to not have two scrollbars
				$(this).height($(this).contents().find('html').height() + 20);
				// Fix styling
				$(this).contents().find('body').css({
					'margin': '0',
					'font-weight': 'normal',
					'font-size': '.8em',
					'line-height': '1.6em',
					'font-family': "'Open Sans', Frutiger, Calibri, 'Myriad Pro', Myriad, sans-serif",
					'color': '#000'
				});
				// Fix font when different font is forced
				$(this).contents().find('font').prop({
					'face': 'Open Sans',
					'color': '#000'
				});
				$(this).contents().find('.moz-text-flowed').css({
					'font-family': 'inherit',
					'font-size': 'inherit'
				});
				// Expand height again after rendering to account for new size
				$(this).height($(this).contents().find('html').height() + 20);
				// Grey out previous replies
				$(this).contents().find('blockquote').css({
					'-ms-filter': '"progid:DXImageTransform.Microsoft.Alpha(Opacity=50)"',
					'filter': 'alpha(opacity=50)',
					'opacity': '.5'
				});
				// Remove spinner when loading finished
				$('iframe').parent().removeClass('icon-loading');

				// Add body content to inline reply (html mails)
				var text = $(this).contents().find('body').html();
				text = UI.htmlToText(text);
				if (!draft) {
					var date = new Date(message.dateIso);
					replyComposer.setReplyBody(message.from, date, text);
				}

				// Safe current mesages's content for later use (forward)
				require('Mail').State.currentMessageBody = text;

				// Show forward button
				$('#forward-button').show();
			});
		};

		var loadDraftSuccess = function(data) {
			self.openComposer(data);
		};

		require('Mail').Communication.fetchMessage(
			require('Mail').State.currentAccountId,
			require('Mail').State.currentFolderId,
			messageId,
			{
				onSuccess: function(message) {
					if (draft) {
						loadDraftSuccess(message);
					} else {
						require('Mail').Cache.addMessage(require('Mail').State.currentAccountId,
							require('Mail').State.currentFolderId,
							message);
						loadMessageSuccess(message);
					}
				},
				onError: function(jqXHR, textStatus) {
					if (textStatus !== 'abort') {
						UI.showError(t('mail', 'Error while loading the selected message.'));
					}
				}
			});
	};

	UI.setFolderActive = function(accountId, folderId) {
		UI.messageView.clearFilter();

		// disable all other folders for all accounts
		_.each(require('Mail').State.accounts, function(account) {
			var localAccount = require('Mail').State.folderView.collection.get(account.accountId);
			if (_.isUndefined(localAccount)) {
				return;
			}
			var folders = localAccount.get('folders');
			_.each(folders.models, function(folder) {
				folders.get(folder).set('active', false);
			});
		});

		require('Mail').State.folderView.getFolderById(accountId, folderId)
			.set('active', true);
	};

	UI.setMessageActive = function(messageId) {
		UI.messageView.setActiveMessage(messageId);
		require('Mail').State.currentMessageId = messageId;
		require('Mail').State.folderView.updateTitle();
	};

	UI.addAccount = function() {
		UI.Events.onComposerLeave();

		$('#mail_messages').addClass('hidden');
		$('#mail-message').addClass('hidden');
		$('#mail_new_message').addClass('hidden');
		$('#app-navigation').removeClass('icon-loading');

		UI.hideMenu();

		$('#mail-setup').removeClass('hidden');
		// don't show New Message button on Add account screen
		$('#mail_new_message').hide();
	};

	UI.toggleManualSetup = function() {
		$('#mail-setup-manual').slideToggle();
		$('#mail-imap-host').focus();
		if ($('#mail-address').parent().prop('class') === 'groupmiddle') {
			$('#mail-password').slideToggle(function() {
				$('#mail-address').parent()
					.removeClass('groupmiddle').addClass('groupbottom');
			});
		} else {
			$('#mail-password').slideToggle();
			$('#mail-address').parent()
				.removeClass('groupbottom').addClass('groupmiddle');
		}
	};

	UI.showDraftSavedNotification = function() {
		OC.Notification.showTemporary(t('mail', 'Draft saved!'));
	};

	UI.Events = {
		onComposerLeave: function() {
			// Trigger only once
			if (composerVisible === true) {
				composerVisible = false;

				if (composer && composer.hasData === true) {
					if (composer.hasUnsavedChanges === true) {
						composer.saveDraft(function() {
							UI.showDraftSavedNotification();
						});
					} else {
						UI.showDraftSavedNotification();
					}
				}
			}
		},
		onFolderChanged: function() {
			// Stop background message fetcher of previous folder
			require('Mail').BackGround.messageFetcher.restart();
			// hide message detail view on mobile
			$('#mail-message').addClass('hidden-mobile');
		},
		onWindowResize: function() {
			// Resize iframe
			var iframe = $('#mail-content iframe');
			iframe.height(iframe.contents().find('html').height() + 20);
		}
	};

	Object.defineProperties(UI, {
		messageView: {
			get: function() {
				return messageView;
			},
			set: function(mv) {
				messageView = mv;
			}
		}
	});

	return UI;
});
