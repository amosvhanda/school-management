<script setup lang="ts" generic="T">
import { FlexRender, type ColumnDef, type Table as TableType } from '@tanstack/vue-table'
import { Search } from 'lucide-vue-next'
import { Input } from '@/components/ui/input'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { Button } from '@/components/ui/button'
import EmptyState from '@/components/feedback/EmptyState.vue'

defineProps<{
  table: TableType<T>
  columns: ColumnDef<T, unknown>[]
  globalFilter: string
  searchable?: boolean
  searchPlaceholder?: string
  /** Server-side pagination metadata */
  serverPagination?: boolean
  serverPage?: number
  serverPageCount?: number
  serverTotal?: number
}>()

defineEmits<{
  'update:globalFilter': [value: string]
  'server-page-change': [page: number]
}>()

defineSlots<{
  toolbar?(): unknown
  filters?(): unknown
} & {
  [name in `cell-${string}`]?: (props: { row: any }) => unknown
}>()
</script>

<template>
  <div class="rounded-xl border bg-card text-card-foreground shadow-sm overflow-hidden">
    <!-- Top Interactive Toolbar -->
    <div
      v-if="searchable !== false"
      class="flex flex-col gap-3 border-b border-border/60 p-4 sm:flex-row sm:items-center sm:justify-between bg-card"
    >
      <div class="relative w-full max-w-sm">
        <Search
          class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
          aria-hidden="true"
        />
        <Input
          :model-value="globalFilter"
          :placeholder="searchPlaceholder ?? 'Search records…'"
          :aria-label="searchPlaceholder ?? 'Search records'"
          class="h-10 pl-9 text-sm bg-background"
          type="search"
          @update:model-value="$emit('update:globalFilter', String($event))"
        />
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <slot name="toolbar" />
      </div>
    </div>

    <!-- Context Optional Filters Bar -->
    <slot name="filters" />

    <!-- Table Workspace -->
    <div class="overflow-x-auto">
      <Table aria-label="Data table">
        <TableHeader>
          <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id" class="hover:bg-transparent border-b border-border/60">
            <TableHead
              v-for="header in headerGroup.headers"
              :key="header.id"
              class="h-11 bg-muted/40 text-xs font-semibold uppercase tracking-wider text-muted-foreground vertical-middle"
            >
              <FlexRender
                v-if="!header.isPlaceholder"
                :render="header.column.columnDef.header"
                :props="header.getContext()"
              />
            </TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <template v-if="table.getRowModel().rows.length">
            <TableRow
              v-for="row in table.getRowModel().rows"
              :key="row.id"
              class="transition-colors border-b border-border/40 last:border-0 hover:bg-muted/30"
            >
              <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id" class="py-3 text-sm">
                <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
              </TableCell>
            </TableRow>
          </template>
          <TableRow v-else>
            <TableCell :colspan="columns.length" class="h-40 text-center">
              <EmptyState />
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>

    <!-- Bottom Pagination Infrastructure -->
    <div
      class="flex flex-col gap-3 border-t border-border/60 bg-card px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
      aria-live="polite"
    >
      <p class="text-xs font-medium text-muted-foreground">
        <template v-if="serverPagination">
          Page {{ serverPage ?? 1 }} of {{ serverPageCount ?? 1 }}
          <span v-if="serverTotal != null"> · {{ serverTotal.toLocaleString() }} records</span>
        </template>
        <template v-else>
          Page {{ table.getState().pagination.pageIndex + 1 }} of {{ table.getPageCount() || 1 }}
        </template>
      </p>
      <nav class="flex gap-2" aria-label="Table pagination">
        <Button
          variant="outline"
          size="sm"
          class="h-8 text-xs px-3"
          :disabled="serverPagination ? (serverPage ?? 1) <= 1 : !table.getCanPreviousPage()"
          @click="serverPagination ? $emit('server-page-change', (serverPage ?? 1) - 1) : table.previousPage()"
        >
          Previous
        </Button>
        <Button
          variant="outline"
          size="sm"
          class="h-8 text-xs px-3"
          :disabled="serverPagination ? (serverPage ?? 1) >= (serverPageCount ?? 1) : !table.getCanNextPage()"
          @click="serverPagination ? $emit('server-page-change', (serverPage ?? 1) + 1) : table.nextPage()"
        >
          Next
        </Button>
      </nav>
    </div>
  </div>
</template>
