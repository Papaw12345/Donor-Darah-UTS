<?php

namespace Tests\Unit;

use App\Rules\MySqlTextBytes;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MySqlTextBytesTest extends TestCase
{
    #[DataProvider('textBoundaries')]
    public function test_rule_checks_bytes_including_utf8mb4(string $value, int $bytes, bool $passes): void
    {
        $this->assertSame(65535, MySqlTextBytes::MAX_BYTES);
        $this->assertSame($bytes, strlen($value));

        if (str_contains($value, "\u{1F600}")) {
            $this->assertLessThan(65535, mb_strlen($value, 'UTF-8'));
        }

        $failures = [];
        (new MySqlTextBytes)->validate('catatan', $value, function (string $message) use (&$failures): void {
            $failures[] = $message;
        });

        $this->assertSame(! $passes, MySqlTextBytes::exceedsCapacity($value));
        $this->assertCount($passes ? 0 : 1, $failures);

        if (! $passes) {
            $this->assertStringContainsString('melebihi kapasitas penyimpanan teks', $failures[0]);
        }
    }

    public static function textBoundaries(): array
    {
        return [
            'ASCII exactly TEXT capacity' => [str_repeat('a', 65535), 65535, true],
            'ASCII exceeds TEXT capacity' => [str_repeat('a', 65536), 65536, false],
            'UTF8MB4 exactly TEXT capacity' => [str_repeat("\u{1F600}", 16383).'abc', 65535, true],
            'UTF8MB4 exceeds TEXT capacity' => [str_repeat("\u{1F600}", 16384), 65536, false],
        ];
    }

    public function test_non_string_values_are_left_to_existing_type_rules(): void
    {
        $rule = new MySqlTextBytes;

        foreach ([null, 0, false, ['bukan-teks'], new \stdClass] as $value) {
            $failures = [];
            $rule->validate('catatan', $value, function (string $message) use (&$failures): void {
                $failures[] = $message;
            });

            $this->assertSame([], $failures);
        }
    }
}
