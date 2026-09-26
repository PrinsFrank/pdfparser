<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Dictionary\DictionaryValue\TextString;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\NameObjectStringValue;

#[CoversClass(NameObjectStringValue::class)]
class NameObjectStringValueTest extends TestCase {
    public function testFromValue(): void {
        static::assertEquals(new NameObjectStringValue('/Foo'), NameObjectStringValue::fromValue('/Foo'));
    }

    /** @see 7.3.5, table 4 */
    public function testGetTextLiteralNames(): void {
        static::assertSame(
            '/Name1',
            (new NameObjectStringValue('/Name1'))->getText(),
        );
        static::assertSame(
            '/ASomewhatLongerName',
            (new NameObjectStringValue('/ASomewhatLongerName'))->getText(),
        );
        static::assertSame(
            '/A;Name_With-Various***Characters?',
            (new NameObjectStringValue('/A;Name_With-Various***Characters?'))->getText(),
        );
        static::assertSame(
            '/1.2',
            (new NameObjectStringValue('/1.2'))->getText(),
        );
        static::assertSame(
            '/$$',
            (new NameObjectStringValue('/$$'))->getText(),
        );
        static::assertSame(
            '/@pattern',
            (new NameObjectStringValue('/@pattern'))->getText(),
        );
        static::assertSame(
            '/.notdef',
            (new NameObjectStringValue('/.notdef'))->getText(),
        );
        static::assertSame(
            '/Lime Green',
            (new NameObjectStringValue('/Lime#20Green'))->getText(),
        );
        static::assertSame(
            '/paired()parentheses',
            (new NameObjectStringValue('/paired#28#29parentheses'))->getText(),
        );
        static::assertSame(
            '/The_Key_of_F#_Minor',
            (new NameObjectStringValue('/The_Key_of_F#23_Minor'))->getText(),
        );
        static::assertSame(
            '/AB',
            (new NameObjectStringValue('/A#42'))->getText(),
        );
    }
}
