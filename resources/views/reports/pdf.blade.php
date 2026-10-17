<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 11px; color: #1b1a1f; }
        h1 { font-size: 16px; margin: 0 0 2px; }
        .muted { color: #666; font-size: 10px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; background: #f4f0ea; border-bottom: 1px solid #ddd; padding: 6px 8px; font-size: 10px; text-transform: uppercase; }
        td { padding: 5px 8px; border-bottom: 1px solid #eee; }
        .note { margin-top: 10px; color: #9a6b00; font-size: 10px; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <div class="muted">Generated {{ now()->format('D, d M Y H:i') }}</div>
    <table>
        <thead><tr>@foreach ($columns as $c)<th>{{ $c }}</th>@endforeach</tr></thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>@foreach ($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>
            @empty
                <tr><td colspan="{{ count($columns) }}">No data for the selected filters.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if ($truncated)
        <p class="note">Showing the first {{ number_format($maxRows) }} rows. Export to Excel/CSV for the full dataset.</p>
    @endif
</body>
</html>
