<script setup lang="ts">
import { ref, useAttrs, type HTMLAttributes } from 'vue'
import { Eye, EyeOff } from '@lucide/vue'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { cn } from '@/lib/utils'

defineOptions({ inheritAttrs: false })

defineProps<{
  class?: HTMLAttributes['class']
}>()

const attrs = useAttrs()
const visible = ref(false)
</script>

<template>
  <div class="relative w-full">
    <slot name="leading" />
    <Input
      v-bind="attrs"
      :type="visible ? 'text' : 'password'"
      :class="cn('pr-10', $props.class)"
    />
    <Button
      type="button"
      variant="ghost"
      size="icon"
      tabindex="0"
      class="absolute top-1/2 right-1 size-8 -translate-y-1/2 text-muted-foreground hover:text-foreground"
      :disabled="Boolean(attrs.disabled)"
      :aria-label="visible ? 'Hide password' : 'Show password'"
      :aria-pressed="visible"
      @click="visible = !visible"
    >
      <EyeOff v-if="visible" class="size-4" aria-hidden="true" />
      <Eye v-else class="size-4" aria-hidden="true" />
    </Button>
  </div>
</template>
