// Confirmation-email editor helper: clickable {placeholder} chips that insert at the
// caret, plus a live WYSIWYG preview that mirrors the branded ticket email actually
// sent (resources/views/emails/registration-confirmed.blade.php). Substitution mirrors
// App\Support\EmailTemplateRenderer (plain {key} replace over a fixed whitelist).
(function ($) {
  const esc = (s) => $('<div>').text(s == null ? '' : s).html();
  const nl2br = (s) => esc(s).replace(/\r\n|\r|\n/g, '<br>');

  function render(tpl, vars) {
    let out = String(tpl == null ? '' : tpl);
    Object.keys(vars).forEach(function (k) {
      out = out.split('{' + k + '}').join(vars[k] == null ? '' : String(vars[k]));
    });
    return out;
  }

  function insertAtCaret(el, text) {
    el.focus();
    const start = el.selectionStart != null ? el.selectionStart : el.value.length;
    const end = el.selectionEnd != null ? el.selectionEnd : el.value.length;
    el.value = el.value.slice(0, start) + text + el.value.slice(end);
    const pos = start + text.length;
    el.selectionStart = el.selectionEnd = pos;
    el.dispatchEvent(new Event('input', { bubbles: true }));
  }

  // opts: { subject, body, chips, preview, sample }
  function init(opts) {
    const $subject = $(opts.subject);
    const $body = $(opts.body);
    const $chips = $(opts.chips);
    const $preview = $(opts.preview);
    const sample = opts.sample || {};

    // Track the field the caret was last in (default: body).
    let last = $body.get(0) || $subject.get(0);
    $subject.add($body).on('focus', function () { last = this; });

    $chips.on('click', '.chip', function () {
      const token = $(this).data('ph');
      insertAtCaret(last || $body.get(0), '{' + token + '}');
    });

    function paint() {
      const subject = render($subject.val() || 'You are registered for {event_title}', sample);
      const rawBody = $body.val() || '';
      const body = rawBody.trim() === ''
        ? '<span style="color:var(--text-muted)">Your message will appear here…</span>'
        : nl2br(render(rawBody, sample));

      $preview.html(
        '<div class="to"><b>To:</b> ' + esc(sample.email) + '<br><b>Subject:</b> ' + esc(subject) + '</div>' +
        '<div class="email-ticket">' +
          '<div class="banner">' +
            '<div class="kicker">You\'re registered</div>' +
            '<div class="title">' + esc(sample.event_title) + '</div>' +
            '<div class="meta">' +
              (sample.event_date ? '<span>&#128197; ' + esc(sample.event_date) + '</span>' : '') +
              (sample.event_location ? '<span>&#128205; ' + esc(sample.event_location) + '</span>' : '') +
            '</div>' +
          '</div>' +
          '<div class="perf"></div>' +
          '<div class="body">' + body + '</div>' +
          '<div class="refbox"><div class="lab">Your reference</div><div class="ref">' + esc(sample.reference) + '</div></div>' +
          '<div class="foot"><div class="inner">This confirmation was sent to ' + esc(sample.email) + '.<br>Powered by <b>Event Registration</b></div></div>' +
        '</div>'
      );
    }

    $subject.add($body).on('input', paint);
    paint();
    return { paint: paint };
  }

  window.EmailEditor = { init: init };
})(jQuery);
