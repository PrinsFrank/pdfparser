<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Object\Decorator;

use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Boolean\BooleanValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Integer\IntegerValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\SecurityHandlerNameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\TextStringValue;
use PrinsFrank\PdfParser\Document\Security\SecurityAlgorithm;
use PrinsFrank\PdfParser\Document\Security\StandardSecurityHandlerRevision;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
use PrinsFrank\PdfParser\Exception\RuntimeException;

class EncryptDictionary extends DecoratedObject {
    public function getSecurityHandler(): ?SecurityHandlerNameValue {
        $filterType = $this->getDictionary()->getTypeForKey(DictionaryKey::FILTER);
        if ($filterType === null) {
            return null;
        }

        if ($filterType !== SecurityHandlerNameValue::class) {
            throw new RuntimeException('Unable to retrieve security handler for non-security handler dictionaries');
        }

        return $this->getDictionary()->getValueForKey($this->document, DictionaryKey::FILTER, SecurityHandlerNameValue::class);
    }

    public function getLengthFileEncryptionKeyInBits(): ?int {
        return $this->getDictionary()
            ->getValueForKey($this->document, DictionaryKey::LENGTH, IntegerValue::class)
            ?->value;
    }

    public function getOwnerPasswordEntry(): string {
        $binaryString = $this->getDictionary()
            ->getValueForKey($this->document, DictionaryKey::O, TextStringValue::class)
            ?->getBinaryString()
            ?? throw new ParseFailureException();

        $binaryString = str_pad($binaryString, 32, "\x00");
        if ($this->getStandardSecurityHandlerRevision()->value <= 4) {
            return substr($binaryString, 0, 32);
        }

        return $binaryString;
    }

    public function getUserPasswordEntry(): string {
        $binaryString = $this->getDictionary()
            ->getValueForKey($this->document, DictionaryKey::U, TextStringValue::class)
            ?->getBinaryString()
            ?? throw new ParseFailureException();

        $binaryString = str_pad($binaryString, 32, "\x00");
        if ($this->getStandardSecurityHandlerRevision()->value <= 4) {
            return substr($binaryString, 0, 32);
        }

        return $binaryString;
    }

    public function getPValue(): int {
        return $this->getDictionary()
            ->getValueForKey($this->document, DictionaryKey::P, IntegerValue::class)
            ->value
            ?? throw new ParseFailureException('Unable to retrieve p value');
    }

    public function getSecurityAlgorithm(): ?SecurityAlgorithm {
        return $this->getDictionary()
            ->getValueForKey($this->document, DictionaryKey::V, SecurityAlgorithm::class);
    }

    public function getStandardSecurityHandlerRevision(): StandardSecurityHandlerRevision {
        return $this->getDictionary()
            ->getValueForKey($this->document, DictionaryKey::R, StandardSecurityHandlerRevision::class)
            ?? throw new ParseFailureException('Unable to retrieve standard security handler revision');
    }

    public function isMetadataEncrypted(): bool {
        $encryptMetadata = $this->getDictionary()
            ->getValueForKey($this->document, DictionaryKey::ENCRYPT_METADATA, BooleanValue::class);

        return $encryptMetadata === null || $encryptMetadata->value; // If key is not present, assume encrypted metadata
    }
}
