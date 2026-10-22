<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  Building2,
  CheckCircle2,
  Download,
  Globe,
  Link2,
  MapPin,
  MoreHorizontal,
  Pencil,
  Share2,
  Shield,
  Upload,
} from '@lucide/vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import EmptyState from '@/components/feedback/EmptyState.vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import { useToast } from '@/composables/useToast'
import { schoolApi, uploadsApi } from '@/services/api.service'
import { getErrorMessage } from '@/lib/api-response'
import { resolveMediaUrl } from '@/lib/media-url'
import { cn } from '@/lib/utils'
import ProfileFieldList, { type ProfileField } from './ProfileFieldList.vue'
import EditSchoolDialog, { type SchoolEditPayload } from './EditSchoolDialog.vue'
import {
  SCHOOL_PROFILE_GROUPS,
  SCHOOL_PROFILE_SECTIONS,
  displayValue,
  parseProfileSection,
  sectionById,
  type SchoolProfileSectionId,
} from './school-profile-sections'

export type SchoolRecord = {
  name?: string
  code?: string
  principal_name?: string
  email?: string
  phone?: string
  website?: string
  year_founded?: number | null
  suburb?: string
  city?: string
  timezone?: string
  motto?: string
  address?: string
  currency?: string
  logo_path?: string
  academic_year?: string
  current_term?: string
  status?: string
  license_status?: string
  created_at?: string
  updated_at?: string
}

const props = defineProps<{
  school: SchoolRecord | null
  setupCounts: {
    gradeLevels: number
    rooms: number
    subjects: number
    terms: number
    classes: number
  }
  studentCount?: number
}>()

const emit = defineEmits<{
  refreshed: []
}>()

const toast = useToast()
const route = useRoute()
const router = useRouter()

const settingsBag = ref<Record<string, Record<string, unknown>>>({})
const settingsLoading = ref(true)
const saving = ref(false)
const editOpen = ref(false)
const socialOpen = ref(false)
const colourEditKey = ref<string | null>(null)
const colourDraft = ref('')
const socialDraft = ref({
  facebook: '',
  twitter: '',
  instagram: '',
  youtube: '',
  linkedin: '',
})
const uploadingLogo = ref(false)

const activeSection = computed(() => parseProfileSection(route.query.section))
const activeMeta = computed(() => sectionById(activeSection.value))

const groupedNav = computed(() =>
  SCHOOL_PROFILE_GROUPS.map((group) => ({
    ...group,
    sections: SCHOOL_PROFILE_SECTIONS.filter((s) => s.group === group.id),
  })),
)

function setting(group: string, key: string): unknown {
  return settingsBag.value?.[group]?.[key]
}

function filled(...values: unknown[]): boolean {
  return values.every((v) => String(v ?? '').trim().length > 0)
}

const logoUrl = computed(() => resolveMediaUrl(props.school?.logo_path))
const isPublished = computed(() => {
  const status = String(props.school?.status ?? '').toLowerCase()
  return !status || status === 'active' || status === 'published'
})
const isVerified = computed(() => {
  const lic = String(props.school?.license_status ?? '').toLowerCase()
  return lic === 'active' || lic === 'verified' || lic === 'licensed'
})

const completeness = computed(() => {
  const checks = [
    props.school?.name,
    props.school?.email,
    props.school?.phone,
    props.school?.address,
    props.school?.principal_name,
    props.school?.logo_path,
    setting('profile', 'affiliation_board'),
    setting('branding', 'primary_color'),
    setting('social', 'facebook') || setting('social', 'instagram'),
    props.school?.academic_year,
  ]
  const done = checks.filter((v) => filled(v)).length
  const total = checks.length
  const percent = Math.round((done / total) * 100)
  const missing = total - done
  return { percent, missing, hint: missing ? `${missing} field${missing === 1 ? '' : 's'} still empty.` : 'Profile looks complete.' }
})

const metrics = computed(() => {
  const board = setting('profile', 'affiliation_board')
  const items: Array<{ label: string; value: string; tone?: 'success' }> = []
  if (filled(board)) items.push({ label: 'Affiliation Board', value: String(board) })
  items.push(
    { label: 'Established', value: displayValue(props.school?.year_founded) },
    {
      label: 'Students Enrolled',
      value: props.studentCount != null ? props.studentCount.toLocaleString() : '—',
    },
    { label: 'School Code', value: displayValue(props.school?.code) },
    { label: 'Academic Year', value: displayValue(props.school?.academic_year) },
    {
      label: 'Status',
      value: isPublished.value ? 'Active' : displayValue(props.school?.status, 'Unknown'),
      tone: isPublished.value ? 'success' : undefined,
    },
    {
      label: 'Last Updated',
      value: props.school?.updated_at
        ? new Date(props.school.updated_at).toLocaleString(undefined, {
            dateStyle: 'medium',
            timeStyle: 'short',
          })
        : '—',
    },
  )
  return items
})

const generalFields = computed((): ProfileField[] => {
  const type = setting('profile', 'school_type')
  const board = setting('profile', 'affiliation_board')
  const medium = setting('profile', 'medium_of_instruction')
  const term = props.school?.current_term
  const year = props.school?.academic_year
  return [
    { label: 'School name', value: displayValue(props.school?.name), set: filled(props.school?.name) },
    { label: 'School code', value: displayValue(props.school?.code), badge: true, set: filled(props.school?.code) },
    { label: 'School type', value: displayValue(type), set: filled(type), muted: !filled(type) },
    { label: 'Affiliation board', value: displayValue(board), set: filled(board), muted: !filled(board) },
    { label: 'Established year', value: displayValue(props.school?.year_founded), set: filled(props.school?.year_founded) },
    {
      label: 'Academic year',
      value: year ? `${year}${term ? ` · ${term}` : ''}` : 'Not set',
      set: filled(year),
      muted: !filled(year),
    },
    { label: 'Medium of instruction', value: displayValue(medium), set: filled(medium), muted: !filled(medium) },
    { label: 'Website', value: displayValue(props.school?.website), set: filled(props.school?.website), muted: !filled(props.school?.website) },
    { label: 'Official email', value: displayValue(props.school?.email), set: filled(props.school?.email) },
    { label: 'Primary phone', value: displayValue(props.school?.phone), set: filled(props.school?.phone) },
  ]
})

