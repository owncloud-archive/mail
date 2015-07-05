/* global Backbone, Handlebars, models, OC, Mail, _ */

var views = views || {};

views.SendMail = Backbone.View.extend({

	// The collection will be kept here
	attachments: null,

	sentCallback: null,

	aliases: null,
	currentAccountId: null,
	data: null,
	draftIntervalIMAP: 1500,
	draftIntervalLocal: 100,
	draftTimerIMAP: null,
	draftTimerLocal: null,
	draftUID: null,
	hasData: false,
	hasUnsavedChanges: false,

	events: {
		"click #new-message-send" : "sendMail",
		"click #new-message-draft" : "saveDraft",
		"keypress #new-message-body" : "handleKeyPress",
		"keyup #new-message-body": "handleKeyUp",
		"keyup #to": "handleKeyUp",
		"keyup #cc": "handleKeyUp",
		"keyup #bcc": "handleKeyUp",
		"keyup #subject": "handleKeyUp",
		"click .mail_account" : "changeAlias"
	},

	initialize: function(options) {
		this.attachments = new models.Attachments();

		this.aliases = _.filter(options.aliases, function(item) {
			return item.accountId !== -1;
		});

		if (options.data) {
			this.data = options.data;
			this.draftUID = options.data.id;
		}
		this.el = options.el;
		this.currentAccountId = this.aliases[0].accountId;
	},

	changeAlias: function(event) {
		this.currentAccountId = parseInt($(event.target).val(), 10);
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
			var sendBtnState = $('#new-message-send').attr('disabled');
			if (sendBtnState === undefined) {
				this.sendMail();
			}
		}
		return true;
	},

	handleKeyUp: function() {
		this.hasData = true;
		this.hasUnsavedChanges = true;
		clearTimeout(this.draftTimerIMAP);
		clearTimeout(this.draftIntervalLocal);
		var self = this;
		this.draftTimerIMAP = setTimeout(function() {
			self.saveDraft();
		}, this.draftIntervalIMAP);
		this.draftTimerLocal = setTimeout(function() {
			self.saveDraftLocally();
		}, this.draftIntervalLocal);
	},

	getMessage: function() {
		var message = {};
		var newMessageBody = $('#new-message-body');
		var to = $('#to');
		var cc = $('#cc');
		var bcc = $('#bcc');
		var subject = $('#subject');

		message.body = newMessageBody.val();
		message.to = to.val();
		message.cc = cc.val();
		message.bcc = bcc.val();
		message.subject = subject.val();
		message.attachments = this.attachments.toJSON();

		return message;
	},

	sendMail: function() {
		clearTimeout(this.draftTimerIMAP);
		//
		// TODO:
		//  - input validation
		//  - feedback on success
		//  - undo lie - very important
		//

		// loading feedback: show spinner and disable elements
		var newMessageBody = $('#new-message-body');
		var newMessageSend = $('#new-message-send');
		newMessageBody.addClass('icon-loading');
		var to = $('#to');
		var cc = $('#cc');
		var bcc = $('#bcc');
		var subject = $('#subject');
		$('.mail_account').prop('disabled', true);
		to.prop('disabled', true);
		cc.prop('disabled', true);
		bcc.prop('disabled', true);
		subject.prop('disabled', true);
		$('.new-message-attachments-action').css('display', 'none');
		$('#mail_new_attachment').prop('disabled', true);
		$('#mail_new_attachment_local').prop('disabled', true);
		newMessageBody.prop('disabled', true);
		newMessageSend.prop('disabled', true);
		newMessageSend.val(t('mail', 'Sending …'));

		var message = this.getMessage();
		var self = this;
		var data = new FormData();
		jQuery.each($('#fileupload')[0].files, function(i, file) {
			data.append('file-'+i, file);
		});

		data.append('to', message.to);
		data.append('cc', message.cc);
		data.append('bcc', message.bcc);
		data.append('subject', message.subject);
		data.append('body', message.body);
		data.append('attachments', message.attachments);
		data.append('files', message.files);
		data.append('draftUID', this.draftUID);

		// send the mail
		$.ajax({
			url:OC.generateUrl('/apps/mail/accounts/{accountId}/send', {accountId: this.currentAccountId}),
			type: 'POST',
			contentType: false,
			processData: false,
			data: data,
			success:function () {
				OC.Notification.showTemporary(t('mail', 'Message sent!'));

				// close composer
				if (self.sentCallback !== null) {
					self.sentCallback();
				} else {
					$('#new-message').slideUp();
				}
				$('#mail_new_message').prop('disabled', false);
				to.val('');
				cc.val('');
				bcc.val('');
				subject.val('');
				newMessageBody.val('');
				newMessageBody.trigger('autosize.resize');
				self.attachments.reset();
				if (self.draftUID !== null) {
					// the sent message was a draft
					Mail.UI.messageView.collection.remove({id: self.draftUID});
					self.draftUID = null;
				}
				self.hasUnsavedChanges = false;
			},
			error: function (jqXHR) {
				newMessageSend.prop('disabled', false);
				OC.Notification.showTemporary(jqXHR.responseJSON.message);
			},
			complete: function() {
				// remove loading feedback
				newMessageBody.removeClass('icon-loading');
				$('.mail_account').prop('disabled', false);
				to.prop('disabled', false);
				cc.prop('disabled', false);
				bcc.prop('disabled', false);
				subject.prop('disabled', false);
				$('.new-message-attachments-action').css('display', 'inline-block');
				$('#mail_new_attachment').prop('disabled', false);
				$('#mail_new_attachment_local').prop('disabled', false);
				newMessageBody.prop('disabled', false);
				newMessageSend.prop('disabled', false);
				newMessageSend.val(t('mail', 'Send'));
			}
		});

		return false;
	},

	saveDraftLocally: function() {
		var storage = $.localStorage;
		storage.set("draft", "default", this.getMessage());
	},

	saveDraft: function(onSuccess) {
		clearTimeout(this.draftTimerIMAP);
		//
		// TODO:
		//  - input validation
		//  - feedback on success
		//  - undo lie - very important
		//

		var message = this.getMessage();
		var self = this;
		// send the mail
		$.ajax({
			url: OC.generateUrl('/apps/mail/accounts/{accountId}/draft', {accountId: this.currentAccountId}),
			beforeSend:function () {
				OC.msg.startAction('#new-message-msg', "");
			},
			type: 'POST',
			data: {
				'to': message.to,
				'cc': message.cc,
				'bcc': message.bcc,
				'subject': message.subject,
				'body': message.body,
				'uid': self.draftUID
			},
			success: function (data) {
				if (self.draftUID !== null) {
					// update UID in message list
					var message = Mail.UI.messageView.collection.findWhere({id: self.draftUID});
					if (message) {
						message.set({id: data.uid});
						Mail.UI.messageView.collection.set([message], {remove: false});
					}
				}
				if (_.isFunction(onSuccess)) {
					onSuccess();
				}
				self.draftUID = data.uid;
				self.hasUnsavedChanges = false;
			},
			error: function (jqXHR) {
				OC.msg.finishedAction('#new-message-msg', {
					status: 'error',
					data: {
						message: jqXHR.responseJSON.message
					}
				});
			}
		});
		return false;
	},

	render: function() {
		var source   = $("#new-message-template").html();
		var template = Handlebars.compile(source);
		var data = {
			aliases: this.aliases
		};

		// draft data
		if (this.data) {
			data.to = this.data.toEmail;
			data.subject = this.data.subject;
			data.message = this.data.body;
		}

		var html = template(data);

		this.$el.html(html);

		var view = new views.Attachments({
			el: $('#new-message-attachments'),
			collection: this.attachments
		});

		// And render it
		view.render();

		$('textarea').autosize({append:'"\n\n"'});

		return this;
	}
});
