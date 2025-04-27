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
class ContentStream {
    /** @var list<TextObject|ContentStreamCommand> */
    public readonly array $content;

    /** @no-named-arguments */
    public function __construct(
        TextObject|ContentStreamCommand... $content
    ) {
        $this->content = $content;
    }

    /** @return list<PositionedTextElement> */
    public function getPositionedTextElements(): array {
        $positionedTextElements = [];
        $textState = null; // See table 103, Tf operator for initial value
        foreach ($this->content as $content) {
            $textMatrix = new TextMatrix(1, 0, 0, 1, 0, 0); // See Table 106, Tm operator for initial value in text object
            if ($content instanceof ContentStreamCommand) {
                throw new \RuntimeException();
                continue;
            }

            foreach ($content->commands as $textOperator) {
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
            static function (PositionedTextElement $a, PositionedTextElement $b) {
                if ($a->textMatrix->scaleY * $a->textMatrix->offsetY !== $b->textMatrix->scaleY * $b->textMatrix->offsetY) {
                    return $a->textMatrix->scaleY * $a->textMatrix->offsetY <=> $b->textMatrix->scaleY * $b->textMatrix->offsetY;
                }

                return $a->textMatrix->scaleX * $a->textMatrix->offsetX <=> $b->textMatrix->scaleX * $b->textMatrix->offsetX;
            }
        );

        $text = '';
        foreach ($positionedTextElements as $positionedTextElement) {
            $text .= $positionedTextElement->getText($document, $page->getFontDictionary());
        }

        return $text;
    }
}
