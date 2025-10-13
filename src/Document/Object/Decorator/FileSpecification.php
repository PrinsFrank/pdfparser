<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Object\Decorator;

use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\TextStringValue;
use PrinsFrank\PdfParser\Exception\ParseFailureException;

/** @see 7.11.3 File specification dictionaries */
class FileSpecification extends DecoratedObject {
    public function getFileSpecificationString(): ?string {
        $ufType = $this->getDictionary()->getTypeForKey(DictionaryKey::UF);
        if ($ufType === TextStringValue::class) {
            return $this->getDictionary()
                ->getValueForKey(DictionaryKey::UF, $ufType)
                ?->getText() ?? throw new ParseFailureException();
        }

        $fType = $this->getDictionary()->getTypeForKey(DictionaryKey::F);
        if ($fType === TextStringValue::class) {
            return $this->getDictionary()
                ->getValueForKey(DictionaryKey::F, $fType)
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
