<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink, type RouteLocationRaw } from 'vue-router'
import { useRouteAccess } from '@/composables/useRouteAccess'

/**
 * Drop-in replacement for `<RouterLink>` that renders nothing when the current
 * user cannot access the target route (same rule as the router guard).
 *
 * - Accessible  → renders `<RouterLink>` with the default slot + forwarded attrs.
 * - Not allowed → renders the optional `#fallback` slot (nothing by default).
 *
 * This makes links "safe by default": authors no longer need to remember a
 * capability check beside every link, and visibility can never drift from the
 * route's `meta`.
 */
const props = defineProps<{ to: RouteLocationRaw }>()

defineOptions({ inheritAttrs: false })

const { canOpen } = useRouteAccess()
const allowed = computed(() => canOpen(props.to))
</script>

<template>
  <RouterLink v-if="allowed" :to="to" v-bind="$attrs">
    <slot />
  </RouterLink>
  <slot v-else name="fallback" />
</template>
