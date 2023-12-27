<?php

declare(strict_types=1);

namespace AMgrade\Scaffolding\Tokenizers\Traits;

use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ClassConstFetch;

trait HasMapProperty
{
    protected function getMapProperty(
        string $propertyName,
        array $map,
        array $imports = [],
    ): array {
        if (null === ($property = $this->getClassProperty($propertyName))) {
            return $map;
        }

        /** @var \PhpParser\Node\Expr\Array_|null $propertyValue */
        $propertyValue = $property->default;

        /** @var \PhpParser\Node\Expr\ArrayItem $item */
        foreach ($propertyValue?->items as $item) {
            if (!$item->key instanceof ClassConstFetch) {
                continue;
            }

            $key = $this->getClassConstFetchName($item->key, $imports);

            if ($item->value instanceof ClassConstFetch) {
                $value = $this->getClassConstFetchName($item->value, $imports);
            } elseif ($item->value instanceof Array_) {
                $value = [];

                foreach ($item->value->items as $subItem) {
                    $value[] = $this->getClassConstFetchName($subItem->value, $imports);
                }
            } else {
                continue;
            }

            $map[$key] = $value;
        }

        return $map;
    }
}
