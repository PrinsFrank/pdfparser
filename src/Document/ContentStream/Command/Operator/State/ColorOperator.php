<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State;

/** @internal */
enum ColorOperator: string {
    case SetName = 'CS';
    case SetNameNonStroking = 'cs';
    case SetStrokingColor = 'SC';
    case SetStrokingParams = 'SCN';
    case SetColor = 'sc';
    case SetColorParams = 'scn';
    case SetStrokingColorSpace = 'G';
    case SetColorSpace = 'g';
    case SetStrokingColorDeviceRGB = 'RG';
    case SetColorDeviceRGB = 'rg';
    case SetStrokingColorDeviceCMYK = 'K';
    case SetColorDeviceCMYK = 'k';
}
