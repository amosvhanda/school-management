<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { ArrowLeft, Plus, Save, Upload } from '@lucide/vue'
import PageShell from '@/components/layout/PageShell.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'
import { useAcademicYearFilter } from '@/composables/useAcademicYearFilter'
import { useToast } from '@/composables/useToast'
import { getErrorMessage, getValidationErrors } from '@/lib/api-response'
import { PAGE_STACK } from '@/lib/design-system'
import { cn } from '@/lib/utils'
import { academicStructureApi, operationsApi, studentsApi } from '@/services/api.service'
import { fetchList, moduleEndpoints } from '@/services'

const SECTIONS = [
  { id: 'personal', label: 'Personal Information' },
  { id: 'parents', label: 'Parents & Guardian' },
  { id: 'siblings', label: 'Siblings' },
  { id: 'address', label: 'Address' },
  { id: 'transport', label: 'Transport' },
  { id: 'hostel', label: 'Hostel' },
  { id: 'documents', label: 'Documents' },
  { id: 'medical', label: 'Medical History' },
  { id: 'previous', label: 'Previous School' },
  { id: 'other', label: 'Other Details' },
] as const

const BLOOD_GROUPS = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']
const RELIGIONS = ['Christian', 'Muslim', 'Hindu', 'Buddhist', 'Jewish', 'Traditional', 'Other', 'Prefer not to say']
const MOTHER_TONGUES = ['English', 'Shona', 'Ndebele', 'Tonga', 'Venda', 'Kalanga', 'Other']

const router = useRouter()
const toast = useToast()
const { academicYear, options: yearOptions, setAcademicYear } = useAcademicYearFilter()
const activeSection = ref<string>('personal')
const saving = ref(false)
const photoFile = ref<File | null>(null)
const photoPreview = ref<string | null>(null)
const fatherPhotoPreview = ref<string | null>(null)
const motherPhotoPreview = ref<string | null>(null)
const photoInput = ref<HTMLInputElement | null>(null)
const fatherPhotoInput = ref<HTMLInputElement | null>(null)
const motherPhotoInput = ref<HTMLInputElement | null>(null)
const documentFiles = ref<File[]>([])

const classes = ref<Array<{ id: number; name: string }>>([])
const streams = ref<Array<{ id: number; name: string }>>([])
const houses = ref<Array<{ id: number; name: string }>>([])
const categories = ref<Array<{ id: number; name: string }>>([])

const form = ref({
  academic_year: academicYear.value,
  student_number: '',
  admission_date: new Date().toISOString().slice(0, 10),
  roll_number: '',
  status: 'active',
  first_name: '',
  last_name: '',
  class_id: '',
  stream_id: 'none',
  gender: '',
  date_of_birth: '',
  blood_group: '',
  house_id: 'none',
  religion: '',
  student_category_id: 'none',
  phone: '',
  email: '',
  caste: '',
  mother_tongue: '',
  languages_known: '',
  father_name: '',
  father_phone: '',
  father_occupation: '',
  father_email: '',
  mother_name: '',
  mother_phone: '',
  mother_occupation: '',
  mother_email: '',
  guardian_first_name: '',
  guardian_last_name: '',
  guardian_phone: '',
  guardian_email: '',
  guardian_relationship: 'parent',
  address: '',
  suburb: '',
  previous_school: '',
  transport_notes: '',
  hostel_notes: '',
  medical_notes: '',
  allergies: '',
  siblings_text: '',
  notes: '',
})

const fieldClass = 'rounded-xl'
const labelClass = 'text-sm font-medium text-foreground'

const canSubmit = computed(() =>
  Boolean(form.value.first_name.trim() && form.value.last_name.trim() && form.value.class_id),
)

/** Draft only needs identity — class can be set later when activating. */
const canSaveDraft = computed(() =>
  Boolean(form.value.first_name.trim() && form.value.last_name.trim()),
)

