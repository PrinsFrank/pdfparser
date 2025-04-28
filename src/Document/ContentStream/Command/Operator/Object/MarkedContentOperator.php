<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\Object;

/**
 * @internal
 *
 * @specification Table 352 - Marked-content operators
 */
enum MarkedContentOperator: string {
    case Tag = 'MD';
    case TagProperties = 'DP';
    case BeginMarkedContent = 'BMC';
    case BeginMarkedContentWithProperties = 'BDC';
    case EndMarkedContent = 'EMC';
}
