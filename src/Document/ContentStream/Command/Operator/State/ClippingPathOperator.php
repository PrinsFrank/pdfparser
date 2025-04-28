<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State;

/**
 * @internal
 *
 * @specification Table 60 - Clipping path operators
 */
enum ClippingPathOperator: string {
    case INTERSECT = 'W';
    case INTERSECT_EVEN_ODD = 'W*';
}
