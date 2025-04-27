<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\OperatorString;

/** @internal */
enum TextObjectOperator: string {
    case BEGIN = 'BT';
    case END = 'ET';
}
