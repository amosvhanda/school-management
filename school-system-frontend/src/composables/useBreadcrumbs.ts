import { computed } from 'vue'
import { useRoute } from 'vue-router'

export function useBreadcrumbs() {
  const route = useRoute()

  const items = computed(() => {
    const segments = route.path.split('/').filter(Boolean)
    return segments.map((segment, index) => ({
      title: segment.replace(/-/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase()),
      href: `/${segments.slice(0, index + 1).join('/')}`,
      isLast: index === segments.length - 1,
    }))
  })

  return { items }
}
