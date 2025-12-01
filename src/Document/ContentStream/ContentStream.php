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
                if ($content->operator instanceof InteractsWithTextState) {
                    $textState = $content->operator->applyToTextState($content->operands, $textState);
                } elseif ($content->operator === GraphicsStateOperator::SaveCurrentStateToStack) {
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

                if ($contentStreamCommand->operator instanceof ProducesPositionedTextElements && $textState !== null) {
                    $positionedTextElements[] = $contentStreamCommand->operator->getPositionedTextElement($contentStreamCommand->operands, $textMatrix, $transformationMatrix, $textState);
                }
            }
        }

        return $positionedTextElements;
    }

    /** @throws PdfParserException */
    public function getText(Document $document, Page $page): string {
        $positionedTextElements = $this->getPositionedTextElements();
        if ($positionedTextElements === []) {
            return '';
        }

        $lowestY = min(array_map(static fn(PositionedTextElement $positionedTextElement): float => $positionedTextElement->absoluteMatrix->offsetY * $positionedTextElement->absoluteMatrix->scaleY, $positionedTextElements));
        $highestY = max(array_map(static fn(PositionedTextElement $positionedTextElement): float => $positionedTextElement->absoluteMatrix->offsetY * $positionedTextElement->absoluteMatrix->scaleY, $positionedTextElements));
        $variabilityY = $lowestY !== $highestY
            ? ($highestY - $lowestY) / 200
            : 0;

        usort(
            $positionedTextElements,
            static function (PositionedTextElement $a, PositionedTextElement $b) use ($variabilityY): int {
                $differenceY = $b->absoluteMatrix->offsetY * $b->absoluteMatrix->scaleY - $a->absoluteMatrix->offsetY * $a->absoluteMatrix->scaleY;
                if ($differenceY > $variabilityY) {
                    return 1;
                }

                if ($differenceY < -$variabilityY) {
                    return -1;
                }

                return $a->absoluteMatrix->offsetX <=> $b->absoluteMatrix->offsetX;
            }
        );

        $text = '';
        $previousPositionedTextElement = null;
        foreach ($positionedTextElements as $positionedTextElement) {
            if ($previousPositionedTextElement !== null) {
                $diffY = $previousPositionedTextElement->absoluteMatrix->offsetY * $previousPositionedTextElement->absoluteMatrix->scaleY - $positionedTextElement->absoluteMatrix->offsetY * $positionedTextElement->absoluteMatrix->scaleY;
                if ($diffY > $variabilityY || $diffY < -$variabilityY) {
                    $text .= "\n";
                } elseif (($positionedTextElement->absoluteMatrix->getAbsoluteX() - $previousPositionedTextElement->absoluteMatrix->getAbsoluteX() - $positionedTextElement->getFont($document, $page)->getWidthForChars($previousPositionedTextElement->getCodePoints(), $previousPositionedTextElement->textState, $previousPositionedTextElement->absoluteMatrix)) >= ($previousPositionedTextElement->textState->fontSize ?? 10) * $previousPositionedTextElement->absoluteMatrix->scaleX * 0.20 && str_ends_with($text, ' ') === false) {
                    $text .= ' ';
                }
            }

            $text .= $positionedTextElement->getText($document, $page);
            $previousPositionedTextElement = $positionedTextElement;
        }

        return $text;
    }
}
