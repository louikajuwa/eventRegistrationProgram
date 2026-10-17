@extends('layouts.app')
@section('title', 'Settings')
@section('page-title', 'Settings')

@php
    $placeholders = ['name', 'email', 'event_title', 'event_date', 'event_location', 'reference'];
    $sample = [
        'name' => 'Amina Wanjiru',
        'email' => 'amina@example.com',
        'event_title' => 'Your Event',
        'event_date' => 'Sat, 14 Mar 2026 · 9:00 AM',
        'event_location' => 'Your venue',
        'reference' => 'REG-20260314-A7F2C9',
    ];
@endphp

@section('content')
<x-page-header title="Settings" subtitle="Your organization's default confirmation email and outgoing mail (SMTP)." />

<form id="settings-form">
    {{-- Default confirmation email — two-panel editor + live preview --}}
    <div class="card">
        <div class="card-header">
            <div>
                <span class="card-title">Default confirmation email</span>
                <div class="card-sub">Used when an event has no custom email of its own.</div>
            </div>
        </div>
        <div class="card-pad">
            <div class="mail-grid">
                {{-- Editor --}}
                <div>
                    <div class="field">
                        <span class="field-lab">Subject line</span>
                        <input class="input" id="s-subject" name="default_email_subject"
                               value="{{ $settings['default_email_subject'] ?? '' }}"
                               placeholder="You're registered for {event_title}">
                    </div>
                    <div class="field">
                        <span class="field-lab">Email body</span>
                        <textarea class="textarea" id="s-body" name="default_email_body"
                                  style="min-height:200px;font-family:ui-monospace,'Cascadia Code',Consolas,monospace;font-size:.8rem;line-height:1.55"
                                  placeholder="Hi {name}, thanks for registering for {event_title}…">{{ $settings['default_email_body'] ?? '' }}</textarea>
                    </div>
                    <span class="field-lab">Insert a placeholder</span>
                    <div class="chips" id="s-chips">
                        @foreach ($placeholders as $ph)
                            <span class="chip" data-ph="{{ $ph }}">{{ '{'.$ph.'}' }}</span>
                        @endforeach
                    </div>
                </div>
                {{-- Live preview --}}
                <div>
                    <span class="field-lab">Preview</span>
                    <div class="mail-preview" id="s-preview"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Outgoing mail (SMTP) --}}
    <div class="card" style="margin-top:1.25rem">
        <div class="card-header"><span class="card-title">Outgoing mail (SMTP)</span></div>
        <div class="card-pad">
            <div class="form-grid">
                <div class="field"><label class="label" for="s-host">Host</label><input class="input" id="s-host" name="mail_host" value="{{ $settings['mail_host'] ?? '' }}" placeholder="mailpit"></div>
                <div class="field"><label class="label" for="s-port">Port</label><input class="input" id="s-port" name="mail_port" value="{{ $settings['mail_port'] ?? '' }}" placeholder="1025"></div>
            </div>
            <div class="form-grid">
                <div class="field"><label class="label" for="s-user">Username</label><input class="input" id="s-user" name="mail_username" value="{{ $settings['mail_username'] ?? '' }}"></div>
                <div class="field"><label class="label" for="s-pass">Password</label><input class="input" id="s-pass" name="mail_password" type="password" placeholder="•••••• (unchanged)"></div>
            </div>
            <div class="form-grid">
                <div class="field"><label class="label" for="s-enc">Encryption</label><input class="input" id="s-enc" name="mail_encryption" value="{{ $settings['mail_encryption'] ?? '' }}" placeholder="tls / null"></div>
                <div class="field"><label class="label" for="s-from">From address</label><input class="input" id="s-from" name="mail_from_address" value="{{ $settings['mail_from_address'] ?? '' }}"></div>
            </div>
            <div class="field"><label class="label" for="s-fromname">From name</label><input class="input" id="s-fromname" name="mail_from_name" value="{{ $settings['mail_from_name'] ?? '' }}"></div>
        </div>
    </div>

    <div class="flex items-center gap-2" style="margin-top:1.25rem">
        <button type="submit" class="btn btn-primary">Save settings</button>
        <button type="button" id="btn-test" class="btn btn-secondary">Send test email</button>
    </div>
</form>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/core/email-editor.js') }}?v={{ @filemtime(public_path('assets/js/core/email-editor.js')) }}"></script>
<script>
$(function () {
    EmailEditor.init({
        subject: '#s-subject', body: '#s-body', chips: '#s-chips', preview: '#s-preview',
        sample: @json($sample),
    });

    $('#settings-form').on('submit', function (e) {
        e.preventDefault();
        window.http({ method: 'PUT', url: "{{ route('admin.settings.update') }}", data: $(this).serialize(), form: $(this), button: $(this).find('[type=submit]') });
    });
    $('#btn-test').on('click', function () {
        window.http({ method: 'POST', url: "{{ route('admin.settings.test-email') }}", button: this });
    });
});
</script>
@endpush
