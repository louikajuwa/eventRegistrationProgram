<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Registration;
use App\Repositories\Contracts\RegistrationRepositoryInterface;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RegistrationController extends Controller
{
    public function __construct(
        private readonly RegistrationRepositoryInterface $registrations,
        private readonly AttendanceService $attendance,
    ) {}

    public function index(Request $request): mixed
    {
        if ($request->expectsJson() || $request->ajax()) {
            return $this->paginate($this->registrations->paginate($request->all(), ['event:id,title']));
        }

        // Events for the filter dropdown (tenant-scoped).
        return view('registrations.index', [
            'events' => Event::orderBy('title')->get(['id', 'title']),
        ]);
    }

    public function show(Registration $registration): mixed
    {
        $this->authorizeTenant($registration);

        return $this->ok($registration->load(['answers', 'event:id,title']));
    }

    public function toggleAttendance(Request $request, Registration $registration): mixed
    {
        $this->authorizeTenant($registration);
        $attended = $request->boolean('attended');

        return $this->ok($this->attendance->toggle($registration, $attended), 'Attendance updated.');
    }

    /** Set a registration active/inactive (lifecycle flag, independent of attendance). */
    public function updateStatus(Request $request, Registration $registration): mixed
    {
        $this->authorizeTenant($registration);

        $data = $request->validate([
            'status' => ['required', Rule::in(RegistrationStatus::values())],
        ]);
        $registration->update(['status' => $data['status']]);

        return $this->ok($registration->refresh(), 'Registration status updated.');
    }

    public function destroy(Registration $registration): mixed
    {
        $this->authorizeTenant($registration);
        $registration->delete(); // answers cascade via FK

        return $this->ok(null, 'Registration deleted.');
    }
}
