<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State;

use Override;
use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\State\Interaction\InteractsWithColorState;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\ColorState;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\DeviceColorSpaceNameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\NameObjectStringValue;

/**
 * @internal
 *
 * @specification table 73 - Colour operators
 */
enum ColorOperator: string implements InteractsWithColorState {
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

    #[Override]
    public function applyToColorState(string $operands, ColorState $colorState): ColorState {
        return match ($this) {
            self::SetName => new ColorState(
                NameObjectStringValue::fromValue($operands),
                $colorState->colorStrokingOperations,
                $colorState->colorSpaceNonStrokingOperations,
                $colorState->colorNonStrokingOperations,
            ),
            self::SetNameNonStroking => new ColorState(
                $colorState->colorSpaceStrokingOperations,
                $colorState->colorStrokingOperations,
                NameObjectStringValue::fromValue($operands),
                $colorState->colorNonStrokingOperations,
            ),
            self::SetStrokingColor,
            self::SetStrokingParams => new ColorState(
                $colorState->colorSpaceStrokingOperations,
                array_map('floatval', explode(' ', $operands)),
                $colorState->colorSpaceNonStrokingOperations,
                $colorState->colorNonStrokingOperations,
            ),
            self::SetColor,
            self::SetColorParams => new ColorState(
                $colorState->colorSpaceStrokingOperations,
                $colorState->colorStrokingOperations,
                $colorState->colorSpaceNonStrokingOperations,
                array_map('floatval', explode(' ', $operands)),
            ),
            self::SetStrokingColorSpace => new ColorState(
                DeviceColorSpaceNameValue::DeviceGray,
                array_map('floatval', explode(' ', $operands)),
                $colorState->colorSpaceNonStrokingOperations,
                $colorState->colorNonStrokingOperations,
            ),
            self::SetColorSpace => new ColorState(
                $colorState->colorSpaceStrokingOperations,
                $colorState->colorStrokingOperations,
                DeviceColorSpaceNameValue::DeviceGray,
                array_map('floatval', explode(' ', $operands)),
            ),
            self::SetStrokingColorDeviceRGB => new ColorState(
                DeviceColorSpaceNameValue::DeviceRGB,
                array_map('floatval', explode(' ', $operands)),
                $colorState->colorSpaceNonStrokingOperations,
                $colorState->colorNonStrokingOperations,
            ),
            self::SetColorDeviceRGB => new ColorState(
                $colorState->colorSpaceStrokingOperations,
                $colorState->colorStrokingOperations,
                DeviceColorSpaceNameValue::DeviceRGB,
                array_map('floatval', explode(' ', $operands)),
            ),
            self::SetStrokingColorDeviceCMYK => new ColorState(
                DeviceColorSpaceNameValue::DeviceCMYK,
                array_map('floatval', explode(' ', $operands)),
                $colorState->colorSpaceNonStrokingOperations,
                $colorState->colorNonStrokingOperations,
            ),
            self::SetColorDeviceCMYK => new ColorState(
                $colorState->colorSpaceStrokingOperations,
                $colorState->colorStrokingOperations,
                DeviceColorSpaceNameValue::DeviceCMYK,
                array_map('floatval', explode(' ', $operands)),
            ),
        };
    }
}
