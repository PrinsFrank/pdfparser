<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Text;

use PrinsFrank\PdfParser\Document\Document;
use PrinsFrank\PdfParser\Document\Object\Decorator\Page;
use PrinsFrank\PdfParser\Document\Text\OperatorString\TextPositioningOperator;
use PrinsFrank\PdfParser\Document\Text\OperatorString\TextShowingOperator;
use PrinsFrank\PdfParser\Document\Text\OperatorString\TextStateOperator;
use PrinsFrank\PdfParser\Document\Text\Positioning\PositionedTextElement;
use PrinsFrank\PdfParser\Document\Text\Positioning\TextMatrix;
use PrinsFrank\PdfParser\Exception\PdfParserException;

/** @api */
class TextObjectCollection {
    /** @var list<TextObject> */
    public readonly array $textObjects;

    /** @no-named-arguments */
    public function __construct(
        TextObject... $textObjects
    ) {
        $this->textObjects = $textObjects;
    }

    /** @return list<PositionedTextElement> */
    public function getPositionedTextElements(): array {
        $positionedTextElements = [];
        $textState = null; // See table 103, Tf operator for initial value
        foreach ($this->textObjects as $textObject) {
            $textMatrix = new TextMatrix(1, 0, 0, 1, 0, 0); // See Table 106, Tm operator for initial value in text object
            foreach ($textObject->textOperators as $textOperator) {
                if ($textOperator->operator instanceof TextStateOperator) {
                    $textState = $textOperator->operator->getNewTextState($textOperator->operands, $textState);
                } elseif ($textOperator->operator instanceof TextPositioningOperator) {
                    $textState = $textOperator->operator->getNewTextState($textOperator->operands, $textState);
                    $textMatrix = $textOperator->operator->getNewTextMatrix($textOperator->operands, $textMatrix, $textState);
                } elseif ($textOperator->operator instanceof TextShowingOperator) {
                    $textState = $textOperator->operator->getNewTextState($textOperator->operands, $textState);
                    $textMatrix = $textOperator->operator->getNewTextMatrix($textOperator->operands, $textMatrix);
                    $positionedTextElements[] = new PositionedTextElement($textOperator->operands, $textMatrix, $textState);
                }
            }
        }

        return $positionedTextElements;
    }

    /** @throws PdfParserException */
    public function getText(Document $document, Page $page): string {
        $positionedTextElements = $this->getPositionedTextElements();
        usort(
            $positionedTextElements,
            fn (PositionedTextElement $a, PositionedTextElement $b) => ($a->textMatrix->scaleY * $a->textMatrix->scaleX) + $a->textMatrix->scaleY <=> ($b->textMatrix->scaleY * $b->textMatrix->scaleX) + $b->textMatrix->scaleY,
        );

        $text = '';
        foreach ($positionedTextElements as $positionedTextElement) {
            $text .= $positionedTextElement->getText($document, $page->getFontDictionary());
        }

        return $text;
    }
}
