/* global requirejs */

requirejs.config({
	baseUrl: './../../../../apps/mail/js',
	paths: {
		/**
		 * Application
		 */
		app: 'mail',
		/**
		 * Libraries
		 */
		backbone: 'vendor/backbone/backbone',
		domready: 'vendor/domready/ready.min',
		handlebars: 'vendor/handlebars/handlebars',
		jasmine: 'vendor/jasmine-core/lib/jasmine-core',
		'jasmine-html': '../js/vendor/jasmine-core/lib/jasmine-core/jasmine-html',
		jquery: '../../../../../core/vendor/jquery/jquery.min',
		specrunner: './SpecRunner',
		spec: '../tests/jasmine/spec/',
		marionette: 'vendor/backbone.marionette/lib/backbone.marionette',
		underscore: '../../../core/vendor/underscore/underscore'
	},
	shim: {
	}
});

require(['underscore', 'jquery', 'jasmine-html'], function(_, $, jasmine) {

	var jasmineEnv = jasmine.getEnv();
	jasmineEnv.updateInterval = 1000;

	var htmlReporter = new jasmine.HtmlReporter();

	jasmineEnv.addReporter(htmlReporter);

	jasmineEnv.specFilter = function(spec) {

		return htmlReporter.specFilter(spec);
	};

	var specs = [];

	specs.push('spec/models/TodoSpec');

	$(function() {
		require(specs, function() {
			jasmineEnv.execute();
		});
	});

});
