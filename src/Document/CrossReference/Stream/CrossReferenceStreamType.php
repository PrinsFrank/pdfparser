<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\CrossReference\Stream;

/** @internal */
enum CrossReferenceStreamType: int {
    case LINKED_LIST_FREE_OBJECT = 0;
    case UNCOMPRESSED_OBJECT = 1;
    case COMPRESSED_OBJECT = 2;
}
