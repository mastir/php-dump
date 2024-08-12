<?php

namespace Mastir\PhpDump\Mutator;

interface LimitRefCount extends Mutator
{
    public function canContinue(bool &$continue, int $index, int &$total, int $ref_count, int $ref_length, int $count_data): void;
}
