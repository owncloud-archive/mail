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
	var Marionette = require('marionette');
	var Handlebars = require('handlebars');
	var SignatureListTemplate = require('text!templates/signature-list.html');
	var Radio = require('radio');

	return Marionette.ItemView.extend({
		collection: null,
		model: null,
		tagName: 'select',
		childViewContainer: '.signature-container',
		template: Handlebars.compile(SignatureListTemplate),
		templateHelpers: function() {
			return {
				data: this.model.toJSON()
			};
		},
		// ui: {
		// 	'updateButton' : '.update-signature-button'
		// },
		// events: {
		// 	'click @ui.updateButton': 'updateSignature'
		// },
		initialize: function(options) {
			this.model = options.model;
			console.log(this.model);
		},
		updateSignature: function(event) {
			event.stopPropagation();

			// var currentAccount = require('state').accounts.get(this.model.get('accountId'));
			// var _this = this;
			// var deletingAlias = Radio.aliases.request('delete:alias', currentAccount, this.model.get('id'));
			// this.ui.deleteButton.prop('disabled', true);
			// this.ui.deleteButton.attr('class', 'icon-loading-small');
			// $.when(deletingAlias).done(function() {
			// 	currentAccount.get('aliases').remove(_this.model);
			// });
			// $.when(deletingAlias).always(function() {
			// 	var aliases = currentAccount.get('aliases');
			// 	if (aliases.get(_this.model)) {
			// 		_this.ui.deleteButton.attr('class', 'icon-delete');
			// 		_this.ui.deleteButton.prop('disabled', false);
			// 	}
			// });
		},
		saveAliasSignature: function(alias, signature) {
			// console.log(alias);
			// var defer = $.Deferred();
			//
			// var url = OC.generateUrl('aliases/{id}/signature', {
			// 	id: alias.get('accountId')
			// });
			// var data = {
			// 	type: 'POST',
			// 	data: {
			// 		accountId: account.get('accountId'),
			// 		alias: alias.alias,
			// 		aliasName: alias.name
			// 	}
			// };
			// var promise =  $.ajax(url, data);
			//
			// promise.done(function(data) {
			// 	defer.resolve(data);
			// });
			//
			// promise.fail(function() {
			// 	defer.reject();
			// });
			//
			// return defer.promise();
		}
	});
});
