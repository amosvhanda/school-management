/** Shared page title styling — keep AppHeader separate; this is in-page chrome only. */
export const PAGE_TITLE_CLASS =
  'font-heading text-[1.75rem] font-medium tracking-tight text-foreground md:text-[2rem] md:leading-tight'

export const PAGE_BREADCRUMB_CLASS = 'flex flex-wrap items-center gap-1.5 text-sm text-muted-foreground'

/** Build breadcrumb segments for PageShell (always starts with Dashboard). */
export function pageBreadcrumbs(...segments: Array<string | null | undefined>): string[] {
  const tail = segments.map((s) => String(s ?? '').trim()).filter(Boolean)
  return ['Dashboard', ...tail]
}

/** Shared hub / settings horizontal tab strip (ModuleHub pills + School setup). */
export const HUB_TAB_NAV_WRAP =
  'overflow-x-auto rounded-2xl border border-border/60 bg-muted/30 p-2'

export const hubTabButtonClass = (active: boolean) =>
  [
    'inline-flex shrink-0 items-center gap-2 rounded-xl px-4 py-2 text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
    active
      ? 'bg-background text-foreground shadow-sm'
      : 'text-muted-foreground hover:bg-background/80 hover:text-foreground',
  ].join(' ')
