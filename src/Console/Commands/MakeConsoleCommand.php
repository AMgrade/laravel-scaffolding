<?php

declare(strict_types=1);

namespace AMgrade\Scaffolding\Console\Commands;

use AMgrade\Scaffolding\Tokenizers\KernelTokenizer;
use Illuminate\Foundation\Console\ConsoleMakeCommand;
use Illuminate\Support\Str;
use PhpParser\Node\Stmt\Use_;

use function array_map;
use function count;
use function explode;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function implode;
use function str_replace;
use function trim;

class MakeConsoleCommand extends ConsoleMakeCommand
{
    public function handle(): int
    {
        $this->setCommandOption();

        $result = parent::handle();

        if (false === $result) {
            return self::FAILURE;
        }

        $this->addMappingToKernel();

        return self::SUCCESS;
    }

    protected function setCommandOption(): void
    {
        if ($this->option('command')) {
            return;
        }

        $parts = explode('/', parent::getNameInput());

        if (2 !== count($parts)) {
            return;
        }

        $parts = array_map(static fn (string $part) => Str::kebab($part), $parts);

        $this->input->setOption('command', implode(':', $parts));
    }

    protected function getNameInput(): string
    {
        $name = parent::getNameInput();

        return !str_ends_with($name, 'Command') ? "{$name}Command" : $name;
    }

    protected function addMappingToKernel(): void
    {
        $kernel = $this->laravel->path('Console/Kernel.php');

        if (!file_exists($kernel)) {
            return;
        }

        $tokenizer = new KernelTokenizer(file_get_contents($kernel));

        $imports = $tokenizer->getImports();

        $map = $tokenizer->getMap(
            $this->getCommandFullyQualifiedName(),
            $this->getConsoleNamespace(),
            $imports[Use_::TYPE_NORMAL],
        );

        $content = $tokenizer->getReplacedContent($map, $imports);

        file_put_contents($kernel, $content);
    }

    protected function getCommandFullyQualifiedName(): string
    {
        $command = str_replace('/', '\\', $this->getNameInput());

        $namespace = $this->getDefaultNamespace(
            trim($this->rootNamespace(), '\\'),
        );

        return "\\{$namespace}\\{$command}";
    }

    protected function getConsoleNamespace(): string
    {
        $rootNamespace = trim($this->rootNamespace(), '\\');

        return "{$rootNamespace}\Console";
    }
}
