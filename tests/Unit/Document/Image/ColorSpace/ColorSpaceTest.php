<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Image\ColorSpace;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\DeviceColorSpaceNameValue;
use PrinsFrank\PdfParser\Document\Image\ColorSpace\ColorSpace;
use PrinsFrank\PdfParser\Document\Image\ColorSpace\Components;

#[CoversClass(ColorSpace::class)]
class ColorSpaceTest extends TestCase {
    public function testGetComponentsWithNameValue(): void {
        static::assertSame(
            Components::RGB,
            (new ColorSpace(true, DeviceColorSpaceNameValue::DeviceRGB, null, null, null))
                ->getComponents()
        );
        static::assertSame(
            Components::CMYK,
            (new ColorSpace(true, DeviceColorSpaceNameValue::DeviceCMYK, null, null, null))
                ->getComponents()
        );
        static::assertSame(
            Components::Gray,
            (new ColorSpace(true, DeviceColorSpaceNameValue::DeviceGray, null, null, null))
                ->getComponents()
        );
    }
}
