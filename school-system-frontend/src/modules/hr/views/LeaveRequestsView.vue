<script setup lang="ts">
import { computed, h, nextTick, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import type { ColumnDef } from '@tanstack/vue-table'
import {
  ClipboardCheck,
  Palmtree,
  Plus,
  ScrollText,
} from '@lucide/vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import DataTable from '@/components/data-table/DataTable.vue'
import TableRowActions from '@/components/data-table/TableRowActions.vue'
import type { TableRowMenuAction } from '@/components/data-table/TableRowActions.vue'
import ListFiltersBar from '@/components/data-table/ListFiltersBar.vue'
import FormSheet from '@/components/forms/FormSheet.vue'
import PageShell from '@/components/layout/PageShell.vue'
import KpiCard from '@/components/dashboard/KpiCard.vue'
import { useDataTable } from '@/components/data-table/useDataTable'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetHeader,
  SheetTitle,
} from '@/components/ui/sheet'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'
import { useFormSheetLoader } from '@/composables/useFormSheetLoader'
import { useListFilters } from '@/composables/useListFilters'
import { formatDate, formatDateTime } from '@/lib/format'
import { getErrorMessage } from '@/lib/api-response'
import {
  leaveFormFields,
  leaveFormSchema,
  leaveStatusLabel,
  leaveTypeLabel,
  LEAVE_STATUS_OPTIONS,
  mapLeaveFormToPayload,
  type LeaveRequestRow,
} from '@/modules/hr/leave-form'
import type { ListFilterSchema } from '@/modules/shared/list-filters'
import { moduleEndpoints } from '@/services'
import { hrApi } from '@/services/api.service'

const toast = useToast()
const route = useRoute()
const router = useRouter()
const { checkCapability } = useAuth()
const canViewAudit = computed(() => checkCapability('canViewAuditLogs'))

const rows = ref<LeaveRequestRow[]>([])
const loading = ref(true)
const error = ref<string | null>(null)
const sheetOpen = ref(false)
const saving = ref(false)
const formResetValues = ref<Record<string, unknown> | undefined>()
const formSheetRef = ref<{ applyServerErrors: (error: unknown) => void } | null>(null)
const serverPage = ref(1)
const serverPageCount = ref(1)
const serverTotal = ref(0)
const perPage = 15
const pendingCount = ref(0)
const approvedCount = ref(0)
const rejectedCount = ref(0)

const detailOpen = ref(false)
const detailRow = ref<LeaveRequestRow | null>(null)
const rejectOpen = ref(false)
const rejectTarget = ref<LeaveRequestRow | null>(null)
const rejectNotes = ref('')
const actionLoading = ref<string | null>(null)

const leaveFilters: ListFilterSchema[] = [
  {
    key: 'status',
    label: 'Status',
    type: 'select',
    placeholder: 'Any status',
    options: [...LEAVE_STATUS_OPTIONS],
  },
  {
    key: 'teacher_id',
    label: 'Staff member',
    type: 'relation',
    placeholder: 'Any teacher',
    relation: {
      endpoint: moduleEndpoints.teachers,
      moduleLabel: 'teacher',
    },
  },
]

const {
  values: filterValues,
  activeCount: activeFilterCount,
  buildParams,
  clearAll: clearFilters,
} = useListFilters(computed(() => leaveFilters), {
  onChange: () => {
    serverPage.value = 1
    void load(1)
  },
})

function statusVariant(status?: string): 'default' | 'secondary' | 'destructive' | 'outline' {
  if (status === 'approved') return 'default'
  if (status === 'rejected') return 'destructive'
  if (status === 'pending') return 'secondary'
  return 'outline'
}