const missingRequired = computed(() => {
  const missing: string[] = []
  if (!form.value.first_name.trim()) missing.push('First Name')
  if (!form.value.last_name.trim()) missing.push('Last Name')
  if (!form.value.class_id) missing.push('Class')
  return missing
})

const missingDraftRequired = computed(() => {
  const missing: string[] = []
  if (!form.value.first_name.trim()) missing.push('First Name')
  if (!form.value.last_name.trim()) missing.push('Last Name')
  return missing
})

async function loadOptions() {
  const [classRows, streamRows, houseRows, catRows] = await Promise.all([
    fetchList(moduleEndpoints.classes, { all: true }).catch(() => []),
    academicStructureApi.streams.list({ all: true }).catch(() => []),
    academicStructureApi.houses.list({ all: true }).catch(() => []),
    studentsApi.categories.list({ all: true } as never).catch(() => []),
  ])
  classes.value = classRows as typeof classes.value
  streams.value = streamRows as typeof streams.value
  houses.value = houseRows as typeof houses.value
  categories.value = catRows as typeof categories.value
}

function scrollTo(id: string) {
  activeSection.value = id
  document.getElementById(`section-${id}`)?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

function revokePreview(url: string | null) {
  if (url?.startsWith('blob:')) URL.revokeObjectURL(url)
}

function setPhotoFromInput(event: Event, kind: 'student' | 'father' | 'mother') {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0] ?? null
  if (file && file.size > 4 * 1024 * 1024) {
    toast.warning('File too large', 'Image must be 4MB or smaller.')
    input.value = ''
    return
  }
  if (kind === 'student') {
    revokePreview(photoPreview.value)
    photoFile.value = file
    photoPreview.value = file ? URL.createObjectURL(file) : null
  } else if (kind === 'father') {
    revokePreview(fatherPhotoPreview.value)
    fatherPhotoPreview.value = file ? URL.createObjectURL(file) : null
  } else {
    revokePreview(motherPhotoPreview.value)
    motherPhotoPreview.value = file ? URL.createObjectURL(file) : null
  }
}

function clearStudentPhoto() {
  revokePreview(photoPreview.value)
  photoFile.value = null
  photoPreview.value = null
  if (photoInput.value) photoInput.value.value = ''
}

function onDocs(event: Event) {
  const input = event.target as HTMLInputElement
  documentFiles.value = Array.from(input.files ?? [])
}

function buildPayload(asDraft: boolean) {
  const f = form.value
  const classId = f.class_id ? Number(f.class_id) : undefined
  const streamId = f.stream_id && f.stream_id !== 'none' ? Number(f.stream_id) : undefined
  const houseId = f.house_id && f.house_id !== 'none' ? Number(f.house_id) : undefined
  const categoryId =
    f.student_category_id && f.student_category_id !== 'none'
      ? Number(f.student_category_id)
      : undefined

  const gFirst = (f.guardian_first_name || f.father_name || '').trim()
  const gLast = (f.guardian_last_name || '').trim()
  const gPhone = (f.guardian_phone || f.father_phone || '').trim()
  const gEmail = (f.guardian_email || f.father_email || '').trim()
  const gRel = (f.guardian_relationship || '').trim()

  const payload: Record<string, unknown> = {
    firstName: f.first_name.trim(),
    surname: f.last_name.trim(),
    status: asDraft ? 'inactive' : f.status || 'active',
  }

  if (classId) payload.class_id = classId
  if (streamId) payload.stream_id = streamId
  if (houseId) payload.house_id = houseId
  if (categoryId) payload.student_category_id = categoryId
  if (f.student_number.trim()) payload.student_number = f.student_number.trim()
  if (f.roll_number.trim()) payload.roll_number = f.roll_number.trim()
  if (f.admission_date) payload.admission_date = f.admission_date
  if (f.academic_year) payload.academic_year = f.academic_year
  if (f.gender) payload.gender = f.gender
  if (f.date_of_birth) payload.dateOfBirth = f.date_of_birth
  if (f.blood_group) payload.blood_group = f.blood_group
  if (f.religion) payload.religion = f.religion
  if (f.caste.trim()) payload.caste = f.caste.trim()
  if (f.mother_tongue) payload.mother_tongue = f.mother_tongue
  if (f.languages_known.trim()) payload.languages_known = f.languages_known.trim()
  if (f.phone.trim()) payload.phone = f.phone.trim()
  if (f.email.trim()) payload.email = f.email.trim()
  if (f.address.trim()) payload.address = f.address.trim()
  if (f.suburb.trim()) payload.suburb = f.suburb.trim()
  if (f.previous_school.trim()) payload.previous_school = f.previous_school.trim()
  if (f.father_name.trim()) payload.father_name = f.father_name.trim()
  if (f.father_phone.trim()) payload.father_phone = f.father_phone.trim()
  if (f.father_occupation.trim()) payload.father_occupation = f.father_occupation.trim()
  if (f.mother_name.trim()) payload.mother_name = f.mother_name.trim()
  if (f.mother_phone.trim()) payload.mother_phone = f.mother_phone.trim()
  if (f.mother_occupation.trim()) payload.mother_occupation = f.mother_occupation.trim()
  if (f.siblings_text.trim()) {
    payload.siblings = f.siblings_text
      .split('\n')
      .map((line) => line.trim())
      .filter(Boolean)
  }

  if (gFirst || gLast || gPhone || gEmail || gRel) {
    payload.guardian = {
      ...(gFirst ? { firstName: gFirst } : {}),
      ...(gLast ? { surname: gLast } : {}),
      ...(gPhone ? { phone: gPhone } : {}),
      ...(gEmail ? { email: gEmail } : {}),
      ...(gRel ? { relationship: gRel } : {}),
    }
  }

  return payload
}

