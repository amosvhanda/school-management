<script setup lang="ts">
import { provide } from 'vue'
import { moduleHubKey } from '@/lib/module-hub'

provide(moduleHubKey, { embedded: true })
</script>

<template>
  <div class="contents">
    <slot />
  </div>
</template>
