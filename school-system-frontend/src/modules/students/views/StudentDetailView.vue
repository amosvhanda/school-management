<script setup lang="ts">
import { computed, nextTick, onMounted, ref } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import {
  ArrowLeft,
  Download,
  Mail,
  MapPin,
  Pencil,
  Phone,
  Plus,
  User,
  Users,
} from '@lucide/vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import FormSheet from '@/components/forms/FormSheet.vue'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { Button } from '@/components/ui/button'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { useToast } from '@/composables/useToast'
import { useAuth } from '@/composables/useAuth'
import { getErrorMessage } from '@/lib/api-response'
import { formatDate } from '@/lib/format'
import { mapFormToPayload, mapRowToFormValues } from '@/modules/shared/crud-mappers'
import { preloadRelationFields } from '@/lib/relation-options'
import {
  studentFormFields,
  studentFormSchema,
  studentInvoiceFields,
  studentInvoiceSchema,
} from '@/modules/students/student-form'
import { studentsApi } from '@/services/api.service'
import StudentJourneyPanel from '@/modules/students/components/StudentJourneyPanel.vue'

interface Student {
  id: number
  student_number?: string
  full_name?: string
  first_name?: string
  last_name?: string
  date_of_birth?: string
  gender?: string
  phone?: string
  email?: string
  address?: string
  suburb?: string
  class?: string
  status?: string
  balance?: number
  currency?: string
  guardian?: {
    id?: number
    first_name?: string
    last_name?: string
    phone?: string
    email?: string
    relationship?: string
  }
  guardian_first_name?: string
  guardian_last_name?: string
  guardian_phone?: string
  guardian_email?: string
  guardian_relationship?: string
  guardians?: Array<{
    id: number
    first_name?: string
    last_name?: string
    full_name?: string
    phone?: string
    email?: string
    relationship?: string
    pivot?: { relationship?: string; is_primary?: boolean }
  }>
}

interface PerformanceData {
  average_score?: number
  total_grades?: number
  grades?: Array<{ subject?: string; score?: number; term?: string; grade?: string }>
  attendance?: Array<{ date?: string; status?: string }>
}

interface Invoice {
  id: number
  invoice_number?: string
  amount?: number
  total?: number
  balance?: number
  currency?: string
  status?: string
  due_date?: string
}

const route = useRoute()
const toast = useToast()
const { checkCapability } = useAuth()
const canEditStudent = computed(() => checkCapability('canManageStudents'))
const canCreateInvoice = computed(() => checkCapability('canManageFinance'))
const loading = ref(true)
const downloading = ref(false)
const error = ref<string | null>(null)
const student = ref<Student | null>(null)
const performance = ref<PerformanceData | null>(null)
const invoices = ref<Invoice[]>([])
const editOpen = ref(false)
const invoiceOpen = ref(false)
const saving = ref(false)
const formEditLoading = ref(false)
const studentFormResetValues = ref<Record<string, unknown> | undefined>()
const invoiceFormResetValues = ref<Record<string, unknown> | undefined>()
const studentFormSheetRef = ref<{ applyServerErrors: (error: unknown) => void } | null>(null)
const invoiceFormSheetRef = ref<{ applyServerErrors: (error: unknown) => void } | null>(null)

const id = String(route.params.id)

const displayName = computed(() =>
  student.value?.full_name
  ?? [student.value?.first_name, student.value?.last_name].filter(Boolean).join(' ')
  ?? 'Student',
)

const initials = computed(() =>
  displayName.value.split(' ').map((p) => p[0]).join('').slice(0, 2).toUpperCase(),
)

const guardianName = computed(() => {
  const g = student.value?.guardian
  if (g) {
    return [g.first_name, g.last_name].filter(Boolean).join(' ') || null
  }
  return [student.value?.guardian_first_name, student.value?.guardian_last_name].filter(Boolean).join(' ') || null
})

const guardianPhone = computed(() => student.value?.guardian?.phone ?? student.value?.guardian_phone)
const guardianEmail = computed(() => student.value?.guardian?.email ?? student.value?.guardian_email)
const guardianRelationship = computed(() => student.value?.guardian?.relationship ?? student.value?.guardian_relationship)

