<script setup lang="ts">
import { ref, onMounted, computed, h, watch } from 'vue'
import type { ColumnDef } from '@tanstack/vue-table'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import DataTable from '@/components/data-table/DataTable.vue'
import ListFiltersBar from '@/components/data-table/ListFiltersBar.vue'
import { Button } from '@/components/ui/button'
import PageShell from '@/components/layout/PageShell.vue'
import { useDataTable } from '@/components/data-table/useDataTable'
import { useToast } from '@/composables/useToast'
import { useListFilters } from '@/composables/useListFilters'
import { getErrorMessage } from '@/lib/api-response'
import { applyClientFilters } from '@/lib/list-filter-utils'
import type { RowActionConfig } from '@/modules/shared/registry-actions'
import { getListMeta } from '@/modules/shared/list-filters'
import {
  deleteRecord,
  fetchList,
  patchRecord,
  postRecord,
  updateRecord,
} from '@/services/dashboard.service'

const props = defineProps<{
  title: string
  description?: string
  endpoint: string
  columns: ColumnDef<Record<string, unknown>, unknown>[]
  rowActions?: RowActionConfig[]
  idKey?: string
  listKey?: string
}>()

const toast = useToast()
const rows = ref<Record<string, unknown>[]>([])
const loading = ref(true)
const error = ref<string | null>(null)
const actionLoading = ref<string | null>(null)

const idKey = computed(() => props.idKey ?? 'id')
const subtitle = computed(() => props.description ?? `Data from ${props.endpoint}`)

const displayColumns = computed(() => {
  const cols = [...props.columns]
  if (!props.rowActions?.length) return cols

  cols.push({
    id: 'row-actions',
    header: '',
    cell: ({ row }) => {
      const record = row.original
      const id = record[idKey.value]
      if (id == null) return null

      const buttons = (props.rowActions ?? [])
        .filter((action) => !action.when || action.when(record))
        .map((action) =>
          h(
            Button,
            {
              size: 'sm',
              variant: action.variant ?? 'outline',
              disabled: actionLoading.value === `${action.label}-${id}`,
              onClick: () => runAction(action, record),
            },
            () => action.label,
          ),
        )

      return h('div', { class: 'flex flex-wrap justify-end gap-1' }, buttons)
    },
  })

  return cols
})

const listMeta = computed(() => getListMeta(props.listKey))
const listFilters = computed(() => listMeta.value?.filters ?? [])
const serverSearch = computed(() => listMeta.value?.serverSearch ?? false)
const filterMode = computed(() =>
  listMeta.value?.filterMode ?? (serverSearch.value ? 'server' : 'client'),
)

const {
  values: filterValues,
  activeCount: activeFilterCount,
  buildParams: buildFilterParams,
  clearAll: clearFilters,
} = useListFilters(listFilters, {
  onChange: () => {
    if (filterMode.value === 'server') void load()
  },
})

const tableRows = computed(() => {
  if (filterMode.value === 'server') return rows.value
  return applyClientFilters(rows.value, listFilters.value, filterValues.value)
})

const { table, globalFilter } = useDataTable({
  data: tableRows,
  columns: displayColumns,
  serverSideSearch: serverSearch.value,
})

let searchTimer: ReturnType<typeof setTimeout> | undefined

async function load() {
  loading.value = true
  error.value = null
  try {
    const params = buildFilterParams({ limit: 200 })
    if (serverSearch.value && globalFilter.value.trim()) {
      params.search = globalFilter.value.trim()
    }
    rows.value = await fetchList<Record<string, unknown>>(props.endpoint, params)
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load records'
  } finally {
    loading.value = false
  }
}

watch(globalFilter, () => {
  if (!serverSearch.value) return
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => { void load() }, 350)
})

async function runAction(action: RowActionConfig, row: Record<string, unknown>) {
  const id = row[idKey.value]
  if (id == null) return

  actionLoading.value = `${action.label}-${id}`
  try {
    const body = typeof action.body === 'function' ? action.body(row) : action.body
    const path = action.path(id as string | number)

    if (action.method === 'post') await postRecord(path, body)
    else if (action.method === 'put') await updateRecord(path, body ?? {})
    else if (action.method === 'patch') await patchRecord(path, body)
    else await deleteRecord(path)

    toast.success(action.successMessage ?? `${action.label} completed`)
    await load()
  } catch (err) {
    toast.error(`${action.label} failed`, getErrorMessage(err))
  } finally {
    actionLoading.value = null
  }
}

onMounted(load)
</script>

<template>
  <PageShell :title="title" :description="subtitle">
    <PageLoader v-if="loading" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />
    <DataTable
      v-else
      :table="table"
      :columns="displayColumns"
      :global-filter="globalFilter"
      :search-placeholder="`Search ${title.toLowerCase()}…`"
      @update:global-filter="globalFilter = $event"
    >
      <template v-if="listFilters.length" #filters>
        <ListFiltersBar
          v-model="filterValues"
          :filters="listFilters"
          :active-count="activeFilterCount"
          @clear="clearFilters"
        />
      </template>
    </DataTable>
  </PageShell>
</template>
