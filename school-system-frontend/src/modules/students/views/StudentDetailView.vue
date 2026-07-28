<script setup lang="ts">
import { computed, nextTick, onMounted, ref } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import {
  ArrowLeft,
  Banknote,
  Camera,
  Download,
  FileText,
  IdCard,
  Mail,
  MapPin,
  Pencil,
  Phone,
  Plus,
  Printer,
  Trash2,
  User,
  Users,
} from '@lucide/vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import FormSheet from '@/components/forms/FormSheet.vue'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
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
import { studentsApi, financeApi, platformApi } from '@/services/api.service'
import StudentJourneyPanel from '@/modules/students/components/StudentJourneyPanel.vue'
import InvoicePrintSheet from '@/modules/finance/components/InvoicePrintSheet.vue'
import StudentIdCardSheet from '@/modules/students/components/StudentIdCardSheet.vue'
import PaymentReceiptSheet from '@/modules/finance/components/PaymentReceiptSheet.vue'
import { feePaymentPromptForm } from '@/modules/shared/action-prompt-forms'

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
  photo_url?: string | null
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

interface StudentDocumentRow {
  id: number
  name?: string
  type?: string | null
  mime_type?: string | null
  size?: number | null
  url?: string | null
  created_at?: string | null
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
const invoicePrintOpen = ref(false)
const invoicePrintLoading = ref(false)
const invoicePrintData = ref<Record<string, unknown> | null>(null)
const idCardPrintOpen = ref(false)
const idCardPrintLoading = ref(false)
const idCardPrintData = ref<Record<string, unknown> | null>(null)
const paymentOpen = ref(false)
const paymentTarget = ref<Invoice | null>(null)
const paymentFormResetValues = ref<Record<string, unknown> | undefined>()
const paymentFormSheetRef = ref<{ applyServerErrors: (error: unknown) => void } | null>(null)
const receiptOpen = ref(false)
const receiptLoading = ref(false)
const receiptData = ref<Record<string, unknown> | null>(null)
const saving = ref(false)
const formEditLoading = ref(false)
const studentFormResetValues = ref<Record<string, unknown> | undefined>()
const invoiceFormResetValues = ref<Record<string, unknown> | undefined>()
const studentFormSheetRef = ref<{ applyServerErrors: (error: unknown) => void } | null>(null)
const invoiceFormSheetRef = ref<{ applyServerErrors: (error: unknown) => void } | null>(null)
const photoInput = ref<HTMLInputElement | null>(null)
const uploadingPhoto = ref(false)
const documents = ref<StudentDocumentRow[]>([])
const documentsLoading = ref(false)
const documentsError = ref<string | null>(null)
const documentInput = ref<HTMLInputElement | null>(null)
const uploadingDocuments = ref(false)
const deletingDocumentId = ref<number | null>(null)
const documentType = ref('birth_certificate')
const initiatingOnlinePaymentId = ref<number | null>(null)

const DOCUMENT_TYPE_OPTIONS = [
  { label: 'Birth certificate', value: 'birth_certificate' },
  { label: 'ID / passport', value: 'identity' },
  { label: 'Medical', value: 'medical' },
  { label: 'Report / transcript', value: 'academic' },
  { label: 'Other', value: 'other' },
]

const id = String(route.params.id)

const displayName = computed(() =>
  student.value?.full_name
  ?? [student.value?.first_name, student.value?.last_name].filter(Boolean).join(' ')
  ?? 'Student',
)

const initials = computed(() =>
  displayName.value.split(' ').map((p) => p[0]).join('').slice(0, 2).toUpperCase(),
)

const studentPhotoUrl = computed(() => student.value?.photo_url ?? null)

function pickPhoto() {
  photoInput.value?.click()
}

async function onPhotoSelected(event: Event) {
  if (!student.value?.id) return
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file) return

  if (!file.type.startsWith('image/')) {
    toast.warning('Invalid file', { description: 'Please choose an image file.' })
    input.value = ''
    return
  }
  if (file.size > 5 * 1024 * 1024) {
    toast.warning('File too large', { description: 'Choose an image under 5 MB.' })
    input.value = ''
    return
  }

  uploadingPhoto.value = true
  try {
    const result = await studentsApi.uploadPhoto(student.value.id, file)
    if (student.value) {
      student.value = { ...student.value, photo_url: result.photo_url }
    }
    toast.success('Student photo updated')
  } catch (err) {
    toast.error('Could not upload photo', { description: getErrorMessage(err) })
  } finally {
    uploadingPhoto.value = false
    input.value = ''
  }
}

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

