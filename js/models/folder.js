/* global Backbone */

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
	return Backbone.Model.extend({
		defaults: {
			open: false,
			folders: []
		},
		initialize: function() {
			var FolderList = require('models/folderlist');
			this.set('folders', new FolderList(this.get('folders')));
		},
		toggleOpen: function() {
			this.set({open: !this.get('open')});
		},
		toJSON: function() {
			var data = Backbone.Model.prototype.toJSON.call(this);
			if (data.folders && data.folders.toJSON) {
				data.folders = data.folders.toJSON();
			}
			if (!data.id) {
				data.id = this.cid;
			}
			return data;
		}
	});
});
