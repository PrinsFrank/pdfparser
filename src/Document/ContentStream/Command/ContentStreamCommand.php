<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\Command;

use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\Object\CompatibilityOperator;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\Object\InlineImageOperator;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\Object\MarkedContentOperator;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\Object\TextObjectOperator;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\ClippingPathOperator;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\ColorOperator;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\GraphicsStateOperator;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\PathConstructionOperator;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\PathPaintingOperator;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\TextPositioningOperator;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\TextShowingOperator;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\TextStateOperator;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\Type3FontOperator;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\XObjectOperator;

/** @internal */
class ContentStreamCommand {
    public function __construct(
        public readonly CompatibilityOperator|InlineImageOperator|MarkedContentOperator|TextObjectOperator|ClippingPathOperator|ColorOperator|GraphicsStateOperator|PathConstructionOperator|PathPaintingOperator|TextPositioningOperator|TextShowingOperator|TextStateOperator|Type3FontOperator|XObjectOperator $operator,
        public readonly string $operands
    ) {
    }
}
