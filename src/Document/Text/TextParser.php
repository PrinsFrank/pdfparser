<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Text;

use PrinsFrank\PdfParser\Document\Text\OperatorString\ColorOperator;
use PrinsFrank\PdfParser\Document\Text\OperatorString\GraphicsStateOperator;
use PrinsFrank\PdfParser\Document\Text\OperatorString\TextPositioningOperator;
use PrinsFrank\PdfParser\Document\Text\OperatorString\TextShowingOperator;
use PrinsFrank\PdfParser\Document\Text\OperatorString\TextStateOperator;

class TextParser {
    public static function parse(string $text): TextObjectCollection {
        $operandBuffer = '';
        $textObjects = [];
        $inValue = false;
        $textObject = $previousChar = $secondToLastChar = $thirdToLastChar = null;
        foreach (mb_str_split($text) as $char) {
            $operandBuffer .= $char;
            if (in_array($char, ['[', '<', '('], true) && $previousChar !== '\\') {
                $inValue = true;
            } elseif ($inValue) {
                if (in_array($char, [']', '>', ')'], true) && $previousChar !== '\\') {
                    $inValue = false;
                }
            } elseif ($char === 'T' && $previousChar === 'B') { // TextObjectOperator::BEGIN
                $operandBuffer = '';
                $textObject = new TextObject();
                $textObjects[] = $textObject;
            } elseif ($char === 'T' && $previousChar === 'E') { // TextObjectOperator::END
                $operandBuffer = '';
                $textObject = null;
            } elseif ($char === 'C'
                && (($secondToLastChar === 'B' && ($previousChar === 'M' || $previousChar === 'D')) || ($secondToLastChar === 'E' && $previousChar === 'M'))) { // MarkedContentOperator::BeginMarkedContent, MarkedContentOperator::EndMarkedContent, MarkedContentOperator::BeginMarkedContentWithProperties
                $operandBuffer = '';
            } elseif ($textObject !== null && ($operator = self::getOperator($char, $previousChar, $secondToLastChar, $thirdToLastChar)) !== null) {
                $textObject->addTextOperator(new TextOperator($operator, trim(substr($operandBuffer, 0, -strlen($operator->value)))));
                $operandBuffer = '';
            }

            $thirdToLastChar = $secondToLastChar;
            $secondToLastChar = $previousChar;
            $previousChar = $char;
        }

        return new TextObjectCollection(...$textObjects);
    }

    /**
     * This method uses three maps instead of calling $enum::tryFrom for all possible enums
     * as operator retrieval happens possibly millions of times in a single file
     */
    public static function getOperator(string $currentChar, ?string $previousChar, ?string $secondToLastChar, ?string $thirdToLastChar): TextPositioningOperator|TextShowingOperator|TextStateOperator|GraphicsStateOperator|ColorOperator|null {
        $threeLetterMatch = match ([$secondToLastChar, $previousChar, $currentChar]) {
            ['S', 'C', 'N'] => ColorOperator::SetStrokingParams,
            ['s', 'c', 'n'] => ColorOperator::SetColorParams,
            default => null,
        };
        if ($threeLetterMatch !== null && $thirdToLastChar !== '\\') {
            return $threeLetterMatch;
        }

        $twoLetterMatch = match ([$previousChar, $currentChar]) {
            ['T', 'd'] => TextPositioningOperator::MOVE_OFFSET,
            ['T', 'D'] => TextPositioningOperator::MOVE_OFFSET_LEADING,
            ['T', 'm'] => TextPositioningOperator::SET_MATRIX,
            ['T', '*'] => TextPositioningOperator::NEXT_LINE,
            ['T', 'j'] => TextShowingOperator::SHOW,
            ['T', 'J'] => TextShowingOperator::SHOW_ARRAY,
            ['T', 'c'] => TextStateOperator::CHAR_SIZE,
            ['T', 'w'] => TextStateOperator::WORD_SPACE,
            ['T', 'z'] => TextStateOperator::SCALE,
            ['T', 'L'] => TextStateOperator::LEADING,
            ['T', 'f'] => TextStateOperator::FONT_SIZE,
            ['T', 'r'] => TextStateOperator::RENDER,
            ['T', 's'] => TextStateOperator::RISE,
            ['c', 'm'] => GraphicsStateOperator::ModifyCurrentTransformationMatrix,
            ['r', 'i'] => GraphicsStateOperator::SetIntent,
            ['g', 's'] => GraphicsStateOperator::SetDictName,
            ['C', 'S'] => ColorOperator::SetName,
            ['c', 's'] => ColorOperator::SetNameNonStroking,
            ['S', 'C'] => ColorOperator::SetStrokingColor,
            ['s', 'c'] => ColorOperator::SetColor,
            ['R', 'G'] => ColorOperator::SetStrokingColorDeviceRGB,
            ['r', 'g'] => ColorOperator::SetColorDeviceRGB,
            default => null,
        };
        if ($twoLetterMatch !== null && $secondToLastChar !== '\\') {
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

        if ($oneLetterMatch !== null && $previousChar !== '\\') {
            return $oneLetterMatch;
        }

        return null;
    }
}
