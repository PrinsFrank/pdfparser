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
    public function testParse(): void {
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

    public function testParseWithOperatorOnNewLine(): void {
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
                /F1 24
                Tf
                100 100
                Td
                ( Hello World )
                Tj
                ET
                EOD
            )
        );
    }

    public function testParseWithArrayDelimiterInStringLiteral(): void {
        static::assertEquals(
            new ContentStream(
                (new TextObject())
                    ->addContentStreamCommand(new ContentStreamCommand(TextShowingOperator::SHOW, '([Hello)'))
                    ->addContentStreamCommand(new ContentStreamCommand(TextShowingOperator::SHOW, '(World])'))
            ),
            ContentStreamParser::parse(
                <<<EOD
                BT
                ([Hello) Tj
                (World]) Tj
                ET
                EOD
            )
        );
    }

    public function testParseWithEscapedStringLiteralDelimiterInStringLiteral(): void {
        static::assertEquals(
            new ContentStream(
                (new TextObject())
                    ->addContentStreamCommand(new ContentStreamCommand(TextShowingOperator::SHOW, '(Hel\)lo)'))
            ),
            ContentStreamParser::parse(
                <<<EOD
                BT
                (Hel\)lo) Tj
                ET
                EOD
            )
        );
    }

    public function testParseShowArraySyntax(): void {
        static::assertEquals(
            new ContentStream(
                (new TextObject())
                    ->addContentStreamCommand(new ContentStreamCommand(TextShowingOperator::SHOW_ARRAY, '[(F)-1(O)32(O)]'))
            ),
            ContentStreamParser::parse(
                <<<EOD
                BT
                [(F)-1(O)32(O)] TJ
                ET
                EOD
            )
        );
    }

    public function testParseShowArraySyntaxWithArrayDelimiterInStringLiteral(): void {
        static::assertEquals(
            new ContentStream(
                (new TextObject())
                    ->addContentStreamCommand(new ContentStreamCommand(TextShowingOperator::SHOW_ARRAY, '[(F)-1([O)32(O])]'))
            ),
            ContentStreamParser::parse(
                <<<EOD
                BT
                [(F)-1([O)32(O])] TJ
                ET
                EOD
            )
        );
    }

    public function testParseWithOperatorInTextOperand(): void {
        static::assertEquals(
            new ContentStream(
                (new TextObject())
                    ->addContentStreamCommand(new ContentStreamCommand(TextStateOperator::FONT_SIZE, '/Tc1.0 1'))
            ),
            ContentStreamParser::parse(
                <<<EOD
                BT
                /Tc1.0 1 Tf
                ET
                EOD
            )
        );
    }

    public function testParseWithContentOutsideOfTextObject(): void {
        static::assertEquals(
            new ContentStream(
                new ContentStreamCommand(GraphicsStateOperator::SetLineCap, '0'),
                new ContentStreamCommand(TextStateOperator::FONT_SIZE, '/F1 7'),
                (new TextObject())
                    ->addContentStreamCommand(new ContentStreamCommand(TextShowingOperator::SHOW, '(Hello)'))
                    ->addContentStreamCommand(new ContentStreamCommand(TextShowingOperator::SHOW, '(World)'))
            ),
            ContentStreamParser::parse(
                <<<EOD
                0 J
                /F1 7 Tf
                BT
                (Hello) Tj
                (World) Tj
                ET
                EOD
            )
        );
    }

    public function testParseWithMultibyteEscapedStringLiteralDelimiterInStringLiteral(): void {
        static::assertEquals(
            new ContentStream(
                (new TextObject())
                    ->addContentStreamCommand(new ContentStreamCommand(TextShowingOperator::SHOW, '(Hel' . chr(233) . '\)lo)'))
            ),
            ContentStreamParser::parse('BT (Hel' . chr(233) . '\)lo) Tj ET')
        );
        static::assertEquals(
            new ContentStream(
                (new TextObject())
                    ->addContentStreamCommand(new ContentStreamCommand(TextShowingOperator::SHOW, '(Hel' . chr(233) . chr(108) . '\)lo)'))
            ),
            ContentStreamParser::parse('BT (Hel' . chr(233) . chr(108) . '\)lo) Tj ET')
        );
        static::assertEquals(
            new ContentStream(
                (new TextObject())
                    ->addContentStreamCommand(new ContentStreamCommand(TextShowingOperator::SHOW, '(Hel' . chr(233) . chr(108) . chr(58) . '\)lo)'))
            ),
            ContentStreamParser::parse('BT (Hel' . chr(233) . chr(108) . chr(58) . '\)lo) Tj ET')
        );
    }

    public function testParseWithOperatorInResourceName(): void {
        static::assertEquals(
            new ContentStream(
                (new TextObject())
                    ->addContentStreamCommand(new ContentStreamCommand(TextStateOperator::FONT_SIZE, '/Helvetica-2000805986 24'))
            ),
            ContentStreamParser::parse(
                <<<EOD
                BT
                /Helvetica-2000805986 24 Tf
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
        static::assertNull(
            match (strlen($enumCase->value)) {
                1 => ContentStreamParser::getOperator($enumCase->value, '\\', null, null),
                2 => ContentStreamParser::getOperator(substr($enumCase->value, 1, 1), substr($enumCase->value, 0, 1), '\\', null),
                3 => ContentStreamParser::getOperator(substr($enumCase->value, 2, 1), substr($enumCase->value, 1, 1), substr($enumCase->value, 0, 1), '\\'),
            }
        );
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