const identityFields = computed((): ProfileField[] => [
  { label: 'Legal name', value: displayValue(setting('profile', 'legal_name') || props.school?.name), set: filled(setting('profile', 'legal_name') || props.school?.name) },
  { label: 'School code', value: displayValue(props.school?.code), badge: true, set: filled(props.school?.code) },
  { label: 'Motto', value: displayValue(props.school?.motto), set: filled(props.school?.motto), muted: !filled(props.school?.motto) },
  { label: 'Principal', value: displayValue(props.school?.principal_name), set: filled(props.school?.principal_name) },
  { label: 'City', value: displayValue(props.school?.city), set: filled(props.school?.city), muted: !filled(props.school?.city) },
  { label: 'Suburb', value: displayValue(props.school?.suburb), set: filled(props.school?.suburb), muted: !filled(props.school?.suburb) },
])

const brandingColours = computed(() => [
  { key: 'primary_color', label: 'Primary Color', hint: 'Buttons, links and headers', value: String(setting('branding', 'primary_color') ?? '') },
  { key: 'secondary_color', label: 'Secondary Color', hint: 'Accents and highlights', value: String(setting('branding', 'secondary_color') ?? '') },
  { key: 'success_color', label: 'Success Color', hint: 'Confirmations and passes', value: String(setting('branding', 'success_color') ?? '') },
  { key: 'ink_color', label: 'Ink Color', hint: 'Headings and printed text', value: String(setting('branding', 'ink_color') ?? '') },
])

const brandingSurfaces = computed(() => [
  { label: 'Email Branding', hint: 'Header, footer and signature on outgoing messages.', applied: setting('branding', 'apply_email') === true },
  { label: 'Certificate Branding', hint: 'Crest, seal and signature on certificates.', applied: setting('branding', 'apply_certificate') === true },
  { label: 'ID Card Branding', hint: 'Student and staff card front and reverse.', applied: setting('branding', 'apply_id_card') === true },
])

const contactCards = computed(() => {
  const principal = (setting('contact', 'principal_office') as Record<string, string> | null) ?? {}
  const reception = (setting('contact', 'reception') as Record<string, string> | null) ?? {}
  const phones = (setting('contact', 'phones') as Record<string, string> | null) ?? {}
  const emails = (setting('contact', 'emails') as Record<string, string> | null) ?? {}
  const emergency = (setting('contact', 'emergency') as Record<string, string> | null) ?? {}
  return [
    {
      title: 'Principal Office',
      lines: [
        displayValue(principal.location),
        displayValue(principal.phone || props.school?.phone),
        displayValue(principal.email || props.school?.email),
      ],
    },
    {
      title: 'Reception',
      lines: [
        displayValue(reception.location),
        displayValue(reception.phone || props.school?.phone),
        displayValue(reception.hours, 'Hours not set'),
      ],
    },
    {
      title: 'Phone Numbers',
      lines: [
        `Main · ${displayValue(phones.main || props.school?.phone)}`,
        `Admissions · ${displayValue(phones.admissions)}`,
        `Transport · ${displayValue(phones.transport)}`,
      ],
    },
    {
      title: 'Email Addresses',
      lines: [
        `Office · ${displayValue(emails.office || props.school?.email)}`,
        `Admissions · ${displayValue(emails.admissions)}`,
        `Accounts · ${displayValue(emails.accounts)}`,
      ],
    },
    {
      title: 'Emergency Contact',
      lines: [
        `24-hour · ${displayValue(emergency.hotline)}`,
        `Nurse · ${displayValue(emergency.nurse)}`,
        displayValue(emergency.notes),
      ],
    },
  ]
})

const addressCards = computed(() => [
  {
    title: 'Office Address',
    icon: Building2,
    body: displayValue(setting('address', 'office') || props.school?.address),
  },
  {
    title: 'Campus Address',
    icon: MapPin,
    body: displayValue(setting('address', 'campus') || [props.school?.suburb, props.school?.city].filter(Boolean).join(', ')),
  },
  {
    title: 'Map Location',
    icon: MapPin,
    body: filled(setting('address', 'map_lat'), setting('address', 'map_lng'))
      ? `${setting('address', 'map_lat')}° · ${setting('address', 'map_lng')}°${setting('address', 'map_verified') === true ? ' · Verified' : ''}`
      : 'Not set',
  },
])

const socialCards = computed(() => {
  const website = props.school?.website
  return [
    { id: 'facebook', label: 'Facebook', value: setting('social', 'facebook'), icon: Share2 },
    { id: 'twitter', label: 'X (Twitter)', value: setting('social', 'twitter'), icon: Share2 },
    { id: 'instagram', label: 'Instagram', value: setting('social', 'instagram'), icon: Share2 },
    { id: 'youtube', label: 'YouTube', value: setting('social', 'youtube'), icon: Share2 },
    { id: 'linkedin', label: 'LinkedIn', value: setting('social', 'linkedin'), icon: Link2 },
    { id: 'website', label: 'Public website', value: website, icon: Globe, live: true },
  ]
})

const communicationFields = computed((): ProfileField[] => {
  const fromName = setting('mail', 'from_name') || props.school?.name
  const fromEmail = setting('mail', 'from_address') || props.school?.email
  const smsId = setting('communication', 'sms_sender_id') || setting('sms', 'africastalking_from')
  const wa = setting('communication', 'whatsapp_business') || setting('whatsapp', 'twilio_from')
  return [
    { label: 'Default sender name', value: displayValue(fromName), set: filled(fromName) },
    { label: 'Default sender email', value: displayValue(fromEmail), set: filled(fromEmail) },
    { label: 'Reply-to address', value: displayValue(props.school?.email), set: filled(props.school?.email) },
    { label: 'SMS sender id', value: displayValue(smsId), badge: true, set: filled(smsId), muted: !filled(smsId) },
    { label: 'WhatsApp business number', value: displayValue(wa, 'Not configured'), set: filled(wa), muted: !filled(wa) },
    { label: 'Letterhead template', value: displayValue(setting('communication', 'letterhead_template')), set: filled(setting('communication', 'letterhead_template')), muted: !filled(setting('communication', 'letterhead_template')) },
    { label: 'Email footer', value: displayValue(setting('communication', 'email_footer')), set: filled(setting('communication', 'email_footer')), muted: !filled(setting('communication', 'email_footer')) },
    { label: 'Quiet hours', value: displayValue(setting('communication', 'quiet_hours')), set: filled(setting('communication', 'quiet_hours')), muted: !filled(setting('communication', 'quiet_hours')) },
  ]
})

