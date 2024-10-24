<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser;

use PrinsFrank\PdfParser\Document\CrossReference\CrossReferenceSourceParser;
use PrinsFrank\PdfParser\Document\Document;
use PrinsFrank\PdfParser\Document\Object\ObjectStream\ObjectStreamParser;
use PrinsFrank\PdfParser\Document\Page\PageCollectionParser;
use PrinsFrank\PdfParser\Document\Trailer\TrailerSectionParser;
use PrinsFrank\PdfParser\Document\Version\VersionParser;
use PrinsFrank\PdfParser\Exception\PdfParserException;

final class PdfParser {
    /** @throws PdfParserException */
    public function parse(File $file): Document {
        $document = new Document($file);

        return $document->setVersion(VersionParser::parse($document))
            ->setTrailer(TrailerSectionParser::parse($document))
            ->setCrossReferenceSource(CrossReferenceSourceParser::parse($document))
            ->setObjectStreamCollection(ObjectStreamParser::parse($document))
            ->setPageCollection(PageCollectionParser::parse($document))
        ;
    }
}
