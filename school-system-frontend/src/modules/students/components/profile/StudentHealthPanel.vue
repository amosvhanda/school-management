<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { AlertTriangle, HeartPulse, Phone, Syringe } from '@lucide/vue'
import EmptyState from '@/components/feedback/EmptyState.vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { operationsApi } from '@/services/api.service'
import { formatDate } from '@/lib/format'
import { STATUS_TONE_BADGE, STATUS_TONE_ICON } from '@/lib/ui-status'
import { cn } from '@/lib/utils'

const props = defineProps<{
  studentId: string | number
  emergencyName?: string | null
  emergencyPhone?: string | null
}>()

const loading = ref(false)
const profile = ref<Record<string, unknown> | null>(null)
const visits = ref<Record<string, unknown>[]>([])

const allergies = computed(() => {
  const raw = profile.value?.allergies ?? profile.value?.allergy_notes
  if (Array.isArray(raw)) return raw.map(String)
  if (typeof raw === 'string' && raw.trim()) return [raw]
  return [] as string[]
})

const conditions = computed(() => {
  const raw = profile.value?.conditions ?? profile.value?.medical_conditions
  if (Array.isArray(raw)) return raw.map(String)
  if (typeof raw === 'string' && raw.trim()) return [raw]
  return [] as string[]
})

const vitals = computed(() => [
  { label: 'Height', value: profile.value?.height ?? profile.value?.height_cm ?? '—', tone: STATUS_TONE_ICON.danger },
  { label: 'Weight', value: profile.value?.weight ?? profile.value?.weight_kg ?? '—', tone: STATUS_TONE_ICON.info },
  { label: 'BMI', value: profile.value?.bmi ?? '—', tone: STATUS_TONE_ICON.success },
  { label: 'Blood group', value: profile.value?.blood_group ?? profile.value?.blood_type ?? '—', tone: STATUS_TONE_ICON.warning },
  { label: 'Vision', value: profile.value?.vision ?? '—', tone: STATUS_TONE_ICON.warning },
  { label: 'Dental', value: profile.value?.dental ?? '—', tone: STATUS_TONE_ICON.accent },
])

const immunizations = computed(() => {
  const raw = profile.value?.immunizations ?? profile.value?.vaccinations
  return Array.isArray(raw) ? (raw as Record<string, unknown>[]) : []
})

const hasEmergency = computed(() => allergies.value.length > 0 || conditions.value.length > 0)

async function load() {
  loading.value = true
  try {
    profile.value = (await operationsApi.health.profile(props.studentId)) as Record<string, unknown>
  } catch {
    profile.value = null
  }
  try {
    const rows = (await operationsApi.health.visits.list({
      filter: { student_id: props.studentId },
      per_page: 10,
    } as never)) as Record<string, unknown>[]
    visits.value = Array.isArray(rows) ? rows : []
  } catch {
    visits.value = []
  } finally {
    loading.value = false
  }
}

