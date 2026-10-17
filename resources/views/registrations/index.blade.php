@extends('layouts.app')
@section('title', 'Registrations')
@section('page-title', 'Registrations')
@section('main-mode', 'fill')

@section('content')
<x-page-header title="Registrations" subtitle="Everyone who registered. Toggle attendance to check people in.">
    <a class="btn btn-secondary" href="{{ route('reports.index') }}">Reports</a>
</x-page-header>

<div class="card fill">
    <div class="card-pad flex flex-wrap items-center gap-3 border-b" style="border-color:var(--border)">
        <input id="dt-search" class="input" style="max-width:280px" placeholder="Search name, email or reference…">
        <select id="dt-event" class="select" style="max-width:220px">
            <option value="">All events</option>
            @foreach ($events as $e)
                <option value="{{ $e->id }}" @selected(request('event_id') == $e->id)>{{ $e->title }}</option>
            @endforeach
        </select>
        <select id="dt-attended" class="select" style="max-width:160px">
            <option value="">All attendance</option>
            <option value="1">Attended</option>
            <option value="0">Not attended</option>
        </select>
        <select id="dt-status" class="select" style="max-width:160px">
            <option value="">All statuses</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>
    </div>

    <div id="dt" class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="col-grow" data-dt-sort="name">Registrant</th>
                    <th>Event</th>
                    <th>Reference</th>
                    <th data-dt-sort="created_at">Registered</th>
                    <th data-dt-sort="status">Status</th>
                    <th class="text-center" data-dt-sort="attended">Attended</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody data-dt-body></tbody>
        </table>
        <div data-dt-empty class="hidden"><x-empty-state title="No registrations" message="Registrations will appear here as people sign up." /></div>
        <div class="card-pad" data-dt-pagination></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    const base = "{{ url('registrations') }}";
    const esc = (s) => $('<div>').text(s == null ? '' : s).html();

    const dt = new DataTable({
        url: "{{ route('registrations.index') }}", mount: '#dt', defaultSort: 'id', defaultDir: 'desc',
        searchInput: '#dt-search', filters: { '#dt-event': 'event_id', '#dt-attended': 'attended', '#dt-status': 'status' },
        row: (r) => `<tr style="${r.status === 'inactive' ? 'opacity:.55' : ''}">
            <td class="col-grow"><div class="font-medium">${esc(r.name)}</div>
                <div class="text-xs" style="color:var(--text-muted)">${esc(r.email)}</div></td>
            <td>${esc(r.event ? r.event.title : '—')}</td>
            <td class="text-xs" style="font-family:ui-monospace,monospace">${esc(r.reference)}</td>
            <td>${r.created_at ? new Date(r.created_at).toLocaleString() : '—'}</td>
            <td>
                <select class="select status-sel" data-id="${r.id}" style="height:30px;padding:0 .5rem;font-size:.75rem;max-width:120px">
                    <option value="active" ${r.status === 'active' ? 'selected' : ''}>Active</option>
                    <option value="inactive" ${r.status === 'inactive' ? 'selected' : ''}>Inactive</option>
                </select>
            </td>
            <td class="text-center"><input type="checkbox" class="att-toggle" data-id="${r.id}" ${r.attended ? 'checked' : ''} style="width:18px;height:18px;accent-color:var(--c-accent-600);cursor:pointer"></td>
            <td class="text-right whitespace-nowrap">
                @can('registrations.delete')<button class="btn btn-ghost btn-sm btn-del" data-id="${r.id}" style="color:var(--c-danger)">Delete</button>@endcan
            </td>
        </tr>`,
    });

    // Preselect event filter from query string, then load.
    const params = new URLSearchParams(window.location.search);
    if (params.get('event_id')) { $('#dt-event').val(params.get('event_id')); dt.state.filters.event_id = params.get('event_id'); }
    dt.reload();

    $('#dt').on('change', '.att-toggle', function () {
        const id = $(this).data('id'); const attended = this.checked ? 1 : 0;
        window.http({ method: 'PUT', url: base + '/' + id + '/attendance', data: { attended: attended }, quiet: false });
    });

    $('#dt').on('change', '.status-sel', function () {
        const id = $(this).data('id');
        window.http({ method: 'PUT', url: base + '/' + id + '/status', data: { status: this.value } })
            .done(() => dt.reload());
    });

    $('#dt').on('click', '.btn-del', function () {
        const id = $(this).data('id'); const btn = this;
        window.confirmDialog({ title: 'Delete registration?', message: 'This permanently removes the registrant and their answers. This cannot be undone.', confirmText: 'Delete' })
            .then(function (ok) {
                if (!ok) return;
                window.http({ method: 'DELETE', url: base + '/' + id, button: btn }).done(() => dt.reload());
            });
    });
});
</script>
@endpush
