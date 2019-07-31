<?php

namespace App\Infrastructure\Util;

use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Excel;

class ExcelImporter extends DataImporter
{
    /** @var Excel */
    protected $excel;
    /** @var ImportTransformer */
    protected $importTransformer;

    public function __construct(Excel $excel, ImportTransformer $importTransformer)
    {
        $this->excel = $excel;
        $this->importTransformer = $importTransformer;
    }

    public function fromUploadedFile(UploadedFile $uploadedFile, ImportTransformer $importTransformer = null): array
    {
        $worksheet = $this->excel->toArray($this->convertToArray(), $uploadedFile, null, Excel::XLS);
        $this->importTransformer = $importTransformer ?? $this->importTransformer;

        return array_map(function ($row) {
            return $this->importTransformer->transform($row);
        }, current($worksheet));
    }
}
