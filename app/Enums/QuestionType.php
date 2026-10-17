<?php

namespace App\Enums;

enum QuestionType: string
{
    case Text = 'text';
    case Textarea = 'textarea';
    case Email = 'email';
    case Phone = 'phone';
    case Number = 'number';
    case Date = 'date';
    case Select = 'select';
    case Radio = 'radio';
    case Checkbox = 'checkbox';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Short text',
            self::Textarea => 'Paragraph',
            self::Email => 'Email',
            self::Phone => 'Phone',
            self::Number => 'Number',
            self::Date => 'Date',
            self::Select => 'Dropdown',
            self::Radio => 'Single choice',
            self::Checkbox => 'Checkboxes',
        };
    }

    /** Types whose `options` list drives the choices shown to the registrant. */
    public function hasOptions(): bool
    {
        return in_array($this, [self::Select, self::Radio, self::Checkbox], true);
    }

    /** Checkbox answers are multi-value (stored as a comma-joined string). */
    public function isMultiValue(): bool
    {
        return $this === self::Checkbox;
    }

    /** @return string[] */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
