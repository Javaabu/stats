<?php

namespace Javaabu\Stats\Exports;

use Javaabu\Stats\Contracts\CategoricalStatsRepository;
use Javaabu\Stats\Enums\CategoricalModes;
use Javaabu\Stats\Formatters\Categorical\CombinedCategoricalStatsFormatter;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Events\BeforeSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class CategoricalStatsExport implements FromArray, WithColumnFormatting, WithHeadings, WithMapping, WithStrictNullComparison
{
    use Exportable, RegistersEventListeners;

    protected CombinedCategoricalStatsFormatter $formatter;

    protected CategoricalStatsRepository $stats;

    protected ?CategoricalStatsRepository $compare;

    protected CategoricalModes $mode;

    /** @var list<int|string> */
    protected array $categorical_values;

    /**
     * @param  list<int|string>  $categorical_values
     */
    public function __construct(
        CategoricalStatsRepository $stats,
        ?CategoricalStatsRepository $compare = null,
        CategoricalModes $mode = CategoricalModes::NON_EMPTY,
        array $categorical_values = []
    ) {
        $this->formatter = new CombinedCategoricalStatsFormatter;
        $this->stats = $stats;
        $this->compare = $compare;
        $this->mode = $mode;
        $this->categorical_values = $categorical_values;
    }

    public function getReportTitle(): string
    {
        return $this->stats->getName();
    }

    public function formattedDateRange(string $format = 'YYYYMMDD', string $separator = '-'): string
    {
        $date_range = $this->stats->formattedDateRange($format, $separator);

        if ($compare = $this->compare) {
            $date_range .= ' '.$compare->formattedDateRange($format, $separator);
        }

        return $date_range;
    }

    public static function beforeSheet(BeforeSheet $event)
    {
        $sheet = $event->sheet;
        /** @var CategoricalStatsExport $export */
        $export = $event->getConcernable();

        $sheet->append([
            ['# '.str_repeat('-', 40)],
            ['# '.$export->getReportTitle()],
            ['# '.$export->formattedDateRange()],
            ['# '.str_repeat('-', 40)],
            [' '],
        ]);
    }

    /** @return list<array<string, int|float|string|null>> */
    public function array(): array
    {
        return $this->formatter->format($this->stats, $this->compare, $this->mode, $this->categorical_values);
    }

    /** @return list<string> */
    public function headings(): array
    {
        $headings = [$this->stats->getCategoryIdTitle(), $this->stats->getCategoryTitle()];

        if ($this->compare) {
            $headings[] = $this->stats->getAggregateFieldLabel().' - '.$this->stats->formattedDateRange('YYYYMMDD', '-');
            $headings[] = $this->stats->getAggregateFieldLabel().' - '.$this->compare->formattedDateRange('YYYYMMDD', '-');
        } else {
            $headings[] = $this->stats->getAggregateFieldLabel();
        }

        return $headings;
    }

    /**
     * @param  array<string, int|float|string|null>  $row
     * @return list<int|float|string|null>
     */
    public function map($row): array
    {
        $values = [
            $row[$this->stats->getCategoryFieldAlias()],
            $row[$this->stats->getCategoryNameFieldAlias()],
            $row[$this->stats->getAggregateFieldName()],
        ];

        if ($this->compare) {
            $values[] = $row['compare_'.$this->stats->getAggregateFieldName()];
        }

        return $values;
    }

    /** @return array<string, string> */
    public function columnFormats(): array
    {
        $formats = [
            'A' => NumberFormat::FORMAT_TEXT,
            'B' => NumberFormat::FORMAT_TEXT,
            'C' => NumberFormat::FORMAT_NUMBER,
        ];

        if ($this->compare) {
            $formats['D'] = NumberFormat::FORMAT_NUMBER;
        }

        return $formats;
    }
}
