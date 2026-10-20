<?php

namespace App\Models;

use App\Enums\EventStatus;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'organization_id', 'title', 'slug', 'description', 'location',
        'starts_at', 'ends_at', 'capacity', 'status', 'email_subject', 'email_body',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'capacity' => 'integer',
            'status' => EventStatus::class,
        ];
    }

    public function questions(): HasMany
    {
        return $this->hasMany(EventQuestion::class)->orderBy('sort_order')->orderBy('id');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', EventStatus::Published->value);
    }

    /** Seats taken: only active registrations count (inactive/cancelled free their seat). */
    public function activeRegistrationsCount(): int
    {
        return $this->registrations()->active()->count();
    }

    public function isFull(): bool
    {
        return $this->capacity !== null && $this->activeRegistrationsCount() >= $this->capacity;
    }

    /** Ended = ends_at (or starts_at when no end is set) is in the past. */
    public function hasEnded(): bool
    {
        $end = $this->ends_at ?? $this->starts_at;

        return $end !== null && $end->isPast();
    }

    /** Status allows registration AND the event has not ended. (Capacity is checked separately.) */
    public function acceptsRegistrations(): bool
    {
        return $this->status->acceptsRegistrations() && ! $this->hasEnded();
    }
}
