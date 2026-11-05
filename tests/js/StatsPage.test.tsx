import { render, screen } from "@testing-library/react"
import { describe, expect, it } from "vitest"
import { CategoricalStatsPage, TimeSeriesStatsPage, type StatsPageProps } from "@javaabu/stats"
import categorical from "./fixtures/categorical-props.json"
import timeSeries from "./fixtures/time-series-props.json"

describe("TimeSeriesStatsPage", () => {
  it("renders the stencil form, the chart and the CSV download", () => {
    const { container } = render(<TimeSeriesStatsPage {...(timeSeries as unknown as StatsPageProps)} />)

    expect(screen.getByRole("heading", { name: "Time Series Stats" })).toBeInTheDocument()
    expect(container.querySelector('[data-stats-page="time-series"]')).not.toBeNull()
    expect(container.querySelector('input[name="metric"], select[name="metric"]')).not.toBeNull()

    const download = container.querySelector('[data-slot="stats-export"]') as HTMLFormElement
    expect(download).toHaveAttribute("method", "post")
    expect(download.querySelector('input[name="_token"]')).not.toBeNull()
    expect(download.querySelector('input[name="metric"]')).toHaveValue("payments_count")
    expect(screen.getByRole("button", { name: "Download CSV" })).toBeInTheDocument()
  })

  it("explains invalid input instead of drawing a chart", () => {
    const props = {
      ...(timeSeries as unknown as StatsPageProps),
      chart: null,
      export: null,
      errors: { metric: "The selected stat is invalid." },
    }

    render(<TimeSeriesStatsPage {...props} />)

    expect(screen.getByText("Fix the errors in the form to see the stat.")).toBeInTheDocument()
  })

  it("asks for a stat when there is no chart yet", () => {
    const props = { ...(timeSeries as unknown as StatsPageProps), chart: null, export: null, errors: {} }

    render(<TimeSeriesStatsPage {...props} />)

    expect(screen.getByText("Choose a stat and a date range, then select Generate.")).toBeInTheDocument()
  })
})

describe("CategoricalStatsPage", () => {
  it("renders the categorical page", () => {
    const { container } = render(<CategoricalStatsPage {...(categorical as unknown as StatsPageProps)} />)

    expect(container.querySelector('[data-stats-page="categorical"]')).not.toBeNull()
    expect(screen.getByRole("heading", { name: "Categorical Stats" })).toBeInTheDocument()
  })
})
