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
	var _ = require('underscore');
	var Radio = require('radio');

	Radio.signature.reply('update:aliasSignature', updateAliasSignature);
	Radio.signature.reply('update:accountSignature', updateAccountSignature);

	/**
	 * @param alias
	 * @param signature
	 * @returns {undefined}
	 */
	function updateAliasSignature(alias, signature) {
		var updatingSignature = Radio.signature.request('saveAliasSignature', alias, signature);

		$.when(updatingSignature).fail(function() {
			Radio.ui.trigger('error:show', t('mail', 'Saving Signature Failed.'));
		});

		return updatingSignature;
	}

	/**
	 * @param account
	 * @param signature
	 * @returns {undefined}
	 */
	function updateAccountSignature(account, signature) {
		var updatingSignature = Radio.signature.request('saveAccountSignature', account, signature);

		$.when(updatingSignature).fail(function() {
			Radio.ui.trigger('error:show', t('mail', 'Saving Signature Failed.'));
		});

		return updatingSignature;
	}

});
