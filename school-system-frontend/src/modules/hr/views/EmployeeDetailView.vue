<script setup lang="ts">
import { computed, nextTick, onMounted, ref } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import { CalendarClock, Mail, Phone } from '@lucide/vue'
import EntityDetailShell from '@/components/detail/EntityDetailShell.vue'
import FormSheet from '@/components/forms/FormSheet.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'
import { getErrorMessage } from '@/lib/api-response'
import { formatDate } from '@/lib/format'
import { preloadRelationFields } from '@/lib/relation-options'
import { mapFormToPayload, mapRowToFormValues } from '@/modules/shared/crud-mappers'
import { moduleCrudRegistry } from '@/modules/shared/registry-crud'
import { hrApi } from '@/services/api.service'

interface Employee {
  id: number
  employee_number?: string | null
  first_name?: string
  last_name?: string
  name?: string
  email?: string | null
  phone?: string | null
  designation?: { id: number; name: string } | null
  department?: { id: number; name: string } | null
  employment_type?: string | null
  status?: string
  joining_date?: string | null
  base_salary?: number | null
  salary_currency?: string | null
}

const route = useRoute()
const toast = useToast()
const { checkCapability } = useAuth()
const canEdit = computed(() => checkCapability('canManageTeachers'))

const employeeConfig = moduleCrudRegistry['hr-employees']

const id = String(route.params.id)
const loading = ref(true)
const error = ref<string | null>(null)
const employee = ref<Employee | null>(null)

const editOpen = ref(false)
const saving = ref(false)
const formLoading = ref(false)
const resetValues = ref<Record<string, unknown> | undefined>()
const formSheetRef = ref<{ applyServerErrors: (error: unknown) => void } | null>(null)

const displayName = computed(() =>
  employee.value?.name
  || [employee.value?.first_name, employee.value?.last_name].filter(Boolean).join(' ')
  || 'Employee',
)

const initials = computed(() =>
  displayName.value
    .split(' ')
    .filter(Boolean)
    .map((part) => part[0])
    .join('')
    .slice(0, 2)
    .toUpperCase() || 'E',
)

const subtitle = computed(() => (employee.value?.employee_number ? `Employee #${employee.value.employee_number}` : undefined))

const employmentTypeLabel = computed(() => {
  const type = employee.value?.employment_type
  return type ? type.replace(/_/g, ' ') : null
})

async function load() {
  loading.value = true
  error.value = null
  try {
    employee.value = await hrApi.employees.get(id) as Employee
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load employee')
  } finally {
    loading.value = false
  }
}

async function openEdit() {
  if (!employee.value) return
  resetValues.value = mapRowToFormValues('hr-employees', employee.value as Record<string, unknown>, employeeConfig.formFields)
  editOpen.value = true
  formLoading.value = true
  await nextTick()
  try {
    await preloadRelationFields(employeeConfig.formFields)
  } finally {
    formLoading.value = false
  }
}

async function onSave(values: Record<string, unknown>) {
  saving.value = true
  try {
    const payload = mapFormToPayload('hr-employees', values, employeeConfig.formFields)
    employee.value = await hrApi.employees.update(id, payload) as Employee
    toast.success('Employee updated')
    editOpen.value = false
  } catch (err) {
    formSheetRef.value?.applyServerErrors(err)
    toast.error('Update failed', getErrorMessage(err))
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>

<template>
  <EntityDetailShell
    :title="displayName"
    :subtitle="subtitle"
    back-to="/hr?tab=employees"
    back-label="Back to employees"
    :loading="loading"
    :error="error"
    :initials="initials"
    :status="employee?.status"
    @retry="load"
  >
    <template #actions>
      <Button v-if="canEdit" variant="outline" size="sm" @click="openEdit">
        Edit employee
      </Button>
    </template>

    <div v-if="employee" class="grid gap-4 lg:grid-cols-3">
      <Card class="lg:col-span-2">
        <CardHeader>
          <CardTitle>Profile</CardTitle>
          <CardDescription>Contact and employment details</CardDescription>
        </CardHeader>
        <CardContent>
          <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2">
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Email</dt>
              <dd class="mt-1 flex items-center gap-1.5 text-sm">
                <Mail class="size-3.5 shrink-0 text-muted-foreground" aria-hidden="true" />
                <span class="truncate">{{ employee.email ?? '—' }}</span>
              </dd>
            </div>
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Phone</dt>
              <dd class="mt-1 flex items-center gap-1.5 text-sm">
                <Phone class="size-3.5 shrink-0 text-muted-foreground" aria-hidden="true" />
                {{ employee.phone ?? '—' }}
              </dd>
            </div>
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Designation</dt>
              <dd class="mt-1 text-sm">{{ employee.designation?.name ?? '—' }}</dd>
            </div>
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Department</dt>
              <dd class="mt-1 text-sm">{{ employee.department?.name ?? '—' }}</dd>
            </div>
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Employment type</dt>
              <dd class="mt-1 text-sm capitalize">{{ employmentTypeLabel ?? '—' }}</dd>
            </div>
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Joining date</dt>
              <dd class="mt-1 flex items-center gap-1.5 text-sm">
                <CalendarClock class="size-3.5 shrink-0 text-muted-foreground" aria-hidden="true" />
                {{ formatDate(employee.joining_date) }}
              </dd>
            </div>
          </dl>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Payroll</CardTitle>
          <CardDescription>Compensation summary</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div>
            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Base salary</p>
            <p class="mt-1 text-lg font-semibold tabular-nums">
              {{ employee.base_salary != null
                ? `${employee.salary_currency ?? 'USD'} ${Number(employee.base_salary).toLocaleString(undefined, { minimumFractionDigits: 2 })}`
                : '—' }}
            </p>
          </div>
          <Button variant="outline" class="w-full justify-start" as-child>
            <RouterLink to="/hr/staff-attendance?staff=employee">Employee attendance</RouterLink>
          </Button>
          <Button variant="outline" class="w-full justify-start" as-child>
            <RouterLink to="/finance?tab=payroll">View payroll</RouterLink>
          </Button>
        </CardContent>
      </Card>
    </div>
  </EntityDetailShell>

  <FormSheet
    ref="formSheetRef"
    v-model:open="editOpen"
    title="Edit employee"
    description="Update contact, employment, and payroll details."
    :fields="employeeConfig.formFields"
    :schema="employeeConfig.formSchema"
    :reset-values="resetValues"
    :form-key="`employee-edit-${id}`"
    :form-loading="formLoading"
    :saving="saving"
    save-label="Save changes"
    @submit="onSave"
  />
</template>
