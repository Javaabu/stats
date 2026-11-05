import { CategoryChart, PieChart, Sparkline, StatTile, TimeSeriesChart, type ChartConfig } from "@javaabu/onu/charts"
import type { ComponentProps } from "react"
import type { StatsChartData, StatsComparison, StatTileData } from "../types"

export interface StatsChartProps {
  /** A chart from PHP: `$chart->toArray()`, or an Inertia prop */
  chart: StatsChartData
  className?: string
}

function comparisonProps(comparison: StatsComparison | null) {
  return comparison ? { comparison } : {}
}

/**
 * Draws a stats chart from PHP with Onu's chart components: `TimeSeriesChart`,
 * `CategoryChart`, `PieChart`, `Sparkline` or `StatTile`, by the chart's type.
 */
export function StatsChart({ chart, className }: StatsChartProps) {
  const common = {
    id: chart.id,
    className,
    valueFormat: chart.valueFormat,
    ...(chart.height ? { height: chart.height } : {}),
  }

  switch (chart.type) {
    case "time-series":
      return (
        <TimeSeriesChart
          {...common}
          title={chart.title}
          description={chart.description ?? undefined}
          data={chart.data}
          dateKey={chart.dateKey}
          config={chart.config as ChartConfig}
          interval={chart.interval ?? undefined}
          dateFormat={chart.mode === "year" ? "yyyy" : undefined}
          variant={chart.variant}
          stacked={chart.stacked}
          brush={chart.brush}
          {...comparisonProps(chart.comparison)}
        />
      )

    case "category": {
      const props = {
        ...common,
        title: chart.title,
        description: chart.description ?? undefined,
        data: chart.data,
        categoryKey: chart.categoryKey,
        categoryLabel: chart.categoryLabel ?? undefined,
        config: chart.config as ChartConfig,
        stacked: chart.stacked,
        ...comparisonProps(chart.comparison),
      }

      return chart.variant === "bar" ? (
        <CategoryChart {...props} variant="bar" orientation={chart.orientation ?? "vertical"} />
      ) : (
        <CategoryChart {...props} variant={chart.variant} />
      )
    }

    case "pie":
      return (
        <PieChart
          {...common}
          title={chart.title}
          description={chart.description ?? undefined}
          data={chart.data}
          nameKey={chart.nameKey}
          valueKey={chart.valueKey}
          variant={chart.variant}
          nameLabel={chart.nameLabel ?? undefined}
          {...(chart.centerLabel === false
            ? { centerLabel: null }
            : chart.centerLabel
              ? { centerLabel: { value: chart.centerLabel.value ?? "", label: chart.centerLabel.label } }
              : {})}
        />
      )

    case "sparkline":
      return (
        <Sparkline
          className={className}
          label={chart.label}
          data={chart.data}
          variant={chart.variant}
          valueFormat={chart.valueFormat}
          {...(chart.color ? { color: chart.color } : {})}
          {...(chart.height ? { height: chart.height } : {})}
        />
      )

    case "stat-tile":
      return <StatsTile tile={chart} className={className} />
  }
}

export interface StatsTileProps extends Omit<ComponentProps<typeof StatTile>, "label" | "value"> {
  /** A tile from PHP: `StatTile::make(...)->toArray()` */
  tile: StatTileData
}

/**
 * Onu's `StatTile` for a tile from PHP. With `href`, the whole tile is a link.
 */
export function StatsTile({ tile, ...props }: StatsTileProps) {
  const tileProps = {
    label: tile.label,
    value: tile.value,
    valueFormat: tile.valueFormat,
    positiveIsGood: tile.positiveIsGood,
    ...(tile.previousValue !== null ? { previousValue: tile.previousValue } : {}),
    ...(tile.deltaContext ? { deltaContext: tile.deltaContext } : {}),
    ...(tile.sparkline ? { sparkline: tile.sparkline } : {}),
    ...(tile.icon ? { icon: tile.icon } : {}),
    ...(tile.footer ? { footer: tile.footer } : {}),
    ...props,
  } as ComponentProps<typeof StatTile>

  if (tile.href) {
    return (
      <StatTile {...tileProps} asChild>
        <a href={tile.href}>{tile.label}</a>
      </StatTile>
    )
  }

  return <StatTile {...tileProps} />
}
