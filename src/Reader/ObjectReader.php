<?php

namespace Mastir\PhpDump\Reader;

use ReflectionClass;

interface ObjectReader
{

    public function canRead(ReflectionClass $class): bool;

    public function read(object $object) : array;

}
