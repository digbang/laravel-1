<?php

namespace App\Infrastructure\Util;

class DefaultExportTransformer implements ExportTransformer
{
    /** @inheritDoc */
    public function transform($object): array
    {
        try {
            $reflection = new \ReflectionClass(get_class($object));
            if ($reflection->getMethod('toArray')) {
                return $object->toArray();
            }

            return [];
        } catch (\ReflectionException $exception) {
            return [];
        }
    }
}
