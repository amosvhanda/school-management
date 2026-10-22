<script setup lang="ts">
import { toRef } from 'vue'
import { useResolvedMediaSrc } from '@/composables/useResolvedMediaSrc'
import { cn } from '@/lib/utils'

const props = withDefaults(
  defineProps<{
    src?: string | null
    alt?: string
    cacheToken?: string | number
    class?: string
    fit?: 'cover' | 'contain'
  }>(),
  {
    alt: '',
    fit: 'cover',
  },
)

const { resolved, onImageError } = useResolvedMediaSrc(toRef(props, 'src'), toRef(props, 'cacheToken'))

const fitClass = props.fit === 'contain' ? 'object-contain' : 'object-cover'
</script>

<template>
  <img
    v-if="resolved"
    :src="resolved"
    :alt="alt"
    :class="cn('size-full', fitClass, props.class)"
    @error="onImageError"
  />
  <slot v-else name="fallback" />
</template>
