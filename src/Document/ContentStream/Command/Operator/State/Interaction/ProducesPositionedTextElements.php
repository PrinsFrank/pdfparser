<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\Interaction;

use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\PositionedTextElement;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TransformationMatrix;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TextState;

interface ProducesPositionedTextElements {
    public function getPositionedTextElement(string $operands, TransformationMatrix $transformationMatrix, TextState $textState): ?PositionedTextElement;
}
