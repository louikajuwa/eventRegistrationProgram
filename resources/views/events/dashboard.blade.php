@extends('layouts.app')
@section('title', 'Event dashboard')
@section('page-title', 'Event dashboard')

@section('content')
<x-page-header title="{{ $event->title }}" subtitle="Live registration activity.">
    <span class="badge badge-{{ $event->status->value === 'published' ? 'success' : ($event->status->value === 'closed' ? 'warning' : 'neutral') }}">{{ $event->status->label() }}</span>
    <a class="btn btn-secondary" target="_blank" href="{{ route('public.events.show', $event->slug) }}">Public page</a>
    <a class="btn btn-secondary" href="{{ route('registrations.index', ['event_id' => $event->id]) }}">Registrations</a>
</x-page-header>

<div class="kpi-grid-3 mb-6">
    <div class="card stat">
        <div class="stat-label">Registered <span id="live-dot" class="badge badge-success" style="margin-left:.35rem">● live</span></div>
        <div class="stat-value" id="reg-count">{{ $registered }}</div>
    </div>
    <div class="card stat"><div class="stat-label">Attended</div><div class="stat-value">{{ $attended }}</div></div>
    <div class="card stat"><div class="stat-label">Capacity</div><div class="stat-value">{{ $event->capacity ?? '∞' }}</div></div>
</div>

<div class="card">
    <div class="card-header"><span class="card-title">Recent registrations</span></div>
    <div class="table-scroll">
        <table class="data-table">
            <thead><tr><th class="col-grow">Name</th><th>Email</th><th>Reference</th><th>When</th></tr></thead>
            <tbody id="feed">
                @forelse ($recent as $r)
                    <tr><td class="col-grow font-medium">{{ $r['name'] }}</td><td>{{ $r['email'] }}</td>
                        <td class="text-xs" style="font-family:ui-monospace,monospace">{{ $r['reference'] }}</td><td>{{ $r['at'] }}</td></tr>
                @empty
                    <tr id="feed-empty"><td colspan="4" class="p-6 text-center" style="color:var(--text-muted)">No registrations yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    const EVENT_ID = {{ $event->id }};
    if (!window.Realtime) return;

    // Reuse the single shared socket opened by realtime.js (no duplicate connection).
    window.Realtime.onSocket(function (socket) {
        socket.on('registration.created', function (p) {
            if (!p || p.event_id !== EVENT_ID) return;
            if (typeof p.registered === 'number') $('#reg-count').text(p.registered);
            $('#feed-empty').remove();
            $('#feed').prepend(`<tr style="background:var(--c-accent-50)"><td class="col-grow font-medium">${esc(p.name)}</td>
                <td>—</td><td class="text-xs" style="font-family:ui-monospace,monospace">${esc(p.reference)}</td><td>just now</td></tr>`);
            window.toast && window.toast('New registration: ' + (p.name || ''), 'success');
        });
    });
});
</script>
@endpush
