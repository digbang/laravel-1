<?php

namespace App\Infrastructure\Util;

interface ExportTransformer
{
    /**
     * @param object $object
     *
     * @return array
     */
    public function transform($object): array;
}
