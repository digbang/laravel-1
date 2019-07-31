<?php

namespace App\Infrastructure\Util;

class DefaultImportTransformer implements ImportTransformer
{
    public function transform(array $array)
    {
        return $array;
    }
}
