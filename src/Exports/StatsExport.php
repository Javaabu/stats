<?php
/**
 * Stats Export
 * User: dash-
 * Date: 03/04/2018
 * Time: 22:54
 */

namespace Javaabu\Stats\Exports;

use Javaabu\Stats\Formatters\CombinedStatsFormatter;
use Javaabu\Stats\StatsRepository;
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

    /**
     * @var CombinedStatsFormatter
     */
    protected $formatter;

    /**
     * @var string
     */
    protected $mode;

    /**
     * @var string
     */
    protected $metric;

    /**
     * @var string
     */
    protected $field_name;

    /**
     * @var boolean
     */
    protected $with_compare;

    /**
     * Create a new stats export instance.
     *
     * @param CombinedStatsFormatter $formatter
     * @param $mode
     */
    public function __construct(CombinedStatsFormatter $formatter, $mode)
    {
        $this->formatter = $formatter;
        $this->metric = $formatter->getStats()->metric();
        $this->field_name = $formatter->getStats()->getAggregateFieldName();
        $this->mode = $mode;
        $this->with_compare = ! empty($formatter->getCompare());
    }

    /**
     * Get the metric
     *
     * @return string
     */
    public function metric()
    {
        return $this->metric;
    }

    /**
     * Get the report title
     *
     * @return string
     */
    public function getReportTitle()
    {
        return StatsRepository::getMetricName($this->metric());
    }

    /**
     * Get the formatted date range
     *
     * @param string $format
     * @param string $separator
     * @return string
     */
    public function formattedDateRange($format = 'Ymd', $separator = '-')
    {
        $date_range = $this->formatter->getStats()->formattedDateRange($format, $separator);

        if ($this->with_compare) {
            $date_range .= ' ' .$this->formatter->getCompare()->formattedDateRange($format, $separator);
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

    /**
     * @return array
     */
    public function array(): array
    {
        return $this->formatter->format($this->mode);
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        $headings = [
            slug_to_title($this->mode),
        ];

        if ($this->with_compare) {
            $headings[] = 'Date Range';
        }

        $headings[] = slug_to_title($this->field_name);

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

        $values[] = $row[$this->mode];

        if ($this->with_compare) {
            $values[] = $row['date_range'];
        }

        $values[] = $row[$this->field_name];

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

        if ($this->with_compare) {
            $formats['B'] = NumberFormat::FORMAT_TEXT;
        }

        $formats[$this->with_compare ? 'C' : 'B'] = NumberFormat::FORMAT_NUMBER;

        return $formats;
    }
}
