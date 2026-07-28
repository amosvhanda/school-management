<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import {
  Check,
  CheckCircle2,
  CircleDashed,
  GitBranch,
  Layers,
  Save,
  SlidersHorizontal,
} from '@lucide/vue'
import PageShell from '@/components/layout/PageShell.vue'
import WorkspaceCard from '@/components/layout/WorkspaceCard.vue'
import FormBuilder from '@/components/forms/FormBuilder.vue'
import { useFormBuilder } from '@/components/forms/useFormBuilder'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import SchoolSetupSection from '@/modules/settings/components/SchoolSetupSection.vue'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Checkbox } from '@/components/ui/checkbox'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { useToast } from '@/composables/useToast'
import { useSchoolProfile } from '@/composables/useSchoolProfile'
import { schoolApi } from '@/services/api.service'
import { fetchList, moduleEndpoints } from '@/services'
import { getErrorMessage } from '@/lib/api-response'
import { cn } from '@/lib/utils'
import {
  schoolSettingsFormFields,
  schoolSettingsFormSchema,
} from '@/modules/settings/school-settings-form'
import {
  SETUP_TABS,
  sectionsForTab,
  tabById,
  type SetupTabId,
} from '@/modules/settings/school-setup-tabs'
import { schoolSetupLocation } from '@/modules/settings/school-setup-links'
import { shouldAutoCreateSection } from '@/lib/module-hub'

interface SchoolProfile {
  name?: string
  principal_name?: string
  email?: string
  phone?: string
  website?: string
  year_founded?: number | null
  suburb?: string
  city?: string
  student_capacity?: number | null
  timezone?: string
  motto?: string
  address?: string
  currency?: string
  currency_locked?: boolean
  logo_path?: string
}

interface SetupCounts {
  terms: number
  gradeLevels: number
  classes: number
  streams: number
  rooms: number
  subjects: number
  feeStructures: number
  feeCategories: number
  timetable: number
}

const toast = useToast()
const route = useRoute()
const router = useRouter()
const { loadSchool } = useSchoolProfile()
const loading = ref(true)
const error = ref<string | null>(null)
const currencyLocked = ref(false)
const currentSchool = ref<SchoolProfile | null>(null)
const formResetValues = ref<Record<string, unknown> | undefined>()
const activeTab = ref<SetupTabId>('profile')
const setupCounts = ref<SetupCounts>({
  terms: 0,
  gradeLevels: 0,
  classes: 0,
  streams: 0,
  rooms: 0,
  subjects: 0,
  feeStructures: 0,
  feeCategories: 0,
  timetable: 0,
})

const validTabIds = new Set(SETUP_TABS.map((tab) => tab.id))

function parseSetupTab(value: unknown): SetupTabId | null {
  const tab = String(Array.isArray(value) ? value[0] : value ?? '')
  return validTabIds.has(tab as SetupTabId) ? (tab as SetupTabId) : null
}

const formFields = computed(() =>
  schoolSettingsFormFields({ currencyLocked: currencyLocked.value }),
)

const {
  handleSubmit,
  resetForm,
  applyServerErrors,
  isSubmitting,
} = useFormBuilder(schoolSettingsFormSchema)

watch(
  () => formResetValues.value,
  (values) => {
    resetForm({ values: (values ?? {}) as never })
  },
  { deep: true, immediate: true },
)

const activeTabConfig = computed(() => tabById(activeTab.value))
const activeSections = computed(() => sectionsForTab(activeTabConfig.value))
const isProfileTab = computed(() => activeTab.value === 'profile')
const isNotificationsTab = computed(() => activeTab.value === 'notifications')

const NOTIFICATION_OPTIONS = [
  {
    key: 'email_notices',
    title: 'Allow email notices',
    description: 'Preference for using email when the school sends announcements and notices.',
  },
  {
    key: 'sms_notices',
    title: 'Allow SMS notices',
    description: 'Preference for using SMS for urgent notices when phone numbers are available.',
  },
  {
    key: 'whatsapp_notices',
    title: 'Allow WhatsApp notices',
    description: 'Preference for sending notices via WhatsApp when phone numbers are available.',
  },
  {
    key: 'parent_messages',
    title: 'Parent message alerts',
    description: 'Preference for notifying guardians about new staff message threads.',
  },
  {
    key: 'leave_alerts',
    title: 'Leave request alerts',
    description: 'Preference for alerting approvers when leave requests are submitted.',
  },
] as const

type NotificationKey = (typeof NOTIFICATION_OPTIONS)[number]['key']

const notificationPrefs = ref<Record<NotificationKey, boolean>>({
  email_notices: false,
  sms_notices: false,
  whatsapp_notices: false,
  parent_messages: false,
  leave_alerts: false,
})
const notificationsLoading = ref(false)
const notificationsSaving = ref(false)
const mailSaving = ref(false)
const messagingSaving = ref(false)
const mailForm = ref({
  enabled: false,
  from_address: '',
  from_name: '',
  smtp_host: '',
  smtp_port: '587',
  smtp_username: '',
  smtp_password: '',
  smtp_encryption: 'tls',
})
const smsForm = ref({
  enabled: false,
  provider: 'log',
  twilio_sid: '',
  twilio_token: '',
  twilio_from: '',
  africastalking_username: '',
  africastalking_api_key: '',
  africastalking_from: '',
})
const whatsappForm = ref({
  enabled: false,
  provider: 'log',
  twilio_sid: '',
  twilio_token: '',
  twilio_from: '',
  twilio_content_sid: '',
  use_template: false,
})

