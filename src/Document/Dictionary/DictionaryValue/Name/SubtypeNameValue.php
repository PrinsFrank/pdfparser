<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name;

enum SubtypeNameValue: string implements NameValue {
    case CID_FONT_TYPE_0 = 'CIDFontType0';
    case CID_FONT_TYPE_0_C = 'CIDFontType0C';
    case CID_FONT_TYPE_2 = 'CIDFontType2';
    case FORM = 'Form';
    case IMAGE = 'Image';
    case LINK = 'Link';
    case STREAM = 'Stream';
    case TRUE_TYPE = 'TrueType';
    case TYPE_0 = 'Type0';
    case TYPE_1 = 'Type1';
    case TYPE_1_C = 'Type1C';
    case TYPE_3 = 'Type3';
    case XML = 'XML';
    case TEXT = 'Text';
}
