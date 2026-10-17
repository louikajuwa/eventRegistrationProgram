@php($asset = fn ($p) => asset($p).'?v='.(@filemtime(public_path($p)) ?: config('app.version', '1')))
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Register') · {{ config('app.name') }}</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">
    <link rel="stylesheet" href="{{ $asset('assets/css/app.css') }}">
    <style>
        body { background: var(--bg); }
        .pub-wrap { max-width: 620px; margin-inline: auto; padding: 2.5rem 1rem 4rem; }
        @media (max-width:560px){ .pub-wrap { padding: 1.25rem 0.9rem 3rem; } }
        .ticket { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg); overflow: hidden; }
        .ticket-banner { background: linear-gradient(120deg, var(--c-accent-600), var(--c-accent-700));
            color: #fff; padding: 1.75rem 1.75rem 1.5rem; }
        @media (max-width:560px){ .ticket-banner { padding: 1.25rem 1.1rem; } }
        .ticket-kicker { font-size: .7rem; letter-spacing: .14em; text-transform: uppercase; opacity: .85; font-weight: 600; }
        .ticket-title { font-size: 1.6rem; font-weight: 700; line-height: 1.15; margin-top: .35rem; letter-spacing: -.02em; }
        .ticket-meta { display: flex; flex-wrap: wrap; gap: .35rem 1rem; margin-top: .9rem; font-size: .82rem; opacity: .95; }
        .ticket-perf { height: 0; border-top: 2px dashed var(--border); position: relative; }
        .ticket-perf::before, .ticket-perf::after { content: ""; position: absolute; top: -11px; width: 20px; height: 20px;
            border-radius: 50%; background: var(--bg); }
        .ticket-perf::before { left: -11px; } .ticket-perf::after { right: -11px; }
        .ticket-body { padding: 1.6rem 1.75rem 1.9rem; }
        @media (max-width:560px){ .ticket-body { padding: 1.2rem 1.1rem 1.5rem; } }
        .q-radio { display: flex; align-items: center; gap: .6rem; border: 1px solid var(--c-neutral-300);
            border-radius: var(--radius-sm); padding: .6rem .75rem; cursor: pointer; font-size: .85rem; }
        .q-radio:hover { border-color: var(--c-accent-500); }
        .q-radio input { accent-color: var(--c-accent-600); }
        .powered { text-align: center; font-size: .78rem; color: var(--text-muted); margin-top: 1.1rem; }
    </style>
    <script>
        (function () {
            var t = localStorage.getItem('erp-theme');
            if (t) document.documentElement.setAttribute('data-theme', t);
            if (localStorage.getItem('erp-mono') === '1') document.documentElement.setAttribute('data-mono', '1');
        })();
    </script>
</head>
<body class="h-full">
    <div class="pub-wrap">
        @yield('content')
        <p class="powered">Powered by <strong>{{ config('app.name') }}</strong></p>
    </div>

    <div id="toast-root"></div>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="{{ $asset('assets/js/core/toast.js') }}"></script>
    <script src="{{ $asset('assets/js/core/http.js') }}"></script>
    @stack('scripts')
</body>
</html>
