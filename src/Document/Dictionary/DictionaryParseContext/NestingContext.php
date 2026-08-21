<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryParseContext;

/** @internal */
class NestingContext {
    private string $currentLevel;

    /** @var array<string, DictionaryParseContext> */
    private array $nestingContext = [];

    /** @var array<string, string> */
    private array $keyBuffer = [];

    /** @var array<string, string> */
    private array $valueBuffer = [];

    public function __construct() {
        $this->currentLevel = '';
    }

    public function incrementNesting(): self {
        $this->currentLevel = (string) ($this->keyBuffer[$this->currentLevel] ?? (int) $this->currentLevel + 1);
        $this->keyBuffer[$this->currentLevel] = '';
        $this->valueBuffer[$this->currentLevel] = '';

        return $this;
    }

    public function decrementNesting(): self {
        array_pop($this->nestingContext);
        unset($this->keyBuffer[$this->currentLevel]);
        unset($this->valueBuffer[$this->currentLevel]);
        $this->currentLevel = (string) array_key_last($this->nestingContext);

        return $this;
    }

    public function setContext(DictionaryParseContext $dictionaryParseContext): self {
        $this->nestingContext[$this->currentLevel] = $dictionaryParseContext;

        return $this;
    }

    public function getContext(): DictionaryParseContext {
        return $this->nestingContext[$this->currentLevel] ?? DictionaryParseContext::ROOT;
    }

    public function addToKeyBuffer(string $char): void {
        $this->keyBuffer[$this->currentLevel] .= $char;
    }

    public function removeCharFromKeyBuffer(): void {
        $this->keyBuffer[$this->currentLevel] = substr($this->keyBuffer[$this->currentLevel], 0, -1);
    }

    public function getKeyBuffer(): string {
        return $this->keyBuffer[$this->currentLevel] ?? '';
    }

    public function addToValueBuffer(string $char): void {
        $this->valueBuffer[$this->currentLevel] .= $char;
    }

    public function removeCharFromValueBuffer(): void {
        $this->valueBuffer[$this->currentLevel] = substr($this->valueBuffer[$this->currentLevel], 0, -1);
    }

    public function getValueBuffer(): string {
        return $this->valueBuffer[$this->currentLevel] ?? '';
    }

    /** @return list<string> */
    public function getKeysFromRoot(): array {
        $keysFromRoot = [];
        foreach ($this->keyBuffer as $keyBuffer) {
            if ($keyBuffer === '') {
                continue;
            }

            $keysFromRoot[] = $keyBuffer;
        }

        return $keysFromRoot;
    }

    public function flush(): self {
        $this->valueBuffer[$this->currentLevel] = '';
        $this->keyBuffer[$this->currentLevel] = '';

        return $this;
    }
}
