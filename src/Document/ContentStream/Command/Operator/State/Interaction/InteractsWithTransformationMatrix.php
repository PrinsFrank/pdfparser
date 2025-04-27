<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\Interaction;

use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TransformationMatrix;

interface InteractsWithTransformationMatrix {
    public function applyToTransformationMatrix(string $operands, TransformationMatrix $transformationMatrix): TransformationMatrix;
}
