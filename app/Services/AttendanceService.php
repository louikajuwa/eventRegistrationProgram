<?php

namespace App\Services;

use App\Models\Registration;

class AttendanceService
{
    /** Flip the attendance flag and stamp / clear the check-in time. */
    public function toggle(Registration $registration, bool $attended): Registration
    {
        $registration->attended = $attended;
        $registration->checked_in_at = $attended ? now() : null;
        $registration->save();

        return $registration->refresh();
    }
}
