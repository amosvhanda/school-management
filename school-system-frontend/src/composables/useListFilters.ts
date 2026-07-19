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
    const params: ListQueryParams = { ...base }
    const filter: Record<string, string | number | boolean | undefined | null> = {
      ...(base.filter ?? {}),
    }

    for (const listFilter of filters.value ?? []) {
      const value = values.value[listFilter.key]
      if (value != null && value !== '') {
        filter[listFilter.key] = value
        // Also set top-level for endpoints not yet on Spatie Query Builder.
        params[listFilter.key] = value
      }
    }

    // Prefer Spatie filter[] contract; keep top-level search for legacy callers.
    if (typeof params.search === 'string' && params.search !== '' && filter.search == null) {
      filter.search = params.search
    }

    if (Object.keys(filter).length > 0) {
      params.filter = filter
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
