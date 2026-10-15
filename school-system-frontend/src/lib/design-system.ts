/**
 * Application design system — single source of layout + accent conventions.
 *
 * Colours live as CSS variables in `src/style.css` (mapped via `@theme`).
 * Semantic status hues live in `src/lib/ui-status.ts`.
 * Forms: `src/lib/form-standards.ts`. Tables: `src/components/ui/table/table-chrome.ts`.
 *
 * Prefer these tokens over ad-hoc Tailwind palette classes (emerald-*, sky-*, etc.).
 */

/** Page content max-widths — keep PageShell / dashboards aligned. */
export const CONTENT_MAX = {
  default: 'max-w-[1400px]',
  wide: 'max-w-[1600px]',
  full: 'max-w-none',
} as const

export type ContentMax = keyof typeof CONTENT_MAX

/** Vertical rhythm for page sections (8px grid). */
export const PAGE_STACK = 'space-y-6 md:space-y-8'

/** In-page `<h1>` — re-exported from page-meta for layout components. */
export { PAGE_TITLE_CLASS, PAGE_BREADCRUMB_CLASS, pageBreadcrumbs } from '@/lib/page-meta'

/** Shared action-toolbar sizing used by PageShell action slots. */
export const PAGE_ACTIONS =
  'flex flex-wrap items-center gap-2 [&_button]:h-9 [&_button]:rounded-xl [&_button]:px-4 [&_a]:inline-flex [&_a]:h-9 [&_a]:items-center [&_a]:rounded-xl [&_a]:px-4'

/**
 * Decorative solid accents for launcher tiles / quick-add icons.
 * Cycles chart + brand tokens so modules stay on-palette.
 */
export const ACCENT_SOLID = [
  'bg-primary',
  'bg-chart-2',
  'bg-chart-4',
  'bg-chart-3',
  'bg-chart-5',
  'bg-chart-1',
] as const

export type AccentSolid = (typeof ACCENT_SOLID)[number]

export function accentSolidAt(index: number): AccentSolid {
  return ACCENT_SOLID[((index % ACCENT_SOLID.length) + ACCENT_SOLID.length) % ACCENT_SOLID.length]!
}

/** Soft icon wells (KPI / empty states / side cards). */
export const ACCENT_SOFT = {
  primary: 'bg-primary/10 text-primary',
  success: 'bg-chart-2/15 text-chart-2',
  warning: 'bg-chart-3/15 text-chart-3',
  info: 'bg-chart-4/15 text-chart-4',
  danger: 'bg-destructive/10 text-destructive',
  muted: 'bg-muted text-muted-foreground',
} as const

export type AccentSoft = keyof typeof ACCENT_SOFT

/** Canonical shared building blocks — inspect these before inventing new UI. */
export const DESIGN_SYSTEM_COMPONENTS = {
  shell: 'DashboardShell + AppSidebar + AppHeader',
  page: 'PageShell',
  hub: 'ModuleHub + WorkspaceCard',
  forms: 'FormBuilder + FormSheet + form-standards',
  tables: 'DataTable + ListFiltersBar + table-chrome',
  feedback: 'EmptyState + ErrorState + PageLoader + Toaster',
  metrics: 'MetricBand + KpiCard + TrendBadge',
  status: 'Badge variants + ui-status STATUS_TONE_*',
} as const
