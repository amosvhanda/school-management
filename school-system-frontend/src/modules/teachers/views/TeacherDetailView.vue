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
import { teachersApi } from '@/services/api.service'

interface Teacher {
  id: number
  employee_id?: string | null
  first_name?: string
  last_name?: string
  name?: string
  email?: string | null
  phone?: string | null
  subject?: string | null
  department?: string | null
  designation?: { id: number; name: string; code?: string | null } | null
  status?: string
  joining_date?: string | null
}

const route = useRoute()
const toast = useToast()
const { checkCapability } = useAuth()
const canEdit = computed(() => checkCapability('canManageTeachers'))

const teacherConfig = moduleCrudRegistry.teachers

const id = String(route.params.id)
const loading = ref(true)
const error = ref<string | null>(null)
const teacher = ref<Teacher | null>(null)

const editOpen = ref(false)
const saving = ref(false)
const formLoading = ref(false)
const resetValues = ref<Record<string, unknown> | undefined>()
const formSheetRef = ref<{ applyServerErrors: (error: unknown) => void } | null>(null)

const displayName = computed(() =>
  teacher.value?.name
  || [teacher.value?.first_name, teacher.value?.last_name].filter(Boolean).join(' ')
  || 'Teacher',
)

const initials = computed(() =>
  displayName.value
    .split(' ')
    .filter(Boolean)
    .map((part) => part[0])
    .join('')
    .slice(0, 2)
    .toUpperCase() || 'T',
)

const subtitle = computed(() => (teacher.value?.employee_id ? `Employee ID: ${teacher.value.employee_id}` : undefined))
const jobTitle = computed(() => teacher.value?.designation?.name ?? null)

async function load() {
  loading.value = true
  error.value = null
  try {
    teacher.value = await teachersApi.get(id) as Teacher
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load teacher')
  } finally {
    loading.value = false
  }
}

async function openEdit() {
  if (!teacher.value) return
  resetValues.value = mapRowToFormValues('teachers', teacher.value as Record<string, unknown>, teacherConfig.formFields)
  editOpen.value = true
  formLoading.value = true
  await nextTick()
  try {
    await preloadRelationFields(teacherConfig.formFields)
  } finally {
    formLoading.value = false
  }
}

async function onSave(values: Record<string, unknown>) {
  saving.value = true
  try {
    const payload = mapFormToPayload('teachers', values, teacherConfig.formFields)
    teacher.value = await teachersApi.update(id, payload) as Teacher
    toast.success('Teacher updated')
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
    back-to="/people?tab=teachers"
    back-label="Back to teachers"
    :loading="loading"
    :error="error"
    :initials="initials"
    :status="teacher?.status"
    @retry="load"
  >
    <template #actions>
      <Button v-if="canEdit" variant="outline" size="sm" @click="openEdit">
        Edit teacher
      </Button>
    </template>

    <div v-if="teacher" class="grid gap-4 lg:grid-cols-3">
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
                <span class="truncate">{{ teacher.email ?? '—' }}</span>
              </dd>
            </div>
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Phone</dt>
              <dd class="mt-1 flex items-center gap-1.5 text-sm">
                <Phone class="size-3.5 shrink-0 text-muted-foreground" aria-hidden="true" />
                {{ teacher.phone ?? '—' }}
              </dd>
            </div>
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Subject</dt>
              <dd class="mt-1 text-sm">{{ teacher.subject ?? '—' }}</dd>
            </div>
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Department</dt>
              <dd class="mt-1 text-sm">{{ teacher.department ?? '—' }}</dd>
            </div>
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Job title</dt>
              <dd class="mt-1 text-sm">{{ jobTitle ?? '—' }}</dd>
            </div>
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Joining date</dt>
              <dd class="mt-1 flex items-center gap-1.5 text-sm">
                <CalendarClock class="size-3.5 shrink-0 text-muted-foreground" aria-hidden="true" />
                {{ formatDate(teacher.joining_date) }}
              </dd>
            </div>
          </dl>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Quick links</CardTitle>
          <CardDescription>Related staff workspaces</CardDescription>
        </CardHeader>
        <CardContent class="flex flex-col gap-2">
          <Button variant="outline" class="justify-start" as-child>
            <RouterLink to="/academics/timetable">Timetable</RouterLink>
          </Button>
          <Button variant="outline" class="justify-start" as-child>
            <RouterLink to="/hr/staff-attendance?staff=teacher">Teacher attendance</RouterLink>
          </Button>
          <Button variant="outline" class="justify-start" as-child>
            <RouterLink to="/hr/leave">Leave</RouterLink>
          </Button>
        </CardContent>
      </Card>
    </div>
  </EntityDetailShell>

  <FormSheet
    ref="formSheetRef"
    v-model:open="editOpen"
    title="Edit teacher"
    description="Update personal, contact, employment, and payroll details."
    :fields="teacherConfig.formFields"
    :schema="teacherConfig.formSchema"
    :reset-values="resetValues"
    :form-key="`teacher-edit-${id}`"
    :form-loading="formLoading"
    :saving="saving"
    :staged="teacherConfig.staged"
    save-label="Save changes"
    @submit="onSave"
  />
</template>
