/**
 * @author Tahaa Karim <tahaalibra@gmail.com>
 *
 * ownCloud - Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

define(function(require) {
	'use strict';

	var $ = require('jquery');
	var OC = require('OC');
	var Radio = require('radio');

	Radio.signature.reply('saveAliasSignature', saveAliasSignature);
	Radio.signature.reply('saveAccountSignature', saveAccountSignature);

	/**
	 * @param aliasId
	 * @param signature
	 * @returns {undefined}
	 */
	function saveAliasSignature(aliasId, signature) {
		var defer = $.Deferred();

		var url = OC.generateUrl('/apps/mail/aliases/{id}/signature', {
			id: aliasId
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

	/**
	 * @param accountId
	 * @param signature
	 * @returns {undefined}
	 */
	function saveAccountSignature(accountId, signature) {
		var defer = $.Deferred();

		var url = OC.generateUrl('/apps/mail/accounts/{id}/signature', {
			id: accountId
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