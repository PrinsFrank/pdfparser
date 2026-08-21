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
use PrinsFrank\PdfParser\Document\Object\Decorator\DecoratedObject;
use PrinsFrank\PdfParser\Exception\ParseFailureException;

/** @internal */
class ContentStreamParser {
    public const OPERATOR_CHARS = ['X' => true, 'x' => true, 'I' => true, 'D' => true, 'C' => true, 'T' => true, '*' => true, 'S' => true, 's' => true, 'N' => true, 'n' => true, 'G' => true, 'g' => true, 'K' => true, 'k' => true, 'm' => true, 'i' => true, 'e' => true, 'W' => true, 'w' => true, 'J' => true, 'j' => true, 'M' => true, 'd' => true, 'l' => true, 'c' => true, 'v' => true, 'y' => true, 'h' => true, 'f' => true, 'F' => true, 'B' => true, 'Q' => true, 'q' => true, '\'' => true, '"' => true, 'E' => true, 'P' => true, 'R' => true, 'r' => true, 'b' => true, 'z' => true, 'L' => true, 'o' => true, '1' => true, '0' => true];
    private const OPERATOR_MAP = [
        'BMC' => MarkedContentOperator::BeginMarkedContent,
        'BDC' => MarkedContentOperator::BeginMarkedContentWithProperties,
        'EMC' => MarkedContentOperator::EndMarkedContent,
        'SCN' => ColorOperator::SetStrokingParams,
        'scn' => ColorOperator::SetColorParams,
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
    ];

