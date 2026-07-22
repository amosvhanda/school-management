<script setup lang="ts">
import { computed, h, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import type { ColumnDef } from '@tanstack/vue-table'
import {
  Eye,
  LogIn,
  RefreshCw,
  ScrollText,
  Shield,
} from '@lucide/vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import DataTable from '@/components/data-table/DataTable.vue'
import ListFiltersBar from '@/components/data-table/ListFiltersBar.vue'
import PageShell from '@/components/layout/PageShell.vue'
import DatePicker from '@/components/forms/DatePicker.vue'
import { useDataTable } from '@/components/data-table/useDataTable'
import { useListFilters } from '@/composables/useListFilters'
import { getErrorMessage } from '@/lib/api-response'
import {
  auditActionLabel,
  auditActionTone,
  AUDIT_ACTIONS,
  AUDIT_MODULES,
  roleLabel,
} from '@/lib/audit-labels'
import { formatDateTime, formatRelativeTime } from '@/lib/format'
import type { ListFilterSchema } from '@/modules/shared/list-filters'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetHeader,
  SheetTitle,
} from '@/components/ui/sheet'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import {
  complianceApi,
  type AuditLogRow,
  type LoginHistoryRow,
} from '@/services/api.service'
import type { Paginator } from '@/types/api'

const route = useRoute()
const router = useRouter()

const activeTab = ref<'activity' | 'login'>(route.query.tab === 'login' ? 'login' : 'activity')
const loading = ref(true)
const error = ref<string | null>(null)
const search = ref('')
const dateFrom = ref('')
const dateTo = ref('')
const page = ref(1)
const perPage = 25

const activityRows = ref<AuditLogRow[]>([])
const activityMeta = ref<Pick<Paginator<AuditLogRow>, 'current_page' | 'last_page' | 'total'>>({
  current_page: 1,
  last_page: 1,
  total: 0,
})

const loginRows = ref<LoginHistoryRow[]>([])
const loginMeta = ref<Pick<Paginator<LoginHistoryRow>, 'current_page' | 'last_page' | 'total'>>({
  current_page: 1,
  last_page: 1,
  total: 0,
})

const selectedLog = ref<AuditLogRow | null>(null)
const detailOpen = ref(false)

const activityFilters: ListFilterSchema[] = [
  {
    key: 'module',
    label: 'Area',
    type: 'select',
    options: [
      { label: 'All areas', value: '' },
      ...Object.entries(AUDIT_MODULES).map(([value, label]) => ({ label, value })),
    ],
  },
  {
    key: 'action',
    label: 'Action',
    type: 'select',
    options: [
      { label: 'All actions', value: '' },
      ...Object.entries(AUDIT_ACTIONS).map(([value, label]) => ({ label, value })),
    ],
  },
]

const loginFilters: ListFilterSchema[] = [
  {
    key: 'event',
    label: 'Event',
    type: 'select',
    options: [
      { label: 'All events', value: '' },
      { label: 'Signed in', value: 'login' },
      { label: 'Signed out', value: 'logout' },
      { label: 'Failed sign-in', value: 'failed_login' },
    ],
  },
]

const {
  values: activityFilterValues,
  activeCount: activityFilterCount,
  buildParams: buildActivityParams,
  clearAll: clearActivityFilters,
} = useListFilters(computed(() => activityFilters))

const {
  values: loginFilterValues,
  activeCount: loginFilterCount,
  buildParams: buildLoginParams,
  clearAll: clearLoginFilters,
} = useListFilters(computed(() => loginFilters))

watch(activeTab, (tab) => {
  const query = { ...route.query }
  if (tab === 'login') query.tab = 'login'
  else delete query.tab
  void router.replace({ query })
})

watch(
  () => route.query.tab,
  (tab) => {
    activeTab.value = tab === 'login' ? 'login' : 'activity'
  },
)

let searchTimer: ReturnType<typeof setTimeout> | undefined

watch(search, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    page.value = 1
    void loadActiveTab()
  }, 350)
})

