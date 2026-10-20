// AJAX wrapper around jQuery: attaches CSRF, shows loading state on the
// triggering button, surfaces toasts on error, and maps 422 -> inline field
// errors. Returns a jQuery promise resolving to the JSON envelope's data.
(function ($) {
  const csrf = () => $('meta[name="csrf-token"]').attr('content');

  // Global CSRF + AJAX headers for all requests
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' } });

  // answers.5 -> answers[5]; answers.5.0 -> answers[5][0]
  function toBracket(key) {
    const parts = String(key).split('.');
    return parts[0] + parts.slice(1).map(function (p) { return '[' + p + ']'; }).join('');
  }

  // answers.5.0 -> answers.5 (drops trailing numeric segments)
  function baseKey(key) {
    return String(key).replace(/(\.\d+)+$/, '');
  }

  function findInputs($form, key) {
    const base = baseKey(key);
    const candidates = [key, toBracket(key), toBracket(base), toBracket(base) + '[]'];
    for (let i = 0; i < candidates.length; i++) {
      const $f = $form.find('[name="' + candidates[i] + '"]');
      if ($f.length) return $f;
    }
    return $();
  }

  function clearErrors($form) {
    if (!$form || !$form.length) return;
    $form.find('.error-text').removeClass('show').text('').removeAttr('role');
    $form.find('.is-invalid').removeClass('is-invalid');
    $form.find('[aria-invalid]').removeAttr('aria-invalid');
    $form.find('[data-erp-desc]').each(function () {
      $(this).removeAttr('aria-describedby').removeAttr('data-erp-desc');
    });
    $form.find('[data-form-errors]').remove();
  }

  // Form-level box for errors that don't map to any input (e.g. email duplicate, capacity).
  function showFormLevel($form, messages) {
    if (!$form || !$form.length || !messages.length) return;
    let $box = $form.find('[data-form-errors]').first();
    if (!$box.length) {
      $box = $('<div class="form-errors" role="alert" data-form-errors tabindex="-1"></div>');
      $form.prepend($box);
    }
    $box.html('<ul>' + messages.map(function (m) { return '<li>' + window.esc(m) + '</li>'; }).join('') + '</ul>');
    if ($box[0].scrollIntoView) $box[0].scrollIntoView({ block: 'nearest', behavior: 'smooth' });
  }

  function showErrors($form, errors) {
    if (!$form || !$form.length || !errors) return;
    const unmatched = [];
    Object.keys(errors).forEach(function (field) {
      const msg = Array.isArray(errors[field]) ? errors[field][0] : errors[field];
      const $inputs = findInputs($form, field);
      let $err = $form.find('.error-text[data-error="' + field + '"]');
      if (!$err.length) $err = $form.find('.error-text[data-error="' + baseKey(field) + '"]');

      if (!$inputs.length && !$err.length) { unmatched.push(msg); return; }
      // Group errors (answers.5.0 + answers.5) show once.
      if ($err.length && $err.hasClass('show')) return;

      if (!$err.length) {
        $err = $('<div class="error-text"></div>').attr('data-error', field);
        const $last = $inputs.last();
        // Radio/checkbox group: put the error after the group, not after one option.
        const $anchor = $last.is(':radio, :checkbox') ? ($last.closest('.field').length ? null : $last.parent()) : $last;
        if ($anchor === null) $last.closest('.field').append($err); else $anchor.after($err);
      }
      if (!$err.attr('id')) $err.attr('id', window.uid('err'));
      $err.text(msg).attr('role', 'alert').addClass('show');
      $inputs.addClass('is-invalid').attr('aria-invalid', 'true')
        .attr('aria-describedby', $err.attr('id')).attr('data-erp-desc', '1');
    });
    showFormLevel($form, unmatched);
  }

  window.formErrors = { clear: clearErrors, show: showErrors, formLevel: showFormLevel };
  window.clearErrors = clearErrors;

  function handleFail(xhr, $form, opts) {
    const res = xhr.responseJSON || {};
    if (xhr.status === 422) {
      showErrors($form, res.errors);
      if (opts && typeof opts.onValidation === 'function') opts.onValidation(res.errors || {}, res);
      window.toast(res.message || 'Please check the form.', 'error');
    } else if (xhr.status === 419) {
      window.toast('Session expired. Reloading…', 'error');
      setTimeout(function () { location.reload(); }, 1200);
    } else {
      window.toast((res && res.message) || 'Something went wrong.', 'error');
    }
  }

  // opts: { method, url, data, form (jQuery), button (jQuery), quiet, onValidation(errors) }
  window.http = function (opts) {
    const $form = opts.form ? $(opts.form) : null;
    const $btn = opts.button ? $(opts.button) : null;
    let btnHtml;
    clearErrors($form);
    if ($btn && $btn.length) {
      btnHtml = $btn.html();
      $btn.prop('disabled', true).html('<span class="spinner"></span>');
    }

    return $.ajax({
      method: (opts.method || 'GET').toUpperCase(),
      url: opts.url,
      data: opts.data,
      dataType: 'json',
    })
      .done(function (res) {
        if (res && res.message && !opts.quiet && (opts.method || 'GET').toUpperCase() !== 'GET') {
          window.toast(res.message, 'success');
        }
      })
      .fail(function (xhr) { handleFail(xhr, $form, opts); })
      .always(function () {
        if ($btn && $btn.length) {
          $btn.prop('disabled', false).html(btnHtml);
        }
      });
  };

  // Multipart submit (file uploads) via FormData. opts: {form, url, method, button}
  window.httpForm = function (opts) {
    var $form = $(opts.form);
    var $btn = opts.button ? $(opts.button) : $form.find('[type=submit]').first();
    var btnHtml;
    clearErrors($form);
    if ($btn.length) { btnHtml = $btn.html(); $btn.prop('disabled', true).html('<span class="spinner"></span>'); }
    var fd = new FormData($form[0]);
    var method = (opts.method || 'POST').toUpperCase();
    if (method !== 'POST') fd.append('_method', method);
    return $.ajax({ method: 'POST', url: opts.url, data: fd, processData: false, contentType: false, dataType: 'json' })
      .done(function (res) { if (res && res.message) window.toast(res.message, 'success'); })
      .fail(function (xhr) { handleFail(xhr, $form, opts); })
      .always(function () { if ($btn.length) $btn.prop('disabled', false).html(btnHtml); });
  };

  // Convenience: submit a <form> via AJAX. Reads method from data-method or the form.
  window.ajaxForm = function ($form, onSuccess) {
    $form = $($form);
    $form.on('submit', function (e) {
      e.preventDefault();
      const $submit = $form.find('[type="submit"]').first();
      http({
        method: $form.attr('data-method') || $form.attr('method') || 'POST',
        url: $form.attr('action'),
        data: $form.serialize(),
        form: $form,
        button: $submit,
      }).done(function (res) {
        if (typeof onSuccess === 'function') onSuccess(res);
        else if (res && res.data && res.data.redirect) window.location = res.data.redirect;
      });
    });
  };
})(jQuery);
