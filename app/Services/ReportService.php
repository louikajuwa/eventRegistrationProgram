<?php

namespace App\Services;

use App\Models\Registration;

/**
 * Builds report specs: { title, columns[], query, map }. The query is streamed by the
 * exporter (chunked), so a large report never materializes every row in memory.
 * Queries run inside an admin request, so the TenantScope keeps them to the org.
 */
class ReportService
{
    public const TYPES = ['registrations'];

    public function build(string $type, array $filters = [], ?int $eventId = null): array
    {
        return match ($type) {
            'registrations' => $this->registrations($filters, $eventId),
            default => ['title' => 'Report', 'columns' => [], 'query' => Registration::query()->whereRaw('1 = 0'), 'map' => fn () => []],
        };
    }

    private function registrations(array $f, ?int $eventId): array
    {
        $q = Registration::query()->with('event:id,title');
        if ($eventId) {
            $q->where('event_id', $eventId);
        }
        if (! empty($f['from'])) {
            $q->whereDate('created_at', '>=', $f['from']);
        }
        if (! empty($f['to'])) {
            $q->whereDate('created_at', '<=', $f['to']);
        }

        return [
            'title' => 'Registrations Report',
            'columns' => ['Reference', 'Event', 'Name', 'Email', 'Phone', 'Status', 'Registered', 'Attended', 'Checked In'],
            'query' => $q->latest('id'),
            'map' => fn (Registration $r) => [
                $r->reference,
                $r->event?->title,
                $r->name,
                $r->email,
                $r->phone,
                $r->status->label(),
                (string) $r->created_at?->toDateTimeString(),
                $r->attended ? 'Yes' : 'No',
                (string) $r->checked_in_at?->toDateTimeString(),
            ],
        ];
    }
}
