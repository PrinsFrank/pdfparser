<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Object\Decorator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryEntry\DictionaryEntry;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Integer\IntegerValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\SubtypeNameValue;
use PrinsFrank\PdfParser\Document\Document;
use PrinsFrank\PdfParser\Document\Object\Decorator\XObject;
use PrinsFrank\PdfParser\Document\Object\Item\ObjectItem;

#[CoversClass(XObject::class)]
class XObjectTest extends TestCase {
    public function testIsImage(): void {
        $objectItem = $this->createMock(ObjectItem::class);
        $objectItem->expects(self::atLeastOnce())
            ->method('getDictionary')
            ->willReturn(new Dictionary(
                new DictionaryEntry(DictionaryKey::SUBTYPE, SubtypeNameValue::IMAGE),
            ));

        static::assertTrue(
            (new XObject($objectItem, $this->createMock(Document::class)))
                ->isImage(),
        );
    }

    public function testIsImageWhenIsForm(): void {
        $objectItem = $this->createMock(ObjectItem::class);
        $objectItem->expects(self::atLeastOnce())
            ->method('getDictionary')
            ->willReturn(new Dictionary(
                new DictionaryEntry(DictionaryKey::SUBTYPE, SubtypeNameValue::FORM),
            ));

        static::assertFalse(
            (new XObject($objectItem, $this->createMock(Document::class)))
                ->isImage(),
        );
    }

    public function testIsImageWithNoSubType(): void {
        $objectItem = $this->createMock(ObjectItem::class);
        $objectItem->expects(self::atLeastOnce())
            ->method('getDictionary')
            ->willReturn(new Dictionary());

        static::assertFalse(
            (new XObject($objectItem, $this->createMock(Document::class)))
                ->isImage(),
        );
    }

    public function testIsForm(): void {
        $objectItem = $this->createMock(ObjectItem::class);
        $objectItem->expects(self::atLeastOnce())
            ->method('getDictionary')
            ->willReturn(new Dictionary(
                new DictionaryEntry(DictionaryKey::SUBTYPE, SubtypeNameValue::FORM),
            ));

        static::assertTrue(
            (new XObject($objectItem, $this->createMock(Document::class)))
                ->isForm(),
        );
    }

    public function testIsFormWhenIsImage(): void {
        $objectItem = $this->createMock(ObjectItem::class);
        $objectItem->expects(self::atLeastOnce())
            ->method('getDictionary')
            ->willReturn(new Dictionary(
                new DictionaryEntry(DictionaryKey::SUBTYPE, SubtypeNameValue::IMAGE),
            ));

        static::assertFalse(
            (new XObject($objectItem, $this->createMock(Document::class)))
                ->isForm(),
        );
    }

    public function testIsFormWithNoSubType(): void {
        $objectItem = $this->createMock(ObjectItem::class);
        $objectItem->expects(self::atLeastOnce())
            ->method('getDictionary')
            ->willReturn(new Dictionary());

        static::assertFalse(
            (new XObject($objectItem, $this->createMock(Document::class)))
                ->isForm(),
        );
    }

    public function testGetWidth(): void {
        $objectItem = $this->createMock(ObjectItem::class);
        $objectItem->expects(self::atLeastOnce())
            ->method('getDictionary')
            ->willReturn(new Dictionary(new DictionaryEntry(DictionaryKey::WIDTH, new IntegerValue(42))));

        static::assertSame(
            42,
            (new XObject($objectItem, $this->createMock(Document::class)))
                ->getWidth(),
        );
    }

    public function testGetWidthReturnsNullWhenNoWidth(): void {
        $objectItem = $this->createMock(ObjectItem::class);
        $objectItem->expects(self::atLeastOnce())
            ->method('getDictionary')
            ->willReturn(new Dictionary());

        static::assertNull(
            (new XObject($objectItem, $this->createMock(Document::class)))
                ->getWidth(),
        );
    }

    public function testGetHeight(): void {
        $objectItem = $this->createMock(ObjectItem::class);
        $objectItem->expects(self::atLeastOnce())
            ->method('getDictionary')
            ->willReturn(new Dictionary(new DictionaryEntry(DictionaryKey::HEIGHT, new IntegerValue(42))));

        static::assertSame(
            42,
            (new XObject($objectItem, $this->createMock(Document::class)))
                ->getHeight(),
        );
    }

    public function testGetHeightReturnsNullWhenNoWidth(): void {
        $objectItem = $this->createMock(ObjectItem::class);
        $objectItem->expects(self::atLeastOnce())
            ->method('getDictionary')
            ->willReturn(new Dictionary());

        static::assertNull(
            (new XObject($objectItem, $this->createMock(Document::class)))
                ->getHeight(),
        );
    }

    public function testGetLength(): void {
        $objectItem = $this->createMock(ObjectItem::class);
        $objectItem->expects(self::atLeastOnce())
            ->method('getDictionary')
            ->willReturn(new Dictionary(new DictionaryEntry(DictionaryKey::LENGTH, new IntegerValue(42))));

        static::assertSame(
            42,
            (new XObject($objectItem, $this->createMock(Document::class)))
                ->getLength(),
        );
    }

    public function testGetLengthReturnsNullWhenNoWidth(): void {
        $objectItem = $this->createMock(ObjectItem::class);
        $objectItem->expects(self::atLeastOnce())
            ->method('getDictionary')
            ->willReturn(new Dictionary());

        static::assertNull(
            (new XObject($objectItem, $this->createMock(Document::class)))
                ->getLength(),
        );
    }
}