const columns: ColumnDef<LeaveRequestRow>[] = [
  {
    id: 'staff',
    header: 'Staff member',
    cell: ({ row }) => {
      const name = row.original.teacher_name ?? '—'
      const emp = row.original.employee_id ? ` · ${row.original.employee_id}` : ''
      return `${name}${emp}`
    },
  },
  {
    id: 'type',
    header: 'Type',
    cell: ({ row }) => leaveTypeLabel(row.original.type, row.original.leave_type_name),
  },
  {
    id: 'dates',
    header: 'Dates',
    cell: ({ row }) => {
      const start = formatDate(row.original.start_date)
      const end = formatDate(row.original.end_date)
      return `${start} – ${end}`
    },
  },
  {
    id: 'days',
    header: 'Days',
    cell: ({ row }) => String(row.original.days ?? '—'),
  },
  {
    id: 'status',
    header: 'Status',
    cell: ({ row }) =>
      h(Badge, { variant: statusVariant(row.original.status), class: 'font-normal' }, () =>
        leaveStatusLabel(row.original.status),
      ),
  },
  {
    id: 'reviewed',
    header: 'Reviewed',
    cell: ({ row }) => row.original.reviewed_by ?? '—',
  },
  {
    id: 'actions',
    header: () => h('span', { class: 'sr-only' }, 'Actions'),
    cell: ({ row }) => {
      const record = row.original
      const menuActions: TableRowMenuAction[] = []
      if (record.status === 'pending') {
        menuActions.push(
          {
            label: 'Approve',
            onSelect: () => { void approve(record) },
          },
          {
            label: 'Reject',
            destructive: true,
            onSelect: () => openReject(record),
          },
        )
      }
      return h(TableRowActions, {
        canView: true,
        actions: menuActions,
        onView: () => openDetail(record),
      })
    },
  },
]

const { table, globalFilter } = useDataTable({
  data: rows,
  columns,
  pageSize: perPage,
  serverSideSearch: true,
})

const { formLoading, prepareCreate } = useFormSheetLoader(() => ({
  endpoint: moduleEndpoints.leaveRequests,
  formFields: leaveFormFields,
  setFormValues: (values) => { formResetValues.value = values },
}))

async function loadStatusCounts(baseParams: Record<string, unknown>) {
  try {
    const { status: _status, filter, ...rest } = baseParams
    const nextFilter =
      filter && typeof filter === 'object'
        ? { ...(filter as Record<string, unknown>) }
        : undefined
    if (nextFilter && 'status' in nextFilter) delete nextFilter.status
    const clean = {
      ...rest,
      ...(nextFilter && Object.keys(nextFilter).length ? { filter: nextFilter } : {}),
    }
    const [pending, approved, rejected] = await Promise.all([
      hrApi.leaveRequests.list({ ...clean, status: 'pending', page: 1, per_page: 1 }),
      hrApi.leaveRequests.list({ ...clean, status: 'approved', page: 1, per_page: 1 }),
      hrApi.leaveRequests.list({ ...clean, status: 'rejected', page: 1, per_page: 1 }),
    ])
    pendingCount.value = pending.total
    approvedCount.value = approved.total
    rejectedCount.value = rejected.total
  } catch {
    pendingCount.value = rows.value.filter((r) => r.status === 'pending').length
    approvedCount.value = rows.value.filter((r) => r.status === 'approved').length
    rejectedCount.value = rows.value.filter((r) => r.status === 'rejected').length
  }
}

async function load(page = serverPage.value) {
  loading.value = true
  error.value = null
  try {
    const params = buildParams({ page, per_page: perPage })
    if (globalFilter.value.trim()) params.search = globalFilter.value.trim()
    const list = await hrApi.leaveRequests.list(params)
    rows.value = list.data as LeaveRequestRow[]
    serverPage.value = list.current_page
    serverPageCount.value = list.last_page
    serverTotal.value = list.total
    const countParams = buildParams({})
    if (globalFilter.value.trim()) countParams.search = globalFilter.value.trim()
    await loadStatusCounts(countParams)
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load leave requests')
  } finally {
    loading.value = false
  }
}

function onServerPageChange(page: number) {
  if (page < 1 || page > serverPageCount.value) return
  serverPage.value = page
  void load(page)
}

async function openCreate() {
  sheetOpen.value = true
  await nextTick()
  await prepareCreate()
}

function openDetail(row: LeaveRequestRow) {
  detailRow.value = row
  detailOpen.value = true
}

function openReject(row: LeaveRequestRow) {
  rejectTarget.value = row
  rejectNotes.value = ''
  rejectOpen.value = true
}

async function approve(row: LeaveRequestRow) {
  if (row.id == null) return
  actionLoading.value = `approve-${row.id}`
  try {
    await hrApi.leaveRequests.approve(row.id)
    toast.success('Leave approved', 'Recorded in the audit trail.')
    await load()
    if (detailRow.value?.id === row.id) {
      detailRow.value = rows.value.find((r) => r.id === row.id) ?? detailRow.value
    }
  } catch (err) {
    toast.error('Approval failed', getErrorMessage(err))
  } finally {
    actionLoading.value = null
  }
}