function openDetail(row: AuditLogRow) {
  selectedLog.value = row
  detailOpen.value = true
}

/** Short row headline — prefer the affected record, not a long sentence. */
function activityHeadline(row: AuditLogRow): string {
  const name = row.target?.name || row.target?.label
  if (name) return name

  const type = row.target?.type_label
  if (type) return type

  const summary = row.summary || row.description
  if (!summary) return '—'

  // Strip “Actor verb … in Area” wrappers when present.
  return summary
    .replace(/^[^“"]+\s+(created|updated|deleted|approved|rejected|recorded)\s+/i, '')
    .replace(/\s+in\s+[^]+$/i, '')
    .replace(/^[“"]|[”"]$/g, '')
    .trim() || summary
}

const activityColumns: ColumnDef<AuditLogRow>[] = [
  {
    id: 'when',
    header: 'When',
    accessorKey: 'created_at',
    cell: ({ row }) =>
      h(
        'time',
        {
          class: 'text-sm text-muted-foreground whitespace-nowrap',
          datetime: row.original.created_at,
          title: formatDateTime(row.original.created_at),
        },
        formatRelativeTime(row.original.created_at),
      ),
  },
  {
    id: 'who',
    header: 'Who',
    accessorFn: (row) => row.actor.name,
    cell: ({ row }) =>
      h('p', { class: 'text-sm font-medium truncate max-w-[12rem]' }, row.original.actor.name || 'System'),
  },
  {
    id: 'record',
    header: 'Record',
    accessorFn: (row) => activityHeadline(row),
    cell: ({ row }) => {
      const type = row.original.target?.type_label
      const headline = activityHeadline(row.original)
      return h('div', { class: 'min-w-[14rem] max-w-md' }, [
        type
          ? h('p', { class: 'text-xs text-muted-foreground' }, type)
          : null,
        h('p', { class: 'text-sm font-medium leading-snug line-clamp-2' }, headline),
      ])
    },
  },
  {
    id: 'action',
    header: 'Action',
    accessorKey: 'action_label',
    cell: ({ row }) =>
      h(
        Badge,
        { variant: auditActionTone(row.original.action), class: 'font-normal' },
        () => row.original.action_label || auditActionLabel(row.original.action),
      ),
  },
  {
    id: 'details',
    header: '',
    enableSorting: false,
    cell: ({ row }) =>
      h(
        Button,
        {
          variant: 'ghost',
          size: 'sm',
          class: 'h-8 w-8 p-0',
          'aria-label': `View details for ${activityHeadline(row.original)}`,
          onClick: () => openDetail(row.original),
        },
        () => [h(Eye, { class: 'size-4', 'aria-hidden': 'true' })],
      ),
  },
]

const loginColumns: ColumnDef<LoginHistoryRow>[] = [
  {
    id: 'when',
    header: 'When',
    accessorKey: 'created_at',
    cell: ({ row }) =>
      h(
        'time',
        {
          class: 'text-sm text-muted-foreground whitespace-nowrap',
          datetime: row.original.created_at,
          title: formatDateTime(row.original.created_at),
        },
        formatRelativeTime(row.original.created_at),
      ),
  },
  {
    id: 'who',
    header: 'User',
    accessorFn: (row) => row.actor.name,
    cell: ({ row }) =>
      h('p', { class: 'text-sm font-medium truncate max-w-[12rem]' }, row.original.actor.name || '—'),
  },
  {
    id: 'event',
    header: 'Event',
    accessorKey: 'event_label',
    cell: ({ row }) =>
      h(
        Badge,
        { variant: auditActionTone(row.original.event), class: 'font-normal' },
        () => row.original.event_label,
      ),
  },
  {
    id: 'summary',
    header: 'Summary',
    accessorKey: 'summary',
    cell: ({ row }) =>
      h('p', { class: 'text-sm leading-snug line-clamp-2 max-w-md' }, row.original.summary),
  },
  {
    id: 'device',
    header: 'Device',
    accessorFn: (row) => row.context.platform,
    cell: ({ row }) => {
      const ctx = row.original.context
      const parts = [ctx.platform, ctx.device_type].filter(Boolean)
      return h('p', { class: 'text-sm text-muted-foreground capitalize' }, parts.join(' · ') || '—')
    },
  },
  {
    id: 'ip',
    header: 'IP',
    accessorKey: 'context.ip_address',
    cell: ({ row }) =>
      h('p', { class: 'text-sm font-mono text-muted-foreground' }, row.original.context.ip_address ?? '—'),
  },
]

const {
  table: activityTable,
  globalFilter: activitySearch,
} = useDataTable({
  data: activityRows,
  columns: activityColumns,
  pageSize: perPage,
})

const {
  table: loginTable,
  globalFilter: loginSearch,
} = useDataTable({
  data: loginRows,
  columns: loginColumns,
  pageSize: perPage,
})

async function loadActivity() {
  loading.value = true
  error.value = null
  try {
    const params = buildActivityParams({
      page: page.value,
      per_page: perPage,
    })
    if (search.value.trim()) params.search = search.value.trim()
    if (dateFrom.value) params.from = dateFrom.value
    if (dateTo.value) params.to = dateTo.value
    const result = await complianceApi.auditLogs.listPaginated(params)
    activityRows.value = result.data
    activityMeta.value = {
      current_page: result.current_page,
      last_page: result.last_page,
      total: result.total,
    }
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load audit trail')
  } finally {
    loading.value = false
  }
}

async function loadLoginHistory() {
  loading.value = true
  error.value = null
  try {
    const params = buildLoginParams({
      page: page.value,
      per_page: perPage,
    })
    if (dateFrom.value) params.from = dateFrom.value
    if (dateTo.value) params.to = dateTo.value
    const result = await complianceApi.auditLogs.loginHistoryPaginated(params)
    loginRows.value = result.data
    loginMeta.value = {
      current_page: result.current_page,
      last_page: result.last_page,
      total: result.total,
    }
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load sign-in history')
  } finally {
    loading.value = false
  }
}

async function loadActiveTab() {
  if (activeTab.value === 'login') await loadLoginHistory()
  else await loadActivity()
}

function goToPage(next: number) {
  page.value = next
  void loadActiveTab()
}

watch(activeTab, () => {
  page.value = 1
  void loadActiveTab()
})

watch(activityFilterValues, () => {
  page.value = 1
  void loadActivity()
}, { deep: true })

watch(loginFilterValues, () => {
  page.value = 1
  void loadLoginHistory()
}, { deep: true })

watch([dateFrom, dateTo], () => {
  page.value = 1
  void loadActiveTab()
})

onMounted(() => {
  if (typeof route.query.module === 'string' && route.query.module) {
    activityFilterValues.value = {
      ...activityFilterValues.value,
      module: route.query.module,
    }
  }
  void loadActiveTab()
})

const detailChanges = computed(() => {
  const log = selectedLog.value
  if (!log) return []
  const hidden = new Set(['id', 'school_id', 'created_at', 'updated_at', 'deleted_at'])
  const source = log.changes?.length
    ? log.changes
    : []
  return source.filter((change) => !hidden.has(change.field))
})

function formatChangeValue(value: unknown): string {
  if (value == null || value === '') return '—'
  if (typeof value === 'object') return JSON.stringify(value, null, 2)
  return String(value)
}
</script>

<template>
  <PageShell
    title="Audit trail"
    description="A clear record of who did what across your school — changes, exports, and sign-ins."
   max-width="wide">
    <Tabs v-model="activeTab" class="space-y-6">
      <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <TabsList aria-label="Audit views">
          <TabsTrigger value="activity" class="gap-2">
            <ScrollText class="size-4" aria-hidden="true" />
            Activity log
          </TabsTrigger>
          <TabsTrigger value="login" class="gap-2">
            <LogIn class="size-4" aria-hidden="true" />
            Sign-in history
          </TabsTrigger>
        </TabsList>

        <Button variant="outline" size="sm" :disabled="loading" @click="loadActiveTab">
          <RefreshCw class="size-4" :class="loading ? 'animate-spin' : ''" aria-hidden="true" />
          Refresh
        </Button>
      </div>

      <TabsContent value="activity" class="space-y-4">
        <div class="flex flex-col gap-3 rounded-xl border bg-card p-4">
          <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 flex-1">
              <div class="space-y-2 sm:col-span-2 lg:col-span-1 xl:col-span-2">
                <Label for="audit-search">Search</Label>
                <Input
                  id="audit-search"
                  v-model="search"
                  type="search"
                  placeholder="Search activity…"
                  autocomplete="off"
                />
              </div>
              <div class="space-y-2">
                <Label for="audit-from">From</Label>
                <DatePicker id="audit-from" v-model="dateFrom" placeholder="Start date" />
              </div>
              <div class="space-y-2">
                <Label for="audit-to">To</Label>
                <DatePicker id="audit-to" v-model="dateTo" placeholder="End date" />
              </div>
            </div>
          </div>
          <ListFiltersBar
            v-model="activityFilterValues"
            :filters="activityFilters"
            :active-count="activityFilterCount"
            @clear="clearActivityFilters"
          />
        </div>

        <PageLoader v-if="loading && !activityRows.length" label="Loading audit trail…" />
        <ErrorState v-else-if="error" :message="error" @retry="loadActivity" />
        <template v-else>
          <p class="text-sm text-muted-foreground" aria-live="polite">
            {{ activityMeta.total }} {{ activityMeta.total === 1 ? 'entry' : 'entries' }}
          </p>
          <DataTable
            :table="activityTable"
            :columns="activityColumns"
            :global-filter="activitySearch"
            :searchable="false"
            server-pagination
            :server-page="activityMeta.current_page"
            :server-page-count="activityMeta.last_page"
            :server-total="activityMeta.total"
            @server-page-change="goToPage"
          />
        </template>
      </TabsContent>

      <TabsContent value="login" class="space-y-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:flex-wrap sm:items-end">
          <div class="space-y-2">
            <Label for="login-from">From</Label>
            <DatePicker id="login-from" v-model="dateFrom" placeholder="Start date" class="w-full sm:w-auto" />
          </div>
          <div class="space-y-2">
            <Label for="login-to">To</Label>
            <DatePicker id="login-to" v-model="dateTo" placeholder="End date" class="w-full sm:w-auto" />
          </div>
        </div>

        <ListFiltersBar
          v-model="loginFilterValues"
          :filters="loginFilters"
          :active-count="loginFilterCount"
          @clear="clearLoginFilters"
        />

        <PageLoader v-if="loading && !loginRows.length" label="Loading sign-in history…" />
        <ErrorState v-else-if="error" :message="error" @retry="loadLoginHistory" />
        <template v-else>
          <p class="text-sm text-muted-foreground" aria-live="polite">
            {{ loginMeta.total }} sign-in {{ loginMeta.total === 1 ? 'event' : 'events' }}
          </p>
          <DataTable
            :table="loginTable"
            :columns="loginColumns"
            :global-filter="loginSearch"
            :searchable="false"
            server-pagination
            :server-page="loginMeta.current_page"
            :server-page-count="loginMeta.last_page"
            :server-total="loginMeta.total"
            @server-page-change="goToPage"
          />
        </template>
      </TabsContent>
    </Tabs>

    <Sheet v-model:open="detailOpen">
      <SheetContent class="flex w-full flex-col sm:max-w-lg">
        <SheetHeader>
          <SheetTitle>Audit entry details</SheetTitle>
          <SheetDescription v-if="selectedLog">
            {{ formatDateTime(selectedLog.created_at) }}
          </SheetDescription>
        </SheetHeader>

        <div v-if="selectedLog" class="flex-1 space-y-6 overflow-y-auto py-4">
          <section class="space-y-2">
            <h3 class="text-sm font-medium">Summary</h3>
            <p class="text-sm leading-relaxed">{{ selectedLog.summary || selectedLog.description }}</p>
            <div class="flex flex-wrap gap-2 pt-1">
              <Badge variant="outline">{{ selectedLog.module_label }}</Badge>
              <Badge :variant="auditActionTone(selectedLog.action)">{{ selectedLog.action_label }}</Badge>
            </div>
          </section>

          <section class="space-y-2">
            <h3 class="text-sm font-medium">Performed by</h3>
            <dl class="grid gap-1 text-sm">
              <div class="flex justify-between gap-4">
                <dt class="text-muted-foreground">Name</dt>
                <dd class="font-medium text-right">{{ selectedLog.actor.name }}</dd>
              </div>
              <div v-if="selectedLog.actor.email" class="flex justify-between gap-4">
                <dt class="text-muted-foreground">Email</dt>
                <dd class="text-right break-all">{{ selectedLog.actor.email }}</dd>
              </div>
              <div v-if="selectedLog.actor.role" class="flex justify-between gap-4">
                <dt class="text-muted-foreground">Role</dt>
                <dd class="text-right capitalize">{{ roleLabel(selectedLog.actor.role) }}</dd>
              </div>
            </dl>
          </section>

          <section v-if="selectedLog.target?.type_label" class="space-y-2">
            <h3 class="text-sm font-medium">Record affected</h3>
            <p class="text-sm">
              {{ selectedLog.target.type_label }}
              <span v-if="selectedLog.target.name || selectedLog.target.label">
                · {{ selectedLog.target.name || selectedLog.target.label }}
              </span>
            </p>
          </section>

          <section v-if="detailChanges.length" class="space-y-3">
            <h3 class="text-sm font-medium">Changes</h3>
            <div
              v-for="change in detailChanges"
              :key="change.field"
              class="rounded-lg border p-3 space-y-2"
            >
              <p class="text-sm font-medium">{{ change.label }}</p>
              <div class="grid gap-2 sm:grid-cols-2 text-xs">
                <div>
                  <p class="text-muted-foreground mb-1">Before</p>
                  <pre class="whitespace-pre-wrap break-words rounded bg-muted/50 p-2">{{ formatChangeValue(change.from) }}</pre>
                </div>
                <div>
                  <p class="text-muted-foreground mb-1">After</p>
                  <pre class="whitespace-pre-wrap break-words rounded bg-muted/50 p-2">{{ formatChangeValue(change.to) }}</pre>
                </div>
              </div>
            </div>
          </section>

          <section class="space-y-2">
            <h3 class="text-sm font-medium flex items-center gap-2">
              <Shield class="size-4" aria-hidden="true" />
              Context
            </h3>
            <dl class="grid gap-1 text-sm">
              <div v-if="selectedLog.context.ip_address" class="flex justify-between gap-4">
                <dt class="text-muted-foreground">IP address</dt>
                <dd class="font-mono text-right">{{ selectedLog.context.ip_address }}</dd>
              </div>
              <div v-if="selectedLog.context.platform" class="flex justify-between gap-4">
                <dt class="text-muted-foreground">Platform</dt>
                <dd class="text-right capitalize">{{ selectedLog.context.platform }}</dd>
              </div>
              <div v-if="selectedLog.context.device_type" class="flex justify-between gap-4">
                <dt class="text-muted-foreground">Device</dt>
                <dd class="text-right capitalize">{{ selectedLog.context.device_type }}</dd>
              </div>
              <div v-if="selectedLog.context.request_path" class="flex justify-between gap-4">
                <dt class="text-muted-foreground">Request</dt>
                <dd class="font-mono text-right text-xs break-all">
                  {{ selectedLog.context.request_method }} {{ selectedLog.context.request_path }}
                </dd>
              </div>
            </dl>
          </section>
        </div>
      </SheetContent>
    </Sheet>
  </PageShell>
</template>
