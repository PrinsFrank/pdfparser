<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name;

use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Integer\IntegerValue;
use PrinsFrank\PdfParser\Document\Filter\Decode\FlateDecode;
use PrinsFrank\PdfParser\Document\Filter\Decode\LZWFlatePredictorValue;
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
    case JBX_DECODE = 'JPXDecode';
    case CRYPT = 'Crypt';
    case ADOBE_PPK_LITE = 'Adobe.PPKLite';
    case ADOBE_PUB_SEC = 'Adobe.PubSec';
    case ENTRUST_PPKEF = 'Entrust.PPKEF';
    case CICI_SIGN_IT = 'CIC.SignIt';
    case VERISIGN_PPKVS = 'Verisign.PPKVS';

    public function decode(string $content, ?Dictionary $decodeParams): string {
        return match($this) {
            self::DCT_DECODE => $content, // Dont decode JPEG content
            self::FLATE_DECODE => FlateDecode::decode(
                $content,
                $decodeParams !== null && ($predictorValue = LZWFlatePredictorValue::tryFrom((int) $decodeParams->getValueForKey(DictionaryKey::PREDICTOR, IntegerValue::class)?->value)) !== null
                    ? $predictorValue
                    : LZWFlatePredictorValue::None,
                $decodeParams?->getValueForKey(DictionaryKey::COLUMNS, IntegerValue::class)->value ?? 1
            ),
            default => throw new ParseFailureException('Content "' . $content . '" cannot be decoded for filter "' . $this->name . '"')
        };
    }
}
