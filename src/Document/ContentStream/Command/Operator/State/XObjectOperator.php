<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State;

/**
 * @internal
 *
 * @specification table 86 - XObject operator
 */
enum XObjectOperator: string {
    case Paint = 'Do';
}
