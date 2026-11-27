<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream;

use PrinsFrank\PdfParser\Document\ContentStream\Command\ContentStreamCommand;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\Object\MarkedContentOperator;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\GraphicsStateOperator;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\Interaction\IncludesXObjects;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\Interaction\InteractsWithColorState;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\Interaction\InteractsWithTextState;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\Interaction\InteractsWithTransformationMatrix;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\Interaction\ProducesPositionedTextElements;
use PrinsFrank\PdfParser\Document\ContentStream\Object\TextObject;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\ColorState;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\PositionedTextElement;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TextState;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TransformationMatrix;
use PrinsFrank\PdfParser\Document\Dictionary\Decorator\Page;
use PrinsFrank\PdfParser\Document\Dictionary\Decorator\XObject;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryParser;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
use PrinsFrank\PdfParser\Stream\InMemoryStream;

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

    /**
     * @param list<int> $visitedObjectIds
     * @return list<PositionedTextElement>
     */
    public function getPositionedTextElements(Page|XObject $context, ?Page $parent, TransformationMatrix $transformationMatrix, array $visitedObjectIds): array {
        $positionedTextElements = $transformationStateStack = $textStateStack = $actualTextStack = [];
        $textState = new TextState(null, null); // See table 103, Tf operator for initial value
        $colorState = new ColorState();
        foreach ($this->content as $content) {
            if ($content instanceof ContentStreamCommand) {
                if ($content->operator instanceof MarkedContentOperator) {
                    $actualTextStack = $this->applyToMarkedContent($content, $actualTextStack);
                    continue;
                }

                if ($content->operator === GraphicsStateOperator::SaveCurrentStateToStack) {
                    $transformationStateStack[] = $transformationMatrix;
                    $textStateStack[] = $textState;
                } elseif ($content->operator === GraphicsStateOperator::RestoreMostRecentStateFromStack) {
                    $transformationMatrix = array_pop($transformationStateStack)
                        ?? throw new ParseFailureException();
                    $textState = array_pop($textStateStack)
                        ?? throw new ParseFailureException();
                }

                if ($content->operator instanceof InteractsWithColorState) {
                    $colorState = $content->operator->applyToColorState($content->operands, $colorState);
                }

                if ($content->operator instanceof InteractsWithTextState) {
                    $textState = $content->operator->applyToTextState($content->operands, $textState, $context, $parent);
                }

                if ($content->operator instanceof InteractsWithTransformationMatrix) {
                    $transformationMatrix = $content->operator->applyToTransformationMatrix($content->operands, $transformationMatrix, $textState);
                }

                if ($content->operator instanceof IncludesXObjects) {
                    $positionedTextElements = [...$positionedTextElements, ...$content->operator->getPositionedTextElements($content->operands, $transformationMatrix, $context, $visitedObjectIds)];
                }

                continue;
            }

            $textMatrix = new TransformationMatrix(1, 0, 0, 1, 0, 0); // Identity matrix, See Table 106, Tm operator for initial value in text object
            foreach ($content->contentStreamCommands as $contentStreamCommand) {
                if ($contentStreamCommand->operator instanceof MarkedContentOperator) {
                    $actualTextStack = $this->applyToMarkedContent($contentStreamCommand, $actualTextStack);
                    continue;
                }

                if ($contentStreamCommand->operator instanceof InteractsWithColorState) {
                    $colorState = $contentStreamCommand->operator->applyToColorState($contentStreamCommand->operands, $colorState);
                }

                if ($contentStreamCommand->operator instanceof InteractsWithTextState) {
                    $textState = $contentStreamCommand->operator->applyToTextState($contentStreamCommand->operands, $textState, $context, $parent);
                }

                if ($contentStreamCommand->operator instanceof InteractsWithTransformationMatrix) {
                    $textMatrix = $contentStreamCommand->operator->applyToTransformationMatrix($contentStreamCommand->operands, $textMatrix, $textState);
                }

                if ($contentStreamCommand->operator instanceof ProducesPositionedTextElements && $textState !== null) {
                    $positionedTextElement = $contentStreamCommand->operator->getPositionedTextElement($contentStreamCommand->operands, $textMatrix, $transformationMatrix, $textState, $colorState);
                    foreach ($actualTextStack as $level => $actualText) {
                        if ($actualText !== null) { // The outermost ActualText replaces all text in its sequence, so it goes on the first element only
                            $positionedTextElement = new PositionedTextElement($positionedTextElement->textSegments, $positionedTextElement->absoluteMatrix, $positionedTextElement->textState, $actualText);
                            $actualTextStack[$level] = '';
                            break;
                        }
                    }

                    $positionedTextElements[] = $positionedTextElement;
                }
            }
        }

        return $positionedTextElements;
    }

    /**
     * @param list<?string> $actualTextStack
     * @return list<?string>
     */
    private function applyToMarkedContent(ContentStreamCommand $command, array $actualTextStack): array {
        return match ($command->operator) {
            MarkedContentOperator::BeginMarkedContent => [...$actualTextStack, null],
            MarkedContentOperator::BeginMarkedContentWithProperties => [...$actualTextStack, $this->getActualText($command->operands)],
            MarkedContentOperator::EndMarkedContent => array_slice($actualTextStack, 0, -1),
            default => $actualTextStack,
        };
    }

    /**
     * @specification 14.9.4 Replacement text
     */
    private function getActualText(string $operands): ?string {
        if (($dictionaryStart = strpos($operands, '<<')) === false) { // A name of a property list in the Properties resource
            return null;
        }

        return DictionaryParser::parse(null, null, $stream = new InMemoryStream($operands), $dictionaryStart, $stream->getSizeInBytes() - $dictionaryStart)
            ->getStringValue(DictionaryKey::ACTUAL_TEXT)
            ?->getText();
    }
}
