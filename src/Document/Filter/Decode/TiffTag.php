<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Filter\Decode;

enum TiffTag: int {
    case ImageWidth = 256;
    case ImageHeight = 257;
    case Compression = 259;
    case PhotometricInterpretation = 262;
    case StripOffsets = 273;
    case RowsPerStrip = 278;
    case StripByteCounts = 279;
}
