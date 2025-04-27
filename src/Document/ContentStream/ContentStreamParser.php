<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream;

use PrinsFrank\PdfParser\Document\ContentStream\Command\ContentStreamCommand;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\ColorOperator;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\GraphicsStateOperator;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\TextPositioningOperator;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\TextShowingOperator;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\TextStateOperator;
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
        foreach (mb_str_split($contentStream) as $char) {
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
            } elseif (($operator = self::getOperator($char, $previousChar, $secondToLastChar, $thirdToLastChar)) !== null) {
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
    public static function getOperator(string $currentChar, ?string $previousChar, ?string $secondToLastChar, ?string $thirdToLastChar): TextPositioningOperator|TextShowingOperator|TextStateOperator|GraphicsStateOperator|ColorOperator|null {
        $threeLetterMatch = match ($secondToLastChar . $previousChar . $currentChar) {
            'SCN' => ColorOperator::SetStrokingParams,
            'scn' => ColorOperator::SetColorParams,
            default => null,
        };
        if ($threeLetterMatch !== null && !in_array($thirdToLastChar, ['\\', '/'], true)) {
            return $threeLetterMatch;
        }

        $twoLetterMatch = match ($previousChar . $currentChar) {
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
            'cm' => GraphicsStateOperator::ModifyCurrentTransformationMatrix,
            'ri' => GraphicsStateOperator::SetIntent,
            'gs' => GraphicsStateOperator::SetDictName,
            'CS' => ColorOperator::SetName,
            'cs' => ColorOperator::SetNameNonStroking,
            'SC' => ColorOperator::SetStrokingColor,
            'sc' => ColorOperator::SetColor,
            'RG' => ColorOperator::SetStrokingColorDeviceRGB,
            'rg' => ColorOperator::SetColorDeviceRGB,
            default => null,
        };
        if ($twoLetterMatch !== null && !in_array($secondToLastChar, ['\\', '/'], true)) {
            return $twoLetterMatch;
        }

        $oneLetterMatch = match ($currentChar) {
            '\'' => TextShowingOperator::MOVE_SHOW,
            '"' => TextShowingOperator::MOVE_SHOW_SPACING,
            'q' => GraphicsStateOperator::SaveCurrentStateToStack,
            'Q' => GraphicsStateOperator::RestoreMostRecentStateFromStack,
            'w' => GraphicsStateOperator::SetLineWidth,
            'J' => GraphicsStateOperator::SetLineCap,
            'j' => GraphicsStateOperator::SetLineJoin,
            'M' => GraphicsStateOperator::SetMiterJoin,
            'd' => GraphicsStateOperator::SetLineDash,
            'i' => GraphicsStateOperator::SetFlatness,
            'G' => ColorOperator::SetStrokingColorSpace,
            'g' => ColorOperator::SetColorSpace,
            'K' => ColorOperator::SetStrokingColorDeviceCMYK,
            'k' => ColorOperator::SetColorDeviceCMYK,
            default => null,
        };

        if ($oneLetterMatch !== null && !in_array($previousChar, ['\\', '/'], true)) {
            return $oneLetterMatch;
        }

        return null;
    }
}
