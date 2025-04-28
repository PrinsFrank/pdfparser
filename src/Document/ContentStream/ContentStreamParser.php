<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream;

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
use PrinsFrank\PdfParser\Document\ContentStream\Object\TextObject;
use PrinsFrank\PdfParser\Exception\ParseFailureException;

/** @internal */
class ContentStreamParser {
    /** @throws ParseFailureException */
    public static function parse(string $contentStream): ContentStream {
        $operandBuffer = '';
        $content = [];
        $inArrayLevel = $inStringLevel = $inStringLiteralLevel = 0;
        $textObject = $previousChar = $secondToLastChar = $thirdToLastChar = null;
        foreach (($characters = mb_str_split($contentStream)) as $index => $char) {
            $operandBuffer .= $char;
            if ($char === '[' && $previousChar !== '\\') {
                $inArrayLevel++;
            } elseif ($char === '<' && $previousChar !== '\\') {
                $inStringLevel++;
            } elseif ($char === '(' && $previousChar !== '\\') {
                $inStringLiteralLevel++;
            } elseif ($inStringLevel > 0 || $inStringLiteralLevel > 0 || $inArrayLevel > 0) {
                if ($inStringLevel > 0 && $char === '>' && $previousChar !== '\\') {
                    $inStringLevel--;
                } elseif ($inStringLiteralLevel > 0 && $char === ')' && $previousChar !== '\\') {
                    $inStringLiteralLevel--;
                } elseif ($inArrayLevel > 0 && $char === ']' && $previousChar !== '\\') {
                    $inArrayLevel--;
                }
            } elseif ($char === 'T' && $previousChar === 'B') { // TextObjectOperator::BEGIN
                $operandBuffer = '';
                $textObject = new TextObject();
            } elseif ($char === 'T' && $previousChar === 'E') { // TextObjectOperator::END
                $operandBuffer = '';
                if ($textObject === null) {
                    throw new ParseFailureException('Encountered TextObjectOperator::END without preceding TextObjectOperator::BEGIN');
                }

                $content[] = $textObject;
                $textObject = null;
            } elseif ($char === 'C'
                && (($secondToLastChar === 'B' && ($previousChar === 'M' || $previousChar === 'D')) || ($secondToLastChar === 'E' && $previousChar === 'M'))) { // MarkedContentOperator::BeginMarkedContent, MarkedContentOperator::EndMarkedContent, MarkedContentOperator::BeginMarkedContentWithProperties
                $operandBuffer = '';
            } elseif (($operator = self::getOperator($char, $previousChar, $secondToLastChar, $thirdToLastChar)) !== null
                && self::getOperator($characters[$index + 1] ?? '', $char, $previousChar, $secondToLastChar) === null) { // Skip the current hit if the next iteration is also a valid operator
                $command = new ContentStreamCommand($operator, trim(substr($operandBuffer, 0, -strlen($operator->value))));
                if ($textObject !== null) {
                    $textObject->addContentStreamCommand($command);
                } else {
                    $content[] = $command;
                }
                $operandBuffer = '';
            }

            $thirdToLastChar = $secondToLastChar;
            $secondToLastChar = $previousChar;
            $previousChar = $char;
        }

        return new ContentStream(...$content);
    }

