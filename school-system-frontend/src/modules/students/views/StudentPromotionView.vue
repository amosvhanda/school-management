<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import {
  ArrowRight,
  CheckCircle2,
  Download,
  GraduationCap,
  Printer,
  RefreshCw,
  TrendingUp,
  UserMinus,
  Users,
} from '@lucide/vue'
import PageShell from '@/components/layout/PageShell.vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import KpiCard from '@/components/dashboard/KpiCard.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { useAcademicYearFilter } from '@/composables/useAcademicYearFilter'
import { useToast } from '@/composables/useToast'
import { getErrorMessage } from '@/lib/api-response'
import { cn } from '@/lib/utils'
import { STATUS_TONE_BADGE } from '@/lib/ui-status'
import { studentsApi } from '@/services/api.service'

const toast = useToast()
const { academicYear, academicYearStart, options: yearOptions, setAcademicYear } = useAcademicYearFilter()

const step = ref(1)
const loading = ref(false)
const running = ref(false)
const eligible = ref(0)
const lastResult = ref<{
  promoted: number
  repeated: number
  graduated: number
  errors: string[]
} | null>(null)

const sourceYear = computed(() => academicYear.value)
const targetYear = computed(() => {
  const start = Number(academicYearStart.value)
  return Number.isFinite(start) ? `${start + 1} / ${start + 2}` : 'Next year'
})

const successRate = computed(() => {
  if (!lastResult.value) return null
  const total = lastResult.value.promoted + lastResult.value.repeated + lastResult.value.graduated
  if (!total) return null
  return Math.round((lastResult.value.promoted / total) * 1000) / 10
})

async function loadPreview() {
  loading.value = true
  try {
    const data = await studentsApi.promotionPreview({
      academic_year: academicYearStart.value,
      next_academic_year: String(Number(academicYearStart.value) + 1),
    })
    eligible.value = Number(data?.eligible ?? 0)
  } catch {
    eligible.value = 0
  } finally {
    loading.value = false
  }
}

async function runPromote() {
  running.value = true
  try {
    const result = (await studentsApi.promote({
      academic_year: academicYearStart.value,
      next_academic_year: String(Number(academicYearStart.value) + 1),
      pass_mark: 50,
    })) as { promoted?: number; repeated?: number; graduated?: number; errors?: string[] }
    lastResult.value = {
      promoted: Number(result?.promoted ?? 0),
      repeated: Number(result?.repeated ?? 0),
      graduated: Number(result?.graduated ?? 0),
      errors: Array.isArray(result?.errors) ? result.errors : [],
    }
    toast.success(
      'Promotion complete',
      `${lastResult.value.promoted} promoted, ${lastResult.value.repeated} repeating, ${lastResult.value.graduated} graduated`,
    )
    step.value = 4
    await loadPreview()
  } catch (err) {
    toast.error('Promotion failed', getErrorMessage(err))
  } finally {
    running.value = false
  }
}

function printPage() {
  window.print()
}

onMounted(loadPreview)
</script>

