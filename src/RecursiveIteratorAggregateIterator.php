<?php

namespace Mastir\PhpDump;

use IteratorAggregate;

/**
 * @template TKey
 * @template T
 *
 * @implements IteratorAggregate<int, T>
 */
class RecursiveIteratorAggregateIterator implements \IteratorAggregate
{
    private int $depth = -1;

    /**
     * @param \Traversable<TKey,T> $input
     */
    public function __construct(private readonly \Traversable $input) {}

    public function getDepth(): int
    {
        return $this->depth;
    }

    /**
     * @return \Generator<int,T>
     *
     * @throws \Exception
     */
    public function getIterator(): \Generator
    {
        $stack = [];
        $iterator = $this->findIterator($this->input);

        while (true) {
            while (null !== $iterator && false === $iterator->valid()) {
                $iterator = array_pop($stack);
            }

            if (null === $iterator) {
                $stack = null;
                $this->depth = -1;

                return;
            }
            $current = $iterator->current();
            $this->depth = \count($stack);

            yield $current;
            $iterator->next();

            if ($current instanceof \Traversable) {
                $stack[] = $iterator;
                $iterator = $this->findIterator($current);
            }
        }
    }

    /**
     * @param \Traversable<mixed> $input
     *
     * @throws \Exception
     */
    private function findIterator(\Traversable $input): \Iterator
    {
        $prev = null;

        while (!$input instanceof \Iterator) {
            if ($prev === $input || !($input instanceof \IteratorAggregate)) {
                throw new \RuntimeException('Invalid iterator');
            }
            $prev = $input;
            $input = $input->getIterator();
        }

        return $input;
    }
}
