@extends('layouts.app')
@section('title', 'Events')
@section('page-title', 'Events')
@section('main-mode', 'fill')

@section('content')
<x-page-header title="Events" subtitle="Create an event, build its registration form, and share the public link.">
    @can('events.create')
        <button class="btn btn-primary" id="btn-new">+ New event</button>
    @endcan
</x-page-header>

<div class="card fill">
    <div class="card-pad flex flex-wrap items-center gap-3 border-b" style="border-color:var(--border)">
        <input id="dt-search" class="input" style="max-width:280px" placeholder="Search events…" aria-label="Search events" type="search">
        <select id="dt-status" class="select" style="max-width:160px" aria-label="Filter by status">
            <option value="">All statuses</option>
            <option value="draft">Draft</option>
            <option value="published">Published</option>
            <option value="closed">Closed</option>
        </select>
    </div>

    <div id="dt" class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="col-grow" data-dt-sort="title">Event</th>
                    <th data-dt-sort="status">Status</th>
                    <th data-dt-sort="starts_at">Starts</th>
                    <th class="num">Registered</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody data-dt-body></tbody>
        </table>
        <div data-dt-empty class="hidden">
            <x-empty-state title="No events yet" message="Create your first event to start collecting registrations." />
        </div>
        <div class="card-pad" data-dt-pagination></div>
    </div>
</div>

<x-modal id="event-modal" title="Event">
    <form id="event-form">
        <div class="modal-body">
            <div class="field">
                <label class="label" for="f-title">Title</label>
                <input class="input" id="f-title" name="title">
            </div>
            <div class="form-grid">
                <div class="field">
                    <label class="label" for="f-status">Status</label>
                    <select class="select" id="f-status" name="status">
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
                <div class="field">
                    <label class="label" for="f-capacity">Capacity</label>
                    <input class="input" id="f-capacity" name="capacity" type="number" min="1" placeholder="Unlimited">
                </div>
            </div>
            <div class="form-grid">
                <div class="field">
                    <label class="label" for="f-starts">Starts at</label>
                    <input class="input" id="f-starts" name="starts_at" type="datetime-local">
                </div>
                <div class="field">
                    <label class="label" for="f-ends">Ends at</label>
                    <input class="input" id="f-ends" name="ends_at" type="datetime-local">
                </div>
            </div>
            <div class="field">
                <label class="label" for="f-location">Location</label>
                <input class="input" id="f-location" name="location" placeholder="Venue or online link">
            </div>
            <div class="field">
                <label class="label" for="f-description">Description</label>
                <textarea class="textarea" id="f-description" name="description"></textarea>
            </div>
            <p class="help-text">The confirmation email for this event is edited on its own page — use the <strong>Email</strong> button in the events list.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
    </form>
</x-modal>
@endsection

@push('scripts')
<script>
$(function () {
    const listUrl = "{{ route('events.index') }}";
    const base = {!! json_encode(route("events.index", [], false)) !!};
    const publicBase = window.location.origin + window.APP.routes.publicEvent.replace("__SLUG__", "");
    const badge = { published: 'success', closed: 'warning', draft: 'neutral' };
    const dtLocal = (iso) => iso ? String(iso).substring(0, 16) : '';
    const fmt = (iso) => window.formatDate(iso);

    const dt = new DataTable({
        url: listUrl, mount: '#dt', defaultSort: 'id', defaultDir: 'desc',
        searchInput: '#dt-search', filters: { '#dt-status': 'status' },
        row: (e) => `<tr>
            <td class="col-grow"><div class="font-medium">${esc(e.title)}</div>
                <div class="text-xs" style="color:var(--text-muted)">/e/${esc(e.slug)}</div></td>
            <td><span class="badge badge-${badge[e.status] || 'neutral'}">${esc(e.status)}</span></td>
            <td>${fmt(e.starts_at)}</td>
            <td class="num">${e.registrations_count ?? 0}${e.attended_count != null ? ` · ${e.attended_count} in` : ''}</td>
            <td class="text-right"><div class="row-actions">
                <a class="btn btn-ghost btn-sm" href="${base}/${e.id}/dashboard">Dashboard</a>
                @can('events.update')<a class="btn btn-ghost btn-sm" href="${base}/${e.id}/questions">Form</a>@endcan
                @can('events.update')<a class="btn btn-ghost btn-sm" href="${base}/${e.id}/email">Email</a>@endcan
                <button class="btn btn-ghost btn-sm btn-link" data-id="${e.id}" title="Copy public link" aria-label="Copy public link for ${esc(e.title)}">Link</button>
                @can('events.update')<button class="btn btn-ghost btn-sm btn-edit" data-id="${e.id}" aria-label="Edit ${esc(e.title)}">Edit</button>@endcan
                @can('events.delete')<button class="btn btn-ghost btn-sm btn-del" data-id="${e.id}" aria-label="Delete ${esc(e.title)}" style="color:var(--c-danger)">Delete</button>@endcan
            </div></td>
        </tr>`,
    });
    dt.reload();

    function fill(e) {
        $('#f-title').val(e.title || '');
        $('#f-status').val(e.status || 'draft');
        $('#f-capacity').val(e.capacity || '');
        $('#f-starts').val(dtLocal(e.starts_at));
        $('#f-ends').val(dtLocal(e.ends_at));
        $('#f-location').val(e.location || '');
        $('#f-description').val(e.description || '');
    }

    $('#btn-new').on('click', function () {
        $('#event-form')[0].reset();
        $('#event-form').data('url', "{{ route('events.store') }}").data('method', 'POST');
        Modal.open('event-modal');
    });
    $('#dt').on('click', '.btn-edit', function () {
        const e = dt.getItem($(this).data('id')); fill(e);
        $('#event-form').data('url', base + '/' + e.id).data('method', 'PUT');
        Modal.open('event-modal');
    });
    $('#dt').on('click', '.btn-link', function () {
        const e = dt.getItem($(this).data('id'));
        const url = publicBase + e.slug;
        const ok = () => window.toast('Public link copied: ' + url, 'success');
        const fallback = function () {
            const $t = $('<input readonly style="position:fixed;opacity:0;top:0;left:0">').val(url).appendTo('body');
            $t[0].focus(); $t[0].select();
            let done = false;
            try { done = document.execCommand('copy'); } catch (err) { done = false; }
            $t.remove();
            if (done) ok(); else window.toast('Could not copy automatically. Link: ' + url, 'info');
        };
        if (navigator.clipboard && navigator.clipboard.writeText) navigator.clipboard.writeText(url).then(ok, fallback);
        else fallback();
    });
    $('#dt').on('click', '.btn-del', function () {
        const id = $(this).data('id'); const btn = this;
        window.confirmDialog({ title: 'Delete event?', message: 'This removes the event, its questions and all registrations.', confirmText: 'Delete' })
            .then(function (ok) { if (!ok) return;
                window.http({ method: 'DELETE', url: base + '/' + id, button: btn }).done(() => dt.reload());
            });
    });

    $('#event-form').on('submit', function (e) {
        e.preventDefault();
        const $form = $(this);
        window.http({ method: $form.data('method'), url: $form.data('url'),
            data: $form.serialize(), form: $form, button: $form.find('[type=submit]') })
            .done(function () { Modal.close('event-modal'); dt.reload(); });
    });
});
</script>
@endpush
