<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import {
  Check,
  CheckCircle2,
  CircleDashed,
  DoorOpen,
  Save,
  SlidersHorizontal,
  Tags,
} from '@lucide/vue'
import PageShell from '@/components/layout/PageShell.vue'
import FormBuilder from '@/components/forms/FormBuilder.vue'
import { useFormBuilder } from '@/components/forms/useFormBuilder'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import SchoolSetupSection from '@/modules/settings/components/SchoolSetupSection.vue'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
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
  tabById,
  type SetupTabId,
} from '@/modules/settings/school-setup-tabs'

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
  classes: number
  rooms: number
  subjects: number
  feeStructures: number
  timetable: number
}

const toast = useToast()
const { loadSchool } = useSchoolProfile()
const loading = ref(true)
const error = ref<string | null>(null)
const currencyLocked = ref(false)
const currentSchool = ref<SchoolProfile | null>(null)
const formResetValues = ref<Record<string, unknown> | undefined>()
const activeTab = ref<SetupTabId>('profile')
const setupCounts = ref<SetupCounts>({
  terms: 0,
  classes: 0,
  rooms: 0,
  subjects: 0,
  feeStructures: 0,
  timetable: 0,
})

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
const activeListKey = computed(() => activeTabConfig.value.listKey)
const isProfileTab = computed(() => activeTab.value === 'profile')

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
    href: '/settings/custom-fields',
    icon: SlidersHorizontal,
  },
  {
    title: 'Rooms',
    description: 'Set up classrooms and specialist learning spaces.',
    tabId: 'rooms' as SetupTabId,
    icon: DoorOpen,
  },
  {
    title: 'Fee structures',
    description: 'Define the charges used by invoices and student accounts.',
    tabId: 'fees' as SetupTabId,
    icon: Tags,
  },
])

function selectTab(tabId: SetupTabId) {
  activeTab.value = tabId
}

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
  const [terms, classes, rooms, subjects, feeStructures, timetable] = await Promise.all([
    fetchList(moduleEndpoints.terms, { all: true }).catch(() => []),
    fetchList(moduleEndpoints.classes, { all: true }).catch(() => []),
    fetchList(moduleEndpoints.rooms, { all: true }).catch(() => []),
    fetchList(moduleEndpoints.subjects, { all: true }).catch(() => []),
    fetchList(moduleEndpoints.feeStructures, { all: true }).catch(() => []),
    fetchList(moduleEndpoints.timetable, { all: true }).catch(() => []),
  ])

  setupCounts.value = {
    terms: Array.isArray(terms) ? terms.length : 0,
    classes: Array.isArray(classes) ? classes.length : 0,
    rooms: Array.isArray(rooms) ? rooms.length : 0,
    subjects: Array.isArray(subjects) ? subjects.length : 0,
    feeStructures: Array.isArray(feeStructures) ? feeStructures.length : 0,
    timetable: Array.isArray(timetable) ? timetable.length : 0,
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
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
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
        <Card class="overflow-hidden border-border/70 shadow-sm">
          <div v-show="isProfileTab">
            <CardHeader class="border-b border-border/60 bg-gradient-to-r from-background via-background to-muted/40 px-6 py-6">
              <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div class="space-y-2">
                  <CardTitle class="text-xl">School Profile</CardTitle>
                  <CardDescription class="max-w-2xl text-sm leading-relaxed">
                    Basic information about your school that defines your institution and powers invoices,
                    communications, and public-facing school details.
                  </CardDescription>
                </div>

                <div class="flex items-center gap-4 rounded-2xl border border-border/60 bg-background px-5 py-4">
                  <div class="inline-flex size-16 shrink-0 items-center justify-center rounded-full bg-muted text-xl font-semibold text-muted-foreground">
                    {{ String(currentSchool?.name ?? 'SC').slice(0, 2).toUpperCase() }}
                  </div>
                  <div class="space-y-1.5">
                    <p class="text-sm font-medium text-foreground">Upload School Logo</p>
                    <p class="text-xs text-muted-foreground">
                      Recommended: 500x500px, PNG or JPG, max 2MB
                    </p>
                  </div>
                </div>
              </div>
            </CardHeader>

            <CardContent class="px-6 py-6">
              <form
                id="school-setup-form"
                class="space-y-6"
                novalidate
                :aria-busy="isSubmitting"
                @submit.prevent="onSubmit"
              >
                <FormBuilder
                  form-key="school-settings"
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
            </CardContent>
          </div>

          <CardContent v-if="!isProfileTab && activeListKey" class="px-6 py-6">
            <KeepAlive>
              <SchoolSetupSection
                :key="activeTab"
                :list-key="activeListKey"
                @saved="loadSetupCounts"
              />
            </KeepAlive>
          </CardContent>
        </Card>

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
