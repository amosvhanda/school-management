<script setup lang="ts">
import { computed, nextTick, onMounted, ref } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import { Mail, Phone, Users } from '@lucide/vue'
import EntityDetailShell from '@/components/detail/EntityDetailShell.vue'
import FormSheet from '@/components/forms/FormSheet.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'
import { getErrorMessage } from '@/lib/api-response'
import { preloadRelationFields } from '@/lib/relation-options'
import { guardianFormFields, guardianFormSchema } from '@/modules/guardians/guardian-form'
import { mapFormToPayload, mapRowToFormValues } from '@/modules/shared/crud-mappers'
import { guardiansApi } from '@/services/api.service'

interface GuardianStudent {
  id: number
  full_name?: string
  student_number?: string
  class?: string
  status?: string
  pivot?: { relationship?: string; is_primary?: boolean }
}

interface Guardian {
  id: number
  first_name?: string
  last_name?: string
  email?: string | null
  phone?: string | null
  relationship?: string | null
  address?: string | null
  occupation?: string | null
  national_id?: string | null
}

const route = useRoute()
const toast = useToast()
const { checkCapability } = useAuth()
const canEdit = computed(() => checkCapability('canManageTeachers'))

const id = String(route.params.id)
const loading = ref(true)
const error = ref<string | null>(null)
const guardian = ref<Guardian | null>(null)
const students = ref<GuardianStudent[]>([])

const editOpen = ref(false)
const saving = ref(false)
const formLoading = ref(false)
const resetValues = ref<Record<string, unknown> | undefined>()
const formSheetRef = ref<{ applyServerErrors: (error: unknown) => void } | null>(null)

const displayName = computed(() =>
  [guardian.value?.first_name, guardian.value?.last_name].filter(Boolean).join(' ') || 'Guardian',
)

const initials = computed(() =>
  displayName.value
    .split(' ')
    .filter(Boolean)
    .map((part) => part[0])
    .join('')
    .slice(0, 2)
    .toUpperCase() || 'G',
)

const subtitle = computed(() => {
  const relationship = guardian.value?.relationship
  return relationship ? `${relationship.charAt(0).toUpperCase()}${relationship.slice(1)}` : undefined
})

async function load() {
  loading.value = true
  error.value = null
  try {
    const [guardianData, studentsData] = await Promise.all([
      guardiansApi.get(id) as Promise<Guardian>,
      guardiansApi.students(id) as Promise<GuardianStudent[]>,
    ])
    guardian.value = guardianData
    students.value = Array.isArray(studentsData) ? studentsData : []
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load guardian')
  } finally {
    loading.value = false
  }
}

async function openEdit() {
  if (!guardian.value) return
  resetValues.value = mapRowToFormValues('guardians', guardian.value as Record<string, unknown>, guardianFormFields)
  editOpen.value = true
  formLoading.value = true
  await nextTick()
  try {
    await preloadRelationFields(guardianFormFields)
  } finally {
    formLoading.value = false
  }
}

async function onSave(values: Record<string, unknown>) {
  saving.value = true
  try {
    const payload = mapFormToPayload('guardians', values, guardianFormFields)
    guardian.value = await guardiansApi.update(id, payload) as Guardian
    toast.success('Guardian updated')
    editOpen.value = false
    await load()
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
    back-to="/people?tab=guardians"
    back-label="Back to guardians"
    :loading="loading"
    :error="error"
    :initials="initials"
    @retry="load"
  >
    <template #actions>
      <Button v-if="canEdit" variant="outline" size="sm" @click="openEdit">
        Edit guardian
      </Button>
    </template>

    <div v-if="guardian" class="grid gap-4 lg:grid-cols-3">
      <Card class="lg:col-span-2">
        <CardHeader>
          <CardTitle>Profile</CardTitle>
          <CardDescription>Contact and identification details</CardDescription>
        </CardHeader>
        <CardContent>
          <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2">
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Email</dt>
              <dd class="mt-1 flex items-center gap-1.5 text-sm">
                <Mail class="size-3.5 shrink-0 text-muted-foreground" aria-hidden="true" />
                <span class="truncate">{{ guardian.email ?? '—' }}</span>
              </dd>
            </div>
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Phone</dt>
              <dd class="mt-1 flex items-center gap-1.5 text-sm">
                <Phone class="size-3.5 shrink-0 text-muted-foreground" aria-hidden="true" />
                {{ guardian.phone ?? '—' }}
              </dd>
            </div>
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Occupation</dt>
              <dd class="mt-1 text-sm">{{ guardian.occupation ?? '—' }}</dd>
            </div>
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">National ID</dt>
              <dd class="mt-1 text-sm">{{ guardian.national_id ?? '—' }}</dd>
            </div>
            <div class="sm:col-span-2">
              <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Address</dt>
              <dd class="mt-1 text-sm">{{ guardian.address ?? '—' }}</dd>
            </div>
          </dl>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <Users class="size-4" aria-hidden="true" />
            Linked students
          </CardTitle>
          <CardDescription>{{ students.length }} student{{ students.length === 1 ? '' : 's' }} linked</CardDescription>
        </CardHeader>
        <CardContent class="p-0">
          <Table v-if="students.length">
            <TableHeader>
              <TableRow>
                <TableHead>Student</TableHead>
                <TableHead>Class</TableHead>
                <TableHead>Relationship</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="student in students" :key="student.id">
                <TableCell>
                  <RouterLink
                    :to="{ name: 'student-detail', params: { id: student.id } }"
                    class="font-medium text-primary underline-offset-4 hover:underline focus-visible:underline focus-visible:outline-none"
                  >
                    {{ student.full_name ?? `Student #${student.id}` }}
                  </RouterLink>
                  <p class="text-xs text-muted-foreground">{{ student.student_number ?? '—' }}</p>
                </TableCell>
                <TableCell>{{ student.class ?? '—' }}</TableCell>
                <TableCell>
                  <div class="flex items-center gap-1.5">
                    <span class="capitalize">{{ student.pivot?.relationship ?? '—' }}</span>
                    <Badge v-if="student.pivot?.is_primary" variant="secondary">Primary</Badge>
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
          <p v-else class="p-6 text-sm text-muted-foreground">No students linked to this guardian yet.</p>
        </CardContent>
      </Card>
    </div>
  </EntityDetailShell>

  <FormSheet
    ref="formSheetRef"
    v-model:open="editOpen"
    title="Edit guardian"
    description="Update contact details and the linked student."
    :fields="guardianFormFields"
    :schema="guardianFormSchema"
    :reset-values="resetValues"
    :form-key="`guardian-edit-${id}`"
    :form-loading="formLoading"
    :saving="saving"
    save-label="Save changes"
    @submit="onSave"
  />
</template>
