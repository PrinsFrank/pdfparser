<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\Command;

use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\ColorOperator;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\GraphicsStateOperator;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\TextPositioningOperator;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\TextShowingOperator;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\TextStateOperator;

/** @internal */
class ContentStreamCommand {
    public function __construct(
        public readonly TextPositioningOperator|TextShowingOperator|TextStateOperator|GraphicsStateOperator|ColorOperator $operator,
        public readonly string $operands
    ) {
    }
}
