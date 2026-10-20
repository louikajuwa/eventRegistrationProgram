// Connects to the Node Socket.IO bridge using a short-lived token from Laravel,
// joins the authorized rooms, and refreshes the bell (+ toasts) on live events.
// Degrades silently if the socket server / client is unavailable.
// Pages that need extra socket events call window.Realtime.onSocket(fn) to reuse
// the single shared connection instead of opening their own.
(function ($) {
    const queue = [];
    const Realtime = window.Realtime = {
        socket: null,
        onSocket: function (fn) { if (Realtime.socket) fn(Realtime.socket); else queue.push(fn); },
    };
    if (!window.io) return;

    const tokenUrl = (window.APP && window.APP.routes && window.APP.routes.realtimeToken) || '/realtime/token';

    function connect() {
        $.getJSON(tokenUrl).done(function (res) {
            const d = res && res.data;
            if (!d || !d.token) return;

            const socket = window.io({ path: '/socket.io/', auth: { token: d.token }, transports: ['websocket', 'polling'] });
            Realtime.socket = socket;
            queue.splice(0).forEach(function (fn) { fn(socket); });

            socket.on('notification', function (p) {
                if (window.loadNotifs) window.loadNotifs();
                if (window.toast) window.toast((p && p.title) || 'New notification', 'info');
            });

            // Re-issue the short-lived token before it expires (~5 min).
            setInterval(function () {
                $.getJSON(tokenUrl).done(function (r) {
                    if (r && r.data && r.data.token) socket.auth = { token: r.data.token };
                });
            }, 240000);
        });
    }

    $(connect);
})(jQuery);
