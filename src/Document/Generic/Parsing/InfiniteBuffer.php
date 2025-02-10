<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Generic\Parsing;

use Override;

/** @internal */
class InfiniteBuffer {
    private string $buffer = '';

    public function addChar(string $char): self {
        $this->buffer .= $char;

        return $this;
    }

    public function flush(): self {
        return $this->setValue('');
    }

    #[Override]
    public function __toString(): string {
        return $this->buffer;
    }

    public function getLength(): int {
        return strlen($this->buffer);
    }

    public function isEmpty(): bool {
        return $this->getLength() === 0;
    }

    public function setValue(string $buffer): self {
        $this->buffer = $buffer;

        return $this;
    }

    public function removeChar(int $nChars): self {
        if ($this->buffer !== '') {
            $this->buffer = substr($this->buffer, 0, -$nChars);
        }

        return $this;
    }
}
