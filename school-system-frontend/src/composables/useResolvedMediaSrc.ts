import { computed, ref, unref, watch, type MaybeRef } from 'vue'
import { resolveMediaUrl, withCacheBust } from '@/lib/media-url'

/** Resolve a media src with optional cache busting; clears on load error. */
export function useResolvedMediaSrc(
  src: MaybeRef<string | null | undefined>,
  cacheToken?: MaybeRef<string | number | undefined>,
) {
  const failed = ref(false)

  watch(
    () => unref(src),
    () => {
      failed.value = false
    },
  )

  watch(
    () => unref(cacheToken),
    () => {
      failed.value = false
    },
  )

  const resolved = computed(() => {
    if (failed.value) return null
    const raw = unref(src)
    const token = unref(cacheToken)
    if (token != null && token !== '') {
      return withCacheBust(raw, token)
    }
    return resolveMediaUrl(raw)
  })

  function onImageError() {
    failed.value = true
  }

  return { resolved, onImageError, failed }
}
