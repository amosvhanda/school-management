<script setup lang="ts">
import { ChevronDown, Download, Plus, Printer, RefreshCw } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'

withDefaults(
  defineProps<{
    loading?: boolean
    exportLoading?: boolean
    showPrint?: boolean
    showExport?: boolean
    showCreate?: boolean
    createLabel?: string
  }>(),
  {
    showPrint: true,
    showExport: true,
    showCreate: true,
    createLabel: 'Add new',
  },
)

defineEmits<{
  refresh: []
  print: []
  export: []
  create: []
}>()
</script>

<template>
  <Button
    variant="outline"
    size="icon"
    class="size-9 rounded-xl"
    :disabled="loading"
    aria-label="Refresh list"
    @click="$emit('refresh')"
  >
    <RefreshCw class="size-4" :class="loading && 'animate-spin'" aria-hidden="true" />
  </Button>
  <Button
    v-if="showPrint"
    variant="outline"
    size="icon"
    class="size-9 rounded-xl"
    aria-label="Print list"
    @click="$emit('print')"
  >
    <Printer class="size-4" aria-hidden="true" />
  </Button>
  <DropdownMenu v-if="showExport">
    <DropdownMenuTrigger as-child>
      <Button variant="outline" class="gap-1.5 rounded-xl" :disabled="exportLoading || loading">
        <Download class="size-4" aria-hidden="true" />
        Export
        <ChevronDown class="size-3.5 opacity-70" aria-hidden="true" />
      </Button>
    </DropdownMenuTrigger>
    <DropdownMenuContent align="end" class="rounded-xl">
      <DropdownMenuItem class="cursor-pointer" @click="$emit('export')">
        {{ exportLoading ? 'Exporting…' : 'Export CSV' }}
      </DropdownMenuItem>
    </DropdownMenuContent>
  </DropdownMenu>
  <slot name="extra" />
  <Button v-if="showCreate" class="gap-1.5 rounded-xl" @click="$emit('create')">
    <Plus class="size-4" aria-hidden="true" />
    {{ createLabel }}
  </Button>
</template>
