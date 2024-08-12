<?php

namespace Mastir\PhpDump\Mutator;

class Mutators
{
    /**
     * @template T
     *
     * @param class-string<T> $type
     * @param Mutator[]       $mutators
     *
     * @return T[]
     */
    public static function ofType(string $type, array $mutators): array
    {
        $result = [];
        foreach ($mutators as $mutator) {
            if (is_a($mutator, $type, true)) {
                $result[] = $mutator;
            }
        }

        return $result;
    }
}
