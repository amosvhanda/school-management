<script setup lang="ts">
import { computed, nextTick, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import {
  Banknote,
  BookOpen,
  CalendarCheck2,
  CalendarDays,
  Camera,
  ChevronDown,
  ClipboardList,
  Download,
  FileText,
  Home,
  IdCard,
  Mail,
  MessageCircle,
  Pencil,
  Phone,
  Printer,
  QrCode,
  Shield,
  UserPlus,
  Users,
  Wallet,
} from '@lucide/vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import FormSheet from '@/components/forms/FormSheet.vue'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Button } from '@/components/ui/button'
import { useToast } from '@/composables/useToast'
import { useAuth } from '@/composables/useAuth'
import {
  getErrorMessage,
  getValidationErrors,
  messageFromBlobError,
  triggerBrowserDownload,
} from '@/lib/api-response'
import { resolveMediaUrl, withCacheBust } from '@/lib/media-url'
import { badgeVariantForStatus, STATUS_TONE_ICON } from '@/lib/ui-status'
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
import StudentInsightCard from '@/modules/students/components/StudentInsightCard.vue'
import StudentMetricRing from '@/modules/students/components/StudentMetricRing.vue'
import StudentProfileSideCards from '@/modules/students/components/StudentProfileSideCards.vue'
import StudentOverviewPanel from '@/modules/students/components/profile/StudentOverviewPanel.vue'
import StudentDocumentsPanel from '@/modules/students/components/profile/StudentDocumentsPanel.vue'
import StudentAcademicsPanel from '@/modules/students/components/profile/StudentAcademicsPanel.vue'
import StudentAttendancePanel from '@/modules/students/components/profile/StudentAttendancePanel.vue'
import StudentFeesPanel from '@/modules/students/components/profile/StudentFeesPanel.vue'
import StudentExamsPanel from '@/modules/students/components/profile/StudentExamsPanel.vue'
import StudentHealthPanel from '@/modules/students/components/profile/StudentHealthPanel.vue'
import StudentDisciplinePanel from '@/modules/students/components/profile/StudentDisciplinePanel.vue'
import StudentTimelinePanel from '@/modules/students/components/profile/StudentTimelinePanel.vue'
import StudentTimetablePanel from '@/modules/students/components/profile/StudentTimetablePanel.vue'
import StudentShellPanels from '@/modules/students/components/profile/StudentShellPanels.vue'
import InvoicePrintSheet from '@/modules/finance/components/InvoicePrintSheet.vue'
import StudentIdCardSheet from '@/modules/students/components/StudentIdCardSheet.vue'
import StudentProfileOverflowMenu from '@/modules/students/components/StudentProfileOverflowMenu.vue'
import PaymentReceiptSheet from '@/modules/finance/components/PaymentReceiptSheet.vue'
import { feePaymentPromptForm } from '@/modules/shared/action-prompt-forms'
import {
  STUDENT_PROFILE_TABS,
  profileTabTriggerClass,
  studentProfileMoreTabs,
  studentProfilePrimaryTabs,
  type StudentProfileTabId,
} from '@/modules/students/student-profile-tabs'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { cn } from '@/lib/utils'
import PageShell from '@/components/layout/PageShell.vue'

interface NamedRelation {
  id?: number
  name?: string
}

interface Student {
  id: number
  student_number?: string
  roll_number?: string | number | null
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
  class_model?: NamedRelation & { form?: string | null }
  stream?: NamedRelation & { code?: string | null }
  house?: NamedRelation
  grade_level?: NamedRelation
  student_category?: NamedRelation
  status?: string
  balance?: number
  currency?: string
  created_at?: string
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
const lifecycle = ref<Record<string, unknown> | null>(null)
const documentInput = ref<HTMLInputElement | null>(null)
const uploadingDocuments = ref(false)
const deletingDocumentId = ref<number | null>(null)
const documentType = ref('birth_certificate')
const initiatingOnlinePaymentId = ref<number | null>(null)
const profileTab = ref<StudentProfileTabId>(
  (typeof route.query.tab === 'string' &&
  STUDENT_PROFILE_TABS.some((t) => t.id === route.query.tab)
    ? route.query.tab
    : 'overview') as StudentProfileTabId,
)

function goToProfileTab(tab: StudentProfileTabId) {
  profileTab.value = tab
}

const primaryProfileTabs = studentProfilePrimaryTabs()
const moreProfileTabs = studentProfileMoreTabs()
const moreTabActive = computed(() => moreProfileTabs.some((tab) => tab.id === profileTab.value))

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

const profileDescription = computed(() => {
  const s = student.value
  if (!s) return 'Learner profile, attendance, fees, and documents.'
  const parts = [
    s.student_number ? `Adm. ${s.student_number}` : '',
    s.class_model?.name || s.class || '',
  ].filter(Boolean)
  return parts.join(' · ') || 'Learner profile, attendance, fees, and documents.'
})

const initials = computed(() =>
  displayName.value.split(' ').map((p) => p[0]).join('').slice(0, 2).toUpperCase(),
)

const photoPreviewUrl = ref<string | null>(null)
const photoCacheToken = ref(0)

const studentPhotoUrl = computed(() => {
  if (photoPreviewUrl.value) return photoPreviewUrl.value
  return withCacheBust(student.value?.photo_url, photoCacheToken.value)
})

function pickPhoto() {
  if (uploadingPhoto.value) return
  photoInput.value?.click()
}

function firstValidationMessage(err: unknown): string | null {
  const errors = getValidationErrors(err)
  const first = Object.values(errors)[0]
  return Array.isArray(first) && first[0] ? String(first[0]) : null
}

async function onPhotoSelected(event: Event) {
  if (!student.value?.id) return
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file) return

  if (!file.type.startsWith('image/')) {
    toast.warning('Invalid file', 'Please choose an image file (JPG, PNG, or WebP).')
    input.value = ''
    return
  }
  if (file.size > 5 * 1024 * 1024) {
    toast.warning('File too large', 'Choose an image under 5 MB.')
    input.value = ''
    return
  }

  const localPreview = URL.createObjectURL(file)
  photoPreviewUrl.value = localPreview
  uploadingPhoto.value = true
  try {
    const result = await studentsApi.uploadPhoto(student.value.id, file)
    const nextUrl = resolveMediaUrl(result.photo_url) ?? result.photo_url
    photoCacheToken.value = Date.now()
    if (student.value) {
      const fromServer = (result.student ?? {}) as Partial<Student>
      student.value = {
        ...student.value,
        ...fromServer,
        photo_url: nextUrl,
      }
    }
    toast.success('Student photo updated')
  } catch (err) {
    toast.error(
      'Could not upload photo',
      firstValidationMessage(err) ?? getErrorMessage(err, 'Upload failed. Try a smaller JPG or PNG.'),
    )
  } finally {
    uploadingPhoto.value = false
    if (photoPreviewUrl.value === localPreview) {
      photoPreviewUrl.value = null
      URL.revokeObjectURL(localPreview)
    }
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

const classLabel = computed(() => {
  const s = student.value
  if (!s) return '—'
  const className = s.class_model?.name || s.class || ''
  const stream = s.stream?.name || s.stream?.code || ''
  return [className, stream].filter(Boolean).join(' · ') || 'Unassigned'
})

const houseName = computed(() => student.value?.house?.name ?? '—')
const categoryName = computed(() => student.value?.student_category?.name ?? '')
const gradeLevelName = computed(() => student.value?.grade_level?.name ?? '—')
const rollNumber = computed(() => {
  const roll = student.value?.roll_number
  return roll != null && String(roll).trim() !== '' ? String(roll) : '—'
})

const ageYears = computed(() => {
  const dob = student.value?.date_of_birth
  if (!dob) return null
  const birth = new Date(dob)
  if (Number.isNaN(birth.getTime())) return null
  const today = new Date()
  let age = today.getFullYear() - birth.getFullYear()
  const m = today.getMonth() - birth.getMonth()
  if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) age -= 1
  return age >= 0 && age < 120 ? age : null
})

const attendanceStats = computed(() => {
  const rows = performance.value?.attendance ?? []
  if (!rows.length) return { percent: 0, present: 0, total: 0, label: '—' }
  const present = rows.filter((r) => {
    const status = String(r.status ?? '').toLowerCase()
    return status === 'present' || status === 'late' || status === 'half_day'
  }).length
  const percent = Math.round((present / rows.length) * 100)
  return { percent, present, total: rows.length, label: `${percent}%` }
})

const academicScore = computed(() => {
  const avg = performance.value?.average_score
  if (avg == null || Number.isNaN(Number(avg))) return null
  return Number(avg)
})

const academicLabel = computed(() =>
  academicScore.value != null ? academicScore.value.toFixed(1) : '—',
)

const perfIndex = computed(() => {
  if (academicScore.value == null) return attendanceStats.value.percent || 0
  return Math.round((academicScore.value + attendanceStats.value.percent) / 2)
})

const profileCompletion = computed(() => {
  const s = student.value
  if (!s) return 0
  const checks = [
    Boolean(s.first_name),
    Boolean(s.last_name),
    Boolean(s.date_of_birth),
    Boolean(s.gender),
    Boolean(s.phone || s.email),
    Boolean(s.address || s.suburb),
    Boolean(s.class || s.class_model),
    Boolean(s.photo_url),
    Boolean(guardianName.value),
    Boolean(s.student_number),
  ]
  return Math.round((checks.filter(Boolean).length / checks.length) * 100)
})

const feeBalance = computed(() => Number(student.value?.balance ?? 0))
const feePaid = computed(() => feeBalance.value <= 0)
const feeHint = computed(() => {
  if (feePaid.value) return 'All clear'
  return `Due · ${formatMoney(feeBalance.value, student.value?.currency)}`
})

const statusIsActive = computed(() => String(student.value?.status ?? '').toLowerCase() === 'active')

const featureTags = computed(() => {
  const tags: Array<{ label: string; class: string }> = []
  if (categoryName.value) {
    tags.push({
      label: categoryName.value,
      class: STATUS_TONE_ICON.accent,
    })
  }
  if (feePaid.value) {
    tags.push({
      label: 'Fees clear',
      class: STATUS_TONE_ICON.success,
    })
  } else if (feeBalance.value > 0) {
    tags.push({
      label: 'Fee pending',
      class: STATUS_TONE_ICON.danger,
    })
  }
  if (linkedGuardians.value.length) {
    tags.push({
      label: 'Parent portal',
      class: STATUS_TONE_ICON.info,
    })
  }
  return tags
})

const insightCards = computed(() => [
  {
    title: 'Attendance',
    value: attendanceStats.value.label,
    hint: attendanceStats.value.total
      ? `${attendanceStats.value.present}/${attendanceStats.value.total} recent`
      : 'No records yet',
    icon: CalendarDays,
    tone: 'success' as const,
  },
  {
    title: 'Academic',
    value: academicLabel.value === '—' ? '—' : `${academicLabel.value}%`,
    hint: performance.value?.total_grades
      ? `${performance.value.total_grades} grades`
      : 'No grades yet',
    icon: BookOpen,
    tone: 'info' as const,
  },
  {
    title: 'Behavior',
    value: statusIsActive.value ? 'Good' : 'Review',
    hint: 'Conduct summary',
    icon: Shield,
    tone: statusIsActive.value ? 'success' as const : 'warning' as const,
  },
  {
    title: 'Fee status',
    value: feePaid.value ? 'Paid' : 'Pending',
    hint: feeHint.value,
    hintTone: feePaid.value ? 'success' as const : 'danger' as const,
    icon: Banknote,
    tone: feePaid.value ? 'success' as const : 'danger' as const,
  },
  {
    title: 'Guardians',
    value: String(linkedGuardians.value.length || 0),
    hint: guardianName.value ?? 'None linked',
    icon: Users,
    tone: 'default' as const,
  },
  {
    title: 'Documents',
    value: String(documents.value.length),
    hint: documents.value.length ? 'On file' : 'None uploaded',
    icon: FileText,
    tone: 'info' as const,
  },
  {
    title: 'House',
    value: houseName.value,
    hint: 'House allocation',
    icon: Home,
    tone: 'warning' as const,
  },
  {
    title: 'Class',
    value: classLabel.value,
    hint: gradeLevelName.value !== '—' ? gradeLevelName.value : 'Current class',
    icon: ClipboardList,
    tone: 'default' as const,
  },
])

function printPage() {
  document.body.classList.add('printing-student-profile')
  const cleanup = () => {
    document.body.classList.remove('printing-student-profile')
    window.removeEventListener('afterprint', cleanup)
  }
  window.addEventListener('afterprint', cleanup)
  window.setTimeout(cleanup, 1500)
  window.print()
}

function buildLocalIdCardPayload(s: Student): Record<string, unknown> {
  const className =
    s.class_model?.name
    ?? s.class
    ?? null
  const padded = String(s.id).padStart(6, '0')
  return {
    document_number: `ID-${padded}`,
    issued_at: new Date().toISOString(),
    student: {
      id: s.id,
      full_name:
        s.full_name
        || [s.first_name, s.last_name].filter(Boolean).join(' ')
        || 'Student',
      student_number: s.student_number ?? null,
      class: className,
      grade_level: s.grade_level?.name ?? null,
      house: s.house?.name ?? null,
      date_of_birth: s.date_of_birth ?? null,
      gender: s.gender ?? null,
      photo_url: s.photo_url ?? null,
    },
    school: {
      name: 'School',
      motto: null,
      primary_color: '#3D5EE1',
    },
  }
}

async function openInvoicePrint(invoiceId: number) {
  invoicePrintOpen.value = true
  invoicePrintLoading.value = true
  invoicePrintData.value = null
  try {
    invoicePrintData.value = (await financeApi.invoices.print(invoiceId)) as Record<string, unknown>
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
    idCardPrintData.value = (await studentsApi.printIdCard(student.value.id)) as Record<string, unknown>
  } catch (err) {
    // Fall back to the already-loaded profile so staff can still print an ID card offline.
    idCardPrintData.value = buildLocalIdCardPayload(student.value)
    toast.error('Using profile data', getErrorMessage(err, 'Could not load branded ID card from the server.'))
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
      instructions?: string | null
      transaction?: { internal_reference?: string }
    }

    if (result.checkout_url) {
      window.location.assign(result.checkout_url)
      return
    }

    toast.success(
      'Payment initiated',
      result.instructions
        ?? (result.transaction?.internal_reference
          ? `Reference ${result.transaction.internal_reference}`
          : 'Awaiting gateway confirmation.'),
    )
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
        receiptData.value = (await financeApi.payments.receipt(payment.id)) as Record<string, unknown>
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
  return badgeVariantForStatus(status)
}

async function downloadResults() {
  downloading.value = true
  try {
    const blob = await studentsApi.downloadResults(id, 'html')
    const safeName = String(student.value?.full_name ?? student.value?.student_number ?? 'student')
      .replace(/[^A-Za-z0-9_-]+/g, '_')
      .replace(/^_+|_+$/g, '') || 'student'
    triggerBrowserDownload(blob, `${safeName}_report_card.html`)
    toast.success('Report card downloaded')
  } catch (err) {
    toast.error('Download failed', await messageFromBlobError(err, 'Could not download report card.'))
  } finally {
    downloading.value = false
  }
}

async function load() {
  loading.value = true
  error.value = null
  try {
    const loaded = await studentsApi.get(id) as Student
    student.value = {
      ...loaded,
      photo_url: resolveMediaUrl(loaded.photo_url) ?? loaded.photo_url,
    }
    photoCacheToken.value = Date.now()
    performance.value = await studentsApi.performance(id) as PerformanceData
    invoices.value = await studentsApi.invoices(id) as Invoice[]
    void loadLifecycle()
    void loadDocuments()
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load student')
  } finally {
    loading.value = false
  }
}

async function loadLifecycle() {
  try {
    lifecycle.value = await studentsApi.lifecycle(id) as Record<string, unknown>
  } catch {
    lifecycle.value = null
  }
}

const primaryTransport = computed(() => {
  const rows = lifecycle.value?.transport
  if (!Array.isArray(rows) || !rows.length) return null
  return (rows[0] ?? null) as Record<string, unknown> | null
})

const hostelAllocation = computed(() => {
  const row = lifecycle.value?.hostel
  return row && typeof row === 'object' ? (row as Record<string, unknown>) : null
})

const subjectPackage = computed(() => {
  const placement = lifecycle.value?.placement as Record<string, unknown> | undefined
  const pkg = placement?.subject_package
  if (!Array.isArray(pkg)) return [] as Array<{ subject?: string; is_core?: boolean; code?: string }>
  return pkg as Array<{ subject?: string; is_core?: boolean; code?: string }>
})

const coreSubjects = computed(() =>
  subjectPackage.value.filter((s) => s.is_core && s.subject).map((s) => String(s.subject)),
)

const electiveSubjects = computed(() =>
  subjectPackage.value.filter((s) => !s.is_core && s.subject).map((s) => String(s.subject)),
)

const gradeSubjects = computed(() => {
  const grades = performance.value?.grades ?? []
  const names = [...new Set(grades.map((g) => String(g.subject ?? '').trim()).filter(Boolean))]
  return names
})

const displayCoreSubjects = computed(() =>
  coreSubjects.value.length ? coreSubjects.value : gradeSubjects.value,
)

const overviewFields = computed(() => ({
  admissionNo: student.value?.student_number,
  rollNo: rollNumber.value,
  classLabel: classLabel.value,
  gradeLevel: gradeLevelName.value,
  house: houseName.value,
  category: categoryName.value,
  fullName: displayName.value,
  dateOfBirth: student.value?.date_of_birth,
  ageLabel: ageYears.value != null ? `${ageYears.value} yrs` : '—',
  gender: student.value?.gender,
  phone: student.value?.phone,
  email: student.value?.email,
  address: [student.value?.address, student.value?.suburb].filter(Boolean).join(', ') || '—',
  status: student.value?.status,
  joined: student.value?.created_at ? formatDate(student.value.created_at) : '—',
  balanceLabel: formatMoney(student.value?.balance, student.value?.currency),
  batch: null as string | null,
  academicYear: null as string | null,
  classTeacher: null as string | null,
}))

const timelineEvents = computed(() => {
  const events: Array<{
    id: string
    date?: string | null
    title: string
    description?: string
    tone: 'success' | 'warning' | 'info' | 'danger' | 'neutral' | 'accent'
    icon: typeof FileText
  }> = []

  if (student.value?.created_at) {
    events.push({
      id: 'admission',
      date: student.value.created_at,
      title: 'Admission completed',
      description: `${displayName.value} enrolled${classLabel.value !== 'Unassigned' ? ` in ${classLabel.value}` : ''}.`,
      tone: 'accent',
      icon: UserPlus,
    })
  }

  for (const doc of documents.value.slice(0, 5)) {
    events.push({
      id: `doc-${doc.id}`,
      date: doc.created_at,
      title: 'Document uploaded',
      description: doc.name ?? String(doc.type ?? 'File'),
      tone: 'info',
      icon: FileText,
    })
  }

  for (const inv of invoices.value.slice(0, 5)) {
    events.push({
      id: `inv-${inv.id}`,
      date: inv.due_date,
      title: 'Fee invoice generated',
      description: `${inv.invoice_number ?? `#${inv.id}`} · ${formatMoney(inv.amount ?? inv.total, inv.currency)}`,
      tone: 'warning',
      icon: Wallet,
    })
  }

  for (const [i, grade] of (performance.value?.grades ?? []).slice(0, 5).entries()) {
    events.push({
      id: `grade-${i}`,
      date: null,
      title: 'Grade recorded',
      description: `${grade.subject ?? 'Subject'} — ${grade.score ?? '—'} (${grade.grade ?? '—'})`,
      tone: 'success',
      icon: ClipboardList,
    })
  }

  for (const [i, row] of (performance.value?.attendance ?? []).slice(0, 3).entries()) {
    if (String(row.status ?? '').toLowerCase() !== 'present') continue
    events.push({
      id: `att-${i}`,
      date: row.date,
      title: 'Attendance marked',
      description: `Status: ${row.status}`,
      tone: 'success',
      icon: CalendarCheck2,
    })
  }

  return events
})

const classId = computed(() => student.value?.class_model?.id ?? null)

async function loadDocuments() {
  documentsLoading.value = true
  documentsError.value = null
  try {
    const rows = await studentsApi.documents(id) as StudentDocumentRow[]
    documents.value = rows.map((row) => ({
      ...row,
      url: resolveMediaUrl(row.url) ?? row.url,
    }))
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

  const maxBytes = 10 * 1024 * 1024
  const tooLarge = files.find((file) => file.size > maxBytes)
  if (tooLarge) {
    toast.warning('File too large', `"${tooLarge.name}" exceeds the 10 MB limit.`)
    input.value = ''
    return
  }

  uploadingDocuments.value = true
  try {
    await studentsApi.uploadDocuments(id, files, documentType.value)
    toast.success(files.length === 1 ? 'Document uploaded' : `${files.length} documents uploaded`)
    await loadDocuments()
  } catch (err) {
    toast.error(
      'Upload failed',
      firstValidationMessage(err) ?? getErrorMessage(err, 'Could not upload document(s).'),
    )
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
    // StudentController::createInvoice expects camelCase dueDate + numeric relation ids.
    const dueDate = String(values.dueDate ?? '').trim().slice(0, 10)
    const payload: Record<string, unknown> = {
      dueDate,
      apply_discounts: values.apply_discounts !== false,
      combine_group: values.combine_group === true,
    }
    if (values.fee_group_id) {
      payload.fee_group_id = Number(values.fee_group_id)
      if (values.description) payload.description = String(values.description).trim()
    } else {
      payload.description = values.description ? String(values.description).trim() : undefined
      payload.amount = values.amount != null && values.amount !== '' ? Number(values.amount) : undefined
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
  <PageShell
    data-student-profile-print
    root-class="print:max-w-none"
    title="Student profile"
    :description="profileDescription"
    :breadcrumbs="['Students', 'Profile']"
    back-to="/students"
    back-label="Back to students"
  >
    <template
      v-if="student && !loading"
      #actions
    >
      <div class="flex flex-wrap items-center gap-2 print:hidden">
        <Button type="button" v-if="canEditStudent" class="rounded-xl" @click="openEdit">
          <Pencil class="mr-1.5 size-4" aria-hidden="true" />
          Edit Student
        </Button>
        <Button type="button" variant="outline" class="rounded-xl" @click="printPage">
          <Printer class="mr-1.5 size-4" aria-hidden="true" />
          Print
        </Button>
        <Button
          type="button"
          variant="outline"
          class="rounded-xl"
          :disabled="downloading"
          :aria-busy="downloading"
          @click="downloadResults"
        >
          <Download class="mr-1.5 size-4" aria-hidden="true" />
          {{ downloading ? 'Downloading…' : 'Download' }}
        </Button>
        <Button
          type="button"
          variant="outline"
          class="rounded-xl"
          :disabled="idCardPrintLoading"
          :aria-busy="idCardPrintLoading"
          @click="openIdCardPrint"
        >
          <IdCard class="mr-1.5 size-4" aria-hidden="true" />
          {{ idCardPrintLoading ? 'Loading…' : 'ID Card' }}
        </Button>
        <StudentProfileOverflowMenu
          :student-id="id"
          :student-name="displayName"
          :student-email="student.email"
          :admission-no="student.student_number"
          :can-manage="canEditStudent"
          @navigate="goToProfileTab"
          @refreshed="load"
        />
      </div>
    </template>

    <PageLoader v-if="loading" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />

    <template v-else-if="student">
      <!-- Hero -->
      <section
        class="rounded-2xl bg-card shadow-[var(--shadow-soft)] ring-1 ring-border/70 dark:ring-border/50"
        aria-label="Student summary"
      >
        <div class="flex flex-col gap-6 p-5 xl:flex-row xl:items-start xl:p-6">
          <div class="flex min-w-0 flex-1 flex-col gap-4 sm:flex-row sm:items-start">
            <div class="relative shrink-0">
              <Avatar class="size-24 rounded-2xl shadow-sm ring-2 ring-background sm:size-28">
                <AvatarImage
                  v-if="studentPhotoUrl"
                  :key="studentPhotoUrl"
                  :src="studentPhotoUrl"
                  :alt="displayName"
                  class="rounded-2xl object-cover"
                />
                <AvatarFallback class="rounded-2xl bg-primary/10 text-2xl font-semibold text-primary">
                  {{ initials }}
                </AvatarFallback>
              </Avatar>
              <span
                class="absolute bottom-1 left-1 size-3.5 rounded-full border-2 border-card"
                :class="statusIsActive ? 'bg-chart-2' : 'bg-muted-foreground'"
                :title="student.status ?? 'status'"
                aria-hidden="true"
              />
              <button
                v-if="canEditStudent"
                type="button"
                class="absolute bottom-1 right-1 flex size-8 items-center justify-center rounded-full border border-border bg-background/95 text-muted-foreground shadow-sm backdrop-blur-sm transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:opacity-60 print:hidden"
                :disabled="uploadingPhoto"
                :aria-busy="uploadingPhoto"
                :aria-label="uploadingPhoto ? 'Uploading photo' : 'Change student photo'"
                @click="pickPhoto"
              >
                <Camera class="size-3.5" aria-hidden="true" />
              </button>
              <span
                v-if="uploadingPhoto"
                class="absolute inset-0 flex items-center justify-center rounded-2xl bg-background/55 text-xs font-medium text-foreground backdrop-blur-[1px]"
                role="status"
              >
                Uploading…
              </span>
              <input
                ref="photoInput"
                type="file"
                accept="image/jpeg,image/png,image/webp,image/gif,.jpg,.jpeg,.png,.webp,.gif"
                class="sr-only"
                tabindex="-1"
                @change="onPhotoSelected"
              />
            </div>

            <div class="min-w-0 flex-1 space-y-3">
              <div class="flex flex-wrap items-center gap-2">
                <h2 class="truncate text-xl font-semibold tracking-tight sm:text-2xl">{{ displayName }}</h2>
                <Badge
                  :variant="statusIsActive ? 'success' : statusVariant(student.status)"
                  class="capitalize"
                >
                  {{ student.status ?? 'unknown' }}
                </Badge>
              </div>
              <dl class="grid min-w-0 grid-cols-1 gap-x-4 gap-y-2 text-sm text-muted-foreground sm:grid-cols-2">
                <div class="min-w-0 space-y-0.5 sm:col-span-2">
                  <dt class="text-xs font-medium text-foreground/70">Admission #</dt>
                  <dd class="break-words font-medium tabular-nums text-foreground">
                    {{ student.student_number ?? '—' }}
                  </dd>
                </div>
                <div class="min-w-0 space-y-0.5">
                  <dt class="text-xs font-medium text-foreground/70">Roll #</dt>
                  <dd class="font-medium tabular-nums text-foreground">{{ rollNumber }}</dd>
                </div>
                <div class="min-w-0 space-y-0.5 sm:col-span-2">
                  <dt class="text-xs font-medium text-foreground/70">Class</dt>
                  <dd class="break-words font-medium text-foreground">{{ classLabel }}</dd>
                </div>
                <div class="min-w-0 space-y-0.5">
                  <dt class="text-xs font-medium text-foreground/70">House</dt>
                  <dd class="break-words font-medium text-foreground">{{ houseName }}</dd>
                </div>
              </dl>
              <div v-if="featureTags.length" class="flex flex-wrap gap-1.5">
                <span
                  v-for="tag in featureTags"
                  :key="tag.label"
                  :class="cn('inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold', tag.class)"
                >
                  {{ tag.label }}
                </span>
              </div>
              <div class="space-y-1.5 pt-1">
                <div class="flex items-center justify-between gap-2 text-xs">
                  <span class="font-medium text-muted-foreground">Profile completion</span>
                  <span class="font-semibold tabular-nums text-chart-2">{{ profileCompletion }}%</span>
                </div>
                <div
                  class="h-2 overflow-hidden rounded-full bg-muted"
                  role="progressbar"
                  :aria-valuenow="profileCompletion"
                  aria-valuemin="0"
                  aria-valuemax="100"
                  :aria-label="`Profile ${profileCompletion}% complete`"
                >
                  <div
                    class="h-full rounded-full bg-chart-2 transition-all duration-500"
                    :style="{ width: `${profileCompletion}%` }"
                  />
                </div>
              </div>
            </div>
          </div>

          <div class="flex w-full shrink-0 flex-col items-stretch gap-4 sm:flex-row sm:items-center sm:justify-between xl:w-auto xl:flex-col xl:items-end">
          <div class="flex flex-wrap items-center justify-center gap-4 sm:gap-6 xl:justify-end">
            <StudentMetricRing
              label="Attendance"
              :value-label="attendanceStats.label"
              :percent="attendanceStats.percent || (attendanceStats.total ? 0 : 0)"
              tone="success"
            />
            <StudentMetricRing
              label="Academic"
              :value-label="academicLabel"
              :percent="academicScore ?? 0"
              tone="primary"
            />
            <StudentMetricRing
              label="Perf. Index"
              :value-label="String(perfIndex || '—')"
              :percent="perfIndex"
              tone="warning"
            />
          </div>

          <div class="flex flex-row items-center justify-center gap-3 sm:justify-end xl:flex-col xl:items-end">
            <div
              class="flex size-20 items-center justify-center rounded-2xl bg-muted/50 text-muted-foreground ring-1 ring-border/50"
              aria-hidden="true"
              title="Student QR"
            >
              <QrCode class="size-10 opacity-70" />
            </div>
            <div class="flex gap-1.5 print:hidden">
              <Button
                v-if="student.phone"
                variant="outline"
                size="icon"
                class="size-9 rounded-full"
                as-child
              >
                <a :href="`tel:${student.phone}`" :aria-label="`Call ${displayName}`">
                  <Phone class="size-4" />
                </a>
              </Button>
              <Button
                v-if="student.phone"
                variant="outline"
                size="icon"
                class="size-9 rounded-full"
                as-child
              >
                <a
                  :href="`https://wa.me/${String(student.phone).replace(/\D/g, '')}`"
                  target="_blank"
                  rel="noopener noreferrer"
                  :aria-label="`WhatsApp ${displayName}`"
                >
                  <MessageCircle class="size-4" />
                </a>
              </Button>
              <Button
                v-if="student.email"
                variant="outline"
                size="icon"
                class="size-9 rounded-full"
                as-child
              >
                <a :href="`mailto:${student.email}`" :aria-label="`Email ${displayName}`">
                  <Mail class="size-4" />
                </a>
              </Button>
            </div>
          </div>
          </div>
        </div>
      </section>

      <!-- Insight strip -->
      <section
        class="flex gap-3 overflow-x-auto pb-1 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
        aria-label="Student insights"
      >
        <StudentInsightCard
          v-for="card in insightCards"
          :key="card.title"
          :title="card.title"
          :value="card.value"
          :hint="card.hint"
          :icon="card.icon"
          :tone="card.tone"
          :hint-tone="card.hintTone"
        />
      </section>

      <!-- Side cards + tabbed workspace -->
      <div class="grid gap-6 xl:grid-cols-[minmax(0,20rem)_minmax(0,1fr)] 2xl:grid-cols-[minmax(0,22rem)_minmax(0,1fr)]">
        <div class="xl:sticky xl:top-24 xl:self-start">
          <StudentProfileSideCards
            :guardians="linkedGuardians as any"
            :transport="primaryTransport"
            :hostel="hostelAllocation"
          />
        </div>

        <div class="min-w-0 space-y-6">
          <input
            ref="documentInput"
            type="file"
            multiple
            accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,application/pdf,image/*"
            class="sr-only"
            @change="onDocumentsSelected"
          />

          <Tabs v-model="profileTab">
            <TabsList
              class="!h-auto !min-h-0 flex w-full flex-wrap content-start items-center justify-start gap-x-1.5 gap-y-2 overflow-visible rounded-full bg-card p-1.5 shadow-[var(--shadow-soft)] ring-1 ring-border/70 group-data-horizontal/tabs:!h-auto dark:ring-border/50"
              aria-label="Student profile sections"
            >
              <TabsTrigger
                v-for="tab in primaryProfileTabs"
                :key="tab.id"
                :value="tab.id"
                :class="profileTabTriggerClass"
              >
                <component :is="tab.icon" class="size-3.5 shrink-0" aria-hidden="true" />
                <span class="whitespace-nowrap">{{ tab.label }}</span>
              </TabsTrigger>
              <DropdownMenu v-if="moreProfileTabs.length">
                <DropdownMenuTrigger as-child>
                  <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    :class="cn(profileTabTriggerClass, moreTabActive && 'border-primary/40 bg-primary/10 text-primary')"
                    aria-label="More profile sections"
                  >
                    <span class="whitespace-nowrap">More</span>
                    <ChevronDown class="size-3.5 shrink-0" aria-hidden="true" />
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="start" class="max-h-80 overflow-y-auto">
                  <DropdownMenuItem
                    v-for="tab in moreProfileTabs"
                    :key="tab.id"
                    :disabled="tab.locked"
                    @select="goToProfileTab(tab.id)"
                  >
                    <component :is="tab.icon" class="mr-2 size-4 shrink-0" aria-hidden="true" />
                    {{ tab.label }}
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            </TabsList>

            <TabsContent value="overview" class="mt-4">
              <StudentOverviewPanel
                :fields="overviewFields"
                :core-subjects="displayCoreSubjects"
                :elective-subjects="electiveSubjects"
              />
            </TabsContent>

            <TabsContent value="attendance" class="mt-4">
              <StudentAttendancePanel
                :student-id="id"
                :recent="performance?.attendance"
              />
            </TabsContent>

            <TabsContent value="academics" class="mt-4">
              <StudentAcademicsPanel
                :average-score="performance?.average_score"
                :total-grades="performance?.total_grades"
                :grades="performance?.grades"
              />
            </TabsContent>

            <TabsContent value="exams" class="mt-4">
              <StudentExamsPanel
                :student-id="id"
                :average-score="performance?.average_score"
                :grades="performance?.grades"
                :downloading="downloading"
                @download-report="downloadResults"
                @print="downloadResults"
              />
            </TabsContent>

            <TabsContent value="fees" class="mt-4">
              <StudentFeesPanel
                :invoices="invoices"
                :currency="student.currency"
                :can-manage="canCreateInvoice"
                :initiating-payment-id="initiatingOnlinePaymentId"
                @create="openInvoice"
                @pay="payOnline"
                @record="openRecordPayment"
                @print="openInvoicePrint"
              />
            </TabsContent>

            <TabsContent value="timetable" class="mt-4">
              <StudentTimetablePanel
                :class-id="classId"
                :class-label="classLabel"
              />
            </TabsContent>

            <TabsContent value="health" class="mt-4">
              <StudentHealthPanel
                :student-id="id"
                :emergency-name="guardianName"
                :emergency-phone="guardianPhone"
              />
            </TabsContent>

            <TabsContent value="library" class="mt-4">
              <StudentShellPanels panel="library" :student-id="id" :student-name="displayName" />
            </TabsContent>

            <TabsContent value="documents" class="mt-4">
              <StudentDocumentsPanel
                :documents="documents"
                :loading="documentsLoading"
                :error="documentsError"
                :can-manage="canEditStudent"
                :uploading="uploadingDocuments"
                :deleting-id="deletingDocumentId"
                :document-type="documentType"
                :type-options="DOCUMENT_TYPE_OPTIONS"
                @update:document-type="documentType = $event"
                @retry="loadDocuments"
                @upload="pickDocuments"
                @remove="removeDocument"
              />
            </TabsContent>

            <TabsContent value="timeline" class="mt-4">
              <StudentTimelinePanel :events="timelineEvents" />
            </TabsContent>

            <TabsContent value="achievements" class="mt-4">
              <StudentShellPanels panel="achievements" :student-id="id" :student-name="displayName" />
            </TabsContent>

            <TabsContent value="discipline" class="mt-4">
              <StudentDisciplinePanel :student-id="id" />
            </TabsContent>

            <TabsContent value="communication" class="mt-4">
              <StudentShellPanels panel="communication" :student-id="id" :student-name="displayName" />
            </TabsContent>

            <TabsContent value="notes" class="mt-4">
              <StudentShellPanels panel="notes" :student-id="id" :student-name="displayName" />
            </TabsContent>

            <TabsContent value="audit" class="mt-4">
              <StudentShellPanels panel="audit" :student-id="id" :student-name="displayName" />
            </TabsContent>
          </Tabs>
        </div>
      </div>
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
  </PageShell>
</template>
