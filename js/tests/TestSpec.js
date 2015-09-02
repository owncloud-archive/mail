/**
 * ownCloud - Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015
 */

define(['views/attachments'], function(AttachmentView) {

	describe('View test', function() {

		beforeEach(function() {
			$('body').append('<div id="#mail-attachments-template"></div>');
			this.AttachmentView = new AttachmentView({});
		});

		afterEach(function() {
			this.AttachmentView.remove();
			$('#mail-attachments-template').remove();
		});

		it('should pass', function() {
			expect(1).toEqual(1);
		});

		it('should also pass', function() {
			expect(2).toEqual(2);
		});
	});
});

define(['models/account'], function(Account) {
	describe('test tests', function() {
		it('should pass', function() {
			expect(1).toEqual(1);
		});
		it('should also pass', function() {
			expect(2).toEqual(2);
		});
	});
});
