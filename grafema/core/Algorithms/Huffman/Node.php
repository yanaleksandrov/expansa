<?php

declare(strict_types=1);

namespace Grafema\Algorithms\Huffman;

class Node
{
    public function __construct(
	    protected array|string $chars,
		protected int          $weight = 1,
		protected ?array       $leafs  = null
    ) {
        $this->chars = is_array($chars) ? $chars : [$chars];
    }

    public static function create(string $char): Node
    {
        return new static($char);
    }

    public static function merge(Node $left, Node $right): Node
    {
        return new static(
            array_merge($left->chars(), $right->chars()),
            $left->weight + $right->weight,
            [$left, $right]
        );
    }

    public function chars(int $index = null): null|array|string
    {
        if (! is_null($index)) {
            return $this->chars[$index] ?? null;
        }
        return $this->chars;
    }

    public function length(): int
    {
        return count($this->chars);
    }

    public function weight(): int
    {
        return $this->weight;
    }

    public function leafs(int $direction = null): Node|array|null
    {
        if (! is_null($direction)) {
            return $this->leafs[$direction] ?? null;
        }
        return $this->leafs;
    }

    public function left(): ?Node
    {
        return $this->leafs[0] ?? null;
    }

    public function right(): ?Node
    {
        return $this->leafs[1] ?? null;
    }
}