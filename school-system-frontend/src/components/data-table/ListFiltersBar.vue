<script setup lang="ts">
import { computed } from 'vue'
import { FilterX, SlidersHorizontal } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import RelationFilterSelect from '@/components/data-table/RelationFilterSelect.vue'
import type { ListFilterSchema } from '@/modules/shared/list-filters'

const props = defineProps<{
  filters: ListFilterSchema[]
  activeCount?: number
}>()

const model = defineModel<Record<string, string>>({ required: true })
const emit = defineEmits<{ clear: [] }>()

const ALL = '__all__'

function update(key: string, value: string) {
  const normalized = value === ALL ? '' : value
  const next = { ...model.value, [key]: normalized }

  for (const filter of props.filters) {
    if (filter.relation?.dependsOn?.field === key && next[filter.key]) {
      delete next[filter.key]
    }
  }

  model.value = next
}

function parentValue(filter: ListFilterSchema): string | undefined {
  const parentField = filter.relation?.dependsOn?.field
  if (!parentField) return undefined
  const raw = model.value[parentField]
  return raw || undefined
}

const hasActive = computed(() => (props.activeCount ?? 0) > 0)
</script>

<template>
  <section
    class="flex flex-col gap-3 border-b border-border/60 bg-muted/10 px-4 py-3.5"
    aria-label="List filters"
  >
    <div class="flex flex-wrap items-center justify-between gap-2">
      <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-muted-foreground/90">
        <SlidersHorizontal class="size-3.5 text-muted-foreground/70" aria-hidden="true" />
        <span>Filters Matrix</span>
        <span
          v-if="hasActive"
          class="rounded-full bg-primary/10 px-2 py-0.5 text-[10px] font-bold text-primary transition-all"
        >
          {{ activeCount }} active
        </span>
      </div>

      <Button
        v-if="hasActive"
        type="button"
        variant="ghost"
        size="sm"
        class="h-7 text-xs px-2 text-muted-foreground hover:text-destructive hover:bg-destructive/10 transition-colors"
        @click="emit('clear')"
      >
        <FilterX class="mr-1.5 size-3.5" aria-hidden="true" />
        Reset entries
      </Button>
    </div>

    <div class="flex flex-wrap items-center gap-3 mt-1">
      <template v-for="filter in filters" :key="filter.key">
        <RelationFilterSelect
          v-if="filter.type === 'relation' && filter.relation"
          :model-value="model[filter.key] ?? ''"
          :label="filter.label"
          :placeholder="filter.placeholder"
          :relation="filter.relation"
          :parent-value="parentValue(filter)"
          @update:model-value="update(filter.key, $event)"
        />

        <div v-else class="min-w-[10rem] flex-1 sm:max-w-[14rem]">
          <label class="sr-only">{{ filter.label }}</label>
          <Select
            :model-value="model[filter.key] || ALL"
            @update:model-value="update(filter.key, String($event ?? ALL))"
          >
            <SelectTrigger class="h-9 w-full text-xs bg-background" :aria-label="filter.label">
              <SelectValue :placeholder="filter.placeholder ?? filter.label" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem :value="ALL" class="text-xs">
                {{ filter.placeholder ?? `All ${filter.label.toLowerCase()}` }}
              </SelectItem>
              <SelectItem
                v-for="option in filter.options ?? []"
                :key="option.value"
                :value="option.value"
                class="text-xs"
              >
                {{ option.label }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>
      </template>
    </div>
  </section>
</template>
