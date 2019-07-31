<?php

namespace App\Infrastructure\Util;

use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

abstract class DataImporter
{
    abstract public function fromUploadedFile(UploadedFile $uploadedFile, ImportInterpreter $interpreter): array;

    public function convertToArray(): ToArray
    {
        return new class implements ToArray, WithHeadingRow {
            public function array(array $array)
            {
                return $array;
            }
        };
    }
}
