<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Text;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\ContentStream\Command\ContentStreamCommand;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\Object\CompatibilityOperator;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\Object\InlineImageOperator;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\Object\MarkedContentOperator;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\Object\TextObjectOperator;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\ClippingPathOperator;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\ColorOperator;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\GraphicsStateOperator;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\PathConstructionOperator;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\PathPaintingOperator;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\TextPositioningOperator;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\TextShowingOperator;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\TextStateOperator;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\Type3FontOperator;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\XObjectOperator;
use PrinsFrank\PdfParser\Document\ContentStream\ContentStream;
use PrinsFrank\PdfParser\Document\ContentStream\ContentStreamParser;
use PrinsFrank\PdfParser\Document\ContentStream\Object\TextObject;
use PrinsFrank\PdfParser\Exception\RuntimeException;

#[CoversClass(ContentStreamParser::class)]
class ContentStreamParserTest extends TestCase {
    public function testParseText(): void {
        static::assertEquals(
            new ContentStream(
                (new TextObject())
                    ->addContentStreamCommand(new ContentStreamCommand(TextStateOperator::FONT_SIZE, '/F1 24'))
                    ->addContentStreamCommand(new ContentStreamCommand(TextPositioningOperator::MOVE_OFFSET, '100 100'))
                    ->addContentStreamCommand(new ContentStreamCommand(TextShowingOperator::SHOW, '( Hello World )'))
            ),
            ContentStreamParser::parse(
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

    #[DataProvider('provideOperators')]
    public function testGetOperator(CompatibilityOperator|InlineImageOperator|MarkedContentOperator|TextObjectOperator|ClippingPathOperator|ColorOperator|GraphicsStateOperator|PathConstructionOperator|PathPaintingOperator|TextPositioningOperator|TextShowingOperator|TextStateOperator|Type3FontOperator|XObjectOperator $enumCase): void {
        static::assertSame(
            $enumCase,
            match (strlen($enumCase->value)) {
                1 => ContentStreamParser::getOperator($enumCase->value, null, null, null),
                2 => ContentStreamParser::getOperator(substr($enumCase->value, 1, 1), substr($enumCase->value, 0, 1), null, null),
                3 => ContentStreamParser::getOperator(substr($enumCase->value, 2, 1), substr($enumCase->value, 1, 1), substr($enumCase->value, 0, 1), null),
            }
        );
    }

    #[DataProvider('provideOperators')]
    public function testGetOperatorWithLeadingEscapeValue(CompatibilityOperator|InlineImageOperator|MarkedContentOperator|TextObjectOperator|ClippingPathOperator|ColorOperator|GraphicsStateOperator|PathConstructionOperator|PathPaintingOperator|TextPositioningOperator|TextShowingOperator|TextStateOperator|Type3FontOperator|XObjectOperator $enumCase): void {
        if (in_array($enumCase, [TextPositioningOperator::MOVE_OFFSET, TextShowingOperator::SHOW, TextShowingOperator::SHOW_ARRAY, TextStateOperator::WORD_SPACE, GraphicsStateOperator::SetIntent, ColorOperator::SetStrokingColorDeviceRGB, ColorOperator::SetColorDeviceRGB, ColorOperator::SetName, ColorOperator::SetNameNonStroking, ColorOperator::SetColor, ColorOperator::SetColorParams, GraphicsStateOperator::ModifyCurrentTransformationMatrix, GraphicsStateOperator::SetDictName, TextPositioningOperator::SET_MATRIX, TextStateOperator::CHAR_SPACE, TextStateOperator::FONT_SIZE, TextStateOperator::RISE], true)) {
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
                    ColorOperator::SetName => PathPaintingOperator::STROKE,
                    ColorOperator::SetNameNonStroking => PathPaintingOperator::CLOSE_STROKE,
                    ColorOperator::SetColor => PathConstructionOperator::CURVE_BEZIER_123,
                    ColorOperator::SetColorParams => PathPaintingOperator::END,
                    GraphicsStateOperator::ModifyCurrentTransformationMatrix => PathConstructionOperator::MOVE,
                    GraphicsStateOperator::SetDictName => PathPaintingOperator::CLOSE_STROKE,
                    TextPositioningOperator::SET_MATRIX => PathConstructionOperator::MOVE,
                    TextStateOperator::CHAR_SPACE => PathConstructionOperator::CURVE_BEZIER_123,
                    TextStateOperator::FONT_SIZE => PathPaintingOperator::FILL,
                    TextStateOperator::RISE => PathPaintingOperator::CLOSE_STROKE,
                },
                match (strlen($enumCase->value)) {
                    1 => ContentStreamParser::getOperator($enumCase->value, '\\', null, null),
                    2 => ContentStreamParser::getOperator(substr($enumCase->value, 1, 1), substr($enumCase->value, 0, 1), '\\', null),
                    3 => ContentStreamParser::getOperator(substr($enumCase->value, 2, 1), substr($enumCase->value, 1, 1), substr($enumCase->value, 0, 1), '\\'),
                }
            );
        } else {
            static::assertNull(
                match (strlen($enumCase->value)) {
                    1 => ContentStreamParser::getOperator($enumCase->value, '\\', null, null),
                    2 => ContentStreamParser::getOperator(substr($enumCase->value, 1, 1), substr($enumCase->value, 0, 1), '\\', null),
                    3 => ContentStreamParser::getOperator(substr($enumCase->value, 2, 1), substr($enumCase->value, 1, 1), substr($enumCase->value, 0, 1), '\\'),
                }
            );
        }
    }

    /** @return iterable<string, array{0: CompatibilityOperator|InlineImageOperator|MarkedContentOperator|TextObjectOperator|ClippingPathOperator|ColorOperator|GraphicsStateOperator|PathConstructionOperator|PathPaintingOperator|TextPositioningOperator|TextShowingOperator|TextStateOperator|Type3FontOperator|XObjectOperator}> */
    public static function provideOperators(): iterable {
        foreach ([CompatibilityOperator::class, InlineImageOperator::class, MarkedContentOperator::class, TextObjectOperator::class, ClippingPathOperator::class, ColorOperator::class, GraphicsStateOperator::class, PathConstructionOperator::class, PathPaintingOperator::class, TextPositioningOperator::class, TextShowingOperator::class, TextStateOperator::class, Type3FontOperator::class, XObjectOperator::class] as $enumClass) {
            foreach ($enumClass::cases() as $enumCase) {
                if (($lastNamespacePos = strrpos($enumClass, '\\')) === false) {
                    throw new RuntimeException();
                }

                yield substr($enumClass, $lastNamespacePos + 1) . '::' . $enumCase->name => [$enumCase];
            }
        }
    }
}
