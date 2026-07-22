import { findRelationRaw } from '@/lib/relation-options'
import { moduleEndpoints } from '@/services'

/**
 * Zimbabwe-style class naming:
 * - Form 4 + stream A2  → "Form 4A2" (level + section code, no space)
 * - Lower 6 (no stream) → "Lower 6"
 * - Lower 6 + Commercials → "Lower 6 Commercials" (level + named stream)
 */
export function isALevelGradeName(levelName: string): boolean {
  return /^(Lower|Upper)\s*6\b/i.test(levelName.trim())
}

export function isFormOrGradeLevelName(levelName: string): boolean {
  const name = levelName.trim()
  return /^Form\s+\d+/i.test(name) || /^Grade\s+\d+/i.test(name)
}

/** Compact section codes such as A, B, A1, A2, Sci. */
export function isCompactStreamCode(value: string): boolean {
  return /^[A-Za-z]{1,4}\d{0,2}$/.test(value.trim())
}

export function buildClassDisplayName(
  levelName: string,
  stream?: { name?: string | null; code?: string | null } | null,
): string {
  const level = String(levelName ?? '').trim()
  if (!level) return ''

  const streamName = String(stream?.name ?? '').trim()
  const streamCode = String(stream?.code ?? '').trim()

  if (isALevelGradeName(level)) {
    // Lower/Upper 6: stream is a named pathway (Commercials, Sciences), not a letter.
    return streamName ? `${level} ${streamName}` : level
  }

  // Form / Grade: prefer compact code (A2) so Form 4 + A2 → Form 4A2
  const suffix = streamCode || streamName
  if (!suffix) return level

  if (isCompactStreamCode(suffix)) {
    // Form 4 + A2 → Form 4A2 (no space)
    return `${level.replace(/\s+$/, '')}${suffix}`
  }

  // Prefer compact code even when name has a leading space / odd casing
  const compactFromName = suffix.replace(/\s+/g, '')
  if (isCompactStreamCode(compactFromName) && isFormOrGradeLevelName(level)) {
    return `${level}${compactFromName}`
  }

  return `${level} ${suffix}`
}

export function resolveClassNamingParts(
  gradeLevelId: unknown,
  streamId: unknown,
): { levelName: string; className: string; formLabel: string } {
  const levelRaw = gradeLevelId
    ? findRelationRaw(moduleEndpoints.gradeLevels, gradeLevelId)
    : undefined
  const streamRaw = streamId
    ? findRelationRaw(moduleEndpoints.streams, streamId)
    : undefined

  const levelName = String(levelRaw?.name ?? '').trim()
  const formLabel = levelName
  const className = buildClassDisplayName(levelName, {
    name: streamRaw?.name != null ? String(streamRaw.name) : null,
    code: streamRaw?.code != null ? String(streamRaw.code) : null,
  })

  return { levelName, className, formLabel }
}