const linkedGuardians = computed(() => {
  if (student.value?.guardians?.length) return student.value.guardians
  if (guardianName.value) {
    return [{
      id: student.value?.guardian?.id,
      full_name: guardianName.value,
      phone: guardianPhone.value,
      email: guardianEmail.value,
      relationship: guardianRelationship.value,
      pivot: { is_primary: true, relationship: guardianRelationship.value },
    }]
  }
  return []
})

function formatMoney(amount?: number, currency = 'USD') {
  return `${currency} ${Number(amount ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 })}`
}

function invoiceTotal(inv: Invoice) {
  return inv.amount ?? inv.total
}

function statusVariant(status?: string) {
  if (status === 'active' || status === 'paid') return 'default'
  if (status === 'pending' || status === 'partial') return 'secondary'
  return 'destructive'
}

async function downloadResults() {
  downloading.value = true
  try {
    const blob = await studentsApi.downloadResults(id, 'html')
    const number = student.value?.student_number ?? 'student'
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `${number}_report_card.html`
    link.click()
    URL.revokeObjectURL(url)
    toast.success('Report card downloaded')
  } catch (err) {
    toast.error('Download failed', getErrorMessage(err))
  } finally {
    downloading.value = false
  }
}

async function load() {
  loading.value = true
  error.value = null
  try {
    student.value = await studentsApi.get(id) as Student
    performance.value = await studentsApi.performance(id) as PerformanceData
    invoices.value = await studentsApi.invoices(id) as Invoice[]
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load student')
  } finally {
    loading.value = false
  }
}

async function openEdit() {
  if (!student.value) return
  studentFormResetValues.value = mapRowToFormValues(
    'students',
    student.value as Record<string, unknown>,
    studentFormFields,
  )
  editOpen.value = true
  formEditLoading.value = true
  await nextTick()
  try {
    await preloadRelationFields(studentFormFields)
  } finally {
    formEditLoading.value = false
  }
}

async function openInvoice() {
  invoiceFormResetValues.value = {}
  invoiceOpen.value = true
}

async function onSaveStudent(values: Record<string, unknown>) {
  saving.value = true
  try {
    const payload = mapFormToPayload('students', values, studentFormFields)
    student.value = await studentsApi.update(id, payload) as Student
    toast.success('Student updated')
    editOpen.value = false
    await load()
  } catch (err) {
    studentFormSheetRef.value?.applyServerErrors(err)
    toast.error('Update failed', getErrorMessage(err))
  } finally {
    saving.value = false
  }
}

