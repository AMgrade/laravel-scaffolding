<?php

declare(strict_types=1);

namespace AMgrade\Scaffolding\Console\Commands;

use AMgrade\Scaffolding\Tokenizers\EventServiceProviderTokenizer;
use Illuminate\Foundation\Console\ObserverMakeCommand;
use PhpParser\Node\Stmt\Use_;

use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function str_ends_with;

class MakeObserverCommand extends ObserverMakeCommand
{
    public function handle(): int
    {
        $result = parent::handle();

        if (false === $result) {
            return self::FAILURE;
        }

        $this->addMappingToProvider();

        return self::SUCCESS;
    }

    protected function getNameInput(): string
    {
        $name = parent::getNameInput();

        if (!str_ends_with($name, 'Observer')) {
            if (!$this->option('model')) {
                $this->input->setOption('model', $name);
            }

            return "{$name}Observer";
        }

        return $name;
    }

    protected function addMappingToProvider(): void
    {
        if (!($model = $this->option('model'))) {
            return;
        }

        $observer = $this->getNameInput();
        $observerClass = $this->qualifyClass($observer);
        $modelClass = $this->parseModel($model);

        $provider = $this->laravel->path('Providers/EventServiceProvider.php');

        if (!file_exists($provider)) {
            return;
        }

        $tokenizer = new EventServiceProviderTokenizer(file_get_contents($provider));

        $imports = $tokenizer->getImports();
        $imports[Use_::TYPE_NORMAL][$modelClass] = $modelClass;
        $imports[Use_::TYPE_NORMAL][$observerClass] = $observerClass;

        $map = $tokenizer->getMap(
            $modelClass,
            $observerClass,
            $imports[Use_::TYPE_NORMAL],
        );

        $content = $tokenizer->getReplacedContent($map, $imports);

        file_put_contents($provider, $content);
    }
}
