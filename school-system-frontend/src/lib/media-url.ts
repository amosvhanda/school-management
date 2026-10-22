/**
 * Normalize storage / CDN URLs so they load from the Vite proxy in dev
 * and from the API origin when VITE_API_URL is set.
 */
export function resolveMediaUrl(url: string | null | undefined): string | null {
  if (!url) return null
  const trimmed = String(url).trim()
  if (!trimmed) return null

  // Already a data/blob URL (local preview).
  if (trimmed.startsWith('data:') || trimmed.startsWith('blob:')) return trimmed

  try {
    if (/^https?:\/\//i.test(trimmed)) {
      const parsed = new URL(trimmed)
      // Prefer same-origin /storage paths so the Vite proxy can serve them in dev.
      if (parsed.pathname.startsWith('/storage/')) {
        return `${parsed.pathname}${parsed.search}`
      }
      return trimmed
    }
  } catch {
    /* fall through */
  }

  if (trimmed.startsWith('/')) return trimmed

  const path = trimmed.replace(/^storage\//, '')
  // Relative public-disk paths from uploads (e.g. school-1/avatars/photo.jpg).
  return `/storage/${path}`
}

/** Bust browser cache after a photo replace. */
export function withCacheBust(url: string | null | undefined, token?: string | number): string | null {
  const resolved = resolveMediaUrl(url)
  if (!resolved || resolved.startsWith('data:') || resolved.startsWith('blob:')) return resolved
  const bust = token ?? Date.now()
  const join = resolved.includes('?') ? '&' : '?'
  return `${resolved}${join}v=${bust}`
}
