<?php

namespace Javaabu\Stats\Exports;

use Javaabu\Stats\Contracts\TimeSeriesStatsRepository;
use Javaabu\Stats\Enums\TimeSeriesModes;
use Javaabu\Stats\Formatters\TimeSeries\CombinedStatsFormatter;

/**
 * A time series stat as CSV: one row per step, and per period when compared
 */
class TimeSeriesStatsExport extends CsvExport
{
    protected CombinedStatsFormatter $formatter;

    public function __construct(
        protected TimeSeriesModes $mode,
        protected TimeSeriesStatsRepository $stats,
        protected ?TimeSeriesStatsRepository $compare = null,
    ) {
        $this->formatter = new CombinedStatsFormatter;
    }

    public function metric(): string
    {
        return $this->stats->metric();
    }

    public function getReportTitle(): string
    {
        return $this->stats->getName();
    }

    public function formattedDateRange(string $format = 'YYYYMMDD', string $separator = '-'): string
    {
        $date_range = $this->stats->formattedDateRange($format, $separator);

        if ($compare = $this->compare) {
            $date_range .= ' ' . $compare->formattedDateRange($format, $separator);
        }

        return $date_range;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function array(): array
    {
        return $this->formatter->format($this->mode, $this->stats, $this->compare);
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        $headings = [$this->mode->getLabel()];

        if ($this->compare) {
            $headings[] = __('Date Range');
        }

        $headings[] = $this->stats->getAggregateFieldLabel();

        return $headings;
    }

    /**
     * @return list<int|float|string|null>
     */
    public function map(mixed $row): array
    {
        $values = [$row[$this->mode->value]];

        if ($this->compare) {
            $values[] = $row['date_range'];
        }

        $values[] = $row[$this->stats->getAggregateFieldName()];

        return $values;
    }
}