const activeSectionMeta = computed(() => {
  switch (activeTab.value) {
    case 'academic-setup':
      return {
        label: 'Terms configured',
        value: String(setupCounts.value.terms),
      }
    case 'grade-levels':
      return {
        label: 'Grade levels',
        value: String(setupCounts.value.gradeLevels),
      }
    case 'classes':
      return {
        label: 'Classes configured',
        value: String(setupCounts.value.classes),
      }
    case 'streams':
      return {
        label: 'Streams configured',
        value: String(setupCounts.value.streams),
      }
    case 'rooms':
      return {
        label: 'Rooms configured',
        value: String(setupCounts.value.rooms),
      }
    case 'subjects':
      return {
        label: 'Subjects configured',
        value: String(setupCounts.value.subjects),
      }
    case 'fees':
      return {
        label: 'Fee structures',
        value: String(setupCounts.value.feeStructures),
      }
    default:
      return null
  }
})

const schoolProfileComplete = computed(() => {
  const school = currentSchool.value
  if (!school) return false
  return [school.name, school.email, school.phone, school.address, school.principal_name].every(
    (value) => String(value ?? '').trim().length > 0,
  )
})

const setupProgressItems = computed(() => [
  {
    tabId: 'profile' as SetupTabId,
    title: 'School Profile',
    complete: schoolProfileComplete.value,
    meta: schoolProfileComplete.value ? 'Complete' : 'Add your core school details',
  },
  {
    tabId: 'academic-setup' as SetupTabId,
    title: 'Academic Terms',
    complete: setupCounts.value.terms > 0,
    meta: setupCounts.value.terms > 0
      ? `${setupCounts.value.terms} term${setupCounts.value.terms === 1 ? '' : 's'} configured`
      : 'No terms added yet',
  },
  {
    tabId: 'grade-levels' as SetupTabId,
    title: 'Grade Levels',
    complete: setupCounts.value.gradeLevels > 0,
    meta: setupCounts.value.gradeLevels > 0
      ? `${setupCounts.value.gradeLevels} level${setupCounts.value.gradeLevels === 1 ? '' : 's'} configured`
      : 'Add Form / Grade levels',
  },
  {
    tabId: 'classes' as SetupTabId,
    title: 'Classes',
    complete: setupCounts.value.classes > 0,
    meta: setupCounts.value.classes > 0
      ? `${setupCounts.value.classes} class${setupCounts.value.classes === 1 ? '' : 'es'} configured`
      : 'Create your first class',
  },
  {
    tabId: 'subjects' as SetupTabId,
    title: 'Subjects',
    complete: setupCounts.value.subjects > 0,
    meta: setupCounts.value.subjects > 0
      ? `${setupCounts.value.subjects} subject${setupCounts.value.subjects === 1 ? '' : 's'} configured`
      : 'Add subjects for teaching',
  },
])

const completionCount = computed(() =>
  setupProgressItems.value.filter((item) => item.complete).length,
)

const completionPercent = computed(() =>
  Math.round((completionCount.value / setupProgressItems.value.length) * 100),
)

const quickLinks = computed(() => [
  {
    title: 'Custom fields',
    description: 'Add school-specific student and staff fields.',
    tabId: 'custom-fields' as SetupTabId,
    icon: SlidersHorizontal,
  },
  {
    title: 'Grade levels',
    description: 'Form 1–4, Grade 1–7, Lower/Upper 6.',
    tabId: 'grade-levels' as SetupTabId,
    icon: Layers,
  },
  {
    title: 'Streams',
    description: 'Sciences, Arts, and other academic streams.',
    tabId: 'streams' as SetupTabId,
    icon: GitBranch,
  },
])

function selectTab(tabId: SetupTabId) {
  activeTab.value = tabId
  void router.replace(schoolSetupLocation(tabId, {
    query: Object.fromEntries(
      Object.entries(route.query).filter(([key]) => key !== 'tab' && key !== 'create'),
    ),
  }))
}

watch(
  () => route.query.tab,
  (tab) => {
    const next = parseSetupTab(tab) ?? 'profile'
    if (next !== activeTab.value) {
      activeTab.value = next
    }
  },
  { immediate: true },
)

watch(
  isNotificationsTab,
  (active) => {
    if (active) void loadNotifications()
  },
  { immediate: true },
)

const onSubmit = handleSubmit(async (values) => {
  try {
    await schoolApi.update(values as Record<string, unknown>)
    toast.success('School settings saved')
    await loadSchool({ force: true })
    await load()
  } catch (err) {
    applyServerErrors(err)
    toast.error('Save failed', getErrorMessage(err))
  }
})

