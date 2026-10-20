@extends('layouts.public')
@section('title', $event->title)

@php
    $open = $registrationOpen ?? true;
    $closedReason = null;
    if (! $open) {
        if ($event->status->value === 'closed') {
            $closedReason = ['Registration is closed', 'The organizer is no longer accepting registrations for this event.'];
        } elseif (($event->ends_at ?? $event->starts_at)?->isPast()) {
            $closedReason = ['Registration is closed', 'This event has already ended.'];
        } elseif (method_exists($event, 'isFull') && $event->isFull()) {
            $closedReason = ['Registration is closed', 'This event is full — all spots have been taken.'];
        } else {
            $closedReason = ['Registration is closed', 'This event is not accepting registrations right now.'];
        }
    }
@endphp

@section('content')
<div class="ticket">
    <div class="ticket-banner">
        <div class="ticket-kicker">You're invited</div>
        <h1 class="ticket-title">{{ $event->title }}</h1>
        <div class="ticket-meta">
            @if ($event->starts_at)<span><span aria-hidden="true">📅</span> {{ $event->starts_at->format('D, d M Y · g:i A') }}</span>@endif
            @if ($event->location)<span><span aria-hidden="true">📍</span> {{ $event->location }}</span>@endif
        </div>
    </div>
    <div class="ticket-perf"></div>
    <div class="ticket-body">
        @if ($event->description)
            <p class="text-sm mb-5" style="color:var(--text-muted)">{{ $event->description }}</p>
        @endif

        @if (! $open)
            <div class="notice is-closed" role="status">
                <h2>{{ $closedReason[0] }}</h2>
                <p>{{ $closedReason[1] }}</p>
            </div>
        @else
        <form id="reg-form" method="POST" action="{{ route('public.registrations.store', $event->slug) }}">
            @csrf
            @foreach ($questions as $q)
                @php
                    $req = (bool) $q->is_required;
                    $type = $q->type->value;
                    $group = in_array($type, ['radio', 'checkbox'], true);
                    $errId = 'err-q'.$q->id;
                @endphp
                @if ($group)
                    <fieldset class="field">
                        <legend class="label">
                            {{ $q->label }}@if ($req)<span style="color:var(--c-danger)" aria-hidden="true"> *</span><span class="sr-only"> (required)</span>@endif
                        </legend>
                        <div class="flex flex-col gap-2">
                            @foreach (($q->options ?? []) as $opt)
                                @if ($type === 'radio')
                                    <label class="q-radio"><input type="radio" name="answers[{{ $q->id }}]" value="{{ $opt }}" @if ($req) required @endif> {{ $opt }}</label>
                                @else
                                    <label class="q-radio"><input type="checkbox" name="answers[{{ $q->id }}][]" value="{{ $opt }}"> {{ $opt }}</label>
                                @endif
                            @endforeach
                        </div>
                        <div class="error-text" id="{{ $errId }}" data-error="answers.{{ $q->id }}"></div>
                    </fieldset>
                @else
                    <div class="field">
                        <label class="label" for="q{{ $q->id }}">
                            {{ $q->label }}@if ($req)<span style="color:var(--c-danger)" aria-hidden="true"> *</span><span class="sr-only"> (required)</span>@endif
                        </label>
                        @switch($type)
                            @case('textarea')
                                <textarea class="textarea" id="q{{ $q->id }}" name="answers[{{ $q->id }}]" @if ($req) required @endif></textarea>
                                @break
                            @case('email')
                                <input class="input" type="email" id="q{{ $q->id }}" name="answers[{{ $q->id }}]" autocomplete="email" @if ($req) required @endif>
                                @break
                            @case('number')
                                <input class="input" type="number" id="q{{ $q->id }}" name="answers[{{ $q->id }}]" @if ($req) required @endif>
                                @break
                            @case('date')
                                <input class="input" type="date" id="q{{ $q->id }}" name="answers[{{ $q->id }}]" @if ($req) required @endif>
                                @break
                            @case('phone')
                                <input class="input" type="tel" id="q{{ $q->id }}" name="answers[{{ $q->id }}]" autocomplete="tel" @if ($req) required @endif>
                                @break
                            @case('select')
                                <select class="select" id="q{{ $q->id }}" name="answers[{{ $q->id }}]" @if ($req) required @endif>
                                    <option value="">Choose…</option>
                                    @foreach (($q->options ?? []) as $opt)
                                        <option value="{{ $opt }}">{{ $opt }}</option>
                                    @endforeach
                                </select>
                                @break
                            @default
                                <input class="input" type="text" id="q{{ $q->id }}" name="answers[{{ $q->id }}]" @if ($req) required @endif>
                        @endswitch
                        <div class="error-text" id="{{ $errId }}" data-error="answers.{{ $q->id }}"></div>
                    </div>
                @endif
            @endforeach

            <button type="submit" class="btn btn-primary btn-block" style="height:44px;margin-top:.4rem">Complete registration <span aria-hidden="true">→</span></button>
        </form>
        @endif
    </div>
</div>
@endsection

@push('scripts')
@if ($open)
<script>
$(function () {
    // Server may reject with field keys (answers.N, email) or form-level keys
    // (event = closed/ended, capacity = full, email = duplicate); http.js shows
    // anything that doesn't map to an input in the form-level error box.
    window.ajaxForm('#reg-form', function (res) {
        if (res && res.data && res.data.redirect) window.location = res.data.redirect;
    });
});
</script>
@endif
@endpush
