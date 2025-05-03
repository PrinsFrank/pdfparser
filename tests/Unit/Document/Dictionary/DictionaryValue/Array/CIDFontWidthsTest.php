<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Dictionary\DictionaryValue\Array;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array\CIDFontWidths;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array\Item\ConsecutiveCIDWidth;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array\Item\RangeCIDWidth;

#[CoversClass(CIDFontWidths::class)]
class CIDFontWidthsTest extends TestCase {
    public function testFromValue(): void {
        static::assertEquals(
            new CIDFontWidths(
                new ConsecutiveCIDWidth(17, [277.83203]),
                new ConsecutiveCIDWidth(36, [722.16797, 0, 0, 0, 666.99219]),
                new RangeCIDWidth(43, 49, 722.16797),
                new ConsecutiveCIDWidth(52, [777.83203]),
                new ConsecutiveCIDWidth(68, [556.15234, 610.83984, 556.15234, 610.83984, 556.15234, 333.00781, 610.83984, 610.83984, 277.83203, 0, 0, 277.83203, 889.16016]),
                new RangeCIDWidth(81, 84, 610.83984),
                new ConsecutiveCIDWidth(85, [389.16016, 556.15234, 333.00781, 610.83984, 556.15234, 0, 556.15234]),
            ),
            CIDFontWidths::fromValue('[17 [277.83203] 36 [722.16797 0 0 0 666.99219] 43 49 722.16797 52 [777.83203] 68 [556.15234 610.83984 556.15234 610.83984 556.15234 333.00781 610.83984 610.83984 277.83203 0 0 277.83203 889.16016] 81 84 610.83984 85 [389.16016 556.15234 333.00781 610.83984 556.15234 0 556.15234]]')
        );
    }
}
