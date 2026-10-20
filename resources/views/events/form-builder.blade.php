@extends('layouts.app')
@section('title', 'Edit form')
@section('page-title', 'Form builder')

@section('content')
<x-page-header title="{{ $event->title }} — registration form"
    subtitle="Build the questionnaire people fill out at your public link.">
    <a class="btn btn-secondary" target="_blank" rel="noopener" href="{{ route('public.events.show', $event->slug) }}">Preview</a>
    <a class="btn btn-secondary" href="{{ route('events.index') }}">Back</a>
    <span class="save-hint" id="save-hint" role="status"></span>
    <button type="button" class="btn btn-primary" id="btn-save">Save form</button>
</x-page-header>

<div id="q-errors" class="form-errors hidden" role="alert" tabindex="-1"></div>

<div class="builder-grid">
    <div>
        <div id="q-list" class="flex flex-col gap-3"></div>
        <div id="q-empty" class="card hidden"><x-empty-state title="No questions yet" message="Add a field from the panel on the right." /></div>
    </div>
    <div class="card" style="align-self:start">
        <div class="card-header"><span class="card-title">Add a field</span></div>
        <div class="card-pad flex flex-col gap-2">
            @foreach (\App\Enums\QuestionType::cases() as $t)
                <button type="button" class="btn btn-secondary btn-block add-field" data-type="{{ $t->value }}" style="justify-content:flex-start">{{ $t->label() }}</button>
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
    const $list = $('#q-list');
    let dirty = false;
    const setDirty = (v) => { dirty = v; $('#save-hint').text(v ? 'Unsaved changes' : ''); };

    function optionRow(val) {
        return `<div class="opt-row flex items-center gap-2 mb-1">
            <input class="input opt-input" value="${esc(val || '')}" placeholder="Option" aria-label="Option">
            <button type="button" class="btn btn-ghost btn-sm opt-del" style="color:var(--c-danger)" aria-label="Remove option" title="Remove option"><span aria-hidden="true">✕</span></button></div>`;
    }
    function typeOptions(sel) {
        return Object.keys(TYPES).map(v => `<option value="${esc(v)}" ${v === sel ? 'selected' : ''}>${esc(TYPES[v])}</option>`).join('');
    }
    function card(q) {
        q = q || {};
        const hasOpts = CHOICE.includes(q.type);
        const opts = (q.options || []);
        const n = uid('q');
        return $(`<div class="card q-card" data-qid="${esc(q.id || '')}">
            <div class="card-pad q-head border-b" style="border-color:var(--border);background:var(--c-neutral-50)">
                <span class="drag" aria-hidden="true" style="cursor:grab;color:var(--text-muted)">⣿</span>
                <select class="select q-type" style="max-width:170px" id="${n}-type" aria-label="Question type">${typeOptions(q.type || 'text')}</select>
                <label class="inline-flex items-center gap-2 text-sm" style="margin-left:auto;color:var(--c-neutral-700)">
                    <input type="checkbox" class="q-req" ${q.is_required ? 'checked' : ''}> Required</label>
                <button type="button" class="btn btn-ghost btn-sm q-up" title="Move up" aria-label="Move question up"><span aria-hidden="true">↑</span></button>
                <button type="button" class="btn btn-ghost btn-sm q-down" title="Move down" aria-label="Move question down"><span aria-hidden="true">↓</span></button>
                <button type="button" class="btn btn-ghost btn-sm q-del" style="color:var(--c-danger)">Delete</button>
            </div>
            <div class="card-pad">
                <div class="field" style="margin-bottom:.5rem">
                    <label class="label" for="${n}-label">Question label</label>
                    <input class="input q-label" id="${n}-label" value="${esc(q.label || '')}">
                </div>
                <div class="q-options ${hasOpts ? '' : 'hidden'}">
                    <div class="label" id="${n}-opts">Options</div>
                    <div class="opt-list" role="group" aria-labelledby="${n}-opts">${(opts.length ? opts : ['']).map(optionRow).join('')}</div>
                    <button type="button" class="btn btn-ghost btn-sm opt-add">+ Add option</button>
                </div>
            </div>
        </div>`);
    }
    function refreshEmpty() { $('#q-empty').toggleClass('hidden', $list.children().length > 0); }
    function render(items) { $list.empty(); (items || []).forEach(q => $list.append(card(q))); refreshEmpty(); }

    $.getJSON(loadUrl).done(function (res) { render(res.data); setDirty(false); });

    $('.add-field').on('click', function () { $list.append(card({ type: $(this).data('type') })); refreshEmpty(); setDirty(true); });
    $list.on('input change', 'input, select', function () { setDirty(true); });
    $list.on('change', '.q-type', function () {
        $(this).closest('.q-card').find('.q-options').toggleClass('hidden', !CHOICE.includes(this.value));
    });
    $list.on('click', '.opt-add', function () { $(this).siblings('.opt-list').append(optionRow('')); setDirty(true); });
    $list.on('click', '.opt-del', function () { $(this).closest('.opt-row').remove(); setDirty(true); });
    $list.on('click', '.q-del', function () {
        const $c = $(this).closest('.q-card');
        const label = $c.find('.q-label').val().trim();
        window.confirmDialog({
            title: 'Delete question?',
            message: label ? '"' + label + '" and any answers already collected for it will be removed when you save.' : 'This question will be removed when you save.',
            confirmText: 'Delete',
        }).then(function (ok) {
            if (!ok) return;
            $c.remove(); refreshEmpty(); setDirty(true); $('#btn-save').trigger('focus');
        });
    });
    $list.on('click', '.q-up', function () { const c = $(this).closest('.q-card'); c.prev('.q-card').before(c); setDirty(true); $(this).trigger('focus'); });
    $list.on('click', '.q-down', function () { const c = $(this).closest('.q-card'); c.next('.q-card').after(c); setDirty(true); $(this).trigger('focus'); });

    window.addEventListener('beforeunload', function (e) {
        if (!dirty) return;
        e.preventDefault(); e.returnValue = '';
    });

    // questions.3.label -> "Question 4: label ..." summary near the save button.
    function showSummary(errors) {
        const items = [];
        $list.find('.is-invalid').removeClass('is-invalid').removeAttr('aria-invalid');
        Object.keys(errors || {}).forEach(function (key) {
            const msg = Array.isArray(errors[key]) ? errors[key][0] : errors[key];
            const m = /^questions\.(\d+)(?:\.(\w+))?/.exec(key);
            if (!m) { items.push(msg); return; }
            const idx = parseInt(m[1], 10), field = m[2] || '';
            const clean = String(msg)
                .replace(/questions\.\d+\.(\w+)/g, (x, f) => f.replace(/_/g, ' '))
                .replace(/questions\.\d+/g, 'question');
            items.push('Question ' + (idx + 1) + (field ? ': ' + field.replace(/_/g, ' ') : '') + ' — ' + clean);
            const $c = $list.children('.q-card').eq(idx);
            const sel = { label: '.q-label', type: '.q-type', options: '.opt-input' }[field];
            if (sel) $c.find(sel).first().addClass('is-invalid').attr('aria-invalid', 'true');
        });
        const $box = $('#q-errors');
        if (!items.length) { $box.addClass('hidden').empty(); return; }
        $box.html('<ul>' + items.map(i => '<li>' + esc(i) + '</li>').join('') + '</ul>').removeClass('hidden').trigger('focus');
    }

    $('#btn-save').on('click', function () {
        showSummary({});
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
        window.http({ method: 'PUT', url: saveUrl, data: { questions: questions }, button: this, onValidation: showSummary })
            .done(function (res) { render(res.data); setDirty(false); });
    });
});
</script>
@endpush
