import { computed, ref, watch, type Ref } from 'vue'
import type { ListFilterSchema } from '@/modules/shared/list-filters'
import type { ListQueryParams } from '@/types/api'

export function useListFilters(
  filters: Ref<ListFilterSchema[] | undefined>,
  options?: { onChange?: () => void },
) {
  const values = ref<Record<string, string>>({})

  const activeCount = computed(() =>
    Object.values(values.value).filter((v) => v != null && v !== '').length,
  )

  const hasActiveFilters = computed(() => activeCount.value > 0)

  function buildParams(base: ListQueryParams = {}): ListQueryParams {
    const params = { ...base }
    for (const filter of filters.value ?? []) {
      const value = values.value[filter.key]
      if (value != null && value !== '') {
        params[filter.key] = value
      }
    }
    return params
  }

  function setValue(key: string, value: string) {
    values.value = { ...values.value, [key]: value }
  }

  function clearAll() {
    values.value = {}
  }

  function clearFilter(key: string) {
    const next = { ...values.value }
    delete next[key]
    values.value = next
  }

  watch(
    values,
    () => options?.onChange?.(),
    { deep: true },
  )

  return {
    values,
    activeCount,
    hasActiveFilters,
    buildParams,
    setValue,
    clearAll,
    clearFilter,
  }
}
