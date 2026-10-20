@php($asset = fn ($p) => asset($p).'?v='.(@filemtime(public_path($p)) ?: config('app.version', '1')))
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sign in') · {{ config('app.name') }}</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">
    <link rel="stylesheet" href="{{ $asset('assets/css/app.css') }}">
    <script>
        (function () {
            var t = localStorage.getItem('erp-theme');
            if (t) document.documentElement.setAttribute('data-theme', t);
            if (localStorage.getItem('erp-mono') === '1') document.documentElement.setAttribute('data-mono', '1');
        })();
    </script>
</head>
<body class="h-full">
    <a href="#main" class="skip-link">Skip to content</a>
    <div class="min-h-full flex flex-col items-center justify-center px-4 py-12">
        <main id="main" class="w-full max-w-md" tabindex="-1">
            <div class="text-center mb-6">
                <div class="inline-flex items-center gap-2 text-[color:var(--c-neutral-900)] font-bold text-xl">
                    <span aria-hidden="true" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-white"
                          style="background: var(--btn-primary)">🎟</span>
                    {{ config('app.name') }}
                </div>
            </div>
            <div class="card card-pad">
                @yield('content')
            </div>
            <p class="text-center text-xs mt-6" style="color: var(--text-muted)">
                &copy; {{ date('Y') }} {{ config('app.name') }}
            </p>
        </main>
    </div>

    <div id="toast-root" role="status" aria-live="polite"></div>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="{{ $asset('assets/js/core/util.js') }}"></script>
    <script src="{{ $asset('assets/js/core/toast.js') }}"></script>
    <script src="{{ $asset('assets/js/core/http.js') }}"></script>
    @stack('scripts')
</body>
</html>
