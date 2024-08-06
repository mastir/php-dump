<?php

namespace Mastir\PhpDump\Reader;

class SimpleReader implements ObjectReader
{
    public function canRead(\ReflectionClass $class): bool
    {
        return true;
    }

    public function read(object $object): array
    {
        return get_object_vars($object);
    }
}
