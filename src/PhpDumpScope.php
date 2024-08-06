<?php

namespace Mastir\PhpDump;

use ArrayIterator;
use Ds\Vector;
use IteratorAggregate;
use Traversable;

class PhpDumpScope implements IteratorAggregate
{
    /**
     * @var Vector<PhpDumpScope>
     */
    private Vector $scopes;

    /**
     * @param string $title
     * @param array<string,mixed> $vars
     * @param array<string,mixed> $extra
     */
    public function __construct(public readonly string $title, public readonly array $vars, public readonly array $extra = []){
        $this->scopes = new Vector();
    }

    /**
     * @param string $title
     * @param array<string,mixed> $vars
     * @param array<string,mixed> $extra
     * @return PhpDumpScope
     */
    public function addScope(string $title, array $vars, array $extra = []): PhpDumpScope
    {
        return $this->scopes[] = new PhpDumpScope($title, $vars, $extra);
    }

    /**
     * @return ArrayIterator<PhpDumpScope>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->scopes->toArray());
    }

}
