/** Preskool-style in-card list title (page H1 stays singular). */
export function listWorkspaceTitle(pageTitle: string): string {
  const trimmed = pageTitle.trim()
  if (!trimmed) return 'List'
  if (/list$/i.test(trimmed)) return trimmed
  return `${trimmed} List`
}
