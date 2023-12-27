<?php

declare(strict_types=1);

namespace AMgrade\Scaffolding\Tokenizers;

use AMgrade\Scaffolding\Tokenizers\Traits\HasClassNames;
use AMgrade\Scaffolding\Tokenizers\Traits\HasSimpleArrayProperty;

use function array_map;
use function array_slice;
use function explode;
use function implode;
use function ltrim;
use function preg_replace;
use function sort;
use function str_pad;
use function str_replace;
use function str_starts_with;
use function trim;

class KernelTokenizer extends Tokenizer
{
    use HasClassNames;
    use HasSimpleArrayProperty;

    public function getMap(
        string $commandClass,
        string $consoleNamespace,
        array $imports = [],
    ): array {
        $commands = $this->getSimpleArrayProperty('commands', $imports, true);
        $commands[] = $commandClass;

        $commands = array_map(
            static fn (string $command) => ltrim($command, '\\'),
            $commands,
        );

        $spaces = str_pad(' ', 8);
        $map = [];

        foreach ($commands as $command) {
            $command = ltrim($command, '\\');

            if (isset($imports[$command])) {
                $class = $this->getClassFetchName(
                    $command,
                    $this->getBaseClassName($command),
                    $imports,
                );
            } elseif (str_starts_with($command, $consoleNamespace)) {
                $class = trim(str_replace($consoleNamespace, '', $command), '\\');
            } else {
                $class = "\\{$command}";
            }

            $map[$command] = "{$spaces}{$class}::class,";
        }

        sort($map);

        return $map;
    }

    public function getReplacedContent(array $map, array $imports): string
    {
        $content = $this->content;

        $spaces = str_pad(' ', 4);

        $inject = "protected \$commands = [\n".implode("\n", $map)."\n{$spaces}];";

        if (null !== $this->getClassProperty('commands')) {
            $content = preg_replace(
                '~protected\s*\$commands[^;]*\s*]\s*;~',
                $inject,
                $content,
            );
        } else {
            $content = $this->injectPropertyCommands($content, $inject);
        }

        return $this->replaceImports($content, $imports);
    }

    protected function injectPropertyCommands(string $content, string $inject): string
    {
        $explodedContent = explode("\n", $content);

        foreach ($explodedContent as $key => $line) {
            if (str_starts_with(trim($line), 'class Kernel')) {
                $replaced = implode("\n", array_slice($explodedContent, 0, $key + 2));
                $replaced .= "\n    {$inject}\n\n";
                $replaced .= implode("\n", array_slice($explodedContent, $key + 2));

                return $replaced;
            }
        }

        return $content;
    }
}
