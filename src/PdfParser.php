<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser;

use PrinsFrank\PdfParser\Document\CrossReference\CrossReferenceSourceParser;
use PrinsFrank\PdfParser\Document\Document;
use PrinsFrank\PdfParser\Document\Version\VersionParser;
use PrinsFrank\PdfParser\Exception\InvalidArgumentException;
use PrinsFrank\PdfParser\Exception\PdfParserException;
use PrinsFrank\PdfParser\Stream\FileStream;
use PrinsFrank\PdfParser\Stream\InMemoryStream;
use PrinsFrank\PdfParser\Stream\Stream;

/** @api */
final class PdfParser {
    /** @throws PdfParserException */
    public function parse(Stream $stream): Document {
        return new Document(
            $stream,
            VersionParser::parse($stream),
            CrossReferenceSourceParser::parse($stream),
        );
    }

    /**
     * @param bool $useInMemoryStream if set to false, a handle to the file itself will be used. This uses less memory, but will be significantly slower
     * @throws PdfParserException
     */
    public function parseFile(string $filePath, bool $useInMemoryStream = true): Document {
        if ($useInMemoryStream) {
            $fileContent = file_get_contents($filePath);
            if ($fileContent === false) {
                throw new InvalidArgumentException(sprintf('Failed to open file at path "%s"', $filePath));
            }

            $stream = new InMemoryStream($fileContent);
        } else {
            $stream = FileStream::openFile($filePath);
        }

        return $this->parse($stream);
    }

    /**
     * @param bool $useFileCache if set to true, the file will be cached to a temporary file. This will use less memory, but will be significantly slower
     * @throws PdfParserException
     */
    public function parseString(string $content, bool $useFileCache = false): Document {
        if ($useFileCache) {
            $stream = FileStream::fromString($content);
        } else {
            $stream = new InMemoryStream($content);
        }

        return $this->parse($stream);
    }
}
