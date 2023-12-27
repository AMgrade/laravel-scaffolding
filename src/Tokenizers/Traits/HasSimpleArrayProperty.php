<?php

declare(strict_types=1);

namespace AMgrade\Scaffolding\Tokenizers\Traits;

use PhpParser\Node\Expr\ClassConstFetch;

use const false;

trait HasSimpleArrayProperty
{
    protected function getSimpleArrayProperty(
        string $propertyName,
        array $imports = [],
        bool $useFQN = false,
    ): array {
        if (null === ($property = $this->getClassProperty($propertyName))) {
            return [];
        }

        $values = [];

        /** @var \PhpParser\Node\Expr\Array_|null $propertyValue */
        $propertyValue = $property->default;

        /** @var \PhpParser\Node\Expr\ArrayItem $item */
        foreach ($propertyValue?->items as $item) {
            if (!$item->value instanceof ClassConstFetch) {
                continue;
            }

            if ($useFQN) {
                $values[] = $item->value->class->toCodeString();
            } else {
                $values[] = $this->getClassConstFetchName($item->value, $imports);
            }
        }

        return $values;
    }
}