const academicFields = computed((): ProfileField[] => {
  const board = setting('profile', 'affiliation_board')
  const levels = props.setupCounts.gradeLevels
  return [
    { label: 'Academic board', value: displayValue(board), set: filled(board), muted: !filled(board) },
    { label: 'Grade levels', value: levels ? `${levels} levels configured` : 'Not set', set: levels > 0, muted: levels === 0 },
    { label: 'Subjects', value: props.setupCounts.subjects ? `${props.setupCounts.subjects} subjects` : 'Not set', set: props.setupCounts.subjects > 0 },
    { label: 'Campus type', value: displayValue(setting('campus', 'campus_type')), set: filled(setting('campus', 'campus_type')), muted: !filled(setting('campus', 'campus_type')) },
    { label: 'Session type', value: displayValue(setting('campus', 'session_type') || (props.setupCounts.terms ? `Annual · ${props.setupCounts.terms} terms` : null)), set: filled(setting('campus', 'session_type')) || props.setupCounts.terms > 0 },
    { label: 'Working days', value: displayValue(setting('campus', 'working_days')), set: filled(setting('campus', 'working_days')), muted: !filled(setting('campus', 'working_days')) },
    { label: 'School timings', value: displayValue(setting('campus', 'school_timings')), set: filled(setting('campus', 'school_timings')), muted: !filled(setting('campus', 'school_timings')) },
    { label: 'Examination pattern', value: displayValue(setting('campus', 'exam_pattern')), set: filled(setting('campus', 'exam_pattern')), muted: !filled(setting('campus', 'exam_pattern')) },
  ]
})

const campusFields = computed((): ProfileField[] => [
  { label: 'Campus area', value: displayValue(setting('campus', 'area')), set: filled(setting('campus', 'area')), muted: !filled(setting('campus', 'area')) },
  { label: 'Classrooms', value: displayValue(setting('campus', 'classrooms') || (props.setupCounts.rooms ? `${props.setupCounts.rooms} rooms` : null)), set: filled(setting('campus', 'classrooms')) || props.setupCounts.rooms > 0 },
  { label: 'Facilities', value: displayValue(setting('campus', 'facilities')), set: filled(setting('campus', 'facilities')), muted: !filled(setting('campus', 'facilities')) },
  { label: 'Medium of instruction', value: displayValue(setting('profile', 'medium_of_instruction')), set: filled(setting('profile', 'medium_of_instruction')), muted: !filled(setting('profile', 'medium_of_instruction')) },
  { label: 'Second language', value: displayValue(setting('campus', 'second_language')), set: filled(setting('campus', 'second_language')), muted: !filled(setting('campus', 'second_language')) },
  { label: 'Third language', value: displayValue(setting('campus', 'third_language')), set: filled(setting('campus', 'third_language')), muted: !filled(setting('campus', 'third_language')) },
  { label: 'Timezone', value: displayValue(props.school?.timezone || setting('regional', 'timezone')), set: filled(props.school?.timezone || setting('regional', 'timezone')) },
  { label: 'Date format', value: displayValue(setting('regional', 'date_format')), set: filled(setting('regional', 'date_format')) },
  { label: 'Currency', value: displayValue(props.school?.currency || setting('regional', 'currency')), set: filled(props.school?.currency || setting('regional', 'currency')) },
  { label: 'Week starts on', value: displayValue(setting('campus', 'week_starts_on')), set: filled(setting('campus', 'week_starts_on')) },
])

const managementPeople = computed(() => {
  const people = setting('management', 'people')
  if (Array.isArray(people) && people.length) return people as Array<Record<string, string>>
  if (props.school?.principal_name) {
    return [{
      role: 'Principal',
      name: String(props.school.principal_name),
      email: String(props.school.email ?? ''),
      status: 'In office',
    }]
  }
  return []
})

const trustFields = computed((): ProfileField[] => [
  { label: 'Trust name', value: displayValue(setting('management', 'trust_name')), set: filled(setting('management', 'trust_name')), muted: !filled(setting('management', 'trust_name')) },
  { label: 'Trust registration', value: displayValue(setting('management', 'trust_registration')), badge: true, set: filled(setting('management', 'trust_registration')), muted: !filled(setting('management', 'trust_registration')) },
  { label: 'Registered office', value: displayValue(setting('management', 'trust_office') || props.school?.address), set: filled(setting('management', 'trust_office') || props.school?.address) },
  { label: 'Management committee', value: displayValue(setting('management', 'committee_summary')), set: filled(setting('management', 'committee_summary')), muted: !filled(setting('management', 'committee_summary')) },
])

const complianceItems = computed(() => {
  const items = setting('compliance', 'items')
  return Array.isArray(items) ? (items as Array<Record<string, string>>) : []
})

const documentItems = computed(() => {
  const items = setting('documents', 'items')
  return Array.isArray(items) ? (items as Array<Record<string, string>>) : []
})

