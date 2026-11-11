<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Feature;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\PdfParser;

#[CoversClass(PdfParser::class)]
class FormResourceScopeTest extends TestCase {
    /** @return iterable<string, array{string, string, string}> */
    public static function outerContents(): iterable {
        yield 'only nested invocation' => ['/Inner Do', 'Nested text', ''];
        yield 'outer and nested text' => ['BT /F0 12 Tf 20 100 Td (Outer) Tj ET /Inner Do', "Outer\nNested text", ''];
        yield 'private FillSign metadata' => ['/Inner Do', 'Nested text', '/ADBE_FillSign << /Type /FillSignData /Text 1234567 /Size [55.044 14.641] /Nested << /Text 8 >> >>'];
    }

    #[DataProvider('outerContents')]
    public function testNestedFormsUseTheirOwnFonts(string $outerContent, string $expected, string $metadata): void {
        $pdf = self::pdf([
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 600 800] /Resources << /XObject << /Outer 5 0 R >> >> /Contents 4 0 R >>',
            self::stream('q 2 0 0 2 100 200 cm /Outer Do Q'),
            self::stream($outerContent, '/Type /XObject /Subtype /Form /BBox [0 0 600 800] /Resources << /Font << /F0 7 0 R >> /XObject << /Inner 6 0 R >> >>'),
            self::stream('BT /F1 12 Tf 20 40 Td (Nested text) Tj ET', '/Type /XObject /Subtype /Form /BBox [0 0 600 800] /Resources << /Font << /F1 7 0 R >> >> ' . $metadata),
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ]);
        $document = (new PdfParser())->parseString($pdf);
        static::assertSame($expected, $document->getText());
        static::assertStringContainsString('Nested text', $document->getMarkdown());
        $elements = $document->getPages()[0]->getPositionedTextElements();
        $nested = $elements[count($elements) - 1];
        static::assertSame(140.0, $nested->absoluteMatrix->offsetX);
        static::assertSame(280.0, $nested->absoluteMatrix->offsetY);
    }

    public function testSiblingFormsDoNotShareFontsWithTheSameResourceName(): void {
        $pdf = self::pdf([
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 300 300] /Resources << /Font << /F1 9 0 R >> /XObject << /AlphaForm 5 0 R /BetaForm 6 0 R >> >> /Contents 4 0 R >>',
            self::stream('/AlphaForm Do /BetaForm Do'),
            self::stream('BT /F1 12 Tf 20 100 Td (WWW) Tj ET', '/Type /XObject /Subtype /Form /BBox [0 0 300 300] /Resources << /Font << /F1 7 0 R >> >>'),
            self::stream('BT /F1 12 Tf 20 50 Td (WWW) Tj ET', '/Type /XObject /Subtype /Form /BBox [0 0 300 300] /Resources << /Font << /F1 8 0 R >> >>'),
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Courier >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Times-Roman >>',
        ]);
        $document = (new PdfParser())->parseString($pdf);
        $page = $document->getPages()[0];
        $elements = $page->getPositionedTextElements();
        static::assertCount(2, $elements);
        $fonts = [];
        static::assertSame('Helvetica', $elements[0]->getFont($page, $fonts)->getBaseFont());
        static::assertSame('Courier', $elements[1]->getFont($page, $fonts)->getBaseFont());
        static::assertEqualsWithDelta(33.984, $elements[0]->getRight($page, $fonts) - 20, .0001);
        static::assertEqualsWithDelta(21.6, $elements[1]->getRight($page, $fonts) - 20, .0001);
        static::assertSame("WWW\nWWW", $document->getText());
        static::assertSame("WWW\nWWW", trim($document->getMarkdown()));
    }

    public function testEmptyFormHasNoText(): void {
        $pdf = self::pdf([
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 300 300] /Resources << /XObject << /Example 5 0 R >> >> /Contents 4 0 R >>',
            self::stream('/Example Do'),
            self::stream('', '/Type /XObject /Subtype /Form /BBox [0 0 300 300]'),
        ]);
        $document = (new PdfParser())->parseString($pdf);
        static::assertSame('', $document->getText());
        static::assertSame('', trim($document->getMarkdown()));
    }

    private static function stream(string $content, string $dictionary = ''): string {
        return '<< /Length ' . strlen($content) . ' ' . $dictionary . " >>\nstream\n" . $content . "\nendstream";
    }

    /** @param list<string> $objects */
    private static function pdf(array $objects): string {
        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1) . " 0 obj\n" . $object . "\nendobj\n";
        }
        $xref = strlen($pdf);
        $size = count($objects) + 1;
        $pdf .= "xref\n0 " . $size . "\n0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }
        return $pdf . "trailer\n<< /Size " . $size . " /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF\n";
    }
}
