(function ($) {
	'use strict';
	$.extend(true, $.trumbowyg, {
	langs: {
		en: {
			createLinks: 'create link',
			removelinks: 'unlink'
			}
		}
	});

	$.extend(true, $.trumbowyg, {
	plugins: {
		hyperlink: {
			init: function (trumbowyg) {
			var btnDef = {
				dropdown: [
				'createLinks','removelinks'
			],
		ico: 'link'
		};
		var createDef = {
			fn: function () {
				var t = trumbowyg,
					documentSelection = t.doc.getSelection(),
					node = documentSelection.focusNode,
					url;
				while (['A', 'DIV'].indexOf(node.nodeName) < 0) {
					node = node.parentNode;
				}

				if (node && node.nodeName === 'A') {
					var $a = $(node);
					url = $a.attr('href');
					var range = t.doc.createRange();
					range.selectNode(node);
					documentSelection.addRange(range);
				}

			t.saveRange();

			t.openModalInsert(t.lang.createLink, {
				url: {
					label: 'URL',
					required: true,
					value: url
                            },
				text: {
					label: t.lang.text,
					value: t.getRangeText()
				}
			}, function (v) { // v is value
				var link = $(['<a href="', v.url, '">', v.text, '</a>'].join(''));
				t.range.deleteContents();
				t.range.insertNode(link[0]);
				return true;
			});
		},
		key: 'K',
		tag: 'a'
	};
	var removeDef = {
		fn:function () {
			var t = trumbowyg,
			documentSelection = t.doc.getSelection(),
			node = documentSelection.focusNode;
			if (documentSelection.isCollapsed) {
				while (['A', 'DIV'].indexOf(node.nodeName) < 0) {
					node = node.parentNode;
			}
			if (node && node.nodeName === 'A') {
				var range = t.doc.createRange();
				range.selectNode(node);
				documentSelection.addRange(range);
			}
		}
		t.execCmd('unlink', undefined, undefined, true);
	}
};
	trumbowyg.addBtnDef('hyperlink', btnDef);
	trumbowyg.addBtnDef('createLinks', createDef);
	trumbowyg.addBtnDef('removelinks',removeDef);
	}
}
}
});
})(jQuery);
