<?php

namespace Mastir\PhpDump\Mutator;

interface FilterRef extends Mutator
{
    public function canIncludeRef(int $index, mixed $value, int $size);
}
