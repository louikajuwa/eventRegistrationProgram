<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Registration;
use Illuminate\Http\Request;

/**
 * Platform-operator (SuperAdmin) oversight — READ-ONLY drill-down across every
 * organization: Organizations → an org's Events → an event's Registrations.
 * SuperAdmin has no tenant, so all tenant-owned queries are explicitly unscoped.
 */
class OrganizationController extends Controller
{
    public function index(Request $request): mixed
    {
        if ($request->expectsJson() || $request->ajax()) {
            $term = trim((string) $request->get('search', ''));

            $query = Organization::query()->withCount(['users', 'events', 'registrations']);
            if ($term !== '') {
                $query->where(function ($w) use ($term): void {
                    $w->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%")
                        ->orWhere('slug', 'like', "%{$term}%");
                });
            }

            $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);

            return $this->paginate($query->orderBy('name')->paginate($perPage)->withQueryString());
        }

        return view('admin.organizations.index');
    }

    public function show(Organization $organization): mixed
    {
        $events = Event::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->withCount([
                'registrations' => fn ($q) => $q->active(),
                'registrations as attended_count' => fn ($q) => $q->where('attended', true),
            ])
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.organizations.show', [
            'organization' => $organization,
            'events' => $events,
        ]);
    }

    /** Activate / deactivate an organization. Deactivated orgs cannot sign in. */
    public function updateStatus(Request $request, Organization $organization): mixed
    {
        $data = $request->validate(['is_active' => ['required', 'boolean']]);
        $organization->update(['is_active' => $data['is_active']]);

        return $this->ok(
            $organization->refresh(),
            $data['is_active'] ? 'Organization activated.' : 'Organization deactivated — its users can no longer sign in.',
        );
    }

    public function event(Organization $organization, Event $event): mixed
    {
        // Ensure the event belongs to the organization in the URL.
        abort_unless((int) $event->organization_id === (int) $organization->id, 404);

        $registrations = Registration::withoutGlobalScopes()
            ->where('event_id', $event->id)
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        $totals = [
            'registered' => Registration::withoutGlobalScopes()->where('event_id', $event->id)->active()->count(),
            'attended' => Registration::withoutGlobalScopes()->where('event_id', $event->id)->where('attended', true)->count(),
        ];

        return view('admin.organizations.event', [
            'organization' => $organization,
            'event' => $event,
            'registrations' => $registrations,
            'totals' => $totals,
        ]);
    }
}
