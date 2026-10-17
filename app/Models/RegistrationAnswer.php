<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistrationAnswer extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'organization_id', 'registration_id', 'event_question_id', 'question_label', 'value',
    ];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(EventQuestion::class, 'event_question_id');
    }
}
