<?php

namespace Javaabu\Stats\Exports;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * A CSV file of rows with a heading row, preceded by a few `#` comment lines
 * that say what the file is. Written with a UTF-8 byte order mark, so
 * spreadsheet apps show Thaana correctly.
 */
abstract class CsvExport
{
    /**
     * The rows before mapping
     *
     * @return array<int, mixed>
     */
    abstract public function array(): array;

    /**
     * @return list<string>
     */
    abstract public function headings(): array;

    /**
     * One row of the file
     *
     * @return list<int|float|string|null>
     */
    abstract public function map(mixed $row): array;

    abstract public function getReportTitle(): string;

    abstract public function formattedDateRange(string $format = 'YYYYMMDD', string $separator = '-'): string;

    /**
     * The comment lines at the top of the file
     *
     * @return list<string>
     */
    public function preamble(): array
    {
        return [
            '# ' . str_repeat('-', 40),
            '# ' . $this->getReportTitle(),
            '# ' . $this->formattedDateRange(),
            '# ' . str_repeat('-', 40),
        ];
    }

    /**
     * Every line of the file, as lists of cells
     *
     * @return list<list<int|float|string|null>>
     */
    public function lines(): array
    {
        $lines = array_map(fn (string $line) => [$line], $this->preamble());
        $lines[] = [];
        $lines[] = $this->headings();

        foreach ($this->array() as $row) {
            $lines[] = $this->map($row);
        }

        return $lines;
    }

    /**
     * The file's contents
     */
    public function toCsv(): string
    {
        $handle = fopen('php://temp', 'r+');

        $this->write($handle);

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    public function download(string $file_name): StreamedResponse
    {
        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');

            $this->write($handle);

            fclose($handle);
        }, $file_name, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @param  resource  $handle
     */
    protected function write($handle): void
    {
        fwrite($handle, "\u{FEFF}");

        foreach ($this->lines() as $line) {
            fputcsv($handle, $line, escape: '');
        }
    }
}