async function openInvoicePrint(invoiceId: number) {
  invoicePrintOpen.value = true
  invoicePrintLoading.value = true
  invoicePrintData.value = null
  try {
    invoicePrintData.value = await financeApi.invoices.print(invoiceId)
  } catch (err) {
    toast.error('Invoice unavailable', getErrorMessage(err))
    invoicePrintOpen.value = false
  } finally {
    invoicePrintLoading.value = false
  }
}

async function openIdCardPrint() {
  if (!student.value?.id) return
  idCardPrintOpen.value = true
  idCardPrintLoading.value = true
  idCardPrintData.value = null
  try {
    idCardPrintData.value = await studentsApi.printIdCard(student.value.id)
  } catch (err) {
    toast.error('Could not load ID card', getErrorMessage(err))
    idCardPrintOpen.value = false
  } finally {
    idCardPrintLoading.value = false
  }
}

function canRecordPayment(inv: Invoice) {
  const status = String(inv.status ?? '').toLowerCase()
  const balance = Number(inv.balance ?? 0)
  return ['pending', 'partial', 'overdue'].includes(status) && balance > 0
}

async function openRecordPayment(inv: Invoice) {
  paymentTarget.value = inv
  const defaults =
    typeof feePaymentPromptForm.defaults === 'function'
      ? feePaymentPromptForm.defaults(inv as unknown as Record<string, unknown>)
      : (feePaymentPromptForm.defaults ?? {})
  paymentFormResetValues.value = { ...defaults }
  paymentOpen.value = true
  await nextTick()
  await preloadRelationFields(feePaymentPromptForm.fields)
}

async function payOnline(inv: Invoice) {
  if (!canRecordPayment(inv)) return
  initiatingOnlinePaymentId.value = inv.id
  try {
    const result = await platformApi.initiatePayment({
      invoice_id: inv.id,
      student_id: Number(id),
      amount: Number(inv.balance),
      payment_method: 'mobile_money',
      provider: 'paynow',
      return_url: window.location.href,
    }) as {
      checkout_url?: string | null
      transaction?: { internal_reference?: string }
    }

    if (result.checkout_url) {
      window.location.assign(result.checkout_url)
      return
    }

    toast.success('Payment initiated', {
      description: result.transaction?.internal_reference
        ? `Reference ${result.transaction.internal_reference}`
        : 'Awaiting gateway confirmation.',
    })
    await load()
  } catch (err) {
    toast.error('Online payment failed', getErrorMessage(err))
  } finally {
    initiatingOnlinePaymentId.value = null
  }
}

async function onRecordPayment(values: Record<string, unknown>) {
  if (!paymentTarget.value) return
  saving.value = true
  try {
    const payload: Record<string, unknown> = {
      invoice_id: paymentTarget.value.id,
      amount: Number(values.amount),
      method: values.method,
    }
    if (values.reference) payload.reference = values.reference
    if (values.notes) payload.notes = values.notes
    if (values.income_head_id) payload.income_head_id = Number(values.income_head_id)

    const payment = await financeApi.payments.create(payload) as { id?: number }
    toast.success('Payment recorded')
    paymentOpen.value = false
    paymentTarget.value = null
    await load()

    if (payment?.id) {
      receiptOpen.value = true
      receiptLoading.value = true
      receiptData.value = null
      try {
        receiptData.value = await financeApi.payments.receipt(payment.id)
      } catch {
        receiptOpen.value = false
      } finally {
        receiptLoading.value = false
      }
    }
  } catch (err) {
    paymentFormSheetRef.value?.applyServerErrors(err)
    toast.error('Payment failed', getErrorMessage(err))
  } finally {
    saving.value = false
  }
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
    void loadDocuments()
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load student')
  } finally {
    loading.value = false
  }
}

async function loadDocuments() {
  if (!canEditStudent.value) {
    documents.value = []
    return
  }
  documentsLoading.value = true
  documentsError.value = null
  try {
    documents.value = await studentsApi.documents(id) as StudentDocumentRow[]
  } catch (err) {
    documentsError.value = getErrorMessage(err, 'Failed to load documents')
  } finally {
    documentsLoading.value = false
  }
}

function pickDocuments() {
  documentInput.value?.click()
}

async function onDocumentsSelected(event: Event) {
  const input = event.target as HTMLInputElement
  const files = Array.from(input.files ?? [])
  if (!files.length) return

  uploadingDocuments.value = true
  try {
    await studentsApi.uploadDocuments(id, files, documentType.value)
    toast.success(files.length === 1 ? 'Document uploaded' : `${files.length} documents uploaded`)
    await loadDocuments()
  } catch (err) {
    toast.error('Upload failed', getErrorMessage(err))
  } finally {
    uploadingDocuments.value = false
    input.value = ''
  }
}

