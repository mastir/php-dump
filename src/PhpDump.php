<?php

namespace Mastir\PhpDump;

use Ds\Vector;
use Mastir\PhpDump\Mutator\FilterRef;
use Mastir\PhpDump\Mutator\LimitRefCount;
use Mastir\PhpDump\Mutator\Mutator;
use Mastir\PhpDump\Mutator\Mutators;
use Mastir\PhpDump\Reader\ObjectReader;

/**
 * Describes any php structures
 * adds referred data set for dereference and deep analyze
 * top level variable described as [name|key|index] type [reference] (value / title).
 *
 * control seq:
 * \x01 - block start
 * \x02 - block end
 * \x05 - reference (id)
 * \x06 - ref block start
 * block types:
 * a = array
 * b = boolean
 * f = float / double
 * i = integer
 * o = object
 * n = null
 * r = resource
 * s = string
 * u = unknown
 * v = variable (string name, block value)
 * k = key (int key, block value)
 * q = scope (string title, ?array params, variable[])
 * examples:
 * \x01b1\x04 - TRUE, block{b = boolean, 1 = TRUE}
 * \x01o\x05\0\0\0\x0CTestObject#1\x04 - object <a href="#123>TestObject#1</a>, block{o = object, reference 123, string(TestObject#1)}
 * \x01a\0\0\0\3\0\0\0\x07[1,2,3]\x01k\0\0\0\1\x01i\0\0\0\1\x04\x04\x04 - array(3) [1,2,[3]], block{a = array, int(3), text(7)"[1,2,3]"}, block{0: 1,1: 2, 2: 3}
 * \x01a\x05222\x02[TestObject(12),"Test string",...]\x04 - array <a href="#222">[TestObject(12),"Test string",...]</a>
 */
class PhpDump
{
    public const BLOCK_OPEN = "\x01";
    public const BLOCK_CLOSE = "\x02";
    public const REF_BLOCK = "\x06";
    public const INC_BLOCK = "\x07";

    public int $titleLengthLimit = 100;

    /**
     * @var ObjectReader[]
     */
    public array $readers = [];

    /**
     * @var Mutator[]
     */
    public array $mutators = [];

    /**
     * @var Vector<PhpDumpScope>
     */
    private Vector $scopes;

    /**
     * @var Vector<array|object|string>
     */
    private Vector $data;

    public function __construct()
    {
        $this->scopes = new Vector();
        $this->data = new Vector();
    }

    /**
     * @param array<string,mixed> $vars
     * @param array<string,mixed> $extras
     */
    public function addScope(string $name, array $vars, array $extras = []): PhpDumpScope
    {
        return $this->scopes[] = new PhpDumpScope($name, $vars, $extras);
    }

    /**
     * @param list<int> $include
     */
    public function build(array $include = []): string
    {
        $dump = '';
        $prev_level = -1;
        $iterator = new RecursiveIteratorAggregateIterator(new \ArrayIterator($this->scopes->toArray()));
        foreach ($iterator as $scope) {
            $current_level = $iterator->getDepth();
            for ($i = $current_level; $i <= $prev_level; ++$i) {
                $dump .= self::BLOCK_CLOSE;
            }
            $dump .= self::BLOCK_OPEN.'q'.$this->string($scope->title);
            if ($scope->extra) {
                $dump .= $this->array($scope->extra);
            }
            $dump .= $this->variableList($scope->vars);
            $prev_level = $current_level;
        }
        for ($i = 0; $i <= $prev_level; ++$i) {
            $dump .= self::BLOCK_CLOSE;
        }
        $links = '';
        $refs = '';
        $offset = 0;
        $count = 0;
        $index = 0;
        $total = count($this->data);
        $ref_filter = Mutators::ofType(FilterRef::class, $this->mutators);
        $refs_limit = Mutators::ofType(LimitRefCount::class, $this->mutators);
        $continue = $index < $total;
        while ($continue) {
            $ref = $this->data[$index];
            $block = $this->buildRefData($ref);
            $include_ref = true;
            foreach ($ref_filter as $mutator) {
                $include_ref &= $mutator->canIncludeRef($index, $ref, strlen($block));
            }
            if ($include_ref) {
                $refs .= $block;
                $links .= $this->int($offset);
                $offset += strlen($block);
                ++$count;
            }
            ++$index;
            $continue = $index < $total;
            foreach ($refs_limit as $mutator) {
                $mutator->canContinue($continue, $index, $total, $count, $offset, $this->data->count());
            }
        }

        $inc = '';
        if ($include) {
            $inc_count = 0;
            $inc_links = '';
            foreach ($include as $i) {
                if ($i > $count && $i < $this->data->count()) {
                    ++$inc_count;
                    $block = $this->buildRefData($this->data[$i]);
                    $inc_links .= $this->int($i);
                    $inc_links .= $this->int($offset);
                    $refs .= $block;
                    $offset += strlen($block);
                    ++$count;
                }
            }
            $inc = self::INC_BLOCK.$this->int($count).$inc_links;
        }

        return $dump.self::REF_BLOCK.$this->int($count).$links.$inc.$refs;
    }

    public function buildRefData(array|object|string $value): string
    {
        if (is_string($value)) {
            return $this->block('s', $this->string($value));
        }
        if (is_array($value)) {
            return $this->arrayData($value);
        }
        if (is_object($value)) {
            return $this->objectData($value);
        }

        throw new \InvalidArgumentException('Referred value must be a string, object or an array');
        //        return '';
    }

