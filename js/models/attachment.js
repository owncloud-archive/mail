/* global Backbone, _ */

define(function() {
	return Backbone.Model.extend({
		initialize: function() {
			this.set('id', _.uniqueId());

			var s = this.get('fileName');
			if (s.charAt(0) === '/') {
				s = s.substr(1);
			}

			this.set('displayName', s);
		}
	});
});
