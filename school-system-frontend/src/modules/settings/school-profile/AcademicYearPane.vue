<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { CalendarDays, ChevronRight, RefreshCw, Save, School } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Checkbox } from '@/components/ui/checkbox'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { useToast } from '@/composables/useToast'
import { useAcademicYearFilter } from '@/composables/useAcademicYearFilter'
import { schoolApi } from '@/services/api.service'
import { getErrorMessage } from '@/lib/api-response'
import { buildSettingsSetupNav } from '@/modules/settings/school-profile/settings-setup-nav'
import { cn } from '@/lib/utils'

const props = defineProps<{
  schoolName?: string | null
  schoolEmail?: string | null
  principalName?: string | null
  academicYear?: string | null
}>()

const emit = defineEmits<{
  saved: []
}>()

const toast = useToast()
const { options: yearOptions, academicYear: selectedAcademicYear, setAcademicYear } = useAcademicYearFilter()
const saving = ref(false)
const loading = ref(true)

const form = reactive({
  contact: '',
  support_email: '',
  enabled: true,
  notify: true,
  require_approval: false,
  notes: '',
})

const navItems = buildSettingsSetupNav('Academic Year')

const yearSelect = computed({
  get: () => selectedAcademicYear.value || String(props.academicYear ?? '') || yearOptions.value[0] || '',
  set: (v: string) => setAcademicYear(v),
})

async function loadPrefs() {
  loading.value = true
  try {
    const settings = (await schoolApi.settings()) as Record<string, Record<string, unknown>>
    const prefs = settings?.prefs ?? {}
    form.contact = String(prefs.academic_year_contact ?? props.principalName ?? '')
    form.support_email = String(prefs.academic_year_support_email ?? props.schoolEmail ?? '')
    form.enabled = prefs.academic_year_enabled !== false
    form.notify = prefs.academic_year_notify !== false
    form.require_approval = prefs.academic_year_require_approval === true
    form.notes = String(prefs.academic_year_notes ?? '')
    if (props.academicYear) setAcademicYear(String(props.academicYear))
  } catch (err) {
    toast.error('Could not load academic year settings', getErrorMessage(err))
  } finally {
    loading.value = false
  }
}

async function save() {
  saving.value = true
  try {
    await schoolApi.update({
      academic_year: yearSelect.value || null,
    })
    await schoolApi.updateSettings([
      { group: 'prefs', key: 'academic_year_enabled', value: form.enabled },
      { group: 'prefs', key: 'academic_year_notify', value: form.notify },
      {
        group: 'prefs',
        key: 'academic_year_require_approval',
        value: form.require_approval,
      },
      { group: 'prefs', key: 'academic_year_notes', value: form.notes || null },
      { group: 'prefs', key: 'academic_year_contact', value: form.contact || null },
      { group: 'prefs', key: 'academic_year_support_email', value: form.support_email || null },
      { group: 'academic', key: 'academic_year', value: yearSelect.value || null },
    ])
    toast.success('Academic year settings saved')
    emit('saved')
  } catch (err) {
    toast.error('Save failed', getErrorMessage(err))
  } finally {
    saving.value = false
  }
}

function resetForm() {
  void loadPrefs()
}

watch(
  () => [props.academicYear, props.schoolEmail, props.principalName],
  () => {
    if (!loading.value) {
      if (!form.contact && props.principalName) form.contact = String(props.principalName)
      if (!form.support_email && props.schoolEmail) form.support_email = String(props.schoolEmail)
    }
  },
)

onMounted(loadPrefs)
</script>

