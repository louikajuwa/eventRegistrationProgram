<?php

namespace App\Models;

use App\Enums\RegistrationStatus;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Registration extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'organization_id', 'event_id', 'name', 'email', 'phone',
        'status', 'attended', 'checked_in_at', 'reference',
    ];

    protected function casts(): array
    {
        return [
            'attended' => 'boolean',
            'checked_in_at' => 'datetime',
            'status' => RegistrationStatus::class,
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(RegistrationAnswer::class);
    }

    public function scopeAttended(Builder $query): Builder
    {
        return $query->where('attended', true);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', RegistrationStatus::Active->value);
    }
}