async function confirmReject() {
  if (!rejectTarget.value?.id) return
  const id = rejectTarget.value.id
  if (!rejectNotes.value.trim()) {
    toast.error('Rejection reason required', 'Provide a reason for audit purposes.')
    return
  }

  actionLoading.value = `reject-${id}`
  try {
    await hrApi.leaveRequests.reject(id, { notes: rejectNotes.value.trim() })
    toast.success('Leave rejected', 'Decision recorded in the audit trail.')
    rejectOpen.value = false
    rejectTarget.value = null
    rejectNotes.value = ''
    await load()
  } catch (err) {
    toast.error('Rejection failed', getErrorMessage(err))
  } finally {
    actionLoading.value = null
  }
}

async function onSubmit(values: Record<string, unknown>) {
  saving.value = true
  try {
    await hrApi.leaveRequests.create(mapLeaveFormToPayload(values))
    toast.success('Leave request submitted', 'Awaiting approval.')
    sheetOpen.value = false
    await load()
  } catch (err) {
    formSheetRef.value?.applyServerErrors(err)
    toast.error('Submit failed', getErrorMessage(err))
  } finally {
    saving.value = false
  }
}

function openAuditTrail() {
  router.push({ path: '/hr', query: { tab: 'audit', module: 'hr' } })
}

let searchTimer: ReturnType<typeof setTimeout> | undefined

watch(globalFilter, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    serverPage.value = 1
    void load(1)
  }, 350)
})

onMounted(async () => {
  await load(1)
  if (route.query.create === '1') {
    await openCreate()
    const { create: _create, ...rest } = route.query
    router.replace({ query: rest })
  }
})

</script>

