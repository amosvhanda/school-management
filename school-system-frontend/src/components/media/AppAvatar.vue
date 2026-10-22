<script setup lang="ts">
import { computed, toRef } from 'vue'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { useResolvedMediaSrc } from '@/composables/useResolvedMediaSrc'
import { cn } from '@/lib/utils'

const props = withDefaults(
  defineProps<{
    src?: string | null
    alt?: string
    /** Shown in fallback slot when no image or load fails. */
    fallback?: string
    cacheToken?: string | number
    class?: string
    imageClass?: string
    shape?: 'circle' | 'rounded'
  }>(),
  {
    alt: '',
    shape: 'circle',
  },
)

const { resolved, onImageError } = useResolvedMediaSrc(toRef(props, 'src'), toRef(props, 'cacheToken'))

const radiusClass = computed(() => (props.shape === 'rounded' ? 'rounded-xl' : 'rounded-full'))
</script>

<template>
  <Avatar :class="cn(radiusClass, props.class)">
    <AvatarImage
      v-if="resolved"
      :src="resolved"
      :alt="alt"
      :class="cn(radiusClass, 'object-cover', imageClass)"
      @error="onImageError"
    />
    <AvatarFallback :class="cn(radiusClass, 'bg-primary/10 text-xs font-semibold text-primary')">
      <slot>{{ fallback }}</slot>
    </AvatarFallback>
  </Avatar>
</template>
