<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Object\Decorator;

use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\HexadecimalStringValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\LiteralStringValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\NameObjectStringValue;
use PrinsFrank\PdfParser\Exception\ParseFailureException;

/** @see 7.11.3 File specification dictionaries */
class FileSpecification extends DecoratedObject {
    public function getFileSpecificationString(): ?string {
        $ufType = $this->getDictionary()->getTypeForKey(DictionaryKey::UF);
        if (in_array($ufType, [HexadecimalStringValue::class, LiteralStringValue::class, NameObjectStringValue::class], true)) {
            return $this->getDictionary()
                ->getStringValue($this->document, DictionaryKey::UF)
                ?->getText() ?? throw new ParseFailureException();
        }

        $fType = $this->getDictionary()->getTypeForKey(DictionaryKey::F);
        if (in_array($fType, [HexadecimalStringValue::class, LiteralStringValue::class, NameObjectStringValue::class], true)) {
            return $this->getDictionary()
                ->getStringValue($this->document, DictionaryKey::F)
                ?->getText() ?? throw new ParseFailureException();
        }

        return null;
    }

    public function getEmbeddedFileStreamDictionary(): ?Dictionary {
        return $this->getDictionary()
            ->getSubDictionary($this->document, DictionaryKey::EF);
    }

    public function getEmbeddedFile(): ?EmbeddedFile {
        return $this->getEmbeddedFileStreamDictionary()
            ?->getObjectForReference($this->document, DictionaryKey::F, EmbeddedFile::class);
    }
}
