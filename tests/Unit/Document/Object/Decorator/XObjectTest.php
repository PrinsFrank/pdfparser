<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Object\Decorator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Dictionary\Decorator\XObject;
use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryEntry\DictionaryEntry;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Integer\IntegerValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\SubtypeNameValue;

#[CoversClass(XObject::class)]
class XObjectTest extends TestCase {
    public function testIsImage(): void {
        $dictionary = new Dictionary(
            null,
            new DictionaryEntry(DictionaryKey::SUBTYPE, SubtypeNameValue::IMAGE),
        );

        static::assertTrue(
            (new XObject($dictionary))
                ->isImage(),
        );
    }

    public function testIsImageWhenIsForm(): void {
        $dictionary = new Dictionary(
            null,
            new DictionaryEntry(DictionaryKey::SUBTYPE, SubtypeNameValue::FORM),
        );

        static::assertFalse(
            (new XObject($dictionary))
                ->isImage(),
        );
    }

    public function testIsImageWithNoSubType(): void {
        static::assertFalse(
            (new XObject(new Dictionary(null)))
                ->isImage(),
        );
    }

    public function testIsForm(): void {
        $dictionary = new Dictionary(
            null,
            new DictionaryEntry(DictionaryKey::SUBTYPE, SubtypeNameValue::FORM),
        );

        static::assertTrue(
            (new XObject($dictionary))
                ->isForm(),
        );
    }

    public function testIsFormWhenIsImage(): void {
        $dictionary = new Dictionary(
            null,
            new DictionaryEntry(DictionaryKey::SUBTYPE, SubtypeNameValue::IMAGE),
        );

        static::assertFalse(
            (new XObject($dictionary))
                ->isForm(),
        );
    }

    public function testIsFormWithNoSubType(): void {
        static::assertFalse(
            (new XObject(new Dictionary(null)))
                ->isForm(),
        );
    }

    public function testGetWidth(): void {
        $dictionary = new Dictionary(null, new DictionaryEntry(DictionaryKey::WIDTH, new IntegerValue(42)));

        static::assertSame(
            42,
            (new XObject($dictionary))
                ->getWidth(),
        );
    }

    public function testGetWidthReturnsNullWhenNoWidth(): void {
        static::assertNull(
            (new XObject(new Dictionary(null)))
                ->getWidth(),
        );
    }

    public function testGetHeight(): void {
        $dictionary = new Dictionary(null, new DictionaryEntry(DictionaryKey::HEIGHT, new IntegerValue(42)));

        static::assertSame(
            42,
            (new XObject($dictionary))
                ->getHeight(),
        );
    }

    public function testGetHeightReturnsNullWhenNoWidth(): void {
        static::assertNull(
            (new XObject(new Dictionary(null)))
                ->getHeight(),
        );
    }

    public function testGetLength(): void {
        $dictionary = new Dictionary(null, new DictionaryEntry(DictionaryKey::LENGTH, new IntegerValue(42)));

        static::assertSame(
            42,
            (new XObject($dictionary))
                ->getLength(),
        );
    }

    public function testGetLengthReturnsNullWhenNoWidth(): void {
        static::assertNull(
            (new XObject(new Dictionary(null)))
                ->getLength(),
        );
    }
}
