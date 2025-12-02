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
use PrinsFrank\PdfParser\Document\Object\Decorator\GenericObject;
use PrinsFrank\PdfParser\Exception\RuntimeException;
use PrinsFrank\PdfParser\Stream\FileStream;

#[CoversClass(ContentStreamParser::class)]
class ContentStreamParserTest extends TestCase {
    public function testParse(): void {
        $contentStream = FileStream::fromString(
            <<<EOD
                BT
                /F1 24 Tf
                100 100 Td
                ( Hello World ) Tj
                ET
            EOD
        );
        $decoratedObject = $this->createMock(GenericObject::class);
        $decoratedObject->expects(self::once())->method('getStream')->willReturn($contentStream);
        static::assertEquals(
            new ContentStream(
                (new TextObject())
                    ->addContentStreamCommand(new ContentStreamCommand(TextStateOperator::FONT_SIZE, '/F1 24'))
                    ->addContentStreamCommand(new ContentStreamCommand(TextPositioningOperator::MOVE_OFFSET, '100 100'))
                    ->addContentStreamCommand(new ContentStreamCommand(TextShowingOperator::SHOW, '( Hello World )'))
            ),
            ContentStreamParser::parse(
                [$decoratedObject],
            )
        );
    }

    public function testParseWithPrecedingArtifactDictionary(): void {
        $contentStream = FileStream::fromString(
            <<<EOD
                /Artifact <</Attached [/Bottom]>>
                BT
                /F1 24 Tf
                100 100 Td
                ( Hello World ) Tj
                ET
            EOD
        );
        $decoratedObject = $this->createMock(GenericObject::class);
        $decoratedObject->expects(self::once())->method('getStream')->willReturn($contentStream);
        static::assertEquals(
            new ContentStream(
                (new TextObject())
                    ->addContentStreamCommand(new ContentStreamCommand(TextStateOperator::FONT_SIZE, '/F1 24'))
                    ->addContentStreamCommand(new ContentStreamCommand(TextPositioningOperator::MOVE_OFFSET, '100 100'))
                    ->addContentStreamCommand(new ContentStreamCommand(TextShowingOperator::SHOW, '( Hello World )'))
            ),
            ContentStreamParser::parse(
                [$decoratedObject],
            )
        );
    }

    public function testParseWithOperatorOnNewLine(): void {
        $contentStream = FileStream::fromString(
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
        );
        $decoratedObject = $this->createMock(GenericObject::class);
        $decoratedObject->expects(self::once())->method('getStream')->willReturn($contentStream);
        static::assertEquals(
            new ContentStream(
                (new TextObject())
                    ->addContentStreamCommand(new ContentStreamCommand(TextStateOperator::FONT_SIZE, '/F1 24'))
                    ->addContentStreamCommand(new ContentStreamCommand(TextPositioningOperator::MOVE_OFFSET, '100 100'))
                    ->addContentStreamCommand(new ContentStreamCommand(TextShowingOperator::SHOW, '( Hello World )'))
            ),
            ContentStreamParser::parse([$decoratedObject])
        );
    }

    public function testParseWithArrayDelimiterInStringLiteral(): void {
        $contentStream = FileStream::fromString(
            <<<EOD
            BT
            ([Hello) Tj
            (World]) Tj
            ET
            EOD
        );
        $decoratedObject = $this->createMock(GenericObject::class);
        $decoratedObject->expects(self::once())->method('getStream')->willReturn($contentStream);
        static::assertEquals(
            new ContentStream(
                (new TextObject())
                    ->addContentStreamCommand(new ContentStreamCommand(TextShowingOperator::SHOW, '([Hello)'))
                    ->addContentStreamCommand(new ContentStreamCommand(TextShowingOperator::SHOW, '(World])'))
            ),
            ContentStreamParser::parse([$decoratedObject])
        );
    }

