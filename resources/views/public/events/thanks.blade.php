@extends('layouts.public')
@section('title', 'You are registered')

@section('content')
<div class="ticket">
    <div class="ticket-body" style="text-align:center;padding-top:2.25rem">
        <div style="width:56px;height:56px;border-radius:50%;background:var(--c-success-bg);color:var(--c-success);
                    display:grid;place-items:center;font-size:1.6rem;margin:0 auto 1rem">✓</div>
        <h1 class="text-xl font-bold">You're registered!</h1>
        <p class="text-sm mt-2" style="color:var(--text-muted)">
            A confirmation email is on its way to <strong>{{ $registration->email }}</strong>
            with everything you need for {{ $event->title }}.
        </p>
        <div style="margin-top:1.25rem;font-family:ui-monospace,monospace;font-size:.85rem;background:var(--c-neutral-50);
                    border:1px dashed var(--c-neutral-300);border-radius:var(--radius-sm);padding:.7rem">
            Reference · {{ $registration->reference }}
        </div>
        @if ($event->starts_at || $event->location)
            <div class="text-sm mt-4" style="color:var(--text-muted)">
                @if ($event->starts_at)📅 {{ $event->starts_at->format('D, d M Y · g:i A') }}<br>@endif
                @if ($event->location)📍 {{ $event->location }}@endif
            </div>
        @endif
    </div>
</div>
@endsection
