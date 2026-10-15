<script setup lang="ts">
import { computed, h, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import type { ColumnDef } from '@tanstack/vue-table'
import { Download, Eye, Pencil, Plus, Printer, RefreshCw } from '@lucide/vue'
import DataTable from '@/components/data-table/DataTable.vue'
import { useDataTable } from '@/components/data-table/useDataTable'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import PageShell from '@/components/layout/PageShell.vue'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { resolveMediaUrl } from '@/lib/media-url'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { badgeVariantForStatus } from '@/lib/ui-status'
import { formatDate } from '@/lib/format'
import { getErrorMessage } from '@/lib/api-response'
import { hrApi } from '@/services/api.service'
import { cn } from '@/lib/utils'

type Employee = {
  id: number
  first_name?: string
  last_name?: string
  name?: string
  employee_number?: string
  email?: string
  phone?: string
  status?: string
  employment_type?: string
  joining_date?: string
  department?: { name?: string }
  designation?: { name?: string }
  photo_url?: string | null
}

const router = useRouter()
const rows = ref<Employee[]>([])
const loading = ref(true)
const error = ref<string | null>(null)
const statusFilter = ref('all')

const filtered = computed(() => {
  if (statusFilter.value === 'all') return rows.value
  return rows.value.filter((r) => String(r.status ?? '').toLowerCase() === statusFilter.value)
})

const statusChips = computed(() => {
  const counts: Record<string, number> = {
    active: 0,
    on_leave: 0,
    probation: 0,
    suspended: 0,
    inactive: 0,
    terminated: 0,
  }
  for (const row of rows.value) {
    const s = String(row.status ?? 'active').toLowerCase()
    counts[s] = (counts[s] ?? 0) + 1
  }
  return [
    { key: 'all', label: 'All', count: rows.value.length },
    { key: 'active', label: 'Active', count: counts.active },
    { key: 'on_leave', label: 'On Leave', count: counts.on_leave },
    { key: 'probation', label: 'Probation', count: counts.probation },
    { key: 'suspended', label: 'Suspended', count: counts.suspended },
    { key: 'inactive', label: 'Inactive', count: counts.inactive },
  ]
})

function displayName(row: Employee) {
  return row.name || [row.first_name, row.last_name].filter(Boolean).join(' ') || 'Staff'
}

function initials(row: Employee) {
  return displayName(row).split(/\s+/).slice(0, 2).map((p) => p[0]?.toUpperCase() ?? '').join('') || 'ST'
}

const columns: ColumnDef<Record<string, unknown>>[] = [
  {
    id: 'staff',
    header: 'Staff',
    cell: ({ row }) => {
      const emp = row.original as unknown as Employee
      const photo = resolveMediaUrl(String(emp.photo_url ?? '').trim() || null)
      return h('div', { class: 'flex items-center gap-3 py-1' }, [
        h(Avatar, { class: 'size-10' }, () => [
          photo ? h(AvatarImage, { src: photo, alt: displayName(emp) }) : null,
          h(AvatarFallback, { class: 'text-xs font-semibold' }, () => initials(emp)),
        ]),
        h('div', { class: 'min-w-0 space-y-1' }, [
          h('p', { class: 'truncate font-semibold' }, displayName(emp)),
          h('p', { class: 'text-xs text-muted-foreground' }, emp.employee_number || `STF-${emp.id}`),
          h('div', { class: 'flex flex-wrap gap-1' }, [
            emp.department?.name
              ? h(Badge, { variant: 'secondary', class: 'font-normal' }, () => emp.department!.name)
              : null,
            emp.designation?.name
              ? h(Badge, { variant: 'outline', class: 'font-normal' }, () => emp.designation!.name)
              : null,
          ]),
        ]),
      ])
    },
  },
  {
    id: 'contact',
    header: 'Contact details',
    cell: ({ row }) => {
      const emp = row.original as unknown as Employee
      return h('div', { class: 'space-y-1 text-sm' }, [
        h('p', emp.email || '—'),
        h('p', { class: 'text-muted-foreground' }, emp.phone || '—'),
        h('div', { class: 'flex flex-wrap gap-1 pt-1' }, [
          emp.employment_type
            ? h(Badge, { variant: 'secondary', class: 'capitalize font-normal' }, () => String(emp.employment_type).replaceAll('_', ' '))
            : null,
          emp.joining_date
            ? h('span', { class: 'text-xs text-muted-foreground' }, `Joined ${formatDate(emp.joining_date)}`)
            : null,
        ]),
      ])
    },
  },
  {
    id: 'status',
    header: 'Status',
    cell: ({ row }) => {
      const emp = row.original as unknown as Employee
      return h(Badge, { variant: badgeVariantForStatus(emp.status), class: 'capitalize' }, () =>
        String(emp.status ?? '—').replaceAll('_', ' '),
      )
    },
  },
  {
    id: 'actions',
    header: () => h('span', { class: 'sr-only' }, 'Actions'),
    cell: ({ row }) => {
      const emp = row.original as unknown as Employee
      return h('div', { class: 'flex justify-end gap-1' }, [
        h(Button, {
          variant: 'outline',
          size: 'icon-sm',
          class: 'rounded-xl',
          onClick: () => router.push(`/hr/employees/${emp.id}`),
        }, () => h(Eye, { class: 'size-4' })),
        h(Button, {
          variant: 'outline',
          size: 'icon-sm',
          class: 'rounded-xl',
          onClick: () => router.push({ path: '/hr', query: { tab: 'employees', edit: String(emp.id) } }),
        }, () => h(Pencil, { class: 'size-4' })),
      ])
    },
  },
]

const tableData = computed(() => filtered.value as unknown as Record<string, unknown>[])
const { table, globalFilter } = useDataTable({ data: tableData as never, columns })

async function load() {
  loading.value = true
  error.value = null
  try {
    rows.value = (await hrApi.employees.list({
      per_page: 200,
      include: 'department,designation',
    } as never)) as Employee[]
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load staff')
    rows.value = []
  } finally {
    loading.value = false
  }
}

function addStaff() {
  void router.push({ path: '/hr', query: { tab: 'employees', create: '1' } })
}

function printPage() {
  window.print()
}

onMounted(load)
</script>

<template>
  <PageShell
    title="Employees"
    description="Non-teaching staff records, departments, and employment status."
    :breadcrumbs="['HR', 'Employees']"
    max-width="wide"
  >
    <template #actions>
      <Button type="button" variant="outline" size="icon" class="rounded-xl" aria-label="Refresh" @click="load">
        <RefreshCw class="size-4" />
      </Button>
      <Button type="button" variant="outline" class="rounded-xl" disabled>
        <Download class="mr-1.5 size-4" />
        Export Staff
      </Button>
      <Button type="button" variant="outline" class="rounded-xl" @click="printPage">
        <Printer class="mr-1.5 size-4" />
        Print
      </Button>
      <Button type="button" class="rounded-xl" @click="addStaff">
        <Plus class="mr-1.5 size-4" />
        Add Staff
      </Button>
    </template>

    <div class="mb-4 flex flex-wrap gap-2">
      <button
        v-for="chip in statusChips"
        :key="chip.key"
        type="button"
        :class="cn(
          'rounded-full border px-3 py-1 text-xs font-medium',
          statusFilter === chip.key ? 'border-primary/40 bg-primary/10 text-primary' : 'border-border text-muted-foreground',
        )"
        @click="statusFilter = chip.key"
      >
        {{ chip.label }} {{ chip.count }}
      </button>
    </div>

    <PageLoader v-if="loading" label="Loading staff" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />
    <DataTable
      v-else
      :table="table"
      :columns="columns"
      :global-filter="globalFilter"
      search-placeholder="Search staff or employee ID…"
      @update:global-filter="globalFilter = $event"
    />
  </PageShell>
</template>
