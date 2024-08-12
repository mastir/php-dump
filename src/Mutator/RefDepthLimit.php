<?php

namespace Mastir\PhpDump\Mutator;

class RefDepthLimit implements LimitRefCount
{
    private $level = 0;

    public function __construct(public readonly int $depth) {}

    public function canContinue(bool &$continue, int $index, int &$total, int $ref_count, int $ref_length, int $count_data): void
    {
        if ($index === $total && $this->level < $this->depth) {
            ++$this->level;
            $total = $count_data;
            $continue = $index < $total;
        }
    }
}
