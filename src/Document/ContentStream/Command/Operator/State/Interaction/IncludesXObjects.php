<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\Interaction;

use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\PositionedTextElement;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TransformationMatrix;
use PrinsFrank\PdfParser\Document\Object\Decorator\Page;

interface IncludesXObjects {
    /** @return list<PositionedTextElement> */
    public function getPositionedTextElements(string $operands, TransformationMatrix $transformationMatrix, Page $page): array;
}