    /**
     * This method uses three maps instead of calling $enum::tryFrom for all possible enums
     * as operator retrieval happens possibly millions of times in a single file
     */
    public static function getOperator(string $currentChar, ?string $previousChar, ?string $secondToLastChar, ?string $thirdToLastChar): CompatibilityOperator|InlineImageOperator|MarkedContentOperator|TextObjectOperator|ClippingPathOperator|ColorOperator|GraphicsStateOperator|PathConstructionOperator|PathPaintingOperator|TextPositioningOperator|TextShowingOperator|TextStateOperator|Type3FontOperator|XObjectOperator|null {
        $threeLetterMatch = match ($secondToLastChar . $previousChar . $currentChar) {
            'BMC' => MarkedContentOperator::BeginMarkedContent,
            'BDC' => MarkedContentOperator::BeginMarkedContentWithProperties,
            'EMC' => MarkedContentOperator::EndMarkedContent,
            'SCN' => ColorOperator::SetStrokingParams,
            'scn' => ColorOperator::SetColorParams,
            default => null,
        };
        if ($threeLetterMatch !== null && !in_array($thirdToLastChar, ['\\', '/'], true)) {
            return $threeLetterMatch;
        }

        $twoLetterMatch = match ($previousChar . $currentChar) {
            'BX' => CompatibilityOperator::BeginCompatibilitySection,
            'EX' => CompatibilityOperator::EndCompatibilitySection,
            'BI' => InlineImageOperator::Begin,
            'ID' => InlineImageOperator::BeginImageData,
            'EI' => InlineImageOperator::End,
            'MD' => MarkedContentOperator::Tag,
            'DP' => MarkedContentOperator::TagProperties,
            'BT' => TextObjectOperator::BEGIN,
            'ET' => TextObjectOperator::END,
            'W*' => ClippingPathOperator::INTERSECT_EVEN_ODD,
            'CS' => ColorOperator::SetName,
            'cs' => ColorOperator::SetNameNonStroking,
            'SC' => ColorOperator::SetStrokingColor,
            'sc' => ColorOperator::SetColor,
            'RG' => ColorOperator::SetStrokingColorDeviceRGB,
            'rg' => ColorOperator::SetColorDeviceRGB,
            'cm' => GraphicsStateOperator::ModifyCurrentTransformationMatrix,
            'ri' => GraphicsStateOperator::SetIntent,
            'gs' => GraphicsStateOperator::SetDictName,
            're' => PathConstructionOperator::RECTANGLE,
            'f*' => PathPaintingOperator::FILL_EVEN_ODD,
            'B*' => PathPaintingOperator::FILL_STROKE_EVEN_ODD,
            'b*' => PathPaintingOperator::CLOSE_FILL_STROKE,
            'Td' => TextPositioningOperator::MOVE_OFFSET,
            'TD' => TextPositioningOperator::MOVE_OFFSET_LEADING,
            'Tm' => TextPositioningOperator::SET_MATRIX,
            'T*' => TextPositioningOperator::NEXT_LINE,
            'Tj' => TextShowingOperator::SHOW,
            'TJ' => TextShowingOperator::SHOW_ARRAY,
            'Tc' => TextStateOperator::CHAR_SPACE,
            'Tw' => TextStateOperator::WORD_SPACE,
            'Tz' => TextStateOperator::SCALE,
            'TL' => TextStateOperator::LEADING,
            'Tf' => TextStateOperator::FONT_SIZE,
            'Tr' => TextStateOperator::RENDER,
            'Ts' => TextStateOperator::RISE,
            'd0' => Type3FontOperator::SetWidth,
            'd1' => Type3FontOperator::SetWidthAndBoundingBox,
            'Do' => XObjectOperator::Paint,
            default => null,
        };
        if ($twoLetterMatch !== null && !in_array($secondToLastChar, ['\\', '/'], true)) {
            return $twoLetterMatch;
        }

        $oneLetterMatch = match ($currentChar) {
            'W' => ClippingPathOperator::INTERSECT,
            'G' => ColorOperator::SetStrokingColorSpace,
            'g' => ColorOperator::SetColorSpace,
            'K' => ColorOperator::SetStrokingColorDeviceCMYK,
            'k' => ColorOperator::SetColorDeviceCMYK,
            'q' => GraphicsStateOperator::SaveCurrentStateToStack,
            'Q' => GraphicsStateOperator::RestoreMostRecentStateFromStack,
            'w' => GraphicsStateOperator::SetLineWidth,
            'J' => GraphicsStateOperator::SetLineCap,
            'j' => GraphicsStateOperator::SetLineJoin,
            'M' => GraphicsStateOperator::SetMiterJoin,
            'd' => GraphicsStateOperator::SetLineDash,
            'i' => GraphicsStateOperator::SetFlatness,
            'm' => PathConstructionOperator::MOVE,
            'l' => PathConstructionOperator::LINE,
            'c' => PathConstructionOperator::CURVE_BEZIER_123,
            'v' => PathConstructionOperator::CURVE_BEZIER_23,
            'y' => PathConstructionOperator::CURVE_BEZIER_13,
            'h' => PathConstructionOperator::CLOSE,
            'S' => PathPaintingOperator::STROKE,
            's' => PathPaintingOperator::CLOSE_STROKE,
            'f' => PathPaintingOperator::FILL,
            'F' => PathPaintingOperator::FILL_DEPRECATED,
            'B' => PathPaintingOperator::FILL_STROKE,
            'n' => PathPaintingOperator::END,
            '\'' => TextShowingOperator::MOVE_SHOW,
            '"' => TextShowingOperator::MOVE_SHOW_SPACING,
            default => null,
        };

        if ($oneLetterMatch !== null && !in_array($previousChar, ['\\', '/'], true)) {
            return $oneLetterMatch;
        }

        return null;
    }
}
