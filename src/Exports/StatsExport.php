<?php
/**
 * Stats Export
 * User: dash-
 * Date: 03/04/2018
 * Time: 22:54
 */

namespace Javaabu\Stats\Exports;

use Javaabu\Stats\Contracts\TimeSeriesStatsRepository;
use Javaabu\Stats\Enums\TimeSeriesModes;
use Javaabu\Stats\Formatters\TimeSeries\CombinedStatsFormatter;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Events\BeforeSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class StatsExport implements
    FromArray,
    WithMapping,
    WithHeadings,
    WithColumnFormatting,
    WithStrictNullComparison
{
    use Exportable, RegistersEventListeners;

    protected CombinedStatsFormatter $formatter;

    protected TimeSeriesModes $mode;

    protected TimeSeriesStatsRepository $stats;
    protected ?TimeSeriesStatsRepository $compare;

    /**
     * Create a new stats export instance.
     */
    public function __construct(TimeSeriesModes $mode, TimeSeriesStatsRepository $stats, ?TimeSeriesStatsRepository $compare = null)
    {
        $this->formatter = new CombinedStatsFormatter();
        $this->mode = $mode;
        $this->stats = $stats;
        $this->compare = $compare;
    }

    /**
     * Get the metric
     */
    public function metric(): string
    {
        return $this->stats->metric();
    }

    /**
     * Get the report title
     */
    public function getReportTitle(): string
    {
        return $this->stats->getName();
    }

    /**
     * Get the formatted date range
     */
    public function formattedDateRange(string $format = 'YYYYMMDD', string $separator = '-'): string
    {
        $date_range = $this->stats->formattedDateRange($format, $separator);

        if ($compare = $this->compare) {
            $date_range .= ' ' . $compare->formattedDateRange($format, $separator);
        }

        return $date_range;
    }

    /**
     * Before sheet event handler
     *
     * @param BeforeSheet $event
     */
    public static function beforeSheet(BeforeSheet $event)
    {
        $sheet = $event->sheet;
        $export = $event->getConcernable();

        $sheet->append([
            ['# '.str_repeat('-', 40)],
            ['# '.$export->getReportTitle()],
            ['# '.$export->formattedDateRange()],
            ['# '.str_repeat('-', 40)],
            [' '],
        ]);
    }

    public function array(): array
    {
        return $this->formatter->format($this->mode, $this->stats, $this->compare);
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        $headings = [
            $this->mode->getLabel(),
        ];

        if ($this->compare) {
            $headings[] = 'Date Range';
        }

        $headings[] = $this->stats->getAggregateFieldLabel();

        return $headings;
    }

    /**
     * @param mixed $row
     *
     * @return array
     */
    public function map($row): array
    {
        $values = [];

        $values[] = $row[$this->mode->value];

        if ($this->compare) {
            $values[] = $row['date_range'];
        }

        $values[] = $row[$this->stats->getAggregateFieldName()];

        return $values;
    }

    /**
     * Format the columns
     *
     * @return array
     */
    public function columnFormats(): array
    {
        $formats = [];

        $formats['A'] = NumberFormat::FORMAT_TEXT;

        if ($this->compare) {
            $formats['B'] = NumberFormat::FORMAT_TEXT;
        }

        $formats[$this->compare ? 'C' : 'B'] = NumberFormat::FORMAT_NUMBER;

        return $formats;
    }
}
