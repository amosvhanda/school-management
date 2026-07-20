<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'
// Corrected package reference coordinates
import { Check, ChevronsUpDown, RefreshCw, Search } from '@lucide/vue'
import { PopoverContent, PopoverPortal, PopoverRoot, PopoverTrigger } from 'reka-ui'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Skeleton } from '@/components/ui/skeleton'
import { cn } from '@/lib/utils'
import { formInputClass, formSelectTriggerClass } from '@/lib/form-standards'

export interface SearchableSelectOption {
  value: string
  label: string
}

const props = withDefaults(
  defineProps<{
    modelValue?: string
    options: SearchableSelectOption[]
    loading?: boolean
    disabled?: boolean
    placeholder?: string
    searchPlaceholder?: string
    emptyMessage?: string
    allowClear?: boolean
    showRefresh?: boolean
    allLabel?: string
    allValue?: string
    invalid?: boolean
    required?: boolean
    describedBy?: string
    triggerClass?: string
  }>(),
  {
    modelValue: '',
    loading: false,
    disabled: false,
    placeholder: 'Select…',
    searchPlaceholder: 'Type to filter…',
    emptyMessage: 'No matches found.',
    allowClear: false,
    showRefresh: false,
    allLabel: '',
    allValue: '',
    invalid: false,
    required: false,
  },
)

const emit = defineEmits<{
  'update:modelValue': [value: string]
  refresh: []
}>()

const open = ref(false)
const search = ref('')
const debouncedSearch = ref('')
const searchInputRef = ref<HTMLInputElement | null>(null)

const applySearch = useDebounceFn((value: string) => {
  debouncedSearch.value = value
}, 120)

watch(search, (value) => {
  applySearch(value)
}, { immediate: true })

watch(open, async (isOpen) => {
  if (!isOpen) {
    search.value = ''
    return
  }
  await nextTick()
  searchInputRef.value?.focus()
})

const selectedLabel = computed(() => {
  if (!props.modelValue) {
    return props.allLabel || null
  }
  return props.options.find((option) => option.value === props.modelValue)?.label ?? null
})

const filteredOptions = computed(() => {
  const query = debouncedSearch.value.trim().toLowerCase()
  if (!query) return props.options
  return props.options.filter((option) => option.label.toLowerCase().includes(query))
})

function selectOption(value: string) {
  emit('update:modelValue', value)
  open.value = false
}

function onSearchKeydown(event: KeyboardEvent) {
  event.stopPropagation()

  if (event.key === 'Escape') {
    open.value = false
    return
  }

  if (event.key === 'Enter' && filteredOptions.value.length === 1) {
    event.preventDefault()
    selectOption(filteredOptions.value[0]!.value)
  }
}
</script>

<template>
  <PopoverRoot v-model:open="open">
    <PopoverTrigger as-child>
      <Button
        type="button"
        variant="outline"
        role="combobox"
        :aria-expanded="open"
        :aria-invalid="invalid || undefined"
        :aria-required="required || undefined"
        :aria-describedby="describedBy"
        :disabled="disabled || loading"
        :class="cn(
          'h-10 w-full justify-between font-normal text-sm',
          formSelectTriggerClass,
          !selectedLabel && 'text-muted-foreground',
          triggerClass,
        )"
      >
        <span class="truncate">
          {{ selectedLabel ?? placeholder }}
        </span>
        <ChevronsUpDown class="size-4 shrink-0 opacity-50" aria-hidden="true" />
      </Button>
    </PopoverTrigger>

    <PopoverPortal>
      <PopoverContent
        align="start"
        :side-offset="4"
        class="z-50 w-[var(--reka-popover-trigger-width)] min-w-[16rem] overflow-hidden rounded-lg border bg-popover p-0 text-popover-foreground shadow-md"
        @open-auto-focus.prevent
      >
        <div class="border-b bg-popover p-2">
          <div class="relative">
            <Search
              class="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground"
              aria-hidden="true"
            />
            <Input
              ref="searchInputRef"
              v-model="search"
              type="search"
              :placeholder="searchPlaceholder"
              :class="cn(formInputClass, 'h-9 pl-9 text-xs')"
              autocomplete="off"
              @keydown="onSearchKeydown"
              @keyup.stop
            />
          </div>
          <div class="mt-2 flex items-center justify-between gap-2">
            <Badge variant="secondary" class="text-[10px] px-1.5 py-0.5 font-normal">
              {{ filteredOptions.length }} of {{ options.length }}
            </Badge>
            <Button
              v-if="showRefresh"
              type="button"
              variant="ghost"
              size="sm"
              class="h-7 gap-1 px-2 text-xs text-muted-foreground hover:text-foreground"
              @click="emit('refresh')"
            >
              <RefreshCw class="size-3" aria-hidden="true" />
              Refresh
            </Button>
          </div>
        </div>

        <div
          class="max-h-64 overflow-y-auto p-1"
          role="listbox"
          :aria-label="placeholder"
        >
          <button
            v-if="allLabel"
            type="button"
            role="option"
            class="flex w-full items-center gap-2 rounded-md px-2.5 py-2 text-left text-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:bg-accent focus-visible:outline-none"
            :aria-selected="!modelValue"
            @click="selectOption(allValue ?? '')"
          >
            <Check
              class="size-4 shrink-0"
              :class="!modelValue ? 'opacity-100' : 'opacity-0'"
              aria-hidden="true"
            />
            <span class="truncate">{{ allLabel }}</span>
          </button>

          <div v-if="loading" class="space-y-2 p-2" role="status" aria-live="polite">
            <Skeleton class="h-8 w-full rounded-md" />
            <Skeleton class="h-8 w-full rounded-md" />
          </div>

          <template v-else>
            <button
              v-for="option in filteredOptions"
              :key="option.value"
              type="button"
              role="option"
              class="flex w-full items-center gap-2 rounded-md px-2.5 py-2 text-left text-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:bg-accent focus-visible:outline-none"
              :aria-selected="modelValue === option.value"
              @click="selectOption(option.value)"
            >
              <Check
                class="size-4 shrink-0"
                :class="modelValue === option.value ? 'opacity-100' : 'opacity-0'"
                aria-hidden="true"
              />
              <span class="truncate">{{ option.label }}</span>
            </button>

            <div v-if="!filteredOptions.length" class="px-3 py-6 text-center text-sm text-muted-foreground italic">
              {{ emptyMessage }}
            </div>
          </template>
        </div>
      </PopoverContent>
    </PopoverPortal>
  </PopoverRoot>
</template>
