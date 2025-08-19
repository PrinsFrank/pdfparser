<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Object\Decorator;

use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Integer\IntegerValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\SecurityHandlerNameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\TextStringValue;
use PrinsFrank\PdfParser\Document\Security\SecurityAlgorithm;
use PrinsFrank\PdfParser\Document\Security\StandardSecurityHandlerRevision;
use PrinsFrank\PdfParser\Exception\ParseFailureException;

class EncryptDictionary extends DecoratedObject {
    public function getSecurityHandler(): ?SecurityHandlerNameValue {
        $filterType = $this->getDictionary()->getTypeForKey(DictionaryKey::FILTER);
        if ($filterType === null) {
            return null;
        }

        if ($filterType !== SecurityHandlerNameValue::class) {
            throw new \RuntimeException('Unable to retrieve security handler for non-security handler dictionaries');
        }

        return $this->getDictionary()->getValueForKey(DictionaryKey::FILTER, SecurityHandlerNameValue::class);
    }

    public function getLengthFileEncryptionKeyInBits(): ?int {
        return $this->getDictionary()
            ->getValueForKey(DictionaryKey::LENGTH, IntegerValue::class)
            ?->value;
    }

    public function getOwnerPasswordEntry(): string {
        $textStringValue = $this->getDictionary()
            ->getValueForKey(DictionaryKey::O, TextStringValue::class)
            ?->textStringValue
            ?? throw new ParseFailureException();

        if (str_starts_with($textStringValue, '<') && str_ends_with($textStringValue, '>')) {
            return hex2bin(substr($textStringValue, 1, -1));
        }

        if (str_starts_with($textStringValue, '(') && str_ends_with($textStringValue, ')')) {
            return substr($textStringValue, 1, -1);
        }

        throw new ParseFailureException();
    }

    public function getUserPasswordEntry(): string {
        $textStringValue = $this->getDictionary()
            ->getValueForKey(DictionaryKey::U, TextStringValue::class)
            ?->textStringValue
            ?? throw new ParseFailureException();

        if (str_starts_with($textStringValue, '<') && str_ends_with($textStringValue, '>')) {
            return hex2bin(substr($textStringValue, 1, -1));
        }

        if (str_starts_with($textStringValue, '(') && str_ends_with($textStringValue, ')')) {
            return substr($textStringValue, 1, -1);
        }

        throw new ParseFailureException();
    }

    public function getPValue(): int {
        return $this->getDictionary()
            ->getValueForKey(DictionaryKey::P, IntegerValue::class)
            ?->value
            ?? throw new ParseFailureException('Unable to retrieve p value');
    }

    public function getSecurityAlgorithm(): ?SecurityAlgorithm {
        return $this->getDictionary()
            ->getValueForKey(DictionaryKey::V, SecurityAlgorithm::class);
    }

    public function getStandardSecurityHandlerRevision(): StandardSecurityHandlerRevision {
        return $this->getDictionary()
            ->getValueForKey(DictionaryKey::R, StandardSecurityHandlerRevision::class)
            ?? throw new ParseFailureException('Unable to retrieve standard security handler revision');
    }
}
