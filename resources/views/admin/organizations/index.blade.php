@extends('layouts.app')
@section('title', 'Organizations')
@section('page-title', 'Organizations')
@section('main-mode', 'fill')

@section('content')
<x-page-header title="Organizations" subtitle="Every organization on the platform. Deactivate to block an org's users from signing in." />

<div class="card fill">
    <div class="card-pad flex flex-wrap items-center gap-3 border-b" style="border-color:var(--border)">
        <input id="dt-search" class="input" style="max-width:320px" placeholder="Search by name, email or slug…" aria-label="Search organizations" type="search">
    </div>

    <div id="dt" class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="col-grow" data-dt-sort="name">Organization</th>
                    <th>Status</th>
                    <th class="num">Admins</th>
                    <th class="num">Events</th>
                    <th class="num">Registrations</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody data-dt-body></tbody>
        </table>
        <div data-dt-empty class="hidden">
            <x-empty-state title="No organizations found" message="Try a different search." />
        </div>
        <div class="card-pad" data-dt-pagination></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    const base = window.APP.routes.organizations;

    const dt = new DataTable({
        url: "{{ route('admin.organizations.index') }}", mount: '#dt', defaultSort: 'name', defaultDir: 'asc',
        searchInput: '#dt-search',
        row: (o) => `<tr style="${o.is_active ? '' : 'opacity:.55'}">
            <td class="col-grow"><div class="font-medium">${esc(o.name)}</div>
                <div class="text-xs" style="color:var(--text-muted)">${esc(o.email || '/' + o.slug)}</div></td>
            <td><span class="badge badge-${o.is_active ? 'success' : 'neutral'}">${o.is_active ? 'Active' : 'Inactive'}</span></td>
            <td class="num">${o.users_count ?? 0}</td>
            <td class="num">${o.events_count ?? 0}</td>
            <td class="num">${o.registrations_count ?? 0}</td>
            <td class="text-right"><div class="row-actions">
                <button class="btn btn-ghost btn-sm btn-toggle" data-id="${o.id}" data-active="${o.is_active ? 1 : 0}"
                        style="color:var(${o.is_active ? '--c-danger' : '--c-success'})">${o.is_active ? 'Deactivate' : 'Activate'}</button>
                <a class="btn btn-ghost btn-sm" href="${base}/${o.id}">View</a></div>
            </td>
        </tr>`,
    });
    dt.reload();

    $('#dt').on('click', '.btn-toggle', function () {
        const btn = this;
        const id = $(btn).data('id');
        const active = $(btn).data('active');
        const apply = function () {
            window.http({ method: 'PUT', url: base + '/' + id + '/status', data: { is_active: active ? 0 : 1 }, button: btn })
                .done(() => dt.reload());
        };
        if (active) {
            window.confirmDialog({ title: 'Deactivate organization?', message: 'Its users will be signed out and blocked from logging in until reactivated.', confirmText: 'Deactivate' })
                .then((ok) => { if (ok) apply(); });
        } else {
            apply();
        }
    });
});
</script>
@endpush
