<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Dictionary\Normalization;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Dictionary\Normalization\NameValueNormalizer;

#[CoversClass(NameValueNormalizer::class)]
class NameValueNormalizerTest extends TestCase {
    public function testNormalize(): void {
        static::assertSame('', NameValueNormalizer::normalize(''));
        static::assertSame('Foo', NameValueNormalizer::normalize('Foo'));
        static::assertSame('Foo', NameValueNormalizer::normalize('/Foo'));
        static::assertSame('F.o', NameValueNormalizer::normalize('/F.o'));
        static::assertSame('F.o', NameValueNormalizer::normalize('/F#2Eo'));
        static::assertSame('Foo Bar', NameValueNormalizer::normalize('/Foo#20Bar'));
    }
}
