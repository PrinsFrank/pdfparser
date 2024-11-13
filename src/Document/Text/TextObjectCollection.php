<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Text;

use Override;
use Stringable;

class TextObjectCollection implements Stringable {
    /** @var list<TextObject> */
    public array $textObjects = [];

    public function addTextObject(TextObject $textObject): self {
        $this->textObjects[] = $textObject;

        return $this;
    }

    #[Override]
    public function __toString(): string {
        return implode('', $this->textObjects);
    }
}
