@extends('layouts.app')
@section('title', 'Reports')
@section('page-title', 'Reports')

@section('content')
<x-page-header title="Reports" subtitle="Export registrations — registered vs attended — to Excel, CSV or PDF." />

<div class="card content-narrow">
    <div class="card-pad">
        <form id="report-form" method="GET" action="{{ route('reports.export') }}">
            <input type="hidden" name="type" value="registrations">
            <div class="field">
                <label class="label" for="r-event">Event</label>
                <select class="select" id="r-event" name="event_id">
                    <option value="">All events</option>
                    @foreach ($events as $e)
                        <option value="{{ $e->id }}">{{ $e->title }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-grid">
                <div class="field"><label class="label" for="r-from">From</label><input class="input" type="date" id="r-from" name="from"></div>
                <div class="field"><label class="label" for="r-to">To</label><input class="input" type="date" id="r-to" name="to"></div>
            </div>
            <div class="flex items-center gap-2 mt-2">
                <button type="submit" class="btn btn-primary" name="format" value="xlsx">Download Excel</button>
                <button type="submit" class="btn btn-secondary" name="format" value="csv">CSV</button>
                <button type="submit" class="btn btn-secondary" name="format" value="pdf">PDF</button>
            </div>
        </form>
    </div>
</div>
@endsection