    /**
     * @param list<DecoratedObject> $contentsObjects
     * @throws ParseFailureException
     */
    public static function parse(array $contentsObjects): ContentStream {
        $content = [];
        $inComment = $inStringLiteral = $inResourceName = $inDictionary = $inInlineImage = false;
        $inArrayLevel = $inStringLevel = 0;
        $textObject = $previousChar = $secondToLastChar = $thirdToLastChar = $previousContentStream = $startPreviousOperandIndex = $startOfCommentOffset = $endCommentOffset = null;
        foreach ($contentsObjects as $contentsObject) {
            $startCurrentOperandIndex = 0;
            $contentStream = $contentsObject->getStream();
            $contentStreamSize = $contentStream->getSizeInBytes();
            $rawStream = $contentStream->toString();
            for ($index = 0; $index < $contentStreamSize; $index++) {
                $char = $rawStream[$index];
                if ($inComment === true) {
                    if ($char === "\r" || $char === "\n") {
                        $endCommentOffset = $index + 1;
                        $inComment = false;
                    }
                    continue;
                }

                if ($inInlineImage === true) {
                    if ($char === 'I' && $previousChar === 'E' && $secondToLastChar !== '\\') {
                        $inInlineImage = false;
                    }
                } elseif ($inStringLiteral === true) {
                    if ($char === ')' && $previousChar !== '\\') {
                        $inStringLiteral = false;
                    }
                } elseif ($inResourceName === true) {
                    if ($previousChar !== '\\' && ($char === ' ' || $char === '<' || $char === '(' || $char === '/' || $char === "\r" || $char === "\n")) {
                        $inResourceName = false;
                    }
                } elseif ($inDictionary === true) {
                    if ($char === '>' && $previousChar === '>' && $secondToLastChar !== '\\') {
                        $inDictionary = false;
                    }
                } elseif ($char === '%') {
                    $inComment = true;
                    $startOfCommentOffset = $index;
                    $previousChar = $secondToLastChar = $thirdToLastChar = null;
                    continue;
                } elseif ($char === '[' && $previousChar !== '\\') {
                    $inArrayLevel++;
                } elseif ($char === '<' && $previousChar === '<' && $secondToLastChar !== '\\') {
                    $inDictionary = true;
                } elseif ($char === '<' && $previousChar !== '\\' && $rawStream[$index + 1] !== '<') {
                    $inStringLevel++;
                } elseif ($char === '(' && $previousChar !== '\\') {
                    $inStringLiteral = true;
                } elseif ($char === '/' && $previousChar !== '\\') {
                    $inResourceName = true;
                } elseif ($inStringLevel > 0 || $inArrayLevel > 0) {
                    if ($inStringLevel > 0 && $char === '>' && $previousChar !== '\\') {
                        $inStringLevel--;
                    } elseif ($inArrayLevel > 0 && $char === ']' && $previousChar !== '\\') {
                        $inArrayLevel--;
                    }
                } elseif (isset(self::OPERATOR_CHARS[$char])) {
                    if ($char === 'T' && $previousChar === 'B') { // TextObjectOperator::BEGIN
                        $startCurrentOperandIndex = $index + 1;
                        $textObject = new TextObject();
                    } elseif ($char === 'T' && $previousChar === 'E') { // TextObjectOperator::END
                        $startCurrentOperandIndex = $index + 1;
                        if ($textObject !== null) {
                            $content[] = $textObject;
                            $textObject = null;
                        }
                    } elseif ($char === 'D' && $previousChar === 'I' && $secondToLastChar !== '\\') {
                        $inInlineImage = true;
                    } elseif ($char === 'C'
                        && (($secondToLastChar === 'B' && ($previousChar === 'M' || $previousChar === 'D')) || ($secondToLastChar === 'E' && $previousChar === 'M'))) { // MarkedContentOperator::BeginMarkedContent, MarkedContentOperator::EndMarkedContent, MarkedContentOperator::BeginMarkedContentWithProperties
                        $startCurrentOperandIndex = $index + 1;
                    } elseif (($operator = self::getOperator($char, $previousChar, $secondToLastChar, $thirdToLastChar)) !== null
                        && (($nextChar = $rawStream[$index + 1] ?? '') === '' || self::getOperator($nextChar, $char, $previousChar, $secondToLastChar) === null)) { // Skip the current hit if the next iteration is also a valid operator
                        $operands = '';
                        if ($previousContentStream !== null && $startPreviousOperandIndex !== null && $startPreviousOperandIndex < $previousContentStream->getSizeInBytes()) {
                            $operands .= $previousContentStream->read($startPreviousOperandIndex, $previousContentStream->getSizeInBytes() - $startPreviousOperandIndex);
                            $startPreviousOperandIndex = null;
                        }
                        $operatorLength = strlen($operator->value);
                        $operandLength = $index + 1 - $startCurrentOperandIndex - $operatorLength;
                        if ($operandLength > 0) {
                            $operandEndOffset = $index + 1 - $operatorLength;
                            if ($endCommentOffset !== null
                                && $endCommentOffset < $operandEndOffset
                                && $startOfCommentOffset !== null
                                && $startOfCommentOffset > $startCurrentOperandIndex) {
                                $operands .= substr($rawStream, $startCurrentOperandIndex, $startOfCommentOffset - $startCurrentOperandIndex);
                                $operands .= substr($rawStream, $endCommentOffset, $operandEndOffset - $endCommentOffset);
                            } else {
                                $operands .= substr($rawStream, $startCurrentOperandIndex, $operandLength);
                            }
                        }

                        $command = new ContentStreamCommand($operator, trim($operands));
                        if ($textObject !== null) {
                            $textObject->addContentStreamCommand($command);
                        } else {
                            $content[] = $command;
                        }

                        $startCurrentOperandIndex = $index + 1;
                        $endCommentOffset = $startOfCommentOffset = null;
                    }
                }

                $thirdToLastChar = $secondToLastChar;
                $secondToLastChar = $previousChar;
                $previousChar = $char;
            }

            $previousContentStream = $contentStream;
            $startPreviousOperandIndex = $startCurrentOperandIndex;
        }

        return new ContentStream(...$content);
    }

    public static function getOperator(string $currentChar, ?string $previousChar, ?string $secondToLastChar, ?string $thirdToLastChar): CompatibilityOperator|InlineImageOperator|MarkedContentOperator|TextObjectOperator|ClippingPathOperator|ColorOperator|GraphicsStateOperator|PathConstructionOperator|PathPaintingOperator|TextPositioningOperator|TextShowingOperator|TextStateOperator|Type3FontOperator|XObjectOperator|null {
        if ($secondToLastChar !== null && isset(self::OPERATOR_CHARS[$secondToLastChar]) && $previousChar !== null && isset(self::OPERATOR_CHARS[$previousChar])) {
            $key = $secondToLastChar . $previousChar . $currentChar;
            if (isset(self::OPERATOR_MAP[$key])) {
                return ($thirdToLastChar === '\\' || $thirdToLastChar === '/') ? null : self::OPERATOR_MAP[$key];
            }
        }

        if ($previousChar !== null && isset(self::OPERATOR_CHARS[$previousChar])) {
            $key = $previousChar . $currentChar;
            if (isset(self::OPERATOR_MAP[$key])) {
                return ($secondToLastChar === '\\' || $secondToLastChar === '/') ? null : self::OPERATOR_MAP[$key];
            }
        }

        if (isset(self::OPERATOR_MAP[$currentChar])) {
            return ($previousChar === '\\' || $previousChar === '/') ? null : self::OPERATOR_MAP[$currentChar];
        }

        return null;
    }
}