const securityFields = computed((): ProfileField[] => [
  { label: 'Two-factor authentication', value: displayValue(setting('security', 'two_factor')), set: filled(setting('security', 'two_factor')), muted: !filled(setting('security', 'two_factor')) },
  { label: 'Password policy', value: displayValue(setting('security', 'password_policy')), set: filled(setting('security', 'password_policy')), muted: !filled(setting('security', 'password_policy')) },
  { label: 'Session timeout', value: displayValue(setting('security', 'session_timeout')), set: filled(setting('security', 'session_timeout')), muted: !filled(setting('security', 'session_timeout')) },
  { label: 'Login IP restriction', value: displayValue(setting('security', 'ip_restriction'), 'Not enabled'), set: filled(setting('security', 'ip_restriction')), muted: !filled(setting('security', 'ip_restriction')) },
  { label: 'Who can edit this profile', value: displayValue(setting('security', 'profile_editors')), set: filled(setting('security', 'profile_editors')), muted: !filled(setting('security', 'profile_editors')) },
  { label: 'Data retention', value: displayValue(setting('security', 'data_retention')), set: filled(setting('security', 'data_retention')), muted: !filled(setting('security', 'data_retention')) },
  { label: 'Audit log retention', value: displayValue(setting('security', 'audit_retention')), set: filled(setting('security', 'audit_retention')), muted: !filled(setting('security', 'audit_retention')) },
])

const auditEvents = computed(() => {
  const events: Array<{ title: string; detail: string; when: string }> = []
  if (props.school?.created_at) {
    events.push({
      title: 'School Created',
      detail: 'Institution record created in the system.',
      when: new Date(props.school.created_at).toLocaleString(),
    })
  }
  if (props.school?.updated_at) {
    events.push({
      title: 'Profile Updated',
      detail: 'Latest changes saved to the school profile.',
      when: new Date(props.school.updated_at).toLocaleString(),
    })
  }
  return events
})

function selectSection(id: SchoolProfileSectionId) {
  void router.replace({
    query: {
      ...route.query,
      tab: 'profile',
      section: id === 'general' ? undefined : id,
    },
  })
}

async function loadSettings() {
  settingsLoading.value = true
  try {
    settingsBag.value = (await schoolApi.settings()) as Record<string, Record<string, unknown>>
  } catch (err) {
    toast.error('Could not load profile settings', getErrorMessage(err))
  } finally {
    settingsLoading.value = false
  }
}

async function saveSettings(entries: Array<{ group: string; key: string; value: unknown }>) {
  saving.value = true
  try {
    await schoolApi.updateSettings(entries)
    await loadSettings()
    toast.success('Saved')
    emit('refreshed')
  } catch (err) {
    toast.error('Save failed', getErrorMessage(err))
  } finally {
    saving.value = false
  }
}

async function onEditSave(payload: SchoolEditPayload) {
  saving.value = true
  try {
    const yearFounded = payload.year_founded.trim()
      ? Number(payload.year_founded)
      : null
    await schoolApi.update({
      name: payload.name,
      principal_name: payload.principal_name || null,
      email: payload.email || null,
      phone: payload.phone || null,
      website: payload.website || null,
      year_founded: Number.isFinite(yearFounded) ? yearFounded : null,
      suburb: payload.suburb || null,
      city: payload.city || null,
      timezone: payload.timezone || null,
      motto: payload.motto || null,
      address: payload.address || null,
      academic_year: payload.academic_year || null,
    })
    await schoolApi.updateSettings([
      { group: 'profile', key: 'school_type', value: payload.school_type || null },
      { group: 'profile', key: 'affiliation_board', value: payload.affiliation_board || null },
      { group: 'profile', key: 'medium_of_instruction', value: payload.medium_of_instruction || null },
      { group: 'profile', key: 'legal_name', value: payload.legal_name || null },
    ])
    editOpen.value = false
    toast.success('School information saved')
    await loadSettings()
    emit('refreshed')
  } catch (err) {
    toast.error('Save failed', getErrorMessage(err))
  } finally {
    saving.value = false
  }
}

async function onLogoUpload(kind: 'logo' | 'dark' | 'compact' | 'favicon', file: File | null) {
  if (!file) return
  uploadingLogo.value = true
  try {
    const uploaded = await uploadsApi.upload(file, 'school-branding')
    if (kind === 'logo') {
      await schoolApi.update({ logo_path: uploaded.path })
    } else {
      const key =
        kind === 'dark' ? 'dark_logo_path' : kind === 'compact' ? 'compact_logo_path' : 'favicon_path'
      await schoolApi.updateSettings([{ group: 'branding', key, value: uploaded.path }])
    }
    toast.success('Logo updated')
    await loadSettings()
    emit('refreshed')
  } catch (err) {
    toast.error('Upload failed', getErrorMessage(err))
  } finally {
    uploadingLogo.value = false
  }
}

function openColourEdit(key: string, value: string) {
  colourEditKey.value = key
  colourDraft.value = value || '#3D5EE1'
}

async function saveColour() {
  if (!colourEditKey.value) return
  await saveSettings([{ group: 'branding', key: colourEditKey.value, value: colourDraft.value || null }])
  colourEditKey.value = null
}

function openSocialEditor() {
  socialDraft.value = {
    facebook: String(setting('social', 'facebook') ?? ''),
    twitter: String(setting('social', 'twitter') ?? ''),
    instagram: String(setting('social', 'instagram') ?? ''),
    youtube: String(setting('social', 'youtube') ?? ''),
    linkedin: String(setting('social', 'linkedin') ?? ''),
  }
  socialOpen.value = true
}

async function saveSocial() {
  await saveSettings([
    { group: 'social', key: 'facebook', value: socialDraft.value.facebook || null },
    { group: 'social', key: 'twitter', value: socialDraft.value.twitter || null },
    { group: 'social', key: 'instagram', value: socialDraft.value.instagram || null },
    { group: 'social', key: 'youtube', value: socialDraft.value.youtube || null },
    { group: 'social', key: 'linkedin', value: socialDraft.value.linkedin || null },
  ])
  socialOpen.value = false
}

async function onDocumentUpload(fileList: FileList | null) {
  const file = fileList?.[0]
  if (!file) return
  try {
    const uploaded = await uploadsApi.upload(file, 'school-documents')
    const existing = Array.isArray(setting('documents', 'items'))
      ? [...(setting('documents', 'items') as Array<Record<string, string>>)]
      : []
    existing.push({
      name: file.name,
      path: uploaded.path,
      url: uploaded.url,
      mime: uploaded.mime ?? file.type,
      size: String(uploaded.size),
      uploaded_at: new Date().toISOString(),
    })
    await saveSettings([{ group: 'documents', key: 'items', value: existing }])
  } catch (err) {
    toast.error('Upload failed', getErrorMessage(err))
  }
}

