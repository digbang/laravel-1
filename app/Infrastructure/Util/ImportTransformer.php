<?php

namespace App\Infrastructure\Util;

interface ImportTransformer
{
    /**
     * @param array $array
     *
     * @return array | object
     */
    public function transform(array $array);
}
