<?php

namespace App\Infrastructure\Util;

use Illuminate\Contracts\Support\Arrayable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Excel;

class DataExporter
{
    /** @var Excel */
    private $excel;
    /** @var string */
    private $sheetName = 'WorkSheet 1';
    /** @var array */
    private $columnFormats = [];
    /** @var array */
    private $headings = [];

    public function __construct(Excel $excel)
    {
        $this->excel = $excel;
    }

    /**
     * @param string $sheetName
     *
     * @return DataExporter
     */
    public function setSheetName(string $sheetName): self
    {
        $this->sheetName = $sheetName;

        return $this;
    }

    public function setColumnFormats(array $columnFormats): self
    {
        $this->columnFormats = $columnFormats;

        return $this;
    }

    /**
     * @param string $fileName
     * @param Arrayable ...$data Please use `...` operator when call the method. Ex: $exporter->xls('file', ...$resultOfSearch)
     * @return mixed
     */
    public function xls(string $fileName, Arrayable  ...$data)
    {
        return $this->excel->download($this->fromArray($data), $fileName, Excel::XLS);
    }

    public function setHeadings(array $headings): self
    {
        $this->headings = $headings;

        return $this;
    }

    private function fromArray(array $data)
    {
        return new class($data, $this->headings, $this->columnFormats, $this->sheetName) implements FromArray, WithHeadings, WithColumnFormatting, WithTitle {
            /** @var array */
            private $data;
            /** @var array */
            private $headings;
            /** @var array */
            private $columnFormats;
            /** @var string */
            private $sheetName;

            public function __construct(array $data, array $headings = [], array $columnFormats = [], string $sheetName = 'Worksheet')
            {
                $this->data = $data;
                $this->headings = $headings;
                $this->columnFormats = $columnFormats;
                $this->sheetName = $sheetName;
            }

            public function array(): array
            {
                return $this->data;
            }

            public function columnFormats(): array
            {
                return $this->columnFormats;
            }

            public function headings(): array
            {
                return $this->headings;
            }

            public function title(): string
            {
                return $this->sheetName;
            }
        };
    }
}
