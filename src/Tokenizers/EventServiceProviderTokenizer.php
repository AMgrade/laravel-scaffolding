<?php

declare(strict_types=1);

namespace AMgrade\Scaffolding\Tokenizers;

use AMgrade\Scaffolding\Tokenizers\Traits\HasMapProperty;

use function array_map;
use function array_slice;
use function count;
use function explode;
use function implode;
use function is_array;
use function is_string;
use function preg_replace;
use function sort;
use function str_pad;
use function str_starts_with;
use function trim;

use const null;

class EventServiceProviderTokenizer extends Tokenizer
{
    use HasMapProperty;

    public function getMap(
        string $modelClass,
        string $observerClass,
        array $imports = [],
    ): array {
        $model = $this->getClassFetchName(
            $modelClass,
            $this->getBaseClassName($modelClass),
            $imports,
        );

        $observer = $this->getClassFetchName(
            $observerClass,
            $this->getBaseClassName($observerClass),
            $imports,
        );

        $map = $this->getMapProperty(
            'observers',
            [$model => [$observer]],
            $imports,
        );

        ksort($map);

        $result = [];

        $spaces = str_pad(' ', 4);

        foreach ($map as $model => $observers) {
            if (is_array($observers)) {
                if (1 === count($observers)) {
                    $result[$model] = "{$spaces}{$spaces}{$model}::class => {$observers[0]}::class,";
                } else {
                    sort($observers);

                    $observers = implode(
                        ',',
                        array_map(
                            static fn ($observer) => "\n{$spaces}{$spaces}{$spaces}{$observer}::class",
                            $observers,
                        ),
                    );

                    $result[$model] = "{$spaces}{$spaces}{$model}::class => [{$observers},\n{$spaces}{$spaces}],";
                }
            } elseif (is_string($observers)) {
                $result[$model] = "{$spaces}{$spaces}{$model}::class => {$observers}::class,";
            }
        }

        return $result;
    }

    public function getReplacedContent(array $map, array $imports): string
    {
        $content = $this->content;

        $spaces = str_pad(' ', 4);

        $inject = "protected \$observers = [\n".implode("\n", $map)."\n{$spaces}];";

        if (null !== $this->getClassProperty('observers')) {
            $content = preg_replace(
                '~protected\s*\$observers[^;]*\s*]\s*;~',
                $inject,
                $content,
            );
        } else {
            $content = $this->injectPropertyObservers($content, $inject);
        }

        return $this->replaceImports($content, $imports);
    }

    protected function injectPropertyObservers(string $content, string $inject): string
    {
        $explodedContent = explode("\n", $content);

        foreach ($explodedContent as $key => $line) {
            if (str_starts_with(trim($line), 'class EventServiceProvider')) {
                $replaced = implode("\n", array_slice($explodedContent, 0, $key + 2));
                $replaced .= "\n    {$inject}\n\n";
                $replaced .= implode("\n", array_slice($explodedContent, $key + 2));

                return $replaced;
            }
        }

        return $content;
    }
}
