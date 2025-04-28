<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\Object;

/**
 * @internal
 *
 * @specification Table 105 - Text object operators
 */
enum TextObjectOperator: string {
    case BEGIN = 'BT';
    case END = 'ET';
}
