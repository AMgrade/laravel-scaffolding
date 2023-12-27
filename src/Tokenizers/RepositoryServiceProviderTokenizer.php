<?php

declare(strict_types=1);

namespace AMgrade\Scaffolding\Tokenizers;

use AMgrade\Scaffolding\Tokenizers\Traits\HasMapProperty;

use function ksort;

class RepositoryServiceProviderTokenizer extends Tokenizer
{
    use HasMapProperty;

    public function getMap(
        string $contractClass,
        string $repositoryClass,
        array $imports = [],
    ): array {
        $contract = $this->getClassFetchName(
            $contractClass,
            $this->getBaseClassName($contractClass),
            $imports,
        );

        $repository = $this->getClassFetchName(
            $repositoryClass,
            $this->getBaseClassName($repositoryClass),
            $imports,
        );

        $map = $this->getMapProperty(
            'map',
            [$contract => $repository],
            $imports,
        );

        ksort($map);

        $result = [];

        foreach ($map as $contract => $repository) {
            $result[$contract] = "{$contract}::class => {$repository}::class";
        }

        return $result;
    }
}
