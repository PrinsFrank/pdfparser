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
        $text = str_replace(["\r", "\n", '  '], ' ', $text);
        if ((preg_match_all('/(BT)(?<content>((?!ET).)*)/s', $text, $textObjectMatches, PREG_SET_ORDER)) === false) {
            throw new ParseFailureException('Failed to parse text objects');
        }

        $operators = array_map(
            fn(TextPositioningOperator|TextShowingOperator|TextStateOperator|GraphicsStateOperator|ColorOperator $operator) => str_replace(['*'], ['\*'], $operator->value),
            [...TextPositioningOperator::cases(), ...TextShowingOperator::cases(), ...TextStateOperator::cases(), ...GraphicsStateOperator::cases(), ...ColorOperator::cases()],
        );

        usort($operators, fn (string $operator, string $operator2) => strlen($operator2) <=> strlen($operator)); // Make sure longest operators are first so they match when a shorter match is also possible
        $operatorStrings = implode('|', $operators);
        $textObjects = [];
        foreach ($textObjectMatches as $textObjectMatch) {
            $textObjects[] = ($textObject = new TextObject());
            if (str_starts_with($textObjectMatch['content'], TextObjectOperator::BEGIN->value)) {
                $textObjectMatch['content'] = substr($textObjectMatch['content'], strlen(TextObjectOperator::BEGIN->value));
            }
            if (str_ends_with($textObjectMatch['content'], TextObjectOperator::END->value)) {
                $textObjectMatch['content'] = substr($textObjectMatch['content'], 0, -strlen(TextObjectOperator::END->value));
            }

            $regex = '/(?<operand>\[.+?\]|\(.+?\)|[a-zA-Z0-9\.\/_<>-][a-zA-Z0-9 \._<>-]+?)\s*(?<!\/)(?<operator>' . $operatorStrings . ')/';
            if (preg_match_all($regex, $textObjectMatch['content'], $textObjectOperatorMatch, PREG_SET_ORDER) === false) {
                throw new ParseFailureException();
            }

            foreach ($textObjectOperatorMatch as $match) {
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
