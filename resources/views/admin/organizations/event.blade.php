@extends('layouts.app')
@section('title', $event->title)
@section('page-title', 'Registrations')

@section('content')
<x-page-header title="{{ $event->title }}" subtitle="{{ $organization->name }} · {{ $totals['registered'] }} registered · {{ $totals['attended'] }} attended">
    <a class="btn btn-secondary" href="{{ route('admin.organizations.show', $organization->id) }}">Back to {{ $organization->name }}</a>
</x-page-header>

<div class="card">
    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="col-grow">Registrant</th>
                    <th>Reference</th>
                    <th>Registered</th>
                    <th>Status</th>
                    <th class="text-center">Attended</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($registrations as $r)
                    <tr>
                        <td class="col-grow"><div class="font-medium">{{ $r->name }}</div>
                            <div class="text-xs" style="color:var(--text-muted)">{{ $r->email }}</div></td>
                        <td class="text-xs" style="font-family:ui-monospace,monospace">{{ $r->reference }}</td>
                        <td>{{ $r->created_at?->format('M j, Y H:i') }}</td>
                        <td><span class="badge badge-{{ $r->status->value === 'active' ? 'success' : 'neutral' }}">{{ $r->status->label() }}</span></td>
                        <td class="text-center"><span class="badge badge-{{ $r->attended ? 'success' : 'neutral' }}">{{ $r->attended ? 'Yes' : 'No' }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-empty-state title="No registrations" message="No one has registered for this event yet." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($registrations->hasPages())
        <div class="p-4">{{ $registrations->links() }}</div>
    @endif
</div>
@endsection
