<?php

namespace App\Infrastructure\Util;

class DefaultImportTransformer implements ImportTransformer
{
    /** @inheritDoc */
    public function transform(array $array)
    {
        return $array;
    }
}
