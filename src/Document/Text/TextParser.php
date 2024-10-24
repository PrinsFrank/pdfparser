<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Text;

use PrinsFrank\PdfParser\Document\Generic\Parsing\InfiniteBuffer;
use PrinsFrank\PdfParser\Document\Generic\Parsing\RollingCharBuffer;
use PrinsFrank\PdfParser\Document\Text\OperatorString\TextObjectOperator;
use PrinsFrank\PdfParser\Document\Text\OperatorString\TextPositioningOperator;
use PrinsFrank\PdfParser\Document\Text\OperatorString\TextShowingOperator;
use PrinsFrank\PdfParser\Document\Text\OperatorString\TextStateOperator;
use PrinsFrank\PdfParser\Exception\BufferTooSmallException;

class TextParser {
    /** @throws BufferTooSmallException */
    public static function parse(string $text): TextObjectCollection {
        $textObjectCollection = new TextObjectCollection();
        $operatorBuffer = new RollingCharBuffer(2);
        $textObject = null;
        $operandBuffer = new InfiniteBuffer();
        foreach (str_split($text) as $char) {
            $operandBuffer->addChar($char);
            $operatorBuffer->next()->setCharacter($char);
            if ($operatorBuffer->seenBackedEnumValue(TextObjectOperator::BEGIN)) {
                $operandBuffer->flush();
                $textObject = new TextObject();
                $textObjectCollection->addTextObject($textObject);
                continue;
            }

            if ($operatorBuffer->seenBackedEnumValue(TextObjectOperator::END)) {
                $operandBuffer->flush();
                $textObject = null;
                continue;
            }

            if ($textObject === null) {
                continue;
            }

            $operator = $operatorBuffer->getBackedEnumValue(TextPositioningOperator::class, TextShowingOperator::class, TextStateOperator::class);
            if ($operator !== null) {
                $textObject->addTextOperator(new TextOperator($operator, trim($operandBuffer->removeChar(strlen($operator->value))->__toString())));
                $operandBuffer->flush();
            }
        }

        return $textObjectCollection;
    }
}
