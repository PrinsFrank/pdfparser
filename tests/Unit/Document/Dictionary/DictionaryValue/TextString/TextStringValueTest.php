<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Dictionary\DictionaryValue\TextString;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\TextStringValue;

#[CoversClass(TextStringValue::class)]
class TextStringValueTest extends TestCase {
    public function testFromValue(): void {
        static::assertEquals(new TextStringValue('(foo)'), TextStringValue::fromValue('(foo)'));
    }

    /** @see 7.3.5, table 4 */
    public function testGetTextLiteralNames(): void {
        static::assertSame(
            '/Name1',
            (new TextStringValue('/Name1'))->getText()
        );
        static::assertSame(
            '/ASomewhatLongerName',
            (new TextStringValue('/ASomewhatLongerName'))->getText()
        );
        static::assertSame(
            '/A;Name_With-Various***Characters?',
            (new TextStringValue('/A;Name_With-Various***Characters?'))->getText()
        );
        static::assertSame(
            '/1.2',
            (new TextStringValue('/1.2'))->getText()
        );
        static::assertSame(
            '/$$',
            (new TextStringValue('/$$'))->getText()
        );
        static::assertSame(
            '/@pattern',
            (new TextStringValue('/@pattern'))->getText()
        );
        static::assertSame(
            '/.notdef',
            (new TextStringValue('/.notdef'))->getText()
        );
        static::assertSame(
            '/Lime Green',
            (new TextStringValue('/Lime#20Green'))->getText()
        );
        static::assertSame(
            '/paired()parentheses',
            (new TextStringValue('/paired#28#29parentheses'))->getText()
        );
        static::assertSame(
            '/The_Key_of_F#_Minor',
            (new TextStringValue('/The_Key_of_F#23_Minor'))->getText()
        );
        static::assertSame(
            '/AB',
            (new TextStringValue('/A#42'))->getText()
        );
    }
}
