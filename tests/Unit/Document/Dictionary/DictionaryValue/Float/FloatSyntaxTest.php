<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Dictionary\DictionaryValue\Float;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Float\FloatValue;

#[CoversClass(FloatValue::class)]
class FloatSyntaxTest extends TestCase {
    /** @return iterable<string, array{string, float}> */
    public static function validNumbers(): iterable {
        yield 'leading decimal' => ['.8', 0.8];
        yield 'signed leading decimal' => ['-.8', -0.8];
        yield 'positive leading decimal' => ['+.8', 0.8];
        yield 'trailing decimal' => ['4.', 4.0];
        yield 'positive integer' => ['+17', 17.0];
        yield 'leading zeroes' => ['000.80', 0.8];
        yield 'large number without thousands separators' => ['12345.67', 12345.67];
        yield 'PDF whitespace' => ["\x00\x09\x0A\x0C\x0D\x20.8\x00", .8];
        yield 'negative zero' => ['-0.0', -0.0];
    }

    #[DataProvider('validNumbers')]
    public function testValidPdfNumbers(string $input, float $expected): void {
        static::assertSame($expected, FloatValue::fromValue($input)?->value);
    }

    /** @return iterable<array{string}> */
    public static function invalidNumbers(): iterable {
        foreach (['', '.', '+', '-.', '1e2', '1,000.0', 'NAN', 'INF', '1.2.3', '3junk', "\x0B.8", ".8\x0B"] as $input) {
            yield [$input];
        }
        yield [str_repeat('9', 400)];
    }

    #[DataProvider('invalidNumbers')]
    public function testRejectsNonPdfNumbers(string $input): void {
        static::assertNull(FloatValue::fromValue($input));
    }
}
