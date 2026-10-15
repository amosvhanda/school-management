import { resolveMediaUrl } from '@/lib/media-url'
import type { AuthUser } from '@/types/auth'

/** Normalize API user fields for client display (storage URLs, avatars). */
export function normalizeAuthUser(user: AuthUser): AuthUser {
  const avatar = resolveMediaUrl(user.avatar_url ?? null)
  if (!avatar || avatar === user.avatar_url) {
    return user
  }
  return { ...user, avatar_url: avatar }
}
