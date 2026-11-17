<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Extraction\TextGrouping;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\PositionedTextElement;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TextSegment\TextSegment;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TextState;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TransformationMatrix;
use PrinsFrank\PdfParser\Document\Dictionary\Decorator\Font;
use PrinsFrank\PdfParser\Document\Dictionary\Decorator\Page;
use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\LiteralStringValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\NameObjectStringValue;
use PrinsFrank\PdfParser\Extraction\TextGrouping\ColumnGrouping\ColumnGroupingStrategy;
use PrinsFrank\PdfParser\Extraction\TextGrouping\LineGrouping\TextOverlapStrategy;

#[CoversClass(ColumnGroupingStrategy::class)]
class ColumnGroupingStrategyTest extends TestCase {
    public function testColumnsAtExactElementEdgesKeepHeaderAndFooter(): void {
        $header = self::element(0, 700, 500);
        $footer = self::element(0, 0, 500);
        $elements = [$header];
        $leftColumn = $rightColumn = [];
        for ($line = 0; $line < 25; $line++) {
            $elements[] = $leftColumn[] = self::element(0, 650 - $line * 20, 200);
            $elements[] = $rightColumn[] = self::element(300, 650 - $line * 20, 200);
        }
        $elements[] = $footer;
        $fonts = ['F1' => new Font(new Dictionary(null))];

        static::assertSame(
            [[$header], $leftColumn, $rightColumn, [$footer]],
            ColumnGroupingStrategy::group($elements, new Page(new Dictionary(null)), $fonts),
        );
    }

    public function testEarlierWideElementStillCrossesGutterAfterLaterShortElement(): void {
        $elements = [];
        for ($line = 0; $line < 30; $line++) {
            $elements[] = self::element(0, 650 - $line * 20, 320);
            $elements[] = self::element(100, 650 - $line * 20, 20);
            $elements[] = self::element(300, 650 - $line * 20, 200);
        }
        $fonts = ['F1' => new Font(new Dictionary(null))];

        static::assertSame([$elements], ColumnGroupingStrategy::group($elements, new Page(new Dictionary(null)), $fonts));
    }

    public function testShortPagePreservesItsElementOrder(): void {
        $first = self::element(0, 100, 20);
        $second = self::element(0, 80, 20);
        $fonts = ['F1' => new Font(new Dictionary(null))];
        static::assertSame([[$second, $first]], ColumnGroupingStrategy::group([$second, $first], new Page(new Dictionary(null)), $fonts));
    }

    public function testLineSortKeepsEqualCoordinatesInInputOrder(): void {
        $first = self::element(10, 100, 20);
        $second = self::element(10, 100, 30);
        $right = self::element(50, 100, 20);
        $lower = self::element(10, 50, 20);
        static::assertSame(
            [[$second, $first, $right], [$lower]],
            iterator_to_array(TextOverlapStrategy::group([$lower, $right, $second, $first]), false),
        );
        static::assertSame(
            [[$first, $second, $right], [$lower]],
            iterator_to_array(TextOverlapStrategy::group([$first, $second, $right, $lower]), false),
        );
    }

    private static function element(float $x, float $y, float $width): PositionedTextElement {
        return new PositionedTextElement(
            [new TextSegment(new LiteralStringValue(''), -100 * $width)],
            new TransformationMatrix(1, 0, 0, 1, $x, $y),
            new TextState(new NameObjectStringValue('F1'), 10),
        );
    }
}
