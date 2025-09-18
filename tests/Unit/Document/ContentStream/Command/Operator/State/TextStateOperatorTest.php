<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\ContentStream\Command\Operator\State;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\TextStateOperator;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TextState;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\ExtendedDictionaryKey;

#[CoversClass(TextStateOperator::class)]
class TextStateOperatorTest extends TestCase {
    public function testApplyToTextState(): void {
        static::assertEquals(
            new TextState(new ExtendedDictionaryKey('F0'), 12, 0, 0, 100, 0, 0, 0),
            TextStateOperator::FONT_SIZE->applyToTextState('/F0 12', null)
        );
        static::assertEquals(
            new TextState(new ExtendedDictionaryKey('F0'), -12, 0, 0, 100, 0, 0, 0),
            TextStateOperator::FONT_SIZE->applyToTextState('/F0 -12', null)
        );
        static::assertEquals(
            new TextState(new ExtendedDictionaryKey('F2+0'), 12, 0, 0, 100, 0, 0, 0),
            TextStateOperator::FONT_SIZE->applyToTextState('/F2+0 12', null)
        );
    }
}
