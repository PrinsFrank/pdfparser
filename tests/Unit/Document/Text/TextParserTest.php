<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Text;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Text\OperatorString\ColorOperator;
use PrinsFrank\PdfParser\Document\Text\OperatorString\GraphicsStateOperator;
use PrinsFrank\PdfParser\Document\Text\OperatorString\TextPositioningOperator;
use PrinsFrank\PdfParser\Document\Text\OperatorString\TextShowingOperator;
use PrinsFrank\PdfParser\Document\Text\OperatorString\TextStateOperator;
use PrinsFrank\PdfParser\Document\Text\TextObject;
use PrinsFrank\PdfParser\Document\Text\TextObjectCollection;
use PrinsFrank\PdfParser\Document\Text\TextOperator;
use PrinsFrank\PdfParser\Document\Text\TextParser;
use PrinsFrank\PdfParser\Exception\RuntimeException;

#[CoversClass(TextParser::class)]
class TextParserTest extends TestCase {
    public function testParseText(): void {
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

    #[DataProvider('provideTextOperators')]
    public function testGetOperator(TextPositioningOperator|TextShowingOperator|TextStateOperator|GraphicsStateOperator|ColorOperator $enumCase): void {
        static::assertSame(
            $enumCase,
            match (strlen($enumCase->value)) {
                1 => TextParser::getOperator($enumCase->value, null, null, null),
                2 => TextParser::getOperator(substr($enumCase->value, 1, 1), substr($enumCase->value, 0, 1), null, null),
                3 => TextParser::getOperator(substr($enumCase->value, 2, 1), substr($enumCase->value, 1, 1), substr($enumCase->value, 0, 1), null),
            }
        );
    }

    #[DataProvider('provideTextOperators')]
    public function testGetOperatorWithLeadingEscapeValue(TextPositioningOperator|TextShowingOperator|TextStateOperator|GraphicsStateOperator|ColorOperator $enumCase): void {
        if (in_array($enumCase, [TextPositioningOperator::MOVE_OFFSET, TextShowingOperator::SHOW, TextShowingOperator::SHOW_ARRAY, TextStateOperator::WORD_SPACE, GraphicsStateOperator::SetIntent, ColorOperator::SetStrokingColorDeviceRGB, ColorOperator::SetColorDeviceRGB], true)) {
            // If a enum case matches, but there is an escape character in front, it will match partially a different enum case or none at all
            static::assertSame(
                match ($enumCase) {
                    TextPositioningOperator::MOVE_OFFSET => GraphicsStateOperator::SetLineDash,
                    TextShowingOperator::SHOW => GraphicsStateOperator::SetLineJoin,
                    TextShowingOperator::SHOW_ARRAY => GraphicsStateOperator::SetLineCap,
                    TextStateOperator::WORD_SPACE => GraphicsStateOperator::SetLineWidth,
                    GraphicsStateOperator::SetIntent => GraphicsStateOperator::SetFlatness,
                    ColorOperator::SetStrokingColorDeviceRGB => ColorOperator::SetStrokingColorSpace,
                    ColorOperator::SetColorDeviceRGB => ColorOperator::SetColorSpace,
                },
                match (strlen($enumCase->value)) {
                    1 => TextParser::getOperator($enumCase->value, '\\', null, null),
                    2 => TextParser::getOperator(substr($enumCase->value, 1, 1), substr($enumCase->value, 0, 1), '\\', null),
                }
            );
        } else {
            static::assertNull(
                match (strlen($enumCase->value)) {
                    1 => TextParser::getOperator($enumCase->value, '\\', null, null),
                    2 => TextParser::getOperator(substr($enumCase->value, 1, 1), substr($enumCase->value, 0, 1), '\\', null),
                    3 => TextParser::getOperator(substr($enumCase->value, 2, 1), substr($enumCase->value, 1, 1), substr($enumCase->value, 0, 1), '\\'),
                }
            );
        }
    }

    /** @return iterable<string, array{0: TextPositioningOperator|TextShowingOperator|TextStateOperator|GraphicsStateOperator|ColorOperator}> */
    public static function provideTextOperators(): iterable {
        foreach ([TextPositioningOperator::class, TextShowingOperator::class, TextStateOperator::class, GraphicsStateOperator::class, ColorOperator::class] as $enumClass) {
            foreach ($enumClass::cases() as $enumCase) {
                if (($lastNamespacePos = strrpos($enumClass, '\\')) === false) {
                    throw new RuntimeException();
                }

                yield substr($enumClass, $lastNamespacePos + 1) . '::' . $enumCase->name => [$enumCase];
            }
        }
    }
}
