<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\CrossReference\Source;

use Override;
use PrinsFrank\PdfParser\Document\CrossReference\Source\Section\CrossReferenceSection;
use PrinsFrank\PdfParser\Document\CrossReference\Source\Section\SubSection\Entry\CrossReferenceEntryCompressed;
use PrinsFrank\PdfParser\Document\CrossReference\Source\Section\SubSection\Entry\CrossReferenceEntryInUseObject;
use PrinsFrank\PdfParser\Document\Document;
use Throwable;

class RecoveredCrossReferenceSource extends CrossReferenceSource {
    /**
     * @param array<int, int> $recoveredByteOffsetMap where the key is the object nr and the value the byte offset
     *
     * @no-named-arguments
     */
    public function __construct(
        private readonly array $recoveredByteOffsetMap,
        CrossReferenceSection... $crossReferenceSections,
    ) {
        parent::__construct(...$crossReferenceSections);
    }

    #[Override]
    public function getCrossReferenceEntry(int $objNumber, Document $document): CrossReferenceEntryInUseObject|CrossReferenceEntryCompressed|null {
        try {
            $crossReferenceEntry = parent::getCrossReferenceEntry($objNumber, $document);
        } catch (Throwable) {
            $crossReferenceEntry = null;
        }

        if ($crossReferenceEntry instanceof CrossReferenceEntryInUseObject
            && $document->stream->read($crossReferenceEntry->byteOffsetInDecodedStream, strlen($expectedStartObjMarker = sprintf('%d %d obj', $objNumber, $crossReferenceEntry->generationNumber))) === $expectedStartObjMarker) {
            return $crossReferenceEntry;
        }

        if (array_key_exists($objNumber, $this->recoveredByteOffsetMap)) {
            return new CrossReferenceEntryInUseObject($this->recoveredByteOffsetMap[$objNumber], 0);
        }

        return $crossReferenceEntry;
    }
}
