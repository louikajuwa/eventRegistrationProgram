@php
    $active = fn (string $r) => request()->routeIs($r) || request()->routeIs($r.'.*');
    $href = fn (string $r) => \Illuminate\Support\Facades\Route::has($r) ? route($r) : '#';
@endphp

<a href="{{ $href('dashboard') }}" class="nav-link {{ $active('dashboard') ? 'active' : '' }}" @if($active('dashboard')) aria-current="page" @endif>Dashboard</a>

@if (auth()->user()->isSuperAdmin())
    {{-- Platform operator: oversees all organizations (read-only). --}}
    <div class="nav-section">Platform</div>
    <a href="{{ $href('admin.organizations.index') }}" class="nav-link {{ $active('admin.organizations') ? 'active' : '' }}" @if($active('admin.organizations')) aria-current="page" @endif>Organizations</a>
@else
    @canany(['events.view', 'registrations.view', 'reports.view', 'settings.view'])
        <div class="nav-section">Manage</div>
        @can('events.view')
            <a href="{{ $href('events.index') }}" class="nav-link {{ $active('events') ? 'active' : '' }}" @if($active('events')) aria-current="page" @endif>Events</a>
        @endcan
        @can('registrations.view')
            <a href="{{ $href('registrations.index') }}" class="nav-link {{ $active('registrations') ? 'active' : '' }}" @if($active('registrations')) aria-current="page" @endif>Registrations</a>
        @endcan
        @can('reports.view')
            <a href="{{ $href('reports.index') }}" class="nav-link {{ $active('reports') ? 'active' : '' }}" @if($active('reports')) aria-current="page" @endif>Reports</a>
        @endcan
        @can('settings.view')
            <a href="{{ $href('admin.settings.index') }}" class="nav-link {{ $active('admin.settings') ? 'active' : '' }}" @if($active('admin.settings')) aria-current="page" @endif>Settings</a>
        @endcan
    @endcanany
@endif

<div class="nav-section">Account</div>
<a href="{{ $href('profile.edit') }}" class="nav-link {{ $active('profile.edit') ? 'active' : '' }}" @if($active('profile.edit')) aria-current="page" @endif>Profile</a>
