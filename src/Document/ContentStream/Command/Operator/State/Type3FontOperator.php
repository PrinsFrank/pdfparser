<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State;

/**
 * @internal
 *
 * @specification table 111 - Type 3 font operators
 */
enum Type3FontOperator: string {
    case SetWidth = 'd0';
    case SetWidthAndBoundingBox = 'd1';
}
