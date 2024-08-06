<?php

namespace Mastir\PhpDump\Reader;

class ThrowableReader implements ObjectReader
{
    public const TRACE_NONE = 0;
    public const TRACE_STRING = 1;
    public const TRACE_FULL = 2;

    public function __construct(public readonly int $mode = self::TRACE_STRING) {}

    public function canRead(\ReflectionClass $class): bool
    {
        return $class->isSubclassOf(\Throwable::class);
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

    private function getTrace(object $object): null|array|string
    {
        return match ($this->mode) {
            self::TRACE_FULL => $object->getTrace(),
            self::TRACE_STRING => $object->getTraceAsString(),
            default => null,
        };
    }
}
