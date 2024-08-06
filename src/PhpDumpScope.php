<?php

namespace Mastir\PhpDump;

/**
 * @template-implements \IteratorAggregate<int, PhpDumpScope>
 */
class PhpDumpScope implements \IteratorAggregate
{
    /**
     * @var list<PhpDumpScope>
     */
    private array $scopes;

    /**
     * @param array<string,mixed> $vars
     * @param array<string,mixed> $extra
     */
    public function __construct(public readonly string $title, public readonly array $vars, public readonly array $extra = [])
    {
        $this->scopes = [];
    }

    /**
     * @param array<string,mixed> $vars
     * @param array<string,mixed> $extra
     */
    public function addScope(string $title, array $vars, array $extra = []): PhpDumpScope
    {
        return $this->scopes[] = new PhpDumpScope($title, $vars, $extra);
    }

    /**
     * @return \Traversable<int,PhpDumpScope>
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->scopes);
    }
}
