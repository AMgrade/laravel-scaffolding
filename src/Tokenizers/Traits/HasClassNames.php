<?php

declare(strict_types=1);

namespace AMgrade\Scaffolding\Tokenizers\Traits;

use PhpParser\Node\Expr\ClassConstFetch;

use function basename;
use function preg_match;
use function str_replace;

trait HasClassNames
{
    protected function getBaseClassName(string $class): string
    {
        return basename(str_replace('\\', '/', $class));
    }

    protected function getClassConstFetchName(
        ClassConstFetch $node,
        array $imports = [],
    ): string {
        $name = $node->class->toString();

        $result = $this->getClassFetchName(
            $name,
            $node->class->getLast(),
            $imports,
        );

        return $result === $name ? $node->class->toCodeString() : $result;
    }

    protected function getClassFetchName(
        string $name,
        string $basename,
        array $imports = [],
    ): string {
        if (!isset($imports[$name])) {
            return $name;
        }

        if (preg_match('~ as (?<alias>.*)$~i', $imports[$name], $match)) {
            return $match['alias'];
        }

        return $basename;
    }
}
