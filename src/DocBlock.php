<?php

namespace CrazyFactory\DocBlocks;

use Traversable;

class DocBlock implements \IteratorAggregate
{
    /* @var string */
    protected $raw;

    /* @var DocBlockParameter[] */
    protected $results;

    /**
     * DocBlock constructor.
     *
     * @param object|string $raw
     */
    public function __construct($raw)
    {
        // Use Reflection method or just cast to string
        $this->raw = method_exists($raw, 'getDocComment')
            ? $raw->getDocComment()
            : (string)$raw;
        // Parse the actual docblock
        $this->results = static::parse($this->raw);
    }

    /**
     * Accepts a DocBlock formatted string and returns an array with all key values pairs.
     * Values are trimmed per line. Text blocks have a null-key.
     *
     * @param string $raw
     *
     * @return array[]
     */
    public static function parse(string $raw): array
    {
        $key = null;
        $results = $values = [];
        $lines = explode("\n", $raw);
        // Parse every line individually
        while (!empty($lines)) {
            $content = static::nextLineContent($lines);
            // Determine key/value
            $annotation = preg_match('/@(?<key>\w+)(?<value>.*)/', $content, $matches);
            $value = $annotation
                ? trim($matches['value'])
                : $content;
            // New key OR no content? => Finalize prior and reset!
            if ($annotation || !$value) {
                // There was a key OR a text-block before? => Finalize
                if ($key || !empty($values)) {
                    array_push($results, new DocBlockParameter(
                        $key,
                        !empty($values)
                            ? implode("\n", $values)
                            : null
                    ));
                }
                // Reset
                $key = null;
                $values = [];
            }
            // Set up new attribute
            if ($annotation) {
                $key = $matches['key'];
            }
            // Append the content if any
            if ($value) {
                $values[] = $value;
            }
        }

        return $results;
    }

    /**
     * Removes the first line of the array and returns its cleaned content.
     *
     * @param string[] $lines
     *
     * @return null|string
     */
    protected static function nextLineContent(array &$lines)
    {
        $line = array_splice($lines, 0, 1)[0];
        preg_match('/\\s*[\/]?[*]+\\s*+(?<content>.*)/', $line, $matches);

        // Trim (and handle special last line case)
        return isset($matches['content']) && trim($matches['content']) !== '/'
            ? trim($matches['content'])
            : null;
    }

    /**
     * Gets the title.
     *
     * @return null|string
     */
    public function title(): ?string
    {
        $first = reset($this->results);

        // Return the value of the first result (if it is a text-block)
        return isset($first) && $first->getKey() === null
            ? $first->getValue()
            : null;
    }

    /**
     * Gets the header text.
     *
     * @return string
     */
    public function header(): ?string
    {
        $blocks = [];
        foreach ($this->results as $kv) {
            if ($kv->getKey() === null) {
                $blocks[] = $kv->getValue();
            }
            else {
                break;
            }
        }

        return empty($blocks)
            ? null
            : implode("\n\n", $blocks);
    }

    /**
     * Gets all text-blocks.
     *
     * @return string[]
     */
    public function texts(): array
    {
        return $this->findValues(null);
    }

    /**
     * Get all result values with a matching key
     *
     * @param string|null $key
     *
     * @return string|null[]
     */
    public function findValues(?string $key): array
    {
        return array_map(function($kv) {
            /* @var DocBlockParameter $kv */
            return $kv->getValue();
        }, $this->find($key));
    }

    /**
     * Get all results with a matching key.
     *
     * @param string $key
     *
     * @return DocBlockParameter[]
     */
    public function find(?string $key): array
    {
        return array_filter($this->results, function($kv) use ($key) {
            /* @var DocBlockParameter $kv */
            return $kv->getKey() === $key;
        });
    }

    /**
     * Gets all attributes.
     *
     * @return array[]
     */
    public function attributes(): array
    {
        return array_filter($this->results, function($kv) {
            /* @var DocBlockParameter $kv */
            return $kv->getKey() !== null;
        });
    }

    /**
     * Gets all results with a matching key or dies trying.
     *
     * @param string $key
     *
     * @throws \Exception
     *
     * @return DocBlockParameter[]
     */
    public function findOrFail(?string $key): array
    {
        $results = $this->find($key);
        if (empty($results)) {
            throw new \Exception("DocBlock key '$key' not found!");
        }

        return $results;
    }

    /**
     * Gets the value of the first result with a matching key.
     *
     * @param string $key
     *
     * @return string|null
     */
    public function firstValue(?string $key): ?string
    {
        return ($first = $this->first($key))
            ? $first->getValue()
            : null;
    }

    /**
     * Gets the first value of the first result with a matching key.
     *
     * @param null|string $key
     *
     * @return string
     * @throws \Exception
     */
    public function firstValueOrFail(?string $key): string
    {
        $result = $this->firstValue($key);
        if (!$result) {
            throw new \Exception("DocBlock key '$key' not found!");
        }

        return $result;
    }

    /**
     * Gets the first result with a matching key.
     *
     * @param string $key
     *
     * @return DocBlockParameter|null
     */
    public function first(?string $key): ?DocBlockParameter
    {
        foreach ($this->results as $kv) {
            if ($kv->getKey() === $key) {
                return $kv;
            }
        }

        return null;
    }

    /**
     * Gets the first result with a matching key.
     *
     * @param string $key
     *
     * @throws \Exception
     *
     * @return DocBlockParameter
     */
    public function firstOrFail(?string $key): DocBlockParameter
    {
        $result = $this->first($key);
        if (!$result) {
            throw new \Exception("DocBlock key '$key' not found!");
        }

        return $result;
    }

    /**
     * Retrieve an external iterator
     *
     * @link  http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @since 5.0.0
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->all());
    }

    /**
     * Gets all results.
     *
     * @return DocBlockParameter[]
     */
    public function all(): array
    {
        return array_slice($this->results, 0);
    }
}
