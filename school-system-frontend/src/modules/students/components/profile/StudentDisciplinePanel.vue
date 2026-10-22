<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { Ban, MessageCircle, ShieldAlert, ThumbsUp, TriangleAlert } from '@lucide/vue'
import EmptyState from '@/components/feedback/EmptyState.vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import StudentMetricRing from '@/modules/students/components/StudentMetricRing.vue'
import { hrApi } from '@/services/api.service'
import { formatDate } from '@/lib/format'
import { STATUS_TONE_BADGE, STATUS_TONE_ICON } from '@/lib/ui-status'
import { cn } from '@/lib/utils'

const props = defineProps<{
  studentId: string | number
}>()

const loading = ref(false)
const records = ref<Record<string, unknown>[]>([])

const positives = computed(() =>
  records.value.filter((r) => {
    const t = String(r.type ?? r.category ?? r.severity ?? '').toLowerCase()
    return t.includes('positive') || t.includes('commend') || Number(r.points ?? 0) > 0
  }),
)
const warnings = computed(() =>
  records.value.filter((r) => String(r.type ?? r.severity ?? '').toLowerCase().includes('warn')),
)
const suspensions = computed(() =>
  records.value.filter((r) => String(r.type ?? r.severity ?? '').toLowerCase().includes('suspend')),
)

const conductScore = computed(() => {
  const base = 100
  const penalty = warnings.value.length * 5 + suspensions.value.length * 20
  const bonus = Math.min(10, positives.value.length * 2)
  return Math.max(0, Math.min(100, base - penalty + bonus))
})

async function load() {
  loading.value = true
  try {
    const rows = (await hrApi.discipline.list({
      filter: { student_id: props.studentId },
      per_page: 50,
    } as never)) as Record<string, unknown>[]
    records.value = Array.isArray(rows) ? rows : []
  } catch {
    records.value = []
  } finally {
    loading.value = false
  }
}

watch(() => props.studentId, load)
onMounted(load)
</script>

<template>
  <div class="space-y-6">
    <PageLoader v-if="loading" label="Loading discipline records" />

    <div class="grid gap-4 lg:grid-cols-[minmax(0,16rem)_minmax(0,1fr)]">
      <Card>
        <CardContent class="flex flex-col items-center gap-3 p-6">
          <StudentMetricRing
            label="Conduct score"
            :value-label="String(conductScore)"
            :percent="conductScore"
            tone="success"
          />
          <p class="text-center text-xs text-muted-foreground">
            {{ conductScore >= 90 ? 'Excellent behaviour this term.' : 'Based on disciplinary records on file.' }}
          </p>
        </CardContent>
      </Card>
      <div class="grid gap-3 sm:grid-cols-2">
        <Card class="border-l-4 border-l-chart-2">
          <CardContent class="flex items-center gap-3 p-4">
            <div :class="cn('flex size-10 items-center justify-center rounded-xl', STATUS_TONE_ICON.success)">
              <ThumbsUp class="size-5" aria-hidden="true" />
            </div>
            <div>
              <p class="text-2xl font-semibold tabular-nums">{{ positives.length }}</p>
              <p class="text-xs text-muted-foreground">Positive points</p>
            </div>
          </CardContent>
        </Card>
        <Card class="border-l-4 border-l-chart-3">
          <CardContent class="flex items-center gap-3 p-4">
            <div :class="cn('flex size-10 items-center justify-center rounded-xl', STATUS_TONE_ICON.warning)">
              <TriangleAlert class="size-5" aria-hidden="true" />
            </div>
            <div>
              <p class="text-2xl font-semibold tabular-nums">{{ warnings.length }}</p>
              <p class="text-xs text-muted-foreground">Warnings</p>
            </div>
          </CardContent>
        </Card>
        <Card class="border-l-4 border-l-destructive">
          <CardContent class="flex items-center gap-3 p-4">
            <div :class="cn('flex size-10 items-center justify-center rounded-xl', STATUS_TONE_ICON.danger)">
              <Ban class="size-5" aria-hidden="true" />
            </div>
            <div>
              <p class="text-2xl font-semibold tabular-nums">{{ suspensions.length }}</p>
              <p class="text-xs text-muted-foreground">Suspensions</p>
            </div>
          </CardContent>
        </Card>
        <Card class="border-l-4 border-l-chart-4">
          <CardContent class="flex items-center gap-3 p-4">
            <div :class="cn('flex size-10 items-center justify-center rounded-xl', STATUS_TONE_ICON.info)">
              <MessageCircle class="size-5" aria-hidden="true" />
            </div>
            <div>
              <p class="text-2xl font-semibold tabular-nums">{{ records.length }}</p>
              <p class="text-xs text-muted-foreground">Total reports</p>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>

    <Card>
      <CardHeader class="border-b border-border/50 px-5 py-4">
        <CardTitle class="flex items-center gap-2 text-base">
          <ShieldAlert class="size-4" aria-hidden="true" />
          Behaviour reports
        </CardTitle>
      </CardHeader>
      <CardContent class="space-y-3 p-5">
        <template v-if="records.length">
          <article
            v-for="(row, i) in records.slice(0, 12)"
            :key="String(row.id ?? i)"
            :class="cn(
              'flex flex-col gap-2 rounded-2xl border border-border/60 px-4 py-3 sm:flex-row sm:items-center sm:justify-between',
              'border-l-4',
              Number(row.points ?? 0) > 0 ? 'border-l-chart-2' : 'border-l-chart-3',
            )"
          >
            <div class="min-w-0">
              <p class="truncate text-sm font-semibold">
                {{ row.title ?? row.reason ?? row.description ?? 'Behaviour record' }}
              </p>
              <p class="text-xs text-muted-foreground">
                {{ formatDate(String(row.incident_date ?? row.created_at ?? '')) }}
                <span v-if="row.severity || row.type"> · {{ row.severity ?? row.type }}</span>
              </p>
            </div>
            <Badge
              :class="cn(
                'w-fit border font-normal capitalize',
                Number(row.points ?? 0) > 0 ? STATUS_TONE_BADGE.success : STATUS_TONE_BADGE.warning,
              )"
            >
              <template v-if="row.points != null">{{ Number(row.points) > 0 ? '+' : '' }}{{ row.points }} pts</template>
              <template v-else>{{ row.severity ?? row.status ?? 'Logged' }}</template>
            </Badge>
          </article>
        </template>
        <EmptyState
          v-else
          variant="embedded"
          title="No behaviour records"
          description="Disciplinary and positive behaviour reports will appear here."
        />
      </CardContent>
    </Card>
  </div>
</template>