async function loadSetupCounts() {
  const [
    terms,
    gradeLevels,
    classes,
    streams,
    rooms,
    subjects,
    feeStructures,
    feeCategories,
    timetable,
  ] = await Promise.all([
    fetchList(moduleEndpoints.terms, { all: true }).catch(() => []),
    fetchList(moduleEndpoints.gradeLevels, { all: true }).catch(() => []),
    fetchList(moduleEndpoints.classes, { all: true }).catch(() => []),
    fetchList(moduleEndpoints.streams, { all: true }).catch(() => []),
    fetchList(moduleEndpoints.rooms, { all: true }).catch(() => []),
    fetchList(moduleEndpoints.subjects, { all: true }).catch(() => []),
    fetchList(moduleEndpoints.feeStructures, { all: true }).catch(() => []),
    fetchList(moduleEndpoints.feeCategories, { all: true }).catch(() => []),
    fetchList(moduleEndpoints.timetable, { all: true }).catch(() => []),
  ])

  setupCounts.value = {
    terms: Array.isArray(terms) ? terms.length : 0,
    gradeLevels: Array.isArray(gradeLevels) ? gradeLevels.length : 0,
    classes: Array.isArray(classes) ? classes.length : 0,
    streams: Array.isArray(streams) ? streams.length : 0,
    rooms: Array.isArray(rooms) ? rooms.length : 0,
    subjects: Array.isArray(subjects) ? subjects.length : 0,
    feeStructures: Array.isArray(feeStructures) ? feeStructures.length : 0,
    feeCategories: Array.isArray(feeCategories) ? feeCategories.length : 0,
    timetable: Array.isArray(timetable) ? timetable.length : 0,
  }
}

async function loadNotifications() {
  notificationsLoading.value = true
  try {
    const settings = (await schoolApi.settings()) as Record<string, Record<string, unknown>>
    const group = settings?.notifications ?? {}
    for (const option of NOTIFICATION_OPTIONS) {
      notificationPrefs.value[option.key] = group[option.key] === true
    }

    const mail = settings?.mail ?? {}
    mailForm.value = {
      enabled: mail.enabled === true,
      from_address: String(mail.from_address ?? ''),
      from_name: String(mail.from_name ?? ''),
      smtp_host: String(mail.smtp_host ?? ''),
      smtp_port: String(mail.smtp_port ?? '587'),
      smtp_username: String(mail.smtp_username ?? ''),
      smtp_password: '',
      smtp_encryption: String(mail.smtp_encryption ?? 'tls'),
    }

    const sms = settings?.sms ?? {}
    smsForm.value = {
      enabled: sms.enabled === true,
      provider: String(sms.provider ?? 'log'),
      twilio_sid: String(sms.twilio_sid ?? ''),
      twilio_token: '',
      twilio_from: String(sms.twilio_from ?? ''),
      africastalking_username: String(sms.africastalking_username ?? ''),
      africastalking_api_key: '',
      africastalking_from: String(sms.africastalking_from ?? ''),
    }

    const whatsapp = settings?.whatsapp ?? {}
    whatsappForm.value = {
      enabled: whatsapp.enabled === true,
      provider: String(whatsapp.provider ?? 'log'),
      twilio_sid: String(whatsapp.twilio_sid ?? ''),
      twilio_token: '',
      twilio_from: String(whatsapp.twilio_from ?? ''),
      twilio_content_sid: String(whatsapp.twilio_content_sid ?? ''),
      use_template: whatsapp.use_template === true,
    }
  } catch (err) {
    toast.error('Could not load notification settings', getErrorMessage(err))
  } finally {
    notificationsLoading.value = false
  }
}

async function saveNotifications() {
  notificationsSaving.value = true
  try {
    await schoolApi.updateSettings(
      NOTIFICATION_OPTIONS.map((option) => ({
        group: 'notifications',
        key: option.key,
        value: notificationPrefs.value[option.key],
        type: 'boolean',
      })),
    )
    toast.success('Notification settings saved')
  } catch (err) {
    toast.error('Save failed', getErrorMessage(err))
  } finally {
    notificationsSaving.value = false
  }
}

async function saveMailSettings() {
  mailSaving.value = true
  try {
    const payload: Array<{ group: string; key: string; value: unknown; type?: string }> = [
      { group: 'mail', key: 'enabled', value: mailForm.value.enabled, type: 'boolean' },
      { group: 'mail', key: 'from_address', value: mailForm.value.from_address || null },
      { group: 'mail', key: 'from_name', value: mailForm.value.from_name || null },
      { group: 'mail', key: 'smtp_host', value: mailForm.value.smtp_host || null },
      { group: 'mail', key: 'smtp_port', value: Number(mailForm.value.smtp_port || 587), type: 'integer' },
      { group: 'mail', key: 'smtp_username', value: mailForm.value.smtp_username || null },
      { group: 'mail', key: 'smtp_encryption', value: mailForm.value.smtp_encryption || 'tls' },
    ]

    if (mailForm.value.smtp_password.trim()) {
      payload.push({
        group: 'mail',
        key: 'smtp_password',
        value: mailForm.value.smtp_password,
        type: 'encrypted',
      })
    }

    await schoolApi.updateSettings(payload)
    mailForm.value.smtp_password = ''
    toast.success('Email delivery settings saved')
  } catch (err) {
    toast.error('Save failed', getErrorMessage(err))
  } finally {
    mailSaving.value = false
  }
}

