<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Dictionary\DictionaryValue\Name;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\DeviceColorSpaceNameValue;
use PrinsFrank\PdfParser\Document\Image\ColorSpace\Components;

#[CoversClass(DeviceColorSpaceNameValue::class)]
class DeviceColorSpaceNameValueTest extends TestCase {
    public function testGetComponents(): void {
        static::assertSame(Components::Gray, DeviceColorSpaceNameValue::DeviceGray->getComponents());
        static::assertSame(Components::RGB, DeviceColorSpaceNameValue::DeviceRGB->getComponents());
        static::assertSame(Components::CMYK, DeviceColorSpaceNameValue::DeviceCMYK->getComponents());
    }
}
