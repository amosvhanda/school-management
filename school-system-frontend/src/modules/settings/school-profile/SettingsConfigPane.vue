<script setup lang="ts">
import { onMounted, reactive, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { ChevronRight, RefreshCw, Save, School } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { useToast } from '@/composables/useToast'
import { getErrorMessage } from '@/lib/api-response'
import { cn } from '@/lib/utils'
import { schoolApi } from '@/services/api.service'
import { buildSettingsSetupNav } from '@/modules/settings/school-profile/settings-setup-nav'

const props = defineProps<{
  title: string
  /** @deprecated Page chrome lives on SettingsView PageShell */
  breadcrumb?: string
  activeNavLabel: string
  /** Prefix for prefs keys, e.g. `class_settings` → class_settings_enabled */
  settingsPrefix: string
  schoolName?: string | null
  schoolEmail?: string | null
  principalName?: string | null
}>()

const emit = defineEmits<{ saved: [] }>()

const toast = useToast()
const saving = ref(false)
const loading = ref(true)

const navItems = buildSettingsSetupNav(props.activeNavLabel)

const form = reactive({
  contact: '',
  support_email: '',
  enabled: true,
  notify: true,
  require_approval: false,
  notes: '',
})

function prefKey(suffix: string) {
  return `${props.settingsPrefix}_${suffix}`
}

async function loadPrefs() {
  loading.value = true
  try {
    const settings = (await schoolApi.settings()) as Record<string, Record<string, unknown>>
    const prefs = settings?.prefs ?? {}
    form.contact = String(prefs[prefKey('contact')] ?? props.principalName ?? '')
    form.support_email = String(prefs[prefKey('support_email')] ?? props.schoolEmail ?? '')
    form.enabled = prefs[prefKey('enabled')] !== false
    form.notify = prefs[prefKey('notify')] !== false
    form.require_approval = prefs[prefKey('require_approval')] === true
    form.notes = String(prefs[prefKey('notes')] ?? '')
  } catch (err) {
    toast.error(`Could not load ${props.title.toLowerCase()}`, getErrorMessage(err))
  } finally {
    loading.value = false
  }
}

async function save() {
  saving.value = true
  try {
    await schoolApi.updateSettings([
      { group: 'prefs', key: prefKey('enabled'), value: form.enabled },
      { group: 'prefs', key: prefKey('notify'), value: form.notify },
      { group: 'prefs', key: prefKey('require_approval'), value: form.require_approval },
      { group: 'prefs', key: prefKey('notes'), value: form.notes || null },
      { group: 'prefs', key: prefKey('contact'), value: form.contact || null },
      { group: 'prefs', key: prefKey('support_email'), value: form.support_email || null },
    ])
    toast.success(`${props.title} saved`)
    emit('saved')
  } catch (err) {
    toast.error('Save failed', getErrorMessage(err))
  } finally {
    saving.value = false
  }
}

watch(
  () => [props.schoolEmail, props.principalName],
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
                <School class="size-4 shrink-0" aria-hidden="true" />
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
              <Label :for="`${settingsPrefix}-institution`">Institution</Label>
              <Input :id="`${settingsPrefix}-institution`" :model-value="schoolName ?? ''" readonly />
            </div>
            <div class="space-y-2">
              <Label :for="`${settingsPrefix}-contact`">Primary Contact</Label>
              <Input :id="`${settingsPrefix}-contact`" v-model="form.contact" autocomplete="name" />
            </div>
            <div class="space-y-2 sm:col-span-2">
              <Label :for="`${settingsPrefix}-email`">Support Email</Label>
              <Input
                :id="`${settingsPrefix}-email`"
                v-model="form.support_email"
                type="email"
                autocomplete="email"
              />
            </div>
          </div>

          <div class="space-y-3">
            <label class="flex cursor-pointer items-start justify-between gap-4 rounded-xl border border-border/60 px-4 py-3">
              <span class="min-w-0 space-y-0.5">
                <span class="block text-sm font-medium">Enable {{ title }}</span>
                <span class="block text-xs text-muted-foreground">
                  Turn this configuration on or off for the institution.
                </span>
              </span>
              <Checkbox
                :checked="form.enabled"
                :aria-label="`Enable ${title}`"
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
            <Label :for="`${settingsPrefix}-notes`">Notes</Label>
            <Textarea
              :id="`${settingsPrefix}-notes`"
              v-model="form.notes"
              rows="3"
              placeholder="Internal notes for this setting"
            />
          </div>

          <div class="flex flex-col-reverse gap-2 border-t border-border/60 pt-4 sm:flex-row sm:justify-end">
            <Button type="button" variant="outline" class="w-full sm:w-auto" :disabled="saving" @click="loadPrefs">
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
