<?php

declare(strict_types=1);

namespace AMgrade\Scaffolding\NodeVisitors;

use PhpParser\Node;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeVisitorAbstract;

use const null;

class ImportVisitor extends NodeVisitorAbstract
{
    protected array $imports = [];

    protected ?int $startLine = null;

    protected ?int $endLine = null;

    public function enterNode(Node $node): void
    {
        if (!($node instanceof Use_ || $node instanceof GroupUse)) {
            return;
        }

        if (null === $this->startLine) {
            $this->startLine = $node->getStartLine();
        }

        $this->endLine = $node->getEndLine();

        $prefix = $node instanceof GroupUse
            ? "{$node->prefix->toString()}\\"
            : '';

        foreach ($node->uses as $use) {
            $type = $node instanceof GroupUse ? $use->type : $node->type;

            $import = "{$prefix}{$use->name->toString()}";

            if (null !== $use->alias) {
                $this->imports[$type][$import] = "{$import} as {$use->alias->name}";
            } else {
                $this->imports[$type][$import] = $import;
            }
        }
    }

    public function getImports(): array
    {
        return $this->imports;
    }

    public function getStartLine(): ?int
    {
        return $this->startLine;
    }

    public function getEndLine(): ?int
    {
        return $this->endLine;
    }
}
