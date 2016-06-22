(function ($) {
	'use strict';
	$.extend(true, $.trumbowyg, {
    langs: {
		en: {
			toggleeditor: 'text only'
			}
		},
    plugins: {
      toggleeditor: {
        init: function (trumbowyg) {
          var btnDef = {
            ico: 'close',
            fn:function () {
              var t = trumbowyg;
              var  prefix = t.o.prefix;
              t.semanticCode(false, true);
							t.$c.trigger('octoggle');
              setTimeout(function () {
                  document.activeElement.blur();
                  t.$box.toggleClass(prefix + 'editor-hidden ' + prefix + 'editor-visible');
                  t.$btnPane.toggleClass(prefix + 'disable');
                  $('.' + prefix + 'toggleeditor-button', t.$btnPane).toggleClass(prefix + 'active');
                  if (t.$box.hasClass(prefix + 'editor-visible')) {
                      t.$ta.attr('tabindex', -1);
                  } else {
                      t.$ta.removeAttr('tabindex');
                  }
              }, 0);
          	}
        	};
          trumbowyg.addBtnDef('toggleeditor', btnDef);
        }
      }
    }
  });
})(jQuery);
