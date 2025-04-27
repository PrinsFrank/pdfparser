<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Text;

use PrinsFrank\PdfParser\Document\Text\OperatorString\ColorOperator;
use PrinsFrank\PdfParser\Document\Text\OperatorString\GraphicsStateOperator;
use PrinsFrank\PdfParser\Document\Text\OperatorString\TextPositioningOperator;
use PrinsFrank\PdfParser\Document\Text\OperatorString\TextShowingOperator;
use PrinsFrank\PdfParser\Document\Text\OperatorString\TextStateOperator;

/** @internal */
class ContentStreamCommand {
    public function __construct(
        public readonly TextPositioningOperator|TextShowingOperator|TextStateOperator|GraphicsStateOperator|ColorOperator $operator,
        public readonly string $operands
    ) {
    }
}
