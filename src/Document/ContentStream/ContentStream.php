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
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TextState;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TransformationMatrix;
use PrinsFrank\PdfParser\Document\Document;
use PrinsFrank\PdfParser\Document\Object\Decorator\Page;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
use PrinsFrank\PdfParser\Exception\PdfParserException;

/** @api */
readonly class ContentStream {
    public const WORD_BREAK_THRESHOLD = 0.25;

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
        $positionedTextElements = $transformationStateStack = $textStateStack = [];
        $textState = new TextState(null, null); // See table 103, Tf operator for initial value
        $transformationMatrix = new TransformationMatrix(1, 0, 0, 1, 0, 0); // Identity matrix
        foreach ($this->content as $content) {
            if ($content instanceof ContentStreamCommand) {
                if ($content->operator === GraphicsStateOperator::SaveCurrentStateToStack) {
                    $transformationStateStack[] = clone $transformationMatrix;
                    $textStateStack[] = clone $textState;
                } elseif ($content->operator === GraphicsStateOperator::RestoreMostRecentStateFromStack) {
                    $transformationMatrix = array_pop($transformationStateStack)
                        ?? throw new ParseFailureException();
                    $textState = array_pop($textStateStack)
                        ?? throw new ParseFailureException();
                }

                if ($content->operator instanceof InteractsWithTextState) {
                    $textState = $content->operator->applyToTextState($content->operands, $textState);
                }

                if ($content->operator instanceof InteractsWithTransformationMatrix) {
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
        $isFirstLine = true;
        foreach ($lineGroupingStrategy->group($this->getPositionedTextElements()) as $positionedTextElementsForLine) {
            if (!$isFirstLine) {
                $text .= "\n";
            }

            $isFirstLine = false;
            $texts = [];
            foreach ($positionedTextElementsForLine as $index => $positionedTextElement) {
                $texts[$index] = $positionedTextElement->getText($document, $page);
            }

            $previousTextElementOnLine = null;
            foreach ($positionedTextElementsForLine as $index => $positionedTextElement) {
                if ($texts[$index] === '') {
                    $previousTextElementOnLine = $positionedTextElement;
                    continue;
                }

                if (
                    $previousTextElementOnLine !== null
                    && $lineGroupingStrategy->requiresSpaceBetween($previousTextElementOnLine, $positionedTextElement, $document, $page)
                    && str_ends_with($text, ' ') === false
                    && str_starts_with($texts[$index], ' ') === false
                ) {
                    $text .= ' ';
                }

                $text .= $texts[$index];
                $previousTextElementOnLine = $positionedTextElement;
            }
        }

        return $text;
    }
}
