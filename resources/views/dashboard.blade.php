@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<x-page-header title="Overview"
    subtitle="{{ $role === 'super' ? 'Platform-wide activity across all organizations.' : 'Your events and registrations at a glance.' }}" />

<div class="kpi-grid mb-6">
    @foreach ($kpis as $kpi)
        <div class="card stat">
            <div class="stat-label">{{ $kpi['label'] }}</div>
            <div class="stat-value">{{ $kpi['value'] }}</div>
        </div>
    @endforeach
</div>

@if ($role === 'super')
    <div class="card">
        <div class="card-header"><span class="card-title">Platform</span></div>
        <div class="card-pad">
            <p class="text-sm mb-3" style="color:var(--text-muted)">Browse every organization and drill into their events and registrations.</p>
            <a href="{{ route('admin.organizations.index') }}" class="btn btn-primary">View organizations →</a>
        </div>
    </div>
@endif

@if ($role !== 'super')
    <div class="card">
        <div class="card-header"><span class="card-title">Recent events</span>
            @can('events.view')<a href="{{ route('events.index') }}" class="btn btn-secondary btn-sm">All events</a>@endcan
        </div>
        @if ($recentEvents->isEmpty())
            <x-empty-state title="No events yet" message="Create your first event to get a shareable registration link.">
                @can('events.create')<a href="{{ route('events.index') }}" class="btn btn-primary">Create an event</a>@endcan
            </x-empty-state>
        @else
            <div class="table-scroll">
                <table class="data-table">
                    <thead><tr><th class="col-grow">Event</th><th>Status</th><th>Starts</th><th class="num">Registered</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($recentEvents as $e)
                            <tr>
                                <td class="col-grow font-medium">{{ $e['title'] }}</td>
                                <td><span class="badge badge-{{ $e['status'] === 'published' ? 'success' : ($e['status'] === 'closed' ? 'warning' : 'neutral') }}">{{ $e['status'] }}</span></td>
                                <td>{{ $e['starts_at'] ?? '—' }}</td>
                                <td class="num">{{ $e['registrations'] }}</td>
                                <td class="text-right whitespace-nowrap">
                                    <a class="btn btn-ghost btn-sm" href="{{ route('events.dashboard', $e['id']) }}">Dashboard</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endif
@endsection
