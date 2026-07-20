<script setup lang="ts">
import { computed } from 'vue'
import { FilterX, SlidersHorizontal, X } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { findRelationLabel } from '@/lib/relation-options'
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

const activeFilters = computed(() =>
  props.filters
    .map((filter) => {
      const raw = model.value[filter.key]
      if (!raw) return null

      if (filter.type === 'relation' && filter.relation) {
        const parent = parentValue(filter)
        const paramKey = filter.relation.dependsOn?.paramKey ?? filter.relation.dependsOn?.field
        const params = parent && paramKey ? { [paramKey]: parent } : undefined
        const relationLabel = findRelationLabel(filter.relation.endpoint, raw, params)
        return {
          key: filter.key,
          label: filter.label,
          value: relationLabel ?? filter.label,
        }
      }

      const optionLabel = filter.options?.find((option) => option.value === raw)?.label
      return {
        key: filter.key,
        label: filter.label,
        value: optionLabel ?? raw,
      }
    })
    .filter((item): item is { key: string; label: string; value: string } => Boolean(item)),
)

function clearFilter(key: string) {
  update(key, ALL)
}

function selectOptions(filter: ListFilterSchema) {
  return (filter.options ?? []).filter((option) => option.value !== '')
}

const hasActive = computed(() => (props.activeCount ?? 0) > 0)
</script>

<template>
  <section
    class="flex flex-col gap-3 border-b border-border/60 bg-muted/20 px-4 py-3.5"
    aria-label="List filters"
  >
    <div class="flex flex-wrap items-center justify-between gap-2">
      <div class="flex items-center gap-2 text-xs font-medium text-muted-foreground">
        <SlidersHorizontal class="size-3.5" aria-hidden="true" />
        <span>Filters</span>
        <Badge v-if="hasActive" variant="secondary" class="h-5 px-1.5 text-[10px] font-semibold">
          {{ activeCount }} active
        </Badge>
      </div>

      <Button
        v-if="hasActive"
        type="button"
        variant="ghost"
        size="sm"
        class="h-7 px-2 text-xs text-muted-foreground hover:bg-destructive/10 hover:text-destructive"
        @click="emit('clear')"
      >
        <FilterX class="mr-1.5 size-3.5" aria-hidden="true" />
        Clear all
      </Button>
    </div>

    <div class="flex flex-wrap items-center gap-3">
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
            <SelectTrigger class="h-9 w-full bg-background text-xs" :aria-label="filter.label">
              <SelectValue :placeholder="filter.placeholder ?? filter.label" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem :value="ALL" class="text-xs">
                {{ filter.placeholder ?? `All ${filter.label.toLowerCase()}` }}
              </SelectItem>
              <SelectItem
                v-for="option in selectOptions(filter)"
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

    <div v-if="activeFilters.length" class="flex flex-wrap gap-2" aria-label="Active filters">
      <Badge
        v-for="chip in activeFilters"
        :key="chip.key"
        variant="secondary"
        class="gap-1.5 py-1 pr-1 font-normal"
      >
        <span class="font-medium text-foreground">{{ chip.label }}:</span>
        <span>{{ chip.value }}</span>
        <Button
          type="button"
          variant="ghost"
          size="icon"
          class="size-5 rounded-full text-muted-foreground hover:text-destructive"
          :aria-label="`Clear ${chip.label} filter`"
          @click="clearFilter(chip.key)"
        >
          <X class="size-3" aria-hidden="true" />
        </Button>
      </Badge>
    </div>
  </section>
</template>
