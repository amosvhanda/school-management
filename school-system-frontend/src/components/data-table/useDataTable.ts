import { ref, computed, unref, type Ref } from 'vue'
import {
  getCoreRowModel,
  getFilteredRowModel,
  getPaginationRowModel,
  getSortedRowModel,
  useVueTable,
  type ColumnDef,
  type SortingState,
} from '@tanstack/vue-table'
import { flattenRowSearchText } from '@/lib/list-filter-utils'

export function useDataTable<T>(options: {
  data: Ref<T[]>
  columns: ColumnDef<T, unknown>[] | Ref<ColumnDef<T, unknown>[]>
  pageSize?: number
  /** When true, global filter is handled by the API — skip client-side row filtering. */
  serverSideSearch?: boolean
}) {
  const sorting = ref<SortingState>([])
  const globalFilter = ref('')

  const table = useVueTable({
    get data() {
      return options.data.value
    },
    get columns() {
      return unref(options.columns)
    },
    state: {
      get sorting() {
        return sorting.value
      },
      get globalFilter() {
        return globalFilter.value
      },
    },
    onSortingChange: (updater) => {
      sorting.value = typeof updater === 'function' ? updater(sorting.value) : updater
    },
    onGlobalFilterChange: (updater) => {
      globalFilter.value = typeof updater === 'function' ? updater(globalFilter.value) : updater
    },
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: options.serverSideSearch ? undefined : getFilteredRowModel(),
    globalFilterFn: options.serverSideSearch
      ? undefined
      : (row, _columnId, filterValue) => {
          const query = String(filterValue ?? '').trim().toLowerCase()
          if (!query) return true
          return flattenRowSearchText(row.original as Record<string, unknown>).includes(query)
        },
    getPaginationRowModel: getPaginationRowModel(),
    initialState: {
      pagination: { pageSize: options.pageSize ?? 10 },
    },
  })

  const pageCount = computed(() => table.getPageCount())

  return { table, sorting, globalFilter, pageCount }
}