<template>
  <PageShell
    title="Student Promotion"
    description="Promote learners to the next grade level or class for the new academic year."
    :breadcrumbs="['Students', 'Promotion']"
    max-width="wide"
  >
    <template #actions>
      <Button type="button" variant="outline" size="icon" class="rounded-xl" aria-label="Refresh" @click="loadPreview">
        <RefreshCw class="size-4" />
      </Button>
      <Button type="button" variant="outline" class="rounded-xl" @click="printPage">
        <Printer class="mr-1.5 size-4" />
        Print
      </Button>
      <Button type="button" variant="outline" class="rounded-xl" disabled>
        <Download class="mr-1.5 size-4" />
        Export
      </Button>
    </template>

    <PageLoader v-if="loading && !eligible" label="Loading promotion data" />

    <div v-else class="space-y-6">
      <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <KpiCard title="Academic Year" :value="sourceYear" hint="Session: Term-end Promotion" />
        <KpiCard title="Total Eligible" :value="String(eligible)" hint="Active students" :icon="Users" />
        <KpiCard
          title="Promotion Completed"
          :value="String(lastResult?.promoted ?? 0)"
          :hint="lastResult ? `of ${eligible} eligible` : 'No batch yet'"
          :icon="CheckCircle2"
        />
        <KpiCard
          title="Students Detained"
          :value="String(lastResult?.repeated ?? 0)"
          hint="Held back this term"
          :icon="UserMinus"
        />
        <KpiCard
          title="Graduation Eligible"
          :value="String(lastResult?.graduated ?? 0)"
          hint="Graduated in last run"
          :icon="GraduationCap"
        />
        <KpiCard
          title="Promotion Success"
          :value="successRate != null ? `${successRate}%` : '—'"
          hint="From last batch"
          :icon="TrendingUp"
        />
      </div>

      <div class="rounded-2xl border border-primary/20 bg-primary/5 px-4 py-3 text-sm text-foreground">
        Promoting students moves them to the next class and enrolls them into
        <strong>{{ targetYear }}</strong>. Review eligibility before confirming.
      </div>

      <ol class="grid gap-2 sm:grid-cols-4">
        <li
          v-for="(label, i) in ['Academic Year', 'Class & Section', 'Review Students', 'Confirm']"
          :key="label"
          :class="cn(
            'flex items-center gap-2 rounded-xl border px-3 py-2 text-sm',
            step === i + 1 ? 'border-primary/40 bg-primary/10 font-semibold text-primary' : 'border-border/60 text-muted-foreground',
          )"
        >
          <span class="flex size-6 items-center justify-center rounded-full bg-background text-xs ring-1 ring-border">
            {{ i + 1 }}
          </span>
          {{ label }}
        </li>
      </ol>

      <Card v-if="step === 1">
        <CardHeader>
          <CardTitle>Select academic year</CardTitle>
          <CardDescription>Promotion source session</CardDescription>
        </CardHeader>
        <CardContent class="grid gap-3 sm:grid-cols-3">
          <button
            v-for="year in yearOptions"
            :key="year"
            type="button"
            :class="cn(
              'rounded-2xl border p-4 text-left transition-colors',
              year === sourceYear ? 'border-primary bg-primary/5 ring-1 ring-primary/30' : 'border-border/70 hover:bg-muted/40',
            )"
            @click="setAcademicYear(year)"
          >
            <p class="font-semibold">{{ year }}</p>
            <p class="mt-1 text-xs text-muted-foreground">
              {{ year === sourceYear ? 'Current session — promote from here' : 'Available session' }}
            </p>
            <Badge v-if="year === sourceYear" class="mt-2" variant="success">Current</Badge>
          </button>
          <div class="sm:col-span-3 flex items-center justify-between pt-2">
            <p class="text-sm text-muted-foreground">Promotion source: {{ sourceYear }} → {{ targetYear }}</p>
            <Button type="button" class="rounded-xl" @click="step = 2; loadPreview()">
              Continue
              <ArrowRight class="ml-1.5 size-4" />
            </Button>
          </div>
        </CardContent>
      </Card>

      <Card v-else-if="step === 2">
        <CardHeader>
          <CardTitle>Class & section</CardTitle>
          <CardDescription>Year-end promote runs for all active students in the school.</CardDescription>
        </CardHeader>
        <CardContent class="flex justify-between gap-3">
          <Button type="button" variant="outline" class="rounded-xl" @click="step = 1">Back</Button>
          <Button type="button" class="rounded-xl" @click="step = 3">Continue</Button>
        </CardContent>
      </Card>

      <Card v-else-if="step === 3">
        <CardHeader>
          <CardTitle>Review eligibility</CardTitle>
          <CardDescription>{{ eligible }} active students will be evaluated.</CardDescription>
        </CardHeader>
        <CardContent class="flex justify-between gap-3">
          <Button type="button" variant="outline" class="rounded-xl" @click="step = 2">Back</Button>
          <Button type="button" class="rounded-xl" :disabled="running || eligible < 1" @click="runPromote">
            {{ running ? 'Promoting…' : 'Confirm promotion' }}
          </Button>
        </CardContent>
      </Card>

      <Card v-else>
        <CardHeader>
          <CardTitle>Recent promotions</CardTitle>
          <CardDescription>Latest promotion batch this session</CardDescription>
        </CardHeader>
        <CardContent class="grid gap-3 sm:grid-cols-3">
          <div :class="cn('rounded-xl border p-4', STATUS_TONE_BADGE.success)">
            <p class="text-2xl font-semibold">{{ lastResult?.promoted ?? 0 }}</p>
            <p class="text-xs">Promoted</p>
          </div>
          <div :class="cn('rounded-xl border p-4', STATUS_TONE_BADGE.info)">
            <p class="text-2xl font-semibold">{{ lastResult?.graduated ?? 0 }}</p>
            <p class="text-xs">Graduated</p>
          </div>
          <div :class="cn('rounded-xl border p-4', STATUS_TONE_BADGE.warning)">
            <p class="text-2xl font-semibold">{{ lastResult?.repeated ?? 0 }}</p>
            <p class="text-xs">Detained</p>
          </div>
          <Button type="button" variant="outline" class="rounded-xl sm:col-span-3" @click="step = 1">
            Start another run
          </Button>
        </CardContent>
      </Card>
    </div>
  </PageShell>
</template>
