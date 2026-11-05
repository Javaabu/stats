import { StatsPage } from "../../components/StatsPage"
import type { StatsPageProps } from "../../types"

/** stats::Categorical/Index — the categorical stats page */
export default function Index(props: StatsPageProps) {
  return <StatsPage page="categorical" {...props} />
}
