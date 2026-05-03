<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Dictionary\DictionaryValue\Array;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array\CrossReferenceStreamByteSizes;
use PrinsFrank\PdfParser\Exception\RuntimeException;

#[CoversClass(CrossReferenceStreamByteSizes::class)]
class CrossReferenceStreamByteSizesTest extends TestCase {
    public function testFromValue(): void {
        static::assertNull(CrossReferenceStreamByteSizes::fromValue(''));
        static::assertNull(CrossReferenceStreamByteSizes::fromValue('[]'));
        static::assertNull(CrossReferenceStreamByteSizes::fromValue('[0]'));
        static::assertNull(CrossReferenceStreamByteSizes::fromValue('[0 1]'));
        static::assertEquals(
            new CrossReferenceStreamByteSizes(0, 1, 2),
            CrossReferenceStreamByteSizes::fromValue('[0 1 2]'),
        );
        static::assertEquals(
            new CrossReferenceStreamByteSizes(0, 1, 2),
            CrossReferenceStreamByteSizes::fromValue('[ 0 1 2 ]'),
        );
    }

    public function testGetTotalLengthInBytesThrowsExceptionWhenNegative(): void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Total length should not be less than 1, got -1');
        (new CrossReferenceStreamByteSizes(-1, 0, 0))
            ->getTotalLengthInBytes();
    }

    public function testGetTotalLengthInBytes(): void {
        static::assertSame(
            6,
            (new CrossReferenceStreamByteSizes(1, 2, 3))
                ->getTotalLengthInBytes(),
        );
    }
}
