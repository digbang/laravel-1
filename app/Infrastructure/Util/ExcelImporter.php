<?php

namespace App\Infrastructure\Util;

use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Excel;

class ExcelImporter extends DataImporter
{
    /** @var Excel */
    protected $excel;

    public function __construct(Excel $excel)
    {
        $this->excel = $excel;
    }

    public function fromUploadedFile(UploadedFile $uploadedFile, ImportTransformer $importTransformer): array
    {
        $worksheet = $this->excel->toArray($this->convertToArray(), $uploadedFile, null, Excel::XLS);

        return array_map(function ($row) use ($importTransformer) {
            return $importTransformer->transform($row);
        }, current($worksheet));
    }
}
