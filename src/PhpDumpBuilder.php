<?php

namespace Mastir\PhpDump;

use Mastir\PhpDump\Reader\SimpleReader;
use Mastir\PhpDump\Reader\ThrowableReader;

class PhpDumpBuilder
{
    private array $includes = [];

    public function __construct(public readonly PhpDump $dump = new PhpDump(), ?array $readers = null)
    {
        if (!$readers) {
            $readers = [
                new ThrowableReader(ThrowableReader::TRACE_STRING),
                new SimpleReader(),
            ];
        }
        $this->dump->readers = $readers;
    }

    /**
     * @param list<int> $includes
     */
    public function include(array $includes): void
    {
        $this->includes = array_merge($this->includes, $includes);
    }

    public function build(): string
    {
        return $this->dump->build($this->includes);
    }

    public function addException(\Throwable $exception, $withPrevious = true): PhpDumpScope
    {
        do {
            $scope = $this->dump->addScope($exception->getMessage(), ['exception' => $exception]);
            $pre_lines = min($exception->getLine(), 5);
            $start = $exception->getLine() - $pre_lines;
            $scope->addScope($exception->getFile().':'.$exception->getLine(), [], [
                'code' => $this->getFileCode($exception->getFile(), $start, $pre_lines + 4),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'code_line' => $start,
            ]);
            foreach ($exception->getTrace() as $index => $trace) {
                $title = '';
                $extras = [];
                $args = [];
                if ($trace['function'] ?? false) {
                    if ($trace['class'] ?? false) {
                        $title .= $trace['class'].($trace['type'] ?? '::');
                    }
                    $title .= $trace['function'];
                    if ($trace['args'] ?? false) {
                        $ref = $trace['class'] ?? false ? new \ReflectionMethod($trace['class'], $trace['function']) : new \ReflectionFunction($trace['function']);
                        $names = [];
                        $display = [];
                        $count = count($trace['args']);
                        $args = array_values($trace['args']);
                        foreach ($ref->getParameters() as $k => $param) {
                            $pre = '';
                            if ($param->isVariadic()) {
                                $pre = '...';
                                $new_args = array_slice($args, 0, $k);
                                $array = [];
                                for ($i = 0; $i < $count; ++$i) {
                                    $array[] = $args[$k + $i];
                                }
                                $new_args[] = $array;
                                $args = $new_args;
                            } else {
                                --$count;
                            }
                            $names[] = $param->getName();
                            $display[] = $pre.(null === $param->getType() ? '$'.$param->getName() : $param->getType().' $'.$param->getName());
                        }
                        $args = array_combine($names, $args);
                        $title .= '('.implode(', ', $display).')';
                    } else {
                        $title .= '()';
                    }
                } else {
                    $title .= '(anonymous)';
                }
                if ($trace['file'] ?? false) {
                    $title .= ' in '.$trace['file'];
                    $extras['file'] = $trace['file'];
                    if ($trace['line'] ?? false) {
                        $extras['line'] = $trace['line'];
                        $title .= ':'.$trace['line'];
                        $pre_lines = min($trace['line'], 7);
                        $start = $trace['line'] - $pre_lines;
                        $extras['code'] = $this->getFileCode($trace['file'], $start, $pre_lines + 4);
                        $extras['code_line'] = $start;
                    }
                }

                $scope->addScope($title, $args, $extras);
            }
        } while ($withPrevious && $exception = $exception->getPrevious());

        return $scope;
    }

    private function getFileCode($file, $start_line, $limit, int $max_line_length = 500): string
    {
        $file = new \SplFileObject($file);
        $file->seek($start_line);
        if ($max_line_length > 0) {
            $file->setMaxLineLen($max_line_length);
        }
        $code = '';
        for ($i = 0; $i < $limit; ++$i) {
            if ($file->eof()) {
                break;
            }

            $code .= $file->current(); // add \n?
            $file->next();
        }

        return $code;
    }
}
