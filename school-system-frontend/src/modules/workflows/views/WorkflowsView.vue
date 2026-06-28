<script setup lang="ts">
import { computed, h, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import type { ColumnDef } from '@tanstack/vue-table'
import {
  CheckCircle2,
  GitBranch,
  History,
  MoreHorizontal,
  ScrollText,
  XCircle,
} from '@lucide/vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import DataTable from '@/components/data-table/DataTable.vue'
import ListFiltersBar from '@/components/data-table/ListFiltersBar.vue'
import PageShell from '@/components/layout/PageShell.vue'
import KpiCard from '@/components/dashboard/KpiCard.vue'
import { useDataTable } from '@/components/data-table/useDataTable'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
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
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { useToast } from '@/composables/useToast'
import { useListFilters } from '@/composables/useListFilters'
import { formatDateTime } from '@/lib/format'
import { getErrorMessage } from '@/lib/api-response'
import type { ListFilterSchema } from '@/modules/shared/list-filters'
import {
  WORKFLOW_MODULE_OPTIONS,
  WORKFLOW_STATUS_OPTIONS,
  workflowModuleLabel,
  workflowProgress,
  workflowStatusLabel,
  type WorkflowRow,
} from '@/modules/workflows/workflow-utils'
import { workflowsApi } from '@/services/api.service'

const toast = useToast()
const route = useRoute()
const router = useRouter()

const activeTab = ref<'pending' | 'history'>(
  route.query.tab === 'history' || route.name === 'workflows-history' ? 'history' : 'pending',
)

const pendingRows = ref<WorkflowRow[]>([])
const historyRows = ref<WorkflowRow[]>([])
const loading = ref(true)
const error = ref<string | null>(null)
const actionLoading = ref<string | null>(null)

const detailOpen = ref(false)
const detailRow = ref<WorkflowRow | null>(null)

const rejectOpen = ref(false)
const rejectTarget = ref<WorkflowRow | null>(null)
const rejectComments = ref('')

const approveOpen = ref(false)
const approveTarget = ref<WorkflowRow | null>(null)
const approveComments = ref('')

const historyFilters: ListFilterSchema[] = [
  {
    key: 'status',
    label: 'Status',
    type: 'select',
    placeholder: 'Any status',
    options: [...WORKFLOW_STATUS_OPTIONS],
  },
  {
    key: 'module',
    label: 'Module',
    type: 'select',
    placeholder: 'Any module',
    options: [...WORKFLOW_MODULE_OPTIONS],
  },
]

const {
  values: historyFilterValues,
  activeCount: historyFilterCount,
  buildParams: buildHistoryParams,
  clearAll: clearHistoryFilters,
} = useListFilters(computed(() => historyFilters))

const tableRows = computed(() =>
  activeTab.value === 'pending' ? pendingRows.value : historyRows.value,
)

function statusVariant(status?: string): 'default' | 'secondary' | 'destructive' | 'outline' {
  if (status === 'approved') return 'default'
  if (status === 'rejected') return 'destructive'
  if (status === 'pending') return 'secondary'
  return 'outline'
}

const columns: ColumnDef<WorkflowRow>[] = [
  {
    id: 'workflow',
    header: 'Workflow',
    cell: ({ row }) => row.original.workflow_name ?? row.original.workflow_code ?? '—',
  },
  {
    id: 'subject',
    header: 'Subject',
    cell: ({ row }) => row.original.subject_label ?? '—',
  },
  {
    id: 'module',
    header: 'Module',
    cell: ({ row }) => workflowModuleLabel(row.original.module),
  },
  {
    id: 'step',
    header: 'Step',
    cell: ({ row }) => workflowProgress(row.original),
  },
  {
    id: 'initiator',
    header: 'Requested by',
    cell: ({ row }) => row.original.initiated_by_name ?? row.original.initiator?.name ?? '—',
  },
  {
    id: 'status',
    header: 'Status',
    cell: ({ row }) =>
      h(Badge, { variant: statusVariant(row.original.status), class: 'font-normal' }, () =>
        workflowStatusLabel(row.original.status),
      ),
  },
  {
    id: 'actions',
    header: '',
    cell: ({ row }) =>
      h(
        DropdownMenu,
        {},
        {
          default: () => [
            h(
              DropdownMenuTrigger,
              { asChild: true },
              {
                default: () =>
                  h(Button, { variant: 'ghost', size: 'icon', class: 'h-8 w-8' }, () =>
                    h(MoreHorizontal, { class: 'h-4 w-4', 'aria-hidden': 'true' }),
                  ),
              },
            ),
            h(DropdownMenuContent, { align: 'end' }, () => [
              h(
                DropdownMenuItem,
                { onClick: () => openDetail(row.original) },
                () => 'View details',
              ),
              activeTab.value === 'pending' && row.original.status === 'pending'
                ? h(DropdownMenuItem, { onClick: () => openApprove(row.original) }, () => 'Approve step')
                : null,
              activeTab.value === 'pending' && row.original.status === 'pending'
                ? h(
                    DropdownMenuItem,
                    { class: 'text-destructive', onClick: () => openReject(row.original) },
                    () => 'Reject',
                  )
                : null,
            ]),
          ],
        },
      ),
  },
]

const { table, globalFilter } = useDataTable({
  data: tableRows,
  columns,
  pageSize: 15,
})

const pendingCount = computed(() => pendingRows.value.length)
const approvedCount = computed(() =>
  historyRows.value.filter((r) => r.status === 'approved').length,
)
const rejectedCount = computed(() =>
  historyRows.value.filter((r) => r.status === 'rejected').length,
)

async function loadPending() {
  pendingRows.value = await workflowsApi.pending() as WorkflowRow[]
}

async function loadHistory() {
  const params = buildHistoryParams({ limit: 200 })
  if (globalFilter.value.trim()) params.search = globalFilter.value.trim()
  historyRows.value = await workflowsApi.history(params) as WorkflowRow[]
}

async function load() {
  loading.value = true
  error.value = null
  try {
    await Promise.all([loadPending(), loadHistory()])
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load workflows')
  } finally {
    loading.value = false
  }
}

async function reloadActiveTab() {
  try {
    if (activeTab.value === 'pending') await loadPending()
    else await loadHistory()
  } catch (err) {
    toast.error('Refresh failed', getErrorMessage(err))
  }
}

function openDetail(row: WorkflowRow) {
  detailRow.value = row
  detailOpen.value = true
}

function openApprove(row: WorkflowRow) {
  approveTarget.value = row
  approveComments.value = ''
  approveOpen.value = true
}

function openReject(row: WorkflowRow) {
  rejectTarget.value = row
  rejectComments.value = ''
  rejectOpen.value = true
}

async function confirmApprove() {
  if (!approveTarget.value?.id) return
  const id = approveTarget.value.id
  actionLoading.value = `approve-${id}`
  try {
    await workflowsApi.approve(id, {
      comments: approveComments.value.trim() || undefined,
    })
    toast.success('Workflow approved', 'Decision recorded in the audit trail.')
    approveOpen.value = false
    approveTarget.value = null
    await Promise.all([loadPending(), loadHistory()])
    if (detailRow.value?.id === id) detailOpen.value = false
  } catch (err) {
    toast.error('Approval failed', getErrorMessage(err))
  } finally {
    actionLoading.value = null
  }
}

async function confirmReject() {
  if (!rejectTarget.value?.id) return
  if (!rejectComments.value.trim()) {
    toast.error('Comments required', 'Explain why this workflow was rejected.')
    return
  }

  const id = rejectTarget.value.id
  actionLoading.value = `reject-${id}`
  try {
    await workflowsApi.reject(id, { comments: rejectComments.value.trim() })
    toast.success('Workflow rejected', 'Decision recorded in the audit trail.')
    rejectOpen.value = false
    rejectTarget.value = null
    rejectComments.value = ''
    await Promise.all([loadPending(), loadHistory()])
    if (detailRow.value?.id === id) detailOpen.value = false
  } catch (err) {
    toast.error('Rejection failed', getErrorMessage(err))
  } finally {
    actionLoading.value = null
  }
}

function openAuditTrail() {
  router.push({ path: '/compliance/audit', query: { module: 'workflow' } })
}

watch(activeTab, (tab) => {
  router.replace({
    query: {
      ...route.query,
      tab: tab === 'history' ? 'history' : undefined,
    },
  })
  if (tab === 'history') void loadHistory()
})

watch(historyFilterValues, () => {
  if (activeTab.value === 'history') void loadHistory()
}, { deep: true })

onMounted(load)
</script>

<template>
  <PageShell
    title="Approval workflows"
    description="Review multi-step approvals for procurement, finance, HR, and examinations"
  >
    <template #actions>
      <Button variant="outline" @click="openAuditTrail">
        <ScrollText class="mr-2 h-4 w-4" aria-hidden="true" />
        Workflow audit
      </Button>
      <Button variant="outline" :disabled="loading" @click="reloadActiveTab">
        Refresh
      </Button>
    </template>

    <PageLoader v-if="loading && !pendingRows.length && !historyRows.length" label="Loading workflows" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />

    <template v-else>
      <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <KpiCard
          title="Awaiting you"
          :value="String(pendingCount)"
          subtitle="Pending your approval step"
          :icon="GitBranch"
          :accent="pendingCount > 0 ? 'warning' : undefined"
        />
        <KpiCard
          title="Approved"
          :value="String(approvedCount)"
          subtitle="Completed approvals"
          :icon="CheckCircle2"
          accent="success"
        />
        <KpiCard
          title="Rejected"
          :value="String(rejectedCount)"
          subtitle="Declined requests"
          :icon="XCircle"
        />
        <KpiCard
          title="How it works"
          value="Multi-step"
          subtitle="Procurement, fees, results & more"
          :icon="History"
        />
      </div>

      <Tabs v-model="activeTab">
        <TabsList aria-label="Workflow views">
          <TabsTrigger value="pending">Pending ({{ pendingCount }})</TabsTrigger>
          <TabsTrigger value="history">History</TabsTrigger>
        </TabsList>

        <TabsContent value="pending" class="mt-4">
          <p v-if="!pendingRows.length" class="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground">
            No workflows are waiting for your approval. New requests appear here when staff submit
            procurement requisitions, fee waivers, or other configured flows.
          </p>
          <DataTable
            v-else
            :table="table"
            :columns="columns"
            :global-filter="globalFilter"
            search-placeholder="Search pending workflows…"
            @update:global-filter="globalFilter = $event"
          />
        </TabsContent>

        <TabsContent value="history" class="mt-4">
          <DataTable
            :table="table"
            :columns="columns"
            :global-filter="globalFilter"
            search-placeholder="Search workflow history…"
            @update:global-filter="globalFilter = $event"
          >
            <template #filters>
              <ListFiltersBar
                v-model="historyFilterValues"
                :filters="historyFilters"
                :active-count="historyFilterCount"
                @clear="clearHistoryFilters"
              />
            </template>
          </DataTable>
        </TabsContent>
      </Tabs>
    </template>

    <Sheet v-model:open="detailOpen">
      <SheetContent class="w-full overflow-y-auto sm:max-w-lg">
        <SheetHeader v-if="detailRow">
          <SheetTitle>{{ detailRow.workflow_name ?? 'Workflow' }}</SheetTitle>
          <SheetDescription>{{ detailRow.subject_label }}</SheetDescription>
        </SheetHeader>

        <div v-if="detailRow" class="mt-6 space-y-6">
          <div class="flex flex-wrap gap-2">
            <Badge :variant="statusVariant(detailRow.status)">
              {{ workflowStatusLabel(detailRow.status) }}
            </Badge>
            <Badge variant="outline">{{ workflowModuleLabel(detailRow.module) }}</Badge>
          </div>

          <dl class="grid gap-3 text-sm">
            <div>
              <dt class="text-muted-foreground">Current step</dt>
              <dd class="font-medium">{{ workflowProgress(detailRow) }}</dd>
            </div>
            <div>
              <dt class="text-muted-foreground">Requested by</dt>
              <dd>{{ detailRow.initiated_by_name ?? '—' }}</dd>
            </div>
            <div>
              <dt class="text-muted-foreground">Submitted</dt>
              <dd>{{ formatDateTime(detailRow.created_at) }}</dd>
            </div>
            <div v-if="detailRow.completed_at">
              <dt class="text-muted-foreground">Completed</dt>
              <dd>{{ formatDateTime(detailRow.completed_at) }}</dd>
            </div>
          </dl>

          <section v-if="detailRow.definition?.steps?.length" aria-labelledby="workflow-steps-heading">
            <h3 id="workflow-steps-heading" class="text-sm font-semibold">Approval steps</h3>
            <ol class="mt-3 space-y-2 text-sm" role="list">
              <li
                v-for="step in detailRow.definition.steps"
                :key="step.step_order"
                class="flex items-center justify-between rounded-md border px-3 py-2"
                :class="step.step_order === detailRow.current_step_order && detailRow.status === 'pending'
                  ? 'border-primary bg-primary/5'
                  : ''"
              >
                <span>{{ step.step_order }}. {{ step.name }}</span>
                <span class="text-muted-foreground">{{ step.approver_role ?? 'Assigned user' }}</span>
              </li>
            </ol>
          </section>

          <section v-if="detailRow.approvals?.length" aria-labelledby="workflow-approvals-heading">
            <h3 id="workflow-approvals-heading" class="text-sm font-semibold">Decision log</h3>
            <ul class="mt-3 space-y-3 text-sm" role="list">
              <li
                v-for="approval in detailRow.approvals"
                :key="approval.id ?? `${approval.step_order}-${approval.action}`"
                class="rounded-md border p-3"
              >
                <p class="font-medium capitalize">
                  Step {{ approval.step_order }} · {{ approval.action }}
                  <span v-if="approval.approver_name" class="font-normal text-muted-foreground">
                    — {{ approval.approver_name }}
                  </span>
                </p>
                <p v-if="approval.comments" class="mt-1 text-muted-foreground">{{ approval.comments }}</p>
                <p v-if="approval.acted_at" class="mt-1 text-xs text-muted-foreground">
                  {{ formatDateTime(approval.acted_at) }}
                </p>
              </li>
            </ul>
          </section>

          <div v-if="detailRow.status === 'pending' && activeTab === 'pending'" class="flex flex-wrap gap-2">
            <Button :disabled="!!actionLoading" @click="openApprove(detailRow)">Approve step</Button>
            <Button variant="outline" :disabled="!!actionLoading" @click="openReject(detailRow)">
              Reject
            </Button>
          </div>
        </div>
      </SheetContent>
    </Sheet>

    <Dialog v-model:open="approveOpen">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Approve workflow step</DialogTitle>
          <DialogDescription>
            {{ approveTarget?.workflow_name }} — {{ approveTarget?.subject_label }}
          </DialogDescription>
        </DialogHeader>
        <div class="space-y-2">
          <Label for="approve-comments">Comments (optional)</Label>
          <Textarea
            id="approve-comments"
            v-model="approveComments"
            rows="3"
            placeholder="Optional note for the audit trail"
          />
        </div>
        <DialogFooter class="gap-2">
          <Button variant="outline" @click="approveOpen = false">Cancel</Button>
          <Button :disabled="!!actionLoading" @click="confirmApprove">Approve</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <Dialog v-model:open="rejectOpen">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Reject workflow</DialogTitle>
          <DialogDescription>
            Comments are required and stored in the audit trail.
          </DialogDescription>
        </DialogHeader>
        <div class="space-y-2">
          <Label for="reject-comments">Reason</Label>
          <Textarea
            id="reject-comments"
            v-model="rejectComments"
            rows="4"
            placeholder="Explain why this request is rejected"
          />
        </div>
        <DialogFooter class="gap-2">
          <Button variant="outline" @click="rejectOpen = false">Cancel</Button>
          <Button
            variant="destructive"
            :disabled="!!actionLoading || !rejectComments.trim()"
            @click="confirmReject"
          >
            Reject workflow
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </PageShell>
</template>
