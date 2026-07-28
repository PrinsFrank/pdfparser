<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Extraction\Markdown\Table;

use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\PositionedTextElement;
use PrinsFrank\PdfParser\Document\Object\Decorator\Page;

class ColumnBounds {
    private const GROW_TOLERANCE_PT = 20;

    /** @var PositionedTextElement[] */
    public array $positionedTextElements;

    public function __construct(
        PositionedTextElement... $positionedTextElements,
    ) {
        $this->positionedTextElements = $positionedTextElements;
    }

    public function canFitElement(PositionedTextElement $positionedTextElement, Page $page): bool {
        $elementLeft = $positionedTextElement->absoluteMatrix->offsetX;
        $elementRight = $positionedTextElement->absoluteMatrix->offsetX + $positionedTextElement->getAdvanceWidth($page);

        $columnRight = $this->getRight($page);
        $columnLeft = $this->getLeft();

        return $elementLeft >= $columnLeft - self::GROW_TOLERANCE_PT
            && $elementRight <= $columnRight + self::GROW_TOLERANCE_PT;
    }

    public function addElement(PositionedTextElement $positionedTextElement): void {
        $this->positionedTextElements[] = $positionedTextElement;
    }

    private function getLeft(): float {
        return min(
            array_map(
                fn(PositionedTextElement $positionedTextElement): float => $positionedTextElement->absoluteMatrix->offsetX,
                $this->positionedTextElements,
            ),
        );
    }

    private function getRight(Page $page): float {
        return max(
            array_map(
                fn(PositionedTextElement $positionedTextElement): float => $positionedTextElement->absoluteMatrix->offsetX + $positionedTextElement->getAdvanceWidth($page),
                $this->positionedTextElements,
            ),
        );
    }
}
