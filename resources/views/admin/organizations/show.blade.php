@extends('layouts.app')
@section('title', $organization->name)
@section('page-title', 'Organization')

@section('content')
<x-page-header title="{{ $organization->name }}" subtitle="Events for this organization.">
    <a class="btn btn-secondary" href="{{ route('admin.organizations.index') }}">All organizations</a>
</x-page-header>

<div class="card">
    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="col-grow">Event</th>
                    <th>Status</th>
                    <th>Starts</th>
                    <th class="num">Registered</th>
                    <th class="num">Attended</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($events as $e)
                    <tr>
                        <td class="col-grow"><div class="font-medium">{{ $e->title }}</div>
                            <div class="text-xs" style="color:var(--text-muted)">/e/{{ $e->slug }}</div></td>
                        <td><span class="badge badge-{{ $e->status->value === 'published' ? 'success' : ($e->status->value === 'closed' ? 'warning' : 'neutral') }}">{{ $e->status->label() }}</span></td>
                        <td>{{ $e->starts_at?->format('M j, Y') ?? '—' }}</td>
                        <td class="num">{{ $e->registrations_count }}</td>
                        <td class="num">{{ $e->attended_count }}</td>
                        <td class="text-right">
                            <a class="btn btn-ghost btn-sm" href="{{ route('admin.organizations.event', [$organization->id, $e->id]) }}">Registrations</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-empty-state title="No events" message="This organization has not created any events yet." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($events->hasPages())
        <div class="p-4">{{ $events->links() }}</div>
    @endif
</div>
@endsection
