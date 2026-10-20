<?php

namespace App\Support;

use App\Models\Event;
use App\Models\Registration;

/**
 * Substitutes a whitelisted set of {placeholders} in an organizer-authored email
 * template. Uses a plain strtr over an explicit map — NO Blade/eval — so a template
 * can never execute code or leak arbitrary data (injection-safe).
 */
class EmailTemplateRenderer
{
    /** @param array<string,string> $vars placeholder (without braces) => value */
    public static function render(string $template, array $vars): string
    {
        $map = [];
        foreach ($vars as $key => $value) {
            $map['{'.$key.'}'] = (string) $value;
        }

        return strtr($template, $map);
    }

    /**
     * The canonical placeholder set available in confirmation emails.
     *
     * @return array<string,string>
     */
    public static function varsFor(Registration $registration, Event $event): array
    {
        return [
            'name' => $registration->name,
            'email' => (string) $registration->email,
            'reference' => $registration->reference,
            'event_title' => $event->title,
            'event_date' => $event->starts_at?->format('D, d M Y · g:i A') ?? 'TBD',
            'event_location' => $event->location ?? 'TBD',
        ];
    }
}
