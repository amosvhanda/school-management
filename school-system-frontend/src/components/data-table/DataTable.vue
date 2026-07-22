<script setup lang="ts" generic="T">
import { FlexRender, type ColumnDef, type Table as TableType } from '@tanstack/vue-table'
import { Search } from '@lucide/vue'
import { Input } from '@/components/ui/input'
import {
  Table,
  TableBody,
  TableCell,
  TableEmpty,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'
import { tableHeaderRowClass } from '@/components/ui/table/table-chrome'

withDefaults(
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
    /**
     * When false, render table chrome without an outer Card
     * (use inside WorkspaceCard to avoid nested cards).
     */
    framed?: boolean
    /** Hide pagination footer (ops / embedded summary tables). */
    showPagination?: boolean
    emptyTitle?: string
    emptyDescription?: string
  }>(),
  {
    searchable: true,
    framed: true,
    showPagination: true,
    emptyTitle: 'No records found',
    emptyDescription: 'Try adjusting search or filters, or create a new record.',
  },
)

defineEmits<{
  'update:globalFilter': [value: string]
  'server-page-change': [page: number]
}>()

defineSlots<{
  toolbar?(): unknown
  filters?(): unknown
} & {
  [name in `cell-${string}`]?: (props: { row: any; cell?: any }) => unknown
}>()
</script>

<template>
  <component
    :is="framed === false ? 'div' : Card"
    :class="framed === false ? 'overflow-hidden' : 'gap-0 overflow-hidden py-0'"
  >
    <div
      v-if="searchable !== false"
      class="flex flex-col gap-3 border-b border-border/60 bg-card px-4 py-4 sm:flex-row sm:items-center sm:justify-between"
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
          class="h-10 bg-background pl-9 text-sm"
          type="search"
          @update:model-value="$emit('update:globalFilter', String($event))"
        />
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <slot name="toolbar" />
      </div>
    </div>

    <slot name="filters" />

    <div class="overflow-x-auto">
      <Table aria-label="Data table">
        <TableHeader>
          <TableRow
            v-for="headerGroup in table.getHeaderGroups()"
            :key="headerGroup.id"
            :class="tableHeaderRowClass"
          >
            <TableHead
              v-for="header in headerGroup.headers"
              :key="header.id"
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
            >
              <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id">
                <slot
                  v-if="$slots[`cell-${cell.column.id}`]"
                  :name="`cell-${cell.column.id}`"
                  :row="row"
                  :cell="cell"
                />
                <FlexRender
                  v-else
                  :render="cell.column.columnDef.cell"
                  :props="cell.getContext()"
                />
              </TableCell>
            </TableRow>
          </template>
          <TableEmpty
            v-else
            :colspan="columns.length"
            :title="emptyTitle"
            :description="emptyDescription"
          />
        </TableBody>
      </Table>
    </div>

    <div
      v-if="showPagination"
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
          class="h-8 px-3 text-xs"
          :disabled="serverPagination ? (serverPage ?? 1) <= 1 : !table.getCanPreviousPage()"
          @click="serverPagination ? $emit('server-page-change', (serverPage ?? 1) - 1) : table.previousPage()"
        >
          Previous
        </Button>
        <Button
          variant="outline"
          size="sm"
          class="h-8 px-3 text-xs"
          :disabled="serverPagination ? (serverPage ?? 1) >= (serverPageCount ?? 1) : !table.getCanNextPage()"
          @click="serverPagination ? $emit('server-page-change', (serverPage ?? 1) + 1) : table.nextPage()"
        >
          Next
        </Button>
      </nav>
    </div>
  </component>
</template>