    /**
     * @param array<null|bool|int|string,mixed> $array
     */
    public function arrayData(array $array): string
    {
        return $this->block(
            'a',
            $this->string($this->arrayTitle($array)),
            $this->variableList($array)
        );
    }

    public function objectData(object $value): string
    {
        return $this->block(
            'o',
            $this->string($this->objectTitle($value)),
            $this->string(get_class($value)),
            $this->variableList($this->getObjectProps($value))
        );
    }

    public function value(mixed $input): string
    {
        if (null === $input) {
            return $this->block('n');
        }
        if (is_scalar($input)) {
            return $this->scalar($input);
        }
        if (is_resource($input)) {
            return $this->resource($input);
        }
        if (is_array($input)) {
            return $this->array($input);
        }
        if (is_object($input)) {
            return $this->object($input);
        }

        return $this->block('u');
    }

    public function title(mixed $input, int $limit = -1): string
    {
        if (-1 === $limit) {
            $limit = $this->titleLengthLimit;
        }
        if (null === $input) {
            return 'null';
        }
        if (is_bool($input)) {
            return $input ? 'true' : 'false';
        }
        if (is_int($input)) {
            return ''.$input;
        }
        if (is_float($input)) {
            return sprintf('%.2f', $input);
        }
        if (is_string($input)) {
            if (strlen($input) < 20) {
                return '"'.addcslashes($input, '"').'"';
            }

            return '"'.addcslashes(substr($input, 0, 17), '"').'..."';
        }
        if (is_object($input)) {
            return $this->objectTitle($input, $limit);
        }
        if (is_array($input)) {
            return $this->arrayTitle($input, $limit);
        }
        if (is_resource($input)) {
            return 'resource(#'.get_resource_id($input).')';
        }

        return '';
    }

    public function objectTitle(object $input, int $limit = -1): string
    {
        // @todo add more info (identity, some props, iterable support)
        return get_class($input);
    }

    /**
     * @param array<null|bool|int|string,mixed> $input
     */
    public function arrayTitle(array $input, int $limit = -1): string
    {
        if (-1 === $limit) {
            $limit = $this->titleLengthLimit;
        }
        $title = '[';
        $index = 0;
        foreach ($input as $key => $value) {
            $item = $this->title($value);
            if ($key !== $index++) {
                $item = $key.':'.$item;
            }
            if ((strlen($title) + strlen($item) + 2) > $limit) {
                return $title.', ...]';
            }
            if (1 !== $index) {
                $title .= ', ';
            }
            $title .= $item;
        }

        return $title.']';
    }

    public function scalar(mixed $value): string
    {
        if (is_bool($value)) {
            return $this->block('b', $value ? '1' : '0');
        }
        if (is_int($value)) {
            return $this->block('i', $this->int($value));
        }
        if (is_float($value)) {
            return $this->block('f', $this->float($value));
        }
        if (is_string($value)) {
            $len = strlen($value);
            if ($len < $this->titleLengthLimit) {
                return $this->block('s', $this->string($value));
            }
            $link = $this->createLink($value);

            return $this->block($this->link($link), 's', $this->string(substr($value, 0, $this->titleLengthLimit - 3).'...'));
        }

        return $this->block('u');
    }

    public function array(array $array): string
    {
        $link = $this->createLink($array);
        $title = $this->arrayTitle($array);

        return $this->block(
            $this->link($link),
            'a',
            $this->string($title),
            $this->int(count($array)),
        );
    }

    public function object(object $value): string
    {
        $idx = $this->createLink($value);
        $ref = $this->link($idx);
        $title = $this->objectTitle($value);

        return $this->block($this->link($idx), 'o', $this->string($title));
    }

    public function resource($input): string
    {
        return $this->block(
            'r',
            $this->int(get_resource_id($input)),
            $this->string(get_resource_type($input))
        );
    }

    public function link(int $idx): string
    {
        return "\x05".$this->int($idx);
    }

    public function string(string $string): string
    {
        return $this->int(strlen($string)).$string;
    }

    public function int(int $integer): string
    {
        return pack('V', $integer);
    }

    public function float(float $float): string
    {
        return pack('g', $float);
    }

    public function block(string ...$parts): string
    {
        return self::BLOCK_OPEN.implode('', $parts).self::BLOCK_CLOSE;
    }

    public function stringBlock(string $string): string
    {
        return $this->block('s', $string);
    }

    public function variable(int|string $name, string $data): string
    {
        if (is_int($name)) {
            return $this->block('k', $this->int($name), $data);
        }

        return $this->block('v', $this->string($name), $data);
    }

    public function variableList(array $input): string
    {
        $result = $this->int(count($input));
        foreach ($input as $key => $value) {
            $result .= $this->variable($key, $this->value($value));
        }

        return $result;
    }

    public function createLink(mixed $value): int
    {
        $idx = $this->data->find($value);
        if (false === $idx) {
            $idx = $this->data->count();
            $this->data->push($value);
        }

        return $idx;
    }

    private function getObjectProps(object $value): array
    {
        $ref = new \ReflectionClass($value);
        foreach ($this->readers as $reader) {
            if ($reader->canRead($ref)) {
                return $reader->read($value);
            }
        }

        return [];
    }
}
