// Shared helpers loaded before every other script: HTML escaping (safe in text AND
// attribute context), URL sanitising, and one date formatter.
(function () {
  const MAP = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };

  // Escape for HTML text or quoted attribute values.
  window.esc = function (s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return MAP[c]; });
  };

  // Returns the URL only if it is relative ("/path", not "//host") or same-origin http(s).
  window.safeUrl = function (u) {
    if (!u || typeof u !== 'string') return '#';
    u = u.trim();
    if (u.charAt(0) === '/' && u.charAt(1) !== '/' && u.charAt(1) !== '\\') return u;
    try {
      const parsed = new URL(u, window.location.origin);
      if ((parsed.protocol === 'http:' || parsed.protocol === 'https:') && parsed.origin === window.location.origin) return u;
    } catch (e) { /* fall through */ }
    return '#';
  };

  // Consistent client-side date display. opts overrides Intl options.
  window.formatDate = function (iso, opts) {
    if (!iso) return '—';
    const d = new Date(iso);
    if (isNaN(d.getTime())) return '—';
    return new Intl.DateTimeFormat(undefined, opts || { dateStyle: 'medium', timeStyle: 'short' }).format(d);
  };

  // Visually-hidden live-region friendly id helper.
  window.uid = (function () { let n = 0; return function (p) { return (p || 'id') + '-' + (++n); }; })();
})();
