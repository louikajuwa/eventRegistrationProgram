<?php

namespace App\Http\Requests\Event;

class UpdateEventRequest extends StoreEventRequest
{
    // Same rules as store; the service keeps the original slug on update.
}
