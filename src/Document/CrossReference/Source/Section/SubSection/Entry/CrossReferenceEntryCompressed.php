<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\CrossReference\Source\Section\SubSection\Entry;

/** 7.5.8, Table 18, only present in crossReferenceStreams */
class CrossReferenceEntryCompressed {
    /**
     * @see Table 18
     *
     * The object number of the object stream in which this object is
     * stored. (The generation number of the object stream shall be
     * implicitly 0.)
     */
    final public const GENERATION_NUMBER = 0;

    public function __construct(
        public readonly int $storedInStreamWithObjectNumber,
        public readonly int $indexOfThisObjectWithinObjectStream,
    ) {}
}
