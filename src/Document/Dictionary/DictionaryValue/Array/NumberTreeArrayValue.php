<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array;

use Override;
use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryParser;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\DocumentAwareDictionaryValue;
use PrinsFrank\PdfParser\Document\Object\CompressedObject\CompressedObject;
use PrinsFrank\PdfParser\Document\Object\UncompressedObject\UncompressedObject;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
use PrinsFrank\PdfParser\Stream\InMemoryStream;

readonly class NumberTreeArrayValue implements DocumentAwareDictionaryValue {
    /** @param array<int, Dictionary> $numberTree */
    public function __construct(
        public array $numberTree,
    ) {}

    #[Override]
    public static function fromValue(string $valueString, UncompressedObject|CompressedObject|null $object = null): ?self {
        $sanitizedValueString = trim($valueString);
        if (!str_starts_with($sanitizedValueString, '[') || !str_ends_with($sanitizedValueString, ']')) {
            return null;
        }

        $sanitizedValueString = trim(substr(preg_replace('/%[^\n]*/', '', $sanitizedValueString) ?? throw new ParseFailureException(), 1, -1));
        if (preg_match_all('/(-?\d+)\s*(<<(?:[^<>]|<(?!<)|>(?!>)|(?R))*>>)/', $sanitizedValueString, $matches, PREG_SET_ORDER) === 0) {
            return null;
        }

        $pageLabelDictionaries = [];
        foreach ($matches as $match) {
            $pageLabelDictionaries[(int) $match[1]] = DictionaryParser::parse($object, null, $dictionaryStream = new InMemoryStream($match[2]), 0, $dictionaryStream->getSizeInBytes());
        }

        return new self($pageLabelDictionaries);
    }
}
