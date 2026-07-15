<?php declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/.al-custom.php';

$sampleName = $argv[1] ?? throw new InvalidArgumentException('Missing sample name');
if (preg_match('/^[a-zA-Z0-9_-]+$/', $sampleName) !== 1) {
    throw new InvalidArgumentException('Invalid sample name. Only alphanumeric characters, hyphens, and underscores are allowed.');
}

$document = (new \PrinsFrank\PdfParser\PdfParser())
    ->parseFile(__DIR__ . '/files/' . $sampleName . '/file.pdf');

match ($argv[2] ?? throw new InvalidArgumentException('Missing profile type')) {
    'getText' => $document->getText(),
    'getImages' => array_map(fn(\PrinsFrank\PdfParser\Document\Object\Decorator\XObject $image) => $image->getStream()->toString(), $document->getImages()),
    default => throw new InvalidArgumentException('Please provide a profile type'),
};
