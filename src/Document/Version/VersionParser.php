<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Version;

use PrinsFrank\PdfParser\Document\Generic\Marker;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
use PrinsFrank\PdfParser\Stream\Stream;

class VersionParser {
    public static function parse(Stream $stream): Version {
        if ($stream->read(0, Marker::VERSION->length()) !== Marker::VERSION->value) {
            throw new ParseFailureException('Unexpected start of file format. is this a pdf?');
        }

        $versionString = $stream->read(strlen(Marker::VERSION->value), Version::length());
        $version = Version::tryFrom($versionString);
        if ($version === null) {
            throw new ParseFailureException(sprintf('Unsupported PDF version "%s"', $versionString));
        }

        return $version;
    }
}
