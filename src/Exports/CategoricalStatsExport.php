<?php

namespace Javaabu\Stats\Exports;

use Javaabu\Stats\Contracts\CategoricalStatsRepository;
use Javaabu\Stats\Enums\CategoricalModes;
use Javaabu\Stats\Formatters\Categorical\CombinedCategoricalStatsFormatter;

/**
 * A categorical stat as CSV: one row per category, with a column per period when compared
 */
class CategoricalStatsExport extends CsvExport
{
    protected CombinedCategoricalStatsFormatter $formatter;

    /**
     * @param  list<int|string>  $categorical_values
     */
    public function __construct(
        protected CategoricalStatsRepository $stats,
        protected ?CategoricalStatsRepository $compare = null,
        protected CategoricalModes $mode = CategoricalModes::NON_EMPTY,
        protected array $categorical_values = [],
    ) {
        $this->formatter = new CombinedCategoricalStatsFormatter;
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
     * @return list<array<string, int|float|string|null>>
     */
    public function array(): array
    {
        return $this->formatter->format($this->stats, $this->compare, $this->mode, $this->categorical_values);
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        $headings = [$this->stats->getCategoryIdTitle(), $this->stats->getCategoryTitle()];

        if (! $this->compare) {
            $headings[] = $this->stats->getAggregateFieldLabel();

            return $headings;
        }

        $headings[] = $this->stats->getAggregateFieldLabel() . ' - ' . $this->stats->formattedDateRange('YYYYMMDD', '-');
        $headings[] = $this->stats->getAggregateFieldLabel() . ' - ' . $this->compare->formattedDateRange('YYYYMMDD', '-');

        return $headings;
    }

    /**
     * @return list<int|float|string|null>
     */
    public function map(mixed $row): array
    {
        $values = [
            $row[$this->stats->getCategoryFieldAlias()],
            $row[$this->stats->getCategoryNameFieldAlias()],
            $row[$this->stats->getAggregateFieldName()],
        ];

        if ($this->compare) {
            $values[] = $row['compare_' . $this->stats->getAggregateFieldName()];
        }

        return $values;
    }
}