function exportAudit() {
  const rows = auditEvents.value.map((e) => `"${e.title}","${e.detail}","${e.when}"`).join('\n')
  const blob = new Blob([`title,detail,when\n${rows}`], { type: 'text/csv' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = 'school-profile-audit.csv'
  a.click()
  URL.revokeObjectURL(url)
}

const editInitial = computed(() => ({
  name: props.school?.name ?? '',
  principal_name: props.school?.principal_name ?? '',
  email: props.school?.email ?? '',
  phone: props.school?.phone ?? '',
  website: props.school?.website ?? '',
  year_founded: props.school?.year_founded != null ? String(props.school.year_founded) : '',
  suburb: props.school?.suburb ?? '',
  city: props.school?.city ?? '',
  timezone: props.school?.timezone ?? '',
  motto: props.school?.motto ?? '',
  address: props.school?.address ?? '',
  academic_year: props.school?.academic_year ?? '',
  school_type: String(setting('profile', 'school_type') ?? ''),
  affiliation_board: String(setting('profile', 'affiliation_board') ?? ''),
  medium_of_instruction: String(setting('profile', 'medium_of_instruction') ?? ''),
  legal_name: String(setting('profile', 'legal_name') ?? ''),
}))

watch(
  () => route.query.section,
  () => {
    /* reactive via computed */
  },
)

function openEditor() {
  editOpen.value = true
}

defineExpose({ openEditor })

onMounted(loadSettings)
</script>

<template>
  <div class="space-y-6">
    <!-- Identity header -->
    <header class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
      <div class="flex min-w-0 items-start gap-3">
        <div class="relative size-14 shrink-0 overflow-hidden rounded-2xl bg-muted ring-1 ring-border/70">
          <img
            v-if="logoUrl"
            :src="logoUrl"
            alt=""
            class="size-full object-cover"
          />
          <Building2 v-else class="m-auto mt-4 size-6 text-muted-foreground" aria-hidden="true" />
          <span
            v-if="isVerified"
            class="absolute -bottom-1 -right-1 flex size-5 items-center justify-center rounded-full bg-chart-2 text-white ring-2 ring-background"
            title="Verified"
          >
            <CheckCircle2 class="size-3.5" aria-hidden="true" />
          </span>
        </div>
        <div class="min-w-0 space-y-1">
          <div class="flex flex-wrap items-center gap-2">
            <h2 class="truncate text-xl font-semibold tracking-tight text-foreground">
              {{ displayValue(school?.name, 'School profile') }}
            </h2>
            <Badge v-if="isPublished" variant="success">Published</Badge>
            <Badge v-if="isVerified" variant="outline" class="gap-1">
              <Shield class="size-3" aria-hidden="true" />
              Verified institution
            </Badge>
          </div>
          <p class="max-w-2xl text-sm text-muted-foreground">
            Everything the system prints, sends or publishes on the school&apos;s behalf is drawn from this profile.
          </p>
        </div>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <Button type="button" variant="outline" size="icon" aria-label="More actions">
          <MoreHorizontal class="size-4" aria-hidden="true" />
        </Button>
        <Button type="button" variant="default" :disabled="saving" @click="editOpen = true">
          <Pencil class="mr-2 size-4" aria-hidden="true" />
          Edit information
        </Button>
      </div>
    </header>

    <!-- Metrics -->
    <div
      class="grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-7"
      role="list"
    >
      <div
        v-for="metric in metrics"
        :key="metric.label"
        class="min-w-0 rounded-xl border border-border/60 bg-muted/30 px-3 py-2.5"
        role="listitem"
      >
        <p class="text-[10px] font-semibold uppercase tracking-wide text-muted-foreground">
          {{ metric.label }}
        </p>
        <p
          :class="cn(
            'mt-1 truncate text-sm font-semibold',
            metric.tone === 'success' ? 'text-chart-2' : 'text-foreground',
          )"
        >
          {{ metric.value }}
        </p>
      </div>
    </div>

    <PageLoader v-if="settingsLoading" label="Loading profile configuration" />

    <div v-else class="grid gap-6 lg:grid-cols-[16rem_minmax(0,1fr)]">
      <!-- Section nav -->
      <aside class="min-w-0 space-y-4 xl:sticky xl:top-4 xl:self-start">
        <nav
          class="rounded-2xl border border-border/70 bg-card p-2 shadow-[var(--shadow-soft)]"
          aria-label="School profile sections"
        >
          <!-- Mobile / tablet: single-row chip scroller (no stacked group rows that collide) -->
          <div class="flex gap-1.5 overflow-x-auto pb-1 lg:hidden" role="list">
            <button
              v-for="section in SCHOOL_PROFILE_SECTIONS"
              :key="`m-${section.id}`"
              type="button"
              role="listitem"
              :class="cn(
                'inline-flex shrink-0 items-center gap-1.5 rounded-xl px-3 py-2 text-sm whitespace-nowrap transition-colors',
                activeSection === section.id
                  ? 'bg-primary/10 font-medium text-primary ring-1 ring-primary/20'
                  : 'text-muted-foreground hover:bg-muted/50 hover:text-foreground',
              )"
              :aria-current="activeSection === section.id ? 'page' : undefined"
              @click="selectSection(section.id)"
            >
              <component :is="section.icon" class="size-4 shrink-0" aria-hidden="true" />
              {{ section.label }}
            </button>
          </div>

          <!-- Desktop: stacked groups -->
          <div class="hidden space-y-3 lg:block">
            <div v-for="group in groupedNav" :key="group.id" class="space-y-1">
              <p class="px-2 pt-1 text-[10px] font-semibold uppercase tracking-wide text-muted-foreground">
                {{ group.label }}{{ group.sections.length }}
              </p>
              <button
                v-for="section in group.sections"
                :key="section.id"
                type="button"
                :class="cn(
                  'flex w-full items-center gap-2 rounded-xl px-3 py-2 text-left text-sm transition-colors',
                  activeSection === section.id
                    ? 'border-l-2 border-primary bg-primary/10 font-medium text-primary'
                    : 'text-muted-foreground hover:bg-muted/50 hover:text-foreground',
                )"
                :aria-current="activeSection === section.id ? 'page' : undefined"
                @click="selectSection(section.id)"
              >
                <component :is="section.icon" class="size-4 shrink-0" aria-hidden="true" />
                <span class="min-w-0 truncate">{{ section.label }}</span>
              </button>
            </div>
          </div>
        </nav>

        <div class="hidden rounded-2xl border border-border/70 bg-card p-4 shadow-[var(--shadow-soft)] lg:block">
          <p class="text-[10px] font-semibold uppercase tracking-wide text-muted-foreground">
            Profile Completeness
          </p>
          <p class="mt-2 text-2xl font-semibold tabular-nums">{{ completeness.percent }}%</p>
          <div class="mt-2 h-2 overflow-hidden rounded-full bg-muted">
            <div
              class="h-full rounded-full bg-primary transition-all"
              :style="{ width: `${completeness.percent}%` }"
            />
          </div>
          <p class="mt-2 text-xs text-muted-foreground">{{ completeness.hint }}</p>
        </div>
      </aside>

      <!-- Detail -->
      <div class="min-w-0 space-y-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div class="min-w-0 space-y-1">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-primary">
              {{ activeMeta.group }} / {{ activeMeta.title }}
            </p>
            <h3 class="text-lg font-semibold text-foreground">{{ activeMeta.title }}</h3>
            <p class="text-sm text-muted-foreground">{{ activeMeta.subtitle }}</p>
          </div>
          <div class="flex flex-wrap gap-2">
            <Button
              v-if="activeSection === 'general' || activeSection === 'identity'"
              type="button"
              variant="outline"
              size="sm"
              @click="editOpen = true"
            >
              <Pencil class="mr-2 size-3.5" aria-hidden="true" />
              Edit
            </Button>
            <Button
              v-if="activeSection === 'social'"
              type="button"
              variant="outline"
              size="sm"
              @click="openSocialEditor"
            >
              Manage links
            </Button>
            <Button
              v-if="activeSection === 'audit'"
              type="button"
              variant="outline"
              size="sm"
              :disabled="!auditEvents.length"
              @click="exportAudit"
            >
              <Download class="mr-2 size-3.5" aria-hidden="true" />
              Export log
            </Button>
            <label
              v-if="activeSection === 'documents'"
              class="inline-flex h-8 cursor-pointer items-center gap-2 rounded-xl border border-border px-3 text-sm font-medium hover:bg-muted/40"
            >
              <Upload class="size-3.5" aria-hidden="true" />
              Upload document
              <input
                type="file"
                class="sr-only"
                accept=".pdf,.doc,.docx,.png,.jpg,.jpeg,.zip"
                @change="onDocumentUpload(($event.target as HTMLInputElement).files)"
              />
            </label>
          </div>
        </div>

        <!-- GENERAL -->
        <ProfileFieldList
          v-if="activeSection === 'general'"
          title="General Information"
          description="The core record every other module reads from"
          :status-label="isPublished ? 'Published' : undefined"
          :fields="generalFields"
        />

        <!-- IDENTITY -->
        <ProfileFieldList
          v-else-if="activeSection === 'identity'"
          title="School Identity"
          description="Legal and brand identity fields"
          :fields="identityFields"
        />

        <!-- BRANDING -->
        <div v-else-if="activeSection === 'branding'" class="space-y-6">
          <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <article
              v-for="asset in [
                { kind: 'logo' as const, label: 'School Logo', path: school?.logo_path },
                { kind: 'dark' as const, label: 'Dark Logo', path: setting('branding', 'dark_logo_path') },
                { kind: 'compact' as const, label: 'Compact Logo', path: setting('branding', 'compact_logo_path') },
                { kind: 'favicon' as const, label: 'Favicon', path: setting('branding', 'favicon_path') },
              ]"
              :key="asset.kind"
              class="rounded-2xl border border-border/70 bg-card p-3 shadow-[var(--shadow-soft)]"
            >
              <div class="flex h-24 items-center justify-center rounded-xl bg-[repeating-conic-gradient(#f3f4f6_0%_25%,#fff_0%_50%)] bg-size-[12px_12px] dark:bg-muted">
                <img
                  v-if="resolveMediaUrl(String(asset.path ?? ''))"
                  :src="resolveMediaUrl(String(asset.path ?? ''))!"
                  :alt="asset.label"
                  class="max-h-16 max-w-full object-contain"
                />
                <span v-else class="text-xs text-muted-foreground">Not set</span>
              </div>
              <div class="mt-3 flex items-center justify-between gap-2">
                <div class="min-w-0">
                  <p class="truncate text-sm font-medium">{{ asset.label }}</p>
                  <p class="text-xs text-muted-foreground">{{ asset.path ? 'Uploaded' : 'No file' }}</p>
                </div>
                <label class="inline-flex size-8 cursor-pointer items-center justify-center rounded-lg border border-border hover:bg-muted/40">
                  <Upload class="size-3.5" aria-hidden="true" />
                  <span class="sr-only">Upload {{ asset.label }}</span>
                  <input
                    type="file"
                    class="sr-only"
                    accept="image/*,.svg"
                    :disabled="uploadingLogo"
                    @change="onLogoUpload(asset.kind, ($event.target as HTMLInputElement).files?.[0] ?? null)"
                  />
                </label>
              </div>
            </article>
          </div>

          <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            <article
              v-for="colour in brandingColours"
              :key="colour.key"
              class="flex items-center gap-3 rounded-2xl border border-border/70 bg-card p-3 shadow-[var(--shadow-soft)]"
            >
              <div
                class="size-12 shrink-0 rounded-xl ring-1 ring-border/60"
                :style="{ background: colour.value || 'var(--muted)' }"
                aria-hidden="true"
              />
              <div class="min-w-0 flex-1">
                <p class="text-sm font-medium">{{ colour.label }}</p>
                <p class="font-mono text-xs text-muted-foreground">{{ colour.value || 'Not set' }}</p>
                <p class="text-xs text-muted-foreground">{{ colour.hint }}</p>
              </div>
              <Button type="button" variant="ghost" size="icon" :aria-label="`Edit ${colour.label}`" @click="openColourEdit(colour.key, colour.value)">
                <Pencil class="size-4" aria-hidden="true" />
              </Button>
            </article>
          </div>

          <section class="rounded-2xl border border-border/70 bg-card p-4 shadow-[var(--shadow-soft)]">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
              <div>
                <h4 class="text-sm font-semibold">Where branding is applied</h4>
                <p class="text-xs text-muted-foreground">Surfaces that draw from the assets above</p>
              </div>
              <Badge variant="warning">
                {{ brandingSurfaces.filter((s) => !s.applied).length }} not set
              </Badge>
            </div>
            <ul class="divide-y divide-border/60" role="list">
              <li
                v-for="surface in brandingSurfaces"
                :key="surface.label"
                class="flex flex-wrap items-center justify-between gap-2 py-3"
              >
                <div class="min-w-0">
                  <p class="text-sm font-medium">{{ surface.label }}</p>
                  <p class="text-xs text-muted-foreground">{{ surface.hint }}</p>
                </div>
                <Badge :variant="surface.applied ? 'success' : 'warning'">
                  {{ surface.applied ? 'Applied' : 'Not set' }}
                </Badge>
              </li>
            </ul>
          </section>
        </div>

        <!-- CONTACT -->
        <div v-else-if="activeSection === 'contact'" class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
          <article
            v-for="card in contactCards"
            :key="card.title"
            class="rounded-2xl border border-border/70 bg-card p-4 shadow-[var(--shadow-soft)]"
          >
            <h4 class="text-sm font-semibold">{{ card.title }}</h4>
            <ul class="mt-3 space-y-1.5 text-sm text-muted-foreground">
              <li v-for="(line, idx) in card.lines" :key="idx" class="break-words">{{ line }}</li>
            </ul>
            <Button type="button" variant="link" class="mt-3 h-auto px-0" @click="editOpen = true">
              <Pencil class="mr-1 size-3.5" aria-hidden="true" />
              Edit
            </Button>
          </article>
        </div>

        <!-- ADDRESS -->
        <div v-else-if="activeSection === 'address'" class="grid grid-cols-1 gap-3 md:grid-cols-3">
          <article
            v-for="card in addressCards"
            :key="card.title"
            class="rounded-2xl border border-border/70 bg-card p-4 shadow-[var(--shadow-soft)]"
          >
            <component :is="card.icon" class="size-5 text-primary" aria-hidden="true" />
            <h4 class="mt-3 text-sm font-semibold">{{ card.title }}</h4>
            <p class="mt-2 text-sm break-words text-muted-foreground">{{ card.body }}</p>
            <Button type="button" variant="link" class="mt-3 h-auto px-0" @click="editOpen = true">
              <Pencil class="mr-1 size-3.5" aria-hidden="true" />
              Edit
            </Button>
          </article>
        </div>

        <!-- SOCIAL -->
        <div v-else-if="activeSection === 'social'" class="grid grid-cols-1 gap-3 md:grid-cols-2">
          <article
            v-for="card in socialCards"
            :key="card.id"
            class="flex items-center gap-3 rounded-2xl border border-border/70 bg-card p-4 shadow-[var(--shadow-soft)]"
          >
            <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-muted">
              <component :is="card.icon" class="size-4" aria-hidden="true" />
            </div>
            <div class="min-w-0 flex-1">
              <p class="text-sm font-semibold">{{ card.label }}</p>
              <p class="truncate text-xs text-muted-foreground">{{ displayValue(card.value) }}</p>
            </div>
            <Badge
              :variant="filled(card.value) ? (card.live ? 'success' : 'success') : 'warning'"
            >
              {{ filled(card.value) ? (card.live ? 'Live' : 'Connected') : 'Not linked' }}
            </Badge>
          </article>
        </div>

        <!-- COMMUNICATION -->
        <ProfileFieldList
          v-else-if="activeSection === 'communication'"
          title="Sender & Templates"
          description="Applied to email, SMS and printed letters"
          :status-label="communicationFields.filter((f) => f.set === false).length ? `${communicationFields.filter((f) => f.set === false).length} not set` : 'Configured'"
          :status-tone="communicationFields.some((f) => f.set === false) ? 'warning' : 'success'"
          :fields="communicationFields"
        />

        <!-- ACADEMIC -->
        <ProfileFieldList
          v-else-if="activeSection === 'academic'"
          title="Academic Configuration"
          description="Structural settings for timetables, terms and assessment"
          :status-label="academicFields.filter((f) => f.set).length >= 3 ? 'Configured' : 'Incomplete'"
          :status-tone="academicFields.filter((f) => f.set).length >= 3 ? 'success' : 'warning'"
          :fields="academicFields"
        />

        <!-- CAMPUS -->
        <ProfileFieldList
          v-else-if="activeSection === 'campus'"
          title="Campus, Languages & Time"
          description="Physical and regional settings"
          :status-label="`${campusFields.length} fields`"
          status-tone="muted"
          :fields="campusFields"
        >
          <template #footer>
            <div class="flex justify-end">
              <Button type="button" variant="ghost" size="icon" aria-label="Edit campus settings" @click="editOpen = true">
                <Pencil class="size-4" aria-hidden="true" />
              </Button>
            </div>
          </template>
        </ProfileFieldList>

        <!-- MANAGEMENT -->
        <div v-else-if="activeSection === 'management'" class="space-y-4">
          <EmptyState
            v-if="!managementPeople.length"
            title="No management contacts"
            description="Add principal details in Edit School Information to populate this list."
          />
          <div v-else class="grid grid-cols-1 gap-3 md:grid-cols-2">
            <article
              v-for="(person, idx) in managementPeople"
              :key="idx"
              class="rounded-2xl border border-border/70 bg-card p-4 shadow-[var(--shadow-soft)]"
            >
              <div class="flex items-start justify-between gap-2">
                <div>
                  <p class="text-[10px] font-semibold uppercase tracking-wide text-primary">
                    {{ person.role || 'Role' }}
                  </p>
                  <p class="mt-1 text-sm font-semibold">{{ person.name || 'Not set' }}</p>
                  <p class="mt-1 text-xs text-muted-foreground">
                    {{ [person.email, person.phone].filter(Boolean).join(' · ') || 'No contact' }}
                  </p>
                </div>
                <Badge variant="success">{{ person.status || 'In office' }}</Badge>
              </div>
            </article>
          </div>
          <ProfileFieldList title="Trust & Committee" :fields="trustFields" />
        </div>

        <!-- COMPLIANCE -->
        <div v-else-if="activeSection === 'compliance'">
          <EmptyState
            v-if="!complianceItems.length"
            title="No compliance records"
            description="Add affiliation and licence items via school settings when available. Nothing is invented for demo."
          />
          <div v-else class="grid grid-cols-1 gap-3 md:grid-cols-2">
            <article
              v-for="(item, idx) in complianceItems"
              :key="idx"
              class="rounded-2xl border border-border/70 border-l-4 bg-card p-4 shadow-[var(--shadow-soft)]"
              :class="item.status === 'expiring' ? 'border-l-[var(--warning)]' : 'border-l-chart-2'"
            >
              <h4 class="text-sm font-semibold">{{ item.title || 'Item' }}</h4>
              <p class="mt-1 text-xs text-muted-foreground">{{ item.description }}</p>
              <p class="mt-3 rounded-lg bg-muted px-3 py-2 text-center font-mono text-sm">
                {{ item.number || '—' }}
              </p>
              <div class="mt-3 flex items-center justify-between text-xs">
                <span class="text-muted-foreground">{{ item.valid_to || '' }}</span>
                <Badge :variant="item.status === 'expiring' ? 'warning' : 'success'">
                  {{ item.status_label || item.status || 'Valid' }}
                </Badge>
              </div>
            </article>
          </div>
        </div>

        <!-- DOCUMENTS -->
        <div v-else-if="activeSection === 'documents'">
          <EmptyState
            v-if="!documentItems.length"
            title="No documents uploaded"
            description="Upload certificates and packs referenced by Compliance."
          />
          <div v-else class="grid grid-cols-1 gap-3 md:grid-cols-2">
            <article
              v-for="(doc, idx) in documentItems"
              :key="idx"
              class="flex items-center gap-3 rounded-2xl border border-border/70 bg-card p-4 shadow-[var(--shadow-soft)]"
            >
              <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-destructive/10 text-destructive">
                <span class="text-[10px] font-bold">FILE</span>
              </div>
              <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold">{{ doc.name }}</p>
                <p class="text-xs text-muted-foreground">
                  {{ doc.mime || 'File' }}
                  <template v-if="doc.uploaded_at">
                    · {{ new Date(doc.uploaded_at).toLocaleDateString() }}
                  </template>
                </p>
              </div>
              <a
                v-if="doc.url || doc.path"
                :href="resolveMediaUrl(doc.url || doc.path) || '#'"
                class="inline-flex size-8 items-center justify-center rounded-lg border border-border hover:bg-muted/40"
                download
                :aria-label="`Download ${doc.name}`"
              >
                <Download class="size-3.5" aria-hidden="true" />
              </a>
            </article>
          </div>
        </div>

        <!-- SECURITY -->
        <ProfileFieldList
          v-else-if="activeSection === 'security'"
          title="Access & Retention"
          description="Who may change this profile, and how long records are kept"
          :fields="securityFields"
        />

        <!-- AUDIT -->
        <div v-else-if="activeSection === 'audit'" class="rounded-2xl border border-border/70 bg-card p-4 shadow-[var(--shadow-soft)] sm:p-6">
          <EmptyState
            v-if="!auditEvents.length"
            title="No audit events"
            description="Events appear when the school record is created or updated."
          />
          <ol v-else class="relative space-y-6 border-l border-border/70 pl-6" role="list">
            <li v-for="(event, idx) in auditEvents" :key="idx" class="relative">
              <span
                class="absolute -left-[1.625rem] top-1 size-3 rounded-full border-2 border-primary bg-background"
                aria-hidden="true"
              />
              <p class="text-sm font-semibold">{{ event.title }}</p>
              <p class="text-sm text-muted-foreground">{{ event.detail }}</p>
              <p class="mt-1 text-xs text-muted-foreground">{{ event.when }}</p>
            </li>
          </ol>
        </div>
      </div>
    </div>

    <EditSchoolDialog
      v-model:open="editOpen"
      :saving="saving"
      :initial="editInitial"
      @save="onEditSave"
    />

    <Dialog v-model:open="socialOpen">
      <DialogContent class="max-h-[90dvh] overflow-y-auto sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Manage social links</DialogTitle>
          <DialogDescription>Public handles linked from the school profile.</DialogDescription>
        </DialogHeader>
        <div class="grid gap-3">
          <div v-for="key in (['facebook', 'twitter', 'instagram', 'youtube', 'linkedin'] as const)" :key="key" class="space-y-2">
            <Label :for="`social-${key}`">{{ key }}</Label>
            <Input :id="`social-${key}`" v-model="socialDraft[key]" />
          </div>
        </div>
        <DialogFooter>
          <Button type="button" variant="outline" @click="socialOpen = false">Cancel</Button>
          <Button type="button" :disabled="saving" @click="saveSocial">Save</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <Dialog :open="colourEditKey != null" @update:open="(v: boolean) => { if (!v) colourEditKey = null }">
      <DialogContent class="sm:max-w-sm">
        <DialogHeader>
          <DialogTitle>Edit colour</DialogTitle>
          <DialogDescription>Stored in school branding settings.</DialogDescription>
        </DialogHeader>
        <div class="space-y-2">
          <Label for="brand-colour">Hex colour</Label>
          <div class="flex gap-2">
            <Input id="brand-colour" v-model="colourDraft" class="font-mono" />
            <input v-model="colourDraft" type="color" class="h-9 w-12 cursor-pointer rounded-md border border-border bg-transparent" aria-label="Pick colour" />
          </div>
        </div>
        <DialogFooter>
          <Button type="button" variant="outline" @click="colourEditKey = null">Cancel</Button>
          <Button type="button" :disabled="saving" @click="saveColour">Save</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>
