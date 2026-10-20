<?php

namespace Tests\Unit;

use App\Support\SpreadsheetSanitizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SpreadsheetSanitizerTest extends TestCase
{
    public static function dangerous(): array
    {
        return [
            ['=HYPERLINK("http://x")'],
            ['+SUM(1,2)'],
            ['-2+3+cmd|calc'],
            ['@SUM(A1)'],
            ["\t=1+1"],
        ];
    }

    #[DataProvider('dangerous')]
    public function test_formula_like_values_are_prefixed(string $value): void
    {
        $this->assertSame("'".$value, SpreadsheetSanitizer::cell($value));
    }

    public function test_phone_numbers_and_plain_values_are_untouched(): void
    {
        foreach (['+254 712-345-678', '-5', '(020) 123.456', 'Jane Doe', 'a@b.com', ''] as $v) {
            $this->assertSame($v, SpreadsheetSanitizer::cell($v));
        }

        $this->assertNull(SpreadsheetSanitizer::cell(null));
    }
}
