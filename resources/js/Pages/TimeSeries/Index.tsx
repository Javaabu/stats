import { StatsPage } from "../../components/StatsPage"
import type { StatsPageProps } from "../../types"

/** stats::TimeSeries/Index — the time series stats page */
export default function Index(props: StatsPageProps) {
  return <StatsPage page="time-series" {...props} />
}
