<script setup lang="ts">
import { ref, onMounted, computed, h, nextTick, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { z } from 'zod'
import type { ColumnDef } from '@tanstack/vue-table'
import { Pencil, Plus, Trash2 } from '@lucide/vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import DataTable from '@/components/data-table/DataTable.vue'
import ListFiltersBar from '@/components/data-table/ListFiltersBar.vue'
import FormSheet from '@/components/forms/FormSheet.vue'
import PageShell from '@/components/layout/PageShell.vue'
import type { FormFieldSchema } from '@/components/forms/useFormBuilder'
import { useDataTable } from '@/components/data-table/useDataTable'
import { Button } from '@/components/ui/button'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { useToast } from '@/composables/useToast'
import { useSchoolProfile } from '@/composables/useSchoolProfile'
import { getErrorMessage } from '@/lib/api-response'
import type { RowActionConfig } from '@/modules/shared/registry-actions'
import { mapFormToPayload } from '@/modules/shared/crud-mappers'
import { clearRelationCache } from '@/lib/relation-options'
import { useFormSheetLoader } from '@/composables/useFormSheetLoader'
import { useListFilters } from '@/composables/useListFilters'
import { getListMeta } from '@/modules/shared/list-filters'
import { applyClientFilters } from '@/lib/list-filter-utils'
import {
  createRecord,
  deleteRecord,
  fetchList,
  fetchOne,
  fetchPaginatedList,
  patchRecord,
  postRecord,
  updateRecord,
} from '@/services/dashboard.service'
import PaymentReceiptSheet from '@/modules/finance/components/PaymentReceiptSheet.vue'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { teacherCreateDefaults } from '@/modules/teachers/teacher-form'

const props = defineProps<{
  title: string
  description?: string
  endpoint: string
  createEndpoint?: string
  columns: ColumnDef<Record<string, unknown>, unknown>[]
  formFields?: FormFieldSchema[]
  formSchema?: z.ZodTypeAny
  canCreate?: boolean
  canEdit?: boolean
  canDelete?: boolean
  idKey?: string
  rowActions?: RowActionConfig[]
  toolbarActions?: RowActionConfig[]
  listKey?: string
  staged?: boolean
}>()

const toast = useToast()
const route = useRoute()
const router = useRouter()
const { currency: schoolCurrency, loadSchool } = useSchoolProfile()
const rows = ref<Record<string, unknown>[]>([])
const loading = ref(true)
const error = ref<string | null>(null)
const sheetOpen = ref(false)
const saving = ref(false)
const actionLoading = ref<string | null>(null)
const editingRow = ref<Record<string, unknown> | null>(null)
const deleteTarget = ref<Record<string, unknown> | null>(null)
const serverPage = ref(1)
const serverPageCount = ref(1)
const serverTotal = ref(0)
const receiptOpen = ref(false)
const receiptLoading = ref(false)
const receiptData = ref<Record<string, unknown> | null>(null)
const reverseTarget = ref<{ action: RowActionConfig; row: Record<string, unknown> } | null>(null)
const reverseReason = ref('')

const idKey = computed(() => props.idKey ?? 'id')
const subtitle = computed(() => props.description ?? `Manage records from ${props.endpoint}`)
const hasForm = computed(() => Boolean(props.formFields?.length && props.formSchema))

const actionPrompt = ref<{
  action: RowActionConfig
  row: Record<string, unknown>
  isToolbar: boolean
} | null>(null)
const actionPromptOpen = computed({
  get: () => actionPrompt.value != null,
  set: (open: boolean) => {
    if (!open) actionPrompt.value = null
  },
})
const actionPromptResetValues = ref<Record<string, unknown> | undefined>()
const actionPromptSaving = ref(false)
const actionPromptForm = computed(() => actionPrompt.value?.action.promptForm ?? null)
const actionPromptKey = computed(() => {
  if (!actionPrompt.value) return 'closed'
  const id = actionPrompt.value.row[idKey.value]
  return `${actionPrompt.value.action.label}-${String(id ?? 'toolbar')}`
})

const displayColumns = computed<ColumnDef<Record<string, unknown>, unknown>[]>(() => {
  const cols = [...props.columns]
  const hasRowActions =
    props.canEdit || props.canDelete || (props.rowActions?.length ?? 0) > 0

  if (!hasRowActions) return cols

  cols.push({
    id: 'row-actions',
    header: () => h('span', { class: 'sr-only' }, 'Actions'),
    cell: ({ row }) => {
      const record = row.original
      const id = record[idKey.value]
      if (id == null) return null

      const buttons: ReturnType<typeof h>[] = []

      if (props.canEdit && hasForm.value) {
        const canEditRow = props.listKey !== 'finance-payroll'
          || String(record.status ?? '').toLowerCase() === 'pending'
        if (canEditRow) {
          buttons.push(
            h(Button, {
              size: 'sm',
              variant: 'ghost',
              'aria-label': 'Edit record',
              onClick: () => openEdit(record),
            }, () => h(Pencil, { class: 'h-4 w-4', 'aria-hidden': 'true' })),
          )
        }
      }

      for (const action of props.rowActions ?? []) {
        if (action.when && !action.when(record)) continue
        buttons.push(
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
      }

      if (props.canDelete) {
        buttons.push(
          h(Button, {
            size: 'sm',
            variant: 'ghost',
            'aria-label': 'Delete record',
            onClick: () => { deleteTarget.value = record },
          }, () => h(Trash2, { class: 'h-4 w-4 text-destructive', 'aria-hidden': 'true' })),
        )
      }

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
const serverPagination = computed(() => listMeta.value?.serverPagination ?? false)
const perPage = computed(() => listMeta.value?.perPage ?? 25)

const {
  values: filterValues,
  activeCount: activeFilterCount,
  buildParams: buildFilterParams,
  clearAll: clearFilters,
} = useListFilters(listFilters, {
  onChange: () => {
    if (filterMode.value === 'server') {
      serverPage.value = 1
      void load(1)
    }
  },
})

const tableRows = computed(() => {
  if (filterMode.value === 'server') return rows.value
  return applyClientFilters(rows.value, listFilters.value, filterValues.value)
})

const { table, globalFilter } = useDataTable({
  data: tableRows,
  columns: displayColumns,
  pageSize: serverPagination.value ? perPage.value : 10,
  serverSideSearch: serverSearch.value,
})

const defaultSchema = z.object({})
const schema = computed(() => props.formSchema ?? defaultSchema)
const formResetValues = ref<Record<string, unknown> | undefined>()
const formSheetRef = ref<{ applyServerErrors: (error: unknown, fieldAliases?: Record<string, string>) => void } | null>(null)

const { formLoading, prepareCreate, prepareEdit } = useFormSheetLoader(() => ({
  endpoint: props.endpoint,
  formFields: props.formFields,
  listKey: props.listKey,
  setFormValues: (values) => { formResetValues.value = values },
  createDefaults: () => {
    if (props.listKey === 'students') return { guardianMode: 'new' }
    if (props.listKey === 'teachers') return teacherCreateDefaults(schoolCurrency.value)
    return {}
  },
  idKey: idKey.value,
}))

let searchTimer: ReturnType<typeof setTimeout> | undefined

const formInstanceKey = computed(() =>
  editingRow.value ? String(editingRow.value[idKey.value]) : 'create',
)

async function load(page = serverPage.value) {
  loading.value = true
  error.value = null
  try {
    const params = buildFilterParams(
      serverPagination.value
        ? { page, per_page: perPage.value }
        : { limit: 200 },
    )
    if (serverSearch.value && globalFilter.value.trim()) {
      params.search = globalFilter.value.trim()
      params.filter = {
        ...(params.filter ?? {}),
        search: globalFilter.value.trim(),
      }
    }
    if (props.listKey === 'students') {
      params.include = 'guardians,classModel,gradeLevel'
      params.sort = params.sort ?? '-created_at'
    }
    if (props.listKey === 'teachers') {
      params.sort = params.sort ?? 'name'
    }
    if (props.listKey === 'finance-payments') {
      params.include = 'student,invoice'
      params.sort = params.sort ?? '-date'
    }
    if (props.listKey === 'finance-transactions') {
      const payrollId = route.query.payroll_id
      if (typeof payrollId === 'string' && payrollId !== '') {
        params.payroll_id = payrollId
      }
    }
    if (props.listKey === 'finance-invoices') {
      params.include = 'student'
      params.sort = params.sort ?? '-created_at'
    }

    if (serverPagination.value) {
      const paginator = await fetchPaginatedList<Record<string, unknown>>(props.endpoint, params)
      rows.value = paginator.data
      serverPage.value = paginator.current_page
      serverPageCount.value = paginator.last_page
      serverTotal.value = paginator.total
    } else {
      rows.value = await fetchList<Record<string, unknown>>(props.endpoint, params)
    }
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load records'
  } finally {
    loading.value = false
  }
}

function onServerPageChange(page: number) {
  if (page < 1 || page > serverPageCount.value) return
  serverPage.value = page
  void load(page)
}

watch(globalFilter, () => {
  if (!serverSearch.value) return
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    serverPage.value = 1
    void load(1)
  }, 350)
})

async function openCreate() {
  editingRow.value = null
  if (props.listKey === 'students') {
    formResetValues.value = { guardianMode: 'new' }
  } else if (props.listKey === 'academics-terms') {
    formResetValues.value = { is_active: true, is_current: false, order: 1 }
  } else if (props.listKey === 'ops-inventory') {
    formResetValues.value = {
      type: 'uniform',
      stock_quantity: 0,
      reorder_level: 5,
      unit_price: 0,
      billing_mode: 'direct_sale',
      is_active: true,
    }
  } else if (props.listKey === 'ops-transport' || props.listKey === 'ops-transport-drivers' || props.listKey === 'ops-transport-routes') {
    formResetValues.value = { status: 'active' }
  } else if (props.listKey === 'ops-hostels' || props.listKey === 'ops-assets') {
    formResetValues.value = { status: 'active' }
  } else if (props.listKey === 'ops-events') {
    formResetValues.value = { status: 'scheduled' }
  } else if (props.listKey === 'compliance') {
    formResetValues.value = { status: 'active' }
  } else if (props.listKey === 'compliance-incidents') {
    formResetValues.value = { severity: 'low', status: 'open' }
  } else if (props.listKey === 'compliance-consent') {
    formResetValues.value = { requires_signature: false }
  } else if (
    props.listKey === 'academics-streams'
    || props.listKey === 'academics-houses'
    || props.listKey === 'academics-subjects'
    || props.listKey === 'academics-departments'
    || props.listKey === 'academics-grade-levels'
    || props.listKey === 'academics-holiday-programs'
    || props.listKey === 'ops-school-trips'
    || props.listKey === 'academics-teacher-assignments'
    || props.listKey === 'settings-custom-fields'
  ) {
    formResetValues.value = props.listKey === 'ops-school-trips'
      ? { is_active: true, open_for_registration: true, fee_amount: 0 }
      : { is_active: true }
  } else if (props.listKey === 'ops-procurement') {
    formResetValues.value = {
      spend_type: 'procurement',
      item_quantity: 1,
      submit: true,
    }
  } else {
    formResetValues.value = {}
  }
  sheetOpen.value = true
  await nextTick()
  void prepareCreate()
}

async function openEdit(row: Record<string, unknown>) {
  if (props.listKey === 'finance-payroll' && String(row.status ?? '').toLowerCase() !== 'pending') {
    toast.error('Cannot edit', 'Only pending payslips can be adjusted before payment.')
    return
  }
  editingRow.value = row
  sheetOpen.value = true
  await nextTick()
  const record = await prepareEdit(row)
  editingRow.value = record
}

function openActionPrompt(action: RowActionConfig, row: Record<string, unknown>, isToolbar = false) {
  const form = action.promptForm
  if (!form) return
  const defaults =
    typeof form.defaults === 'function' ? form.defaults(row) : (form.defaults ?? {})
  actionPromptResetValues.value = { ...defaults }
  actionPrompt.value = { action, row, isToolbar }
}

async function runAction(action: RowActionConfig, row: Record<string, unknown>) {
  const id = row[idKey.value]
  if (id == null) return

  if (action.openReceipt) {
    receiptOpen.value = true
    receiptLoading.value = true
    receiptData.value = null
    try {
      receiptData.value = await fetchOne<Record<string, unknown>>(action.path(id as string | number))
    } catch (err) {
      toast.error('Receipt unavailable', getErrorMessage(err))
      receiptOpen.value = false
    } finally {
      receiptLoading.value = false
    }
    return
  }

  if (action.confirmReason) {
    reverseTarget.value = { action, row }
    reverseReason.value = ''
    return
  }

  if (action.promptForm) {
    openActionPrompt(action, row, false)
    return
  }

  await executeAction(action, row)
}

function sanitizeActionBody(values: Record<string, unknown>): Record<string, unknown> {
  const body: Record<string, unknown> = {}
  for (const [key, value] of Object.entries(values)) {
    if (value === '' || value === undefined || value === null) continue
    body[key] = value
  }
  return body
}

async function executeAction(action: RowActionConfig, row: Record<string, unknown>, extraBody?: Record<string, unknown>): Promise<boolean> {
  const id = row[idKey.value]
  const pathId = (id as string | number | undefined) ?? 0

  const resolvedBody = typeof action.body === 'function' ? action.body(row) : action.body ?? {}
  if (resolvedBody == null) return false

  const key = `${action.label}-${String(id ?? 'toolbar')}`
  actionLoading.value = key
  try {
    const body = sanitizeActionBody({
      ...resolvedBody,
      ...extraBody,
    })
    const path = action.path(pathId)

    let result: unknown
    if (action.method === 'post') result = await postRecord(path, body)
    else if (action.method === 'put') result = await updateRecord(path, body)
    else if (action.method === 'patch') result = await patchRecord(path, body)
    else await deleteRecord(path)

    const successTitle = action.successMessage ?? `${action.label} completed`
    const generateMeta =
      result && typeof result === 'object'
        ? (result as { created?: number; errors?: string[] })
        : null
    if (generateMeta && Array.isArray(generateMeta.errors) && generateMeta.errors.length > 0) {
      toast.info(
        successTitle,
        `${generateMeta.created ?? 0} updated. ${generateMeta.errors.slice(0, 3).join(' ')}`,
      )
    } else {
      toast.success(successTitle)
    }
    await load()
    return true
  } catch (err) {
    toast.error(`${action.label} failed`, getErrorMessage(err))
    return false
  } finally {
    actionLoading.value = null
  }
}

async function confirmReverse() {
  if (!reverseTarget.value) return
  const { action, row } = reverseTarget.value
  reverseTarget.value = null
  const reason = reverseReason.value.trim()
  await executeAction(action, row, reason ? { notes: reason, reason } : {})
  reverseReason.value = ''
}

async function runToolbarAction(action: RowActionConfig) {
  if (action.promptForm) {
    openActionPrompt(action, {}, true)
    return
  }

  actionLoading.value = action.label
  try {
    const body = typeof action.body === 'function' ? action.body({}) : action.body
    if (body == null && action.method !== 'delete') {
      actionLoading.value = null
      return
    }
    const path = action.path(0)
    const payload = body ?? {}
    if (action.method === 'post') await postRecord(path, payload)
    else if (action.method === 'put') await updateRecord(path, payload)
    else if (action.method === 'patch') await patchRecord(path, payload)
    toast.success(action.successMessage ?? `${action.label} completed`)
    await load()
  } catch (err) {
    toast.error(`${action.label} failed`, getErrorMessage(err))
  } finally {
    actionLoading.value = null
  }
}

async function onActionPromptSubmit(values: Record<string, unknown>) {
  if (!actionPrompt.value) return
  const { action, row } = actionPrompt.value
  actionPromptSaving.value = true
  try {
    const ok = await executeAction(action, row, sanitizeActionBody(values))
    if (ok) actionPrompt.value = null
  } finally {
    actionPromptSaving.value = false
  }
}

async function confirmDelete() {
  if (!deleteTarget.value) return
  const id = deleteTarget.value[idKey.value]
  try {
    await deleteRecord(`${props.endpoint}/${id}`)
    toast.success('Record deleted')
    deleteTarget.value = null
    await load()
  } catch (err) {
    toast.error('Delete failed', getErrorMessage(err))
  }
}

async function onSubmit(values: Record<string, unknown>) {
  if (!hasForm.value) return
  saving.value = true
  try {
    const payload = mapFormToPayload(props.listKey, values, props.formFields ?? [])
    if (editingRow.value) {
      const id = editingRow.value[idKey.value]
      await updateRecord(`${props.endpoint}/${id}`, payload)
      toast.success('Record updated')
    } else {
      await createRecord(props.createEndpoint ?? props.endpoint, payload)
      toast.success('Record created')
    }
    sheetOpen.value = false
    clearRelationCache()
    await load()
  } catch (err) {
    const serverFieldAliases = props.listKey === 'students'
      ? { class: 'class_id', dateOfBirth: 'dateOfBirth' }
      : undefined
    formSheetRef.value?.applyServerErrors(err, serverFieldAliases)
    toast.error('Save failed', getErrorMessage(err))
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  // Seed list filters from URL (e.g. /finance/fees?fee_category_id=3)
  for (const filter of listFilters.value) {
    const raw = route.query[filter.key]
    if (typeof raw === 'string' && raw !== '') {
      filterValues.value = { ...filterValues.value, [filter.key]: raw }
    }
  }

  if (props.listKey === 'teachers') {
    void loadSchool()
  }

  await load()
  if (route.query.create === '1' && props.canCreate && hasForm.value) {
    void openCreate()
    const { create: _create, ...rest } = route.query
    router.replace({ query: rest })
  }
})

defineExpose({ load, openEdit })
</script>

<template>
  <PageShell :title="title" :description="subtitle">
    <template #actions>
      <Button
        v-for="action in toolbarActions"
        :key="action.label"
        variant="outline"
        :disabled="!!actionLoading"
        @click="runToolbarAction(action)"
      >
        {{ action.label }}
      </Button>
      <Button v-if="canCreate && hasForm" @click="openCreate">
        <Plus class="mr-2 h-4 w-4" aria-hidden="true" />
        {{ listKey === 'ops-visitors' ? 'Check in' : 'Add new' }}
      </Button>
    </template>

    <PageLoader v-if="loading" :label="`Loading ${title}`" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />
    <DataTable
      v-else
      :table="table"
      :columns="displayColumns"
      :global-filter="globalFilter"
      :search-placeholder="`Search ${title.toLowerCase()}…`"
      :server-pagination="serverPagination"
      :server-page="serverPage"
      :server-page-count="serverPageCount"
      :server-total="serverTotal"
      @update:global-filter="globalFilter = $event"
      @server-page-change="onServerPageChange"
    >
      <template v-if="listFilters.length" #filters>
        <ListFiltersBar
          v-model="filterValues"
          :filters="listFilters"
          :active-count="activeFilterCount"
          @clear="clearFilters"
        />
      </template>
      <template #toolbar>
        <slot name="toolbar" />
      </template>
    </DataTable>

    <FormSheet
      v-if="formFields && formSchema"
      ref="formSheetRef"
      v-model:open="sheetOpen"
      :title="`${editingRow ? 'Edit' : 'Add'} ${title.replace(/s$/, '') || title}`"
      :fields="formFields"
      :schema="schema"
      :reset-values="formResetValues"
      :form-key="formInstanceKey"
      :form-loading="formLoading"
      :saving="saving"
      :save-label="editingRow ? 'Save changes' : 'Save'"
      :staged="staged"
      @submit="onSubmit"
    />

    <FormSheet
      v-if="actionPromptForm"
      v-model:open="actionPromptOpen"
      :title="actionPromptForm.title"
      :description="actionPromptForm.description"
      :fields="actionPromptForm.fields"
      :schema="actionPromptForm.schema"
      :reset-values="actionPromptResetValues"
      :form-key="actionPromptKey"
      :saving="actionPromptSaving"
      :save-label="actionPromptForm.saveLabel ?? 'Confirm'"
      :size="actionPromptForm.size"
      @submit="onActionPromptSubmit"
    />

    <Dialog :open="!!deleteTarget" @update:open="(v) => !v && (deleteTarget = null)">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Delete record?</DialogTitle>
          <DialogDescription>This action cannot be undone.</DialogDescription>
        </DialogHeader>
        <DialogFooter class="gap-2">
          <Button variant="outline" @click="deleteTarget = null">Cancel</Button>
          <Button variant="destructive" @click="confirmDelete">Delete</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <Dialog :open="!!reverseTarget" @update:open="(v) => !v && (reverseTarget = null)">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{{ reverseTarget?.action.label ?? 'Confirm' }}?</DialogTitle>
          <DialogDescription>
            Provide a reason for this action. This may be recorded in the audit trail.
          </DialogDescription>
        </DialogHeader>
        <div class="space-y-2">
          <Label for="action-reason">Reason</Label>
          <Input
            id="action-reason"
            v-model="reverseReason"
            placeholder="Optional note"
          />
        </div>
        <DialogFooter class="gap-2">
          <Button variant="outline" @click="reverseTarget = null">Cancel</Button>
          <Button variant="destructive" @click="confirmReverse">
            {{ reverseTarget?.action.label ?? 'Confirm' }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <PaymentReceiptSheet
      v-model:open="receiptOpen"
      :receipt="receiptData"
      :loading="receiptLoading"
    />
  </PageShell>
</template>
