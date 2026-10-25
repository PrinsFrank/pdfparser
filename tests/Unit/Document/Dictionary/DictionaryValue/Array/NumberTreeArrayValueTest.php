<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Dictionary\DictionaryValue\Array;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryEntry\DictionaryEntry;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array\NumberTreeArrayValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Integer\IntegerValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\NumberingStyleNameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\LiteralStringValue;

#[CoversClass(NumberTreeArrayValue::class)]
class NumberTreeArrayValueTest extends TestCase {
    public function testFromValueWithExampleFromTable161(): void {
        static::assertEquals(
            new NumberTreeArrayValue(
                [
                    0 => new Dictionary(null, new DictionaryEntry(DictionaryKey::S, NumberingStyleNameValue::LowerCaseRomanNumerals)),
                    4 => new Dictionary(null, new DictionaryEntry(DictionaryKey::S, NumberingStyleNameValue::DecimalArabic)),
                    7 => new Dictionary(
                        null,
                        new DictionaryEntry(DictionaryKey::S, NumberingStyleNameValue::DecimalArabic),
                        new DictionaryEntry(DictionaryKey::P, new LiteralStringValue(' A- ')),
                        new DictionaryEntry(DictionaryKey::ST, new IntegerValue(8)),
                    ),
                ],
            ),
            NumberTreeArrayValue::fromValue(
                <<<TREE
                [ 0 <</S /r>> %A number tree containing
                4 <</S /D>> %three page label dictionaries
                7 <</S /D
                /P ( A- )
                /St 8
                >>
                ]
                TREE,
            ),
        );
        static::assertEquals(
            new NumberTreeArrayValue(
                [
                    0 => new Dictionary(null, new DictionaryEntry(DictionaryKey::S, NumberingStyleNameValue::LowerCaseRomanNumerals)),
                    4 => new Dictionary(null, new DictionaryEntry(DictionaryKey::S, NumberingStyleNameValue::DecimalArabic)),
                    7 => new Dictionary(
                        null,
                        new DictionaryEntry(DictionaryKey::S, NumberingStyleNameValue::DecimalArabic),
                        new DictionaryEntry(DictionaryKey::P, new LiteralStringValue(' A- ')),
                        new DictionaryEntry(DictionaryKey::ST, new IntegerValue(8)),
                    ),
                ],
            ),
            NumberTreeArrayValue::fromValue('[ 0 <</S /r>>4 <</S /D>> 7 <</S /D/P ( A- )/St 8>>]'),
        );
        static::assertEquals(
            new NumberTreeArrayValue(
                [
                    0 => new Dictionary(null, new DictionaryEntry(DictionaryKey::S, NumberingStyleNameValue::LowerCaseRomanNumerals)),
                    4 => new Dictionary(null, new DictionaryEntry(DictionaryKey::S, NumberingStyleNameValue::DecimalArabic)),
                    7 => new Dictionary(
                        null,
                        new DictionaryEntry(DictionaryKey::S, NumberingStyleNameValue::DecimalArabic),
                        new DictionaryEntry(DictionaryKey::P, new LiteralStringValue(' A- ')),
                        new DictionaryEntry(DictionaryKey::ST, new IntegerValue(8)),
                    ),
                ],
            ),
            NumberTreeArrayValue::fromValue('[0<</S/r>>4<</S/D>>7<</S/D/P( A- )/St 8>>]'),
        );
    }
}
