import { render, screen } from "@testing-library/react"
import { describe, expect, it } from "vitest"
import { StatsChart, StatsTile, type StatsChartData, type StatTileData } from "@javaabu/stats"
import categorical from "./fixtures/categorical-props.json"
import timeSeries from "./fixtures/time-series-props.json"

const base = { id: "chart", description: null, valueFormat: "number" as const, height: null }

describe("StatsChart", () => {
  it("draws a time series chart from PHP with its comparison", () => {
    const chart = timeSeries.chart as StatsChartData

    const { container } = render(<StatsChart chart={chart} />)

    expect(screen.getByText(chart.title)).toBeInTheDocument()
    expect(container.querySelector('[data-slot="period-totals"]')).not.toBeNull()
    expect(screen.getByRole("button", { name: /table/i })).toBeInTheDocument()
  })

  it("draws a pie from PHP", () => {
    const chart = categorical.chart as StatsChartData

    const { container } = render(<StatsChart chart={chart} />)

    expect(screen.getByText("Payments By User")).toBeInTheDocument()
    expect(container.querySelector('[data-slot="chart-summary"]')).toHaveTextContent(
      "Donut chart with 2 slices, total 5. Largest: އަޙްމަދު",
    )
  })

  it("draws a category chart with horizontal bars", () => {
    const chart: StatsChartData = {
      ...base,
      type: "category",
      title: "ރަށްތަކުގެ ފައިސާ",
      data: [
        { category: "މާލެ", categoryId: 1, payments: 12 },
        { category: "ހުޅުމާލެ", categoryId: 2, payments: 7 },
      ],
      config: { payments: { label: "ފައިސާ" } },
      categoryKey: "category",
      categoryLabel: "ރަށް",
      aggregateLabel: "Count",
      variant: "bar",
      orientation: "horizontal",
      stacked: false,
      comparison: null,
    }

    const { container } = render(<StatsChart chart={chart} />)

    expect(screen.getByText("ރަށްތަކުގެ ފައިސާ")).toBeInTheDocument()
    expect(container.querySelector('[data-orientation="horizontal"]')).not.toBeNull()
  })

  it("draws a line category chart", () => {
    const chart: StatsChartData = {
      ...base,
      type: "category",
      title: "Lines",
      data: [{ category: "A", categoryId: 1, a: 1 }],
      config: { a: { label: "A" } },
      categoryKey: "category",
      categoryLabel: null,
      aggregateLabel: null,
      variant: "line",
      orientation: null,
      stacked: false,
      comparison: null,
    }

    render(<StatsChart chart={chart} />)

    expect(screen.getByText("Lines")).toBeInTheDocument()
  })

  it("draws a sparkline with an accessible summary", () => {
    const chart: StatsChartData = {
      ...base,
      type: "sparkline",
      title: "Payments",
      data: [0, 0, 3, 2],
      label: "ފައިސާ",
      variant: "area",
      color: "var(--chart-2)",
      mode: "day",
    }

    render(<StatsChart chart={chart} />)

    expect(screen.getByRole("img", { name: /ފައިސާ/ })).toBeInTheDocument()
  })

  it("passes the donut's centre label, or none", () => {
    const pie = categorical.chart as Extract<StatsChartData, { type: "pie" }>

    const { rerender, container } = render(
      <StatsChart chart={{ ...pie, centerLabel: { value: "5", label: "ޖުމްލަ" } }} />,
    )
    expect(container.querySelector('[data-slot="chart"]')).not.toBeNull()

    rerender(<StatsChart chart={{ ...pie, variant: "pie", centerLabel: false }} />)
    expect(container.querySelector('[data-slot="chart-summary"]')).toHaveTextContent("Pie chart")
  })
})

describe("StatsTile", () => {
  const tile: StatTileData = {
    ...base,
    type: "stat-tile",
    title: "ފައިސާ",
    label: "ފައިސާ",
    value: 5,
    previousValue: 1,
    deltaContext: "vs previous period",
    positiveIsGood: true,
    sparkline: [0, 0, 3, 2],
    icon: null,
    href: null,
    footer: "4 days",
    period: null,
  }

  it("shows the value, the change and the footer", () => {
    render(<StatsChart chart={tile} />)

    expect(screen.getByRole("group", { name: "ފައިސާ" })).toBeInTheDocument()
    expect(screen.getByText("5")).toBeInTheDocument()
    expect(screen.getByText("vs previous period")).toBeInTheDocument()
    expect(screen.getByText("4 days")).toBeInTheDocument()
  })

  it("is a link when it has an href", () => {
    render(<StatsTile tile={{ ...tile, href: "/payments", previousValue: null, sparkline: null, footer: null }} />)

    expect(screen.getByRole("link")).toHaveAttribute("href", "/payments")
  })
})
