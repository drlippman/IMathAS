/*
 * Load after jquery.validate.min.js.
 * jQuery Validate binds delegated click handlers directly on the <form>,
 * which makes screen readers announce the form as "clickable".  Move those
 * handlers to document, limited to clicks inside the validated form.
 */
(function ($) {
  if (!$ || !$.fn || !$.fn.validate) { return; }
  var orig = $.fn.validate;
  $.fn.validate = function () {
    var isNew = this.length && !$.data(this[0], 'validator');
    var res = orig.apply(this, arguments);
    if (isNew && res && res.currentForm) {
      var form = res.currentForm;
      var ev = $._data(form, 'events');
      var clicks = ev && ev.click ? ev.click.slice() : [];
      $.each(clicks, function (i, h) {
        if (h.namespace !== 'validate' || !h.selector) { return; }
        $(form).off('click.validate', h.selector, h.handler);
        $(document).on('click.validate', h.selector, function (e) {
          if ($(this).closest('form')[0] !== form) { return; }
          return h.handler.apply(this, arguments);
        });
      });
    }
    return res;
  };
  $.extend($.fn.validate, orig);
})(window.jQuery);