    public function testParseWithEscapedStringLiteralDelimiterInStringLiteral(): void {
        $contentStream = FileStream::fromString(
            <<<EOD
            BT
            (Hel\)lo) Tj
            ET
            EOD
        );
        $decoratedObject = $this->createMock(GenericObject::class);
        $decoratedObject->expects(self::once())->method('getStream')->willReturn($contentStream);
        static::assertEquals(
            new ContentStream(
                (new TextObject())
                    ->addContentStreamCommand(new ContentStreamCommand(TextShowingOperator::SHOW, '(Hel\)lo)'))
            ),
            ContentStreamParser::parse([$decoratedObject])
        );
    }

    public function testParseShowArraySyntax(): void {
        $contentStream = FileStream::fromString(
            <<<EOD
            BT
            [(F)-1(O)32(O)] TJ
            ET
            EOD
        );
        $decoratedObject = $this->createMock(GenericObject::class);
        $decoratedObject->expects(self::once())->method('getStream')->willReturn($contentStream);
        static::assertEquals(
            new ContentStream(
                (new TextObject())
                    ->addContentStreamCommand(new ContentStreamCommand(TextShowingOperator::SHOW_ARRAY, '[(F)-1(O)32(O)]'))
            ),
            ContentStreamParser::parse([$decoratedObject])
        );
    }

    public function testParseShowArraySyntaxWithArrayDelimiterInStringLiteral(): void {
        $contentStream = FileStream::fromString(
            <<<EOD
            BT
            [(F)-1([O)32(O])] TJ
            ET
            EOD
        );
        $decoratedObject = $this->createMock(GenericObject::class);
        $decoratedObject->expects(self::once())->method('getStream')->willReturn($contentStream);
        static::assertEquals(
            new ContentStream(
                (new TextObject())
                    ->addContentStreamCommand(new ContentStreamCommand(TextShowingOperator::SHOW_ARRAY, '[(F)-1([O)32(O])]'))
            ),
            ContentStreamParser::parse([$decoratedObject])
        );
    }

    public function testParseWithOperatorInTextOperand(): void {
        $contentStream = FileStream::fromString(
            <<<EOD
            BT
            /Tc1.0 1 Tf
            ET
            EOD
        );
        $decoratedObject = $this->createMock(GenericObject::class);
        $decoratedObject->expects(self::once())->method('getStream')->willReturn($contentStream);
        static::assertEquals(
            new ContentStream(
                (new TextObject())
                    ->addContentStreamCommand(new ContentStreamCommand(TextStateOperator::FONT_SIZE, '/Tc1.0 1'))
            ),
            ContentStreamParser::parse([$decoratedObject])
        );
    }

    public function testParseWithContentOutsideOfTextObject(): void {
        $contentStream = FileStream::fromString(
            <<<EOD
            0 J
            /F1 7 Tf
            BT
            (Hello) Tj
            (World) Tj
            ET
            EOD
        );
        $decoratedObject = $this->createMock(GenericObject::class);
        $decoratedObject->expects(self::once())->method('getStream')->willReturn($contentStream);
        static::assertEquals(
            new ContentStream(
                new ContentStreamCommand(GraphicsStateOperator::SetLineCap, '0'),
                new ContentStreamCommand(TextStateOperator::FONT_SIZE, '/F1 7'),
                (new TextObject())
                    ->addContentStreamCommand(new ContentStreamCommand(TextShowingOperator::SHOW, '(Hello)'))
                    ->addContentStreamCommand(new ContentStreamCommand(TextShowingOperator::SHOW, '(World)'))
            ),
            ContentStreamParser::parse([$decoratedObject])
        );
    }

    public function testParseWithMultibyteEscapedStringLiteralDelimiterInStringLiteral(): void {
        $contentStream = FileStream::fromString('BT (Hel' . chr(233) . '\)lo) Tj ET');
        $decoratedObject = $this->createMock(GenericObject::class);
        $decoratedObject->expects(self::once())->method('getStream')->willReturn($contentStream);
        static::assertEquals(
            new ContentStream(
                (new TextObject())
                    ->addContentStreamCommand(new ContentStreamCommand(TextShowingOperator::SHOW, '(Hel' . chr(233) . '\)lo)'))
            ),
            ContentStreamParser::parse([$decoratedObject])
        );

        $contentStream = FileStream::fromString('BT (Hel' . chr(233) . chr(108) . '\)lo) Tj ET');
        $decoratedObject = $this->createMock(GenericObject::class);
        $decoratedObject->expects(self::once())->method('getStream')->willReturn($contentStream);
        static::assertEquals(
            new ContentStream(
                (new TextObject())
                    ->addContentStreamCommand(new ContentStreamCommand(TextShowingOperator::SHOW, '(Hel' . chr(233) . chr(108) . '\)lo)'))
            ),
            ContentStreamParser::parse([$decoratedObject])
        );

        $contentStream = FileStream::fromString('BT (Hel' . chr(233) . chr(108) . chr(58) . '\)lo) Tj ET');
        $decoratedObject = $this->createMock(GenericObject::class);
        $decoratedObject->expects(self::once())->method('getStream')->willReturn($contentStream);
        static::assertEquals(
            new ContentStream(
                (new TextObject())
                    ->addContentStreamCommand(new ContentStreamCommand(TextShowingOperator::SHOW, '(Hel' . chr(233) . chr(108) . chr(58) . '\)lo)'))
            ),
            ContentStreamParser::parse([$decoratedObject])
        );
    }

    public function testParseWithOperatorInResourceName(): void {
        $contentStream = FileStream::fromString(
            <<<EOD
            BT
            /Helvetica-2000805986 24 Tf
            ET
            EOD
        );
        $decoratedObject = $this->createMock(GenericObject::class);
        $decoratedObject->expects(self::once())->method('getStream')->willReturn($contentStream);
        static::assertEquals(
            new ContentStream(
                (new TextObject())
                    ->addContentStreamCommand(new ContentStreamCommand(TextStateOperator::FONT_SIZE, '/Helvetica-2000805986 24'))
            ),
            ContentStreamParser::parse([$decoratedObject])
        );
    }

    public function testParseWithResourceNameFollowedByLineEnding(): void {
        $contentStream = FileStream::fromString(
            <<<EOD
            BT
            /Artifact
            ET
            EOD
        );
        $decoratedObject = $this->createMock(GenericObject::class);
        $decoratedObject->expects(self::once())->method('getStream')->willReturn($contentStream);
        static::assertEquals(
            new ContentStream(
                (new TextObject())
            ),
            ContentStreamParser::parse([$decoratedObject])
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