async function removeDocument(doc: StudentDocumentRow) {
  deletingDocumentId.value = doc.id
  try {
    await studentsApi.deleteDocument(id, doc.id)
    toast.success('Document deleted')
    documents.value = documents.value.filter((row) => row.id !== doc.id)
  } catch (err) {
    toast.error('Could not delete document', getErrorMessage(err))
  } finally {
    deletingDocumentId.value = null
  }
}

function formatFileSize(bytes?: number | null) {
  if (!bytes || bytes < 1) return '—'
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
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
  invoiceFormResetValues.value = { apply_discounts: true }
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
    const payload: Record<string, unknown> = {
      dueDate: values.dueDate,
      apply_discounts: values.apply_discounts !== false,
      combine_group: values.combine_group === true,
    }
    if (values.fee_group_id) {
      payload.fee_group_id = Number(values.fee_group_id)
      if (values.description) payload.description = values.description
    } else {
      payload.description = values.description
      payload.amount = values.amount
      if (values.fee_structure_id) {
        payload.fee_structure_id = Number(values.fee_structure_id)
      }
    }
    await studentsApi.createInvoice(id, payload)
    toast.success('Invoice created')
    invoiceOpen.value = false
    invoiceFormResetValues.value = { apply_discounts: true }
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
          @click="openIdCardPrint"
        >
          <IdCard class="mr-1 h-4 w-4" aria-hidden="true" />
          Print ID card
        </Button>
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
          <div class="relative shrink-0">
            <Avatar class="h-16 w-16">
              <AvatarImage v-if="studentPhotoUrl" :src="studentPhotoUrl" :alt="displayName" />
              <AvatarFallback class="text-lg">{{ initials }}</AvatarFallback>
            </Avatar>
            <button
              v-if="canEditStudent"
              type="button"
              class="absolute -right-1 -bottom-1 flex size-7 items-center justify-center rounded-full border border-border bg-background text-muted-foreground shadow-sm transition-colors hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:opacity-60"
              :disabled="uploadingPhoto"
              :aria-busy="uploadingPhoto"
              aria-label="Change student photo"
              @click="pickPhoto"
            >
              <Camera class="size-3.5" aria-hidden="true" />
            </button>
            <input
              ref="photoInput"
              type="file"
              accept="image/*"
              class="sr-only"
              @change="onPhotoSelected"
            />
          </div>
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
          <TabsTrigger value="documents">Documents</TabsTrigger>
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
                    <TableHead class="w-24">
                      <span class="sr-only">Actions</span>
                    </TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow v-for="inv in invoices" :key="inv.id">
                    <TableCell>{{ inv.invoice_number ?? '—' }}</TableCell>
                    <TableCell>{{ formatMoney(invoiceTotal(inv), inv.currency) }}</TableCell>
                    <TableCell>{{ formatMoney(inv.balance, inv.currency) }}</TableCell>
                    <TableCell>{{ formatDate(inv.due_date) }}</TableCell>
                    <TableCell>
                      <Badge :variant="statusVariant(inv.status)">{{ inv.status ?? '—' }}</Badge>
                    </TableCell>
                    <TableCell>
                      <div class="flex flex-wrap justify-end gap-2">
                        <Button
                          v-if="canCreateInvoice && canRecordPayment(inv)"
                          variant="outline"
                          size="sm"
                          :aria-label="`Record payment for invoice ${inv.invoice_number ?? inv.id}`"
                          @click="openRecordPayment(inv)"
                        >
                          <Banknote class="h-4 w-4" aria-hidden="true" />
                          <span class="sr-only sm:not-sr-only sm:ml-1">Record</span>
                        </Button>
                        <Button
                          v-if="canCreateInvoice && canRecordPayment(inv)"
                          variant="secondary"
                          size="sm"
                          :disabled="initiatingOnlinePaymentId === inv.id"
                          :aria-busy="initiatingOnlinePaymentId === inv.id"
                          :aria-label="`Pay online for invoice ${inv.invoice_number ?? inv.id}`"
                          @click="payOnline(inv)"
                        >
                          Pay online
                        </Button>
                        <Button
                          v-if="canCreateInvoice"
                          variant="outline"
                          size="sm"
                          :aria-label="`Print invoice ${inv.invoice_number ?? inv.id}`"
                          @click="openInvoicePrint(inv.id)"
                        >
                          <Printer class="h-4 w-4" aria-hidden="true" />
                        </Button>
                      </div>
                    </TableCell>
                  </TableRow>
                </TableBody>
              </Table>
              <p v-else class="text-sm text-muted-foreground">No invoices for this student.</p>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="documents" class="mt-4">
          <Card>
            <CardHeader class="flex flex-col gap-4 space-y-0 sm:flex-row sm:items-start sm:justify-between">
              <div>
                <CardTitle class="flex items-center gap-2">
                  <FileText class="h-4 w-4" aria-hidden="true" />
                  Student documents
                </CardTitle>
                <CardDescription>Birth certificates, IDs, medical notes, and other files.</CardDescription>
              </div>
              <div v-if="canEditStudent" class="flex flex-wrap items-end gap-2">
                <div class="space-y-1">
                  <label for="student-document-type" class="text-xs font-medium text-muted-foreground">Type</label>
                  <select
                    id="student-document-type"
                    v-model="documentType"
                    class="flex h-9 w-full min-w-[10rem] rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                  >
                    <option
                      v-for="option in DOCUMENT_TYPE_OPTIONS"
                      :key="option.value"
                      :value="option.value"
                    >
                      {{ option.label }}
                    </option>
                  </select>
                </div>
                <Button
                  size="sm"
                  :disabled="uploadingDocuments"
                  :aria-busy="uploadingDocuments"
                  @click="pickDocuments"
                >
                  <Plus class="mr-1 h-4 w-4" aria-hidden="true" />
                  {{ uploadingDocuments ? 'Uploading…' : 'Upload' }}
                </Button>
                <input
                  ref="documentInput"
                  type="file"
                  multiple
                  accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,application/pdf,image/*"
                  class="sr-only"
                  @change="onDocumentsSelected"
                />
              </div>
            </CardHeader>
            <CardContent>
              <PageLoader v-if="documentsLoading" label="Loading documents" />
              <ErrorState
                v-else-if="documentsError"
                :description="documentsError"
                @retry="loadDocuments"
              />
              <Table v-else-if="documents.length">
                <TableHeader>
                  <TableRow>
                    <TableHead>Name</TableHead>
                    <TableHead>Type</TableHead>
                    <TableHead>Size</TableHead>
                    <TableHead>Uploaded</TableHead>
                    <TableHead class="w-28">
                      <span class="sr-only">Actions</span>
                    </TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow v-for="doc in documents" :key="doc.id">
                    <TableCell class="font-medium">{{ doc.name ?? '—' }}</TableCell>
                    <TableCell class="capitalize">{{ (doc.type ?? 'other').replaceAll('_', ' ') }}</TableCell>
                    <TableCell>{{ formatFileSize(doc.size) }}</TableCell>
                    <TableCell>{{ formatDate(doc.created_at) }}</TableCell>
                    <TableCell>
                      <div class="flex justify-end gap-2">
                        <Button
                          v-if="doc.url"
                          variant="outline"
                          size="sm"
                          as-child
                        >
                          <a :href="doc.url" target="_blank" rel="noopener noreferrer">
                            <Download class="h-4 w-4" aria-hidden="true" />
                            <span class="sr-only">Download {{ doc.name }}</span>
                          </a>
                        </Button>
                        <Button
                          v-if="canEditStudent"
                          variant="outline"
                          size="sm"
                          :disabled="deletingDocumentId === doc.id"
                          :aria-label="`Delete ${doc.name ?? 'document'}`"
                          @click="removeDocument(doc)"
                        >
                          <Trash2 class="h-4 w-4" aria-hidden="true" />
                        </Button>
                      </div>
                    </TableCell>
                  </TableRow>
                </TableBody>
              </Table>
              <p v-else class="text-sm text-muted-foreground">No documents uploaded yet.</p>
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

    <FormSheet
      ref="paymentFormSheetRef"
      v-model:open="paymentOpen"
      :title="feePaymentPromptForm.title"
      :description="feePaymentPromptForm.description"
      :fields="feePaymentPromptForm.fields"
      :schema="feePaymentPromptForm.schema"
      :reset-values="paymentFormResetValues"
      :form-key="`student-payment-${paymentTarget?.id ?? 'new'}`"
      :saving="saving"
      :save-label="feePaymentPromptForm.saveLabel ?? 'Record payment'"
      @submit="onRecordPayment"
    />

    <InvoicePrintSheet
      v-model:open="invoicePrintOpen"
      :document="invoicePrintData"
      :loading="invoicePrintLoading"
    />

    <StudentIdCardSheet
      v-model:open="idCardPrintOpen"
      :document="idCardPrintData"
      :loading="idCardPrintLoading"
    />

    <PaymentReceiptSheet
      v-model:open="receiptOpen"
      :receipt="receiptData"
      :loading="receiptLoading"
    />
  </div>
</template>
