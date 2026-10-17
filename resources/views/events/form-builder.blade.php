@extends('layouts.app')
@section('title', 'Edit form')
@section('page-title', 'Form builder')

@section('content')
<x-page-header title="{{ $event->title }} — registration form"
    subtitle="Build the questionnaire people fill out at your public link.">
    <a class="btn btn-secondary" target="_blank" href="{{ route('public.events.show', $event->slug) }}">Preview</a>
    <a class="btn btn-secondary" href="{{ route('events.index') }}">Back</a>
    <button class="btn btn-primary" id="btn-save">Save form</button>
</x-page-header>

<div class="grid gap-4" style="grid-template-columns:1fr 260px">
    <div>
        <div id="q-list" class="flex flex-col gap-3"></div>
        <div id="q-empty" class="card hidden"><x-empty-state title="No questions yet" message="Add a field from the panel on the right." /></div>
    </div>
    <div class="card" style="align-self:start">
        <div class="card-header"><span class="card-title">Add a field</span></div>
        <div class="card-pad flex flex-col gap-2">
            @foreach (\App\Enums\QuestionType::cases() as $t)
                <button class="btn btn-secondary btn-block add-field" data-type="{{ $t->value }}" style="justify-content:flex-start">{{ $t->label() }}</button>
            @endforeach
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    const loadUrl = "{{ route('events.questions.index', $event->id) }}";
    const saveUrl = "{{ route('events.questions.sync', $event->id) }}";
    const CHOICE = ['select', 'radio', 'checkbox'];
    const TYPES = @json(collect(\App\Enums\QuestionType::cases())->mapWithKeys(fn($t)=>[$t->value=>$t->label()]));
    const esc = (s) => $('<div>').text(s == null ? '' : s).html();
    const $list = $('#q-list');

    function optionRow(val) {
        return `<div class="opt-row flex items-center gap-2 mb-1">
            <input class="input opt-input" value="${esc(val || '')}" placeholder="Option">
            <button type="button" class="btn btn-ghost btn-sm opt-del" style="color:var(--c-danger)">✕</button></div>`;
    }
    function typeOptions(sel) {
        return Object.keys(TYPES).map(v => `<option value="${v}" ${v === sel ? 'selected' : ''}>${esc(TYPES[v])}</option>`).join('');
    }
    function card(q) {
        q = q || {};
        const hasOpts = CHOICE.includes(q.type);
        const opts = (q.options || []);
        return $(`<div class="card q-card" data-qid="${q.id || ''}">
            <div class="card-pad flex items-center gap-2 border-b" style="border-color:var(--border);background:var(--c-neutral-50)">
                <span class="drag" style="cursor:grab;color:var(--text-muted)">⣿</span>
                <select class="select q-type" style="max-width:170px">${typeOptions(q.type || 'text')}</select>
                <label class="inline-flex items-center gap-2 text-sm" style="margin-left:auto;color:var(--c-neutral-700)">
                    <input type="checkbox" class="q-req" ${q.is_required ? 'checked' : ''}> Required</label>
                <button type="button" class="btn btn-ghost btn-sm q-up" title="Move up">↑</button>
                <button type="button" class="btn btn-ghost btn-sm q-down" title="Move down">↓</button>
                <button type="button" class="btn btn-ghost btn-sm q-del" style="color:var(--c-danger)">Delete</button>
            </div>
            <div class="card-pad">
                <div class="field" style="margin-bottom:.5rem">
                    <label class="label">Question label</label>
                    <input class="input q-label" value="${esc(q.label || '')}">
                </div>
                <div class="q-options ${hasOpts ? '' : 'hidden'}">
                    <label class="label">Options</label>
                    <div class="opt-list">${(opts.length ? opts : ['']).map(optionRow).join('')}</div>
                    <button type="button" class="btn btn-ghost btn-sm opt-add">+ Add option</button>
                </div>
            </div>
        </div>`);
    }
    function refreshEmpty() { $('#q-empty').toggleClass('hidden', $list.children().length > 0); }

    $.getJSON(loadUrl).done(function (res) {
        (res.data || []).forEach(q => $list.append(card(q)));
        refreshEmpty();
    });

    $('.add-field').on('click', function () { $list.append(card({ type: $(this).data('type') })); refreshEmpty(); });
    $list.on('change', '.q-type', function () {
        $(this).closest('.q-card').find('.q-options').toggleClass('hidden', !CHOICE.includes(this.value));
    });
    $list.on('click', '.opt-add', function () { $(this).siblings('.opt-list').append(optionRow('')); });
    $list.on('click', '.opt-del', function () { $(this).closest('.opt-row').remove(); });
    $list.on('click', '.q-del', function () { $(this).closest('.q-card').remove(); refreshEmpty(); });
    $list.on('click', '.q-up', function () { const c = $(this).closest('.q-card'); c.prev('.q-card').before(c); });
    $list.on('click', '.q-down', function () { const c = $(this).closest('.q-card'); c.next('.q-card').after(c); });

    $('#btn-save').on('click', function () {
        const questions = [];
        $list.children('.q-card').each(function () {
            const $c = $(this);
            const type = $c.find('.q-type').val();
            const options = CHOICE.includes(type)
                ? $c.find('.opt-input').map((i, el) => $(el).val().trim()).get().filter(v => v)
                : [];
            questions.push({
                id: $c.data('qid') || null,
                label: $c.find('.q-label').val(),
                type: type,
                is_required: $c.find('.q-req').is(':checked') ? 1 : 0,
                options: options,
            });
        });
        window.http({ method: 'PUT', url: saveUrl, data: { questions: questions }, button: this })
            .done(function (res) {
                $list.empty();
                (res.data || []).forEach(q => $list.append(card(q)));
                refreshEmpty();
            });
    });
});
</script>
@endpush
