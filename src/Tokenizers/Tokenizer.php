<?php

declare(strict_types=1);

namespace AMgrade\Scaffolding\Tokenizers;

use AMgrade\Scaffolding\Tokenizers\Traits\HasClassNames;
use AMgrade\Scaffolding\Tokenizers\Traits\HasClassProperty;
use AMgrade\Scaffolding\Tokenizers\Traits\HasImports;
use PhpParser\ParserFactory;

use function defined;
use function method_exists;

abstract class Tokenizer
{
    use HasClassNames;
    use HasClassProperty;
    use HasImports;

    protected ?array $ast;

    public function __construct(protected string $content)
    {
        $factory = new ParserFactory();

        $factory = defined(ParserFactory::class.'::PREFER_PHP7') && method_exists($factory, 'create')
            ? $factory->create(ParserFactory::PREFER_PHP7)
            : $factory->createForHostVersion();

        $this->ast = $factory->parse($this->content);
    }
}
