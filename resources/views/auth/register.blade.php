@extends('layouts.guest')
@section('title', 'Create your organization')

@section('content')
    <h1 class="text-lg font-semibold mb-1">Create your organization</h1>
    <p class="text-sm mb-5" style="color: var(--text-muted)">Set up your account and start collecting event registrations.</p>

    <form id="register-form" method="POST" action="{{ route('register.attempt') }}">
        @csrf
        <div class="field">
            <label class="label" for="company">Company / organization</label>
            <input class="input" id="company" name="company" autocomplete="organization" required autofocus>
        </div>
        <div class="field">
            <label class="label" for="name">Your name</label>
            <input class="input" id="name" name="name" autocomplete="name" required>
        </div>
        <div class="field">
            <label class="label" for="email">Email</label>
            <input class="input" type="email" id="email" name="email" autocomplete="email" required>
        </div>
        <div class="form-grid">
            <div class="field">
                <label class="label" for="password">Password</label>
                <input class="input" type="password" id="password" name="password" autocomplete="new-password" required>
            </div>
            <div class="field">
                <label class="label" for="password_confirmation">Confirm</label>
                <input class="input" type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
            </div>
        </div>
        <button type="submit" class="btn btn-primary btn-block mt-2">Create organization</button>
    </form>
    <p class="text-center text-sm mt-5" style="color: var(--text-muted)">
        Already have an account? <a class="font-medium" style="color: var(--c-accent-600)" href="{{ route('login') }}">Sign in</a>
    </p>
@endsection

@push('scripts')
<script>
    $(function () { window.ajaxForm('#register-form'); });
</script>
@endpush
