<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Trailer;

use PrinsFrank\PdfParser\Document\Document;

/**
 * PDF 32000-1:2008
 * The trailer of a PDF file enables a conforming reader to quickly find the cross-reference table and certain special objects
 */
final class Trailer
{
    public readonly Document $document;
    public readonly int      $eofMarkerPos;
    public readonly int      $startXrefMarkerPos;
    public readonly int      $byteOffsetLastCrossReferenceSection;

    public function __construct(Document $document)
    {
        $this->document = $document;
    }

    public function setEofMarkerPos(int $eofMarkerPos): void
    {
        $this->eofMarkerPos = $eofMarkerPos;
    }

    public function setStartXrefMarkerPos(int $startXrefMarkerPos): void
    {
        $this->startXrefMarkerPos = $startXrefMarkerPos;
    }

    public function setByteOffsetLastCrossReferenceSection(mixed $byteOffsetLastCrossReferenceSection): void
    {
        $this->byteOffsetLastCrossReferenceSection = $byteOffsetLastCrossReferenceSection;
    }
}
