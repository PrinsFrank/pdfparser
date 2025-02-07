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
        $textObject = new TextObject();
        $previousChar = $secondToLastChar = $thirdToLastChar = null;
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
                $textObject = new TextObject();
            } elseif ($char === 'C'
                && (($secondToLastChar === 'B' && ($previousChar === 'M' || $previousChar === 'D')) || ($secondToLastChar === 'E' && $previousChar === 'M'))) { // MarkedContentOperator::BeginMarkedContent, MarkedContentOperator::EndMarkedContent, MarkedContentOperator::BeginMarkedContentWithProperties
                $operandBuffer = '';
            } elseif (($operator = self::getOperator($char, $previousChar, $secondToLastChar, $thirdToLastChar)) !== null) {
                $textObject->addTextOperator(new TextOperator($operator, trim(substr($operandBuffer, 0, -strlen($operator->value)))));
                $operandBuffer = '';
            }

            $thirdToLastChar = $secondToLastChar;
            $secondToLastChar = $previousChar;
            $previousChar = $char;
        }

        return new TextObjectCollection(
            ...array_filter($textObjects, fn (TextObject $textObject) => $textObject->isEmpty() === false)
        );
    }

    public static function getOperator(string $currentChar, ?string $previousChar, ?string $secondToLastChar, ?string $thirdToLastChar): TextPositioningOperator|TextShowingOperator|TextStateOperator|GraphicsStateOperator|ColorOperator|null {
        foreach ([TextPositioningOperator::class, TextShowingOperator::class, TextStateOperator::class, GraphicsStateOperator::class, ColorOperator::class] as $enumClass) {
            if (($case = $enumClass::tryFrom($secondToLastChar . $previousChar . $currentChar)) !== null && $thirdToLastChar !== '\\') {
                return $case;
            }
            if (($case = $enumClass::tryFrom($previousChar . $currentChar)) !== null && $secondToLastChar !== '\\') {
                return $case;
            }
            if (($case = $enumClass::tryFrom($currentChar)) !== null && $previousChar !== '\\') {
                return $case;
            }
        }

        return null;
    }
}
