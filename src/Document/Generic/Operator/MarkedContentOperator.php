<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Generic\Operator;

/** @internal */
enum MarkedContentOperator: string {
    case Tag = 'MD';
    case TagProperties = 'DP';
    case BeginMarkedContent = 'BMC';
    case BeginMarkedContentWithProperties = 'BDC';
    case EndMarkedContent = 'EMC';
}
