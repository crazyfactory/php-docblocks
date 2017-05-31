<?php

namespace CrazyFactory\DocBlocks;

class DocBlockParameter
{
    protected $key;
    protected $value;

    public function __construct(?string $key = null, ?string $value = null)
    {
        // accept only non-empty strings as key
        if ($key !== null && trim($key) !== '') {
            $this->key = trim($key);
        }

        // accept only non-empty strings as value
        if ($value !== null && trim($value) !== '') {
            $this->value = trim($value);
        }
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }
}
