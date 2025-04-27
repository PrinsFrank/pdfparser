<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream;

use PrinsFrank\PdfParser\Document\ContentStream\Command\ContentStreamCommand;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\Interaction\InteractsWithTextMatrix;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\Interaction\InteractsWithTextState;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\Interaction\ProducesPositionedTextElements;
use PrinsFrank\PdfParser\Document\ContentStream\Object\TextObject;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\PositionedTextElement;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TextMatrix;
use PrinsFrank\PdfParser\Document\Document;
use PrinsFrank\PdfParser\Document\Object\Decorator\Page;
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
            foreach (($content instanceof ContentStreamCommand ? [$content] : $content->contentStreamCommands) as $contentStreamCommand) {
                if ($contentStreamCommand->operator instanceof InteractsWithTextState) {
                    $textState = $contentStreamCommand->operator->applyToTextState($contentStreamCommand->operands, $textState);
                }

                if ($contentStreamCommand->operator instanceof InteractsWithTextMatrix) {
                    $textMatrix = $contentStreamCommand->operator->applyToTextMatrix($contentStreamCommand->operands, $textMatrix);
                }

                if ($contentStreamCommand->operator instanceof ProducesPositionedTextElements
                    && ($positionedTextElement = $contentStreamCommand->operator->getPositionedTextElement($contentStreamCommand->operands, $textMatrix, $textState)) !== null) {
                    $positionedTextElements[] = $positionedTextElement;
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