async function submit(asDraft = false) {
  if (asDraft) {
    if (!canSaveDraft.value) {
      toast.warning('Missing required fields', 'First name and last name are required to save a draft.')
      scrollTo('personal')
      return
    }
  } else if (!canSubmit.value) {
    toast.warning('Missing required fields', 'First name, last name, and class are required.')
    scrollTo('personal')
    return
  }
  saving.value = true
  try {
    const created = (await studentsApi.create(buildPayload(asDraft))) as { id?: number }
    const id = created?.id
    if (id && photoFile.value) {
      await studentsApi.uploadPhoto(id, photoFile.value).catch(() => null)
    }
    if (id && documentFiles.value.length) {
      await studentsApi.uploadDocuments(id, documentFiles.value, 'admission').catch(() => null)
    }
    if (id && (form.value.medical_notes.trim() || form.value.allergies.trim() || form.value.blood_group)) {
      await operationsApi.health.updateProfile(id, {
        conditions: form.value.medical_notes.trim() || undefined,
        allergies: form.value.allergies.trim() || undefined,
        blood_group: form.value.blood_group || undefined,
      }).catch(() => null)
    }
    toast.success(asDraft ? 'Draft saved' : 'Student added')
    if (id) await router.push(`/students/${id}`)
    else await router.push('/students')
  } catch (err) {
    const validation = getValidationErrors(err)
    const first = validation ? Object.values(validation).flat()[0] : null
    toast.error('Could not save student', first ?? getErrorMessage(err))
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  form.value.academic_year = academicYear.value
  void loadOptions()
})

onBeforeUnmount(() => {
  revokePreview(photoPreview.value)
  revokePreview(fatherPhotoPreview.value)
  revokePreview(motherPhotoPreview.value)
})
</script>

