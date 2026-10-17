@extends('layouts.app')
@section('title', 'Confirmation email')
@section('page-title', 'Confirmation email')

@php
    $placeholders = ['name', 'email', 'event_title', 'event_date', 'event_location', 'reference'];
    $sample = [
        'name' => 'Amina Wanjiru',
        'email' => 'amina@example.com',
        'event_title' => $event->title,
        'event_date' => $event->starts_at?->format('D, d M Y · g:i A') ?? 'TBD',
        'event_location' => $event->location ?: 'TBD',
        'reference' => 'REG-'.now()->format('Ymd').'-A7F2C9',
    ];
@endphp

@section('content')
<x-page-header title="{{ $event->title }} — confirmation email"
    subtitle="This email is sent to each person who registers for this event.">
    <a class="btn btn-secondary" href="{{ route('events.questions.index', $event->id) }}">Form</a>
    <a class="btn btn-secondary" href="{{ route('events.index') }}">Back</a>
    <button class="btn btn-primary" id="btn-save">Save email</button>
</x-page-header>

<div class="card">
    <div class="card-pad">
        <div class="mail-grid">
            {{-- Editor --}}
            <div>
                <div class="field">
                    <span class="field-lab">Subject line</span>
                    <input class="input" id="e-subject" value="{{ $event->email_subject }}"
                           placeholder="You're registered for {event_title} 🎉">
                </div>
                <div class="field">
                    <span class="field-lab">Email body</span>
                    <textarea class="textarea" id="e-body"
                              style="min-height:240px;font-family:ui-monospace,'Cascadia Code',Consolas,monospace;font-size:.8rem;line-height:1.55"
                              placeholder="Hi {name}, thanks for registering for {event_title}…">{{ $event->email_body }}</textarea>
                </div>
                <span class="field-lab">Insert a placeholder</span>
                <div class="chips" id="e-chips">
                    @foreach ($placeholders as $ph)
                        <span class="chip" data-ph="{{ $ph }}">{{ '{'.$ph.'}' }}</span>
                    @endforeach
                </div>
                <p class="help-text" style="margin-top:.75rem">Leave both blank to use your organization's default email (Settings).</p>
            </div>
            {{-- Live preview --}}
            <div>
                <span class="field-lab">Preview</span>
                <div class="mail-preview" id="e-preview"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/core/email-editor.js') }}?v={{ @filemtime(public_path('assets/js/core/email-editor.js')) }}"></script>
<script>
$(function () {
    EmailEditor.init({
        subject: '#e-subject', body: '#e-body', chips: '#e-chips', preview: '#e-preview',
        sample: @json($sample),
    });

    $('#btn-save').on('click', function () {
        window.http({
            method: 'PUT', url: "{{ route('events.email.update', $event->id) }}",
            data: { email_subject: $('#e-subject').val(), email_body: $('#e-body').val() },
            button: this,
        });
    });
});
</script>
@endpush
