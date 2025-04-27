<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\Interaction;

use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TransformationMatrix;

interface InteractsWithTextMatrix {
    public function applyToTextMatrix(string $operands, TransformationMatrix $transformationMatrix): TransformationMatrix;
}