<template>
  <PageShell
    title="Leave requests"
    description="Submit, review, and audit staff leave with a full approval trail"
   max-width="wide">
    <template #actions>
      <Button v-if="canViewAudit" variant="outline" @click="openAuditTrail">
        <ScrollText class="mr-2 h-4 w-4" aria-hidden="true" />
        HR audit trail
      </Button>
      <Button @click="openCreate">
        <Plus class="mr-2 h-4 w-4" aria-hidden="true" />
        New request
      </Button>
    </template>

    <PageLoader v-if="loading && !rows.length" label="Loading leave requests" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />

    <template v-else>
      <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <KpiCard
          title="Pending approval"
          :value="String(pendingCount)"
          subtitle="Awaiting review"
          :icon="ClipboardCheck"
          :accent="pendingCount > 0 ? 'warning' : undefined"
        />
        <KpiCard
          title="Approved"
          :value="String(approvedCount)"
          subtitle="Active decisions"
          :icon="Palmtree"
          accent="success"
        />
        <KpiCard
          title="Rejected"
          :value="String(rejectedCount)"
          subtitle="Declined requests"
          :icon="Palmtree"
        />
        <KpiCard
          v-if="canViewAudit"
          title="Audit"
          value="HR log"
          subtitle="All actions recorded"
          :icon="ScrollText"
          href="/hr?tab=audit&module=hr"
        />
      </div>

      <DataTable
        :table="table"
        :columns="columns"
        :global-filter="globalFilter"
        search-placeholder="Search staff, type, or reason…"
        :server-pagination="true"
        :server-page="serverPage"
        :server-page-count="serverPageCount"
        :server-total="serverTotal"
        @update:global-filter="globalFilter = $event"
        @server-page-change="onServerPageChange"
      >
        <template #filters>
          <ListFiltersBar
            v-model="filterValues"
            :filters="leaveFilters"
            :active-count="activeFilterCount"
            @clear="clearFilters"
          />
        </template>
      </DataTable>
    </template>

    <FormSheet
      ref="formSheetRef"
      v-model:open="sheetOpen"
      title="New leave request"
      description="Submitted requests are logged for audit and require approval."
      :fields="leaveFormFields"
      :schema="leaveFormSchema"
      :reset-values="formResetValues"
      form-key="leave-create"
      :form-loading="formLoading"
      :saving="saving"
      save-label="Submit request"
      @submit="onSubmit"
    />

    <Sheet v-model:open="detailOpen">
      <SheetContent class="w-full overflow-y-auto sm:max-w-lg">
        <SheetHeader v-if="detailRow">
          <SheetTitle>
            Leave · {{ detailRow.teacher_name || 'Staff' }} · {{ leaveTypeLabel(detailRow.type, detailRow.leave_type_name) }}
          </SheetTitle>
          <SheetDescription>
            {{ formatDate(detailRow.start_date) }} – {{ formatDate(detailRow.end_date) }}
          </SheetDescription>
        </SheetHeader>

        <div v-if="detailRow" class="mt-6 space-y-6">
          <div class="flex flex-wrap gap-2">
            <Badge :variant="statusVariant(detailRow.status)">
              {{ leaveStatusLabel(detailRow.status) }}
            </Badge>
            <Badge variant="outline">{{ detailRow.days ?? 0 }} day(s)</Badge>
          </div>

          <dl class="grid gap-4 text-sm">
            <div>
              <dt class="text-muted-foreground">Staff member</dt>
              <dd class="font-medium">
                {{ detailRow.teacher_name ?? '—' }}
                <span v-if="detailRow.employee_id" class="text-muted-foreground">
                  · {{ detailRow.employee_id }}
                </span>
              </dd>
            </div>
            <div>
              <dt class="text-muted-foreground">Department</dt>
              <dd>{{ detailRow.department ?? '—' }}</dd>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <dt class="text-muted-foreground">Start</dt>
                <dd>{{ formatDate(detailRow.start_date) }}</dd>
              </div>
              <div>
                <dt class="text-muted-foreground">End</dt>
                <dd>{{ formatDate(detailRow.end_date) }}</dd>
              </div>
            </div>
            <div>
              <dt class="text-muted-foreground">Reason</dt>
              <dd>{{ detailRow.reason?.trim() || '—' }}</dd>
            </div>
          </dl>

          <section aria-labelledby="leave-audit-heading" class="rounded-lg border p-4">
            <h3 id="leave-audit-heading" class="text-sm font-semibold">Audit trail</h3>
            <dl class="mt-3 space-y-3 text-sm">
              <div>
                <dt class="text-muted-foreground">Submitted by</dt>
                <dd>{{ detailRow.requested_by_name ?? '—' }}</dd>
              </div>
              <div>
                <dt class="text-muted-foreground">Submitted on</dt>
                <dd>{{ formatDateTime(detailRow.created_at ?? detailRow.applied_date) }}</dd>
              </div>
              <div v-if="detailRow.reviewed_by">
                <dt class="text-muted-foreground">Reviewed by</dt>
                <dd>{{ detailRow.reviewed_by }}</dd>
              </div>
              <div v-if="detailRow.reviewed_at">
                <dt class="text-muted-foreground">Reviewed at</dt>
                <dd>{{ formatDateTime(detailRow.reviewed_at) }}</dd>
              </div>
              <div v-if="detailRow.review_notes">
                <dt class="text-muted-foreground">Review notes</dt>
                <dd>{{ detailRow.review_notes }}</dd>
              </div>
            </dl>
            <Button variant="link" class="mt-3 h-auto p-0" @click="openAuditTrail">
              View full HR audit log
            </Button>
          </section>

          <div v-if="detailRow.status === 'pending'" class="flex flex-wrap gap-2">
            <Button
              :disabled="!!actionLoading"
              @click="approve(detailRow)"
            >
              Approve
            </Button>
            <Button
              variant="outline"
              :disabled="!!actionLoading"
              @click="openReject(detailRow)"
            >
              Reject
            </Button>
          </div>
        </div>
      </SheetContent>
    </Sheet>

    <Dialog v-model:open="rejectOpen">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Reject leave request</DialogTitle>
          <DialogDescription>
            A reason is required and will be stored in the audit trail.
          </DialogDescription>
        </DialogHeader>
        <div class="space-y-2">
          <Label for="reject-notes">Reason</Label>
          <Textarea
            id="reject-notes"
            v-model="rejectNotes"
            rows="4"
            placeholder="Explain why this request was declined"
          />
        </div>
        <DialogFooter class="gap-2">
          <Button variant="outline" @click="rejectOpen = false">Cancel</Button>
          <Button
            variant="destructive"
            :disabled="!!actionLoading || !rejectNotes.trim()"
            @click="confirmReject"
          >
            Reject request
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </PageShell>
</template>
