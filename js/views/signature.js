/**
 * ownCloud - Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Tahaa Karim <tahaalibra@gmail.com>
 * @copyright Tahaa Karim 2016
 */

define(function(require) {
	'use strict';

	var $ = require('jquery');
	var _ = require('underscore');
	var Marionette = require('marionette');
	var Handlebars = require('handlebars');
	var SignatureTemplate = require('text!templates/signature.html');
	var Radio = require('radio');
	var OC = require('OC');

	return Marionette.LayoutView.extend({
		currentAccount: null,
		template: Handlebars.compile(SignatureTemplate),
		aliases: null,
		signature: null,
		templateHelpers: function() {
			var aliases = this.buildAliases();
			this.signature = this.aliases[0].signature;
			return {
				aliases: this.aliases,
				signature: this.signature
			};
		},
		ui: {
			'updateButton' : '.update-signature-button',
			'signatureBody': '.signature-body',
			'aliasSelect': '.mail-account'
		},
		events: {
			'click @ui.updateButton': 'updateSignature',
			'click @ui.aliasSelect': 'showSignature'
		},
		initialize: function(options) {
			this.currentAccount = options.currentAccount;
		},
		buildAliases: function() {
			var aliases = [];
			var id = 0;
			var json = this.currentAccount.toJSON();
			console.log(json);
			// add Primary email address
			aliases.push({
				id: id++,
				accountId: json.accountId,
				aliasId: null,
				emailAddress: json.emailAddress,
				name: json.name,
				signature: json.signature
			});
			// add Aliases email adresses
			for (var x in json.aliases) {
				aliases.push({
					id: id++,
					accountId: json.aliases[x].accountId,
					aliasId: json.aliases[x].id,
					emailAddress: json.aliases[x].alias,
					name: json.aliases[x].name,
					signature: json.aliases[x].signature
				});
			}
			this.aliases = aliases;
		},
		findAliasById: function(id) {
		return _.find(this.aliases, function(alias) { return parseInt(alias.id)  === parseInt(id); });
	    },
		showSignature: function() {
			var alias = this.findAliasById(this.$('.mail-account').
			find(':selected').val());
			this.ui.signatureBody.val(alias.signature);
		},
		updateSignature: function(event) {
			event.stopPropagation();
			var alias = this.findAliasById(this.$('.mail-account').
			find(':selected').val());
			var signature = this.ui.signatureBody.val();
			var _this = this;

			if(alias.aliasId){
				var updateSignature = this.saveAliasSignature(alias, signature);
				this.ui.updateButton.prop('disabled', true);
				this.ui.signatureBody.prop('disabled', true);
				this.ui.updateButton.val('Saving');

				$.when(updateSignature).done(function() {
					// set signature to alias in aliases
					_this.aliases[alias.id].signature = signature;
				});

				$.when(updateSignature).always(function() {
					_this.ui.updateButton.prop('disabled', false);
					_this.ui.signatureBody.prop('disabled', false);
					_this.ui.updateButton.val('Save');
				});
			} else {
				var updateSignature = this.saveAccountSignature(alias, signature);
				this.ui.updateButton.prop('disabled', true);
				this.ui.signatureBody.prop('disabled', true);
				this.ui.updateButton.val('Saving');

				$.when(updateSignature).done(function() {
					// set signature to alias in aliases
					_this.aliases[alias.id].signature = signature;
				});

				$.when(updateSignature).always(function() {
					_this.ui.updateButton.prop('disabled', false);
					_this.ui.signatureBody.prop('disabled', false);
					_this.ui.updateButton.val('Save');
				});
			}
		},
		saveAliasSignature: function(alias, signature) {
			console.log(signature);
			var defer = $.Deferred();

			var url = OC.generateUrl('/apps/mail/aliases/{id}/signature', {
				id: alias.aliasId
			});
			var data = {
				type: 'POST',
				data: {
					signature: signature
				}
			};
			var promise =  $.ajax(url, data);

			promise.done(function(data) {
				defer.resolve(data);
			});

			promise.fail(function() {
				defer.reject();
			});

			return defer.promise();
		},
		saveAccountSignature: function(alias, signature) {
			var defer = $.Deferred();

			var url = OC.generateUrl('/apps/mail/accounts/{id}/signature', {
				id: alias.accountId
			});
			var data = {
				type: 'POST',
				data: {
					signature: signature
				}
			};
			var promise =  $.ajax(url, data);

			promise.done(function(data) {
				defer.resolve(data);
			});

			promise.fail(function() {
				defer.reject();
			});

			return defer.promise();
		}
	});
});
