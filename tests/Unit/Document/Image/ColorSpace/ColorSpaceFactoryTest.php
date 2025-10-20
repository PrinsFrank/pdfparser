<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Image\ColorSpace;

use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\CIEColorSpaceNameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\DeviceColorSpaceNameValue;
use PrinsFrank\PdfParser\Document\Document;
use PrinsFrank\PdfParser\Document\Image\ColorSpace\ColorSpace;
use PrinsFrank\PdfParser\Document\Image\ColorSpace\ColorSpaceFactory;
use PrinsFrank\PdfParser\Document\Object\Decorator\DecoratedObject;
use PrinsFrank\PdfParser\Stream\InMemoryStream;

#[CoversClass(ColorSpaceFactory::class)]
class ColorSpaceFactoryTest extends TestCase {
    public function testFromStringNonIndexed(): void {
        $lutObject = $this->createMock(DecoratedObject::class);
        $document = $this->createMock(Document::class);
        $document->expects(self::once())
            ->method('getObject')
            ->with(4)
            ->willReturn($lutObject);

        static::assertEquals(
            new ColorSpace(
                false,
                DeviceColorSpaceNameValue::DeviceRGB,
                $lutObject,
                null,
                null,
            ),
            ColorSpaceFactory::fromString('[/DeviceRGB 4 0 R]', $document),
        );
    }

    public function testFromStringIndexed(): void {
        $lutObject = $this->createMock(DecoratedObject::class);
        $document = $this->createMock(Document::class);
        $document->expects(self::once())
            ->method('getObject')
            ->with(16)
            ->willReturn($lutObject);

        static::assertEquals(
            new ColorSpace(
                true,
                DeviceColorSpaceNameValue::DeviceRGB,
                $lutObject,
                null,
                255,
            ),
            ColorSpaceFactory::fromString('[/Indexed/DeviceRGB 255 16 0 R]', $document),
        );
    }

    public function testFromStringIndexedWithExtraSpaces(): void {
        $lutObject = $this->createMock(DecoratedObject::class);
        $document = $this->createMock(Document::class);
        $document->expects(self::once())
            ->method('getObject')
            ->with(16)
            ->willReturn($lutObject);

        static::assertEquals(
            new ColorSpace(
                true,
                DeviceColorSpaceNameValue::DeviceRGB,
                $lutObject,
                null,
                255,
            ),
            ColorSpaceFactory::fromString('[/Indexed /DeviceRGB 255 16 0 R]', $document),
        );
    }

    public function testFromStringIndexedWithTwoReferences(): void {
        $colorSpaceObject = $this->createMock(DecoratedObject::class);
        $colorSpaceObject->expects(self::atLeastOnce())
            ->method('getStream')
            ->willReturn(new InMemoryStream('[ /ICCBased 15 0 R ]'));

        $lutObject = $this->createMock(DecoratedObject::class);

        $document = $this->createMock(Document::class);
        $document->expects(self::exactly(2))
            ->method('getObject')
            ->willReturnCallback(static fn (int $id) => match ($id) {
                14 => $colorSpaceObject,
                18 => $lutObject,
                default => throw new Exception(sprintf('Unexpected id %d', $id)),
            });

        static::assertEquals(
            new ColorSpace(
                true,
                CIEColorSpaceNameValue::ICCBased,
                $lutObject,
                null,
                77,
            ),
            ColorSpaceFactory::fromString('[ /Indexed 14 0 R 77 18 0 R ]', $document),
        );
    }

    public function testFromStringWithInlineLUT(): void {
        $colorSpaceObject = $this->createMock(DecoratedObject::class);
        $colorSpaceObject->expects(self::atLeastOnce())
            ->method('getStream')
            ->willReturn(new InMemoryStream('[/DeviceRGB 16 0 R]'));

        $document = $this->createMock(Document::class);
        $document->expects(self::once())
            ->method('getObject')
            ->willReturnCallback(static fn (int $id) => match ($id) {
                41 => $colorSpaceObject,
                default => throw new Exception(sprintf('Unexpected id %d', $id)),
            });

        static::assertEquals(
            new ColorSpace(
                true,
                DeviceColorSpaceNameValue::DeviceRGB,
                null,
                '<fffffffdfefffdf8f6fcedeaf9e3daf9e0d7f9e2dbf8ded7fbece6fbf4f3fdfbfafdfeff>',
                11,
            ),
            ColorSpaceFactory::fromString('[ /Indexed 41 0 R 11 <fffffffdfefffdf8f6fcedeaf9e3daf9e0d7f9e2dbf8ded7fbece6fbf4f3fdfbfafdfeff>]', $document),
        );
    }
}
