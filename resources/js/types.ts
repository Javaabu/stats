import type { StencilSchema } from "@javaabu/stencil"

/** Onu's `valueFormat` names that PHP sends */
export type StatsValueFormat = "number" | "integer" | "compact" | "percent" | "currency"

/** What every chart from PHP has (`Chart::toArray()`) */
export interface StatsChartBase {
  id: string
  title: string
  description?: string | null
  valueFormat: StatsValueFormat
  height?: number | null
}

export interface StatsSeriesConfig {
  label: string
  color?: string
}

/** The current period against the previous one (Onu's `ChartComparison`) */
export interface StatsComparison {
  previousKey: string
  currentLabel?: string
  previousLabel?: string
  positiveIsGood?: boolean
}

/** `TimeSeriesChart`: one row per step, keyed by series */
export interface TimeSeriesChartData extends StatsChartBase {
  type: "time-series"
  data: Array<Record<string, string | number | null>>
  /** The steps formatted with `config('stats.date_formats')`, for tables */
  labels: string[]
  config: Record<string, StatsSeriesConfig>
  totals: Record<string, number>
  dateKey: string
  mode: "hour" | "day" | "week" | "month" | "year"
  interval: "hour" | "day" | "week" | "month" | null
  variant: "line" | "area" | "bar"
  stacked: boolean
  brush: boolean
  comparison: StatsComparison | null
  aggregateLabel: string | null
}

/** `CategoryChart`: one row per category, keyed by series */
export interface CategoryChartData extends StatsChartBase {
  type: "category"
  data: Array<Record<string, string | number | null>>
  config: Record<string, StatsSeriesConfig>
  categoryKey: string
  categoryLabel: string | null
  aggregateLabel: string | null
  variant: "line" | "area" | "bar"
  orientation: "vertical" | "horizontal" | null
  stacked: boolean
  comparison: StatsComparison | null
}

/** `PieChart`: one slice per category, largest first */
export interface PieChartData extends StatsChartBase {
  type: "pie"
  data: Array<{ name: string; value: number; categoryId: string | number | null }>
  nameKey: string
  valueKey: string
  variant: "pie" | "donut"
  /** null for the total, false for none */
  centerLabel: { value?: string; label?: string } | false | null
  nameLabel: string | null
  aggregateLabel: string | null
  total: number
}

/** `Sparkline`: one value per step */
export interface SparklineData extends StatsChartBase {
  type: "sparkline"
  data: Array<number | null>
  label: string
  variant: "line" | "area"
  color: string | null
  mode: string
}

/** `StatTile`: a total, the previous total and a sparkline */
export interface StatTileData extends StatsChartBase {
  type: "stat-tile"
  label: string
  value: number | string | null
  previousValue: number | null
  deltaContext: string | null
  positiveIsGood: boolean
  sparkline: Array<number | null> | null
  icon: string | null
  href: string | null
  footer: string | null
  period: string | null
}

export type StatsChartData = TimeSeriesChartData | CategoryChartData | PieChartData | SparklineData | StatTileData

/** The CSV download of a page */
export interface StatsExport {
  url: string
  params: Record<string, unknown>
  token?: string | null
}

export interface StatsPageStrings {
  title: string
  export: string
  empty: string
  invalid: string
}

/** Props of `stats::TimeSeries/Index` and `stats::Categorical/Index` */
export interface StatsPageProps {
  form: StencilSchema
  chart: StatsChartData | null
  export: StatsExport | null
  strings: StatsPageStrings
  errors?: Record<string, string>
}
