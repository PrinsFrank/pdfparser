<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream;

use PrinsFrank\PdfParser\Document\ContentStream\Command\ContentStreamCommand;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\GraphicsStateOperator;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\Interaction\InteractsWithTransformationMatrix;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\Interaction\InteractsWithTextState;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\Interaction\ProducesPositionedTextElements;
use PrinsFrank\PdfParser\Document\ContentStream\Object\TextObject;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\PositionedTextElement;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TransformationMatrix;
use PrinsFrank\PdfParser\Document\Document;
use PrinsFrank\PdfParser\Document\Object\Decorator\Page;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
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
        $positionedTextElements = $transformationStateStack = [];
        $textState = null; // See table 103, Tf operator for initial value
        $transformationMatrix = new TransformationMatrix(1, 0, 0, 1, 0, 0); // Identity matrix
        foreach ($this->content as $content) {
            if ($content instanceof ContentStreamCommand) {
                if ($content->operator === GraphicsStateOperator::SaveCurrentStateToStack) {
                    $transformationStateStack[] = clone $transformationMatrix;
                } elseif ($content->operator === GraphicsStateOperator::RestoreMostRecentStateFromStack) {
                    $transformationMatrix = array_pop($transformationStateStack)
                        ?? throw new ParseFailureException();
                } elseif ($content->operator instanceof InteractsWithTransformationMatrix) {
                    $transformationMatrix = $content->operator->applyToTransformationMatrix($content->operands, $transformationMatrix);
                }

                continue;
            }

            $textMatrix = new TransformationMatrix(1, 0, 0, 1, 0, 0); // Identity matrix, See Table 106, Tm operator for initial value in text object
            foreach ($content->contentStreamCommands as $contentStreamCommand) {
                if ($contentStreamCommand->operator instanceof InteractsWithTextState) {
                    $textState = $contentStreamCommand->operator->applyToTextState($contentStreamCommand->operands, $textState);
                }

                if ($contentStreamCommand->operator instanceof InteractsWithTransformationMatrix) {
                    $textMatrix = $contentStreamCommand->operator->applyToTransformationMatrix($contentStreamCommand->operands, $textMatrix);
                }

                if ($contentStreamCommand->operator instanceof ProducesPositionedTextElements) {
                    $positionedTextElements[] = $contentStreamCommand->operator->getPositionedTextElement($contentStreamCommand->operands, $textMatrix, $transformationMatrix, $textState);
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
            static function (PositionedTextElement $a, PositionedTextElement $b): int {
                if (($differenceY = $b->absoluteMatrix->offsetY <=> $a->absoluteMatrix->offsetY) !== 0) {
                    return $differenceY;
                }

                return $a->absoluteMatrix->offsetX <=> $b->absoluteMatrix->offsetX;
            }
        );

        $text = '';
        $previousOffsetY = null;
        foreach ($positionedTextElements as $positionedTextElement) {
            $currentOffsetY = $positionedTextElement->absoluteMatrix->offsetY;
            if ($previousOffsetY !== null && $previousOffsetY !== $currentOffsetY) {
                $text .= "\n";
            }

            $previousOffsetY = $currentOffsetY;
            $text .= $positionedTextElement->getText($document, $page->getFontDictionary());
        }

        return $text;
    }
}
