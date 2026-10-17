<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\User;

class DashboardController extends Controller
{
    public function index(): mixed
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return view('dashboard', [
                'role' => 'super',
                'kpis' => [
                    ['label' => 'Organizations', 'value' => number_format(Organization::count())],
                    ['label' => 'Events', 'value' => number_format(Event::withoutGlobalScopes()->count())],
                    ['label' => 'Registrations', 'value' => number_format(Registration::withoutGlobalScopes()->count())],
                    ['label' => 'Admins', 'value' => number_format(User::whereNotNull('organization_id')->count())],
                ],
                'recentEvents' => collect(),
            ]);
        }

        // OrgAdmin — tenant-scoped automatically.
        $registered = Registration::count();
        $attended = Registration::where('attended', true)->count();

        return view('dashboard', [
            'role' => 'org',
            'kpis' => [
                ['label' => 'Events', 'value' => number_format(Event::count())],
                ['label' => 'Published', 'value' => number_format(Event::published()->count())],
                ['label' => 'Registrations', 'value' => number_format($registered)],
                ['label' => 'Attended', 'value' => number_format($attended)],
            ],
            'recentEvents' => Event::withCount('registrations')->latest('id')->take(6)->get()
                ->map(fn (Event $e) => [
                    'id' => $e->id,
                    'title' => $e->title,
                    'status' => $e->status->value,
                    'registrations' => $e->registrations_count,
                    'starts_at' => $e->starts_at?->format('M j, Y'),
                ]),
        ]);
    }
}
