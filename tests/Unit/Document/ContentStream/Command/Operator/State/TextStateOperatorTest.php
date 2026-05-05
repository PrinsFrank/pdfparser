<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\ContentStream\Command\Operator\State;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\TextStateOperator;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TextState;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\ExtendedDictionaryKey;
use PrinsFrank\PdfParser\Exception\ParseFailureException;

#[CoversClass(TextStateOperator::class)]
class TextStateOperatorTest extends TestCase {
    public function testApplyToTextState(): void {
        static::assertEquals(
            new TextState(new ExtendedDictionaryKey('F0'), 12, 0, 0, 100, 0, 0, 0),
            TextStateOperator::FONT_SIZE->applyToTextState('/F0 12', null),
        );
        static::assertEquals(
            new TextState(new ExtendedDictionaryKey('F0'), -12, 0, 0, 100, 0, 0, 0),
            TextStateOperator::FONT_SIZE->applyToTextState('/F0 -12', null),
        );
        static::assertEquals(
            new TextState(new ExtendedDictionaryKey('F2+0'), 12, 0, 0, 100, 0, 0, 0),
            TextStateOperator::FONT_SIZE->applyToTextState('/F2+0 12', null),
        );
    }

    #[DataProvider('validScaleOperandsProvider')]
    public function testApplyToTextStateWithValidScaleOperand(string $operand, float $expectedScale): void {
        static::assertEquals(
            new TextState(null, null, 0, 0, $expectedScale, 0, 0, 0),
            TextStateOperator::SCALE->applyToTextState($operand, null),
        );
    }

    /** @return iterable<string, array{0: string, 1: float}> */
    public static function validScaleOperandsProvider(): iterable {
        yield 'trailing zero decimal' => ['83.977440', 83.97744];
        yield 'zero fraction decimal' => ['90.000000', 90.0];
        yield 'integer' => ['100', 100.0];
        yield 'positive sign without leading zero' => ['+.5', 0.5];
        yield 'negative without leading zero' => ['-.002', -0.002];
    }

    #[DataProvider('invalidScaleOperandsProvider')]
    public function testApplyToTextStateWithInvalidScaleOperand(string $operand): void {
        $this->expectException(ParseFailureException::class);
        $this->expectExceptionMessage(sprintf('Invalid scale operand "%s" for scale operator', $operand));

        TextStateOperator::SCALE->applyToTextState($operand, null);
    }

    /** @return iterable<string, array{0: string}> */
    public static function invalidScaleOperandsProvider(): iterable {
        yield 'trailing text' => ['83.977440 foo'];
        yield 'scientific notation' => ['1e2'];
        yield 'dot only' => ['.'];
        yield 'empty' => [''];
        yield 'whitespace only' => ['   '];
    }
}
