/**
 * ownCloud - require('app')
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2016
 */

define(function(require) {
	'use strict';

	var Marionette = require('marionette');
	var Handlebars = require('handlebars');
	var $ = require('jquery');
	var _ = require('underscore');
	var OC = require('OC');
	var Radio = require('radio');
	var Attachments = require('models/attachments');
	var AttachmentsView = require('views/attachments');
	var ComposerTemplate = require('text!templates/composer.html');

	require('trumbowyg');
	require('trhyperlink');

	return Marionette.LayoutView.extend({

		template: Handlebars.compile(ComposerTemplate),
		templateHelpers: function() {
			var accounts = null;
			if (this.accounts) {
				accounts = this.accounts.map(function(account) {
					return account.toJSON();
				});
				accounts = _.filter(accounts, function(account) {
					return account.accountId !== -1;
				});
			}

			return {
				aliases: accounts,
				isReply: this.isReply(),
				to: this.data.to,
				cc: this.data.cc,
				subject: this.data.subject,
				message: this.data.body,
				submitButtonTitle: this.isReply() ? t('mail', 'Reply') : t('mail', 'Send'),
				// Reply data
				replyToList: this.data.replyToList,
				replyCc: this.data.replyCc,
				replyCcList: this.data.replyCcList
			};
		},
		type: 'new',
		data: null,
		attachments: null,
		accounts: null,
		account: null,
		folderId: null,
		messageId: null,
		draftInterval: 1500,
		draftTimer: null,
		draftUID: null,
		hasData: false,
		autosized: false,
		regions: {
			attachmentsRegion: '.new-message-attachments'
		},
		events: {
		        'click .submit-message': 'submitMessage',
			'click .submit-message-wrapper-inside': 'submitMessageWrapperInside',
			'keypress .message-body': 'handleKeyPress',
			'click .toggle-editor': 'toggleEditor',
			'input  .to': 'onInputChanged',
			'paste  .to': 'onInputChanged',
			'keyup  .to': 'onInputChanged',
			'input  .cc': 'onInputChanged',
			'paste  .cc': 'onInputChanged',
			'keyup  .cc': 'onInputChanged',
			'input  .bcc': 'onInputChanged',
			'paste  .bcc': 'onInputChanged',
			'keyup  .bcc': 'onInputChanged',
			'input  .subject': 'onInputChanged',
			'paste  .subject': 'onInputChanged',
			'keyup  .subject': 'onInputChanged',
			'input  .message-body': 'onInputChanged',
			'paste  .message-body': 'onInputChanged',
			'keyup  .message-body': 'onInputChanged',
			'tbwchange 	.message-body' : 'onInputChanged',
			'focus  .recipient-autocomplete': 'onAutoComplete',
			// CC/BCC toggle
			'click .composer-cc-bcc-toggle': 'ccBccToggle'
		},
		initialize: function(options) {
			var defaultOptions = {
				type: 'new',
				account: null,
				folderId: null,
				messageId: null,
				data: {
					to: '',
					cc: '',
					subject: '',
					body: ''
				}
			};
			_.defaults(options, defaultOptions);
			this.trumbowygOpt = function(){
				this.$('.message-body').trumbowyg({
					btns: [['bold', 'italic', 'underline'],
						['hyperlink'],
						['justifyLeft', 'justifyCenter', 'justifyRight', 'justifyFull']
					],
					resetCss: true,
					semantic:false
				});
			};
			/**
			 * Composer type (new, reply)
			 */
			this.type = options.type;

			/**
			 * Containing element
			 */
			if (options.el) {
				this.el = options.el;
			}

			/**
			 * Attachments sub-view
			 */
			this.attachments = new Attachments();

			/**
			 * Data for replies
			 */
			this.data = options.data;

			if (!this.isReply()) {
				this.accounts = options.accounts;
				this.account = options.account || this.accounts.at(0);
				this.draftUID = options.data.id;
			} else {
				this.account = options.account;
				this.folderId = options.folderId;
				this.messageId = options.messageId;
			}

		},
		onRender: function() {

			//this.trumbowygOpt();
			this.attachmentsRegion.show(new AttachmentsView({
				collection: this.attachments
			}));

			$('.tipsy-mailto').tipsy({gravity: 'n', live: true});

			if (this.isReply()) {
				// Expand reply message body on click
				var _this = this;
				this.$('.message-body').click(function() {
					_this.setAutoSize(true);
				});
			} else {
				this.setAutoSize(true);
			}

		},
		setAutoSize: function(state) {
			if (state === true) {
				if (!this.autosized) {
					this.$('textarea').autosize({append: '\n\n'});
					this.autosized = true;
				}
				this.$('.message-body').trigger('autosize.resize');
			} else {
				this.$('.message-body').trigger('autosize.destroy');

				// dirty workaround to set reply message body to the default size
				this.$('.message-body').css('height', '');
				this.autosized = false;
			}
		},
		isReply: function() {
			return this.type === 'reply';
		},
		onInputChanged: function() {
			// Submit button state
			var to = this.$('.to').val();
			var subject = this.$('.subject').val();
			if(!$('.toggle-editor').prop('checked')) {
				var body = this.$('.message-body').val();
			}else{
				var body = this.$('.message-body').trumbowyg('html');
			}
			if (to !== '' || subject !== '' || body !== '') {
				this.$('.submit-message').removeAttr('disabled');
			} else {
				this.$('.submit-message').attr('disabled', true);
			}

			// Save draft
			this.hasData = true;
			clearTimeout(this.draftTimer);
			var _this = this;
			this.draftTimer = setTimeout(function() {
				_this.saveDraft();
			}, this.draftInterval);
		},
		toggleEditor: function()
		{
			if(!$('.toggle-editor').prop('checked')) {
				this.$('.message-body').trumbowyg('destroy');
				if(this.isReply()){
					this.$('.message-body').first().val(this.replyText);
				}
			} else {
				this.trumbowygOpt();
				if(this.isReply()){

					this.$('.message-body').first().trumbowyg('html',this.replyHtml);

				}
			}
		},
		handleKeyPress: function(event) {
			// Define which objects to check for the event properties.
			// (Window object provides fallback for IE8 and lower.)
			event = event || window.event;
			var key = event.keyCode || event.which;
			// If enter and control keys are pressed:
			// (Key 13 and 10 set for compatibility across different operating systems.)
			if ((key === 13 || key === 10) && event.ctrlKey) {
				// If the new message is completely filled, and ready to be sent:
				// Send the new message.
				var sendBtnState = this.$('.submit-message').attr('disabled');
				if (sendBtnState === undefined) {
					this.submitMessage();
				}
			}
			return true;
		},
		ccBccToggle: function(e) {
			e.preventDefault();
			this.$('.composer-cc-bcc').slideToggle();
			this.$('.composer-cc-bcc .cc').focus();
			this.$('.composer-cc-bcc-toggle').fadeOut();
		},
		getMessage: function() {
			var message = {};
			var newMessageBody = this.$('.message-body');
			var to = this.$('.to');
			var cc = this.$('.cc');
			var bcc = this.$('.bcc');
			var subject = this.$('.subject');

			message.to = to.val();
			message.cc = cc.val();
			message.bcc = bcc.val();
			message.subject = subject.val();
			message.attachments = this.attachments.toJSON();
			if(!$('.toggle-editor').prop('checked')) {
				message.type = 'text/plain';
				message.body = newMessageBody.val();
			}else{
				message.type = "text/html"
				message.body = newMessageBody.trumbowyg('html').replace('<br>&gt;','\n>');
			}
			return message;
		},
		submitMessageWrapperInside: function() {
			// http://stackoverflow.com/questions/487073/check-if-element-is-visible-after-scrolling
			if (this._isVisible()) {
				this.$('.submit-message').click();
			} else {
				$('#mail-message').animate({
					scrollTop: this.$el.offset().top
				}, 1000);
				this.$('.submit-message-wrapper-inside').hide();
				// This function is needed because $('.message-body').focus does not focus the first line
				this._setCaretToPos(this.$('.message-body')[0], 0);
			}
		},
		_setSelectionRange: function(input, selectionStart, selectionEnd) {
			if (input.setSelectionRange) {
				input.focus();
				input.setSelectionRange(selectionStart, selectionEnd);
			} else if (input.createTextRange) {
				var range = input.createTextRange();
				range.collapse(true);
				range.moveEnd('character', selectionEnd);
				range.moveStart('character', selectionStart);
				range.select();
			}
		},
		_setCaretToPos: function(input, pos) {
			this._setSelectionRange(input, pos, pos);
		},
		_isVisible: function() {
			var $elem = this.$el;
			var $window = $(window);
			var docViewTop = $window.scrollTop();
			var docViewBottom = docViewTop + $window.height();
			var elemTop = $elem.offset().top;

			return elemTop <= docViewBottom;
		},
		submitMessage: function() {
			clearTimeout(this.draftTimer);
			//
			// TODO:
			//  - input validation
			//  - feedback on success
			//  - undo lie - very important
			//

			// loading feedback: show spinner and disable elements
			var newMessageBody = this.$('.message-body');
			var newMessageSend = this.$('.submit-message');
			newMessageBody.addClass('icon-loading');
			var to = this.$('.to');
			var cc = this.$('.cc');
			var bcc = this.$('.bcc');
			var subject = this.$('.subject');
			this.$('.mail-account').prop('disabled', true);
			to.prop('disabled', true);
			cc.prop('disabled', true);
			bcc.prop('disabled', true);
			subject.prop('disabled', true);
			this.$('.new-message-attachments-action').css('display', 'none');
			this.$('#mail_new_attachment').prop('disabled', true);
			newMessageBody.trumbowyg('disable');
			newMessageSend.prop('disabled', true);
			newMessageSend.val(t('mail', 'Sending …'));

			// if available get account from drop-down list
			if (this.$('.mail-account').length > 0) {
				this.account = this.accounts.get(this.$('.mail-account').
					find(':selected').val());
			}

			// send the mail
			var _this = this;
			var options = {
				draftUID: this.draftUID
			};

			if (this.isReply()) {
				options.messageId = this.messageId;
				options.folder = this.account.getFolderById(this.folderId);
			}

			var sendingMessage = Radio.message.request('send', this.account, this.getMessage(), options);
			$.when(sendingMessage).done(function() {
				OC.Notification.showTemporary(t('mail', 'Message sent!'));

				_this.$('#mail_new_message').prop('disabled', false);
				to.val('');
				cc.val('');
				bcc.val('');
				subject.val('');
				if(!$('.toggle-editor').prop('checked')) {
					newMessageBody.val('');
				}else{
					newMessageBody.trumbowyg('html','');
				}
				newMessageBody.trigger('autosize.resize');
				_this.attachments.reset();
				if (_this.draftUID !== null) {
					// the sent message was a draft
					if (!_.isUndefined(Radio.ui.request('messagesview:collection'))) {
						Radio.ui.request('messagesview:collection').
							remove({id: _this.draftUID});
					}
					_this.draftUID = null;
				}
			});
			$.when(sendingMessage).fail(function(jqXHR) {
				var error = '';
				if (jqXHR.status === 500) {
					error = t('mail', 'Server error');
				} else {
					var resp = JSON.parse(jqXHR.responseText);
					error = resp.message;
				}
				newMessageSend.prop('disabled', false);
				OC.Notification.showTemporary(error);
			});
			$.when(sendingMessage).always(function() {
				// remove loading feedback
				newMessageBody.removeClass('icon-loading');
				_this.$('.mail-account').prop('disabled', false);
				to.prop('disabled', false);
				cc.prop('disabled', false);
				bcc.prop('disabled', false);
				subject.prop('disabled', false);
				_this.$('.new-message-attachments-action').
					css('display', 'inline-block');
				_this.$('#mail_new_attachment').prop('disabled', false);
				newMessageBody.trumbowyg('enable');
				newMessageSend.prop('disabled', false);
				newMessageSend.val(t('mail', 'Send'));
			});
			return false;
		},
		saveDraft: function(onSuccess) {
			clearTimeout(this.draftTimer);
			//
			// TODO:
			//  - input validation
			//  - feedback on success
			//  - undo lie - very important
			//

			// if available get account from drop-down list
			if (this.$('.mail-account').length > 0) {
				this.account = this.accounts.get(this.$('.mail-account').
					find(':selected').val());
			}

			// send the mail
			var _this = this;
			var savingDraft = Radio.message.request('draft', this.account, this.getMessage(), {
				folder: this.account.getFolderById(this.folderId),
				messageId: this.messageId,
				draftUID: this.draftUID
			});
			$.when(savingDraft).done(function(data) {
				if (_.isFunction(onSuccess)) {
					onSuccess();
				}
				_this.draftUID = data.uid;
			});
			$.when(savingDraft).fail(function() {
				// TODO: show error
			});
			return false;
		},
		setReplyBody: function(from, date, text) {
			var minutes = date.getMinutes();
			this.replyHtml = '<br/><br/><br/>' +
				from + ' – ' +
				$.datepicker.formatDate('D, d. MM yy ', date) +
				date.getHours() + ':' + (minutes < 10 ? '0' : '') + minutes + '<br>&gt; ' +
				text.replace(/(?:\r\n|\r|\n)/g, '<br>&gt;');
			this.replyText = 	'\n\n\n' +
				from + ' – ' +
				$.datepicker.formatDate('D, d. MM yy ', date) +
				date.getHours() + ':' + (minutes < 10 ? '0' : '') + minutes + '\n> ' +
				text.replace(/(?:\r\n|\r|\n)/g, '\n> ');
				this.$('.message-body').val(this.replyText);
			//this.$('.message-body').first().trumbowyg('html',this.replyHtml);

			this.setAutoSize(false);
			// Expand reply message body on click
			var _this = this;
			this.$('.message-body').click(function() {
				_this.setAutoSize(true);
			});
		},
		focusTo: function() {
			this.$el.find('input.to').focus();
		},
		setTo: function(value) {
			this.$el.find('input.to').val(value);
		},
		focusSubject: function() {
			this.$el.find('input.subject').focus();
		},
		onAutoComplete: function(e) {
			var elem = $(e.target);
			function split(val) {
				return val.split(/,\s*/);
			}

			function extractLast(term) {
				return split(term).pop();
			}
			if (!elem.data('autocomplete')) {
				// If the autocomplete wasn't called yet:
				// don't navigate away from the field on tab when selecting an item
				elem.bind('keydown', function(event) {
					if (event.keyCode === $.ui.keyCode.TAB &&
						typeof elem.data('autocomplete') !== 'undefined' &&
						elem.data('autocomplete').menu.active) {
						event.preventDefault();
					}
				}).autocomplete({
					source: function(request, response) {
						$.getJSON(
							OC.generateUrl('/apps/mail/autoComplete'),
							{
								term: extractLast(request.term)
							}, response);
					},
					search: function() {
						// custom minLength
						var term = extractLast(this.value);
						return term.length >= 2;

					},
					focus: function() {
						// prevent value inserted on focus
						return false;
					},
					select: function(event, ui) {
						var terms = split(this.value);
						// remove the current input
						terms.pop();
						// add the selected item
						terms.push(ui.item.value);
						// add placeholder to get the comma-and-space at the end
						terms.push('');
						this.value = terms.join(', ');
						return false;
					}
				});
			}
		}
	});

});

