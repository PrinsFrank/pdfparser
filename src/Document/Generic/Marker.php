<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Generic;

/** @internal */
enum Marker: string {
    case VERSION = '%PDF-';
    case EOF = '%%EOF';
    case TRAILER = 'trailer';
    case XREF = 'xref';
    case START_XREF = 'startxref';
    case STREAM = 'stream';
    case END_STREAM = 'endstream';
    case OBJ = 'obj';
    case END_OBJ = 'endobj';

    public function length(): int {
        return strlen($this->value);
    }
}
