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

    public function testFromValueWithNewLines(): void {
        static::assertEquals(
            new CIDFontWidths(
                new ConsecutiveCIDWidth(1, [611.0, 722.0]),
                new RangeCIDWidth(3, 9, 556.0),
                new ConsecutiveCIDWidth(10, [667.0, 556.0, 556.0, 556.0, 278.0, 278.0, 556.0, 556.0, 611.0, 556.0, 222.0, 556.0, 556.0, 556.0, 556.0, 278.0, 556.0, 500.0, 222.0, 833.0, 278.0, 833.0, 500.0, 278.0, 333.0, 500.0, 1015.0, 556.0, 556.0, 500.0, 333.0, 278.0, 722.0, 778.0, 667.0, 278.0, 722.0, 667.0, 556.0, 191.0, 722.0, 722.0, 667.0, 667.0, 722.0, 400.0, 667.0, 667.0, 611.0, 778.0, 278.0, 889.0]),
                new RangeCIDWidth(62, 62, 333),
            ),
            CIDFontWidths::fromValue(
                <<<EOD
                [1[611 722]
                3 9 556
                10[667 556 556 556 278 278 556 556 611 556 222 556 556 556 556
                278 556 500 222 833 278 833 500 278 333 500 1015 556 556 500 333
                278 722 778 667 278 722 667 556 191 722 722 667 667 722 400 667
                667 611 778 278 889]
                62 62 333
                ]
                EOD
            ),
        );
    }
}
