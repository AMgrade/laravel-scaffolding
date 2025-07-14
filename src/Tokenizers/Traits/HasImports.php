<?php

declare(strict_types=1);

namespace AMgrade\Scaffolding\Tokenizers\Traits;

use AMgrade\Scaffolding\NodeVisitors\ImportVisitor;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;

use function array_map;
use function array_slice;
use function array_unique;
use function defined;
use function explode;
use function implode;
use function method_exists;
use function sort;

trait HasImports
{
    protected ImportVisitor $importVisitor;

    public function getImports(): array
    {
        $factory = new ParserFactory();

        $factory = defined(ParserFactory::class.'::PREFER_PHP7') && method_exists($factory, 'create')
            ? $factory->create(ParserFactory::PREFER_PHP7)
            : $factory->createForHostVersion();

        $ast = $factory->parse($this->content);

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());
        $traverser->addVisitor($this->importVisitor = new ImportVisitor());
        $traverser->traverse($ast);

        return $this->importVisitor->getImports();
    }

    public function getImportsAsString(array $imports): string
    {
        $importTypes = [
            Use_::TYPE_NORMAL,
            Use_::TYPE_FUNCTION,
            Use_::TYPE_CONSTANT,
        ];

        foreach ($importTypes as $importType) {
            if (empty($imports[$importType])) {
                continue;
            }

            $imports[$importType] = array_unique($imports[$importType]);

            sort($imports[$importType]);

            if ($importType === Use_::TYPE_FUNCTION) {
                $prefix = ' function ';
            } elseif ($importType === Use_::TYPE_CONSTANT) {
                $prefix = ' const ';
            } else {
                $prefix = ' ';
            }

            $imports[$importType] = array_map(
                static fn ($import) => "use{$prefix}{$import};",
                $imports[$importType],
            );

            $imports[$importType] = implode("\n", $imports[$importType]);
        }

        return implode("\n\n", $imports);
    }

    protected function replaceImports(string $content, array $imports): string
    {
        $start = $this->importVisitor->getStartLine();
        $end = $this->importVisitor->getEndLine();

        $explodedContent = explode("\n", $content);

        $replaced = implode("\n", array_slice($explodedContent, 0, $start - 1));
        $replaced .= "\n".trim($this->getImportsAsString($imports))."\n";
        $replaced .= implode("\n", array_slice($explodedContent, $end));

        return $replaced;
    }
}
