<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\Object;

/** @internal */
enum TextObjectOperator: string {
    case BEGIN = 'BT';
    case END = 'ET';
}
