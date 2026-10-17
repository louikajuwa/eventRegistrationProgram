@extends('layouts.public')
@section('title', $event->title)

@section('content')
<div class="ticket">
    <div class="ticket-banner">
        <div class="ticket-kicker">You're invited</div>
        <div class="ticket-title">{{ $event->title }}</div>
        <div class="ticket-meta">
            @if ($event->starts_at)<span>📅 {{ $event->starts_at->format('D, d M Y · g:i A') }}</span>@endif
            @if ($event->location)<span>📍 {{ $event->location }}</span>@endif
        </div>
    </div>
    <div class="ticket-perf"></div>
    <div class="ticket-body">
        @if ($event->description)
            <p class="text-sm mb-5" style="color:var(--text-muted)">{{ $event->description }}</p>
        @endif

        <form id="reg-form" method="POST" action="{{ route('public.registrations.store', $event->slug) }}">
            @csrf
            @foreach ($questions as $q)
                <div class="field">
                    <label class="label" for="q{{ $q->id }}">
                        {{ $q->label }}@if ($q->is_required)<span style="color:var(--c-danger)"> *</span>@endif
                    </label>
                    @switch($q->type->value)
                        @case('textarea')
                            <textarea class="textarea" id="q{{ $q->id }}" name="answers[{{ $q->id }}]"></textarea>
                            @break
                        @case('email')
                            <input class="input" type="email" id="q{{ $q->id }}" name="answers[{{ $q->id }}]">
                            @break
                        @case('number')
                            <input class="input" type="number" id="q{{ $q->id }}" name="answers[{{ $q->id }}]">
                            @break
                        @case('date')
                            <input class="input" type="date" id="q{{ $q->id }}" name="answers[{{ $q->id }}]">
                            @break
                        @case('phone')
                            <input class="input" type="tel" id="q{{ $q->id }}" name="answers[{{ $q->id }}]">
                            @break
                        @case('select')
                            <select class="select" id="q{{ $q->id }}" name="answers[{{ $q->id }}]">
                                <option value="">Choose…</option>
                                @foreach (($q->options ?? []) as $opt)
                                    <option value="{{ $opt }}">{{ $opt }}</option>
                                @endforeach
                            </select>
                            @break
                        @case('radio')
                            <div class="flex flex-col gap-2">
                                @foreach (($q->options ?? []) as $opt)
                                    <label class="q-radio"><input type="radio" name="answers[{{ $q->id }}]" value="{{ $opt }}"> {{ $opt }}</label>
                                @endforeach
                            </div>
                            @break
                        @case('checkbox')
                            <div class="flex flex-col gap-2">
                                @foreach (($q->options ?? []) as $opt)
                                    <label class="q-radio"><input type="checkbox" name="answers[{{ $q->id }}][]" value="{{ $opt }}"> {{ $opt }}</label>
                                @endforeach
                            </div>
                            @break
                        @default
                            <input class="input" type="text" id="q{{ $q->id }}" name="answers[{{ $q->id }}]">
                    @endswitch
                    <div class="error-text" data-error="answers.{{ $q->id }}"></div>
                </div>
            @endforeach

            <button type="submit" class="btn btn-primary btn-block" style="height:44px;margin-top:.4rem">Complete registration →</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    window.ajaxForm('#reg-form', function (res) {
        if (res && res.data && res.data.redirect) window.location = res.data.redirect;
    });
});
</script>
@endpush
