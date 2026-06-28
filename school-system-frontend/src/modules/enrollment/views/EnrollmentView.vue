<script setup lang="ts">
import { h, nextTick, onMounted, ref } from 'vue'
import type { ColumnDef } from '@tanstack/vue-table'
import { Plus } from '@lucide/vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import DataTable from '@/components/data-table/DataTable.vue'
import FormSheet from '@/components/forms/FormSheet.vue'
import { useDataTable } from '@/components/data-table/useDataTable'
import PageShell from '@/components/layout/PageShell.vue'
import { Button } from '@/components/ui/button'
import { useToast } from '@/composables/useToast'
import { useFormSheetLoader } from '@/composables/useFormSheetLoader'
import { getErrorMessage } from '@/lib/api-response'
import { enrollmentApi } from '@/services/api.service'
import { moduleEndpoints } from '@/services'
import { dateColumn, statusColumn, textColumn } from '@/modules/shared/columns'
import { enrollmentFormFields, enrollmentFormSchema } from '@/modules/enrollment/enrollment-form'

interface Application {
  id: number
  first_name: string
  surname: string
  grade_applying_for?: string
  academic_year?: string
  status: string
  created_at?: string
}

const toast = useToast()
const rows = ref<Application[]>([])
const loading = ref(true)
const error = ref<string | null>(null)
const sheetOpen = ref(false)
const saving = ref(false)
const formResetValues = ref<Record<string, unknown> | undefined>()
const formSheetRef = ref<{ applyServerErrors: (error: unknown) => void } | null>(null)

const { formLoading, prepareCreate } = useFormSheetLoader(() => ({
  endpoint: moduleEndpoints.enrollment,
  formFields: enrollmentFormFields,
  setFormValues: (values) => { formResetValues.value = values },
}))

const columns: ColumnDef<Record<string, unknown>>[] = [
  textColumn('Applicant', 'first_name'),
  textColumn('Surname', 'surname'),
  textColumn('Grade', 'grade_applying_for'),
  textColumn('Year', 'academic_year'),
  statusColumn(),
  dateColumn('Applied', 'created_at'),
  {
    id: 'actions',
    header: () => h('span', { class: 'sr-only' }, 'Actions'),
    cell: ({ row }) => {
      const app = row.original as unknown as Application
      if (app.status !== 'pending') return null
      return h('div', { class: 'flex gap-1' }, [
        h(Button, { size: 'sm', variant: 'default', onClick: () => approve(app.id) }, () => 'Approve'),
        h(Button, { size: 'sm', variant: 'outline', onClick: () => reject(app.id) }, () => 'Reject'),
      ])
    },
  },
]

const { table, globalFilter } = useDataTable({ data: rows as never, columns })

async function load() {
  loading.value = true
  error.value = null
  try {
    rows.value = await enrollmentApi.list() as Application[]
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load applications'
  } finally {
    loading.value = false
  }
}

async function approve(id: number) {
  try {
    await enrollmentApi.approve(id)
    toast.success('Application approved')
    await load()
  } catch (err) {
    toast.error('Approve failed', getErrorMessage(err))
  }
}

async function reject(id: number) {
  try {
    await enrollmentApi.reject(id, { notes: 'Rejected from admin panel' })
    toast.success('Application rejected')
    await load()
  } catch (err) {
    toast.error('Reject failed', getErrorMessage(err))
  }
}

async function onSubmit(values: Record<string, unknown>) {
  saving.value = true
  try {
    await enrollmentApi.create(values)
    toast.success('Application submitted')
    sheetOpen.value = false
    await load()
  } catch (err) {
    formSheetRef.value?.applyServerErrors(err)
    toast.error('Submit failed', getErrorMessage(err))
  } finally {
    saving.value = false
  }
}

async function openCreate() {
  formResetValues.value = {}
  sheetOpen.value = true
  await nextTick()
  await prepareCreate()
}

onMounted(load)
</script>

<template>
  <PageShell
    title="Enrollment"
    description="Review and manage student enrollment applications"
  >
    <template #actions>
      <Button @click="openCreate">
        <Plus class="mr-2 h-4 w-4" aria-hidden="true" />
        New application
      </Button>
    </template>

    <PageLoader v-if="loading" label="Loading enrollment applications" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />
    <DataTable
      v-else
      :table="table"
      :columns="columns"
      :global-filter="globalFilter"
      search-placeholder="Search applications…"
      @update:global-filter="globalFilter = $event"
    />
  </PageShell>

  <FormSheet
    ref="formSheetRef"
    v-model:open="sheetOpen"
    title="New enrollment application"
    description="Submit a new student application. All guardian and emergency contact fields are required."
    :fields="enrollmentFormFields"
    :schema="enrollmentFormSchema"
    :reset-values="formResetValues"
    form-key="enrollment-create"
    :form-loading="formLoading"
    :saving="saving"
    save-label="Submit application"
    saving-label="Submitting…"
    @submit="onSubmit"
  />
</template>
