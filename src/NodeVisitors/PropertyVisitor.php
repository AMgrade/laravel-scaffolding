<?php

declare(strict_types=1);

namespace AMgrade\Scaffolding\NodeVisitors;

use PhpParser\Node;
use PhpParser\Node\Stmt\PropertyProperty;
use PhpParser\NodeVisitorAbstract;

class PropertyVisitor extends NodeVisitorAbstract
{
    protected ?PropertyProperty $property = null;

    public function __construct(protected string $name)
    {
    }

    public function enterNode(Node $node): void
    {
        if (
            $node instanceof PropertyProperty &&
            $node->name->toString() === $this->name
        ) {
            $this->property = $node;
        }
    }

    public function getProperty(): ?PropertyProperty
    {
        return $this->property;
    }
}
