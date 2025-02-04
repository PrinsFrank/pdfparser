<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Text;

use PrinsFrank\PdfParser\Document\Text\OperatorString\ColorOperator;
use PrinsFrank\PdfParser\Document\Text\OperatorString\GraphicsStateOperator;
use PrinsFrank\PdfParser\Document\Text\OperatorString\TextObjectOperator;
use PrinsFrank\PdfParser\Document\Text\OperatorString\TextPositioningOperator;
use PrinsFrank\PdfParser\Document\Text\OperatorString\TextShowingOperator;
use PrinsFrank\PdfParser\Document\Text\OperatorString\TextStateOperator;
use PrinsFrank\PdfParser\Exception\ParseFailureException;

class TextParser {
    public static function parse(string $text): TextObjectCollection {
        if (($textObjectStrings = preg_split('/' . TextObjectOperator::END->value . '((?!' . TextObjectOperator::BEGIN->value . ')[[:ascii:]\n])+' . TextObjectOperator::BEGIN->value . '/', $text)) === false) {
            throw new ParseFailureException('Failed to parse text objects');
        }

        $operatorStrings = implode('|', array_map(
            fn(TextPositioningOperator|TextShowingOperator|TextStateOperator|GraphicsStateOperator|ColorOperator $operator) => str_replace(['*'], ['\*'], $operator->value),
            [...TextPositioningOperator::cases(), ...TextShowingOperator::cases(), ...TextStateOperator::cases(), ...GraphicsStateOperator::cases(), ...ColorOperator::cases()],
        ));

        $textObjects = [];
        foreach ($textObjectStrings as $textObjectString) {
            $textObjects[] = ($textObject = new TextObject());
            if (str_starts_with($textObjectString, TextObjectOperator::BEGIN->value)) {
                $textObjectString = substr($textObjectString, strlen(TextObjectOperator::BEGIN->value));
            }
            if (str_ends_with($textObjectString, TextObjectOperator::END->value)) {
                $textObjectString = substr($textObjectString, 0, -strlen(TextObjectOperator::END->value));
            }

            $regex = '/(?<operand>\[[^]]*\]|\([^\)]*\)|[a-zA-Z0-9 \.\/_<>-]+?)\s*(?<operator>' . $operatorStrings . ')/';
            if (preg_match_all($regex, $textObjectString, $matches, PREG_SET_ORDER) === false) {
                throw new ParseFailureException();
            }

            foreach ($matches as $match) {
                $textObject->addTextOperator(
                    new TextOperator(
                        TextPositioningOperator::tryFrom($match['operator']) ?? TextShowingOperator::tryFrom($match['operator']) ?? TextStateOperator::tryFrom($match['operator']) ?? GraphicsStateOperator::tryFrom($match['operator']) ?? ColorOperator::tryFrom($match['operator']) ?? throw new ParseFailureException(),
                        trim($match['operand']),
                    )
                );
            }
        }

        return new TextObjectCollection(...$textObjects);
    }
}
