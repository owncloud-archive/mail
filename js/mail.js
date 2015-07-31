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
	var Mail = {};

	Mail.BackGround = require('background');
	Mail.Cache = require('cache');
	Mail.Communication = require('communication');
	Mail.Search = require('search');
	Mail.State = require('state');
	Mail.UI = require('UI');

	return Mail;
});