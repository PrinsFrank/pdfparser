<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\CMap\ToUnicode;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\CMap\ToUnicode\BFChar;
use PrinsFrank\PdfParser\Document\CMap\ToUnicode\BFRange;
use PrinsFrank\PdfParser\Document\CMap\ToUnicode\ToUnicodeCMap;
use PrinsFrank\PdfParser\Document\CMap\ToUnicode\ToUnicodeCMapParser;
use PrinsFrank\PdfParser\Stream\InMemoryStream;

#[CoversClass(ToUnicodeCMapParser::class)]
class ToUnicodeCMapParserTest extends TestCase {
    /** Example 2 from 9.10.3 */
    public function testParseExample2(): void {
        $stream = new InMemoryStream(
            <<<EOD
            /CIDInit /ProcSet findresource begin
            12 dict begin
            begincmap
            /CIDSystemInfo
            << /Registry ( Adobe )
            /Ordering ( UCS )
            /Supplement 0
            >> def
            /CMapName /Adobe−Identity−UCS def
            /CMapType 2 def
            1 begincodespacerange
            < 0000 > < FFFF >
            endcodespacerange
            2 beginbfrange
            < 0000 > < 005E > < 0020 >
            < 005F > < 0061 > [ < 00660066 > < 00660069 > < 00660066006C > ]
            endbfrange
            1 beginbfchar
            <3A51> <D840DC3E>
            endbfchar
            endcmap
            CMapName currentdict /CMap defineresource pop
            end
            end
            EOD
        );
        static::assertEquals(
            new ToUnicodeCMap(
                0x0000,
                0xFFFF,
                2,
                new BFRange(0x0000, 0x005E, [0x0020]),
                new BFRange(0x005F, 0x0061, [0x00660066, 0x00660069, 0x00660066006C]),
                new BFChar(0x3A51, 0xD840DC3E),
            ),
            ToUnicodeCMapParser::parse($stream, 0, $stream->getSizeInBytes())
        );
    }
}
