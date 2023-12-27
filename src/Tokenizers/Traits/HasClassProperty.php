<?php

declare(strict_types=1);

namespace AMgrade\Scaffolding\Tokenizers\Traits;

use AMgrade\Scaffolding\NodeVisitors\PropertyVisitor;
use PhpParser\Node\Stmt\PropertyProperty;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;

trait HasClassProperty
{
    protected function getClassProperty(string $name): ?PropertyProperty
    {
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());
        $traverser->addVisitor($visitor = new PropertyVisitor($name));
        $traverser->traverse($this->ast);

        return $visitor->getProperty();
    }
}
