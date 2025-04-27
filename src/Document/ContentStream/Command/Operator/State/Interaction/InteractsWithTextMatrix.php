<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\Interaction;

use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TextMatrix;

interface InteractsWithTextMatrix {
    public function applyToTextMatrix(string $operands, TextMatrix $currentTextMatrix): TextMatrix;
}