async function saveMessagingSettings() {
  messagingSaving.value = true
  try {
    const payload: Array<{ group: string; key: string; value: unknown; type?: string }> = [
      { group: 'sms', key: 'enabled', value: smsForm.value.enabled, type: 'boolean' },
      { group: 'sms', key: 'provider', value: smsForm.value.provider || 'log' },
      { group: 'sms', key: 'twilio_sid', value: smsForm.value.twilio_sid || null },
      { group: 'sms', key: 'twilio_from', value: smsForm.value.twilio_from || null },
      { group: 'sms', key: 'africastalking_username', value: smsForm.value.africastalking_username || null },
      { group: 'sms', key: 'africastalking_from', value: smsForm.value.africastalking_from || null },
      { group: 'whatsapp', key: 'enabled', value: whatsappForm.value.enabled, type: 'boolean' },
      { group: 'whatsapp', key: 'provider', value: whatsappForm.value.provider || 'log' },
      { group: 'whatsapp', key: 'twilio_sid', value: whatsappForm.value.twilio_sid || null },
      { group: 'whatsapp', key: 'twilio_from', value: whatsappForm.value.twilio_from || null },
      { group: 'whatsapp', key: 'twilio_content_sid', value: whatsappForm.value.twilio_content_sid || null },
      { group: 'whatsapp', key: 'use_template', value: whatsappForm.value.use_template, type: 'boolean' },
    ]

    if (smsForm.value.twilio_token.trim()) {
      payload.push({
        group: 'sms',
        key: 'twilio_token',
        value: smsForm.value.twilio_token,
        type: 'encrypted',
      })
    }
    if (smsForm.value.africastalking_api_key.trim()) {
      payload.push({
        group: 'sms',
        key: 'africastalking_api_key',
        value: smsForm.value.africastalking_api_key,
        type: 'encrypted',
      })
    }
    if (whatsappForm.value.twilio_token.trim()) {
      payload.push({
        group: 'whatsapp',
        key: 'twilio_token',
        value: whatsappForm.value.twilio_token,
        type: 'encrypted',
      })
    }

    await schoolApi.updateSettings(payload)
    smsForm.value.twilio_token = ''
    smsForm.value.africastalking_api_key = ''
    whatsappForm.value.twilio_token = ''
    toast.success('SMS & WhatsApp settings saved')
  } catch (err) {
    toast.error('Save failed', getErrorMessage(err))
  } finally {
    messagingSaving.value = false
  }
}

