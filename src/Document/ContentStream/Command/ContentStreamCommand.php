<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\Command;

use PrinsFrank\PdfParser\Document\ContentStream\OperatorString\ColorOperator;
use PrinsFrank\PdfParser\Document\ContentStream\OperatorString\GraphicsStateOperator;
use PrinsFrank\PdfParser\Document\ContentStream\OperatorString\TextPositioningOperator;
use PrinsFrank\PdfParser\Document\ContentStream\OperatorString\TextShowingOperator;
use PrinsFrank\PdfParser\Document\ContentStream\OperatorString\TextStateOperator;

/** @internal */
class ContentStreamCommand {
    public function __construct(
        public readonly TextPositioningOperator|TextShowingOperator|TextStateOperator|GraphicsStateOperator|ColorOperator $operator,
        public readonly string $operands
    ) {
    }
}