<template>
  <div class="space-y-4">
    <div class="overflow-hidden rounded-2xl border border-border/70 bg-card shadow-[var(--shadow-soft)]">
      <div class="grid lg:grid-cols-[14rem_minmax(0,1fr)]">
        <nav
          class="border-b border-border/60 p-3 lg:border-b-0 lg:border-r"
          aria-label="General settings"
        >
          <p class="mb-2 px-2 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
            Settings
          </p>
          <ul class="flex gap-1 overflow-x-auto lg:flex-col lg:overflow-visible" role="list">
            <li v-for="item in navItems" :key="item.label" class="shrink-0">
              <RouterLink
                :to="item.href"
                :class="cn(
                  'flex items-center gap-2 rounded-xl px-3 py-2.5 text-sm transition-colors',
                  item.active
                    ? 'bg-primary/10 font-medium text-primary'
                    : 'text-muted-foreground hover:bg-muted/50 hover:text-foreground',
                )"
                :aria-current="item.active ? 'page' : undefined"
              >
                <School v-if="item.label === 'School Profile'" class="size-4 shrink-0" aria-hidden="true" />
                <CalendarDays v-else class="size-4 shrink-0" aria-hidden="true" />
                <span class="min-w-0 flex-1 truncate">{{ item.label }}</span>
                <ChevronRight v-if="item.active" class="size-4 shrink-0" aria-hidden="true" />
              </RouterLink>
            </li>
          </ul>
        </nav>

        <div class="min-w-0 space-y-6 p-4 sm:p-6">
          <div class="flex flex-wrap items-center justify-between gap-2">
            <h3 class="text-base font-semibold">Configuration</h3>
            <Button type="button" variant="outline" size="sm" :disabled="loading" @click="loadPrefs">
              <RefreshCw class="mr-2 size-4" aria-hidden="true" />
              Refresh
            </Button>
          </div>

          <div class="grid gap-4 sm:grid-cols-2">
            <div class="space-y-2">
              <Label for="ay-institution">Institution</Label>
              <Input id="ay-institution" :model-value="schoolName ?? ''" readonly />
            </div>
            <div class="space-y-2">
              <Label for="ay-year">Academic Year</Label>
              <Select v-model="yearSelect">
                <SelectTrigger id="ay-year">
                  <SelectValue placeholder="Select year" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="year in yearOptions" :key="year" :value="year">
                    {{ year }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div class="space-y-2">
              <Label for="ay-contact">Primary Contact</Label>
              <Input id="ay-contact" v-model="form.contact" autocomplete="name" />
            </div>
            <div class="space-y-2">
              <Label for="ay-email">Support Email</Label>
              <Input id="ay-email" v-model="form.support_email" type="email" autocomplete="email" />
            </div>
          </div>

          <div class="space-y-3">
            <label class="flex cursor-pointer items-start justify-between gap-4 rounded-xl border border-border/60 px-4 py-3">
              <span class="min-w-0 space-y-0.5">
                <span class="block text-sm font-medium">Enable Academic Year</span>
                <span class="block text-xs text-muted-foreground">
                  Turn this configuration on or off for the institution.
                </span>
              </span>
              <Checkbox
                :checked="form.enabled"
                aria-label="Enable Academic Year"
                @update:checked="(v: boolean | 'indeterminate') => (form.enabled = v === true)"
              />
            </label>
            <label class="flex cursor-pointer items-start justify-between gap-4 rounded-xl border border-border/60 px-4 py-3">
              <span class="min-w-0 space-y-0.5">
                <span class="block text-sm font-medium">Send notifications</span>
                <span class="block text-xs text-muted-foreground">
                  Notify administrators when this configuration changes.
                </span>
              </span>
              <Checkbox
                :checked="form.notify"
                aria-label="Send notifications"
                @update:checked="(v: boolean | 'indeterminate') => (form.notify = v === true)"
              />
            </label>
            <label class="flex cursor-pointer items-start justify-between gap-4 rounded-xl border border-border/60 px-4 py-3">
              <span class="min-w-0 space-y-0.5">
                <span class="block text-sm font-medium">Require approval</span>
                <span class="block text-xs text-muted-foreground">
                  Changes must be approved before they take effect.
                </span>
              </span>
              <Checkbox
                :checked="form.require_approval"
                aria-label="Require approval"
                @update:checked="(v: boolean | 'indeterminate') => (form.require_approval = v === true)"
              />
            </label>
          </div>

          <div class="space-y-2">
            <Label for="ay-notes">Notes</Label>
            <Textarea
              id="ay-notes"
              v-model="form.notes"
              rows="3"
              placeholder="Internal notes for this setting"
            />
          </div>

          <div class="flex flex-col-reverse gap-2 border-t border-border/60 pt-4 sm:flex-row sm:justify-end">
            <Button type="button" variant="outline" class="w-full sm:w-auto" :disabled="saving" @click="resetForm">
              Cancel
            </Button>
            <Button type="button" class="w-full sm:w-auto" :disabled="saving || loading" @click="save">
              <Save class="mr-2 size-4" aria-hidden="true" />
              {{ saving ? 'Saving…' : 'Save Changes' }}
            </Button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