async function onCreateInvoice(values: Record<string, unknown>) {
  saving.value = true
  try {
    await studentsApi.createInvoice(id, values)
    toast.success('Invoice created')
    invoiceOpen.value = false
    invoiceFormResetValues.value = {}
    await load()
  } catch (err) {
    invoiceFormSheetRef.value?.applyServerErrors(err)
    toast.error('Invoice failed', getErrorMessage(err))
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between gap-3">
      <Button variant="ghost" size="sm" as-child>
        <RouterLink to="/students">
          <ArrowLeft class="mr-1 h-4 w-4" />
          Back to students
        </RouterLink>
      </Button>
      <div class="flex flex-wrap gap-2">
        <Button
          v-if="student && !loading"
          variant="outline"
          size="sm"
          :disabled="downloading"
          @click="downloadResults"
        >
          <Download class="mr-1 h-4 w-4" aria-hidden="true" />
          Download report card
        </Button>
        <Button v-if="student && !loading && canEditStudent" variant="outline" size="sm" @click="openEdit">
          <Pencil class="mr-1 h-4 w-4" />
          Edit student
        </Button>
      </div>
    </div>

    <PageLoader v-if="loading" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />

    <template v-else-if="student">
      <Card>
        <CardContent class="flex flex-col gap-6 p-6 sm:flex-row sm:items-center">
          <Avatar class="h-16 w-16">
            <AvatarFallback class="text-lg">{{ initials }}</AvatarFallback>
          </Avatar>
          <div class="flex-1 space-y-1">
            <div class="flex flex-wrap items-center gap-2">
              <h1 class="text-2xl font-semibold tracking-tight">{{ displayName }}</h1>
              <Badge :variant="statusVariant(student.status)">{{ student.status ?? 'unknown' }}</Badge>
            </div>
            <p class="text-muted-foreground">
              {{ student.student_number }} · {{ student.class ?? 'No class assigned' }}
            </p>
            <p v-if="student.balance" class="text-sm font-medium">
              Balance: {{ formatMoney(student.balance, student.currency) }}
            </p>
          </div>
        </CardContent>
      </Card>

      <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardHeader class="pb-2">
            <CardDescription>Date of birth</CardDescription>
            <CardTitle class="text-base">{{ formatDate(student.date_of_birth) }}</CardTitle>
          </CardHeader>
        </Card>
        <Card>
          <CardHeader class="pb-2">
            <CardDescription>Gender</CardDescription>
            <CardTitle class="text-base capitalize">{{ student.gender ?? '—' }}</CardTitle>
          </CardHeader>
        </Card>
        <Card>
          <CardHeader class="pb-2">
            <CardDescription>Phone</CardDescription>
            <CardTitle class="flex items-center gap-1 text-base">
              <Phone class="h-3.5 w-3.5 text-muted-foreground" />
              {{ student.phone ?? '—' }}
            </CardTitle>
          </CardHeader>
        </Card>
        <Card>
          <CardHeader class="pb-2">
            <CardDescription>Email</CardDescription>
            <CardTitle class="flex items-center gap-1 text-base">
              <Mail class="h-3.5 w-3.5 text-muted-foreground" />
              {{ student.email ?? '—' }}
            </CardTitle>
          </CardHeader>
        </Card>
      </div>

      <div class="grid gap-4 lg:grid-cols-3">
        <Card class="lg:col-span-2">
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <User class="h-4 w-4" />
              Contact & address
            </CardTitle>
          </CardHeader>
          <CardContent class="space-y-2 text-sm">
            <p v-if="student.address" class="flex items-start gap-2">
              <MapPin class="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground" />
              {{ student.address }}{{ student.suburb ? `, ${student.suburb}` : '' }}
            </p>
            <p v-else class="text-muted-foreground">No address on file</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <Users class="h-4 w-4" />
              Guardians
            </CardTitle>
            <CardDescription>Linked from the guardian register</CardDescription>
          </CardHeader>
          <CardContent class="space-y-4 text-sm">
            <div v-if="!linkedGuardians.length" class="text-muted-foreground">
              No guardian linked yet. Edit the student to select or register one.
            </div>
            <div
              v-for="guardian in linkedGuardians"
              :key="String(guardian.id ?? guardian.full_name)"
              class="rounded-lg border p-3"
            >
              <div class="flex items-start justify-between gap-2">
                <p class="font-medium">
                  {{ guardian.full_name ?? ('first_name' in guardian ? [guardian.first_name, guardian.last_name].filter(Boolean).join(' ') : '') }}
                </p>
                <Badge v-if="guardian.pivot?.is_primary" variant="secondary">Primary</Badge>
              </div>
              <p v-if="guardian.pivot?.relationship ?? guardian.relationship" class="mt-1 capitalize text-muted-foreground">
                {{ guardian.pivot?.relationship ?? guardian.relationship }}
              </p>
              <p v-if="guardian.phone" class="mt-2">{{ guardian.phone }}</p>
              <p v-if="guardian.email">{{ guardian.email }}</p>
              <Button
                v-if="guardian.id"
                variant="link"
                class="mt-2 h-auto p-0"
                as-child
              >
                <RouterLink :to="{ name: 'guardians' }">View in guardians</RouterLink>
              </Button>
            </div>
          </CardContent>
        </Card>
      </div>

      <Tabs default-value="journey">
        <TabsList class="flex h-auto flex-wrap gap-1">
          <TabsTrigger value="journey">Journey</TabsTrigger>
          <TabsTrigger value="performance">Performance</TabsTrigger>
          <TabsTrigger value="fees">Fees & invoices</TabsTrigger>
          <TabsTrigger value="attendance">Attendance</TabsTrigger>
        </TabsList>

        <TabsContent value="journey" class="mt-4">
          <StudentJourneyPanel :student-id="id" @refreshed="load" />
        </TabsContent>

        <TabsContent value="performance" class="space-y-4">
          <div class="grid gap-4 sm:grid-cols-2">
            <Card>
              <CardHeader class="pb-2">
                <CardDescription>Average score</CardDescription>
                <CardTitle class="text-2xl">
                  {{ performance?.average_score != null ? Number(performance.average_score).toFixed(1) : '—' }}%
                </CardTitle>
              </CardHeader>
            </Card>
            <Card>
              <CardHeader class="pb-2">
                <CardDescription>Total grades recorded</CardDescription>
                <CardTitle class="text-2xl">{{ performance?.total_grades ?? 0 }}</CardTitle>
              </CardHeader>
            </Card>
          </div>
          <Card>
            <CardHeader><CardTitle>Grade history</CardTitle></CardHeader>
            <CardContent>
              <Table v-if="performance?.grades?.length">
                <TableHeader>
                  <TableRow>
                    <TableHead>Subject</TableHead>
                    <TableHead>Term</TableHead>
                    <TableHead>Score</TableHead>
                    <TableHead>Grade</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow v-for="(grade, i) in performance.grades" :key="i">
                    <TableCell>{{ grade.subject ?? '—' }}</TableCell>
                    <TableCell>{{ grade.term ?? '—' }}</TableCell>
                    <TableCell>{{ grade.score ?? '—' }}</TableCell>
                    <TableCell>{{ grade.grade ?? '—' }}</TableCell>
                  </TableRow>
                </TableBody>
              </Table>
              <p v-else class="text-sm text-muted-foreground">No grades recorded yet.</p>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="fees">
          <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0">
              <div>
                <CardTitle>Invoices</CardTitle>
                <CardDescription>Fee invoices and outstanding balances</CardDescription>
              </div>
              <Button v-if="canCreateInvoice" size="sm" @click="openInvoice">
                <Plus class="mr-1 h-4 w-4" />
                New invoice
              </Button>
            </CardHeader>
            <CardContent>
              <Table v-if="invoices.length">
                <TableHeader>
                  <TableRow>
                    <TableHead>Invoice #</TableHead>
                    <TableHead>Total</TableHead>
                    <TableHead>Balance</TableHead>
                    <TableHead>Due</TableHead>
                    <TableHead>Status</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow v-for="inv in invoices" :key="inv.id">
                    <TableCell>{{ inv.invoice_number ?? inv.id }}</TableCell>
                    <TableCell>{{ formatMoney(invoiceTotal(inv), inv.currency) }}</TableCell>
                    <TableCell>{{ formatMoney(inv.balance, inv.currency) }}</TableCell>
                    <TableCell>{{ formatDate(inv.due_date) }}</TableCell>
                    <TableCell>
                      <Badge :variant="statusVariant(inv.status)">{{ inv.status ?? '—' }}</Badge>
                    </TableCell>
                  </TableRow>
                </TableBody>
              </Table>
              <p v-else class="text-sm text-muted-foreground">No invoices for this student.</p>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="attendance">
          <Card>
            <CardHeader><CardTitle>Recent attendance</CardTitle></CardHeader>
            <CardContent>
              <Table v-if="performance?.attendance?.length">
                <TableHeader>
                  <TableRow>
                    <TableHead>Date</TableHead>
                    <TableHead>Status</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow v-for="(record, i) in performance.attendance.slice(0, 20)" :key="i">
                    <TableCell>{{ formatDate(record.date) }}</TableCell>
                    <TableCell>
                      <Badge :variant="statusVariant(record.status)">{{ record.status ?? '—' }}</Badge>
                    </TableCell>
                  </TableRow>
                </TableBody>
              </Table>
              <p v-else class="text-sm text-muted-foreground">No attendance records yet.</p>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </template>

    <FormSheet
      ref="studentFormSheetRef"
      v-model:open="editOpen"
      title="Edit student"
      :fields="studentFormFields"
      :schema="studentFormSchema"
      :reset-values="studentFormResetValues"
      :form-key="`student-edit-${id}`"
      :form-loading="formEditLoading"
      :saving="saving"
      description="Update personal, enrollment, contact, and guardian information in one place."
      save-label="Save changes"
      @submit="onSaveStudent"
    />

    <FormSheet
      ref="invoiceFormSheetRef"
      v-model:open="invoiceOpen"
      title="Create invoice"
      description="Issue a fee invoice for this student. Amount is in the school currency."
      :fields="studentInvoiceFields"
      :schema="studentInvoiceSchema"
      :reset-values="invoiceFormResetValues"
      form-key="student-invoice"
      :saving="saving"
      save-label="Create invoice"
      saving-label="Creating…"
      @submit="onCreateInvoice"
    />
  </div>
</template>
