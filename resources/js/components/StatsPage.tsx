import { Alert, Button } from "@javaabu/forms"
import { StencilForm } from "@javaabu/stencil"
import type { StatsExport, StatsPageProps } from "../types"
import { StatsChart } from "./StatsChart"

function hiddenInputs(params: Record<string, unknown>, prefix = ""): Array<[string, string]> {
  const inputs: Array<[string, string]> = []

  for (const [key, value] of Object.entries(params)) {
    if (value === null || value === undefined || value === "") continue

    const name = prefix ? `${prefix}[${key}]` : key

    if (Array.isArray(value)) {
      for (const item of value) inputs.push([`${name}[]`, String(item)])
    } else if (typeof value === "object") {
      inputs.push(...hiddenInputs(value as Record<string, unknown>, name))
    } else {
      inputs.push([name, String(value)])
    }
  }

  return inputs
}

/** A plain POST form that downloads the chart's data as CSV */
export function StatsExportButton({ download, label }: { download: StatsExport; label: string }) {
  return (
    <form method="post" action={download.url} className="flex justify-end" data-slot="stats-export">
      {download.token ? <input type="hidden" name="_token" value={download.token} /> : null}
      {hiddenInputs(download.params).map(([name, value], index) => (
        <input key={`${name}-${index}`} type="hidden" name={name} value={value} />
      ))}
      <Button type="submit" color="secondary">
        {label}
      </Button>
    </form>
  )
}

/**
 * The body of the stats pages: the stencil form, then the chart and the CSV
 * download, or a message when there's nothing to show
 */
export function StatsPage({ page, form, chart, export: download, strings, errors }: StatsPageProps & { page: string }) {
  const invalid = !chart && errors && Object.keys(errors).length > 0

  return (
    <div className="flex flex-col gap-6" data-stats-page={page}>
      <h1 className="text-2xl font-semibold">{strings.title}</h1>

      <StencilForm schema={form} />

      {chart ? (
        <>
          <StatsChart chart={chart} />
          {download ? <StatsExportButton download={download} label={strings.export} /> : null}
        </>
      ) : invalid ? (
        <Alert type="warning">{strings.invalid}</Alert>
      ) : (
        <p className="text-sm text-muted-foreground">{strings.empty}</p>
      )}
    </div>
  )
}
