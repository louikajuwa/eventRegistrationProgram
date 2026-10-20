@extends('layouts.app')
@section('title', 'Notifications')
@section('page-title', 'Notifications')
@section('main-mode', 'fill')

@section('content')
<x-page-header title="Notifications" subtitle="Everything that's happened across your organization.">
    <button class="btn btn-secondary" id="btn-read-all">Mark all read</button>
</x-page-header>

<div class="card fill">
    <div id="dt" class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="col-grow">Notification</th>
                    <th>When</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody data-dt-body></tbody>
        </table>
        <div data-dt-empty class="hidden"><x-empty-state title="No notifications" message="You're all caught up." /></div>
        <div class="card-pad" data-dt-pagination></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {

    const dt = new DataTable({
        url: "{{ route('notifications.all') }}", mount: '#dt', perPage: 20,
        row: (n) => `<tr style="${n.read ? '' : 'background:var(--c-accent-50)'}">
            <td class="col-grow" style="${n.read ? '' : 'background:var(--c-accent-50)'}">
                <a href="${esc(safeUrl(n.url))}" class="font-medium" style="color:var(--text)">${esc(n.title || 'Notification')}</a>
                <div class="text-xs" style="color:var(--text-muted)">${esc(n.message || '')}</div>
            </td>
            <td class="text-xs" style="color:var(--text-muted)">${esc(n.at || '')}</td>
            <td class="text-center"><span class="badge badge-${n.read ? 'neutral' : 'info'}">${n.read ? 'Read' : 'Unread'}</span></td>
        </tr>`,
    });
    dt.reload();

    $('#btn-read-all').on('click', function () {
        window.http({ method: 'POST', url: "{{ route('notifications.read') }}", button: this })
            .done(() => { dt.reload(); if (window.loadNotifs) window.loadNotifs(); });
    });
});
</script>
@endpush
