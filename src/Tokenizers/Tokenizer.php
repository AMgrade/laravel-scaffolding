<?php

declare(strict_types=1);

namespace AMgrade\Scaffolding\Tokenizers;

use AMgrade\Scaffolding\Tokenizers\Traits\HasClassNames;
use AMgrade\Scaffolding\Tokenizers\Traits\HasClassProperty;
use AMgrade\Scaffolding\Tokenizers\Traits\HasImports;
use PhpParser\ParserFactory;

abstract class Tokenizer
{
    use HasClassNames;
    use HasClassProperty;
    use HasImports;

    protected ?array $ast;

    public function __construct(protected string $content)
    {
        $this->ast = (new ParserFactory())
            ->create(ParserFactory::PREFER_PHP7)
            ->parse($this->content);
    }
}