async function load() {
  loading.value = true
  error.value = null
  try {
    const [school] = await Promise.all([
      schoolApi.show() as Promise<SchoolProfile>,
      loadSetupCounts(),
    ])
    currentSchool.value = school
    currencyLocked.value = school.currency_locked === true
    formResetValues.value = {
      name: String(school.name ?? ''),
      principal_name: String(school.principal_name ?? ''),
      email: String(school.email ?? ''),
      phone: String(school.phone ?? ''),
      website: String(school.website ?? ''),
      year_founded: school.year_founded ?? '',
      suburb: String(school.suburb ?? ''),
      city: String(school.city ?? ''),
      student_capacity: school.student_capacity ?? '',
      timezone: String(school.timezone ?? ''),
      currency: school.currency === 'ZWG' ? 'ZWG' : 'USD',
      motto: String(school.motto ?? ''),
      address: String(school.address ?? ''),
    }
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load school settings')
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<template>
  <PageShell
    title="School Setup & Configuration"
    description="Configure all foundational settings for your school in one place."
    max-width="wide"
  >
    <template #actions>
      <Button
        v-if="isProfileTab"
        type="submit"
        form="school-setup-form"
        class="min-w-[10rem]"
        :disabled="loading || isSubmitting"
        :aria-busy="isSubmitting"
      >
        <Save class="mr-2 size-4" aria-hidden="true" />
        {{ isSubmitting ? 'Saving…' : 'Save All Setup' }}
      </Button>
    </template>

    <PageLoader v-if="loading" label="Loading school setup" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />

    <div v-else class="space-y-6">
      <section class="rounded-2xl border border-border/70 bg-card p-4 shadow-sm">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
          <button
            v-for="item in setupProgressItems"
            :key="item.title"
            type="button"
            class="rounded-xl border border-border/60 bg-background px-4 py-3 text-left transition-colors hover:bg-muted/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
            :aria-current="activeTab === item.tabId ? 'step' : undefined"
            @click="selectTab(item.tabId)"
          >
            <div class="flex items-start gap-3">
              <div
                :class="cn(
                  'mt-0.5 inline-flex size-9 items-center justify-center rounded-full border',
                  item.complete
                    ? 'border-emerald-200 bg-emerald-50 text-emerald-600'
                    : 'border-border bg-muted text-muted-foreground',
                )"
              >
                <CheckCircle2 v-if="item.complete" class="size-4" aria-hidden="true" />
                <CircleDashed v-else class="size-4" aria-hidden="true" />
              </div>

              <div class="min-w-0 space-y-1">
                <div class="flex items-center gap-2">
                  <p class="text-sm font-medium text-foreground">{{ item.title }}</p>
                  <Badge :variant="item.complete ? 'secondary' : 'outline'" class="text-[11px]">
                    {{ item.complete ? 'Ready' : 'Pending' }}
                  </Badge>
                </div>
                <p class="text-xs leading-relaxed text-muted-foreground">{{ item.meta }}</p>
              </div>
            </div>
          </button>
        </div>
      </section>

      <section class="overflow-x-auto rounded-2xl border border-border/60 bg-muted/30 p-2">
        <nav class="flex min-w-max items-center gap-2" aria-label="School setup sections">
          <button
            v-for="tab in SETUP_TABS"
            :key="tab.id"
            type="button"
            :class="cn(
              'inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
              activeTab === tab.id
                ? 'bg-background text-foreground shadow-sm'
                : 'text-muted-foreground hover:bg-background/80 hover:text-foreground',
            )"
            :aria-current="activeTab === tab.id ? 'page' : undefined"
            @click="selectTab(tab.id)"
          >
            <component :is="tab.icon" class="size-4" aria-hidden="true" />
            {{ tab.title }}
          </button>
        </nav>
      </section>

      <div class="grid gap-6 xl:grid-cols-[minmax(0,1.7fr)_minmax(20rem,0.9fr)]">
        <WorkspaceCard
          :title="activeTabConfig.title"
          :description="activeTabConfig.description"
        >
          <template v-if="isProfileTab" #meta>
            <div class="inline-flex size-16 shrink-0 items-center justify-center rounded-full bg-muted text-xl font-semibold text-muted-foreground">
              {{ String(currentSchool?.name ?? 'SC').slice(0, 2).toUpperCase() }}
            </div>
            <div class="space-y-1.5">
              <p class="text-sm font-medium text-foreground">Upload School Logo</p>
              <p class="text-xs text-muted-foreground">
                Recommended: 500x500px, PNG or JPG, max 2MB
              </p>
            </div>
          </template>

          <template v-else-if="activeSectionMeta" #meta>
            <div class="inline-flex size-16 shrink-0 items-center justify-center rounded-full bg-muted text-muted-foreground">
              <component :is="activeTabConfig.icon" class="size-7" aria-hidden="true" />
            </div>
            <div class="space-y-1.5">
              <p class="text-sm font-medium text-foreground">{{ activeSectionMeta.label }}</p>
              <p class="text-2xl font-semibold tracking-tight text-foreground">
                {{ activeSectionMeta.value }}
              </p>
            </div>
          </template>

          <div v-if="isProfileTab">
            <form
              id="school-setup-form"
              class="space-y-6"
              novalidate
              :aria-busy="isSubmitting"
              @submit.prevent="onSubmit"
            >
              <FormBuilder
                :fields="formFields"
                :columns="2"
              />

              <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-border/60 bg-muted/30 px-4 py-3">
                <p class="text-sm text-muted-foreground">
                  Fees currency applies to invoices, fee structures, student accounts, store sales, and trip fees.
                </p>
                <div class="flex items-center gap-2 text-sm">
                  <Badge :variant="currencyLocked ? 'secondary' : 'outline'">
                    {{ currencyLocked ? 'Currency locked' : 'Currency editable' }}
                  </Badge>
                  <Button type="submit" :disabled="isSubmitting" variant="outline">
                    <Check class="mr-2 size-4" aria-hidden="true" />
                    {{ isSubmitting ? 'Saving…' : 'Save profile' }}
                  </Button>
                </div>
              </div>
            </form>
          </div>

          <div v-else-if="isNotificationsTab" class="space-y-6">
            <PageLoader v-if="notificationsLoading" label="Loading notification settings" />
            <template v-else>
              <fieldset class="space-y-3">
                <legend class="sr-only">Notification preferences</legend>
                <label
                  v-for="option in NOTIFICATION_OPTIONS"
                  :key="option.key"
                  :for="`notify-${option.key}`"
                  class="flex cursor-pointer items-start gap-3 rounded-xl border border-border/60 px-4 py-3 transition-colors hover:bg-muted/40"
                >
                  <Checkbox
                    :id="`notify-${option.key}`"
                    class="mt-0.5"
                    :checked="notificationPrefs[option.key]"
                    @update:checked="(checked: boolean | 'indeterminate') => (notificationPrefs[option.key] = checked === true)"
                  />
                  <span class="min-w-0 space-y-1">
                    <span class="block text-sm font-medium text-foreground">{{ option.title }}</span>
                    <span class="block text-xs leading-relaxed text-muted-foreground">{{ option.description }}</span>
                  </span>
                </label>
              </fieldset>
              <div class="flex justify-end">
                <Button type="button" :disabled="notificationsSaving" @click="saveNotifications">
                  <Save class="mr-2 size-4" aria-hidden="true" />
                  {{ notificationsSaving ? 'Saving…' : 'Save notifications' }}
                </Button>
              </div>

              <section class="space-y-4 border-t pt-6" aria-labelledby="mail-settings-heading">
                <div class="space-y-1">
                  <h3 id="mail-settings-heading" class="text-sm font-semibold">Email delivery</h3>
                  <p class="text-xs text-muted-foreground">
                    Configure the from-address and optional SMTP server used for school notices and queued emails.
                  </p>
                </div>

                <label class="flex items-start gap-3 rounded-xl border border-border/60 px-4 py-3">
                  <Checkbox
                    id="mail-enabled"
                    class="mt-0.5"
                    :checked="mailForm.enabled"
                    @update:checked="(checked: boolean | 'indeterminate') => (mailForm.enabled = checked === true)"
                  />
                  <span class="space-y-1">
                    <span class="block text-sm font-medium">Use school SMTP</span>
                    <span class="block text-xs text-muted-foreground">
                      When enabled, outbound email uses this school&apos;s SMTP credentials instead of the platform default.
                    </span>
                  </span>
                </label>

                <div class="grid gap-4 sm:grid-cols-2">
                  <div class="space-y-2">
                    <Label for="mail-from-name">From name</Label>
                    <Input id="mail-from-name" v-model="mailForm.from_name" autocomplete="organization" />
                  </div>
                  <div class="space-y-2">
                    <Label for="mail-from-address">From email</Label>
                    <Input id="mail-from-address" v-model="mailForm.from_address" type="email" autocomplete="email" />
                  </div>
                  <div class="space-y-2">
                    <Label for="mail-smtp-host">SMTP host</Label>
                    <Input id="mail-smtp-host" v-model="mailForm.smtp_host" autocomplete="off" />
                  </div>
                  <div class="space-y-2">
                    <Label for="mail-smtp-port">SMTP port</Label>
                    <Input id="mail-smtp-port" v-model="mailForm.smtp_port" inputmode="numeric" />
                  </div>
                  <div class="space-y-2">
                    <Label for="mail-smtp-username">SMTP username</Label>
                    <Input id="mail-smtp-username" v-model="mailForm.smtp_username" autocomplete="off" />
                  </div>
                  <div class="space-y-2">
                    <Label for="mail-smtp-password">SMTP password</Label>
                    <Input
                      id="mail-smtp-password"
                      v-model="mailForm.smtp_password"
                      type="password"
                      autocomplete="new-password"
                      placeholder="Leave blank to keep current password"
                    />
                  </div>
                  <div class="space-y-2 sm:col-span-2">
                    <Label for="mail-smtp-encryption">Encryption</Label>
                    <Select v-model="mailForm.smtp_encryption">
                      <SelectTrigger id="mail-smtp-encryption">
                        <SelectValue placeholder="Select encryption" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="tls">TLS</SelectItem>
                        <SelectItem value="ssl">SSL</SelectItem>
                        <SelectItem value="none">None</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                </div>

                <div class="flex justify-end">
                  <Button type="button" :disabled="mailSaving" @click="saveMailSettings">
                    <Save class="mr-2 size-4" aria-hidden="true" />
                    {{ mailSaving ? 'Saving…' : 'Save email delivery' }}
                  </Button>
                </div>
              </section>

              <section class="space-y-4 border-t pt-6" aria-labelledby="messaging-settings-heading">
                <div class="space-y-1">
                  <h3 id="messaging-settings-heading" class="text-sm font-semibold">SMS & WhatsApp</h3>
                  <p class="text-xs text-muted-foreground">
                    Optional per-school provider credentials. When disabled, the platform defaults from
                    environment variables are used (if enabled).
                  </p>
                </div>

                <label class="flex items-start gap-3 rounded-xl border border-border/60 px-4 py-3">
                  <Checkbox
                    id="sms-enabled"
                    class="mt-0.5"
                    :checked="smsForm.enabled"
                    @update:checked="(checked: boolean | 'indeterminate') => (smsForm.enabled = checked === true)"
                  />
                  <span class="space-y-1">
                    <span class="block text-sm font-medium">Use school SMS credentials</span>
                    <span class="block text-xs text-muted-foreground">
                      Requires &quot;Allow SMS notices&quot; above. Leave blank secrets to keep current values.
                    </span>
                  </span>
                </label>

                <div class="grid gap-4 sm:grid-cols-2">
                  <div class="space-y-2">
                    <Label for="sms-provider">SMS provider</Label>
                    <Select v-model="smsForm.provider">
                      <SelectTrigger id="sms-provider">
                        <SelectValue placeholder="Select provider" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="log">Log (dev)</SelectItem>
                        <SelectItem value="twilio">Twilio</SelectItem>
                        <SelectItem value="africastalking">Africa&apos;s Talking</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                  <div class="space-y-2">
                    <Label for="sms-twilio-from">Twilio from number</Label>
                    <Input id="sms-twilio-from" v-model="smsForm.twilio_from" autocomplete="off" placeholder="+263…" />
                  </div>
                  <div class="space-y-2">
                    <Label for="sms-twilio-sid">Twilio Account SID</Label>
                    <Input id="sms-twilio-sid" v-model="smsForm.twilio_sid" autocomplete="off" />
                  </div>
                  <div class="space-y-2">
                    <Label for="sms-twilio-token">Twilio auth token</Label>
                    <Input
                      id="sms-twilio-token"
                      v-model="smsForm.twilio_token"
                      type="password"
                      autocomplete="new-password"
                      placeholder="Leave blank to keep current token"
                    />
                  </div>
                  <div class="space-y-2">
                    <Label for="sms-at-username">Africa&apos;s Talking username</Label>
                    <Input id="sms-at-username" v-model="smsForm.africastalking_username" autocomplete="off" />
                  </div>
                  <div class="space-y-2">
                    <Label for="sms-at-key">Africa&apos;s Talking API key</Label>
                    <Input
                      id="sms-at-key"
                      v-model="smsForm.africastalking_api_key"
                      type="password"
                      autocomplete="new-password"
                      placeholder="Leave blank to keep current key"
                    />
                  </div>
                  <div class="space-y-2 sm:col-span-2">
                    <Label for="sms-at-from">Africa&apos;s Talking sender ID</Label>
                    <Input id="sms-at-from" v-model="smsForm.africastalking_from" autocomplete="off" />
                  </div>
                </div>

                <label class="flex items-start gap-3 rounded-xl border border-border/60 px-4 py-3">
                  <Checkbox
                    id="whatsapp-enabled"
                    class="mt-0.5"
                    :checked="whatsappForm.enabled"
                    @update:checked="(checked: boolean | 'indeterminate') => (whatsappForm.enabled = checked === true)"
                  />
                  <span class="space-y-1">
                    <span class="block text-sm font-medium">Use school WhatsApp credentials</span>
                    <span class="block text-xs text-muted-foreground">
                      Requires &quot;Allow WhatsApp notices&quot; above.
                    </span>
                  </span>
                </label>

                <div class="grid gap-4 sm:grid-cols-2">
                  <div class="space-y-2">
                    <Label for="wa-provider">WhatsApp provider</Label>
                    <Select v-model="whatsappForm.provider">
                      <SelectTrigger id="wa-provider">
                        <SelectValue placeholder="Select provider" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="log">Log (dev)</SelectItem>
                        <SelectItem value="twilio">Twilio</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                  <div class="space-y-2">
                    <Label for="wa-from">From (whatsapp:+…)</Label>
                    <Input id="wa-from" v-model="whatsappForm.twilio_from" autocomplete="off" />
                  </div>
                  <div class="space-y-2">
                    <Label for="wa-sid">Twilio Account SID</Label>
                    <Input id="wa-sid" v-model="whatsappForm.twilio_sid" autocomplete="off" />
                  </div>
                  <div class="space-y-2">
                    <Label for="wa-token">Twilio auth token</Label>
                    <Input
                      id="wa-token"
                      v-model="whatsappForm.twilio_token"
                      type="password"
                      autocomplete="new-password"
                      placeholder="Leave blank to keep current token"
                    />
                  </div>
                  <div class="space-y-2">
                    <Label for="wa-content-sid">Content template SID</Label>
                    <Input id="wa-content-sid" v-model="whatsappForm.twilio_content_sid" autocomplete="off" />
                  </div>
                  <label class="flex items-start gap-3 self-end rounded-xl border border-border/60 px-4 py-3">
                    <Checkbox
                      id="wa-use-template"
                      class="mt-0.5"
                      :checked="whatsappForm.use_template"
                      @update:checked="(checked: boolean | 'indeterminate') => (whatsappForm.use_template = checked === true)"
                    />
                    <span class="text-sm font-medium">Use Twilio content template</span>
                  </label>
                </div>

                <div class="flex justify-end">
                  <Button type="button" :disabled="messagingSaving" @click="saveMessagingSettings">
                    <Save class="mr-2 size-4" aria-hidden="true" />
                    {{ messagingSaving ? 'Saving…' : 'Save SMS & WhatsApp' }}
                  </Button>
                </div>
              </section>
            </template>
          </div>

          <div v-else class="space-y-8">
            <section
              v-for="(section, index) in activeSections"
              :key="`${activeTab}-${section.listKey}`"
              class="space-y-3"
            >
              <div v-if="section.title || activeSections.length > 1" class="space-y-1">
                <h3 class="text-base font-semibold text-foreground">
                  {{ section.title }}
                </h3>
                <p v-if="section.description" class="text-sm text-muted-foreground">
                  {{ section.description }}
                </p>
              </div>
              <SchoolSetupSection
                :list-key="section.listKey"
                :auto-create="shouldAutoCreateSection(section, index, route.query)"
                @saved="loadSetupCounts"
              />
            </section>
            <p
              v-if="!activeSections.length"
              class="text-sm text-muted-foreground"
              role="status"
            >
              This setup section is not available.
            </p>
          </div>
        </WorkspaceCard>

        <div class="space-y-6">
          <Card class="border-border/70 shadow-sm">
            <CardHeader class="pb-3">
              <CardTitle class="text-base">Setup Progress</CardTitle>
              <CardDescription>
                {{ completionCount }} of {{ setupProgressItems.length }} core setup areas completed.
              </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
              <div class="space-y-2">
                <div class="flex items-center justify-between text-sm">
                  <span class="text-muted-foreground">Completion</span>
                  <span class="font-medium text-foreground">{{ completionPercent }}%</span>
                </div>
                <div class="h-2 overflow-hidden rounded-full bg-muted">
                  <div
                    class="h-full rounded-full bg-primary transition-all"
                    :style="{ width: `${completionPercent}%` }"
                  />
                </div>
              </div>

              <div class="space-y-3">
                <button
                  v-for="item in setupProgressItems"
                  :key="`${item.title}-summary`"
                  type="button"
                  class="flex w-full items-center justify-between gap-3 rounded-xl border border-border/50 px-3 py-2 text-left transition-colors hover:bg-muted/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                  @click="selectTab(item.tabId)"
                >
                  <div class="flex min-w-0 items-center gap-3">
                    <CheckCircle2
                      v-if="item.complete"
                      class="size-4 shrink-0 text-emerald-600"
                      aria-hidden="true"
                    />
                    <CircleDashed
                      v-else
                      class="size-4 shrink-0 text-muted-foreground"
                      aria-hidden="true"
                    />
                    <div class="min-w-0">
                      <p class="truncate text-sm font-medium text-foreground">{{ item.title }}</p>
                      <p class="truncate text-xs text-muted-foreground">{{ item.meta }}</p>
                    </div>
                  </div>
                  <Badge :variant="item.complete ? 'secondary' : 'outline'">
                    {{ item.complete ? 'Done' : 'Open' }}
                  </Badge>
                </button>
              </div>
            </CardContent>
          </Card>

          <Card class="border-border/70 shadow-sm">
            <CardHeader class="pb-3">
              <CardTitle class="text-base">Quick Access</CardTitle>
              <CardDescription>Jump into the next parts of your school setup workspace.</CardDescription>
            </CardHeader>
            <CardContent class="space-y-3">
              <template v-for="link in quickLinks" :key="link.title">
                <RouterLink
                  v-if="'href' in link && link.href"
                  :to="link.href"
                  class="flex items-start gap-3 rounded-xl border border-border/60 px-4 py-3 transition-colors hover:bg-muted/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                >
                  <div class="inline-flex size-9 shrink-0 items-center justify-center rounded-full bg-muted text-muted-foreground">
                    <component :is="link.icon" class="size-4" aria-hidden="true" />
                  </div>
                  <div class="min-w-0">
                    <p class="text-sm font-medium text-foreground">{{ link.title }}</p>
                    <p class="text-xs leading-relaxed text-muted-foreground">{{ link.description }}</p>
                  </div>
                </RouterLink>
                <button
                  v-else-if="'tabId' in link && link.tabId"
                  type="button"
                  class="flex w-full items-start gap-3 rounded-xl border border-border/60 px-4 py-3 text-left transition-colors hover:bg-muted/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                  @click="selectTab(link.tabId)"
                >
                  <div class="inline-flex size-9 shrink-0 items-center justify-center rounded-full bg-muted text-muted-foreground">
                    <component :is="link.icon" class="size-4" aria-hidden="true" />
                  </div>
                  <div class="min-w-0">
                    <p class="text-sm font-medium text-foreground">{{ link.title }}</p>
                    <p class="text-xs leading-relaxed text-muted-foreground">{{ link.description }}</p>
                  </div>
                </button>
              </template>
            </CardContent>
          </Card>

          <Card class="border-border/70 shadow-sm">
            <CardHeader class="pb-3">
              <CardTitle class="text-base">Configuration Snapshot</CardTitle>
              <CardDescription>Current records already configured across your school foundation.</CardDescription>
            </CardHeader>
            <CardContent class="grid grid-cols-2 gap-3">
              <div class="rounded-xl border border-border/60 bg-muted/20 p-4">
                <p class="text-xs uppercase tracking-wide text-muted-foreground">Terms</p>
                <p class="mt-2 text-2xl font-semibold text-foreground">{{ setupCounts.terms }}</p>
              </div>
              <div class="rounded-xl border border-border/60 bg-muted/20 p-4">
                <p class="text-xs uppercase tracking-wide text-muted-foreground">Classes</p>
                <p class="mt-2 text-2xl font-semibold text-foreground">{{ setupCounts.classes }}</p>
              </div>
              <div class="rounded-xl border border-border/60 bg-muted/20 p-4">
                <p class="text-xs uppercase tracking-wide text-muted-foreground">Rooms</p>
                <p class="mt-2 text-2xl font-semibold text-foreground">{{ setupCounts.rooms }}</p>
              </div>
              <div class="rounded-xl border border-border/60 bg-muted/20 p-4">
                <p class="text-xs uppercase tracking-wide text-muted-foreground">Subjects</p>
                <p class="mt-2 text-2xl font-semibold text-foreground">{{ setupCounts.subjects }}</p>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  </PageShell>
</template>
