<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name;

use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Integer\IntegerValue;
use PrinsFrank\PdfParser\Document\Filter\Decode\CCITTFaxDecode;
use PrinsFrank\PdfParser\Document\Filter\Decode\FlateDecode;
use PrinsFrank\PdfParser\Document\Filter\Decode\LZWFlatePredictorValue;
use PrinsFrank\PdfParser\Document\Image\ImageType;
use PrinsFrank\PdfParser\Exception\ParseFailureException;

enum FilterNameValue: string implements NameValue {
    case ASCII_HEX_DECODE = 'ASCIIHexDecode';
    case ASCII_85_DECODE = 'ASCII85Decode';
    case LZW_DECODE = 'LZWDecode';
    case FLATE_DECODE = 'FlateDecode';
    case RUN_LENGTH_DECODE = 'RunLengthDecode';
    case CCITT_FAX_DECODE = 'CCITTFaxDecode';
    case JBIG2_DECODE = 'JBIG2Decode';
    case DCT_DECODE = 'DCTDecode'; // Grayscale or color image data encoded in JPEG baseline format
    case JPX_DECODE = 'JPXDecode';
    case CRYPT = 'Crypt';
    case ADOBE_PPK_LITE = 'Adobe.PPKLite';
    case ADOBE_PUB_SEC = 'Adobe.PubSec';
    case ENTRUST_PPKEF = 'Entrust.PPKEF';
    case CICI_SIGN_IT = 'CIC.SignIt';
    case VERISIGN_PPKVS = 'Verisign.PPKVS';

    /** @return string in binary format */
    public function decodeBinary(string $content, ?Dictionary $dictionary): string {
        $decodeParams = $dictionary?->getValueForKey(DictionaryKey::DECODE_PARMS, Dictionary::class);

        return match($this) {
            self::JPX_DECODE,
            self::DCT_DECODE => $content, // Don't decode JPEG content
            self::FLATE_DECODE => FlateDecode::decodeBinary(
                $content,
                $decodeParams !== null && ($predictorValue = LZWFlatePredictorValue::tryFrom((int) $decodeParams->getValueForKey(DictionaryKey::PREDICTOR, IntegerValue::class)?->value)) !== null
                    ? $predictorValue
                    : LZWFlatePredictorValue::None,
                $decodeParams?->getValueForKey(DictionaryKey::COLUMNS, IntegerValue::class)->value ?? 1
            ),
            self::CCITT_FAX_DECODE => CCITTFaxDecode::addHeaderAndIFD(
                $content,
                $decodeParams?->getValueForKey(DictionaryKey::COLUMNS, IntegerValue::class)->value
                    ?? throw new ParseFailureException('Missing columns'),
                $decodeParams->getValueForKey(DictionaryKey::ROWS, IntegerValue::class)->value
                    ?? $dictionary->getValueForKey(DictionaryKey::HEIGHT, IntegerValue::class)->value
                    ?? throw new ParseFailureException('Missing rows'),
                $decodeParams->getValueForKey(DictionaryKey::K, IntegerValue::class)->value
                    ?? throw new ParseFailureException('Missing K'),
            ),
            default => throw new ParseFailureException('Content "' . $content . '" cannot be decoded for filter "' . $this->name . '"')
        };
    }

    public function getImageType(): ?ImageType {
        return match ($this) {
            self::LZW_DECODE => ImageType::TIFF,
            self::FLATE_DECODE => ImageType::PNG,
            self::RUN_LENGTH_DECODE => ImageType::RAW,
            self::CCITT_FAX_DECODE => ImageType::TIFF_FAX,
            self::DCT_DECODE => ImageType::JPEG,
            self::JPX_DECODE => ImageType::JPEG2000,
            default => null,
        };
    }
}
