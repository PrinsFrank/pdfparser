<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Text;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Text\OperatorString\TextPositioningOperator;
use PrinsFrank\PdfParser\Document\Text\OperatorString\TextShowingOperator;
use PrinsFrank\PdfParser\Document\Text\OperatorString\TextStateOperator;
use PrinsFrank\PdfParser\Document\Text\TextObject;
use PrinsFrank\PdfParser\Document\Text\TextObjectCollection;
use PrinsFrank\PdfParser\Document\Text\TextOperator;
use PrinsFrank\PdfParser\Document\Text\TextParser;

#[CoversClass(TextParser::class)]
class TextParserTest extends TestCase {
    public function testParseTextSingleObject(): void {
        static::assertEquals(
            new TextObjectCollection(
                (new TextObject())
                    ->addTextOperator(new TextOperator(TextStateOperator::FONT_SIZE, '/F1 24'))
                    ->addTextOperator(new TextOperator(TextPositioningOperator::MOVE_OFFSET, '100 100'))
                    ->addTextOperator(new TextOperator(TextShowingOperator::SHOW, '( Hello World )'))
            ),
            TextParser::parse(
                <<<EOD
                BT
                /F1 24 Tf
                100 100 Td
                ( Hello World ) Tj
                ET
                EOD
            )
        );
    }

    public function testParseTextMultiObject(): void {
        static::assertEquals(
            new TextObjectCollection(
                (new TextObject())
                    ->addTextOperator(new TextOperator(TextStateOperator::FONT_SIZE, '/F1 24'))
                    ->addTextOperator(new TextOperator(TextPositioningOperator::MOVE_OFFSET, '100 100'))
                    ->addTextOperator(new TextOperator(TextShowingOperator::SHOW, '( Hello World )')),
                (new TextObject())
                    ->addTextOperator(new TextOperator(TextStateOperator::FONT_SIZE, '/F1 24'))
                    ->addTextOperator(new TextOperator(TextPositioningOperator::MOVE_OFFSET, '100 100'))
                    ->addTextOperator(new TextOperator(TextShowingOperator::SHOW, '( Hello World )'))
            ),
            TextParser::parse(
                <<<EOD
                BT
                /F1 24 Tf
                100 100 Td
                ( Hello World ) Tj
                ET
                BT
                /F1 24 Tf
                100 100 Td
                ( Hello World ) Tj
                ET
                EOD
            )
        );
    }

    public function testParseTextWithoutTextOperatorBegin(): void {
        static::assertEquals(
            new TextObjectCollection(
                (new TextObject())
                    ->addTextOperator(new TextOperator(TextStateOperator::FONT_SIZE, '/F1 24'))
                    ->addTextOperator(new TextOperator(TextPositioningOperator::MOVE_OFFSET, '100 100'))
                    ->addTextOperator(new TextOperator(TextShowingOperator::SHOW, '( Hello World )'))
            ),
            TextParser::parse(
                <<<EOD
                /F1 24 Tf
                100 100 Td
                ( Hello World ) Tj
                ET
                EOD
            )
        );
    }

    public function testParseTextWithoutTextOperatorEnd(): void {
        static::assertEquals(
            new TextObjectCollection(
                (new TextObject())
                    ->addTextOperator(new TextOperator(TextStateOperator::FONT_SIZE, '/F1 24'))
                    ->addTextOperator(new TextOperator(TextPositioningOperator::MOVE_OFFSET, '100 100'))
                    ->addTextOperator(new TextOperator(TextShowingOperator::SHOW, '( Hello World )'))
            ),
            TextParser::parse(
                <<<EOD
                BT
                /F1 24 Tf
                100 100 Td
                ( Hello World ) Tj
                EOD
            )
        );
    }
}