watch(() => props.studentId, load)
onMounted(load)
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
      <div class="flex items-center gap-2">
        <HeartPulse class="size-4 text-primary" aria-hidden="true" />
        <h2 class="text-base font-semibold">Health & wellness</h2>
      </div>
      <p v-if="profile?.updated_at" class="text-xs text-muted-foreground">
        Last update {{ formatDate(String(profile.updated_at)) }}
      </p>
    </div>

    <PageLoader v-if="loading" label="Loading health profile" />

    <template v-else>
      <div
        v-if="hasEmergency"
        class="flex flex-col gap-3 rounded-2xl border border-destructive/30 bg-destructive/5 p-4 sm:flex-row sm:items-start"
        role="alert"
      >
        <div :class="cn('flex size-11 shrink-0 items-center justify-center rounded-2xl', STATUS_TONE_ICON.danger)">
          <AlertTriangle class="size-5" aria-hidden="true" />
        </div>
        <div class="min-w-0 flex-1 space-y-2">
          <p class="text-sm font-semibold">Emergency protocol · health alert</p>
          <p v-if="allergies.length" class="text-sm text-muted-foreground">
            Allergies:
            <span class="font-semibold text-destructive">{{ allergies.join(', ') }}</span>
          </p>
          <p v-if="conditions.length" class="text-sm text-muted-foreground">
            Conditions: {{ conditions.join(', ') }}
          </p>
          <div v-if="emergencyName || emergencyPhone" class="flex flex-wrap items-center gap-2 pt-1 text-xs">
            <span class="font-medium uppercase tracking-wide text-muted-foreground">Emergency contact</span>
            <span>{{ emergencyName || '—' }}</span>
            <span v-if="emergencyPhone">· {{ emergencyPhone }}</span>
            <Button
              v-if="emergencyPhone"
              type="button"
              size="sm"
              variant="outline"
              class="h-7 rounded-lg"
              as-child
            >
              <a :href="`tel:${emergencyPhone}`">
                <Phone class="mr-1 size-3.5" aria-hidden="true" />
                Call
              </a>
            </Button>
          </div>
        </div>
      </div>

      <section class="space-y-3">
        <h3 class="flex items-center gap-2 text-sm font-semibold">
          <HeartPulse class="size-4 text-muted-foreground" aria-hidden="true" />
          Vitals & measurements
        </h3>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
          <Card v-for="item in vitals" :key="item.label">
            <CardContent class="flex items-center gap-3 p-4">
              <div :class="cn('flex size-10 items-center justify-center rounded-xl', item.tone)" aria-hidden="true">
                <HeartPulse class="size-4" />
              </div>
              <div>
                <p class="text-lg font-semibold tabular-nums">{{ item.value }}</p>
                <p class="text-xs text-muted-foreground">{{ item.label }}</p>
              </div>
            </CardContent>
          </Card>
        </div>
      </section>

      <Card>
        <CardHeader class="border-b border-border/50 px-5 py-4">
          <CardTitle class="flex items-center gap-2 text-base">
            <Syringe class="size-4" aria-hidden="true" />
            Immunization history
          </CardTitle>
        </CardHeader>
        <CardContent class="p-0">
          <Table v-if="immunizations.length">
            <TableHeader>
              <TableRow>
                <TableHead>Vaccine</TableHead>
                <TableHead>Dose</TableHead>
                <TableHead>Date</TableHead>
                <TableHead>Status</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="(row, i) in immunizations" :key="i">
                <TableCell>{{ row.name ?? row.vaccine ?? '—' }}</TableCell>
                <TableCell>{{ row.dose ?? '—' }}</TableCell>
                <TableCell>{{ formatDate(String(row.date ?? row.given_at ?? '')) }}</TableCell>
                <TableCell>
                  <Badge :class="cn('border font-normal', STATUS_TONE_BADGE.success)">
                    {{ row.status ?? 'Completed' }}
                  </Badge>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
          <div v-else class="p-5">
            <EmptyState
              variant="embedded"
              title="No immunizations on file"
              description="Vaccination records will appear when added to the health profile."
            />
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader class="border-b border-border/50 px-5 py-4">
          <CardTitle class="text-base">Allergies & conditions</CardTitle>
        </CardHeader>
        <CardContent class="space-y-2 p-5">
          <template v-if="allergies.length || conditions.length">
            <div
              v-for="item in allergies"
              :key="`a-${item}`"
              class="flex items-center justify-between gap-3 rounded-xl border-l-4 border-l-destructive bg-card px-4 py-3 ring-1 ring-border/60"
            >
              <div>
                <p class="text-sm font-semibold">{{ item }}</p>
                <p class="text-xs text-muted-foreground">Allergy</p>
              </div>
              <Badge :class="cn('border', STATUS_TONE_BADGE.danger)">High</Badge>
            </div>
            <div
              v-for="item in conditions"
              :key="`c-${item}`"
              class="flex items-center justify-between gap-3 rounded-xl border-l-4 border-l-chart-3 bg-card px-4 py-3 ring-1 ring-border/60"
            >
              <div>
                <p class="text-sm font-semibold">{{ item }}</p>
                <p class="text-xs text-muted-foreground">Condition</p>
              </div>
              <Badge :class="cn('border', STATUS_TONE_BADGE.warning)">Monitor</Badge>
            </div>
          </template>
          <EmptyState
            v-else
            variant="embedded"
            title="No allergies or conditions listed"
            description="Health alerts for this student will show here."
          />
        </CardContent>
      </Card>

      <Card v-if="visits.length">
        <CardHeader class="border-b border-border/50 px-5 py-4">
          <CardTitle class="text-base">Recent clinic visits</CardTitle>
        </CardHeader>
        <CardContent class="space-y-2 p-5">
          <div
            v-for="(visit, i) in visits.slice(0, 5)"
            :key="String(visit.id ?? i)"
            class="rounded-xl border border-border/60 px-4 py-3 text-sm"
          >
            <p class="font-medium">{{ visit.complaint ?? visit.reason ?? 'Clinic visit' }}</p>
            <p class="text-xs text-muted-foreground">{{ formatDate(String(visit.visit_date ?? visit.created_at ?? '')) }}</p>
          </div>
        </CardContent>
      </Card>
    </template>
  </div>
</template>
