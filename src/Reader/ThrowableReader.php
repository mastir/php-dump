<?php

namespace Mastir\PhpDump\Reader;

use PHPUnit\Event\Code\Throwable;
use ReflectionClass;

class ThrowableReader implements ObjectReader
{

    const TRACE_NONE = 0;
    const TRACE_STRING = 1;
    const TRACE_FULL = 2;

    public function __construct(public readonly int $mode = self::TRACE_STRING)
    {

    }

    public function canRead(ReflectionClass $class): bool
    {
        return $class->isSubclassOf("Throwable");
    }

    public function read(object $object): array
    {
        return [
           'message' => $object->getMessage(),
           'code' => $object->getCode(),
           'file' => $object->getFile(),
           'line' => $object->getLine(),
           'trace' => $this->getTrace($object),
           'previous' => $object->getPrevious(),
        ];
    }

    private function getTrace(object $object)
    {
        return match ($this->mode) {
            self::TRACE_FULL => $object->getTrace(),
            self::TRACE_STRING => $object->getTraceAsString(),
            self::TRACE_NONE => null,
            default => null,
        };
    }
}
