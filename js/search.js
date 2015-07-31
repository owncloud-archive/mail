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
	return {
		timeoutID: null,
		attach: function(search) {
			search.setFilter('mail', require('Mail').Search.filter);
		},
		filter: function(query) {
			window.clearTimeout(require('Mail').Search.timeoutID);
			require('Mail').Search.timeoutID = window.setTimeout(function() {
				require('Mail').UI.messageView.filterCurrentMailbox(query);
			}, 500);
			$('#searchresults').hide();
		}
	};
});
