// Minimal modal controller. Markup: a container #<id> with .modal-overlay + .modal-panel.
// window.Modal.open(id), window.Modal.close(id). Esc + backdrop close, focus is trapped
// inside the dialog and returned to the trigger on close.
(function ($) {
  const FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]):not([type=hidden]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';
  const returnTo = {};

  function trap(e, $root) {
    if (e.key !== 'Tab') return;
    const $f = $root.find(FOCUSABLE).filter(':visible');
    if (!$f.length) { e.preventDefault(); return; }
    const first = $f[0], last = $f[$f.length - 1];
    if (e.shiftKey && (document.activeElement === first || !$root[0].contains(document.activeElement))) { e.preventDefault(); last.focus(); }
    else if (!e.shiftKey && (document.activeElement === last || !$root[0].contains(document.activeElement))) { e.preventDefault(); first.focus(); }
  }

  const Modal = {
    open(id) {
      const $m = $('#' + id);
      returnTo[id] = document.activeElement;
      $m.removeClass('hidden').attr('aria-hidden', 'false');
      if (window.clearErrors) window.clearErrors($m.find('form'));
      $m.find('[autofocus], input, select, textarea, button').filter(':visible').not('[data-modal-close]').first().trigger('focus');
      $('body').addClass('overflow-hidden');
    },
    close(id) {
      const $m = id ? $('#' + id) : $('.modal-root:not(.hidden)').not('#confirm-dialog');
      $m.each(function () {
        const mid = this.id;
        $(this).addClass('hidden').attr('aria-hidden', 'true');
        const el = returnTo[mid];
        delete returnTo[mid];
        if (el && el.focus && document.contains(el)) el.focus();
      });
      if (!$('.modal-root:not(.hidden)').length) $('body').removeClass('overflow-hidden');
    },
  };

  $(function () {
    $(document).on('click', '[data-modal-open]', function () { Modal.open($(this).data('modal-open')); });
    $(document).on('click', '[data-modal-close]', function () { Modal.close($(this).closest('.modal-root').attr('id')); });
    $(document).on('click', '.modal-overlay', function () {
      const $r = $(this).closest('.modal-root');
      if ($r.attr('id') !== 'confirm-dialog') Modal.close($r.attr('id'));
    });
    $(document).on('keydown', function (e) {
      if ($('#confirm-dialog').length) return; // confirm dialog handles its own keys
      if (e.key === 'Escape') Modal.close();
      else {
        const $open = $('.modal-root:not(.hidden)').last();
        if ($open.length) trap(e, $open);
      }
    });
  });

  window.Modal = Modal;

  // Promise-based styled confirm dialog — replaces window.confirm().
  // window.confirmDialog({title, message, confirmText, cancelText, danger}) -> Promise<bool>
  // title/message are treated as plain text (escaped).
  window.confirmDialog = function (opts) {
    opts = opts || {};
    var danger = opts.danger !== false; // default to danger styling
    var e = window.esc;
    var opener = document.activeElement;
    return new Promise(function (resolve) {
      $('#confirm-dialog').remove();
      var html =
        '<div id="confirm-dialog" class="modal-root" role="alertdialog" aria-modal="true" aria-labelledby="cd-title" aria-describedby="cd-msg">' +
          '<div class="modal-overlay"></div>' +
          '<div class="modal-panel" style="max-width:400px">' +
            '<div class="modal-body" style="display:flex;gap:.85rem;align-items:flex-start">' +
              '<div class="confirm-ico ' + (danger ? 'is-danger' : 'is-accent') + '" aria-hidden="true">!</div>' +
              '<div><div id="cd-title" style="font-weight:600">' + e(opts.title || 'Are you sure?') + '</div>' +
              '<div id="cd-msg" style="color:var(--text-muted);font-size:.8125rem;margin-top:.2rem">' + e(opts.message || '') + '</div></div>' +
            '</div>' +
            '<div class="modal-footer">' +
              '<button type="button" class="btn btn-secondary" data-cd="cancel">' + e(opts.cancelText || 'Cancel') + '</button>' +
              '<button type="button" class="btn ' + (danger ? 'btn-danger' : 'btn-primary') + '" data-cd="ok">' + e(opts.confirmText || 'Confirm') + '</button>' +
            '</div>' +
          '</div>' +
        '</div>';
      var $d = $(html).appendTo('body');
      $('body').addClass('overflow-hidden');
      function close(v) {
        $(document).off('keydown.cd'); $d.remove();
        if (!$('.modal-root:not(.hidden)').length) $('body').removeClass('overflow-hidden');
        if (opener && opener.focus && document.contains(opener)) opener.focus();
        resolve(v);
      }
      $d.on('click', '[data-cd=ok]', function () { close(true); });
      $d.on('click', '[data-cd=cancel], .modal-overlay', function () { close(false); });
      $(document).on('keydown.cd', function (ev) {
        if (ev.key === 'Escape') close(false); else trap(ev, $d);
      });
      $d.find('[data-cd=ok]').trigger('focus');
    });
  };
})(jQuery);