<template>
  <PageShell
    title="Add student"
    description="Capture admission details and assign the student to a class."
    :breadcrumbs="['Students', 'Add student']"
    max-width="wide"
  >
    <template #actions>
      <Button type="button" variant="outline" class="rounded-xl" as-child>
        <RouterLink to="/students">
          <ArrowLeft class="mr-1.5 size-4" aria-hidden="true" />
          Back to List
        </RouterLink>
      </Button>
      <Button
        type="button"
        variant="outline"
        class="rounded-xl"
        :disabled="saving || !canSaveDraft"
        :title="missingDraftRequired.length ? `Missing: ${missingDraftRequired.join(', ')}` : undefined"
        @click="submit(true)"
      >
        <Save class="mr-1.5 size-4" aria-hidden="true" />
        Save Draft
      </Button>
      <Button
        type="button"
        class="rounded-xl"
        :disabled="saving || !canSubmit"
        :title="missingRequired.length ? `Missing: ${missingRequired.join(', ')}` : undefined"
        @click="submit(false)"
      >
        <Plus class="mr-1.5 size-4" aria-hidden="true" />
        {{ saving ? 'Saving…' : 'Add Student' }}
      </Button>
    </template>

    <div :class="cn('grid gap-6 lg:grid-cols-[15rem_minmax(0,1fr)]', PAGE_STACK)">
      <!-- Form sections nav -->
      <aside
        class="rounded-2xl border border-border/70 bg-card p-3 shadow-[var(--shadow-soft)] lg:sticky lg:top-24 lg:self-start"
      >
        <p class="mb-2 px-2 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
          Form Sections
        </p>
        <nav class="space-y-0.5" aria-label="Form sections">
          <button
            v-for="section in SECTIONS"
            :key="section.id"
            type="button"
            :class="cn(
              'w-full rounded-xl px-3 py-2.5 text-left text-sm transition-colors',
              activeSection === section.id
                ? 'bg-primary/10 font-semibold text-primary'
                : 'text-muted-foreground hover:bg-muted/50 hover:text-foreground',
            )"
            @click="scrollTo(section.id)"
          >
            {{ section.label }}
          </button>
        </nav>
      </aside>

      <div class="space-y-6">
        <!-- Personal -->
        <Card id="section-personal" class="overflow-hidden shadow-[var(--shadow-soft)]">
          <CardHeader class="border-b border-border/50 px-5 py-4">
            <CardTitle class="text-base">Personal Information</CardTitle>
            <p class="text-sm text-muted-foreground">
              Fields marked with
              <span class="font-semibold text-destructive" aria-hidden="true">*</span>
              <span class="sr-only">asterisk</span>
              are required to add a student. Save Draft needs First Name and Last Name only.
            </p>
          </CardHeader>
          <CardContent class="space-y-6 p-5">
            <!-- Photo -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
              <div
                class="flex size-28 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-dashed border-border bg-muted/30"
              >
                <img
                  v-if="photoPreview"
                  :src="photoPreview"
                  alt="Student photo preview"
                  class="size-full object-cover"
                />
                <span v-else class="text-xs text-muted-foreground">No photo</span>
              </div>
              <div class="space-y-2">
                <div class="flex flex-wrap gap-2">
                  <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    class="rounded-xl"
                    @click="photoInput?.click()"
                  >
                    <Upload class="mr-1.5 size-3.5" aria-hidden="true" />
                    Upload
                  </Button>
                  <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    class="rounded-xl"
                    :disabled="!photoPreview"
                    @click="clearStudentPhoto"
                  >
                    Remove
                  </Button>
                </div>
                <p class="text-xs text-muted-foreground">
                  Upload image size 4MB, Format JPG, PNG, SVG
                </p>
                <input
                  ref="photoInput"
                  type="file"
                  accept="image/jpeg,image/png,image/svg+xml,image/webp"
                  class="sr-only"
                  @change="setPhotoFromInput($event, 'student')"
                />
              </div>
            </div>

            <!-- Fields 4-col -->
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
              <div class="space-y-1.5">
                <Label :class="labelClass">Academic Year</Label>
                <Select
                  :model-value="academicYear"
                  @update:model-value="(v) => { if (typeof v === 'string') { setAcademicYear(v); form.academic_year = v } }"
                >
                  <SelectTrigger :class="fieldClass"><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="y in yearOptions" :key="y" :value="y">{{ y }}</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div class="space-y-1.5">
                <Label for="admission-no" :class="labelClass">Admission Number</Label>
                <Input id="admission-no" v-model="form.student_number" :class="fieldClass" placeholder="Auto if blank" />
              </div>
              <div class="space-y-1.5">
                <Label for="admission-date" :class="labelClass">Admission Date</Label>
                <Input id="admission-date" v-model="form.admission_date" type="date" :class="fieldClass" />
              </div>
              <div class="space-y-1.5">
                <Label for="roll" :class="labelClass">Roll Number</Label>
                <Input id="roll" v-model="form.roll_number" :class="fieldClass" />
              </div>

              <div class="space-y-1.5">
                <Label :class="labelClass">Status</Label>
                <Select v-model="form.status">
                  <SelectTrigger :class="fieldClass"><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="active">Active</SelectItem>
                    <SelectItem value="inactive">Inactive</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div class="space-y-1.5">
                <Label for="first" :class="labelClass">
                  First Name
                  <span class="text-destructive" aria-hidden="true">*</span>
                </Label>
                <Input
                  id="first"
                  v-model="form.first_name"
                  :class="fieldClass"
                  required
                  aria-required="true"
                  :aria-invalid="!form.first_name.trim() && missingRequired.length > 0 ? true : undefined"
                />
              </div>
              <div class="space-y-1.5">
                <Label for="last" :class="labelClass">
                  Last Name
                  <span class="text-destructive" aria-hidden="true">*</span>
                </Label>
                <Input
                  id="last"
                  v-model="form.last_name"
                  :class="fieldClass"
                  required
                  aria-required="true"
                  :aria-invalid="!form.last_name.trim() && missingRequired.length > 0 ? true : undefined"
                />
              </div>
              <div class="space-y-1.5">
                <Label :class="labelClass">
                  Class
                  <span class="text-destructive" aria-hidden="true">*</span>
                </Label>
                <Select v-model="form.class_id">
                  <SelectTrigger
                    :class="fieldClass"
                    aria-required="true"
                    :aria-invalid="!form.class_id && missingRequired.length > 0 ? true : undefined"
                  >
                    <SelectValue placeholder="Select" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="c in classes" :key="c.id" :value="String(c.id)">{{ c.name }}</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div class="space-y-1.5">
                <Label :class="labelClass">Section</Label>
                <Select v-model="form.stream_id">
                  <SelectTrigger :class="fieldClass"><SelectValue placeholder="Select" /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="none">None</SelectItem>
                    <SelectItem v-for="s in streams" :key="s.id" :value="String(s.id)">{{ s.name }}</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div class="space-y-1.5">
                <Label :class="labelClass">Gender</Label>
                <Select v-model="form.gender">
                  <SelectTrigger :class="fieldClass"><SelectValue placeholder="Select" /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="male">Male</SelectItem>
                    <SelectItem value="female">Female</SelectItem>
                    <SelectItem value="other">Other</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div class="space-y-1.5">
                <Label for="dob" :class="labelClass">Date of Birth</Label>
                <Input id="dob" v-model="form.date_of_birth" type="date" :class="fieldClass" />
              </div>
              <div class="space-y-1.5">
                <Label :class="labelClass">Blood Group</Label>
                <Select v-model="form.blood_group">
                  <SelectTrigger :class="fieldClass"><SelectValue placeholder="Select" /></SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="bg in BLOOD_GROUPS" :key="bg" :value="bg">{{ bg }}</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div class="space-y-1.5">
                <Label :class="labelClass">House</Label>
                <Select v-model="form.house_id">
                  <SelectTrigger :class="fieldClass"><SelectValue placeholder="Select" /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="none">None</SelectItem>
                    <SelectItem v-for="h in houses" :key="h.id" :value="String(h.id)">{{ h.name }}</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div class="space-y-1.5">
                <Label :class="labelClass">Religion</Label>
                <Select v-model="form.religion">
                  <SelectTrigger :class="fieldClass"><SelectValue placeholder="Select" /></SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="r in RELIGIONS" :key="r" :value="r">{{ r }}</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div class="space-y-1.5">
                <Label :class="labelClass">Category</Label>
                <Select v-model="form.student_category_id">
                  <SelectTrigger :class="fieldClass"><SelectValue placeholder="Select" /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="none">None</SelectItem>
                    <SelectItem v-for="c in categories" :key="c.id" :value="String(c.id)">{{ c.name }}</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div class="space-y-1.5">
                <Label for="phone" :class="labelClass">Primary Contact Number</Label>
                <Input id="phone" v-model="form.phone" :class="fieldClass" placeholder="077 123 4567" />
              </div>

              <div class="space-y-1.5">
                <Label for="email" :class="labelClass">Email Address</Label>
                <Input id="email" v-model="form.email" type="email" :class="fieldClass" />
              </div>
              <div class="space-y-1.5">
                <Label for="caste" :class="labelClass">Caste</Label>
                <Input id="caste" v-model="form.caste" :class="fieldClass" />
              </div>
              <div class="space-y-1.5">
                <Label :class="labelClass">Mother Tongue</Label>
                <Select v-model="form.mother_tongue">
                  <SelectTrigger :class="fieldClass"><SelectValue placeholder="Select" /></SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="t in MOTHER_TONGUES" :key="t" :value="t">{{ t }}</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div class="space-y-1.5">
                <Label for="langs" :class="labelClass">Language Known</Label>
                <Input id="langs" v-model="form.languages_known" :class="fieldClass" placeholder="e.g. English, Shona" />
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Parents -->
        <Card id="section-parents" class="overflow-hidden shadow-[var(--shadow-soft)]">
          <CardHeader class="border-b border-border/50 px-5 py-4">
            <CardTitle class="text-base">Parents & Guardian Information</CardTitle>
          </CardHeader>
          <CardContent class="space-y-8 p-5">
            <!-- Father -->
            <div class="space-y-4">
              <h3 class="text-sm font-semibold text-foreground">Father's Info</h3>
              <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                <div
                  class="flex size-24 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-dashed border-border bg-muted/30"
                >
                  <img
                    v-if="fatherPhotoPreview"
                    :src="fatherPhotoPreview"
                    alt="Father photo preview"
                    class="size-full object-cover"
                  />
                  <span v-else class="px-2 text-center text-[10px] text-muted-foreground">Photo</span>
                </div>
                <div class="space-y-2">
                  <div class="flex gap-2">
                    <Button type="button" variant="outline" size="sm" class="rounded-xl" @click="fatherPhotoInput?.click()">
                      <Upload class="mr-1.5 size-3.5" /> Upload
                    </Button>
                  </div>
                  <p class="text-xs text-muted-foreground">Optional · JPG, PNG</p>
                  <input
                    ref="fatherPhotoInput"
                    type="file"
                    accept="image/*"
                    class="sr-only"
                    @change="setPhotoFromInput($event, 'father')"
                  />
                </div>
              </div>
              <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="space-y-1.5 xl:col-span-2">
                  <Label :class="labelClass">Father Name</Label>
                  <Input v-model="form.father_name" :class="fieldClass" />
                </div>
                <div class="space-y-1.5">
                  <Label :class="labelClass">Phone</Label>
                  <Input v-model="form.father_phone" :class="fieldClass" />
                </div>
                <div class="space-y-1.5">
                  <Label :class="labelClass">Occupation</Label>
                  <Input v-model="form.father_occupation" :class="fieldClass" />
                </div>
                <div class="space-y-1.5 xl:col-span-2">
                  <Label :class="labelClass">Email</Label>
                  <Input v-model="form.father_email" type="email" :class="fieldClass" />
                </div>
              </div>
            </div>

            <!-- Mother -->
            <div class="space-y-4 border-t border-border/50 pt-6">
              <h3 class="text-sm font-semibold text-foreground">Mother's Info</h3>
              <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                <div
                  class="flex size-24 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-dashed border-border bg-muted/30"
                >
                  <img
                    v-if="motherPhotoPreview"
                    :src="motherPhotoPreview"
                    alt="Mother photo preview"
                    class="size-full object-cover"
                  />
                  <span v-else class="px-2 text-center text-[10px] text-muted-foreground">Photo</span>
                </div>
                <div class="space-y-2">
                  <Button type="button" variant="outline" size="sm" class="rounded-xl" @click="motherPhotoInput?.click()">
                    <Upload class="mr-1.5 size-3.5" /> Upload
                  </Button>
                  <input
                    ref="motherPhotoInput"
                    type="file"
                    accept="image/*"
                    class="sr-only"
                    @change="setPhotoFromInput($event, 'mother')"
                  />
                </div>
              </div>
              <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="space-y-1.5 xl:col-span-2">
                  <Label :class="labelClass">Mother Name</Label>
                  <Input v-model="form.mother_name" :class="fieldClass" />
                </div>
                <div class="space-y-1.5">
                  <Label :class="labelClass">Phone</Label>
                  <Input v-model="form.mother_phone" :class="fieldClass" />
                </div>
                <div class="space-y-1.5">
                  <Label :class="labelClass">Occupation</Label>
                  <Input v-model="form.mother_occupation" :class="fieldClass" />
                </div>
                <div class="space-y-1.5 xl:col-span-2">
                  <Label :class="labelClass">Email</Label>
                  <Input v-model="form.mother_email" type="email" :class="fieldClass" />
                </div>
              </div>
            </div>

            <!-- Guardian -->
            <div class="space-y-4 border-t border-border/50 pt-6">
              <h3 class="text-sm font-semibold text-foreground">Guardian (if different)</h3>
              <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="space-y-1.5">
                  <Label :class="labelClass">First Name</Label>
                  <Input v-model="form.guardian_first_name" :class="fieldClass" />
                </div>
                <div class="space-y-1.5">
                  <Label :class="labelClass">Last Name</Label>
                  <Input v-model="form.guardian_last_name" :class="fieldClass" />
                </div>
                <div class="space-y-1.5">
                  <Label :class="labelClass">Phone</Label>
                  <Input v-model="form.guardian_phone" :class="fieldClass" />
                </div>
                <div class="space-y-1.5">
                  <Label :class="labelClass">Relationship</Label>
                  <Input v-model="form.guardian_relationship" :class="fieldClass" />
                </div>
                <div class="space-y-1.5 xl:col-span-2">
                  <Label :class="labelClass">Email</Label>
                  <Input v-model="form.guardian_email" type="email" :class="fieldClass" />
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card id="section-siblings" class="shadow-[var(--shadow-soft)]">
          <CardHeader class="border-b border-border/50 px-5 py-4">
            <CardTitle class="text-base">Siblings</CardTitle>
          </CardHeader>
          <CardContent class="p-5">
            <Label :class="labelClass">Sibling names</Label>
            <Textarea
              v-model="form.siblings_text"
              class="mt-1.5 rounded-xl"
              placeholder="One sibling name per line"
              rows="3"
            />
          </CardContent>
        </Card>

        <Card id="section-address" class="shadow-[var(--shadow-soft)]">
          <CardHeader class="border-b border-border/50 px-5 py-4">
            <CardTitle class="text-base">Address</CardTitle>
          </CardHeader>
          <CardContent class="grid gap-4 p-5 sm:grid-cols-2">
            <div class="space-y-1.5 sm:col-span-2">
              <Label :class="labelClass">Street address</Label>
              <Textarea v-model="form.address" class="rounded-xl" rows="2" />
            </div>
            <div class="space-y-1.5">
              <Label :class="labelClass">Suburb / City</Label>
              <Input v-model="form.suburb" :class="fieldClass" />
            </div>
          </CardContent>
        </Card>

        <Card id="section-transport" class="shadow-[var(--shadow-soft)]">
          <CardHeader class="border-b border-border/50 px-5 py-4">
            <CardTitle class="text-base">Transport</CardTitle>
          </CardHeader>
          <CardContent class="p-5">
            <Textarea
              v-model="form.transport_notes"
              class="rounded-xl"
              placeholder="Route / pickup notes — allocate after create"
              rows="2"
            />
          </CardContent>
        </Card>

        <Card id="section-hostel" class="shadow-[var(--shadow-soft)]">
          <CardHeader class="border-b border-border/50 px-5 py-4">
            <CardTitle class="text-base">Hostel</CardTitle>
          </CardHeader>
          <CardContent class="p-5">
            <Textarea v-model="form.hostel_notes" class="rounded-xl" placeholder="Hostel preference" rows="2" />
          </CardContent>
        </Card>

        <Card id="section-documents" class="shadow-[var(--shadow-soft)]">
          <CardHeader class="border-b border-border/50 px-5 py-4">
            <CardTitle class="text-base">Documents</CardTitle>
          </CardHeader>
          <CardContent class="p-5">
            <Input
              type="file"
              multiple
              accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
              class="rounded-xl"
              @change="onDocs"
            />
            <p v-if="documentFiles.length" class="mt-2 text-xs text-muted-foreground">
              {{ documentFiles.length }} file(s) selected — uploaded after student is created
            </p>
          </CardContent>
        </Card>

        <Card id="section-medical" class="shadow-[var(--shadow-soft)]">
          <CardHeader class="border-b border-border/50 px-5 py-4">
            <CardTitle class="text-base">Medical History</CardTitle>
          </CardHeader>
          <CardContent class="grid gap-4 p-5 sm:grid-cols-2">
            <div class="space-y-1.5">
              <Label :class="labelClass">Conditions / notes</Label>
              <Textarea v-model="form.medical_notes" class="rounded-xl" rows="2" />
            </div>
            <div class="space-y-1.5">
              <Label :class="labelClass">Allergies</Label>
              <Textarea v-model="form.allergies" class="rounded-xl" rows="2" />
            </div>
          </CardContent>
        </Card>

        <Card id="section-previous" class="shadow-[var(--shadow-soft)]">
          <CardHeader class="border-b border-border/50 px-5 py-4">
            <CardTitle class="text-base">Previous School</CardTitle>
          </CardHeader>
          <CardContent class="p-5">
            <Input v-model="form.previous_school" :class="fieldClass" placeholder="School name" />
          </CardContent>
        </Card>

        <Card id="section-other" class="shadow-[var(--shadow-soft)]">
          <CardHeader class="border-b border-border/50 px-5 py-4">
            <CardTitle class="text-base">Other Details</CardTitle>
          </CardHeader>
          <CardContent class="p-5">
            <Textarea v-model="form.notes" class="rounded-xl" placeholder="Additional notes" rows="3" />
          </CardContent>
        </Card>

        <div class="space-y-3 border-t border-border/60 pt-4 print:hidden">
          <p
            v-if="missingRequired.length"
            class="text-sm text-muted-foreground"
            role="status"
            aria-live="polite"
          >
            Required before Add Student:
            <span class="font-medium text-foreground">{{ missingRequired.join(', ') }}</span>
          </p>
          <div class="flex flex-wrap items-center justify-end gap-2">
            <Button type="button" variant="secondary" class="rounded-xl" as-child>
              <RouterLink to="/students">Cancel</RouterLink>
            </Button>
            <Button
              type="button"
              variant="outline"
              class="rounded-xl"
              :disabled="saving || !canSaveDraft"
              :title="missingDraftRequired.length ? `Missing: ${missingDraftRequired.join(', ')}` : undefined"
              @click="submit(true)"
            >
              <Save class="mr-1.5 size-4" aria-hidden="true" />
              Save Draft
            </Button>
            <Button
              type="button"
              class="rounded-xl"
              :disabled="saving || !canSubmit"
              :title="missingRequired.length ? `Missing: ${missingRequired.join(', ')}` : undefined"
              @click="submit(false)"
            >
              <Plus class="mr-1.5 size-4" aria-hidden="true" />
              Add Student
            </Button>
          </div>
        </div>
      </div>
    </div>
  </PageShell>
</template>
