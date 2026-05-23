<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream;

use PrinsFrank\PdfParser\Document\ContentStream\Command\ContentStreamCommand;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\GraphicsStateOperator;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\Interaction\InteractsWithTransformationMatrix;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\Interaction\InteractsWithTextState;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\Interaction\ProducesPositionedTextElements;
use PrinsFrank\PdfParser\Document\ContentStream\Object\TextObject;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\LineGroupingStrategy\LineGroupingStrategy;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\PositionedTextElement;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TransformationMatrix;
use PrinsFrank\PdfParser\Document\Document;
use PrinsFrank\PdfParser\Document\Object\Decorator\Page;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
use PrinsFrank\PdfParser\Exception\PdfParserException;

/** @api */
readonly class ContentStream {
    /** @var list<TextObject|ContentStreamCommand> */
    public array $content;

    /** @no-named-arguments */
    public function __construct(
        TextObject|ContentStreamCommand... $content,
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
                    $transformationMatrix = $content->operator->applyToTransformationMatrix($content->operands, $transformationMatrix, $textState);
                }

                continue;
            }

            $textMatrix = new TransformationMatrix(1, 0, 0, 1, 0, 0); // Identity matrix, See Table 106, Tm operator for initial value in text object
            foreach ($content->contentStreamCommands as $contentStreamCommand) {
                if ($contentStreamCommand->operator instanceof InteractsWithTextState) {
                    $textState = $contentStreamCommand->operator->applyToTextState($contentStreamCommand->operands, $textState);
                }

                if ($contentStreamCommand->operator instanceof InteractsWithTransformationMatrix) {
                    $textMatrix = $contentStreamCommand->operator->applyToTransformationMatrix($contentStreamCommand->operands, $textMatrix, $textState);
                }

                if ($contentStreamCommand->operator instanceof ProducesPositionedTextElements && $textState !== null) {
                    $positionedTextElements[] = $contentStreamCommand->operator->getPositionedTextElement($contentStreamCommand->operands, $textMatrix, $transformationMatrix, $textState);
                }
            }
        }

        return $positionedTextElements;
    }

    /** @throws PdfParserException */
    public function getText(Document $document, Page $page, LineGroupingStrategy $lineGroupingStrategy): string {
        $text = '';
        foreach ($lineGroupingStrategy->group($this->getPositionedTextElements()) as $i => $positionedTextElementsForLine) {
            if ($i !== 0) {
                $text .= "\n";
            }

            $previousTextElementOnLine = null;
            foreach ($positionedTextElementsForLine as $positionedTextElement) {
                if ($previousTextElementOnLine !== null
                    && ($positionedTextElement->absoluteMatrix->offsetX - $previousTextElementOnLine->absoluteMatrix->offsetX - $positionedTextElement->getFont($document, $page)->getWidthForChars($previousTextElementOnLine->getCodePoints(), $previousTextElementOnLine->textState, $previousTextElementOnLine->absoluteMatrix)) >= ($previousTextElementOnLine->textState->fontSize ?? 10) * $previousTextElementOnLine->absoluteMatrix->scaleX * 0.40) {
                    $text .= ' ';
                }

                $text .= $positionedTextElement->getText($document, $page);
                $previousTextElementOnLine = $positionedTextElement;
            }
        }

        return $text;
    }
}
